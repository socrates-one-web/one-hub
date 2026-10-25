<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('assistant.use');
require_once __DIR__.'/includes/assistant_engine.php';
$pageTitle='SÓCRATES Copilot';
$snapshot=assistant_home_snapshot();
$lang=crm_current_language();
$firstName=trim((string)(current_user()['name']??''));$firstName=explode(' ',$firstName)[0]??'';
$prompt=trim((string)($_GET['prompt']??''));
require __DIR__.'/includes/header.php';
?>
<section class="assistant-pro-hero" data-tour-target="assistant-copilot">
  <div class="assistant-pro-hero-main">
    <span class="assistant-pro-mark"><?=crm_icon('assistant')?><i></i></span>
    <div><span class="assistant-pro-kicker">SÓCRATES CRM · COPILOT</span><h2>Hola <?=e($firstName)?>. ¿Qué necesitas resolver?</h2><p>Consulta datos reales del CRM, encuentra registros, detecta pendientes y abre la acción correcta sin recorrer módulos.</p></div>
  </div>
  <div class="assistant-pro-status"><span><i></i> DATOS EN TIEMPO REAL</span><small>Respeta tus permisos · Sin IA externa</small></div>
</section>

<?php if($snapshot['attention']):?>
<section class="assistant-attention-strip">
  <div class="assistant-attention-label"><span><?=crm_icon('bell')?></span><div><strong>REQUIERE ATENCIÓN</strong><small>Resumen automático de tus pendientes</small></div></div>
  <div class="assistant-attention-items">
    <?php foreach($snapshot['attention'] as$a):?><a class="tone-<?=e($a['tone'])?>" href="<?=e($a['url'])?>"><span><?=crm_icon($a['icon'])?></span><b><?=e($a['value'])?></b><small><?=e($a['label'])?></small></a><?php endforeach;?>
  </div>
</section>
<?php endif;?>

<div class="assistant-pro-layout">
  <section class="panel assistant-pro-chat">
    <header class="assistant-pro-chat-head"><div><span class="assistant-online-dot"></span><strong>SÓCRATES Copilot</strong><small>Contexto: <b data-assistant-context-label><?=e($pageTitle)?></b></small></div><div class="assistant-chat-tools"><button type="button" class="btn btn-light btn-small" data-assistant-clear>Limpiar</button><a class="btn btn-light btn-small" href="manual.php">Manual 360°</a></div></header>
    <div class="assistant-messages assistant-pro-messages" data-assistant-messages data-assistant-history="main">
      <div class="assistant-message bot assistant-welcome-message">
        <div class="assistant-answer-title"><span class="assistant-data-badge tone-green">CONECTADO</span><strong>Puedo trabajar con lo que ya tiene instalado el CRM</strong></div>
        <p>Puedo resumir <strong>ventas, proyectos, documentos, facturación, soporte y agenda</strong>; encontrar registros; explicar la pantalla actual; y llevarte directamente a formularios y acciones permitidas.</p>
        <div class="assistant-start-grid">
          <button type="button" data-assistant-suggestion>¿Qué requiere mi atención hoy?</button>
          <button type="button" data-assistant-suggestion>Dame un resumen ejecutivo</button>
          <button type="button" data-assistant-suggestion>¿Qué puedo hacer aquí?</button>
          <button type="button" data-assistant-suggestion>¿Qué puedes hacer por mí?</button>
        </div>
      </div>
    </div>
    <form class="assistant-input assistant-pro-composer" data-assistant-form>
      <span class="assistant-composer-icon"><?=crm_icon('assistant')?></span>
      <input name="question" value="<?=e($prompt)?>" placeholder="Ej: ¿Qué proyectos están en riesgo? · Buscar cliente Acme · ¿Qué debo aprobar?" autocomplete="off" maxlength="900" required>
      <button class="btn btn-primary" type="submit"><?=crm_icon('search')?> <span>PREGUNTAR</span></button>
    </form>
    <div class="assistant-pro-foot"><span><?=crm_icon('shield')?> Solo consulta datos que tu usuario puede ver.</span><span>Las altas y cambios se confirman en el módulo correspondiente.</span></div>
  </section>

  <aside class="assistant-pro-side">
    <section class="panel assistant-pro-panel">
      <div class="panel-header"><div><h3>Accesos inteligentes</h3><span class="table-sub">Preguntas frecuentes con datos reales</span></div></div>
      <div class="assistant-smart-prompts">
        <?php foreach([
          ['dashboard','Mi día','¿Qué requiere mi atención hoy?'],
          ['deal','Ventas','Resume el pipeline'],
          ['project','Proyectos','Muéstrame proyectos en riesgo'],
          ['documents','Documentos','¿Qué documentos debo aprobar?'],
          ['billing','Finanzas','¿Qué cobros están vencidos?'],
          ['support','Soporte','¿Qué tickets requieren atención?'],
          ['search','Buscar','Buscar cliente '],
        ] as[$icon,$label,$q]):?><button type="button" data-assistant-suggestion data-prompt="<?=e($q)?>"><span><?=crm_icon($icon)?></span><div><strong><?=e($label)?></strong><small><?=e($q)?></small></div><b>→</b></button><?php endforeach;?>
      </div>
    </section>

    <section class="panel assistant-pro-panel">
      <div class="panel-header"><div><h3>Módulos conectados</h3><span class="table-sub">Según instalación y permisos</span></div><span class="badge badge-blue"><?=count($snapshot['capabilities'])?></span></div>
      <div class="assistant-module-list">
        <?php foreach($snapshot['capabilities'] as$c):?><a href="<?=e($c['page'])?>"><span><?=crm_icon($c['icon'])?></span><div><strong><?=e($c['name'])?></strong><small><?=e($c['desc'])?></small></div></a><?php endforeach;?>
      </div>
    </section>
  </aside>
</div>
<script>window.SOCRATES_ASSISTANT_AUTORUN=<?=json_encode($prompt!==''?$prompt:null,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;</script>
<?php require __DIR__.'/includes/footer.php';?>
