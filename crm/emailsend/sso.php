<?php
declare(strict_types=1);
// Validación aislada: POST firmado por ONE HUB. No confiar en email/rol sin firma.
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);header('Allow: POST');exit('Acceso solo desde ONE HUB.');}
require __DIR__.'/includes/bootstrap.php';
$crmConfigFile=dirname(__DIR__).'/includes/config.php';
if(!is_file($crmConfigFile)){http_response_code(503);exit('ONE HUB no está configurado.');}
$crmConfig=require $crmConfigFile;
$key=(string)($crmConfig['app_key']??'');
$payload=(string)($_POST['payload']??'');
$signature=(string)($_POST['signature']??'');
if(strlen($key)<32 || str_contains($key,'CHANGE_ME') || strlen($payload)>8192 || !preg_match('/^[A-Za-z0-9_-]+$/D',$payload) || !preg_match('/^[a-f0-9]{64}$/D',$signature) || !hash_equals(hash_hmac('sha256',$payload,$key.'|onehub-emailsend-sso-v1'),$signature)){
 http_response_code(403);exit('Autorización inválida. Regresa a ONE HUB e inténtalo de nuevo.');
}
$decoded=base64_decode(strtr($payload,'-_','+/'),true);
$data=is_string($decoded)?json_decode($decoded,true):null;
if(!is_array($data) || ($data['v']??null)!==1 || ($data['iss']??null)!=='onehub-emailsend' || (int)($data['exp']??0)<time() || (int)($data['exp']??0)>time()+90 || (int)($data['id']??0)<1 || !filter_var($data['email']??'',FILTER_VALIDATE_EMAIL) || !is_string($data['name']??null) || strlen((string)$data['name'])>160 || !preg_match('/^[a-f0-9]{40}$/D',(string)($data['nonce']??''))){
 http_response_code(403);exit('Solicitud inválida o vencida. Vuelve a ONE HUB.');
}
$pdo=db();
// Tablas de puente, separadas de la estructura principal de EMAILSEND.
$pdo->exec('CREATE TABLE IF NOT EXISTS onehub_sso_nonce (nonce CHAR(40) PRIMARY KEY, created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec('CREATE TABLE IF NOT EXISTS onehub_sso_meta (singleton TINYINT UNSIGNED PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec('CREATE TABLE IF NOT EXISTS onehub_sso_accounts (crm_user_id INT UNSIGNED PRIMARY KEY, emailsend_user_id INT UNSIGNED NOT NULL UNIQUE, email VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->beginTransaction();
try {
 $pdo->prepare('INSERT INTO onehub_sso_nonce (nonce,created_at) VALUES (?,NOW())')->execute([$data['nonce']]);
 $tenant=(int)($pdo->query('SELECT tenant_id FROM onehub_sso_meta WHERE singleton=1 FOR UPDATE')->fetchColumn()?:0);
 if(!$tenant){
  $pdo->exec("INSERT INTO tenants (name,created_at) VALUES ('ONE HUB · Community',NOW())");
  $tenant=(int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO onehub_sso_meta (singleton,tenant_id) VALUES (1,?)')->execute([$tenant]);
  $pdo->prepare('INSERT INTO settings (tenant_id,default_from_name,default_from_email) VALUES (?,?,?)')->execute([$tenant,'ONE HUB · Community','demo@onehub.test']);
  if (!empty($config['seed_demo'])) {
    require __DIR__.'/includes/onehub_demo_seed.php';
    onehub_seed_emailsend_demo($pdo,$tenant);
   }
 }
 $stmt=$pdo->prepare('SELECT emailsend_user_id FROM onehub_sso_accounts WHERE crm_user_id=?');
 $stmt->execute([(int)$data['id']]);$userId=(int)($stmt->fetchColumn()?:0);
 $role=!empty($data['admin'])?'admin':'user';
 if($userId){
  $stmt=$pdo->prepare('UPDATE users SET name=?, email=?, role=?, active=1 WHERE id=? AND tenant_id=?');
  $stmt->execute([$data['name'],strtolower($data['email']),$role,$userId,$tenant]);
  if($stmt->rowCount()===0){$v=$pdo->prepare('SELECT 1 FROM users WHERE id=? AND tenant_id=? AND active=1');$v->execute([$userId,$tenant]);if(!$v->fetchColumn())throw new RuntimeException('Cuenta SSO no encontrada.');}
 }else{
  $lookup=$pdo->prepare('SELECT id FROM users WHERE email=?');$lookup->execute([strtolower($data['email'])]);
  if($lookup->fetchColumn())throw new RuntimeException('Este email ya existe fuera de ONE HUB; no se puede vincular automáticamente.');
  $pdo->prepare('INSERT INTO users (tenant_id,name,email,password_hash,role,active,created_at) VALUES (?,?,?,?,?,1,NOW())')->execute([$tenant,$data['name'],strtolower($data['email']),password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),$role]);
  $userId=(int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO onehub_sso_accounts (crm_user_id,emailsend_user_id,email,created_at) VALUES (?,?,?,NOW())')->execute([(int)$data['id'],$userId,strtolower($data['email'])]);
 }
 // Demo has no email send capability, even with Admin role: SimpleSMTP blocks it.
 $pdo->commit();
} catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack(); error_log('EMAILSEND SSO: '.$e->getMessage());http_response_code(409);exit('No se pudo vincular la cuenta. Comprueba el estado de la base de datos independiente y su configuración.');}
session_regenerate_id(true);
$_SESSION['user_id']=$userId;
unset($_SESSION['effective_tenant_id'],$_SESSION['report_user_id']);
$pdo->exec('DELETE FROM onehub_sso_nonce WHERE created_at<DATE_SUB(NOW(),INTERVAL 1 DAY)');
header('Cache-Control: no-store');
redirect('index.php');
