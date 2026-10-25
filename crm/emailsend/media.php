<?php
declare(strict_types=1);

$relative=trim((string)($_GET['f']??''));
$relative=str_replace('\\','/',$relative);
$relative=ltrim($relative,'/');

if(!preg_match('~^\d{4}/\d{2}/[a-f0-9]{24}\.(?:jpg|jpeg|png|gif|webp)$~i',$relative)){
    http_response_code(404);exit;
}

$root=realpath(__DIR__.'/uploads');
$file=realpath(__DIR__.'/uploads/'.$relative);
if(!$root||!$file||!is_file($file)||!is_readable($file)){
    http_response_code(404);exit;
}

$rootPrefix=rtrim(str_replace('\\','/',$root),'/').'/';
$fileNorm=str_replace('\\','/',$file);
if(strpos($fileNorm,$rootPrefix)!==0){
    http_response_code(404);exit;
}

$ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
$m=[
    'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
    'gif'=>'image/gif','webp'=>'image/webp'
];
if(!isset($m[$ext])){http_response_code(404);exit;}

$size=(int)filesize($file);
$mtime=(int)(filemtime($file)?:time());
$etag='"'.sha1($relative.'|'.$size.'|'.$mtime).'"';

if(trim((string)($_SERVER['HTTP_IF_NONE_MATCH']??''))===$etag){
    http_response_code(304);exit;
}

header('Content-Type: '.$m[$ext]);
header('Content-Length: '.$size);
header('Content-Disposition: inline; filename="'.basename($file).'"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: '.$etag);
header('Last-Modified: '.gmdate('D, d M Y H:i:s',$mtime).' GMT');
readfile($file);
exit;
