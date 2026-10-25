<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_login();
$pageTitle='Mi perfil';
$errors=[];$userId=(int)$_SESSION['user_id'];
$stmt=db()->prepare('SELECT * FROM users WHERE id=?');$stmt->execute([$userId]);$profile=$stmt->fetch();
if(!$profile){redirect('logout.php');}

if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'profile');
 if($action==='password'){
  $current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
  if(!password_verify($current,(string)$profile['password_hash']))$errors[]='La contraseña actual no es correcta.';
  $errors=array_merge($errors,crm_password_errors($new,(string)$profile['email'],(string)$profile['name']));
  if($new!==$confirm)$errors[]='La confirmación no coincide.';
  if(!$errors){db()->prepare('UPDATE users SET password_hash=?,password_changed_at=NOW(),failed_login_count=0,locked_until=NULL WHERE id=?')->execute([crm_password_hash($new),$userId]);crm_revoke_user_sessions($userId,(string)($_SESSION['crm_session_token']??''));crm_security_audit('password_changed','critical','El usuario cambió su contraseña','user',$userId);log_activity('Contraseña actualizada');flash('success','Contraseña actualizada correctamente. Se cerraron las demás sesiones.');redirect('profile.php');}
 }else{
  $name=trim((string)($_POST['name']??''));$email=strtolower(trim((string)($_POST['email']??'')));$whatsapp=trim((string)($_POST['whatsapp']??''));$language=(string)($_POST['language']??'es');if(!isset(crm_supported_languages()[$language]))$language='es';
  $notifyEmail=isset($_POST['notify_email'])?1:0;$notifyWhatsapp=isset($_POST['notify_whatsapp'])?1:0;$notifyInapp=isset($_POST['notify_inapp'])?1:0;
  $twoFactor=((string)($profile['two_factor_method']??'email')==='totp'||(string)setting('two_factor_required','0')==='1'||isset($_POST['two_factor_enabled']))?1:0;
  $alertDays=implode(',',parse_alert_days($_POST['alert_days']??[1]));
  if($name==='')$errors[]='El nombre es obligatorio.';
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='El email no es válido.';
  if($email!==strtolower((string)$profile['email'])&&!password_verify((string)($_POST['profile_current_password']??''),(string)$profile['password_hash']))$errors[]='Para cambiar el email debes confirmar tu contraseña actual.';
  if($notifyWhatsapp&&$whatsapp==='')$errors[]='Indica tu número de WhatsApp.';
  $check=db()->prepare('SELECT COUNT(*) FROM users WHERE email=? AND id<>?');$check->execute([$email,$userId]);if((int)$check->fetchColumn()>0)$errors[]='Ese email ya está registrado.';
  $avatar=$profile['avatar_path']??null;
  if(!$errors&&isset($_FILES['avatar'])){try{$avatar=upload_user_avatar($_FILES['avatar'],$avatar);}catch(Throwable$e){$errors[]=$e->getMessage();}}
  if(!$errors){
   db()->prepare('UPDATE users SET name=?,email=?,whatsapp=?,avatar_path=?,notify_email=?,notify_whatsapp=?,notify_inapp=?,alert_days=?,two_factor_enabled=?,language=? WHERE id=?')
    ->execute([$name,$email,$whatsapp?:null,$avatar,$notifyEmail,$notifyWhatsapp,$notifyInapp,$alertDays,$twoFactor,$language,$userId]);
   if($email!==strtolower((string)$profile['email'])){crm_revoke_user_sessions($userId,(string)($_SESSION['crm_session_token']??''));crm_security_audit('email_changed','critical','Email de acceso modificado','user',$userId);}log_activity('Perfil actualizado');flash('success','Perfil y preferencias de alerta actualizados.');redirect('profile.php');
  }
 }
}
$alertSelected=parse_alert_days($_POST['alert_days']??($profile['alert_days']??'1'));
require __DIR__.'/includes/header.php';
?>
<?php foreach($errors as$error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
<div class="profile-layout">
<form method="post" enctype="multipart/form-data" class="panel"><div class="panel-header"><div><h2>Datos personales</h2><span class="table-sub">Esta información se utiliza para el acceso, alertas y tickets de soporte.</span></div></div><div class="panel-body form-grid"><?=csrf_field()?><input type="hidden" name="action" value="profile">
<div class="full avatar-editor"><div id="profile-avatar-preview"><?=user_avatar_html($profile,'avatar')?></div><label>Foto de perfil<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-preview-input="#profile-avatar-preview"><span class="table-sub">JPG, PNG o WEBP. Máximo 4 MB.</span></label></div>
<label>Nombre completo *<input name="name" required value="<?=e($_POST['name']??$profile['name'])?>"></label>
<label>Email de acceso *<input type="email" name="email" required value="<?=e($_POST['email']??$profile['email'])?>"><span class="table-sub">Al cambiarlo, confirma tu contraseña actual.</span></label><label>Confirmación para cambio de email<input type="password" name="profile_current_password" autocomplete="current-password" placeholder="Solo si cambias el email"></label>
<label>WhatsApp<input name="whatsapp" value="<?=e($_POST['whatsapp']??$profile['whatsapp'])?>" placeholder="+55 45 ..."></label>
<label>Perfil<input value="<?=e(role_label((string)$profile['role']))?>" disabled></label>
<label>Idioma de la interfaz<select name="language"><?php foreach(crm_supported_languages() as $code=>$label):?><option value="<?=e($code)?>" <?=($_POST['language']??$profile['language']??'es')===$code?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label>
<div class="form-section full form-grid"><h3>Canales de alerta</h3>
<label class="checkbox-row"><input type="checkbox" name="notify_email" value="1" <?=($_SERVER['REQUEST_METHOD']==='POST'?isset($_POST['notify_email']):(bool)$profile['notify_email'])?'checked':''?>> Email</label>
<label class="checkbox-row"><input type="checkbox" name="notify_whatsapp" value="1" <?=($_SERVER['REQUEST_METHOD']==='POST'?isset($_POST['notify_whatsapp']):(bool)$profile['notify_whatsapp'])?'checked':''?>> WhatsApp</label>
<label class="checkbox-row"><input type="checkbox" name="notify_inapp" value="1" <?=($_SERVER['REQUEST_METHOD']==='POST'?isset($_POST['notify_inapp']):(bool)$profile['notify_inapp'])?'checked':''?>> Dentro del CRM</label>
<label class="checkbox-row"><input type="checkbox" name="two_factor_enabled" value="1" <?=((string)setting('two_factor_required','0')==='1'||($_SERVER['REQUEST_METHOD']==='POST'?isset($_POST['two_factor_enabled']):(bool)$profile['two_factor_enabled']))?'checked':''?> <?=((string)setting('two_factor_required','0')==='1')?'disabled':''?>> Código de seguridad por email</label>
<div class="full"><strong>Anticipación de vencimientos</strong><div class="actions" style="margin-top:10px"><?php foreach([0,1,3,7,15,30]as$day):?><label class="checkbox-row" style="margin:0"><input type="checkbox" name="alert_days[]" value="<?=$day?>" <?=in_array($day,$alertSelected,true)?'checked':''?>> <?=$day===0?'Mismo día':$day.' días antes'?></label><?php endforeach;?></div></div>
</div>
<div class="full form-actions"><button class="btn btn-primary">GUARDAR PERFIL</button></div>
</div></form>
<aside>
<section class="panel"><div class="panel-header"><h2>Seguridad</h2></div><div class="panel-body"><a class="btn btn-primary btn-block" href="security_2fa.php">CONFIGURAR AUTENTICACIÓN 2FA</a></div><form method="post" class="panel-body form-grid"><?=csrf_field()?><input type="hidden" name="action" value="password"><label class="full">Contraseña actual<input type="password" name="current_password" required autocomplete="current-password"></label><label class="full">Nueva contraseña<input type="password" name="new_password" minlength="12" required autocomplete="new-password"></label><label class="full">Confirmar contraseña<input type="password" name="confirm_password" minlength="12" required autocomplete="new-password"></label><button class="btn btn-secondary full">CAMBIAR CONTRASEÑA</button></form></section>
<section class="panel"><div class="panel-header"><h2>Accesos rápidos</h2></div><div class="panel-body actions"><a class="btn btn-light" href="my_expirations.php">Mis vencimientos</a><a class="btn btn-light" href="notifications.php">Notificaciones</a><a class="btn btn-light" href="manual.php">Manual de uso</a></div></section>
</aside></div>
<?php require __DIR__.'/includes/footer.php';?>
