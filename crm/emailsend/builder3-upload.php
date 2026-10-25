<?php
declare(strict_types=1);

require __DIR__.'/includes/bootstrap.php';
require_login();
require_permission('campaigns','edit');

$dir=__DIR__.'/uploads/campaigns3';
$cloudDir=__DIR__.'/email-assets';
if(!is_dir(__DIR__.'/uploads')) @mkdir(__DIR__.'/uploads',0755,true);
if(!is_dir($dir)) @mkdir($dir,0755,true);
if(!is_dir($cloudDir)) @mkdir($cloudDir,0755,true);
@chmod(__DIR__.'/uploads',0755);
@chmod($dir,0755);
@chmod($cloudDir,0755);

$target=preg_replace('/[^a-z0-9_-]/i','',(string)($_GET['target']??'image')) ?: 'image';
$error='';
$success=false;
$url='';
$name='';

function cb3_h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function cb3_origin(): string {
    // Campaign assets must always be published through HTTPS.
    $scheme='https';
    $host=trim((string)($_SERVER['HTTP_HOST']??'localhost'));
    if($host==='')$host='localhost';
    $script=(string)($_SERVER['SCRIPT_NAME']??'/builder3-upload.php');
    $base=rtrim(str_replace('\\','/',dirname($script)),'/');
    if($base==='.'||$base==='/')$base='';
    return $scheme.'://'.$host.$base;
}

function cb3_email_safe_asset(string $source,string $mime,string $cloudDir): array {
    $mime=strtolower(trim($mime));
    $tmp=$cloudDir.'/.cb3safe-'.bin2hex(random_bytes(8)).'.tmp';
    $outMime=$mime;$ok=false;
    try{
        if($mime==='image/jpeg' && function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')){
            $im=@imagecreatefromjpeg($source);
            if($im!==false){if(function_exists('imageinterlace'))@imageinterlace($im,false);$ok=@imagejpeg($im,$tmp,90);@imagedestroy($im);$outMime='image/jpeg';}
        }elseif($mime==='image/png' && function_exists('imagecreatefrompng') && function_exists('imagepng')){
            $im=@imagecreatefrompng($source);
            if($im!==false){@imagealphablending($im,false);@imagesavealpha($im,true);$ok=@imagepng($im,$tmp,6);@imagedestroy($im);$outMime='image/png';}
        }elseif($mime==='image/webp' && function_exists('imagecreatefromwebp') && function_exists('imagepng')){
            $im=@imagecreatefromwebp($source);
            if($im!==false){@imagealphablending($im,false);@imagesavealpha($im,true);$ok=@imagepng($im,$tmp,6);@imagedestroy($im);$outMime='image/png';}
        }elseif($mime==='image/gif'){
            $ok=@copy($source,$tmp);$outMime='image/gif';
        }
    }catch(Throwable $ignore){$ok=false;}
    if(!$ok && in_array($mime,['image/jpeg','image/png','image/gif'],true)){
        @unlink($tmp);$ok=@copy($source,$tmp);$outMime=$mime;
    }
    if(!$ok){@unlink($tmp);return ['', $mime==='image/webp'?'El servidor no puede convertir WEBP a PNG. Usa JPG o PNG.':'No se pudo preparar una versión compatible para email.'];}
    $probe=@getimagesize($tmp);
    if(!is_array($probe)){@unlink($tmp);return ['','La imagen generada no es válida.'];}
    $outMime=strtolower((string)($probe['mime']??$outMime));
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'][$outMime]??'';
    if($ext===''){@unlink($tmp);return ['','Formato final no compatible con Outlook.'];}
    $hash=@sha1_file($tmp);
    if(!is_string($hash)||!preg_match('/^[a-f0-9]{40}$/',$hash)){@unlink($tmp);return ['','No se pudo calcular la firma de la imagen.'];}
    $name=$hash.'.'.$ext;$dest=$cloudDir.'/'.$name;
    if(!is_file($dest)||(int)@filesize($dest)!==(int)@filesize($tmp)){
        if(!@rename($tmp,$dest)){
            if(!@copy($tmp,$dest)){@unlink($tmp);return ['','No se pudo publicar la imagen en email-assets.'];}
            @unlink($tmp);
        }
    }else @unlink($tmp);
    @chmod($dest,0644);
    return [$name,''];
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{verify_csrf();}catch(Throwable $e){$error='La sesión expiró. Recarga Campaign Builder 3.';}

    if($error===''&&!isset($_FILES['image']))$error='No se recibió ningún archivo.';

    if($error===''){
        $f=$_FILES['image'];
        $code=(int)($f['error']??UPLOAD_ERR_NO_FILE);
        $errors=[
            UPLOAD_ERR_INI_SIZE=>'La imagen supera upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE=>'La imagen supera el tamaño permitido.',
            UPLOAD_ERR_PARTIAL=>'La imagen llegó incompleta.',
            UPLOAD_ERR_NO_FILE=>'Selecciona una imagen.',
            UPLOAD_ERR_NO_TMP_DIR=>'El servidor no tiene carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE=>'PHP no pudo escribir el archivo temporal.',
            UPLOAD_ERR_EXTENSION=>'Una extensión de PHP bloqueó la subida.',
        ];
        if($code!==UPLOAD_ERR_OK){
            $error=$errors[$code]??('Error PHP '.$code);
        }else{
            $tmp=(string)($f['tmp_name']??'');
            $size=(int)($f['size']??0);
            if($tmp===''||!is_file($tmp)||!is_uploaded_file($tmp))$error='PHP no reconoce el archivo como una subida válida.';
            elseif($size<1)$error='La imagen está vacía.';
            elseif($size>15*1024*1024)$error='La imagen supera 15 MB.';
            else{
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
                if(!isset($map[$key]))$error='Formato no reconocido: '.$mime.'. Usa JPG, PNG, GIF o WEBP.';
                elseif(!is_writable($dir))$error='uploads/campaigns3 no tiene permisos de escritura.';
                else{
                    $name=bin2hex(random_bytes(16)).'.'.$map[$key];
                    $dest=$dir.'/'.$name;
                    if(!@move_uploaded_file($tmp,$dest))$error='No se pudo guardar la imagen.';
                    else{
                        @chmod($dest,0644);
                        clearstatcache(true,$dest);
                        if(!is_file($dest)||!is_readable($dest)||(int)filesize($dest)<1){
                            @unlink($dest);$error='La imagen quedó incompleta después de guardarla.';
                        }else{
                            // v0.15.0: create an Outlook-safe, content-addressed public rendition.
                            // JPEG is baseline RGB-compatible, PNG is normalized, WEBP becomes PNG.
                            [$cloudName,$safeError]=cb3_email_safe_asset($dest,$key,$cloudDir);
                            if($safeError!==''){
                                $error=$safeError;
                            }else{
                                $url=cb3_origin().'/email-assets/'.rawurlencode($cloudName);
                                $success=true;
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Subir imagen</title>
<style>
*{box-sizing:border-box}html,body{margin:0;background:transparent;font-family:Arial,sans-serif;color:#36566d}
form{margin:0}.file{width:100%;padding:10px;border:1px dashed #9fc7df;border-radius:8px;background:#f7fbfe;font-size:12px;color:#36566d}
button{width:100%;margin-top:7px;padding:9px;border:0;border-radius:7px;background:#0074c7;color:#fff;font-size:11px;font-weight:800;cursor:pointer}
.status{margin-top:7px;padding:8px 9px;border-radius:7px;font-size:10px;line-height:1.4}.ok{background:#eff9e9;color:#39741d;border:1px solid #bfe0ad}.err{background:#fff2ef;color:#a94129;border:1px solid #ffc9ba}
.thumb{display:block;max-width:100%;max-height:105px;margin:8px auto 0;border-radius:6px}
.note{font-size:9px;color:#8095a3;margin-top:6px}
</style>
</head>
<body>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?=cb3_h(csrf_token())?>">
<input class="file" type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" required>
<button type="submit">SUBIR IMAGEN</button>
</form>
<?php if($error):?><div class="status err"><?=cb3_h($error)?></div><?php endif?>
<?php if($success):?>
<div class="status ok">✓ Imagen guardada correctamente.</div>
<img class="thumb" src="<?=cb3_h($url.(str_contains($url,'?')?'&':'?').'v='.time())?>" alt="">
<script>
parent.postMessage({
  source:'EMAILSEND_CB3_UPLOAD',
  ok:true,
  target:<?=json_encode($target)?>,
  url:<?=json_encode($url,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>
}, location.origin);
</script>
<?php endif?>
<div class="note">JPG, PNG, GIF o WEBP · máximo 15 MB · WEBP se convierte a PNG para Outlook</div>
</body>
</html>
