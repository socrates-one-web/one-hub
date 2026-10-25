<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';require __DIR__.'/includes/project_manager.php';
require_login();
$pageTitle=t('search');
$q=trim((string)($_GET['q']??''));
$results=[];

$add=function(string $type,string $title,string $subtitle,string $url,string $icon='search')use(&$results):void{
    $results[]=['type'=>$type,'title'=>$title,'subtitle'=>$subtitle,'url'=>$url,'icon'=>$icon];
};
$matches=function(string $haystack,string $needle):bool{
    if($needle==='')return false;
    return str_contains(crm_lower($haystack),crm_lower($needle));
};

$sections=[
    ['Dashboard','Panel principal, indicadores y gráficos','dashboard.php','dashboard','dashboard.view'],
    ['Clientes','Empresas, prospectos y datos comerciales','clients.php','clients','clients.view'],
    ['Contactos','Personas, correos, notas e historial','contacts.php','contact','contacts.view'],
    ['Actividades','Tareas, llamadas, correos, eventos y reuniones','activities.php','activity','activities.view'],
    ['Negocios y pipeline','Embudo comercial, Kanban y pronóstico','deals.php','kanban','deals.view'],
    ['Proyectos','Portafolio, Kanban, Gantt, calendario, horas, riesgos y costos','project_dashboard.php','project','projects.view'],
    ['Servicios de clientes','Servicios contratados, pagos y vencimientos','services.php','services','services.view'],
    ['Catálogo de servicios','Servicios disponibles, precios y categorías','service_catalog.php','catalog','catalog.view'],
    ['Cotizaciones','Crear, enviar, imprimir y descargar PDF','quotations.php','quote','quotes.view'],
    ['Contratos','Contratos de clientes y plantillas legales','contracts.php','contract','contracts.view'],
    ['Document Manager','Archivos, versiones, aprobaciones y vencimientos','document_dashboard.php','contract','documents.view'],
    ['Mailing','Campañas de correo para clientes y contactos','mailing.php','mail','mailing.view'],
    ['Formularios de captación','Crear formularios públicos sin código','lead_forms.php','form','forms.view'],
    ['Citas y reuniones','Agenda y enlaces de reserva','appointments.php','calendar','appointments.view'],
    ['Informes','Datos de marketing, ventas y servicio','reports.php','report','reports.view'],
    ['Facturación y renovaciones','Servicios activos, cobros, pagos y vencimientos','billing.php','billing','billing.view'],
    ['Importar y exportar','Carga masiva y respaldos','data_tools.php','import','imports.view'],
    ['Usuarios','Accesos, fotos, roles y permisos','users.php','users','users.view'],
    ['Configuración','SMTP, alertas, WhatsApp y seguridad','settings.php','settings','settings.manage'],
    ['Manual de uso','Guía visual e imprimible del CRM','manual.php','manual','manual.view'],
];
$actions=[
    ['Crear cliente','Registrar una nueva empresa o prospecto','client_form.php','clients','clients.create'],
    ['Crear contacto','Agregar una persona a la base de datos','contact_form.php','contact','contacts.manage'],
    ['Crear negocio','Agregar una oportunidad al pipeline','deal_form.php','deal','deals.manage'],
    ['Crear proyecto','Crear un proyecto y planificar su ejecución','project_form.php','project','projects.manage'],
    ['Crear cotización','Preparar una propuesta en CLP, USD o BRL','quotation_form.php','quote','quotes.create'],
    ['Subir documento','Agregar un archivo al Document Manager','document_upload.php','contract','documents.upload'],
    ['Crear actividad','Registrar una tarea, llamada, correo o reunión','activity_form.php','activity','activities.manage'],
    ['Registrar pago','Registrar un ingreso y avanzar el próximo cobro','payment_form.php','billing','payments.manage'],
    ['Crear formulario','Usar plantillas o diseñar un formulario','lead_form_builder.php','form','forms.manage'],
    ['Crear mailing','Enviar una campaña a clientes o contactos','mailing_form.php','mail','mailing.manage'],
];
if($q!==''){
    foreach($sections as[$title,$subtitle,$url,$icon,$permission]){
        if(has_permission($permission)&&($matches($title,$q)||$matches($subtitle,$q)))$add('Sección',crm_translate_text($title),crm_translate_text($subtitle),$url,$icon);
    }
    foreach($actions as[$title,$subtitle,$url,$icon,$permission]){
        if(has_permission($permission)&&($matches($title,$q)||$matches($subtitle,$q)))$add('Acción',crm_translate_text($title),crm_translate_text($subtitle),$url,$icon);
    }
    $like='%'.$q.'%';
    if(has_permission('clients.view')){
        $s=db()->prepare("SELECT id,company_name,contact_name,email,country FROM clients WHERE company_name LIKE ? OR contact_name LIKE ? OR email LIKE ? OR tax_id LIKE ? ORDER BY company_name LIMIT 12");
        $s->execute([$like,$like,$like,$like]);
        foreach($s->fetchAll()as$r)$add('Cliente',(string)$r['company_name'],trim(($r['contact_name']?:'').' · '.($r['email']?:'').' · '.($r['country']?:''),' ·'),'client_view.php?id='.(int)$r['id'],'clients');
    }
    if(has_permission('contacts.view')){
        $s=db()->prepare("SELECT ct.id,ct.first_name,ct.last_name,ct.email,c.company_name FROM contacts ct LEFT JOIN clients c ON c.id=ct.client_id WHERE CONCAT_WS(' ',ct.first_name,ct.last_name) LIKE ? OR ct.email LIKE ? OR ct.phone LIKE ? ORDER BY ct.first_name,ct.last_name LIMIT 12");
        $s->execute([$like,$like,$like]);
        foreach($s->fetchAll()as$r)$add('Contacto',trim((string)$r['first_name'].' '.(string)$r['last_name']),trim(($r['company_name']?:'').' · '.($r['email']?:''),' ·'),'contact_view.php?id='.(int)$r['id'],'contact');
    }
    if(has_permission('deals.view')){
        $s=db()->prepare("SELECT d.id,d.title,d.currency,d.amount,c.company_name FROM deals d LEFT JOIN clients c ON c.id=d.client_id WHERE d.title LIKE ? OR c.company_name LIKE ? ORDER BY d.updated_at DESC LIMIT 12");
        $s->execute([$like,$like]);
        foreach($s->fetchAll()as$r)$add('Negocio',(string)$r['title'],trim(($r['company_name']?:'').' · '.format_money($r['amount'],$r['currency']),' ·'),'deal_view.php?id='.(int)$r['id'],'deal');
    }
    if(has_permission('projects.view')){
        $s=db()->prepare("SELECT p.id,p.name,p.code,p.status,c.company_name FROM projects p LEFT JOIN clients c ON c.id=p.client_id WHERE p.name LIKE ? OR p.code LIKE ? OR c.company_name LIKE ? ORDER BY p.updated_at DESC LIMIT 12");
        $s->execute([$like,$like,$like]);
        foreach($s->fetchAll()as$r)$add('Proyecto',(string)$r['name'],trim(($r['code']?:'').' · '.($r['company_name']?:'Interno').' · '.pm_status_label($r['status']),' ·'),'project_view.php?id='.(int)$r['id'],'project');
    }
    if(has_permission('quotes.view')){
        $s=db()->prepare("SELECT q.id,q.quotation_number,q.status,q.total,q.currency,c.company_name FROM quotations q JOIN clients c ON c.id=q.client_id WHERE q.quotation_number LIKE ? OR c.company_name LIKE ? ORDER BY q.created_at DESC LIMIT 12");
        $s->execute([$like,$like]);
        foreach($s->fetchAll()as$r)$add('Cotización',(string)$r['quotation_number'],trim($r['company_name'].' · '.quote_status_label($r['status']).' · '.format_money($r['total'],$r['currency']),' ·'),'quotation_view.php?id='.(int)$r['id'],'quote');
    }
    if(has_permission('documents.view')){
        require_once __DIR__.'/includes/document_manager.php';
        [$dacl,$dparams]=dms_access_clause(current_user(),'d');
        $sql="SELECT d.id,d.title,d.category,d.status,f.name folder_name FROM documents d LEFT JOIN document_folders f ON f.id=d.folder_id WHERE d.is_deleted=0 AND $dacl AND (d.title LIKE ? OR d.description LIKE ? OR d.category LIKE ?) ORDER BY d.updated_at DESC LIMIT 12";
        $s=db()->prepare($sql);$s->execute(array_merge($dparams,[$like,$like,$like]));
        foreach($s->fetchAll()as$r)$add('Documento',(string)$r['title'],trim(($r['folder_name']?:'').' · '.($r['category']?:'').' · '.dms_status_label((string)$r['status']),' ·'),'document_view.php?id='.(int)$r['id'],'contract');
    }
    if(has_permission('catalog.view')){
        $s=db()->prepare("SELECT sc.id,sc.name_es,sc.name_pt,sc.name_en,COALESCE(cat.name,sc.category) category FROM service_catalog sc LEFT JOIN service_categories cat ON cat.id=sc.category_id WHERE sc.name_es LIKE ? OR sc.name_pt LIKE ? OR sc.name_en LIKE ? OR COALESCE(cat.name,sc.category) LIKE ? ORDER BY sc.name_es LIMIT 12");
        $s->execute([$like,$like,$like,$like]);
        foreach($s->fetchAll()as$r)$add('Servicio',(string)$r['name_es'],trim(($r['name_pt']?:'').' · '.($r['name_en']?:'').' · '.($r['category']?:''),' ·'),'service_catalog_form.php?id='.(int)$r['id'],'catalog');
    }
}
require __DIR__.'/includes/header.php';
?>
<section class="panel global-search-page">
<div class="panel-header"><div><h2><?=crm_icon('search')?> Buscar en todo el CRM</h2><span class="table-sub">Encuentra secciones, acciones, clientes, contactos, negocios, proyectos, documentos, cotizaciones y servicios.</span></div></div>
<div class="panel-body">
<form class="search-page-form" method="get"><input type="search" name="q" value="<?=e($q)?>" placeholder="Ej.: crear cotización, Comercial Euroandina, hosting…" autofocus><button class="btn btn-primary">BUSCAR</button></form>
<?php if($q===''):?><div class="search-hints"><strong>Búsquedas rápidas</strong><div class="actions"><a href="?q=crear+cotización">Crear cotización</a><a href="?q=clientes">Clientes</a><a href="?q=pipeline">Pipeline</a><a href="?q=formularios">Formularios</a><a href="?q=configuración">Configuración</a></div></div>
<?php elseif(!$results):?><div class="empty">No se encontraron resultados para <strong><?=e($q)?></strong>.</div>
<?php else:?><div class="global-search-results"><?php foreach($results as$r):?><a class="global-search-result" href="<?=e($r['url'])?>"><span class="search-result-icon"><?=crm_icon($r['icon'])?></span><span><small><?=e(crm_translate_text($r['type']))?></small><strong><?=e($r['title'])?></strong><em><?=e($r['subtitle'])?></em></span><b>→</b></a><?php endforeach;?></div><?php endif;?>
</div></section>
<?php require __DIR__.'/includes/footer.php';?>
