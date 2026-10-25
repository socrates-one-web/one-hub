<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('billing.view');
$pageTitle='Facturación y renovaciones';$pdo=db();
if(db_table_exists('billing_charges')){
    $rows=$pdo->query("SELECT s.* FROM client_services s WHERE s.status='active' AND COALESCE(s.billing_status,'pending')<>'not_billable'")->fetchAll();
    foreach($rows as$r)billing_sync_charge($r);
    $pdo->exec("UPDATE billing_charges SET status='overdue' WHERE status='pending' AND due_date<CURDATE()");
}
$serviceCount=(int)$pdo->query("SELECT COUNT(*) FROM client_services WHERE status='active'")->fetchColumn();
$due7=(int)$pdo->query("SELECT COUNT(*) FROM client_services WHERE status='active' AND COALESCE(next_billing_date,expiration_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)")->fetchColumn();
$due30=(int)$pdo->query("SELECT COUNT(*) FROM client_services WHERE status='active' AND COALESCE(next_billing_date,expiration_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)")->fetchColumn();
$overdue=(int)$pdo->query("SELECT COUNT(*) FROM billing_charges WHERE status='overdue'")->fetchColumn();
$expected=$pdo->query("SELECT currency,SUM(amount) total FROM billing_charges WHERE status IN ('pending','overdue','partial') AND due_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) GROUP BY currency")->fetchAll();
$received=$pdo->query("SELECT currency,SUM(amount) total FROM payments WHERE payment_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) GROUP BY currency")->fetchAll();
$totExp=['CLP'=>0,'USD'=>0,'BRL'=>0];foreach($expected as$r)$totExp[$r['currency']]=(float)$r['total'];$totRec=['CLP'=>0,'USD'=>0,'BRL'=>0];foreach($received as$r)$totRec[$r['currency']]=(float)$r['total'];
$next=$pdo->query("SELECT bc.*,c.company_name,s.name service_name FROM billing_charges bc JOIN clients c ON c.id=bc.client_id JOIN client_services s ON s.id=bc.service_id WHERE bc.status IN ('pending','overdue','partial') ORDER BY bc.due_date ASC LIMIT 12")->fetchAll();
$monthly=$pdo->query("SELECT currency,SUM(amount) total FROM client_services WHERE status='active' AND contract_type='monthly' GROUP BY currency")->fetchAll();
$annual=$pdo->query("SELECT currency,SUM(amount) total FROM client_services WHERE status='active' AND contract_type='annual' GROUP BY currency")->fetchAll();
$mrr=['CLP'=>0.0,'USD'=>0.0,'BRL'=>0.0];foreach($monthly as$r)$mrr[$r['currency']]=(float)$r['total'];$arr=['CLP'=>0.0,'USD'=>0.0,'BRL'=>0.0];foreach($annual as$r)$arr[$r['currency']]=(float)$r['total'];
require __DIR__.'/includes/header.php';
?>
<div class="stats-grid">
<div class="stat-card"><small>SERVICIOS ACTIVOS</small><strong><?=$serviceCount?></strong><span>Contratos y proyectos en curso</span></div>
<div class="stat-card"><small>COBROS PRÓXIMOS 7 DÍAS</small><strong><?=$due7?></strong><span>Requieren seguimiento cercano</span></div>
<div class="stat-card"><small>COBROS PRÓXIMOS 30 DÍAS</small><strong><?=$due30?></strong><span>Renovaciones y mensualidades</span></div>
<div class="stat-card danger"><small>VENCIDOS SIN PAGAR</small><strong><?=$overdue?></strong><span>Ciclos de cobro atrasados</span></div>
</div>
<div class="dashboard-grid">
<section class="panel"><div class="panel-header"><div><h2>Facturación esperada este mes</h2><span class="table-sub">Según los cobros pendientes del período.</span></div></div><div class="panel-body metric-list"><?php foreach($totExp as$cur=>$v):?><div class="metric-row"><span><?=$cur?></span><strong><?=e(format_money($v,$cur))?></strong></div><?php endforeach;?></div></section>
<section class="panel"><div class="panel-header"><div><h2>Ingresado este mes</h2><span class="table-sub">Pagos efectivamente registrados.</span></div></div><div class="panel-body metric-list"><?php foreach($totRec as$cur=>$v):?><div class="metric-row"><span><?=$cur?></span><strong><?=e(format_money($v,$cur))?></strong></div><?php endforeach;?></div></section>
</div>
<section class="panel" style="margin-top:18px"><div class="panel-header"><div><h2>Ingresos recurrentes activos</h2><span class="table-sub">MRR = mensualidades activas · ARR = contratos anuales activos.</span></div></div><div class="panel-body"><div class="details-grid"><?php foreach(['CLP','USD','BRL']as$cur):?><div class="detail"><small>MRR <?=e($cur)?></small><strong><?=e(format_money($mrr[$cur],$cur))?></strong></div><div class="detail"><small>ARR <?=e($cur)?></small><strong><?=e(format_money($arr[$cur],$cur))?></strong></div><?php endforeach;?></div></div></section>
<section class="panel" style="margin-top:18px"><div class="panel-header"><div><h2>Próximos cobros y vencidos</h2><span class="table-sub">Desde aquí puedes registrar el pago y generar automáticamente el siguiente ciclo.</span></div><div class="actions"><a class="btn btn-primary" href="billing_services.php">VER TODOS LOS SERVICIOS</a><?php if(has_permission('payments.manage')):?><a class="btn btn-secondary" href="payment_form.php">+ REGISTRAR INGRESO</a><?php endif;?></div></div><div class="table-wrap"><?php if(!$next):?><div class="empty">No hay cobros pendientes.</div><?php else:?><table class="data-table"><thead><tr><th>Cliente</th><th>Servicio</th><th>Vence</th><th>Valor</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($next as$r):?><tr><td><a class="table-title" href="client_view.php?id=<?=(int)$r['client_id']?>"><?=e($r['company_name'])?></a></td><td><?=e($r['service_name'])?></td><td><?=e(format_date($r['due_date']))?></td><td><?=e(format_money($r['amount'],$r['currency']))?></td><td><span class="badge badge-<?=e(billing_status_class($r['status']))?>"><?=e(billing_status_label($r['status']))?></span></td><td><?php if(has_permission('payments.manage')):?><a class="btn btn-primary btn-small" href="billing_charge_pay.php?id=<?=(int)$r['id']?>">REGISTRAR PAGO</a><?php endif;?></td></tr><?php endforeach;?></tbody></table><?php endif;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
