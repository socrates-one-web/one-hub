(() => {
  'use strict';
  const ctx = window.SOCRATES_TOUR_CONTEXT || {};
  if (!ctx.page) return;
  const lang = ['es','pt','en'].includes(ctx.lang) ? ctx.lang : 'es';
  const uid = String(ctx.userId || '0');
  const KEY = `socrates.crm.tour.v220.${uid}`;
  const SEEN = `socrates.crm.tour.welcome.v220.${uid}`;
  const txt = (es, pt, en) => ({es,pt,en});
  const pick = value => typeof value === 'string' ? value : (value?.[lang] || value?.es || '');
  const labels = {
    full: txt('TOUR COMPLETO','TOUR COMPLETO','FULL TOUR'),
    quick: txt('TOUR RÁPIDO','TOUR RÁPIDO','QUICK TOUR'),
    prev: txt('Anterior','Anterior','Previous'),
    next: txt('Siguiente','Próximo','Next'),
    pause: txt('Pausar','Pausar','Pause'),
    restart: txt('Empezar de nuevo','Recomeçar','Restart'),
    finish: txt('Finalizar','Finalizar','Finish'),
    resume: txt('Retomar','Retomar','Resume'),
    what: txt('QUÉ VES','O QUE VOCÊ VÊ','WHAT YOU SEE'),
    why: txt('PARA QUÉ SIRVE','PARA QUE SERVE','WHY IT MATTERS'),
    readOnly: txt('El tour es solo demostrativo: no crea, mueve ni modifica datos.','O tour é apenas demonstrativo: não cria, move nem altera dados.','The tour is read-only: it does not create, move or change data.'),
    doneTitle: txt('Recorrido completado','Tour concluído','Tour completed'),
    doneBody: txt('Ya conoces el flujo principal de SÓCRATES CRM. Puedes repetirlo completo o entrar por capítulos cuando quieras.','Você já conhece o fluxo principal do SÓCRATES CRM. Pode repetir o tour completo ou entrar por capítulos quando quiser.','You now know the main SÓCRATES CRM workflow. You can repeat the full tour or start from any chapter whenever you want.'),
  };
  const P = ctx.permissions || {};
  const step = (id, chapter, route, selector, title, what, why, permission, quick=false, placement='auto') => ({id,chapter,route,selector,title,what,why,permission,quick,placement});
  const steps = [
    step('dashboard-welcome',1,'dashboard.php','.dashboard-toolbar',txt('Tu centro de mando','Seu centro de comando','Your command center'),txt('El Dashboard reúne KPI, alertas y gráficos para comenzar el día con contexto.','O Dashboard reúne KPIs, alertas e gráficos para começar o dia com contexto.','The Dashboard brings KPIs, alerts and charts together so you start the day with context.'),txt('Aquí decides qué requiere atención antes de entrar a cada módulo.','Aqui você decide o que precisa de atenção antes de entrar em cada módulo.','Use it to decide what needs attention before opening each module.'),'dashboard',true),
    step('sidebar',1,'dashboard.php','#sidebar',txt('Menú organizado por áreas','Menu organizado por áreas','Navigation by work area'),txt('El menú agrupa Comercial, Ventas, Operación, Control, Ayuda y Sistema.','O menu agrupa Comercial, Vendas, Operação, Controle, Ajuda e Sistema.','The menu groups Commercial, Sales, Operations, Control, Help and System.'),txt('La estructura sigue el mismo orden del Manual 360°, para que aprender y trabajar sea lo mismo.','A estrutura segue a mesma ordem do Manual 360°, para que aprender e trabalhar seja a mesma coisa.','It mirrors the 360° Manual so learning and working follow the same structure.'),null,false,'right'),
    step('search',1,'dashboard.php','.global-search',txt('Buscador global','Busca global','Global search'),txt('Busca clientes, funciones y rutas del CRM desde cualquier pantalla.','Busque clientes, funções e rotas do CRM em qualquer tela.','Search clients, functions and CRM routes from any screen.'),txt('Usa Ctrl/Cmd + K para llegar rápido a una función sin recorrer menús.','Use Ctrl/Cmd + K para chegar rapidamente a uma função sem navegar pelos menus.','Use Ctrl/Cmd + K to reach a function without navigating menus.'),null,false,'bottom'),
    step('copilot',1,'dashboard.php','[data-tour-target="assistant-float"]',txt('SÓCRATES Copilot','SÓCRATES Copilot','SÓCRATES Copilot'),txt('El copiloto consulta los módulos instalados y los datos que tu usuario tiene permiso para ver.','O copiloto consulta os módulos instalados e os dados que seu usuário tem permissão para ver.','The copilot reads installed modules and the data your user is allowed to see.'),txt('Úsalo para resumir pendientes, buscar registros, entender la pantalla actual y abrir la acción correcta sin recorrer el CRM.','Use-o para resumir pendências, localizar registros, entender a tela atual e abrir a ação correta sem navegar pelo CRM.','Use it to summarize pending work, find records, understand the current screen and open the right action without navigating the CRM.'),'assistant',true,'left'),
    step('quick-actions',1,'dashboard.php','.top-actions',txt('Acciones rápidas','Ações rápidas','Quick actions'),txt('Desde la barra superior puedes cambiar idioma, tema, revisar notificaciones y crear registros frecuentes.','Na barra superior você pode trocar idioma, tema, revisar notificações e criar registros frequentes.','From the top bar you can switch language/theme, view notifications and create frequent records.'),txt('Reduce pasos en tareas que repites durante el día.','Reduz etapas nas tarefas que você repete durante o dia.','It cuts steps from repetitive daily tasks.'),null,false,'bottom'),
    step('dashboard-kpi',1,'dashboard.php','.dashboard-layout',txt('Dashboard personalizable','Dashboard personalizável','Customizable dashboard'),txt('Cada KPI o gráfico es un widget independiente.','Cada KPI ou gráfico é um widget independente.','Each KPI or chart is an independent widget.'),txt('En modo Personalizar puedes cambiar tamaño y ordenar la información según tu rol.','No modo Personalizar você pode alterar tamanho e ordenar as informações conforme sua função.','In Customize mode you can resize and reorder information for your role.'),'dashboard',false),
    step('profile',1,'dashboard.php','.profile-menu',txt('Tu perfil y seguridad','Seu perfil e segurança','Your profile and security'),txt('Desde aquí accedes a perfil, 2FA, notificaciones, manual y cierre de sesión.','Aqui você acessa perfil, 2FA, notificações, manual e saída.','Access profile, 2FA, notifications, manual and logout here.'),txt('Centraliza las opciones personales sin mezclarlas con la configuración global.','Centraliza opções pessoais sem misturá-las com a configuração global.','Keeps personal options separate from global configuration.'),null,false,'left'),

    step('clients-list',2,'clients.php','.panel:first-of-type',txt('Clientes: ficha 360°','Clientes: visão 360°','Clients: 360° record'),txt('El directorio agrupa empresas y permite filtrar por país y estado.','O diretório agrupa empresas e permite filtrar por país e status.','The directory groups companies and filters by country and status.'),txt('El cliente es el centro de contactos, servicios, negocios, documentos, facturación y proyectos.','O cliente é o centro de contatos, serviços, negócios, documentos, faturamento e projetos.','The client is the hub for contacts, services, deals, documents, billing and projects.'),'clients',true),
    step('clients-create',2,'clients.php','.panel:first-of-type .btn-primary',txt('Crear cliente','Criar cliente','Create client'),txt('Este acceso abre el formulario de una nueva empresa.','Este acesso abre o formulário de uma nova empresa.','This opens the new company form.'),txt('Registra primero la empresa y luego vincula contactos, oportunidades y servicios.','Cadastre primeiro a empresa e depois vincule contatos, oportunidades e serviços.','Create the company first, then link contacts, opportunities and services.'),'clients',false),
    step('pipeline-tabs',2,'deals.php','.module-tabs',txt('Pipeline comercial','Pipeline comercial','Sales pipeline'),txt('Kanban, Flow, Informes, Formularios, Emails e IA forman el espacio comercial.','Kanban, Flow, Relatórios, Formulários, Emails e IA formam o espaço comercial.','Kanban, Flow, Reports, Forms, Emails and AI make up the commercial workspace.'),txt('Cambia de vista sin perder el contexto de ventas.','Troque de visualização sem perder o contexto de vendas.','Switch views without losing sales context.'),'deals',false),
    step('pipeline-board',2,'deals.php','.pipeline-board',txt('Kanban de oportunidades','Kanban de oportunidades','Opportunity Kanban'),txt('Cada tarjeta representa un negocio y cada columna una etapa del embudo.','Cada cartão representa um negócio e cada coluna uma etapa do funil.','Each card is a deal and each column is a pipeline stage.'),txt('Arrastrar una oportunidad actualiza la etapa y ayuda a mantener el pronóstico al día.','Arrastar uma oportunidade atualiza a etapa e ajuda a manter a previsão em dia.','Dragging an opportunity updates its stage and keeps forecasts current.'),'deals',true),
    step('pipeline-forecast',2,'deals.php','.pipeline-summary',txt('Pronóstico por moneda','Previsão por moeda','Forecast by currency'),txt('El sistema separa CLP, USD y BRL y calcula valor ponderado.','O sistema separa CLP, USD e BRL e calcula o valor ponderado.','The system separates CLP, USD and BRL and calculates weighted value.'),txt('Permite analizar el pipeline sin mezclar monedas.','Permite analisar o pipeline sem misturar moedas.','It lets you analyze pipeline value without mixing currencies.'),'deals',false),
    step('quotes',2,'quotations.php','.panel:first-of-type',txt('Cotizaciones','Cotações','Quotations'),txt('Historial multimoneda y multidioma con estado, vigencia y total.','Histórico multimoeda e multilíngue com status, validade e total.','Multicurrency, multilingual history with status, validity and total.'),txt('Desde aquí creas, filtras, editas y abres propuestas comerciales.','Aqui você cria, filtra, edita e abre propostas comerciais.','Create, filter, edit and open proposals here.'),'quotes',true),
    step('contracts',2,'contracts.php','.panel:first-of-type',txt('Contratos','Contratos','Contracts'),txt('Formaliza servicios desde plantillas y controla el estado de cada documento.','Formalize serviços a partir de modelos e controle o status de cada documento.','Formalize services from templates and track each document status.'),txt('Mantiene la trazabilidad entre propuesta, aprobación y firma.','Mantém a rastreabilidade entre proposta, aprovação e assinatura.','Keeps traceability from proposal through approval and signing.'),'contracts',false),
    step('commercial-flow',2,'contracts.php','.topbar-title',txt('De venta a ejecución','Da venda à execução','From sale to delivery'),txt('El flujo recomendado es Cliente → Negocio → Cotización → Contrato → Proyecto.','O fluxo recomendado é Cliente → Negócio → Cotação → Contrato → Projeto.','Recommended flow: Client → Deal → Quotation → Contract → Project.'),txt('La información se reutiliza para evitar volver a escribir lo que ya existe.','As informações são reutilizadas para evitar digitar novamente o que já existe.','Information is reused so you do not re-enter existing data.'),'contracts',false),

    step('pm-home',3,'project_dashboard.php','.project-hero',txt('Project Manager','Project Manager','Project Manager'),txt('El centro de mando resume portafolio, carga, plazos, riesgos y costos.','O centro de comando resume portfólio, carga, prazos, riscos e custos.','The command center summarizes portfolio, workload, deadlines, risks and costs.'),txt('Es la entrada recomendada para dirección y responsables de proyecto.','É a entrada recomendada para gestores e responsáveis de projeto.','It is the recommended entry point for managers and project owners.'),'projects',true),
    step('pm-kpis',3,'project_dashboard.php','.pm-kpis',txt('Salud del portafolio','Saúde do portfólio','Portfolio health'),txt('Proyectos activos, planificación, riesgos, atrasos, próximos vencimientos y horas del mes.','Projetos ativos, planejamento, riscos, atrasos, próximos vencimentos e horas do mês.','Active projects, planning, risks, delays, upcoming deadlines and monthly hours.'),txt('Un vistazo basta para detectar dónde intervenir.','Uma olhada basta para detectar onde intervir.','One glance is enough to see where intervention is needed.'),'projects',false),
    step('pm-modules',3,'project_dashboard.php','.pm-module-grid',txt('Módulos independientes','Módulos independentes','Independent modules'),txt('Kanban, Gantt, Calendario, Horas, Riesgos y Costos se abren de forma independiente.','Kanban, Gantt, Calendário, Horas, Riscos e Custos abrem de forma independente.','Kanban, Gantt, Calendar, Hours, Risks and Costs open independently.'),txt('Cada equipo puede entrar directamente en la vista que necesita.','Cada equipe pode entrar diretamente na visualização de que precisa.','Each team can jump straight to the view it needs.'),'projects',false),
    step('pm-kanban',3,'project_kanban.php','.pm-kanban',txt('Kanban de tareas','Kanban de tarefas','Task Kanban'),txt('Las tareas se agrupan por estado con responsable, prioridad, fecha y avance.','As tarefas são agrupadas por status com responsável, prioridade, data e progresso.','Tasks are grouped by status with owner, priority, date and progress.'),txt('Permite gestionar el trabajo diario arrastrando tarjetas entre columnas.','Permite gerenciar o trabalho diário arrastando cartões entre colunas.','Manage daily work by dragging cards across columns.'),'project_tasks',true),
    step('pm-gantt',3,'project_gantt.php','.pm-gantt-wrap',txt('Gantt / Project','Gantt / Project','Gantt / Project'),txt('El cronograma alinea tareas, fechas, duración, responsables y avance.','O cronograma alinha tarefas, datas, duração, responsáveis e progresso.','The timeline aligns tasks, dates, duration, owners and progress.'),txt('Ayuda a anticipar solapamientos y atrasos antes de que afecten la entrega.','Ajuda a antecipar sobreposições e atrasos antes que afetem a entrega.','It helps anticipate overlaps and delays before delivery is impacted.'),'project_tasks',true),
    step('pm-calendar',3,'project_calendar.php','.pm-calendar',txt('Calendario de proyectos','Calendário de projetos','Project calendar'),txt('Tareas e hitos se ordenan por fecha en una vista mensual.','Tarefas e marcos são organizados por data em uma visualização mensal.','Tasks and milestones are arranged by date in a monthly view.'),txt('Puedes compartir el calendario y exportarlo para Outlook o Apple Calendar.','Você pode compartilhar o calendário e exportá-lo para Outlook ou Apple Calendar.','You can share the calendar and export it for Outlook or Apple Calendar.'),'project_tasks',true),
    step('pm-calendar-share',3,'project_calendar.php','.calendar-sharebar',txt('Compartir calendario','Compartilhar calendário','Share calendar'),txt('WhatsApp, email, enlace e ICS están disponibles desde esta barra.','WhatsApp, email, link e ICS estão disponíveis nesta barra.','WhatsApp, email, link and ICS are available from this bar.'),txt('Facilita coordinar fechas con equipo y cliente fuera del CRM.','Facilita coordenar datas com equipe e cliente fora do CRM.','It makes it easy to coordinate dates with team and client outside the CRM.'),'project_tasks',false),
    step('pm-hours',3,'project_timesheet.php','.project-hero',txt('Horas y recursos','Horas e recursos','Hours and resources'),txt('Registra tiempo real, horas facturables y esfuerzo por proyecto.','Registre tempo real, horas faturáveis e esforço por projeto.','Track actual time, billable hours and project effort.'),txt('Sirve para comparar dedicación, costo y rentabilidad.','Serve para comparar dedicação, custo e rentabilidade.','Use it to compare effort, cost and profitability.'),'project_time',true),
    step('pm-risks',3,'project_risks.php','.project-hero',txt('Gestión de riesgos','Gestão de riscos','Risk management'),txt('Cada riesgo combina probabilidad, impacto, responsable y mitigación.','Cada risco combina probabilidade, impacto, responsável e mitigação.','Each risk combines probability, impact, owner and mitigation.'),txt('Mantiene problemas potenciales visibles antes de que se conviertan en retrasos o costos.','Mantém problemas potenciais visíveis antes que se tornem atrasos ou custos.','It keeps potential issues visible before they become delays or costs.'),'project_risks',true),
    step('pm-costs',3,'project_expenses.php','.project-hero',txt('Costos y gastos','Custos e despesas','Costs and expenses'),txt('Controla presupuesto, gastos internos y cargos facturables por proyecto.','Controle orçamento, despesas internas e cobranças faturáveis por projeto.','Track budget, internal expenses and billable project charges.'),txt('Ayuda a entender la rentabilidad real de cada entrega.','Ajuda a entender a rentabilidade real de cada entrega.','It helps reveal the true profitability of each delivery.'),'project_finance',false),

    step('docs-dashboard',4,'document_dashboard.php','[data-tour-target="dms-hero"]',txt('Gestión documental 360°','Gestão documental 360°','360° document management'),txt('El Dashboard documental resume biblioteca, documentos por vencer, aprobaciones y actividad reciente.','O painel documental resume biblioteca, documentos a vencer, aprovações e atividade recente.','The document dashboard summarizes the library, expiring documents, approvals and recent activity.'),txt('Es el punto de entrada para administrar conocimiento y archivos empresariales con contexto.','É o ponto de entrada para administrar conhecimento e arquivos empresariais com contexto.','It is the entry point for managing business knowledge and files with context.'),'documents',true),
    step('docs-kpis',4,'document_dashboard.php','[data-tour-target="dms-kpis"]',txt('Estado documental','Status documental','Document status'),txt('Los KPI muestran total, revisión, aprobados, vencimientos y documentos propios.','Os KPIs mostram total, revisão, aprovados, vencimentos e documentos próprios.','KPIs show total, review, approved, expiration and owned documents.'),txt('Ayuda a detectar documentos que requieren revisión o renovación antes de que venzan.','Ajuda a detectar documentos que precisam de revisão ou renovação antes do vencimento.','It surfaces documents that need review or renewal before they expire.'),'documents',false),
    step('docs-library',4,'documents.php','[data-tour-target="dms-library"]',txt('Biblioteca tipo SharePoint','Biblioteca estilo SharePoint','SharePoint-like library'),txt('Filtra por nombre, carpeta, estado, favoritos, compartidos y próximos a vencer; alterna entre lista y tarjetas.','Filtre por nome, pasta, status, favoritos, compartilhados e próximos do vencimento; alterne entre lista e cartões.','Filter by name, folder, status, favorites, shared items and expiration; switch between list and cards.'),txt('Centraliza documentos de clientes, proyectos, contratos, cotizaciones y soporte.','Centraliza documentos de clientes, projetos, contratos, cotações e suporte.','It centralizes client, project, contract, quotation and support documents.'),'documents',true),
    step('docs-approvals',4,'document_approvals.php','[data-tour-target="dms-approval-workflow"]',txt('Rutas de aprobación','Fluxos de aprovação','Approval workflows'),txt('Los documentos pueden recorrer una secuencia de aprobadores con decisión y comentarios.','Os documentos podem percorrer uma sequência de aprovadores com decisão e comentários.','Documents can move through a sequence of approvers with decisions and comments.'),txt('La aprobación queda registrada y actualiza automáticamente el estado documental.','A aprovação fica registrada e atualiza automaticamente o status documental.','Approval is recorded and automatically updates document status.'),'documents',false),
    step('docs-audit',4,'document_audit.php','.dms-page .panel',txt('Auditoría y trazabilidad','Auditoria e rastreabilidade','Audit and traceability'),txt('El historial registra cargas, versiones, vistas, descargas, compartidos y decisiones.','O histórico registra envios, versões, visualizações, downloads, compartilhamentos e decisões.','History records uploads, versions, previews, downloads, shares and decisions.'),txt('Permite saber quién hizo qué y cuándo sin depender de notas externas.','Permite saber quem fez o quê e quando sem depender de notas externas.','It shows who did what and when without external notes.'),'documents',false),
    step('billing',5,'billing.php','.project-hero, .panel:first-of-type',txt('Facturación y renovaciones','Faturamento e renovações','Billing and renewals'),txt('Resume cobros, vencimientos, MRR/ARR y ciclos recurrentes.','Resume cobranças, vencimentos, MRR/ARR e ciclos recorrentes.','Summarizes collections, due dates, MRR/ARR and recurring cycles.'),txt('Conecta la operación con el dinero que debe cobrarse y renovarse.','Conecta a operação com o dinheiro que deve ser cobrado e renovado.','Connects operations to what must be billed and renewed.'),'billing',true),
    step('reports',5,'reports.php','.stats-grid',txt('Panel de informes','Painel de relatórios','Reports panel'),txt('KPI comerciales, pipeline, leads, cotizaciones y actividad se concentran aquí.','KPIs comerciais, pipeline, leads, cotações e atividade ficam concentrados aqui.','Commercial KPIs, pipeline, leads, quotations and activity are centralized here.'),txt('Es la vista ejecutiva para analizar resultados y tomar decisiones.','É a visão executiva para analisar resultados e tomar decisões.','This is the executive view for analyzing results and making decisions.'),'reports',false),
    step('reports-charts',5,'reports.php','.report-chart-grid',txt('Gráficos de gestión','Gráficos de gestão','Management charts'),txt('Los gráficos muestran pronóstico ponderado, estado de cotizaciones y leads.','Os gráficos mostram previsão ponderada, status das cotações e leads.','Charts show weighted forecast, quotation status and leads.'),txt('Permiten leer tendencia y distribución sin revisar registro por registro.','Permitem ler tendências e distribuição sem revisar registro por registro.','They reveal trends and distribution without reviewing every record.'),'reports',false),
    step('helpdesk',5,'support.php','.project-hero',txt('Helpdesk y soporte','Helpdesk e suporte','Helpdesk and support'),txt('La mesa de soporte recibe, asigna y sigue cada ticket con prioridad y SLA.','A central de suporte recebe, atribui e acompanha cada ticket com prioridade e SLA.','The helpdesk receives, assigns and tracks every ticket with priority and SLA.'),txt('El historial conserva conversación, ruta y próximos pasos para no perder contexto.','O histórico preserva conversa, rota e próximos passos para não perder contexto.','History preserves conversation, routing and next steps so context is never lost.'),'tickets',true),
    step('helpdesk-kpis',5,'support.php','.helpdesk-kpis',txt('SLA y seguimiento','SLA e acompanhamento','SLA and follow-up'),txt('Abiertos, urgentes, SLA vencido, seguimientos y cerrados aparecen como KPI.','Abertos, urgentes, SLA vencido, acompanhamentos e fechados aparecem como KPIs.','Open, urgent, overdue SLA, follow-ups and closed tickets appear as KPIs.'),txt('Permite priorizar soporte antes de abrir cada caso.','Permite priorizar o suporte antes de abrir cada caso.','It helps prioritize support before opening each case.'),'tickets',false),

    step('data-tools',5,'data_tools.php','.panel:first-of-type, .data-tools-grid',txt('Importar y exportar','Importar e exportar','Import and export'),txt('La herramienta permite mover información mediante CSV y descargar respaldos compatibles con Excel.','A ferramenta permite mover informações por CSV e baixar backups compatíveis com Excel.','The tool moves information through CSV and downloads Excel-compatible backups.'),txt('Facilita migraciones, cargas masivas y respaldos sin editar registro por registro.','Facilita migrações, cargas em massa e backups sem editar registro por registro.','It simplifies migrations, bulk loads and backups without editing records one by one.'),'imports',false),
    step('users',6,'users.php','.panel:first-of-type',txt('Usuarios y permisos','Usuários e permissões','Users and permissions'),txt('Administra cuentas, perfiles, alertas, 2FA, estado y acceso.','Gerencie contas, perfis, alertas, 2FA, status e acesso.','Manage accounts, roles, alerts, 2FA, status and access.'),txt('Cada usuario puede ver solo los módulos que corresponden a su función.','Cada usuário pode ver apenas os módulos correspondentes à sua função.','Each user can see only the modules relevant to their role.'),'users',false),
    step('security',6,'security_center.php','.security-score-card, .security-controls-grid, .panel:first-of-type',txt('Centro de Seguridad','Centro de Segurança','Security Center'),txt('2FA, sesiones, intentos fallidos, integridad, auditoría y respaldos se revisan aquí.','2FA, sessões, tentativas falhas, integridade, auditoria e backups são revisados aqui.','2FA, sessions, failed attempts, integrity, audit and backups are reviewed here.'),txt('Convierte la seguridad en controles visibles y accionables.','Transforma a segurança em controles visíveis e acionáveis.','It turns security into visible, actionable controls.'),'security',false),
    step('settings',6,'settings.php','.settings-board',txt('Configuración modular','Configuração modular','Modular settings'),txt('Identidad, SMTP, proyectos, soporte, cotizaciones, contratos y seguridad viven en tarjetas.','Identidade, SMTP, projetos, suporte, cotações, contratos e segurança vivem em cartões.','Identity, SMTP, projects, support, quotations, contracts and security live in cards.'),txt('Las tarjetas pueden ordenarse y cambiar de tamaño según la forma de trabajo.','Os cartões podem ser ordenados e redimensionados conforme a forma de trabalho.','Cards can be reordered and resized to match how you work.'),'settings',false),
    step('license',6,'license.php','.license-activation-grid',txt('Licencia y versión','Licença e versão','License and version'),txt('Muestra estado DEMO/ACTIVA, plan, empresa, vencimiento e ID de instalación.','Mostra status DEMO/ATIVA, plano, empresa, vencimento e ID da instalação.','Shows DEMO/ACTIVE status, plan, company, expiry and installation ID.'),txt('Una licencia solo puede activarse mediante validación remota del código.','Uma licença só pode ser ativada por validação remota do código.','A license can only be activated through remote code validation.'),'settings',false),
    step('manual',6,'manual.php','.s1help-hero',txt('Manual 360°','Manual 360°','360° Manual'),txt('El manual reúne pantallas, mapa del sistema, búsqueda y explicaciones por módulo.','O manual reúne telas, mapa do sistema, busca e explicações por módulo.','The manual brings screens, system map, search and module explanations together.'),txt('Úsalo cuando quieras profundizar después de este recorrido.','Use-o quando quiser aprofundar depois deste tour.','Use it whenever you want to go deeper after this tour.'),'manual',false),
    step('tour-end',6,'tour.php','.tour-hub-hero',txt('Tour guiado siempre disponible','Tour guiado sempre disponível','Guided tour always available'),txt('Desde aquí puedes iniciar el tour completo, uno rápido o entrar por capítulos.','Aqui você pode iniciar o tour completo, um tour rápido ou entrar por capítulos.','From here you can start the full tour, a quick tour or jump to a chapter.'),txt('Tu progreso queda guardado en este navegador para poder pausar y continuar.','Seu progresso fica salvo neste navegador para pausar e continuar depois.','Your progress is saved in this browser so you can pause and resume.'),null,false),
  ];

  const hasPermission = s => !s.permission || P[s.permission] !== false;
  const filtered = mode => steps.filter(s => hasPermission(s) && (mode !== 'quick' || s.quick));
  const stateDefault = {active:false,mode:'full',index:0,started:false,chapter:null};
  const load = () => { try { return {...stateDefault, ...(JSON.parse(localStorage.getItem(KEY) || '{}'))}; } catch { return {...stateDefault}; } };
  const save = s => { try { localStorage.setItem(KEY, JSON.stringify(s)); } catch {} };
  const currentPage = () => (location.pathname.split('/').pop() || 'dashboard.php').toLowerCase();
  const routeUrl = route => route;
  let state = load();
  let activeSteps = filtered(state.mode);
  let ui = null;
  let resizeTimer = null;

  function resolveIndexById(list,id){ const n=list.findIndex(s=>s.id===id); return n>=0?n:0; }
  function currentStep(){ activeSteps = filtered(state.mode); if(state.index>=activeSteps.length) state.index=Math.max(0,activeSteps.length-1); return activeSteps[state.index]; }
  function setState(patch){ state={...state,...patch}; save(state); }
  function goToStep(index){
    activeSteps = filtered(state.mode);
    index=Math.max(0,Math.min(index,activeSteps.length-1));
    setState({index,active:true,started:true});
    const s=activeSteps[index];
    if(!s) return finish();
    if(currentPage()!==s.route.toLowerCase()) { location.href=routeUrl(s.route); return; }
    renderStep();
  }
  function start(mode='full', chapter=null){
    try{localStorage.setItem(SEEN,'1')}catch{}
    const list=filtered(mode);
    let index=0;
    if(chapter!==null){ const found=list.findIndex(s=>s.chapter===Number(chapter)); if(found>=0) index=found; }
    state={active:true,mode,index,started:true,chapter:chapter===null?null:Number(chapter)}; save(state); activeSteps=list;
    goToStep(index);
  }
  function pause(){ setState({active:false}); destroyUi(); showToast(pick(labels.resume), pick(txt('Tu progreso quedó guardado.','Seu progresso foi salvo.','Your progress was saved.')), () => { setState({active:true}); resume(); }); }
  function resume(){ state=load(); state.active=true; save(state); activeSteps=filtered(state.mode); goToStep(state.index||0); }
  function restart(){ start(state.mode||'full',state.chapter); }
  function finish(){ setState({active:false,index:0,started:false,chapter:null}); destroyUi(); showCompletion(); }

  function ensureUi(){
    if(ui) return ui;
    const shade=document.createElement('div'); shade.className='soc-tour-shade'; shade.setAttribute('aria-hidden','true');
    const spot=document.createElement('div'); spot.className='soc-tour-spotlight';
    const card=document.createElement('section'); card.className='soc-tour-card'; card.setAttribute('role','dialog'); card.setAttribute('aria-modal','true'); card.innerHTML=`
      <div class="soc-tour-card-head"><div><span data-tour-kicker></span><strong data-tour-title></strong></div><button type="button" class="soc-tour-close" data-tour-close aria-label="Cerrar">×</button></div>
      <div class="soc-tour-progress"><span data-tour-progress-bar></span></div>
      <div class="soc-tour-progress-copy"><span data-tour-progress-text></span><b data-tour-percent></b></div>
      <div class="soc-tour-copy"><div><small>${pick(labels.what)}</small><p data-tour-what></p></div><div><small>${pick(labels.why)}</small><p data-tour-why></p></div></div>
      <div class="soc-tour-safe"><span>✓</span><em>${pick(labels.readOnly)}</em></div>
      <div class="soc-tour-actions"><button type="button" class="tour-btn ghost" data-tour-prev>${pick(labels.prev)}</button><button type="button" class="tour-btn ghost" data-tour-pause>${pick(labels.pause)}</button><button type="button" class="tour-btn ghost desktop-only-tour" data-tour-restart>${pick(labels.restart)}</button><button type="button" class="tour-btn primary" data-tour-next>${pick(labels.next)}</button></div>`;
    document.body.append(shade,spot,card);
    ui={shade,spot,card};
    card.querySelector('[data-tour-close]').addEventListener('click',pause);
    card.querySelector('[data-tour-pause]').addEventListener('click',pause);
    card.querySelector('[data-tour-restart]').addEventListener('click',restart);
    card.querySelector('[data-tour-prev]').addEventListener('click',()=>goToStep(state.index-1));
    card.querySelector('[data-tour-next]').addEventListener('click',()=> state.index>=activeSteps.length-1 ? finish() : goToStep(state.index+1));
    document.addEventListener('keydown',keyboardHandler);
    window.addEventListener('resize',positionCurrent,{passive:true});
    return ui;
  }
  function destroyUi(){
    if(!ui) return;
    ui.shade.remove(); ui.spot.remove(); ui.card.remove(); ui=null;
    document.removeEventListener('keydown',keyboardHandler);
    window.removeEventListener('resize',positionCurrent);
    document.body.classList.remove('soc-tour-active');
    document.querySelectorAll('.soc-tour-target').forEach(el=>el.classList.remove('soc-tour-target'));
  }
  function keyboardHandler(e){
    if(!ui) return;
    if(e.key==='Escape') pause();
    if(e.key==='ArrowRight') ui.card.querySelector('[data-tour-next]')?.click();
    if(e.key==='ArrowLeft') ui.card.querySelector('[data-tour-prev]')?.click();
  }
  function findTarget(selector){
    if(!selector) return document.querySelector('main.content') || document.body;
    const parts=selector.split(',').map(s=>s.trim()).filter(Boolean);
    for(const part of parts){ const el=document.querySelector(part); if(el && el.getBoundingClientRect().width>0 && el.getBoundingClientRect().height>0) return el; }
    return document.querySelector('main.content') || document.querySelector('.app-main') || document.body;
  }
  function renderStep(){
    const s=currentStep(); if(!s) return finish();
    if(currentPage()!==s.route.toLowerCase()){ location.href=routeUrl(s.route); return; }
    requestAnimationFrame(()=>{
      const target=findTarget(s.selector);
      document.querySelectorAll('.soc-tour-target').forEach(el=>el.classList.remove('soc-tour-target'));
      target.classList.add('soc-tour-target');
      try{target.scrollIntoView({behavior:'smooth',block:'center',inline:'nearest'});}catch{}
      window.setTimeout(()=>{
        ensureUi(); document.body.classList.add('soc-tour-active');
        const chapterNames={1:txt('01 · ORIENTACIÓN','01 · ORIENTAÇÃO','01 · ORIENTATION'),2:txt('02 · CRM Y VENTAS','02 · CRM E VENDAS','02 · CRM & SALES'),3:txt('03 · PROJECT MANAGER','03 · PROJECT MANAGER','03 · PROJECT MANAGER'),4:txt('04 · GESTIÓN DOCUMENTAL','04 · GESTÃO DOCUMENTAL','04 · DOCUMENT MANAGEMENT'),5:txt('05 · CONTROL Y SOPORTE','05 · CONTROLE E SUPORTE','05 · CONTROL & SUPPORT'),6:txt('06 · ADMINISTRACIÓN','06 · ADMINISTRAÇÃO','06 · ADMINISTRATION')};
        ui.card.querySelector('[data-tour-kicker]').textContent=`${pick(state.mode==='quick'?labels.quick:labels.full)} · ${pick(chapterNames[s.chapter])}`;
        ui.card.querySelector('[data-tour-title]').textContent=pick(s.title);
        ui.card.querySelector('[data-tour-what]').textContent=pick(s.what);
        ui.card.querySelector('[data-tour-why]').textContent=pick(s.why);
        const pos=state.index+1,total=activeSteps.length,pct=Math.round(pos/total*100);
        ui.card.querySelector('[data-tour-progress-text]').textContent=`${pos} / ${total}`;
        ui.card.querySelector('[data-tour-percent]').textContent=`${pct}%`;
        ui.card.querySelector('[data-tour-progress-bar]').style.width=`${pct}%`;
        ui.card.querySelector('[data-tour-prev]').disabled=state.index===0;
        ui.card.querySelector('[data-tour-next]').textContent=state.index===total-1?pick(labels.finish):pick(labels.next);
        positionCard(target,s.placement);
      }, 340);
    });
  }
  function positionCurrent(){ clearTimeout(resizeTimer); resizeTimer=setTimeout(()=>{ const s=currentStep(); if(!s||!ui)return; positionCard(findTarget(s.selector),s.placement); },80); }
  function positionCard(target,placement='auto'){
    if(!ui||!target) return;
    const r=target.getBoundingClientRect(), pad=7, vw=window.innerWidth, vh=window.innerHeight;
    const spot={left:Math.max(6,r.left-pad),top:Math.max(6,r.top-pad),width:Math.min(vw-12,r.width+pad*2),height:Math.min(vh-12,r.height+pad*2)};
    Object.assign(ui.spot.style,{left:`${spot.left}px`,top:`${spot.top}px`,width:`${spot.width}px`,height:`${spot.height}px`});
    const card=ui.card, cw=Math.min(440,vw-24); card.style.width=`${cw}px`;
    const ch=card.offsetHeight||400, gap=18;
    let left=Math.min(Math.max(12,r.right+gap),vw-cw-12), top=Math.min(Math.max(12,r.top),vh-ch-12);
    const canRight=r.right+gap+cw<=vw-12, canLeft=r.left-gap-cw>=12, canBelow=r.bottom+gap+ch<=vh-12, canAbove=r.top-gap-ch>=12;
    if(placement==='right'&&canRight){left=r.right+gap;top=Math.min(Math.max(12,r.top),vh-ch-12);} else if(placement==='left'&&canLeft){left=r.left-gap-cw;top=Math.min(Math.max(12,r.top),vh-ch-12);} else if(placement==='bottom'&&canBelow){left=Math.min(Math.max(12,r.left),vw-cw-12);top=r.bottom+gap;} else if(placement==='top'&&canAbove){left=Math.min(Math.max(12,r.left),vw-cw-12);top=r.top-gap-ch;} else if(canRight){left=r.right+gap;top=Math.min(Math.max(12,r.top),vh-ch-12);} else if(canLeft){left=r.left-gap-cw;top=Math.min(Math.max(12,r.top),vh-ch-12);} else if(canBelow){left=Math.min(Math.max(12,r.left),vw-cw-12);top=r.bottom+gap;} else if(canAbove){left=Math.min(Math.max(12,r.left),vw-cw-12);top=r.top-gap-ch;} else {left=Math.max(12,(vw-cw)/2);top=Math.max(12,vh-ch-18);}
    Object.assign(card.style,{left:`${left}px`,top:`${top}px`});
  }
  function showToast(title,body,action){
    document.querySelector('.soc-tour-toast')?.remove();
    const t=document.createElement('div');t.className='soc-tour-toast';t.innerHTML=`<div><strong>${title}</strong><span>${body}</span></div><button type="button">${title}</button>`;document.body.appendChild(t);
    t.querySelector('button').addEventListener('click',()=>{t.remove();action?.();});setTimeout(()=>t.remove(),9000);
  }
  function showCompletion(){
    const old=document.querySelector('.soc-tour-complete'); old?.remove();
    const box=document.createElement('div');box.className='soc-tour-complete';box.innerHTML=`<div class="soc-tour-complete-icon">✓</div><span>SÓCRATES CRM</span><h2>${pick(labels.doneTitle)}</h2><p>${pick(labels.doneBody)}</p><div><button type="button" data-again>${pick(labels.restart)}</button><a href="tour.php">${pick(txt('Ver capítulos','Ver capítulos','View chapters'))}</a></div>`;document.body.appendChild(box);box.querySelector('[data-again]').addEventListener('click',()=>{box.remove();start('full');});setTimeout(()=>box.classList.add('show'),20);
  }
  function welcomePrompt(){
    if(ctx.licenseStatus!=='demo') return;
    try{if(localStorage.getItem(SEEN)==='1') return;}catch{}
    const box=document.createElement('section');box.className='soc-tour-welcome';box.innerHTML=`<button type="button" class="soc-tour-welcome-x">×</button><span class="soc-tour-welcome-kicker">SÓCRATES CRM · TOUR 360°</span><h2>${pick(txt('¿Primera vez aquí?','Primeira vez aqui?','First time here?'))}</h2><p>${pick(txt('Te mostramos el sistema de principio a fin sin modificar ningún dato. Puedes pausar y continuar cuando quieras.','Mostramos o sistema do início ao fim sem alterar nenhum dado. Você pode pausar e continuar quando quiser.','We can show you the system end to end without changing any data. Pause and resume whenever you want.'))}</p><div><button type="button" class="primary" data-welcome-full>${pick(labels.full)}</button><button type="button" data-welcome-quick>${pick(labels.quick)}</button></div><small>${pick(labels.readOnly)}</small>`;document.body.appendChild(box);
    const close=()=>{try{localStorage.setItem(SEEN,'1')}catch{} box.remove();};box.querySelector('.soc-tour-welcome-x').addEventListener('click',close);box.querySelector('[data-welcome-full]').addEventListener('click',()=>{close();start('full');});box.querySelector('[data-welcome-quick]').addEventListener('click',()=>{close();start('quick');});
  }

  window.SocratesTour={start,resume,restart,pause,finish,state:()=>load(),steps:()=>steps.slice()};
  document.querySelectorAll('[data-tour-start]').forEach(btn=>btn.addEventListener('click',e=>{e.preventDefault();start(btn.dataset.tourStart||'full',btn.dataset.tourChapter||null);}));
  document.querySelectorAll('[data-tour-resume]').forEach(btn=>btn.addEventListener('click',e=>{e.preventDefault();resume();}));
  document.querySelectorAll('[data-tour-reset]').forEach(btn=>btn.addEventListener('click',e=>{e.preventDefault();state={...stateDefault};save(state);document.dispatchEvent(new CustomEvent('socrates-tour-state'));}));
  document.querySelectorAll('[data-tour-launch]').forEach(btn=>btn.addEventListener('click',e=>{e.preventDefault();const s=load();if(s.started)resume();else start('quick');}));
  document.dispatchEvent(new CustomEvent('socrates-tour-ready',{detail:{state:load(),full:filtered('full').length,quick:filtered('quick').length}}));

  state=load(); activeSteps=filtered(state.mode);
  if(state.active&&state.started){ window.setTimeout(()=>goToStep(state.index||0),260); }
  else if(ctx.page==='dashboard.php'){ window.setTimeout(welcomePrompt,900); }
})();
