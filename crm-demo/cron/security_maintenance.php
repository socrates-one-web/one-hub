<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
$isCli=PHP_SAPI==='cli';$key=(string)($_GET['key']??'');
$expectedKey=trim((string)config('cron_key',''));
if(!$isCli && ($expectedKey==='' || str_starts_with($expectedKey,'CHANGE_ME') || $key==='' || !hash_equals($expectedKey,$key))){http_response_code(403);exit('Acceso restringido.');}
$messages=[];
try{
 if(db_table_exists('login_attempts')){db()->exec('DELETE FROM login_attempts WHERE attempted_at<DATE_SUB(NOW(),INTERVAL 30 DAY)');$messages[]='Intentos antiguos eliminados';}
 if(db_table_exists('user_sessions')){db()->exec('DELETE FROM user_sessions WHERE (expires_at IS NOT NULL AND expires_at<DATE_SUB(NOW(),INTERVAL 7 DAY)) OR (is_revoked=1 AND revoked_at<DATE_SUB(NOW(),INTERVAL 7 DAY))');$messages[]='Sesiones antiguas eliminadas';}
 if(db_table_exists('security_audit_logs')){db()->exec('DELETE FROM security_audit_logs WHERE created_at<DATE_SUB(NOW(),INTERVAL 365 DAY)');$messages[]='Auditoría antigua depurada';}
 $integrity=crm_integrity_check();
 if($integrity['baseline']&&!$integrity['ok']){
  $alert=trim((string)setting('security_alert_email','info@socrates.la'));
  $details='Modificados: '.count($integrity['changed']).' · Faltantes: '.count($integrity['missing']).' · Nuevos: '.count($integrity['new']);
  crm_security_audit('integrity_change_detected','critical',$details);
  if(filter_var($alert,FILTER_VALIDATE_EMAIL))send_system_email($alert,'Alerta crítica · archivos del CRM modificados','<div style="font-family:Arial,sans-serif"><h2 style="color:#b42318">Cambios de integridad detectados</h2><p>'.e($details).'</p><p>Ingresa al Centro de Seguridad para revisar los archivos.</p></div>');
  $messages[]='Alerta de integridad enviada';
 }else{$messages[]=$integrity['baseline']?'Integridad correcta':'Sin línea base de integridad';}
 set_setting('security_maintenance_last_run',date(DATE_ATOM));
 echo implode("\n",$messages)."\n";
}catch(Throwable$e){crm_log_exception($e);http_response_code(500);echo 'ERROR: '.$e->getMessage()."\n";}
