<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('users.manage');

$id=max(0,(int)($_GET['id']??$_POST['id']??0));
$edit=null;
if($id){
 $stmt=db()->prepare('SELECT * FROM users WHERE id=?');$stmt->execute([$id]);$edit=$stmt->fetch();
 if(!$edit){http_response_code(404);exit('Usuario no encontrado.');}
}
$pageTitle=$edit?'Editar usuario':'Nuevo usuario';
$errors=[];
$existingPermissions=$edit?json_decode((string)($edit['permissions']??''),true):null;
if(!is_array($existingPermissions))$existingPermissions=$edit?default_permissions_for_role((string)$edit['role']):default_permissions_for_role('viewer');

if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $name=trim((string)($_POST['name']??''));
 $email=strtolower(trim((string)($_POST['email']??'')));
 $whatsapp=trim((string)($_POST['whatsapp']??''));
 $department=trim((string)($_POST['department']??''));
 $role=(string)($_POST['role']??'viewer');
 $password=(string)($_POST['password']??'');
 $active=isset($_POST['is_active'])?1:0;
 $notifyEmail=isset($_POST['notify_email'])?1:0;
 $notifyWhatsapp=isset($_POST['notify_whatsapp'])?1:0;
 $notifyInapp=isset($_POST['notify_inapp'])?1:0;
 $twoFactor=isset($_POST['two_factor_enabled'])?1:0;$language=(string)($_POST['language']??'es');if(!isset(crm_supported_languages()[$language]))$language='es';
 $alertDays=implode(',',parse_alert_days($_POST['alert_days']??[1]));
 $permissions=array_values(array_intersect(all_permission_keys(),array_map('strval',$_POST['permissions']??[])));
 if(!in_array($role,['admin','manager','sales','viewer','custom'],true))$role='viewer';
 if($role==='admin')$permissions=[];
 if($name==='')$errors[]='El nombre es obligatorio.';
 if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='El email no es válido.';
 if(!$edit&&$password==='')$errors[]='La contraseña es obligatoria.';
 if($password!=='')$errors=array_merge($errors,crm_password_errors($password,$email,$name));
 if($notifyWhatsapp&&$whatsapp==='')$errors[]='Indica el WhatsApp del usuario para activar esa alerta.';
 $stmt=db()->prepare('SELECT COUNT(*) FROM users WHERE email=? AND id<>?');$stmt->execute([$email,$id]);
 if((int)$stmt->fetchColumn()>0)$errors[]='Ya existe otro usuario con ese email.';
 if($id===(int)($_SESSION['user_id']??0)&&!$active)$errors[]='No puedes desactivar tu propio usuario.';
 $avatar=$edit['avatar_path']??null;
 if(!$errors&&isset($_FILES['avatar'])){
  try{$avatar=upload_user_avatar($_FILES['avatar'],$avatar);}catch(Throwable$e){$errors[]=$e->getMessage();}
 }
 if(!$errors){
  $json=$role==='admin'?null:json_encode($permissions,JSON_UNESCAPED_UNICODE);
  if($edit){
   $sql='UPDATE users SET name=?,email=?,whatsapp=?,role=?,permissions=?,avatar_path=?,notify_email=?,notify_whatsapp=?,notify_inapp=?,alert_days=?,two_factor_enabled=?,language=?,department=?,is_active=?'.($password!==''?',password_hash=?':'').' WHERE id=?';
   $params=[$name,$email,$whatsapp?:null,$role,$json,$avatar,$notifyEmail,$notifyWhatsapp,$notifyInapp,$alertDays,$twoFactor,$language,$department?:null,$active];
   if($password!=='')$params[]=crm_password_hash($password);
   $params[]=$id;db()->prepare($sql)->execute($params);if($password!=='')db()->prepare('UPDATE users SET password_changed_at=NOW(),failed_login_count=0,locked_until=NULL WHERE id=?')->execute([$id]);
   if($password!==''||!$active)crm_revoke_user_sessions($id);crm_security_audit('user_updated','critical','Usuario o permisos actualizados: '.$email,'user',$id);log_activity('Usuario actualizado',null,null,$email);flash('success','Usuario, foto, alertas y permisos actualizados.');
  }else{
   db()->prepare('INSERT INTO users(name,email,password_hash,role,permissions,avatar_path,whatsapp,notify_email,notify_whatsapp,notify_inapp,alert_days,two_factor_enabled,language,department,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$name,$email,crm_password_hash($password),$role,$json,$avatar,$whatsapp?:null,$notifyEmail,$notifyWhatsapp,$notifyInapp,$alertDays,$twoFactor,$language,$department?:null,$active]);
   $newId=(int)db()->lastInsertId();db()->prepare('UPDATE users SET password_changed_at=NOW() WHERE id=?')->execute([$newId]);crm_security_audit('user_created','critical','Usuario creado: '.$email,'user',$newId);log_activity('Usuario creado',null,null,$email);flash('success','Usuario creado correctamente.');
  }
  redirect('users.php');
 }
}
$v=fn(string$key,mixed$default='')=>$_POST[$key]??($edit[$key]??$default);
$selected=$_SERVER['REQUEST_METHOD']==='POST'?array_map('strval',$_POST['permissions']??[]):$existingPermissions;
$alertSelected=parse_alert_days($_SERVER['REQUEST_METHOD']==='POST'?($_POST['alert_days']??[]):($edit['alert_days']??'1'));
$previewUser=$edit?:['name'=>$v('name','Usuario'),'avatar_path'=>null];
require __DIR__.'/includes/header.php';
?>
<?php foreach($errors as$error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
<form method="post" enctype="multipart/form-data" class="panel">
<div class="panel-header"><div><h2><?=$edit?'Actualizar usuario':'Crear acceso al CRM'?></h2><span class="table-sub">Configura acceso, fotografía, alertas, verificación en dos pasos y permisos.</span></div><a class="btn btn-light btn-small" href="users.php">Cancelar</a></div>
<div class="panel-body form-grid"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
<div class="full avatar-editor"><div id="user-avatar-preview"><?=user_avatar_html($previewUser,'avatar')?></div><label>Foto de perfil<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-preview-input="#user-avatar-preview"><span class="table-sub">JPG, PNG o WEBP. Máximo 4 MB.</span></label></div>
<label>Nombre completo *<input name="name" required value="<?=e($v('name'))?>"></label>
<label>Email de acceso *<input type="email" name="email" required value="<?=e($v('email'))?>"></label>
<label>WhatsApp<input name="whatsapp" value="<?=e($v('whatsapp'))?>" placeholder="+55 45 ..."></label>
<label>Departamento<input name="department" value="<?=e($v('department'))?>" placeholder="Ventas, Finanzas, Operaciones…"></label>
<label>Idioma<select name="language"><?php foreach(crm_supported_languages() as $code=>$label):?><option value="<?=e($code)?>" <?=$v('language','es')===$code?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label>
<label>Perfil<select name="role" data-role-select><?php foreach(['admin','manager','sales','viewer','custom']as$role):?><option value="<?=$role?>" data-permissions='<?=e(json_encode(default_permissions_for_role($role)))?>' <?=$v('role','viewer')===$role?'selected':''?>><?=e(role_label($role))?></option><?php endforeach;?></select></label>
<label>Contraseña <?=$edit?'(vacía para conservar)':'*'?><input type="password" name="password" <?=$edit?'':'required'?> minlength="12" autocomplete="new-password"></label>
<label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?=($v('is_active',1))?'checked':''?>> Usuario activo</label>

<div class="form-section full form-grid"><h3>Seguridad y alertas</h3>
<label class="checkbox-row"><input type="checkbox" name="two_factor_enabled" value="1" <?=($v('two_factor_enabled',0))?'checked':''?>> Autenticación de dos factores por email</label>
<label class="checkbox-row"><input type="checkbox" name="notify_email" value="1" <?=($v('notify_email',1))?'checked':''?>> Recibir alertas por email</label>
<label class="checkbox-row"><input type="checkbox" name="notify_whatsapp" value="1" <?=($v('notify_whatsapp',0))?'checked':''?>> Recibir alertas por WhatsApp</label>
<label class="checkbox-row"><input type="checkbox" name="notify_inapp" value="1" <?=($v('notify_inapp',1))?'checked':''?>> Recibir alertas dentro del CRM</label>
<div class="full"><strong>Días de anticipación</strong><div class="actions" style="margin-top:10px"><?php foreach([0,1,3,7,15,30]as$day):?><label class="checkbox-row" style="margin:0"><input type="checkbox" name="alert_days[]" value="<?=$day?>" <?=in_array($day,$alertSelected,true)?'checked':''?>> <?=$day===0?'El mismo día':$day.' días antes'?></label><?php endforeach;?></div></div>
</div>

<div class="full form-section"><h3>Permisos individuales</h3><div class="permission-grid" style="margin-top:14px"><?php foreach(permission_catalog()as$group=>$permissions):?><section class="permission-group"><h3><?=e($group)?></h3><?php foreach($permissions as$key=>$label):?><label class="permission-option"><input type="checkbox" name="permissions[]" value="<?=e($key)?>" <?=in_array($key,$selected,true)?'checked':''?>> <span><?=e($label)?></span></label><?php endforeach;?></section><?php endforeach;?></div><p class="table-sub">Los administradores tienen todos los permisos, aunque las casillas no estén marcadas.</p></div>
<div class="full form-actions"><a class="btn btn-light" href="users.php">Cancelar</a><button class="btn btn-primary">GUARDAR USUARIO</button></div>
</div></form>
<?php require __DIR__.'/includes/footer.php';?>
