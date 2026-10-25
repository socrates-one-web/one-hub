<?php
declare(strict_types=1);

require __DIR__.'/includes/bootstrap.php';
require_login();

$id=(int)($_GET['id']??0);
require_permission('campaigns',$id?'edit':'create');
$tid=current_tenant_id();

$c=['id'=>0,'name'=>'','subject'=>'','preheader'=>'','from_name'=>'','from_email'=>'','list_id'=>null,'blocks_json'=>'[]','html'=>'','status'=>'draft'];
if($id){
    $st=db()->prepare('SELECT * FROM campaigns WHERE id=? AND tenant_id=?');
    $st->execute([$id,$tid]);
    $c=$st->fetch()?:$c;
    if(!$c['id']){flash('error','Campaña no encontrada.');redirect('index.php?route=campaigns');}
    if(!campaign_lists_are_authorized($id,$tid)){flash('error','Esta campaña usa listas no autorizadas para tu usuario.');redirect('index.php?route=campaigns');}
}else{
    try{
        $st=db()->prepare('SELECT default_from_name,default_from_email FROM settings WHERE tenant_id=?');
        $st->execute([$tid]);$cfg=$st->fetch();
        if($cfg){$c['from_name']=(string)($cfg['default_from_name']??'');$c['from_email']=(string)($cfg['default_from_email']??'');}
    }catch(Throwable $ignore){}
}

$allowed=user_allowed_list_ids($tid);
$sql='SELECT l.*,rc.company client_company,(SELECT COUNT(*) FROM contacts ct WHERE ct.list_id=l.id AND ct.status="active") active_count FROM contact_lists l LEFT JOIN report_clients rc ON rc.id=l.client_id WHERE l.tenant_id=?';
$params=[$tid];
if($allowed!==null){
    if($allowed){$sql.=' AND l.id IN ('.implode(',',array_fill(0,count($allowed),'?')).')';$params=array_merge($params,$allowed);}
    else{$sql.=' AND 1=0';}
}
$sql.=' ORDER BY COALESCE(rc.company,"ZZZZ"),l.name';
$ls=db()->prepare($sql);$ls->execute($params);$lists=$ls->fetchAll();
$selected=$id?campaign_selected_list_ids($id,$c['list_id']?:(null)):[];

$flash=$_SESSION['flash']??null;unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($id?'Editar campaña':'Nueva campaña')?> · EMAILSEND ONE</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/campaign-builder3.css?v=<?=@filemtime(__DIR__.'/assets/campaign-builder3.css')?>">
</head>
<body>
<div class="cb3-app">
<aside class="cb3-nav">
  <div class="cb3-logo"><img src="assets/logo-emailsend.png" alt="EMAILSEND"></div>
  <small>PLATAFORMA</small>
  <a href="index.php?route=dashboard"><span class="ico">⌂</span>Dashboard</a>
  <a class="active" href="index.php?route=campaigns"><span class="ico">✉</span>Campañas</a>
  <a href="index.php?route=contacts"><span class="ico">♧</span>Contactos</a>
  <a href="index.php?route=reports"><span class="ico">▥</span>Reportes</a>
  <?php if(is_admin()):?><a href="index.php?route=clients"><span class="ico">▣</span>Clientes / Portal</a><a href="index.php?route=users"><span class="ico">♙</span>Usuarios / Permisos</a><a href="index.php?route=settings"><span class="ico">⚙</span>Configuración</a><?php endif?>
  <div class="cb3-nav-foot"><strong>CAMPAIGN BUILDER 3 STUDIO</strong>EMAILSEND ONE · v0.13.3</div>
</aside>

<main class="cb3-main">
  <div class="cb3-topbar">
    <div><span class="cb3-eyebrow">EMAILSEND ONE · CAMPAIGN BUILDER 3 STUDIO</span><h1><?=$id?'Editar campaña':'Nueva campaña'?></h1><p>Editor PRO · columnas anidadas · drag resize · biblioteca profesional.</p></div>
    <div class="cb3-actions">
      <span id="cb3Dirty" class="cb3-saved">Editor listo</span>
      <a class="cb3-btn" href="index.php?route=campaigns">← Campañas</a>
      <?php if($id):?><a class="cb3-btn" target="_blank" href="index.php?route=campaign_preview&id=<?=$id?>">Preview guardado</a><?php endif?>
      <button type="button" class="cb3-btn" id="cb3Undo">↶ Deshacer</button>
      <button type="button" class="cb3-btn" id="cb3Redo">↷ Rehacer</button>
      <button type="button" class="cb3-btn dark" id="cb3Html">&lt;/&gt; HTML</button>
      <button type="button" class="cb3-btn blue" id="cb3Preview">◉ Previsualizar</button>
      <button type="submit" form="cb3Form" class="cb3-btn orange">▣ Guardar</button>
    </div>
  </div>

  <?php if($flash):?><div class="cb3-alert <?=e(($flash['type']??'')==='ok'?'ok':'err')?>"><?=e($flash['message']??'')?></div><?php endif?>

  <form method="post" action="builder3-save.php" id="cb3Form">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="id" value="<?=$c['id']?>">
    <input type="hidden" name="blocks_json" id="cb3Blocks" value="<?=e($c['blocks_json']?:'[]')?>">

    <section class="cb3-setup">
      <div class="cb3-setup-head"><div><span class="cb3-eyebrow">CONFIGURACIÓN</span><h2>Datos de la campaña</h2></div><span class="cb3-badge"><?=e($c['status']??'draft')?></span></div>
      <div class="cb3-fields">
        <label class="cb3-field">Nombre interno<input name="name" value="<?=e($c['name'])?>" required></label>
        <label class="cb3-field">Asunto<input name="subject" value="<?=e($c['subject'])?>" required></label>
        <label class="cb3-field">Preheader<input name="preheader" value="<?=e($c['preheader'])?>"></label>
        <label class="cb3-field">Nombre remitente<input name="from_name" value="<?=e($c['from_name'])?>"></label>
        <label class="cb3-field">Email remitente<input type="email" name="from_email" value="<?=e($c['from_email'])?>"></label>
        <label class="cb3-field">Audiencia
          <details class="cb3-audience">
            <summary><span>Listas</span><b id="cb3AudienceCount">Seleccionar</b></summary>
            <div class="cb3-audience-pop"><div class="cb3-list">
            <?php foreach($lists as $l):?><label><input type="checkbox" name="list_ids[]" value="<?=$l['id']?>" data-count="<?=$l['active_count']?>" <?=in_array((int)$l['id'],$selected,true)?'checked':''?>><span><strong><?=e($l['name'])?></strong><small><?=e($l['client_company']??'Sin cliente')?> · <?=$l['active_count']?> activos</small></span></label><?php endforeach?>
            <?php if(!$lists):?><div>No hay listas disponibles.</div><?php endif?>
            </div></div>
          </details>
        </label>
      </div>
    </section>

    <section class="cb3-builder" id="cb3Root">
      <aside class="cb3-palette">
        <h3>Elementos</h3><div class="cb3-sub">Clic o arrastra al lienzo</div>
        <div class="cb3-group"><div class="cb3-group-title">CONTENIDO</div><div class="cb3-grid">
          <button type="button" class="cb3-add" data-add="hero"><b>▣</b>Hero</button>
          <button type="button" class="cb3-add" data-add="title"><b>H</b>Título</button>
          <button type="button" class="cb3-add" data-add="text"><b>T</b>Texto</button>
          <button type="button" class="cb3-add orange" data-add="image"><b>▧</b>Imagen</button>
          <button type="button" class="cb3-add orange" data-add="button"><b>▰</b>Botón</button>
          <button type="button" class="cb3-add" data-add="card"><b>▤</b>Tarjeta</button>
          <button type="button" class="cb3-add" data-add="quote"><b>“</b>Destacado</button>
          <button type="button" class="cb3-add" data-add="logo"><b>◈</b>Logo</button>
          <button type="button" class="cb3-add" data-add="header"><b>☰</b>Header</button>
          <button type="button" class="cb3-add" data-add="banner"><b>▣</b>Banner</button>
          <button type="button" class="cb3-add" data-add="iconbox"><b>★</b>Icon Box</button>
          <button type="button" class="cb3-add" data-add="video"><b>▶</b>Video</button>
          <button type="button" class="cb3-add orange" data-add="product"><b>▣</b>Producto</button>
          <button type="button" class="cb3-add" data-add="testimonial"><b>★</b>Testimonio</button>
          <button type="button" class="cb3-add green" data-add="stats"><b>▥</b>Estadísticas</button>
          <button type="button" class="cb3-add green" data-add="iconlist"><b>✓</b>Lista Iconos</button>
          <button type="button" class="cb3-add" data-add="pricing"><b>$</b>Pricing</button>
          <button type="button" class="cb3-add" data-add="qr"><b>▦</b>QR</button>
          <button type="button" class="cb3-add" data-add="social"><b>●</b>Social</button>
          <button type="button" class="cb3-add" data-add="footer"><b>▿</b>Footer</button>
          <button type="button" class="cb3-add" data-add="html"><b>&lt;/&gt;</b>HTML</button>
        </div></div>
        <div class="cb3-group"><div class="cb3-group-title">ESTRUCTURA</div><div class="cb3-grid">
          <button type="button" class="cb3-add" data-add="columns1"><b>▯</b>1 columna</button>
          <button type="button" class="cb3-add" data-add="columns"><b>▯▯</b>2 columnas</button>
          <button type="button" class="cb3-add" data-add="columns3"><b>▯▯▯</b>3 columnas</button>
          <button type="button" class="cb3-add green" data-add="divider"><b>—</b>Separador</button>
          <button type="button" class="cb3-add green" data-add="spacer"><b>↕</b>Espacio</button>
        </div></div>
        <div class="cb3-group"><div class="cb3-group-title">PLANTILLAS</div>
          <button type="button" class="cb3-template" data-template="promo"><strong>Promoción Premium</strong><small>Hero + beneficios + CTA</small></button>
          <button type="button" class="cb3-template" data-template="newsletter"><strong>Newsletter Editorial</strong><small>Noticias + imagen + contenido</small></button>
          <button type="button" class="cb3-template" data-template="corporate"><strong>Corporativa PRO</strong><small>Hero + métricas + columnas</small></button>
          <button type="button" class="cb3-template" data-template="product"><strong>Lanzamiento Producto</strong><small>Producto + prueba social</small></button>
          <button type="button" class="cb3-template" data-template="event"><strong>Evento / Invitación</strong><small>Fecha + beneficios + registro</small></button>
          <button type="button" class="cb3-template" data-template="services"><strong>Servicios 3 Columnas</strong><small>Componentes dentro de columnas</small></button>
          <button type="button" class="cb3-template" data-template="blackfriday"><strong>Black Friday / Sale</strong><small>Campaña comercial de impacto</small></button>
          <button type="button" class="cb3-template" data-template="webinar"><strong>Webinar / Video</strong><small>Video + métricas + CTA</small></button>
          <button type="button" class="cb3-template" data-template="followup"><strong>Follow-up Comercial</strong><small>Seguimiento profesional</small></button>
          <button type="button" class="cb3-template" data-template="minimal"><strong>Minimal Elegante</strong><small>Diseño limpio y directo</small></button>
        </div>
      </aside>

      <div class="cb3-center">
        <div class="cb3-canvasbar"><strong>Vista del email · ancho editable</strong><div class="cb3-device-actions"><button type="button" id="cb3Document">⚙ Documento</button><button type="button" class="active" id="cb3Desktop">Desktop</button><button type="button" id="cb3Mobile">Móvil</button></div></div>
        <div class="cb3-stage"><div class="cb3-device" id="cb3Device"><div class="cb3-canvas" id="cb3Canvas"></div></div></div>
      </div>

      <aside class="cb3-inspector" id="cb3Inspector"></aside>
    </section>
  </form>
</main>
</div>

<div class="cb3-preview-modal" id="cb3PreviewModal"><div class="cb3-modal"><div class="cb3-modal-head"><strong>Vista previa HTML final</strong><button type="button" class="cb3-btn" data-preview-close>× Cerrar</button></div><div class="cb3-modal-body"><iframe name="cb3PreviewFrame" title="Preview"></iframe></div><div class="cb3-modal-foot"><button type="button" class="cb3-btn orange" data-preview-close>Volver al editor</button></div></div></div>
<form method="post" action="builder3-preview.php" target="cb3PreviewFrame" id="cb3PreviewForm" hidden><input type="hidden" name="csrf" value="<?=csrf_token()?>"><textarea name="blocks_json" id="cb3PreviewJson"></textarea></form>

<div class="cb3-html-modal" id="cb3HtmlModal"><div class="cb3-modal"><div class="cb3-modal-head"><strong>Agregar HTML personalizado</strong><button type="button" class="cb3-btn" data-html-close>× Cerrar</button></div><div class="cb3-html-body"><textarea id="cb3HtmlCode" placeholder="<table>...</table>"></textarea></div><div class="cb3-modal-foot"><button type="button" class="cb3-btn" data-html-close>Cancelar</button><button type="button" class="cb3-btn orange" id="cb3AddHtml">Agregar bloque HTML</button></div></div></div>

<script src="assets/campaign-builder3.js?v=<?=@filemtime(__DIR__.'/assets/campaign-builder3.js')?>"></script>
</body>
</html>
