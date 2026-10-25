<?php
declare(strict_types=1);

$dir=__DIR__.'/uploads/campaigns3';

function cb3_media_name(string $name): string {
    $name=basename(trim($name));
    return preg_match('/^[a-f0-9]{32}\.(?:jpg|jpeg|png|gif|webp)$/i',$name)?$name:'';
}
function cb3_media_mime(string $file): ?string {
    $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
    return [
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
        'gif'=>'image/gif','webp'=>'image/webp'
    ][$ext]??null;
}

$name=cb3_media_name((string)($_GET['show']??''));
if($name===''){http_response_code(404);exit('IMAGE_NOT_FOUND');}

$root=realpath($dir);
$file=realpath($dir.'/'.$name);
if(!$root||!$file||!is_file($file)||!is_readable($file)){
    http_response_code(404);exit('IMAGE_NOT_FOUND');
}
$prefix=rtrim(str_replace('\\','/',$root),'/').'/';
if(strpos(str_replace('\\','/',$file),$prefix)!==0){
    http_response_code(404);exit('IMAGE_NOT_FOUND');
}
$mime=cb3_media_mime($file);
if(!$mime){http_response_code(404);exit('IMAGE_NOT_FOUND');}

$size=(int)filesize($file);
$mtime=(int)(filemtime($file)?:time());
$etag='"'.sha1($name.'|'.$size.'|'.$mtime).'"';
if(trim((string)($_SERVER['HTTP_IF_NONE_MATCH']??''))===$etag){
    http_response_code(304);exit;
}

header('Content-Type: '.$mime);
header('Content-Length: '.$size);
header('Content-Disposition: inline; filename="'.$name.'"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: '.$etag);
header('Last-Modified: '.gmdate('D, d M Y H:i:s',$mtime).' GMT');
readfile($file);
exit;
