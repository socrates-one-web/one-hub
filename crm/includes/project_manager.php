<?php
declare(strict_types=1);

function pm_status_label(string $status): string { return match($status){'planning'=>'Planificación','active'=>'Activo','on_hold'=>'En pausa','completed'=>'Completado','cancelled'=>'Cancelado',default=>ucfirst($status)}; }
function pm_task_status_label(string $status): string { return match($status){'backlog'=>'Backlog','todo'=>'Por hacer','in_progress'=>'En progreso','review'=>'Revisión','blocked'=>'Bloqueado','done'=>'Completado',default=>ucfirst($status)}; }
function pm_health_label(string $health): string { return match($health){'on_track'=>'En curso','at_risk'=>'En riesgo','off_track'=>'Fuera de curso',default=>ucfirst($health)}; }
function pm_priority_label(string $priority): string { return match($priority){'low'=>'Baja','normal'=>'Normal','high'=>'Alta','urgent'=>'Urgente',default=>ucfirst($priority)}; }
function pm_badge(string $value,string $type='status'):string{
    $class=match($value){'completed','done','on_track'=>'badge-green','active','in_progress','review'=>'badge-blue','planning','todo','backlog'=>'badge-gray','on_hold','at_risk','high'=>'badge-yellow','cancelled','blocked','off_track','urgent'=>'badge-red',default=>'badge-gray'};
    $label=$type==='task'?pm_task_status_label($value):($type==='health'?pm_health_label($value):($type==='priority'?pm_priority_label($value):pm_status_label($value)));
    return '<span class="badge '.$class.'">'.e($label).'</span>';
}
function pm_project_tabs(int $projectId,string $active='summary'):string{
    $tabs=[
        'summary'=>['project_view.php?id='.$projectId,'Resumen','project'],
        'kanban'=>['project_kanban.php?project_id='.$projectId,'Kanban','kanban'],
        'gantt'=>['project_gantt.php?project_id='.$projectId,'Gantt','gantt'],
        'calendar'=>['project_calendar.php?project_id='.$projectId,'Calendario','calendar'],
        'time'=>['project_timesheet.php?project_id='.$projectId,'Horas','time'],
        'risks'=>['project_risks.php?project_id='.$projectId,'Riesgos','risk'],
        'expenses'=>['project_expenses.php?project_id='.$projectId,'Costos','billing'],
    ];
    $riskCount=0;try{$q=db()->prepare("SELECT COUNT(*) FROM project_risks WHERE project_id=? AND status IN ('open','mitigating')");$q->execute([$projectId]);$riskCount=(int)$q->fetchColumn();}catch(Throwable){}
    $html='<div class="module-tabs project-tabs" aria-label="Módulos del proyecto">';
    foreach($tabs as$key=>$t){
        if(($key==='time'&&!has_permission('project_time.view'))||($key==='risks'&&!has_permission('project_risks.view'))||($key==='expenses'&&!has_permission('project_finance.view')))continue;
        $classes=[];if($active===$key)$classes[]='active';if($key==='risks'&&$riskCount>0)$classes[]='has-active-risks';
        $label=crm_translate_text($t[1]);$suffix=$key==='risks'&&$riskCount>0?'<span class="project-tab-count">'.$riskCount.'</span>':'';
        $html.='<a class="'.e(implode(' ',$classes)).'" href="'.e($t[0]).'" title="'.e('Abrir módulo: '.$label).'">'.crm_icon($t[2]).' <span>'.e($label).'</span>'.$suffix.'<small class="tab-open-cue">↗</small></a>';
    }
    return $html.'</div>';
}

function pm_fetch_project(PDO $pdo,int $id):array{
    $s=$pdo->prepare("SELECT p.*,c.company_name,u.name owner_name,d.title deal_title FROM projects p LEFT JOIN clients c ON c.id=p.client_id LEFT JOIN users u ON u.id=p.owner_user_id LEFT JOIN deals d ON d.id=p.deal_id WHERE p.id=?");
    $s->execute([$id]);$r=$s->fetch();if(!$r){http_response_code(404);exit('Proyecto no encontrado.');}return $r;
}
function pm_recalculate_project(PDO $pdo,int $projectId):void{
    $s=$pdo->prepare("SELECT COUNT(*) total,SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) done,AVG(progress) avg_progress,SUM(CASE WHEN status<>'done' AND due_date<CURDATE() THEN 1 ELSE 0 END) overdue FROM project_tasks WHERE project_id=?");
    $s->execute([$projectId]);$m=$s->fetch()?:[];$total=(int)($m['total']??0);$done=(int)($m['done']??0);
    $progress=$total?min(100,max(0,(int)round((float)($m['avg_progress']??($done/$total*100))))):0;
    if($total&&$progress===0&&$done)$progress=(int)round($done/$total*100);
    $r=$pdo->prepare("SELECT MAX(probability*impact) score FROM project_risks WHERE project_id=? AND status IN ('open','mitigating')");$r->execute([$projectId]);$risk=(int)($r->fetchColumn()?:0);$overdue=(int)($m['overdue']??0);
    $health=$overdue>2||$risk>=6400?'off_track':($overdue>0||$risk>=3600?'at_risk':'on_track');
    $pdo->prepare('UPDATE projects SET progress=?,health=? WHERE id=?')->execute([$progress,$health,$projectId]);
}
function pm_date(?string $date):string{return $date?date('d/m/Y',strtotime($date)):'—';}
function pm_project_options(PDO $pdo):array{return $pdo->query("SELECT id,name,status FROM projects WHERE status NOT IN ('cancelled') ORDER BY status='active' DESC,name")->fetchAll();}
