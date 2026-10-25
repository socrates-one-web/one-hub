<?php
declare(strict_types=1);

/**
 * EMAILSEND ONE — builder-media.php
 * GET  ?show=<archivo.jpg>  -> sirve la imagen
 * POST ?upload=1            -> recibe imagen desde Campaign Builder 2
 */

$campaignDir=__DIR__.'/uploads/campaigns';

function bm_safe_name(string $name): string {
    $name=basename(trim($name));
    return preg_match('/^[a-f0-9]{24}\.(?:jpg|jpeg|png|gif|webp)$/i',$name)?$name:'';
}
function bm_mime(string $file): ?string {
    $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
    return [
        'jpg'=>'image/jpeg',
        'jpeg'=>'image/jpeg',
        'png'=>'image/png',
        'gif'=>'image/gif',
        'webp'=>'image/webp'
    ][$ext]??null;
}
function bm_serve(string $name): never {
    global $campaignDir;
    $name=bm_safe_name($name);
    if($name===''){http_response_code(404);exit('IMAGE_NOT_FOUND');}

    $root=realpath($campaignDir);
    $file=realpath($campaignDir.'/'.$name);

    if(!$root||!$file||!is_file($file)||!is_readable($file)){
        http_response_code(404);
        exit('IMAGE_NOT_FOUND');
    }

    $rootPrefix=rtrim(str_replace('\\','/',$root),'/').'/';
    $fileNorm=str_replace('\\','/',$file);
    if(strpos($fileNorm,$rootPrefix)!==0){
        http_response_code(404);
        exit('IMAGE_NOT_FOUND');
    }

    $mime=bm_mime($file);
    if(!$mime){http_response_code(404);exit('IMAGE_NOT_FOUND');}

    $size=(int)filesize($file);
    header('Content-Type: '.$mime);
    header('Content-Length: '.$size);
    header('Content-Disposition: inline; filename="'.$name.'"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=86400');
    readfile($file);
    exit;
}

/* Public image display */
if($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['show'])){
    bm_serve((string)$_GET['show']);
}

/* Health check — proves the file itself is reachable */
if($_SERVER['REQUEST_METHOD']==='GET'){
    header('Content-Type: text/html; charset=UTF-8');
    http_response_code(200);
    $exists=is_dir($campaignDir);
    $writable=$exists && is_writable($campaignDir);
    ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EMAILSEND Builder Media</title>
<style>
body{margin:0;background:#07182b;color:#fff;font-family:Arial,sans-serif;min-height:100vh;display:grid;place-items:center}
.card{width:min(720px,calc(100% - 36px));background:#0d2942;border:1px solid #285371;border-radius:18px;padding:28px}
h1{margin:0 0 10px;color:#73bc25;font-size:28px}p{color:#c4d7e3;line-height:1.5}.row{padding:9px 0;border-bottom:1px solid #284b65}.k{display:inline-block;width:220px;color:#6ed0ff;font-weight:bold}.ok{color:#9df378}
</style>
</head>
<body><div class="card">
<h1>✓ builder-media.php responde</h1>
<p>Si ves esta pantalla, el archivo existe y PHP puede ejecutarlo.</p>
<div class="row"><span class="k">PHP</span><?=htmlspecialchars(PHP_VERSION)?></div>
<div class="row"><span class="k">Carpeta campaigns</span><?=$exists?'<span class="ok">EXISTE</span>':'NO EXISTE'?></div>
<div class="row"><span class="k">Carpeta escribible</span><?=$writable?'<span class="ok">SI</span>':'NO'?></div>
<div class="row"><span class="k">Ruta</span><?=htmlspecialchars($campaignDir)?></div>
</div></body></html>
<?php
    exit;
}

/* POST upload used by Campaign Builder 2 */
require __DIR__.'/includes/bootstrap.php';
require_login();

$token=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_POST['upload_token']??''));

function bm_reply(array $payload,int $status=200): never {
    global $token;
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    $data=json_encode([
        'source'=>'EMAILSEND_BUILDER_MEDIA',
        'token'=>$token,
        'payload'=>$payload
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    echo '<!doctype html><meta charset="utf-8"><script>';
    echo 'parent.postMessage('.$data.', location.origin);';
    echo '</script>';
    exit;
}

try{verify_csrf();}catch(Throwable $e){
    bm_reply(['ok'=>false,'error'=>'La sesión expiró. Recarga el editor e intenta nuevamente.'],403);
}

$errors=[
    UPLOAD_ERR_INI_SIZE=>'La imagen supera el máximo permitido por PHP.',
    UPLOAD_ERR_FORM_SIZE=>'La imagen supera el máximo permitido por el formulario.',
    UPLOAD_ERR_PARTIAL=>'La imagen llegó incompleta.',
    UPLOAD_ERR_NO_FILE=>'Selecciona una imagen.',
    UPLOAD_ERR_NO_TMP_DIR=>'No existe carpeta temporal.',
    UPLOAD_ERR_CANT_WRITE=>'PHP no pudo escribir el archivo temporal.',
    UPLOAD_ERR_EXTENSION=>'Una extensión de PHP bloqueó la subida.',
];

if(!isset($_FILES['image']))bm_reply(['ok'=>false,'error'=>'El servidor no recibió el archivo.'],400);

$f=$_FILES['image'];
$err=(int)($f['error']??UPLOAD_ERR_NO_FILE);
if($err!==UPLOAD_ERR_OK)bm_reply(['ok'=>false,'error'=>$errors[$err]??('Error PHP: '.$err)],400);

$tmp=(string)($f['tmp_name']??'');
if($tmp===''||!is_file($tmp)||!is_uploaded_file($tmp)){
    bm_reply(['ok'=>false,'error'=>'PHP no reconoce la imagen como una subida válida.'],400);
}

$size=(int)($f['size']??0);
if($size<1)bm_reply(['ok'=>false,'error'=>'La imagen está vacía.'],400);
if($size>15*1024*1024)bm_reply(['ok'=>false,'error'=>'La imagen supera 15 MB.'],400);

$mime='';
if(class_exists('finfo')){
    try{$fi=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$fi->file($tmp);}catch(Throwable $ignore){}
}
if($mime===''&&function_exists('mime_content_type'))$mime=(string)@mime_content_type($tmp);
if($mime===''&&function_exists('getimagesize')){
    $info=@getimagesize($tmp);$mime=(string)($info['mime']??'');
}

$map=['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
$key=strtolower($mime);
if(!isset($map[$key]))bm_reply(['ok'=>false,'error'=>'Formato no reconocido: '.$mime],400);

$uploads=__DIR__.'/uploads';
foreach([$uploads,$campaignDir] as $folder){
    if(!is_dir($folder)&&!@mkdir($folder,0755,true)&&!is_dir($folder)){
        bm_reply(['ok'=>false,'error'=>'No se pudo crear uploads/campaigns.'],500);
    }
    @chmod($folder,0755);
}

if(!is_writable($campaignDir)){
    bm_reply(['ok'=>false,'error'=>'uploads/campaigns no tiene permisos de escritura.'],500);
}

$name=bin2hex(random_bytes(12)).'.'.$map[$key];
$dest=$campaignDir.'/'.$name;

if(!@move_uploaded_file($tmp,$dest)){
    bm_reply(['ok'=>false,'error'=>'No se pudo guardar la imagen.'],500);
}

@chmod($dest,0644);
clearstatcache(true,$dest);

if(!is_file($dest)||!is_readable($dest)||(int)filesize($dest)<1){
    @unlink($dest);
    bm_reply(['ok'=>false,'error'=>'La imagen quedó incompleta.'],500);
}

$url=app_url('builder-media.php?show='.rawurlencode($name));

bm_reply([
    'ok'=>true,
    'url'=>$url,
    'filename'=>$name,
    'bytes'=>(int)filesize($dest),
    'mime'=>$key
]);
