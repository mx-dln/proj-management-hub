<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
// Run against a disposable database, never the live project tables.
$connection = Database::getInstance()->getConnection();
$testDatabase = 'explorer_test_' . bin2hex(random_bytes(5));
$connection->exec("CREATE DATABASE `$testDatabase`");
$testPdo = new PDO('mysql:host='.DB_HOST.';dbname='.$testDatabase.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function db() { global $testPdo; return $testPdo; }
function auditLog(...$args) {}
require_once __DIR__ . '/../models/ExplorerModel.php';
function check($value,$message) { if (!$value) throw new RuntimeException($message); echo "PASS: $message\n"; }
function rejected($fn,$message) { try {$fn();} catch (RuntimeException $e) { check(in_array($e->getCode(),[400,403,409],true),$message); return; } throw new RuntimeException('Not rejected: '.$message); }
try {
    $testPdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['users','faculty_profiles','programs','projects','components','extension_activities','program_assignments','project_assignments','component_assignments','activity_assignments'] as $table) {
        $testPdo->exec($connection->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1]);
        $testPdo->exec("INSERT INTO `$table` SELECT * FROM `".DB_NAME."`.`$table`");
    }
    $testPdo->exec(file_get_contents(__DIR__.'/migrations/20260913_explorer.sql'));
    $testPdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $_SESSION=['user_id'=>(int)$testPdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn(),'role'=>'admin'];
    $m=new ExplorerModel(); $parent=0;
    foreach (['project','program'] as $kind) {
        $parent=$m->mutate('create',['parent_id'=>$parent,'name'=>'Test '.$kind])['id'];
        check($m->node($parent)['kind']===$kind,'Create '.$kind);
    }
    $program=$parent;
    $listing=$m->listing($program);
    $componentsGroup=$activitiesGroup=null;
    foreach ($listing['items'] as $item) {
        if ($item['kind']==='component_group') $componentsGroup=$item['id'];
        if ($item['kind']==='activity_group') $activitiesGroup=$item['id'];
    }
    check($componentsGroup && $activitiesGroup,'Program shows Components and Activities containers');
    $component=$m->mutate('create',['parent_id'=>$componentsGroup,'name'=>'Test component'])['id'];
    check($m->node($component)['kind']==='component','Create component in Components');
    check($m->listing($component)['upload'] === true,'Component accepts uploads like an Activity');
    $componentFolder=$m->mutate('create',['parent_id'=>$component,'name'=>'Component Files'])['id'];
    check($m->node($componentFolder)['kind']==='folder','Create normal folder inside component');
    $activity=$m->mutate('create',['parent_id'=>$activitiesGroup,'name'=>'Test activity'])['id'];
    check($m->node($activity)['kind']==='activity','Create activity in Activities');
    $parent=$activity; $folderIds=[];
    for($i=0;$i<72;$i++) { $parent=$m->mutate('create',['parent_id'=>$parent,'name'=>'Level '.$i])['id']; $folderIds[]=$parent; }
    check(count($m->chain($parent))===76,'72 nested folders and complete breadcrumbs');
    $m->mutate('rename',['id'=>$parent,'name'=>"Training O'Brien <notes>"]);
    check(count($m->listing($activity,'Training')['items'])===1,'Partial-name search finds nested folder');
    rejected(fn()=>$m->mutate('move',['id'=>$folderIds[0],'parent_id'=>$parent]),'Reject descendant move');
    rejected(fn()=>$m->mutate('move',['id'=>$parent,'parent_id'=>$parent]),'Reject self move');
    rejected(fn()=>$m->mutate('move',['id'=>$parent,'parent_id'=>0]),'Reject invalid hierarchy');
    $m->mutate('move',['id'=>$parent,'parent_id'=>$activity]);
    check(count($m->chain($parent))===5,'Move folder updates location');
    rejected(fn()=>$m->mutate('create',['parent_id'=>$activity,'name'=>"Training O'Brien <notes>"]),'Reject duplicate names');
    rejected(fn()=>$m->mutate('create',['parent_id'=>$activity,'name'=>'../escape']),'Reject path traversal names');
    $faculty=$testPdo->query('SELECT user_id,id FROM faculty_profiles LIMIT 1')->fetch();
    $testPdo->prepare("INSERT INTO activity_assignments(activity_id,faculty_id,assignment_type,assigned_by) VALUES(?,?,'member',?)")->execute([$m->node($activity)['entity_id'],$faculty['id'],$_SESSION['user_id']]);
    $_SESSION=['role'=>'faculty','user_id'=>$faculty['user_id']];$m=new ExplorerModel();
    check($m->readable($m->chain($activity)[0]['id']),'Assigned faculty can navigate organizational ancestors');
    check($m->writable($parent),'Assigned faculty can manage activity folders');
    $m->mutate('rename',['id'=>$parent,'name'=>'Faculty rename']);
    rejected(fn()=>$m->mutate('create',['parent_id'=>0,'name'=>'Forbidden project']),'Faculty cannot create organizational entities');
    $_SESSION['role']='viewer'; $m=new ExplorerModel();
    check($m->readable($parent),'Viewer can read');
    rejected(fn()=>$m->mutate('rename',['id'=>$parent,'name'=>'Forbidden']),'Viewer cannot write');
    $_SESSION=['role'=>'faculty','user_id'=>999999];$m=new ExplorerModel();
    check(count($m->listing(0)['items'])===0,'Faculty without profile sees no records');
    rejected(fn()=>$m->listing($parent),'Reject unauthorized direct access');
    $_SESSION=['role'=>'admin','user_id'=>(int)$testPdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn()];$m=new ExplorerModel();
    $m->mutate('delete',['id'=>$folderIds[0]]);
    check(!$m->readable($folderIds[1]),'Recursive soft deletion hides descendants');
    check($m->readable($parent),'Moved item survives old parent deletion');
    check((int)$testPdo->query('SELECT COUNT(*) FROM explorer_nodes WHERE deleted_at IS NOT NULL')->fetchColumn()===71,'Soft deletion retains recoverable rows');
    $activityEntity=$m->node($activity)['entity_id'];
    $testPdo->prepare('UPDATE extension_activities SET deleted_at=NOW() WHERE id=?')->execute([$activityEntity]);
    check(!(new ExplorerModel())->readable($parent),'Deleted business activity hides its files and folders');
    echo "All Explorer integration checks passed.\n";
} finally { $connection->exec("DROP DATABASE `$testDatabase`"); }
