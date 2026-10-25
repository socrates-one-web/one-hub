<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('mailing.view');
$pageTitle='Mailing';
$rows=db()->query("SELECT mc.*,u.name creator_name,(SELECT COUNT(*) FROM mailing_recipients mr WHERE mr.campaign_id=mc.id) recipients,(SELECT COUNT(*) FROM mailing_recipients mr WHERE mr.campaign_id=mc.id AND mr.status='sent') delivered FROM mailing_campaigns mc LEFT JOIN users u ON u.id=mc.created_by ORDER BY mc.created_at DESC")->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="panel"><div class="panel-header"><div><h2>Campañas de email</h2><span class="table-sub">Selecciona clientes o contactos y envía promociones, noticias y mensajes especiales.</span></div><?php if(has_permission('mailing.manage')):?><a class="btn btn-primary" href="mailing_form.php">+ CREAR CAMPAÑA</a><?php endif;?></div>
<div class="table-wrap"><?php if(!$rows):?><div class="empty">Todavía no hay campañas de mailing.</div><?php else:?><table class="data-table"><thead><tr><th>Campaña</th><th>Idioma</th><th>Audiencia</th><th>Enviados</th><th>Estado</th><th>Creada</th><th></th></tr></thead><tbody><?php foreach($rows as$row):?><tr><td><a class="table-title" href="mailing_view.php?id=<?=(int)$row['id']?>"><?=e($row['name'])?></a><span class="table-sub"><?=e($row['subject'])?></span></td><td><?=e(language_label($row['language']))?></td><td><?=(int)$row['recipients']?></td><td><?=(int)$row['delivered']?></td><td><span class="badge badge-<?=e($row['status']==='sent'?'green':($row['status']==='sending'?'blue':'orange'))?>"><?=e(ucfirst($row['status']))?></span></td><td><?=e(format_date($row['created_at']))?></td><td><div class="actions"><a class="btn btn-light btn-small" href="mailing_view.php?id=<?=(int)$row['id']?>">Ver</a><?php if(has_permission('mailing.manage')&&$row['status']!=='sent'):?><a class="btn btn-light btn-small" href="mailing_form.php?id=<?=(int)$row['id']?>">Editar</a><?php endif;?></div></td></tr><?php endforeach;?></tbody></table><?php endif;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
