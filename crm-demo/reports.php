<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('reports.view');
$pdo=db();$view=(string)($_GET['view']??'overview');$allowed=['overview','commercial','projects','finance','activity'];if(!in_array($view,$allowed,true))$view='overview';
$pageTitle=match($view){'commercial'=>'Informes · Comercial y pipeline','projects'=>'Informes · Proyectos y operación','finance'=>'Informes · Finanzas e ingresos','activity'=>'Informes · Actividad y captación',default=>'Informes y análisis'};

$scalar=static function(string$sql,mixed$default=0)use($pdo){try{$v=$pdo->query($sql)->fetchColumn();return $v===false?$default:$v;}catch(Throwable){return $default;}};
$rows=static function(string$sql)use($pdo):array{try{return $pdo->query($sql)->fetchAll()?:[];}catch(Throwable){return [];}};

$kpi=[
 'clients'=>(int)$scalar("SELECT COUNT(*) FROM clients"),
 'contacts'=>(int)$scalar("SELECT COUNT(*) FROM contacts"),
 'openDeals'=>(int)$scalar("SELECT COUNT(*) FROM deals WHERE status='open'"),
 'leads'=>(int)$scalar("SELECT COUNT(*) FROM lead_submissions WHERE created_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01')"),
 'tasks'=>(int)$scalar("SELECT COUNT(*) FROM activities WHERE status='pending' AND due_at<NOW()"),
 'quotes'=>(int)$scalar("SELECT COUNT(*) FROM quotations WHERE issue_date>=DATE_FORMAT(CURDATE(),'%Y-%m-01')"),
];
$pipeline=$rows("SELECT s.name_es stage,s.name_pt stage_pt,s.name_en stage_en,s.color,s.probability,COUNT(d.id) deals,d.currency,SUM(d.amount) amount,SUM(d.amount*d.probability/100) weighted FROM pipeline_stages s LEFT JOIN deals d ON d.stage_id=s.id AND d.status='open' WHERE s.is_active=1 GROUP BY s.id,d.currency ORDER BY s.sort_order,d.currency");
$owners=$rows("SELECT COALESCE(u.name,'Sin asignar') owner,d.currency,COUNT(*) deals,SUM(d.amount) amount,SUM(d.amount*d.probability/100) weighted FROM deals d LEFT JOIN users u ON u.id=d.owner_user_id WHERE d.status='open' GROUP BY u.id,d.currency ORDER BY weighted DESC");
$countries=$rows("SELECT COALESCE(NULLIF(c.country,''),'Sin país') country,d.currency,COUNT(*) deals,SUM(d.amount) amount,SUM(d.amount*d.probability/100) weighted FROM deals d LEFT JOIN clients c ON c.id=d.client_id WHERE d.status='open' GROUP BY country,d.currency ORDER BY deals DESC");
$quotes=$rows("SELECT status,currency,COUNT(*) total,SUM(total) amount FROM quotations WHERE issue_date>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH) GROUP BY status,currency ORDER BY currency,status");
$activities=$rows("SELECT activity_type,status,COUNT(*) total FROM activities WHERE created_at>=DATE_SUB(NOW(),INTERVAL 90 DAY) GROUP BY activity_type,status");
$leadStats=$rows("SELECT lf.name,COUNT(ls.id) total,SUM(ls.converted_contact_id IS NOT NULL) converted FROM lead_forms lf LEFT JOIN lead_submissions ls ON ls.form_id=lf.id GROUP BY lf.id ORDER BY total DESC LIMIT 12");
$revenue=$rows("SELECT DATE_FORMAT(payment_date,'%Y-%m') month,currency,SUM(amount) total FROM payments WHERE payment_date>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH) GROUP BY month,currency ORDER BY month,currency");

$projectKpi=['active'=>0,'planning'=>0,'risk'=>0,'overdue'=>0,'due7'=>0,'hours'=>0.0];
$projectRows=[];$riskRows=[];$expenseTotals=[];$billingTotals=[];
if(db_table_exists('projects')){
 $projectKpi['active']=(int)$scalar("SELECT COUNT(*) FROM projects WHERE status='active'");
 $projectKpi['planning']=(int)$scalar("SELECT COUNT(*) FROM projects WHERE status='planning'");
 $projectKpi['risk']=(int)$scalar("SELECT COUNT(*) FROM projects WHERE health IN ('at_risk','off_track') AND status IN ('planning','active','on_hold')");
 $projectRows=$rows("SELECT p.id,p.code,p.name,p.status,p.health,p.progress,p.due_date,p.currency,p.budget,c.company_name,u.name owner_name,(SELECT COUNT(*) FROM project_tasks t WHERE t.project_id=p.id) task_count,(SELECT COUNT(*) FROM project_tasks t WHERE t.project_id=p.id AND t.status='done') done_count FROM projects p LEFT JOIN clients c ON c.id=p.client_id LEFT JOIN users u ON u.id=p.owner_user_id ORDER BY FIELD(p.status,'active','planning','on_hold','completed'),p.due_date IS NULL,p.due_date LIMIT 20");
}
if(db_table_exists('project_tasks')){
 $projectKpi['overdue']=(int)$scalar("SELECT COUNT(*) FROM project_tasks WHERE status<>'done' AND due_date<CURDATE()");
 $projectKpi['due7']=(int)$scalar("SELECT COUNT(*) FROM project_tasks WHERE status<>'done' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)");
}
if(db_table_exists('project_time_entries'))$projectKpi['hours']=(float)$scalar("SELECT COALESCE(SUM(hours),0) FROM project_time_entries WHERE work_date>=DATE_FORMAT(CURDATE(),'%Y-%m-01')",0);
if(db_table_exists('project_risks'))$riskRows=$rows("SELECT r.title,r.probability,r.impact,r.status,r.due_date,p.name project_name,u.name owner_name FROM project_risks r JOIN projects p ON p.id=r.project_id LEFT JOIN users u ON u.id=r.owner_user_id WHERE r.status IN ('open','mitigating') ORDER BY (r.probability*r.impact) DESC,r.due_date LIMIT 12");
if(db_table_exists('project_expenses'))$expenseTotals=$rows("SELECT currency,SUM(amount) total,SUM(CASE WHEN billable=1 THEN amount ELSE 0 END) billable FROM project_expenses WHERE expense_date>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH) GROUP BY currency");
if(db_table_exists('billing_charges'))$billingTotals=$rows("SELECT currency,status,COUNT(*) cycles,SUM(amount) total,SUM(paid_amount) paid FROM billing_charges GROUP BY currency,status ORDER BY currency,status");

$chart=['pipeline'=>['labels'=>[],'values'=>[]],'quotes'=>['labels'=>[],'values'=>[]],'leads'=>['labels'=>array_column($leadStats,'name'),'values'=>array_map('intval',array_column($leadStats,'total'))]];
foreach($pipeline as$r){$chart['pipeline']['labels'][]=crm_stage_localized(['name_es'=>$r['stage'],'name_pt'=>$r['stage_pt'],'name_en'=>$r['stage_en']]).' '.$r['currency'];$chart['pipeline']['values'][]=(float)$r['weighted'];}
foreach($quotes as$r){$chart['quotes']['labels'][]=quote_status_label((string)$r['status']).' '.$r['currency'];$chart['quotes']['values'][]=(int)$r['total'];}
require __DIR__.'/includes/header.php';
?>
<div class="report-section-hero">
 <div><span class="eyebrow">CENTRO GENERAL DE INFORMES</span><h2>Información para decidir</h2><p>Ventas, proyectos, finanzas y actividad reunidos bajo un solo menú de análisis.</p></div>
 <div class="report-actions no-print"><button class="btn btn-primary" type="button" onclick="window.print()">IMPRIMIR / PDF</button><a class="btn btn-secondary" href="reports_export.php">EXPORTAR</a></div>
</div>
<nav class="reports-hub no-print">
 <a class="report-hub-card <?=$view==='overview'?'active':''?>" href="reports.php"><span class="report-hub-icon"><?=crm_icon('dashboard')?></span><div><strong>Resumen ejecutivo</strong><small>Visión general</small></div></a>
 <a class="report-hub-card <?=$view==='commercial'?'active':''?>" href="reports.php?view=commercial"><span class="report-hub-icon"><?=crm_icon('deal')?></span><div><strong>Comercial</strong><small>Pipeline y ventas</small></div></a>
 <a class="report-hub-card <?=$view==='projects'?'active':''?>" href="reports.php?view=projects"><span class="report-hub-icon"><?=crm_icon('project')?></span><div><strong>Proyectos</strong><small>Entrega y riesgos</small></div></a>
 <a class="report-hub-card <?=$view==='finance'?'active':''?>" href="reports.php?view=finance"><span class="report-hub-icon"><?=crm_icon('billing')?></span><div><strong>Finanzas</strong><small>Cobros e ingresos</small></div></a>
 <a class="report-hub-card <?=$view==='activity'?'active':''?>" href="reports.php?view=activity"><span class="report-hub-icon"><?=crm_icon('activity')?></span><div><strong>Actividad</strong><small>Leads y productividad</small></div></a>
</nav>

<div class="reports-print-area">
<?php if($view==='overview'):?>
<div class="stats-grid stats-6">
 <div class="stat-card"><small>CLIENTES</small><strong><?=$kpi['clients']?></strong><span>Empresas en la base</span></div>
 <div class="stat-card"><small>NEGOCIOS ABIERTOS</small><strong><?=$kpi['openDeals']?></strong><span>Oportunidades activas</span></div>
 <div class="stat-card"><small>PROYECTOS ACTIVOS</small><strong><?=$projectKpi['active']?></strong><span>En ejecución</span></div>
 <div class="stat-card"><small>RIESGOS ACTIVOS</small><strong><?=$projectKpi['risk']?></strong><span>Requieren atención</span></div>
 <div class="stat-card"><small>LEADS DEL MES</small><strong><?=$kpi['leads']?></strong><span>Captación reciente</span></div>
 <div class="stat-card"><small>TAREAS ATRASADAS</small><strong><?=$projectKpi['overdue']+$kpi['tasks']?></strong><span>CRM + proyectos</span></div>
</div>
<div class="chart-grid report-chart-grid">
 <section class="panel chart-panel"><div class="panel-header"><div><h2>Pronóstico del pipeline</h2><span class="table-sub">Valor ponderado por probabilidad.</span></div></div><div class="chart-box"><canvas id="report-pipeline"></canvas></div></section>
 <section class="panel chart-panel"><div class="panel-header"><h2>Cotizaciones por estado</h2></div><div class="chart-box"><canvas id="report-quotes"></canvas></div></section>
 <section class="panel chart-panel"><div class="panel-header"><h2>Leads por formulario</h2></div><div class="chart-box"><canvas id="report-leads"></canvas></div></section>
</div>
<div class="dashboard-table-grid">
 <section class="panel"><div class="panel-header"><h2>Pipeline por responsable</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Responsable</th><th>Moneda</th><th>Negocios</th><th>Valor</th><th>Pronóstico</th></tr></thead><tbody><?php foreach($owners as$r):?><tr><td><strong><?=e($r['owner'])?></strong></td><td><?=e($r['currency'])?></td><td><?=(int)$r['deals']?></td><td><?=e(format_money($r['amount'],$r['currency']))?></td><td><strong><?=e(format_money($r['weighted'],$r['currency']))?></strong></td></tr><?php endforeach;?></tbody></table></div></section>
 <section class="panel"><div class="panel-header"><h2>Proyectos que requieren atención</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Proyecto</th><th>Cliente</th><th>Salud</th><th>Avance</th></tr></thead><tbody><?php foreach(array_slice(array_filter($projectRows,fn($r)=>in_array($r['health'],['at_risk','off_track'],true)),0,8) as$r):?><tr><td><a class="table-title" href="project_view.php?id=<?=(int)$r['id']?>"><?=e($r['name'])?></a></td><td><?=e($r['company_name']?:'—')?></td><td><?=e(str_replace('_',' ',(string)$r['health']))?></td><td><?=(int)$r['progress']?>%</td></tr><?php endforeach;?></tbody></table></div></section>
</div>

<?php elseif($view==='commercial'):?>
<div class="report-mini-grid">
 <div class="report-mini-card"><small>Negocios abiertos</small><strong><?=$kpi['openDeals']?></strong><span>Oportunidades en curso</span></div>
 <div class="report-mini-card"><small>Cotizaciones del mes</small><strong><?=$kpi['quotes']?></strong><span>Propuestas emitidas</span></div>
 <div class="report-mini-card"><small>Contactos</small><strong><?=$kpi['contacts']?></strong><span>Personas registradas</span></div>
 <div class="report-mini-card"><small>Leads del mes</small><strong><?=$kpi['leads']?></strong><span>Nuevas entradas</span></div>
</div>
<div class="chart-grid" style="margin-top:14px"><section class="panel chart-panel"><div class="panel-header"><h2>Pipeline ponderado</h2></div><div class="chart-box"><canvas id="report-pipeline"></canvas></div></section><section class="panel"><div class="panel-header"><h2>Negocios por país</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>País</th><th>Moneda</th><th>Cantidad</th><th>Valor</th><th>Ponderado</th></tr></thead><tbody><?php foreach($countries as$r):?><tr><td><?=e($r['country'])?></td><td><?=e($r['currency'])?></td><td><?=(int)$r['deals']?></td><td><?=e(format_money($r['amount'],$r['currency']))?></td><td><?=e(format_money($r['weighted'],$r['currency']))?></td></tr><?php endforeach;?></tbody></table></div></section></div>
<section class="panel" style="margin-top:14px"><div class="panel-header"><h2>Responsables comerciales</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Responsable</th><th>Moneda</th><th>Negocios</th><th>Valor</th><th>Pronóstico</th></tr></thead><tbody><?php foreach($owners as$r):?><tr><td><strong><?=e($r['owner'])?></strong></td><td><?=e($r['currency'])?></td><td><?=(int)$r['deals']?></td><td><?=e(format_money($r['amount'],$r['currency']))?></td><td><?=e(format_money($r['weighted'],$r['currency']))?></td></tr><?php endforeach;?></tbody></table></div></section>

<?php elseif($view==='projects'):?>
<div class="report-mini-grid">
 <div class="report-mini-card"><small>Activos</small><strong><?=$projectKpi['active']?></strong><span>En ejecución</span></div>
 <div class="report-mini-card"><small>Planificación</small><strong><?=$projectKpi['planning']?></strong><span>Próximos a iniciar</span></div>
 <div class="report-mini-card"><small>En riesgo</small><strong><?=$projectKpi['risk']?></strong><span>At risk / off track</span></div>
 <div class="report-mini-card"><small>Horas del mes</small><strong><?=e(number_format($projectKpi['hours'],1,',','.'))?></strong><span>Tiempo registrado</span></div>
</div>
<div class="dashboard-table-grid" style="margin-top:14px">
 <section class="panel"><div class="panel-header"><h2>Portafolio</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Proyecto</th><th>Cliente</th><th>Estado</th><th>Salud</th><th>Avance</th><th>Vence</th></tr></thead><tbody><?php foreach($projectRows as$r):?><tr><td><a class="table-title" href="project_view.php?id=<?=(int)$r['id']?>"><?=e($r['name'])?></a><span class="table-sub"><?=e($r['code']?:'')?></span></td><td><?=e($r['company_name']?:'—')?></td><td><?=e(str_replace('_',' ',(string)$r['status']))?></td><td><?=e(str_replace('_',' ',(string)$r['health']))?></td><td><?=(int)$r['progress']?>%</td><td><?=e($r['due_date']?format_date($r['due_date']):'—')?></td></tr><?php endforeach;?></tbody></table></div></section>
 <section class="panel"><div class="panel-header"><h2>Riesgos prioritarios</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Riesgo</th><th>Proyecto</th><th>Score</th><th>Responsable</th></tr></thead><tbody><?php foreach($riskRows as$r):?><tr><td><strong><?=e($r['title'])?></strong></td><td><?=e($r['project_name'])?></td><td><span class="badge badge-orange"><?=e((string)((float)$r['probability']*(float)$r['impact']))?></span></td><td><?=e($r['owner_name']?:'—')?></td></tr><?php endforeach;?></tbody></table></div></section>
</div>

<?php elseif($view==='finance'):?>
<div class="report-mini-grid">
 <?php foreach(array_slice($expenseTotals,0,4) as$r):?><div class="report-mini-card"><small>Gastos <?=e($r['currency'])?></small><strong><?=e(format_money($r['total'],$r['currency']))?></strong><span>Facturables: <?=e(format_money($r['billable'],$r['currency']))?></span></div><?php endforeach;?>
 <?php if(!$expenseTotals):?><div class="report-mini-card"><small>Gastos de proyectos</small><strong>0</strong><span>Sin gastos registrados</span></div><?php endif;?>
</div>
<div class="dashboard-table-grid" style="margin-top:14px">
 <section class="panel"><div class="panel-header"><h2>Ingresos últimos 12 meses</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Mes</th><th>Moneda</th><th>Total</th></tr></thead><tbody><?php foreach($revenue as$r):?><tr><td><?=e($r['month'])?></td><td><?=e($r['currency'])?></td><td><strong><?=e(format_money($r['total'],$r['currency']))?></strong></td></tr><?php endforeach;?></tbody></table></div></section>
 <section class="panel"><div class="panel-header"><h2>Ciclos de cobro</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Moneda</th><th>Estado</th><th>Ciclos</th><th>Total</th><th>Pagado</th></tr></thead><tbody><?php foreach($billingTotals as$r):?><tr><td><?=e($r['currency'])?></td><td><?=e($r['status'])?></td><td><?=(int)$r['cycles']?></td><td><?=e(format_money($r['total'],$r['currency']))?></td><td><?=e(format_money($r['paid'],$r['currency']))?></td></tr><?php endforeach;?></tbody></table></div></section>
</div>

<?php else:?>
<div class="report-mini-grid">
 <div class="report-mini-card"><small>Leads del mes</small><strong><?=$kpi['leads']?></strong><span>Formularios recibidos</span></div>
 <div class="report-mini-card"><small>Tareas CRM atrasadas</small><strong><?=$kpi['tasks']?></strong><span>Actividades pendientes</span></div>
 <div class="report-mini-card"><small>Formularios activos</small><strong><?=count($leadStats)?></strong><span>Fuentes con datos</span></div>
 <div class="report-mini-card"><small>Contactos</small><strong><?=$kpi['contacts']?></strong><span>Base de relaciones</span></div>
</div>
<div class="chart-grid" style="margin-top:14px"><section class="panel chart-panel"><div class="panel-header"><h2>Leads por formulario</h2></div><div class="chart-box"><canvas id="report-leads"></canvas></div></section><section class="panel"><div class="panel-header"><h2>Actividad últimos 90 días</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Tipo</th><th>Estado</th><th>Cantidad</th></tr></thead><tbody><?php foreach($activities as$r):?><tr><td><?=e(activity_type_label($r['activity_type']))?></td><td><?=e($r['status'])?></td><td><strong><?=(int)$r['total']?></strong></td></tr><?php endforeach;?></tbody></table></div></section></div>
<section class="panel" style="margin-top:14px"><div class="panel-header"><h2>Conversión por formulario</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Formulario</th><th>Leads</th><th>Convertidos</th><th>Conversión</th></tr></thead><tbody><?php foreach($leadStats as$r):$pct=(int)$r['total']?round((int)$r['converted']/(int)$r['total']*100):0;?><tr><td><strong><?=e($r['name'])?></strong></td><td><?=(int)$r['total']?></td><td><?=(int)$r['converted']?></td><td><?=$pct?>%</td></tr><?php endforeach;?></tbody></table></div></section>
<?php endif;?>
</div>
<script type="application/json" id="reports-data"><?=json_encode($chart,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG)?></script>
<script src="assets/js/reports.js?v=2.0.2"></script>
<?php require __DIR__.'/includes/footer.php';?>
