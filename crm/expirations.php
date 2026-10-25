<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('services.view');
$pageTitle='Vencimientos';$view=(string)($_GET['view']??'upcoming');
$filters=[
 'today'=>["s.expiration_date=CURDATE()",'Vencen hoy'],
 'tomorrow'=>["s.expiration_date=DATE_ADD(CURDATE(),INTERVAL 1 DAY)",'Vencen mañana'],
 'week'=>["s.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)",'Próximos 7 días'],
 'month'=>["s.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)",'Próximos 30 días'],
 'expired'=>["s.expiration_date<CURDATE()",'Vencidos'],
 'no_date'=>["s.expiration_date IS NULL",'Sin vencimiento'],
 'upcoming'=>["s.expiration_date>=CURDATE()",'Todos los próximos'],
];
if(!isset($filters[$view]))$view='upcoming';[$condition,$label]=$filters[$view];
$sql="SELECT s.*,c.company_name,c.email client_email,c.whatsapp FROM client_services s JOIN clients c ON c.id=s.client_id WHERE s.status='active' AND c.status<>'inactive' AND {$condition} ORDER BY s.expiration_date ASC,c.company_name";$services=db()->query($sql)->fetchAll();
$counts=db()->query("SELECT
 SUM(CASE WHEN s.expiration_date=CURDATE() AND s.status='active' THEN 1 ELSE 0 END) today_count,
 SUM(CASE WHEN s.expiration_date=DATE_ADD(CURDATE(),INTERVAL 1 DAY) AND s.status='active' THEN 1 ELSE 0 END) tomorrow_count,
 SUM(CASE WHEN s.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND s.status='active' THEN 1 ELSE 0 END) week_count,
 SUM(CASE WHEN s.expiration_date<CURDATE() AND s.status='active' THEN 1 ELSE 0 END) expired_count
 FROM client_services s")->fetch();
require __DIR__.'/includes/header.php';
?>
<div class="stats-grid"><a class="stat-card" style="--accent:#d84343;text-decoration:none" href="?view=today"><small>VENCEN HOY</small><strong><?=(int)$counts['today_count']?></strong><span>Servicios que requieren atención inmediata</span></a><a class="stat-card" style="--accent:#f6bd00;text-decoration:none" href="?view=tomorrow"><small>VENCEN MAÑANA</small><strong><?=(int)$counts['tomorrow_count']?></strong><span>Recibirán la alerta configurada</span></a><a class="stat-card" style="--accent:#0074c7;text-decoration:none" href="?view=week"><small>PRÓXIMOS 7 DÍAS</small><strong><?=(int)$counts['week_count']?></strong><span>Planifica sus renovaciones</span></a><a class="stat-card" style="--accent:#ff5300;text-decoration:none" href="?view=expired"><small>VENCIDOS</small><strong><?=(int)$counts['expired_count']?></strong><span>Pendientes de renovar o finalizar</span></a></div>
<section class="panel"><div class="panel-header"><div><h2><?=e($label)?></h2><span class="table-sub"><?=count($services)?> servicios</span></div><div class="filters"><a class="btn btn-light btn-small" href="?view=today">Hoy</a><a class="btn btn-light btn-small" href="?view=tomorrow">Mañana</a><a class="btn btn-light btn-small" href="?view=week">7 días</a><a class="btn btn-light btn-small" href="?view=month">30 días</a><a class="btn btn-light btn-small" href="?view=expired">Vencidos</a><a class="btn btn-light btn-small" href="?view=no_date">Sin fecha</a></div></div><div class="table-wrap">
<?php if(!$services):?><div class="empty">No hay servicios en esta categoría.</div><?php else:?><table class="data-table"><thead><tr><th>Cliente / servicio</th><th>Contrato</th><th>Vencimiento</th><th>Tiempo</th><th>Contacto</th><th></th></tr></thead><tbody><?php foreach($services as $service):$days=days_until($service['expiration_date']);$state=computed_service_status($service);?><tr><td><a class="table-title" href="client_view.php?id=<?=(int)$service['client_id']?>"><?=e($service['company_name'])?></a><span class="table-sub"><?=e($service['name'])?></span></td><td><?=e(contract_label($service['contract_type']))?></td><td><?=e(format_date($service['expiration_date']))?><span class="table-sub"><span class="badge badge-<?=e($state['class'])?>"><?=e($state['label'])?></span></span></td><td><span class="days-pill <?=$days!==null&&$days<0?'negative':''?>"><?php if($days===null):?>—<?php elseif($days<0):?><?=abs($days)?> días vencido<?php elseif($days===0):?>Hoy<?php else:?>En <?=$days?> días<?php endif;?></span></td><td><div class="icon-actions"><?php if($service['client_email']):?><a class="icon-action contact-icon email" title="Enviar email" href="mailto:<?=e($service['client_email'])?>"><?=crm_icon('mail')?></a><?php endif;?><?php if($service['whatsapp']):?><a class="icon-action contact-icon whatsapp" title="Abrir WhatsApp" target="_blank" rel="noopener" href="https://wa.me/<?=e(preg_replace('/\D+/','',$service['whatsapp']))?>"><?=crm_icon('whatsapp')?></a><?php endif;?></div></td><td><a class="btn btn-secondary btn-small" href="service_renew.php?id=<?=(int)$service['id']?>">Renovar</a></td></tr><?php endforeach;?></tbody></table><?php endif;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
