<?php
/**
 * ONE HUB Community · Download lead dashboard (ES/PT/EN).
 * Self-contained, no CRM/MySQL credentials. Admin setup requires a private
 * one-time token included ONLY in the deploy ZIP, never in the GitHub repo.
 */
declare(strict_types=1);

$base=__DIR__.'/_private_downloads';
$configFile=$base.'/admin_config.php';
$setupFile=$base.'/admin_setup.key';
$https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https');
$local=in_array((string)($_SERVER['REMOTE_ADDR']??''),['127.0.0.1','::1'],true);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, max-age=0, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
if (!$https&&!$local){http_response_code(403);exit('HTTPS required.');}
if ($https)header('Strict-Transport-Security: max-age=31536000');
if (!is_dir($base)||!is_file($base.'/.htaccess')){http_response_code(503);exit('Private storage directory is unavailable.');}

session_name('ONEHUB_DOWNLOADS_ADMIN');
session_set_cookie_params(['lifetime'=>0,'path'=>'/admin-descargas.php','secure'=>$https,'httponly'=>true,'samesite'=>'Strict']);
ini_set('session.use_strict_mode','1');
session_start();
if(!isset($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
function e(string $t):string{return htmlspecialchars($t,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function lang():string{$v=(string)($_POST['lang']??$_GET['lang']??$_SESSION['admin_lang']??'es');return in_array($v,['es','pt','en'],true)?$v:'es';}
$L=lang();$_SESSION['admin_lang']=$L;
$dictionary=[
'es'=>['admin'=>'ADMINISTRACIÓN DE DESCARGAS','intro'=>'Registros y actividad de ONE HUB Community','setup'=>'ACTIVAR PANEL PRIVADO','setupBody'=>'Introduce la clave de activación del ZIP privado y crea una contraseña segura. La clave se destruye al finalizar.','token'=>'Clave de activación','password'=>'Contraseña','passwordRepeat'=>'Repetir contraseña','create'=>'ACTIVAR ADMINISTRADOR','login'=>'INICIAR SESIÓN','signin'=>'ENTRAR AL PANEL','logout'=>'CERRAR SESIÓN','wrong'=>'Credenciales incorrectas o acceso temporalmente limitado.','invalid'=>'Solicitud inválida. Comprueba tus datos.','setupErr'=>'No fue posible activar el administrador. Comprueba los permisos de la carpeta protegida.','setupMissing'=>'No existe clave de activación privada. Instala el archivo admin_setup.key del paquete de despliegue en _private_downloads.','dashboard'=>'Panel general','leads'=>'Registros','clicks'=>'Clics de descarga','unique'=>'Correos únicos','clean'=>'CLEAN','demo'=>'DEMO','complete'=>'COMPLETO','search'=>'Buscar nombre o correo','edition'=>'Edición','all'=>'Todas','filter'=>'FILTRAR','date'=>'Fecha (UTC)','name'=>'Nombre','email'=>'Correo','language'=>'Idioma','source'=>'Tipo','event'=>'Acción','exportLeads'=>'EXPORTAR REGISTROS CSV','exportEvents'=>'EXPORTAR CLICS CSV','none'=>'No hay registros para estos filtros.','last'=>'Últimos registros','first'=>'Anterior','next'=>'Siguiente','page'=>'Página','privacy'=>'Acceso privado · Datos personales','note'=>'Un clic en GitHub no confirma que se haya completado la transferencia del ZIP. Los registros de formularios anteriores a esta versión no tienen un clic asociado.','retention'=>'Elimina registros cuando corresponda, según la política de privacidad.','delete'=>'ELIMINAR DATOS','deleteEmail'=>'Eliminar solicitudes y clics de un correo electrónico','deleteConfirm'=>'Confirmo la eliminación irreversible de los datos de este correo.','deleteSuccess'=>'Registros eliminados correctamente.','deleteFailed'=>'No fue posible eliminar los datos; comprueba los permisos y vuelve a intentarlo.','back'=>'VOLVER AL SITIO','notready'=>'Panel protegido: pendiente de configuración','setupOk'=>'Administrador configurado. Ahora puedes iniciar sesión.','details'=>'Detalle de registros','limit'=>'Puedes solicitar una exportación CSV completa para el análisis.','logged'=>'Sesión iniciada'],
'pt'=>['admin'=>'ADMINISTRAÇÃO DE DOWNLOADS','intro'=>'Cadastros e atividade do ONE HUB Community','setup'=>'ATIVAR PAINEL PRIVADO','setupBody'=>'Informe a chave de ativação do ZIP privado e crie uma senha forte. A chave será destruída após o uso.','token'=>'Chave de ativação','password'=>'Senha','passwordRepeat'=>'Repetir senha','create'=>'ATIVAR ADMINISTRADOR','login'=>'ENTRAR','signin'=>'ACESSAR PAINEL','logout'=>'SAIR','wrong'=>'Credenciais incorretas ou acesso temporariamente limitado.','invalid'=>'Solicitação inválida. Confira os dados.','setupErr'=>'Não foi possível ativar o painel. Verifique as permissões da pasta protegida.','setupMissing'=>'Não existe chave privada. Instale admin_setup.key do pacote de implantação em _private_downloads.','dashboard'=>'Painel geral','leads'=>'Cadastros','clicks'=>'Cliques para baixar','unique'=>'E-mails únicos','clean'=>'CLEAN','demo'=>'DEMO','complete'=>'COMPLETO','search'=>'Buscar nome ou e-mail','edition'=>'Edição','all'=>'Todas','filter'=>'FILTRAR','date'=>'Data (UTC)','name'=>'Nome','email'=>'E-mail','language'=>'Idioma','source'=>'Tipo','event'=>'Ação','exportLeads'=>'EXPORTAR CADASTROS CSV','exportEvents'=>'EXPORTAR CLIQUES CSV','none'=>'Nenhum registro encontrado.','last'=>'Registros recentes','first'=>'Anterior','next'=>'Próxima','page'=>'Página','privacy'=>'Acesso privado · Dados pessoais','note'=>'Um clique no GitHub não comprova que o ZIP terminou de baixar. Cadastros anteriores a esta versão não têm cliques associados.','retention'=>'Exclua cadastros conforme a política de privacidade.','delete'=>'EXCLUIR DADOS','deleteEmail'=>'Excluir cadastros e cliques de um e-mail','deleteConfirm'=>'Confirmo a exclusão irreversível dos dados deste e-mail.','deleteSuccess'=>'Registros excluídos.','deleteFailed'=>'Não foi possível excluir os dados; verifique as permissões.','back'=>'VOLTAR AO SITE','notready'=>'Painel protegido: configuração pendente','setupOk'=>'Administrador configurado. Entre com sua senha.','details'=>'Detalhes dos cadastros','limit'=>'Exporte um CSV completo para uma análise mais detalhada.','logged'=>'Sessão iniciada'],
'en'=>['admin'=>'DOWNLOAD ADMINISTRATION','intro'=>'ONE HUB Community registrations and activity','setup'=>'ACTIVATE PRIVATE DASHBOARD','setupBody'=>'Enter the private activation key from your deploy ZIP and create a strong password. The key is deleted after setup.','token'=>'Activation key','password'=>'Password','passwordRepeat'=>'Repeat password','create'=>'ACTIVATE DASHBOARD','login'=>'SIGN IN','signin'=>'OPEN DASHBOARD','logout'=>'SIGN OUT','wrong'=>'Invalid credentials or temporarily restricted access.','invalid'=>'Invalid request. Check your details.','setupErr'=>'Could not activate the dashboard. Verify permissions on the protected directory.','setupMissing'=>'Private activation key missing. Install admin_setup.key from your deployment package to _private_downloads.','dashboard'=>'Overview','leads'=>'Registrations','clicks'=>'Download clicks','unique'=>'Unique emails','clean'=>'CLEAN','demo'=>'DEMO','complete'=>'COMPLETE','search'=>'Search name or email','edition'=>'Edition','all'=>'All','filter'=>'FILTER','date'=>'Date (UTC)','name'=>'Name','email'=>'Email','language'=>'Language','source'=>'Type','event'=>'Action','exportLeads'=>'EXPORT REGISTRATIONS CSV','exportEvents'=>'EXPORT CLICKS CSV','none'=>'No matching records.','last'=>'Recent records','first'=>'Previous','next'=>'Next','page'=>'Page','privacy'=>'Private access · Personal data','note'=>'A GitHub click does not prove the ZIP finished downloading. Registrations from before this version will not have click events.','retention'=>'Delete records as required by your privacy policy.','delete'=>'DELETE DATA','deleteEmail'=>'Delete registrations and clicks for an email address','deleteConfirm'=>'I confirm permanent deletion for this email.','deleteSuccess'=>'Data removed.','deleteFailed'=>'Could not delete data. Check directory permissions.','back'=>'BACK TO WEBSITE','notready'=>'Protected dashboard: setup required','setupOk'=>'Administrator configured. Please sign in.','details'=>'Registration details','limit'=>'Export the full CSV for detailed analysis.','logged'=>'Signed in']
];
$t=$dictionary[$L];
$csrf=(string)$_SESSION['csrf'];
function validCsrf(string $csrf):bool{return hash_equals($csrf,(string)($_POST['csrf']??''));}
function readRecords(string $filename):array{
 $rows=[];$fp=@fopen($filename,'rb');if(!$fp)return $rows;
 if(!flock($fp,LOCK_SH)){fclose($fp);return $rows;}
 $header=fgetcsv($fp);if(!is_array($header)){$header=[];}
 while(($row=fgetcsv($fp))!==false){if(count($row)!==count($header))continue;$rows[]=array_combine($header,$row);}
 flock($fp,LOCK_UN);fclose($fp);return $rows;
}
function safeCsvCell(string $value):string{return preg_match('/^\s*[=+\-@\t\r]/u',$value)?"'".$value:$value;}
function deleteByEmail(string $file,string $email):bool{
 if(!is_file($file))return true;
 $fp=@fopen($file,'c+b');if(!$fp)return false;
 if(!flock($fp,LOCK_EX)){fclose($fp);return false;}
 rewind($fp);$header=fgetcsv($fp);if(!is_array($header)||!in_array('email',$header,true)){flock($fp,LOCK_UN);fclose($fp);return false;}
 $idx=array_search('email',$header,true);$rows=[];
 while(($row=fgetcsv($fp))!==false){if(count($row)!==count($header))continue;if(strcasecmp(trim($row[$idx]),trim($email))!==0)$rows[]=$row;}
 rewind($fp);ftruncate($fp,0);fputcsv($fp,$header);
 foreach($rows as $row){fputcsv($fp,$row);}fflush($fp);flock($fp,LOCK_UN);fclose($fp);return true;
}
function safeConfig(string $file):array{
 if(!is_file($file))return [];
 $data=require $file;
 return is_array($data)&&!empty($data['password_hash'])?$data:[];
}
$config=safeConfig($configFile);
$configured=isset($config['password_hash']);
$auth=(bool)($_SESSION['onehub_admin_auth']??false)&&$configured;
$error='';$message='';
$action=(string)($_POST['action']??'');
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!validCsrf($csrf)){$error=$t['invalid'];}
 elseif($action==='setup'&&!$configured){
   $key=@file_get_contents($setupFile);$received=(string)($_POST['setup_key']??'');$p=(string)($_POST['password']??'');$p2=(string)($_POST['password_repeat']??'');
   if(!is_string($key)||strlen(trim($key))<40||!hash_equals(trim($key),$received)||strlen($p)<12||strlen($p)>128||!hash_equals($p,$p2)){$error=$t['invalid'];}
   else{
    $hash=password_hash($p,PASSWORD_DEFAULT);$pepper=bin2hex(random_bytes(24));
    $php='<?php return '.var_export(['password_hash'=>$hash,'key'=>$pepper,'created_at'=>gmdate(DATE_ATOM)],true).';'."\n";
    $created=@file_put_contents($configFile,$php,LOCK_EX);
    if($created!==false){@chmod($configFile,0600);@unlink($setupFile);$configured=true;$config=safeConfig($configFile);session_regenerate_id(true);$message=$t['setupOk'];}
    else{$error=$t['setupErr'];}
   }
 }
 elseif($action==='login'&&$configured){
   $ipHash=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??''),(string)($config['key']??'fallback'));
   $ratePath=$base.'/login_attempts.json';$now=time();$blocked=false;
   $rateFp=@fopen($ratePath,'c+b');
   if(!$rateFp){$blocked=true;}else{
     flock($rateFp,LOCK_EX);rewind($rateFp);$buf=stream_get_contents($rateFp);$state=json_decode($buf?:'{}',true);if(!is_array($state))$state=[];
     foreach($state as $key=>$value){if(($value['until']??0)<$now-86400)unset($state[$key]);}
     $item=$state[$ipHash]??['count'=>0,'until'=>0];
     $blocked=($item['count']>=6&&$item['until']>$now);
     $ok=!$blocked&&password_verify((string)($_POST['password']??''),(string)$config['password_hash']);
     if($ok){unset($state[$ipHash]);}else{$item['count']=($item['until']<$now?0:(int)$item['count'])+1;$item['until']=$now+($item['count']>=6?900:300);$state[$ipHash]=$item;}
     rewind($rateFp);ftruncate($rateFp,0);fwrite($rateFp,json_encode($state));fflush($rateFp);flock($rateFp,LOCK_UN);fclose($rateFp);@chmod($ratePath,0600);
     if($ok){session_regenerate_id(true);$_SESSION['onehub_admin_auth']=true;$_SESSION['onehub_admin_at']=$now;header('Location: admin-descargas.php?lang='.$L,true,303);exit;}
   }
   $error=$t['wrong'];
 }
 elseif($action==='logout'){
   $_SESSION['onehub_admin_auth']=false;session_regenerate_id(true);header('Location: admin-descargas.php?lang='.$L,true,303);exit;
 }
 elseif($action==='delete'&&$auth){
   $deleteEmail=strtolower(trim((string)($_POST['delete_email']??'')));
   if(!filter_var($deleteEmail,FILTER_VALIDATE_EMAIL)||($_POST['confirm']??'')!=='yes')$error=$t['invalid'];
   else{
     $ok=deleteByEmail($base.'/lead_requests.csv',$deleteEmail)&&deleteByEmail($base.'/download_events.csv',$deleteEmail);
     if($ok){header('Location: admin-descargas.php?lang='.$L.'&deleted=1',true,303);exit;}
     $error=$t['deleteFailed'];
   }
 }
}
$auth=(bool)($_SESSION['onehub_admin_auth']??false)&&$configured;
if($auth&&isset($_SESSION['onehub_admin_at'])&&time()-(int)$_SESSION['onehub_admin_at']>7200){$auth=false;$_SESSION['onehub_admin_auth']=false;}
if($auth)$_SESSION['onehub_admin_at']=time();
$leads=$auth?readRecords($base.'/lead_requests.csv'):[];
$events=$auth?readRecords($base.'/download_events.csv'):[];
$search=trim(function_exists('mb_substr')?mb_substr((string)($_GET['q']??''),0,100):substr((string)($_GET['q']??''),0,100));
$ed=(string)($_GET['edition']??'all');if(!in_array($ed,['all','clean','demo','completo'],true))$ed='all';
$tab=($_GET['tab']??'leads')==='clicks'?'clicks':'leads';
$rows=$tab==='leads'?$leads:$events;
$matching=array_values(array_filter($rows,static function(array $r)use($search,$ed):bool{
 if($ed!=='all'&&($r['edition']??'')!==$ed)return false;
 if($search==='')return true;
 $find=static fn(string $t):bool=>function_exists('mb_stripos')?mb_stripos($t,$search)!==false:stripos($t,$search)!==false;
 return $find((string)($r['email']??''))||$find((string)($r['full_name']??''));
}));
usort($matching,static fn(array $a,array $b):int=>strcmp((string)($b['created_utc']??''),(string)($a['created_utc']??'')));
$perPage=30;$page=max(1,min(1000000,(int)($_GET['page']??1)));$pages=max(1,(int)ceil(count($matching)/$perPage));$page=min($page,$pages);
$filtered=array_slice($matching,($page-1)*$perPage,$perPage);
if($auth&&isset($_GET['export'])&&in_array($_GET['export'],['leads','clicks'],true)){
 $type=(string)$_GET['export'];$csvRows=$type==='leads'?$leads:$events;
 $columns=$type==='leads'?['created_utc','full_name','email','edition','lang']:['created_utc','email','edition','lang'];
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="onehub_'.($type==='leads'?'registros':'clics').'_'.gmdate('Ymd').'.csv"');
 $out=fopen('php://output','wb');fwrite($out,"\xef\xbb\xbf");fputcsv($out,$columns);
 foreach($csvRows as $r){fputcsv($out,array_map(static fn(string $c):string=>safeCsvCell((string)($r[$c]??'')),$columns));}
 fclose($out);exit;
}
$totalLeads=count($leads);$totalClicks=count($events);$uniqueEmails=count(array_unique(array_filter(array_column($leads,'email'))));
$byEd=['clean'=>0,'demo'=>0,'completo'=>0];foreach($events as $r){$key=$r['edition']??'';if(isset($byEd[$key]))$byEd[$key]++;}
function queryUrl(array $add=[]):string{$args=$_GET;unset($args['export']);$args=array_merge($args,$add);return 'admin-descargas.php?'.http_build_query($args);}
?><!doctype html><html lang="<?=e($L==='pt'?'pt-BR':$L)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?=e($t['admin'])?> · ONE HUB</title>
<style>
:root{--blue:#003865;--sky:#00a9e0;--orange:#ff5300;--bg:#f3f7fb;--ink:#18354b;--muted:#687e91;--line:#dce5ef}*{box-sizing:border-box}body{margin:0;font:15px/1.55 system-ui,-apple-system,'Segoe UI',sans-serif;background:var(--bg);color:var(--ink)}a{color:#0074c7;text-decoration:none}a:hover{text-decoration:underline}button,input,select{font:inherit}.wrap{max-width:1190px;margin:auto;padding:24px 22px}.top{background:var(--blue);color:white}.topin{display:flex;gap:16px;align-items:center;justify-content:space-between}.brand{display:flex;gap:12px;align-items:center}.logo{width:42px;height:42px;display:grid;place-items:center;background:var(--sky);color:var(--blue);border-radius:12px;font-weight:900}.brand b{font-size:17px;letter-spacing:.01em}.brand small{display:block;opacity:.7}.links{display:flex;align-items:center;gap:13px}.links a{color:#fff;opacity:.85}.links a[aria-current=page]{color:#ffc72c;opacity:1;font-weight:800}.card{padding:23px;background:white;border-radius:18px;border:1px solid var(--line);box-shadow:0 10px 35px rgba(0,47,87,.045)}.narrow{max-width:560px;margin:48px auto}.eyebrow{text-transform:uppercase;letter-spacing:.08em;font-size:12px;font-weight:800;color:#0074c7}.title{font-size:clamp(23px,3vw,36px);color:var(--blue);line-height:1.15;margin:8px 0}.sub{color:var(--muted);margin:0 0 24px}.field{display:flex;flex-direction:column;gap:6px;min-width:0;margin:0 0 16px;font-weight:700;font-size:13px}.field input,.field select{width:100%;min-height:43px;padding:10px 12px;border:1px solid #bdccd8;border-radius:10px;background:white;color:var(--ink)}.field input:focus,.field select:focus{outline:3px solid rgba(0,169,224,.16);border-color:#00a9e0}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--blue);color:white;border:0;border-radius:10px;min-height:43px;padding:10px 17px;cursor:pointer;font-weight:800;font-size:13px;text-decoration:none!important}.btn:hover{background:#005282;color:white}.btn.orange{background:var(--orange)}.btn.orange:hover{background:#d84800}.btn.ghost{background:#f1f6fb;color:var(--blue)}.btn.danger{background:#a6382e}.box-error{background:#fff0ed;color:#a6382e;padding:11px 15px;border-radius:9px;margin-bottom:14px}.box-ok{background:#eef9e8;color:#34751d;padding:11px 15px;border-radius:9px;margin-bottom:14px}.kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin:24px 0}.kpi{background:white;border:1px solid var(--line);padding:15px 14px;border-radius:15px;min-width:0}.kpi small{color:var(--muted);font-size:12px;font-weight:700;display:block}.kpi strong{font-size:clamp(20px,2.8vw,31px);font-weight:850;display:block;color:var(--blue)}.filter{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.filter .field{flex:1 1 220px;margin:0}.filter .select{flex:0 0 170px}.toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin:24px 0 12px}.tabs{display:flex;gap:7px}.tab{padding:10px 14px;border:1px solid var(--line);background:#fff;border-radius:9px;font-weight:750}.tab.active{background:#003865;color:white}.tablewrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:650px;text-align:left}th{font-size:12px;color:#61798a;text-transform:uppercase;letter-spacing:.03em;background:#f5f8fb}th,td{padding:12px 14px;border-bottom:1px solid #e7edf2}td{font-size:13px}td strong{font-weight:800}.pill{display:inline-block;background:#e8f7ff;color:#00649a;border-radius:100px;padding:3px 8px;font-size:11px;font-weight:800}.actions{display:flex;gap:9px;flex-wrap:wrap}.pager{display:flex;gap:12px;justify-content:center;align-items:center;margin:20px}.quiet{font-size:13px;color:var(--muted);margin:12px 0}.dangerbox{margin-top:24px;border:1px solid #f2cccc}.dangerbox h2{font-size:17px;margin:0 0 8px}.inline{display:flex;gap:12px;flex-wrap:wrap;align-items:end}.inline .field{flex:1 1 260px}.confirm{display:flex;gap:8px;align-items:start;margin-bottom:14px;font-size:13px}.confirm input{margin-top:4px}form.inlineform{display:inline;margin:0}.footer{margin:20px 0;text-align:center;color:var(--muted);font-size:12px}
@media(max-width:980px){.kpis{grid-template-columns:repeat(3,1fr)}}@media(max-width:620px){.wrap{padding:18px 12px}.kpis{grid-template-columns:repeat(2,1fr)}.topin{flex-direction:column;align-items:start}.card{padding:16px}.filter .select{flex:1 1 170px}.links{gap:9px}}
</style></head><body><header class="top"><div class="wrap topin"><div class="brand"><span class="logo">ONE</span><div><b>ONE HUB COMMUNITY</b><small><?=e($t['admin'])?></small></div></div><div class="links">
<?php foreach(['es'=>'ES','pt'=>'PT','en'=>'EN'] as $v=>$label):?><a href="<?=e(queryUrl(['lang'=>$v]))?>" <?=$L===$v?'aria-current="page"':''?>><?=$label?></a><?php endforeach; ?>
<a href="index.php">↗ <?=e($t['back'])?></a>
<?php if($auth):?><form method="post" class="inlineform"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="lang" value="<?=e($L)?>"><button class="btn ghost" type="submit"><?=e($t['logout'])?></button></form><?php endif;?></div></div></header>
<main class="wrap">
<?php if(!$configured||!$auth):?>
<div class="card narrow"><span class="eyebrow"><?=e($configured?$t['login']:$t['notready'])?></span><h1 class="title"><?=e($configured?$t['admin']:$t['setup'])?></h1><p class="sub"><?=e($configured?$t['intro']:$t['setupBody'])?></p>
<?php if($error!==''):?><div class="box-error" role="alert"><?=e($error)?></div><?php endif; ?>
<?php if($message!==''):?><div class="box-ok"><?=e($message)?></div><?php endif; ?>
<?php if(!$configured&&!is_file($setupFile)):?><div class="box-error"><?=e($t['setupMissing'])?></div><?php else:?>
<form method="post" action="admin-descargas.php" autocomplete="off"><input type="hidden" name="action" value="<?=$configured?'login':'setup'?>"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="lang" value="<?=e($L)?>">
<?php if(!$configured):?><label class="field"><span><?=e($t['token'])?></span><input type="password" name="setup_key" autocomplete="off" required minlength="40"></label><?php endif; ?>
<label class="field"><span><?=e($t['password'])?></span><input type="password" name="password" required <?=!$configured?'minlength="12"':''?> autocomplete="<?=$configured?'current-password':'new-password'?>"></label>
<?php if(!$configured):?><label class="field"><span><?=e($t['passwordRepeat'])?></span><input type="password" name="password_repeat" required minlength="12" autocomplete="new-password"></label><?php endif;?>
<button type="submit" class="btn orange"><?=e($configured?$t['signin']:$t['create'])?></button></form>
<?php endif; ?></div>
<?php else:?>
<div class="eyebrow">ONE HUB COMMUNITY · v1.0.0</div><h1 class="title"><?=e($t['dashboard'])?></h1><p class="sub"><?=e($t['intro'])?> · <?=e($t['privacy'])?></p>
<?php if(isset($_GET['deleted'])):?><div class="box-ok"><?=e($t['deleteSuccess'])?></div><?php endif;?>
<?php if($error!==''):?><div class="box-error"><?=e($error)?></div><?php endif;?>
<div class="kpis"><div class="kpi"><small><?=e($t['leads'])?></small><strong><?=number_format($totalLeads)?></strong></div><div class="kpi"><small><?=e($t['unique'])?></small><strong><?=number_format($uniqueEmails)?></strong></div><div class="kpi"><small><?=e($t['clicks'])?></small><strong><?=number_format($totalClicks)?></strong></div>
<?php foreach(['clean','demo','completo'] as $k):?><div class="kpi"><small><?=e($t[$k==='completo'?'complete':$k])?> · <?=e($t['clicks'])?></small><strong><?=number_format($byEd[$k])?></strong></div><?php endforeach;?></div>
<div class="toolbar"><div class="tabs"><a class="tab <?=$tab==='leads'?'active':''?>" href="<?=e(queryUrl(['tab'=>'leads','page'=>1]))?>"><?=e($t['leads'])?></a><a class="tab <?=$tab==='clicks'?'active':''?>" href="<?=e(queryUrl(['tab'=>'clicks','page'=>1]))?>"><?=e($t['clicks'])?></a></div><div class="actions"><a class="btn ghost" href="<?=e(queryUrl(['export'=>'leads']))?>"><?=e($t['exportLeads'])?></a><a class="btn ghost" href="<?=e(queryUrl(['export'=>'clicks']))?>"><?=e($t['exportEvents'])?></a></div></div>
<div class="card"><form class="filter" method="get"><input type="hidden" name="lang" value="<?=e($L)?>"><input type="hidden" name="tab" value="<?=e($tab)?>"><label class="field"><span><?=e($t['search'])?></span><input name="q" value="<?=e($search)?>" type="search" maxlength="100"></label><label class="field select"><span><?=e($t['edition'])?></span><select name="edition"><option value="all"><?=e($t['all'])?></option><?php foreach(['clean','demo','completo'] as $k):?><option value="<?=$k?>" <?=$k===$ed?'selected':''?>><?=e($t[$k==='completo'?'complete':$k])?></option><?php endforeach;?></select></label><button class="btn" type="submit"><?=e($t['filter'])?></button></form>
<div class="toolbar"><strong><?=e($t['details'])?>: <?=count($matching)?></strong><span class="quiet"><?=e($t['page'])?> <?=$page?> / <?=$pages?></span></div><div class="tablewrap"><table><thead><tr><th><?=e($t['date'])?></th><?php if($tab==='leads'):?><th><?=e($t['name'])?></th><?php endif;?><th><?=e($t['email'])?></th><th><?=e($t['edition'])?></th><th><?=e($t['language'])?></th></tr></thead><tbody>
<?php if(!$filtered):?><tr><td colspan="<?=$tab==='leads'?5:4?>"><?=e($t['none'])?></td></tr><?php endif;?>
<?php foreach($filtered as $r):?><tr><td><?=e((string)($r['created_utc']??''))?></td><?php if($tab==='leads'):?><td><strong><?=e((string)($r['full_name']??''))?></strong></td><?php endif;?><td><?=e((string)($r['email']??''))?></td><td><span class="pill"><?=e(strtoupper((string)($r['edition']??'')))?></span></td><td><?=e(strtoupper((string)($r['lang']??'')))?></td></tr><?php endforeach;?></tbody></table></div>
<div class="pager"><?php if($page>1):?><a class="btn ghost" href="<?=e(queryUrl(['page'=>$page-1]))?>">‹ <?=e($t['first'])?></a><?php endif;?><span><?=e($t['page'])?> <?=$page?> / <?=$pages?></span><?php if($page<$pages):?><a class="btn ghost" href="<?=e(queryUrl(['page'=>$page+1]))?>"><?=e($t['next'])?> ›</a><?php endif;?></div><p class="quiet"><?=e($t['note'])?></p></div>
<div class="card dangerbox"><h2><?=e($t['deleteEmail'])?></h2><p class="quiet"><?=e($t['retention'])?></p><form method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="lang" value="<?=e($L)?>"><label class="field"><span><?=e($t['email'])?></span><input type="email" name="delete_email" maxlength="254" required></label><label class="confirm"><input type="checkbox" name="confirm" value="yes" required><span><?=e($t['deleteConfirm'])?></span></label><button class="btn danger" type="submit"><?=e($t['delete'])?></button></form></div>
<?php endif; ?></main><footer class="footer">ONE HUB Community · Sócrates Creative Studio · Private administration</footer></body></html>
