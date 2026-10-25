<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.view');
$pageTitle='Todos los documentos';
$u=current_user();
[$acl,$ap]=dms_access_clause($u,'d');
$where=['d.is_deleted=0',$acl];
$params=$ap;
$q=trim((string)($_GET['q']??''));
$status=trim((string)($_GET['status']??''));
$folder=(int)($_GET['folder']??0);
if($q!==''){
    $where[]='(d.title LIKE ? OR d.description LIKE ? OR d.category LIKE ?)';
    $like='%'.$q.'%';
    array_push($params,$like,$like,$like);
}
if($status!==''){$where[]='d.status=?';$params[]=$status;}
if($folder>0){$where[]='d.folder_id=?';$params[]=$folder;}
if(isset($_GET['mine'])){$where[]='d.owner_user_id=?';$params[]=(int)$u['id'];}
if(isset($_GET['favorite'])){$where[]='d.is_favorite=1';}
if(isset($_GET['expiring'])){$where[]='d.expires_at IS NOT NULL AND d.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)';}
if(isset($_GET['shared'])){$where[]='d.owner_user_id<>?';$params[]=(int)$u['id'];}

$sql="SELECT d.*,f.name folder_name,u.name owner_name,v.original_name,v.mime_type,v.file_size,v.version_no
      FROM documents d
      LEFT JOIN document_folders f ON f.id=d.folder_id
      LEFT JOIN users u ON u.id=d.owner_user_id
      LEFT JOIN document_versions v ON v.id=d.current_version_id
      WHERE ".implode(' AND ',$where).' ORDER BY d.updated_at DESC';
$st=db()->prepare($sql);
$st->execute($params);
$docs=$st->fetchAll();
$folders=db()->query('SELECT id,name FROM document_folders ORDER BY name')->fetchAll();

$statsStmt=db()->prepare("SELECT COUNT(*) total,
    SUM(d.status='draft') draft_count,
    SUM(d.status='review') review_count,
    SUM(d.status='approved') approved_count,
    SUM(d.status='rejected') rejected_count,
    SUM(d.status='archived') archived_count,
    SUM(d.is_favorite=1) favorite_count,
    SUM(d.owner_user_id=?) mine_count
    FROM documents d WHERE d.is_deleted=0 AND $acl");
$statsStmt->execute(array_merge([(int)$u['id']],$ap));
$stats=$statsStmt->fetch()?:[];

require __DIR__.'/includes/header.php';
?>
<div class="dms-page dms-library-page">
    <section class="dms-subhero" data-tour-target="dms-library">
        <div>
            <span class="dms-page-kicker">DOCUMENT MANAGER · BIBLIOTECA</span>
            <h2>Biblioteca documental</h2>
            <p>Una vista más visual para localizar archivos, reconocer su tipo de inmediato y controlar estados, versiones y responsables.</p>
        </div>
        <div class="dms-subhero-actions">
            <?php if(has_permission('documents.upload')):?><a class="btn dms-btn-primary" href="document_upload.php"><?=crm_icon('import')?> SUBIR DOCUMENTO</a><?php endif;?>
            <a class="btn btn-light" href="document_dashboard.php"><?=crm_icon('dashboard')?> DASHBOARD</a>
        </div>
    </section>

    <section class="dms-mini-kpi-grid">
        <a class="dms-mini-kpi is-blue" href="documents.php"><small>DOCUMENTOS</small><strong><?=(int)($stats['total']??0)?></strong><span>Biblioteca visible</span></a>
        <a class="dms-mini-kpi is-orange" href="documents.php?status=review"><small>EN REVISIÓN</small><strong><?=(int)($stats['review_count']??0)?></strong><span>Requieren seguimiento</span></a>
        <a class="dms-mini-kpi is-green" href="documents.php?status=approved"><small>APROBADOS</small><strong><?=(int)($stats['approved_count']??0)?></strong><span>Documentos validados</span></a>
        <a class="dms-mini-kpi is-yellow" href="documents.php?status=draft"><small>BORRADORES</small><strong><?=(int)($stats['draft_count']??0)?></strong><span>Pendientes de completar</span></a>
        <a class="dms-mini-kpi is-navy" href="documents.php?favorite=1"><small>FAVORITOS</small><strong><?=(int)($stats['favorite_count']??0)?></strong><span>Accesos personales</span></a>
    </section>

    <section class="panel dms-card" style="margin-top:18px">
        <div class="panel-header dms-card-head">
            <div><span class="dms-section-kicker">FILTROS INTELIGENTES</span><h2>Encuentra lo que necesitas</h2></div>
        </div>
        <div class="panel-body">
            <form class="dms-filter-grid" method="get">
                <label><span>Buscar</span><input name="q" value="<?=e($q)?>" placeholder="Nombre, descripción o categoría"></label>
                <label><span>Estado</span><select name="status"><option value="">Todos los estados</option><?php foreach(['draft'=>'Borrador','review'=>'En revisión','approved'=>'Aprobado','rejected'=>'Rechazado','archived'=>'Archivado'] as $k=>$l):?><option value="<?=$k?>" <?=$status===$k?'selected':''?>><?=$l?></option><?php endforeach;?></select></label>
                <label><span>Carpeta</span><select name="folder"><option value="0">Todas las carpetas</option><?php foreach($folders as $f):?><option value="<?=(int)$f['id']?>" <?=$folder===(int)$f['id']?'selected':''?>><?=e($f['name'])?></option><?php endforeach;?></select></label>
                <div class="dms-filter-actions"><button class="btn btn-secondary">FILTRAR</button><a class="btn btn-light" href="documents.php">LIMPIAR</a></div>
            </form>
            <div class="dms-chip-row">
                <a class="dms-chip<?=isset($_GET['mine'])?' is-active':''?>" href="documents.php?mine=1">Mis documentos</a>
                <a class="dms-chip<?=isset($_GET['favorite'])?' is-active':''?>" href="documents.php?favorite=1">Favoritos</a>
                <a class="dms-chip<?=isset($_GET['expiring'])?' is-active':''?>" href="documents.php?expiring=1">Por vencer</a>
                <a class="dms-chip<?=isset($_GET['shared'])?' is-active':''?>" href="documents.php?shared=1">Compartidos</a>
            </div>
        </div>
    </section>

    <section class="panel dms-card dms-library-shell" style="margin-top:18px">
        <div class="panel-header dms-card-head dms-library-head">
            <div>
                <span class="dms-section-kicker">BIBLIOTECA</span>
                <h2>Documentos</h2>
                <span class="table-sub"><?=count($docs)?> documento(s) encontrados.</span>
            </div>
            <div class="dms-view-switch" role="group" aria-label="Vista de biblioteca">
                <button type="button" class="dms-view-btn is-active" data-view="list" title="Vista lista"><?=crm_icon('catalog')?> LISTA</button>
                <button type="button" class="dms-view-btn" data-view="cards" title="Vista tarjetas"><?=crm_icon('dashboard')?> TARJETAS</button>
            </div>
        </div>

        <?php if(!$docs):?>
            <div class="dms-empty-note">No hay documentos para estos filtros.</div>
        <?php else:?>
            <div class="dms-library-items" id="dmsLibraryView" data-view="list">
                <?php foreach($docs as $d):
                    $kind=dms_file_kind($d['original_name']??null,$d['mime_type']??null);
                ?>
                <article class="dms-library-item dms-kind-<?=e($kind)?>">
                    <a class="dms-library-mainlink" href="document_view.php?id=<?=(int)$d['id']?>" aria-label="Abrir <?=e($d['title'])?>"></a>
                    <div class="dms-file-iconbox dms-kind-<?=e($kind)?>">
                        <?=dms_file_icon($d['original_name']??null,$d['mime_type']??null)?>
                        <span><?=e(dms_file_type_label($d['original_name']??null,$d['mime_type']??null))?></span>
                    </div>
                    <div class="dms-library-copy">
                        <div class="dms-library-titleline">
                            <h3><?=e($d['title'])?></h3>
                            <?php if((int)$d['is_favorite']===1):?><span class="dms-favorite-mark" title="Favorito">★</span><?php endif;?>
                        </div>
                        <p><?=e($d['description']?:'Sin descripción')?></p>
                        <div class="dms-library-meta">
                            <span><?=crm_icon('catalog')?> <?=e($d['folder_name']?:'Sin carpeta')?></span>
                            <span><?=crm_icon('contract')?> <?=e($d['category']?:'Sin categoría')?></span>
                            <span><?=crm_icon('route')?> v<?=(int)($d['version_no']??0)?></span>
                            <span><?=crm_icon('billing')?> <?=e(dms_format_bytes((int)($d['file_size']??0)))?></span>
                        </div>
                    </div>
                    <div class="dms-library-status"><span class="dms-status-chip is-<?=e(dms_status_badge((string)$d['status']))?>"><?=e(dms_status_label((string)$d['status']))?></span></div>
                    <div class="dms-library-owner">
                        <b><?=e(crm_substr((string)($d['owner_name']?:'—'),0,1))?></b>
                        <span><strong><?=e($d['owner_name']?:'—')?></strong><small><?=e(format_date($d['updated_at']))?></small></span>
                    </div>
                    <div class="dms-library-action"><a class="btn btn-light btn-small" href="document_view.php?id=<?=(int)$d['id']?>">ABRIR →</a></div>
                </article>
                <?php endforeach;?>
            </div>
        <?php endif;?>
    </section>
</div>
<script>
(function(){
    const root=document.getElementById('dmsLibraryView');
    const buttons=[...document.querySelectorAll('.dms-view-btn')];
    if(!root||!buttons.length)return;
    let view='list';
    try{view=localStorage.getItem('socrates-crm-dms-library-view')||'list';}catch(e){}
    if(!['list','cards'].includes(view))view='list';
    const apply=(next)=>{
        root.dataset.view=next;
        buttons.forEach(b=>b.classList.toggle('is-active',b.dataset.view===next));
        try{localStorage.setItem('socrates-crm-dms-library-view',next);}catch(e){}
    };
    buttons.forEach(b=>b.addEventListener('click',()=>apply(b.dataset.view)));
    apply(view);
})();
</script>
<?php require __DIR__.'/includes/footer.php'; ?>
