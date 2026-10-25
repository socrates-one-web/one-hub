<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.view');
$pageTitle='Dashboard documental';
$u=current_user();
[$acl,$ap]=dms_access_clause($u,'d');
$pdo=db();

$statsSql="SELECT
    COUNT(*) total,
    SUM(d.status='draft') draft_count,
    SUM(d.status='review') review_count,
    SUM(d.status='approved') approved_count,
    SUM(d.status='rejected') rejected_count,
    SUM(d.status='archived') archived_count,
    SUM(d.expires_at IS NOT NULL AND d.expires_at < CURDATE()) expired_count,
    SUM(d.expires_at IS NOT NULL AND d.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)) expiring_count,
    SUM(d.is_favorite=1) favorite_count,
    SUM(d.owner_user_id=?) mine_count
FROM documents d
WHERE d.is_deleted=0 AND $acl";
$st=$pdo->prepare($statsSql);
$st->execute(array_merge([(int)$u['id']],$ap));
$k=$st->fetch()?:[];

$st=$pdo->prepare("SELECT d.*,u.name owner_name,f.name folder_name,v.original_name,v.mime_type,v.file_size,v.version_no
    FROM documents d
    LEFT JOIN users u ON u.id=d.owner_user_id
    LEFT JOIN document_folders f ON f.id=d.folder_id
    LEFT JOIN document_versions v ON v.id=d.current_version_id
    WHERE d.is_deleted=0 AND $acl
    ORDER BY d.updated_at DESC LIMIT 7");
$st->execute($ap);
$recent=$st->fetchAll();

$st=$pdo->prepare("SELECT s.*,d.title,a.document_id,a.started_at
    FROM document_approval_steps s
    JOIN document_approvals a ON a.id=s.approval_id
    JOIN documents d ON d.id=a.document_id
    WHERE a.status='pending' AND s.status='pending' AND s.approver_user_id=?
    ORDER BY a.started_at DESC LIMIT 6");
$st->execute([(int)$u['id']]);
$pending=$st->fetchAll();

$total=(int)($k['total']??0);
$approved=(int)($k['approved_count']??0);
$review=(int)($k['review_count']??0);
$draft=(int)($k['draft_count']??0);
$rejected=(int)($k['rejected_count']??0);
$archived=(int)($k['archived_count']??0);
$expiring=(int)($k['expiring_count']??0);
$expired=(int)($k['expired_count']??0);
$favorites=(int)($k['favorite_count']??0);
$mine=(int)($k['mine_count']??0);
$approvalPct=$total>0?(int)round(($approved/$total)*100):0;
$reviewPct=$total>0?(int)round(($review/$total)*100):0;
$draftPct=$total>0?(int)round(($draft/$total)*100):0;
$archivedPct=$total>0?(int)round(($archived/$total)*100):0;

function dms_dashboard_ext(?string $name): string {
    $ext=strtoupper((string)pathinfo((string)$name,PATHINFO_EXTENSION));
    return $ext!==''?substr($ext,0,5):'FILE';
}
function dms_dashboard_size(int $bytes): string {
    if($bytes<=0)return '—';
    if($bytes>=1073741824)return number_format($bytes/1073741824,1,',','.').' GB';
    if($bytes>=1048576)return number_format($bytes/1048576,1,',','.').' MB';
    if($bytes>=1024)return number_format($bytes/1024,0,',','.').' KB';
    return $bytes.' B';
}

require __DIR__.'/includes/header.php';
?>
<div class="dms-dashboard">
<section class="dms-hero" data-tour-target="dms-hero">
    <div class="dms-hero-copy">
        <span class="dms-eyebrow"><?=crm_icon('contract')?> DOCUMENT MANAGER</span>
        <h2>Todo el ciclo documental, en un solo lugar.</h2>
        <p>Centraliza archivos, controla versiones y permisos, relaciona documentos con tu operación y sigue aprobaciones y vencimientos sin perder trazabilidad.</p>
        <div class="dms-hero-actions">
            <?php if(has_permission('documents.upload')):?><a class="btn dms-btn-primary" href="document_upload.php"><?=crm_icon('import')?> SUBIR DOCUMENTO</a><?php endif;?>
            <a class="btn dms-btn-ghost" href="documents.php"><?=crm_icon('contract')?> ABRIR BIBLIOTECA</a>
        </div>
    </div>
    <div class="dms-hero-visual" aria-hidden="true">
        <div class="dms-orbit dms-orbit-a"></div><div class="dms-orbit dms-orbit-b"></div>
        <div class="dms-file-stack">
            <span class="dms-file dms-file-back"><?=crm_icon('contract')?></span>
            <span class="dms-file dms-file-mid"><?=crm_icon('shield')?></span>
            <span class="dms-file dms-file-front"><?=crm_icon('route')?></span>
        </div>
        <div class="dms-hero-mini"><strong><?=$approvalPct?>%</strong><span>aprobados</span></div>
    </div>
</section>

<section class="dms-kpi-grid" data-tour-target="dms-kpis">
    <a class="dms-kpi dms-kpi-blue" href="documents.php">
        <span class="dms-kpi-icon"><?=crm_icon('contract')?></span><div><small>DOCUMENTOS</small><strong><?=$total?></strong><span><?=$mine?> propios · <?=$favorites?> favoritos</span></div>
    </a>
    <a class="dms-kpi dms-kpi-orange" href="documents.php?status=review">
        <span class="dms-kpi-icon"><?=crm_icon('activity')?></span><div><small>EN REVISIÓN</small><strong><?=$review?></strong><span>requieren seguimiento</span></div>
    </a>
    <a class="dms-kpi dms-kpi-green" href="documents.php?status=approved">
        <span class="dms-kpi-icon"><?=crm_icon('shield')?></span><div><small>APROBADOS</small><strong><?=$approved?></strong><span><?=$approvalPct?>% de la biblioteca</span></div>
    </a>
    <a class="dms-kpi dms-kpi-yellow" href="documents.php?expiring=1">
        <span class="dms-kpi-icon"><?=crm_icon('clock')?></span><div><small>POR VENCER</small><strong><?=$expiring?></strong><span>próximos 30 días</span></div>
    </a>
    <?php if(has_permission('documents.approve')):?>
    <a class="dms-kpi dms-kpi-navy" href="document_approvals.php">
        <span class="dms-kpi-icon"><?=crm_icon('route')?></span><div><small>MIS APROBACIONES</small><strong><?=count($pending)?></strong><span>decisiones pendientes</span></div>
    </a>
    <?php else:?>
    <a class="dms-kpi dms-kpi-navy" href="documents.php?favorite=1">
        <span class="dms-kpi-icon"><?=crm_icon('lead')?></span><div><small>FAVORITOS</small><strong><?=$favorites?></strong><span>acceso rápido personal</span></div>
    </a>
    <?php endif;?>
</section>

<div class="dms-layout">
    <section class="panel dms-recent" data-tour-target="dms-recent">
        <div class="panel-header dms-panel-head">
            <div><span class="dms-section-kicker">ÚLTIMOS MOVIMIENTOS</span><h2>Actividad reciente</h2><span class="table-sub">Los documentos modificados más recientemente.</span></div>
            <a class="dms-text-link" href="documents.php">VER TODOS →</a>
        </div>
        <div class="dms-document-list">
            <?php if(!$recent):?><div class="empty">Todavía no hay documentos visibles para tu usuario.</div><?php endif;?>
            <?php foreach($recent as$d):?>
            <a class="dms-document-row" href="document_view.php?id=<?=(int)$d['id']?>">
                <span class="dms-file-iconbox dms-file-iconbox-row dms-kind-<?=e(dms_file_kind($d['original_name']??null,$d['mime_type']??null))?>"><?=dms_file_icon($d['original_name']??null,$d['mime_type']??null)?><em><?=e(dms_file_type_label($d['original_name']??null,$d['mime_type']??null))?></em></span>
                <span class="dms-document-info">
                    <strong><?=e($d['title'])?></strong>
                    <small><?=e($d['folder_name']?:'Sin carpeta')?> · v<?=(int)($d['version_no']??0)?> · <?=e(dms_format_bytes((int)($d['file_size']??0)))?></small>
                </span>
                <span class="badge badge-<?=dms_status_badge((string)$d['status'])?>"><?=e(dms_status_label((string)$d['status']))?></span>
                <span class="dms-owner"><b><?=e(crm_substr((string)($d['owner_name']?:'—'),0,1))?></b><span><?=e($d['owner_name']?:'—')?><small><?=e(format_date($d['updated_at']))?></small></span></span>
                <span class="dms-row-arrow">→</span>
            </a>
            <?php endforeach;?>
        </div>
    </section>

    <aside class="dms-side-stack">
        <section class="panel dms-approval-panel" data-tour-target="dms-approvals">
            <div class="panel-header dms-panel-head"><div><span class="dms-section-kicker">FLUJO DE DECISIÓN</span><h2>Mis aprobaciones</h2></div><span class="dms-count-bubble"><?=count($pending)?></span></div>
            <div class="panel-body dms-approval-list">
                <?php if(!$pending):?>
                    <div class="dms-empty-state"><span><?=crm_icon('shield')?></span><strong>Todo al día</strong><p>No tienes decisiones pendientes.</p></div>
                <?php else:?>
                    <?php foreach($pending as$p):?><div class="dms-approval-item"><div class="dms-approval-step"><?=(int)$p['step_order']?></div><div><strong><?=e($p['title'])?></strong><small><?=e($p['label']?:'Revisión')?> · <?=e(format_date($p['started_at']))?></small></div><a href="document_view.php?id=<?=(int)$p['document_id']?>">REVISAR →</a></div><?php endforeach;?>
                <?php endif;?>
            </div>
        </section>

        <section class="panel dms-health-panel" data-tour-target="dms-health">
            <div class="panel-header dms-panel-head"><div><span class="dms-section-kicker">ESTADO DE LA BIBLIOTECA</span><h2>Salud documental</h2></div><strong class="dms-health-score"><?=$approvalPct?>%</strong></div>
            <div class="panel-body">
                <div class="dms-health-row"><span>Aprobados <b><?=$approved?></b></span><div><i style="width:<?=$approvalPct?>%"></i></div></div>
                <div class="dms-health-row is-orange"><span>En revisión <b><?=$review?></b></span><div><i style="width:<?=$reviewPct?>%"></i></div></div>
                <div class="dms-health-row is-blue"><span>Borradores <b><?=$draft?></b></span><div><i style="width:<?=$draftPct?>%"></i></div></div>
                <div class="dms-health-row is-gray"><span>Archivados <b><?=$archived?></b></span><div><i style="width:<?=$archivedPct?>%"></i></div></div>
                <?php if($expired>0||$rejected>0):?><div class="dms-health-alert"><?=crm_icon('clock')?> <span><?php if($expired>0):?><strong><?=$expired?></strong> vencido<?=$expired===1?'':'s'?><?php endif;?><?php if($expired>0&&$rejected>0):?> · <?php endif;?><?php if($rejected>0):?><strong><?=$rejected?></strong> rechazado<?=$rejected===1?'':'s'?><?php endif;?></span></div><?php endif;?>
            </div>
        </section>
    </aside>
</div>

<section class="dms-quick-actions" data-tour-target="dms-quick-actions">
    <div class="dms-quick-head"><div><span class="dms-section-kicker">ACCESOS DIRECTOS</span><h2>Gestiona el ciclo documental</h2></div><p>Entra directamente a la tarea que necesitas realizar.</p></div>
    <div class="dms-action-grid">
        <a href="documents.php"><span><?=crm_icon('contract')?></span><div><strong>Biblioteca</strong><small>Buscar, filtrar y abrir documentos</small></div><b>→</b></a>
        <?php if(has_permission('documents.upload')):?><a href="document_upload.php"><span><?=crm_icon('import')?></span><div><strong>Subir documento</strong><small>Archivo, metadatos y relación</small></div><b>→</b></a><?php endif;?>
        <a href="documents.php?favorite=1"><span><?=crm_icon('lead')?></span><div><strong>Favoritos</strong><small><?=$favorites?> documentos destacados</small></div><b>→</b></a>
        <?php if(has_permission('documents.approve')):?><a href="document_approvals.php"><span><?=crm_icon('route')?></span><div><strong>Aprobaciones</strong><small>Revisiones y decisiones asignadas</small></div><b>→</b></a><?php endif;?>
        <?php if(has_permission('documents.manage')):?><a href="document_folders.php"><span><?=crm_icon('catalog')?></span><div><strong>Organización</strong><small>Carpetas, subcarpetas y rutas</small></div><b>→</b></a><?php endif;?>
        <?php if(has_permission('documents.audit')):?><a href="document_audit.php"><span><?=crm_icon('shield')?></span><div><strong>Auditoría</strong><small>Trazabilidad de eventos y cambios</small></div><b>→</b></a><?php endif;?>
    </div>
</section>
</div>
<?php require __DIR__.'/includes/footer.php';?>
