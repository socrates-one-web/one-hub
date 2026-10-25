<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('dashboard.view');
$pageTitle=t('dashboard');
$pdo=db();

$stats=[
    'clients'=>(int)$pdo->query("SELECT COUNT(*) FROM clients WHERE status='active'")->fetchColumn(),
    'upcoming7'=>(int)$pdo->query("SELECT COUNT(*) FROM client_services WHERE status='active' AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)")->fetchColumn(),
    'upcoming30'=>(int)$pdo->query("SELECT COUNT(*) FROM client_services WHERE status='active' AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)")->fetchColumn(),
    'expiredUnpaid'=>(int)$pdo->query("SELECT COUNT(*) FROM client_services WHERE status='active' AND expiration_date<CURDATE() AND COALESCE(payment_status,'pending')<>'paid'")->fetchColumn(),
    'quotesMonth'=>(int)$pdo->query("SELECT COUNT(*) FROM quotations WHERE issue_date>=DATE_FORMAT(CURDATE(),'%Y-%m-01')")->fetchColumn(),
    'openDeals'=>(int)$pdo->query("SELECT COUNT(*) FROM deals WHERE status='open'")->fetchColumn(),
    'overdueTasks'=>(int)$pdo->query("SELECT COUNT(*) FROM activities WHERE status='pending' AND due_at<NOW()")->fetchColumn(),
    'leadsMonth'=>(int)$pdo->query("SELECT COUNT(*) FROM lead_submissions WHERE created_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01')")->fetchColumn(),
    'billingDueMonth'=>(int)$pdo->query("SELECT COUNT(*) FROM client_services WHERE status='active' AND COALESCE(next_billing_date,expiration_date) BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())")->fetchColumn(),
    'billingOverdue'=>(int)$pdo->query("SELECT COUNT(*) FROM billing_charges WHERE status='overdue'")->fetchColumn(),
];
$forecastRows=$pdo->query("SELECT currency,SUM(amount) amount,SUM(amount*probability/100) weighted FROM deals WHERE status='open' GROUP BY currency")->fetchAll();
$upcoming=$pdo->query("SELECT s.*,c.company_name FROM client_services s JOIN clients c ON c.id=s.client_id WHERE s.status='active' AND s.expiration_date IS NOT NULL ORDER BY s.expiration_date ASC LIMIT 10")->fetchAll();
$uid=(int)$_SESSION['user_id'];
$taskStmt=$pdo->prepare("SELECT a.*,c.company_name,d.title deal_title,u.name assigned_name FROM activities a LEFT JOIN clients c ON c.id=a.client_id LEFT JOIN deals d ON d.id=a.deal_id LEFT JOIN users u ON u.id=a.assigned_to WHERE a.status='pending' AND (a.assigned_to=? OR ?=(SELECT id FROM users WHERE id=? AND role='admin')) ORDER BY a.due_at IS NULL,a.due_at ASC LIMIT 10");
$taskStmt->execute([$uid,$uid,$uid]);
$tasks=$taskStmt->fetchAll();
$activities=$pdo->query("SELECT l.*,u.name user_name,c.company_name,s.name service_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id LEFT JOIN clients c ON c.id=l.client_id LEFT JOIN client_services s ON s.id=l.service_id ORDER BY l.created_at DESC LIMIT 8")->fetchAll();
$quoteRows=$pdo->query("SELECT status,COUNT(*) total FROM quotations WHERE issue_date>=DATE_FORMAT(CURDATE(),'%Y-%m-01') GROUP BY status")->fetchAll();
$quoteMap=['accepted'=>0,'rejected'=>0,'waiting'=>0];
foreach($quoteRows as$r){
    if($r['status']==='accepted')$quoteMap['accepted']+=(int)$r['total'];
    elseif($r['status']==='rejected')$quoteMap['rejected']+=(int)$r['total'];
    else$quoteMap['waiting']+=(int)$r['total'];
}
$countryRows=$pdo->query("SELECT COALESCE(NULLIF(country,''),'Sin país') country,COUNT(*) total FROM clients WHERE status='active' GROUP BY country ORDER BY total DESC,country LIMIT 12")->fetchAll();
$contractRows=$pdo->query("SELECT contract_type,COUNT(*) total FROM client_services WHERE status='active' GROUP BY contract_type")->fetchAll();
$months=[];$monthKeys=[];
for($i=11;$i>=0;$i--){
    $d=(new DateTimeImmutable('first day of this month'))->modify("-{$i} months");
    $key=$d->format('Y-m');$monthKeys[]=$key;
    $months[$key]=['label'=>$d->format('m/Y'),'CLP'=>0.0,'USD'=>0.0,'BRL'=>0.0];
}
$stmt=$pdo->prepare("SELECT DATE_FORMAT(payment_date,'%Y-%m') ym,currency,SUM(amount) total FROM payments WHERE payment_date>=? GROUP BY ym,currency");
$stmt->execute([$monthKeys[0].'-01']);
foreach($stmt->fetchAll()as$r){if(isset($months[$r['ym']][$r['currency']]))$months[$r['ym']][$r['currency']]=(float)$r['total'];}
$businessRows=$pdo->query("SELECT COALESCE(NULLIF(c.country,''),'Sin país') country,s.currency,COUNT(DISTINCT c.id) businesses,COUNT(*) services,SUM(COALESCE(s.amount,0)) total FROM client_services s JOIN clients c ON c.id=s.client_id WHERE s.status='active' AND c.status='active' GROUP BY country,s.currency ORDER BY country,s.currency")->fetchAll();
$chartData=[
    'expiration'=>['labels'=>[crm_translate_text('Vencidos sin pagar'),crm_translate_text('Por vencer 30 días')],'values'=>[$stats['expiredUnpaid'],$stats['upcoming30']]],
    'quotes'=>['labels'=>[crm_translate_text('Aprobadas'),crm_translate_text('Rechazadas'),crm_translate_text('En espera')],'values'=>[$quoteMap['accepted'],$quoteMap['rejected'],$quoteMap['waiting']]],
    'revenue'=>['labels'=>array_column($months,'label'),'datasets'=>[
        ['label'=>'CLP','values'=>array_column($months,'CLP')],
        ['label'=>'USD','values'=>array_column($months,'USD')],
        ['label'=>'BRL','values'=>array_column($months,'BRL')],
    ]],
    'countries'=>['labels'=>array_column($countryRows,'country'),'values'=>array_map('intval',array_column($countryRows,'total'))],
    'contracts'=>['labels'=>array_map(fn($r)=>contract_label($r['contract_type']),$contractRows),'values'=>array_map(fn($r)=>(int)$r['total'],$contractRows)],
];

$kpis=[
    'kpi-clients'=>['label'=>'CLIENTES ACTIVOS','value'=>$stats['clients'],'href'=>'clients.php?status=active','class'=>'','icon'=>'clients'],
    'kpi-upcoming7'=>['label'=>'VENCIMIENTOS 7 DÍAS','value'=>$stats['upcoming7'],'href'=>'expirations.php?view=week','class'=>'','icon'=>'clock'],
    'kpi-upcoming30'=>['label'=>'VENCIMIENTOS 30 DÍAS','value'=>$stats['upcoming30'],'href'=>'expirations.php?view=month','class'=>'','icon'=>'calendar'],
    'kpi-expired'=>['label'=>'VENCIDOS SIN PAGAR','value'=>$stats['expiredUnpaid'],'href'=>'expirations.php?view=expired','class'=>'danger','icon'=>'billing'],
    'kpi-deals'=>['label'=>'NEGOCIOS ABIERTOS','value'=>$stats['openDeals'],'href'=>'deals.php','class'=>'','icon'=>'deal'],
    'kpi-tasks'=>['label'=>'TAREAS ATRASADAS','value'=>$stats['overdueTasks'],'href'=>'activities.php?status=pending','class'=>'danger','icon'=>'activity'],
    'kpi-leads'=>['label'=>'LEADS DEL MES','value'=>$stats['leadsMonth'],'href'=>'lead_submissions.php','class'=>'','icon'=>'lead'],
    'kpi-quotes'=>['label'=>'COTIZACIONES DEL MES','value'=>$stats['quotesMonth'],'href'=>'quotations.php','class'=>'','icon'=>'quote'],
    'kpi-billing-month'=>['label'=>'COBROS DEL MES','value'=>$stats['billingDueMonth'],'href'=>'billing.php','class'=>'','icon'=>'billing'],
    'kpi-billing-overdue'=>['label'=>'COBROS VENCIDOS','value'=>$stats['billingOverdue'],'href'=>'billing_services.php?filter=overdue','class'=>'danger','icon'=>'clock'],
];
$default=[];
foreach(array_keys($kpis) as$kpiId)$default[]=['id'=>$kpiId,'size'=>'small'];
$default=array_merge($default,[
    ['id'=>'expiration-chart','size'=>'medium'],['id'=>'quotes-chart','size'=>'medium'],['id'=>'revenue-chart','size'=>'full'],
    ['id'=>'countries-chart','size'=>'medium'],['id'=>'forecast','size'=>'medium'],['id'=>'businesses','size'=>'full'],
    ['id'=>'contracts-chart','size'=>'medium'],['id'=>'upcoming','size'=>'large'],['id'=>'tasks','size'=>'medium'],['id'=>'activity','size'=>'medium'],
]);
$prefStmt=$pdo->prepare('SELECT layout_json FROM dashboard_preferences WHERE user_id=?');
$prefStmt->execute([$uid]);
$saved=json_decode((string)($prefStmt->fetchColumn()?:''),true);
$layout=is_array($saved)&&$saved?$saved:$default;

// Compatibilidad con la versión 1.5: la antigua tarjeta "summary" se expande
// en ocho KPI independientes sin borrar la disposición guardada del usuario.
$expanded=[];
foreach($layout as$widget){
    if(($widget['id']??'')==='summary'){
        foreach(array_keys($kpis) as$kpiId)$expanded[]=['id'=>$kpiId,'size'=>'small'];
    }else{$expanded[]=$widget;}
}
$layout=$expanded;
$known=array_column($default,'id');
$currentIds=[];$clean=[];
foreach($layout as$widget){
    $wid=(string)($widget['id']??'');
    if(!in_array($wid,$known,true)||in_array($wid,$currentIds,true))continue;
    $currentIds[]=$wid;$clean[]=$widget;
}
$layout=$clean;
foreach($default as$widget)if(!in_array($widget['id'],array_column($layout,'id'),true))$layout[]=$widget;

function dash_tools():string{
    return has_permission('dashboard.customize')?'<div class="widget-size-tools" aria-label="Tamaño de tarjeta"><button type="button" data-widget-size="small">S</button><button type="button" data-widget-size="medium">M</button><button type="button" data-widget-size="large">L</button><button type="button" data-widget-size="full">XL</button></div>':'';
}
require __DIR__.'/includes/header.php';
?>
<div class="dashboard-toolbar"><div><strong>Tu panel de trabajo</strong><span class="table-sub">Mueve cualquier KPI o gráfico y cambia su tamaño. La disposición se guarda por usuario.</span></div><?php if(has_permission('dashboard.customize')):?><button class="btn btn-secondary" type="button" data-dashboard-edit>Personalizar dashboard</button><?php endif;?></div>
<div class="dashboard-layout" data-dashboard-layout>
<?php foreach($layout as$w):$id=(string)$w['id'];$size=in_array($w['size']??'',['small','medium','large','full'],true)?$w['size']:'medium';?>
<div class="dashboard-widget <?=isset($kpis[$id])?'dashboard-kpi-widget':''?>" data-widget-id="<?=e($id)?>" data-size="<?=e($size)?>"><?=dash_tools()?>
<?php if(isset($kpis[$id])):$k=$kpis[$id];?>
<a class="stat-card dashboard-kpi <?=e($k['class'])?>" href="<?=e($k['href'])?>"><span class="kpi-icon"><?=crm_icon($k['icon'])?></span><small><?=e($k['label'])?></small><strong><?=(int)$k['value']?></strong><span class="kpi-open">Abrir →</span></a>
<?php elseif($id==='expiration-chart'):?><section class="panel chart-panel"><div class="panel-header"><h2>Vencidos y por vencer</h2></div><div class="chart-box"><canvas id="chart-expiration"></canvas></div></section>
<?php elseif($id==='quotes-chart'):?><section class="panel chart-panel"><div class="panel-header"><h2>Cotizaciones del mes</h2></div><div class="chart-box"><canvas id="chart-quotes"></canvas></div></section>
<?php elseif($id==='revenue-chart'):?><section class="panel chart-panel"><div class="panel-header"><div><h2>Dinero ingresado por mes</h2><span class="table-sub">CLP, USD y BRL.</span></div></div><div class="chart-box"><canvas id="chart-revenue"></canvas></div></section>
<?php elseif($id==='countries-chart'):?><section class="panel chart-panel"><div class="panel-header"><h2>Clientes por país</h2></div><div class="chart-box"><canvas id="chart-countries"></canvas></div></section>
<?php elseif($id==='forecast'):?><section class="panel"><div class="panel-header"><div><h2>Pronóstico comercial</h2><span class="table-sub">Pipeline abierto y valor ponderado.</span></div><a class="btn btn-light btn-small" href="deals.php">Kanban</a></div><div class="panel-body metric-list"><?php if(!$forecastRows):?><div class="empty">Sin negocios abiertos.</div><?php endif;?><?php foreach($forecastRows as$r):?><div class="metric-row"><span><?=e($r['currency'])?> · <?=(int)$stats['openDeals']?> oportunidades</span><strong><?=e(format_money($r['weighted'],$r['currency']))?></strong><small>Total: <?=e(format_money($r['amount'],$r['currency']))?></small></div><?php endforeach;?></div></section>
<?php elseif($id==='businesses'):?><section class="panel"><div class="panel-header"><h2>Negocios por país y moneda</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>País</th><th>Moneda</th><th>Clientes</th><th>Servicios</th><th>Valor activo</th></tr></thead><tbody><?php foreach($businessRows as$r):?><tr><td><strong><?=e($r['country'])?></strong></td><td><?=e($r['currency'])?></td><td><?=(int)$r['businesses']?></td><td><?=(int)$r['services']?></td><td><?=e(format_money($r['total'],$r['currency']))?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php elseif($id==='contracts-chart'):?><section class="panel chart-panel"><div class="panel-header"><h2>Modalidades de contratación</h2></div><div class="chart-box"><canvas id="chart-contracts"></canvas></div></section>
<?php elseif($id==='upcoming'):?><section class="panel"><div class="panel-header"><h2>Próximos vencimientos</h2><a class="btn btn-light btn-small" href="expirations.php">Ver todos</a></div><div class="table-wrap"><?php if(!$upcoming):?><div class="empty">Sin vencimientos.</div><?php else:?><table class="data-table"><thead><tr><th>Cliente / servicio</th><th>Vence</th><th>Pago</th><th></th></tr></thead><tbody><?php foreach($upcoming as$s):?><tr><td><a class="table-title" href="client_view.php?id=<?=(int)$s['client_id']?>"><?=e($s['company_name'])?></a><span class="table-sub"><?=e($s['name'])?></span></td><td><?=e(format_date($s['expiration_date']))?></td><td><span class="badge badge-<?=e(payment_status_class($s['payment_status']??'pending'))?>"><?=e(payment_status_label($s['payment_status']??'pending'))?></span></td><td><?php if(has_permission('services.renew')):?><a class="btn btn-light btn-small" href="service_renew.php?id=<?=(int)$s['id']?>">Renovar</a><?php endif;?></td></tr><?php endforeach;?></tbody></table><?php endif;?></div></section>
<?php elseif($id==='tasks'):?><section class="panel"><div class="panel-header"><h2>Mis próximas actividades</h2><a class="btn btn-light btn-small" href="activities.php">Ver todas</a></div><div class="panel-body timeline"><?php if(!$tasks):?><div class="empty">No tienes actividades pendientes.</div><?php endif;?><?php foreach($tasks as$a):?><div class="timeline-item"><strong><?=e($a['subject'])?></strong><small><?=e(activity_type_label($a['activity_type']))?> · <?=e($a['due_at']?(new DateTimeImmutable($a['due_at']))->format('d/m/Y H:i'):'Sin fecha')?></small><span class="table-sub"><?=e($a['company_name']?:$a['deal_title']?:'')?></span></div><?php endforeach;?></div></section>
<?php elseif($id==='activity'):?><section class="panel"><div class="panel-header"><h2>Actividad reciente</h2></div><div class="panel-body timeline"><?php foreach($activities as$a):?><div class="timeline-item"><strong><?=e($a['action'])?></strong><small><?=e($a['company_name']?:$a['user_name']?:'Sistema')?> · <?=e((new DateTimeImmutable($a['created_at']))->format('d/m/Y H:i'))?></small></div><?php endforeach;?></div></section>
<?php endif;?>
</div>
<?php endforeach;?>
</div>
<script type="application/json" id="dashboard-chart-data"><?=json_encode($chartData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG)?></script>
<script src="assets/js/dashboard-charts.js?v=2.0.0"></script>
<?php require __DIR__.'/includes/footer.php';?>
