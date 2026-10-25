<?php
declare(strict_types=1);

/**
 * SÓCRATES CRM · Copiloto inteligente local
 * v2.2.0
 *
 * El copiloto trabaja con los módulos instalados y la base de datos local.
 * No envía información a servicios de IA externos. Toda lectura respeta los
 * permisos del usuario autenticado. Las acciones críticas se limitan a abrir
 * formularios/pantallas; no modifica datos sin la interacción explícita del usuario.
 */

function assistant_html(string $value): string { return e($value); }
function assistant_norm(string $value): string
{
    $value = crm_lower(trim($value));
    if (class_exists('Normalizer')) {
        $n = \Normalizer::normalize($value, \Normalizer::FORM_D);
        if (is_string($n)) $value = preg_replace('/\p{Mn}+/u', '', $n) ?? $value;
    }
    $map=['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','ç'=>'c'];
    return strtr($value,$map);
}
function assistant_contains_any(string $q,array $terms):bool{foreach($terms as$t)if(str_contains($q,assistant_norm((string)$t)))return true;return false;}
function assistant_page_exists(string $path): bool { return is_file(dirname(__DIR__).'/'.ltrim($path,'/')); }
function assistant_action(string $label,string $url,string $icon='open',string $style='light'): array
{
    return ['label'=>$label,'url'=>$url,'icon'=>$icon,'style'=>$style];
}
function assistant_link(string $label,string $url): string
{
    return '<a class="assistant-inline-link" href="'.assistant_html($url).'">'.assistant_html($label).'</a>';
}
function assistant_count(string $sql,array $params=[]): int
{
    try{$st=db()->prepare($sql);$st->execute($params);return (int)($st->fetchColumn()?:0);}catch(Throwable){return 0;}
}
function assistant_rows(string $sql,array $params=[]): array
{
    try{$st=db()->prepare($sql);$st->execute($params);$r=$st->fetchAll();return is_array($r)?$r:[];}catch(Throwable){return [];}
}
function assistant_currency_rows(string $sql,array $params=[]): array
{
    $out=[];foreach(assistant_rows($sql,$params) as$r){$cur=(string)($r['currency']??'USD');$out[$cur]=(float)($r['total']??0);}return $out;
}
function assistant_money(float $amount,string $currency):string
{
    $dec=$currency==='CLP'?0:2;return $currency.' '.number_format($amount,$dec,',','.');
}
function assistant_money_html(array $map): string
{
    if(!$map)return '<span class="assistant-muted">Sin valores registrados.</span>';
    $parts=[];foreach($map as$cur=>$amount)$parts[]='<strong>'.assistant_html(assistant_money((float)$amount,(string)$cur)).'</strong>';
    return implode(' · ',$parts);
}
function assistant_status_badge(string $text,string $tone='blue'):string{return '<span class="assistant-data-badge tone-'.assistant_html($tone).'">'.assistant_html($text).'</span>';}
function assistant_list(array $items): string
{
    if(!$items)return '<p class="assistant-muted">No hay elementos que mostrar.</p>';
    $html='<ul class="assistant-result-list">';foreach($items as$i){$html.='<li>'.$i.'</li>';}$html.='</ul>';return $html;
}

function assistant_module_catalog(): array
{
    $modules=[
        'clients'=>['perm'=>'clients.view','table'=>'clients','page'=>'clients.php','icon'=>'clients','es'=>'Clientes','pt'=>'Clientes','en'=>'Clients','desc_es'=>'Empresas, contactos y relación comercial.','desc_pt'=>'Empresas, contatos e relacionamento comercial.','desc_en'=>'Companies, contacts and commercial relationship.'],
        'contacts'=>['perm'=>'contacts.view','table'=>'contacts','page'=>'contacts.php','icon'=>'contact','es'=>'Contactos y actividades','pt'=>'Contatos e atividades','en'=>'Contacts & activities','desc_es'=>'Personas, tareas, llamadas, reuniones y seguimiento.','desc_pt'=>'Pessoas, tarefas, chamadas, reuniões e acompanhamento.','desc_en'=>'People, tasks, calls, meetings and follow-up.'],
        'services'=>['perm'=>'services.view','table'=>'client_services','page'=>'services.php','icon'=>'services','es'=>'Servicios de clientes','pt'=>'Serviços de clientes','en'=>'Client services','desc_es'=>'Servicios contratados, vigencias, renovaciones y cobros.','desc_pt'=>'Serviços contratados, vigências, renovações e cobranças.','desc_en'=>'Contracted services, terms, renewals and charges.'],
        'catalog'=>['perm'=>'catalog.view','table'=>'service_catalog','page'=>'service_catalog.php','icon'=>'catalog','es'=>'Catálogo comercial','pt'=>'Catálogo comercial','en'=>'Commercial catalog','desc_es'=>'Productos, servicios, categorías, precios y formas de pago.','desc_pt'=>'Produtos, serviços, categorias, preços e formas de pagamento.','desc_en'=>'Products, services, categories, prices and payment methods.'],
        'deals'=>['perm'=>'deals.view','table'=>'deals','page'=>'deals.php','icon'=>'deal','es'=>'Negocios y Pipeline','pt'=>'Negócios e Pipeline','en'=>'Deals & Pipeline','desc_es'=>'Oportunidades, etapas, previsión y próximas acciones.','desc_pt'=>'Oportunidades, etapas, previsão e próximas ações.','desc_en'=>'Opportunities, stages, forecast and next actions.'],
        'marketing'=>['perm'=>'leads.view','table'=>'lead_campaigns','page'=>'lead_campaigns.php','icon'=>'lead','es'=>'Marketing y leads','pt'=>'Marketing e leads','en'=>'Marketing & leads','desc_es'=>'Mailing, campañas, formularios y captación.','desc_pt'=>'Mailing, campanhas, formulários e captação.','desc_en'=>'Mailing, campaigns, forms and lead capture.'],
        'quotes'=>['perm'=>'quotes.view','table'=>'quotations','page'=>'quotations.php','icon'=>'quote','es'=>'Cotizaciones','pt'=>'Cotações','en'=>'Quotations','desc_es'=>'Propuestas, vigencia, estados y envío.','desc_pt'=>'Propostas, validade, status e envio.','desc_en'=>'Proposals, validity, status and delivery.'],
        'contracts'=>['perm'=>'contracts.view','table'=>'contracts','page'=>'contracts.php','icon'=>'contract','es'=>'Contratos','pt'=>'Contratos','en'=>'Contracts','desc_es'=>'Contratos, aprobación y firma.','desc_pt'=>'Contratos, aprovação e assinatura.','desc_en'=>'Contracts, approval and signing.'],
        'projects'=>['perm'=>'projects.view','table'=>'projects','page'=>'project_dashboard.php','icon'=>'project','es'=>'Project Manager','pt'=>'Project Manager','en'=>'Project Manager','desc_es'=>'Portafolio, tareas, Gantt, horas, riesgos y costos.','desc_pt'=>'Portfólio, tarefas, Gantt, horas, riscos e custos.','desc_en'=>'Portfolio, tasks, Gantt, time, risks and costs.'],
        'documents'=>['perm'=>'documents.view','table'=>'documents','page'=>'document_dashboard.php','icon'=>'documents','es'=>'Gestión documental','pt'=>'Gestão documental','en'=>'Document management','desc_es'=>'Biblioteca, versiones, permisos, aprobaciones y auditoría.','desc_pt'=>'Biblioteca, versões, permissões, aprovações e auditoria.','desc_en'=>'Library, versions, permissions, approvals and audit.'],
        'billing'=>['perm'=>'billing.view','table'=>'billing_charges','page'=>'billing.php','icon'=>'billing','es'=>'Facturación','pt'=>'Faturamento','en'=>'Billing','desc_es'=>'Cobros, renovaciones, ingresos y vencimientos.','desc_pt'=>'Cobranças, renovações, receitas e vencimentos.','desc_en'=>'Charges, renewals, income and due dates.'],
        'appointments'=>['perm'=>'appointments.view','table'=>'appointments','page'=>'appointments.php','icon'=>'calendar','es'=>'Agenda','pt'=>'Agenda','en'=>'Calendar','desc_es'=>'Citas, reuniones y seguimiento.','desc_pt'=>'Compromissos, reuniões e acompanhamento.','desc_en'=>'Appointments, meetings and follow-up.'],
        'tickets'=>['perm'=>'tickets.view','table'=>'support_tickets','page'=>'support.php','icon'=>'support','es'=>'Helpdesk','pt'=>'Helpdesk','en'=>'Helpdesk','desc_es'=>'Tickets, responsables, SLA y trazabilidad.','desc_pt'=>'Tickets, responsáveis, SLA e rastreabilidade.','desc_en'=>'Tickets, owners, SLA and traceability.'],
        'reports'=>['perm'=>'reports.view','table'=>'deals','page'=>'reports.php','icon'=>'report','es'=>'Informes','pt'=>'Relatórios','en'=>'Reports','desc_es'=>'Indicadores comerciales, proyectos y finanzas.','desc_pt'=>'Indicadores comerciais, projetos e finanças.','desc_en'=>'Commercial, project and finance indicators.'],
        'users'=>['perm'=>'users.view','table'=>'users','page'=>'users.php','icon'=>'users','es'=>'Usuarios','pt'=>'Usuários','en'=>'Users','desc_es'=>'Usuarios, roles y permisos.','desc_pt'=>'Usuários, funções e permissões.','desc_en'=>'Users, roles and permissions.'],
        'data_tools'=>['perm'=>'imports.view','table'=>null,'page'=>'data_tools.php','icon'=>'import','es'=>'Importar y exportar','pt'=>'Importar e exportar','en'=>'Import & export','desc_es'=>'Migración, respaldos e intercambio de datos.','desc_pt'=>'Migração, backups e intercâmbio de dados.','desc_en'=>'Migration, backups and data exchange.'],
        'security'=>['perm'=>'security.view','table'=>'security_audit_logs','page'=>'security_center.php','icon'=>'shield','es'=>'Seguridad','pt'=>'Segurança','en'=>'Security','desc_es'=>'2FA, sesiones, auditoría, integridad y respaldos.','desc_pt'=>'2FA, sessões, auditoria, integridade e backups.','desc_en'=>'2FA, sessions, audit, integrity and backups.'],
        'settings'=>['perm'=>'settings.manage','table'=>'settings','page'=>'settings.php','icon'=>'settings','es'=>'Configuración','pt'=>'Configuração','en'=>'Settings','desc_es'=>'Identidad, SMTP, alertas y parámetros del sistema.','desc_pt'=>'Identidade, SMTP, alertas e parâmetros do sistema.','desc_en'=>'Branding, SMTP, alerts and system parameters.'],
        'manual'=>['perm'=>'manual.view','table'=>null,'page'=>'manual.php','icon'=>'manual','es'=>'Manual y Tour 360°','pt'=>'Manual e Tour 360°','en'=>'Manual & 360° Tour','desc_es'=>'Ayuda visual, onboarding y recorrido guiado.','desc_pt'=>'Ajuda visual, onboarding e tour guiado.','desc_en'=>'Visual help, onboarding and guided tour.'],
    ];
    foreach($modules as$key=>&$m){$table=$m['table']??null;$m['installed']=assistant_page_exists($m['page'])&&($table===null||db_table_exists((string)$table));$m['allowed']=has_permission($m['perm']);}unset($m);
    return $modules;
}

function assistant_page_map(): array
{
    return [
      'dashboard.php'=>['icon'=>'dashboard','title'=>'Dashboard','text'=>'Centro de mando con KPI, vencimientos, alertas y gráficos.','actions'=>[['Ver pendientes','prompt:¿Qué requiere mi atención hoy?'],['Abrir informes','reports.php']]],
      'clients.php'=>['icon'=>'clients','title'=>'Clientes','text'=>'Directorio de empresas y acceso a la ficha 360° de cada cliente.','actions'=>[['Crear cliente','client_form.php'],['Buscar cliente','prompt:Buscar cliente ']]],
      'client_view.php'=>['icon'=>'clients','title'=>'Ficha de cliente','text'=>'Centraliza contactos, actividades, negocios, servicios, proyectos, documentos, cotizaciones y facturación del cliente.','actions'=>[['Resumir esta ficha','prompt:Resume el cliente que estoy viendo'],['Ver documentos','documents.php']]],
      'deals.php'=>['icon'=>'deal','title'=>'Pipeline','text'=>'Gestiona oportunidades por etapas, probabilidad, valor, responsable y próxima acción.','actions'=>[['Resumen del pipeline','prompt:Resume el pipeline'],['Negocios por cerrar','prompt:¿Qué negocios están por cerrar?']]],
      'quotations.php'=>['icon'=>'quote','title'=>'Cotizaciones','text'=>'Administra propuestas comerciales multimoneda y multidioma.','actions'=>[['Crear cotización','quotation_form.php'],['Cotizaciones pendientes','prompt:¿Cuántas cotizaciones están pendientes?']]],
      'contracts.php'=>['icon'=>'contract','title'=>'Contratos','text'=>'Controla contratos desde borrador hasta aprobación y firma.','actions'=>[['Nuevo contrato','contract_form.php'],['Contratos pendientes','prompt:¿Qué contratos requieren seguimiento?']]],
      'project_dashboard.php'=>['icon'=>'project','title'=>'Dashboard de proyectos','text'=>'Vista ejecutiva del portafolio, salud, avance, fechas, horas y riesgos.','actions'=>[['Proyectos en riesgo','prompt:Muéstrame proyectos en riesgo'],['Tareas vencidas','prompt:¿Qué tareas de proyecto están vencidas?']]],
      'project_kanban.php'=>['icon'=>'kanban','title'=>'Kanban de proyectos','text'=>'Gestiona tareas por estado con prioridad, responsable y avance.','actions'=>[['Mis tareas','prompt:Muéstrame mis tareas pendientes'],['Tareas bloqueadas','prompt:¿Qué tareas están bloqueadas?']]],
      'project_gantt.php'=>['icon'=>'gantt','title'=>'Gantt','text'=>'Cronograma visual de tareas, fechas, responsables y dependencias.','actions'=>[['Próximos hitos','prompt:¿Qué hitos vencen pronto?']]],
      'project_calendar.php'=>['icon'=>'calendar','title'=>'Calendario de proyectos','text'=>'Vista temporal de tareas e hitos del portafolio.','actions'=>[['Mi agenda','prompt:¿Qué tengo programado próximamente?']]],
      'project_timesheet.php'=>['icon'=>'time','title'=>'Horas y recursos','text'=>'Registra esfuerzo real, horas facturables y carga de trabajo.','actions'=>[['Mis horas del mes','prompt:¿Cuántas horas registré este mes?']]],
      'project_risks.php'=>['icon'=>'risk','title'=>'Riesgos','text'=>'Registro de riesgos con probabilidad, impacto, responsable y mitigación.','actions'=>[['Riesgos abiertos','prompt:Muéstrame riesgos abiertos']]],
      'project_expenses.php'=>['icon'=>'billing','title'=>'Costos y gastos','text'=>'Controla gastos, presupuesto y cargos facturables de proyectos.','actions'=>[['Resumen de costos','prompt:Resume los costos de proyectos']]],
      'document_dashboard.php'=>['icon'=>'documents','title'=>'Gestión documental','text'=>'Dashboard documental con biblioteca, vencimientos, aprobaciones y trazabilidad.','actions'=>[['Pendientes de aprobación','prompt:¿Qué documentos debo aprobar?'],['Subir documento','document_upload.php']]],
      'documents.php'=>['icon'=>'documents','title'=>'Biblioteca documental','text'=>'Busca y administra documentos, carpetas, versiones, permisos y compartidos.','actions'=>[['Buscar documento','prompt:Buscar documento '],['Subir documento','document_upload.php']]],
      'document_approvals.php'=>['icon'=>'shield','title'=>'Aprobaciones documentales','text'=>'Bandeja de rutas de aprobación y decisiones pendientes.','actions'=>[['Mis aprobaciones','prompt:¿Qué documentos debo aprobar?']]],
      'contacts.php'=>['icon'=>'contact','title'=>'Contactos','text'=>'Directorio de personas vinculadas a clientes, responsables y seguimiento.','actions'=>[['Crear contacto','contact_form.php'],['Buscar contacto','prompt:Buscar contacto ']]],
      'activities.php'=>['icon'=>'activity','title'=>'Actividades','text'=>'Tareas, llamadas, reuniones, correos y notas comerciales con fechas y responsables.','actions'=>[['Mis pendientes','prompt:¿Qué requiere mi atención hoy?']]],
      'services.php'=>['icon'=>'services','title'=>'Servicios de clientes','text'=>'Servicios contratados, vigencias, renovaciones, modalidad y próximo cobro.','actions'=>[['Servicios por vencer','prompt:¿Qué servicios vencen pronto?'],['Facturación','billing.php']]],
      'service_catalog.php'=>['icon'=>'catalog','title'=>'Catálogo comercial','text'=>'Productos y servicios reutilizables en cotizaciones con precios por moneda.','actions'=>[['Nuevo ítem','service_catalog_form.php'],['Cotizar','quotation_form.php']]],
      'mailing.php'=>['icon'=>'mail','title'=>'Mailing','text'=>'Campañas de correo a clientes y contactos usando el SMTP del sistema.','actions'=>[['Nueva campaña','mailing_form.php']]],
      'lead_campaigns.php'=>['icon'=>'lead','title'=>'Campañas de leads','text'=>'Captación por canal, presupuesto, estado y formularios asociados.','actions'=>[['Nueva campaña','lead_campaign_form.php']]],
      'lead_forms.php'=>['icon'=>'form','title'=>'Formularios de captación','text'=>'Formularios públicos que alimentan contactos y oportunidades.','actions'=>[['Crear formulario','lead_form_builder.php']]],
      'data_tools.php'=>['icon'=>'import','title'=>'Importar y exportar','text'=>'Herramientas de migración, respaldo e intercambio de datos del CRM.','actions'=>[]],
      'license.php'=>['icon'=>'settings','title'=>'Licencia','text'=>'Estado de licencia, plan, instalación y activación del producto.','actions'=>[]],
      'manual.php'=>['icon'=>'manual','title'=>'Manual 360°','text'=>'Guía visual de módulos, flujos y buenas prácticas del sistema.','actions'=>[['Abrir tour','tour.php']]],
      'tour.php'=>['icon'=>'route','title'=>'Tour guiado 360°','text'=>'Recorrido interactivo por las funciones reales del CRM, incluido SÓCRATES Copilot.','actions'=>[]],
      'billing.php'=>['icon'=>'billing','title'=>'Facturación','text'=>'Resumen de cobros, renovaciones, vencimientos y métricas recurrentes.','actions'=>[['Cobros vencidos','prompt:¿Qué cobros están vencidos?'],['Cobros próximos','prompt:¿Qué cobros vencen pronto?']]],
      'payments.php'=>['icon'=>'payment','title'=>'Ingresos','text'=>'Historial de pagos registrados por cliente y moneda.','actions'=>[['Ingresos del mes','prompt:Resume los ingresos de este mes']]],
      'appointments.php'=>['icon'=>'calendar','title'=>'Agenda','text'=>'Organiza citas y reuniones vinculadas a clientes, contactos y negocios.','actions'=>[['Próximas reuniones','prompt:¿Cuáles son mis próximas reuniones?'],['Nueva reunión','appointment_form.php']]],
      'support.php'=>['icon'=>'support','title'=>'Helpdesk','text'=>'Mesa de soporte con tickets, responsables, SLA, seguimiento y trazabilidad.','actions'=>[['Tickets urgentes','prompt:¿Qué tickets requieren atención?'],['Nuevo ticket','support_ticket_form.php']]],
      'reports.php'=>['icon'=>'report','title'=>'Informes','text'=>'Panel ejecutivo para ventas, proyectos, actividad y finanzas.','actions'=>[['Resumen ejecutivo','prompt:Dame un resumen ejecutivo del CRM']]],
      'security_center.php'=>['icon'=>'shield','title'=>'Centro de Seguridad','text'=>'2FA, sesiones, intentos fallidos, integridad, auditoría y respaldos.','actions'=>[['Estado de seguridad','prompt:Resume el estado de seguridad']]],
      'settings.php'=>['icon'=>'settings','title'=>'Configuración','text'=>'Identidad, SMTP, alertas, proyectos, soporte, cotizaciones y seguridad.','actions'=>[['Probar correo','settings.php#smtp']]],
      'assistant.php'=>['icon'=>'assistant','title'=>'SÓCRATES Copilot','text'=>'Copiloto contextual conectado con los módulos y datos que tienes permiso para consultar.','actions'=>[['Mi día','prompt:¿Qué requiere mi atención hoy?'],['Qué puedes hacer','prompt:¿Qué puedes hacer por mí?']]],
    ];
}

function assistant_module_name(array $m,string $lang):string{return (string)($m[$lang]??$m['es']??'');}
function assistant_module_desc(array $m,string $lang):string{return (string)($m['desc_'.$lang]??$m['desc_es']??'');}

function assistant_capabilities(string $lang='es'): array
{
    $catalog=assistant_module_catalog();$items=[];
    foreach($catalog as$key=>$m){if(!$m['installed']||!$m['allowed'])continue;$items[]=['key'=>$key,'name'=>assistant_module_name($m,$lang),'desc'=>assistant_module_desc($m,$lang),'page'=>$m['page'],'icon'=>$m['icon']];}
    return $items;
}

function assistant_attention_snapshot(): array
{
    $u=current_user();$uid=(int)($u['id']??0);$items=[];
    if($uid<=0)return $items;
    if(has_permission('activities.view')&&db_table_exists('activities')){
        $n=assistant_count("SELECT COUNT(*) FROM activities WHERE assigned_to=? AND status='pending' AND due_at IS NOT NULL AND due_at<NOW()",[$uid]);
        if($n)$items[]=['tone'=>'red','icon'=>'activity','label'=>'Actividades vencidas','value'=>$n,'url'=>'activities.php'];
    }
    if(has_permission('project_tasks.view')&&db_table_exists('project_tasks')){
        $n=assistant_count("SELECT COUNT(*) FROM project_tasks WHERE assigned_to=? AND status<>'done' AND due_date IS NOT NULL AND due_date<CURDATE()",[$uid]);
        if($n)$items[]=['tone'=>'red','icon'=>'kanban','label'=>'Tareas de proyecto vencidas','value'=>$n,'url'=>'project_kanban.php'];
    }
    if(has_permission('documents.approve')&&db_table_exists('document_approval_steps')){
        $n=assistant_count("SELECT COUNT(*) FROM document_approval_steps WHERE approver_user_id=? AND status='pending'",[$uid]);
        if($n)$items[]=['tone'=>'orange','icon'=>'documents','label'=>'Documentos por aprobar','value'=>$n,'url'=>'document_approvals.php'];
    }
    if(has_permission('tickets.view')&&db_table_exists('support_tickets')){
        if(has_permission('tickets.manage'))$n=assistant_count("SELECT COUNT(*) FROM support_tickets WHERE status NOT IN ('closed','resolved') AND (assigned_to=? OR assigned_to IS NULL)",[$uid]);
        else $n=assistant_count("SELECT COUNT(*) FROM support_tickets WHERE user_id=? AND status NOT IN ('closed','resolved')",[$uid]);
        if($n)$items[]=['tone'=>'orange','icon'=>'support','label'=>'Tickets abiertos','value'=>$n,'url'=>'support.php'];
    }
    if(has_permission('appointments.view')&&db_table_exists('appointments')){
        $n=assistant_count("SELECT COUNT(*) FROM appointments WHERE assigned_to=? AND status='scheduled' AND start_at BETWEEN NOW() AND DATE_ADD(NOW(),INTERVAL 24 HOUR)",[$uid]);
        if($n)$items[]=['tone'=>'blue','icon'=>'calendar','label'=>'Reuniones próximas 24h','value'=>$n,'url'=>'appointments.php'];
    }
    if(has_permission('project_risks.view')&&db_table_exists('project_risks')){
        $n=assistant_count("SELECT COUNT(*) FROM project_risks WHERE status IN ('open','mitigating') AND probability*impact>=2500");
        if($n)$items[]=['tone'=>'red','icon'=>'risk','label'=>'Riesgos altos abiertos','value'=>$n,'url'=>'project_risks.php'];
    }
    return $items;
}

function assistant_search_everywhere(string $term,string $lang='es'): array
{
    $term=trim($term);if($term==='')return [];$like='%'.str_replace(['%','_'],['\\%','\\_'],$term).'%';$out=[];
    $add=function(string $type,string $title,string $subtitle,string $url,string $icon)use(&$out):void{$out[]=['type'=>$type,'title'=>$title,'subtitle'=>$subtitle,'url'=>$url,'icon'=>$icon];};
    if(has_permission('clients.view')&&db_table_exists('clients'))foreach(assistant_rows("SELECT id,company_name,country FROM clients WHERE company_name LIKE ? OR trade_name LIKE ? ORDER BY updated_at DESC LIMIT 4",[$like,$like]) as$r)$add('Cliente',(string)$r['company_name'],(string)($r['country']??''),'client_view.php?id='.(int)$r['id'],'clients');
    if(has_permission('contacts.view')&&db_table_exists('contacts'))foreach(assistant_rows("SELECT id,first_name,last_name,email FROM contacts WHERE CONCAT(first_name,' ',COALESCE(last_name,'')) LIKE ? OR email LIKE ? ORDER BY updated_at DESC LIMIT 3",[$like,$like]) as$r)$add('Contacto',trim((string)$r['first_name'].' '.(string)($r['last_name']??'')),(string)($r['email']??''),'contact_view.php?id='.(int)$r['id'],'contact');
    if(has_permission('services.view')&&db_table_exists('client_services'))foreach(assistant_rows("SELECT s.id,s.name,s.status,c.company_name FROM client_services s JOIN clients c ON c.id=s.client_id WHERE s.name LIKE ? OR c.company_name LIKE ? ORDER BY s.updated_at DESC LIMIT 3",[$like,$like]) as$r)$add('Servicio',(string)$r['name'],(string)$r['company_name'].' · '.strtoupper((string)$r['status']),'service_form.php?id='.(int)$r['id'],'services');
    if(has_permission('catalog.view')&&db_table_exists('service_catalog'))foreach(assistant_rows("SELECT id,item_type,name_es,category,price_usd FROM service_catalog WHERE name_es LIKE ? OR name_pt LIKE ? OR name_en LIKE ? OR category LIKE ? ORDER BY updated_at DESC LIMIT 3",[$like,$like,$like,$like]) as$r)$add(((string)$r['item_type']==='product'?'Producto':'Servicio catálogo'),(string)$r['name_es'],(string)($r['category']??''),'service_catalog_form.php?id='.(int)$r['id'],'catalog');
    if(has_permission('deals.view')&&db_table_exists('deals'))foreach(assistant_rows("SELECT id,title,status,currency,amount FROM deals WHERE title LIKE ? ORDER BY updated_at DESC LIMIT 4",[$like]) as$r)$add('Negocio',(string)$r['title'],strtoupper((string)$r['status']).' · '.assistant_money((float)$r['amount'],(string)$r['currency']),'deal_view.php?id='.(int)$r['id'],'deal');
    if(has_permission('projects.view')&&db_table_exists('projects'))foreach(assistant_rows("SELECT id,name,status,progress FROM projects WHERE name LIKE ? OR code LIKE ? ORDER BY updated_at DESC LIMIT 4",[$like,$like]) as$r)$add('Proyecto',(string)$r['name'],strtoupper((string)$r['status']).' · '.(int)$r['progress'].'%','project_view.php?id='.(int)$r['id'],'project');
    if(has_permission('documents.view')&&db_table_exists('documents'))foreach(assistant_rows("SELECT id,title,status,category FROM documents WHERE is_deleted=0 AND (title LIKE ? OR description LIKE ? OR category LIKE ?) ORDER BY updated_at DESC LIMIT 4",[$like,$like,$like]) as$r)$add('Documento',(string)$r['title'],(string)($r['category']??'').' · '.strtoupper((string)$r['status']),'document_view.php?id='.(int)$r['id'],'documents');
    if(has_permission('quotes.view')&&db_table_exists('quotations'))foreach(assistant_rows("SELECT id,quotation_number,status,total,currency FROM quotations WHERE quotation_number LIKE ? OR recipient_name LIKE ? ORDER BY updated_at DESC LIMIT 3",[$like,$like]) as$r)$add('Cotización',(string)$r['quotation_number'],strtoupper((string)$r['status']).' · '.assistant_money((float)$r['total'],(string)$r['currency']),'quotation_view.php?id='.(int)$r['id'],'quote');
    if(has_permission('contracts.view')&&db_table_exists('contracts'))foreach(assistant_rows("SELECT id,contract_number,title,status FROM contracts WHERE contract_number LIKE ? OR title LIKE ? OR recipient_name LIKE ? ORDER BY updated_at DESC LIMIT 3",[$like,$like,$like]) as$r)$add('Contrato',(string)$r['contract_number'].' · '.(string)$r['title'],strtoupper((string)$r['status']),'contract_view.php?id='.(int)$r['id'],'contract');
    if(has_permission('tickets.view')&&db_table_exists('support_tickets')){
        $uid=(int)(current_user()['id']??0);$scope=has_permission('tickets.manage')?'1=1':'user_id='.(int)$uid;
        foreach(assistant_rows("SELECT id,ticket_number,subject,status,priority FROM support_tickets WHERE $scope AND (ticket_number LIKE ? OR subject LIKE ?) ORDER BY updated_at DESC LIMIT 3",[$like,$like]) as$r)$add('Ticket',(string)$r['ticket_number'].' · '.(string)$r['subject'],strtoupper((string)$r['priority']).' · '.strtoupper((string)$r['status']),'support_ticket_view.php?id='.(int)$r['id'],'support');
    }
    return array_slice($out,0,12);
}

function assistant_search_html(array $rows):string
{
    if(!$rows)return '<p class="assistant-muted">No encontré coincidencias en los módulos a los que tienes acceso.</p>';
    $h='<div class="assistant-search-results">';foreach($rows as$r){$h.='<a href="'.assistant_html((string)$r['url']).'"><span class="assistant-search-icon">'.crm_icon((string)$r['icon']).'</span><div><small>'.assistant_html((string)$r['type']).'</small><strong>'.assistant_html((string)$r['title']).'</strong><em>'.assistant_html((string)$r['subtitle']).'</em></div><b>→</b></a>';}$h.='</div>';return $h;
}

function assistant_answer_my_day(string $lang):array
{
    $uid=(int)(current_user()['id']??0);$parts=[];$actions=[];
    $parts[]='<div class="assistant-answer-title">'.assistant_status_badge('EN VIVO','green').'<strong>'.($lang==='en'?'Your attention list':($lang==='pt'?'Sua lista de atenção':'Tu lista de atención')).'</strong></div>';
    $attention=assistant_attention_snapshot();
    if(!$attention)$parts[]='<p>✓ '.($lang==='en'?'I do not see urgent pending items assigned to you right now.':($lang==='pt'?'Não vejo pendências urgentes atribuídas a você agora.':'No veo pendientes urgentes asignados a ti en este momento.')).'</p>';
    else{$li=[];foreach($attention as$a){$li[]='<a href="'.assistant_html($a['url']).'"><strong>'.assistant_html((string)$a['value']).'</strong> '.assistant_html($a['label']).'</a>';}$parts[]=assistant_list($li);}
    if(has_permission('appointments.view')&&db_table_exists('appointments')){
        $rows=assistant_rows("SELECT id,title,start_at FROM appointments WHERE assigned_to=? AND status='scheduled' AND start_at>=NOW() ORDER BY start_at LIMIT 3",[$uid]);
        if($rows){$li=[];foreach($rows as$r){$d=date('d/m H:i',strtotime((string)$r['start_at']));$li[]='<a href="appointment_form.php?id='.(int)$r['id'].'"><strong>'.assistant_html($d).'</strong> '.assistant_html((string)$r['title']).'</a>';}$parts[]='<p><strong>Próximas reuniones</strong></p>'.assistant_list($li);$actions[]=assistant_action('Abrir agenda','appointments.php','calendar','light');}
    }
    if(has_permission('project_tasks.view')&&db_table_exists('project_tasks')){
        $rows=assistant_rows("SELECT t.id,t.title,t.due_date,p.name project_name FROM project_tasks t JOIN projects p ON p.id=t.project_id WHERE t.assigned_to=? AND t.status<>'done' ORDER BY (t.due_date IS NULL),t.due_date LIMIT 4",[$uid]);
        if($rows){$li=[];foreach($rows as$r){$due=$r['due_date']?date('d/m',strtotime((string)$r['due_date'])):'sin fecha';$li[]='<a href="project_task_form.php?id='.(int)$r['id'].'"><strong>'.assistant_html((string)$r['title']).'</strong> · '.assistant_html((string)$r['project_name']).' · '.assistant_html($due).'</a>';}$parts[]='<p><strong>Mis próximas tareas</strong></p>'.assistant_list($li);$actions[]=assistant_action('Abrir Kanban','project_kanban.php','kanban','primary');}
    }
    return ['answer'=>implode('',$parts),'actions'=>$actions,'suggestions'=>['Resume el pipeline','Muéstrame proyectos en riesgo','¿Qué documentos debo aprobar?','¿Qué cobros están vencidos?']];
}

function assistant_answer_pipeline(string $lang):array
{
    $open=assistant_count("SELECT COUNT(*) FROM deals WHERE status='open'");
    $money=assistant_currency_rows("SELECT currency,SUM(amount) total FROM deals WHERE status='open' GROUP BY currency");
    $closing=assistant_count("SELECT COUNT(*) FROM deals WHERE status='open' AND expected_close_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 14 DAY)");
    $stale=assistant_count("SELECT COUNT(*) FROM deals WHERE status='open' AND (next_action_at IS NULL OR next_action_at<CURDATE())");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('PIPELINE','blue').'<strong>Resumen comercial</strong></div><div class="assistant-metric-row"><span><b>'.$open.'</b><small>abiertos</small></span><span><b>'.$closing.'</b><small>cierran ≤14 días</small></span><span><b>'.$stale.'</b><small>sin próxima acción vigente</small></span></div><p>Valor abierto: '.assistant_money_html($money).'</p>';
    $rows=assistant_rows("SELECT d.id,d.title,d.expected_close_date,d.currency,d.amount,COALESCE(c.company_name,'Sin cliente') client FROM deals d LEFT JOIN clients c ON c.id=d.client_id WHERE d.status='open' ORDER BY (d.expected_close_date IS NULL),d.expected_close_date LIMIT 5");
    $li=[];foreach($rows as$r){$li[]='<a href="deal_view.php?id='.(int)$r['id'].'"><strong>'.assistant_html((string)$r['title']).'</strong> · '.assistant_html((string)$r['client']).' · '.assistant_html(assistant_money((float)$r['amount'],(string)$r['currency'])).'</a>';}
    if($li)$answer.='<p><strong>Oportunidades a revisar</strong></p>'.assistant_list($li);
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir Pipeline','deals.php','deal','primary'),assistant_action('Nuevo negocio','deal_form.php','plus','light')],'suggestions'=>['¿Qué negocios están por cerrar?','Buscar cliente ','Dame un resumen ejecutivo']];
}

function assistant_answer_projects(string $lang):array
{
    $active=assistant_count("SELECT COUNT(*) FROM projects WHERE status IN ('planning','active','on_hold')");
    $risk=assistant_count("SELECT COUNT(*) FROM projects WHERE status IN ('planning','active','on_hold') AND health IN ('at_risk','off_track')");
    $overdue=assistant_count("SELECT COUNT(*) FROM project_tasks WHERE status<>'done' AND due_date IS NOT NULL AND due_date<CURDATE()");
    $blocked=assistant_count("SELECT COUNT(*) FROM project_tasks WHERE status='blocked'");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('PROJECTS','cyan').'<strong>Estado del portafolio</strong></div><div class="assistant-metric-row"><span><b>'.$active.'</b><small>activos</small></span><span><b>'.$risk.'</b><small>en riesgo</small></span><span><b>'.$overdue.'</b><small>tareas vencidas</small></span><span><b>'.$blocked.'</b><small>bloqueadas</small></span></div>';
    $rows=assistant_rows("SELECT id,name,status,health,progress,due_date FROM projects WHERE status IN ('planning','active','on_hold') ORDER BY FIELD(health,'off_track','at_risk','on_track'),(due_date IS NULL),due_date LIMIT 6");
    $li=[];foreach($rows as$r){$tone=$r['health']==='off_track'?'red':($r['health']==='at_risk'?'orange':'green');$li[]='<a href="project_view.php?id='.(int)$r['id'].'">'.assistant_status_badge(strtoupper((string)$r['health']),$tone).' <strong>'.assistant_html((string)$r['name']).'</strong> · '.(int)$r['progress'].'%</a>';}
    if($li)$answer.=assistant_list($li);
    return ['answer'=>$answer,'actions'=>[assistant_action('Dashboard de proyectos','project_dashboard.php','project','primary'),assistant_action('Abrir Kanban','project_kanban.php','kanban','light'),assistant_action('Abrir Gantt','project_gantt.php','gantt','light')],'suggestions'=>['Muéstrame riesgos abiertos','¿Qué tareas están vencidas?','¿Cuántas horas registré este mes?']];
}

function assistant_answer_documents(string $lang):array
{
    if(!db_table_exists('documents'))return ['answer'=>'El Gestor Documental no está instalado en esta instancia.','actions'=>[]];
    $total=assistant_count("SELECT COUNT(*) FROM documents WHERE is_deleted=0");
    $review=assistant_count("SELECT COUNT(*) FROM documents WHERE is_deleted=0 AND status='review'");
    $exp=assistant_count("SELECT COUNT(*) FROM documents WHERE is_deleted=0 AND expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)");
    $uid=(int)(current_user()['id']??0);$approve=has_permission('documents.approve')?assistant_count("SELECT COUNT(*) FROM document_approval_steps WHERE approver_user_id=? AND status='pending'",[$uid]):0;
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('DMS','blue').'<strong>Gestión documental</strong></div><div class="assistant-metric-row"><span><b>'.$total.'</b><small>documentos</small></span><span><b>'.$review.'</b><small>en revisión</small></span><span><b>'.$exp.'</b><small>vencen ≤30 días</small></span><span><b>'.$approve.'</b><small>por aprobar</small></span></div>';
    $rows=assistant_rows("SELECT id,title,status,category,expires_at FROM documents WHERE is_deleted=0 ORDER BY (expires_at IS NULL),expires_at,updated_at DESC LIMIT 5");
    $li=[];foreach($rows as$r){$extra=$r['expires_at']?' · vence '.date('d/m/Y',strtotime((string)$r['expires_at'])):'';$li[]='<a href="document_view.php?id='.(int)$r['id'].'"><strong>'.assistant_html((string)$r['title']).'</strong> · '.assistant_html(strtoupper((string)$r['status'])).assistant_html($extra).'</a>';}
    if($li)$answer.=assistant_list($li);
    $actions=[assistant_action('Abrir biblioteca','documents.php','documents','primary')];if(has_permission('documents.upload'))$actions[]=assistant_action('Subir documento','document_upload.php','upload','light');if(has_permission('documents.approve'))$actions[]=assistant_action('Aprobaciones','document_approvals.php','shield','light');
    return ['answer'=>$answer,'actions'=>$actions,'suggestions'=>['Buscar documento ','¿Qué documentos debo aprobar?','¿Qué documentos vencen pronto?']];
}

function assistant_answer_billing(string $lang):array
{
    $over=assistant_count("SELECT COUNT(*) FROM billing_charges WHERE status NOT IN ('paid','cancelled') AND due_date<CURDATE()");
    $next=assistant_count("SELECT COUNT(*) FROM billing_charges WHERE status NOT IN ('paid','cancelled') AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)");
    $overMoney=assistant_currency_rows("SELECT currency,SUM(GREATEST(amount-paid_amount,0)) total FROM billing_charges WHERE status NOT IN ('paid','cancelled') AND due_date<CURDATE() GROUP BY currency");
    $monthMoney=assistant_currency_rows("SELECT currency,SUM(amount) total FROM payments WHERE payment_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) GROUP BY currency");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('FINANZAS','green').'<strong>Cobros e ingresos</strong></div><div class="assistant-metric-row"><span><b>'.$over.'</b><small>cobros vencidos</small></span><span><b>'.$next.'</b><small>vencen ≤30 días</small></span></div><p><strong>Saldo vencido:</strong> '.assistant_money_html($overMoney).'</p><p><strong>Ingresos del mes:</strong> '.assistant_money_html($monthMoney).'</p>';
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir facturación','billing.php','billing','primary'),assistant_action('Registrar ingreso','payment_form.php','payment','light')],'suggestions'=>['¿Qué cobros vencen pronto?','Resume los ingresos de este mes','Dame un resumen ejecutivo']];
}

function assistant_answer_tickets(string $lang):array
{
    $uid=(int)(current_user()['id']??0);$scope=has_permission('tickets.manage')?'1=1':'user_id='.(int)$uid;
    $open=assistant_count("SELECT COUNT(*) FROM support_tickets WHERE $scope AND status NOT IN ('closed','resolved')");
    $urgent=assistant_count("SELECT COUNT(*) FROM support_tickets WHERE $scope AND status NOT IN ('closed','resolved') AND priority IN ('urgent','high')");
    $sla=assistant_count("SELECT COUNT(*) FROM support_tickets WHERE $scope AND status NOT IN ('closed','resolved') AND sla_due_at IS NOT NULL AND sla_due_at<NOW()");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('HELPDESK','orange').'<strong>Centro de soporte</strong></div><div class="assistant-metric-row"><span><b>'.$open.'</b><small>abiertos</small></span><span><b>'.$urgent.'</b><small>alta/urgente</small></span><span><b>'.$sla.'</b><small>SLA vencido</small></span></div>';
    $rows=assistant_rows("SELECT id,ticket_number,subject,priority,status,updated_at FROM support_tickets WHERE $scope AND status NOT IN ('closed','resolved') ORDER BY FIELD(priority,'urgent','high','normal','low'),updated_at LIMIT 5");
    $li=[];foreach($rows as$r){$li[]='<a href="support_ticket_view.php?id='.(int)$r['id'].'"><strong>'.assistant_html((string)$r['ticket_number']).'</strong> · '.assistant_html((string)$r['subject']).' · '.assistant_html(strtoupper((string)$r['priority'])).'</a>';}$answer.=assistant_list($li);
    $actions=[assistant_action('Abrir Helpdesk','support.php','support','primary')];if(has_permission('tickets.create'))$actions[]=assistant_action('Nuevo ticket','support_ticket_form.php','plus','light');
    return ['answer'=>$answer,'actions'=>$actions,'suggestions'=>['¿Qué tickets requieren atención?','Buscar ticket ','¿Qué requiere mi atención hoy?']];
}

function assistant_answer_security(string $lang):array
{
    if(!has_permission('security.view'))return ['answer'=>'No tienes permiso para consultar el Centro de Seguridad.','actions'=>[]];
    $failed=db_table_exists('login_attempts')?assistant_count("SELECT COUNT(*) FROM login_attempts WHERE success=0 AND attempted_at>=DATE_SUB(NOW(),INTERVAL 24 HOUR)"):0;
    $sessions=db_table_exists('user_sessions')?assistant_count("SELECT COUNT(*) FROM user_sessions WHERE is_revoked=0 AND (expires_at IS NULL OR expires_at>NOW())"):0;
    $twofa=db_table_exists('users')?assistant_count("SELECT COUNT(*) FROM users WHERE is_active=1 AND role='admin' AND two_factor_enabled=1"):0;
    $admins=db_table_exists('users')?assistant_count("SELECT COUNT(*) FROM users WHERE is_active=1 AND role='admin'"):0;
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('SEGURIDAD','green').'<strong>Estado rápido</strong></div><div class="assistant-metric-row"><span><b>'.$failed.'</b><small>intentos fallidos 24h</small></span><span><b>'.$sessions.'</b><small>sesiones activas</small></span><span><b>'.$twofa.'/'.$admins.'</b><small>usuarios 2FA / admins</small></span></div><p>Para integridad de archivos, copias de seguridad y políticas usa el Centro de Seguridad, donde el sistema hace las comprobaciones completas.</p>';
    return ['answer'=>$answer,'actions'=>[assistant_action('Centro de Seguridad','security_center.php','shield','primary'),assistant_action('2FA','security_2fa.php','shield','light')],'suggestions'=>['¿Qué requiere mi atención hoy?','¿Qué puedes hacer por mí?']];
}

function assistant_answer_quotes(string $lang):array
{
    $draft=assistant_count("SELECT COUNT(*) FROM quotations WHERE status='draft'");$sent=assistant_count("SELECT COUNT(*) FROM quotations WHERE status='sent'");$expired=assistant_count("SELECT COUNT(*) FROM quotations WHERE status='expired' OR (status='sent' AND valid_until<CURDATE())");
    $openMoney=assistant_currency_rows("SELECT currency,SUM(total) total FROM quotations WHERE status IN ('draft','sent') GROUP BY currency");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('COTIZACIONES','cyan').'<strong>Estado de propuestas</strong></div><div class="assistant-metric-row"><span><b>'.$draft.'</b><small>borradores</small></span><span><b>'.$sent.'</b><small>enviadas</small></span><span><b>'.$expired.'</b><small>vencidas</small></span></div><p>Valor abierto: '.assistant_money_html($openMoney).'</p>';
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir cotizaciones','quotations.php','quote','primary'),assistant_action('Nueva cotización','quotation_form.php','plus','light')],'suggestions'=>['Buscar cotización ','Resume el pipeline','¿Qué contratos requieren seguimiento?']];
}

function assistant_answer_contracts(string $lang):array
{
    $draft=assistant_count("SELECT COUNT(*) FROM contracts WHERE status='draft'");$sent=assistant_count("SELECT COUNT(*) FROM contracts WHERE status='sent'");$approved=assistant_count("SELECT COUNT(*) FROM contracts WHERE status='approved'");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('CONTRATOS','blue').'<strong>Seguimiento contractual</strong></div><div class="assistant-metric-row"><span><b>'.$draft.'</b><small>borradores</small></span><span><b>'.$sent.'</b><small>enviados</small></span><span><b>'.$approved.'</b><small>aprobados sin firma</small></span></div>';
    $rows=assistant_rows("SELECT id,contract_number,title,status,updated_at FROM contracts WHERE status IN ('draft','sent','approved') ORDER BY updated_at LIMIT 5");$li=[];foreach($rows as$r)$li[]='<a href="contract_view.php?id='.(int)$r['id'].'"><strong>'.assistant_html((string)$r['contract_number']).'</strong> · '.assistant_html((string)$r['title']).' · '.assistant_html(strtoupper((string)$r['status'])).'</a>';$answer.=assistant_list($li);
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir contratos','contracts.php','contract','primary'),assistant_action('Nuevo contrato','contract_form.php','plus','light')],'suggestions'=>['Buscar contrato ','¿Cuántas cotizaciones están pendientes?']];
}

function assistant_answer_appointments(string $lang):array
{
    $uid=(int)(current_user()['id']??0);$rows=assistant_rows("SELECT id,title,start_at,status FROM appointments WHERE assigned_to=? AND status='scheduled' AND start_at>=NOW() ORDER BY start_at LIMIT 7",[$uid]);
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('AGENDA','blue').'<strong>Próximas reuniones</strong></div>';$li=[];foreach($rows as$r){$li[]='<a href="appointment_form.php?id='.(int)$r['id'].'"><strong>'.assistant_html(date('d/m H:i',strtotime((string)$r['start_at']))).'</strong> · '.assistant_html((string)$r['title']).'</a>';}$answer.=assistant_list($li);
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir agenda','appointments.php','calendar','primary'),assistant_action('Nueva reunión','appointment_form.php','plus','light')],'suggestions'=>['¿Qué requiere mi atención hoy?']];
}

function assistant_answer_time(string $lang):array
{
    $uid=(int)(current_user()['id']??0);$r=assistant_rows("SELECT COALESCE(SUM(hours),0) hours,COALESCE(SUM(CASE WHEN billable=1 THEN hours ELSE 0 END),0) billable,COALESCE(SUM(CASE WHEN billable=1 THEN hours*hourly_rate ELSE 0 END),0) value FROM project_time_entries WHERE user_id=? AND work_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())",[$uid]);$x=$r[0]??['hours'=>0,'billable'=>0,'value'=>0];
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('TIEMPO','cyan').'<strong>Mis horas del mes</strong></div><div class="assistant-metric-row"><span><b>'.number_format((float)$x['hours'],1,',','.').'h</b><small>totales</small></span><span><b>'.number_format((float)$x['billable'],1,',','.').'h</b><small>facturables</small></span><span><b>'.number_format((float)$x['value'],2,',','.').'</b><small>valor estimado</small></span></div>';
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir horas','project_timesheet.php','time','primary'),assistant_action('Registrar horas','project_time_form.php','plus','light')],'suggestions'=>['Muéstrame mis tareas pendientes','Resume los costos de proyectos']];
}

function assistant_answer_risks(string $lang):array
{
    $open=assistant_count("SELECT COUNT(*) FROM project_risks WHERE status IN ('open','mitigating')");$high=assistant_count("SELECT COUNT(*) FROM project_risks WHERE status IN ('open','mitigating') AND probability*impact>=2500");
    $rows=assistant_rows("SELECT r.id,r.title,r.probability,r.impact,r.status,p.name project_name FROM project_risks r JOIN projects p ON p.id=r.project_id WHERE r.status IN ('open','mitigating') ORDER BY r.probability*r.impact DESC LIMIT 6");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge($high?'ATENCIÓN':'RIESGOS',$high?'red':'green').'<strong>Registro de riesgos</strong></div><div class="assistant-metric-row"><span><b>'.$open.'</b><small>abiertos</small></span><span><b>'.$high.'</b><small>alto impacto</small></span></div>';$li=[];foreach($rows as$r){$score=(int)$r['probability']*(int)$r['impact'];$tone=$score>=2500?'red':($score>=1200?'orange':'blue');$li[]='<a href="project_risk_form.php?id='.(int)$r['id'].'">'.assistant_status_badge((string)$score,$tone).' <strong>'.assistant_html((string)$r['title']).'</strong> · '.assistant_html((string)$r['project_name']).'</a>';}$answer.=assistant_list($li);
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir riesgos','project_risks.php','risk',$high?'danger':'primary'),assistant_action('Registrar riesgo','project_risk_form.php','plus','light')],'suggestions'=>['Muéstrame proyectos en riesgo','¿Qué requiere mi atención hoy?']];
}

function assistant_answer_costs(string $lang):array
{
    $money=assistant_currency_rows("SELECT currency,SUM(amount) total FROM project_expenses WHERE expense_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) GROUP BY currency");$bill=assistant_currency_rows("SELECT currency,SUM(amount) total FROM project_expenses WHERE billable=1 AND expense_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()) GROUP BY currency");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('COSTOS','orange').'<strong>Gastos de proyectos este mes</strong></div><p><strong>Total:</strong> '.assistant_money_html($money).'</p><p><strong>Refacturable:</strong> '.assistant_money_html($bill).'</p>';
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir costos','project_expenses.php','billing','primary'),assistant_action('Registrar gasto','project_expense_form.php','plus','light')],'suggestions'=>['¿Cuántas horas registré este mes?','Muéstrame proyectos en riesgo']];
}

function assistant_answer_capabilities(string $lang):array
{
    $caps=assistant_capabilities($lang);$cards='<div class="assistant-capability-grid">';foreach($caps as$c){$cards.='<a href="'.assistant_html($c['page']).'"><span>'.crm_icon($c['icon']).'</span><div><strong>'.assistant_html($c['name']).'</strong><small>'.assistant_html($c['desc']).'</small></div></a>';}$cards.='</div>';
    $intro=$lang==='en'?'I am connected to the modules installed in this CRM and I only read what your permissions allow. I can summarize live information, find records, explain the current screen and take you directly to the right action.':($lang==='pt'?'Estou conectado aos módulos instalados neste CRM e só leio o que suas permissões permitem. Posso resumir informações em tempo real, localizar registros, explicar a tela atual e levar você diretamente à ação correta.':'Estoy conectado a los módulos instalados en este CRM y solo leo lo que tus permisos permiten. Puedo resumir información en tiempo real, localizar registros, explicar la pantalla actual y llevarte directamente a la acción correcta.');
    return ['answer'=>'<div class="assistant-answer-title">'.assistant_status_badge('COPILOT','green').'<strong>SÓCRATES Copilot</strong></div><p>'.$intro.'</p>'.$cards,'actions'=>[assistant_action('Mi día','assistant.php?prompt='.rawurlencode('¿Qué requiere mi atención hoy?'),'assistant','primary')],'suggestions'=>['¿Qué requiere mi atención hoy?','Dame un resumen ejecutivo','Buscar cliente ','Buscar documento ']];
}

function assistant_answer_executive(string $lang):array
{
    $sections=[];
    if(has_permission('deals.view')&&db_table_exists('deals')){$n=assistant_count("SELECT COUNT(*) FROM deals WHERE status='open'");$v=assistant_currency_rows("SELECT currency,SUM(amount) total FROM deals WHERE status='open' GROUP BY currency");$sections[]='<div><small>PIPELINE</small><strong>'.$n.' abiertos</strong><span>'.assistant_money_html($v).'</span></div>';}
    if(has_permission('projects.view')&&db_table_exists('projects')){$a=assistant_count("SELECT COUNT(*) FROM projects WHERE status IN ('planning','active','on_hold')");$r=assistant_count("SELECT COUNT(*) FROM projects WHERE status IN ('planning','active','on_hold') AND health IN ('at_risk','off_track')");$sections[]='<div><small>PROYECTOS</small><strong>'.$a.' activos</strong><span>'.$r.' requieren atención</span></div>';}
    if(has_permission('billing.view')&&db_table_exists('billing_charges')){$o=assistant_count("SELECT COUNT(*) FROM billing_charges WHERE status NOT IN ('paid','cancelled') AND due_date<CURDATE()");$sections[]='<div><small>COBROS</small><strong>'.$o.' vencidos</strong><span>Revisar facturación</span></div>';}
    if(has_permission('documents.view')&&db_table_exists('documents')){$e=assistant_count("SELECT COUNT(*) FROM documents WHERE is_deleted=0 AND expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)");$sections[]='<div><small>DOCUMENTOS</small><strong>'.$e.' por vencer</strong><span>Próximos 30 días</span></div>';}
    if(has_permission('tickets.view')&&db_table_exists('support_tickets')){$u=(int)(current_user()['id']??0);$scope=has_permission('tickets.manage')?'1=1':'user_id='.(int)$u;$t=assistant_count("SELECT COUNT(*) FROM support_tickets WHERE $scope AND status NOT IN ('closed','resolved')");$sections[]='<div><small>SOPORTE</small><strong>'.$t.' abiertos</strong><span>Tickets pendientes</span></div>';}
    $html='<div class="assistant-answer-title">'.assistant_status_badge('EJECUTIVO','blue').'<strong>Resumen del CRM</strong></div><div class="assistant-executive-grid">'.implode('',$sections).'</div>';
    $att=assistant_attention_snapshot();if($att){$li=[];foreach($att as$a)$li[]='<a href="'.assistant_html($a['url']).'"><strong>'.assistant_html((string)$a['value']).'</strong> '.assistant_html($a['label']).'</a>';$html.='<p><strong>Atención inmediata</strong></p>'.assistant_list($li);}
    return ['answer'=>$html,'actions'=>[assistant_action('Dashboard','dashboard.php','dashboard','primary'),assistant_action('Informes','reports.php','report','light')],'suggestions'=>['Resume el pipeline','Muéstrame proyectos en riesgo','¿Qué cobros están vencidos?','¿Qué tickets requieren atención?']];
}

function assistant_answer_current_page(string $page,string $lang):array
{
    $map=assistant_page_map();$p=$map[$page]??null;
    if(!$p)return ['answer'=>'Esta pantalla no tiene todavía una guía contextual específica. Puedo buscar la función en el sistema o abrir el Manual 360°.','actions'=>[assistant_action('Abrir manual','manual.php','manual','light')],'suggestions'=>['¿Qué puedes hacer por mí?']];
    $actions=[];$actionText=[];foreach($p['actions']??[] as$a){[$label,$url]=$a;if(str_starts_with($url,'prompt:')){$actionText[]=substr($url,7);}else $actions[]=assistant_action($label,$url,'open','light');}
    $html='<div class="assistant-answer-title">'.assistant_status_badge('CONTEXTO','blue').'<strong>'.assistant_html($p['title']).'</strong></div><p>'.assistant_html($p['text']).'</p><p class="assistant-muted">Puedo ayudarte a interpretar lo que ves, resumir datos de esta sección o llevarte a la acción correcta.</p>';
    return ['answer'=>$html,'actions'=>$actions,'suggestions'=>($actionText ? $actionText : ['¿Qué requiere mi atención hoy?'])];
}


function assistant_context_record_id(array $context): int
{
    $url=(string)($context['url']??'');if($url==='')return 0;
    $query=(string)(parse_url($url,PHP_URL_QUERY)??'');$vars=[];parse_str($query,$vars);return max(0,(int)($vars['id']??0));
}

function assistant_answer_current_entity(array $context,string $lang):?array
{
    $page=basename((string)($context['page']??''));$id=assistant_context_record_id($context);if($id<=0)return null;
    if($page==='client_view.php'&&has_permission('clients.view')&&db_table_exists('clients')){
        $rows=assistant_rows('SELECT id,company_name,status,country,city,email,owner_user_id FROM clients WHERE id=? LIMIT 1',[$id]);if(!$rows)return null;$c=$rows[0];
        $deals=db_table_exists('deals')?assistant_count("SELECT COUNT(*) FROM deals WHERE client_id=? AND status='open'",[$id]):0;
        $projects=db_table_exists('projects')?assistant_count("SELECT COUNT(*) FROM projects WHERE client_id=? AND status IN ('planning','active','on_hold')",[$id]):0;
        $quotes=db_table_exists('quotations')?assistant_count("SELECT COUNT(*) FROM quotations WHERE client_id=? AND status IN ('draft','sent')",[$id]):0;
        $docs=db_table_exists('document_links')?assistant_count("SELECT COUNT(*) FROM document_links l JOIN documents d ON d.id=l.document_id WHERE l.entity_type='client' AND l.entity_id=? AND d.is_deleted=0",[$id]):0;
        $html='<div class="assistant-answer-title">'.assistant_status_badge('CLIENTE','blue').'<strong>'.assistant_html((string)$c['company_name']).'</strong></div><div class="assistant-metric-row"><span><b>'.$deals.'</b><small>negocios abiertos</small></span><span><b>'.$projects.'</b><small>proyectos activos</small></span><span><b>'.$quotes.'</b><small>cotizaciones abiertas</small></span><span><b>'.$docs.'</b><small>documentos</small></span></div><p>'.assistant_html(trim((string)($c['country']??'').' · '.(string)($c['city']??''),' ·')).' · '.assistant_status_badge(strtoupper((string)$c['status']),$c['status']==='active'?'green':'orange').'</p>';
        $actions=[];if(has_permission('quotes.create'))$actions[]=assistant_action('Crear cotización','quotation_form.php?client_id='.$id,'quote','primary');if(has_permission('projects.manage'))$actions[]=assistant_action('Crear proyecto','project_form.php?client_id='.$id,'project','light');if(has_permission('documents.upload'))$actions[]=assistant_action('Subir documento','document_upload.php?entity_type=client&entity_id='.$id,'documents','light');
        return ['answer'=>$html,'actions'=>$actions,'suggestions'=>['Buscar cliente ','Resume el pipeline','¿Qué requiere mi atención hoy?']];
    }
    if($page==='project_view.php'&&has_permission('projects.view')&&db_table_exists('projects')){
        $rows=assistant_rows('SELECT id,name,status,health,progress,due_date,budget,currency FROM projects WHERE id=? LIMIT 1',[$id]);if(!$rows)return null;$p=$rows[0];
        $tasks=assistant_count("SELECT COUNT(*) FROM project_tasks WHERE project_id=? AND status<>'done'",[$id]);$over=assistant_count("SELECT COUNT(*) FROM project_tasks WHERE project_id=? AND status<>'done' AND due_date IS NOT NULL AND due_date<CURDATE()",[$id]);$risks=assistant_count("SELECT COUNT(*) FROM project_risks WHERE project_id=? AND status IN ('open','mitigating')",[$id]);$hours=(float)(assistant_rows("SELECT COALESCE(SUM(hours),0) h FROM project_time_entries WHERE project_id=?",[$id])[0]['h']??0);
        $tone=$p['health']==='off_track'?'red':($p['health']==='at_risk'?'orange':'green');$html='<div class="assistant-answer-title">'.assistant_status_badge(strtoupper((string)$p['health']),$tone).'<strong>'.assistant_html((string)$p['name']).'</strong></div><div class="assistant-metric-row"><span><b>'.(int)$p['progress'].'%</b><small>avance</small></span><span><b>'.$tasks.'</b><small>tareas abiertas</small></span><span><b>'.$over.'</b><small>vencidas</small></span><span><b>'.$risks.'</b><small>riesgos</small></span><span><b>'.number_format($hours,1,',','.').'h</b><small>registradas</small></span></div><p>Presupuesto: <strong>'.assistant_html(assistant_money((float)$p['budget'],(string)$p['currency'])).'</strong>'.($p['due_date']?' · vence '.assistant_html(date('d/m/Y',strtotime((string)$p['due_date']))):'').'</p>';
        return ['answer'=>$html,'actions'=>[assistant_action('Abrir Kanban','project_kanban.php?project_id='.$id,'kanban','primary'),assistant_action('Abrir Gantt','project_gantt.php?project_id='.$id,'gantt','light'),assistant_action('Registrar horas','project_time_form.php?project_id='.$id,'time','light')],'suggestions'=>['Muéstrame riesgos abiertos','¿Cuántas horas registré este mes?']];
    }
    if($page==='document_view.php'&&has_permission('documents.view')&&db_table_exists('documents')){
        $rows=assistant_rows('SELECT id,title,status,category,expires_at,visibility,updated_at FROM documents WHERE id=? AND is_deleted=0 LIMIT 1',[$id]);if(!$rows)return null;$d=$rows[0];$versions=assistant_count('SELECT COUNT(*) FROM document_versions WHERE document_id=?',[$id]);$comments=assistant_count('SELECT COUNT(*) FROM document_comments WHERE document_id=?',[$id]);$approval=assistant_rows("SELECT status FROM document_approvals WHERE document_id=? ORDER BY id DESC LIMIT 1",[$id]);$ap=(string)($approval[0]['status']??'sin flujo');
        $html='<div class="assistant-answer-title">'.assistant_status_badge(strtoupper((string)$d['status']),'blue').'<strong>'.assistant_html((string)$d['title']).'</strong></div><div class="assistant-metric-row"><span><b>'.$versions.'</b><small>versiones</small></span><span><b>'.$comments.'</b><small>comentarios</small></span><span><b>'.assistant_html(strtoupper($ap)).'</b><small>aprobación</small></span></div><p>Categoría: <strong>'.assistant_html((string)($d['category']??'—')).'</strong> · Visibilidad: '.assistant_html((string)$d['visibility']).($d['expires_at']?' · vence '.assistant_html(date('d/m/Y',strtotime((string)$d['expires_at']))):'').'</p>';
        $actions=[assistant_action('Biblioteca','documents.php','documents','light')];if(has_permission('documents.approve'))$actions[]=assistant_action('Aprobaciones','document_approvals.php','shield','primary');
        return ['answer'=>$html,'actions'=>$actions,'suggestions'=>['¿Qué documentos debo aprobar?','Buscar documento ']];
    }
    if($page==='support_ticket_view.php'&&has_permission('tickets.view')&&db_table_exists('support_tickets')){
        $scope=has_permission('tickets.manage')?'1=1':'user_id='.(int)(current_user()['id']??0);$rows=assistant_rows("SELECT id,ticket_number,subject,priority,status,stage,sla_due_at,next_follow_up_at,updated_at FROM support_tickets WHERE id=? AND $scope LIMIT 1",[$id]);if(!$rows)return null;$t=$rows[0];$messages=assistant_count('SELECT COUNT(*) FROM support_ticket_messages WHERE ticket_id=?',[$id]);$events=assistant_count('SELECT COUNT(*) FROM support_ticket_events WHERE ticket_id=?',[$id]);$slaTone=($t['sla_due_at']&&strtotime((string)$t['sla_due_at'])<time()&&!in_array($t['status'],['closed','resolved'],true))?'red':'green';
        $html='<div class="assistant-answer-title">'.assistant_status_badge(strtoupper((string)$t['priority']),$t['priority']==='urgent'?'red':'orange').'<strong>'.assistant_html((string)$t['ticket_number']).' · '.assistant_html((string)$t['subject']).'</strong></div><div class="assistant-metric-row"><span><b>'.assistant_html(strtoupper((string)$t['stage'])).'</b><small>etapa</small></span><span><b>'.$messages.'</b><small>mensajes</small></span><span><b>'.$events.'</b><small>eventos</small></span></div><p>Estado: <strong>'.assistant_html((string)$t['status']).'</strong>'.($t['sla_due_at']?' · '.assistant_status_badge('SLA '.date('d/m H:i',strtotime((string)$t['sla_due_at'])),$slaTone):'').'</p>';
        return ['answer'=>$html,'actions'=>[assistant_action('Centro de soporte','support.php','support','light')],'suggestions'=>['¿Qué tickets requieren atención?']];
    }
    if($page==='quotation_view.php'&&has_permission('quotes.view')&&db_table_exists('quotations')){
        $rows=assistant_rows('SELECT id,quotation_number,status,total,currency,valid_until,client_id FROM quotations WHERE id=? LIMIT 1',[$id]);if(!$rows)return null;$q=$rows[0];$client=assistant_rows('SELECT company_name FROM clients WHERE id=? LIMIT 1',[(int)$q['client_id']]);$cn=(string)($client[0]['company_name']??'');
        $html='<div class="assistant-answer-title">'.assistant_status_badge(strtoupper((string)$q['status']),'blue').'<strong>'.assistant_html((string)$q['quotation_number']).'</strong></div><p>Cliente: <strong>'.assistant_html($cn).'</strong> · Total: <strong>'.assistant_html(assistant_money((float)$q['total'],(string)$q['currency'])).'</strong>'.($q['valid_until']?' · válida hasta '.assistant_html(date('d/m/Y',strtotime((string)$q['valid_until']))):'').'</p>';
        return ['answer'=>$html,'actions'=>[assistant_action('Abrir cotizaciones','quotations.php','quote','light')],'suggestions'=>['¿Cuántas cotizaciones están pendientes?']];
    }
    if($page==='contract_view.php'&&has_permission('contracts.view')&&db_table_exists('contracts')){
        $rows=assistant_rows('SELECT id,contract_number,title,status,issue_date,client_id FROM contracts WHERE id=? LIMIT 1',[$id]);if(!$rows)return null;$c=$rows[0];$client=assistant_rows('SELECT company_name FROM clients WHERE id=? LIMIT 1',[(int)$c['client_id']]);$cn=(string)($client[0]['company_name']??'');
        $html='<div class="assistant-answer-title">'.assistant_status_badge(strtoupper((string)$c['status']),'blue').'<strong>'.assistant_html((string)$c['contract_number']).' · '.assistant_html((string)$c['title']).'</strong></div><p>Cliente: <strong>'.assistant_html($cn).'</strong> · Emisión: '.assistant_html(date('d/m/Y',strtotime((string)$c['issue_date']))).'</p>';
        return ['answer'=>$html,'actions'=>[assistant_action('Abrir contratos','contracts.php','contract','light')],'suggestions'=>['¿Qué contratos requieren seguimiento?']];
    }
    if($page==='deal_view.php'&&has_permission('deals.view')&&db_table_exists('deals')){
        $rows=assistant_rows("SELECT d.*,COALESCE(c.company_name,'Sin cliente') client,COALESCE(s.name_es,'') stage FROM deals d LEFT JOIN clients c ON c.id=d.client_id LEFT JOIN pipeline_stages s ON s.id=d.stage_id WHERE d.id=? LIMIT 1",[$id]);if(!$rows)return null;$d=$rows[0];
        $html='<div class="assistant-answer-title">'.assistant_status_badge(strtoupper((string)$d['status']),'blue').'<strong>'.assistant_html((string)$d['title']).'</strong></div><p><strong>'.assistant_html((string)$d['client']).'</strong> · '.assistant_html((string)$d['stage']).' · '.assistant_html(assistant_money((float)$d['amount'],(string)$d['currency'])).' · '.(int)$d['probability'].'%</p>'.($d['next_action']?'<p>Próxima acción: <strong>'.assistant_html((string)$d['next_action']).'</strong></p>':'');
        return ['answer'=>$html,'actions'=>[assistant_action('Pipeline','deals.php','deal','light')],'suggestions'=>['Resume el pipeline']];
    }
    return null;
}

function assistant_navigation_action(string $q,string $lang):?array
{
    if(!assistant_contains_any($q,['abrir','abre','ir a','llevarme','vamos a','open','go to','navigate','abrir o','ir para']))return null;
    $targets=[
      [['dashboard','panel principal'],'dashboard.view','dashboard.php','Abrir Dashboard','dashboard'],
      [['cliente','clientes'],'clients.view','clients.php','Abrir Clientes','clients'],
      [['contacto','contactos','contato','contatos'],'contacts.view','contacts.php','Abrir Contactos','contact'],
      [['actividad','actividades','atividade'],'activities.view','activities.php','Abrir Actividades','activity'],
      [['pipeline','negocio','negocios','deal'],'deals.view','deals.php','Abrir Pipeline','deal'],
      [['servicio','servicios','serviço','serviços'],'services.view','services.php','Abrir Servicios','services'],
      [['catalogo','catálogo','catalog'],'catalog.view','service_catalog.php','Abrir Catálogo','catalog'],
      [['cotizacion','cotización','cotizaciones','quote','quotation'],'quotes.view','quotations.php','Abrir Cotizaciones','quote'],
      [['contrato','contratos','contract'],'contracts.view','contracts.php','Abrir Contratos','contract'],
      [['proyecto','proyectos','project'],'projects.view','project_dashboard.php','Abrir Project Manager','project'],
      [['kanban'],'project_tasks.view','project_kanban.php','Abrir Kanban','kanban'],
      [['gantt'],'project_tasks.view','project_gantt.php','Abrir Gantt','gantt'],
      [['calendario de proyecto','project calendar'],'project_tasks.view','project_calendar.php','Abrir Calendario de proyectos','calendar'],
      [['hora','horas','timesheet'],'project_time.view','project_timesheet.php','Abrir Horas y recursos','time'],
      [['riesgo','riesgos','risk'],'project_risks.view','project_risks.php','Abrir Riesgos','risk'],
      [['costo','costos','gasto','gastos','expense'],'project_finance.view','project_expenses.php','Abrir Costos','billing'],
      [['documento','documentos','biblioteca','sharepoint','dms'],'documents.view','document_dashboard.php','Abrir Gestión documental','documents'],
      [['aprobacion','aprobaciones','aprovação'],'documents.approve','document_approvals.php','Abrir Aprobaciones','shield'],
      [['facturacion','facturación','billing'],'billing.view','billing.php','Abrir Facturación','billing'],
      [['ingreso','ingresos','pago','pagos','payments'],'payments.view','payments.php','Abrir Ingresos','payment'],
      [['agenda','reunion','reuniones','cita','citas'],'appointments.view','appointments.php','Abrir Agenda','calendar'],
      [['ticket','tickets','helpdesk','soporte'],'tickets.view','support.php','Abrir Helpdesk','support'],
      [['informe','informes','report','reports'],'reports.view','reports.php','Abrir Informes','report'],
      [['marketing','lead','leads','campaña','campanha'],'leads.view','lead_campaigns.php','Abrir Marketing y leads','lead'],
      [['mailing','email marketing'],'mailing.view','mailing.php','Abrir Mailing','mail'],
      [['importar','exportar','import','export'],'imports.view','data_tools.php','Abrir Importar y exportar','import'],
      [['usuario','usuarios','user','users'],'users.view','users.php','Abrir Usuarios','users'],
      [['seguridad','security'],'security.view','security_center.php','Abrir Seguridad','shield'],
      [['configuracion','configuración','settings'],'settings.manage','settings.php','Abrir Configuración','settings'],
      [['manual'],'manual.view','manual.php','Abrir Manual 360°','manual'],
      [['tour','recorrido'],'manual.view','tour.php','Abrir Tour 360°','route'],
      [['licencia','license'],'settings.manage','license.php','Abrir Licencia','settings'],
    ];
    foreach($targets as[$terms,$perm,$url,$label,$icon])if(assistant_contains_any($q,$terms)&&has_permission($perm)&&assistant_page_exists($url))return ['answer'=>'Listo. Encontré el módulo y puedes abrirlo desde aquí.','actions'=>[assistant_action($label,$url,$icon,'primary')],'suggestions'=>['¿Qué puedo hacer aquí?','¿Qué requiere mi atención hoy?']];
    return null;
}

function assistant_direct_action(string $q,string $lang):?array
{
    $create=assistant_contains_any($q,['crear','nuevo','nueva','registrar','agregar','subir','cadastrar','novo','nova','adicionar','create','new','add','upload','register']);if(!$create)return null;
    $pairs=[
      [['cliente','empresa','client','company'],'clients.create','client_form.php','Crear cliente','clients'],
      [['contacto','contato','contact'],'contacts.manage','contact_form.php','Crear contacto','contact'],
      [['negocio','oportunidad','deal','opportunity'],'deals.manage','deal_form.php','Crear negocio','deal'],
      [['servicio contratado','serviço contratado','client service'],'services.assign','service_form.php','Asignar servicio','services'],
      [['producto','catalogo','catálogo','catalog','product'],'catalog.manage','service_catalog_form.php','Crear producto / servicio','catalog'],
      [['campana de leads','campaña de leads','lead campaign'],'leads.manage','lead_campaign_form.php','Crear campaña de leads','lead'],
      [['formulario','form','lead form'],'forms.manage','lead_form_builder.php','Crear formulario','form'],
      [['cotiza','orçamento','quotation','quote'],'quotes.create','quotation_form.php','Crear cotización','quote'],
      [['contrato','contract'],'contracts.create','contract_form.php','Crear contrato','contract'],
      [['proyecto','project'],'projects.manage','project_form.php','Crear proyecto','project'],
      [['tarea','task'],'project_tasks.manage','project_task_form.php','Crear tarea','kanban'],
      [['hora','time entry','horas'],'project_time.manage','project_time_form.php','Registrar horas','time'],
      [['riesgo','risk'],'project_risks.manage','project_risk_form.php','Registrar riesgo','risk'],
      [['gasto','expense','costo','despesa'],'project_finance.manage','project_expense_form.php','Registrar gasto','billing'],
      [['documento','arquivo','document','file'],'documents.upload','document_upload.php','Subir documento','upload'],
      [['ticket','soporte','support'],'tickets.create','support_ticket_form.php','Crear ticket','support'],
      [['reunion','reunião','meeting','cita','appointment'],'appointments.manage','appointment_form.php','Programar reunión','calendar'],
      [['pago','ingreso','payment','receita'],'payments.manage','payment_form.php','Registrar ingreso','payment'],
    ];
    foreach($pairs as[$terms,$perm,$url,$label,$icon])if(assistant_contains_any($q,$terms)&&has_permission($perm)&&assistant_page_exists($url))return ['answer'=>'Puedo llevarte directamente al formulario correcto. La creación se completa allí para que puedas revisar los datos antes de guardar.','actions'=>[assistant_action($label,$url,$icon,'primary')],'suggestions'=>['¿Qué requiere mi atención hoy?']];
    return null;
}

function assistant_answer_services(string $lang):array
{
    $active=assistant_count("SELECT COUNT(*) FROM client_services WHERE status='active'");
    $next=assistant_count("SELECT COUNT(*) FROM client_services WHERE status='active' AND COALESCE(next_billing_date,expiration_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)");
    $expired=assistant_count("SELECT COUNT(*) FROM client_services WHERE status='active' AND expiration_date IS NOT NULL AND expiration_date<CURDATE()");
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('SERVICIOS','cyan').'<strong>Servicios de clientes</strong></div><div class="assistant-metric-row"><span><b>'.$active.'</b><small>activos</small></span><span><b>'.$next.'</b><small>vencen / cobran ≤30 días</small></span><span><b>'.$expired.'</b><small>con vigencia vencida</small></span></div>';
    $rows=assistant_rows("SELECT s.id,s.name,s.expiration_date,s.next_billing_date,s.currency,s.amount,c.company_name FROM client_services s JOIN clients c ON c.id=s.client_id WHERE s.status='active' ORDER BY COALESCE(s.next_billing_date,s.expiration_date) IS NULL,COALESCE(s.next_billing_date,s.expiration_date) LIMIT 6");
    $li=[];foreach($rows as$r){$date=$r['next_billing_date']?:$r['expiration_date'];$li[]='<a href="service_form.php?id='.(int)$r['id'].'"><strong>'.assistant_html((string)$r['name']).'</strong> · '.assistant_html((string)$r['company_name']).($date?' · '.assistant_html(date('d/m/Y',strtotime((string)$date))):'').'</a>';}
    if($li)$answer.=assistant_list($li);
    return ['answer'=>$answer,'actions'=>[assistant_action('Abrir servicios','services.php','services','primary'),assistant_action('Ver facturación','billing.php','billing','light')],'suggestions'=>['¿Qué cobros están vencidos?','Buscar servicio ','Dame un resumen ejecutivo']];
}

function assistant_answer_marketing(string $lang):array
{
    $campaigns=db_table_exists('lead_campaigns')?assistant_count("SELECT COUNT(*) FROM lead_campaigns WHERE status='active'"):0;
    $leads=db_table_exists('lead_submissions')?assistant_count("SELECT COUNT(*) FROM lead_submissions WHERE created_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01')"):0;
    $mail=db_table_exists('mailing_campaigns')?assistant_count("SELECT COUNT(*) FROM mailing_campaigns WHERE status='sent' AND sent_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01')"):0;
    $answer='<div class="assistant-answer-title">'.assistant_status_badge('CAPTACIÓN','blue').'<strong>Marketing y leads</strong></div><div class="assistant-metric-row"><span><b>'.$campaigns.'</b><small>campañas activas</small></span><span><b>'.$leads.'</b><small>respuestas del mes</small></span><span><b>'.$mail.'</b><small>mailings enviados este mes</small></span></div>';
    return ['answer'=>$answer,'actions'=>[assistant_action('Campañas de leads','lead_campaigns.php','lead','primary'),assistant_action('Formularios','lead_forms.php','form','light'),assistant_action('Mailing','mailing.php','mail','light')],'suggestions'=>['Dame un resumen ejecutivo','Buscar cliente ','¿Qué requiere mi atención hoy?']];
}

function assistant_handle_question(string $question,array $context=[]):array
{
    $lang=crm_current_language();$q=assistant_norm($question);$page=basename((string)($context['page']??''));
    if($q==='')return ['answer'=>$lang==='en'?'Write a question or choose a suggestion.':($lang==='pt'?'Escreva uma pergunta ou escolha uma sugestão.':'Escribe una pregunta o elige una sugerencia.'),'actions'=>[]];
    if(strlen($question)>900)return ['answer'=>'La consulta es demasiado larga. Resume la pregunta en una o dos frases para poder orientarte mejor.','actions'=>[]];

    if(assistant_contains_any($q,['que puedes hacer','que sabes hacer','ayudame','como me ayudas','o que voce faz','what can you do','capabilities']))return assistant_answer_capabilities($lang);
    if(assistant_contains_any($q,['esta pantalla','esta pagina','aqui','donde estoy','nesta tela','this screen','this page','here']))return assistant_answer_current_page($page,$lang);
    if(assistant_contains_any($q,['resume este','resume esta','resumir este','resumir esta','resumen de este','resumen de esta','que estoy viendo','actual'])){if($entity=assistant_answer_current_entity($context,$lang))return $entity;}
    if($x=assistant_navigation_action($q,$lang))return $x;
    if($x=assistant_direct_action($q,$lang))return $x;
    if(assistant_contains_any($q,['mi dia','hoy','pendiente','requiere mi atencion','que debo hacer','minha agenda','pendencias','today','attention','to do']))return assistant_answer_my_day($lang);
    if(assistant_contains_any($q,['resumen ejecutivo','resumen general','estado general','vision ejecutiva','executive summary','resumo executivo']))return assistant_answer_executive($lang);

    if(assistant_contains_any($q,['buscar','busca','search','encontrar','localizar','find'])){
        $term=preg_replace('/\b(buscar|busca|search|encontrar|localizar|find|cliente|contacto|documento|proyecto|negocio|cotizacion|cotización|contrato|ticket)\b/ui','',$question)??'';$term=trim($term," :,-\t\n\r\0\x0B");
        if($term==='')return ['answer'=>'Indícame qué quieres buscar. Por ejemplo: <strong>Buscar cliente Acme</strong> o <strong>Buscar documento contrato</strong>.','actions'=>[],'suggestions'=>['Buscar cliente ','Buscar proyecto ','Buscar documento ','Buscar ticket ']];
        $rows=assistant_search_everywhere($term,$lang);return ['answer'=>'<div class="assistant-answer-title">'.assistant_status_badge('BÚSQUEDA','blue').'<strong>Resultados para “'.assistant_html($term).'”</strong></div>'.assistant_search_html($rows),'actions'=>[assistant_action('Búsqueda global','global_search.php?q='.rawurlencode($term),'search','light')],'suggestions'=>[]];
    }

    if(has_permission('documents.view')&&assistant_contains_any($q,['document','archivo','arquivo','biblioteca','sharepoint','aprobacion','aprobar','vencen']))return assistant_answer_documents($lang);
    if(has_permission('services.view')&&assistant_contains_any($q,['servicio','servico','service','renovacion','renovação','vence pronto']))return assistant_answer_services($lang);
    if((has_permission('leads.view')||has_permission('mailing.view'))&&assistant_contains_any($q,['marketing','lead','campana','campaña','campanha','mailing','formulario','formulário','captacion','captação']))return assistant_answer_marketing($lang);
    if(has_permission('project_risks.view')&&assistant_contains_any($q,['riesgo','risk','mitigacion','mitigar']))return assistant_answer_risks($lang);
    if(has_permission('project_time.view')&&assistant_contains_any($q,['horas','timesheet','tiempo registrado','hours','tempo']))return assistant_answer_time($lang);
    if(has_permission('project_finance.view')&&assistant_contains_any($q,['costos','coste','gasto','expense','custos','despesas']))return assistant_answer_costs($lang);
    if(has_permission('projects.view')&&assistant_contains_any($q,['proyecto','project','portafolio','portfolio','tarea','kanban','gantt','hito','milestone','bloquead']))return assistant_answer_projects($lang);
    if(has_permission('tickets.view')&&assistant_contains_any($q,['ticket','soporte','helpdesk','sla','support']))return assistant_answer_tickets($lang);
    if(has_permission('billing.view')&&assistant_contains_any($q,['factur','cobro','pago','renovacion','ingreso','billing','charge','payment','receita','faturamento']))return assistant_answer_billing($lang);
    if(has_permission('quotes.view')&&assistant_contains_any($q,['cotiza','presupuesto','orçamento','quote','quotation']))return assistant_answer_quotes($lang);
    if(has_permission('contracts.view')&&assistant_contains_any($q,['contrato','contract','firma','signature']))return assistant_answer_contracts($lang);
    if(has_permission('deals.view')&&assistant_contains_any($q,['negocio','pipeline','oportunidad','deal','forecast','pronostico','previsao']))return assistant_answer_pipeline($lang);
    if(has_permission('appointments.view')&&assistant_contains_any($q,['reunion','reunião','cita','agenda','meeting','appointment','calendario']))return assistant_answer_appointments($lang);
    if(has_permission('security.view')&&assistant_contains_any($q,['seguridad','security','2fa','sesion','integridad','backup','intentos fallidos']))return assistant_answer_security($lang);

    // Intento de búsqueda semántica ligera: usa la frase completa como texto de búsqueda.
    $rows=assistant_search_everywhere(trim($question),$lang);
    if($rows)return ['answer'=>'No identifiqué una orden exacta, pero encontré registros relacionados:<br>'.assistant_search_html($rows),'actions'=>[assistant_action('Ver búsqueda global','global_search.php?q='.rawurlencode(trim($question)),'search','light')],'suggestions'=>['¿Qué puedes hacer por mí?','¿Qué requiere mi atención hoy?']];

    $caps=assistant_capabilities($lang);$names=array_map(fn($c)=>$c['name'],$caps);
    return ['answer'=>'No encontré una coincidencia suficientemente precisa. Puedo ayudarte con <strong>'.assistant_html(implode(', ',$names)).'</strong>. También puedes preguntarme “¿Qué puedo hacer aquí?” para recibir ayuda sobre la pantalla actual.','actions'=>[assistant_action('Abrir Manual 360°','manual.php','manual','light')],'suggestions'=>['¿Qué puedes hacer por mí?','¿Qué requiere mi atención hoy?','Buscar cliente ','Buscar documento ']];
}

function assistant_home_snapshot(): array
{
    return ['attention'=>assistant_attention_snapshot(),'capabilities'=>assistant_capabilities(crm_current_language())];
}
