<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.audit');
$pageTitle='Auditoría documental';
$rows=db()->query('SELECT a.*,d.title,u.name user_name FROM document_audit a LEFT JOIN documents d ON d.id=a.document_id LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 500')->fetchAll();
require __DIR__.'/includes/header.php';
?>
<div class="dms-page">
    <section class="dms-subhero">
        <div>
            <span class="dms-page-kicker">DOCUMENT MANAGER · AUDITORÍA</span>
            <h2>Registro de actividad</h2>
            <p>Visualiza los últimos 500 eventos del Document Manager para mantener control, seguridad y trazabilidad.</p>
        </div>
    </section>

    <section class="panel dms-card" style="margin-top:18px">
        <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">EVENTOS</span><h2>Historial documental</h2></div></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Fecha</th><th>Documento</th><th>Acción</th><th>Usuario</th><th>Detalle</th><th>IP</th></tr></thead>
                <tbody>
                    <?php foreach($rows as $r):?>
                    <tr>
                        <td><?=e(format_date($r['created_at']))?></td>
                        <td><?=e($r['title']?:'#'.(int)$r['document_id'])?></td>
                        <td><span class="dms-status-chip is-blue"><?=e($r['action'])?></span></td>
                        <td><?=e($r['user_name']?:'Externo/Sistema')?></td>
                        <td><?=e($r['details']?:'—')?></td>
                        <td><?=e($r['ip_address']?:'—')?></td>
                    </tr>
                    <?php endforeach;?>
                    <?php if(!$rows):?><tr><td colspan="6"><div class="dms-empty-note">No hay eventos registrados.</div></td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
