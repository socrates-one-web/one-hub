<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.manage');
$pageTitle='Rutas de aprobación';
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=(string)($_POST['action']??'create');$ridAction=(int)($_POST['route_id']??0);if($action==='toggle'&&$ridAction){db()->prepare('UPDATE document_approval_routes SET is_active=IF(is_active=1,0,1) WHERE id=?')->execute([$ridAction]);flash('success','Estado de la ruta actualizado.');redirect('document_routes.php');}
    $name=trim((string)($_POST['name']??''));
    $approvers=array_values(array_filter(array_map('intval',$_POST['approvers']??[])));
    if($name==='' || !$approvers){
        flash('error','Indica nombre y al menos un aprobador.');
        redirect('document_routes.php');
    }
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO document_approval_routes(name,description,created_by) VALUES(?,?,?)')
        ->execute([$name,trim((string)($_POST['description']??''))?:null,(int)current_user()['id']]);
    $rid=(int)$pdo->lastInsertId();
    $ins=$pdo->prepare('INSERT INTO document_approval_route_steps(route_id,step_order,approver_user_id,label) VALUES(?,?,?,?)');
    foreach($approvers as $i=>$uid){
        $ins->execute([$rid,$i+1,$uid,'Aprobación '.($i+1)]);
    }
    $pdo->commit();
    flash('success','Ruta creada.');
    redirect('document_routes.php');
}
$routes=$pdo->query('SELECT r.*,(SELECT COUNT(*) FROM document_approval_route_steps s WHERE s.route_id=r.id) steps,(SELECT COUNT(*) FROM document_approvals a WHERE a.route_id=r.id) uses FROM document_approval_routes r ORDER BY r.created_at DESC')->fetchAll();
$users=$pdo->query('SELECT id,name,role,department FROM users WHERE is_active=1 ORDER BY name')->fetchAll();
require __DIR__.'/includes/header.php';
?>
<div class="dms-page">
    <section class="dms-subhero">
        <div>
            <span class="dms-page-kicker">DOCUMENT MANAGER · RUTAS</span>
            <h2>Rutas de aprobación</h2>
            <p>Configura secuencias de aprobación y responsables para acelerar el flujo documental con trazabilidad.</p>
        </div>
        <div class="dms-subhero-actions"><a class="btn btn-light" href="document_folders.php">VER ORGANIZACIÓN</a></div>
    </section>

    <div class="dms-interior-grid" style="margin-top:18px">
        <section class="panel dms-card">
            <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">RUTAS EXISTENTES</span><h2>Configuración actual</h2></div></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Ruta</th><th>Pasos</th><th>Usos</th><th>Estado</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($routes as $r):?>
                        <tr>
                            <td><strong><?=e($r['name'])?></strong><span class="table-sub"><?=e($r['description']?:'')?></span></td>
                            <td><?=(int)$r['steps']?></td>
                            <td><?=(int)$r['uses']?></td>
                            <td><span class="dms-status-chip is-<?=$r['is_active']?'green':'gray'?>"><?=$r['is_active']?'Activa':'Inactiva'?></span></td><td><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="toggle"><input type="hidden" name="route_id" value="<?=(int)$r['id']?>"><button class="btn btn-light btn-small"><?=$r['is_active']?'DESACTIVAR':'ACTIVAR'?></button></form></td>
                        </tr>
                        <?php endforeach;?>
                        <?php if(!$routes):?><tr><td colspan="5"><div class="dms-empty-note">No hay rutas configuradas.</div></td></tr><?php endif;?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel dms-card">
            <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">NUEVA RUTA</span><h2>Crear flujo</h2></div></div>
            <div class="panel-body">
                <form method="post" class="form-grid">
                    <?=csrf_field()?>
                    <label>Nombre<input name="name" required></label>
                    <label class="full">Descripción<input name="description"></label>
                    <label class="full">Aprobadores en orden<select name="approvers[]" multiple size="8" required><?php foreach($users as $usr):?><option value="<?=(int)$usr['id']?>"><?=e($usr['name'])?> · <?=e($usr['role'])?><?=dms_user_department($usr)?' · '.e(dms_user_department($usr)):''?></option><?php endforeach;?></select><small>Selecciona varios con Ctrl/Cmd. El orden será el del listado.</small></label>
                    <div class="full"><button class="btn btn-primary">CREAR RUTA</button></div>
                </form>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
