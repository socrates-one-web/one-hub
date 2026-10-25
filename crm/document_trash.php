<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.view');
$pageTitle='Papelera documental';
$u=current_user();
[$acl,$ap]=dms_access_clause($u,'d');
$st=db()->prepare("SELECT d.*,u.name owner_name FROM documents d LEFT JOIN users u ON u.id=d.owner_user_id WHERE d.is_deleted=1 AND $acl ORDER BY d.deleted_at DESC");
$st->execute($ap);
$rows=$st->fetchAll();
require __DIR__.'/includes/header.php';
?>
<div class="dms-page">
    <section class="dms-subhero">
        <div>
            <span class="dms-page-kicker">DOCUMENT MANAGER · PAPELERA</span>
            <h2>Papelera documental</h2>
            <p>Los archivos enviados a la papelera siguen protegidos y pueden restaurarse cuando sea necesario.</p>
        </div>
    </section>

    <section class="panel dms-card" style="margin-top:18px">
        <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">RECUPERACIÓN</span><h2>Documentos eliminados</h2></div></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Documento</th><th>Responsable</th><th>Eliminado</th><th></th></tr></thead>
                <tbody>
                    <?php foreach($rows as $d):?>
                    <tr>
                        <td><strong><?=e($d['title'])?></strong></td>
                        <td><?=e($d['owner_name']?:'—')?></td>
                        <td><?=e(format_date($d['deleted_at']))?></td>
                        <td><?php if(dms_can_delete($d,$u)):?><form method="post" action="document_action.php"><?=csrf_field()?><input type="hidden" name="action" value="restore"><input type="hidden" name="id" value="<?=(int)$d['id']?>"><button class="btn btn-secondary btn-small">RESTAURAR</button></form><?php endif;?></td>
                    </tr>
                    <?php endforeach;?>
                    <?php if(!$rows):?><tr><td colspan="4"><div class="dms-empty-note">No hay documentos en la papelera.</div></td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
