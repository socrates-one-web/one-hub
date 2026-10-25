<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php';
require_login();
$user=current_user();
$crmVersion=crm_app_version();
$licenseInfo=crm_license_info();
$companyName=crm_company_name();$companyLogo=crm_company_logo_path();
$lang=crm_current_language();
$searchPlaceholder=match($lang){'pt'=>'Pesquisar no CRM…','en'=>'Search CRM…',default=>'Buscar en el CRM…'};
$pageTitle=crm_translate_text((string)($pageTitle??t('dashboard')));
$currentPage=basename((string)($_SERVER['SCRIPT_NAME']??''));
$flashes=pull_flashes();
$unreadCount=has_permission('notifications.view')?unread_notifications_count((int)$user['id']):0;
$pageIcon=crm_page_icon($currentPage);
if ($lang !== 'es' && empty($GLOBALS['crm_ui_translation_buffer'])) {
    $GLOBALS['crm_ui_translation_buffer'] = true;
    ob_start('crm_translate_rendered_html');
}
$active=function(array $pages)use($currentPage):string{return in_array($currentPage,$pages,true)?'active':'';};
$clientPages=['clients.php','client_form.php','client_view.php'];
$contactPages=['contacts.php','contact_form.php','contact_view.php'];
$activityPages=['activities.php','activity_form.php'];
$dealPages=['deals.php','deal_flow.php','deal_form.php','deal_view.php','deal_move.php'];
$catalogPages=['quote_catalog.php','service_catalog.php','service_catalog_form.php','service_catalog_export.php','service_categories.php','service_category_form.php','service_category_toggle.php','payment_methods.php','payment_method_form.php','payment_method_send.php'];
$servicePages=['services.php','service_form.php','service_renew.php'];
$quotePages=['quotations.php','quotation_form.php','quotation_view.php','quotation_send.php','quotation_print.php','quotation_assign.php'];
$contractPages=['contracts.php','contract_form.php','contract_view.php','contract_send.php','contract_pdf.php'];
$contractTemplatePages=['contract_templates.php','contract_template_form.php','contract_template_toggle.php'];
$marketingPages=['emailsend.php','mailing.php','mailing_form.php','mailing_view.php','mailing_send.php','lead_campaigns.php','lead_campaign_form.php','lead_forms.php','lead_form_builder.php','lead_submissions.php','lead_form_public.php'];
$appointmentPages=['appointments.php','appointment_form.php','appointment_send.php'];
$billingPages=['billing.php','billing_services.php','billing_charge_pay.php','payments.php','payment_form.php'];
$supportPages=['support.php','support_ticket_form.php','support_ticket_view.php'];
$projectPages=['project_dashboard.php','projects.php','project_form.php','project_view.php','project_members.php','project_milestone_form.php','project_kanban.php','project_task_form.php','project_gantt.php','project_calendar.php','project_timesheet.php','project_time_form.php','project_risks.php','project_risk_form.php','project_expenses.php','project_expense_form.php'];
$documentPages=['document_dashboard.php','documents.php','document_upload.php','document_view.php','document_approvals.php','document_folders.php','document_routes.php','document_audit.php','document_trash.php'];
$documentIntegrationPages=array_merge($documentPages,['client_view.php','project_view.php','quotation_view.php','contract_view.php','support_ticket_view.php']);
$userPages=['users.php','user_form.php'];
?>
<!doctype html>
<html lang="<?=e($lang)?>" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="<?=e(csrf_token())?>">
<title><?=e($pageTitle)?> · <?=e(config('app_name'))?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700;800&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css?v=<?=e($crmVersion)?>">
<script>try{document.documentElement.dataset.theme=localStorage.getItem('soc-crm-theme')||'light'}catch(e){}</script>
<?php if(in_array($currentPage,$documentIntegrationPages,true)):?><link rel="stylesheet" href="assets/css/document-manager.css?v=<?=e($crmVersion)?>"><?php endif;?>
</head>
<body>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
<a class="company-brand" href="dashboard.php" title="<?=e($companyName)?>"><?php if($companyLogo):?><img src="<?=e($companyLogo)?>" alt="<?=e($companyName)?>"><?php else:?><span class="company-brand-mark"><?=e(crm_upper(crm_substr($companyName,0,1)))?></span><span class="company-brand-text"><strong><?=e($companyName)?></strong><small>Gestión comercial y proyectos</small></span><?php endif;?></a>
<div class="sidebar-nav-scroll" aria-label="Navegación principal">
<nav class="nav-list nav-list-organized">
<div class="nav-section nav-section-home">
<span class="nav-section-label">INICIO</span>
<?php if(has_permission('dashboard.view')):?><a class="<?=$active(['dashboard.php'])?>" href="dashboard.php"><span class="nav-icon icon-blue"><?=crm_icon('dashboard')?></span><span><?=e(t('dashboard'))?></span></a><?php endif;?>
</div>

<?php if(has_any_permission(['clients.view','contacts.view','activities.view','deals.view','mailing.view','leads.view','forms.view','appointments.view'])):?><div class="nav-section"><span class="nav-section-label">COMERCIAL</span><div class="nav-group <?=$active(array_merge($clientPages,$contactPages,$activityPages,$dealPages,$marketingPages,$appointmentPages))?>">
<button type="button" class="nav-group-trigger"><span class="nav-icon icon-cyan"><?=crm_icon('clients')?></span><span>CRM y ventas</span><?=crm_icon('chevron','nav-chevron')?></button>
<div class="nav-flyout nav-flyout-wide">
<div class="nav-flyout-title"><strong>CRM Y VENTAS</strong><small>Relaciones, oportunidades y captación</small></div>
<?php if(has_permission('clients.view')):?><a class="<?=$active($clientPages)?>" href="clients.php"><?=crm_icon('clients')?> <span>Clientes</span></a><?php endif;?>
<?php if(has_permission('contacts.view')):?><a class="<?=$active($contactPages)?>" href="contacts.php"><?=crm_icon('contact')?> <span>Contactos</span></a><?php endif;?>
<?php if(has_permission('activities.view')):?><a class="<?=$active($activityPages)?>" href="activities.php"><?=crm_icon('activity')?> <span>Actividades y tareas</span></a><?php endif;?>
<?php if(has_permission('deals.view')):?><a class="<?=$active($dealPages)?>" href="deals.php"><?=crm_icon('kanban')?> <span>Negocios / Pipeline</span></a><?php endif;?>
<?php if(has_any_permission(['mailing.view','leads.view','forms.view'])):?><div class="nav-flyout-separator">MARKETING Y CAPTACIÓN</div><?php endif;?>
<?php if(has_permission('mailing.view')):?><a class="<?=$active(['mailing.php','mailing_form.php','mailing_view.php','mailing_send.php'])?>" href="mailing.php"><?=crm_icon('mail')?> <span>Mailing</span></a><?php endif;?>
<?php if(has_permission('mailing.view')):?><a class="<?=$active(['emailsend.php'])?>" href="emailsend.php"><?=crm_icon('mail')?> <span>EMAILSEND ONE · Campañas</span></a><?php endif;?>
<?php if(has_permission('leads.view')):?><a class="<?=$active(['lead_campaigns.php','lead_campaign_form.php'])?>" href="lead_campaigns.php"><?=crm_icon('lead')?> <span>Campañas de leads</span></a><?php endif;?>
<?php if(has_permission('forms.view')):?><a class="<?=$active(['lead_forms.php','lead_form_builder.php','lead_submissions.php'])?>" href="lead_forms.php"><?=crm_icon('form')?> <span>Formularios de captación</span></a><?php endif;?>
<?php if(has_permission('appointments.view')):?><a class="<?=$active($appointmentPages)?>" href="appointments.php"><?=crm_icon('calendar')?> <span>Citas y reuniones</span></a><?php endif;?>
</div></div></div><?php endif;?>

<?php if(has_any_permission(['catalog.view','catalog_categories.view','payment_methods.view','quotes.view','contracts.view','contract_templates.view'])):?><div class="nav-section"><span class="nav-section-label">VENTAS</span><div class="nav-group <?=$active(array_merge($catalogPages,$quotePages,$contractPages,$contractTemplatePages))?>">
<button type="button" class="nav-group-trigger"><span class="nav-icon icon-yellow"><?=crm_icon('quote')?></span><span>Cotizaciones y documentos</span><?=crm_icon('chevron','nav-chevron')?></button>
<div class="nav-flyout nav-flyout-wide">
<div class="nav-flyout-title"><strong>COTIZACIONES Y DOCUMENTOS</strong><small>Catálogo, propuestas y contratos</small></div>
<?php if(has_permission('catalog.view')):?><a class="<?=$active(['quote_catalog.php'])?>" href="quote_catalog.php"><?=crm_icon('dashboard')?> <span>Resumen del catálogo</span></a><a class="<?=$active(['service_catalog.php','service_catalog_form.php','service_catalog_export.php'])?>" href="service_catalog.php"><?=crm_icon('catalog')?> <span>Productos y servicios</span></a><?php endif;?>
<?php if(has_permission('catalog_categories.view')):?><a class="<?=$active(['service_categories.php','service_category_form.php'])?>" href="service_categories.php"><?=crm_icon('catalog')?> <span>Categorías</span></a><?php endif;?>
<?php if(has_permission('payment_methods.view')):?><a class="<?=$active(['payment_methods.php','payment_method_form.php','payment_method_send.php'])?>" href="payment_methods.php"><?=crm_icon('payment')?> <span>Formas de pago</span></a><?php endif;?>
<?php if(has_permission('quotes.view')):?><div class="nav-flyout-separator">PROPUESTAS</div><a class="<?=$active($quotePages)?>" href="quotations.php"><?=crm_icon('quote')?> <span>Cotizaciones</span></a><?php endif;?>
<?php if(has_permission('contracts.view')):?><a class="<?=$active($contractPages)?>" href="contracts.php"><?=crm_icon('contract')?> <span>Contratos</span></a><?php endif;?>
<?php if(has_permission('contract_templates.view')):?><a class="<?=$active($contractTemplatePages)?>" href="contract_templates.php"><?=crm_icon('form')?> <span>Plantillas de contrato</span></a><?php endif;?>
</div></div></div><?php endif;?>

<?php if(has_any_permission(['services.view','projects.view','project_tasks.view','project_time.view','project_risks.view','project_finance.view'])):?><div class="nav-section"><span class="nav-section-label">OPERACIÓN</span><div class="nav-group <?=$active(array_merge($servicePages,['expirations.php','my_expirations.php'],$projectPages))?>">
<button type="button" class="nav-group-trigger"><span class="nav-icon icon-green"><?=crm_icon('project')?></span><span>Servicios y proyectos</span><?=crm_icon('chevron','nav-chevron')?></button>
<div class="nav-flyout nav-flyout-wide">
<div class="nav-flyout-title"><strong>OPERACIÓN Y ENTREGA</strong><small>Servicios recurrentes + Project Manager</small></div>
<?php if(has_permission('services.view')):?><a class="<?=$active($servicePages)?>" href="services.php"><?=crm_icon('services')?> <span>Servicios contratados</span></a><a class="<?=$active(['expirations.php','my_expirations.php'])?>" href="expirations.php"><?=crm_icon('clock')?> <span>Vencimientos y renovaciones</span></a><?php endif;?>
<?php if(has_any_permission(['projects.view','project_tasks.view','project_time.view','project_risks.view','project_finance.view'])):?><div class="nav-flyout-separator">PROJECT MANAGER</div><?php endif;?>
<?php if(has_permission('projects.view')):?><a class="<?=$active(['project_dashboard.php'])?>" href="project_dashboard.php"><?=crm_icon('dashboard')?> <span>Dashboard de proyectos</span></a><a class="<?=$active(['projects.php','project_form.php','project_view.php','project_members.php','project_milestone_form.php'])?>" href="projects.php"><?=crm_icon('project')?> <span>Portafolio de proyectos</span></a><?php endif;?>
<?php if(has_permission('project_tasks.view')):?><a class="<?=$active(['project_kanban.php','project_task_form.php'])?>" href="project_kanban.php"><?=crm_icon('kanban')?> <span>Kanban de tareas</span></a><a class="<?=$active(['project_gantt.php'])?>" href="project_gantt.php"><?=crm_icon('gantt')?> <span>Gantt / Project</span></a><a class="<?=$active(['project_calendar.php'])?>" href="project_calendar.php"><?=crm_icon('calendar')?> <span>Calendario</span></a><?php endif;?>
<?php if(has_permission('project_time.view')):?><a class="<?=$active(['project_timesheet.php','project_time_form.php'])?>" href="project_timesheet.php"><?=crm_icon('time')?> <span>Horas y recursos</span></a><?php endif;?>
<?php if(has_permission('project_risks.view')):?><a class="<?=$active(['project_risks.php','project_risk_form.php'])?>" href="project_risks.php"><?=crm_icon('risk')?> <span>Riesgos</span></a><?php endif;?>
<?php if(has_permission('project_finance.view')):?><a class="<?=$active(['project_expenses.php','project_expense_form.php'])?>" href="project_expenses.php"><?=crm_icon('billing')?> <span>Costos y gastos</span></a><?php endif;?>
</div></div></div><?php endif;?>

<?php if(has_permission('documents.view')):?><div class="nav-section"><span class="nav-section-label">DOCUMENTOS</span><div class="nav-group <?=$active($documentPages)?>">
<button type="button" class="nav-group-trigger"><span class="nav-icon icon-cyan"><?=crm_icon('documents')?></span><span>Gestión documental</span><?=crm_icon('chevron','nav-chevron')?></button>
<div class="nav-flyout nav-flyout-wide">
<div class="nav-flyout-title"><strong>GESTIÓN DOCUMENTAL</strong><small>Biblioteca, versiones, permisos, aprobaciones y trazabilidad</small></div>
<a class="<?=$active(['document_dashboard.php'])?>" href="document_dashboard.php"><?=crm_icon('dashboard')?> <span>Dashboard documental</span></a>
<a class="<?=$active(['documents.php','document_view.php'])?>" href="documents.php"><?=crm_icon('contract')?> <span>Biblioteca de documentos</span></a>
<?php if(has_permission('documents.upload')):?><a class="<?=$active(['document_upload.php'])?>" href="document_upload.php"><?=crm_icon('plus')?> <span>Subir documentos</span></a><?php endif;?>
<div class="nav-flyout-separator">VISTAS PERSONALES</div>
<a href="documents.php?mine=1"><?=crm_icon('users')?> <span>Mis documentos</span></a>
<a href="documents.php?shared=1"><?=crm_icon('share')?> <span>Compartidos conmigo</span></a>
<a href="documents.php?favorite=1"><?=crm_icon('project')?> <span>Favoritos</span></a>
<a href="documents.php?expiring=1"><?=crm_icon('risk')?> <span>Por vencer</span></a>
<?php if(has_permission('documents.approve')):?><div class="nav-flyout-separator">FLUJOS</div><a class="<?=$active(['document_approvals.php'])?>" href="document_approvals.php"><?=crm_icon('shield')?> <span>Aprobaciones pendientes</span></a><?php endif;?>
<?php if(has_permission('documents.manage')):?><a class="<?=$active(['document_folders.php'])?>" href="document_folders.php"><?=crm_icon('catalog')?> <span>Carpetas y organización</span></a><a class="<?=$active(['document_routes.php'])?>" href="document_routes.php"><?=crm_icon('route')?> <span>Rutas de aprobación</span></a><?php endif;?>
<?php if(has_permission('documents.audit')):?><a class="<?=$active(['document_audit.php'])?>" href="document_audit.php"><?=crm_icon('report')?> <span>Auditoría documental</span></a><?php endif;?>
<a class="<?=$active(['document_trash.php'])?>" href="document_trash.php"><?=crm_icon('trash')?> <span>Papelera</span></a>
</div></div></div><?php endif;?>

<?php if(has_any_permission(['billing.view','payments.view','reports.view'])):?><div class="nav-section"><span class="nav-section-label">CONTROL</span><div class="nav-group <?=$active(array_merge($billingPages,['reports.php']))?>">
<button type="button" class="nav-group-trigger"><span class="nav-icon icon-teal"><?=crm_icon('billing')?></span><span>Finanzas e informes</span><?=crm_icon('chevron','nav-chevron')?></button>
<div class="nav-flyout nav-flyout-wide">
<div class="nav-flyout-title"><strong>FINANZAS E INFORMES</strong><small>Cobros, ingresos y visión ejecutiva</small></div>
<?php if(has_permission('billing.view')):?><a class="<?=$active(['billing.php'])?>" href="billing.php"><?=crm_icon('billing')?> <span>Resumen de facturación</span></a><a class="<?=$active(['billing_services.php','billing_charge_pay.php'])?>" href="billing_services.php"><?=crm_icon('clock')?> <span>Ciclos de cobro y renovaciones</span></a><?php endif;?>
<?php if(has_permission('payments.view')):?><a class="<?=$active(['payments.php','payment_form.php'])?>" href="payments.php"><?=crm_icon('payment')?> <span>Ingresos registrados</span></a><?php endif;?>
<?php if(has_permission('reports.view')):?><a class="<?=$active(['reports.php'])?>" href="reports.php"><?=crm_icon('report')?> <span>Panel de informes</span></a><?php endif;?>
<?php if(has_permission('billing.view')):?><div class="nav-flyout-separator">FACTURACIÓN EXTERNA</div><a href="https://www.facto.cl" target="_blank" rel="noopener">🇨🇱 <span>Chile · Facto ↗</span></a><a href="https://www.esnfs.com.br/" target="_blank" rel="noopener">🇧🇷 <span>Brasil · NFS-e ↗</span></a><a href="https://www.paypal.com" target="_blank" rel="noopener">🌐 <span>PayPal ↗</span></a><a href="https://dashboard.stripe.com/login" target="_blank" rel="noopener">💳 <span>Stripe ↗</span></a><?php endif;?>
</div></div></div><?php endif;?>

<?php if(has_any_permission(['imports.view','tickets.view','manual.view','assistant.use'])):?><div class="nav-section"><span class="nav-section-label">AYUDA</span><div class="nav-group <?=$active(array_merge(['data_tools.php'],$supportPages,['assistant.php','manual.php','tour.php']))?>">
<button type="button" class="nav-group-trigger"><span class="nav-icon icon-blue"><?=crm_icon('manual')?></span><span>Herramientas y ayuda</span><?=crm_icon('chevron','nav-chevron')?></button>
<div class="nav-flyout">
<div class="nav-flyout-title"><strong>HERRAMIENTAS Y AYUDA</strong><small>Datos, soporte y aprendizaje</small></div>
<?php if(has_permission('imports.view')):?><a class="<?=$active(['data_tools.php'])?>" href="data_tools.php"><?=crm_icon('import')?> <span>Importar y exportar</span></a><?php endif;?>
<?php if(has_permission('tickets.view')):?><a class="<?=$active($supportPages)?>" href="support.php"><?=crm_icon('support')?> <span>Soporte / Tickets</span></a><?php endif;?>
<?php if(has_permission('assistant.use')):?><a class="<?=$active(['assistant.php'])?>" href="assistant.php"><?=crm_icon('assistant')?> <span>SÓCRATES Copilot</span></a><?php endif;?>
<?php if(has_permission('manual.view')):?><a class="<?=$active(['manual.php'])?>" href="manual.php"><?=crm_icon('manual')?> <span>Centro de ayuda visual</span></a><?php endif;?><a class="<?=$active(['tour.php'])?>" href="tour.php"><?=crm_icon('route')?> <span>Tour guiado 360°</span></a>
</div></div></div><?php endif;?>

<?php if(has_any_permission(['users.view','security.view','settings.manage'])):?><div class="nav-section"><span class="nav-section-label">SISTEMA</span><div class="nav-group <?=$active(array_merge($userPages,['security_center.php','security_2fa.php','settings.php','license.php']))?>">
<button type="button" class="nav-group-trigger"><span class="nav-icon icon-gray"><?=crm_icon('settings')?></span><span>Administración</span><?=crm_icon('chevron','nav-chevron')?></button>
<div class="nav-flyout">
<div class="nav-flyout-title"><strong>ADMINISTRACIÓN</strong><small>Accesos, seguridad y configuración</small></div>
<?php if(has_permission('users.view')):?><a class="<?=$active($userPages)?>" href="users.php"><?=crm_icon('users')?> <span>Usuarios y permisos</span></a><?php endif;?>
<?php if(has_permission('security.view')):?><a class="<?=$active(['security_center.php','security_2fa.php'])?>" href="security_center.php"><?=crm_icon('shield')?> <span>Centro de Seguridad</span></a><?php endif;?>
<?php if(has_permission('settings.manage')):?><a class="<?=$active(['settings.php'])?>" href="settings.php"><?=crm_icon('settings')?> <span>Configuración</span></a><a class="<?=$active(['license.php'])?>" href="license.php"><?=crm_icon('shield')?> <span>Licencia y versión</span></a><?php endif;?>
</div></div></div><?php endif;?>
</nav>
</div>
<?php if(has_permission('settings.manage')):?><a class="sidebar-license-card" href="license.php" title="ONE HUB Community"><?php else:?><div class="sidebar-license-card is-static"><?php endif;?>
<div class="sidebar-license-top"><strong>SÓCRATES CRM</strong><span>v<?=e($crmVersion)?></span></div>
<div class="sidebar-license-status license-<?=e($licenseInfo['class'])?>"><i></i><?=e($licenseInfo['label'])?></div>
<small><?=e($licenseInfo['plan'] ?: 'Professional')?><?php if($licenseInfo['expires_at']):?> · vence <?=e((new DateTimeImmutable($licenseInfo['expires_at']))->format('d/m/Y'))?><?php endif;?></small>
<?php if(has_permission('settings.manage')):?></a><?php else:?></div><?php endif;?>
</aside>
<div class="app-main">
<header class="topbar">
<button class="icon-button mobile-menu" type="button" data-sidebar-toggle aria-label="Abrir menú">☰</button>
<div class="topbar-title"><span class="section-title-icon icon-<?=e($pageIcon)?>"><?=crm_icon($pageIcon)?></span><h1><?=e($pageTitle)?></h1><?php if($licenseInfo['status']==='demo'):?><span class="demo-mode-badge">DEMO</span><?php endif;?></div>
<div class="top-actions">
<form class="global-search" action="global_search.php" method="get" role="search" data-global-search-form>
<span class="global-search-icon"><?=crm_icon('search')?></span><input type="search" name="q" value="<?=e($currentPage==='global_search.php'?(string)($_GET['q']??''):'')?>" placeholder="<?=e($searchPlaceholder)?>" aria-label="<?=e(t('search'))?>" autocomplete="off"><kbd>Ctrl K</kbd>
</form>
<button class="tour-launch-button" type="button" data-tour-launch title="Tour guiado 360°" aria-label="Tour guiado 360°"><span class="tour-dot"></span><?=crm_icon('route')?><b>TOUR</b></button>
<div class="language-menu" data-language-menu>
<button class="icon-button language-trigger" type="button" data-language-toggle aria-label="Idioma" aria-haspopup="true" aria-expanded="false"><img src="assets/img/flag-<?=e($lang)?>.png" alt="<?=e($lang)?>"></button>
<div class="language-dropdown">
<a href="language.php?lang=es"><img src="assets/img/flag-es.png" alt=""> Español</a>
<a href="language.php?lang=pt"><img src="assets/img/flag-pt.png" alt=""> Português</a>
<a href="language.php?lang=en"><img src="assets/img/flag-en.png" alt=""> English</a>
</div></div>
<button class="icon-button" type="button" data-theme-toggle aria-label="Cambiar tema">◐</button>
<?php if(has_permission('notifications.view')):?><a class="icon-button notification-button" href="notifications.php" aria-label="<?=e(t('notifications'))?>"><?=crm_icon('bell')?><?php if($unreadCount):?><span><?=$unreadCount>99?'99+':$unreadCount?></span><?php endif;?></a><?php endif;?>
<?php if(has_permission('quotes.create')):?><a class="btn btn-secondary btn-small quick-action" href="quotation_form.php"><?=e(t('new_quote'))?></a><?php endif;?>
<?php if(has_permission('clients.create')):?><a class="btn btn-primary btn-small quick-action" href="client_form.php"><?=e(t('new_client'))?></a><?php endif;?>
<div class="profile-menu" data-profile-menu>
<button class="profile-trigger" type="button" data-profile-toggle aria-expanded="false"><?=user_avatar_html($user,'avatar')?><span class="profile-trigger-copy"><strong><?=e($user['name'])?></strong><small><?=e(role_label((string)$user['role']))?></small></span><b>⌄</b></button>
<div class="profile-dropdown" data-profile-dropdown><div class="profile-dropdown-head"><?=user_avatar_html($user,'avatar avatar-lg')?><div><strong><?=e($user['name'])?></strong><small><?=e($user['email'])?></small></div></div>
<a href="profile.php"><?=e(t('profile'))?></a><?php if(has_permission('assistant.use')):?><a href="assistant.php">SÓCRATES Copilot</a><?php endif;?><a href="tour.php">Tour guiado 360°</a><a href="security_2fa.php">Autenticación y 2FA</a><?php if(has_permission('security.view')):?><a href="security_center.php">Centro de Seguridad</a><?php endif;?><?php if(has_permission('services.view')):?><a href="my_expirations.php"><?=e(t('my_expirations'))?></a><?php endif;?><?php if(has_permission('notifications.view')):?><a href="notifications.php"><?=e(t('notifications'))?> <?php if($unreadCount):?><span class="menu-count"><?=$unreadCount?></span><?php endif;?></a><?php endif;?><a href="manual.php"><?=e(t('manual'))?></a><div class="profile-separator"></div><a class="profile-logout" href="logout.php"><?=e(t('logout'))?></a>
</div></div></div>
</header>
<main class="content">
<?php foreach($flashes as$flash):?><div class="alert alert-<?=e($flash['type'])?>"><?=e($flash['message'])?></div><?php endforeach;?>
