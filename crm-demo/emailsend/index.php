<?php
// EMAILSEND ONE v0.9.61 — public image delivery.
// This runs BEFORE bootstrap/session/database, so email clients can fetch images
// without authentication and without depending on a separate PHP entrypoint.
if (($_GET['route'] ?? '') === 'image_asset') {
    $relative = trim((string)($_GET['f'] ?? ''));
    $relative = str_replace('\\', '/', $relative);
    $relative = ltrim($relative, '/');

    if (!preg_match('~^\d{4}/\d{2}/[a-f0-9]{16,128}\.(?:jpg|jpeg|png|gif|webp)$~i', $relative)) {
        http_response_code(404);
        exit;
    }

    $uploadsRoot = realpath(__DIR__ . '/uploads');
    $candidate = __DIR__ . '/uploads/' . $relative;
    $file = realpath($candidate);

    if ($uploadsRoot === false || $file === false || !is_file($file) || !is_readable($file)) {
        http_response_code(404);
        exit;
    }

    $rootPrefix = rtrim(str_replace('\\', '/', $uploadsRoot), '/') . '/';
    $fileNorm = str_replace('\\', '/', $file);
    if (!str_starts_with($fileNorm, $rootPrefix)) {
        http_response_code(404);
        exit;
    }

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];
    if (!isset($mimeMap[$ext])) {
        http_response_code(404);
        exit;
    }

    $size = filesize($file);
    $mtime = filemtime($file) ?: time();
    $etag = '"' . sha1($relative . '|' . $size . '|' . $mtime) . '"';

    if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
        http_response_code(304);
        exit;
    }

    header('Content-Type: ' . $mimeMap[$ext]);
    header('Content-Length: ' . (string)$size);
    header('Content-Disposition: inline; filename="' . basename($file) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    readfile($file);
    exit;
}

require __DIR__.'/includes/bootstrap.php';

// v0.8.8: all client portals are routed through index.php.
// Some shared hosts/WAFs block direct access to files named portal-*.php.
$portalView=$_GET['portal']??null;
if($portalView!==null){
    $portalMap=[
        'inicio'=>'portal.php',
        'campanas'=>'portal-campanas.php',
        'reportes'=>'portal-reportes.php',
        'ayuda'=>'portal-ayuda.php',
    ];
    if(!isset($portalMap[$portalView])){http_response_code(404);exit('Portal no encontrado.');}
    require __DIR__.'/'.$portalMap[$portalView];
    exit;
}

// v0.9.1 · Instalación empresarial.
// La landing comercial vive fuera de esta aplicación (por ejemplo, emailsend.uno).
// En una instalación de cliente, la raíz abre directamente login o dashboard.
$route=$_GET['route']??(current_user()?'dashboard':'login');

if($route==='login'){
 if(current_user()) redirect('index.php');
 $error=''; if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();if(attempt_login($_POST['email']??'',$_POST['password']??''))redirect('index.php');$error='Email o contraseña incorrectos.';}
 ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/app.css?v=<?=@filemtime(__DIR__.'/assets/app.css')?>"><title><?=e(APP_NAME)?></title></head><body class="auth-screen admin-auth-screen"><div class="auth-effects" aria-hidden="true"><span class="fx-orb fx-orb-a"></span><span class="fx-orb fx-orb-b"></span><span class="fx-orb fx-orb-c"></span><span class="fx-wave fx-wave-a"></span><span class="fx-wave fx-wave-b"></span><span class="fx-grid"></span><span class="fx-mailmark"></span></div><main class="auth-stage"><section class="auth-card"><img src="assets/logo-emailsend.png" alt="EMAILSEND" class="auth-logo"><div class="auth-kicker">ADMINISTRACIÓN</div><h1>Bienvenido</h1><p class="auth-lead">Campañas, contactos y reportes en un solo lugar.</p><?php if($error):?><div class="flash error"><?=e($error)?></div><?php endif?><form method="post" class="auth-form"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><label>Email<div class="input-with-icon"><span aria-hidden="true">✉</span><input type="email" name="email" placeholder="Ingresa tu email" autocomplete="username" required autofocus></div></label><label>Contraseña<div class="input-with-icon"><span aria-hidden="true">⌑</span><input id="admin-pass" type="password" name="password" placeholder="Ingresa tu contraseña" autocomplete="current-password" required><button class="password-toggle" type="button" data-toggle-password="admin-pass" aria-label="Mostrar contraseña">◉</button></div></label><button class="btn auth-submit" type="submit">Ingresar</button></form><div class="auth-divider"><span>Portales para clientes</span></div><div class="auth-portal-links"><a href="index.php?portal=campanas"><b>Campañas</b><small>Revisión de piezas y estado</small><span>→</span></a><a href="index.php?portal=reportes"><b>Reportes</b><small>Métricas y resultados</small><span>→</span></a></div></section><aside class="auth-promo"><div class="auth-promo-badge">EMAILSEND ONE</div><h2>Email marketing simple. Resultados claros.</h2><p>Diseña, envía y entrega a cada cliente un espacio privado con sus campañas y sus reportes.</p><div class="promo-pills"><span>Editor visual + HTML</span><span>Tracking real</span><span>Portal por cliente</span></div><div class="auth-developer"><span>Desarrollado por</span><img src="assets/developer-logo-yellow.png" alt="Sócrates AI Creative Studio"></div></aside></main><script>(function(){document.querySelectorAll('[data-toggle-password]').forEach(function(b){b.addEventListener('click',function(){var i=document.getElementById(b.getAttribute('data-toggle-password'));if(!i)return;var show=i.type==='password';i.type=show?'text':'password';b.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');});});})();</script></body></html><?php exit;
}
if($route==='logout'){session_destroy();redirect('index.php?route=login');}
if($route==='compile'){require_login();verify_csrf();$blocks=json_decode($_POST['blocks_json']??'[]',true)?:[];header('Content-Type:text/html; charset=utf-8');echo compile_campaign_html($blocks);exit;}
require_login();

// v0.9.25 · Buscador global respetando permisos y listas autorizadas.
if($route==='global_search'){
 $q=trim((string)($_GET['q']??''));header('Content-Type: application/json; charset=utf-8');
 if(function_exists('mb_substr'))$q=mb_substr($q,0,80);else$q=substr($q,0,80);
 if(strlen($q)<2){echo json_encode(['ok'=>true,'results'=>[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
 $tid=current_tenant_id();$like='%'.$q.'%';$results=[];
 $add=function(string $type,string $title,string $subtitle,string $url)use(&$results){if(count($results)<18)$results[]=compact('type','title','subtitle','url');};
 $shortcuts=[
  ['dashboard','Dashboard','Resumen general y accesos rápidos','index.php'],
  ['campaigns','Campañas','Crear, editar y gestionar campañas','index.php?route=campaigns'],
  ['contacts','Contactos','Listas, contactos e importación','index.php?route=contacts'],
  ['reports','Reportes','Aperturas, clics y rendimiento','index.php?route=reports'],
  ['help','Centro de ayuda','Guías según tus permisos','index.php?route=help']
 ];
 foreach($shortcuts as $sc){if(!current_user_can($sc[0]))continue;$hay=strtolower($sc[1].' '.$sc[2]);if(strpos($hay,strtolower($q))!==false)$add('Sección',$sc[1],$sc[2],$sc[3]);}
 if(current_user_can('campaigns')||current_user_can('reports')){
  $st=db()->prepare('SELECT id,name,subject,status FROM campaigns WHERE tenant_id=? AND (name LIKE ? OR subject LIKE ?) ORDER BY COALESCE(sent_at,created_at) DESC LIMIT 24');$st->execute([$tid,$like,$like]);
  foreach($st as $r){if(!campaign_lists_are_authorized((int)$r['id'],$tid))continue;
   if(current_user_can('campaigns'))$add('Campaña',(string)$r['name'],((string)$r['subject']?:'Sin asunto').' · '.(string)$r['status'],'index.php?route=campaign_edit&id='.(int)$r['id']);
   if(current_user_can('reports'))$add('Reporte',(string)$r['name'],'Reporte · '.((string)$r['subject']?:'Sin asunto'),'index.php?route=report&id='.(int)$r['id']);
  }
 }
 if(current_user_can('contacts')){
  $allowed=user_allowed_list_ids($tid);$sql='SELECT c.id,c.name,c.email,c.company_name,l.name list_name FROM contacts c LEFT JOIN contact_lists l ON l.id=c.list_id WHERE c.tenant_id=? AND (c.email LIKE ? OR c.name LIKE ? OR c.company_name LIKE ?)';$params=[$tid,$like,$like,$like];
  if($allowed!==null){if($allowed){$sql.=' AND c.list_id IN ('.implode(',',array_fill(0,count($allowed),'?')).')';$params=array_merge($params,$allowed);}else{$sql.=' AND 1=0';}}
  $sql.=' ORDER BY c.id DESC LIMIT 8';$st=db()->prepare($sql);$st->execute($params);foreach($st as $r)$add('Contacto',(string)($r['name']?:$r['email']),((string)$r['email']).($r['list_name']?' · '.$r['list_name']:''),'index.php?route=contacts&q='.urlencode((string)$r['email']));
  $sql='SELECT id,name FROM contact_lists WHERE tenant_id=? AND name LIKE ?';$params=[$tid,$like];if($allowed!==null){if($allowed){$sql.=' AND id IN ('.implode(',',array_fill(0,count($allowed),'?')).')';$params=array_merge($params,$allowed);}else{$sql.=' AND 1=0';}}$sql.=' ORDER BY name LIMIT 6';$st=db()->prepare($sql);$st->execute($params);foreach($st as $r)$add('Lista',(string)$r['name'],'Lista de contactos','index.php?route=contacts&list_id='.(int)$r['id']);
 }
 echo json_encode(['ok'=>true,'results'=>$results],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}

// v0.9.2 · Protección de paneles según permisos del usuario.
$pagePermissions=['dashboard'=>'dashboard','contacts'=>'contacts','campaigns'=>'campaigns','delivery_lab'=>'campaigns','reports'=>'reports','clients'=>'clients','help'=>'help'];
if(isset($pagePermissions[$route])) require_permission($pagePermissions[$route],'view');

if($route==='campaign_preview'){
 $id=(int)($_GET['id']??0);$tid=current_tenant_id();$st=db()->prepare('SELECT html FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);$html=$st->fetchColumn();
 if(!$html){http_response_code(404);exit('Campaña no encontrada');}
 if(!campaign_lists_are_authorized($id,$tid)){http_response_code(403);exit('No autorizado');}
 header('Content-Type:text/html; charset=utf-8');echo $html;exit;
}

// Actions
if($_SERVER['REQUEST_METHOD']==='POST'){
 if($route!=='compile') verify_csrf();
 $actionPermissions=[
  'contact_save'=>['contacts','create'],'contact_delete'=>['contacts','delete'],'contacts_bulk_delete'=>['contacts','delete'],'contact_import'=>['contacts','import'],'contact_import_configure'=>['contacts','import'],'contact_import_chunk'=>['contacts','import'],
  'list_save'=>['contacts','create'],'list_update'=>['contacts','edit'],'list_delete'=>['contacts','delete'],
  'campaign_save'=>['campaigns','edit'],'campaign_queue'=>['campaigns','send'],'campaign_pause'=>['campaigns','send'],'campaign_retry_failed'=>['campaigns','send'],'campaign_delete'=>['campaigns','delete'],'campaign_duplicate'=>['campaigns','create'],'campaign_test'=>['campaigns','send'],'delivery_lab_send'=>['campaigns','send']
 ];
 if(isset($actionPermissions[$route])) require_permission($actionPermissions[$route][0],$actionPermissions[$route][1]);
 if($route==='switch_tenant'){ require_admin(); $_SESSION['effective_tenant_id']=(int)($_POST['tenant_id']?:current_user()['tenant_id']); redirect($_SERVER['HTTP_REFERER']??'index.php'); }
 if($route==='contact_save'){
  require_permission('contacts','create');
  $tid=current_tenant_id(); if(is_admin()&&!empty($_POST['tenant_id']))$tid=(int)$_POST['tenant_id'];
  $email=strtolower(trim($_POST['email']??'')); if(!filter_var($email,FILTER_VALIDATE_EMAIL)){flash('error','Email inválido.');redirect('index.php?route=contacts');}
  $listId=(int)($_POST['list_id']??0);if($listId){$q=db()->prepare('SELECT id FROM contact_lists WHERE id=? AND tenant_id=?');$q->execute([$listId,$tid]);if(!$q->fetchColumn() || !current_user_can_access_list($listId,$tid)){$listId=0;}}
  if(!is_admin() && !$listId){flash('error','Selecciona una lista autorizada para guardar el contacto.');redirect('index.php?route=contacts');}
  $fields=['name','company_name','sales_band','company_type','employees','annual_income','economic_activity','region','province','commune','legal_representative','phone_fixed','phone_mobile'];
  $vals=[];foreach($fields as $f)$vals[$f]=contact_utf8($_POST[$f]??'');if($vals['name']==='')$vals['name']=$vals['legal_representative']?:$vals['company_name'];
  $sql='INSERT INTO contacts(tenant_id,list_id,name,company_name,sales_band,company_type,employees,annual_income,economic_activity,region,province,commune,legal_representative,phone_fixed,phone_mobile,email,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"active",NOW()) ON DUPLICATE KEY UPDATE list_id=VALUES(list_id),name=IF(VALUES(name)<>"",VALUES(name),name),company_name=IF(VALUES(company_name)<>"",VALUES(company_name),company_name),sales_band=IF(VALUES(sales_band)<>"",VALUES(sales_band),sales_band),company_type=IF(VALUES(company_type)<>"",VALUES(company_type),company_type),employees=IF(VALUES(employees)<>"",VALUES(employees),employees),annual_income=IF(VALUES(annual_income)<>"",VALUES(annual_income),annual_income),economic_activity=IF(VALUES(economic_activity)<>"",VALUES(economic_activity),economic_activity),region=IF(VALUES(region)<>"",VALUES(region),region),province=IF(VALUES(province)<>"",VALUES(province),province),commune=IF(VALUES(commune)<>"",VALUES(commune),commune),legal_representative=IF(VALUES(legal_representative)<>"",VALUES(legal_representative),legal_representative),phone_fixed=IF(VALUES(phone_fixed)<>"",VALUES(phone_fixed),phone_fixed),phone_mobile=IF(VALUES(phone_mobile)<>"",VALUES(phone_mobile),phone_mobile)';
  $st=db()->prepare($sql);$st->execute([$tid,$listId?:null,$vals['name'],$vals['company_name'],$vals['sales_band'],$vals['company_type'],$vals['employees'],$vals['annual_income'],$vals['economic_activity'],$vals['region'],$vals['province'],$vals['commune'],$vals['legal_representative'],$vals['phone_fixed'],$vals['phone_mobile'],$email]);flash('ok','Contacto guardado.');redirect('index.php?route=contacts');
 }
 if($route==='list_save'){
  require_permission('contacts','create');$tid=current_tenant_id();$clientId=(int)($_POST['client_id']??0);if($clientId){$q=db()->prepare('SELECT id FROM report_clients WHERE id=? AND tenant_id=?');$q->execute([$clientId,$tid]);if(!$q->fetchColumn())$clientId=0;}
  $name=trim($_POST['name']??'');if($name===''){flash('error','Escribe un nombre para la lista.');redirect('index.php?route=contacts');}
  $userIds=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['user_ids']??[])))));
  db()->beginTransaction();try{
   $st=db()->prepare('INSERT INTO contact_lists(tenant_id,client_id,name,created_at) VALUES(?,?,?,NOW())');$st->execute([$tid,$clientId?:null,$name]);$listId=(int)db()->lastInsertId();
   if(current_user_list_access_mode()==='assigned' && !in_array((int)current_user()['id'],$userIds,true))$userIds[]=(int)current_user()['id'];
   if($userIds){$ph=implode(',',array_fill(0,count($userIds),'?'));$q=db()->prepare('SELECT id FROM users WHERE tenant_id=? AND active=1 AND role IN("admin","user") AND id IN ('.$ph.')');$q->execute([$tid,...$userIds]);$valid=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));$ins=db()->prepare('INSERT IGNORE INTO user_contact_lists(user_id,list_id,assigned_at) VALUES(?,?,NOW())');foreach($valid as $uid)$ins->execute([$uid,$listId]);}
   db()->commit();flash('ok','Lista creada'.($userIds?' y permisos de usuarios asignados.':'.'));
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error','No se pudo crear la lista: '.$e->getMessage());}
  redirect('index.php?route=contacts');
 }
 if($route==='list_update'){
  require_permission('contacts','edit');$tid=current_tenant_id();$listId=(int)($_POST['list_id']??0);$name=trim($_POST['name']??'');
  $q=db()->prepare('SELECT id FROM contact_lists WHERE id=? AND tenant_id=?');$q->execute([$listId,$tid]);if(!$q->fetchColumn()||!current_user_can_access_list($listId,$tid)){flash('error','Lista inválida o sin autorización.');redirect('index.php?route=contacts');}
  if($name===''){flash('error','El nombre de la lista no puede quedar vacío.');redirect('index.php?route=contacts&list_id='.$listId);}
  db()->prepare('UPDATE contact_lists SET name=? WHERE id=? AND tenant_id=?')->execute([$name,$listId,$tid]);flash('ok','Lista actualizada.');redirect('index.php?route=contacts&list_id='.$listId);
 }
 if($route==='list_delete'){
  require_permission('contacts','delete');$tid=current_tenant_id();$listId=(int)($_POST['list_id']??0);
  $q=db()->prepare('SELECT name FROM contact_lists WHERE id=? AND tenant_id=?');$q->execute([$listId,$tid]);$name=$q->fetchColumn();if(!$name||!current_user_can_access_list($listId,$tid)){flash('error','Lista inválida o sin autorización.');redirect('index.php?route=contacts');}
  db()->beginTransaction();try{db()->prepare('UPDATE contacts SET list_id=NULL WHERE tenant_id=? AND list_id=?')->execute([$tid,$listId]);db()->prepare('DELETE FROM contact_lists WHERE id=? AND tenant_id=?')->execute([$listId,$tid]);db()->commit();flash('ok','Lista “'.$name.'” eliminada. Los contactos se conservaron sin lista.');}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error','No se pudo eliminar la lista: '.$e->getMessage());}
  redirect('index.php?route=contacts');
 }
 if($route==='list_permissions_save'){
  require_admin();$tid=current_tenant_id();$listId=(int)($_POST['list_id']??0);$q=db()->prepare('SELECT id FROM contact_lists WHERE id=? AND tenant_id=?');$q->execute([$listId,$tid]);if(!$q->fetchColumn()){flash('error','Lista inválida.');redirect('index.php?route=contacts');}
  $userIds=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['user_ids']??[])))));
  db()->beginTransaction();try{
   db()->prepare('DELETE ucl FROM user_contact_lists ucl JOIN users u ON u.id=ucl.user_id WHERE ucl.list_id=? AND u.tenant_id=?')->execute([$listId,$tid]);
   if($userIds){$ph=implode(',',array_fill(0,count($userIds),'?'));$q=db()->prepare('SELECT id FROM users WHERE tenant_id=? AND active=1 AND role IN("admin","user") AND id IN ('.$ph.')');$q->execute([$tid,...$userIds]);$ins=db()->prepare('INSERT IGNORE INTO user_contact_lists(user_id,list_id,assigned_at) VALUES(?,?,NOW())');foreach($q->fetchAll(PDO::FETCH_COLUMN) as $uid)$ins->execute([(int)$uid,$listId]);}
   db()->commit();flash('ok','Usuarios autorizados para la lista actualizados.');
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error','No se pudieron guardar los permisos: '.$e->getMessage());}
  redirect('index.php?route=contacts&list_id='.$listId);
 }
 if($route==='contact_import'){
  require_permission('contacts','import');
  $tid=current_tenant_id();$list=(int)($_POST['list_id']??0);
  if(!$list){flash('error','Selecciona una lista antes de importar.');redirect('index.php?route=contacts');}
  $q=db()->prepare('SELECT id FROM contact_lists WHERE id=? AND tenant_id=?');$q->execute([$list,$tid]);
  if(!$q->fetchColumn() || !current_user_can_access_list($list,$tid)){flash('error','No tienes autorización para importar contactos en esa lista.');redirect('index.php?route=contacts');}
  $importUserId=(int)($_POST['import_user_id']??0);
  if(is_admin()){
   if($importUserId===0){flash('error','Selecciona primero el usuario responsable de la lista.');redirect('index.php?route=contacts');}
   if($importUserId>0){
    $uq=db()->prepare('SELECT id FROM users WHERE id=? AND tenant_id=? AND active=1 AND role IN("admin","user")');$uq->execute([$importUserId,$tid]);
    if(!$uq->fetchColumn()){flash('error','El usuario seleccionado no es válido.');redirect('index.php?route=contacts');}
    $aq=db()->prepare('SELECT 1 FROM user_contact_lists WHERE user_id=? AND list_id=?');$aq->execute([$importUserId,$list]);
    if(!$aq->fetchColumn()){flash('error','La lista seleccionada no está asignada al usuario elegido.');redirect('index.php?route=contacts');}
   }elseif($importUserId===-1){
    $aq=db()->prepare('SELECT COUNT(*) FROM user_contact_lists WHERE list_id=?');$aq->execute([$list]);
    if((int)$aq->fetchColumn()>0){flash('error','Esa lista tiene usuarios asignados. Selecciona uno de ellos.');redirect('index.php?route=contacts');}
   }
  }
  if(empty($_FILES['csv']['tmp_name']) || ($_FILES['csv']['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK){flash('error','Selecciona un archivo CSV o Excel válido.');redirect('index.php?route=contacts');}
  $original=(string)($_FILES['csv']['name']??'base.csv');$ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
  if(!in_array($ext,['csv','txt','xlsx'],true)){flash('error','Formato no permitido. Usa CSV, TXT o Excel .xlsx. Para archivos .xls antiguos, guárdalos como .xlsx o CSV UTF-8.');redirect('index.php?route=contacts');}
  $jobId=random_token(12);$dest=contact_import_dir().'/'.$jobId.'.csv';
  try{
   if($ext==='xlsx'){
    $xlsx=contact_import_dir().'/'.$jobId.'.xlsx';
    if(!move_uploaded_file($_FILES['csv']['tmp_name'],$xlsx))throw new RuntimeException('No se pudo guardar temporalmente el Excel.');
    contact_xlsx_to_csv($xlsx,$dest);@unlink($xlsx);
   }else{
    if(!move_uploaded_file($_FILES['csv']['tmp_name'],$dest))throw new RuntimeException('No se pudo guardar temporalmente el archivo.');
   }
   $delimiter=contact_csv_delimiter($dest);$fh=fopen($dest,'rb');if(!$fh)throw new RuntimeException('No se pudo abrir el archivo.');
   $headers=fgetcsv($fh,0,$delimiter);if(!is_array($headers)||!$headers){fclose($fh);throw new RuntimeException('El archivo no contiene encabezados válidos.');}
   $headers=array_map('contact_utf8',$headers);$offset=ftell($fh);fclose($fh);
   $customFields=contact_custom_fields($tid);$suggestions=[];foreach($headers as $h)$suggestions[]=contact_import_suggestion($tid,(string)$h,$customFields);
   $samples=contact_import_sample_rows($dest,$delimiter,6);
   $job=['id'=>$jobId,'tenant_id'=>$tid,'list_id'=>$list,'file'=>$dest,'original'=>$original,'delimiter'=>$delimiter,'headers'=>$headers,'suggestions'=>$suggestions,'samples'=>$samples,'data_offset'=>$offset,'offset'=>$offset,'bytes_total'=>max(1,(int)filesize($dest)),'rows'=>0,'emails'=>0,'skipped'=>0,'done'=>false,'stage'=>'mapping','created_at'=>time()];
   contact_import_save_job($job);
   redirect('index.php?route=contacts&import_map='.$jobId);
  }catch(Throwable $e){@unlink($dest);flash('error','No se pudo analizar la base: '.$e->getMessage());redirect('index.php?route=contacts');}
 }
 if($route==='contact_import_configure'){
  $tid=current_tenant_id();$jobId=preg_replace('/[^a-f0-9]/','',(string)($_POST['job']??''));$job=contact_import_load_job($jobId);
  if(!$job || (int)($job['tenant_id']??0)!==$tid){flash('error','La importación ya no está disponible.');redirect('index.php?route=contacts');}
  $list=(int)($job['list_id']??0);if(!$list || !current_user_can_access_list($list,$tid)){flash('error','No tienes autorización para esa lista.');redirect('index.php?route=contacts');}
  $headers=(array)($job['headers']??[]);$mapping=(array)($_POST['mapping']??[]);$stdMap=[];$customMap=[];$standard=contact_standard_fields();
  foreach($headers as $i=>$header){
   $target=(string)($mapping[$i]??'ignore');$type='ignore';$key=null;
   if($target==='new'){
    $fid=contact_find_or_create_custom_field($tid,(string)$header);$customMap[$fid]=(int)$i;$type='custom';$key=(string)$fid;
   }elseif(str_starts_with($target,'std:')){
    $field=substr($target,4);if(isset($standard[$field])&&!array_key_exists($field,$stdMap)){$stdMap[$field]=(int)$i;$type='standard';$key=$field;}
   }elseif(str_starts_with($target,'custom:')){
    $fid=(int)substr($target,7);if($fid && contact_custom_field_by_id($tid,$fid)){$customMap[$fid]=(int)$i;$type='custom';$key=(string)$fid;}
   }
   contact_import_save_alias($tid,(string)$header,$type,$key);
  }
  if(!isset($stdMap['email'])){flash('error','Debes asociar al menos una columna al campo Email antes de importar.');redirect('index.php?route=contacts&import_map='.$jobId);}
  $job['map']=$stdMap;$job['custom_map']=$customMap;$job['stage']='processing';$job['offset']=(int)($job['data_offset']??0);$job['rows']=0;$job['emails']=0;$job['skipped']=0;$job['done']=false;contact_import_save_job($job);
  flash('ok','Mapeo guardado. EMAILSEND recordará estas equivalencias para futuras bases.');redirect('index.php?route=contacts&import_job='.$jobId);
 }
 if($route==='contact_import_chunk'){
  header('Content-Type: application/json; charset=utf-8');
  $jobId=preg_replace('/[^a-f0-9]/','',(string)($_POST['job']??''));$job=contact_import_load_job($jobId);
  if(!$job || (int)($job['tenant_id']??0)!==(int)current_tenant_id()){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Importación no encontrada']);exit;}
  if(($job['stage']??'')!=='processing'){http_response_code(409);echo json_encode(['ok'=>false,'error'=>'La importación aún requiere confirmar el mapeo de columnas.']);exit;}
  if(!empty($job['done'])){echo json_encode(['ok'=>true,'done'=>true,'rows'=>$job['rows'],'emails'=>$job['emails'],'skipped'=>$job['skipped'],'percent'=>100]);exit;}
  $file=(string)$job['file'];$fh=@fopen($file,'rb');if(!$fh){echo json_encode(['ok'=>false,'error'=>'El archivo temporal ya no existe.']);exit;}
  fseek($fh,(int)$job['offset']);$headers=(array)$job['headers'];$map=(array)$job['map'];$customMap=(array)($job['custom_map']??[]);$delimiter=(string)$job['delimiter'];$tid=(int)$job['tenant_id'];$list=(int)$job['list_id'];
  if(!current_user_can_access_list($list,$tid)){fclose($fh);http_response_code(403);echo json_encode(['ok'=>false,'error'=>'Ya no tienes autorización para esta lista.']);exit;}
  $processed=0;$maxRows=500;
  $sql='INSERT INTO contacts(tenant_id,list_id,name,company_name,sales_band,company_type,employees,annual_income,economic_activity,region,province,commune,legal_representative,phone_fixed,phone_mobile,source_data_json,email,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"active",NOW()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),list_id=VALUES(list_id),name=IF(VALUES(name)<>"",VALUES(name),name),company_name=IF(VALUES(company_name)<>"",VALUES(company_name),company_name),sales_band=IF(VALUES(sales_band)<>"",VALUES(sales_band),sales_band),company_type=IF(VALUES(company_type)<>"",VALUES(company_type),company_type),employees=IF(VALUES(employees)<>"",VALUES(employees),employees),annual_income=IF(VALUES(annual_income)<>"",VALUES(annual_income),annual_income),economic_activity=IF(VALUES(economic_activity)<>"",VALUES(economic_activity),economic_activity),region=IF(VALUES(region)<>"",VALUES(region),region),province=IF(VALUES(province)<>"",VALUES(province),province),commune=IF(VALUES(commune)<>"",VALUES(commune),commune),legal_representative=IF(VALUES(legal_representative)<>"",VALUES(legal_representative),legal_representative),phone_fixed=IF(VALUES(phone_fixed)<>"",VALUES(phone_fixed),phone_fixed),phone_mobile=IF(VALUES(phone_mobile)<>"",VALUES(phone_mobile),phone_mobile),source_data_json=VALUES(source_data_json)';
  $ins=db()->prepare($sql);$customIns=$customMap?db()->prepare('INSERT INTO contact_custom_values(contact_id,field_id,value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE value=IF(VALUES(value)<>"",VALUES(value),value)'):null;
  while($processed<$maxRows && ($row=fgetcsv($fh,0,$delimiter))!==false){
   $processed++;$job['rows']++;
   $emailRaw=contact_row_value($row,$map,'email');$emails=contact_extract_emails($emailRaw);if(!$emails){$job['skipped']++;continue;}
   $company=contact_row_value($row,$map,'company_name');$legal=contact_row_value($row,$map,'legal_representative');$name=contact_row_value($row,$map,'name');if($name==='')$name=$legal?:$company;
   $data=['sales_band'=>contact_row_value($row,$map,'sales_band'),'company_type'=>contact_row_value($row,$map,'company_type'),'employees'=>contact_row_value($row,$map,'employees'),'annual_income'=>contact_row_value($row,$map,'annual_income'),'economic_activity'=>contact_row_value($row,$map,'economic_activity'),'region'=>contact_row_value($row,$map,'region'),'province'=>contact_row_value($row,$map,'province'),'commune'=>contact_row_value($row,$map,'commune'),'phone_fixed'=>contact_row_value($row,$map,'phone_fixed'),'phone_mobile'=>contact_row_value($row,$map,'phone_mobile')];
   $source=json_encode(contact_original_row($headers,$row),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
   foreach($emails as $email){
    try{
     $ins->execute([$tid,$list,$name,$company,$data['sales_band'],$data['company_type'],$data['employees'],$data['annual_income'],$data['economic_activity'],$data['region'],$data['province'],$data['commune'],$legal,$data['phone_fixed'],$data['phone_mobile'],$source,$email]);
     $contactId=(int)db()->lastInsertId();if($contactId && $customIns){foreach($customMap as $fieldId=>$idx){$value=contact_utf8($row[(int)$idx]??'');if($value!=='')$customIns->execute([$contactId,(int)$fieldId,$value]);}}
     $job['emails']++;
    }catch(Throwable $e){$job['skipped']++;}
   }
  }
  $job['offset']=ftell($fh);$eof=feof($fh);fclose($fh);$job['done']=$eof;
  if($eof){@unlink($file);$job['file']='';}
  contact_import_save_job($job);$percent=$eof?100:min(99.5,round(((int)$job['offset']/max(1,(int)$job['bytes_total']))*100,1));
  echo json_encode(['ok'=>true,'done'=>$eof,'rows'=>$job['rows'],'emails'=>$job['emails'],'skipped'=>$job['skipped'],'percent'=>$percent],JSON_UNESCAPED_UNICODE);exit;
 }
 if($route==='contact_delete'){
  $id=(int)($_POST['id']??0);$tid=current_tenant_id();$q=db()->prepare('SELECT list_id FROM contacts WHERE id=? AND tenant_id=?');$q->execute([$id,$tid]);$found=$q->fetchColumn();$listId=$found===false?0:(int)$found;
  if($found===false || (!is_admin() && (!$listId || !current_user_can_access_list($listId,$tid)))){flash('error','No tienes permiso para eliminar ese contacto.');redirect('index.php?route=contacts');}
  $st=db()->prepare('DELETE FROM contacts WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);flash('ok','Contacto eliminado.');redirect('index.php?route=contacts');
 }
 if($route==='contacts_bulk_delete'){
  require_permission('contacts','delete');
  $tid=current_tenant_id();
  $ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['ids']??[])))));
  if(count($ids)>1000)$ids=array_slice($ids,0,1000);
  $returnTo=(string)($_POST['return_to']??'index.php?route=contacts');
  if(!str_starts_with($returnTo,'index.php?route=contacts'))$returnTo='index.php?route=contacts';
  if(!$ids){flash('error','Selecciona al menos un contacto para eliminar.');redirect($returnTo);}
  $ph=implode(',',array_fill(0,count($ids),'?'));
  $sql='DELETE FROM contacts WHERE tenant_id=? AND id IN ('.$ph.')';
  $params=[$tid,...$ids];
  if(!is_admin()){
   $allowed=user_allowed_list_ids($tid);
   if($allowed!==null){
    if(!$allowed){flash('error','No tienes listas autorizadas para eliminar contactos.');redirect($returnTo);}
    $sql.=' AND list_id IN ('.implode(',',array_fill(0,count($allowed),'?')).')';
    $params=array_merge($params,$allowed);
   }
  }
  $st=db()->prepare($sql);$st->execute($params);$deleted=$st->rowCount();
  if($deleted<1){flash('error','No se eliminó ningún contacto. Revisa tus permisos o la selección.');}
  else{flash('ok',$deleted.' contacto'.($deleted===1?'':'s').' eliminado'.($deleted===1?'':'s').' correctamente.');}
  redirect($returnTo);
 }
 if($route==='campaign_save'){
  $id=(int)($_POST['id']??0);$tid=current_tenant_id();if($id && !campaign_lists_are_authorized($id,$tid)){flash('error','No tienes autorización para modificar esa campaña.');redirect('index.php?route=campaigns');}$blocks=$_POST['blocks_json']??'[]';$decodedBlocks=json_decode($blocks,true)?:[];$html=compile_campaign_html($decodedBlocks); // v0.9.30: preview, guardado y envío usan exactamente el mismo compilador
  $listIds=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['list_ids']??[])))));
  if($listIds){$ph=implode(',',array_fill(0,count($listIds),'?'));$q=db()->prepare('SELECT id FROM contact_lists WHERE tenant_id=? AND id IN ('.$ph.')');$q->execute([$tid,...$listIds]);$valid=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));if(count($valid)!==count($listIds)){flash('error','Una de las listas seleccionadas ya no existe.');redirect('index.php?route=campaign_edit&id='.$id);}$listIds=$valid;}
  $allowed=user_allowed_list_ids($tid);if($allowed!==null){foreach($listIds as $lid)if(!in_array($lid,$allowed,true)){flash('error','No tienes autorización para utilizar una de las listas seleccionadas.');redirect('index.php?route=campaign_edit&id='.$id);}if(!$listIds){flash('error','Tu usuario debe seleccionar al menos una lista autorizada.');redirect('index.php?route=campaign_edit&id='.$id);}}
  $primary=$listIds[0]??null;$vals=[trim($_POST['name']??''),trim($_POST['subject']??''),trim($_POST['preheader']??''),trim($_POST['from_name']??''),strtolower(trim($_POST['from_email']??'')),$primary,$blocks,$html];
  db()->beginTransaction();try{
   if($id){$st=db()->prepare('UPDATE campaigns SET name=?,subject=?,preheader=?,from_name=?,from_email=?,list_id=?,blocks_json=?,html=?,updated_at=NOW() WHERE id=? AND tenant_id=?');$st->execute([...$vals,$id,$tid]);}
   else{$st=db()->prepare('INSERT INTO campaigns(tenant_id,name,subject,preheader,from_name,from_email,list_id,blocks_json,html,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,"draft",NOW(),NOW())');$st->execute([$tid,...$vals]);$id=(int)db()->lastInsertId();}
   db()->prepare('DELETE FROM campaign_lists WHERE campaign_id=?')->execute([$id]);$ins=db()->prepare('INSERT INTO campaign_lists(campaign_id,list_id) VALUES(?,?)');foreach($listIds as $lid)$ins->execute([$id,$lid]);db()->commit();
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error','No se pudo guardar la audiencia: '.$e->getMessage());redirect('index.php?route=campaign_edit&id='.$id);}
  flash('ok','Campaña guardada.');redirect('index.php?route=campaign_edit&id='.$id);
 }
 if($route==='campaign_queue' && defined('APP_DEMO_MODE') && APP_DEMO_MODE){
  flash('error','MODO DEMO: no se pueden poner campañas en la cola de envío.');redirect('index.php?route=campaigns');
 }
 if($route==='campaign_queue'){
  $id=(int)$_POST['id'];$tid=current_tenant_id();$st=db()->prepare('SELECT * FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);$c=$st->fetch();if(!$c){flash('error','Campaña no encontrada.');redirect('index.php?route=campaigns');}
  if(!campaign_lists_are_authorized($id,$tid)){flash('error','La campaña contiene listas que tu usuario no tiene autorizadas. Solicita acceso antes de enviarla.');redirect('index.php?route=campaigns');}
  if(!in_array($c['status'],['draft','paused'],true)){flash('error','Esta campaña ya fue enviada o está en proceso. Duplica la campaña si deseas volver a enviarla.');redirect('index.php?route=campaigns');}
  $ss=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$ss->execute([$tid]);$cfg=$ss->fetch();$from=$c['from_email']?:($cfg['default_from_email']??'');
  $errors=[];if(!$cfg||empty($cfg['smtp_host']))$errors[]='SMTP no configurado';if(!filter_var($from,FILTER_VALIDATE_EMAIL))$errors[]='remitente inválido';if(trim((string)$c['subject'])==='')$errors[]='asunto vacío';if(trim((string)$c['html'])==='')$errors[]='contenido vacío';
  $recipientCount=campaign_audience_count($id,$tid,$c['list_id']?:(null));if($recipientCount<1)$errors[]='no hay contactos activos en la audiencia seleccionada';
  if($errors){flash('error','No se puede enviar: '.implode(' · ',$errors).'.');redirect('index.php?route=campaigns');}
  db()->beginTransaction();try{
   $existing=db()->prepare('SELECT COUNT(*) FROM deliveries WHERE campaign_id=?');$existing->execute([$id]);$existingCount=(int)$existing->fetchColumn();
   if($existingCount>0 && $c['status']==='paused'){db()->prepare('UPDATE campaigns SET status="queued",updated_at=NOW() WHERE id=?')->execute([$id]);db()->commit();flash('ok','Campaña reanudada. Los envíos pendientes siguen en cola.');redirect('index.php?route=campaigns');}
   if($existingCount>0)throw new RuntimeException('La campaña ya tiene historial de envíos. Duplica la campaña para un nuevo envío.');
   $cs=campaign_audience_contacts($id,$tid,$c['list_id']?:(null));
   $ins=db()->prepare('INSERT INTO deliveries(tenant_id,campaign_id,contact_id,email,token,status,created_at) VALUES(?,?,?,?,?,"queued",NOW())');$n=0;foreach($cs as $ct){$ins->execute([$tid,$id,$ct['id'],$ct['email'],random_token(24)]);$n++;}
   db()->prepare('UPDATE campaigns SET status="queued",updated_at=NOW() WHERE id=?')->execute([$id]);db()->commit();flash('ok',"Campaña preparada para $n destinatarios. La cola será procesada por cron/send.php.");
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}
  redirect('index.php?route=campaigns');
 }
 if($route==='campaign_pause'){
  $id=(int)($_POST['id']??0);$tid=current_tenant_id();if(!campaign_lists_are_authorized($id,$tid)){flash('error','No tienes autorización para esa campaña.');redirect('index.php?route=campaigns');}$st=db()->prepare('UPDATE campaigns SET status="paused",updated_at=NOW() WHERE id=? AND tenant_id=? AND status IN("queued","sending")');$st->execute([$id,$tid]);flash('ok','Campaña pausada. Los envíos pendientes permanecen en cola.');redirect('index.php?route=campaigns');
 }
 if($route==='campaign_retry_failed'){
  $id=(int)($_POST['id']??0);$tid=current_tenant_id();$st=db()->prepare('SELECT id FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);if(!$st->fetchColumn()){flash('error','Campaña inválida.');redirect('index.php?route=campaigns');}if(!campaign_lists_are_authorized($id,$tid)){flash('error','No tienes autorización para esa campaña.');redirect('index.php?route=campaigns');}
  $st=db()->prepare('UPDATE deliveries SET status="queued",error_text=NULL WHERE campaign_id=? AND tenant_id=? AND status="failed"');$st->execute([$id,$tid]);$n=$st->rowCount();if($n>0)db()->prepare('UPDATE campaigns SET status="queued",updated_at=NOW() WHERE id=?')->execute([$id]);flash('ok',$n?("Reintentaremos $n envíos fallidos."):'No había envíos fallidos para reintentar.');redirect('index.php?route=campaigns');
 }
 if($route==='campaign_delete'){$id=(int)($_POST['id']??0);$tid=current_tenant_id();if(!campaign_lists_are_authorized($id,$tid)){flash('error','No tienes autorización para esa campaña.');redirect('index.php?route=campaigns');}$st=db()->prepare('DELETE FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);flash('ok','Campaña eliminada.');redirect('index.php?route=campaigns');}
 if($route==='image_upload'){
  header('Content-Type: application/json; charset=utf-8');

  $uploadErrors=[
   UPLOAD_ERR_INI_SIZE=>'El archivo supera upload_max_filesize del servidor.',
   UPLOAD_ERR_FORM_SIZE=>'El archivo supera el tamaño permitido por el formulario.',
   UPLOAD_ERR_PARTIAL=>'La imagen llegó incompleta. Intenta nuevamente.',
   UPLOAD_ERR_NO_FILE=>'Selecciona una imagen.',
   UPLOAD_ERR_NO_TMP_DIR=>'El servidor no tiene directorio temporal disponible.',
   UPLOAD_ERR_CANT_WRITE=>'El servidor no pudo escribir el archivo temporal.',
   UPLOAD_ERR_EXTENSION=>'Una extensión de PHP detuvo la subida.',
  ];

  if(!isset($_FILES['image'])){
   http_response_code(400);echo json_encode(['ok'=>false,'error'=>'No se recibió ningún archivo.']);exit;
  }

  $f=$_FILES['image'];
  $err=(int)($f['error']??UPLOAD_ERR_NO_FILE);
  if($err!==UPLOAD_ERR_OK){
   http_response_code(400);echo json_encode(['ok'=>false,'error'=>$uploadErrors[$err]??('Error de subida PHP: '.$err)]);exit;
  }

  $tmp=(string)($f['tmp_name']??'');
  if($tmp==='' || !is_file($tmp) || !is_uploaded_file($tmp)){
   http_response_code(400);echo json_encode(['ok'=>false,'error'=>'PHP no reconoce el archivo como una subida válida.']);exit;
  }

  $size=(int)($f['size']??0);
  if($size<1){
   http_response_code(400);echo json_encode(['ok'=>false,'error'=>'El archivo está vacío.']);exit;
  }
  if($size>12*1024*1024){
   http_response_code(400);echo json_encode(['ok'=>false,'error'=>'La imagen supera el máximo de 12 MB.']);exit;
  }

  $mime='';
  if(class_exists('finfo')){
   try{$fi=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$fi->file($tmp);}catch(Throwable $ignore){}
  }
  if($mime==='' && function_exists('mime_content_type'))$mime=(string)@mime_content_type($tmp);
  if($mime==='' && function_exists('getimagesize')){
   $info=@getimagesize($tmp);$mime=(string)($info['mime']??'');
  }

  $map=[
   'image/jpeg'=>'jpg',
   'image/jpg'=>'jpg',
   'image/png'=>'png',
   'image/gif'=>'gif',
   'image/webp'=>'webp'
  ];
  if(!isset($map[strtolower($mime)])){
   http_response_code(400);echo json_encode(['ok'=>false,'error'=>'Formato no permitido o no reconocido por el servidor ('.$mime.'). Usa JPG, PNG, GIF o WEBP.']);exit;
  }

  $ym=date('Y/m');
  $dir=APP_ROOT.'/uploads/'.$ym;
  if(!is_dir($dir) && !@mkdir($dir,0755,true) && !is_dir($dir)){
   http_response_code(500);echo json_encode(['ok'=>false,'error'=>'No se pudo crear la carpeta de destino '.$ym.'.']);exit;
  }
  @chmod(APP_ROOT.'/uploads',0755);
  @chmod(APP_ROOT.'/uploads/'.date('Y'),0755);
  @chmod($dir,0755);

  if(!is_writable($dir)){
   http_response_code(500);echo json_encode(['ok'=>false,'error'=>'La carpeta uploads/'.$ym.' no tiene permiso de escritura.']);exit;
  }

  $name=random_token(12).'.'.$map[strtolower($mime)];
  $dest=$dir.'/'.$name;

  if(!@move_uploaded_file($tmp,$dest)){
   http_response_code(500);echo json_encode(['ok'=>false,'error'=>'PHP recibió la imagen, pero no pudo moverla a uploads/'.$ym.'.']);exit;
  }

  @chmod($dest,0644);
  clearstatcache(true,$dest);

  if(!is_file($dest)){
   http_response_code(500);echo json_encode(['ok'=>false,'error'=>'La subida terminó, pero el archivo no existe en el destino.']);exit;
  }
  if(!is_readable($dest)){
   http_response_code(500);echo json_encode(['ok'=>false,'error'=>'La imagen se guardó pero PHP no puede leerla.']);exit;
  }
  $storedBytes=(int)filesize($dest);
  if($storedBytes<1){
   @unlink($dest);
   http_response_code(500);echo json_encode(['ok'=>false,'error'=>'La imagen quedó guardada con 0 bytes.']);exit;
  }

  $relative=$ym.'/'.$name;
  $assetUrl=app_url('index.php?route=image_asset&f='.rawurlencode($relative));

  echo json_encode([
   'ok'=>true,
   'url'=>$assetUrl,
   'relative'=>$relative,
   'bytes'=>$storedBytes,
   'mime'=>$mime,
   'name'=>$name
  ],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  exit;
 }
 if($route==='campaign_duplicate'){
  $id=(int)($_POST['id']??0);$tid=current_tenant_id();$st=db()->prepare('SELECT * FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);$c=$st->fetch();
  if(!$c){flash('error','Campaña no encontrada.');redirect('index.php?route=campaigns');}
  if(!campaign_lists_are_authorized($id,$tid)){flash('error','No tienes autorización para duplicar esa campaña.');redirect('index.php?route=campaigns');}
  $st=db()->prepare('INSERT INTO campaigns(tenant_id,name,subject,preheader,from_name,from_email,list_id,blocks_json,html,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,"draft",NOW(),NOW())');
  $st->execute([$tid,$c['name'].' (copia)',$c['subject'],$c['preheader'],$c['from_name'],$c['from_email'],$c['list_id'],$c['blocks_json'],$c['html']]);
  $new=(int)db()->lastInsertId();try{$srcLists=campaign_selected_list_ids($id,$c['list_id']?:(null));$insList=db()->prepare('INSERT IGNORE INTO campaign_lists(campaign_id,list_id) VALUES(?,?)');foreach($srcLists as $lid)$insList->execute([$new,$lid]);}catch(Throwable $ignore){}flash('ok','Campaña duplicada.');redirect('index.php?route=campaign_edit&id='.$new);
 }
 if($route==='delivery_lab_send'){
  $tid=current_tenant_id();$campaignId=(int)($_POST['campaign_id']??0);
  $mode=(string)($_POST['test_mode']??'campaign');if(!in_array($mode,['technical','campaign','outlook_matrix'],true))$mode='campaign';
  $fieldMap=['gmail'=>'lab_gmail','outlook'=>'lab_outlook','yahoo'=>'lab_yahoo','corporate'=>'lab_corporate','extra1'=>'lab_extra1','extra2'=>'lab_extra2'];
  $slotLabels=['gmail'=>'Gmail / Google','outlook'=>'Outlook / Microsoft','yahoo'=>'Yahoo','corporate'=>'Corporativo','extra1'=>'Adicional 1','extra2'=>'Adicional 2'];
  $saved=[];$targets=[];$seen=[];
  foreach($fieldMap as $slot=>$field){
    $mail=strtolower(trim((string)($_POST[$field]??'')));$saved[$slot]=$mail;
    if($mail===''||!filter_var($mail,FILTER_VALIDATE_EMAIL))continue;
    if(isset($seen[$mail]))continue;
    $seen[$mail]=true;$targets[]=['slot'=>$slot,'label'=>$slotLabels[$slot]??$slot,'email'=>$mail];
  }
  $_SESSION['delivery_lab_addresses']=$saved;
  if(!$campaignId){flash('error','Selecciona una campaña para la prueba de entrega.');redirect('index.php?route=delivery_lab');}
  if(!$targets){flash('error','Indica al menos una dirección de prueba válida. Cada proveedor tiene ahora su propio campo independiente.');redirect('index.php?route=delivery_lab&campaign_id='.$campaignId);}
  if(count($targets)>6){flash('error','El laboratorio permite hasta 6 destinatarios por prueba.');redirect('index.php?route=delivery_lab&campaign_id='.$campaignId);}
  $st=db()->prepare('SELECT * FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$campaignId,$tid]);$c=$st->fetch();
  if(!$c||!campaign_lists_are_authorized($campaignId,$tid)){flash('error','Campaña no encontrada o sin autorización.');redirect('index.php?route=delivery_lab');}
  $ss=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$ss->execute([$tid]);$cfg=$ss->fetch()?:[];
  $batch=['time'=>date('c'),'campaign_id'=>$campaignId,'campaign_name'=>(string)$c['name'],'subject'=>(string)$c['subject'],'mode'=>$mode,'results'=>[],'preflight'=>[]];
  try{
    if(empty($cfg['smtp_host']))throw new RuntimeException('Configura el SMTP antes de usar el laboratorio.');
    $from=trim((string)($c['from_email']?:($cfg['default_from_email']??'')));
    if(!filter_var($from,FILTER_VALIDATE_EMAIL))throw new RuntimeException('El remitente de la campaña no es válido.');
    $decoded=json_decode((string)($c['blocks_json']??'[]'),true);
    $compiled=(is_array($decoded)&&$decoded)?compile_campaign_html($decoded):(string)($c['html']??'');
    if(trim($compiled)==='')throw new RuntimeException('La campaña no tiene contenido HTML compilado.');
    if(is_array($decoded)&&$decoded)db()->prepare('UPDATE campaigns SET html=? WHERE id=? AND tenant_id=?')->execute([$compiled,$campaignId,$tid]);
    $campaignHtml=email_inject_preheader($compiled,(string)($c['preheader']??''));
    $domain=email_domain($from);$dns=dns_delivery_status($domain,(string)($cfg['dkim_selector']??''));
    $smtpUser=trim((string)($cfg['smtp_user']??''));$smtpDomain=email_domain($smtpUser);
    $batch['from']=$from;$batch['smtp_user']=$smtpUser;$batch['domain']=$domain;$batch['bytes']=strlen($campaignHtml);
    $batch['preflight']=[
      'smtp'=>['ok'=>!empty($cfg['smtp_host']),'label'=>'SMTP configurado','detail'=>(string)($cfg['smtp_host']??'').' · '.(int)($cfg['smtp_port']??0).' · '.strtoupper((string)($cfg['smtp_secure']??''))],
      'alignment'=>['ok'=>($smtpDomain===''||$domain===''||strcasecmp($smtpDomain,$domain)===0),'label'=>'Dominio remitente / SMTP','detail'=>$smtpUser!==''?($from.' ↔ '.$smtpUser):$from],
      'spf'=>['ok'=>!empty($dns['spf']['ok']),'label'=>'SPF publicado','detail'=>(string)($dns['spf']['value']??'')],
      'dkim'=>['ok'=>!empty($dns['dkim']['ok']),'label'=>'DKIM publicado','detail'=>!empty($dns['dkim']['ok'])?((string)($dns['dkim']['host']??'').' · DNS publicado; la firma real depende del servidor de salida'):'No encontrado'],
      'dmarc'=>['ok'=>!empty($dns['dmarc']['ok']),'label'=>'DMARC publicado','detail'=>(string)($dns['dmarc']['value']??'')],
      'https'=>['ok'=>stripos($campaignHtml,'http://')===false,'label'=>'Recursos HTTPS','detail'=>stripos($campaignHtml,'http://')===false?'No se detectaron recursos HTTP':'La campaña contiene uno o más recursos http://'],
      'size'=>['ok'=>strlen($campaignHtml)<102400,'label'=>'Tamaño HTML','detail'=>number_format(strlen($campaignHtml)/1024,1,',','.').' KB'.(strlen($campaignHtml)>=102400?' · Gmail puede recortar mensajes grandes':'')],
    ];
    $mxCache=[];
    $provider=function(string $email) use (&$mxCache): string {
      $d=email_domain($email);if($d==='')return 'Otro';
      if(in_array($d,['gmail.com','googlemail.com'],true))return 'Gmail';
      if(in_array($d,['outlook.com','hotmail.com','live.com','msn.com'],true))return 'Outlook / Microsoft';
      if($d==='yahoo.com'||str_ends_with($d,'.yahoo.com'))return 'Yahoo';
      if(!array_key_exists($d,$mxCache)){
        $mxCache[$d]=[];
        try{foreach((array)@dns_get_record($d,DNS_MX) as $rec){$mxCache[$d][]=strtolower((string)($rec['target']??''));}}catch(Throwable $ignore){}
      }
      $mx=implode(' ',$mxCache[$d]);
      if(str_contains($mx,'google.com')||str_contains($mx,'googlemail.com'))return 'Google Workspace';
      if(str_contains($mx,'protection.outlook.com')||str_contains($mx,'outlook.com'))return 'Microsoft 365';
      if(str_contains($mx,'yahoodns.net')||str_contains($mx,'yahoo.com'))return 'Yahoo';
      return 'Corporativo / Otro';
    };
    $labId='LAB-'.date('Ymd-His').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));
    $technicalHtml='<!doctype html><html><body style="margin:0;padding:24px;background:#f4f7fa;font-family:Arial,Helvetica,sans-serif;color:#172334"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center"><table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #dce5ec"><tr><td style="padding:28px"><h1 style="margin:0 0 12px;font-size:22px">Prueba técnica EMAILSEND ONE</h1><p style="margin:0 0 14px;line-height:1.5">Este mensaje mínimo sirve para comprobar la entrega SMTP sin imágenes, VML ni el diseño de la campaña.</p><p style="margin:0;line-height:1.5"><strong>ID:</strong> '.e($labId).'<br><strong>Remitente:</strong> '.e($from).'</p></td></tr></table></td></tr></table></body></html>';

    $matrixHtml='';
    $matrixUrl='';
    if($mode==='outlook_matrix'){
      $matrixSmtp=new SimpleSMTP();
      $matrixDiag=$matrixSmtp->diagnoseStaticImages($campaignHtml,$from);
      $matrixImage=null;
      foreach((array)($matrixDiag['items']??[]) as $candidate){
        if(!empty($candidate['static_url']) && !empty($candidate['file']) && is_file((string)$candidate['file'])){$matrixImage=$candidate;break;}
      }
      if(!$matrixImage)throw new RuntimeException('La campaña no tiene una imagen local apta para construir la matriz Outlook.');
      $matrixUrl=(string)$matrixImage['static_url'];
      $probe=@getimagesize((string)$matrixImage['file']);
      $iw=max(1,(int)($probe[0]??600));$ih=max(1,(int)($probe[1]??316));
      $dw=min(520,$iw);$dh=max(1,(int)round($ih*($dw/$iw)));
      $u=htmlspecialchars($matrixUrl,ENT_QUOTES|ENT_HTML5,'UTF-8');
      $idSafe=htmlspecialchars($labId,ENT_QUOTES|ENT_HTML5,'UTF-8');
      $fromSafe=htmlspecialchars($from,ENT_QUOTES|ENT_HTML5,'UTF-8');
      $cardStart='<tr><td style="padding:22px 26px;border-top:1px solid #dde6ee"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td style="font-family:Arial,Helvetica,sans-serif;color:#15243a">';
      $cardEnd='</td></tr></table></td></tr>';
      $matrixHtml='<!doctype html><html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head><meta charset="utf-8"><meta name="x-apple-disable-message-reformatting"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;background:#eef4f8">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#eef4f8"><tr><td align="center" style="padding:24px 10px">'
        .'<table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" style="width:640px;max-width:640px;background:#ffffff;border:1px solid #d8e2ea">'
        .'<tr><td style="padding:26px;font-family:Arial,Helvetica,sans-serif;color:#15243a"><div style="font-size:12px;font-weight:bold;letter-spacing:1px;color:#0074c7">EMAILSEND ONE · OUTLOOK IMAGE MATRIX</div><h1 style="margin:8px 0 10px;font-size:24px;line-height:1.2">Una imagen · cinco métodos</h1><p style="margin:0;font-size:14px;line-height:1.5;color:#536274">No es una campaña de diseño. Es una prueba aislada. Indica cuáles bloques A–E muestran la imagen. ID: <b>'.$idSafe.'</b></p></td></tr>'
        .$cardStart.'<div style="font-size:15px;font-weight:bold;margin-bottom:10px">TEST A — IMG SIMPLE</div><div style="font-size:12px;color:#667788;margin-bottom:12px">&lt;img src=&quot;https://...&quot;&gt; sin width ni VML.</div><img src="'.$u.'" alt="TEST A" style="display:block;border:0;outline:none;text-decoration:none">'.$cardEnd
        .$cardStart.'<div style="font-size:15px;font-weight:bold;margin-bottom:10px">TEST B — IMG + WIDTH NUMÉRICO</div><div style="font-size:12px;color:#667788;margin-bottom:12px">IMG remoto con width HTML explícito.</div><img src="'.$u.'" width="'.$dw.'" alt="TEST B" style="display:block;border:0;outline:none;text-decoration:none;width:'.$dw.'px;height:auto">'.$cardEnd
        .$cardStart.'<div style="font-size:15px;font-weight:bold;margin-bottom:10px">TEST C — TABLA CLÁSICA + IMG</div><div style="font-size:12px;color:#667788;margin-bottom:12px">Tabla presentation con width fijo y IMG dentro de TD.</div><table role="presentation" width="'.$dw.'" cellpadding="0" cellspacing="0" border="0" style="width:'.$dw.'px"><tr><td width="'.$dw.'" style="width:'.$dw.'px;padding:0"><img src="'.$u.'" width="'.$dw.'" alt="TEST C" style="display:block;border:0;width:'.$dw.'px;height:auto"></td></tr></table>'.$cardEnd
        .$cardStart.'<div style="font-size:15px;font-weight:bold;margin-bottom:10px">TEST D — VML OUTLOOK</div><div style="font-size:12px;color:#667788;margin-bottom:12px">Solo Outlook clásico debe renderizar este bloque mediante VML.</div><!--[if mso]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:'.$dw.'px;height:'.$dh.'px;"><v:fill type="frame" src="'.$u.'" /></v:rect><![endif]--><!--[if !mso]><!--><div style="width:'.$dw.'px;height:'.$dh.'px;background:#f2f5f8;border:1px dashed #aebbc7;text-align:center;line-height:'.$dh.'px;color:#748292">VML: visible solo en Outlook clásico</div><!--<![endif]-->'.$cardEnd
        .$cardStart.'<div style="font-size:15px;font-weight:bold;margin-bottom:10px">TEST E — BACKGROUND DE CELDA</div><div style="font-size:12px;color:#667788;margin-bottom:12px">La misma URL como atributo background y CSS background-image.</div><table role="presentation" width="'.$dw.'" cellpadding="0" cellspacing="0" border="0" style="width:'.$dw.'px"><tr><td background="'.$u.'" width="'.$dw.'" height="'.$dh.'" style="width:'.$dw.'px;height:'.$dh.'px;background-image:url(\''.$u.'\');background-repeat:no-repeat;background-position:center center;background-size:cover">&nbsp;</td></tr></table>'.$cardEnd
        .'<tr><td style="padding:22px 26px;border-top:1px solid #dde6ee;font-family:Arial,Helvetica,sans-serif;color:#15243a"><div style="font-size:15px;font-weight:bold;margin-bottom:8px">ENLACE DIRECTO A LA MISMA IMAGEN</div><p style="margin:0 0 8px;font-size:13px;color:#536274">Si los cinco bloques fallan, haz clic en este enlace desde el propio Outlook:</p><a href="'.$u.'" style="font-size:13px;color:#0074c7;word-break:break-all">'.$u.'</a><p style="margin:14px 0 0;font-size:12px;color:#778596">Remitente: '.$fromSafe.' · '.$iw.'×'.$ih.' px · render de prueba '.$dw.'×'.$dh.' px</p></td></tr>'
        .'</table></td></tr></table></body></html>';
    }

    if($mode==='technical'){$sendHtml=$technicalHtml;$subject='[LAB TÉCNICO] EMAILSEND ONE · '.$labId;}
    elseif($mode==='outlook_matrix'){$sendHtml=$matrixHtml;$subject='[LAB OUTLOOK MATRIX] EMAILSEND ONE · '.$labId;}
    else{$sendHtml=$campaignHtml;$subject='[LAB CAMPAÑA] '.$c['subject'];}
    foreach($targets as $target){
      $to=$target['email'];$row=['slot'=>$target['label'],'email'=>$to,'provider'=>$provider($to),'mode'=>$mode,'ok'=>false,'queue_id'=>'','smtp_response'=>'','rcpt_response'=>'','error'=>''];
      try{
        $smtp=new SimpleSMTP();$result=$smtp->send([
          'host'=>$cfg['smtp_host'],'port'=>$cfg['smtp_port'],'user'=>$cfg['smtp_user'],'pass'=>decrypt_secret((string)$cfg['smtp_pass']),
          'secure'=>$cfg['smtp_secure'],'from_name'=>$c['from_name']?:($cfg['default_from_name']??'')
        ],$from,$to,$subject,$sendHtml,[
          'Reply-To'=>($cfg['reply_to_email']??'')?:$from,
          'X-EMAILSEND-Test'=>$mode==='technical'?'delivery-lab-technical':($mode==='outlook_matrix'?'delivery-lab-outlook-matrix':'delivery-lab-campaign'),
          'X-EMAILSEND-Lab-ID'=>$labId,
          'X-EMAILSEND-Campaign'=>(string)$campaignId,
          'X-EMAILSEND-Diagnostic-Image-Matrix'=>$mode==='outlook_matrix'?'1':'0'
        ]);
        $row['ok']=true;$row['queue_id']=trim((string)($result['queue_id']??''));$row['smtp_response']=trim((string)($result['smtp_response']??''));$row['rcpt_response']=trim((string)($result['rcpt_response']??''));$row['message_id']=trim((string)($result['message_id']??''));$row['static_images']=(int)($result['static_images']??0);$row['static_image_bytes']=(int)($result['static_image_bytes']??0);$row['related_images']=(int)($result['related_images']??$result['inline_images']??0);$row['related_image_bytes']=(int)($result['related_image_bytes']??$result['inline_image_bytes']??0);$row['image_mode']=(string)($result['image_mode']??'');
      }catch(Throwable $sendError){$row['error']=$sendError->getMessage();}
      $batch['results'][]=$row;
    }
    $_SESSION['delivery_lab_last']=$batch;
    $accepted=count(array_filter($batch['results'],fn($r)=>!empty($r['ok'])));$total=count($batch['results']);$modeLabel=$mode==='technical'?'prueba técnica':($mode==='outlook_matrix'?'matriz Outlook':'campaña real');
    $msg='Laboratorio: '.$accepted.' de '.$total.' mensajes de '.$modeLabel.' fueron aceptados por el SMTP.';
    if($accepted>0)$msg.=' Si la prueba técnica es aceptada pero no llega, el problema ya no es el HTML de la campaña: revisa Queue ID, reputación/IP y autenticación del servidor de salida.';
    flash($accepted===$total?'ok':'error',$msg);
  }catch(Throwable $e){$_SESSION['delivery_lab_last']=$batch;flash('error','Laboratorio: '.$e->getMessage());}
  redirect('index.php?route=delivery_lab&campaign_id='.$campaignId);
 }
 if($route==='campaign_test'){
  $id=(int)($_POST['id']??0);$to=strtolower(trim($_POST['test_email']??''));$tid=current_tenant_id();
  if(!$id||!filter_var($to,FILTER_VALIDATE_EMAIL)){flash('error','Guarda la campaña e indica un email de prueba válido.');redirect('index.php?route=campaign_edit&id='.$id);}
  $st=db()->prepare('SELECT * FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);$c=$st->fetch();if($c && !campaign_lists_are_authorized($id,$tid)){flash('error','No tienes autorización para esa campaña.');redirect('index.php?route=campaigns');}$ss=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$ss->execute([$tid]);$cfg=$ss->fetch();
  try{if(!$c||!$cfg||empty($cfg['smtp_host']))throw new RuntimeException('Configura el SMTP antes de enviar una prueba.');$from=$c['from_email']?:$cfg['default_from_email'];if(!$from)throw new RuntimeException('Falta email remitente.');$smtp=new SimpleSMTP();$decoded=json_decode((string)($c['blocks_json']??'[]'),true);$compiled=(is_array($decoded)&&$decoded)?compile_campaign_html($decoded):(string)($c['html']??'');if(is_array($decoded)&&$decoded)db()->prepare('UPDATE campaigns SET html=? WHERE id=? AND tenant_id=?')->execute([$compiled,$id,$tid]);$testHtml=email_inject_preheader($compiled,(string)($c['preheader']??''));$sendResult=$smtp->send(['host'=>$cfg['smtp_host'],'port'=>$cfg['smtp_port'],'user'=>$cfg['smtp_user'],'pass'=>decrypt_secret($cfg['smtp_pass']),'secure'=>$cfg['smtp_secure'],'from_name'=>$c['from_name']?:($cfg['default_from_name']??'')],$from,$to,'[PRUEBA] '.$c['subject'],$testHtml,['Reply-To'=>($cfg['reply_to_email']??'')?:$from,'X-EMAILSEND-Test'=>'campaign']);$queue=trim((string)($sendResult['queue_id']??''));$resp=trim((string)($sendResult['smtp_response']??''));$detail=$queue!==''?' · Queue ID: '.$queue:($resp!==''?' · SMTP: '.$resp:'');$warning='';$smtpUser=trim((string)($cfg['smtp_user']??''));if($smtpUser!==''&&filter_var($smtpUser,FILTER_VALIDATE_EMAIL)&&strcasecmp(substr(strrchr($smtpUser,'@'),1),substr(strrchr($from,'@'),1))!==0){$warning=' · ADVERTENCIA: el dominio del remitente no coincide con el usuario SMTP.';}flash('ok','Servidor SMTP aceptó la prueba para '.$to.$detail.$warning);}catch(Throwable $e){flash('error','No se pudo enviar: '.$e->getMessage());}
  redirect('index.php?route=campaign_edit&id='.$id);
 }
 if($route==='report_client_save'){
  require_admin();$tid=current_tenant_id();$company=trim($_POST['company']??'');$name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$pwd=$_POST['password']??'';
  if(!$company||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pwd)<8){flash('error','Completa empresa, email y una contraseña de al menos 8 caracteres.');redirect('index.php?route=clients');}
  db()->beginTransaction();try{$st=db()->prepare('INSERT INTO report_clients(tenant_id,company,contact_name,email,phone,logo_url,active,created_at) VALUES(?,?,?,?,?,?,1,NOW())');$st->execute([$tid,$company,$name,$email,trim($_POST['phone']??''),trim($_POST['logo_url']??'')]);$cid=(int)db()->lastInsertId();$st=db()->prepare('INSERT INTO report_users(client_id,name,email,password_hash,active,created_at) VALUES(?,?,?,?,1,NOW())');$st->execute([$cid,$name?:$company,$email,password_hash($pwd,PASSWORD_DEFAULT)]);$listName=$company.' · Contactos';$ls=db()->prepare('INSERT INTO contact_lists(tenant_id,client_id,name,created_at) VALUES(?,?,?,NOW())');$ls->execute([$tid,$cid,$listName]);db()->commit();flash('ok','Cliente creado con su lista de contactos propia. Ahora puedes importar contactos y asignar campañas.');redirect('index.php?route=clients&edit='.$cid);}catch(Throwable $e){db()->rollBack();flash('error',$e->getMessage());redirect('index.php?route=clients');}
 }
 if($route==='report_access_save'){
  require_admin();$tid=current_tenant_id();$cid=(int)($_POST['client_id']??0);$st=db()->prepare('SELECT id FROM report_clients WHERE id=? AND tenant_id=?');$st->execute([$cid,$tid]);if(!$st->fetchColumn()){http_response_code(403);exit('Cliente inválido');}
  db()->beginTransaction();try{db()->prepare('DELETE FROM campaign_report_access WHERE client_id=?')->execute([$cid]);$ins=db()->prepare('INSERT INTO campaign_report_access(campaign_id,client_id,granted_at) VALUES(?,?,NOW())');foreach((array)($_POST['campaigns']??[]) as $campaignId){$campaignId=(int)$campaignId;$q=db()->prepare('SELECT id FROM campaigns WHERE id=? AND tenant_id=?');$q->execute([$campaignId,$tid]);if($q->fetchColumn())$ins->execute([$campaignId,$cid]);}db()->commit();flash('ok','Acceso a reportes actualizado.');}catch(Throwable $e){db()->rollBack();flash('error',$e->getMessage());}redirect('index.php?route=clients&edit='.$cid);
 }
 if($route==='report_client_password'){
  require_admin();$cid=(int)($_POST['client_id']??0);$pwd=$_POST['password']??'';$st=db()->prepare('SELECT id FROM report_clients WHERE id=? AND tenant_id=?');$st->execute([$cid,current_tenant_id()]);if(!$st->fetchColumn()||strlen($pwd)<8){flash('error','Contraseña inválida.');redirect('index.php?route=clients&edit='.$cid);}$st=db()->prepare('UPDATE report_users SET password_hash=? WHERE client_id=?');$st->execute([password_hash($pwd,PASSWORD_DEFAULT),$cid]);flash('ok','Contraseña actualizada.');redirect('index.php?route=clients&edit='.$cid);
 }
 if($route==='report_client_delete'){
  require_admin();verify_csrf();
  if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Método no permitido');}
  $cid=(int)($_POST['client_id']??0);$tid=current_tenant_id();
  $q=db()->prepare('SELECT id,company,email FROM report_clients WHERE id=? AND tenant_id=?');$q->execute([$cid,$tid]);$clientToDelete=$q->fetch();
  if(!$clientToDelete){flash('error','Cliente no encontrado.');redirect('index.php?route=clients');}
  db()->beginTransaction();
  try{
   db()->prepare('UPDATE contact_lists SET client_id=NULL WHERE client_id=? AND tenant_id=?')->execute([$cid,$tid]);
   db()->prepare('DELETE FROM campaign_report_access WHERE client_id=?')->execute([$cid]);
   db()->prepare('DELETE FROM report_users WHERE client_id=?')->execute([$cid]);
   db()->prepare('DELETE FROM report_clients WHERE id=? AND tenant_id=?')->execute([$cid,$tid]);
   db()->commit();
   flash('ok','Cliente/portal eliminado: '.$clientToDelete['company'].'. Las listas y contactos se conservaron sin asignación.');
  }catch(Throwable $e){
   if(db()->inTransaction())db()->rollBack();
   flash('error','No se pudo eliminar el cliente: '.$e->getMessage());
  }
  redirect('index.php?route=clients');
 }
 if($route==='report_clients_bulk_delete'){
  require_admin();verify_csrf();
  if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Método no permitido');}
  $tid=current_tenant_id();
  $ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['client_ids']??[])))));
  if(!$ids){flash('error','Selecciona al menos un cliente para eliminar.');redirect('index.php?route=clients');}
  $ph=implode(',',array_fill(0,count($ids),'?'));
  $params=array_merge([$tid],$ids);
  $q=db()->prepare('SELECT id FROM report_clients WHERE tenant_id=? AND id IN ('.$ph.')');$q->execute($params);
  $valid=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
  if(!$valid){flash('error','No se encontraron clientes válidos para eliminar.');redirect('index.php?route=clients');}
  $vph=implode(',',array_fill(0,count($valid),'?'));
  db()->beginTransaction();
  try{
   db()->prepare('UPDATE contact_lists SET client_id=NULL WHERE tenant_id=? AND client_id IN ('.$vph.')')->execute(array_merge([$tid],$valid));
   db()->prepare('DELETE FROM campaign_report_access WHERE client_id IN ('.$vph.')')->execute($valid);
   db()->prepare('DELETE FROM report_users WHERE client_id IN ('.$vph.')')->execute($valid);
   db()->prepare('DELETE FROM report_clients WHERE tenant_id=? AND id IN ('.$vph.')')->execute(array_merge([$tid],$valid));
   db()->commit();
   flash('ok',count($valid).' cliente(s)/portal(es) eliminados. Las listas y contactos se conservaron sin asignación.');
  }catch(Throwable $e){
   if(db()->inTransaction())db()->rollBack();
   flash('error','No se pudieron eliminar los clientes: '.$e->getMessage());
  }
  redirect('index.php?route=clients');
 }
 if($route==='client_lists_save'){
  require_admin();$tid=current_tenant_id();$cid=(int)($_POST['client_id']??0);$q=db()->prepare('SELECT id FROM report_clients WHERE id=? AND tenant_id=?');$q->execute([$cid,$tid]);if(!$q->fetchColumn()){http_response_code(403);exit('Cliente inválido');}
  $ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['lists']??[])))));db()->beginTransaction();try{db()->prepare('UPDATE contact_lists SET client_id=NULL WHERE tenant_id=? AND client_id=?')->execute([$tid,$cid]);if($ids){$ph=implode(',',array_fill(0,count($ids),'?'));db()->prepare('UPDATE contact_lists SET client_id=? WHERE tenant_id=? AND (client_id IS NULL OR client_id=?) AND id IN ('.$ph.')')->execute([$cid,$tid,$cid,...$ids]);}db()->commit();flash('ok','Listas del cliente actualizadas.');}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}redirect('index.php?route=clients&edit='.$cid);
 }
 if($route==='admin_create'){
  require_admin();
  $name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$pwd=(string)($_POST['password']??'');$tenantId=(int)($_POST['tenant_id']??0);
  $role=in_array($_POST['role']??'user',['admin','user'],true)?$_POST['role']:'user';$mode=in_array($_POST['list_access_mode']??'assigned',['all','assigned'],true)?$_POST['list_access_mode']:'assigned';$listIds=(array)($_POST['list_ids']??[]);
  if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pwd)<8){flash('error','Completa nombre, email válido y una contraseña de al menos 8 caracteres.');redirect('index.php?route=administrators');}
  $q=db()->prepare('SELECT id FROM tenants WHERE id=?');$q->execute([$tenantId]);if(!$q->fetchColumn()){flash('error','Empresa base inválida.');redirect('index.php?route=administrators');}
  db()->beginTransaction();try{
   $st=db()->prepare('INSERT INTO users(tenant_id,name,email,password_hash,role,list_access_mode,active,created_at) VALUES(?,?,?,?,?,?,1,NOW())');$st->execute([$tenantId,$name,$email,password_hash($pwd,PASSWORD_DEFAULT),$role,$mode]);$uid=(int)db()->lastInsertId();
   save_user_list_permissions($uid,$tenantId,$listIds);save_user_permissions($uid,(array)($_POST['permissions']??[]));db()->commit();flash('ok','Usuario creado correctamente.');
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',str_contains(strtolower($e->getMessage()),'duplicate')?'Ese email ya está registrado.':'No se pudo crear el usuario: '.$e->getMessage());}
  redirect('index.php?route=administrators');
 }
 if($route==='admin_update'){
  require_admin();$id=(int)($_POST['id']??0);$name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$tenantId=(int)($_POST['tenant_id']??0);$active=!empty($_POST['active'])?1:0;
  $role=in_array($_POST['role']??'user',['admin','user'],true)?$_POST['role']:'user';$mode=in_array($_POST['list_access_mode']??'assigned',['all','assigned'],true)?$_POST['list_access_mode']:'assigned';$listIds=(array)($_POST['list_ids']??[]);
  $q=db()->prepare('SELECT * FROM users WHERE id=? AND role IN("admin","user")');$q->execute([$id]);$target=$q->fetch();if(!$target){flash('error','Usuario no encontrado.');redirect('index.php?route=administrators');}
  if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)){flash('error','Nombre o email inválido.');redirect('index.php?route=administrators&edit='.$id);}
  $q=db()->prepare('SELECT id FROM tenants WHERE id=?');$q->execute([$tenantId]);if(!$q->fetchColumn()){flash('error','Empresa base inválida.');redirect('index.php?route=administrators&edit='.$id);}
  if($id===(int)current_user()['id']&&(!$active||$role!=='admin')){flash('error','No puedes desactivar ni quitar el rol administrador de tu propia cuenta mientras estás conectado.');redirect('index.php?route=administrators&edit='.$id);}
  if(($target['role']==='admin')&&(!$active||$role!=='admin')){$cnt=(int)db()->query('SELECT COUNT(*) FROM users WHERE role="admin" AND active=1')->fetchColumn();if($cnt<=1){flash('error','EMAILSEND debe conservar al menos un administrador activo.');redirect('index.php?route=administrators&edit='.$id);}}
  db()->beginTransaction();try{
   $st=db()->prepare('UPDATE users SET tenant_id=?,name=?,email=?,role=?,list_access_mode=?,active=? WHERE id=? AND role IN("admin","user")');$st->execute([$tenantId,$name,$email,$role,$mode,$active,$id]);save_user_list_permissions($id,$tenantId,$listIds);save_user_permissions($id,(array)($_POST['permissions']??[]));db()->commit();flash('ok','Usuario actualizado.');
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',str_contains(strtolower($e->getMessage()),'duplicate')?'Ese email ya está registrado.':'No se pudo actualizar el usuario: '.$e->getMessage());}
  redirect('index.php?route=administrators&edit='.$id);
 }
 if($route==='admin_password'){
  require_admin();$id=(int)($_POST['id']??0);$pwd=(string)($_POST['password']??'');if(strlen($pwd)<8){flash('error','La contraseña debe tener al menos 8 caracteres.');redirect('index.php?route=administrators&edit='.$id);}
  $q=db()->prepare('SELECT id FROM users WHERE id=? AND role IN("admin","user")');$q->execute([$id]);if(!$q->fetchColumn()){flash('error','Usuario no encontrado.');redirect('index.php?route=administrators');}
  db()->prepare('UPDATE users SET password_hash=? WHERE id=? AND role IN("admin","user")')->execute([password_hash($pwd,PASSWORD_DEFAULT),$id]);flash('ok','Contraseña del usuario actualizada.');redirect('index.php?route=administrators&edit='.$id);
 }
 if($route==='legacy_demo_remove'){
  require_admin();$tid=current_tenant_id();db()->beginTransaction();try{
   $st=db()->prepare('DELETE rc FROM report_clients rc LEFT JOIN report_users ru ON ru.client_id=rc.id WHERE rc.tenant_id=? AND (rc.email=? OR ru.email=?)');$st->execute([$tid,'cliente.demo@emailsend.uno','cliente.demo@emailsend.uno']);
   $names=['Newsletter Agosto','Lanzamiento EMAILSEND','Servicios Digitales'];$ph=implode(',',array_fill(0,count($names),'?'));$st=db()->prepare('DELETE FROM campaigns WHERE tenant_id=? AND name IN ('.$ph.') AND (from_email=? OR from_name=?)');$st->execute([$tid,...$names,'hola@emailsend.uno','EMAILSEND Demo']);
   $st=db()->prepare('DELETE FROM contacts WHERE tenant_id=? AND email LIKE ?');$st->execute([$tid,'demo.contacto%@example.com']);
   $st=db()->prepare('DELETE l FROM contact_lists l LEFT JOIN contacts c ON c.list_id=l.id WHERE l.tenant_id=? AND l.name IN ("Clientes Activos","Newsletter","Prospectos") AND c.id IS NULL');$st->execute([$tid]);
   db()->commit();flash('ok','Datos heredados de demostración eliminados. EMAILSEND queda listo para datos reales.');
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error','No se pudo limpiar la demo: '.$e->getMessage());}redirect('index.php?route=settings');
 }
 if($route==='settings_save'){
  require_admin();$tid=current_tenant_id();$existing=db()->prepare('SELECT smtp_pass FROM settings WHERE tenant_id=?');$existing->execute([$tid]);$oldPass=(string)($existing->fetchColumn()?:'');$newPass=(string)($_POST['smtp_pass']??'');$storedPass=$newPass!==''?encrypt_secret($newPass):$oldPass;$secure=in_array($_POST['smtp_secure']??'tls',['tls','ssl'],true)?$_POST['smtp_secure']:'tls';
  $st=db()->prepare('INSERT INTO settings(tenant_id,smtp_host,smtp_port,smtp_user,smtp_pass,smtp_secure,default_from_name,default_from_email,dkim_selector,reply_to_email) VALUES(?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE smtp_host=VALUES(smtp_host),smtp_port=VALUES(smtp_port),smtp_user=VALUES(smtp_user),smtp_pass=VALUES(smtp_pass),smtp_secure=VALUES(smtp_secure),default_from_name=VALUES(default_from_name),default_from_email=VALUES(default_from_email),dkim_selector=VALUES(dkim_selector),reply_to_email=VALUES(reply_to_email)');
  $st->execute([$tid,trim($_POST['smtp_host']??''),(int)($_POST['smtp_port']??587),trim($_POST['smtp_user']??''),$storedPass,$secure,trim($_POST['default_from_name']??''),strtolower(trim($_POST['default_from_email']??'')),normalize_dkim_selector(trim($_POST['dkim_selector']??''),email_domain($_POST['default_from_email']??'')),strtolower(trim($_POST['reply_to_email']??''))]);flash('ok','SMTP seguro y preferencias de entrega guardadas.');redirect('index.php?route=settings');
 }
 if($route==='smtp_test'){
  require_admin();$tid=current_tenant_id();$to=strtolower(trim($_POST['test_email']??current_user()['email']??''));if(!filter_var($to,FILTER_VALIDATE_EMAIL)){flash('error','Indica un email válido para la prueba.');redirect('index.php?route=settings');}
  $st=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$st->execute([$tid]);$cfg=$st->fetch();
  try{if(!$cfg||empty($cfg['smtp_host']))throw new RuntimeException('Configura y guarda el SMTP primero.');$from=$cfg['default_from_email']?:$cfg['smtp_user'];if(!filter_var($from,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Configura un remitente válido.');$smtp=new SimpleSMTP();$sendResult=$smtp->send(['host'=>$cfg['smtp_host'],'port'=>$cfg['smtp_port'],'user'=>$cfg['smtp_user'],'pass'=>decrypt_secret($cfg['smtp_pass']),'secure'=>$cfg['smtp_secure'],'from_name'=>$cfg['default_from_name']??'EMAILSEND'],$from,$to,'EMAILSEND · prueba SMTP segura','<div style="font-family:Arial,sans-serif;padding:28px"><h2>SMTP configurado correctamente</h2><p>Este mensaje fue enviado mediante la conexión segura configurada en EMAILSEND.</p></div>',['Reply-To'=>$cfg['reply_to_email']?:$from,'X-EMAILSEND-Test'=>'smtp']);$queue=trim((string)($sendResult['queue_id']??''));$resp=trim((string)($sendResult['smtp_response']??''));$detail=$queue!==''?' · Queue ID: '.$queue:($resp!==''?' · SMTP: '.$resp:'');flash('ok','Prueba SMTP aceptada para '.$to.$detail);}catch(Throwable $e){flash('error','Prueba SMTP fallida: '.$e->getMessage());}redirect('index.php?route=settings');
 }
 if($route==='profile_save'){
  $u=current_user();$name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)){flash('error','Nombre o email inválido.');redirect('index.php?route=profile');}
  $avatar=(string)($u['avatar_url']??'');
  if(!empty($_FILES['avatar']['tmp_name'])){$f=$_FILES['avatar'];if(($f['size']??0)>3*1024*1024){flash('error','La foto no puede superar 3 MB.');redirect('index.php?route=profile');}$fi=new finfo(FILEINFO_MIME_TYPE);$mime=$fi->file($f['tmp_name']);$map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];if(!isset($map[$mime])){flash('error','Formato de foto no permitido.');redirect('index.php?route=profile');}$dir=APP_ROOT.'/uploads/profiles';if(!is_dir($dir))mkdir($dir,0755,true);$nameFile='u'.(int)$u['id'].'-'.random_token(8).'.'.$map[$mime];if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$nameFile)){flash('error','No se pudo guardar la foto.');redirect('index.php?route=profile');}$avatar=app_url('uploads/profiles/'.$nameFile);}
  try{$st=db()->prepare('UPDATE users SET name=?,email=?,avatar_url=?,phone=?,job_title=?,company=?,timezone=? WHERE id=?');$st->execute([$name,$email,$avatar,trim($_POST['phone']??''),trim($_POST['job_title']??''),trim($_POST['company']??''),trim($_POST['timezone']??''),(int)$u['id']]);flash('ok','Preferencias del usuario actualizadas.');}catch(Throwable $e){flash('error','No se pudo actualizar el perfil: '.$e->getMessage());}redirect('index.php?route=profile');
 }
}

function ui_icon(string $name): string {
 $icons=[
 'home'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.2 12 4l9 7.2v8.3a.5.5 0 0 1-.5.5h-5.2v-6h-6.6v6H3.5a.5.5 0 0 1-.5-.5z"/></svg>',
 'mail'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
 'users'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19c.5-4 2.3-6 5.5-6s5 2 5.5 6M16 6.5a2.5 2.5 0 0 1 0 5M16 13.5c2.7.2 4.2 2 4.5 5.5"/></svg>',
 'chart'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>',
 'client'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="15" rx="2"/><path d="M8 5V3h8v2M7 10h10M7 14h6"/></svg>',
 'settings'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19 13.5v-3l-2-.7a7 7 0 0 0-.8-1.9l.9-1.9-2.1-2.1-1.9.9a7 7 0 0 0-1.9-.8L10.5 2h-3l-.7 2a7 7 0 0 0-1.9.8L3 3.9.9 6l.9 1.9a7 7 0 0 0-.8 1.9l-2 .7v3l2 .7a7 7 0 0 0 .8 1.9L.9 18l2.1 2.1 1.9-.9a7 7 0 0 0 1.9.8l.7 2h3l.7-2a7 7 0 0 0 1.9-.8l1.9.9L18 18l-.9-1.9a7 7 0 0 0 .8-1.9z" transform="translate(3 0) scale(.75)"/></svg>',
 'plus'=>'<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>',
 'send'=>'<svg viewBox="0 0 24 24"><path d="m3 11 18-8-7.5 18-2.2-7.3zM11.3 13.7 21 3"/></svg>',
 'eye'=>'<svg viewBox="0 0 24 24"><path d="M2.5 12s3.2-6 9.5-6 9.5 6 9.5 6-3.2 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>',
 'cursor'=>'<svg viewBox="0 0 24 24"><path d="m5 3 6.8 15 2.1-5.1 5.1-2.1zM14 14l5 5"/></svg>',
 'bounce'=>'<svg viewBox="0 0 24 24"><path d="M4 7h10a5 5 0 0 1 0 10H9M4 7l4-4M4 7l4 4"/></svg>',
 'minus-user'=>'<svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20c.5-4.4 2.5-6.5 6-6.5 2 0 3.5.7 4.5 2M16 18h6"/></svg>',
 'edit'=>'<svg viewBox="0 0 24 24"><path d="M4 20h4l11-11-4-4L4 16zM13.5 6.5l4 4"/></svg>',
 'copy'=>'<svg viewBox="0 0 24 24"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>',
 'trash'=>'<svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg>',
 'save'=>'<svg viewBox="0 0 24 24"><path d="M4 3h13l3 3v15H4zM8 3v6h8V3M8 21v-7h8v7"/></svg>',
 'code'=>'<svg viewBox="0 0 24 24"><path d="m8 8-4 4 4 4M16 8l4 4-4 4M14 4l-4 16"/></svg>',
 'desktop'=>'<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
 'mobile'=>'<svg viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>',
 'undo'=>'<svg viewBox="0 0 24 24"><path d="M9 7 4 12l5 5M5 12h8a6 6 0 0 1 6 6"/></svg>',
 'redo'=>'<svg viewBox="0 0 24 24"><path d="m15 7 5 5-5 5M19 12h-8a6 6 0 0 0-6 6"/></svg>',
 'profile'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c.6-5 3.2-7.5 8-7.5S19.4 16 20 21"/></svg>',
 'logout'=>'<svg viewBox="0 0 24 24"><path d="M10 4H4v16h6M14 8l4 4-4 4M8 12h10"/></svg>',
 'book'=>'<svg viewBox="0 0 24 24"><path d="M4 4h6a3 3 0 0 1 3 3v13a3 3 0 0 0-3-3H4zM20 4h-6a3 3 0 0 0-3 3v13a3 3 0 0 1 3-3h6z"/></svg>',
 'shield'=>'<svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 5 2.7 8.2 7 10 4.3-1.8 7-5 7-10V6z"/><path d="m9 12 2 2 4-4"/></svg>',
 'dns'=>'<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 7h.01M7 17h.01M11 7h6M11 17h6"/></svg>',
 'test'=>'<svg viewBox="0 0 24 24"><path d="M9 3h6M10 3v5l-5 9a3 3 0 0 0 2.6 4.5h8.8A3 3 0 0 0 19 17l-5-9V3M8 16h8"/></svg>',
 'image'=>'<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="2"/><path d="m5 18 5-5 3 3 2-2 4 4"/></svg>',
 'layers'=>'<svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5zM3 12l9 5 9-5M3 16l9 5 9-5"/></svg>',
 'pie'=>'<svg viewBox="0 0 24 24"><path d="M11 3a9 9 0 1 0 10 10H11zM14 3v7h7a9 9 0 0 0-7-7z"/></svg>',
 'heading'=>'<svg viewBox="0 0 24 24"><path d="M5 5v14M19 5v14M5 12h14"/></svg>',
 'text'=>'<svg viewBox="0 0 24 24"><path d="M4 6h16M8 6v14M16 6v14M7 20h10"/></svg>',
 'button'=>'<svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="3"/><path d="M9 12h6"/></svg>',
 'columns'=>'<svg viewBox="0 0 24 24"><rect x="3" y="4" width="8" height="16" rx="1"/><rect x="13" y="4" width="8" height="16" rx="1"/></svg>',
 'columns3'=>'<svg viewBox="0 0 24 24"><rect x="2" y="4" width="5" height="16" rx="1"/><rect x="9.5" y="4" width="5" height="16" rx="1"/><rect x="17" y="4" width="5" height="16" rx="1"/></svg>',
 'quote'=>'<svg viewBox="0 0 24 24"><path d="M6 9h5v5H7a4 4 0 0 1-4-4V8a5 5 0 0 1 5-5M16 9h5v5h-4a4 4 0 0 1-4-4V8a5 5 0 0 1 5-5"/></svg>',
 'spacer'=>'<svg viewBox="0 0 24 24"><path d="M5 5h14M5 19h14M12 8v8M9 11l3-3 3 3M9 13l3 3 3-3"/></svg>',
 'social'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><circle cx="5" cy="7" r="2"/><circle cx="19" cy="7" r="2"/><circle cx="5" cy="18" r="2"/><circle cx="19" cy="18" r="2"/><path d="m7 8.3 2.4 2M17 8.3l-2.4 2M7 16.7l2.4-2M17 16.7l-2.4-2"/></svg>',
 'footer'=>'<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15h18M8 18h8"/></svg>',
 'hero'=>'<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="m4 16 5-5 4 4 2-2 5 5M8 8h.01"/></svg>',
 'template'=>'<svg viewBox="0 0 24 24"><path d="M4 3h16v18H4zM4 8h16M9 8v13"/></svg>',
 'download'=>'<svg viewBox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5M4 20h16"/></svg>',
 'filter'=>'<svg viewBox="0 0 24 24"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></svg>',
 'help'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.8 9a2.4 2.4 0 0 1 4.6 1c0 2-2.4 2.1-2.4 4M12 18h.01"/></svg>',
 'search'=>'<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>',
 'moon'=>'<svg viewBox="0 0 24 24"><path d="M20 15.2A8.5 8.5 0 0 1 8.8 4 8.5 8.5 0 1 0 20 15.2z"/></svg>',
 'sun'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg>'
 ]; return $icons[$name]??'';
}
function layout_start(string $title,string $active): void {
 $u=current_user();$f=pull_flash();$first=trim(explode(' ',trim($u['name']??'Usuario'))[0]??'Usuario');$avatar=trim((string)($u['avatar_url']??''));
 ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script>(function(){try{var t=localStorage.getItem('emailsend-theme')||'light';document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/app.css?v=<?=@filemtime(__DIR__.'/assets/app.css')?>"><title><?=e($title)?> · <?=e(APP_NAME)?></title></head><body class="page-<?=e($active)?>"><div class="layout v05"><aside class="sidebar"><a class="brand-logo" href="index.php"><img src="assets/logo-emailsend.png" alt="EMAILSEND"></a><div class="sidebar-label">PLATAFORMA</div><nav class="menu"><a style="color:#d1f3ff;font-weight:800;border:1px solid #2b6b91;margin-bottom:13px;border-radius:9px;padding:10px 12px;display:block;text-decoration:none" href="../dashboard.php">← VOLVER A ONE HUB</a><?php if(current_user_can('dashboard')):?><a class="nav-dashboard <?=$active==='dashboard'?'active':''?>" href="index.php"><span class="nav-ico"><?=ui_icon('home')?></span><span>Dashboard</span></a><?php endif?><?php if(current_user_can('campaigns')):?><a class="nav-campaigns <?=$active==='campaigns'?'active':''?>" href="index.php?route=campaigns"><span class="nav-ico"><?=ui_icon('mail')?></span><span>Campañas</span></a><?php endif?><?php if(current_user_can('campaigns')):?><a class="nav-delivery-lab <?=$active==='delivery_lab'?'active':''?>" href="index.php?route=delivery_lab"><span class="nav-ico"><?=ui_icon('test')?></span><span>Pruebas de entrega</span></a><?php endif?><?php if(current_user_can('contacts')):?><a class="nav-contacts <?=$active==='contacts'?'active':''?>" href="index.php?route=contacts"><span class="nav-ico"><?=ui_icon('users')?></span><span>Contactos</span></a><?php endif?><?php if(current_user_can('reports')):?><a class="nav-reports <?=$active==='reports'?'active':''?>" href="index.php?route=reports"><span class="nav-ico"><?=ui_icon('chart')?></span><span>Reportes</span></a><?php endif?><?php if(is_admin()):?><a class="nav-clients <?=$active==='clients'?'active':''?>" href="index.php?route=clients"><span class="nav-ico"><?=ui_icon('client')?></span><span>Clientes / Portal</span></a><a class="nav-administrators <?=$active==='administrators'?'active':''?>" href="index.php?route=administrators"><span class="nav-ico"><?=ui_icon('profile')?></span><span>Usuarios / Permisos</span></a><?php endif?><?php if(is_admin()):?><a class="nav-settings <?=$active==='settings'?'active':''?>" href="index.php?route=settings"><span class="nav-ico"><?=ui_icon('settings')?></span><span>Configuración</span></a><?php endif?></nav><div class="sidebar-spacer"></div><?php if(current_user_can('help')):?><a class="sidebar-help <?=$active==='help'?'active':''?>" href="index.php?route=help"><span class="nav-ico"><?=ui_icon('book')?></span><div><strong>Centro de ayuda</strong><small>Guías y buenas prácticas</small></div></a><?php endif?><div class="developer-credit"><span>Desarrollado por</span><img src="assets/developer-logo-white.png" alt="Sócrates AI Creative Studio"></div><div class="sidebar-version">EMAILSEND ONE · v<?=e(trim((string)@file_get_contents(__DIR__.'/VERSION'))?:'0.13.8')?></div></aside><main class="main"><div class="topbar"><div class="topbar-copy"><span class="topbar-eyebrow"><?=e(current_tenant_name())?></span><?php if(defined('APP_DEMO_MODE') && APP_DEMO_MODE):?><span style="display:inline-block;background:#e8f7de;color:#325a18;border-radius:999px;font-size:10px;padding:5px 9px;font-weight:900;margin-left:8px">DEMO · SIN ENVÍOS REALES</span><?php endif;?><h1><?=e($title)?></h1><div class="muted">Campañas claras, entrega segura y resultados que se entienden.</div></div><div class="actions top-actions"><div class="global-search" id="globalSearch"><button type="button" class="global-search-trigger" id="globalSearchTrigger" aria-label="Buscar en EMAILSEND" title="Buscar (Ctrl+K)"><?=ui_icon('search')?><span>Buscar</span><kbd>Ctrl K</kbd></button><div class="global-search-panel" id="globalSearchPanel" hidden><div class="global-search-input-wrap"><?=ui_icon('search')?><input type="search" id="globalSearchInput" autocomplete="off" placeholder="Buscar campañas, contactos, listas, reportes..."><button type="button" id="globalSearchClose" aria-label="Cerrar">×</button></div><div class="global-search-hint" id="globalSearchHint">Escribe al menos 2 caracteres.</div><div class="global-search-results" id="globalSearchResults"></div></div></div><button type="button" class="theme-toggle" id="themeToggle" aria-label="Cambiar modo claro u oscuro" title="Cambiar apariencia"><span class="theme-icon theme-icon-moon"><?=ui_icon('moon')?></span><span class="theme-icon theme-icon-sun"><?=ui_icon('sun')?></span></button><?php if(is_admin()): $ts=db()->query('SELECT id,name FROM tenants ORDER BY name')->fetchAll(); ?><form method="post" action="index.php?route=switch_tenant" class="tenant-switch"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><select name="tenant_id" onchange="this.form.submit()" aria-label="Empresa activa"><?php foreach($ts as $t):?><option value="<?=$t['id']?>" <?=$t['id']==current_tenant_id()?'selected':''?>><?=e($t['name'])?></option><?php endforeach?></select></form><?php endif?><details class="user-menu"><summary><span class="avatar"><?php if($avatar):?><img src="<?=e($avatar)?>" alt=""><?php else:?><?=e(strtoupper(substr($first,0,1)))?><?php endif?></span><span class="user-summary"><strong><?=e($u['name'])?></strong><small><?=e($u['job_title']?: (is_admin()?'Administrador':'Usuario'))?></small></span><span class="chevron">⌄</span></summary><div class="user-popover"><div class="user-popover-head"><span class="avatar large"><?php if($avatar):?><img src="<?=e($avatar)?>" alt=""><?php else:?><?=e(strtoupper(substr($first,0,1)))?><?php endif?></span><div><strong><?=e($u['name'])?></strong><small><?=e($u['email'])?></small></div></div><a href="index.php?route=profile"><span class="mini-ico"><?=ui_icon('profile')?></span><span><strong>Preferencias</strong><small>Foto, nombre y datos</small></span></a><?php if(is_admin()):?><a href="index.php?route=settings"><span class="mini-ico"><?=ui_icon('shield')?></span><span><strong>Seguridad y SMTP</strong><small>Entrega y DNS</small></span></a><?php endif?><a href="index.php?route=help"><span class="mini-ico"><?=ui_icon('help')?></span><span><strong>Centro de ayuda</strong><small>Guías y solución de problemas</small></span></a><a class="logout-link" href="index.php?route=logout"><span class="mini-ico"><?=ui_icon('logout')?></span><span><strong>Cerrar sesión</strong><small>Salir de EMAILSEND</small></span></a></div></details></div></div><?php if($f):?><div class="flash <?=e($f['type'])?>"><?=e($f['message'])?></div><?php endif?>
<?php }
function layout_end(): void { ?><script src="assets/app-ui.js?v=<?=@filemtime(__DIR__.'/assets/app-ui.js')?>"></script></main></div></body></html><?php }

if($route==='dashboard'){
 $tid=current_tenant_id();$u=current_user();$first=trim(explode(' ',trim($u['name']??'Usuario'))[0]??'Usuario');layout_start('¡Bienvenido, '.$first.'!','dashboard');
 $m=dashboard_summary($tid);$series=dashboard_series($tid,14);$sentVals=array_column($series,'sent');$openVals=array_column($series,'opens');$clickVals=array_column($series,'clicks');$comparison=dashboard_campaign_comparison($tid,5);
 $st=db()->prepare('SELECT * FROM campaigns WHERE tenant_id=? ORDER BY COALESCE(sent_at,created_at) DESC LIMIT 1');$st->execute([$tid]);$latest=$st->fetch();$lm=$latest?campaign_metrics((int)$latest['id']):null;$links=$latest?campaign_top_links((int)$latest['id'],4):[];
 $ls=db()->prepare('SELECT l.name,COUNT(c.id) total FROM contact_lists l LEFT JOIN contacts c ON c.list_id=l.id WHERE l.tenant_id=? GROUP BY l.id,l.name ORDER BY total DESC LIMIT 4');$ls->execute([$tid]);$listRows=$ls->fetchAll();
 $cs=db()->prepare('SELECT status,COUNT(*) total FROM contacts WHERE tenant_id=? GROUP BY status');$cs->execute([$tid]);$contactStatus=['active'=>0,'bounced'=>0,'unsubscribed'=>0];foreach($cs as $r)$contactStatus[$r['status']] = (int)$r['total'];$ctTotal=max(1,array_sum($contactStatus));$activePct=round($contactStatus['active']/$ctTotal*100,1);$bouncedPct=round($contactStatus['bounced']/$ctTotal*100,1);$unsubPct=round($contactStatus['unsubscribed']/$ctTotal*100,1);
 $cc=db()->prepare('SELECT COUNT(*) FROM report_clients WHERE tenant_id=?');$cc->execute([$tid]);$clientCount=(int)$cc->fetchColumn();
 $statusRows=db()->prepare('SELECT status,COUNT(*) total FROM campaigns WHERE tenant_id=? GROUP BY status');$statusRows->execute([$tid]);$campaignStatus=['draft'=>0,'queued'=>0,'sending'=>0,'paused'=>0,'sent'=>0,'failed'=>0];foreach($statusRows as $r)$campaignStatus[(string)$r['status']]=(int)$r['total'];$inProgress=$campaignStatus['queued']+$campaignStatus['sending'];
 $sq=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$sq->execute([$tid]);$dashCfg=$sq->fetch()?:[];$smtpReady=!empty($dashCfg['smtp_host'])&&filter_var($dashCfg['default_from_email']??'',FILTER_VALIDATE_EMAIL);
 $recentQ=db()->prepare('SELECT id,name,subject,status,COALESCE(sent_at,updated_at,created_at) date_ref FROM campaigns WHERE tenant_id=? ORDER BY COALESCE(sent_at,updated_at,created_at) DESC LIMIT 5');$recentQ->execute([$tid]);$recentCampaigns=$recentQ->fetchAll();
 $heroTitle='Tu próxima campaña';$heroText='Diseña un email profesional con bloques visuales o HTML propio.';if($latest){$bs=json_decode($latest['blocks_json']??'[]',true)?:[];foreach($bs as $b)if(($b['type']??'')==='hero'){$heroTitle=strip_tags($b['title']??$heroTitle);$heroText=strip_tags($b['text']??$heroText);break;}}
 ?>
 <section class="dashboard-v0922-hero">
   <div class="dashboard-v0922-hero-grid" aria-hidden="true"></div>
   <div class="dashboard-v0922-copy">
     <span class="hero-kicker">EMAILSEND ONE · CENTRO DE CONTROL</span>
     <h2>Diseña. Envía. <span>Mide lo que importa.</span></h2>
     <p>Campañas, contactos, clientes y rendimiento en una vista más clara para tomar decisiones rápido.</p>
     <div class="hero-actions"><div class="actions"><a class="btn btn-blue" href="index.php?route=delivery_lab"><span class="btn-ico"><?=ui_icon('test')?></span>Pruebas de entrega</a><a class="btn btn-orange" href="index.php?route=campaign_edit"><span class="btn-ico"><?=ui_icon('plus')?></span>Nueva campaña</a></div><a class="btn btn-blue" href="index.php?route=reports"><span class="btn-ico"><?=ui_icon('chart')?></span>Ver reportes</a></div>
   </div>
   <aside class="dashboard-command-card">
     <div class="command-head"><div><span>ESTADO OPERATIVO</span><strong><?=$smtpReady?'Listo para enviar':'Requiere configuración'?></strong></div><i class="<?=$smtpReady?'ok':'warn'?>"><?=ui_icon($smtpReady?'send':'settings')?></i></div>
     <div class="command-status"><div><span>SMTP</span><b class="<?=$smtpReady?'ok':'warn'?>"><?=$smtpReady?'CONFIGURADO':'PENDIENTE'?></b></div><div><span>En proceso</span><strong><?=$inProgress?></strong></div><div><span>Base activa</span><strong><?=number_format($contactStatus['active'],0,',','.')?></strong></div></div>
     <div class="command-progress"><span><b>Tasa de entrega</b><em><?=number_format((float)$m['delivery_rate'],1,',','.')?>%</em></span><i><b style="width:<?=min(100,(float)$m['delivery_rate'])?>%"></b></i></div>
   </aside>
 </section>

 <nav class="dashboard-quick-actions" aria-label="Acciones rápidas">
   <a href="index.php?route=campaign_edit"><span class="qa-icon orange"><?=ui_icon('edit')?></span><span><strong>Crear campaña</strong><small>Diseñar un nuevo envío</small></span><b>→</b></a>
   <a href="index.php?route=contacts"><span class="qa-icon blue"><?=ui_icon('users')?></span><span><strong>Contactos</strong><small>Importar o administrar la base</small></span><b>→</b></a>
   <?php if(is_admin()):?><a href="index.php?route=clients"><span class="qa-icon green"><?=ui_icon('client')?></span><span><strong>Clientes</strong><small>Portal y permisos</small></span><b>→</b></a><?php else:?><a href="index.php?route=campaigns"><span class="qa-icon green"><?=ui_icon('mail')?></span><span><strong>Campañas</strong><small>Gestionar envíos</small></span><b>→</b></a><?php endif?>
   <a href="index.php?route=reports"><span class="qa-icon sky"><?=ui_icon('chart')?></span><span><strong>Reportes</strong><small>Revisar rendimiento</small></span><b>→</b></a>
 </nav>

 <div class="section-heading dashboard-section-title"><div><span class="eyebrow">PANORAMA GENERAL</span><h2>Rendimiento de tus campañas</h2><p class="muted">Las métricas esenciales, sin ruido.</p></div><a href="index.php?route=campaigns" class="text-link">Ver todas las campañas →</a></div>
 <?php $metricData=[
   ['blue','mail','Campañas',$m['campaigns'],$m['campaign_sent_rate'],'creadas',number_format($m['campaign_sent_rate'],1,',','.').'% enviadas'],
   ['green','send','Enviados',number_format($m['sent'],0,',','.'),$m['delivery_rate'],$m['delivered'].' entregados',number_format($m['delivery_rate'],1,',','.').'% entrega'],
   ['sky','eye','Apertura',$m['open_rate'].'%',$m['open_rate'],$m['unique_opens'].' personas','única'],
   ['orange','cursor','Clics',$m['ctr'].'%',$m['ctr'],$m['unique_clicks'].' personas','CTR único'],
   ['red','bounce','Rebotes',$m['bounce_rate'].'%',$m['bounce_rate'],$m['bounced'].' registrados','mantener bajo'],
   ['lime','minus-user','Bajas',$m['unsub_rate'].'%',$m['unsub_rate'],$m['unsub'].' contactos','desuscripciones']
 ];?>
 <div class="dashboard-kpi-grid"><?php foreach($metricData as $md):$p=max(0,min(100,(float)$md[4]));?><article class="dashboard-kpi <?=$md[0]?>"><span class="dashboard-kpi-icon"><?=ui_icon($md[1])?></span><div><small><?=e($md[2])?></small><strong><?=e((string)$md[3])?></strong><em><?=e($md[5])?></em></div><span class="dashboard-kpi-rate"><?=e($md[6])?></span><i class="dashboard-kpi-line"><b style="width:<?=$p?>%"></b></i></article><?php endforeach?></div>

 <div class="dashboard-v0922-main">
   <section class="card dashboard-chart-card"><div class="panel-head"><div><span class="eyebrow">TENDENCIA · 14 DÍAS</span><h3>Rendimiento de campañas</h3><span class="muted">Envíos, aperturas únicas y clics en una sola lectura.</span></div><a href="index.php?route=reports" class="btn btn-light"><span class="btn-ico"><?=ui_icon('chart')?></span>Reportes</a></div><div class="legend"><span class="lg sent">Enviados</span><span class="lg opens">Aperturas</span><span class="lg clicks">Clics</span></div><div class="line-chart dashboard-line-chart"><svg viewBox="0 0 640 190" preserveAspectRatio="none" aria-label="Rendimiento de campañas"><line x1="18" y1="172" x2="622" y2="172" class="axis"/><line x1="18" y1="120" x2="622" y2="120" class="gridline"/><line x1="18" y1="68" x2="622" y2="68" class="gridline"/><polyline points="<?=e(sparkline_points($sentVals))?>" class="series sent"/><polyline points="<?=e(sparkline_points($openVals))?>" class="series opens"/><polyline points="<?=e(sparkline_points($clickVals))?>" class="series clicks"/></svg><div class="chart-labels"><?php foreach($series as $i=>$r)if($i%2===0):?><span><?=e($r['label'])?></span><?php endif?></div></div></section>
   <section class="card dashboard-campaign-state"><div class="panel-head"><div><span class="eyebrow">FLUJO DE TRABAJO</span><h3>Estado de campañas</h3><span class="muted">Qué está pendiente y qué ya salió.</span></div><a href="index.php?route=campaigns" class="btn btn-light">Gestionar</a></div><div class="campaign-state-grid"><div class="draft"><span>Borradores</span><strong><?=$campaignStatus['draft']?></strong><small>por terminar</small></div><div class="queue"><span>En cola</span><strong><?=$inProgress?></strong><small>en proceso</small></div><div class="sent"><span>Enviadas</span><strong><?=$campaignStatus['sent']?></strong><small>completadas</small></div><div class="paused"><span>Pausadas</span><strong><?=$campaignStatus['paused']?></strong><small>requieren acción</small></div></div><div class="dashboard-attention"><span class="attention-icon"><?=ui_icon('shield')?></span><div><strong><?=$smtpReady?'Sistema preparado':'Configura el envío'?></strong><small><?=$smtpReady?'SMTP configurado. Puedes preparar nuevas campañas.':'El SMTP o el remitente por defecto todavía está pendiente.'?></small></div><?php if(!$smtpReady&&is_admin()):?><a href="index.php?route=settings">Configurar →</a><?php endif?></div></section>
 </div>

 <div class="dashboard-v0922-secondary">
   <section class="card dashboard-recent-card"><div class="panel-head"><div><span class="eyebrow">ACTIVIDAD</span><h3>Campañas recientes</h3><span class="muted">Continúa trabajando justo donde lo dejaste.</span></div><a class="btn btn-orange" href="index.php?route=campaign_edit"><span class="btn-ico"><?=ui_icon('plus')?></span>Nueva</a></div><div class="recent-campaign-list"><?php foreach($recentCampaigns as $rc):?><a href="index.php?route=campaign_edit&id=<?=$rc['id']?>"><span class="recent-campaign-icon"><?=ui_icon('mail')?></span><span class="recent-campaign-copy"><strong><?=e($rc['name'])?></strong><small><?=e($rc['subject']?:'Sin asunto')?> · <?=e($rc['date_ref']?date('d/m/Y',strtotime($rc['date_ref'])):'')?></small></span><span class="badge <?=e($rc['status'])?>"><?=e($rc['status'])?></span><b>→</b></a><?php endforeach?><?php if(!$recentCampaigns):?><div class="dashboard-empty"><strong>Aún no hay campañas</strong><span>Crea la primera y aparecerá aquí.</span></div><?php endif?></div></section>
   <section class="card contact-health dashboard-health-card"><div class="panel-head"><div><span class="eyebrow">SALUD DE LA BASE</span><h3>Calidad de contactos</h3><span class="muted">Una base limpia protege la entregabilidad.</span></div><a href="index.php?route=contacts" class="btn btn-light">Ver base</a></div><div class="health-layout"><div class="health-donut" style="--active:<?=$activePct?>;--bounced:<?=$bouncedPct?>"><div><strong><?=$activePct?>%</strong><span>activos</span></div></div><div class="health-legend"><p><i class="active"></i><span>Activos</span><strong><?=$contactStatus['active']?></strong></p><p><i class="bounced"></i><span>Rebotados</span><strong><?=$contactStatus['bounced']?></strong></p><p><i class="unsub"></i><span>Bajas</span><strong><?=$contactStatus['unsubscribed']?></strong></p></div></div></section>
   <section class="card delivery-funnel dashboard-funnel-card"><div class="panel-head"><div><span class="eyebrow">CONVERSIÓN</span><h3>De envío a interacción</h3><span class="muted">Visualiza dónde avanza tu audiencia.</span></div></div><?php $funnel=[['Enviados',$m['sent'],100,'blue'],['Entregados',$m['delivered'],$m['sent']?round($m['delivered']/$m['sent']*100,1):0,'green'],['Abrieron',$m['unique_opens'],$m['sent']?round($m['unique_opens']/$m['sent']*100,1):0,'sky'],['Hicieron clic',$m['unique_clicks'],$m['sent']?round($m['unique_clicks']/$m['sent']*100,1):0,'orange']];?><div class="funnel-list"><?php foreach($funnel as $f):?><div><span><?=e($f[0])?></span><i><b class="<?=$f[3]?>" style="width:<?=min(100,$f[2])?>%"></b></i><strong><?=number_format($f[1],0,',','.')?></strong></div><?php endforeach?></div></section>
 </div>

 <div class="dashboard-v0922-bottom">
   <section class="card contacts-widget"><div class="panel-head"><div><span class="eyebrow">CONTACTOS</span><h3>Listas principales</h3><span class="muted"><b class="big-inline"><?=number_format($m['contacts'],0,',','.')?></b> contactos totales</span></div><a class="btn btn-blue" href="index.php?route=contacts"><span class="btn-ico"><?=ui_icon('plus')?></span>Administrar</a></div><div class="simple-list"><?php foreach($listRows as $lr):?><div><span><?=e($lr['name'])?></span><b><?=number_format((int)$lr['total'],0,',','.')?></b></div><?php endforeach?><?php if(!$listRows):?><p class="muted">Todavía no hay listas.</p><?php endif?></div></section>
   <section class="card report-widget"><div class="panel-head"><div><span class="eyebrow">ÚLTIMO REPORTE</span><h3><?=e($latest['name']??'Sin campañas todavía')?></h3><span class="muted"><?=e($latest['subject']??'Crea una campaña para empezar a medir.')?></span></div><?php if($latest):?><a class="btn btn-light" href="index.php?route=report&id=<?=$latest['id']?>"><span class="btn-ico"><?=ui_icon('eye')?></span>Ver reporte</a><?php endif?></div><?php if($latest):?><div class="dashboard-report-metrics"><div><span>Entregados</span><strong class="green-txt"><?=$lm['delivered']?></strong></div><div><span>Apertura</span><strong class="blue-txt"><?=$lm['open_rate']?>%</strong></div><div><span>CTR</span><strong class="orange-txt"><?=$lm['ctr']?>%</strong></div><div><span>Bajas</span><strong><?=$lm['unsub']?></strong></div></div><?php else:?><div class="dashboard-empty"><strong>No hay datos todavía</strong><span>El rendimiento aparecerá después del primer envío.</span></div><?php endif?></section>
   <?php if(is_admin()):?><section class="card portal-widget"><div class="panel-head"><div><span class="eyebrow">PORTAL DE CLIENTES</span><h3><?=$clientCount?> cliente(s)</h3><span class="muted">Acceso privado a campañas y reportes.</span></div><span class="lock-badge"><?=ui_icon('shield')?></span></div><div class="portal-compact-list"><span><i>✓</i> Permisos por cliente</span><span><i>✓</i> Reportes privados</span><span><i>✓</i> Campañas autorizadas</span></div><div class="actions"><a class="btn btn-green" href="index.php?route=clients"><span class="btn-ico"><?=ui_icon('users')?></span>Gestionar</a><a class="btn btn-light" target="_blank" href="index.php?portal=inicio"><span class="btn-ico"><?=ui_icon('eye')?></span>Abrir portal</a></div></section><?php else:?><section class="card dashboard-builder-card"><div class="panel-head"><div><span class="eyebrow">BUILDER</span><h3>Editor visual</h3><span class="muted">Construye campañas con bloques y columnas.</span></div></div><div class="dashboard-builder-preview"><div><strong><?=e($heroTitle)?></strong><span><?=e($heroText)?></span></div><a class="btn btn-green" href="index.php?route=campaign_edit"><span class="btn-ico"><?=ui_icon('edit')?></span>Abrir editor</a></div></section><?php endif?>
 </div>
 <?php layout_end();exit;
}
if($route==='contacts'){
 layout_start('Contactos','contacts');$tid=current_tenant_id();$filterList=(int)($_GET['list_id']??0);$filterClient=(int)($_GET['client_id']??0);$filterStatus=trim($_GET['status']??'');$search=trim($_GET['q']??'');$page=max(1,(int)($_GET['page']??1));$perPage=200;
 $allowed=user_allowed_list_ids($tid);
 $listSql='SELECT l.*,rc.company client_company,(SELECT COUNT(*) FROM contacts c WHERE c.list_id=l.id) contact_count,(SELECT GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ", ") FROM user_contact_lists ucl JOIN users u ON u.id=ucl.user_id WHERE ucl.list_id=l.id AND u.active=1) assigned_users,(SELECT GROUP_CONCAT(u.id ORDER BY u.name SEPARATOR ",") FROM user_contact_lists ucl JOIN users u ON u.id=ucl.user_id WHERE ucl.list_id=l.id AND u.active=1) assigned_user_ids FROM contact_lists l LEFT JOIN report_clients rc ON rc.id=l.client_id WHERE l.tenant_id=?';$listParams=[$tid];
 if($allowed!==null){if($allowed){$listSql.=' AND l.id IN ('.implode(',',array_fill(0,count($allowed),'?')).')';$listParams=array_merge($listParams,$allowed);}else{$listSql.=' AND 1=0';}}
 $listSql.=' ORDER BY COALESCE(rc.company,"ZZZZ"),l.name';$ls=db()->prepare($listSql);$ls->execute($listParams);$lists=$ls->fetchAll();
 if($filterList && !in_array($filterList,array_map(fn($x)=>(int)$x['id'],$lists),true))$filterList=0;
 $cl=db()->prepare('SELECT id,company FROM report_clients WHERE tenant_id=? AND active=1 ORDER BY company');$cl->execute([$tid]);$clients=$cl->fetchAll();
 $team=[];if(is_admin()){$q=db()->prepare('SELECT id,name,email,role,list_access_mode FROM users WHERE tenant_id=? AND active=1 AND role IN("admin","user") ORDER BY name');$q->execute([$tid]);$team=$q->fetchAll();}
 $listAssignedIds=function(array $l):array{$raw=trim((string)($l['assigned_user_ids']??''));if($raw==='')return [];return array_values(array_filter(array_map('intval',explode(',',$raw))));};
 $listUserLabel=function(array $l):string{$names=trim((string)($l['assigned_users']??''));return $names!==''?$names:'Sin usuario asignado';};

 $from=' FROM contacts c LEFT JOIN contact_lists l ON l.id=c.list_id LEFT JOIN report_clients rc ON rc.id=l.client_id WHERE c.tenant_id=?';$params=[$tid];
 if($allowed!==null){if($allowed){$from.=' AND c.list_id IN ('.implode(',',array_fill(0,count($allowed),'?')).')';$params=array_merge($params,$allowed);}else{$from.=' AND 1=0';}}
 if($filterList){$from.=' AND c.list_id=?';$params[]=$filterList;}if($filterClient){$from.=' AND l.client_id=?';$params[]=$filterClient;}if(in_array($filterStatus,['active','bounced','unsubscribed'],true)){$from.=' AND c.status=?';$params[]=$filterStatus;}
 if($search!==''){$from.=' AND (c.name LIKE ? OR c.email LIKE ? OR c.company_name LIKE ? OR c.legal_representative LIKE ? OR c.phone_mobile LIKE ? OR c.phone_fixed LIKE ? OR c.economic_activity LIKE ? OR c.region LIKE ? OR c.province LIKE ? OR c.commune LIKE ? OR c.source_data_json LIKE ?)';for($i=0;$i<11;$i++)$params[]='%'.$search.'%';}
 $cnt=db()->prepare('SELECT COUNT(*)'.$from);$cnt->execute($params);$totalContacts=(int)$cnt->fetchColumn();$pages=max(1,(int)ceil($totalContacts/$perPage));if($page>$pages)$page=$pages;$offset=($page-1)*$perPage;
 $sql='SELECT c.*,l.name list_name,l.client_id,rc.company client_company'.$from.' ORDER BY c.id DESC LIMIT '.$perPage.' OFFSET '.$offset;$st=db()->prepare($sql);$st->execute($params);$contacts=$st->fetchAll();
 $customFields=contact_custom_fields($tid);$customValues=[];$contactIds=array_map(fn($c)=>(int)$c['id'],$contacts);
 if($contactIds&&$customFields){$ph=implode(',',array_fill(0,count($contactIds),'?'));$q=db()->prepare('SELECT contact_id,field_id,value FROM contact_custom_values WHERE contact_id IN ('.$ph.')');$q->execute($contactIds);foreach($q as $r)$customValues[(int)$r['contact_id']][(int)$r['field_id']]=$r['value'];}
 $importJobId=preg_replace('/[^a-f0-9]/','',(string)($_GET['import_job']??''));$importJob=$importJobId?contact_import_load_job($importJobId):null;
 $mapJobId=preg_replace('/[^a-f0-9]/','',(string)($_GET['import_map']??''));$mapJob=$mapJobId?contact_import_load_job($mapJobId):null;
 ?>
 <?php if($mapJob && (int)($mapJob['tenant_id']??0)===$tid && ($mapJob['stage']??'')==='mapping'):?>
 <section class="card smart-import-map"><div class="panel-head"><div><span class="eyebrow">IMPORTADOR INTELIGENTE</span><h3>Relaciona las columnas antes de importar</h3><p class="muted"><?=e($mapJob['original']??'Base')?> · <?=count((array)$mapJob['headers'])?> columnas detectadas. EMAILSEND recordará tus decisiones para próximas bases.</p></div><span class="settings-icon"><?=ui_icon('layers')?></span></div>
 <div class="smart-map-actions"><button type="button" class="btn btn-blue" id="mapUseAll"><span class="btn-ico"><?=ui_icon('plus')?></span>Usar todas las columnas</button><button type="button" class="btn btn-light" id="mapKnownOnly">Solo reconocidas</button><a class="btn btn-light" href="index.php?route=contacts">Cancelar</a></div>
 <form method="post" action="index.php?route=contact_import_configure" id="smartMapForm"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="job" value="<?=e($mapJobId)?>">
 <div class="smart-map-table"><div class="smart-map-row smart-map-head"><b>Columna del archivo</b><b>Ejemplo</b><b>Campo EMAILSEND</b></div>
 <?php $std=contact_standard_fields();$suggest=(array)($mapJob['suggestions']??[]);$samples=(array)($mapJob['samples']??[]);foreach((array)$mapJob['headers'] as $i=>$header):$sample='';foreach($samples as $sr){if(trim((string)($sr[$i]??''))!==''){$sample=(string)$sr[$i];break;}}$sel=(string)($suggest[$i]??'new');?>
 <div class="smart-map-row"><div><strong><?=e($header?:('Columna '.($i+1)))?></strong><small><?=e(contact_normalize_header((string)$header))?></small></div><div class="smart-map-sample"><?=e(function_exists('mb_strimwidth')?mb_strimwidth($sample,0,70,'…','UTF-8'):substr($sample,0,70))?></div><div><select name="mapping[<?=$i?>]" class="map-target" data-suggest="<?=e($sel)?>">
 <option value="ignore" <?=$sel==='ignore'?'selected':''?>>No importar</option><optgroup label="Campos estándar"><?php foreach($std as $key=>$label):?><option value="std:<?=e($key)?>" <?=$sel==='std:'.$key?'selected':''?>><?=e($label)?></option><?php endforeach?></optgroup>
 <?php if($customFields):?><optgroup label="Campos creados"><?php foreach($customFields as $f):?><option value="custom:<?=$f['id']?>" <?=$sel==='custom:'.$f['id']?'selected':''?>><?=e($f['name'])?></option><?php endforeach?></optgroup><?php endif?>
 <option value="new" <?=$sel==='new'?'selected':''?>>＋ Crear campo nuevo “<?=e($header?:('Columna '.($i+1)))?>”</option></select></div></div><?php endforeach?></div>
 <div class="smart-map-footer"><div><strong>Importante:</strong> una columna debe quedar asociada a <b>Email</b>. Las columnas nuevas quedarán disponibles para futuras importaciones.</div><button class="btn btn-orange"><span class="btn-ico"><?=ui_icon('save')?></span>Confirmar campos e importar</button></div></form></section>
 <script>(function(){var sels=[].slice.call(document.querySelectorAll('.map-target'));var all=document.getElementById('mapUseAll'),known=document.getElementById('mapKnownOnly');if(all)all.onclick=function(){sels.forEach(function(s){var sug=s.dataset.suggest||'new';s.value=(sug==='ignore'||sug==='new')?'new':sug;});};if(known)known.onclick=function(){sels.forEach(function(s){var sug=s.dataset.suggest||'new';s.value=(sug==='new')?'ignore':sug;});};})();</script>
 <?php endif;?>
 <?php if($importJob && (int)($importJob['tenant_id']??0)===$tid):?><div class="card import-progress-card" id="contactImportProgress" data-job="<?=e($importJobId)?>" data-csrf="<?=e(csrf_token())?>"><div class="panel-head"><div><span class="eyebrow">IMPORTACIÓN MASIVA</span><h3><?=e($importJob['original']??'Base de contactos')?></h3><p class="muted">Procesamiento por bloques para bases grandes sin cortar el servidor.</p></div><strong id="importPct"><?=!empty($importJob['done'])?'100':'0'?>%</strong></div><div class="import-progress-track"><i id="importBar" style="width:<?=!empty($importJob['done'])?'100':'0'?>%"></i></div><div class="import-stats"><span>Filas <b id="importRows"><?=number_format((int)($importJob['rows']??0),0,',','.')?></b></span><span>Emails creados/actualizados <b id="importEmails"><?=number_format((int)($importJob['emails']??0),0,',','.')?></b></span><span>Omitidos <b id="importSkipped"><?=number_format((int)($importJob['skipped']??0),0,',','.')?></b></span></div><p class="muted" id="importState"><?=!empty($importJob['done'])?'Importación terminada.':'Procesando…'?></p></div><?php endif;?>
 <div class="split contacts-entry-grid"><div class="card"><span class="eyebrow">NUEVO CONTACTO</span><h3>Agregar contacto / empresa</h3><form method="post" action="index.php?route=contact_save"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><div class="form-grid contact-expanded-form"><label>Empresa<input name="company_name"></label><label>Nombre / contacto<input name="name"></label><label>Email<input type="email" name="email" required></label><label>Móvil<input name="phone_mobile"></label><label>Fijo<input name="phone_fixed"></label><label>Representante legal<input name="legal_representative"></label><label>Tipo empresa<input name="company_type"></label><label>Tramo ventas<input name="sales_band"></label><label>Trabajadores<input name="employees"></label><label>Ingresos anuales<input name="annual_income"></label><label class="full">Actividad económica<input name="economic_activity"></label><label>Región<input name="region"></label><label>Provincia<input name="province"></label><label>Comuna<input name="commune"></label><label>Lista<select name="list_id" <?=!is_admin()?'required':''?>><option value=""><?=is_admin()?'Sin lista':'Selecciona una lista autorizada'?></option><?php foreach($lists as $l):?><option value="<?=$l['id']?>"><?=e($l['name'].' · '.$listUserLabel($l).($l['client_company']?' · '.$l['client_company']:''))?></option><?php endforeach?></select></label><div class="full"><button class="btn btn-orange"><span class="btn-ico"><?=ui_icon('save')?></span>Guardar contacto</button></div></div></form>
 <?php if(current_user_can('contacts','create')):?><hr class="soft"><span class="eyebrow">NUEVA LISTA</span><h3>Crear lista y asignarla a usuario(s)</h3><form method="post" action="index.php?route=list_save"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><div class="form-grid"><label>Nombre de la lista<input name="name" required placeholder="Ej: Clientes Santiago"></label><label>Cliente / área<select name="client_id"><option value="">Sin cliente</option><?php foreach($clients as $rc):?><option value="<?=$rc['id']?>"><?=e($rc['company'])?></option><?php endforeach?></select></label><div class="full"><span class="field-label">Usuarios autorizados</span><div class="permission-check-grid"><?php foreach($team as $u):?><label><input type="checkbox" name="user_ids[]" value="<?=$u['id']?>"><span><strong><?=e($u['name'])?></strong><small><?=e($u['email'])?> · <?=e($u['role']==='admin'?'Administrador':'Usuario')?></small></span></label><?php endforeach?></div><small class="field-help">Marca al menos al usuario responsable de esta lista. Si la lista es compartida, puedes marcar varios usuarios. Los administradores con acceso “Todas las listas” seguirán pudiendo verla.</small></div><div class="full"><button class="btn btn-light"><span class="btn-ico"><?=ui_icon('plus')?></span>Crear lista</button></div></div></form><?php endif?></div>
 <div class="card import-user-list-card"><span class="eyebrow">IMPORTADOR INTELIGENTE</span><h3>Importar contactos por usuario y lista</h3><p class="muted">Primero elige a qué usuario pertenece la base. Después EMAILSEND mostrará únicamente las listas asignadas a ese usuario, evitando cargar contactos en una lista equivocada.</p>
 <div class="import-recognized"><strong>Formatos</strong><span>CSV UTF-8</span><span>CSV Windows-1252</span><span>Excel .xlsx</span><span>Campos personalizados</span><span>Mapeo recordado</span></div>
 <form method="post" enctype="multipart/form-data" action="index.php?route=contact_import" id="contactImportForm"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
 <?php if(is_admin()):?>
 <label>1. Usuario / responsable
   <select name="import_user_id" id="importUserSelect" required>
     <option value="">Selecciona un usuario</option>
     <?php foreach($team as $u):?><option value="<?=$u['id']?>"><?=e($u['name'].' · '.$u['email'])?></option><?php endforeach?>
     <option value="-1">Sin usuario asignado</option>
   </select>
   <small class="field-help">Al elegir un usuario, abajo aparecerán solamente las listas asignadas a él.</small>
 </label>
 <?php endif?>
 <label><?=is_admin()?'2. Lista del usuario':'Lista autorizada'?>
   <select name="list_id" id="importListSelect" required <?=is_admin()?'disabled':''?>>
     <option value=""><?=is_admin()?'Primero selecciona un usuario':'Selecciona una lista'?></option>
     <?php foreach($lists as $l):$uids=$listAssignedIds($l);?>
       <option value="<?=$l['id']?>" data-users="<?=e(implode(',',$uids))?>" data-unassigned="<?=$uids?'0':'1'?>" data-owner="<?=e($listUserLabel($l))?>" data-list="<?=e($l['name'])?>" data-client="<?=e($l['client_company']??'')?>" data-count="<?=$l['contact_count']?>" <?=is_admin()?'hidden disabled':''?>><?=e($l['name'].' · '.$listUserLabel($l).($l['client_company']?' · '.$l['client_company']:''))?> (<?=$l['contact_count']?>)</option>
     <?php endforeach?>
   </select>
   <small class="field-help" id="importListHelp"><?=is_admin()?'Selecciona primero el responsable de la base.':'Solo aparecen las listas autorizadas para tu usuario.'?></small>
 </label>
 <label><?=is_admin()?'3. Archivo':'Archivo'?>
   <input type="file" name="csv" accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
 </label>
 <div class="import-target-summary" id="importTargetSummary"><span><?=ui_icon('users')?></span><div><strong>Destino de importación</strong><small><?=is_admin()?'Selecciona usuario y lista para confirmar dónde se guardarán los contactos.':'Selecciona una lista para confirmar el destino.'?></small></div></div>
 <button class="btn btn-blue" style="margin-top:12px"><span class="btn-ico"><?=ui_icon('download')?></span>Analizar columnas</button>
 </form>
 <div class="import-tip"><strong>Para bases muy grandes:</strong><small>CSV sigue siendo el formato más rápido. Excel .xlsx se admite para comodidad, pero una base de decenas de miles de filas puede procesarse más rápido como CSV UTF-8.</small></div>
 </div>
 <?php if(current_user_can('contacts','edit') && $filterList):$assignedQ=db()->prepare('SELECT user_id FROM user_contact_lists WHERE list_id=?');$assignedQ->execute([$filterList]);$assignedIds=array_map('intval',$assignedQ->fetchAll(PDO::FETCH_COLUMN));$currentList=null;foreach($lists as $l)if((int)$l['id']===$filterList){$currentList=$l;break;}if($currentList):?>
 <section class="card list-user-permissions"><div class="panel-head"><div><span class="eyebrow">GESTIÓN DE LISTA</span><h3><?=e($currentList['name'])?></h3><p class="muted">Renombra la lista, administra accesos o elimínala sin borrar los contactos.</p></div><span class="settings-icon"><?=ui_icon('settings')?></span></div><form method="post" action="index.php?route=list_update" class="list-rename-form"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="list_id" value="<?=$filterList?>"><label>Nombre de la lista<input name="name" value="<?=e($currentList['name'])?>" required></label><button class="btn btn-blue"><span class="btn-ico"><?=ui_icon('save')?></span>Guardar nombre</button></form><?php if(is_admin()):?><hr class="soft"><div class="panel-head"><div><span class="eyebrow">PERMISOS DE LISTA</span><h3>Usuarios autorizados</h3><p class="muted">Define qué usuarios podrán seleccionar esta lista al crear o enviar campañas.</p></div><span class="settings-icon"><?=ui_icon('shield')?></span></div><form method="post" action="index.php?route=list_permissions_save"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="list_id" value="<?=$filterList?>"><div class="permission-check-grid"><?php foreach($team as $u):?><label><input type="checkbox" name="user_ids[]" value="<?=$u['id']?>" <?=in_array((int)$u['id'],$assignedIds,true)?'checked':''?>><span><strong><?=e($u['name'])?></strong><small><?=e($u['email'])?> · <?=e($u['list_access_mode']==='all'?'Acceso global':'Solo listas asignadas')?></small></span></label><?php endforeach?></div><button class="btn btn-green" style="margin-top:14px"><span class="btn-ico"><?=ui_icon('save')?></span>Guardar usuarios autorizados</button></form><?php endif?><?php if(current_user_can('contacts','delete')):?><hr class="soft"><form method="post" action="index.php?route=list_delete"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="list_id" value="<?=$filterList?>"><button class="btn btn-danger" data-confirm data-confirm-variant="danger" data-confirm-title="Eliminar lista" data-confirm-message="¿Quieres eliminar esta lista?" data-confirm-note="Los contactos se conservarán y quedarán sin lista asignada." data-confirm-label="Sí, eliminar lista"><span class="btn-ico"><?=ui_icon('trash')?></span>Eliminar lista</button></form><?php endif?></section><?php endif;endif;?>
 <div class="card contacts-filter-card"><div class="toolbar"><div><span class="eyebrow">FILTROS</span><h3>Base de contactos</h3><span class="muted">Mostrando <?=count($contacts)?> de <?=number_format($totalContacts,0,',','.')?> contactos<?=($allowed!==null?' · según tus listas autorizadas':'')?></span></div><div class="actions"><details class="column-picker"><summary class="btn btn-light"><span class="btn-ico"><?=ui_icon('columns')?></span>Columnas</summary><div class="column-picker-menu" id="contactColumnPicker"></div></details><a class="btn btn-light" href="index.php?route=contacts"><span class="btn-ico"><?=ui_icon('redo')?></span>Limpiar filtros</a></div></div><form method="get" class="contact-filter-form"><input type="hidden" name="route" value="contacts"><?php if(is_admin()):?><label>Cliente<select name="client_id"><option value="">Todos los clientes</option><?php foreach($clients as $rc):?><option value="<?=$rc['id']?>" <?=$filterClient===$rc['id']?'selected':''?>><?=e($rc['company'])?></option><?php endforeach?></select></label><?php endif?><label>Lista<select name="list_id"><option value="">Todas mis listas</option><?php foreach($lists as $l):?><option value="<?=$l['id']?>" <?=$filterList===$l['id']?'selected':''?>><?=e($l['name'].' · '.$listUserLabel($l).($l['client_company']?' · '.$l['client_company']:''))?> (<?=$l['contact_count']?>)</option><?php endforeach?></select></label><label>Estado<select name="status"><option value="">Todos</option><option value="active" <?=$filterStatus==='active'?'selected':''?>>Activos</option><option value="bounced" <?=$filterStatus==='bounced'?'selected':''?>>Rebotados</option><option value="unsubscribed" <?=$filterStatus==='unsubscribed'?'selected':''?>>Desuscritos</option></select></label><label>Buscar<input name="q" value="<?=e($search)?>" placeholder="Empresa, email, teléfono, región…"></label><button class="btn btn-blue"><span class="btn-ico"><?=ui_icon('filter')?></span>Filtrar</button></form>
 <div class="list-summary-strip"><?php foreach($lists as $l):?><a href="index.php?route=contacts&list_id=<?=$l['id']?>" class="list-summary-pill <?=$filterList===$l['id']?'active':''?>"><span><?=ui_icon('users')?></span><div><strong><?=e($l['name'])?></strong><small><?=e($l['client_company']??'Sin cliente')?> · <?=$l['contact_count']?> contactos<?php if(is_admin()):?> · <?=e($l['assigned_users']?:'sin usuarios específicos')?><?php endif?></small></div></a><?php endforeach?></div>
 <?php
 $stdCols=[
 'company_name'=>['Empresa',true],'name'=>['Contacto',true],'email'=>['Email',true],'phone_mobile'=>['Móvil',true],'phone_fixed'=>['Fijo',false],
 'legal_representative'=>['Representante legal',false],'company_type'=>['Tipo empresa',false],'sales_band'=>['Tramo ventas',false],'employees'=>['Trabajadores',false],
 'annual_income'=>['Ingresos anual',false],'economic_activity'=>['Actividad económica',false],'region'=>['Región',false],'province'=>['Provincia',false],'commune'=>['Comuna',false],
 'client_company'=>['Cliente',false],'list_name'=>['Lista',true],'status'=>['Estado',true]
 ];
 ?>
 <?php if(current_user_can('contacts','delete') && $contacts):?>
 <form id="bulkContactDeleteForm" method="post" action="index.php?route=contacts_bulk_delete">
   <input type="hidden" name="csrf" value="<?=csrf_token()?>">
   <input type="hidden" name="return_to" value="<?=e('index.php?route=contacts&page='.$page.'&client_id='.$filterClient.'&list_id='.$filterList.'&status='.urlencode($filterStatus).'&q='.urlencode($search))?>">
 </form>
 <div class="bulk-contact-bar" id="bulkContactBar">
   <div class="bulk-contact-copy"><span class="bulk-contact-count" id="bulkContactCount">0</span><div><strong>contactos seleccionados</strong><small>Marca varios registros y elimínalos de una sola vez.</small></div></div>
   <div class="bulk-contact-actions"><button type="button" class="btn btn-light" id="bulkSelectVisible"><span class="btn-ico"><?=ui_icon('check')?></span>Seleccionar visibles</button><button type="button" class="btn btn-light" id="bulkClearSelection" disabled>Quitar selección</button><button type="submit" class="btn btn-danger" id="bulkDeleteButton" form="bulkContactDeleteForm" disabled data-confirm data-confirm-variant="danger" data-confirm-title="Eliminar contactos seleccionados" data-confirm-message="¿Quieres eliminar los contactos seleccionados?" data-confirm-note="Esta acción no se puede deshacer." data-confirm-label="Sí, eliminar"><span class="btn-ico"><?=ui_icon('trash')?></span>Eliminar seleccionados</button></div>
 </div>
 <?php endif;?>
 <div class="table-wrap contacts-data-table"><table class="table" id="contactsSmartTable"><thead><tr><?php if(current_user_can('contacts','delete')):?><th class="bulk-select-col"><input type="checkbox" id="contactSelectAll" aria-label="Seleccionar todos los contactos visibles"></th><?php endif?><?php foreach($stdCols as $key=>$def):?><th data-col="<?=e($key)?>" data-label="<?=e($def[0])?>" data-default="<?=$def[1]?'1':'0'?>"><?=e($def[0])?></th><?php endforeach?><?php foreach($customFields as $f):?><th data-col="custom-<?=$f['id']?>" data-label="<?=e($f['name'])?>" data-default="0"><?=e($f['name'])?></th><?php endforeach?><th>Acción</th></tr></thead><tbody><?php foreach($contacts as $c):?><tr>
 <?php if(current_user_can('contacts','delete')):?><td class="bulk-select-col"><input type="checkbox" class="contact-row-select" name="ids[]" value="<?=$c['id']?>" form="bulkContactDeleteForm" aria-label="Seleccionar <?=e($c['email']?:('contacto '.$c['id']))?>"></td><?php endif?>
 <?php foreach($stdCols as $key=>$def):$val=$c[$key]??'';if($key==='name')$val=$c['name']?:$c['legal_representative'];?><td data-col="<?=e($key)?>"><?php if($key==='email'):?><strong><?=e($val?:'—')?></strong><?php elseif($key==='status'):?><span class="badge <?=e($val)?>"><?=e($val)?></span><?php else:?><?=e($val?:'—')?><?php endif?></td><?php endforeach?>
 <?php foreach($customFields as $f):?><td data-col="custom-<?=$f['id']?>"><?=e($customValues[(int)$c['id']][(int)$f['id']]??'—')?></td><?php endforeach?>
 <td><form method="post" action="index.php?route=contact_delete"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="icon-danger" title="Eliminar" data-confirm data-confirm-variant="danger" data-confirm-title="Eliminar contacto" data-confirm-message="¿Quieres eliminar este contacto?" data-confirm-note="Esta acción elimina el registro seleccionado de EMAILSEND." data-confirm-label="Sí, eliminar"><?=ui_icon('trash')?></button></form></td></tr><?php endforeach?></tbody></table></div>
 <?php if($pages>1):?><div class="pagination"><?php for($i=max(1,$page-3);$i<=min($pages,$page+3);$i++):?><a class="<?=$i===$page?'active':''?>" href="index.php?route=contacts&page=<?=$i?>&client_id=<?=$filterClient?>&list_id=<?=$filterList?>&status=<?=e($filterStatus)?>&q=<?=urlencode($search)?>"><?=$i?></a><?php endfor?></div><?php endif?></div>
 <script>
 (function(){
   var user=document.getElementById('importUserSelect');
   var list=document.getElementById('importListSelect');
   var help=document.getElementById('importListHelp');
   var summary=document.getElementById('importTargetSummary');
   if(!list)return;
   var opts=[].slice.call(list.querySelectorAll('option[value]')).filter(function(o){return o.value!=='';});

   function destination(){
     var o=list.options[list.selectedIndex];
     if(!o || !o.value){
       if(summary)summary.querySelector('small').textContent='Selecciona una lista para confirmar dónde se guardarán los contactos.';
       return;
     }
     var owner=o.dataset.owner||'Sin usuario asignado';
     var client=o.dataset.client||'Sin cliente';
     var listName=o.dataset.list||o.textContent;
     var count=o.dataset.count||'0';
     if(summary)summary.querySelector('small').textContent='Usuario(s): '+owner+' · Lista: '+listName+' · Cliente/área: '+client+' · '+count+' contactos actuales';
   }

   function filterLists(){
     if(!user){destination();return;}
     var uid=user.value;
     list.value='';
     if(!uid){
       list.disabled=true;
       opts.forEach(function(o){o.hidden=true;o.disabled=true;});
       list.options[0].textContent='Primero selecciona un usuario';
       if(help)help.textContent='Selecciona primero el responsable de la base.';
       destination();
       return;
     }
     var matches=0;
     opts.forEach(function(o){
       var ids=(o.dataset.users||'').split(',').filter(Boolean);
       var show=(uid==='-1') ? o.dataset.unassigned==='1' : ids.indexOf(uid)!==-1;
       o.hidden=!show;
       o.disabled=!show;
       if(show)matches++;
     });
     list.disabled=false;
     list.options[0].textContent=matches
       ? 'Selecciona una lista ('+matches+' disponible'+(matches===1?'':'s')+')'
       : 'Este usuario no tiene listas asignadas';
     if(help)help.textContent=matches
       ? 'Solo se muestran las listas asignadas al usuario elegido.'
       : 'Asigna una lista a este usuario en “Gestión de lista” o crea una nueva lista.';
     destination();
   }

   if(user)user.addEventListener('change',filterLists);
   list.addEventListener('change',destination);
   if(user)filterLists(); else destination();
 })();
 </script>
 <script>
 (function(){
  var progress=document.getElementById('contactImportProgress');
  if(progress && progress.dataset.job){
   var job=progress.dataset.job,csrf=progress.dataset.csrf,pct=document.getElementById('importPct'),bar=document.getElementById('importBar'),rows=document.getElementById('importRows'),emails=document.getElementById('importEmails'),skipped=document.getElementById('importSkipped'),state=document.getElementById('importState');
   function fmt(n){return Number(n||0).toLocaleString('es-CL');}
   function step(){var fd=new FormData();fd.append('csrf',csrf);fd.append('job',job);fetch('index.php?route=contact_import_chunk',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(!d.ok)throw new Error(d.error||'Error');pct.textContent=d.percent+'%';bar.style.width=d.percent+'%';rows.textContent=fmt(d.rows);emails.textContent=fmt(d.emails);skipped.textContent=fmt(d.skipped);if(d.done){state.textContent='Importación terminada.';setTimeout(function(){location.href='index.php?route=contacts';},1200);}else{setTimeout(step,80);}}).catch(function(e){state.textContent='Error: '+e.message;});}
   if(state && state.textContent.indexOf('terminada')<0)step();
  }
  var table=document.getElementById('contactsSmartTable'),picker=document.getElementById('contactColumnPicker');if(!table||!picker)return;
  var rowChecks=[].slice.call(table.querySelectorAll('.contact-row-select')),selectAll=document.getElementById('contactSelectAll'),selectVisible=document.getElementById('bulkSelectVisible'),clearSelection=document.getElementById('bulkClearSelection'),bulkDelete=document.getElementById('bulkDeleteButton'),bulkCount=document.getElementById('bulkContactCount'),bulkBar=document.getElementById('bulkContactBar');
  function selectedCount(){return rowChecks.filter(function(cb){return cb.checked;}).length;}
  function updateBulkState(){var n=selectedCount();if(bulkCount)bulkCount.textContent=n;if(bulkDelete)bulkDelete.disabled=n===0;if(clearSelection)clearSelection.disabled=n===0;if(bulkBar)bulkBar.classList.toggle('has-selection',n>0);if(selectAll){selectAll.checked=rowChecks.length>0&&n===rowChecks.length;selectAll.indeterminate=n>0&&n<rowChecks.length;}}
  rowChecks.forEach(function(cb){cb.addEventListener('change',updateBulkState);});
  if(selectAll)selectAll.addEventListener('change',function(){rowChecks.forEach(function(cb){cb.checked=selectAll.checked;});updateBulkState();});
  if(selectVisible)selectVisible.addEventListener('click',function(){rowChecks.forEach(function(cb){cb.checked=true;});updateBulkState();});
  if(clearSelection)clearSelection.addEventListener('click',function(){rowChecks.forEach(function(cb){cb.checked=false;});if(selectAll)selectAll.checked=false;updateBulkState();});
  function updateBulkConfirm(){var n=selectedCount();if(!bulkDelete)return;bulkDelete.dataset.confirmMessage='¿Quieres eliminar '+n+' contacto'+(n===1?'':'s')+' seleccionado'+(n===1?'':'s')+'?';bulkDelete.dataset.confirmNote='Esta acción no se puede deshacer.';}
  var originalUpdateBulkState=updateBulkState;
  updateBulkState=function(){originalUpdateBulkState();updateBulkConfirm();};
  updateBulkState();
  var heads=[].slice.call(table.querySelectorAll('th[data-col]')),key='emailsend-contact-columns-<?=e((string)current_user()['id'])?>-<?=e((string)$tid)?>',saved={};try{saved=JSON.parse(localStorage.getItem(key)||'{}')||{};}catch(e){}
  function apply(col,on){table.querySelectorAll('[data-col="'+col+'"]').forEach(function(el){el.style.display=on?'':'none';});}
  heads.forEach(function(h){var col=h.dataset.col,label=h.dataset.label,on=Object.prototype.hasOwnProperty.call(saved,col)?!!saved[col]:h.dataset.default==='1';var lab=document.createElement('label');lab.innerHTML='<input type="checkbox" '+(on?'checked':'')+'> <span>'+label+'</span>';var cb=lab.querySelector('input');cb.addEventListener('change',function(){saved[col]=cb.checked;localStorage.setItem(key,JSON.stringify(saved));apply(col,cb.checked);});picker.appendChild(lab);apply(col,on);});
 })();
 </script>
 <?php layout_end();exit;
}
if($route==='delivery_lab'){
 require_permission('campaigns','view');$tid=current_tenant_id();layout_start('Pruebas de entrega','delivery_lab');
 $st=db()->prepare('SELECT id,name,subject,status,from_name,from_email,blocks_json,html,preheader FROM campaigns WHERE tenant_id=? ORDER BY id DESC');$st->execute([$tid]);$campaigns=$st->fetchAll();
 if(user_allowed_list_ids($tid)!==null)$campaigns=array_values(array_filter($campaigns,fn($row)=>campaign_lists_are_authorized((int)$row['id'],$tid)));
 $selected=(int)($_GET['campaign_id']??($_SESSION['delivery_lab_last']['campaign_id']??0));$selectedCampaign=null;foreach($campaigns as $cc)if((int)$cc['id']===$selected){$selectedCampaign=$cc;break;}
 $ss=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$ss->execute([$tid]);$cfg=$ss->fetch()?:[];$last=$_SESSION['delivery_lab_last']??null;$labAddresses=$_SESSION['delivery_lab_addresses']??[];
 $currentFrom=$selectedCampaign?trim((string)($selectedCampaign['from_email']?:($cfg['default_from_email']??''))):trim((string)($cfg['default_from_email']??''));$currentDomain=email_domain($currentFrom);$dns=dns_delivery_status($currentDomain,(string)($cfg['dkim_selector']??''));
 $smtpUser=trim((string)($cfg['smtp_user']??''));$smtpAligned=($smtpUser===''||$currentDomain===''||strcasecmp(email_domain($smtpUser),$currentDomain)===0);
 $imageDiag=null;
 if($selectedCampaign){
  try{
   $diagDecoded=json_decode((string)($selectedCampaign['blocks_json']??'[]'),true);
   $diagHtml=(is_array($diagDecoded)&&$diagDecoded)?compile_campaign_html($diagDecoded):(string)($selectedCampaign['html']??'');
   $diagHtml=email_inject_preheader($diagHtml,(string)($selectedCampaign['preheader']??''));
   $diagSmtp=new SimpleSMTP();$imageDiag=$diagSmtp->diagnoseStaticImages($diagHtml,$currentFrom);
  }catch(Throwable $diagError){$imageDiag=['error'=>$diagError->getMessage(),'detected'=>0,'embeddable'=>0,'bytes'=>0,'items'=>[],'html_uses_static'=>false];}
 }
 ?>
 <section class="delivery-lab-hero"><div><span class="eyebrow">DELIVERY LAB</span><h2>Prueba la entrega antes del envío masivo</h2><p>Primero envía un mensaje técnico mínimo para comprobar el canal SMTP. Después prueba la campaña real. Los campos de Gmail, Outlook, Yahoo y corporativo son independientes.</p></div><span class="delivery-lab-hero-icon"><?=ui_icon('test')?></span></section>
 <div class="delivery-lab-warning"><strong>Importante:</strong> “SMTP aceptado” significa que el servidor de salida recibió el mensaje. Si la <b>prueba técnica mínima</b> tampoco llega, el problema no está en el diseño: hay que revisar la entrega del SMTP, DNS, reputación/IP o el servidor receptor.</div>
 <?php if($selectedCampaign && $imageDiag):?>
 <section class="card delivery-image-diagnostic">
  <div class="panel-head"><div><span class="eyebrow">DIAGNÓSTICO DE IMÁGENES</span><h3>Imágenes HTTPS públicas · modo Outlook Safe</h3><p class="muted">EMAILSEND no adjunta imágenes CID. Cada imagen local se normaliza para correo, se publica en <b>/email-assets/</b> y el HTML usa una URL HTTPS directa. JPEG/PNG se preparan para máxima compatibilidad y WEBP se convierte a PNG.</p></div><span class="settings-icon"><?=ui_icon('image')?></span></div>
  <?php if(!empty($imageDiag['error'])):?><div class="delivery-image-alert bad"><strong>No se pudo analizar la campaña:</strong> <?=e((string)$imageDiag['error'])?></div><?php else:?>
  <div class="delivery-image-kpis"><span><b><?=(int)$imageDiag['detected']?></b><small>URLs de imagen</small></span><span class="<?=((int)$imageDiag['embeddable']>0)?'ok':'bad'?>"><b><?=(int)$imageDiag['embeddable']?></b><small>archivos nube listos</small></span><span><b><?=number_format(((int)$imageDiag['bytes'])/1024,1,',','.')?> KB</b><small>peso alojado</small></span><span class="<?=!empty($imageDiag['html_uses_static'])?'ok':'bad'?>"><b><?=!empty($imageDiag['html_uses_static'])?'SÍ':'NO'?></b><small>HTML reescrito a HTTPS</small></span></div>
  <?php if(!empty($imageDiag['items'])):?><div class="table-wrap"><table class="table delivery-image-table"><thead><tr><th>Estado</th><th>Archivo / URL pública</th><th>Tipo</th><th>Peso</th></tr></thead><tbody><?php foreach($imageDiag['items'] as $img):?><tr><td><span class="delivery-state <?=!empty($img['embeddable'])?'accepted':'failed'?>"><?=!empty($img['outlook_safe'])?'OUTLOOK SAFE':(!empty($img['embeddable'])?'NUBE LISTA':'NO RESUELTO')?></span></td><td><strong><?=e((string)($img['name']?:basename((string)$img['path'])))?></strong><small class="table-sub">Origen: <?=e((string)$img['url'])?></small><?php if(!empty($img['static_url'])):?><small class="table-sub"><b><?=e((string)$img['static_url'])?></b> · <a href="<?=e((string)$img['static_url'])?>" target="_blank" rel="noopener">abrir imagen</a></small><?php endif?><?php if(!empty($img['file'])):?><small class="table-sub"><?=e((string)$img['file'])?></small><?php endif?></td><td><?=e((string)($img['mime']?:'—'))?></td><td><?=$img['bytes']?number_format(((int)$img['bytes'])/1024,1,',','.').' KB':'—'?></td></tr><?php endforeach?></tbody></table></div><?php else:?><div class="delivery-image-alert bad">No se detectaron imágenes locales en el HTML compilado de esta campaña.</div><?php endif?>
  <?php endif?>
 </section>
 <?php endif?>
 <div class="delivery-lab-grid">
  <section class="card delivery-lab-send"><div class="panel-head"><div><span class="eyebrow">PRUEBA REAL</span><h3>Enviar a buzones de control</h3><p class="muted">Cada dirección se guarda y procesa por separado. Puedes probar hasta 6 cuentas.</p></div><span class="settings-icon"><?=ui_icon('send')?></span></div>
   <form method="post" action="index.php?route=delivery_lab_send" autocomplete="off"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <label>Campaña<select name="campaign_id" required onchange="location.href='index.php?route=delivery_lab&campaign_id='+this.value"><option value="">Seleccionar campaña…</option><?php foreach($campaigns as $cc):?><option value="<?=$cc['id']?>" <?=((int)$cc['id']===$selected)?'selected':''?>><?=e($cc['name'])?> · <?=e($cc['subject']?:'Sin asunto')?></option><?php endforeach?></select></label>
    <div class="delivery-address-note">Escribe solamente las cuentas que realmente puedas revisar. Un correo escrito en Gmail ya <b>no se copiará</b> a Outlook, Yahoo ni Corporativo.</div>
    <div class="delivery-address-grid">
     <label><span class="provider-label gmail">G</span> Gmail / Google Workspace<input id="labGmail" type="email" name="lab_gmail" value="<?=e((string)($labAddresses['gmail']??''))?>" placeholder="cuenta@gmail.com" autocomplete="section-delivery-gmail email" spellcheck="false"></label>
     <label><span class="provider-label outlook">O</span> Outlook / Hotmail / Microsoft 365<input id="labOutlook" type="email" name="lab_outlook" value="<?=e((string)($labAddresses['outlook']??''))?>" placeholder="cuenta@outlook.com" autocomplete="section-delivery-outlook email" spellcheck="false"></label>
     <label><span class="provider-label yahoo">Y</span> Yahoo<input id="labYahoo" type="email" name="lab_yahoo" value="<?=e((string)($labAddresses['yahoo']??''))?>" placeholder="cuenta@yahoo.com" autocomplete="section-delivery-yahoo email" spellcheck="false"></label>
     <label><span class="provider-label corporate">@</span> Corporativo<input id="labCorporate" type="email" name="lab_corporate" value="<?=e((string)($labAddresses['corporate']??''))?>" placeholder="prueba@empresa.com" autocomplete="section-delivery-corporate email" spellcheck="false"></label>
    </div>
    <details class="delivery-extra" <?=(!empty($labAddresses['extra1'])||!empty($labAddresses['extra2']))?'open':''?>><summary>Agregar dos direcciones adicionales</summary><div class="delivery-address-grid"><label>Prueba adicional 1<input type="email" name="lab_extra1" value="<?=e((string)($labAddresses['extra1']??''))?>" placeholder="email@dominio.com" autocomplete="section-delivery-extra1 email" spellcheck="false"></label><label>Prueba adicional 2<input type="email" name="lab_extra2" value="<?=e((string)($labAddresses['extra2']??''))?>" placeholder="email@dominio.com" autocomplete="section-delivery-extra2 email" spellcheck="false"></label></div></details>
    <div class="delivery-lab-meta"><span><b>Remitente</b><?=e($currentFrom?:'No definido')?></span><span><b>SMTP</b><?=e(($cfg['smtp_host']??'No configurado').(!empty($cfg['smtp_port'])?' : '.$cfg['smtp_port']:''))?></span></div>
    <div class="delivery-test-modes"><div><strong>1. Prueba técnica mínima</strong><small>Sin imágenes ni diseño. Sirve para saber si Gmail/Outlook reciben algo desde este SMTP.</small></div><div><strong>2. Campaña real</strong><small>Envía exactamente el HTML compilado de la campaña para comprobar renderizado y filtros.</small></div><div><strong>3. Matriz Outlook · 1 imagen / 5 métodos</strong><small>Usa la primera imagen real de la campaña y la envía como IMG simple, IMG con width, tabla clásica, VML y background. Un solo correo permite identificar qué renderer funciona.</small></div></div>
    <div class="actions delivery-lab-actions"><button class="btn btn-green" name="test_mode" value="technical" <?=current_user_can('campaigns','send')?'':'disabled'?>><span class="btn-ico"><?=ui_icon('test')?></span>Enviar prueba técnica</button><button class="btn btn-orange" name="test_mode" value="campaign" <?=current_user_can('campaigns','send')?'':'disabled'?>><span class="btn-ico"><?=ui_icon('send')?></span>Enviar campaña real</button><button class="btn btn-blue" name="test_mode" value="outlook_matrix" <?=($selected&&current_user_can('campaigns','send'))?'':'disabled'?>><span class="btn-ico"><?=ui_icon('image')?></span>Diagnóstico Outlook A–E</button><?php if($selected):?><a class="btn btn-light" target="_blank" href="index.php?route=campaign_preview&id=<?=$selected?>"><span class="btn-ico"><?=ui_icon('eye')?></span>Ver preview</a><?php endif?></div>
    <?php if(!current_user_can('campaigns','send')):?><small class="field-help">Tu usuario puede ver campañas, pero no tiene permiso para enviar pruebas.</small><?php endif?>
   </form>
  </section>
  <aside class="card delivery-preflight"><div class="panel-head"><div><span class="eyebrow">PREFLIGHT</span><h3>Antes de enviar</h3><p class="muted">Estas comprobaciones detectan problemas frecuentes, pero no sustituyen el análisis del mensaje recibido.</p></div><span class="settings-icon"><?=ui_icon('shield')?></span></div>
   <div class="delivery-checks">
    <div class="delivery-check <?=!empty($cfg['smtp_host'])?'ok':'warn'?>"><i><?=!empty($cfg['smtp_host'])?'✓':'!'?></i><div><strong>SMTP</strong><small><?=e(!empty($cfg['smtp_host'])?($cfg['smtp_host'].' · '.$cfg['smtp_port']):'No configurado')?></small></div></div>
    <div class="delivery-check <?=$smtpAligned?'ok':'warn'?>"><i><?=$smtpAligned?'✓':'!'?></i><div><strong>Alineación remitente / SMTP</strong><small><?=e(($currentFrom?:'Sin remitente').' ↔ '.($smtpUser?:'Sin usuario SMTP'))?></small></div></div>
    <div class="delivery-check <?=$dns['spf']['ok']?'ok':'warn'?>"><i><?=$dns['spf']['ok']?'✓':'!'?></i><div><strong>SPF publicado</strong><small><?=e($dns['spf']['ok']?'Registro encontrado':'No encontrado')?></small></div></div>
    <div class="delivery-check <?=$dns['dkim']['ok']?'ok':'warn'?>"><i><?=$dns['dkim']['ok']?'✓':'!'?></i><div><strong>DKIM publicado</strong><small><?=e($dns['dkim']['ok']?('Selector '.$dns['dkim']['selector'].' · publicación DNS, no firma verificada'):'No encontrado')?></small></div></div>
    <div class="delivery-check <?=$dns['dmarc']['ok']?'ok':'warn'?>"><i><?=$dns['dmarc']['ok']?'✓':'!'?></i><div><strong>DMARC publicado</strong><small><?=e($dns['dmarc']['ok']?'Registro encontrado':'No encontrado')?></small></div></div>
   </div>
   <a class="btn btn-light full-lab-btn" href="index.php?route=settings"><span class="btn-ico"><?=ui_icon('settings')?></span>Revisar SMTP y DNS</a>
  </aside>
 </div>
 <?php if($last && !empty($last['results'])):$lastMode=(string)($last['mode']??'campaign');$lastModeLabel=$lastMode==='technical'?'PRUEBA TÉCNICA':($lastMode==='outlook_matrix'?'MATRIZ OUTLOOK A–E':'CAMPAÑA REAL');?><section class="card delivery-results"><div class="panel-head"><div><span class="eyebrow">ÚLTIMA EJECUCIÓN · <?=$lastModeLabel?></span><h3><?=e($last['campaign_name']??'Campaña')?> · <?=e(date('d/m/Y H:i',strtotime((string)($last['time']??'now'))))?></h3><p class="muted">Queue ID sirve para rastrear el mensaje en Exim/Ferozo. Si fue aceptado pero no llegó, el problema está después de EMAILSEND.</p></div><span class="delivery-total"><?=count(array_filter($last['results'],fn($r)=>!empty($r['ok'])))?> / <?=count($last['results'])?> aceptados</span></div>
  <?php if(!empty($last['preflight'])):?><div class="delivery-preflight-strip"><?php foreach($last['preflight'] as $pf):?><span class="<?=$pf['ok']?'ok':'warn'?>" title="<?=e((string)$pf['detail'])?>"><?=$pf['ok']?'✓':'!'?> <?=e($pf['label'])?></span><?php endforeach?></div><?php endif?>
  <div class="table-wrap"><table class="table delivery-result-table"><thead><tr><th>Campo</th><th>Proveedor detectado</th><th>Destinatario</th><th>SMTP</th><th>Queue ID</th><th>Resultado real</th><th>Imágenes</th><th>Detalle</th></tr></thead><tbody><?php foreach($last['results'] as $r):$key=hash('sha256',($r['email']??'').'|'.($r['queue_id']??'').'|'.($last['time']??''));?><tr><td><?=e($r['slot']??'Prueba')?></td><td><strong><?=e($r['provider']??'Otro')?></strong></td><td><?=e($r['email']??'')?></td><td><span class="delivery-state <?=$r['ok']?'accepted':'failed'?>"><?=$r['ok']?'ACEPTADO':'ERROR'?></span></td><td><code><?=e($r['queue_id']?:'—')?></code></td><td><select class="delivery-inbox-status" data-delivery-key="<?=$key?>"><option value="pending">Sin verificar</option><option value="inbox">Bandeja</option><option value="spam">Spam / No deseado</option><option value="missing">No llegó</option></select></td><td><?php if(($r['mode']??'campaign')==='campaign'):?><strong><?=(int)($r['static_images']??0)?></strong><small class="table-sub"><?=number_format(((int)($r['static_image_bytes']??0))/1024,1,',','.')?> KB · Nube HTTPS</small><?php elseif(($r['mode']??'')==='outlook_matrix'):?><strong>A–E</strong><small class="table-sub">misma imagen · 5 métodos</small><?php else:?>—<?php endif?></td><td class="delivery-detail"><?php if($r['ok']):?><b>RCPT:</b> <?=e($r['rcpt_response']?:'aceptado')?> · <b>DATA:</b> <?=e($r['smtp_response']?:'aceptado')?><?php else:?><?=e($r['error']?:'Error desconocido')?><?php endif?></td></tr><?php endforeach?></tbody></table></div>
  <div class="delivery-legend"><span><b>ACEPTADO:</b> EMAILSEND entregó el mensaje al SMTP.</span><span><b>PRUEBA TÉCNICA:</b> si no llega, no es un problema del HTML de campaña.</span><span><b>QUEUE ID:</b> úsalo para rastrear la salida en Exim/Ferozo.</span></div>
 </section><?php else:?><section class="card delivery-empty"><span><?=ui_icon('test')?></span><div><strong>Aún no hay una prueba de laboratorio</strong><p>Empieza con “Enviar prueba técnica”. Si llega, continúa con la campaña real.</p></div></section><?php endif?>
 <script>(function(){document.querySelectorAll('.delivery-inbox-status').forEach(function(el){var k='emailsend-delivery-lab-'+el.dataset.deliveryKey;try{var v=localStorage.getItem(k);if(v)el.value=v;}catch(e){}el.addEventListener('change',function(){try{localStorage.setItem(k,el.value);}catch(e){}});});})();</script>
 <?php layout_end();exit;
}
if($route==='campaigns'){
 layout_start('Campañas','campaigns');$tid=current_tenant_id();$st=db()->prepare('SELECT c.*,(SELECT COUNT(*) FROM deliveries d WHERE d.campaign_id=c.id) total,(SELECT COUNT(*) FROM deliveries d WHERE d.campaign_id=c.id AND d.status="queued") queued_count,(SELECT COUNT(*) FROM deliveries d WHERE d.campaign_id=c.id AND d.status IN("sent","delivered")) sent_count,(SELECT COUNT(*) FROM deliveries d WHERE d.campaign_id=c.id AND d.status="failed") failed_count FROM campaigns c WHERE c.tenant_id=? ORDER BY c.id DESC');$st->execute([$tid]);$rows=$st->fetchAll();if(user_allowed_list_ids($tid)!==null)$rows=array_values(array_filter($rows,fn($row)=>campaign_lists_are_authorized((int)$row['id'],$tid)));foreach($rows as &$row){$row['audience_count']=campaign_audience_count((int)$row['id'],$tid,$row['list_id']?:(null));$row['audience_label']=campaign_audience_label((int)$row['id'],$row['list_id']?:(null));}unset($row);
 $ss=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$ss->execute([$tid]);$cfg=$ss->fetch()?:[];$domain=email_domain($cfg['default_from_email']??'');$dns=dns_delivery_status($domain,$cfg['dkim_selector']??'');$smtpReady=!empty($cfg['smtp_host'])&&filter_var($cfg['default_from_email']??'',FILTER_VALIDATE_EMAIL);
 ?><div class="toolbar"><div><p class="muted">Diseña, prueba y envía campañas por cola.</p><div class="campaign-readiness"><span class="<?=$smtpReady?'ready':'warn'?>"><?=ui_icon('send')?> SMTP <?=$smtpReady?'listo':'pendiente'?></span><span class="<?=$dns['spf']['ok']?'ready':'warn'?>">SPF <?=$dns['spf']['ok']?'OK':'revisar'?></span><span class="<?=$dns['dkim']['ok']?'ready':'warn'?>">DKIM <?=$dns['dkim']['ok']?'OK':'revisar'?></span><span class="<?=$dns['dmarc']['ok']?'ready':'warn'?>">DMARC <?=$dns['dmarc']['ok']?'OK':'revisar'?></span></div></div><a class="btn btn-orange" href="index.php?route=campaign_edit"><span class="btn-ico"><?=ui_icon('plus')?></span>Nueva campaña</a></div><div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Campaña</th><th>Asunto</th><th>Audiencia</th><th>Estado</th><th>Progreso</th><th>Acciones</th></tr></thead><tbody><?php foreach($rows as $c):$progress=$c['total']?round(($c['sent_count']+$c['failed_count'])/$c['total']*100):0;?><tr><td><strong><?=e($c['name'])?></strong><small class="table-sub"><?=e($c['audience_label']??'Todos los contactos activos')?></small></td><td><?=e($c['subject'])?></td><td><strong><?=$c['audience_count']?></strong><small class="table-sub">contactos activos</small></td><td><span class="badge <?=e($c['status'])?>"><?=e($c['status'])?></span></td><td><div class="queue-progress"><span><b><?=$c['sent_count']?></b> enviados · <b><?=$c['queued_count']?></b> cola<?php if($c['failed_count']):?> · <b><?=$c['failed_count']?></b> fallidos<?php endif?></span><i><em style="width:<?=$progress?>%"></em></i></div></td><td><div class="actions"><a class="btn btn-light" href="index.php?route=campaign_edit&id=<?=$c['id']?>"><span class="btn-ico"><?=ui_icon('edit')?></span>Editar</a><a class="btn btn-light" target="_blank" href="index.php?route=campaign_preview&id=<?=$c['id']?>"><span class="btn-ico"><?=ui_icon('eye')?></span>Preview</a><a class="btn btn-blue" href="index.php?route=report&id=<?=$c['id']?>"><span class="btn-ico"><?=ui_icon('chart')?></span>Reporte</a><form method="post" action="index.php?route=campaign_duplicate"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn btn-light"><span class="btn-ico"><?=ui_icon('copy')?></span>Duplicar</button></form><?php if(in_array($c['status'],['draft','paused'],true)):?><form method="post" action="index.php?route=campaign_queue"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn btn-orange" data-confirm data-confirm-variant="warning" data-confirm-title="<?=$c['status']==='paused'?'Reanudar campaña':'Enviar campaña'?>" data-confirm-message="Se preparará esta campaña para <?=$c['audience_count']?> contactos activos." data-confirm-note="Revisa asunto, remitente, audiencia y vista previa antes de continuar." data-confirm-label="<?=$c['status']==='paused'?'Sí, reanudar':'Sí, enviar'?>"><span class="btn-ico"><?=ui_icon('send')?></span><?=$c['status']==='paused'?'Reanudar':'Enviar'?></button></form><?php endif?><?php if(in_array($c['status'],['queued','sending'],true)):?><form method="post" action="index.php?route=campaign_pause"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn btn-light"><span class="btn-ico"><?=ui_icon('minus')?></span>Pausar</button></form><?php endif?><?php if($c['failed_count']>0):?><form method="post" action="index.php?route=campaign_retry_failed"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn btn-green"><span class="btn-ico"><?=ui_icon('redo')?></span>Reintentar fallidos</button></form><?php endif?><form method="post" action="index.php?route=campaign_delete"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn btn-danger" title="Eliminar campaña" data-confirm data-confirm-variant="danger" data-confirm-title="Eliminar campaña" data-confirm-message="¿Quieres eliminar esta campaña?" data-confirm-note="La campaña dejará de estar disponible en el administrador y sus portales asociados." data-confirm-label="Sí, eliminar campaña"><span class="btn-ico"><?=ui_icon('trash')?></span>Eliminar</button></form></div></td></tr><?php endforeach?></tbody></table></div></div><?php layout_end();exit;
}
if($route==='campaign_edit'){
 $id=(int)($_GET['id']??0);
 redirect('campaign-builder3.php'.($id?'?id='.$id:''));
}
if($route==='campaign_edit_legacy'){
 $id=(int)($_GET['id']??0);$tid=current_tenant_id();$c=['id'=>0,'name'=>'','subject'=>'','preheader'=>'','from_name'=>'','from_email'=>'','list_id'=>null,'blocks_json'=>'[]','html'=>''];
 if($id){$st=db()->prepare('SELECT * FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);$c=$st->fetch()?:$c;if($c['id'] && !campaign_lists_are_authorized($id,$tid)){flash('error','Esta campaña usa listas que tu usuario no tiene autorizadas.');redirect('index.php?route=campaigns');}}
 $allowed=user_allowed_list_ids($tid);$sql='SELECT l.*,rc.company client_company,(SELECT COUNT(*) FROM contacts ct WHERE ct.list_id=l.id AND ct.status="active") active_count FROM contact_lists l LEFT JOIN report_clients rc ON rc.id=l.client_id WHERE l.tenant_id=?';$params=[$tid];
 if($allowed!==null){if($allowed){$sql.=' AND l.id IN ('.implode(',',array_fill(0,count($allowed),'?')).')';$params=array_merge($params,$allowed);}else{$sql.=' AND 1=0';}}
 $sql.=' ORDER BY COALESCE(rc.company,"ZZZZ"),l.name';$ls=db()->prepare($sql);$ls->execute($params);$lists=$ls->fetchAll();$selectedLists=$id?campaign_selected_list_ids($id,$c['list_id']?:(null)):[];layout_start($id?'Editar campaña':'Nueva campaña','campaigns');?>
 <form method="post" action="index.php?route=campaign_save" id="campaignForm"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$c['id']?>"><input type="hidden" id="blocks_json" name="blocks_json" value="<?=e($c['blocks_json']?:'[]')?>"><textarea id="compiled_html" name="compiled_html" hidden><?=e($c['html'])?></textarea>
 <section class="campaign-setup card campaign-setup-compact"><div class="campaign-setup-head campaign-setup-head-compact"><div class="campaign-setup-title"><span class="eyebrow">CONFIGURACIÓN</span><h3>Datos de la campaña</h3><p class="muted">Asunto, remitente y audiencia.</p></div><div class="campaign-status-pill"><?=ui_icon('mail')?><span><?=e($id?($c['status']??'draft'):'nuevo borrador')?></span></div></div><div class="campaign-compact-grid"><label class="campaign-field campaign-field-name">Nombre interno<input name="name" value="<?=e($c['name'])?>" required></label><label class="campaign-field campaign-field-subject">Asunto<input name="subject" value="<?=e($c['subject'])?>" required></label><label class="campaign-field campaign-field-preheader">Preheader<input name="preheader" value="<?=e($c['preheader'])?>"></label><label class="campaign-field campaign-field-from-name">Nombre remitente<input name="from_name" value="<?=e($c['from_name'])?>"></label><label class="campaign-field campaign-field-from-email">Email remitente<input type="email" name="from_email" value="<?=e($c['from_email'])?>"></label><details class="audience-picker audience-picker-compact campaign-field-audience" id="audiencePicker"><summary><span><b>Audiencia</b><small>Seleccionar listas</small></span><span class="audience-count-live" id="audienceCountLive">—</span></summary><div class="audience-popover-content"><div class="audience-picker-minihead"><div><strong>Listas de contactos</strong><small><?=($allowed===null)?'Si no marcas ninguna, se usarán todos los contactos activos.':'Debes seleccionar al menos una lista autorizada.'?></small></div></div><?php if($lists):?><div class="audience-list-grid"><?php foreach($lists as $l):?><label class="audience-list-option"><input type="checkbox" name="list_ids[]" value="<?=$l['id']?>" data-count="<?=$l['active_count']?>" <?=in_array((int)$l['id'],$selectedLists,true)?'checked':''?>><span><strong><?=e($l['name'])?></strong><small><?=e($l['client_company']??'Sin cliente')?> · <?=$l['active_count']?> activos</small></span></label><?php endforeach?></div><?php else:?><div class="audience-empty">No hay listas disponibles. Se utilizarán los contactos activos permitidos.</div><?php endif?></div></details></div></section>
 <link rel="stylesheet" href="assets/campaign-builder-v2.css?v=<?=@filemtime(__DIR__.'/assets/campaign-builder-v2.css')?>">
 <div class="ob2-toolbar">
   <div class="ob2-brand"><span class="ob2-brand-icon"><?=ui_icon('layers')?></span><div><strong>Campaign Builder 2</strong><small>Editor reconstruido · bloques estables · preview real</small></div></div>
   <div class="ob2-actions">
     <span class="ob2-top-save-state" id="ob2SaveState">Editor listo</span>
     <button type="button" class="btn btn-light icon-btn" id="ob2Undo" title="Deshacer"><?=ui_icon('undo')?></button>
     <button type="button" class="btn btn-light icon-btn" id="ob2Redo" title="Rehacer"><?=ui_icon('redo')?></button>
     <button type="button" class="btn btn-light is-active" id="ob2Desktop"><span class="btn-ico"><?=ui_icon('desktop')?></span>Desktop</button>
     <button type="button" class="btn btn-light" id="ob2Mobile"><span class="btn-ico"><?=ui_icon('mobile')?></span>Móvil</button>
     <button type="button" class="btn btn-code" id="ob2ImportHtml"><span class="btn-ico"><?=ui_icon('code')?></span>HTML</button>
     <button type="button" class="btn btn-blue" id="ob2Preview"><span class="btn-ico"><?=ui_icon('eye')?></span>Previsualizar</button>
     <button class="btn btn-orange"><span class="btn-ico"><?=ui_icon('save')?></span>Guardar</button>
   </div>
 </div>

 <div class="ob2" data-ob2>
   <aside class="ob2-sidebar">
     <div class="ob2-side-head"><div><strong>Elementos</strong><small>Clic o arrastra al lienzo</small></div></div>
     <div class="ob2-group"><div class="ob2-group-title">CONTENIDO</div><div class="ob2-palette">
       <button type="button" data-ob2-add="hero"><span class="ob2-palette-ico">▣</span><span>Hero</span></button>
       <button type="button" data-ob2-add="title"><span class="ob2-palette-ico">H</span><span>Título</span></button>
       <button type="button" data-ob2-add="text"><span class="ob2-palette-ico">T</span><span>Texto</span></button>
       <button type="button" data-ob2-add="image" data-kind="image"><span class="ob2-palette-ico">▧</span><span>Imagen</span></button>
       <button type="button" data-ob2-add="button" data-kind="button"><span class="ob2-palette-ico">▰</span><span>Botón</span></button>
       <button type="button" data-ob2-add="card"><span class="ob2-palette-ico">▤</span><span>Tarjeta</span></button>
       <button type="button" data-ob2-add="quote"><span class="ob2-palette-ico">“</span><span>Destacado</span></button>
       <button type="button" data-ob2-add="header"><span class="ob2-palette-ico">☰</span><span>Header</span></button>
       <button type="button" data-ob2-add="banner"><span class="ob2-palette-ico">▣</span><span>Banner</span></button>
       <button type="button" data-ob2-add="iconbox"><span class="ob2-palette-ico">★</span><span>Icon Box</span></button>
       <button type="button" data-ob2-add="video"><span class="ob2-palette-ico">▶</span><span>Video</span></button>
       <button type="button" data-ob2-add="pricing"><span class="ob2-palette-ico">$</span><span>Pricing</span></button>
       <button type="button" data-ob2-add="qr"><span class="ob2-palette-ico">▦</span><span>QR</span></button>
       <button type="button" data-ob2-add="social"><span class="ob2-palette-ico">●</span><span>Social</span></button>
       <button type="button" data-ob2-add="footer"><span class="ob2-palette-ico">▿</span><span>Footer</span></button>
       <button type="button" data-ob2-add="html"><span class="ob2-palette-ico">&lt;/&gt;</span><span>HTML</span></button>
     </div></div>

     <div class="ob2-group"><div class="ob2-group-title">ESTRUCTURA</div><div class="ob2-palette">
       <button type="button" data-ob2-add="columns1"><span class="ob2-palette-ico">▯</span><span>1 columna</span></button>
       <button type="button" data-ob2-add="columns"><span class="ob2-palette-ico">▯▯</span><span>2 columnas</span></button>
       <button type="button" data-ob2-add="columns3"><span class="ob2-palette-ico">▯▯▯</span><span>3 columnas</span></button>
       <button type="button" data-ob2-add="divider" data-kind="divider"><span class="ob2-palette-ico">—</span><span>Separador</span></button>
       <button type="button" data-ob2-add="spacer" data-kind="spacer"><span class="ob2-palette-ico">↕</span><span>Espacio</span></button>
     </div></div>

     <div class="ob2-group"><div class="ob2-group-title">PLANTILLAS</div><div class="ob2-template-list">
       <button type="button" class="ob2-template" data-ob2-template="newsletter"><i></i><span><strong>Newsletter</strong><small>Contenido editorial</small></span></button>
       <button type="button" class="ob2-template" data-ob2-template="promo"><i></i><span><strong>Promoción</strong><small>Oferta + CTA</small></span></button>
       <button type="button" class="ob2-template" data-ob2-template="corporate"><i></i><span><strong>Corporativa</strong><small>Servicios + contacto</small></span></button>
     </div></div>
   </aside>

   <section class="ob2-center">
     <div class="ob2-canvasbar"><div class="ob2-live"><strong>Vista del email</strong></div><span>680 px · responsive</span></div>
     <div class="ob2-stage"><div class="ob2-device" id="ob2Device"><div class="ob2-page" id="ob2Page"></div></div></div>
   </section>

   <aside class="ob2-inspector" id="ob2Inspector"></aside>
 </div>

 <div class="ob2-code-modal" id="ob2CodeModal">
   <div class="ob2-code-box">
     <div class="ob2-code-head"><strong>Agregar bloque HTML</strong><button type="button" class="preview-close" data-ob2-code-close>×</button></div>
     <div class="ob2-code-body"><textarea id="ob2CodeText" placeholder="<table>...</table>"></textarea></div>
     <div class="ob2-modal-actions"><button type="button" class="btn btn-light" data-ob2-code-close>Cancelar</button><button type="button" class="btn btn-orange" id="ob2ApplyHtml">Agregar HTML</button></div>
   </div>
 </div>

 <div class="ob2-preview-modal" id="ob2PreviewModal">
   <div class="ob2-preview-box">
     <div class="ob2-preview-head"><strong>Vista previa del HTML final</strong><div class="actions"><button type="button" class="btn btn-light" id="ob2PreviewDesktop">Desktop</button><button type="button" class="btn btn-light" id="ob2PreviewMobile">Móvil</button><button type="button" class="preview-close" data-ob2-preview-close>×</button></div></div>
     <div class="ob2-preview-body"><iframe id="ob2PreviewFrame" name="ob2PreviewFrame" title="Vista previa final"></iframe></div>
     <div class="ob2-modal-actions"><button type="button" class="btn btn-orange" data-ob2-preview-close>Volver al editor</button></div>
   </div>
 </div>

 <form method="post" action="builder-preview.php" target="ob2PreviewFrame" id="ob2PreviewForm" hidden>
   <input type="hidden" name="csrf" value="<?=csrf_token()?>">
   <textarea name="blocks_json" id="ob2PreviewJson"></textarea>
 </form>

 <script>
 window.CSRF='<?=csrf_token()?>';
 window.EMAILSEND_APP_URL=<?=json_encode(rtrim(app_url(''),'/'))?>;
 (function(){
   var boxes=[].slice.call(document.querySelectorAll('.audience-list-option input[type=checkbox]')),
       out=document.getElementById('audienceCountLive'),picker=document.getElementById('audiencePicker');
   function upd(){if(!out)return;var n=0,sel=0;boxes.forEach(function(b){if(b.checked){n+=parseInt(b.dataset.count||'0',10);sel++;}});out.textContent=sel?(n+' contactos · '+sel+(sel===1?' lista':' listas')):<?=($allowed===null)?json_encode('Todos los contactos activos'):json_encode('Selecciona una lista autorizada')?>;}
   boxes.forEach(function(b){b.addEventListener('change',upd)});
   document.addEventListener('click',function(e){if(picker&&picker.open&&!picker.contains(e.target))picker.removeAttribute('open');});
   upd();
 })();
 </script>
 <script src="assets/campaign-builder-v2.js?v=<?=@filemtime(__DIR__.'/assets/campaign-builder-v2.js')?>"></script>
 <?php if($id):?><div class="card test-send modern-test-send"><div><span class="eyebrow">PRUEBA ANTES DE ENVIAR</span><h3>Envíate una copia</h3><p class="muted">Comprueba diseño, enlaces y visualización antes de poner la campaña en cola.</p></div><form method="post" action="index.php?route=campaign_test"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$id?>"><label>Email de prueba<input type="email" name="test_email" placeholder="tu@email.com" required></label><button class="btn btn-green"><span class="btn-ico"><?=ui_icon('test')?></span>Enviar prueba</button></form></div><?php endif?>
 <script>window.CSRF='<?=csrf_token()?>';window.EMAILSEND_APP_URL=<?=json_encode(rtrim(app_url(''),'/'))?>;(function(){var boxes=[].slice.call(document.querySelectorAll('.audience-list-option input[type=checkbox]')),out=document.getElementById('audienceCountLive'),picker=document.getElementById('audiencePicker');function upd(){if(!out)return;var n=0,sel=0;boxes.forEach(function(b){if(b.checked){n+=parseInt(b.dataset.count||'0',10);sel++;}});out.textContent=sel?(n+' contactos · '+sel+(sel===1?' lista':' listas')):<?=($allowed===null)?json_encode('Todos los contactos activos'):json_encode('Selecciona una lista autorizada')?>;}boxes.forEach(function(b){b.addEventListener('change',upd)});document.addEventListener('click',function(e){if(picker&&picker.open&&!picker.contains(e.target))picker.removeAttribute('open');});upd();})();</script><script src="assets/editor.js?v=<?=@filemtime(__DIR__.'/assets/editor.js')?>"></script><?php layout_end();exit;
}
if($route==='reports'){
 layout_start('Reportes','reports');$tid=current_tenant_id();$st=db()->prepare('SELECT c.id,c.name,c.subject,c.status,c.sent_at FROM campaigns c WHERE c.tenant_id=? ORDER BY c.id DESC');$st->execute([$tid]);$rows=$st->fetchAll();if(user_allowed_list_ids($tid)!==null)$rows=array_values(array_filter($rows,fn($row)=>campaign_lists_are_authorized((int)$row['id'],$tid)));?><div class="report-cards admin-reports"><?php foreach($rows as $r):$m=campaign_metrics((int)$r['id']);?><a class="report-card" href="index.php?route=report&id=<?=$r['id']?>"><span class="badge <?=e($r['status'])?>"><?=e($r['status'])?></span><h3><?=e($r['name'])?></h3><p><?=e($r['subject'])?></p><div class="mini-metrics"><span><strong><?=$m['open_rate']?>%</strong>Apertura</span><span><strong><?=$m['ctr']?>%</strong>CTR</span><span><strong><?=$m['sent']?></strong>Enviados</span></div></a><?php endforeach?></div><?php layout_end();exit;
}
if($route==='report'){
 $id=(int)($_GET['id']??0);$tid=current_tenant_id();$st=db()->prepare('SELECT * FROM campaigns WHERE id=? AND tenant_id=?');$st->execute([$id,$tid]);$c=$st->fetch();if(!$c)exit('Campaña no encontrada');if(!campaign_lists_are_authorized($id,$tid)){http_response_code(403);exit('No autorizado');}$m=campaign_metrics($id);$links=campaign_top_links($id);$activity=campaign_activity($id);
 $st=db()->prepare('SELECT email,status,opens,clicks,first_open_at,last_click_at FROM deliveries WHERE campaign_id=? ORDER BY COALESCE(last_click_at,first_open_at,sent_at) DESC LIMIT 1000');$st->execute([$id]);$people=$st->fetchAll();
 layout_start('Reporte: '.$c['name'],'reports');?><div class="toolbar"><span class="muted"><?=e($c['subject'])?></span><a class="btn btn-light" target="_blank" href="index.php?route=campaign_preview&id=<?=$id?>">Ver campaña</a></div><div class="grid kpis"><div class="card kpi"><span class="muted">Enviados</span><strong><?=$m['sent']?></strong><small><?=$m['delivered']?> entregados confirmados</small></div><div class="card kpi accent"><span class="muted">Apertura única</span><strong><?=$m['open_rate']?>%</strong><small><?=$m['unique_opens']?> personas · <?=$m['total_opens']?> aperturas</small></div><div class="card kpi"><span class="muted">CTR único</span><strong><?=$m['ctr']?>%</strong><small><?=$m['unique_clicks']?> personas · <?=$m['total_clicks']?> clics</small></div><div class="card kpi"><span class="muted">Rebotes / bajas</span><strong><?=$m['bounced']?> / <?=$m['unsub']?></strong><small><?=$m['failed']?> fallidos</small></div></div><div class="split report-split"><div class="card"><h3>Rendimiento</h3><div class="performance"><div class="donut" style="--p:<?=$m['open_rate']?>"><div><strong><?=$m['open_rate']?>%</strong><span>apertura</span></div></div><div class="donut" style="--p:<?=$m['ctr']?>"><div><strong><?=$m['ctr']?>%</strong><span>CTR</span></div></div></div></div><div class="card"><h3>Actividad de apertura</h3><div class="spark-bars"><?php $max=1;foreach($activity as $a)$max=max($max,(int)$a['value']);foreach($activity as $a):?><div class="spark-col"><i style="height:<?=max(6,round($a['value']/$max*120))?>px"></i><span><?=e($a['label'])?></span></div><?php endforeach?></div></div></div><div class="split report-split"><div class="card"><h3>Enlaces más clicados</h3><?php if($links):?><div class="link-ranking"><?php foreach($links as $l):?><div><div class="link-url"><?=e($l['url'])?></div><strong><?=$l['clicks']?> clics</strong><span><?=$l['people']?> personas</span></div><?php endforeach?></div><?php else:?><p class="muted">Aún no hay clics.</p><?php endif?></div><div class="card"><h3>Lectura de métricas</h3><p><strong>Apertura única:</strong> destinatarios que registraron al menos una apertura.</p><p><strong>Aperturas totales:</strong> todas las cargas del pixel, incluidas repetidas.</p><p><strong>CTR único:</strong> destinatarios que hicieron al menos un clic.</p><p class="muted">Las aperturas pueden verse afectadas por sistemas de privacidad; los clics son una señal más directa.</p></div></div><div class="card"><h3>Detalle por destinatario</h3><div class="table-wrap"><table class="table"><thead><tr><th>Email</th><th>Estado</th><th>Aperturas</th><th>Primera apertura</th><th>Clics</th><th>Último clic</th></tr></thead><tbody><?php foreach($people as $p):?><tr><td><?=e($p['email'])?></td><td><span class="badge <?=e($p['status'])?>"><?=e($p['status'])?></span></td><td><?=$p['opens']?></td><td><?=e($p['first_open_at']??'—')?></td><td><?=$p['clicks']?></td><td><?=e($p['last_click_at']??'—')?></td></tr><?php endforeach?></tbody></table></div></div><?php layout_end();exit;
}
if($route==='help'){
 $canContacts=current_user_can('contacts');$canCampaigns=current_user_can('campaigns');$canReports=current_user_can('reports');$admin=is_admin();
 $canContactCreate=current_user_can('contacts','create');$canContactImport=current_user_can('contacts','import');$canCampaignCreate=current_user_can('campaigns','create');$canCampaignEdit=current_user_can('campaigns','edit');$canCampaignSend=current_user_can('campaigns','send');
 layout_start('Centro de ayuda','help');?>
 <section class="admin-help-hero"><div><span class="eyebrow">CENTRO DE AYUDA · EMAILSEND ONE <span class="help-role-badge"><?=$admin?'ADMINISTRADOR':'USUARIO'?></span></span><h2><?=$admin?'Administra la plataforma con claridad.':'Encuentra solo lo que puedes usar.'?></h2><p><?=$admin?'Busca una guía, revisa el flujo recomendado o resuelve dudas sobre contactos, campañas, entrega, permisos y reportes.':'Este centro se adapta a tus permisos. Aquí encontrarás guías para las áreas a las que tienes acceso, sin opciones administrativas que no necesitas.'?></p><div class="admin-help-search"><span><?=ui_icon('search')?></span><input id="adminHelpSearch" type="search" placeholder="Buscar: contactos, campaña, preheader, reporte, clics..."></div><div class="help-access-strip"><?php if($canContacts):?><span class="help-access-pill"><?=ui_icon('users')?> Contactos</span><?php endif?><?php if($canCampaigns):?><span class="help-access-pill"><?=ui_icon('mail')?> Campañas</span><?php endif?><?php if($canReports):?><span class="help-access-pill"><?=ui_icon('chart')?> Reportes</span><?php endif?><?php if($admin):?><span class="help-access-pill"><?=ui_icon('settings')?> Administración</span><?php endif?></div></div><div class="admin-help-orbit"><?=ui_icon('book')?></div></section>

 <div class="help-user-summary">
  <section class="card help-permission-card"><div class="help-permission-head"><div><span class="eyebrow">TU ACCESO</span><h3><?=$admin?'Acceso administrativo completo':'Herramientas disponibles para tu usuario'?></h3><p class="muted">Las guías y accesos rápidos se muestran de acuerdo con tus permisos actuales.</p></div><span class="badge active"><?=$admin?'ADMIN':'PERSONALIZADO'?></span></div><div class="help-permission-grid">
   <div class="help-permission-item <?=$canContacts?'on':'off'?>"><strong>Contactos</strong><small><?=$canContacts?($canContactImport?'Ver, crear e importar según tus listas autorizadas.':'Consultar y trabajar según tus permisos asignados.'):'No tienes acceso a este módulo.'?></small></div>
   <div class="help-permission-item <?=$canCampaigns?'on':'off'?>"><strong>Campañas</strong><small><?=$canCampaigns?($canCampaignSend?'Diseñar, editar y enviar según permisos.':($canCampaignEdit?'Diseñar y editar; el envío puede requerir otro usuario.':'Consultar campañas autorizadas.')):'No tienes acceso a este módulo.'?></small></div>
   <div class="help-permission-item <?=$canReports?'on':'off'?>"><strong>Reportes</strong><small><?=$canReports?'Consultar aperturas, clics, rebotes y detalle por campaña.':'No tienes acceso a este módulo.'?></small></div>
  </div><?php if(!$admin):?><div class="help-no-admin"><?=ui_icon('shield')?><div><strong>¿No ves Configuración, SMTP o Usuarios?</strong><small>Es normal: esas opciones son administrativas. Si necesitas una función adicional, solicita al administrador que ajuste tus permisos.</small></div></div><?php endif?></section>
  <section class="card help-start-card"><span class="eyebrow">INICIO RÁPIDO</span><h3>Flujo recomendado</h3><p class="muted">Empieza por la primera tarea disponible para tu perfil.</p><div class="help-start-steps"><?php $step=1;if($canContacts):?><a href="index.php?route=contacts"><b><?=$step++?></b><span><strong>Revisar audiencia</strong><small>Contactos, listas y calidad de datos.</small></span><em>→</em></a><?php endif?><?php if($canCampaigns):?><a href="index.php?route=campaigns"><b><?=$step++?></b><span><strong>Preparar campaña</strong><small>Diseño, asunto, preheader y vista previa.</small></span><em>→</em></a><?php endif?><?php if($canReports):?><a href="index.php?route=reports"><b><?=$step++?></b><span><strong>Leer resultados</strong><small>Entrega, aperturas, clics y rebotes.</small></span><em>→</em></a><?php endif?><?php if($admin):?><a href="index.php?route=settings"><b><?=$step++?></b><span><strong>Revisar entrega</strong><small>SMTP, DNS y configuración del sistema.</small></span><em>→</em></a><?php endif?></div></section>
 </div>

 <div class="admin-help-quick">
  <?php if($canContacts):?><a class="card" href="index.php?route=contacts"><span><?=ui_icon('users')?></span><div><strong>Contactos y listas</strong><small><?=$canContactImport?'Importar, ordenar y segmentar.':'Consultar tus listas autorizadas.'?></small></div><b>→</b></a><?php endif?>
  <?php if($canCampaigns):?><a class="card" href="index.php?route=campaigns"><span><?=ui_icon('edit')?></span><div><strong>Campañas</strong><small><?=$canCampaignEdit?'Diseñar, editar y previsualizar.':'Consultar campañas disponibles.'?></small></div><b>→</b></a><?php endif?>
  <?php if($canReports):?><a class="card" href="index.php?route=reports"><span><?=ui_icon('chart')?></span><div><strong>Reportes</strong><small>Interpretar resultados y actividad.</small></div><b>→</b></a><?php endif?>
  <?php if($admin):?><a class="card" href="index.php?route=settings"><span><?=ui_icon('shield')?></span><div><strong>Entrega y seguridad</strong><small>SMTP, SPF, DKIM y DMARC.</small></div><b>→</b></a><?php endif?>
 </div>

 <div class="admin-help-grid" id="adminHelpGrid">
  <?php if($canContacts):?><section class="card admin-help-topic is-user-guide" data-help="contactos listas csv excel xlsx importar audiencia permisos duplicados segmentar buscar"><span class="help-topic-icon"><?=ui_icon('users')?></span><div><span class="eyebrow">CONTACTOS Y LISTAS</span><h3>Trabaja solo con la audiencia que te corresponde</h3><p>Las listas organizan los contactos y también pueden limitar qué base puede utilizar cada usuario. Si tu acceso es restringido, únicamente verás las listas autorizadas.</p><ul><li><?=$canContactImport?'Puedes importar CSV o Excel .xlsx y revisar el mapeo antes de guardar.':'La importación no está habilitada para tu usuario.'?></li><li>Busca por nombre, empresa o email y entra a la lista correspondiente.</li><li>Las bajas y rebotados no deben volver a tratarse como contactos activos.</li></ul><a href="index.php?route=contacts">Abrir Contactos →</a></div></section><?php endif?>

  <?php if($canCampaigns):?><section class="card admin-help-topic is-user-guide" data-help="campaña editor plantilla asunto preheader remitente prueba preview vista previa guardar enviar cola"><span class="help-topic-icon"><?=ui_icon('mail')?></span><div><span class="eyebrow">CAMPAÑAS</span><h3>De borrador a campaña lista para enviar</h3><p>Completa primero nombre interno, asunto, preheader y remitente. Después diseña el contenido y utiliza Previsualizar antes de guardar o enviar.</p><ul><li><?=$canCampaignEdit?'Puedes modificar el diseño y contenido de las campañas autorizadas.':'Tu permiso actual es principalmente de consulta.'?></li><li>Desktop y Móvil permiten revisar la pieza antes del envío.</li><li><?=$canCampaignSend?'Tienes permiso para poner campañas en cola.':'Si no aparece Enviar, un usuario con permiso de envío debe completar ese paso.'?></li></ul><a href="index.php?route=campaigns">Abrir Campañas →</a></div></section><?php endif?>

  <?php if($canCampaigns&&$canCampaignEdit):?><section class="card admin-help-topic is-user-guide" data-help="builder editor visual bloque columna texto imagen copiar pegar control z tipografia fondo"><span class="help-topic-icon"><?=ui_icon('layers')?></span><div><span class="eyebrow">EDITOR VISUAL</span><h3>Edita el email sin tocar código</h3><p>Selecciona un bloque o elemento para ver sus propiedades. Puedes mover, duplicar, copiar, pegar y ajustar estructuras directamente en el lienzo.</p><ul><li>Ctrl+C / Ctrl+V copia y pega bloques cuando no estás escribiendo texto.</li><li>Dentro de un texto, esos mismos atajos funcionan como edición normal.</li><li>Ctrl+Z deshace cambios del builder.</li></ul><a href="index.php?route=campaign_edit">Crear campaña →</a></div></section><?php endif?>

  <?php if($canReports):?><section class="card admin-help-topic is-user-guide" data-help="reportes apertura clic ctr rebote baja entregado tracking resultado personas"><span class="help-topic-icon"><?=ui_icon('chart')?></span><div><span class="eyebrow">REPORTES</span><h3>Lee las métricas sin confundir señales</h3><p>La apertura única cuenta personas distintas; las aperturas totales registran todos los eventos. El CTR único indica cuántas personas hicieron al menos un clic.</p><ul><li>Los clics suelen ser una señal de intención más directa que la apertura.</li><li>Rebotes altos pueden indicar problemas de calidad de base.</li><li>Las bajas deben quedar excluidas de futuros envíos.</li></ul><a href="index.php?route=reports">Abrir Reportes →</a></div></section><?php endif?>

  <?php if($admin):?><section class="card admin-help-topic" data-help="smtp ssl tls 465 587 contraseña servidor conexión correo entrega"><span class="help-topic-icon"><?=ui_icon('test')?></span><div><span class="eyebrow">SMTP SEGURO</span><h3>Conecta y prueba el proveedor de entrega</h3><p>EMAILSEND procesa la cola, pero la entrega final depende del SMTP configurado. Recomendamos STARTTLS 587 o TLS 465 con autenticación y certificado válido.</p><ul><li>Prueba la conexión antes de poner campañas en cola.</li><li>No uses una casilla común para grandes volúmenes.</li><li>Ajusta la velocidad de cola al proveedor.</li></ul><a href="index.php?route=settings">Configurar SMTP →</a></div></section>
  <section class="card admin-help-topic" data-help="spf dkim dmarc dns dominio reputación entregabilidad spam"><span class="help-topic-icon"><?=ui_icon('dns')?></span><div><span class="eyebrow">DNS Y ENTREGABILIDAD</span><h3>Protege la reputación del dominio remitente</h3><p>SPF autoriza al proveedor, DKIM firma los mensajes y DMARC define cómo tratar fallos de autenticación.</p><ul><li>Usa un único SPF válido por dominio.</li><li>El selector DKIM lo entrega el proveedor.</li><li>Empieza DMARC con una política prudente.</li></ul><a href="index.php?route=settings">Revisar DNS →</a></div></section>
  <section class="card admin-help-topic" data-help="usuarios permisos roles acceso listas administrador"><span class="help-topic-icon"><?=ui_icon('profile')?></span><div><span class="eyebrow">USUARIOS Y PERMISOS</span><h3>Da a cada persona solo lo que necesita</h3><p>Los usuarios pueden recibir permisos distintos por módulo y también quedar limitados a determinadas listas de contactos.</p><ul><li>Separa Ver, Crear, Editar, Eliminar, Importar y Enviar.</li><li>Un usuario restringido no verá módulos administrativos.</li><li>La ayuda se adapta automáticamente a esos permisos.</li></ul><a href="index.php?route=administrators">Gestionar usuarios →</a></div></section>
  <section class="card admin-help-topic" data-help="cliente portal acceso campaña reporte permiso"><span class="help-topic-icon"><?=ui_icon('client')?></span><div><span class="eyebrow">PORTAL DEL CLIENTE</span><h3>Comparte resultados sin abrir el administrador</h3><p>Cada cliente puede tener un acceso privado a las campañas y reportes que el administrador autorice.</p><ul><li>No accede a contactos, SMTP ni configuración.</li><li>El administrador controla qué campañas puede consultar.</li><li>Cuenta con su propio Centro de Ayuda.</li></ul><a href="index.php?route=clients">Gestionar clientes →</a></div></section><?php endif?>

  <section class="card admin-help-topic" data-help="problema error no encuentro permiso no puedo solución ayuda"><span class="help-topic-icon"><?=ui_icon('help')?></span><div><span class="eyebrow">SOLUCIÓN RÁPIDA</span><h3><?=$admin?'Si algo no funciona, revisa primero el flujo':'Si no encuentras una opción'?></h3><p><?=$admin?'Comprueba permisos, audiencia, remitente, estado de campaña y configuración de entrega antes de repetir una acción.':'La plataforma oculta las acciones que tu usuario no tiene autorizadas. Eso puede incluir importar, editar, enviar o administrar configuración.'?></p><ul><?php if(!$admin):?><li>Revisa en esta página qué módulos y acciones tienes disponibles.</li><li>Si necesitas un permiso adicional, consulta al administrador de tu empresa.</li><li>El buscador superior también encuentra campañas, contactos y reportes autorizados.</li><?php else:?><li>Usa envío de prueba para aislar problemas de SMTP.</li><li>Revisa la audiencia y el estado de la campaña.</li><li>Comprueba el worker/CRON si una cola no avanza.</li><?php endif?></ul></div></section>
 </div>

 <section class="card help-faq"><span class="eyebrow">PREGUNTAS FRECUENTES</span><h3>Dudas habituales</h3>
  <?php if($canCampaigns):?><details><summary>¿Por qué no veo el botón Enviar?</summary><div>El permiso para ver o editar campañas es independiente del permiso para enviarlas. Si tu usuario no tiene la acción “Enviar”, la campaña puede prepararse pero deberá ser puesta en cola por un usuario autorizado.</div></details><?php endif?>
  <?php if($canContacts):?><details><summary>¿Por qué veo unas listas y otras no?</summary><div>Los administradores pueden asignar listas específicas a cada usuario. El sistema utiliza esas mismas restricciones en Contactos, audiencias de campaña y en el buscador global.</div></details><?php endif?>
  <?php if($canReports):?><details><summary>¿Apertura y clic significan lo mismo?</summary><div>No. La apertura indica que se registró la carga del mensaje, mientras que el clic registra una interacción con un enlace. Por eso el clic suele ser una señal más directa de interés.</div></details><?php endif?>
  <details><summary>¿Cómo encuentro rápido una campaña o contacto?</summary><div>Usa el buscador del menú superior o presiona Ctrl+K. Solo aparecerán resultados a los que tu usuario tenga acceso.</div></details>
 </section>
 <div id="adminHelpEmpty" class="card admin-help-empty" hidden><strong>No encontramos una guía con ese término.</strong><p>Prueba con “contactos”, “campaña”, “preheader”, “reporte”, “clics” o “permisos”.</p></div>
 <script>(function(){var input=document.getElementById('adminHelpSearch'),topics=[].slice.call(document.querySelectorAll('.admin-help-topic')),empty=document.getElementById('adminHelpEmpty');if(!input)return;function run(){var q=input.value.trim().toLowerCase(),visible=0;topics.forEach(function(el){var hay=(el.getAttribute('data-help')+' '+el.textContent).toLowerCase(),show=!q||hay.indexOf(q)!==-1;el.hidden=!show;if(show)visible++;});empty.hidden=visible>0;}input.addEventListener('input',run);})();</script>
 <?php layout_end();exit;
}
if($route==='profile'){
 $u=current_user();layout_start('Preferencias del usuario','settings');$tz=$u['timezone']?:'America/Sao_Paulo';?>
 <div class="profile-layout"><section class="card profile-card"><div class="profile-hero"><div class="profile-avatar-large"><?php if(!empty($u['avatar_url'])):?><img src="<?=e($u['avatar_url'])?>" alt="Foto de perfil"><?php else:?><span><?=e(strtoupper(substr($u['name']??'U',0,1)))?></span><?php endif?></div><div><span class="eyebrow">MI CUENTA</span><h2><?=e($u['name'])?></h2><p><?=e($u['job_title']?:'Usuario EMAILSEND')?> · <?=e($u['tenant_name']??'')?></p></div></div><form method="post" enctype="multipart/form-data" action="index.php?route=profile_save"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><div class="form-grid"><label>Nombre completo<input name="name" value="<?=e($u['name'])?>" required></label><label>Email<input type="email" name="email" value="<?=e($u['email'])?>" required></label><label>Cargo<input name="job_title" value="<?=e($u['job_title']??'')?>" placeholder="Ej.: Administrador"></label><label>Empresa<input name="company" value="<?=e($u['company']??current_tenant_name())?>"></label><label>Teléfono<input name="phone" value="<?=e($u['phone']??'')?>"></label><label>Zona horaria<select name="timezone"><option value="America/Sao_Paulo" <?=$tz==='America/Sao_Paulo'?'selected':''?>>Brasil · São Paulo</option><option value="America/Santiago" <?=$tz==='America/Santiago'?'selected':''?>>Chile · Santiago</option><option value="America/New_York" <?=$tz==='America/New_York'?'selected':''?>>EE.UU. · Eastern</option><option value="UTC" <?=$tz==='UTC'?'selected':''?>>UTC</option></select></label><label class="full">Foto de perfil<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"><small class="field-help">JPG, PNG o WEBP · máximo 3 MB.</small></label><div class="full"><button class="btn btn-orange"><span class="btn-ico"><?=ui_icon('save')?></span>Guardar preferencias</button></div></div></form></section><aside class="card profile-help"><span class="eyebrow">PREFERENCIAS</span><h3>Tu identidad dentro del sistema</h3><div class="profile-feature"><span><?=ui_icon('profile')?></span><div><strong>Foto y nombre</strong><p>Se muestran en el header y ayudan a identificar quién está operando EMAILSEND.</p></div></div><div class="profile-feature"><span><?=ui_icon('client')?></span><div><strong>Datos profesionales</strong><p>Cargo, empresa, teléfono y zona horaria quedan asociados a tu usuario.</p></div></div><div class="profile-feature"><span><?=ui_icon('shield')?></span><div><strong>Seguridad</strong><p>La configuración SMTP y DNS se administra por separado desde Configuración.</p></div></div><a class="btn btn-blue" href="index.php?route=settings"><span class="btn-ico"><?=ui_icon('settings')?></span>Ir a Configuración</a></aside></div>
 <?php layout_end();exit;
}
if($route==='clients'){
 require_permission('clients','view');$tid=current_tenant_id();$edit=(int)($_GET['edit']??0);$st=db()->prepare('SELECT rc.*,ru.name user_name,ru.email user_email,ru.last_login_at,(SELECT COUNT(*) FROM campaign_report_access a WHERE a.client_id=rc.id) campaign_count FROM report_clients rc LEFT JOIN report_users ru ON ru.client_id=rc.id WHERE rc.tenant_id=? ORDER BY rc.id DESC');$st->execute([$tid]);$rows=$st->fetchAll();
 layout_start('Clientes / Portal','clients');?>
 <div class="split clients-top"><div class="card"><span class="eyebrow">NUEVO CLIENTE</span><h3>Crear acceso privado</h3><p class="muted">Un mismo usuario accede a dos espacios: Portal de Campañas y Portal de Reportes. Solo verá lo que tú autorices.</p><form method="post" action="index.php?route=report_client_save"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><div class="form-grid"><label class="full">Empresa<input name="company" required></label><label>Nombre del contacto<input name="name" required></label><label>Email de acceso<input type="email" name="email" required></label><label>Teléfono<input name="phone"></label><label>Logo URL (opcional)<input name="logo_url" placeholder="https://..."></label><label class="full">Contraseña inicial<input type="password" name="password" minlength="8" required></label><div class="full"><button class="btn btn-orange"><span class="btn-ico"><?=ui_icon('plus')?></span>Crear cliente</button></div></div></form></div><div class="card portal-explain"><span class="eyebrow">PORTAL PRIVADO DEL CLIENTE</span><h3>Inicio + Campañas + Reportes + Ayuda</h3><div class="portal-dual-links"><a target="_blank" href="index.php?portal=inicio"><strong>Inicio del Cliente</strong><small><?=e(app_url('index.php?portal=inicio'))?></small><span>Dashboard general →</span></a><a target="_blank" href="index.php?portal=campanas"><strong>Portal de Campañas</strong><small><?=e(app_url('index.php?portal=campanas'))?></small><span>Vista y estado de las piezas →</span></a><a target="_blank" href="index.php?portal=reportes"><strong>Portal de Reportes</strong><small><?=e(app_url('index.php?portal=reportes'))?></small><span>Métricas y rendimiento →</span></a><a target="_blank" href="index.php?portal=ayuda"><strong>Centro de Ayuda</strong><small><?=e(app_url('index.php?portal=ayuda'))?></small><span>Guías para el cliente →</span></a></div><p>El cliente no puede editar campañas, importar contactos ni ver datos de otros clientes.</p></div></div>
 <div class="card client-registry-card" style="margin-top:16px">
 <form method="post" id="clientBulkForm">
 <input type="hidden" name="csrf" value="<?=csrf_token()?>">
 <div class="toolbar client-registry-toolbar">
   <div><h3 style="margin:0">Clientes registrados</h3><span class="muted">Acceso con el mismo usuario a Campañas y Reportes</span></div>
   <div class="client-bulk-actions">
     <span class="client-selected-count" id="clientSelectedCount">0 seleccionados</span>
     <button type="submit" class="btn btn-danger" formaction="index.php?route=report_clients_bulk_delete" id="clientBulkDelete" disabled data-confirm data-confirm-variant="danger" data-confirm-title="Eliminar clientes / portales" data-confirm-message="¿Quieres eliminar los clientes/portales seleccionados?" data-confirm-note="Las listas y contactos NO se borrarán; quedarán sin cliente asignado." data-confirm-label="Sí, eliminar seleccionados"><span class="btn-ico"><?=ui_icon('trash')?></span>Eliminar seleccionados</button>
   </div>
 </div>
 <div class="table-wrap"><table class="table client-registry-table"><thead><tr>
 <th class="client-check-col"><input type="checkbox" id="clientSelectAll" aria-label="Seleccionar todos los clientes visibles"></th>
 <th>Empresa</th><th>Usuario</th><th>Email</th><th>Campañas visibles</th><th>Último acceso</th><th>Portales</th><th>Acción</th>
 </tr></thead><tbody>
 <?php foreach($rows as $r):?><tr>
 <td class="client-check-col"><input type="checkbox" class="client-select" name="client_ids[]" value="<?=$r['id']?>" aria-label="Seleccionar <?=e($r['company'])?>"></td>
 <td><strong><?=e($r['company'])?></strong></td>
 <td><?=e($r['user_name']??'—')?></td>
 <td><?=e($r['user_email']??'—')?></td>
 <td><?=$r['campaign_count']?></td>
 <td><?=e($r['last_login_at']??'Nunca')?></td>
 <td><div class="portal-row-actions"><a class="portal-mini campaigns" target="_blank" href="index.php?portal=inicio&amp;admin_client=<?=$r['id']?>">Inicio ↗</a><a class="portal-mini campaigns" target="_blank" href="index.php?portal=campanas&amp;admin_client=<?=$r['id']?>">Campañas ↗</a><a class="portal-mini reports" target="_blank" href="index.php?portal=reportes&amp;admin_client=<?=$r['id']?>">Reportes ↗</a><a class="portal-mini reports" target="_blank" href="index.php?portal=ayuda&amp;admin_client=<?=$r['id']?>">Ayuda ↗</a></div></td>
 <td><div class="client-row-actions">
   <a class="btn btn-blue" href="index.php?route=clients&edit=<?=$r['id']?>"><span class="btn-ico"><?=ui_icon('settings')?></span>Gestionar</a>
   <button type="submit" class="btn btn-danger client-delete-one" name="client_id" value="<?=$r['id']?>" formaction="index.php?route=report_client_delete" data-confirm data-confirm-variant="danger" data-confirm-title="Eliminar cliente / portal" data-confirm-message="<?=e('¿Eliminar el portal de '.$r['company'].' / '.($r['user_email']??'').'?')?>" data-confirm-note="Las listas y contactos se conservarán y podrán reasignarse." data-confirm-label="Sí, eliminar portal"><span class="btn-ico"><?=ui_icon('trash')?></span>Eliminar</button>
 </div></td>
 </tr><?php endforeach?>
 </tbody></table></div>
 </form>
 </div>
 <script>
 (function(){
   var form=document.getElementById('clientBulkForm');
   if(!form)return;
   var all=document.getElementById('clientSelectAll');
   var boxes=[].slice.call(form.querySelectorAll('.client-select'));
   var count=document.getElementById('clientSelectedCount');
   var del=document.getElementById('clientBulkDelete');
   function sync(){
     var n=boxes.filter(function(b){return b.checked;}).length;
     count.textContent=n+' seleccionado'+(n===1?'':'s');
     del.disabled=n===0;
     del.dataset.confirmMessage='¿Quieres eliminar '+n+' cliente'+(n===1?'':'s')+'/portal'+(n===1?'':'es')+' seleccionado'+(n===1?'':'s')+'?';
     if(all){all.checked=n>0&&n===boxes.length;all.indeterminate=n>0&&n<boxes.length;}
   }
   if(all)all.addEventListener('change',function(){boxes.forEach(function(b){b.checked=all.checked;});sync();});
   boxes.forEach(function(b){b.addEventListener('change',sync);});
   sync();
 })();
 </script>
 <?php if($edit):$st=db()->prepare('SELECT rc.*,ru.name user_name,ru.email user_email FROM report_clients rc LEFT JOIN report_users ru ON ru.client_id=rc.id WHERE rc.id=? AND rc.tenant_id=?');$st->execute([$edit,$tid]);$client=$st->fetch();if($client):$st=db()->prepare('SELECT id,name,subject,status FROM campaigns WHERE tenant_id=? ORDER BY id DESC');$st->execute([$tid]);$campaigns=$st->fetchAll();$a=db()->prepare('SELECT campaign_id FROM campaign_report_access WHERE client_id=?');$a->execute([$edit]);$granted=array_map('intval',$a->fetchAll(PDO::FETCH_COLUMN));?>
 <div class="client-manage"><div class="card"><span class="eyebrow">PERMISOS</span><h3><?=e($client['company'])?></h3><p class="muted">Marca únicamente las campañas que este cliente podrá consultar.</p><form method="post" action="index.php?route=report_access_save"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="client_id" value="<?=$edit?>"><div class="campaign-permissions"><?php foreach($campaigns as $c):?><label><input type="checkbox" name="campaigns[]" value="<?=$c['id']?>" <?=in_array((int)$c['id'],$granted,true)?'checked':''?>><span><strong><?=e($c['name'])?></strong><small><?=e($c['subject'])?> · <?=e($c['status'])?></small></span></label><?php endforeach?></div><button class="btn btn-orange" style="margin-top:14px"><span class="btn-ico"><?=ui_icon('save')?></span>Guardar campañas visibles</button></form></div><?php $allListsQ=db()->prepare('SELECT id,name,client_id,(SELECT COUNT(*) FROM contacts c WHERE c.list_id=contact_lists.id) contact_count FROM contact_lists WHERE tenant_id=? ORDER BY name');$allListsQ->execute([$tid]);$allLists=$allListsQ->fetchAll();?><div class="card"><span class="eyebrow">CONTACTOS DEL CLIENTE</span><h3>Listas asignadas</h3><p class="muted">Estas listas permiten separar la base de contactos de <?=e($client['company'])?> del resto de clientes.</p><form method="post" action="index.php?route=client_lists_save"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="client_id" value="<?=$edit?>"><div class="campaign-permissions list-permissions"><?php foreach($allLists as $l):?><label><input type="checkbox" name="lists[]" value="<?=$l['id']?>" <?=(int)$l['client_id']===$edit?'checked':''?> <?=($l['client_id'] && (int)$l['client_id']!==$edit)?'disabled':''?>><span><strong><?=e($l['name'])?></strong><small><?=$l['contact_count']?> contactos<?=($l['client_id'] && (int)$l['client_id']!==$edit)?' · asignada a otro cliente':''?></small></span></label><?php endforeach?></div><button class="btn btn-green" style="margin-top:14px"><span class="btn-ico"><?=ui_icon('users')?></span>Guardar listas del cliente</button></form></div><div class="card"><span class="eyebrow">SEGURIDAD</span><h3>Acceso del cliente</h3><p><strong><?=e($client['user_email'])?></strong></p><form method="post" action="index.php?route=report_client_password"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="client_id" value="<?=$edit?>"><label>Nueva contraseña<input type="password" name="password" minlength="8" required></label><button class="btn btn-blue" style="margin-top:12px"><span class="btn-ico"><?=ui_icon('shield')?></span>Cambiar contraseña</button></form><hr class="soft"><div class="client-portal-access"><span class="eyebrow">VISTA DEL CLIENTE</span><a class="btn portal-access campaign" target="_blank" href="index.php?portal=inicio&amp;admin_client=<?=$edit?>"><span class="btn-ico"><?=ui_icon('home')?></span>Abrir Inicio del Cliente</a><a class="btn portal-access campaign" target="_blank" href="index.php?portal=campanas&amp;admin_client=<?=$edit?>"><span class="btn-ico"><?=ui_icon('mail')?></span>Entrar al Portal de Campañas</a><a class="btn portal-access reports" target="_blank" href="index.php?portal=reportes&amp;admin_client=<?=$edit?>"><span class="btn-ico"><?=ui_icon('chart')?></span>Entrar al Portal de Reportes</a><a class="btn portal-access reports" target="_blank" href="index.php?portal=ayuda&amp;admin_client=<?=$edit?>"><span class="btn-ico"><?=ui_icon('help')?></span>Abrir Centro de Ayuda</a><small>El administrador entra directamente sin conocer la contraseña del cliente.</small></div><div class="client-danger-zone">
 <span class="eyebrow">ZONA PELIGROSA</span>
 <h3>Eliminar cliente / portal</h3>
 <p>Elimina el acceso de <strong><?=e($client['user_email'])?></strong> y sus permisos de portal. <b>No elimina campañas, contactos ni listas.</b> Las listas quedarán disponibles como “Sin cliente”.</p>
 <form method="post" action="index.php?route=report_client_delete">
   <input type="hidden" name="csrf" value="<?=csrf_token()?>">
   <input type="hidden" name="client_id" value="<?=$edit?>">
   <button class="btn btn-danger" data-confirm data-confirm-variant="danger" data-confirm-title="Eliminar cliente / portal" data-confirm-message="¿Quieres eliminar definitivamente este acceso de cliente?" data-confirm-note="Sus listas y contactos se conservarán y quedarán disponibles para reasignación." data-confirm-label="Sí, eliminar portal"><span class="btn-ico"><?=ui_icon('trash')?></span>Eliminar cliente / portal</button>
 </form>
 </div></div></div>
 <?php endif;endif;layout_end();exit;
}
if($route==='administrators'){
 require_admin();layout_start('Usuarios y permisos','administrators');
 $tenants=db()->query('SELECT id,name FROM tenants ORDER BY name')->fetchAll();
 $rows=db()->query('SELECT u.*,t.name tenant_name,(SELECT COUNT(*) FROM user_contact_lists ucl WHERE ucl.user_id=u.id) assigned_count FROM users u JOIN tenants t ON t.id=u.tenant_id WHERE u.role IN("admin","user") ORDER BY u.active DESC,u.name')->fetchAll();
 $allLists=db()->query('SELECT l.id,l.tenant_id,l.name,t.name tenant_name,(SELECT COUNT(*) FROM contacts c WHERE c.list_id=l.id) contact_count FROM contact_lists l JOIN tenants t ON t.id=l.tenant_id ORDER BY t.name,l.name')->fetchAll();
 $edit=(int)($_GET['edit']??0);$editing=null;$assignedIds=[];$editingPerms=[];if($edit){$q=db()->prepare('SELECT * FROM users WHERE id=? AND role IN("admin","user")');$q->execute([$edit]);$editing=$q->fetch()?:null;if($editing){$assignedIds=user_assigned_list_ids($edit);$editingPerms=user_permissions($edit);}}
 ?>
 <section class="settings-hero admin-users-hero"><div><span class="eyebrow">USUARIOS DEL SISTEMA</span><h2>Accesos y listas autorizadas</h2><p>Crea usuarios internos para la empresa y define exactamente qué listas de contactos puede usar cada persona al preparar sus campañas.</p></div><div class="settings-hero-icon"><?=ui_icon('shield')?></div></section>
 <div class="split admin-users-layout"><section class="card"><div class="panel-head"><div><span class="eyebrow"><?=$editing?'EDITAR USUARIO':'NUEVO USUARIO'?></span><h3><?=$editing?'Actualizar acceso':'Crear acceso interno'?></h3><p class="muted">Un Administrador gestiona toda la instalación. Un Usuario trabaja con campañas, contactos y reportes usando únicamente las listas autorizadas.</p></div><span class="settings-icon"><?=ui_icon('profile')?></span></div>
 <form method="post" action="index.php?route=<?=$editing?'admin_update':'admin_create'?>" id="systemUserForm"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><?php if($editing):?><input type="hidden" name="id" value="<?=$editing['id']?>"><?php endif?>
 <div class="form-grid"><label>Nombre<input name="name" value="<?=e($editing['name']??'')?>" required></label><label>Email de acceso<input type="email" name="email" value="<?=e($editing['email']??'')?>" required></label>
 <label>Empresa base<select name="tenant_id" id="userTenant" required><?php foreach($tenants as $t):?><option value="<?=$t['id']?>" <?=((int)($editing['tenant_id']??current_tenant_id())===(int)$t['id'])?'selected':''?>><?=e($t['name'])?></option><?php endforeach?></select></label>
 <label>Rol<select name="role"><option value="user" <?=($editing['role']??'user')==='user'?'selected':''?>>Usuario · campañas y contactos</option><option value="admin" <?=($editing['role']??'')==='admin'?'selected':''?>>Administrador · control total</option></select></label>
 <label>Acceso a listas<select name="list_access_mode" id="listAccessMode"><option value="assigned" <?=($editing['list_access_mode']??'assigned')==='assigned'?'selected':''?>>Solo listas asignadas</option><option value="all" <?=($editing['list_access_mode']??'assigned')==='all'?'selected':''?>>Todas las listas de la empresa</option></select><small class="field-help">Para equipos comerciales o áreas separadas recomendamos “Solo listas asignadas”.</small></label>
 <?php if(!$editing):?><label>Contraseña inicial<input type="password" name="password" minlength="8" required><small class="field-help">Mínimo 8 caracteres.</small></label><?php else:?><label class="admin-active-toggle"><span>Estado</span><span class="checkbox-row"><input type="checkbox" name="active" value="1" <?=$editing['active']?'checked':''?> <?=((int)$editing['id']===(int)current_user()['id'])?'onclick="return false"':''?>> Usuario activo</span><small class="field-help"><?=((int)$editing['id']===(int)current_user()['id'])?'Tu propia cuenta no puede desactivarse desde aquí.':'Puedes suspender temporalmente el acceso.'?></small></label><?php endif?>
 <div class="full user-list-access-panel"><span class="field-label">Listas asignadas</span><p class="muted">Estas son las listas que aparecerán disponibles al usuario cuando cree una campaña, agregue contactos o importe una base.</p><div class="permission-check-grid" id="userListGrid"><?php foreach($allLists as $l):?><label data-tenant="<?=$l['tenant_id']?>"><input type="checkbox" name="list_ids[]" value="<?=$l['id']?>" <?=in_array((int)$l['id'],$assignedIds,true)?'checked':''?>><span><strong><?=e($l['name'])?></strong><small><?=e($l['tenant_name'])?> · <?=$l['contact_count']?> contactos</small></span></label><?php endforeach?></div></div>
 <div class="full user-module-permissions"><span class="field-label">Acceso a paneles y acciones</span><p class="muted">Define exactamente qué puede ver y hacer este usuario. Los administradores siempre conservan control total.</p><div class="permission-matrix"><?php foreach(permission_modules() as $pmodule=>$pmeta):?><div class="permission-module"><strong><?=e($pmeta['label'])?></strong><div><?php foreach($pmeta['actions'] as $paction=>$plabel):$checked=$editing ? !empty($editingPerms[$pmodule][$paction]) : in_array($pmodule,['dashboard','contacts','campaigns','reports','help'],true);?><label><input type="checkbox" name="permissions[<?=e($pmodule)?>][<?=e($paction)?>]" value="1" <?=$checked?'checked':''?>><span><?=e($plabel)?></span></label><?php endforeach?></div></div><?php endforeach?></div></div>
 <div class="full actions"><button class="btn <?=$editing?'btn-blue':'btn-orange'?>"><span class="btn-ico"><?=ui_icon($editing?'save':'plus')?></span><?=$editing?'Guardar usuario y permisos':'Crear usuario'?></button><?php if($editing):?><a class="btn btn-light" href="index.php?route=administrators"><span class="btn-ico"><?=ui_icon('users')?></span>Nuevo usuario</a><?php endif?></div></div></form>
 <?php if($editing):?><hr class="soft"><span class="eyebrow">SEGURIDAD</span><h3>Cambiar contraseña</h3><form method="post" action="index.php?route=admin_password"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$editing['id']?>"><div class="form-grid"><label>Nueva contraseña<input type="password" name="password" minlength="8" required></label><div class="actions" style="align-self:end"><button class="btn btn-green"><span class="btn-ico"><?=ui_icon('shield')?></span>Actualizar contraseña</button></div></div></form><?php endif?>
 </section>
 <section class="card"><div class="panel-head"><div><span class="eyebrow">EQUIPO</span><h3><?=count($rows)?> usuario<?=count($rows)===1?'':'s'?></h3><p class="muted">Administradores y usuarios internos registrados en EMAILSEND.</p></div><span class="settings-icon"><?=ui_icon('users')?></span></div><div class="admin-user-list"><?php foreach($rows as $r):?><a class="admin-user-row <?=$edit===(int)$r['id']?'selected':''?>" href="index.php?route=administrators&edit=<?=$r['id']?>"><span class="avatar"><?=e(strtoupper(substr(trim($r['name']),0,1)))?></span><span class="admin-user-copy"><strong><?=e($r['name'])?><?=((int)$r['id']===(int)current_user()['id'])?' · Tú':''?></strong><small><?=e($r['email'])?></small><em><?=e($r['tenant_name'])?> · <?=e($r['role']==='admin'?'Administrador':'Usuario')?> · <?=e(($r['list_access_mode']??'all')==='all'?'todas las listas':$r['assigned_count'].' asignadas')?></em></span><span class="status-pill <?=$r['active']?'active':'inactive'?>"><?=$r['active']?'Activo':'Inactivo'?></span></a><?php endforeach?></div></section></div>
 <script>(function(){var tenant=document.getElementById('userTenant'),grid=document.getElementById('userListGrid'),mode=document.getElementById('listAccessMode');function filter(){if(!tenant||!grid)return;var t=tenant.value;grid.querySelectorAll('[data-tenant]').forEach(function(x){x.style.display=x.dataset.tenant===t?'':'none';});}function dim(){if(!grid||!mode)return;grid.classList.toggle('is-optional',mode.value==='all');}if(tenant)tenant.addEventListener('change',filter);if(mode)mode.addEventListener('change',dim);filter();dim();})();</script>
 <?php layout_end();exit;
}
if($route==='settings'){
 require_admin();layout_start('Configuración','settings');$tid=current_tenant_id();$st=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$st->execute([$tid]);$cfgs=$st->fetch()?:[];$domain=email_domain($cfgs['default_from_email']??'');$dns=dns_delivery_status($domain,$cfgs['dkim_selector']??'');$dd=db()->prepare('SELECT COUNT(*) FROM contacts WHERE tenant_id=? AND email LIKE ?');$dd->execute([$tid,'demo.contacto%@example.com']);$legacyDemo=(int)$dd->fetchColumn();?>
 <section class="settings-hero"><div><span class="eyebrow">ENTREGA Y REPUTACIÓN</span><h2>SMTP seguro + dominio autenticado</h2><p>EMAILSEND gestiona el diseño, la cola y las métricas. La entrega debe salir por un SMTP profesional con TLS y un dominio correctamente autenticado.</p></div><div class="settings-hero-icon"><?=ui_icon('shield')?></div></section>
 <div class="settings-grid"><section class="card smtp-card"><div class="panel-head"><div><span class="eyebrow">SMTP</span><h3>Servidor de salida</h3><span class="muted">Recomendado: STARTTLS en puerto 587 o SSL/TLS en 465.</span></div><span class="settings-icon"><?=ui_icon('send')?></span></div><form method="post" action="index.php?route=settings_save"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><div class="form-grid"><label>Host SMTP<input name="smtp_host" value="<?=e($cfgs['smtp_host']??'')?>" placeholder="smtp.proveedor.com" required></label><label>Puerto<input type="number" name="smtp_port" value="<?=e($cfgs['smtp_port']??587)?>" required></label><label>Usuario<input name="smtp_user" value="<?=e($cfgs['smtp_user']??'')?>"></label><label>Contraseña<input type="password" name="smtp_pass" value="" placeholder="<?=!empty($cfgs['smtp_pass'])?'•••••••• guardada':'Ingresa la contraseña'?>"><small class="field-help">Déjala vacía para conservar la contraseña actual.</small></label><label>Seguridad<select name="smtp_secure"><option value="tls" <?=($cfgs['smtp_secure']??'tls')==='tls'?'selected':''?>>STARTTLS · 587</option><option value="ssl" <?=($cfgs['smtp_secure']??'')==='ssl'?'selected':''?>>SSL/TLS · 465</option></select></label><label>Selector DKIM<input name="dkim_selector" value="<?=e($cfgs['dkim_selector']??'')?>" placeholder="Ej.: mail"><small class="field-help">Si tu registro es <b>mail._domainkey.<?=e($domain?:'tudominio.com')?></b>, escribe solamente <b>mail</b>. También puedes pegar el nombre completo: EMAILSEND lo normaliza al guardar.</small></label><label>Nombre remitente por defecto<input name="default_from_name" value="<?=e($cfgs['default_from_name']??'')?>"></label><label>Email remitente por defecto<input type="email" name="default_from_email" value="<?=e($cfgs['default_from_email']??'')?>" placeholder="news@tudominio.com"></label><label class="full">Reply-To<input type="email" name="reply_to_email" value="<?=e($cfgs['reply_to_email']??'')?>" placeholder="respuestas@tudominio.com"></label><div class="full actions"><button class="btn btn-orange"><span class="btn-ico"><?=ui_icon('save')?></span>Guardar SMTP</button></div></div></form><div class="smtp-test-box"><form method="post" action="index.php?route=smtp_test"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><label>Email para prueba<input type="email" name="test_email" value="<?=e(current_user()['email']??'')?>" required></label><button class="btn btn-green"><span class="btn-ico"><?=ui_icon('test')?></span>Probar conexión y envío</button></form></div></section>
 <section class="card dns-card"><div class="panel-head"><div><span class="eyebrow">DNS</span><h3>Estado del dominio</h3><span class="muted"><?=e($domain?:'Configura un email remitente para analizar el dominio.')?></span></div><span class="settings-icon"><?=ui_icon('dns')?></span></div><div class="dns-status-list"><div class="dns-status <?=$dns['spf']['ok']?'ok':'warn'?>"><span><?=ui_icon('shield')?></span><div><strong>SPF</strong><small><?=$dns['spf']['ok']?'Detectado correctamente':'No detectado'?></small></div><b><?=$dns['spf']['ok']?'OK':'REVISAR'?></b></div><div class="dns-status <?=$dns['dkim']['ok']?'ok':'warn'?>"><span><?=ui_icon('shield')?></span><div><strong>DKIM</strong><small><?php if($dns['dkim']['ok']):?>Detectado en <?=e($dns['dkim']['host'])?><?=$dns['dkim']['auto']?' · detección automática':''?><?php elseif(!empty($dns['dkim']['host'])):?>No encontrado en <?=e($dns['dkim']['host'])?><?php else:?>No detectado. Puedes indicar el selector o dejar que EMAILSEND pruebe selectores habituales.<?php endif?></small></div><b><?=$dns['dkim']['ok']?'OK':'REVISAR'?></b></div><div class="dns-status <?=$dns['dmarc']['ok']?'ok':'warn'?>"><span><?=ui_icon('shield')?></span><div><strong>DMARC</strong><small><?=$dns['dmarc']['ok']?'Política publicada':'No detectado'?></small></div><b><?=$dns['dmarc']['ok']?'OK':'REVISAR'?></b></div></div><div class="dns-manual"><h4>Qué deberás configurar en DNS</h4><ol><li><strong>SPF</strong> · un único registro TXT que incluya al proveedor SMTP que finalmente usemos. No crear dos SPF separados.</li><li><strong>DKIM</strong> · el proveedor te dará uno o más registros para <code>selector._domainkey.<?=e($domain?:'tudominio.com')?></code>.</li><li><strong>DMARC</strong> · publicar TXT en <code>_dmarc.<?=e($domain?:'tudominio.com')?></code>. Para comenzar puede usarse <code>v=DMARC1; p=none;</code> y luego endurecer la política.</li><li><strong>TLS</strong> · EMAILSEND fuerza conexión segura al SMTP; no habilitamos envío sin cifrado desde esta pantalla.</li><li><strong>Bajas</strong> · EMAILSEND ya envía List-Unsubscribe y One-Click Unsubscribe en campañas.</li></ol><p class="dns-note">Los valores exactos de SPF y DKIM dependen del proveedor de entrega. Cuando definamos el SMTP definitivo te indicaré exactamente qué registros copiar en la zona DNS de <strong><?=e($domain?:'tu dominio')?></strong>.</p></div></section></div>
 <?php if($legacyDemo>0):?><div class="card legacy-cleanup" style="margin-bottom:16px"><div class="panel-head"><div><span class="eyebrow">LIMPIEZA</span><h3>Datos demo heredados detectados</h3><p class="muted">Encontramos <?=$legacyDemo?> contactos ficticios de versiones anteriores. Esta acción elimina solamente los registros demo conocidos.</p></div><span class="settings-icon"><?=ui_icon('trash')?></span></div><form method="post" action="index.php?route=legacy_demo_remove"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><button class="btn btn-danger" data-confirm data-confirm-variant="danger" data-confirm-title="Eliminar datos demo heredados" data-confirm-message="¿Quieres eliminar definitivamente los registros demo detectados?" data-confirm-note="Solo se eliminarán los datos ficticios reconocidos por EMAILSEND." data-confirm-label="Sí, eliminar datos demo"><span class="btn-ico"><?=ui_icon('trash')?></span>Eliminar datos demo ahora</button></form></div><?php endif;?>
 <div class="split settings-secondary"><div class="card"><span class="eyebrow">PUESTA EN MARCHA</span><h3>Checklist antes del primer envío real</h3><p class="muted">Esta edición trabaja sin datos ficticios. Antes de enviar una campaña masiva revisa estos cuatro puntos.</p><div class="best-practice-list"><p><span><?=ui_icon('shield')?></span><b>1. SMTP probado</b><small>Guarda el proveedor SMTP y envía una prueba a tu propio correo.</small></p><p><span><?=ui_icon('dns')?></span><b>2. Dominio autenticado</b><small>SPF, DKIM y DMARC deben estar correctamente publicados.</small></p><p><span><?=ui_icon('users')?></span><b>3. Contactos válidos</b><small>Importa únicamente destinatarios que esperan recibir tus comunicaciones.</small></p><p><span><?=ui_icon('test')?></span><b>4. Campaña probada</b><small>Revisa diseño, enlaces, versión móvil, remitente, asunto y preheader.</small></p></div></div><div class="card delivery-best-practices"><span class="eyebrow">ENVÍO MASIVO RESPONSABLE</span><h3>Cola, tracking y bajas</h3><div class="best-practice-list"><p><span><?=ui_icon('send')?></span><b>Envío por lotes</b><small>cron/send.php procesa la cola en grupos controlados para no disparar miles de conexiones de una vez.</small></p><p><span><?=ui_icon('minus-user')?></span><b>Bajas automáticas</b><small>Los desuscritos quedan fuera de futuros envíos.</small></p><p><span><?=ui_icon('chart')?></span><b>Tracking individual</b><small>Aperturas y clics se registran por destinatario. Entregas y rebotes confirmados dependen del webhook del proveedor.</small></p><p><span><?=ui_icon('book')?></span><b>CRON</b><small>Configura el worker para ejecutarse de forma periódica según los límites de tu proveedor SMTP.</small></p></div></div></div>
 <?php layout_end();exit;
}
http_response_code(404);echo 'Ruta no encontrada';
