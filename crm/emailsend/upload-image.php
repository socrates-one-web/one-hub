<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_login();

$iframeMode = isset($_GET['transport']) && $_GET['transport'] === 'iframe';
$token = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_POST['upload_token'] ?? ''));

function upload_reply(array $payload, int $status=200): never {
    global $iframeMode, $token;
    http_response_code($status);

    if ($iframeMode) {
        header('Content-Type: text/html; charset=UTF-8');
        $message = json_encode([
            'source' => 'EMAILSEND_IMAGE_UPLOAD',
            'token' => $token,
            'payload' => $payload
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        echo '<!doctype html><meta charset="utf-8"><script>';
        echo 'parent.postMessage(' . $message . ', location.origin);';
        echo '</script>';
        exit;
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    upload_reply(['ok'=>false,'error'=>'Método no permitido.'],405);
}

try {
    verify_csrf();
} catch (Throwable $e) {
    upload_reply(['ok'=>false,'error'=>'La sesión/CSRF expiró. Recarga el Builder e intenta nuevamente.'],403);
}

$uploadErrors=[
    UPLOAD_ERR_INI_SIZE=>'El archivo supera upload_max_filesize del servidor.',
    UPLOAD_ERR_FORM_SIZE=>'El archivo supera el tamaño permitido por el formulario.',
    UPLOAD_ERR_PARTIAL=>'La imagen llegó incompleta. Intenta nuevamente.',
    UPLOAD_ERR_NO_FILE=>'Selecciona una imagen.',
    UPLOAD_ERR_NO_TMP_DIR=>'El servidor no tiene directorio temporal disponible.',
    UPLOAD_ERR_CANT_WRITE=>'El servidor no pudo escribir el archivo temporal.',
    UPLOAD_ERR_EXTENSION=>'Una extensión de PHP detuvo la subida.',
];

if (!isset($_FILES['image'])) {
    upload_reply(['ok'=>false,'error'=>'El servidor no recibió el archivo de imagen.'],400);
}

$f=$_FILES['image'];
$err=(int)($f['error']??UPLOAD_ERR_NO_FILE);
if ($err!==UPLOAD_ERR_OK) {
    upload_reply(['ok'=>false,'error'=>$uploadErrors[$err]??('Error de subida PHP: '.$err)],400);
}

$tmp=(string)($f['tmp_name']??'');
if ($tmp==='' || !is_file($tmp) || !is_uploaded_file($tmp)) {
    upload_reply(['ok'=>false,'error'=>'PHP no reconoce el archivo como una subida válida.'],400);
}

$size=(int)($f['size']??0);
if ($size<1) upload_reply(['ok'=>false,'error'=>'El archivo está vacío.'],400);
if ($size>12*1024*1024) upload_reply(['ok'=>false,'error'=>'La imagen supera el máximo de 12 MB.'],400);

$mime='';
if (class_exists('finfo')) {
    try {
        $fi=new finfo(FILEINFO_MIME_TYPE);
        $mime=(string)$fi->file($tmp);
    } catch(Throwable $ignore) {}
}
if ($mime==='' && function_exists('mime_content_type')) $mime=(string)@mime_content_type($tmp);
if ($mime==='' && function_exists('getimagesize')) {
    $info=@getimagesize($tmp);
    $mime=(string)($info['mime']??'');
}

$map=[
    'image/jpeg'=>'jpg',
    'image/jpg'=>'jpg',
    'image/png'=>'png',
    'image/gif'=>'gif',
    'image/webp'=>'webp',
];
$mimeKey=strtolower($mime);
if (!isset($map[$mimeKey])) {
    upload_reply(['ok'=>false,'error'=>'Formato no permitido o no reconocido ('.$mime.'). Usa JPG, PNG, GIF o WEBP.'],400);
}

$ym=date('Y/m');
$base=__DIR__.'/uploads';
$year=$base.'/'.date('Y');
$dir=$base.'/'.$ym;

foreach ([$base,$year,$dir] as $folder) {
    if (!is_dir($folder) && !@mkdir($folder,0755,true) && !is_dir($folder)) {
        upload_reply(['ok'=>false,'error'=>'No se pudo crear la carpeta '.basename($folder).'.'],500);
    }
    @chmod($folder,0755);
}

if (!is_writable($dir)) {
    upload_reply(['ok'=>false,'error'=>'La carpeta uploads/'.$ym.' no tiene permiso de escritura.'],500);
}

$name=bin2hex(random_bytes(12)).'.'.$map[$mimeKey];
$dest=$dir.'/'.$name;

if (!@move_uploaded_file($tmp,$dest)) {
    upload_reply(['ok'=>false,'error'=>'PHP recibió la imagen pero no pudo guardarla en uploads/'.$ym.'.'],500);
}

@chmod($dest,0644);
clearstatcache(true,$dest);

if (!is_file($dest) || !is_readable($dest)) {
    upload_reply(['ok'=>false,'error'=>'La imagen se guardó, pero PHP no puede volver a leerla.'],500);
}
$bytes=(int)filesize($dest);
if ($bytes<1) {
    @unlink($dest);
    upload_reply(['ok'=>false,'error'=>'La imagen quedó guardada con 0 bytes.'],500);
}

$relative=$ym.'/'.$name;
$url=app_url('media.php?f='.rawurlencode($relative));

upload_reply([
    'ok'=>true,
    'url'=>$url,
    'relative'=>$relative,
    'bytes'=>$bytes,
    'mime'=>$mimeKey,
    'original_name'=>(string)($f['name']??'')
]);
