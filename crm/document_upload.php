<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.upload');
$pageTitle='Subir documento';
$errors=[];
$pdo=db();
$folders=$pdo->query('SELECT id,name FROM document_folders ORDER BY name')->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try {
        $folder=(int)($_POST['folder_id']??0)?:null;
        $status=in_array($_POST['status']??'draft',['draft','review','approved','rejected','archived'],true)?$_POST['status']:'draft';
        $visibility=in_array($_POST['visibility']??setting('document_default_visibility','private'),['private','company'],true)?(string)($_POST['visibility']??'private'):'private';
        $expiry=trim((string)($_POST['expires_at']??''))?:null;
        $files=[];
        if(isset($_FILES['files']) && is_array($_FILES['files']['name']??null)){
            foreach($_FILES['files']['name'] as$i=>$name){if(($_FILES['files']['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)continue;$files[]=['name'=>$name,'type'=>$_FILES['files']['type'][$i]??'','tmp_name'=>$_FILES['files']['tmp_name'][$i]??'','error'=>$_FILES['files']['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$_FILES['files']['size'][$i]??0];}
        }elseif(isset($_FILES['file'])){$files[]=$_FILES['file'];}
        if(!$files)throw new RuntimeException('Selecciona al menos un archivo.');
        $baseTitle=trim((string)($_POST['title']??''));
        if(count($files)===1 && $baseTitle==='')$baseTitle=pathinfo((string)$files[0]['name'],PATHINFO_FILENAME);
        $created=[];
        foreach($files as$file){
            $title=count($files)>1?pathinfo((string)$file['name'],PATHINFO_FILENAME):$baseTitle;
            if(trim($title)==='')$title='Documento';
            $st=$pdo->prepare('INSERT INTO documents(folder_id,title,description,category,status,visibility,owner_user_id,expires_at) VALUES(?,?,?,?,?,?,?,?)');
            $st->execute([$folder,$title,trim((string)($_POST['description']??''))?:null,trim((string)($_POST['category']??''))?:null,$status,$visibility,(int)current_user()['id'],$expiry]);
            $id=(int)$pdo->lastInsertId();
            try{dms_add_version($id,$file,(int)current_user()['id'],trim((string)($_POST['version_notes']??'')));}catch(Throwable $e){$pdo->prepare('DELETE FROM documents WHERE id=?')->execute([$id]);throw $e;}
            $et=(string)($_POST['entity_type']??'');$eid=(int)($_POST['entity_id']??0);$allowedEntities=['client','contact','project','quotation','contract','ticket','service'];if($et!==''&&$eid>0&&in_array($et,$allowedEntities,true))$pdo->prepare('INSERT IGNORE INTO document_links(document_id,entity_type,entity_id) VALUES(?,?,?)')->execute([$id,$et,$eid]);
            dms_audit($id,'document_created','Documento creado');$created[]=$id;
        }
        flash('success',count($created).' documento(s) cargado(s) correctamente.');
        redirect(count($created)===1?'document_view.php?id='.$created[0]:'documents.php');
    } catch(Throwable $e) {$errors[]=$e->getMessage();}
}

require __DIR__.'/includes/header.php';
?>
<div class="dms-page">
    <?php foreach($errors as $e1):?><div class="alert alert-error"><?=e($e1)?></div><?php endforeach;?>
    <section class="dms-subhero">
        <div>
            <span class="dms-page-kicker">DOCUMENT MANAGER · CARGA</span>
            <h2>Nuevo documento</h2>
            <p>Sube un archivo con metadatos, estado, visibilidad y relación operacional. Todo queda con control de versiones y trazabilidad.</p>
        </div>
        <div class="dms-subhero-actions">
            <a class="btn btn-light" href="documents.php">VOLVER A BIBLIOTECA</a>
        </div>
    </section>

    <section class="panel dms-card" style="margin-top:18px">
        <div class="panel-header dms-card-head"><div><span class="dms-section-kicker">ALTA DE DOCUMENTO</span><h2>Completa la información principal</h2></div></div>
        <div class="panel-body">
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <?=csrf_field()?>
                <label>Título (opcional en carga múltiple)<input name="title" value="<?=e(old('title'))?>" placeholder="Si subes varios se usará el nombre de cada archivo"></label>
                <label class="full dms-dropzone" data-dms-dropzone><strong>Arrastra archivos aquí o haz clic para seleccionar</strong><span>PDF, Office, imágenes, TXT, CSV o ZIP. Puedes subir varios a la vez.</span><input type="file" name="files[]" multiple required data-dms-file-input><small data-dms-file-summary>Ningún archivo seleccionado.</small></label>
                <label>Carpeta<select name="folder_id"><option value="">Sin carpeta</option><?php foreach($folders as $f):?><option value="<?=(int)$f['id']?>"><?=e($f['name'])?></option><?php endforeach;?></select></label>
                <label>Categoría<input name="category" placeholder="Contrato, propuesta, certificado…"></label>
                <label>Estado<select name="status"><option value="draft">Borrador</option><option value="review">En revisión</option><option value="approved">Aprobado</option><option value="archived">Archivado</option></select></label>
                <label>Visibilidad<select name="visibility"><option value="private" <?=setting('document_default_visibility','private')==='private'?'selected':''?>>Privado</option><option value="company" <?=setting('document_default_visibility')==='company'?'selected':''?>>Toda la empresa</option></select></label>
                <label>Fecha de vencimiento<input type="date" name="expires_at"></label>
                <label>Relacionar con<select name="entity_type"><option value="">Sin relación</option><option value="client" <?=($_GET['entity_type']??'')==='client'?'selected':''?>>Cliente</option><option value="contact" <?=($_GET['entity_type']??'')==='contact'?'selected':''?>>Contacto</option><option value="project" <?=($_GET['entity_type']??'')==='project'?'selected':''?>>Proyecto</option><option value="quotation" <?=($_GET['entity_type']??'')==='quotation'?'selected':''?>>Cotización</option><option value="contract" <?=($_GET['entity_type']??'')==='contract'?'selected':''?>>Contrato</option><option value="ticket" <?=($_GET['entity_type']??'')==='ticket'?'selected':''?>>Ticket</option><option value="service" <?=($_GET['entity_type']??'')==='service'?'selected':''?>>Servicio</option></select></label>
                <label>ID relacionado<input type="number" min="1" name="entity_id" value="<?=e((string)($_GET['entity_id']??''))?>" placeholder="ID del registro"></label>
                <label class="full">Descripción<textarea name="description" rows="3"></textarea></label>
                <label class="full">Notas de versión<textarea name="version_notes" rows="2"></textarea></label>
                <div class="full actions"><button class="btn btn-primary">SUBIR DOCUMENTO</button><a class="btn btn-secondary" href="documents.php">CANCELAR</a></div>
            </form>
        </div>
    </section>
</div>
<script>(function(){const z=document.querySelector('[data-dms-dropzone]'),i=document.querySelector('[data-dms-file-input]'),s=document.querySelector('[data-dms-file-summary]');if(!z||!i)return;const show=()=>{const n=i.files?.length||0;s.textContent=n?n+' archivo(s): '+[...i.files].slice(0,3).map(f=>f.name).join(', ')+(n>3?'…':''):'Ningún archivo seleccionado.';};i.addEventListener('change',show);['dragenter','dragover'].forEach(e=>z.addEventListener(e,x=>{x.preventDefault();z.classList.add('is-dragging')}));['dragleave','drop'].forEach(e=>z.addEventListener(e,x=>{x.preventDefault();z.classList.remove('is-dragging')}));z.addEventListener('drop',e=>{if(e.dataTransfer?.files?.length){i.files=e.dataTransfer.files;show();}});})();</script>
<?php require __DIR__.'/includes/footer.php'; ?>
