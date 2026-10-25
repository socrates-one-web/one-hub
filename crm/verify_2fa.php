<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';
if(crm_current_language()!=='es')ob_start('crm_translate_rendered_html');
if(current_user()){if(crm_validate_session())redirect('dashboard.php');$_SESSION=[];session_regenerate_id(true);}
$userId=(int)($_SESSION['pending_2fa_user_id']??0);if(!$userId)redirect('login.php');
$stmt=db()->prepare('SELECT id,name,email,is_active,two_factor_method,totp_secret,backup_codes FROM users WHERE id=?');$stmt->execute([$userId]);$user=$stmt->fetch();if(!$user||!$user['is_active']){unset($_SESSION['pending_2fa_user_id']);redirect('login.php');}
$method=(string)($_SESSION['pending_2fa_method']??$user['two_factor_method']??'email');
$error=null;$notice=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'verify');
 if($action==='resend'&&$method==='email'){
  if(time()-(int)($_SESSION['two_factor_last_sent']??0)<60)$error='Espera 60 segundos antes de solicitar otro código.';
  else{$full=db()->prepare('SELECT * FROM users WHERE id=?');$full->execute([$userId]);$result=begin_two_factor_login($full->fetch());if($result['success'])$notice='Se envió un nuevo código.';else$error='No fue posible reenviar el código: '.$result['error'];}
 }else{
  $raw=trim((string)($_POST['code']??''));$_SESSION['two_factor_attempts']=(int)($_SESSION['two_factor_attempts']??0)+1;
  if(time()>(int)($_SESSION['two_factor_expires_at']??0))$error='La verificación venció. Inicia sesión nuevamente.';
  elseif((int)$_SESSION['two_factor_attempts']>6){crm_security_audit('two_factor_locked','warning','Demasiados intentos de segundo factor','user',$userId,$userId);unset($_SESSION['pending_2fa_user_id'],$_SESSION['two_factor_code_hash']);$error='Demasiados intentos. Inicia sesión nuevamente.';}
  else{
   $valid=false;
   if($method==='totp'){
    $secret=decrypt_secret((string)$user['totp_secret']);
    $valid=$secret!==''&&crm_totp_verify($secret,$raw,1,crm_totp_user_time_offset($userId));
    if(!$valid)$valid=crm_consume_backup_code($userId,$raw);
   }else{
    $code=preg_replace('/\D+/','',$raw)??'';$valid=$code!==''&&password_verify($code,(string)($_SESSION['two_factor_code_hash']??''));
   }
   if(!$valid){$error='El código no es correcto.';crm_security_audit('two_factor_failed','warning','Código de segundo factor incorrecto','user',$userId,$userId);}
   else{crm_security_audit('two_factor_success','info','Segundo factor validado','user',$userId,$userId);$mustSetup=!empty($_SESSION['force_totp_setup_after_login']);complete_user_login($userId);if($mustSetup)redirect('security_2fa.php?setup=1');redirect('dashboard.php');}
  }
 }
}
$masked=preg_replace('/(^.).*(@.*$)/','$1••••$2',(string)$user['email']);
$description=$method==='totp'?'Ingresa el código de 6 dígitos de tu aplicación autenticadora. También puedes usar un código de recuperación.':'Enviamos un código de 6 dígitos a '.$masked.'. Vence en 10 minutos.';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verificación de acceso</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700;800&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css?v=1.8.0"></head><body class="login-body"><main class="login-card"><div class="login-language"><a href="language.php?lang=es"><img src="assets/img/flag-es.png" alt="Español"></a><a href="language.php?lang=pt"><img src="assets/img/flag-pt.png" alt="Português"></a><a href="language.php?lang=en"><img src="assets/img/flag-en.png" alt="English"></a></div><div class="login-brand"><img src="assets/img/logo-creative-blanco.png" alt="Sócrates Creative Studio"><p>SEGURIDAD</p></div><h2>Código de verificación</h2><p class="muted"><?=e($description)?></p><?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?><?php if($notice):?><div class="alert alert-success"><?=e($notice)?></div><?php endif;?><form method="post" class="stack"><?=csrf_field()?><input type="hidden" name="action" value="verify"><label>Código<input class="security-code" inputmode="text" maxlength="12" name="code" required autofocus autocomplete="one-time-code"></label><button class="btn btn-primary btn-block">VERIFICAR E INGRESAR</button></form><?php if($method==='email'):?><form method="post" style="margin-top:12px"><?=csrf_field()?><input type="hidden" name="action" value="resend"><button class="btn btn-light btn-block">REENVIAR CÓDIGO</button></form><?php endif;?><p style="text-align:center;margin-top:18px"><a href="logout.php">Cancelar acceso</a></p></main></body></html>
