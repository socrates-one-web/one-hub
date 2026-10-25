<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.view');
$id=(int)($_GET['id']??0);
$d=dms_require_document($id);
$versionId=(int)($_GET['version']??0);
if($versionId){
    $st=db()->prepare('SELECT * FROM document_versions WHERE id=? AND document_id=?');
    $st->execute([$versionId,$id]);
    $v=$st->fetch();
}else{
    $v=dms_latest_version($id);
}
if(!$v){http_response_code(404);exit('Archivo no disponible.');}
$path=dms_safe_path((string)$v['stored_path']);
if(!is_file($path)){http_response_code(404);exit('Archivo físico no disponible.');}

$inline=isset($_GET['inline']) && $_GET['inline']==='1' && dms_is_native_inline_preview($v['mime_type']??null,$v['original_name']??null);
dms_audit($id,$inline?'preview':'download',($inline?'Vista previa ':'Descarga ').'v'.$v['version_no']);
$mime=$inline?(string)($v['mime_type']?:'application/octet-stream'):'application/octet-stream';
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header("Content-Disposition: ".($inline?'inline':'attachment')."; filename*=UTF-8''".rawurlencode((string)$v['original_name']));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($path);
exit;
