<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';
require_login();
$pageTitle='Autenticación de dos factores';
$user=current_user();$userId=(int)$user['id'];$errors=[];$backupCodes=$_SESSION['new_backup_codes']??null;unset($_SESSION['new_backup_codes']);
$stmt=db()->prepare('SELECT * FROM users WHERE id=?');$stmt->execute([$userId]);$full=$stmt->fetch();
if($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['setup']) && !empty($_GET['setup'])){
 if(empty($_SESSION['pending_totp_secret']) || isset($_GET['renew'])) $_SESSION['pending_totp_secret']=crm_totp_secret();
}
$pending=(string)($_SESSION['pending_totp_secret']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'');$password=(string)($_POST['password']??'');
 if(!password_verify($password,(string)$full['password_hash']))$errors[]='La contraseña actual no es correcta.';
 if(!$errors&&$action==='enable'){
  $secret=(string)($_SESSION['pending_totp_secret']??'');$code=(string)($_POST['code']??'');
  if($secret==='')$errors[]='La clave TOTP no está disponible. Genera una nueva clave e inténtalo otra vez.';
  else{
   $detectedOffset=crm_totp_find_time_offset($secret,$code,120);
   if($detectedOffset===null)$errors[]='El código de la aplicación autenticadora no es válido. Verifica que la cuenta use TOTP y que la hora automática esté activada en tu teléfono.';
   else{$codes=crm_generate_backup_codes();set_setting('totp_time_offset_user_'.$userId,(string)$detectedOffset);db()->prepare("UPDATE users SET two_factor_enabled=1,two_factor_method='totp',totp_secret=?,backup_codes=? WHERE id=?")->execute([encrypt_secret($secret),json_encode($codes['hashes']),$userId]);unset($_SESSION['pending_totp_secret'],$_SESSION['force_totp_setup_after_login']);$_SESSION['new_backup_codes']=$codes['plain'];crm_revoke_user_sessions($userId,(string)($_SESSION['crm_session_token']??''));crm_security_audit('totp_enabled','warning','TOTP activado; ajuste horario '.$detectedOffset.' segundos','user',$userId);$message='Aplicación autenticadora activada. Guarda los códigos de recuperación.';if(abs($detectedOffset)>60)$message.=' Se detectó una diferencia horaria de '.abs($detectedOffset).' segundos y el CRM la compensará automáticamente.';flash('success',$message);redirect('security_2fa.php');}
  }
 }
 if(!$errors&&$action==='disable'){
  if((string)setting('force_admin_totp','0')==='1'&&(string)$full['role']==='admin')$errors[]='La política del sistema exige TOTP para administradores. Desactiva primero esa política desde el Centro de Seguridad.';
  if(!$errors){
   $code=(string)($_POST['code']??'');$secret=decrypt_secret((string)$full['totp_secret']);
   if($secret!==''&&!crm_totp_verify($secret,$code,1,crm_totp_user_time_offset($userId))&&!crm_consume_backup_code($userId,$code))$errors[]='El código de seguridad no es válido.';
   else{db()->prepare("UPDATE users SET two_factor_method='email',totp_secret=NULL,backup_codes=NULL,two_factor_enabled=1 WHERE id=?")->execute([$userId]);crm_security_audit('totp_disabled','critical','TOTP desactivado; continúa 2FA por email','user',$userId);flash('success','TOTP desactivado. La verificación continuará por email.');redirect('security_2fa.php');}
  }
 }
 if(!$errors&&$action==='regenerate'){
  $code=(string)($_POST['code']??'');$secret=decrypt_secret((string)$full['totp_secret']);
  if($secret===''||!crm_totp_verify($secret,$code,1,crm_totp_user_time_offset($userId)))$errors[]='El código de la aplicación autenticadora no es válido.';
  else{$codes=crm_generate_backup_codes();db()->prepare('UPDATE users SET backup_codes=? WHERE id=?')->execute([json_encode($codes['hashes']),$userId]);$_SESSION['new_backup_codes']=$codes['plain'];crm_security_audit('backup_codes_regenerated','warning','Códigos de recuperación regenerados','user',$userId);redirect('security_2fa.php');}
 }
}
$stmt->execute([$userId]);$full=$stmt->fetch();$enabled=(string)($full['two_factor_method']??'email')==='totp'&&!empty($full['totp_secret']);
require __DIR__.'/includes/header.php';
?>
<?php foreach($errors as$error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
<?php if(is_array($backupCodes)):?><section class="panel security-backup-panel"><div class="panel-header"><div><h2>Códigos de recuperación</h2><span class="table-sub">Se muestran una sola vez. Guárdalos fuera del CRM.</span></div><button class="btn btn-light" type="button" onclick="window.print()">IMPRIMIR</button></div><div class="panel-body"><div class="backup-codes"><?php foreach($backupCodes as$code):?><code><?=e($code)?></code><?php endforeach;?></div></div></section><?php endif;?>
<section class="panel"><div class="panel-header"><div><h2>Aplicación autenticadora</h2><span class="table-sub">Google Authenticator, Microsoft Authenticator, Authy o cualquier app compatible con TOTP.</span></div><span class="badge badge-<?=$enabled?'green':'orange'?>"><?=$enabled?'ACTIVA':'NO CONFIGURADA'?></span></div><div class="panel-body">
<?php if(!$enabled):?>
<?php if($pending===''):?><div class="security-callout"><h3>Protección recomendada</h3><p>La aplicación genera códigos nuevos cada 30 segundos y protege mejor que el email frente al robo de contraseñas.</p><a class="btn btn-primary" href="security_2fa.php?setup=1">CONFIGURAR APLICACIÓN</a></div>
<?php else:$uri=crm_totp_uri($pending,(string)$full['email']);?>
<div class="form-grid security-setup-grid"><div class="form-section"><h3>1. Agrega la cuenta</h3><p>En tu aplicación elige <strong>Agregar cuenta → Introducir clave</strong>, o usa el botón para abrir una app compatible.</p><label>Nombre de cuenta<input readonly value="Socrates ONE CRM · <?=e($full['email'])?>"></label><label>Clave secreta<div style="display:flex;gap:8px;align-items:center"><input id="totp-secret" readonly value="<?=e($pending)?>"><button class="btn btn-light" type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('totp-secret').value)">COPIAR</button></div></label><label>Tipo<select disabled><option>Basado en tiempo (TOTP)</option></select></label><div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-secondary" href="<?=e($uri)?>">ABRIR EN APP</a><a class="btn btn-light" href="security_2fa.php?setup=1&amp;renew=1">GENERAR OTRA CLAVE</a></div><details><summary>URI técnica</summary><code class="code-wrap"><?=e($uri)?></code></details><p class="table-sub">Hora UTC del servidor: <strong><?=e(gmdate('Y-m-d H:i:s'))?></strong>. Activa fecha y hora automáticas en el teléfono.</p></div><form method="post" class="form-section"><?=csrf_field()?><input type="hidden" name="action" value="enable"><h3>2. Confirma la configuración</h3><label>Código de 6 dígitos<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></label><label>Contraseña actual<input type="password" name="password" required autocomplete="current-password"></label><button class="btn btn-primary">ACTIVAR TOTP</button></form></div>
<?php endif;?>
<?php else:?><div class="security-grid-two"><form method="post" class="form-section"><?=csrf_field()?><input type="hidden" name="action" value="regenerate"><h3>Regenerar códigos de recuperación</h3><p>Los códigos anteriores dejarán de funcionar.</p><label>Código actual<input name="code" required></label><label>Contraseña actual<input type="password" name="password" required></label><button class="btn btn-secondary">GENERAR CÓDIGOS NUEVOS</button></form><form method="post" class="form-section danger-zone" onsubmit="return confirm('¿Desactivar la aplicación autenticadora?')"><?=csrf_field()?><input type="hidden" name="action" value="disable"><h3>Desactivar TOTP</h3><p>La cuenta volverá a usar códigos por email.</p><label>Código actual o de recuperación<input name="code" required></label><label>Contraseña actual<input type="password" name="password" required></label><button class="btn btn-danger">DESACTIVAR TOTP</button></form></div><?php endif;?>
</div></section>
<?php require __DIR__.'/includes/footer.php';?>
