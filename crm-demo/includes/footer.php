<?php
$fx=crm_exchange_rates();
$footerVersion=crm_app_version();
$footerLicense=crm_license_info();
$fxUpdated='';
if(!empty($fx['updated_at'])){
    try{$fxUpdated=(new DateTimeImmutable((string)$fx['updated_at']))->setTimezone(new DateTimeZone((string)config('timezone','America/Sao_Paulo')))->format('d/m/Y H:i');}catch(Throwable){}
}
?>
</main>
<footer class="app-footer">
<div class="footer-brand"><img src="assets/img/logo-creative-blanco.png" alt="Sócrates Creative Studio"><span>SÓCRATES CRM · v<?=e($footerVersion)?> · <?=e($footerLicense['label'])?> · <a href="mailto:info@socrates.la">info@socrates.la</a></span></div>
<?php if(!empty($fx['ok'])):?><div class="footer-rates" title="Tasa de referencia; puede diferir del valor bancario o comercial"><strong>USD 1</strong><span>BRL <?=e(number_format((float)$fx['BRL'],4,',','.'))?></span><span>CLP <?=e(number_format((float)$fx['CLP'],2,',','.'))?></span><?php if($fxUpdated):?><small><?=e($fxUpdated)?></small><?php endif;?><a href="https://www.exchangerate-api.com" target="_blank" rel="noopener">Rates By Exchange Rate API</a></div><?php else:?><div class="footer-rates footer-rates-offline"><span>Tipo de cambio temporalmente no disponible</span></div><?php endif;?>
</footer>
</div></div><div class="sidebar-overlay" data-sidebar-overlay></div>

<?php if(has_permission('assistant.use')):?>
<div class="assistant-float assistant-copilot-float" data-assistant-float data-tour-target="assistant-float">
<button class="assistant-float-button" type="button" data-assistant-float-toggle aria-expanded="false" aria-label="SÓCRATES Copilot" title="SÓCRATES Copilot"><span class="assistant-fab-icon"><?=crm_icon('assistant')?></span><span class="assistant-fab-ai">AI</span></button>
<section class="assistant-float-panel" data-assistant-float-panel aria-hidden="true">
<header><span class="assistant-float-avatar"><?=crm_icon('assistant')?><i></i></span><div><strong>SÓCRATES Copilot</strong><small><span class="assistant-online-dot"></span> Conectado a tus módulos y datos</small></div><a href="assistant.php" class="assistant-float-open" title="Abrir Copilot completo">↗</a><button type="button" data-assistant-float-close aria-label="Cerrar">×</button></header>
<div class="assistant-float-context"><button type="button" data-assistant-suggestion data-prompt="¿Qué puedo hacer aquí?">Esta pantalla</button><button type="button" data-assistant-suggestion data-prompt="¿Qué requiere mi atención hoy?">Mis pendientes</button><button type="button" data-assistant-suggestion data-prompt="Dame un resumen ejecutivo">Resumen</button></div>
<div class="assistant-float-messages" data-assistant-float-messages data-assistant-history="float"><div class="assistant-message bot"><div class="assistant-answer-title"><span class="assistant-data-badge tone-green">EN VIVO</span><strong>¿En qué te ayudo?</strong></div><p>Puedo consultar ventas, proyectos, documentos, cobros, agenda y soporte respetando tus permisos.</p></div></div>
<form class="assistant-float-form" data-assistant-float-form><input name="question" placeholder="Pregunta o busca algo…" autocomplete="off" maxlength="900" required><button type="submit" aria-label="Enviar">➤</button></form>
</section>
</div>
<?php endif;?>
<script>
window.SOCRATES_TOUR_CONTEXT = <?=json_encode([
  'page'=>basename((string)($_SERVER['SCRIPT_NAME']??'')),
  'lang'=>crm_current_language(),
  'userId'=>(int)($user['id']??0),
  'licenseStatus'=>(string)($footerLicense['status']??'demo'),
  'permissions'=>[
    'dashboard'=>has_permission('dashboard.view'),
    'clients'=>has_permission('clients.view'),
    'deals'=>has_permission('deals.view'),
    'quotes'=>has_permission('quotes.view'),
    'contracts'=>has_permission('contracts.view'),
    'projects'=>has_permission('projects.view'),
    'project_tasks'=>has_permission('project_tasks.view'),
    'project_time'=>has_permission('project_time.view'),
    'project_risks'=>has_permission('project_risks.view'),
    'project_finance'=>has_permission('project_finance.view'),
    'documents'=>has_permission('documents.view'),
    'billing'=>has_permission('billing.view'),
    'reports'=>has_permission('reports.view'),
    'tickets'=>has_permission('tickets.view'),
    'imports'=>has_permission('imports.view'),
    'users'=>has_permission('users.view'),
    'security'=>has_permission('security.view'),
    'settings'=>has_permission('settings.manage'),
    'manual'=>has_permission('manual.view'),
    'assistant'=>has_permission('assistant.use'),
  ],
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
</script>
<script src="assets/js/app.js?v=<?=e($footerVersion)?>"></script><script src="assets/js/crm15.js?v=<?=e($footerVersion)?>"></script><script src="assets/js/tour.js?v=<?=e($footerVersion)?>"></script></body></html>
