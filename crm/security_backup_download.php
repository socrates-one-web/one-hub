<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('security.manage');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Método no permitido.');}verify_csrf();
$name=(string)($_POST['file']??'');
try{$payload=crm_backup_payload($name);$download=preg_replace('/\.crmbackup$/','',$name).'.'.$payload['format'];crm_security_audit('database_backup_downloaded','critical','Respaldo descargado: '.$name);header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.str_replace('"','',$download).'"');header('Content-Length: '.strlen($payload['data']));echo$payload['data'];}catch(Throwable$e){http_response_code(404);exit($e->getMessage());}
