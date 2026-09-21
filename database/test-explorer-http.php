<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Uses only disposable Explorer folders and the existing local demo login.
$base=$argv[1]??'http://127.0.0.1:8088';
if (!preg_match('~^http://127\.0\.0\.1:\d+$~',$base)) throw new RuntimeException('Local preview only');
$curl=curl_init();
curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>'',CURLOPT_TIMEOUT=>20]);
function request($path,$data=null) { global $curl,$base; curl_setopt($curl,CURLOPT_URL,$base.$path);curl_setopt($curl,CURLOPT_POST,$data!==null);if($data!==null)curl_setopt($curl,CURLOPT_POSTFIELDS,$data);$body=curl_exec($curl);return [curl_getinfo($curl,CURLINFO_RESPONSE_CODE),$body]; }
function verify($v,$m) {if(!$v)throw new RuntimeException($m);echo "PASS: $m\n";}
function api($action,$data=null) {[$code,$body]=request('/ajax/drive.php?action='.$action,$data);$json=json_decode($body,true);if($code!==200||empty($json['success']))throw new RuntimeException($body);return $json;}
request('/login.php',['username'=>'admin','password'=>'password123']);
[$code,$html]=request('/index.php?module=explorer');
$dom=new DOMDocument();@$dom->loadHTML($html);$csrf=$dom->getElementById('project-drive')->getAttribute('data-csrf');
verify(strlen($csrf)===64,'Authenticated page and CSRF token');
[$code]=request('/ajax/drive.php?action=create',['name'=>'Bad','parent_id'=>0]);verify($code===403,'Missing CSRF rejected');
$items=api('list')['items'];$id=0;$componentId=0;
foreach($items as $n){
    foreach(api('list&id='.$n['id'])['items'] as $p){
        foreach(api('list&id='.$p['id'])['items'] as $group){
            foreach(api('list&id='.$group['id'])['items'] as $child){
                if($group['kind']==='component_group' && $child['kind']==='component' && !$componentId) $componentId=$child['id'];
                if($group['kind']==='activity_group' && $child['kind']==='activity' && !$id) $id=$child['id'];
                if($id && $componentId) break 4;
            }
        }
    }
}
verify($id>0,'Navigate Project > Program > Activities > Activity');
verify($componentId>0,'Navigate Project > Program > Components > Component');
$componentFolder=api('create',['csrf'=>$csrf,'parent_id'=>$componentId,'name'=>'Explorer HTTP component '.bin2hex(random_bytes(4))])['id'];
try {
    $componentFile=api('upload',['csrf'=>$csrf,'parent_id'=>$componentFolder,'file'=>new CURLFile(__DIR__.'/test-explorer-fixture.txt','text/plain','component-file.txt')])['id'];
    verify(api('list&id='.$componentFolder)['items'][0]['name']==='component-file.txt','Component folders accept uploads');
} finally {api('delete',['csrf'=>$csrf,'id'=>$componentFolder]);}
$folder=api('create',['csrf'=>$csrf,'parent_id'=>$id,'name'=>'Explorer HTTP test '.bin2hex(random_bytes(4))])['id'];
$folder2=null;
try {
    $folder2=api('create',['csrf'=>$csrf,'parent_id'=>$folder,'name'=>'Nested destination'])['id'];
    $file=api('upload',['csrf'=>$csrf,'parent_id'=>$folder,'file'=>new CURLFile(__DIR__.'/test-explorer-fixture.txt','text/plain','training-plan.txt')])['id'];
    verify(count(api('list&id='.$folder)['items'])===2,'Upload appears in its folder');
    [$code,$body]=request('/ajax/drive.php?action=file&id='.$file);verify($code===200&&str_contains($body,'Explorer upload verification'),'Preview returns correct bytes');
    [$code,$body]=request('/ajax/drive.php?action=file&id='.$file.'&download=1');verify($code===200&&str_contains($body,'Explorer upload verification'),'Download returns correct bytes');
    curl_setopt($curl,CURLOPT_HTTPHEADER,['Range: bytes=0-7']);
    [$code,$body]=request('/ajax/drive.php?action=file&id='.$file);verify($code===206&&$body==='Explorer','Byte-range preview supports seeking');
    curl_setopt($curl,CURLOPT_HTTPHEADER,[]);
    api('rename',['csrf'=>$csrf,'id'=>$file,'name'=>'renamed.txt']);
    api('move',['csrf'=>$csrf,'id'=>$file,'parent_id'=>$folder2]);
    verify(api('list&id='.$folder2)['items'][0]['name']==='renamed.txt','Rename and move file');
    [$code]=request('/ajax/drive.php?action=move',['csrf'=>$csrf,'id'=>$folder,'parent_id'=>$folder2]);verify($code===400,'HTTP rejects cyclic move');
    api('delete',['csrf'=>$csrf,'id'=>$file]);
    [$code]=request('/ajax/drive.php?action=file&id='.$file);verify($code===403,'Deleted file cannot be downloaded');
    [$code]=request('/uploads/explorer/.htaccess');verify($code===403,'Direct storage URL blocked');
} finally {api('delete',['csrf'=>$csrf,'id'=>$folder]);}
curl_close($curl);
echo "All HTTP checks passed. Test folders soft-deleted.\n";
