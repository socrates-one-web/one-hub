<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('users.manage');

$id=max(1,(int)($_GET['id']??$_POST['id']??0));
$stmt=db()->prepare('SELECT id,name,email,role,is_active,failed_login_count,locked_until,last_login_at FROM users WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$user=$stmt->fetch();
if(!$user){http_response_code(404);exit('Usuario no encontrado.');}

$pageTitle='Restablecer acceso';
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $password=(string)($_POST['password']??'');
    $confirm=(string)($_POST['password_confirm']??'');
    if($password!==$confirm)$errors[]='Las contraseñas no coinciden.';
    if($password!=='')$errors=array_merge($errors,crm_password_errors($password,(string)$user['email'],(string)$user['name']));
    else $errors[]='Indica la nueva contraseña.';
    if(!$errors){
        $hash=crm_password_hash($password);
        db()->prepare('UPDATE users SET password_hash=?,password_changed_at=NOW(),failed_login_count=0,locked_until=NULL,is_active=1 WHERE id=?')->execute([$hash,$id]);
        crm_revoke_user_sessions($id);
        try{db()->prepare('DELETE FROM login_attempts WHERE user_id=? OR identifier_hash=?')->execute([$id,hash('sha256',crm_lower(trim((string)$user['email'])))]);}catch(Throwable){}
        crm_security_audit('user_access_reset','critical','Acceso restablecido por administrador: '.(string)$user['email'],'user',$id);
        log_activity('Acceso de usuario restablecido',null,null,(string)$user['email']);
        flash('success','Acceso restablecido. El usuario quedó activo, desbloqueado y con la nueva contraseña.');
        redirect('users.php');
    }
}
require __DIR__.'/includes/header.php';
?>
<section class="panel" style="max-width:760px;margin-inline:auto">
 <div class="panel-header"><div><h2>Restablecer acceso</h2><span class="table-sub">Corrige contraseña, bloqueo e inactividad en una sola operación.</span></div><a class="btn btn-light btn-small" href="users.php">Volver</a></div>
 <div class="panel-body">
  <?php foreach($errors as$error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
  <div class="form-section" style="margin-bottom:18px">
   <h3><?=e($user['name'])?></h3>
   <p class="table-sub"><?=e($user['email'])?></p>
   <div class="actions" style="margin-top:10px">
    <span class="badge badge-<?=$user['is_active']?'green':'gray'?>"><?=$user['is_active']?'Activo':'Inactivo'?></span>
    <?php $locked=!empty($user['locked_until'])&&strtotime((string)$user['locked_until'])>time(); ?>
    <span class="badge badge-<?=$locked?'orange':'green'?>"><?=$locked?'Bloqueado hasta '.e((new DateTimeImmutable((string)$user['locked_until']))->format('d/m/Y H:i')):'Sin bloqueo'?></span>
    <span class="badge badge-gray">Intentos fallidos: <?= (int)$user['failed_login_count'] ?></span>
   </div>
  </div>
  <form method="post" class="form-grid" autocomplete="off">
   <?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
   <label>Nueva contraseña *<div style="position:relative"><input id="new-password" type="password" name="password" minlength="12" maxlength="128" required autocomplete="new-password" style="padding-right:90px"><button type="button" class="btn btn-light btn-small" style="position:absolute;right:5px;top:5px" onclick="const p=document.getElementById('new-password');p.type=p.type==='password'?'text':'password';this.textContent=p.type==='password'?'Ver':'Ocultar'">Ver</button></div><span class="table-sub">Mínimo 12 caracteres. Evita usar el nombre o la parte inicial del email.</span></label>
   <label>Repetir contraseña *<input type="password" name="password_confirm" minlength="12" maxlength="128" required autocomplete="new-password"></label>
   <div class="full alert alert-info" style="margin:0">Al guardar también se <strong>activa el usuario</strong>, se elimina cualquier <strong>bloqueo por intentos fallidos</strong> y se cierran sesiones anteriores.</div>
   <div class="full form-actions"><a class="btn btn-light" href="users.php">Cancelar</a><button class="btn btn-primary">RESTABLECER ACCESO</button></div>
  </form>
 </div>
</section>
<?php require __DIR__.'/includes/footer.php';?>
