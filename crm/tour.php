<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_login();
$lang=crm_current_language();
$pageTitle=match($lang){'pt'=>'Tour guiado 360°','en'=>'360° Guided Tour',default=>'Tour guiado 360°'};
require __DIR__.'/includes/header.php';
$copy=[
'es'=>[
 'eyebrow'=>'ONBOARDING INTERACTIVO','title'=>'Conoce SÓCRATES CRM usándolo.','lead'=>'Un recorrido virtual sobre las pantallas reales del sistema. El tour navega por los módulos, ilumina cada zona y explica qué ves, para qué sirve y cómo encaja en el flujo completo.','full'=>'INICIAR TOUR COMPLETO','quick'=>'TOUR RÁPIDO','resume'=>'RETOMAR DONDE QUEDÉ','reset'=>'REINICIAR PROGRESO','readonly'=>'Solo lectura durante el recorrido · no crea, mueve ni modifica datos.','chapters'=>'RECORRIDO POR CAPÍTULOS','how'=>'Cómo funciona','resumeTitle'=>'Tu progreso queda guardado','resumeText'=>'Puedes pausar el recorrido, cerrar el navegador y continuar más tarde desde el mismo paso.','startChapter'=>'INICIAR CAPÍTULO','steps'=>'pasos','guide1'=>'Spotlight real','guide1b'=>'La pantalla se oscurece y la función explicada queda iluminada.','guide2'=>'Navegación automática','guide2b'=>'Al avanzar, el tour abre el módulo correcto y continúa sin perder el paso.','guide3'=>'Seguro para demos','guide3b'=>'Durante el recorrido la interfaz queda bloqueada para evitar cambios accidentales.','guide4'=>'Responsive','guide4b'=>'La tarjeta se adapta a escritorio, tablet y celular y también funciona con modo oscuro.','keys'=>'También puedes usar ← / → para avanzar y ESC para pausar.'],
'pt'=>[
 'eyebrow'=>'ONBOARDING INTERATIVO','title'=>'Conheça o SÓCRATES CRM usando o sistema.','lead'=>'Um tour virtual sobre as telas reais do sistema. O tour navega pelos módulos, destaca cada área e explica o que você vê, para que serve e como se encaixa no fluxo completo.','full'=>'INICIAR TOUR COMPLETO','quick'=>'TOUR RÁPIDO','resume'=>'RETOMAR DE ONDE PAREI','reset'=>'REINICIAR PROGRESSO','readonly'=>'Somente leitura durante o tour · não cria, move nem altera dados.','chapters'=>'TOUR POR CAPÍTULOS','how'=>'Como funciona','resumeTitle'=>'Seu progresso fica salvo','resumeText'=>'Você pode pausar o tour, fechar o navegador e continuar mais tarde no mesmo passo.','startChapter'=>'INICIAR CAPÍTULO','steps'=>'passos','guide1'=>'Spotlight real','guide1b'=>'A tela escurece e a função explicada fica iluminada.','guide2'=>'Navegação automática','guide2b'=>'Ao avançar, o tour abre o módulo correto e continua sem perder o passo.','guide3'=>'Seguro para demos','guide3b'=>'Durante o tour a interface fica bloqueada para evitar alterações acidentais.','guide4'=>'Responsivo','guide4b'=>'O cartão se adapta a desktop, tablet e celular e também funciona no modo escuro.','keys'=>'Você também pode usar ← / → para avançar e ESC para pausar.'],
'en'=>[
 'eyebrow'=>'INTERACTIVE ONBOARDING','title'=>'Learn SÓCRATES CRM by using it.','lead'=>'A virtual tour over the real system screens. It navigates modules, highlights each area and explains what you see, why it matters and how it fits the end-to-end workflow.','full'=>'START FULL TOUR','quick'=>'QUICK TOUR','resume'=>'RESUME WHERE I LEFT OFF','reset'=>'RESET PROGRESS','readonly'=>'Read-only during the tour · it does not create, move or change data.','chapters'=>'TOUR BY CHAPTER','how'=>'How it works','resumeTitle'=>'Your progress is saved','resumeText'=>'Pause the tour, close the browser and continue later from the same step.','startChapter'=>'START CHAPTER','steps'=>'steps','guide1'=>'Real spotlight','guide1b'=>'The screen dims while the feature being explained stays highlighted.','guide2'=>'Automatic navigation','guide2b'=>'When you continue, the tour opens the correct module and keeps your place.','guide3'=>'Demo-safe','guide3b'=>'The interface is blocked during the tour to prevent accidental changes.','guide4'=>'Responsive','guide4b'=>'The tour card adapts to desktop, tablet and phone and supports dark mode.','keys'=>'You can also use ← / → to navigate and ESC to pause.']
];
$c=$copy[$lang]??$copy['es'];
$chapters=[
 [1,'01','Orientación','Orientation','Orientação','Dashboard, menú, búsqueda, accesos rápidos, perfil y SÓCRATES Copilot.','Dashboard, menu, search, quick actions, profile and SÓCRATES Copilot.','Dashboard, menu, busca, ações rápidas, perfil e SÓCRATES Copilot.',7,'dashboard'],
 [2,'02','CRM y ventas','CRM & Sales','CRM e vendas','Clientes, pipeline, cotizaciones y contratos.','Clients, pipeline, quotations and contracts.','Clientes, pipeline, cotações e contratos.',8,'deal'],
 [3,'03','Project Manager','Project Manager','Project Manager','Portafolio, Kanban, Gantt, calendario, horas, riesgos y costos.','Portfolio, Kanban, Gantt, calendar, hours, risks and costs.','Portfólio, Kanban, Gantt, calendário, horas, riscos e custos.',10,'project'],
 [4,'04','Gestión documental','Document Management','Gestão documental','Biblioteca, versiones, permisos, aprobaciones, enlaces seguros y auditoría.','Library, versions, permissions, approvals, secure links and audit.','Biblioteca, versões, permissões, aprovações, links seguros e auditoria.',5,'contract'],
 [5,'05','Control y soporte','Control & Support','Controle e suporte','Facturación, informes, importación y Helpdesk con SLA.','Billing, reports, import/export and Helpdesk with SLA.','Faturamento, relatórios, importação e Helpdesk com SLA.',6,'report'],
 [6,'06','Administración','Administration','Administração','Usuarios, seguridad, configuración, licencia y manual.','Users, security, settings, license and manual.','Usuários, segurança, configuração, licença e manual.',6,'settings'],
];
?>
<div class="tour-hub" data-tour-hub>
  <section class="tour-hub-hero">
    <div>
      <span class="eyebrow"><?=e($c['eyebrow'])?> · v<?=e(crm_app_version())?></span>
      <h2><?=e($c['title'])?></h2>
      <p><?=e($c['lead'])?></p>
      <div class="tour-hub-actions">
        <button class="primary" type="button" data-tour-start="full"><?=crm_icon('route')?> <?=e($c['full'])?></button>
        <button type="button" data-tour-start="quick"><?=crm_icon('gantt')?> <?=e($c['quick'])?></button>
        <button type="button" data-tour-resume data-tour-resume-button hidden><?=crm_icon('clock')?> <?=e($c['resume'])?></button>
      </div>
      <p style="font-size:10px;margin-top:14px;color:#a9cbdc">✓ <?=e($c['readonly'])?></p>
    </div>
    <div class="tour-hub-preview" aria-label="Capítulos del tour">
      <?php foreach($chapters as $ch):?><div><b><?=e($ch[1])?></b><span><?=e($lang==='en'?$ch[3]:($lang==='pt'?$ch[4]:$ch[2]))?></span></div><?php endforeach;?>
    </div>
  </section>

  <div class="panel"><div class="panel-header"><div><h2><?=e($c['chapters'])?></h2><span class="table-sub">42 pasos en 6 niveles · Tour rápido disponible</span></div></div><div class="panel-body">
    <div class="tour-chapters">
      <?php foreach($chapters as $ch):$title=$lang==='en'?$ch[3]:($lang==='pt'?$ch[4]:$ch[2]);$desc=$lang==='en'?$ch[6]:($lang==='pt'?$ch[7]:$ch[5]);?>
      <article class="tour-chapter"><span class="tour-chapter-num"><?=e($ch[1])?></span><h3><?=e($title)?></h3><p><?=e($desc)?></p><small><?=e((string)$ch[8])?> <?=e($c['steps'])?></small><button type="button" data-tour-start="full" data-tour-chapter="<?=$ch[0]?>"><?=e($c['startChapter'])?> →</button></article>
      <?php endforeach;?>
    </div>
  </div></div>

  <div class="tour-guide-panel">
    <section class="panel"><div class="panel-header"><h2><?=e($c['how'])?></h2></div><div class="panel-body"><div class="tour-guide-list">
      <div><b>01</b><strong><?=e($c['guide1'])?></strong><small><?=e($c['guide1b'])?></small></div>
      <div><b>02</b><strong><?=e($c['guide2'])?></strong><small><?=e($c['guide2b'])?></small></div>
      <div><b>03</b><strong><?=e($c['guide3'])?></strong><small><?=e($c['guide3b'])?></small></div>
      <div><b>04</b><strong><?=e($c['guide4'])?></strong><small><?=e($c['guide4b'])?></small></div>
    </div><p class="table-sub" style="margin:14px 0 0"><?=e($c['keys'])?></p></div></section>
    <aside class="tour-resume-box"><span>SÓCRATES CRM · TOUR 360°</span><h3><?=e($c['resumeTitle'])?></h3><p><?=e($c['resumeText'])?></p><div><button type="button" data-tour-resume data-tour-resume-button hidden><?=e($c['resume'])?></button><button type="button" data-tour-reset><?=e($c['reset'])?></button></div><small data-tour-state-copy style="display:block;margin-top:12px;color:#91bad0"></small></aside>
  </div>
</div>
<script>
(function(){
  function sync(){
    if(!window.SocratesTour) return;
    const s=window.SocratesTour.state();
    document.querySelectorAll('[data-tour-resume-button]').forEach(b=>b.hidden=!s.started);
    const out=document.querySelector('[data-tour-state-copy]');
    if(out){out.textContent=s.started?((s.active?'Tour activo':'Tour pausado')+' · '+(s.mode==='quick'?'Rápido':'Completo')+' · paso '+((s.index||0)+1)):'';}
  }
  document.addEventListener('socrates-tour-ready',sync);document.addEventListener('socrates-tour-state',sync);window.setTimeout(sync,500);
})();
</script>
<?php require __DIR__.'/includes/footer.php';?>
