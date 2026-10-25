<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('users.view');
$pageTitle='Usuarios y permisos';
$users=db()->query('SELECT id,name,email,role,avatar_path,whatsapp,notify_email,notify_whatsapp,notify_inapp,two_factor_enabled,is_active,failed_login_count,locked_until,last_login_at,created_at FROM users ORDER BY is_active DESC,name')->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="panel"><div class="panel-header"><div><h2>Usuarios del CRM</h2><span class="table-sub">Crea usuarios, define permisos y seguridad, restablece accesos o elimina cuentas que ya no deben pertenecer al sistema.</span></div><?php if(has_permission('users.manage')):?><a class="btn btn-primary" href="user_form.php">+ NUEVO USUARIO</a><?php endif;?></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Usuario</th><th>Perfil</th><th>Alertas</th><th>Seguridad</th><th>Estado</th><th>Último acceso</th><th></th></tr></thead><tbody>
<?php foreach($users as$u):?><tr>
<td><div style="display:flex;align-items:center;gap:11px"><?=user_avatar_html($u,'avatar')?><div><strong><?=e($u['name'])?></strong><span class="table-sub"><?=e($u['email'])?><?=$u['whatsapp']?' · '.e($u['whatsapp']):''?></span></div></div></td>
<td><?=e(role_label($u['role']))?></td>
<td><div class="actions"><?php if($u['notify_email']):?><span class="badge badge-blue">Email</span><?php endif;?><?php if($u['notify_whatsapp']):?><span class="badge badge-green">WhatsApp</span><?php endif;?><?php if($u['notify_inapp']):?><span class="badge badge-orange">CRM</span><?php endif;?></div></td>
<td><span class="badge badge-<?=$u['two_factor_enabled']?'green':'gray'?>"><?=$u['two_factor_enabled']?'2FA activo':'2FA inactivo'?></span></td>
<td><?php $isLocked=!empty($u['locked_until'])&&strtotime((string)$u['locked_until'])>time(); ?><div class="actions"><span class="badge badge-<?=$u['is_active']?'green':'gray'?>"><?=$u['is_active']?'Activo':'Inactivo'?></span><?php if($isLocked):?><span class="badge badge-orange">Bloqueado</span><?php elseif((int)$u['failed_login_count']>0):?><span class="badge badge-gray"><?= (int)$u['failed_login_count'] ?> intento(s)</span><?php endif;?></div></td>
<td><?=e($u['last_login_at']?(new DateTimeImmutable($u['last_login_at']))->format('d/m/Y H:i'):'Nunca')?></td>
<td><?php if(has_permission('users.manage')):?><div class="actions"><a class="btn btn-light btn-small" href="user_form.php?id=<?=(int)$u['id']?>">Editar</a><a class="btn btn-light btn-small" href="user_access_reset.php?id=<?=(int)$u['id']?>">Restablecer acceso</a><?php if((int)$u['id']!==(int)$_SESSION['user_id']):?><form method="post" action="user_toggle.php"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$u['id']?>"><button class="btn btn-small <?=$u['is_active']?'btn-danger':'btn-success'?>" data-confirm="¿Confirmar cambio de acceso?"><?=$u['is_active']?'Desactivar':'Activar'?></button></form><a class="btn btn-small btn-danger btn-outline-danger" href="user_delete.php?id=<?=(int)$u['id']?>">Eliminar</a><?php endif;?></div><?php endif;?></td>
</tr><?php endforeach;?></tbody></table></div></section>
<?php require __DIR__.'/includes/footer.php';?>
