<?php
require_once __DIR__ . '/../includes/Permissions.php';

class ExplorerModel {
    private $pdo;
    private $nodes;
    private $access = [];
    private $alive = [];
    const TABLES = ['project'=>'projects','program'=>'programs','component'=>'components','activity'=>'extension_activities'];
    const NEXT = ['root'=>'project','project'=>'program','component_group'=>'component','activity_group'=>'activity','component'=>'folder','activity'=>'folder','folder'=>'folder'];
    const GROUPS = ['component_group'=>'Components','activity_group'=>'Activities'];
    const APP_UPLOAD_LIMIT = 104857600;

    public function __construct($pdo = null) { $this->pdo = $pdo ?: db(); }
    public function all() {
        if ($this->nodes === null) {
            $this->nodes = [];
            foreach ($this->pdo->query('SELECT n.*, u.username AS owner FROM explorer_nodes n JOIN users u ON u.id=n.created_by WHERE n.deleted_at IS NULL') as $n) $this->nodes[(int)$n['id']] = $n;
        }
        return $this->nodes;
    }
    public function node($id) {
        $n = $this->all()[(int)$id] ?? null;
        if (!$n) throw new RuntimeException('Item not found', 404);
        return $n;
    }
    public function chain($id) {
        $result = []; $seen = [];
        while ($id) {
            if (isset($seen[$id])) throw new RuntimeException('Invalid folder hierarchy', 409);
            $seen[$id] = true;
            $n = $this->node($id); $result[] = $n; $id = $n['parent_id'];
        }
        return array_reverse($result);
    }
    private function direct($n) {
        if (isset($this->access[$n['id']])) return $this->access[$n['id']];
        if (isset(self::GROUPS[$n['kind']])) {
            foreach (array_reverse($this->chain($n['id'])) as $p) {
                if ($p['kind'] === 'program') return $this->access[$n['id']] = $this->direct($p);
            }
            return $this->access[$n['id']] = false;
        }
        if (isset(self::TABLES[$n['kind']])) {
            $table = self::TABLES[$n['kind']];
            $s = $this->pdo->prepare("SELECT id FROM `$table` WHERE id=? AND deleted_at IS NULL");
            $s->execute([$n['entity_id']]);
            if (!$s->fetchColumn()) return $this->access[$n['id']] = false;
            $method = 'canAccess' . ucfirst($n['kind']);
            return $this->access[$n['id']] = Permissions::$method($n['entity_id']);
        }
        foreach (array_reverse($this->chain($n['id'])) as $p) {
            if (isset(self::TABLES[$p['kind']])) return $this->access[$n['id']] = $this->direct($p);
        }
        return false;
    }
    public function readable($id) {
        try {
            $n = $this->node($id);
            foreach ($this->chain($id) as $ancestor) {
                if (!isset(self::TABLES[$ancestor['kind']])) continue;
                $key = $ancestor['kind'] . ':' . $ancestor['entity_id'];
                if (!array_key_exists($key, $this->alive)) {
                    $table = self::TABLES[$ancestor['kind']];
                    $s = $this->pdo->prepare("SELECT id FROM `$table` WHERE id=? AND deleted_at IS NULL");
                    $s->execute([$ancestor['entity_id']]);
                    $this->alive[$key] = (bool)$s->fetchColumn();
                }
                if (!$this->alive[$key]) return false;
            }
        } catch (RuntimeException $e) { return false; }
        if ($this->direct($n)) return true;
        // Assigned descendants make their organizational ancestors navigable, not their siblings.
        if (isset(self::TABLES[$n['kind']]) || isset(self::GROUPS[$n['kind']])) {
            foreach ($this->all() as $child) {
                if (!isset(self::TABLES[$child['kind']]) || !$this->direct($child)) continue;
                try { foreach ($this->chain($child['id']) as $p) if ($p['id'] == $id) return true; } catch (RuntimeException $e) {}
            }
        }
        return false;
    }
    public function requireRead($id) {
        if ($id && !$this->readable($id)) throw new RuntimeException('You do not have access to this location', 403);
    }
    public function writable($id) {
        if (!$id) return Permissions::isAdmin();
        if (!$this->readable($id)) return false;
        $n = $this->node($id);
        if (Permissions::isAdmin()) return true;
        if (!in_array($n['kind'], ['component_group','activity_group','component','activity','folder','file'], true)) return false;
        if (in_array($n['kind'], ['component_group','activity_group'], true)) return false;
        foreach (array_reverse($this->chain($id)) as $p) {
            if (isset(self::TABLES[$p['kind']])) return Permissions::canUploadDocument($p['kind'], $p['entity_id']);
        }
        return false;
    }
    private function requireWrite($id) {
        if (!$this->writable($id)) throw new RuntimeException('You do not have permission to change this item', 403);
    }
    public function describe($n) {
        $chain = $this->chain($n['id']); array_pop($chain);
        unset($n['path']);
        $n['location'] = implode(' / ', array_merge(['My Drive'], array_column($chain, 'name')));
        $n['writable'] = !isset(self::GROUPS[$n['kind']]) && $this->writable($n['id']);
        $n['item_count'] = count(array_filter($this->all(), fn($x) => $x['parent_id'] == $n['id'] && $this->readable($x['id'])));
        return $n;
    }
    public function listing($id, $search = '') {
        $this->requireRead($id);
        $current = $id ? $this->node($id) : ['id'=>0,'name'=>'My Drive','kind'=>'root'];
        if ($current['kind'] === 'file') throw new RuntimeException('Choose a folder', 400);
        $chain = $id ? $this->chain($id) : [];
        $project = $chain[0]['id'] ?? null;
        $items = [];
        foreach ($this->all() as $n) {
            if ($search === '' && (int)$n['parent_id'] !== (int)$id) continue;
            if ($search !== '' && stripos($n['name'], $search) === false) continue;
            if (!$this->readable($n['id'])) continue;
            if ($search !== '' && $project && $this->chain($n['id'])[0]['id'] != $project) continue;
            $items[] = $this->describe($n);
        }
        $order = ['project'=>10,'program'=>20,'component_group'=>30,'activity_group'=>40,'component'=>50,'activity'=>60,'folder'=>70,'file'=>80];
        usort($items, fn($a,$b) => (($order[$a['kind']] ?? 90) <=> ($order[$b['kind']] ?? 90)) ?: strnatcasecmp($a['name'],$b['name']));
        $create = [];
        $childGroups = [];
        if ($this->writable($id) && $current['kind'] === 'program') {
            foreach ($items as $item) if (isset(self::GROUPS[$item['kind']])) $childGroups[$item['kind']] = $item['id'];
            foreach (['component_group'=>'component','activity_group'=>'activity'] as $groupKind => $nextKind) {
                if (isset($childGroups[$groupKind])) $create[] = ['kind'=>$nextKind,'parent_id'=>$childGroups[$groupKind],'label'=>'New '.$nextKind];
            }
        } elseif ($this->writable($id) && isset(self::NEXT[$current['kind']])) {
            $create[] = ['kind'=>self::NEXT[$current['kind']],'parent_id'=>(int)$current['id'],'label'=>'New '.self::NEXT[$current['kind']]];
        }
        $canUpload = in_array($current['kind'],['component','activity','folder'],true) && $this->writable($id);
        return ['success'=>true,'current'=>$current,'breadcrumbs'=>array_map(fn($n)=>['id'=>$n['id'],'name'=>$n['name']],$chain), 'items'=>$items,'writable'=>$this->writable($id), 'next'=>self::NEXT[$current['kind']] ?? null,'create'=>$create,'upload'=>$canUpload,'storage'=>$this->storageStats()];
    }
    private function iniBytes($value) {
        $value = trim((string)$value);
        if ($value === '' || $value === '-1') return null;
        $unit = strtolower(substr($value, -1));
        $number = (float)$value;
        if ($unit === 'g') $number *= 1024 * 1024 * 1024;
        elseif ($unit === 'm') $number *= 1024 * 1024;
        elseif ($unit === 'k') $number *= 1024;
        return (int)$number;
    }
    private function diskBytes($function, $path) {
        if (!function_exists($function)) return null;
        $bytes = @$function($path);
        return ($bytes === false || $bytes === null) ? null : (int)$bytes;
    }
    public function storageStats() {
        $files = 0; $folders = 0; $used = 0;
        foreach ($this->all() as $n) {
            if (!$this->readable($n['id'])) continue;
            if ($n['kind'] === 'file') { $files++; $used += (int)$n['size']; }
            elseif ($n['kind'] === 'folder') $folders++;
        }
        $uploadMax = $this->iniBytes(ini_get('upload_max_filesize'));
        $postMax = $this->iniBytes(ini_get('post_max_size'));
        $memory = $this->iniBytes(ini_get('memory_limit'));
        $diskTotal = $this->diskBytes('disk_total_space', UPLOAD_PATH);
        $diskFree = $this->diskBytes('disk_free_space', UPLOAD_PATH);
        $limits = array_filter([self::APP_UPLOAD_LIMIT, $uploadMax, $postMax, $diskFree], fn($v) => $v !== null && $v > 0);
        $effective = min($limits);
        return [
            'files'=>$files,
            'folders'=>$folders,
            'used_bytes'=>$used,
            'disk_total_bytes'=>$diskTotal ?? 0,
            'disk_free_bytes'=>$diskFree ?? 0,
            'disk_used_bytes'=>($diskTotal && $diskFree !== null) ? (int)max(0,$diskTotal-$diskFree) : 0,
            'app_upload_limit_bytes'=>self::APP_UPLOAD_LIMIT,
            'upload_max_bytes'=>$uploadMax,
            'post_max_bytes'=>$postMax,
            'memory_limit_bytes'=>$memory,
            'effective_upload_limit_bytes'=>$effective,
        ];
    }
    private function name($name) {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 255 || preg_match('/[\x00-\x1F\x7F\/\\\\]/u', $name) || in_array($name,['.','..'],true)) throw new RuntimeException('Enter a valid name of up to 255 characters',400);
        return $name;
    }
    private function uniqueName($parent,$name,$except=0) {
        $s=$this->pdo->prepare('SELECT id FROM explorer_nodes WHERE parent_id <=> ? AND name=? AND deleted_at IS NULL AND id<>?');
        $s->execute([$parent ?: null,$name,$except]);
        if ($s->fetchColumn()) throw new RuntimeException('An item with that name already exists here',409);
    }
    public function mutate($action,$input,$file=null) {
        // Serialize structural writes so concurrent moves cannot introduce cycles.
        $lock = $this->pdo->query("SELECT GET_LOCK('explorer_structure',10)")->fetchColumn();
        if (!$lock) throw new RuntimeException('Explorer is busy. Please try again.',409);
        $stored = null;
        try {
            $this->pdo->beginTransaction(); $this->nodes=null; $this->access=[];
            $id=(int)($input['id'] ?? 0); $parent=(int)($input['parent_id'] ?? 0);
            if (in_array($action,['create','upload'],true)) {
                $this->requireWrite($parent);
                $kind=$parent ? $this->node($parent)['kind'] : 'root';
                $newKind=$action==='upload' ? 'file' : (self::NEXT[$kind] ?? null);
                if (!$newKind || ($action==='upload' && !in_array($kind,['component','activity','folder'],true))) throw new RuntimeException('This location does not accept that item',400);
                $name=$this->name($action==='upload' ? ($file['name'] ?? '') : ($input['name'] ?? ''));
                $this->uniqueName($parent,$name);
                $entity=null; $mime=null; $size=null; $ext=null;
                if (isset(self::TABLES[$newKind])) {
                    if (!Permissions::isAdmin()) throw new RuntimeException('Only administrators can create organizational folders',403);
                    $table=self::TABLES[$newKind];
                    $fields=['title','created_by', $newKind.'_code'];
                    $values=[$name,Permissions::getUserId(),strtoupper(substr($newKind,0,3)).'-'.bin2hex(random_bytes(8))];
                    if ($newKind==='component') {
                        foreach ($this->chain($parent) as $p) if ($p['kind']==='project') { $fields[]='project_id'; $values[]=$p['entity_id']; }
                    } elseif ($newKind==='activity') {
                        $componentId = null;
                        $projectEntityId = null;
                        foreach ($this->chain($parent) as $p) if ($p['kind']==='project') $projectEntityId = $p['entity_id'];
                        if ($projectEntityId) {
                            $q = $this->pdo->prepare('SELECT id FROM components WHERE project_id=? AND deleted_at IS NULL ORDER BY id LIMIT 1');
                            $q->execute([$projectEntityId]);
                            $componentId = $q->fetchColumn() ?: null;
                        }
                        if (!$componentId) throw new RuntimeException('Create at least one component before adding activities',400);
                        $fields[]='component_id'; $values[]=$componentId;
                    }
                    $this->pdo->prepare("INSERT INTO `$table` (".implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);
                    $entity=$this->pdo->lastInsertId();
                }
                if ($action==='upload') {
                    if (!$file || $file['error']!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Upload failed or exceeds the server limit',400);
                    $size=filesize($file['tmp_name']);
                    if ($size>self::APP_UPLOAD_LIMIT) throw new RuntimeException('Files must be '.$this->formatBytes(self::APP_UPLOAD_LIMIT).' or smaller',400);
                    $free = $this->diskBytes('disk_free_space', UPLOAD_PATH);
                    if ($free !== null && $size > $free) throw new RuntimeException('This upload is larger than the available hosting storage',507);
                    $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
                    $allowed=['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif','webp','mp4','webm','mov','mp3','wav','zip','txt','csv'];
                    if (!in_array($ext,$allowed,true)) throw new RuntimeException('This file format is not supported',400);
                    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    $directory='explorer';
                    foreach ($this->chain($parent) as $p) if (isset(self::TABLES[$p['kind']])) $directory.='/'.$p['kind'].'/'.$p['entity_id'];
                    $directory.='/folder/'.$parent;
                    $result=uploadFile($file,$directory,$allowed);
                    if (!$result['success']) throw new RuntimeException($result['error'],400);
                    $stored=$result['file_path'];
                }
                $s=$this->pdo->prepare('INSERT INTO explorer_nodes(parent_id,name,kind,entity_id,created_by,original_name,path,mime_type,extension,size) VALUES(?,?,?,?,?,?,?,?,?,?)');
                $s->execute([$parent ?: null,$name,$newKind,$entity,Permissions::getUserId(),$action==='upload'?$name:null,$stored,$mime,$ext,$size]);
                $id=(int)$this->pdo->lastInsertId();
                if ($newKind === 'program') {
                    $group = $this->pdo->prepare('INSERT INTO explorer_nodes(parent_id,name,kind,created_by) VALUES(?,?,?,?)');
                    foreach (self::GROUPS as $groupKind => $groupName) $group->execute([$id,$groupName,$groupKind,Permissions::getUserId()]);
                }
            } else {
                $this->requireWrite($id); $n=$this->node($id);
                if (isset(self::GROUPS[$n['kind']])) throw new RuntimeException('This system folder cannot be changed',400);
                if ($action==='rename') {
                    $name=$this->name($input['name'] ?? ''); $this->uniqueName($n['parent_id'],$name,$id);
                    $this->pdo->prepare('UPDATE explorer_nodes SET name=? WHERE id=?')->execute([$name,$id]);
                } elseif ($action==='move') {
                    $this->requireWrite($parent);
                    $targetKind=$parent?$this->node($parent)['kind']:'root';
                    $valid=$n['kind']==='file' ? in_array($targetKind,['component','activity','folder'],true) : (self::NEXT[$targetKind]??null)===$n['kind'];
                    if (!$valid) throw new RuntimeException('Invalid destination for this item type',400);
                    foreach ($parent?$this->chain($parent):[] as $p) if ($p['id']==$id) throw new RuntimeException('A folder cannot move into itself or a descendant',400);
                    // Keep file access tied to the original component/activity. Cross-branch transfers require admin.
                    if (!Permissions::isAdmin()) {
                        $sourceBranch=null; $targetBranch=null;
                        foreach ($this->chain($id) as $p) if (in_array($p['kind'],['component','activity'],true)) $sourceBranch=$p['kind'].':'.$p['id'];
                        foreach ($this->chain($parent) as $p) if (in_array($p['kind'],['component','activity'],true)) $targetBranch=$p['kind'].':'.$p['id'];
                        if ($sourceBranch!==$targetBranch) throw new RuntimeException('Only administrators can move items between components or activities',403);
                    }
                    $this->uniqueName($parent,$n['name'],$id);
                    $this->pdo->prepare('UPDATE explorer_nodes SET parent_id=? WHERE id=?')->execute([$parent ?: null,$id]);
                } elseif ($action==='delete') {
                    $ids=[$id];
                    for ($i=0;$i<count($ids);$i++) foreach ($this->all() as $child) if ($child['parent_id']==$ids[$i]) $ids[]=(int)$child['id'];
                    foreach ($ids as $childId) $this->requireWrite($childId);
                    $this->pdo->prepare('UPDATE explorer_nodes SET deleted_at=NOW() WHERE id IN ('.implode(',',array_fill(0,count($ids),'?')).')')->execute($ids);
                } else throw new RuntimeException('Unknown action',400);
            }
            if ($parent) $this->pdo->prepare('UPDATE explorer_nodes SET updated_at=NOW() WHERE id=?')->execute([$parent]);
            if (isset($n) && $n['parent_id']) $this->pdo->prepare('UPDATE explorer_nodes SET updated_at=NOW() WHERE id=?')->execute([$n['parent_id']]);
            $this->pdo->commit(); $this->nodes=null; $this->access=[];
            auditLog($action,'explorer',$id,'Explorer '.$action);
            return ['success'=>true,'id'=>$id];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if ($stored && is_file(UPLOAD_PATH.$stored)) unlink(UPLOAD_PATH.$stored);
            throw $e;
        } finally { $this->pdo->query("SELECT RELEASE_LOCK('explorer_structure')"); }
    }
    private function formatBytes($bytes) {
        $bytes = (float)$bytes;
        foreach (['B','KB','MB','GB','TB'] as $unit) {
            if ($bytes < 1024 || $unit === 'TB') return rtrim(rtrim(number_format($bytes, 1), '0'), '.') . ' ' . $unit;
            $bytes /= 1024;
        }
    }
}
