<?php
declare(strict_types=1);
require_once __DIR__.'/includes/schema_manager.php';
require_once __DIR__.'/includes/demo_seed.php';
$configPath=__DIR__.'/includes/config.php';
if(file_exists($configPath)){header('Location: login.php');exit;}
$errors=[];$success=false;$demoSummary=null;
function h(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function crm_installer_reset_known_tables(PDO $pdo, string $schema): int
{
    preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?/i', $schema, $matches);
    $tables = array_values(array_unique(array_filter($matches[1] ?? [])));
    if (!$tables) return 0;
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach (array_reverse($tables) as $table) {
            $safe = str_replace('`', '``', $table);
            $pdo->exec('DROP TABLE IF EXISTS `'.$safe.'`');
        }
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
    return count($tables);
}
function install_password_hash(string $password): string
{
    if (defined('PASSWORD_ARGON2ID') && function_exists('password_algos') && in_array('argon2id', password_algos(), true)) {
        try {
            $hash = password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 1,
            ]);
            if (is_string($hash) && $hash !== '') return $hash;
        } catch (Throwable $e) {
            // Algunos hostings no admiten Argon2 con varios hilos. Usa bcrypt como respaldo.
        }
    }
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    if (!is_string($hash) || $hash === '') throw new RuntimeException('No fue posible proteger la contraseña del administrador.');
    return $hash;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!version_compare(PHP_VERSION,'8.1.0','>='))$errors[]='El CRM requiere PHP 8.1 o superior. Versión actual: '.PHP_VERSION;
 if(!extension_loaded('pdo_mysql'))$errors[]='La extensión PDO MySQL no está activa en el servidor.';
 $appUrl=rtrim(trim((string)($_POST['app_url']??'')),'/');$dbHost=trim((string)($_POST['db_host']??'localhost'));$dbPort=trim((string)($_POST['db_port']??'3306'));$dbName=trim((string)($_POST['db_name']??''));$dbUser=trim((string)($_POST['db_user']??''));$dbPass=(string)($_POST['db_pass']??'');$adminName=trim((string)($_POST['admin_name']??'Administrador'));$adminEmail=strtolower(trim((string)($_POST['admin_email']??'admin@example.com')));$adminPassword=(string)($_POST['admin_password']??'');$companyName=trim((string)($_POST['company_name']??'Mi empresa'));$installDemo=!empty($_POST['install_demo']);$resetIncomplete=false && !empty($_POST['reset_incomplete']);
 if(!filter_var($appUrl,FILTER_VALIDATE_URL))$errors[]='La URL de la aplicación no es válida.';if($dbName===''||$dbUser==='')$errors[]='Completa los datos de la base de datos.';if(!filter_var($adminEmail,FILTER_VALIDATE_EMAIL))$errors[]='El email del administrador no es válido.';if(strlen($adminPassword)<12)$errors[]='La contraseña debe tener al menos 12 caracteres.';
 if(!$errors){try{$dsn=sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$dbHost,$dbPort,$dbName);$pdo=new PDO($dsn,$dbUser,$dbPass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);$schema=file_get_contents(__DIR__.'/sql/schema.sql');if($schema===false)throw new RuntimeException('No se encontró el esquema SQL.');if($resetIncomplete){crm_installer_reset_known_tables($pdo,$schema);}foreach(crm_split_sql($schema) as $statement){$statement=trim($statement);if($statement!=='')$pdo->exec($statement);}
 $pdo->prepare('INSERT INTO users (name,email,password_hash,role,permissions) VALUES (?,?,?,"admin",NULL) ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash),role="admin",permissions=NULL,is_active=1')->execute([$adminName,$adminEmail,install_password_hash($adminPassword)]);
 $defaults=['notification_email'=>$adminEmail,'support_email'=>$adminEmail,'mail_mode'=>'mail','mail_from_email'=>$adminEmail,'mail_from_name'=>'SÓCRATES CRM','smtp_force_username_from'=>'1','smtp_timeout'=>'20','alert_days_before'=>'1','quote_prefix'=>'COT','quote_terms_es'=>'Validez indicada en la cotización. El inicio del servicio se coordina después de la aceptación y del pago acordado.','quote_terms_pt'=>'Validade indicada na cotação. O início do serviço será coordenado após a aprovação e o pagamento acordado.','quote_terms_en'=>'Validity as shown in the quotation. Service begins after acceptance and the agreed payment.','quote_payment_methods_clp'=>"Transferencia bancaria Chile
Banco: 
Tipo de cuenta: 
Cuenta: 
Titular: 
RUT: 
Email: 
---
Webpay / Link de pago
Pago por link enviado por email o WhatsApp.",'quote_payment_methods_usd'=>"Bank transfer USA
Bank: 
Account type: 
Account number: 
Beneficiary: 
SWIFT / ABA: 
---
Stripe payment link
Secure payment via Stripe link.",'quote_payment_methods_brl'=>"PIX
Chave PIX: 
Favorecido: 
CPF/CNPJ: 
---
Transferência bancária Brasil
Banco: 
Agência: 
Conta: ",'session_idle_minutes'=>'30','session_absolute_hours'=>'8','password_min_length'=>'12','login_max_attempts'=>'5','login_lock_minutes'=>'15','security_alert_email'=>'info@socrates.la','force_admin_totp'=>'0','security_headers_enabled'=>'1','company_name'=>$companyName?:'Mi empresa','company_logo_path'=>'','project_default_currency'=>'USD','project_default_hourly_rate'=>'75','calendar_share_enabled'=>'1','support_default_department'=>'Soporte','support_sla_urgent_hours'=>'4','support_sla_high_hours'=>'8','support_sla_normal_hours'=>'24','support_sla_low_hours'=>'48','developer_contact_email'=>'info@socrates.la','app_version'=>'2.0.2','license_status'=>'active','license_plan'=>'Community','demo_mode'=>'0','license_customer'=>'','license_expires_at'=>''];$settingStmt=$pdo->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');foreach($defaults as$key=>$value)$settingStmt->execute([$key,$value]);
 if(isset($_FILES['company_logo'])&&($_FILES['company_logo']['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_OK){$mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['company_logo']['tmp_name']);$map=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];if(isset($map[$mime])&&($_FILES['company_logo']['size']??0)<=4*1024*1024){$dir=__DIR__.'/uploads/branding';if(!is_dir($dir))@mkdir($dir,0775,true);$name='company-logo-'.date('YmdHis').'.'.$map[$mime];if(move_uploaded_file($_FILES['company_logo']['tmp_name'],$dir.'/'.$name))$settingStmt->execute(['company_logo_path','uploads/branding/'.$name]);}}
 crm_apply_schema($pdo);
 $adminIdStmt=$pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$adminIdStmt->execute([$adminEmail]);$adminUserId=(int)$adminIdStmt->fetchColumn();if($installDemo){$demoSummary=crm_seed_demo($pdo,$adminUserId);}
 $config="<?php\nreturn ".var_export(['app_name'=>'ONE HUB Community','app_url'=>$appUrl,'timezone'=>'America/Sao_Paulo','app_key'=>bin2hex(random_bytes(32)),'cron_key'=>bin2hex(random_bytes(24)),'db'=>['host'=>$dbHost,'port'=>$dbPort,'name'=>$dbName,'user'=>$dbUser,'pass'=>$dbPass,'charset'=>'utf8mb4'],'debug'=>false],true).";\n";if(file_put_contents($configPath,$config,LOCK_EX)===false)throw new RuntimeException('No se pudo crear includes/config.php. Revisa los permisos de escritura.');@chmod($configPath,0640);$success=true;}catch(Throwable$e){$errors[]='Instalación incompleta: '.$e->getMessage();}}
}
$detectedUrl=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.($_SERVER['HTTP_HOST']??'crm.socrates.la').rtrim(dirname($_SERVER['SCRIPT_NAME']??'/'),'/\\');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar SÓCRATES CRM</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700;800&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><style>:root{--navy:#002f57;--blue:#0074c7;--orange:#ff5300;--text:#183247;--line:#d7e2ea}*{box-sizing:border-box}body{margin:0;background:linear-gradient(135deg,#002f57,#0074c7);min-height:100vh;font-family:'Ubuntu Sans',sans-serif;color:var(--text);padding:30px}.wrap{max-width:880px;margin:auto;background:#fff;border-radius:26px;overflow:hidden;box-shadow:0 30px 80px #001d365c}.hero{background:#002f57;padding:28px 42px;border-bottom:1px solid var(--line)}.hero img{width:285px;height:82px;object-fit:contain}.hero h1{font-family:'Exo 2';margin:8px 0 0;color:#fff;font-size:25px}.hero p{margin:5px 0 0;color:#a9daf8}.body{padding:34px 42px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.full{grid-column:1/-1}label{display:block;font-weight:700;font-size:14px;margin-bottom:7px}input{width:100%;border:1px solid var(--line);border-radius:11px;padding:13px 14px;font:inherit;outline:none}.btn{border:0;background:var(--orange);color:#fff;font-weight:800;padding:14px 24px;border-radius:12px;cursor:pointer;font:inherit}.alert{border-radius:12px;padding:13px 16px;margin-bottom:18px}.error{background:#fff0ee;color:#9c281c}.success{background:#e9fbef;color:#146c32}.note{font-size:13px;color:#63798a;margin-top:18px}.demo-choice{border:2px solid #d7eaf8;background:#f5fbff;border-radius:16px;padding:16px}.demo-check{display:flex;gap:13px;align-items:flex-start;margin:0;cursor:pointer}.demo-check input{width:20px;height:20px;margin-top:2px;accent-color:#ff5300}.demo-check span{display:flex;flex-direction:column;gap:5px}.demo-check strong{font-family:'Exo 2';color:#002f57}.demo-check small{font-weight:500;line-height:1.45;color:#526d80}.demo-warning{margin-top:10px;padding:9px 12px;border-radius:9px;background:#fff3e8;color:#9a4b16;font-size:12px}.reset-choice{border:2px solid #ffd1c9;background:#fff4f1;border-radius:16px;padding:16px}.reset-choice strong{color:#9c281c}.reset-warning{margin-top:9px;font-size:12px;color:#8b3b30}.demo-result{display:flex;flex-direction:column;gap:4px;background:#edf8ff;border:1px solid #b9ddf3;color:#07588d;padding:14px 16px;border-radius:12px;margin:0 0 18px}.demo-result span{font-size:13px}@media(max-width:650px){body{padding:12px}.hero,.body{padding:25px 20px}.grid{grid-template-columns:1fr}.full{grid-column:auto}}</style></head><body><div class="wrap"><div class="hero"><img src="assets/img/logo-creative-blanco.png" alt="Sócrates Creative Studio"><h1>SÓCRATES CRM · Project Manager</h1><p>CRM, ventas, proyectos, soporte, facturación y seguridad en una sola plataforma.</p></div><div class="body">
<?php if($success):?><div class="alert success"><strong>Instalación finalizada.</strong> El CRM + Project Manager ya está preparado.</div><?php if($demoSummary):?><div class="demo-result"><strong>✓ DEMO COMPLETA INSTALADA</strong><span><?=h((string)($demoSummary['clients']??8))?> clientes · <?=h((string)($demoSummary['projects']??6))?> proyectos · <?=h((string)($demoSummary['tasks']??74))?> tareas · <?=h((string)($demoSummary['deals']??8))?> negocios · <?=h((string)($demoSummary['quotes']??5))?> cotizaciones</span></div><?php endif;?><a class="btn" style="display:inline-block;text-decoration:none" href="login.php">Ingresar al CRM</a><p class="note">Por seguridad, elimina o renombra <strong>install.php</strong> después de comprobar el acceso.</p><?php else:?><?php foreach($errors as$error):?><div class="alert error"><?=h($error)?></div><?php endforeach;?><form method="post" enctype="multipart/form-data" autocomplete="off"><div class="grid"><div class="full"><label>URL del CRM</label><input type="url" name="app_url" required value="<?=h($_POST['app_url']??$detectedUrl)?>"></div><div><label>Servidor MySQL</label><input name="db_host" required value="<?=h($_POST['db_host']??'localhost')?>"></div><div><label>Puerto</label><input name="db_port" required value="<?=h($_POST['db_port']??'3306')?>"></div><div><label>Base de datos</label><input name="db_name" required value="<?=h($_POST['db_name']??'')?>"></div><div><label>Usuario MySQL</label><input name="db_user" required value="<?=h($_POST['db_user']??'')?>"></div><div class="full"><label>Contraseña MySQL</label><input type="password" name="db_pass"></div><div><label>Nombre de la empresa</label><input name="company_name" required value="<?=h($_POST['company_name']??'Mi empresa')?>"></div><div><label>Logo de la empresa</label><input type="file" name="company_logo" accept="image/png,image/jpeg,image/webp"></div><div><label>Nombre del administrador</label><input name="admin_name" required value="<?=h($_POST['admin_name']??'Administrador')?>"></div><div><label>Email del administrador</label><input type="email" name="admin_email" required value="<?=h($_POST['admin_email']??'admin@example.com')?>"></div><div class="full"><label>Contraseña del CRM</label><input type="password" name="admin_password" minlength="12" required placeholder="Mínimo 12 caracteres"></div><div class="full reset-choice"><label class="demo-check"><input type="checkbox" name="reset_incomplete" value="1" disabled <?=false && !empty($_POST['reset_incomplete'])?'checked':''?>><span><strong>LIMPIEZA DE TABLAS DESACTIVADA POR SEGURIDAD</strong><small>Recomendado en esta instalación DEMO. Elimina y reconstruye únicamente las tablas pertenecientes a SÓCRATES CRM, corrigiendo restos de intentos anteriores y estructuras incompatibles.</small></span></label><div class="reset-warning"><strong>Atención:</strong> úsalo solamente en esta base nueva destinada a la DEMO. Si la base contiene información real de otro CRM, desmarca esta opción.</div></div><div class="full demo-choice"><label class="demo-check"><input type="checkbox" name="install_demo" value="1" <?=($_SERVER['REQUEST_METHOD']!=='POST' ? false : !empty($_POST['install_demo']))?'checked':''?>><span><strong>INSTALAR CONTENIDO DEMO COMPLETO</strong><small>Opcional solo para pruebas: agrega clientes, proyectos, Kanban, Gantt, tareas, equipo, horas, costos, riesgos, pipeline, cotizaciones, contratos, leads, agenda y datos para los gráficos.</small></span></label><div class="demo-warning">Los datos son 100% ficticios y se identifican como DEMO. Desmarca esta opción para una instalación limpia.</div></div><div class="full"><button class="btn">INSTALAR SÓCRATES CRM + PROJECT MANAGER</button></div></div></form><p class="note">La base de datos debe estar creada previamente desde el panel de hosting.</p><?php endif;?></div></div></body></html>
