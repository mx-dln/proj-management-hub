<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/ExplorerModel.php';
if (!isLoggedIn()) jsonResponse(['success'=>false,'message'=>'Sign in to continue'],401);
$s=db()->prepare('SELECT role FROM users WHERE id=? AND is_active=1 AND deleted_at IS NULL');
$s->execute([$_SESSION['user_id']]);
$role=$s->fetchColumn();
if (!$role) jsonResponse(['success'=>false,'message'=>'Account unavailable'],403);
$_SESSION['role']=$role;
try {
    $model=new ExplorerModel();
    $action=$_GET['action']??'list';
    $id=(int)($_GET['id']??0);
    if ($action==='list') jsonResponse($model->listing($id,trim($_GET['q']??'')));
    if ($action==='details') { $model->requireRead($id); jsonResponse(['success'=>true,'item'=>$model->describe($model->node($id))]); }
    if ($action==='file') {
        $model->requireRead($id); $n=$model->node($id);
        if ($n['kind']!=='file') throw new RuntimeException('File not found',404);
        $base=realpath(UPLOAD_PATH.'explorer'); $path=realpath(UPLOAD_PATH.$n['path']);
        if (!$base || !$path || !str_starts_with($path,$base.DIRECTORY_SEPARATOR) || !is_file($path)) throw new RuntimeException('File not found',404);
        $safe=['application/pdf','image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm','audio/mpeg','audio/wav','text/plain'];
        $inline=!isset($_GET['download']) && in_array($n['mime_type'],$safe,true);
        header('Content-Type: '.($inline?$n['mime_type']:'application/octet-stream'));
        header('Content-Disposition: '.($inline?'inline':'attachment')."; filename=\"download\"; filename*=UTF-8''".rawurlencode($n['name']));
        header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
        header("Content-Security-Policy: sandbox; default-src 'none'; media-src 'self'; style-src 'unsafe-inline'");
        $size=filesize($path); $start=0; $end=$size-1;
        header('Accept-Ranges: bytes');
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (!preg_match('/^bytes=(\d*)-(\d*)$/',$_SERVER['HTTP_RANGE'],$range) || ($range[1]==='' && $range[2]==='')) {
                http_response_code(416); header('Content-Range: bytes */'.$size); exit;
            }
            if ($range[1]==='') $start=max(0,$size-(int)$range[2]);
            else { $start=(int)$range[1]; if ($range[2]!=='') $end=min($end,(int)$range[2]); }
            if ($start>$end || $start>=$size) { http_response_code(416); header('Content-Range: bytes */'.$size); exit; }
            http_response_code(206); header("Content-Range: bytes $start-$end/$size");
        }
        $remaining=max(0,$end-$start+1);
        header('Content-Length: '.$remaining); session_write_close();
        if ($_SERVER['REQUEST_METHOD']==='HEAD') exit;
        $stream=fopen($path,'rb'); fseek($stream,$start);
        while ($remaining>0 && !feof($stream)) { $chunk=fread($stream,min(1048576,$remaining)); echo $chunk; $remaining-=strlen($chunk); }
        fclose($stream); exit;
    }
    if ($_SERVER['REQUEST_METHOD']!=='POST') throw new RuntimeException('POST required',405);
    if ($action === 'upload') {
        $storage = $model->storageStats();
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > 0 && $contentLength > (int)$storage['effective_upload_limit_bytes']) {
            throw new RuntimeException('File is larger than the current upload limit',413);
        }
    }
    if (empty($_SESSION['explorer_csrf']) || !hash_equals($_SESSION['explorer_csrf'],$_POST['csrf']??'')) throw new RuntimeException('Session expired. Refresh and try again.',403);
    jsonResponse($model->mutate($action,$_POST,$_FILES['file']??null));
} catch (Throwable $e) {
    $code=in_array($e->getCode(),[400,403,404,405,409,413,507],true)?$e->getCode():500;
    if ($code===500) error_log('Explorer: '.$e->getMessage());
    jsonResponse(['success'=>false,'message'=>$code===500?'Explorer could not complete the request. Please try again.':$e->getMessage()],$code);
}
