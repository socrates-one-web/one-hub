<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.view');
$id=(int)($_GET['id']??0);
$d=dms_require_document($id);
$pageTitle=$d['title'];
$pdo=db();
$u=current_user();
$canEdit=dms_can_edit($d,$u);
$canShare=dms_can_share($d,$u);
$canDelete=dms_can_delete($d,$u);

$st=$pdo->prepare('SELECT v.*,u.name user_name FROM document_versions v LEFT JOIN users u ON u.id=v.created_by WHERE v.document_id=? ORDER BY v.version_no DESC');
$st->execute([$id]);
$versions=$st->fetchAll();
$latest=$versions[0]??null;
$st=$pdo->prepare('SELECT * FROM document_links WHERE document_id=? ORDER BY id');
$st->execute([$id]);
$links=$st->fetchAll();
$st=$pdo->prepare('SELECT c.*,u.name user_name FROM document_comments c LEFT JOIN users u ON u.id=c.user_id WHERE c.document_id=? ORDER BY c.created_at DESC');
$st->execute([$id]);
$comments=$st->fetchAll();
$st=$pdo->prepare('SELECT a.*,r.name route_name FROM document_approvals a LEFT JOIN document_approval_routes r ON r.id=a.route_id WHERE a.document_id=? ORDER BY a.id DESC');
$st->execute([$id]);
$approvals=$st->fetchAll();
$currentApproval=$approvals[0]??null;
$routes=has_permission('documents.approve') ? $pdo->query('SELECT id,name FROM document_approval_routes WHERE is_active=1 ORDER BY name')->fetchAll() : [];
$kind=dms_file_kind($latest['original_name']??null,$latest['mime_type']??null);
$canPreview=$latest && dms_can_preview($latest['mime_type']??null,$latest['original_name']??null);
$previewMode=$latest?dms_preview_mode($latest['original_name']??null,$latest['mime_type']??null):'fallback';
$previewFileAvailable=$latest && is_file(dms_safe_path((string)($latest['stored_path']??'')));

$folders=$pdo->query('SELECT id,name FROM document_folders ORDER BY name')->fetchAll();
$activeUsers=has_permission('documents.manage')?$pdo->query('SELECT id,name,email,role,department FROM users WHERE is_active=1 ORDER BY name')->fetchAll():[];
$ps=$pdo->prepare("SELECT * FROM document_permissions WHERE document_id=? ORDER BY subject_type,subject_value");$ps->execute([$id]);$docPermissions=$ps->fetchAll();
$userPermissionMap=[];$rolePermissionMap=[];$departmentPermissionMap=[];
foreach($docPermissions as$pr){
    if($pr['subject_type']==='user')$userPermissionMap[(int)$pr['subject_value']]=$pr;
    elseif($pr['subject_type']==='role')$rolePermissionMap[(string)$pr['subject_value']]=$pr;
    elseif($pr['subject_type']==='department')$departmentPermissionMap[(string)$pr['subject_value']]=$pr;
}
$permissionRoles=['manager','sales','viewer','custom'];
$departments=[];foreach($activeUsers as$au){$dep=trim((string)($au['department']??''));if($dep!==''&&!in_array($dep,$departments,true))$departments[]=$dep;}sort($departments,SORT_NATURAL|SORT_FLAG_CASE);
$ss=$pdo->prepare('SELECT * FROM document_shares WHERE document_id=? ORDER BY id DESC');$ss->execute([$id]);$shares=$ss->fetchAll();

require __DIR__.'/includes/header.php';
?>
<div class="dms-page dms-document-detail-page">
    <section class="dms-detail-premium-hero">
        <div class="dms-detail-premium-main">
            <div class="dms-file-iconbox dms-file-iconbox-hero dms-kind-<?=e($kind)?>">
                <?=dms_file_icon($latest['original_name']??null,$latest['mime_type']??null)?>
                <span><?=e(dms_file_type_label($latest['original_name']??null,$latest['mime_type']??null))?></span>
            </div>
            <div class="dms-detail-premium-copy">
                <span class="dms-page-kicker">DOCUMENT MANAGER · FICHA DEL DOCUMENTO</span>
                <h2><?=e($d['title'])?></h2>
                <p><?=e($d['description']?:'Sin descripción detallada para este documento.')?></p>
                <div class="dms-detail-tags">
                    <span class="dms-status-chip is-<?=e(dms_status_badge((string)$d['status']))?>"><?=e(dms_status_label((string)$d['status']))?></span>
                    <span class="dms-soft-tag"><?=crm_icon('catalog')?> <?=e($d['folder_name']?:'Sin carpeta')?></span>
                    <span class="dms-soft-tag"><?=crm_icon('contract')?> <?=e($d['category']?:'Sin categoría')?></span>
                    <span class="dms-soft-tag"><?=crm_icon('users')?> <?=e($d['visibility']==='company'?'Empresa':'Privado')?></span>
                </div>
            </div>
        </div>
        <div class="dms-detail-premium-actions">
            <?php if($canPreview):?><a class="btn btn-light" href="#dms-preview">VISTA PREVIA</a><?php endif;?>
            <a class="btn dms-btn-primary" href="document_download.php?id=<?=$id?>">DESCARGAR</a>
            <?php if($canEdit):?><button class="btn btn-light" type="button" onclick="document.getElementById('dms-edit-meta').scrollIntoView({behavior:'smooth'})">EDITAR DATOS</button><button class="btn btn-secondary" type="button" onclick="document.getElementById('new-version').scrollIntoView({behavior:'smooth'})">NUEVA VERSIÓN</button><?php endif;?>
            <a class="btn btn-light" href="documents.php">BIBLIOTECA</a>
        </div>
    </section>

    <section class="dms-meta-grid dms-meta-grid-five">
        <article class="dms-meta-card is-blue"><small>Responsable</small><strong><?=e($d['owner_name']?:'—')?></strong><span>Propietario</span></article>
        <article class="dms-meta-card is-green"><small>Versión actual</small><strong>v<?=(int)($latest['version_no']??0)?></strong><span><?=e(dms_format_bytes((int)($latest['file_size']??0)))?></span></article>
        <article class="dms-meta-card is-orange"><small>Estado</small><strong><?=e(dms_status_label((string)$d['status']))?></strong><span>Flujo documental</span></article>
        <article class="dms-meta-card is-yellow"><small>Vencimiento</small><strong><?=e(format_date($d['expires_at']))?></strong><span>Control de vigencia</span></article>
        <article class="dms-meta-card is-navy"><small>Actualizado</small><strong><?=e(format_date($d['updated_at']))?></strong><span><?=count($versions)?> versión(es)</span></article>
    </section>

    <div class="dms-detail-workspace">
        <section class="panel dms-card dms-preview-panel" id="dms-preview">
            <div class="panel-header dms-card-head">
                <div><span class="dms-section-kicker">ARCHIVO ACTUAL</span><h2>Vista previa</h2><span class="table-sub"><?=e($latest['original_name']??'Archivo no disponible')?></span></div>
                <?php if($latest):?><a class="btn btn-light btn-small" href="document_download.php?id=<?=$id?>">DESCARGAR</a><?php endif;?>
            </div>
            <div class="dms-preview-body">
                <?php if(!$latest):?>
                    <div class="dms-preview-placeholder"><strong>Sin archivo</strong><span>No existe una versión disponible.</span></div>
                <?php elseif(!$previewFileAvailable):?>
                    <div class="dms-preview-placeholder">
                      <strong>Archivo físico no encontrado</strong>
                      <span>El registro del documento existe, pero su archivo no está disponible en este servidor. Comprueba el directorio de almacenamiento y la última copia de seguridad.</span>
                    </div>
                <?php elseif($canPreview && $previewMode==='image'):?>
                    <img class="dms-preview-image" src="document_preview.php?id=<?=$id?>&version=<?=(int)$latest['id']?>" alt="Vista previa de <?=e($d['title'])?>">
                <?php elseif($canPreview):?>
                    <iframe class="dms-preview-frame" src="document_preview.php?id=<?=$id?>&version=<?=(int)$latest['id']?>" title="Vista previa de <?=e($d['title'])?>" loading="lazy"></iframe>
                    <?php if($previewMode==='pdf'):?><p class="dms-preview-open-note" style="margin:10px 0 0;text-align:center;color:var(--muted);font-size:12px">¿No aparece el PDF? <a href="document_preview.php?id=<?=$id?>&version=<?=(int)$latest['id']?>" target="_blank" rel="noopener">Abrir PDF en una pestaña nueva</a></p><?php endif;?>
                <?php else:?>
                    <div class="dms-preview-placeholder dms-kind-<?=e($kind)?>">
                        <div class="dms-file-iconbox dms-file-iconbox-preview dms-kind-<?=e($kind)?>">
                            <?=dms_file_icon($latest['original_name']??null,$latest['mime_type']??null)?>
                            <span><?=e(dms_file_type_label($latest['original_name']??null,$latest['mime_type']??null))?></span>
                        </div>
                        <strong>Vista previa no disponible</strong>
                        <span>Este tipo de archivo se descarga para abrirlo en su aplicación correspondiente.</span>
                        <a class="btn btn-secondary" href="document_download.php?id=<?=$id?>">DESCARGAR ARCHIVO</a>
                    </div>
                <?php endif;?>
            </div>
        </section>

        <aside class="dms-detail-sidebar">
            <section class="panel dms-card">
                <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">RESUMEN</span><h2>Información</h2></div></div>
                <div class="panel-body dms-facts-list">
                    <div><span>Archivo</span><strong><?=e($latest['original_name']??'—')?></strong></div>
                    <div><span>Tipo</span><strong><?=e(strtoupper((string)($latest['mime_type']??'—')))?></strong></div>
                    <div><span>Tamaño</span><strong><?=e(dms_format_bytes((int)($latest['file_size']??0)))?></strong></div>
                    <div><span>Carpeta</span><strong><?=e($d['folder_name']?:'Sin carpeta')?></strong></div>
                    <div><span>Categoría</span><strong><?=e($d['category']?:'Sin categoría')?></strong></div>
                    <div><span>Comentarios</span><strong><?=count($comments)?></strong></div>
                    <div><span>Relaciones</span><strong><?=count($links)?></strong></div>
                </div>
            </section>

            <section class="panel dms-card">
                <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">APROBACIÓN</span><h2>Estado del flujo</h2></div></div>
                <div class="panel-body">
                    <?php if($currentApproval && $currentApproval['status']==='pending'):?>
                        <div class="dms-approval-banner is-orange"><strong>EN APROBACIÓN</strong><span><?=e($currentApproval['route_name']?:'Ruta manual')?></span></div>
                    <?php elseif($currentApproval && $currentApproval['status']==='approved'):?>
                        <div class="dms-approval-banner is-green"><strong>APROBACIÓN COMPLETADA</strong><span><?=e($currentApproval['route_name']?:'Ruta manual')?></span></div>
                    <?php else:?>
                        <div class="dms-empty-note dms-empty-compact">No hay una aprobación activa.</div>
                        <?php if(has_permission('documents.approve') && $routes):?>
                            <form method="post" action="document_action.php" class="dms-compact-form" style="margin-top:12px">
                                <?=csrf_field()?>
                                <input type="hidden" name="action" value="start_approval"><input type="hidden" name="id" value="<?=$id?>">
                                <label><span>Ruta</span><select name="route_id" required><option value="">Seleccionar ruta</option><?php foreach($routes as $r):?><option value="<?=(int)$r['id']?>"><?=e($r['name'])?></option><?php endforeach;?></select></label>
                                <button class="btn btn-primary">INICIAR APROBACIÓN</button>
                            </form>
                        <?php endif;?>
                    <?php endif;?>
                </div>
            </section>

            <section class="panel dms-card">
                <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">RELACIONES</span><h2>Conexiones</h2></div></div>
                <div class="panel-body">
                    <?php if(!$links):?><div class="dms-empty-note dms-empty-compact">Sin relaciones asociadas.</div><?php else:?><div class="dms-rel-list"><?php foreach($links as $l):?><div class="dms-rel-item"><strong><?=e(ucfirst($l['entity_type']))?></strong><span><?=e(dms_entity_label($l['entity_type'],(int)$l['entity_id']))?></span></div><?php endforeach;?></div><?php endif;?>
                </div>
            </section>
        </aside>
    </div>

    <section class="panel dms-card" style="margin-top:18px">
        <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">TRAZABILIDAD</span><h2>Historial de versiones</h2><span class="table-sub">Cada versión conserva su autor, fecha, tamaño y notas.</span></div></div>
        <div class="panel-body dms-version-list">
            <?php foreach($versions as $v):$vKind=dms_file_kind($v['original_name']??null,$v['mime_type']??null);?>
                <div class="dms-version-item<?= (int)$v['id']===(int)$d['current_version_id'] ? ' is-current' : ''?>">
                    <div class="dms-version-fileicon dms-kind-<?=e($vKind)?>"><?=dms_file_icon($v['original_name']??null,$v['mime_type']??null)?><span>v<?=(int)$v['version_no']?></span></div>
                    <div class="dms-version-content">
                        <strong><?=e($v['original_name'])?></strong>
                        <small><?=e(dms_format_bytes((int)$v['file_size']))?> · <?=e($v['user_name']?:'—')?> · <?=e(format_date($v['created_at']))?></small>
                        <?php if(!empty($v['notes'])):?><p><?=e($v['notes'])?></p><?php endif;?>
                    </div>
                    <div class="dms-inline-actions">
                        <?php if(dms_can_preview($v['mime_type']??null)):?><a class="btn btn-light btn-small" target="_blank" rel="noopener" href="document_download.php?id=<?=$id?>&version=<?=(int)$v['id']?>&inline=1">VER</a><?php endif;?>
                        <a class="btn btn-light btn-small" href="document_download.php?id=<?=$id?>&version=<?=(int)$v['id']?>">DESCARGAR</a>
                        <?php if($canEdit && (int)$v['id']!==(int)$d['current_version_id']):?>
                            <form method="post" action="document_action.php"><?=csrf_field()?><input type="hidden" name="action" value="restore_version"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="version_id" value="<?=(int)$v['id']?>"><button class="btn btn-secondary btn-small">RESTAURAR</button></form>
                        <?php else:?><span class="dms-soft-tag is-current">ACTUAL</span><?php endif;?>
                    </div>
                </div>
            <?php endforeach;?>
        </div>
    </section>

    <?php if($canEdit):?>
    <section class="panel dms-card" id="dms-edit-meta" style="margin-top:18px">
      <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">METADATOS</span><h2>Editar información y clasificación</h2></div></div>
      <div class="panel-body"><form method="post" action="document_action.php" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="update_meta"><input type="hidden" name="id" value="<?=$id?>">
        <label>Título<input name="title" required value="<?=e($d['title'])?>"></label><label>Carpeta<select name="folder_id"><option value="">Sin carpeta</option><?php foreach($folders as$f):?><option value="<?=(int)$f['id']?>" <?=(int)$d['folder_id']===(int)$f['id']?'selected':''?>><?=e($f['name'])?></option><?php endforeach;?></select></label>
        <label>Categoría<input name="category" value="<?=e($d['category']??'')?>"></label><label>Estado<select name="status"><?php foreach(['draft'=>'Borrador','review'=>'En revisión','approved'=>'Aprobado','rejected'=>'Rechazado','archived'=>'Archivado'] as$k=>$v):?><option value="<?=$k?>" <?=$d['status']===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></label>
        <label>Visibilidad<select name="visibility"><option value="private" <?=$d['visibility']==='private'?'selected':''?>>Privado</option><option value="company" <?=$d['visibility']==='company'?'selected':''?>>Toda la empresa</option></select></label><label>Vencimiento<input type="date" name="expires_at" value="<?=e($d['expires_at']??'')?>"></label>
        <label class="full">Descripción<textarea name="description" rows="3"><?=e($d['description']??'')?></textarea></label><div class="full"><button class="btn btn-primary">GUARDAR CAMBIOS</button></div></form></div>
    </section>
    <?php endif;?>

    <?php if(has_permission('documents.manage')):?>
    <section class="panel dms-card" style="margin-top:18px">
      <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">PERMISOS</span><h2>Acceso por usuario</h2><span class="table-sub">Los administradores y usuarios con gestión documental conservan acceso total.</span></div></div>
      <div class="panel-body"><form method="post" action="document_action.php"><?=csrf_field()?><input type="hidden" name="action" value="save_permissions"><input type="hidden" name="id" value="<?=$id?>">
      <div class="dms-permission-section"><h3>Usuarios</h3><p>Concede acceso específico a personas. El propietario del documento siempre conserva acceso.</p><div class="dms-permission-grid">
      <?php foreach($activeUsers as$usr):$uid=(int)$usr['id'];$pr=$userPermissionMap[$uid]??null;if($uid===(int)$d['owner_user_id'])continue;?>
        <label class="dms-permission-user"><span><strong><?=e($usr['name'])?></strong><small><?=e($usr['email'])?><?=!empty($usr['department'])?' · '.e($usr['department']):''?></small></span><span><input type="checkbox" name="user_ids[]" value="<?=$uid?>" <?=$pr?'checked':''?>> Ver</span><span><input type="checkbox" name="edit_user_ids[]" value="<?=$uid?>" <?=($pr&&$pr['can_edit'])?'checked':''?>> Editar</span><span><input type="checkbox" name="share_user_ids[]" value="<?=$uid?>" <?=($pr&&$pr['can_share'])?'checked':''?>> Compartir</span></label>
      <?php endforeach;?></div></div>
      <div class="dms-permission-section"><h3>Roles</h3><p>Aplica el acceso a todos los usuarios que tengan ese perfil.</p><div class="dms-permission-grid">
      <?php foreach($permissionRoles as$role):$pr=$rolePermissionMap[$role]??null;?><label class="dms-permission-user"><span><strong><?=e(role_label($role))?></strong><small>Permiso por perfil</small></span><span><input type="checkbox" name="role_ids[]" value="<?=e($role)?>" <?=$pr?'checked':''?>> Ver</span><span><input type="checkbox" name="edit_role_ids[]" value="<?=e($role)?>" <?=($pr&&$pr['can_edit'])?'checked':''?>> Editar</span><span><input type="checkbox" name="share_role_ids[]" value="<?=e($role)?>" <?=($pr&&$pr['can_share'])?'checked':''?>> Compartir</span></label><?php endforeach;?></div></div>
      <?php if($departments):?><div class="dms-permission-section"><h3>Departamentos</h3><p>Comparte con todos los usuarios asignados a un departamento.</p><div class="dms-permission-grid">
      <?php foreach($departments as$dep):$pr=$departmentPermissionMap[$dep]??null;?><label class="dms-permission-user"><span><strong><?=e($dep)?></strong><small>Permiso por departamento</small></span><span><input type="checkbox" name="department_ids[]" value="<?=e($dep)?>" <?=$pr?'checked':''?>> Ver</span><span><input type="checkbox" name="edit_department_ids[]" value="<?=e($dep)?>" <?=($pr&&$pr['can_edit'])?'checked':''?>> Editar</span><span><input type="checkbox" name="share_department_ids[]" value="<?=e($dep)?>" <?=($pr&&$pr['can_share'])?'checked':''?>> Compartir</span></label><?php endforeach;?></div></div><?php endif;?>
      <button class="btn btn-primary" style="margin-top:14px">GUARDAR PERMISOS</button></form></div>
    </section>
    <?php endif;?>

    <section class="panel dms-card" style="margin-top:18px">
      <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">RELACIONES</span><h2>Vincular con el CRM</h2></div></div><div class="panel-body">
      <?php if($canEdit):?><form method="post" action="document_action.php" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="add_link"><input type="hidden" name="id" value="<?=$id?>"><label>Tipo<select name="entity_type"><option value="client">Cliente</option><option value="contact">Contacto</option><option value="project">Proyecto</option><option value="quotation">Cotización</option><option value="contract">Contrato</option><option value="ticket">Ticket</option><option value="service">Servicio</option></select></label><label>ID del registro<input type="number" min="1" name="entity_id" required></label><div class="full"><button class="btn btn-secondary">AGREGAR RELACIÓN</button></div></form><?php endif;?>
      <?php if($links):?><div class="dms-version-list" style="margin-top:14px"><?php foreach($links as$l):?><div class="dms-version-item"><div class="dms-version-content"><strong><?=e(ucfirst($l['entity_type']))?> · <?=e(dms_entity_label($l['entity_type'],(int)$l['entity_id']))?></strong><small>ID <?=(int)$l['entity_id']?></small></div><?php if($canEdit):?><form method="post" action="document_action.php"><?=csrf_field()?><input type="hidden" name="action" value="remove_link"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="link_id" value="<?=(int)$l['id']?>"><button class="btn btn-light btn-small">QUITAR</button></form><?php endif;?></div><?php endforeach;?></div><?php endif;?>
      </div>
    </section>

    <?php if($canShare && $shares):?>
    <section class="panel dms-card" style="margin-top:18px"><div class="panel-header dms-card-head"><div><span class="dms-section-kicker">COMPARTIDOS</span><h2>Enlaces externos activos e históricos</h2></div></div><div class="panel-body dms-version-list"><?php foreach($shares as$s):?><div class="dms-version-item"><div class="dms-version-content"><strong>Enlace #<?=(int)$s['id']?> · <?=$s['is_active']?'Activo':'Desactivado'?></strong><small>Descargas <?=(int)$s['download_count']?><?=$s['download_limit']!==null?' / '.(int)$s['download_limit']:''?> · Vence <?=e(format_date($s['expires_at']))?></small></div><?php if($s['is_active']):?><form method="post" action="document_action.php"><?=csrf_field()?><input type="hidden" name="action" value="disable_share"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="share_id" value="<?=(int)$s['id']?>"><button class="btn btn-danger btn-small">DESACTIVAR</button></form><?php endif;?></div><?php endforeach;?></div></section>
    <?php endif;?>

    <?php if($canEdit):?>
    <section class="panel dms-card" id="new-version" style="margin-top:18px">
        <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">ACTUALIZACIÓN</span><h2>Subir nueva versión</h2></div></div>
        <div class="panel-body"><form method="post" enctype="multipart/form-data" action="document_action.php" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="new_version"><input type="hidden" name="id" value="<?=$id?>"><label>Archivo<input type="file" name="file" required></label><label>Notas<input name="notes" placeholder="Cambios de esta versión"></label><div class="full"><button class="btn btn-primary">CARGAR NUEVA VERSIÓN</button></div></form></div>
    </section>
    <?php endif;?>

    <?php if($canShare):?>
    <section class="panel dms-card" style="margin-top:18px">
        <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">COMPARTIR</span><h2>Enlace seguro externo</h2></div></div>
        <div class="panel-body"><form method="post" action="document_action.php" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="create_share"><input type="hidden" name="id" value="<?=$id?>"><label>Contraseña opcional<input type="password" name="password"></label><label>Vence<input type="datetime-local" name="expires_at"></label><label>Límite de descargas<input type="number" min="0" name="download_limit" value="0"></label><label class="dms-check-card"><input type="checkbox" name="allow_download" value="1" checked> Permitir descarga</label><div class="full"><button class="btn btn-secondary">CREAR ENLACE SEGURO</button></div></form></div>
    </section>
    <?php endif;?>

    <section class="panel dms-card" style="margin-top:18px">
        <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">COLABORACIÓN</span><h2>Comentarios</h2></div></div>
        <div class="panel-body">
            <form method="post" action="document_action.php" class="dms-comment-form"><?=csrf_field()?><input type="hidden" name="action" value="comment"><input type="hidden" name="id" value="<?=$id?>"><input name="comment" required placeholder="Agregar comentario"><button class="btn btn-secondary">COMENTAR</button></form>
            <div class="dms-comment-list">
                <?php if(!$comments):?><div class="dms-empty-note">Aún no hay comentarios.</div><?php endif;?>
                <?php foreach($comments as $c):?><div class="dms-comment-item"><b><?=e(crm_substr((string)($c['user_name']?:'U'),0,1))?></b><div><strong><?=e($c['user_name']?:'Usuario')?></strong><small><?=e(format_date($c['created_at']))?></small><p><?=e($c['comment'])?></p></div></div><?php endforeach;?>
            </div>
        </div>
    </section>

    <div class="actions dms-bottom-actions" style="margin-top:18px">
        <form method="post" action="document_action.php"><?=csrf_field()?><input type="hidden" name="action" value="favorite"><input type="hidden" name="id" value="<?=$id?>"><button class="btn btn-light"><?=$d['is_favorite']?'QUITAR FAVORITO':'★ FAVORITO'?></button></form>
        <?php if($canDelete):?><form method="post" action="document_action.php" onsubmit="return confirm('¿Enviar este documento a la papelera?')"><?=csrf_field()?><input type="hidden" name="action" value="trash"><input type="hidden" name="id" value="<?=$id?>"><button class="btn btn-danger">PAPELERA</button></form><?php endif;?>
    </div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
