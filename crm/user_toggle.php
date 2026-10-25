<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('users.manage');
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('users.php');verify_csrf();
$id=(int)($_POST['id']??0);if($id<1||$id===(int)($_SESSION['user_id']??0)){flash('error','No puedes cambiar el estado de tu propio usuario.');redirect('users.php');}
$stmt=db()->prepare('SELECT name,email,is_active FROM users WHERE id=?');$stmt->execute([$id]);$user=$stmt->fetch();
if(!$user){flash('error','Usuario no encontrado.');redirect('users.php');}
$new=$user['is_active']?0:1;db()->prepare('UPDATE users SET is_active=? WHERE id=?')->execute([$new,$id]);
if(!$new)crm_revoke_user_sessions($id);crm_security_audit($new?'user_activated':'user_deactivated','critical',(string)$user['email'],'user',$id);
log_activity($new?'Usuario activado':'Usuario desactivado',null,null,(string)$user['email']);
flash('success',$new?'Usuario activado.':'Usuario desactivado.');redirect('users.php');
