<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('notifications.view');
$pageTitle='Notificaciones';
$userId=(int)$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$action=(string)($_POST['action']??'');
 if($action==='all')db()->prepare('UPDATE user_notifications SET is_read=1 WHERE user_id=?')->execute([$userId]);
 elseif($action==='one'){db()->prepare('UPDATE user_notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([(int)($_POST['id']??0),$userId]);}
 flash('success','Notificaciones actualizadas.');redirect('notifications.php');
}
$stmt=db()->prepare('SELECT * FROM user_notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 150');$stmt->execute([$userId]);$items=$stmt->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="panel"><div class="panel-header"><div><h2>Centro de notificaciones</h2><span class="table-sub"><?=count($items)?> avisos recientes.</span></div><?php if($items):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="all"><button class="btn btn-light btn-small">Marcar todas como leídas</button></form><?php endif;?></div>
<div class="notification-list"><?php if(!$items):?><div class="empty">No tienes notificaciones.</div><?php endif;?><?php foreach($items as$item):?><article class="notification-item <?=$item['is_read']?'read':'unread'?>"><span class="notification-dot"></span><div><strong><?=e($item['title'])?></strong><p><?=e($item['message'])?></p><?php if($item['link']):?><a class="btn btn-light btn-small" style="margin-top:9px" href="<?=e($item['link'])?>">Abrir</a><?php endif;?></div><div><time><?=e((new DateTimeImmutable($item['created_at']))->format('d/m/Y H:i'))?></time><?php if(!$item['is_read']):?><form method="post" style="margin-top:8px"><?=csrf_field()?><input type="hidden" name="action" value="one"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><button class="btn btn-light btn-small">Leída</button></form><?php endif;?></div></article><?php endforeach;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
