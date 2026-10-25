<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.manage');
$pageTitle='Organización documental';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$action=(string)($_POST['action']??'create');$fid=(int)($_POST['id']??0);
    if($action==='delete'&&$fid){$st=db()->prepare('SELECT COUNT(*) FROM document_folders WHERE parent_id=?');$st->execute([$fid]);if((int)$st->fetchColumn()>0){flash('error','Mueve o elimina primero las subcarpetas.');}else{db()->prepare('DELETE FROM document_folders WHERE id=?')->execute([$fid]);flash('success','Carpeta eliminada. Los documentos quedaron sin carpeta.');}redirect('document_folders.php');}
    if($action==='toggle'&&$fid){redirect('document_folders.php');}
    $name=trim((string)($_POST['name']??''));if($name!==''){$parent=(int)($_POST['parent_id']??0)?:null;if($parent===$fid)$parent=null;if($action==='update'&&$fid&&$parent){$cursor=$parent;$seen=[];while($cursor){if($cursor===$fid){flash('error','Una carpeta no puede quedar dentro de una de sus propias subcarpetas.');redirect('document_folders.php?edit='.$fid);}if(isset($seen[$cursor]))break;$seen[$cursor]=true;$st=db()->prepare('SELECT parent_id FROM document_folders WHERE id=?');$st->execute([$cursor]);$cursor=(int)($st->fetchColumn()?:0);}}if($action==='update'&&$fid){db()->prepare('UPDATE document_folders SET parent_id=?,name=?,description=? WHERE id=?')->execute([$parent,$name,trim((string)($_POST['description']??''))?:null,$fid]);flash('success','Carpeta actualizada.');}else{db()->prepare('INSERT INTO document_folders(parent_id,name,description,created_by) VALUES(?,?,?,?)')->execute([$parent,$name,trim((string)($_POST['description']??''))?:null,(int)current_user()['id']]);flash('success','Carpeta creada.');}redirect('document_folders.php');}
}
$folders=db()->query("SELECT f.*,p.name parent_name,
    (SELECT COUNT(*) FROM documents d WHERE d.folder_id=f.id AND d.is_deleted=0) document_count
    FROM document_folders f
    LEFT JOIN document_folders p ON p.id=f.parent_id
    ORDER BY COALESCE(p.name,''),f.name")->fetchAll();
$editId=(int)($_GET['edit']??0);$editFolder=null;if($editId){foreach($folders as$ff){if((int)$ff['id']===$editId){$editFolder=$ff;break;}}}
require __DIR__.'/includes/header.php';
?>
<div class="dms-page">
    <section class="dms-subhero">
        <div>
            <span class="dms-page-kicker">DOCUMENT MANAGER · ORGANIZACIÓN</span>
            <h2>Carpetas y estructura documental</h2>
            <p>Organiza la biblioteca por carpetas y subcarpetas, y conecta esta estructura con tus rutas de aprobación.</p>
        </div>
        <div class="dms-subhero-actions"><a class="btn btn-light" href="document_routes.php">ADMINISTRAR RUTAS</a></div>
    </section>

    <div class="dms-interior-grid" style="margin-top:18px">
        <section class="panel dms-card">
            <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">ESTRUCTURA</span><h2>Carpetas y subcarpetas</h2></div></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Carpeta</th><th>Padre</th><th>Documentos</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach($folders as $f):?>
                        <tr>
                            <td><strong><?=e($f['name'])?></strong></td>
                            <td><?=e($f['parent_name']?:'Raíz')?></td>
                            <td><span class="dms-status-chip is-blue"><?=(int)$f['document_count']?></span></td><td><div class="actions"><a class="btn btn-light btn-small" href="document_folders.php?edit=<?=(int)$f['id']?>" title="Editar carpeta"><?=crm_icon('edit')?></a><form method="post" onsubmit="return confirm('¿Eliminar esta carpeta? Los documentos quedarán sin carpeta.')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$f['id']?>"><button class="btn btn-light btn-small" title="Eliminar carpeta"><?=crm_icon('trash')?></button></form></div></td>
                        </tr>
                    <?php endforeach;?>
                    <?php if(!$folders):?><tr><td colspan="4"><div class="dms-empty-note">No hay carpetas creadas todavía.</div></td></tr><?php endif;?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel dms-card">
            <div class="panel-header dms-card-head"><div><span class="dms-section-kicker"><?=$editFolder?'EDITAR CARPETA':'NUEVA CARPETA'?></span><h2><?=$editFolder?'Actualizar carpeta':'Crear carpeta'?></h2></div></div>
            <div class="panel-body">
                <form method="post" class="form-grid">
                    <?=csrf_field()?><?php if($editFolder):?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=(int)$editFolder['id']?>"><?php endif;?>
                    <label>Nombre<input name="name" required value="<?=e($editFolder['name']??'')?>"></label>
                    <label>Carpeta padre<select name="parent_id"><option value="">Raíz</option><?php foreach($folders as $f):?><?php if($editFolder&&(int)$editFolder['id']===(int)$f['id'])continue;?><option value="<?=(int)$f['id']?>" <?=($editFolder&&(int)($editFolder['parent_id']??0)===(int)$f['id'])?'selected':''?>><?=e($f['name'])?></option><?php endforeach;?></select></label>
                    <label class="full">Descripción<input name="description" value="<?=e($editFolder['description']??'')?>"></label>
                    <div class="full actions"><button class="btn btn-primary"><?=$editFolder?'GUARDAR CAMBIOS':'CREAR CARPETA'?></button><?php if($editFolder):?><a class="btn btn-light" href="document_folders.php">CANCELAR</a><?php endif;?></div>
                </form>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
