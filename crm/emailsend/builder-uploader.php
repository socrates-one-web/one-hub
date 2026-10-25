<?php
declare(strict_types=1);

require __DIR__.'/includes/bootstrap.php';
require_login();

$dir=__DIR__.'/uploads/campaigns';
if(!is_dir($dir)) @mkdir($dir,0755,true);
@chmod(__DIR__.'/uploads',0755);
@chmod($dir,0755);

$target=(string)($_GET['target']??'image');
$target=preg_replace('/[^a-z0-9_-]/i','',$target) ?: 'image';

$error='';
$success=false;
$url='';
$name='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        verify_csrf();
    }catch(Throwable $e){
        $error='La sesión expiró. Recarga el editor.';
    }

    if($error==='' && !isset($_FILES['image'])){
        $error='No se recibió ningún archivo.';
    }

    if($error===''){
        $f=$_FILES['image'];
        $code=(int)($f['error']??UPLOAD_ERR_NO_FILE);
        $errors=[
            UPLOAD_ERR_INI_SIZE=>'La imagen supera el máximo permitido por PHP.',
            UPLOAD_ERR_FORM_SIZE=>'La imagen supera el máximo permitido por el formulario.',
            UPLOAD_ERR_PARTIAL=>'La imagen llegó incompleta.',
            UPLOAD_ERR_NO_FILE=>'Selecciona una imagen.',
            UPLOAD_ERR_NO_TMP_DIR=>'No existe carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE=>'PHP no pudo escribir el archivo temporal.',
            UPLOAD_ERR_EXTENSION=>'Una extensión de PHP bloqueó la subida.',
        ];
        if($code!==UPLOAD_ERR_OK){
            $error=$errors[$code]??('Error PHP '.$code);
        }else{
            $tmp=(string)($f['tmp_name']??'');
            if($tmp===''||!is_file($tmp)||!is_uploaded_file($tmp)){
                $error='PHP no reconoce la imagen como una subida válida.';
            }else{
                $size=(int)($f['size']??0);
                if($size<1){
                    $error='La imagen está vacía.';
                }elseif($size>15*1024*1024){
                    $error='La imagen supera 15 MB.';
                }else{
                    $mime='';
                    if(class_exists('finfo')){
                        try{$fi=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$fi->file($tmp);}catch(Throwable $ignore){}
                    }
                    if($mime===''&&function_exists('mime_content_type'))$mime=(string)@mime_content_type($tmp);
                    if($mime===''&&function_exists('getimagesize')){
                        $i=@getimagesize($tmp);$mime=(string)($i['mime']??'');
                    }

                    $map=[
                        'image/jpeg'=>'jpg',
                        'image/jpg'=>'jpg',
                        'image/png'=>'png',
                        'image/gif'=>'gif',
                        'image/webp'=>'webp'
                    ];

                    $key=strtolower($mime);
                    if(!isset($map[$key])){
                        $error='Formato no reconocido: '.$mime;
                    }elseif(!is_writable($dir)){
                        $error='uploads/campaigns no tiene permisos de escritura.';
                    }else{
                        $name=bin2hex(random_bytes(12)).'.'.$map[$key];
                        $dest=$dir.'/'.$name;

                        if(!@move_uploaded_file($tmp,$dest)){
                            $error='No se pudo guardar la imagen.';
                        }else{
                            @chmod($dest,0644);
                            clearstatcache(true,$dest);

                            if(!is_file($dest)||!is_readable($dest)||(int)filesize($dest)<1){
                                @unlink($dest);
                                $error='La imagen quedó incompleta después de guardarla.';
                            }else{
                                $url=app_url('builder-media.php?show='.rawurlencode($name));
                                $success=true;
                            }
                        }
                    }
                }
            }
        }
    }
}

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Subir imagen</title>
<style>
*{box-sizing:border-box}html,body{margin:0;background:transparent;font-family:Arial,sans-serif;color:#284b62}
.wrap{padding:0}
form{margin:0}
.pick{display:block;width:100%;border:1px dashed #9fc7df;background:#f6fbfe;border-radius:8px;padding:10px;color:#35637f;font-size:12px}
button{margin-top:8px;width:100%;border:0;border-radius:7px;background:#0074c7;color:#fff;padding:9px 10px;font-weight:800;font-size:11px;cursor:pointer}
button:hover{filter:brightness(1.04)}
.status{margin-top:7px;padding:8px 9px;border-radius:7px;font-size:10px;line-height:1.4}
.err{background:#fff2ef;color:#a94129;border:1px solid #ffcabc}
.ok{background:#eff9e9;color:#3d741f;border:1px solid #bce2a9}
.preview{margin-top:8px;border:1px solid #d7e6ef;border-radius:8px;background:#fff;padding:6px;display:none}
.preview img{display:block;max-width:100%;max-height:130px;margin:auto}
.note{font-size:9px;color:#7a8e9c;margin-top:6px;line-height:1.4}
</style>
</head>
<body>
<div class="wrap">
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
<input class="pick" type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" required>
<button type="submit">SUBIR IMAGEN</button>
</form>

<?php if($error):?><div class="status err"><?=h($error)?></div><?php endif?>

<?php if($success):?>
<div class="status ok" id="status">✓ Archivo guardado correctamente. Enviando al editor…</div>
<div class="preview" id="preview" style="display:block">
  <img src="<?=h($url)?>&v=<?=time()?>" alt="" onerror="this.style.display='none'">
</div>
<script>
(function(){
  var url=<?=json_encode($url,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
  var target=<?=json_encode($target)?>;

  // IMPORTANT:
  // Server-side saving is already the source of truth.
  // builder-media.php has been independently verified on this hosting.
  // Do not block the Builder waiting for an iframe-local image onload.
  parent.postMessage({
    source:'EMAILSEND_BUILDER_IFRAME_UPLOADER',
    ok:true,
    url:url,
    target:target,
    message:'Imagen guardada correctamente.'
  }, location.origin);
})();
</script>
<?php endif?>

<p class="note">JPG, PNG, GIF o WEBP · máximo 15 MB</p>
</div>
</body>
</html>
