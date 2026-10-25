<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.approve');
$pageTitle='Rutas de aprobación';
$u=current_user();
$st=db()->prepare("SELECT s.*,d.title,a.document_id,a.started_at
    FROM document_approval_steps s
    JOIN document_approvals a ON a.id=s.approval_id
    JOIN documents d ON d.id=a.document_id
    WHERE a.status='pending' AND s.approver_user_id=?
    ORDER BY s.status='pending' DESC,a.started_at DESC");
$st->execute([(int)$u['id']]);
$rows=$st->fetchAll();
$pendingCount=0;
foreach($rows as $row){ if(($row['status']??'')==='pending') $pendingCount++; }
require __DIR__.'/includes/header.php';
?>
<div class="dms-page">
    <section class="dms-subhero" data-tour-target="dms-approval-workflow">
        <div>
            <span class="dms-page-kicker">DOCUMENT MANAGER · APROBACIONES</span>
            <h2>Mis aprobaciones</h2>
            <p>Gestiona tus decisiones pendientes con una interfaz más clara y visual, alineada al dashboard documental.</p>
        </div>
        <div class="dms-subhero-actions">
            <span class="dms-status-chip is-orange"><?=$pendingCount?> PENDIENTE(S)</span>
            <a class="btn btn-light" href="document_dashboard.php">IR AL DASHBOARD</a>
        </div>
    </section>

    <section class="panel dms-card" style="margin-top:18px">
        <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">BANDEJA</span><h2>Decisiones asignadas</h2><span class="table-sub">Acciones asignadas a tu usuario.</span></div></div>
        <div class="dms-list-card">
            <?php if(!$rows):?>
                <div class="dms-empty-note">No tienes aprobaciones pendientes.</div>
            <?php else:?>
                <?php foreach($rows as $r):?>
                    <div class="dms-approval-item dms-approval-item-rich">
                        <div class="dms-approval-step"><?=(int)$r['step_order']?></div>
                        <div>
                            <strong><?=e($r['title'])?></strong>
                            <small><?=e($r['label']?:'Revisión')?> · <?=e(format_date($r['started_at']))?></small>
                        </div>
                        <span class="dms-status-chip is-<?=($r['status']==='pending'?'orange':'gray')?>"><?=e($r['status'])?></span>
                        <div class="actions">
                            <a class="btn btn-light btn-small" href="document_view.php?id=<?=(int)$r['document_id']?>">VER</a>
                            <?php if($r['status']==='pending'):?>
                            <form method="post" action="document_action.php" class="actions">
                                <?=csrf_field()?>
                                <input type="hidden" name="action" value="decision">
                                <input type="hidden" name="id" value="<?=(int)$r['document_id']?>">
                                <input type="hidden" name="step_id" value="<?=(int)$r['id']?>">
                                <button class="btn btn-success btn-small" name="decision" value="approved">APROBAR</button>
                                <button class="btn btn-danger btn-small" name="decision" value="rejected">RECHAZAR</button>
                            </form>
                            <?php endif;?>
                        </div>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
        </div>
    </section>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
