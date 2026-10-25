<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('manual.view');
$pageTitle=t('manual');
require __DIR__.'/includes/header.php';

$topics = [
 ['clientes','Clientes y contactos','clients','Clientes → Clientes / Contactos','Crea la empresa, agrega personas y consulta desde una sola ficha servicios, negocios, documentos y proyectos.',['Nuevo cliente','Contacto','Ficha 360°']],
 ['negocios','Negocios y Pipeline','kanban','Negocios → Kanban','Administra oportunidades por etapa, responsable, monto, probabilidad y próxima acción.',['Nuevo negocio','Mover etapa','Cerrar ganado']],
 ['cotizaciones','Cotizaciones y contratos','quote','Cotizaciones / Contratos','Prepara propuestas multimoneda, envíalas por correo y conviértelas en contratos o proyectos.',['Cotizar','Enviar PDF','Aprobar']],
 ['catalogo','Catálogo de cotizaciones','catalog','Catálogo de cotizaciones','Administra productos, servicios, categorías y formas de pago que se reutilizan al preparar propuestas.',['Producto','Servicio','Categoría']],
 ['documentos','Gestión documental','documents','Documentos → Biblioteca','Biblioteca empresarial tipo SharePoint con carpetas, versiones, permisos, aprobaciones, enlaces seguros, vencimientos y auditoría.',['Subir','Versionar','Aprobar']],
 ['proyectos','Project Manager','project','Proyectos → Portafolio','Controla responsables, presupuesto, salud, fechas, avance, hitos, tareas, archivos y equipo.',['Nuevo proyecto','Equipo','Hitos']],
 ['kanban','Kanban de proyecto','kanban','Proyecto → Kanban','Mueve tareas entre estados y ve responsable, prioridad y fecha sin abrir cada tarea.',['Por hacer','En progreso','Revisión']],
 ['gantt','Gantt / Project','gantt','Proyecto → Gantt','Visualiza fechas, duración, hitos y dependencias para anticipar atrasos.',['Cronograma','Dependencias','Hitos']],
 ['horas','Horas y recursos','time','Proyecto → Horas / Equipo','Registra horas reales, facturables e internas y compara dedicación del equipo.',['Timesheet','Recursos','Costo/hora']],
 ['riesgos','Riesgos y costos','risk','Proyecto → Riesgos / Costos','Registra riesgos, impacto, mitigación, gastos y desviaciones presupuestarias.',['Riesgo','Mitigación','Gasto']],
 ['facturacion','Facturación y renovaciones','billing','Facturación','Controla ciclos de cobro, pagos, ingresos, próximos vencimientos y renovaciones.',['Registrar pago','Próximo cobro','Ingresos']],
 ['reuniones','Citas y reuniones','calendar','Citas y reuniones','Agenda reuniones vinculadas a cliente, contacto o negocio con responsable y videollamada.',['Programar','Asignar','Notificar']],
 ['usuarios','Usuarios y permisos','users','Usuarios y permisos','Crea cuentas, asigna permisos, restablece accesos, activa 2FA, desactiva o elimina usuarios.',['Crear','Permisos','Eliminar']],
 ['correo','SMTP, Tickets y soporte','support','Configuración → Correo / Soporte','Configura correo saliente, prueba SMTP y conserva tickets aunque el proveedor de correo falle.',['Probar SMTP','Crear ticket','Responder']],
 ['licencia','Licencia y versión','settings','Pie del menú → Licencia','Consulta versión, estado DEMO/ACTIVA, plan, cliente, vencimiento e ID de instalación.',['Versión','Activar','Vencimiento']],
 ['copilot','SÓCRATES Copilot','assistant','Herramientas y ayuda → SÓCRATES Copilot','Copiloto profesional conectado a los módulos instalados y a los datos que tu usuario tiene permiso para consultar. Resume, busca, detecta pendientes y te lleva a la acción correcta.',['Mi día','Resumen','Buscar']],
];

$manualAreas = [
 ['00','INICIO','dashboard','#1976d2','El punto de partida para saber qué requiere atención hoy.',[
   ['Dashboard','dashboard.php','KPI, vencimientos, cotizaciones, ingresos, cobros y gráficos. Úsalo como centro de control antes de entrar a un módulo.','Ver prioridades del día'],
 ]],
 ['01','CRM Y VENTAS','clients','#00acc1','Desde el primer contacto hasta convertir una oportunidad en cliente.',[
   ['Clientes','clients.php','Ficha central de cada empresa: datos fiscales, contactos, servicios, negocios, documentos, facturación y proyectos.','Crear / editar cliente'],
   ['Contactos','contacts.php','Personas asociadas a cada empresa con email, teléfono, cargo e historial.','Gestionar personas'],
   ['Actividades y tareas','activities.php','Llamadas, reuniones, correos, notas y tareas comerciales con responsable y fecha.','Registrar seguimiento'],
   ['Negocios / Pipeline','deals.php','Oportunidades comerciales por etapa, probabilidad, moneda, responsable y próxima acción.','Mover por Kanban'],
   ['Mailing','mailing.php','Envíos simples a clientes y contactos utilizando el SMTP configurado.','Comunicar'],
   ['Campañas de leads','lead_campaigns.php','Agrupa acciones de captación y permite medir de dónde llegan oportunidades.','Captar leads'],
   ['Formularios de captación','lead_forms.php','Crea formularios públicos y transforma respuestas en contactos o negocios.','Generar formularios'],
   ['Citas y reuniones','appointments.php','Agenda reuniones conectadas con cliente, contacto, negocio y responsable.','Programar reunión'],
 ]],
 ['02','COTIZACIONES Y DOCUMENTOS','quote','#eab308','Todo lo necesario para preparar una propuesta y formalizarla.',[
   ['Resumen del catálogo','quote_catalog.php','Vista general del contenido reutilizable disponible para cotizar.','Revisar catálogo'],
   ['Productos y servicios','service_catalog.php','Crea, edita, activa, desactiva o elimina ítems con precios CLP, USD y BRL y textos ES/PT/EN.','Administrar ítems'],
   ['Categorías','service_categories.php','Organiza el catálogo para encontrar rápidamente qué agregar a una cotización.','Ordenar catálogo'],
   ['Formas de pago','payment_methods.php','Datos bancarios, PIX, PayPal, Stripe o instrucciones reutilizables por país, idioma y moneda.','Configurar cobro'],
   ['Cotizaciones','quotations.php','Crea propuestas multimoneda y multidioma, adjunta documentos, envía email y genera PDF.','Cotizar / enviar'],
   ['Contratos','contracts.php','Formaliza servicios con aprobación, estados y PDF para firma.','Enviar contrato'],
   ['Plantillas de contrato','contract_templates.php','Define textos legales reutilizables y variables automáticas.','Editar plantillas'],
 ]],
 ['03','SERVICIOS Y PROYECTOS','project','#22c55e','La operación real: entregar, controlar tiempos y mantener renovaciones.',[
   ['Servicios contratados','services.php','Servicios activos de cada cliente con modalidad, precio, fechas y próximos cobros.','Administrar servicio'],
   ['Vencimientos y renovaciones','expirations.php','Detecta vencidos, próximos vencimientos y servicios que requieren seguimiento.','Renovar a tiempo'],
   ['Dashboard de proyectos','project_dashboard.php','KPI ejecutivos de proyectos activos, carga, avance, riesgos y presupuesto.','Ver salud general'],
   ['Portafolio de proyectos','projects.php','Listado completo de proyectos con cliente, responsable, salud, avance, presupuesto y fechas.','Crear / abrir proyecto'],
   ['Kanban','project_kanban.php','Mueve tareas por estado y controla prioridad, responsable y fecha.','Gestionar trabajo'],
   ['Gantt / Project','project_gantt.php','Cronograma visual para fases, duración, hitos y dependencias.','Planificar fechas'],
   ['Calendario','project_calendar.php','Entregas, tareas e hitos ordenados por fecha.','Ver agenda de proyecto'],
   ['Horas y recursos','project_timesheet.php','Registra horas, compara plan vs. real y controla dedicación/costo del equipo.','Medir esfuerzo'],
   ['Riesgos','project_risks.php','Probabilidad, impacto, responsable y plan de mitigación.','Prevenir problemas'],
   ['Costos y gastos','project_expenses.php','Presupuesto, gastos internos y cargos refacturables al cliente.','Controlar rentabilidad'],
 ]],
 ['04','GESTIÓN DOCUMENTAL','documents','#31b4e7','Biblioteca empresarial para controlar archivos, conocimiento y aprobaciones con trazabilidad.',[
   ['Dashboard documental','document_dashboard.php','Resumen de biblioteca, documentos en revisión, aprobados, próximos vencimientos y actividad reciente.','Ver estado documental'],
   ['Biblioteca de documentos','documents.php','Explora archivos en lista o tarjetas, filtra por carpeta, estado, favoritos, compartidos y vencimiento.','Buscar / abrir documentos'],
   ['Subir documentos','document_upload.php','Carga uno o varios archivos, arrastra y suelta, completa metadatos y relaciónalos con registros del CRM.','Cargar archivos'],
   ['Mis documentos','documents.php?mine=1','Vista personal de documentos propios y administrados por el usuario.','Ver documentos propios'],
   ['Compartidos conmigo','documents.php?shared=1','Reúne documentos a los que tienes acceso por permisos o visibilidad compartida.','Ver compartidos'],
   ['Aprobaciones','document_approvals.php','Controla rutas secuenciales de revisión, decisiones y comentarios con historial.','Aprobar / rechazar'],
   ['Carpetas y organización','document_folders.php','Crea carpetas y subcarpetas para ordenar la biblioteca sin perder relaciones con el CRM.','Organizar biblioteca'],
   ['Rutas de aprobación','document_routes.php','Define circuitos reutilizables con uno o varios responsables en orden.','Diseñar flujo'],
   ['Auditoría documental','document_audit.php','Registra cargas, versiones, vistas, descargas, cambios, compartidos y decisiones.','Revisar trazabilidad'],
   ['Papelera','document_trash.php','Recupera documentos eliminados antes de su eliminación definitiva.','Restaurar documentos'],
 ]],
 ['05','FINANZAS E INFORMES','billing','#14b8a6','Convierte la operación en cobros, ingresos y datos de gestión.',[
   ['Resumen de facturación','billing.php','Cobros del mes, vencidos, MRR/ARR y estado financiero de servicios recurrentes.','Revisar cobros'],
   ['Ciclos de cobro','billing_services.php','Historial de cada ciclo, pagos parciales, próximos cobros y renovaciones automáticas.','Gestionar ciclos'],
   ['Ingresos registrados','payments.php','Registra pagos reales por cliente, moneda, servicio y referencia.','Registrar pago'],
   ['Panel de informes','reports.php','Métricas comerciales, pipeline, cotizaciones, leads, clientes y resultados.','Analizar negocio'],
 ]],
 ['06','HERRAMIENTAS Y AYUDA','manual','#0ea5e9','Utilidades para mover datos, pedir soporte y aprender el sistema.',[
   ['Importar y exportar','data_tools.php','Intercambia información mediante archivos sin depender de edición manual registro por registro.','Mover datos'],
   ['Soporte / Tickets','support.php','Crea y responde casos internos. El historial permanece incluso si el email falla.','Solicitar soporte'],
   ['Centro de ayuda visual','manual.php','Este manual: buscador, pantallas, recorridos y explicación de cada módulo.','Aprender el sistema'],
   ['Tour guiado 360°','tour.php','Recorrido interactivo sobre las pantallas reales con spotlight, navegación automática y progreso guardado.','Aprender recorriendo'],
 ]],
 ['07','ADMINISTRACIÓN','settings','#64748b','Configura quién entra, cómo se protege y cómo se licencia la plataforma.',[
   ['Usuarios y permisos','users.php','Crea cuentas, define roles, permisos, restablece acceso, desactiva o elimina usuarios.','Controlar accesos'],
   ['Centro de Seguridad','security_center.php','2FA, sesiones, intentos fallidos, integridad, auditoría y respaldos.','Proteger sistema'],
   ['Configuración','settings.php','Panel modular para identidad, SMTP, Project Manager, soporte, cotizaciones, contratos, seguridad y licencias.','Configurar plataforma'],
   ['Licencia y versión','license.php','Estado DEMO/ACTIVA, plan, cliente, vencimiento, versión e ID único de instalación.','Gestionar licencia'],
 ]],
];
?>
<style id="s1help-styles">
/* Manual v1.9.8: centro de ayuda 360°, alineado con el nuevo menú por áreas */
.s1help{--navy:#00345b;--navy2:#052b49;--blue:#0074c7;--sky:#31b4e7;--green:#73bc25;--yellow:#f6bd00;--orange:#ff5300;--ink:#12334a;--muted:#668195;--line:#d9e5ed;--bg:#f3f8fb;--card:#fff;max-width:1680px;margin:0 auto;display:grid;gap:26px;color:var(--ink)}
.s1help *{box-sizing:border-box}.s1help a{text-decoration:none}.s1help button,.s1help input{font:inherit}.s1help .ui-icon{width:22px;height:22px}
.s1help-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1.03fr) minmax(420px,.97fr);gap:34px;min-height:460px;padding:52px;border-radius:28px;background:linear-gradient(135deg,#002f57 0%,#005b97 58%,#0b83c8 100%);box-shadow:0 28px 70px rgba(0,47,87,.22);color:#fff}
.s1help-hero:before{content:"";position:absolute;width:540px;height:540px;border-radius:50%;right:-260px;top:-330px;border:1px solid rgba(255,255,255,.15);box-shadow:0 0 0 80px rgba(255,255,255,.025),0 0 0 160px rgba(255,255,255,.018)}
.s1help-hero-copy,.s1help-hero-visual{position:relative;z-index:1}.s1help-eyebrow{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(255,255,255,.25);border-radius:999px;background:rgba(255,255,255,.09);font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.s1help-eyebrow i{width:7px;height:7px;border-radius:50%;background:#73bc25;box-shadow:0 0 0 4px rgba(115,188,37,.16)}
.s1help-hero h1{max-width:720px;margin:21px 0 13px;font-family:'Exo 2',sans-serif;font-size:50px;line-height:1.02;letter-spacing:-.035em;color:#fff}.s1help-hero-copy>p{max-width:700px;margin:0 0 23px;font-size:16px;line-height:1.7;color:#d9effc}
.s1help-search{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:10px;padding:9px 9px 9px 15px;border-radius:14px;background:#fff;box-shadow:0 18px 40px rgba(0,0,0,.18)}.s1help-search svg{color:#0b6ba7}.s1help-search input{width:100%;min-width:0;border:0!important;outline:0!important;background:transparent!important;box-shadow:none!important;padding:10px 2px!important;color:#17374d;font-size:14px}.s1help-search button{border:0;border-radius:9px;background:var(--orange);color:#fff;padding:13px 20px;font-size:11px;font-weight:900;letter-spacing:.03em;cursor:pointer}.s1help-search button:hover{filter:brightness(.95)}
.s1help-chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:11px}.s1help-chips button{border:1px solid rgba(255,255,255,.23);border-radius:999px;background:rgba(255,255,255,.08);color:#fff;padding:7px 11px;font-size:10px;font-weight:700;cursor:pointer}.s1help-chips button:hover{background:#fff;color:#005b97}.s1help-search-status{min-height:18px;margin-top:9px;color:#c6e8f9;font-size:11px}
.s1help-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:14px}.s1help-primary,.s1help-secondary{display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:0 16px;border:0;border-radius:9px;font-weight:900;font-size:11px;cursor:pointer}.s1help-primary{background:var(--orange);color:#fff}.s1help-secondary{background:#fff;color:#0a4e79}
.s1help-browser{margin-top:18px;padding:10px;border-radius:18px;background:#f7fafc;box-shadow:0 30px 65px rgba(0,0,0,.28);transform:perspective(1000px) rotateY(-4deg) rotateX(1deg)}.s1help-browser-top{height:29px;display:flex;align-items:center;gap:5px;padding:0 7px}.s1help-browser-top i{width:7px;height:7px;border-radius:50%;background:#d4dde3}.s1help-browser-top i:nth-child(1){background:#ff7965}.s1help-browser-top i:nth-child(2){background:#f6bd00}.s1help-browser-top i:nth-child(3){background:#73bc25}.s1help-browser-top span{margin-left:7px;flex:1;height:13px;border-radius:7px;background:#e7eef3}.s1help-browser-screen{position:relative;overflow:hidden;border-radius:11px;background:#eaf1f5;aspect-ratio:1.82/1}.s1help-browser-screen img{width:100%;height:100%;object-fit:cover;object-position:top left;display:block}.s1help-browser-screen:after{content:"DASHBOARD REAL";position:absolute;right:10px;bottom:10px;padding:6px 8px;border-radius:6px;background:rgba(0,52,91,.92);color:#fff;font-size:9px;font-weight:900;letter-spacing:.04em}.s1help-version{display:flex;justify-content:space-between;align-items:center;margin-top:12px;color:#d5ecf8;font-size:10px}.s1help-version strong{font-size:18px;font-family:'Exo 2'}
.s1help-panel{padding:30px;border:1px solid var(--line);border-radius:22px;background:var(--card);box-shadow:0 15px 45px rgba(0,47,87,.07);scroll-margin-top:100px}.s1help-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:23px}.s1help-head-num{width:44px;height:44px;display:grid;place-items:center;border-radius:12px;background:var(--navy);color:#fff;font-family:'Exo 2';font-weight:900;flex:0 0 auto}.s1help-head h2{margin:0 0 4px;font-family:'Exo 2';font-size:27px;letter-spacing:-.02em}.s1help-head p{margin:0;color:var(--muted);line-height:1.55}.s1help-head-side{margin-left:auto;padding:7px 10px;border-radius:999px;background:#eff7fc;color:#0872b4;font-size:9px;font-weight:900;letter-spacing:.04em}
.s1help-flow{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}.s1help-flow a{position:relative;min-height:144px;padding:17px;border:1px solid var(--line);border-radius:14px;background:linear-gradient(180deg,#fff,#f8fbfd);color:var(--ink);overflow:hidden;transition:.2s}.s1help-flow a:hover{transform:translateY(-3px);box-shadow:0 14px 28px rgba(0,47,87,.09)}.s1help-flow a:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--accent)}.s1help-flow-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:11px;background:var(--soft);color:var(--accent)}.s1help-flow strong{display:block;margin:12px 0 4px;font-family:'Exo 2';font-size:14px}.s1help-flow small{display:block;color:var(--muted);line-height:1.45}.s1help-flow em{position:absolute;right:12px;top:12px;font-style:normal;font-size:9px;font-weight:900;color:#9ab0bf}
.s1help-screens{display:grid;grid-template-columns:1.35fr .65fr;gap:18px}.s1help-screen-card{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:0 15px 34px rgba(0,47,87,.08);cursor:zoom-in;text-align:left;padding:0}.s1help-screen-card img{display:block;width:100%;height:100%;min-height:280px;max-height:470px;object-fit:cover;object-position:top left}.s1help-screen-card.tall img{min-height:470px;object-fit:cover}.s1help-screen-label{position:absolute;left:13px;bottom:13px;right:13px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 13px;border-radius:10px;background:rgba(0,47,87,.92);backdrop-filter:blur(8px);color:#fff}.s1help-screen-label div{min-width:0}.s1help-screen-label strong,.s1help-screen-label small{display:block}.s1help-screen-label strong{font-family:'Exo 2';font-size:13px}.s1help-screen-label small{margin-top:2px;color:#cce9f8;font-size:9px}.s1help-screen-label b{font-size:9px;white-space:nowrap;padding:6px 8px;border-radius:6px;background:rgba(255,255,255,.12)}
.s1help-callouts{display:grid;gap:11px;margin-top:18px;grid-template-columns:repeat(3,1fr)}.s1help-callout{display:grid;grid-template-columns:auto 1fr;gap:10px;padding:14px;border-radius:12px;border:1px solid var(--line);background:#f8fbfd}.s1help-callout b{width:29px;height:29px;display:grid;place-items:center;border-radius:50%;background:var(--blue);color:#fff;font-size:11px}.s1help-callout strong{display:block;font-family:'Exo 2';font-size:12px}.s1help-callout p{margin:4px 0 0;color:var(--muted);font-size:10px;line-height:1.45}
.s1help-pm-shell{overflow:hidden;border:1px solid var(--line);border-radius:17px;background:#f8fbfd}.s1help-pm-top{display:flex;align-items:center;gap:12px;padding:18px 20px;background:#fff;border-bottom:1px solid var(--line)}.s1help-pm-title{margin-right:auto}.s1help-pm-title strong,.s1help-pm-title small{display:block}.s1help-pm-title strong{font-family:'Exo 2';font-size:19px}.s1help-pm-title small{margin-top:2px;color:var(--muted)}.s1help-pill{padding:7px 9px;border-radius:999px;font-size:9px;font-weight:900}.s1help-pill.green{background:#edf8e3;color:#4f8c16}.s1help-pill.blue{background:#e9f5fd;color:#0872b4}.s1help-pm-tabs{display:flex;gap:6px;overflow:auto;padding:10px 14px;border-bottom:1px solid var(--line);background:#f4f8fb}.s1help-pm-tabs span{white-space:nowrap;padding:8px 11px;border-radius:7px;font-size:9px;font-weight:900;color:#617d90}.s1help-pm-tabs span.active{background:var(--blue);color:#fff}.s1help-pm-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--line)}.s1help-pm-kpis div{padding:14px 17px;background:#fff}.s1help-pm-kpis small,.s1help-pm-kpis strong{display:block}.s1help-pm-kpis small{font-size:8px;font-weight:900;color:#7d95a5;letter-spacing:.04em}.s1help-pm-kpis strong{margin-top:4px;font-family:'Exo 2';font-size:17px}
.s1help-kanban{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:15px}.s1help-kanban-col h4{display:flex;justify-content:space-between;align-items:center;margin:0 0 8px;padding:0 3px;font-size:9px;color:#678092}.s1help-kanban-col h4 b{padding:2px 5px;border-radius:999px;background:#fff;border:1px solid var(--line)}.s1help-task{position:relative;margin-bottom:8px;padding:12px 38px 12px 11px;border:1px solid var(--line);border-radius:9px;background:#fff;box-shadow:0 5px 14px rgba(0,47,87,.05)}.s1help-task:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:9px 0 0 9px;background:var(--task,#0074c7)}.s1help-task strong,.s1help-task small{display:block}.s1help-task strong{font-size:10px;line-height:1.35}.s1help-task small{margin-top:6px;color:#7e96a7;font-size:8px}.s1help-avatar{position:absolute;right:8px;bottom:9px;width:23px;height:23px;display:grid;place-items:center;border-radius:50%;background:#00345b;color:#fff;font-size:7px;font-weight:900}.s1help-gantt{display:grid;grid-template-columns:150px 1fr;margin:0 15px 15px;border:1px solid var(--line);border-radius:10px;overflow:hidden;background:#fff}.s1help-gantt-labels{display:grid;grid-template-rows:29px repeat(4,36px);border-right:1px solid var(--line)}.s1help-gantt-labels>*{padding:9px 10px;border-bottom:1px solid var(--line);font-size:9px}.s1help-gantt-labels b{background:#f4f8fb}.s1help-gantt-grid{position:relative;min-height:173px;background:repeating-linear-gradient(90deg,transparent 0,transparent calc(14.28% - 1px),#e4edf3 calc(14.28% - 1px),#e4edf3 14.28%)}.s1help-gantt-days{height:29px;display:grid;grid-template-columns:repeat(7,1fr);border-bottom:1px solid var(--line);background:#f4f8fb}.s1help-gantt-days span{text-align:center;padding-top:8px;font-size:8px;color:#7891a2}.s1help-gantt-grid i{position:absolute;left:var(--x);top:var(--y);width:var(--w);height:17px;border-radius:5px;background:var(--c)}
.s1help-module-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:13px}.s1help-module-strip div{display:grid;grid-template-columns:auto 1fr;column-gap:10px;align-items:center;padding:13px;border:1px solid var(--line);border-radius:11px;background:#fff}.s1help-module-strip span:first-child{grid-row:1/3;width:35px;height:35px;display:grid;place-items:center;border-radius:9px;background:#eaf5fc;color:#0074c7}.s1help-module-strip strong{font-family:'Exo 2';font-size:11px}.s1help-module-strip small{color:var(--muted);font-size:9px}
.s1help-quick{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.s1help-topic{display:flex;gap:13px;padding:17px;border:1px solid var(--line);border-radius:13px;background:#fff;transition:.2s;scroll-margin-top:110px}.s1help-topic:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(0,47,87,.07);border-color:#bcd9eb}.s1help-topic[hidden]{display:none!important}.s1help-topic-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:11px;background:#eaf5fc;color:#0074c7;flex:0 0 auto}.s1help-topic-route{display:block;margin-bottom:3px;color:#0872b4;font-size:8px;font-weight:900;letter-spacing:.04em;text-transform:uppercase}.s1help-topic h3{margin:0 0 5px;font-family:'Exo 2';font-size:14px}.s1help-topic p{margin:0;color:var(--muted);font-size:10px;line-height:1.5}.s1help-tags{display:flex;gap:4px;flex-wrap:wrap;margin-top:9px}.s1help-tags em{font-style:normal;padding:4px 6px;border-radius:5px;background:#f5f9fb;border:1px solid #dce8ef;color:#49687b;font-size:8px;font-weight:800}.s1help-no-results{display:none;margin-top:14px;padding:16px;border:1px dashed #b8cedc;border-radius:11px;background:#f8fbfd;text-align:center;color:var(--muted)}
.s1help-admin{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.s1help-admin article{display:flex;gap:13px;padding:19px;border-radius:14px;background:var(--navy);color:#fff}.s1help-admin article:nth-child(2){background:#075f99}.s1help-admin article:nth-child(3){background:#17384f}.s1help-admin-icon{width:43px;height:43px;display:grid;place-items:center;border-radius:11px;background:rgba(255,255,255,.1);flex:0 0 auto}.s1help-admin small{font-size:8px;color:#aedbf2;font-weight:900;letter-spacing:.05em}.s1help-admin h3{margin:4px 0 6px;font-family:'Exo 2';font-size:15px}.s1help-admin p{margin:0 0 9px;color:#d8e9f2;font-size:10px;line-height:1.5}.s1help-admin a{color:#fff;font-size:9px;font-weight:900}
.s1help-demo{display:grid;grid-template-columns:repeat(7,1fr);gap:8px}.s1help-demo div{position:relative;padding:14px 9px;border:1px solid var(--line);border-radius:11px;background:#fff;text-align:center}.s1help-demo b{width:27px;height:27px;margin:0 auto 7px;display:grid;place-items:center;border-radius:50%;background:var(--orange);color:#fff;font-size:10px}.s1help-demo strong,.s1help-demo small{display:block}.s1help-demo strong{font-size:10px}.s1help-demo small{margin-top:2px;color:var(--muted);font-size:8px}.s1help-demo div:not(:last-child):after{content:"→";position:absolute;right:-8px;top:50%;z-index:2;color:#9eb1be;font-size:14px;font-weight:900}
.s1help-highlight{outline:3px solid rgba(0,116,199,.18);outline-offset:4px}.s1help-pulse{animation:s1pulse 1.2s ease}@keyframes s1pulse{45%{box-shadow:0 0 0 7px rgba(0,116,199,.12)}}
.s1help-lightbox[hidden]{display:none!important}.s1help-lightbox{position:fixed;z-index:99999;inset:0;display:grid;place-items:center;padding:32px;background:rgba(0,24,42,.9)}.s1help-lightbox>div{max-width:min(1580px,96vw);max-height:90vh;display:flex;flex-direction:column;gap:9px}.s1help-lightbox strong{color:#fff;font-family:'Exo 2';font-size:14px}.s1help-lightbox img{max-width:100%;max-height:84vh;border-radius:10px;box-shadow:0 30px 80px rgba(0,0,0,.45)}.s1help-lightbox-close{position:fixed;right:18px;top:16px;width:43px;height:43px;border:0;border-radius:50%;background:#fff;color:#00345b;font-size:27px;cursor:pointer}.s1help-lightbox-open{overflow:hidden}
html[data-theme="dark"] .s1help{--ink:#edf7fc;--muted:#9fb7c6;--line:#294c62;--bg:#0d2636;--card:#102d40}.s1help-panel,.s1help-topic,.s1help-callout{transition:background .2s}

.s1help-area-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.s1help-area{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:18px;background:linear-gradient(180deg,#fff,#f9fcfe);box-shadow:0 13px 32px rgba(0,47,87,.055);scroll-margin-top:110px}
.s1help-area:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--area)}
.s1help-area-head{display:grid;grid-template-columns:52px 44px 1fr;gap:12px;align-items:center;padding:20px 21px 16px;border-bottom:1px solid var(--line)}
.s1help-area-num{width:52px;height:52px;display:grid;place-items:center;border-radius:14px;background:var(--area);color:#fff;font-family:'Exo 2';font-size:16px;font-weight:900;box-shadow:0 10px 22px color-mix(in srgb,var(--area) 25%,transparent)}
.s1help-area-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:12px;background:color-mix(in srgb,var(--area) 11%,white);color:var(--area)}
.s1help-area-head h3{margin:0 0 3px;font-family:'Exo 2';font-size:18px}.s1help-area-head p{margin:0;color:var(--muted);font-size:11.5px;line-height:1.45}
.s1help-area-items{display:grid;padding:8px 12px 13px}
.s1help-area-item{display:grid;grid-template-columns:42px minmax(0,1fr) auto;gap:12px;align-items:start;padding:13px 10px;border-bottom:1px solid #edf2f6;color:var(--ink);transition:.16s}
.s1help-area-item:last-child{border-bottom:0}.s1help-area-item:hover{background:#f2f8fc;border-radius:10px}
.s1help-area-item>em{width:34px;height:26px;display:grid;place-items:center;border-radius:7px;background:color-mix(in srgb,var(--area) 10%,white);color:var(--area);font-style:normal;font-size:9px;font-weight:900}
.s1help-area-item strong{display:block;font-size:13px}.s1help-area-item p{margin:4px 0 0;color:var(--muted);font-size:10.5px;line-height:1.48}.s1help-area-item small{align-self:center;white-space:nowrap;padding:6px 8px;border-radius:999px;background:#f0f5f8;color:#587286;font-size:8.5px;font-weight:800}
.s1help-menu-legend{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 18px}.s1help-menu-legend span{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid var(--line);border-radius:999px;background:#fff;color:var(--muted);font-size:9px;font-weight:800}.s1help-menu-legend i{width:7px;height:7px;border-radius:50%;background:var(--dot)}
.s1help-menu-note{display:grid;grid-template-columns:auto 1fr;gap:13px;align-items:start;margin-top:18px;padding:16px 18px;border-radius:14px;background:#eff8fd;border:1px solid #cce7f5}.s1help-menu-note>b{width:30px;height:30px;display:grid;place-items:center;border-radius:50%;background:var(--blue);color:#fff}.s1help-menu-note strong{display:block;margin-bottom:3px}.s1help-menu-note p{margin:0;color:var(--muted);font-size:11px;line-height:1.5}
html[data-theme="dark"] .s1help-area{background:#102d40;border-color:#294354}html[data-theme="dark"] .s1help-area-item{border-color:#294354}html[data-theme="dark"] .s1help-area-item:hover{background:#17384d}html[data-theme="dark"] .s1help-area-item small,html[data-theme="dark"] .s1help-menu-legend span{background:#18374b;color:#b9d0df;border-color:#294354}html[data-theme="dark"] .s1help-menu-note{background:#102d40;border-color:#294354}html[data-theme="dark"] .s1help-panel,html[data-theme="dark"] .s1help-topic,html[data-theme="dark"] .s1help-callout,html[data-theme="dark"] .s1help-flow a,html[data-theme="dark"] .s1help-pm-shell,html[data-theme="dark"] .s1help-pm-top,html[data-theme="dark"] .s1help-pm-kpis div,html[data-theme="dark"] .s1help-task,html[data-theme="dark"] .s1help-gantt,html[data-theme="dark"] .s1help-module-strip div,html[data-theme="dark"] .s1help-demo div{background:#102d40;color:#edf7fc}.s1help-screen-card{background:#102d40}
@media(max-width:1200px){.s1help-area-grid{grid-template-columns:1fr}.s1help-hero{grid-template-columns:1fr}.s1help-hero-visual{display:none}.s1help-flow{grid-template-columns:repeat(3,1fr)}.s1help-quick{grid-template-columns:repeat(2,1fr)}.s1help-admin{grid-template-columns:1fr}.s1help-demo{grid-template-columns:repeat(4,1fr)}.s1help-demo div:after{display:none}}
@media(max-width:900px){.s1help-hero{padding:34px}.s1help-hero h1{font-size:40px}.s1help-screens{grid-template-columns:1fr}.s1help-screen-card.tall img{min-height:320px}.s1help-callouts,.s1help-flow,.s1help-kanban,.s1help-module-strip{grid-template-columns:repeat(2,1fr)}.s1help-pm-kpis{grid-template-columns:repeat(2,1fr)}.s1help-demo{grid-template-columns:repeat(2,1fr)}}
@media(max-width:620px){.s1help-area-head{grid-template-columns:44px 1fr}.s1help-area-icon{display:none}.s1help-area-item{grid-template-columns:36px 1fr}.s1help-area-item small{grid-column:2;justify-self:start}.s1help{gap:15px}.s1help-hero{padding:23px;border-radius:18px}.s1help-hero h1{font-size:31px}.s1help-search{grid-template-columns:auto 1fr}.s1help-search button{grid-column:1/-1}.s1help-panel{padding:18px;border-radius:15px}.s1help-head h2{font-size:21px}.s1help-head-side{display:none}.s1help-flow,.s1help-callouts,.s1help-kanban,.s1help-module-strip,.s1help-quick,.s1help-pm-kpis,.s1help-demo{grid-template-columns:1fr}.s1help-gantt{grid-template-columns:92px 1fr}.s1help-screen-card img,.s1help-screen-card.tall img{min-height:230px}.s1help-admin article{padding:16px}}
@media print{.s1help-search,.s1help-chips,.s1help-actions,.s1help-lightbox,.s1help-screen-label b{display:none!important}.s1help{gap:12px}.s1help-hero{min-height:auto;background:#00345b!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.s1help-panel,.s1help-admin article{box-shadow:none;break-inside:avoid}.s1help-quick{grid-template-columns:repeat(2,1fr)}}

.s1help-v2-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.s1help-v2-item{position:relative;min-height:118px;padding:15px 14px 14px 48px;border:1px solid var(--line);border-radius:12px;background:#fff}.s1help-v2-item>b{position:absolute;left:13px;top:14px;width:25px;height:25px;border-radius:50%;display:grid;place-items:center;background:var(--navy);color:#fff;font:900 10px 'Exo 2'}.s1help-v2-item strong{display:block;font-family:'Exo 2';font-size:12px}.s1help-v2-item p{margin:5px 0 0;color:var(--muted);font-size:10px;line-height:1.45}.s1help-v2-gallery{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px}.s1help-v2-gallery .s1help-screen-card img{aspect-ratio:16/7;object-fit:cover;object-position:top}.s1help-v2-caption{display:grid;grid-template-columns:32px 1fr;gap:9px;align-items:start;margin-top:10px}.s1help-v2-caption b{width:28px;height:28px;border-radius:8px;display:grid;place-items:center;background:var(--blue);color:#fff;font-size:10px}.s1help-v2-caption strong{font-family:'Exo 2';font-size:12px;display:block}.s1help-v2-caption small{display:block;color:var(--muted);margin-top:2px;line-height:1.35}.s1help-product-note{margin-top:16px;padding:15px 17px;border:1px solid #bfe4f8;background:#effaff;border-radius:12px;display:flex;gap:12px}.s1help-product-note>span{width:35px;height:35px;display:grid;place-items:center;border-radius:9px;background:var(--blue);color:#fff;flex:0 0 auto}.s1help-product-note strong{display:block;font-family:'Exo 2'}.s1help-product-note p{margin:4px 0 0;color:var(--muted);font-size:10.5px;line-height:1.45}html[data-theme="dark"] .s1help-v2-item{background:#102d40}html[data-theme="dark"] .s1help-product-note{background:#102d40;border-color:#294354}@media(max-width:1000px){.s1help-v2-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.s1help-v2-grid,.s1help-v2-gallery{grid-template-columns:1fr}}

.s1help-doc-flow{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:16px}.s1help-doc-flow a{position:relative;padding:16px;border:1px solid var(--line);border-radius:13px;background:linear-gradient(180deg,#fff,#f6fbfe);color:var(--ink);min-height:130px;transition:.2s}.s1help-doc-flow a:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(0,47,87,.08)}.s1help-doc-flow b{position:absolute;right:11px;top:10px;color:#93aaba;font:900 9px 'Exo 2'}.s1help-doc-flow span{width:39px;height:39px;display:grid;place-items:center;border-radius:10px;background:#e9f7ff;color:#0074c7}.s1help-doc-flow span .ui-icon{width:22px;height:22px}.s1help-doc-flow strong,.s1help-doc-flow small{display:block}.s1help-doc-flow strong{margin-top:12px;font:800 12px 'Exo 2'}.s1help-doc-flow small{margin-top:4px;color:var(--muted);font-size:9px;line-height:1.4}.s1help-doc-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.s1help-doc-grid article{display:grid;grid-template-columns:auto 1fr;gap:10px;padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8fbfd}.s1help-doc-grid article>span{width:36px;height:36px;display:grid;place-items:center;border-radius:9px;background:#e9f7ff;color:#0074c7}.s1help-doc-grid article>span .ui-icon{width:20px;height:20px}.s1help-doc-grid strong{font:800 11px 'Exo 2'}.s1help-doc-grid p{margin:4px 0 0;color:var(--muted);font-size:9.5px;line-height:1.45}html[data-theme="dark"] .s1help-doc-flow a,html[data-theme="dark"] .s1help-doc-grid article{background:#102d40;border-color:#294354}@media(max-width:1050px){.s1help-doc-flow{grid-template-columns:repeat(3,1fr)}.s1help-doc-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.s1help-doc-flow,.s1help-doc-grid{grid-template-columns:1fr}}
</style>

<div class="s1help" id="s1help">
  <section class="s1help-hero">
    <div class="s1help-hero-copy">
      <span class="s1help-eyebrow"><i></i> Centro de ayuda · v<?=e(crm_app_version())?></span>
      <h1>Aprende el sistema mirando el sistema.</h1>
      <p>Una guía 360° de CRM + Project Manager. El menú y el manual están organizados por las mismas áreas; cada sección está numerada, explicada y conectada con la pantalla que debes abrir.</p>
      <div class="s1help-search">
        <?=crm_icon('search')?>
        <input id="s1helpSearch" type="search" placeholder="¿Qué quieres hacer? Ej.: crear proyecto, Gantt, usuario, SMTP…" autocomplete="off">
        <button type="button" id="s1helpSearchBtn">BUSCAR</button>
      </div>
      <div class="s1help-chips">
        <button type="button" data-s1query="proyecto">Proyecto</button><button type="button" data-s1query="kanban">Kanban</button><button type="button" data-s1query="gantt">Gantt</button><button type="button" data-s1query="cotización">Cotización</button><button type="button" data-s1query="usuario">Usuarios</button><button type="button" data-s1query="smtp">SMTP</button><button type="button" data-s1query="documentos">Documentos</button>
      </div>
      <div class="s1help-search-status" id="s1helpStatus" aria-live="polite"></div>
      <div class="s1help-actions">
        <button class="s1help-primary" type="button" onclick="window.print()"><?=crm_icon('manual')?> IMPRIMIR / GUARDAR PDF</button>
        <a class="s1help-secondary" href="tour.php">INICIAR TOUR GUIADO 360° →</a>
      </div>
    </div>
    <div class="s1help-hero-visual" aria-hidden="true">
      <div class="s1help-browser">
        <div class="s1help-browser-top"><i></i><i></i><i></i><span></span></div>
        <div class="s1help-browser-screen"><img src="assets/img/manual/dashboard.jpg" alt=""></div>
      </div>
      <div class="s1help-version"><span>CRM · PROJECTS · DOCUMENTS · BILLING · SUPPORT</span><strong><?=e(crm_app_version())?></strong></div>
    </div>
  </section>

  <section class="s1help-panel" id="s1helpV2" data-s1search="version 2.0 novedades iconos whatsapp calendario helpdesk soporte seguridad licencia configuración login branding traducciones drag drop">
    <div class="s1help-head"><span class="s1help-head-num">2.0</span><div><h2>Qué cambia en SÓCRATES CRM 2.0</h2><p>La versión 2.0 convierte el CRM + Project Manager en un producto más visual, explicable y preparado para instalarse en empresas distintas.</p></div><span class="s1help-head-side">ACTUALIZACIÓN MAYOR</span></div>
    <div class="s1help-v2-grid">
      <div class="s1help-v2-item"><b>01</b><strong>Contacto por iconos</strong><p>Email y WhatsApp dejan de ocupar texto: ahora son acciones compactas con tooltip.</p></div>
      <div class="s1help-v2-item"><b>02</b><strong>Módulos de proyecto visibles</strong><p>Kanban, Gantt, Calendario, Horas, Riesgos y Costos se presentan como módulos independientes que se pueden abrir directamente.</p></div>
      <div class="s1help-v2-item"><b>03</b><strong>ES / PT / EN revisado</strong><p>Menús, títulos y rutas principales comparten el mismo vocabulario en los tres idiomas.</p></div>
      <div class="s1help-v2-item"><b>04</b><strong>Calendario compartible</strong><p>Comparte por WhatsApp y email, abre Google Calendar o descarga .ICS para Outlook y Apple Calendar.</p></div>
      <div class="s1help-v2-item"><b>05</b><strong>Horas comprensibles</strong><p>KPI, carga por persona, porcentaje facturable, tarifa estimada y tabla detallada explican el esfuerzo real.</p></div>
      <div class="s1help-v2-item"><b>06</b><strong>Riesgos siempre visibles</strong><p>Cuando existen riesgos activos, la pestaña Riesgos cambia a rojo y pulsa para no pasar desapercibida.</p></div>
      <div class="s1help-v2-item"><b>07</b><strong>Centro de costos</strong><p>Resumen por moneda, categorías y una tabla espaciosa separan gastos internos de cargos refacturables.</p></div>
      <div class="s1help-v2-item"><b>08</b><strong>Importar / Exportar guiado</strong><p>La pantalla explica el proceso en tres pasos y muestra cada exportación como una tarjeta independiente.</p></div>
      <div class="s1help-v2-item"><b>09</b><strong>Helpdesk real</strong><p>Tickets con etapa, responsable, prioridad, SLA, seguimiento, notas internas, resolución y línea de tiempo.</p></div>
      <div class="s1help-v2-item"><b>10</b><strong>Centro de Seguridad</strong><p>Sesiones, intentos, 2FA, integridad, respaldo y auditoría se presentan como controles accionables.</p></div>
      <div class="s1help-v2-item"><b>11</b><strong>Configuración modular</strong><p>Las áreas de configuración son tarjetas que se pueden arrastrar y cambiar entre S, M, L y XL.</p></div>
      <div class="s1help-v2-item"><b>12</b><strong>Dashboard drag & drop</strong><p>En modo Personalizar puedes mover widgets y cambiar tamaño; el orden se guarda por usuario.</p></div>
      <div class="s1help-v2-item"><b>13</b><strong>Licencia protegida</strong><p>En DEMO los datos de plan permanecen visibles pero bloqueados. Solo un código validado por el servidor puede activar la licencia.</p></div>
      <div class="s1help-v2-item"><b>14</b><strong>Branding del cliente</strong><p>El logo superior pertenece a la empresa instalada. SÓCRATES CRM queda identificado abajo como desarrollador/producto.</p></div>
      <div class="s1help-v2-item"><b>15</b><strong>Catálogo compacto</strong><p>Editar, activar/desactivar y eliminar productos o servicios usan iconos con explicación al pasar el mouse.</p></div>
      <div class="s1help-v2-item"><b>16</b><strong>Login de producto</strong><p>La portada de acceso explica qué incluye SÓCRATES CRM en una composición de dos columnas.</p></div>
      <div class="s1help-v2-item"><b>17</b><strong>Manual actualizado</strong><p>Este documento incorpora el flujo comercial, operación de proyectos, soporte, licencia, seguridad y administración.</p></div>
      <div class="s1help-v2-item"><b>18</b><strong>Preparado para comercializar</strong><p>Identidad por cliente, DEMO, validación remota de licencia y configuración modular preparan instalaciones de terceros.</p></div>
      <div class="s1help-v2-item"><b>19</b><strong>Document Manager 360°</strong><p>Biblioteca tipo SharePoint con carpetas, versiones, permisos, aprobaciones, enlaces externos, relaciones, vencimientos, auditoría y papelera.</p></div>
    </div>
    <div class="s1help-v2-gallery">
      <div><button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/v2/project-dashboard.png" data-s1title="Project Manager · Centro de mando"><img src="assets/img/manual/v2/project-dashboard.png" alt="Dashboard del Project Manager" loading="lazy"></button><div class="s1help-v2-caption"><b>A</b><div><strong>Centro de mando</strong><small>Resume proyectos activos, planificación, riesgos, atrasos, entregas y horas del mes.</small></div></div></div>
      <div><button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/v2/gantt.png" data-s1title="Gantt global"><img src="assets/img/manual/v2/gantt.png" alt="Gantt global" loading="lazy"></button><div class="s1help-v2-caption"><b>B</b><div><strong>Gantt global</strong><small>Cada barra representa una tarea y permite entender fechas, duración y proyectos de forma independiente.</small></div></div></div>
      <div><button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/v2/calendar.png" data-s1title="Calendario de proyectos"><img src="assets/img/manual/v2/calendar.png" alt="Calendario del Project Manager" loading="lazy"></button><div class="s1help-v2-caption"><b>C</b><div><strong>Calendario</strong><small>Las entregas se pueden consultar por proyecto y compartir con calendarios externos.</small></div></div></div>
      <div><button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/v2/hours.png" data-s1title="Horas y recursos"><img src="assets/img/manual/v2/hours.png" alt="Horas y recursos" loading="lazy"></button><div class="s1help-v2-caption"><b>D</b><div><strong>Horas y recursos</strong><small>La versión 2.0 agrega lectura de carga, facturabilidad y valor estimado.</small></div></div></div>
      <div><button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/v2/risks.png" data-s1title="Riesgos de proyecto"><img src="assets/img/manual/v2/risks.png" alt="Riesgos" loading="lazy"></button><div class="s1help-v2-caption"><b>E</b><div><strong>Riesgos</strong><small>Probabilidad × impacto, responsable, estado y fecha objetivo en una lectura visual.</small></div></div></div>
      <div><button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/v2/costs.png" data-s1title="Costos de proyecto"><img src="assets/img/manual/v2/costs.png" alt="Costos" loading="lazy"></button><div class="s1help-v2-caption"><b>F</b><div><strong>Costos</strong><small>La nueva vista separa moneda, categoría y condición facturable para facilitar el control.</small></div></div></div>
      <div><button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/v2/import-export.png" data-s1title="Importar y exportar"><img src="assets/img/manual/v2/import-export.png" alt="Importar y exportar" loading="lazy"></button><div class="s1help-v2-caption"><b>G</b><div><strong>Importar y exportar</strong><small>Un flujo guiado permite entender qué entra, qué sale y cómo respaldar datos.</small></div></div></div>
    </div>
    <div class="s1help-product-note"><span><?=crm_icon('shield')?></span><div><strong>Licencias: diferencia entre DEMO y producción</strong><p>Una instalación DEMO puede mostrar los planes y características, pero no puede autoactivarse. La activación solo ocurre cuando el código es validado contra el servidor de licencias configurado por SÓCRATES CRM.</p></div></div>
  </section>

  <section class="s1help-panel" id="s1helpRecorrido" data-s1search="recorrido flujo captar vender ejecutar cobrar renovar cliente negocio proyecto factura dashboard">
    <div class="s1help-head"><span class="s1help-head-num">01</span><div><h2>El sistema en una sola mirada</h2><p>Este es el flujo recomendado tanto para trabajar como para hacer una demostración a un cliente.</p></div><span class="s1help-head-side">FLUJO 360°</span></div>
    <div class="s1help-flow">
      <a href="#clientes" style="--accent:#31b4e7;--soft:#e8f8fe"><em>01</em><span class="s1help-flow-icon"><?=crm_icon('clients')?></span><strong>CAPTAR</strong><small>Cliente · Contacto · Lead</small></a>
      <a href="#negocios" style="--accent:#0074c7;--soft:#e9f4fc"><em>02</em><span class="s1help-flow-icon"><?=crm_icon('kanban')?></span><strong>VENDER</strong><small>Negocio · Cotización · Contrato</small></a>
      <a href="#proyectos" style="--accent:#73bc25;--soft:#f1f9e9"><em>03</em><span class="s1help-flow-icon"><?=crm_icon('project')?></span><strong>EJECUTAR</strong><small>Proyecto · Tareas · Gantt</small></a>
      <a href="#facturacion" style="--accent:#f6bd00;--soft:#fff8dc"><em>04</em><span class="s1help-flow-icon"><?=crm_icon('billing')?></span><strong>COBRAR</strong><small>Ciclo · Pago · Ingreso</small></a>
      <a href="#facturacion" style="--accent:#ff5300;--soft:#fff0ea"><em>05</em><span class="s1help-flow-icon"><?=crm_icon('time')?></span><strong>RENOVAR</strong><small>Alertas · Vencimientos</small></a>
    </div>
  </section>

  <section class="s1help-panel" id="mapaMenu" data-s1search="menú mapa secciones areas crm ventas cotizaciones documentos operación proyectos finanzas informes herramientas administración configuración licencia">
    <div class="s1help-head"><span class="s1help-head-num">02</span><div><h2>Mapa completo del sistema</h2><p>El menú ahora está ordenado por áreas de trabajo. Cada número identifica una sección; debajo ves exactamente para qué sirve y qué acción principal realiza.</p></div><span class="s1help-head-side">TODAS LAS SECCIONES</span></div>
    <div class="s1help-menu-legend"><span style="--dot:#1976d2"><i></i> Inicio</span><span style="--dot:#00acc1"><i></i> Comercial</span><span style="--dot:#eab308"><i></i> Ventas</span><span style="--dot:#22c55e"><i></i> Operación</span><span style="--dot:#31b4e7"><i></i> Documentos</span><span style="--dot:#14b8a6"><i></i> Control</span><span style="--dot:#0ea5e9"><i></i> Ayuda</span><span style="--dot:#64748b"><i></i> Sistema</span></div>
    <div class="s1help-area-grid">
      <?php foreach($manualAreas as [$num,$areaTitle,$areaIcon,$areaColor,$areaDesc,$items]):?>
      <article class="s1help-area" style="--area:<?=e($areaColor)?>" data-s1search="<?=e($areaTitle.' '.$areaDesc.' '.implode(' ',array_column($items,0)))?>">
        <header class="s1help-area-head"><span class="s1help-area-num"><?=e($num)?></span><span class="s1help-area-icon"><?=crm_icon($areaIcon)?></span><div><h3><?=e($areaTitle)?></h3><p><?=e($areaDesc)?></p></div></header>
        <div class="s1help-area-items">
          <?php foreach($items as $i=>$item): [$itemTitle,$itemHref,$itemDesc,$itemAction]=$item;?>
          <a class="s1help-area-item" href="<?=e($itemHref)?>"><em><?=e($num.'.'.($i+1))?></em><div><strong><?=e($itemTitle)?></strong><p><?=e($itemDesc)?></p></div><small><?=e($itemAction)?> →</small></a>
          <?php endforeach;?>
        </div>
      </article>
      <?php endforeach;?>
    </div>
    <div class="s1help-menu-note"><b>✓</b><div><strong>El manual y el menú hablan el mismo idioma.</strong><p>Si el usuario aprende “CRM y ventas”, “Cotizaciones y documentos”, “Servicios y proyectos”, “Gestión documental”, “Finanzas e informes”, “Herramientas y ayuda” y “Administración”, encuentra exactamente esos mismos grupos en la barra lateral.</p></div></div>
  </section>

  <section class="s1help-panel" data-s1search="pantallas reales dashboard menu navegación facturación ingresos reuniones citas crm">
    <div class="s1help-head"><span class="s1help-head-num">03</span><div><h2>Pantallas reales del sistema</h2><p>Haz clic en cualquier imagen para verla a pantalla completa. Esta sección usa capturas del propio CRM.</p></div><span class="s1help-head-side">CLICK PARA AMPLIAR</span></div>
    <div class="s1help-screens">
      <button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/dashboard.jpg" data-s1title="Dashboard · centro de control">
        <img src="assets/img/manual/dashboard.jpg" alt="Dashboard real del CRM" loading="lazy"><span class="s1help-screen-label"><div><strong>Dashboard · Centro de control</strong><small>KPI, vencimientos, cotizaciones e ingresos.</small></div><b>AMPLIAR ↗</b></span>
      </button>
      <button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/reports.jpg" data-s1title="Panel de informes del CRM">
        <img src="assets/img/manual/reports.jpg" alt="Panel real de informes" loading="lazy"><span class="s1help-screen-label"><div><strong>Informes · Visión ejecutiva</strong><small>Clientes, contactos, negocios, leads y pronósticos.</small></div><b>AMPLIAR ↗</b></span>
      </button>
    </div>
    <div class="s1help-callouts">
      <div class="s1help-callout"><b>1</b><div><strong>Empieza por el Dashboard</strong><p>Los KPI muestran qué necesita atención antes de abrir módulos específicos.</p></div></div>
      <div class="s1help-callout"><b>2</b><div><strong>Menú agrupado por áreas</strong><p>La barra lateral ahora resume todo en siete grupos de trabajo más Dashboard, reduciendo desplazamiento y haciendo más fácil encontrar cada función.</p></div></div>
      <div class="s1help-callout"><b>3</b><div><strong>Búsqueda global</strong><p>Usa la barra superior para encontrar registros y funciones desde cualquier pantalla.</p></div></div>
    </div>
  </section>

  <section class="s1help-panel" id="proyectos" data-s1search="project manager proyecto portafolio kanban gantt calendario tareas hitos equipo horas riesgos costos presupuesto dependencias">
    <div class="s1help-head"><span class="s1help-head-num">04</span><div><h2>Project Manager: ejecución completa</h2><p>Un proyecto conecta planificación, tareas, responsables, cronograma, horas, riesgos, costos y archivos.</p></div><span class="s1help-head-side">OPERACIÓN</span></div>
    <div class="s1help-pm-shell">
      <div class="s1help-pm-top"><div class="s1help-pm-title"><strong>Rediseño Plataforma Digital</strong><small>PRJ-2026-014 · Cliente Demo</small></div><span class="s1help-pill green">EN CURSO</span><span class="s1help-pill blue">68% AVANCE</span></div>
      <div class="s1help-pm-tabs"><span>PORTAFOLIO</span><span class="active">KANBAN</span><span>GANTT</span><span>CALENDARIO</span><span>HORAS</span><span>RIESGOS</span><span>COSTOS</span></div>
      <div class="s1help-pm-kpis"><div><small>FECHA LÍMITE</small><strong>28 AGO</strong></div><div><small>TAREAS</small><strong>23 / 34</strong></div><div><small>HORAS REALES</small><strong>116 h</strong></div><div><small>PRESUPUESTO USADO</small><strong>72%</strong></div></div>
      <div class="s1help-kanban">
        <div class="s1help-kanban-col"><h4>POR HACER <b>5</b></h4><article class="s1help-task" style="--task:#31b4e7"><strong>Preparar contenido SEO</strong><small>Media · 18 Ago</small><span class="s1help-avatar">LP</span></article><article class="s1help-task" style="--task:#f6bd00"><strong>Integrar formulario</strong><small>Alta · 20 Ago</small><span class="s1help-avatar">RB</span></article></div>
        <div class="s1help-kanban-col"><h4>EN PROGRESO <b>4</b></h4><article class="s1help-task" style="--task:#ff5300"><strong>Desarrollo landing</strong><small>Urgente · 16 Ago</small><span class="s1help-avatar">MR</span></article><article class="s1help-task" style="--task:#0074c7"><strong>Responsive tablet</strong><small>Media · 17 Ago</small><span class="s1help-avatar">RB</span></article></div>
        <div class="s1help-kanban-col"><h4>REVISIÓN <b>2</b></h4><article class="s1help-task" style="--task:#9a54d3"><strong>Home page final</strong><small>Alta · 14 Ago</small><span class="s1help-avatar">SB</span></article></div>
        <div class="s1help-kanban-col"><h4>FINALIZADO <b>9</b></h4><article class="s1help-task" style="--task:#73bc25"><strong>Wireframes aprobados</strong><small>Completada</small><span class="s1help-avatar">SB</span></article><article class="s1help-task" style="--task:#73bc25"><strong>Arquitectura técnica</strong><small>Completada</small><span class="s1help-avatar">RB</span></article></div>
      </div>
      <div class="s1help-gantt"><div class="s1help-gantt-labels"><b>FASE</b><span>Brief + UX</span><span>Diseño UI</span><span>Desarrollo</span><span>QA + Lanzamiento</span></div><div class="s1help-gantt-grid"><div class="s1help-gantt-days"><span>11</span><span>14</span><span>17</span><span>20</span><span>23</span><span>26</span><span>29</span></div><i style="--x:2%;--w:24%;--y:39px;--c:#31b4e7"></i><i style="--x:18%;--w:31%;--y:75px;--c:#0074c7"></i><i style="--x:41%;--w:38%;--y:111px;--c:#73bc25"></i><i style="--x:73%;--w:24%;--y:147px;--c:#ff5300"></i></div></div>
    </div>
    <div class="s1help-module-strip"><div><span><?=crm_icon('kanban')?></span><strong>Kanban</strong><small>Estado de tareas</small></div><div><span><?=crm_icon('gantt')?></span><strong>Gantt</strong><small>Fechas y dependencias</small></div><div><span><?=crm_icon('time')?></span><strong>Horas</strong><small>Plan vs. real</small></div><div><span><?=crm_icon('risk')?></span><strong>Riesgos</strong><small>Impacto y mitigación</small></div></div>
  </section>

  <section class="s1help-panel" id="facturacion" data-s1search="facturación ingresos pagos cobros renovaciones vencimientos mensual anual moneda clp usd brl">
    <div class="s1help-head"><span class="s1help-head-num">05</span><div><h2>Cobros, ingresos y reuniones</h2><p>Dos pantallas clave para cerrar el ciclo comercial: registrar dinero y mantener el seguimiento con el cliente.</p></div><span class="s1help-head-side">GESTIÓN DIARIA</span></div>
    <div class="s1help-screens">
      <button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/billing.jpg" data-s1title="Facturación · ingresos registrados"><img src="assets/img/manual/billing.jpg" alt="Ingresos registrados" loading="lazy"><span class="s1help-screen-label"><div><strong>Facturación · Ingresos</strong><small>CLP, USD y BRL separados.</small></div><b>AMPLIAR ↗</b></span></button>
      <button class="s1help-screen-card" type="button" data-s1lightbox="assets/img/manual/appointments.jpg" data-s1title="Citas y reuniones"><img src="assets/img/manual/appointments.jpg" alt="Formulario de citas y reuniones" loading="lazy"><span class="s1help-screen-label"><div><strong>Citas y reuniones</strong><small>Cliente, contacto, negocio y responsable.</small></div><b>AMPLIAR ↗</b></span></button>
    </div>
    <div class="s1help-callouts"><div class="s1help-callout"><b>1</b><div><strong>Ingresos por moneda</strong><p>Evita mezclar CLP, USD y BRL; filtra historial por cliente, referencia y fecha.</p></div></div><div class="s1help-callout"><b>2</b><div><strong>Próximo ciclo</strong><p>Al registrar un pago, el servicio puede avanzar automáticamente al próximo vencimiento.</p></div></div><div class="s1help-callout"><b>3</b><div><strong>Reunión contextual</strong><p>Vincula la cita con cliente, contacto y negocio para mantener una trazabilidad real.</p></div></div></div>
  </section>

  <section class="s1help-panel" id="s1helpFunciones">
    <div class="s1help-head"><span class="s1help-head-num">06</span><div><h2>Guía rápida por función</h2><p>El buscador de arriba filtra estas guías al instante. No consulta MySQL y no puede provocar errores de base de datos.</p></div><span class="s1help-head-side"><?=count($topics)?> GUÍAS</span></div>
    <div class="s1help-quick" id="s1helpTopicGrid">
      <?php foreach($topics as [$anchor,$title,$icon,$route,$text,$actions]):?>
      <article class="s1help-topic" id="<?=e($anchor)?>" data-s1search="<?=e($anchor.' '.$title.' '.$route.' '.$text.' '.implode(' ',$actions))?>"><span class="s1help-topic-icon"><?=crm_icon($icon)?></span><div><span class="s1help-topic-route"><?=e($route)?></span><h3><?=e($title)?></h3><p><?=e($text)?></p><div class="s1help-tags"><?php foreach($actions as $a):?><em><?=e($a)?></em><?php endforeach;?></div></div></article>
      <?php endforeach;?>
    </div>
    <div class="s1help-no-results" id="s1helpNoResults">No encontré esa función. Prueba con <b>proyecto</b>, <b>cliente</b>, <b>pago</b>, <b>usuario</b>, <b>ticket</b> o <b>SMTP</b>, <b>catálogo</b> o <b>licencia</b>.</div>
  </section>

  <section class="s1help-admin" data-s1search="usuarios permisos seguridad 2fa smtp correo soporte ticket administrador">
    <article><span class="s1help-admin-icon"><?=crm_icon('users')?></span><div><small>ADMINISTRACIÓN</small><h3>Usuarios y permisos</h3><p>Crea cuentas, restablece accesos, asigna permisos, desactiva o elimina usuarios con protección del administrador.</p><a href="users.php">ABRIR USUARIOS →</a></div></article>
    <article><span class="s1help-admin-icon"><?=crm_icon('settings')?></span><div><small>COMUNICACIONES</small><h3>SMTP y Tickets</h3><p>Prueba conexión SMTP y envío real. Si el email falla, la conversación del ticket permanece guardada dentro del CRM.</p><a href="settings.php">ABRIR CONFIGURACIÓN →</a></div></article>
    <article><span class="s1help-admin-icon"><?=crm_icon('shield')?></span><div><small>SEGURIDAD</small><h3>2FA y auditoría</h3><p>Activa autenticación en dos pasos, revisa sesiones, intentos fallidos, integridad y copias de seguridad.</p><a href="security_center.php">ABRIR SEGURIDAD →</a></div></article>
  </section>

  <section class="s1help-panel" data-s1search="demo demostración recorrido cliente presentación mostrar">
    <div class="s1help-head"><span class="s1help-head-num">07</span><div><h2>Recorrido ideal para una demostración</h2><p>En 7 pasos puedes enseñar prácticamente todo el valor del sistema sin perderte entre módulos.</p></div><span class="s1help-head-side">7 PASOS</span></div>
    <div class="s1help-demo"><div><b>1</b><strong>Dashboard</strong><small>KPI + gráficos</small></div><div><b>2</b><strong>Cliente</strong><small>Ficha 360°</small></div><div><b>3</b><strong>Negocio</strong><small>Pipeline</small></div><div><b>4</b><strong>Cotización</strong><small>PDF + email</small></div><div><b>5</b><strong>Proyecto</strong><small>Kanban + Gantt</small></div><div><b>6</b><strong>Horas / Riesgos</strong><small>Control real</small></div><div><b>7</b><strong>Facturación</strong><small>Cobrar + renovar</small></div></div>
  </section>
  <section class="s1help-panel" id="gestion-documental" data-s1search="documentos document manager sharepoint biblioteca carpetas versiones permisos aprobaciones compartir enlaces auditoría papelera vencimientos clientes proyectos contratos tickets">
    <div class="s1help-head"><span class="s1help-head-num">08</span><div><h2>Document Manager: gestión documental 360°</h2><p>Una biblioteca empresarial tipo SharePoint para centralizar archivos, versiones, permisos, aprobaciones y trazabilidad dentro del mismo CRM.</p></div><span class="s1help-head-side">NUEVO · v2.1</span></div>
    <div class="s1help-doc-flow">
      <a href="document_dashboard.php"><b>01</b><span><?=crm_icon('dashboard')?></span><strong>DASHBOARD</strong><small>Estado, vencimientos y aprobaciones</small></a>
      <a href="documents.php"><b>02</b><span><?=crm_icon('documents')?></span><strong>BIBLIOTECA</strong><small>Lista, tarjetas, filtros y favoritos</small></a>
      <a href="document_upload.php"><b>03</b><span><?=crm_icon('plus')?></span><strong>CARGAR</strong><small>Uno o varios archivos por drag & drop</small></a>
      <a href="document_approvals.php"><b>04</b><span><?=crm_icon('shield')?></span><strong>APROBAR</strong><small>Rutas secuenciales y decisiones</small></a>
      <a href="document_audit.php"><b>05</b><span><?=crm_icon('report')?></span><strong>AUDITAR</strong><small>Quién hizo qué y cuándo</small></a>
    </div>
    <div class="s1help-doc-grid">
      <article><span><?=crm_icon('catalog')?></span><div><strong>Carpetas y subcarpetas</strong><p>Organiza el repositorio sin perder la relación del archivo con clientes, proyectos o contratos.</p></div></article>
      <article><span><?=crm_icon('history')?></span><div><strong>Versionado no destructivo</strong><p>Cada archivo actualizado crea una versión nueva. Puedes descargar o restaurar versiones anteriores.</p></div></article>
      <article><span><?=crm_icon('users')?></span><div><strong>Permisos y visibilidad</strong><p>Privado, empresa y acceso explícito por usuario, rol o departamento con control de edición y compartido.</p></div></article>
      <article><span><?=crm_icon('shield')?></span><div><strong>Rutas de aprobación</strong><p>Secuencias de aprobadores con estados, decisiones y comentarios que quedan en el historial.</p></div></article>
      <article><span><?=crm_icon('share')?></span><div><strong>Enlaces externos seguros</strong><p>Comparte con contraseña, fecha de expiración, límite de descargas y permiso de descarga.</p></div></article>
      <article><span><?=crm_icon('link')?></span><div><strong>Conectado al CRM</strong><p>Relaciona documentos con clientes, contactos, proyectos, cotizaciones, contratos, tickets y servicios.</p></div></article>
      <article><span><?=crm_icon('risk')?></span><div><strong>Vencimientos</strong><p>Define vigencia y genera alertas para documentos que deben revisarse o renovarse.</p></div></article>
      <article><span><?=crm_icon('trash')?></span><div><strong>Auditoría + papelera</strong><p>Consulta actividad detallada y recupera archivos eliminados antes de su eliminación definitiva.</p></div></article>
    </div>
    <div class="s1help-product-note"><span><?=crm_icon('documents')?></span><div><strong>Flujo recomendado</strong><p>Crear carpeta → cargar documento → completar metadatos → relacionar con cliente/proyecto → definir permisos → iniciar aprobación → compartir → controlar versiones y vencimiento.</p></div></div>
  </section>

  <section class="s1help-panel" id="copilot" data-s1search="copilot asistente inteligencia ayuda datos pendientes busqueda contexto crm proyectos documentos facturacion soporte">
    <div class="s1help-head"><span class="s1help-head-num">09</span><div><h2>SÓCRATES Copilot: asistente conectado al CRM</h2><p>No es solo una ayuda de navegación: consulta en tiempo real los módulos instalados y los datos que cada usuario tiene permiso para ver.</p></div><span class="s1help-head-side">NUEVO · v2.2</span></div>
    <div class="s1help-doc-flow">
      <a href="assistant.php?prompt=<?=rawurlencode('¿Qué requiere mi atención hoy?')?>"><b>01</b><span><?=crm_icon('bell')?></span><strong>MI DÍA</strong><small>Actividades, tareas, aprobaciones, reuniones y tickets</small></a>
      <a href="assistant.php?prompt=<?=rawurlencode('Dame un resumen ejecutivo')?>"><b>02</b><span><?=crm_icon('dashboard')?></span><strong>RESUMIR</strong><small>Pipeline, proyectos, cobros, documentos y soporte</small></a>
      <a href="assistant.php?prompt=<?=rawurlencode('Buscar cliente ')?>"><b>03</b><span><?=crm_icon('search')?></span><strong>BUSCAR</strong><small>Clientes, proyectos, documentos, negocios y tickets</small></a>
      <a href="assistant.php?prompt=<?=rawurlencode('¿Qué puedo hacer aquí?')?>"><b>04</b><span><?=crm_icon('assistant')?></span><strong>CONTEXTO</strong><small>Explica la pantalla actual y ofrece accesos útiles</small></a>
      <a href="assistant.php"><b>05</b><span><?=crm_icon('assistant')?></span><strong>COPILOT</strong><small>Centro completo del asistente profesional</small></a>
    </div>
    <div class="s1help-doc-grid">
      <article><span><?=crm_icon('deal')?></span><div><strong>Ventas y pipeline</strong><p>Resume oportunidades, valores por moneda, próximos cierres y negocios sin próxima acción.</p></div></article>
      <article><span><?=crm_icon('project')?></span><div><strong>Project Manager</strong><p>Detecta proyectos en riesgo, tareas vencidas o bloqueadas, horas y costos de ejecución.</p></div></article>
      <article><span><?=crm_icon('documents')?></span><div><strong>Gestión documental</strong><p>Consulta documentos en revisión, próximos vencimientos y aprobaciones pendientes.</p></div></article>
      <article><span><?=crm_icon('billing')?></span><div><strong>Finanzas</strong><p>Resume cobros vencidos, próximos vencimientos e ingresos registrados del mes.</p></div></article>
      <article><span><?=crm_icon('support')?></span><div><strong>Helpdesk</strong><p>Muestra tickets abiertos, prioridades y SLA vencidos según el nivel de acceso del usuario.</p></div></article>
      <article><span><?=crm_icon('shield')?></span><div><strong>Permisos respetados</strong><p>El Copilot nunca consulta módulos que el usuario no tenga autorizados y no ejecuta cambios críticos sin confirmación.</p></div></article>
    </div>
    <div class="s1help-product-note"><span><?=crm_icon('assistant')?></span><div><strong>Ejemplos útiles</strong><p>“¿Qué requiere mi atención hoy?” · “Buscar cliente Acme” · “Muéstrame proyectos en riesgo” · “¿Qué documentos debo aprobar?” · “¿Qué cobros están vencidos?” · “Crear una cotización”.</p></div></div>
  </section>

</div>

<div class="s1help-lightbox" id="s1helpLightbox" hidden><button type="button" class="s1help-lightbox-close" aria-label="Cerrar">×</button><div><strong id="s1helpLightboxTitle"></strong><img id="s1helpLightboxImage" alt="Pantalla ampliada"></div></div>
<script>
(function(){
  const root=document.getElementById('s1help'); if(!root) return;
  const input=document.getElementById('s1helpSearch'), btn=document.getElementById('s1helpSearchBtn'), status=document.getElementById('s1helpStatus'), noResults=document.getElementById('s1helpNoResults');
  const norm=s=>(s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
  const searchable=[...root.querySelectorAll('[data-s1search]')];
  function search(go){
    const raw=input.value.trim(), q=norm(raw); let count=0, first=null;
    root.querySelectorAll('.s1help-highlight').forEach(el=>el.classList.remove('s1help-highlight'));
    searchable.forEach(el=>{const hit=!q||norm(el.dataset.s1search).includes(q); if(el.classList.contains('s1help-topic')){el.hidden=!hit;if(hit){count++;if(!first)first=el;}} else if(q&&hit){el.classList.add('s1help-highlight');}});
    noResults.style.display=(q&&count===0)?'block':'none';
    status.textContent=q?(count?`${count} guía${count===1?'':'s'} relacionada${count===1?'':'s'} con “${raw}”.`:`No hay una guía exacta para “${raw}”.`):'';
    if(go&&first){first.scrollIntoView({behavior:'smooth',block:'center'});first.classList.add('s1help-pulse');setTimeout(()=>first.classList.remove('s1help-pulse'),1300);}
  }
  input.addEventListener('input',()=>search(false)); input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();search(true);}}); btn.addEventListener('click',()=>search(true));
  root.querySelectorAll('[data-s1query]').forEach(b=>b.addEventListener('click',()=>{input.value=b.dataset.s1query;search(true);}));
  const box=document.getElementById('s1helpLightbox'), image=document.getElementById('s1helpLightboxImage'), title=document.getElementById('s1helpLightboxTitle');
  root.querySelectorAll('[data-s1lightbox]').forEach(b=>b.addEventListener('click',()=>{image.src=b.dataset.s1lightbox;title.textContent=b.dataset.s1title||'Pantalla del sistema';box.hidden=false;document.body.classList.add('s1help-lightbox-open');}));
  function close(){box.hidden=true;image.removeAttribute('src');document.body.classList.remove('s1help-lightbox-open');} box.querySelector('.s1help-lightbox-close').addEventListener('click',close);box.addEventListener('click',e=>{if(e.target===box)close();});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!box.hidden)close();});
})();
</script>



<?php require __DIR__.'/includes/footer.php';?>
