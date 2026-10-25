(() => {
  'use strict';

  const i18n = {
    es: {
      navProduct:'Producto',navPlatform:'Plataforma',navModules:'Módulos',navImplementation:'Implementación',navSecurity:'Seguridad',navFaq:'FAQ',quoteImplementation:'Cómo se implementa',requestDemo:'Solicitar demo',
      heroEyebrow:'ONE HUB · Business Operating System',heroTitle:'Tu empresa, conectada.<br><span>Del primer contacto a la renovación.</span>',heroLead:'CRM, ventas, cotizaciones, contratos, proyectos, soporte, cobros, reportes y seguridad en una instancia de ONE HUB preparada e instalada para tu empresa.',heroCta:'Solicitar una demo',heroSecondary:'Ver cómo funciona',heroNote:'No vendemos solo una licencia: implementamos ONE HUB para tu organización y acompañamos la puesta en marcha.',chipResponsive:'Desktop + móvil',chipMulti:'Multiempresa',chipSecurity:'2FA + auditoría',chipRoles:'Roles y permisos',floatPipeline:'Pipeline',floatPipelineValue:'Ventas visibles',floatProjects:'Proyectos',floatProjectsValue:'Control de punta a punta',
      proof1Title:'Un solo dato',proof1Text:'compartido entre ventas, proyectos y finanzas.',proof2Title:'Un solo flujo',proof2Text:'desde el lead hasta la renovación.',proof3Title:'Una sola vista',proof3Text:'para saber qué necesita atención hoy.',
      flowKicker:'Operación conectada',flowTitle:'Menos herramientas sueltas.<br><span>Más control en un solo lugar.</span>',flowLead:'Cada etapa alimenta la siguiente y mantiene el contexto de tu empresa.',flow1Title:'Captar',flow1Text:'Leads, clientes y contactos.',flow2Title:'Vender',flow2Text:'Negocios, propuestas y contratos.',flow3Title:'Ejecutar',flow3Text:'Proyectos, tareas, horas y riesgos.',flow4Title:'Cobrar',flow4Text:'Ciclos, pagos y vencimientos.',flow5Title:'Renovar',flow5Text:'Alertas, continuidad e historial.',
      screensKicker:'Pantallas reales del ecosistema',screensTitle:'ONE HUB desde cada nivel de la operación.',screensLead:'Haz clic en cualquier pantalla para verla ampliada.',screen1Title:'Dashboard ejecutivo',screen1Text:'KPIs, vencimientos, negocios, cobros y alertas en una sola vista.',screen2Title:'CRM y ventas',screen2Text:'Clientes, contactos, actividades y navegación comercial conectada.',screen3Title:'Cotizaciones multimoneda',screen3Text:'ES/PT/EN, CLP/USD/BRL, catálogo y seguimiento comercial.',screen4Title:'Dashboard de proyectos',screen4Text:'Portafolio, estado de avance, tareas atrasadas y control operativo.',screen5Title:'Calendario de proyectos',screen5Text:'Fechas de entrega, tareas y coordinación del equipo en vista mensual.',screen6Title:'Finanzas y cobranzas',screen6Text:'Ingresos, cuentas por cobrar y seguimiento de pagos desde el CRM.',screen7Title:'Riesgos de proyectos',screen7Text:'Registro, seguimiento y priorización de riesgos para cada iniciativa.',screen8Title:'Gestión documental',screen8Text:'Biblioteca, versiones, metadatos y control documental integrado.',screen9Title:'Reportes y control',screen9Text:'Indicadores comerciales, proyectos y finanzas en un solo centro de análisis.',screen10Title:'EMAILSEND',screen10Text:'Listas, campañas, configuración técnica y métricas del módulo incluido.',
      modulesKicker:'Un sistema. Múltiples áreas.',modulesTitle:'Todo lo que tu operación necesita,<br><span>sin perder el hilo entre módulos.</span>',m1Title:'CRM & Ventas',m1Text:'Clientes, contactos, oportunidades, pipeline y actividades comerciales.',m1a:'Búsqueda global',m1b:'Historial comercial',m1c:'Seguimiento de negocios',m2Title:'Cotizaciones & Contratos',m2Text:'Propuestas profesionales, multimoneda y multidioma, con trazabilidad.',m2c:'Aprobación y documentos',m3Title:'Project Manager',m3Text:'Kanban, Gantt, calendario, tareas, horas, hitos, riesgos y costos.',m3a:'Equipos y responsables',m3b:'Horas y recursos',m3c:'Riesgos y gastos',m4Title:'Finanzas & Cobros',m4Text:'Pagos, vencimientos, ciclos de cobro e indicadores financieros.',m4a:'Calendario de cobros',m4b:'Alertas de vencimiento',m4c:'Reportes por moneda',m5Title:'Helpdesk & Soporte',m5Text:'Tickets, prioridades, SLA, responsables, respuestas y línea de tiempo.',m5a:'Flujo de atención',m5b:'Notas internas',m5c:'Seguimiento completo',m6Title:'Reportes & Control',m6Text:'Indicadores operativos y comerciales para entender qué está pasando.',m6a:'Dashboard configurable',m6b:'Gráficos por área',m6c:'Exportación de datos',m7Title:'Asistente',m7Text:'Un punto de apoyo dentro del sistema para consultar y acelerar tareas.',m7a:'Contexto operativo',m7b:'Acceso centralizado',m7c:'Preparado para evolucionar',m8Title:'Multiusuario & Permisos',m8Text:'Cada persona ve y gestiona únicamente lo que le corresponde.',m8a:'Roles granulares',m8b:'Permisos por función',m8c:'Registro de actividad',
      focusKicker:'Información que se encuentra',focusTitle:'Busca una vez.<br><span>Encuentra el contexto completo.</span>',focusLead:'ONE HUB reduce la fricción de trabajar con datos repartidos. Clientes, contactos, documentos, servicios y negocios permanecen conectados.',focus1:'Busca clientes por nombre, empresa, email, documento, WhatsApp, ciudad y más.',focus2:'Relaciona cotizaciones con clientes y negocios.',focus3:'Convierte oportunidades en servicios y proyectos sin perder historial.',focus4:'Mantén vencimientos y renovaciones visibles antes de que se transformen en urgencias.',searchDemoTitle:'Búsqueda inteligente de cliente',searchDemoQuery:'Nombre, RUT, email, WhatsApp, ciudad...',
      securityKicker:'Seguridad incorporada',securityTitle:'Tu operación necesita velocidad.<br><span>También necesita control.</span>',securityLead:'ONE HUB incorpora controles de acceso y trazabilidad para que la información empresarial permanezca protegida y auditable.',securityCta:'Conocer ONE HUB',sec1Title:'2FA',sec1Text:'Verificación adicional de acceso.',sec2Title:'Roles',sec2Text:'Permisos por usuario y función.',sec3Title:'Auditoría',sec3Text:'Registro de actividad y eventos.',sec4Title:'Sesiones',sec4Text:'Controles de expiración y bloqueo.',sec5Title:'Recuperación',sec5Text:'Restablecimiento seguro de contraseña.',sec6Title:'HTTPS',sec6Text:'Preparado para operación cifrada.',
      faqTitle:'Preguntas frecuentes.',faq1q:'¿ONE HUB reemplaza un CRM tradicional?',faq1a:'Va más allá de un CRM: conecta clientes y ventas con proyectos, documentos, soporte, cobros, renovaciones y control operativo.',faq2q:'¿Puedo usarlo con varios usuarios?',faq2a:'Sí. ONE HUB permite usuarios, roles y permisos para definir qué puede ver y hacer cada persona.',faq3q:'¿Trabaja con distintas monedas e idiomas?',faq3a:'Sí. Las cotizaciones están preparadas para ES/PT/EN y monedas como CLP, USD y BRL.',faq4q:'¿Puedo gestionar proyectos dentro del mismo sistema?',faq4a:'Sí. Incluye Kanban, Gantt, calendario, tareas, horas, hitos, riesgos, archivos, costos y responsables.',faq5q:'¿Incluye seguimiento de cobros y vencimientos?',faq5a:'Sí. Permite registrar ciclos de cobro, pagos, vencimientos, alertas y visualizar indicadores en el dashboard.',faq6q:'¿La interfaz funciona en celular?',faq6a:'Sí. La plataforma está diseñada para escritorio y dispositivos móviles, manteniendo accesos y vistas adaptadas.',faq7q:'¿ONE HUB se contrata y se instala para cada empresa?',faq7a:'Sí. Sócrates ONE realiza la implementación y configuración de ONE HUB según el entorno y necesidades acordadas con cada organización.',faq8q:'¿Necesito instalarlo yo mismo?',faq8a:'No. Nuestro equipo se encarga de la instalación, configuración inicial y puesta en marcha. Tu empresa recibe el sistema listo para comenzar a operar.',
      installKicker:'Implementado para tu empresa',installTitle:'No es otro SaaS genérico.<br><span>Lo instalamos y configuramos contigo.</span>',installLead:'ONE HUB se entrega como una implementación preparada para tu organización, con acompañamiento de Sócrates ONE desde el diagnóstico hasta la puesta en marcha.',install1Title:'Diagnóstico',install1Text:'Revisamos cómo gestionas hoy clientes, ventas, proyectos, soporte y cobros.',install2Title:'Instalación',install2Text:'Preparamos ONE HUB en el entorno acordado para tu empresa y dejamos la plataforma operativa.',install3Title:'Configuración',install3Text:'Ajustamos identidad, usuarios, permisos, datos iniciales, monedas, correo y flujos clave.',install4Title:'Puesta en marcha',install4Text:'Te acompañamos en el inicio de uso, capacitación y ajustes necesarios para comenzar con orden.',installCtaTitle:'¿Quieres ver cómo quedaría ONE HUB en tu empresa?',installCtaText:'Agenda una demostración y revisamos tu caso antes de cotizar la implementación.',installCtaButton:'Solicitar demostración',contactKicker:'Implementa ONE HUB en tu empresa',contactTitle:'¿Tu empresa necesita dejar de trabajar con información dispersa?',contactLead:'Cuéntanos cómo trabaja tu empresa hoy. Te mostramos ONE HUB, revisamos tu flujo y preparamos una propuesta de implementación.',contactPoint1:'Demostración guiada',contactPoint2:'Revisión de tu flujo actual',contactPoint3:'Propuesta de implementación',formName:'Nombre',formCompany:'Empresa',formEmail:'Email',formWhatsapp:'WhatsApp / Teléfono',formMessage:'Cuéntanos qué necesitas gestionar, integrar o mejorar',formSubmit:'Solicitar demo y cotización',formNote:'Tus datos se usarán únicamente para responder esta solicitud.',footerText:'Business Operating System para conectar clientes, ventas, proyectos, soporte y control.',footerProduct:'Producto',footerCommercial:'Comercial',legalPrivacy:'Privacidad',legalCookies:'Cookies',legalTerms:'Términos',cookieTitle:'Tu privacidad importa',cookieText:'Usamos únicamente cookies técnicas y de preferencia necesarias para el funcionamiento del sitio.',cookieMore:'Ver política',cookieAccept:'Aceptar',developedBy:'Desarrollado por',statusSent:'Solicitud enviada. Te contactaremos a la brevedad.',statusError:'No pudimos enviar la solicitud. Escríbenos a info@socrates.la.',statusInvalid:'Revisa los datos del formulario e inténtalo nuevamente.',statusRate:'Espera unos segundos antes de enviar otra solicitud.'
    },
    pt: {
      navProduct:'Produto',navPlatform:'Plataforma',navModules:'Módulos',navImplementation:'Implementação',navSecurity:'Segurança',navFaq:'FAQ',quoteImplementation:'Como implementamos',requestDemo:'Solicitar demo',
      heroEyebrow:'ONE HUB · Business Operating System',heroTitle:'Sua empresa, conectada.<br><span>Do primeiro contato à renovação.</span>',heroLead:'CRM, vendas, propostas, contratos, projetos, suporte, cobranças, relatórios e segurança em uma instância do ONE HUB preparada e instalada para sua empresa.',heroCta:'Solicitar uma demo',heroSecondary:'Ver como funciona',heroNote:'Não vendemos apenas uma licença: implementamos o ONE HUB para sua organização e acompanhamos a entrada em operação.',chipResponsive:'Desktop + celular',chipMulti:'Multiempresa',chipSecurity:'2FA + auditoria',chipRoles:'Funções e permissões',floatPipeline:'Pipeline',floatPipelineValue:'Vendas visíveis',floatProjects:'Projetos',floatProjectsValue:'Controle de ponta a ponta',
      proof1Title:'Um único dado',proof1Text:'compartilhado entre vendas, projetos e finanças.',proof2Title:'Um único fluxo',proof2Text:'do lead até a renovação.',proof3Title:'Uma única visão',proof3Text:'para saber o que precisa de atenção hoje.',
      flowKicker:'Operação conectada',flowTitle:'Menos ferramentas soltas.<br><span>Mais controle em um só lugar.</span>',flowLead:'Cada etapa alimenta a próxima e mantém o contexto da sua empresa.',flow1Title:'Captar',flow1Text:'Leads, clientes e contatos.',flow2Title:'Vender',flow2Text:'Negócios, propostas e contratos.',flow3Title:'Executar',flow3Text:'Projetos, tarefas, horas e riscos.',flow4Title:'Cobrar',flow4Text:'Ciclos, pagamentos e vencimentos.',flow5Title:'Renovar',flow5Text:'Alertas, continuidade e histórico.',
      screensKicker:'Telas reais do ecossistema',screensTitle:'ONE HUB em cada nível da operação.',screensLead:'Clique em qualquer tela para ampliar.',screen1Title:'Dashboard executivo',screen1Text:'KPIs, vencimentos, negócios, cobranças e alertas em uma única visão.',screen2Title:'CRM e vendas',screen2Text:'Clientes, contatos, atividades e navegação comercial conectada.',screen3Title:'Propostas multimoeda',screen3Text:'ES/PT/EN, CLP/USD/BRL, catálogo e acompanhamento comercial.',screen4Title:'Dashboard de projetos',screen4Text:'Portfólio, andamento, tarefas atrasadas e controle operacional.',screen5Title:'Calendário de projetos',screen5Text:'Datas de entrega, tarefas e coordenação da equipe em visão mensal.',screen6Title:'Finanças e cobranças',screen6Text:'Receitas, contas a receber e acompanhamento de pagamentos no CRM.',screen7Title:'Riscos de projetos',screen7Text:'Registro, acompanhamento e priorização de riscos por iniciativa.',screen8Title:'Gestão documental',screen8Text:'Biblioteca, versões, metadados e controle documental integrado.',screen9Title:'Relatórios e controle',screen9Text:'Indicadores comerciais, projetos e finanças em um único centro de análise.',screen10Title:'EMAILSEND',screen10Text:'Listas, campanhas, configuração técnica e métricas do módulo incluído.',
      modulesKicker:'Um sistema. Múltiplas áreas.',modulesTitle:'Tudo o que sua operação precisa,<br><span>sem perder o contexto entre módulos.</span>',m1Title:'CRM & Vendas',m1Text:'Clientes, contatos, oportunidades, pipeline e atividades comerciais.',m1a:'Busca global',m1b:'Histórico comercial',m1c:'Acompanhamento de negócios',m2Title:'Propostas & Contratos',m2Text:'Propostas profissionais, multimoeda e multilíngues, com rastreabilidade.',m2c:'Aprovação e documentos',m3Title:'Project Manager',m3Text:'Kanban, Gantt, calendário, tarefas, horas, marcos, riscos e custos.',m3a:'Equipes e responsáveis',m3b:'Horas e recursos',m3c:'Riscos e despesas',m4Title:'Finanças & Cobranças',m4Text:'Pagamentos, vencimentos, ciclos de cobrança e indicadores financeiros.',m4a:'Calendário de cobranças',m4b:'Alertas de vencimento',m4c:'Relatórios por moeda',m5Title:'Helpdesk & Suporte',m5Text:'Tickets, prioridades, SLA, responsáveis, respostas e linha do tempo.',m5a:'Fluxo de atendimento',m5b:'Notas internas',m5c:'Acompanhamento completo',m6Title:'Relatórios & Controle',m6Text:'Indicadores operacionais e comerciais para entender o que está acontecendo.',m6a:'Dashboard configurável',m6b:'Gráficos por área',m6c:'Exportação de dados',m7Title:'Assistente',m7Text:'Um ponto de apoio dentro do sistema para consultar e acelerar tarefas.',m7a:'Contexto operacional',m7b:'Acesso centralizado',m7c:'Preparado para evoluir',m8Title:'Multiusuário & Permissões',m8Text:'Cada pessoa vê e gerencia apenas o que lhe corresponde.',m8a:'Funções granulares',m8b:'Permissões por função',m8c:'Registro de atividades',
      focusKicker:'Informação que você encontra',focusTitle:'Busque uma vez.<br><span>Encontre o contexto completo.</span>',focusLead:'ONE HUB reduz a fricção de trabalhar com dados dispersos. Clientes, contatos, documentos, serviços e negócios permanecem conectados.',focus1:'Busque clientes por nome, empresa, email, documento, WhatsApp, cidade e muito mais.',focus2:'Relacione propostas com clientes e negócios.',focus3:'Converta oportunidades em serviços e projetos sem perder o histórico.',focus4:'Mantenha vencimentos e renovações visíveis antes que virem urgências.',searchDemoTitle:'Busca inteligente de cliente',searchDemoQuery:'Nome, CNPJ, email, WhatsApp, cidade...',
      securityKicker:'Segurança incorporada',securityTitle:'Sua operação precisa de velocidade.<br><span>Também precisa de controle.</span>',securityLead:'ONE HUB incorpora controles de acesso e rastreabilidade para manter as informações empresariais protegidas e auditáveis.',securityCta:'Conhecer ONE HUB',sec1Title:'2FA',sec1Text:'Verificação adicional de acesso.',sec2Title:'Funções',sec2Text:'Permissões por usuário e função.',sec3Title:'Auditoria',sec3Text:'Registro de atividades e eventos.',sec4Title:'Sessões',sec4Text:'Controles de expiração e bloqueio.',sec5Title:'Recuperação',sec5Text:'Redefinição segura de senha.',sec6Title:'HTTPS',sec6Text:'Preparado para operação criptografada.',
      faqTitle:'Perguntas frequentes.',faq1q:'O ONE HUB substitui um CRM tradicional?',faq1a:'Vai além de um CRM: conecta clientes e vendas com projetos, documentos, suporte, cobranças, renovações e controle operacional.',faq2q:'Posso usar com vários usuários?',faq2a:'Sim. ONE HUB permite usuários, funções e permissões para definir o que cada pessoa pode ver e fazer.',faq3q:'Trabalha com diferentes moedas e idiomas?',faq3a:'Sim. As propostas estão preparadas para ES/PT/EN e moedas como CLP, USD e BRL.',faq4q:'Posso gerenciar projetos dentro do mesmo sistema?',faq4a:'Sim. Inclui Kanban, Gantt, calendário, tarefas, horas, marcos, riscos, arquivos, custos e responsáveis.',faq5q:'Inclui acompanhamento de cobranças e vencimentos?',faq5a:'Sim. Permite registrar ciclos de cobrança, pagamentos, vencimentos, alertas e visualizar indicadores no dashboard.',faq6q:'A interface funciona no celular?',faq6a:'Sim. A plataforma foi projetada para desktop e dispositivos móveis, mantendo acessos e visões adaptadas.',faq7q:'O ONE HUB é contratado e instalado para cada empresa?',faq7a:'Sim. A Sócrates ONE realiza a implementação e configuração do ONE HUB de acordo com o ambiente e as necessidades combinadas com cada organização.',faq8q:'Preciso instalar por conta própria?',faq8a:'Não. Nossa equipe cuida da instalação, configuração inicial e entrada em operação. Sua empresa recebe o sistema pronto para começar.',
      installKicker:'Implementado para sua empresa',installTitle:'Não é outro SaaS genérico.<br><span>Nós instalamos e configuramos com você.</span>',installLead:'O ONE HUB é entregue como uma implementação preparada para sua organização, com acompanhamento da Sócrates ONE do diagnóstico à entrada em operação.',install1Title:'Diagnóstico',install1Text:'Revisamos como você gerencia hoje clientes, vendas, projetos, suporte e cobranças.',install2Title:'Instalação',install2Text:'Preparamos o ONE HUB no ambiente acordado para sua empresa e deixamos a plataforma operacional.',install3Title:'Configuração',install3Text:'Ajustamos identidade, usuários, permissões, dados iniciais, moedas, email e fluxos essenciais.',install4Title:'Entrada em operação',install4Text:'Acompanhamos o início do uso, treinamento e ajustes necessários para começar com organização.',installCtaTitle:'Quer ver como o ONE HUB ficaria na sua empresa?',installCtaText:'Agende uma demonstração e analisamos seu caso antes de cotar a implementação.',installCtaButton:'Solicitar demonstração',contactKicker:'Implemente o ONE HUB na sua empresa',contactTitle:'Sua empresa precisa parar de trabalhar com informações dispersas?',contactLead:'Conte como sua empresa trabalha hoje. Mostramos o ONE HUB, revisamos seu fluxo e preparamos uma proposta de implementação.',contactPoint1:'Demonstração guiada',contactPoint2:'Revisão do fluxo atual',contactPoint3:'Proposta de implementação',formName:'Nome',formCompany:'Empresa',formEmail:'Email',formWhatsapp:'WhatsApp / Telefone',formMessage:'Conte o que você precisa gerenciar, integrar ou melhorar',formSubmit:'Solicitar demo e cotação',formNote:'Seus dados serão usados somente para responder esta solicitação.',footerText:'Business Operating System para conectar clientes, vendas, projetos, suporte e controle.',footerProduct:'Produto',footerCommercial:'Comercial',legalPrivacy:'Privacidade',legalCookies:'Cookies',legalTerms:'Termos',cookieTitle:'Sua privacidade importa',cookieText:'Usamos apenas cookies técnicos e de preferência necessários para o funcionamento do site.',cookieMore:'Ver política',cookieAccept:'Aceitar',developedBy:'Desenvolvido por',statusSent:'Solicitação enviada. Entraremos em contato em breve.',statusError:'Não foi possível enviar. Escreva para info@socrates.la.',statusInvalid:'Revise os dados do formulário e tente novamente.',statusRate:'Aguarde alguns segundos antes de enviar outra solicitação.'
    },
    en: {
      navProduct:'Product',navPlatform:'Platform',navModules:'Modules',navImplementation:'Implementation',navSecurity:'Security',navFaq:'FAQ',quoteImplementation:'How implementation works',requestDemo:'Request demo',
      heroEyebrow:'ONE HUB · Business Operating System',heroTitle:'Your company, connected.<br><span>From first contact to renewal.</span>',heroLead:'CRM, sales, quotations, contracts, projects, support, collections, reports and security in a ONE HUB instance prepared and installed for your company.',heroCta:'Request a demo',heroSecondary:'See how it works',heroNote:'We do not just sell a license: we implement ONE HUB for your organization and support the go-live process.',chipResponsive:'Desktop + mobile',chipMulti:'Multi-company',chipSecurity:'2FA + audit',chipRoles:'Roles & permissions',floatPipeline:'Pipeline',floatPipelineValue:'Visible sales',floatProjects:'Projects',floatProjectsValue:'End-to-end control',
      proof1Title:'One data source',proof1Text:'shared across sales, projects and finance.',proof2Title:'One workflow',proof2Text:'from lead to renewal.',proof3Title:'One view',proof3Text:'to know what needs attention today.',
      flowKicker:'Connected operations',flowTitle:'Fewer disconnected tools.<br><span>More control in one place.</span>',flowLead:'Each stage feeds the next while preserving company context.',flow1Title:'Capture',flow1Text:'Leads, clients and contacts.',flow2Title:'Sell',flow2Text:'Deals, proposals and contracts.',flow3Title:'Deliver',flow3Text:'Projects, tasks, time and risks.',flow4Title:'Collect',flow4Text:'Cycles, payments and due dates.',flow5Title:'Renew',flow5Text:'Alerts, continuity and history.',
      screensKicker:'Real ecosystem screens',screensTitle:'ONE HUB across every level of operations.',screensLead:'Click any screen to view it larger.',screen1Title:'Executive dashboard',screen1Text:'KPIs, expirations, deals, collections and alerts in one view.',screen2Title:'CRM & sales',screen2Text:'Clients, contacts, activities and connected commercial navigation.',screen3Title:'Multi-currency quotations',screen3Text:'ES/PT/EN, CLP/USD/BRL, catalog and sales tracking.',screen4Title:'Project dashboard',screen4Text:'Portfolio, progress, overdue tasks and operational control.',screen5Title:'Project calendar',screen5Text:'Delivery dates, tasks and team coordination in a monthly view.',screen6Title:'Finance & collections',screen6Text:'Revenue, accounts receivable and payment tracking from the CRM.',screen7Title:'Project risks',screen7Text:'Register, track and prioritize risks for every initiative.',screen8Title:'Document management',screen8Text:'Library, versions, metadata and integrated document control.',screen9Title:'Reports & control',screen9Text:'Commercial, project and financial indicators in one analysis center.',screen10Title:'EMAILSEND',screen10Text:'Lists, campaigns, technical setup and metrics for the included module.',
      modulesKicker:'One system. Multiple areas.',modulesTitle:'Everything your operation needs,<br><span>without losing context between modules.</span>',m1Title:'CRM & Sales',m1Text:'Clients, contacts, opportunities, pipeline and sales activities.',m1a:'Global search',m1b:'Sales history',m1c:'Deal tracking',m2Title:'Quotations & Contracts',m2Text:'Professional multi-currency, multilingual proposals with traceability.',m2c:'Approval and documents',m3Title:'Project Manager',m3Text:'Kanban, Gantt, calendar, tasks, time, milestones, risks and costs.',m3a:'Teams and owners',m3b:'Time and resources',m3c:'Risks and expenses',m4Title:'Finance & Collections',m4Text:'Payments, due dates, billing cycles and financial indicators.',m4a:'Collection calendar',m4b:'Due-date alerts',m4c:'Reports by currency',m5Title:'Helpdesk & Support',m5Text:'Tickets, priorities, SLA, assignees, replies and timeline.',m5a:'Support workflow',m5b:'Internal notes',m5c:'Complete tracking',m6Title:'Reports & Control',m6Text:'Operational and sales indicators to understand what is happening.',m6a:'Configurable dashboard',m6b:'Charts by area',m6c:'Data export',m7Title:'Assistant',m7Text:'An in-system support point to query context and accelerate tasks.',m7a:'Operational context',m7b:'Centralized access',m7c:'Ready to evolve',m8Title:'Multi-user & Permissions',m8Text:'Each person sees and manages only what they are responsible for.',m8a:'Granular roles',m8b:'Functional permissions',m8c:'Activity log',
      focusKicker:'Information you can find',focusTitle:'Search once.<br><span>Find the full context.</span>',focusLead:'ONE HUB reduces the friction of working with scattered data. Clients, contacts, documents, services and deals stay connected.',focus1:'Search clients by name, company, email, ID, WhatsApp, city and more.',focus2:'Connect quotations with clients and deals.',focus3:'Turn opportunities into services and projects without losing history.',focus4:'Keep due dates and renewals visible before they become emergencies.',searchDemoTitle:'Smart client search',searchDemoQuery:'Name, ID, email, WhatsApp, city...',
      securityKicker:'Built-in security',securityTitle:'Your operation needs speed.<br><span>It also needs control.</span>',securityLead:'ONE HUB includes access controls and traceability so business information remains protected and auditable.',securityCta:'Discover ONE HUB',sec1Title:'2FA',sec1Text:'Additional access verification.',sec2Title:'Roles',sec2Text:'Permissions by user and function.',sec3Title:'Audit',sec3Text:'Activity and event logging.',sec4Title:'Sessions',sec4Text:'Expiration and lock controls.',sec5Title:'Recovery',sec5Text:'Secure password reset.',sec6Title:'HTTPS',sec6Text:'Ready for encrypted operation.',
      faqTitle:'Frequently asked questions.',faq1q:'Does ONE HUB replace a traditional CRM?',faq1a:'It goes beyond CRM by connecting clients and sales with projects, documents, support, collections, renewals and operational control.',faq2q:'Can multiple users work in it?',faq2a:'Yes. ONE HUB supports users, roles and permissions to define what each person can see and do.',faq3q:'Does it support different currencies and languages?',faq3a:'Yes. Quotations support ES/PT/EN and currencies such as CLP, USD and BRL.',faq4q:'Can I manage projects inside the same system?',faq4a:'Yes. It includes Kanban, Gantt, calendar, tasks, time, milestones, risks, files, costs and assignees.',faq5q:'Does it track collections and due dates?',faq5a:'Yes. You can manage billing cycles, payments, due dates, alerts and dashboard indicators.',faq6q:'Does the interface work on mobile?',faq6a:'Yes. The platform is designed for desktop and mobile devices with adapted views and navigation.',faq7q:'Is ONE HUB installed for each company?',faq7a:'Yes. Sócrates ONE implements and configures ONE HUB according to the agreed environment and needs of each organization.',faq8q:'Do I need to install it myself?',faq8a:'No. Our team handles installation, initial configuration and go-live. Your company receives the system ready to start operating.',
      installKicker:'Implemented for your company',installTitle:'Not another generic SaaS.<br><span>We install and configure it with you.</span>',installLead:'ONE HUB is delivered as an implementation prepared for your organization, with Sócrates ONE supporting the process from diagnosis to go-live.',install1Title:'Diagnosis',install1Text:'We review how you currently manage clients, sales, projects, support and collections.',install2Title:'Installation',install2Text:'We prepare ONE HUB in the agreed environment for your company and leave the platform operational.',install3Title:'Configuration',install3Text:'We configure identity, users, permissions, initial data, currencies, email and key workflows.',install4Title:'Go-live',install4Text:'We support initial use, training and the adjustments needed to start in an organized way.',installCtaTitle:'Want to see what ONE HUB would look like in your company?',installCtaText:'Schedule a demo and we will review your case before quoting the implementation.',installCtaButton:'Request demonstration',contactKicker:'Implement ONE HUB in your company',contactTitle:'Does your company need to stop working with scattered information?',contactLead:'Tell us how your company works today. We will show you ONE HUB, review your workflow and prepare an implementation proposal.',contactPoint1:'Guided demo',contactPoint2:'Current workflow review',contactPoint3:'Implementation proposal',formName:'Name',formCompany:'Company',formEmail:'Email',formWhatsapp:'WhatsApp / Phone',formMessage:'Tell us what you need to manage, integrate or improve',formSubmit:'Request demo and quote',formNote:'Your data will only be used to respond to this request.',footerText:'Business Operating System connecting clients, sales, projects, support and control.',footerProduct:'Product',footerCommercial:'Sales',legalPrivacy:'Privacy',legalCookies:'Cookies',legalTerms:'Terms',cookieTitle:'Your privacy matters',cookieText:'We only use technical and preference cookies required for the site to work.',cookieMore:'View policy',cookieAccept:'Accept',developedBy:'Developed by',statusSent:'Request sent. We will contact you shortly.',statusError:'We could not send your request. Email us at info@socrates.la.',statusInvalid:'Please review the form and try again.',statusRate:'Please wait a few seconds before sending another request.'
    }
  };


  Object.assign(i18n.es, {
    navCompare:'Comparar',
    compareKicker:'Comparación clara',
    compareTitle:'No pagas para ir desbloqueando el sistema.<br><span>ONE HUB se implementa completo.</span>',
    compareLead:'El producto base se cotiza con un solo valor de implementación e incluye todos los módulos disponibles de ONE HUB. Si tu empresa necesita algo especial, podemos desarrollarlo y cotizarlo aparte.',
    compareOurBadge:'IMPLEMENTACIÓN PROPIA',compareSubBadge:'SUSCRIPCIÓN',compareOurPrice:'UN SOLO PRECIO',compareOurText:'Producto base completo + instalación + configuración acordada para tu empresa.',
    compareOur1:'Todos los módulos actuales incluidos',compareOur2:'Instalado en el dominio del cliente',compareOur3:'Identidad y configuración personalizada',compareOur4:'Desarrollos especiales disponibles',
    compareBitrixPrice:'POR PLAN',compareBitrixText:'El precio y las funciones cambian según el plan y el límite de usuarios. La edición On-Premise se licencia aparte.',compareBitrixRef:'Referencia Brasil: Basic R$399/mes · Standard R$799 · Professional R$1.599',
    compareHubPrice:'POR HUB + EDICIÓN + LICENCIA',compareHubText:'Ventas, servicio y otras áreas se comercializan por productos y niveles, con precios por licencia en varios planes.',compareHubRef:'Referencia: Sales Hub Pro desde US$90/mes/licencia con pago anual',
    compareFeature:'Característica',cmpPrice:'Modelo de precio',cmpPriceOne:'Precio único de implementación',cmpPriceBitrix:'Suscripción mensual/anual por plan',cmpPriceHub:'Suscripción por Hub, edición y licencia',
    cmpModules:'Módulos del producto base',cmpModulesOne:'Todos incluidos',cmpModulesBitrix:'Se amplían según plan',cmpModulesHub:'Se distribuyen por Hubs y ediciones',cmpCRM:'CRM y pipeline',cmpIncluded:'Incluido',
    cmpQuotes:'Cotizaciones y contratos',cmpByPlan:'Según plan',cmpByProduct:'Según producto/edición',cmpProjects:'Gestión de proyectos',cmpProjectOne:'Kanban, Gantt, calendario, horas, riesgos y costos',cmpProjectBitrix:'Incluida, con alcance según plan',cmpProjectHub:'No es un módulo PM integral equivalente',
    cmpHelpdesk:'Helpdesk y SLA',cmpServiceHub:'Service Hub',cmpBilling:'Cobros, vencimientos y pagos',cmpCommerce:'Según producto, plan y región',cmpReports:'Dashboard y reportes',cmpDomain:'Instalación en dominio/servidor del cliente',cmpYes:'Sí',cmpBitrixDomain:'Cloud: no · On-Premise: edición aparte',cmpCloudOnly:'No, plataforma cloud',
    cmpBrand:'Identidad visual de la empresa',cmpBrandOne:'Personalizada en la implementación',cmpBrandOthers:'Opciones según edición/plan',cmpCustom:'Función especial para tu empresa',cmpCustomOne:'Sí, desarrollo directo a medida',cmpCustomOthers:'Apps, API y partners',cmpInfra:'Control del entorno de alojamiento',cmpInfraOne:'En el hosting/servidor acordado con el cliente',cmpInfraBitrix:'Cloud o edición On-Premise separada',cmpInfraHub:'Infraestructura cloud de HubSpot',
    compareBottomTitle:'La diferencia no es solo el software.',compareBottomText:'ONE HUB se instala, configura y adapta para que tu empresa empiece con una plataforma completa, no con una lista de módulos por desbloquear.',compareCta:'Cotizar ONE HUB',compareSources:'Referencias públicas consultadas el 08/09/2026. Los precios y condiciones de terceros pueden cambiar.',
    faq9q:'¿El precio de ONE HUB incluye todos los módulos?',faq9a:'Sí. El producto base se cotiza como una implementación completa e incluye los módulos disponibles de ONE HUB. No necesitas subir de plan para desbloquear áreas del sistema.',faq10q:'¿Pueden desarrollar una función especial para mi empresa?',faq10a:'Sí. Si necesitas una integración, flujo o función que no forme parte del producto base, podemos evaluarla y cotizar ese desarrollo adicional por separado.'
  });

  Object.assign(i18n.pt, {
    navCompare:'Comparar',
    compareKicker:'Comparação clara',
    compareTitle:'Você não paga para ir desbloqueando o sistema.<br><span>O ONE HUB é implementado completo.</span>',
    compareLead:'O produto base é cotado com um único valor de implementação e inclui todos os módulos disponíveis do ONE HUB. Se sua empresa precisar de algo especial, podemos desenvolver e cotar separadamente.',
    compareOurBadge:'IMPLEMENTAÇÃO PRÓPRIA',compareSubBadge:'ASSINATURA',compareOurPrice:'UM ÚNICO PREÇO',compareOurText:'Produto base completo + instalação + configuração acordada para sua empresa.',
    compareOur1:'Todos os módulos atuais incluídos',compareOur2:'Instalado no domínio do cliente',compareOur3:'Identidade e configuração personalizadas',compareOur4:'Desenvolvimentos especiais disponíveis',
    compareBitrixPrice:'POR PLANO',compareBitrixText:'O preço e os recursos mudam conforme o plano e o limite de usuários. A edição On-Premise é licenciada separadamente.',compareBitrixRef:'Referência Brasil: Basic R$399/mês · Standard R$799 · Professional R$1.599',
    compareHubPrice:'POR HUB + EDIÇÃO + LICENÇA',compareHubText:'Vendas, atendimento e outras áreas são comercializadas por produtos e níveis, com preços por licença em vários planos.',compareHubRef:'Referência: Sales Hub Pro a partir de US$90/mês/licença no pagamento anual',
    compareFeature:'Característica',cmpPrice:'Modelo de preço',cmpPriceOne:'Preço único de implementação',cmpPriceBitrix:'Assinatura mensal/anual por plano',cmpPriceHub:'Assinatura por Hub, edição e licença',
    cmpModules:'Módulos do produto base',cmpModulesOne:'Todos incluídos',cmpModulesBitrix:'Ampliados conforme o plano',cmpModulesHub:'Distribuídos por Hubs e edições',cmpCRM:'CRM e pipeline',cmpIncluded:'Incluído',
    cmpQuotes:'Propostas e contratos',cmpByPlan:'Conforme o plano',cmpByProduct:'Conforme produto/edição',cmpProjects:'Gestão de projetos',cmpProjectOne:'Kanban, Gantt, calendário, horas, riscos e custos',cmpProjectBitrix:'Incluída, com alcance conforme o plano',cmpProjectHub:'Não é um módulo PM integral equivalente',
    cmpHelpdesk:'Helpdesk e SLA',cmpServiceHub:'Service Hub',cmpBilling:'Cobranças, vencimentos e pagamentos',cmpCommerce:'Conforme produto, plano e região',cmpReports:'Dashboard e relatórios',cmpDomain:'Instalação no domínio/servidor do cliente',cmpYes:'Sim',cmpBitrixDomain:'Cloud: não · On-Premise: edição separada',cmpCloudOnly:'Não, plataforma cloud',
    cmpBrand:'Identidade visual da empresa',cmpBrandOne:'Personalizada na implementação',cmpBrandOthers:'Opções conforme edição/plano',cmpCustom:'Função especial para sua empresa',cmpCustomOne:'Sim, desenvolvimento direto sob medida',cmpCustomOthers:'Apps, API e parceiros',cmpInfra:'Controle do ambiente de hospedagem',cmpInfraOne:'No hosting/servidor acordado com o cliente',cmpInfraBitrix:'Cloud ou edição On-Premise separada',cmpInfraHub:'Infraestrutura cloud da HubSpot',
    compareBottomTitle:'A diferença não é apenas o software.',compareBottomText:'O ONE HUB é instalado, configurado e adaptado para que sua empresa comece com uma plataforma completa, e não com uma lista de módulos para desbloquear.',compareCta:'Cotar ONE HUB',compareSources:'Referências públicas consultadas em 08/09/2026. Preços e condições de terceiros podem mudar.',
    faq9q:'O preço do ONE HUB inclui todos os módulos?',faq9a:'Sim. O produto base é cotado como uma implementação completa e inclui os módulos disponíveis do ONE HUB. Você não precisa subir de plano para desbloquear áreas do sistema.',faq10q:'Vocês podem desenvolver uma função especial para minha empresa?',faq10a:'Sim. Se você precisar de uma integração, fluxo ou função que não faça parte do produto base, podemos avaliar e cotar esse desenvolvimento adicional separadamente.'
  });

  Object.assign(i18n.en, {
    navCompare:'Compare',
    compareKicker:'Clear comparison',
    compareTitle:'You do not pay to keep unlocking the system.<br><span>ONE HUB is implemented as a complete platform.</span>',
    compareLead:'The base product is quoted with one implementation price and includes all ONE HUB modules available at delivery. If your company needs something special, we can develop and quote it separately.',
    compareOurBadge:'OWN IMPLEMENTATION',compareSubBadge:'SUBSCRIPTION',compareOurPrice:'ONE PRICE',compareOurText:'Complete base product + installation + configuration agreed for your company.',
    compareOur1:'All current modules included',compareOur2:'Installed on the client domain',compareOur3:'Customized identity and configuration',compareOur4:'Special development available',
    compareBitrixPrice:'BY PLAN',compareBitrixText:'Price and features change by plan and user limit. The On-Premise edition is licensed separately.',compareBitrixRef:'Brazil reference: Basic R$399/month · Standard R$799 · Professional R$1,599',
    compareHubPrice:'BY HUB + EDITION + SEAT',compareHubText:'Sales, service and other areas are sold through separate products and tiers, with per-seat pricing across several plans.',compareHubRef:'Reference: Sales Hub Pro from US$90/month/seat with annual billing',
    compareFeature:'Feature',cmpPrice:'Pricing model',cmpPriceOne:'One implementation price',cmpPriceBitrix:'Monthly/annual subscription by plan',cmpPriceHub:'Subscription by Hub, edition and seat',
    cmpModules:'Base product modules',cmpModulesOne:'All included',cmpModulesBitrix:'Expanded by plan',cmpModulesHub:'Split across Hubs and editions',cmpCRM:'CRM and pipeline',cmpIncluded:'Included',
    cmpQuotes:'Quotations and contracts',cmpByPlan:'Plan-dependent',cmpByProduct:'Product/edition-dependent',cmpProjects:'Project management',cmpProjectOne:'Kanban, Gantt, calendar, time, risks and costs',cmpProjectBitrix:'Included, scope varies by plan',cmpProjectHub:'No equivalent full PM module',
    cmpHelpdesk:'Helpdesk and SLA',cmpServiceHub:'Service Hub',cmpBilling:'Collections, due dates and payments',cmpCommerce:'Depends on product, plan and region',cmpReports:'Dashboard and reports',cmpDomain:'Install on client domain/server',cmpYes:'Yes',cmpBitrixDomain:'Cloud: no · On-Premise: separate edition',cmpCloudOnly:'No, cloud platform',
    cmpBrand:'Company visual identity',cmpBrandOne:'Customized during implementation',cmpBrandOthers:'Options vary by edition/plan',cmpCustom:'Special feature for your company',cmpCustomOne:'Yes, direct custom development',cmpCustomOthers:'Apps, API and partners',cmpInfra:'Hosting environment control',cmpInfraOne:'On hosting/server agreed with the client',cmpInfraBitrix:'Cloud or separate On-Premise edition',cmpInfraHub:'HubSpot cloud infrastructure',
    compareBottomTitle:'The difference is not only the software.',compareBottomText:'ONE HUB is installed, configured and adapted so your company starts with a complete platform, not a list of modules waiting to be unlocked.',compareCta:'Get a ONE HUB quote',compareSources:'Public references checked on 08/09/2026. Third-party prices and terms may change.',
    faq9q:'Does ONE HUB pricing include all modules?',faq9a:'Yes. The base product is quoted as a complete implementation and includes the available ONE HUB modules. You do not need to upgrade plans to unlock system areas.',faq10q:'Can you develop a special feature for my company?',faq10a:'Yes. If you need an integration, workflow or feature that is not part of the base product, we can assess it and quote that additional development separately.'
  });


  Object.assign(i18n.es, {
    navAssistant:'Asistente',skipContent:'Saltar al contenido',
    heroTitle:'Tu empresa,<br><span>conectada en un solo lugar.</span>',
    heroLead:'ONE HUB unifica clientes, ventas, cotizaciones, contratos, proyectos, soporte, cobros y reportes en una sola plataforma empresarial instalada para tu organización.',
    heroNote:'Un producto completo, implementado y configurado para tu empresa. Si necesitas algo especial, también podemos desarrollarlo.',
    heroTrust1:'Implementación guiada',heroTrust2:'Soporte cercano',heroTrust3:'Sin módulos por desbloquear',
    assistantKicker:'Ayuda integrada dentro de ONE HUB',assistantTitle:'Asistente del CRM.<br><span>Encuentra funciones sin perder tiempo.</span>',assistantLead:'Orienta a tus usuarios dentro del sistema, ayuda a encontrar funciones y acelera el trabajo diario con respuestas guiadas.',
    assistantB1Title:'Orientación dentro del sistema',assistantB1Text:'Indica dónde realizar una tarea o encontrar una función.',assistantB2Title:'Menos fricción para el usuario',assistantB2Text:'Reduce búsquedas, dudas repetitivas y tiempo de aprendizaje.',assistantB3Title:'Disponible mientras trabajas',assistantB3Text:'Se abre desde el propio ONE HUB sin abandonar la operación.',
    assistantQ1:'¿Dónde creo una cotización?',assistantQ2:'¿Cómo registro un vencimiento?',assistantQ3:'¿Dónde veo mis cobros?',
    assistantA1:'Ve a Cotizaciones y documentos → Nueva cotización. Desde ahí puedes elegir cliente, moneda, idioma y productos o servicios.',assistantA2:'Puedes registrar y revisar vencimientos desde Finanzas y cobros. El dashboard también te muestra los próximos y los vencidos.',assistantA3:'Abre Finanzas e informes → Cobros. Allí puedes revisar cobros del mes, vencidos, pagos y próximos vencimientos.',
    assistantWindowTitle:'Asistente del CRM',assistantWindowSub:'Pregunta cómo usar o dónde encontrar una función',assistantGreeting:'Hola. Puedo orientarte dentro de ONE HUB. Por ejemplo: “¿Dónde creo una cotización?” o “¿Cómo registro un vencimiento?”.',assistantDemoHint:'Selecciona una pregunta de ejemplo para ver cómo te orienta.',assistantPlaceholder:'Escribe tu pregunta...',assistantDemoReply:'En una instalación real, el asistente te orienta dentro de ONE HUB y te indica el camino más directo hacia la función que necesitas.',assistantSideNote:'Tu guía dentro de ONE HUB.',assistantFloatText:'En ONE HUB el asistente acompaña al usuario mientras trabaja y le indica dónde encontrar las funciones que necesita.',
    quickModulesTitle:'Módulos que impulsan tu negocio',quickModulesText:'Todo conectado desde un mismo entorno.',quick1Title:'CRM & Ventas',quick1Text:'Clientes y oportunidades',quick2Title:'Project Manager',quick2Text:'Proyectos y equipos',quick3Title:'Cotizaciones',quick3Text:'Propuestas y contratos',quick4Title:'Helpdesk',quick4Text:'Tickets y SLA',quick5Title:'Finanzas',quick5Text:'Cobros y vencimientos',quick6Title:'Reportes',quick6Text:'Datos para decidir',
    languageStripTitle:'ONE HUB está disponible en 3 idiomas',languageStripNote:'La misma plataforma, en el idioma de tu equipo.',searchDemoClientLabel:'Cliente'
  });

  Object.assign(i18n.pt, {
    navAssistant:'Assistente',skipContent:'Ir para o conteúdo',
    heroTitle:'Sua empresa,<br><span>conectada em um só lugar.</span>',
    heroLead:'O ONE HUB unifica clientes, vendas, propostas, contratos, projetos, suporte, cobranças e relatórios em uma única plataforma empresarial instalada para sua organização.',
    heroNote:'Um produto completo, implementado e configurado para sua empresa. Se você precisar de algo especial, também podemos desenvolver.',
    heroTrust1:'Implementação guiada',heroTrust2:'Suporte próximo',heroTrust3:'Sem módulos para desbloquear',
    assistantKicker:'Ajuda integrada dentro do ONE HUB',assistantTitle:'Assistente do CRM.<br><span>Encontre funções sem perder tempo.</span>',assistantLead:'Orienta seus usuários dentro do sistema, ajuda a encontrar funções e acelera o trabalho diário com respostas guiadas.',
    assistantB1Title:'Orientação dentro do sistema',assistantB1Text:'Indica onde realizar uma tarefa ou encontrar uma função.',assistantB2Title:'Menos atrito para o usuário',assistantB2Text:'Reduz buscas, dúvidas repetitivas e tempo de aprendizado.',assistantB3Title:'Disponível enquanto você trabalha',assistantB3Text:'Abre dentro do próprio ONE HUB sem sair da operação.',
    assistantQ1:'Onde crio uma proposta?',assistantQ2:'Como registro um vencimento?',assistantQ3:'Onde vejo minhas cobranças?',
    assistantA1:'Acesse Propostas e documentos → Nova proposta. Ali você pode escolher cliente, moeda, idioma e produtos ou serviços.',assistantA2:'Você pode registrar e revisar vencimentos em Finanças e cobranças. O dashboard também mostra os próximos e os vencidos.',assistantA3:'Abra Finanças e relatórios → Cobranças. Ali você pode revisar cobranças do mês, vencidas, pagamentos e próximos vencimentos.',
    assistantWindowTitle:'Assistente do CRM',assistantWindowSub:'Pergunte como usar ou onde encontrar uma função',assistantGreeting:'Olá. Posso orientar você dentro do ONE HUB. Por exemplo: “Onde crio uma proposta?” ou “Como registro um vencimento?”.',assistantDemoHint:'Selecione uma pergunta de exemplo para ver como o assistente orienta você.',assistantPlaceholder:'Digite sua pergunta...',assistantDemoReply:'Em uma instalação real, o assistente orienta você dentro do ONE HUB e mostra o caminho mais direto até a função de que precisa.',assistantSideNote:'Seu guia dentro do ONE HUB.',assistantFloatText:'No ONE HUB, o assistente acompanha o usuário durante o trabalho e mostra onde encontrar as funções necessárias.',
    quickModulesTitle:'Módulos que impulsionam seu negócio',quickModulesText:'Tudo conectado no mesmo ambiente.',quick1Title:'CRM & Vendas',quick1Text:'Clientes e oportunidades',quick2Title:'Project Manager',quick2Text:'Projetos e equipes',quick3Title:'Propostas',quick3Text:'Propostas e contratos',quick4Title:'Helpdesk',quick4Text:'Tickets e SLA',quick5Title:'Finanças',quick5Text:'Cobranças e vencimentos',quick6Title:'Relatórios',quick6Text:'Dados para decidir',
    languageStripTitle:'O ONE HUB está disponível em 3 idiomas',languageStripNote:'A mesma plataforma, no idioma da sua equipe.',searchDemoClientLabel:'Cliente'
  });

  Object.assign(i18n.en, {
    navAssistant:'Assistant',skipContent:'Skip to content',
    heroTitle:'Your company,<br><span>connected in one place.</span>',
    heroLead:'ONE HUB unifies clients, sales, quotations, contracts, projects, support, collections and reports in one business platform installed for your organization.',
    heroNote:'A complete product, implemented and configured for your company. If you need something special, we can build it as well.',
    heroTrust1:'Guided implementation',heroTrust2:'Close support',heroTrust3:'No modules to unlock',
    assistantKicker:'Built-in help inside ONE HUB',assistantTitle:'CRM Assistant.<br><span>Find features without wasting time.</span>',assistantLead:'It guides users inside the system, helps them find features and speeds up daily work with guided answers.',
    assistantB1Title:'In-system guidance',assistantB1Text:'Shows where to perform a task or find a feature.',assistantB2Title:'Less friction for users',assistantB2Text:'Reduces searching, repeated questions and learning time.',assistantB3Title:'Available while you work',assistantB3Text:'Opens inside ONE HUB without leaving the workflow.',
    assistantQ1:'Where do I create a quotation?',assistantQ2:'How do I register a due date?',assistantQ3:'Where do I see collections?',
    assistantA1:'Go to Quotations and documents → New quotation. From there you can select the client, currency, language and products or services.',assistantA2:'You can register and review due dates under Finance and collections. The dashboard also shows upcoming and overdue items.',assistantA3:'Open Finance and reports → Collections. There you can review this month’s collections, overdue items, payments and upcoming due dates.',
    assistantWindowTitle:'CRM Assistant',assistantWindowSub:'Ask how to use or where to find a feature',assistantGreeting:'Hi. I can guide you inside ONE HUB. For example: “Where do I create a quotation?” or “How do I register a due date?”.',assistantDemoHint:'Select a sample question to see how the assistant guides you.',assistantPlaceholder:'Type your question...',assistantDemoReply:'In a real installation, the assistant guides you inside ONE HUB and shows the fastest path to the feature you need.',assistantSideNote:'Your guide inside ONE HUB.',assistantFloatText:'In ONE HUB, the assistant stays with users while they work and shows where to find the features they need.',
    quickModulesTitle:'Modules that power your business',quickModulesText:'Everything connected in the same environment.',quick1Title:'CRM & Sales',quick1Text:'Clients and opportunities',quick2Title:'Project Manager',quick2Text:'Projects and teams',quick3Title:'Quotations',quick3Text:'Proposals and contracts',quick4Title:'Helpdesk',quick4Text:'Tickets and SLA',quick5Title:'Finance',quick5Text:'Collections and due dates',quick6Title:'Reports',quick6Text:'Data for decisions',
    languageStripTitle:'ONE HUB is available in 3 languages',languageStripNote:'The same platform, in your team’s language.',searchDemoClientLabel:'Client'
  });

  const normalize = lang => ['es','pt','en'].includes(lang) ? lang : 'es';
  const stored = (() => { try { return localStorage.getItem('onehub_public_language'); } catch(_) { return null; } })();
  let current = normalize(stored || document.documentElement.lang || 'es');

  
  /* ONE HUB Community: override commercial copy; the original design and language system stay intact. */
  Object.assign(i18n.es, {"navImplementation": "DESCARGAS", "requestDemo": "DESCARGAR GRATIS", "heroEyebrow": "ONE HUB · BUSINESS OPERATING SYSTEM · OPEN SOURCE", "heroLead": "CRM, ventas, cotizaciones, contratos, proyectos, documentos, finanzas y email marketing conectados en una plataforma de código abierto que puedes instalar en tu servidor.", "heroCta": "DESCARGAR GRATIS", "heroNote": "Sin licencias obligatorias: descarga el código, configura MySQL y personaliza ONE HUB. Apoyar al desarrollador es voluntario.", "compareOurBadge": "OPEN SOURCE", "compareOurPrice": "GRATIS · CÓDIGO ABIERTO", "compareOurText": "Descarga gratuita de todo el código y los módulos. Instalación y soporte profesional opcionales.", "cmpPriceOne": "Descarga gratuita · Sin licencias obligatorias", "compareCta": "DESCARGAR ONE HUB", "installKicker": "Instala en tu servidor", "installTitle": "Tu empresa conectada.<br><span>Tu código, tu libertad.</span>", "installLead": "Descarga ONE HUB en versión CLEAN o DEMO, crea una base MySQL e instala desde el navegador. El manual está disponible de forma gratuita. También puedes contratar soporte independiente.", "install1Title": "Elegir edición", "install1Text": "Descarga CLEAN para comenzar desde cero o DEMO para explorar datos ficticios.", "install2Title": "Instalar", "install2Text": "Sube los archivos a tu servidor PHP, crea una base MySQL y completa el instalador.", "install3Title": "Personalizar", "install3Text": "Configura tu empresa, usuarios, permisos y procesos en tu propia instalación.", "installCtaButton": "VER DESCARGAS", "faq9q": "¿ONE HUB Community es gratuito?", "faq9a": "Sí. Puedes descargar el código y utilizar los módulos sin pagar licencia. Hosting, instalación gestionada, personalización o soporte contratado pueden tener costos independientes.", "faq10a": "Sí. Los desarrolladores pueden adaptar el código, respetando la licencia definitiva del proyecto. También ofrecemos desarrollos profesionales opcionales.", "contactKicker": "Descarga, instala y colabora", "contactTitle": "Todo ONE HUB, disponible para tu empresa.", "contactLead": "Elige la versión limpia o demo y consulta el manual de instalación. Los aportes voluntarios permiten continuar el desarrollo.", "contactPoint1": "Software descargable", "contactPoint2": "Manual de instalación incluido", "contactPoint3": "Sin pagos obligatorios", "footerText": "Plataforma de gestión empresarial de código abierto. Descarga gratuita y desarrollo comunitario.", "ossKicker": "OPEN SOURCE · DESCARGA GRATUITA", "ossTitle": "Toda la plataforma.<br><span>Sin pagos de licencia.</span>", "ossIntro": "Instala ONE HUB en tu propio servidor. Elige CLEAN o DEMO. Apoyar el desarrollo es siempre voluntario.", "ossCleanDesc": "Para empezar desde cero: CRM, proyectos, documentos, finanzas y EMAILSEND, sin registros empresariales.", "ossDemoDesc": "Para explorar la plataforma: clientes, negocios, proyectos, cotizaciones y campañas ficticias.", "ossCleanA": "Código PHP + MySQL", "ossCleanB": "Instalador desde cero", "ossCleanC": "Sin datos de clientes", "ossDemoA": "Contenido ficticio de muestra", "ossDemoB": "Activa la demostración al instalar", "ossDemoC": "Sin envío real de campañas", "ossSupportTag": "COLABORA", "ossSupportTitle": "Apoya el desarrollo", "ossSupportDesc": "El código de ONE HUB es gratuito. Puedes contribuir con mejoras, traducciones o patrocinio voluntario.", "ossSupportA": "Sin funciones bloqueadas", "ossSupportB": "Soporte profesional opcional", "ossSupportC": "Patrocinios próximamente", "ossCleanBtn": "↓ DESCARGAR CLEAN", "ossDemoBtn": "↓ DESCARGAR DEMO", "ossManualBtn": "↗ LEER MANUAL DE INSTALACIÓN", "ossRCNote": "Edición candidata para pruebas. Antes de la publicación pública habrá que definir licencia y auditar dependencias.", "ossContactBoxKicker": "ACCESO GRATUITO", "ossContactBoxTitle": "Elige cómo empezar con ONE HUB", "ossContactBoxText": "Puedes descargar una instalación vacía o explorar datos ficticios en la edición DEMO. Ambas tienen los mismos módulos.", "ossHelpText": "Si necesitas instalación gestionada o adaptaciones profesionales, contacta al desarrollador.", "faq7q": "¿Necesito contratar una implementación para usar ONE HUB?", "faq7a": "No. Puedes descargar e instalar ONE HUB por tu cuenta. La instalación y configuración profesionales son servicios opcionales.", "faq8q": "¿Puedo instalarlo en mi propio servidor?", "faq8a": "Sí. Necesitas un servidor PHP 8.1+ y MySQL. El manual incluye los pasos para instalar CLEAN o DEMO.", "install4Title": "Poner en marcha", "install4Text": "Crea usuarios, adapta el sistema a tus procesos y decide si quieres contratar apoyo profesional.", "installCtaTitle": "¿Quieres comenzar con ONE HUB Community?", "installCtaText": "Descarga el código sin costo, sigue el manual e instala la edición de tu preferencia.", "securityCta": "VER DESCARGAS", "compareKicker": "CÓDIGO ABIERTO", "compareTitle": "No pagas para desbloquear módulos.<br><span>Descarga el sistema completo.</span>", "compareLead": "ONE HUB Community ofrece el código y los módulos disponibles gratuitamente. Hosting y servicios profesionales se contratan aparte."});
  Object.assign(i18n.pt, {"navImplementation": "DOWNLOADS", "requestDemo": "BAIXAR GRÁTIS", "heroEyebrow": "ONE HUB · BUSINESS OPERATING SYSTEM · OPEN SOURCE", "heroLead": "CRM, vendas, propostas, contratos, projetos, documentos, finanças e email marketing conectados em uma plataforma de código aberto que você instala no próprio servidor.", "heroCta": "BAIXAR GRÁTIS", "heroNote": "Sem licença obrigatória: baixe o código, configure o MySQL e personalize o ONE HUB. Contribuir com o desenvolvedor é opcional.", "compareOurBadge": "CÓDIGO ABERTO", "compareOurPrice": "GRÁTIS · OPEN SOURCE", "compareOurText": "Download gratuito de todos os módulos e código. Instalação gerenciada e suporte profissional são opcionais.", "cmpPriceOne": "Download gratuito · Sem licença obrigatória", "compareCta": "BAIXAR ONE HUB", "installKicker": "Instale no seu servidor", "installTitle": "Sua empresa conectada.<br><span>Seu código, sua liberdade.</span>", "installLead": "Baixe ONE HUB CLEAN ou DEMO, crie um banco MySQL e conclua a instalação pelo navegador. O manual é gratuito. Suporte profissional opcional.", "install1Title": "Escolher edição", "install1Text": "Baixe CLEAN para começar do zero ou DEMO para explorar dados fictícios.", "install2Title": "Instalar", "install2Text": "Envie os arquivos ao seu servidor PHP, crie um banco MySQL e finalize o instalador.", "install3Title": "Personalizar", "install3Text": "Configure empresa, usuários, permissões e processos na sua instalação.", "installCtaButton": "VER DOWNLOADS", "faq9q": "ONE HUB Community é gratuito?", "faq9a": "Sim. Você pode baixar o código e usar os módulos sem licença paga. Hospedagem, configuração, personalização ou suporte contratado são serviços separados.", "faq10a": "Sim. Desenvolvedores podem adaptar o código de acordo com a licença definitiva. Também oferecemos desenvolvimento profissional opcional.", "contactKicker": "Baixe, instale e colabore", "contactTitle": "ONE HUB completo para sua empresa.", "contactLead": "Escolha a edição CLEAN ou DEMO e consulte o manual. Contribuições voluntárias ajudam a manter o desenvolvimento.", "contactPoint1": "Software para download", "contactPoint2": "Manual incluído", "contactPoint3": "Sem pagamentos obrigatórios", "footerText": "Plataforma empresarial de código aberto. Download gratuito e desenvolvimento comunitário.", "ossKicker": "OPEN SOURCE · DOWNLOAD GRATUITO", "ossTitle": "A plataforma completa.<br><span>Sem licença paga.</span>", "ossIntro": "Instale ONE HUB no seu servidor. Escolha CLEAN ou DEMO. Apoiar o desenvolvimento é sempre opcional.", "ossCleanDesc": "Para começar do zero: CRM, projetos, documentos, finanças e EMAILSEND, sem dados empresariais.", "ossDemoDesc": "Para explorar o ecossistema: clientes, negócios, projetos, propostas e campanhas fictícios.", "ossCleanA": "Código PHP + MySQL", "ossCleanB": "Instalador inicial", "ossCleanC": "Sem dados de clientes", "ossDemoA": "Dados fictícios de exemplo", "ossDemoB": "Ative a demo na instalação", "ossDemoC": "Sem envio real de campanhas", "ossSupportTag": "COLABORE", "ossSupportTitle": "Apoie o desenvolvimento", "ossSupportDesc": "ONE HUB é gratuito. Contribua com melhorias, traduções ou patrocínio voluntário.", "ossSupportA": "Sem recursos bloqueados", "ossSupportB": "Suporte profissional opcional", "ossSupportC": "Patrocínios em breve", "ossCleanBtn": "↓ BAIXAR CLEAN", "ossDemoBtn": "↓ BAIXAR DEMO", "ossManualBtn": "↗ LER MANUAL DE INSTALAÇÃO", "ossRCNote": "Versão candidata para testes. É necessário definir a licença e auditar dependências antes da publicação.", "ossContactBoxKicker": "ACESSO GRATUITO", "ossContactBoxTitle": "Escolha como começar com ONE HUB", "ossContactBoxText": "Baixe a versão limpa ou experimente a edição com dados fictícios. Ambas incluem os mesmos módulos.", "ossHelpText": "Precisa de instalação ou personalização profissional? Entre em contato com o desenvolvedor.", "faq7q": "Preciso contratar uma instalação para usar ONE HUB?", "faq7a": "Não. Você pode baixar e instalar ONE HUB por conta própria. Instalação e configuração profissionais são serviços opcionais.", "faq8q": "Posso instalar em meu próprio servidor?", "faq8a": "Sim. É necessário PHP 8.1+ e MySQL. O manual mostra como instalar CLEAN ou DEMO.", "install4Title": "Colocar em operação", "install4Text": "Crie usuários, ajuste processos e escolha se deseja contratar suporte profissional.", "installCtaTitle": "Quer começar com ONE HUB Community?", "installCtaText": "Baixe o código sem custo, siga o manual e instale a edição desejada.", "securityCta": "VER DOWNLOADS", "compareKicker": "CÓDIGO ABERTO", "compareTitle": "Você não paga para liberar módulos.<br><span>Baixe o sistema completo.</span>", "compareLead": "ONE HUB Community oferece gratuitamente código e módulos disponíveis. Hospedagem e serviços profissionais são separados."});
  Object.assign(i18n.en, {"navImplementation": "DOWNLOADS", "requestDemo": "DOWNLOAD FREE", "heroEyebrow": "ONE HUB · BUSINESS OPERATING SYSTEM · OPEN SOURCE", "heroLead": "CRM, sales, proposals, contracts, projects, documents, finance and email marketing integrated into an open-source platform you can host on your own server.", "heroCta": "DOWNLOAD FREE", "heroNote": "No mandatory licensing fee: download the source, set up MySQL and customize ONE HUB. Supporting the developer is voluntary.", "compareOurBadge": "OPEN SOURCE", "compareOurPrice": "FREE · OPEN SOURCE", "compareOurText": "All source code and modules are downloadable for free. Managed installation and professional support are optional.", "cmpPriceOne": "Free download · No mandatory license", "compareCta": "DOWNLOAD ONE HUB", "installKicker": "Host on your server", "installTitle": "Your business connected.<br><span>Your code, your freedom.</span>", "installLead": "Download ONE HUB CLEAN or DEMO, create a MySQL database and complete setup in your browser. The manual is free. Professional support is optional.", "install1Title": "Choose an edition", "install1Text": "Download CLEAN to start from scratch or DEMO to explore fictional data.", "install2Title": "Install", "install2Text": "Upload to your PHP server, create a MySQL database and finish the installer.", "install3Title": "Customize", "install3Text": "Configure your organization, users, access rules and processes in your own instance.", "installCtaButton": "VIEW DOWNLOADS", "faq9q": "Is ONE HUB Community free?", "faq9a": "Yes. You may download the code and use the modules without a license fee. Hosting, managed setup, custom development and support may have separate fees.", "faq10a": "Yes. Developers can adapt source code subject to the final project license. Optional professional customization is also available.", "contactKicker": "Download, install and contribute", "contactTitle": "The complete ONE HUB for your business.", "contactLead": "Choose CLEAN or DEMO and use the installation manual. Voluntary contributions help fund continued development.", "contactPoint1": "Downloadable software", "contactPoint2": "Installation manual included", "contactPoint3": "No mandatory payments", "footerText": "Open-source business management platform. Free downloads and community-led development.", "ossKicker": "OPEN SOURCE · FREE DOWNLOAD", "ossTitle": "The whole platform.<br><span>No licensing fees.</span>", "ossIntro": "Host ONE HUB on your own server. Choose CLEAN or DEMO. Supporting the developer is always optional.", "ossCleanDesc": "Start from scratch: CRM, projects, documents, finance and EMAILSEND, without business records.", "ossDemoDesc": "Explore the ecosystem with fictional customers, deals, projects, quotes and email campaigns.", "ossCleanA": "PHP + MySQL source", "ossCleanB": "Web installer", "ossCleanC": "No customer records", "ossDemoA": "Fictional sample records", "ossDemoB": "Enable during setup", "ossDemoC": "No real campaign sending", "ossSupportTag": "CONTRIBUTE", "ossSupportTitle": "Support development", "ossSupportDesc": "ONE HUB is free. Contribute improvements, translations or voluntary sponsorship.", "ossSupportA": "No locked features", "ossSupportB": "Optional professional support", "ossSupportC": "Sponsorship coming soon", "ossCleanBtn": "↓ DOWNLOAD CLEAN", "ossDemoBtn": "↓ DOWNLOAD DEMO", "ossManualBtn": "↗ READ INSTALLATION GUIDE", "ossRCNote": "Release candidate for testing. Final licensing and third-party dependency audits are required before public release.", "ossContactBoxKicker": "FREE ACCESS", "ossContactBoxTitle": "Choose how to start with ONE HUB", "ossContactBoxText": "Download an empty installation or explore fictional data in DEMO. Both include the same modules.", "ossHelpText": "Need a managed installation or custom development? Contact the developer.", "faq7q": "Must I buy an implementation to use ONE HUB?", "faq7a": "No. You can download and install ONE HUB yourself. Professional setup and configuration are optional services.", "faq8q": "Can I host it on my own server?", "faq8a": "Yes. You need PHP 8.1+ and MySQL. Follow the manual to install CLEAN or DEMO.", "install4Title": "Go live", "install4Text": "Create users, tailor the platform to your workflows and optionally request professional support.", "installCtaTitle": "Ready to try ONE HUB Community?", "installCtaText": "Download the source for free, follow the guide and install your preferred edition.", "securityCta": "VIEW DOWNLOADS", "compareKicker": "OPEN SOURCE", "compareTitle": "No charge to unlock modules.<br><span>Download the full platform.</span>", "compareLead": "ONE HUB Community makes its available modules and source code free to download. Hosting and professional services are optional."});

  // EMAILSEND — catálogo y sección destacada (ES / PT-BR / EN).
  Object.assign(i18n.es, {
    emailModuleBadge:'INCLUIDO EN ONE HUB',
    emailModuleTitle:'EMAILSEND',
    emailModuleText:'Crea campañas de correo, organiza tus contactos y analiza resultados desde ONE HUB.',
    emailModule1:'Listas y segmentación de contactos',
    emailModule2:'Diseño y programación de campañas',
    emailModule3:'Aperturas, clics y reportes',
    emailModuleLink:'CONOCER EMAILSEND',
    emailFooterLink:'EMAILSEND',
    emailFeatureKicker:'EMAILSEND · MÓDULO INCLUIDO',
    emailFeatureTitle:'Email marketing conectado.<br><span>Más cerca de tus clientes.</span>',
    emailFeatureDesc:'Gestiona contactos y listas, diseña campañas personalizadas, programa envíos por SMTP y consulta aperturas, clics y resultados. Todo desde el ecosistema ONE HUB.',
    emailBenefit1:'Contactos y listas organizadas',
    emailBenefit2:'Editor visual de campañas',
    emailBenefit3:'Programación y configuración SMTP',
    emailBenefit4:'Métricas de aperturas y clics',
    emailFeatureCta:'DESCARGAR ONE HUB GRATIS',
    emailDemoNote:'En la edición DEMO los envíos reales están desactivados.',
    emailVisualTitle:'Campañas de email',
    emailVisualCaption:'Marketing y comunicaciones',
    emailVisualMetric1:'Contactos',
    emailVisualMetric2:'Listas',
    emailVisualMetric3:'Campañas',
    emailVisualRecent:'CAMPAÑAS DE EJEMPLO',
    emailVisualCampaign1:'Bienvenida a ONE HUB',
    emailVisualCampaign2:'Newsletter de novedades',
    emailVisualDraft:'Borrador',
    emailVisualSample:'Ejemplo',
    emailVisualDemo:'VISTA ILUSTRATIVA · DATOS FICTICIOS'
  });
  Object.assign(i18n.pt, {
    emailModuleBadge:'INCLUÍDO NO ONE HUB',
    emailModuleTitle:'EMAILSEND',
    emailModuleText:'Crie campanhas por email, organize seus contatos e analise resultados dentro do ONE HUB.',
    emailModule1:'Listas e segmentação de contatos',
    emailModule2:'Criação e agendamento de campanhas',
    emailModule3:'Aberturas, cliques e relatórios',
    emailModuleLink:'CONHECER O EMAILSEND',
    emailFooterLink:'EMAILSEND',
    emailFeatureKicker:'EMAILSEND · MÓDULO INCLUÍDO',
    emailFeatureTitle:'Email marketing conectado.<br><span>Mais perto dos seus clientes.</span>',
    emailFeatureDesc:'Gerencie contatos e listas, crie campanhas personalizadas, agende envios por SMTP e acompanhe aberturas, cliques e resultados. Tudo no ecossistema ONE HUB.',
    emailBenefit1:'Contatos e listas organizados',
    emailBenefit2:'Editor visual de campanhas',
    emailBenefit3:'Agendamento e configuração SMTP',
    emailBenefit4:'Métricas de aberturas e cliques',
    emailFeatureCta:'BAIXAR ONE HUB GRÁTIS',
    emailDemoNote:'Na edição DEMO, o envio de emails reais fica desativado.',
    emailVisualTitle:'Campanhas de email',
    emailVisualCaption:'Marketing e comunicação',
    emailVisualMetric1:'Contatos',
    emailVisualMetric2:'Listas',
    emailVisualMetric3:'Campanhas',
    emailVisualRecent:'CAMPANHAS DE EXEMPLO',
    emailVisualCampaign1:'Boas-vindas ao ONE HUB',
    emailVisualCampaign2:'Newsletter de novidades',
    emailVisualDraft:'Rascunho',
    emailVisualSample:'Exemplo',
    emailVisualDemo:'VISUALIZAÇÃO ILUSTRATIVA · DADOS FICTÍCIOS'
  });
  Object.assign(i18n.en, {
    emailModuleBadge:'INCLUDED WITH ONE HUB',
    emailModuleTitle:'EMAILSEND',
    emailModuleText:'Create email campaigns, organize contacts and analyze performance within ONE HUB.',
    emailModule1:'Contact lists and segmentation',
    emailModule2:'Campaign creation and scheduling',
    emailModule3:'Opens, clicks and reports',
    emailModuleLink:'EXPLORE EMAILSEND',
    emailFooterLink:'EMAILSEND',
    emailFeatureKicker:'EMAILSEND · INCLUDED MODULE',
    emailFeatureTitle:'Connected email marketing.<br><span>Closer to your customers.</span>',
    emailFeatureDesc:'Manage contacts and lists, design personalized campaigns, schedule delivery through SMTP and track opens, clicks and results—all within the ONE HUB ecosystem.',
    emailBenefit1:'Organized contacts and lists',
    emailBenefit2:'Visual campaign editor',
    emailBenefit3:'Scheduling and SMTP settings',
    emailBenefit4:'Open and click metrics',
    emailFeatureCta:'DOWNLOAD ONE HUB FREE',
    emailDemoNote:'Real email sending is disabled in the DEMO edition.',
    emailVisualTitle:'Email campaigns',
    emailVisualCaption:'Marketing and communications',
    emailVisualMetric1:'Contacts',
    emailVisualMetric2:'Lists',
    emailVisualMetric3:'Campaigns',
    emailVisualRecent:'SAMPLE CAMPAIGNS',
    emailVisualCampaign1:'Welcome to ONE HUB',
    emailVisualCampaign2:'Product updates newsletter',
    emailVisualDraft:'Draft',
    emailVisualSample:'Sample',
    emailVisualDemo:'ILLUSTRATIVE VIEW · FICTIONAL DATA'
  });


  /* ONE HUB Community landing v1.4 — Open Source + EMAILSEND, ES/PT/EN */
  Object.assign(i18n.es, {"navEmailMarketing":"EMAILSEND","heroEyebrow":"ONE HUB COMMUNITY · OPEN SOURCE · CÓDIGO ABIERTO","heroTitle":"Todo tu negocio conectado.<br><span>Open Source y gratuito.</span>","heroLead":"CRM, ventas, proyectos, documentos, finanzas y EMAILSEND para email marketing. Descarga el código fuente, instálalo en tu servidor y personaliza tu plataforma.","heroSecondary":"CONOCER EMAILSEND","heroNote":"OPEN SOURCE: código disponible para descargar. CLEAN y DEMO gratis, sin pagos de licencia; las contribuciones al desarrollador son voluntarias.","heroTrust1":"Código fuente descargable","heroTrust2":"CLEAN y DEMO gratuitas","heroTrust3":"EMAILSEND incluido","ossSignatureTitle":"OPEN SOURCE · CÓDIGO ABIERTO","ossSignatureText":"Código fuente · Instalación propia · Descarga gratuita","emailFeatureKicker":"EMAILSEND · INCLUIDO EN ONE HUB OPEN SOURCE","emailFeatureTitle":"EMAILSEND: campañas profesionales.<br><span>Dentro de ONE HUB Open Source.</span>","emailFeatureDesc":"EMAILSEND es el módulo de email marketing incorporado en ONE HUB Community. Centraliza tus contactos, organiza listas, diseña newsletters con su editor visual, programa campañas mediante SMTP y consulta reportes de aperturas y clics. Incluido en las descargas gratuitas CLEAN y DEMO.","emailOpensourceLine":"INCLUIDO EN ONE HUB COMMUNITY · OPEN SOURCE · DESCARGA GRATIS","emailDemoNote":"DEMO: campañas ficticias sin envío real. CLEAN: configura tu propia base de datos y SMTP.","emailFlowBadge":"¿CÓMO FUNCIONA?","emailFlowTitle":"De tus contactos a una campaña medible.","emailFlowIntro":"Una herramienta de marketing por correo integrada en una plataforma empresarial de código abierto.","emailFlow1Title":"CONTACTOS","emailFlow1Text":"Importa y organiza destinatarios en listas.","emailFlow2Title":"DISEÑO","emailFlow2Text":"Crea newsletters y campañas con editor visual.","emailFlow3Title":"PROGRAMACIÓN","emailFlow3Text":"Configura SMTP y agenda las campañas.","emailFlow4Title":"RESULTADOS","emailFlow4Text":"Consulta entregas, aperturas y clics.","ossEmailIncluded":"✓ CRM + EMAILSEND incluidos","ossTitle":"ONE HUB es OPEN SOURCE.<br><span>Descarga gratis CLEAN o DEMO.</span>","footerText":"ONE HUB Community · OPEN SOURCE. CRM, proyectos, documentos, finanzas y EMAILSEND sin pagos de licencia.","footerCommercial":"Comunidad Open Source"});
  Object.assign(i18n.pt, {"navEmailMarketing":"EMAILSEND","heroEyebrow":"ONE HUB COMMUNITY · OPEN SOURCE · CÓDIGO ABERTO","heroTitle":"Toda a sua empresa conectada.<br><span>Open Source e gratuito.</span>","heroLead":"CRM, vendas, projetos, documentos, finanças e EMAILSEND para email marketing. Baixe o código-fonte, instale no seu servidor e personalize sua plataforma.","heroSecondary":"CONHECER EMAILSEND","heroNote":"OPEN SOURCE: código disponível para download. CLEAN e DEMO grátis, sem licença paga; contribuições ao desenvolvedor são voluntárias.","heroTrust1":"Código-fonte para download","heroTrust2":"CLEAN e DEMO gratuitas","heroTrust3":"EMAILSEND incluído","ossSignatureTitle":"OPEN SOURCE · CÓDIGO ABERTO","ossSignatureText":"Código-fonte · Hospedagem própria · Download grátis","emailFeatureKicker":"EMAILSEND · INCLUÍDO NO ONE HUB OPEN SOURCE","emailFeatureTitle":"EMAILSEND: campanhas profissionais.<br><span>Dentro do ONE HUB Open Source.</span>","emailFeatureDesc":"EMAILSEND é o módulo de email marketing incorporado ao ONE HUB Community. Centralize contatos, organize listas, crie newsletters em um editor visual, agende campanhas por SMTP e acompanhe relatórios de aberturas e cliques. Incluído nos downloads gratuitos CLEAN e DEMO.","emailOpensourceLine":"INCLUÍDO NO ONE HUB COMMUNITY · OPEN SOURCE · DOWNLOAD GRÁTIS","emailDemoNote":"DEMO: campanhas fictícias sem envios reais. CLEAN: configure seu próprio banco de dados e SMTP.","emailFlowBadge":"COMO FUNCIONA?","emailFlowTitle":"Dos contatos a uma campanha mensurável.","emailFlowIntro":"Uma ferramenta de email marketing integrada a uma plataforma empresarial de código aberto.","emailFlow1Title":"CONTATOS","emailFlow1Text":"Importe e organize os destinatários em listas.","emailFlow2Title":"CRIAÇÃO","emailFlow2Text":"Crie newsletters e campanhas no editor visual.","emailFlow3Title":"AGENDAMENTO","emailFlow3Text":"Configure o SMTP e agende as campanhas.","emailFlow4Title":"RESULTADOS","emailFlow4Text":"Acompanhe entregas, aberturas e cliques.","ossEmailIncluded":"✓ CRM + EMAILSEND incluídos","ossTitle":"ONE HUB é OPEN SOURCE.<br><span>Baixe CLEAN ou DEMO grátis.</span>","footerText":"ONE HUB Community · OPEN SOURCE. CRM, projetos, documentos, finanças e EMAILSEND sem licença paga.","footerCommercial":"Comunidade Open Source"});
  Object.assign(i18n.en, {"navEmailMarketing":"EMAILSEND","heroEyebrow":"ONE HUB COMMUNITY · OPEN SOURCE","heroTitle":"Your whole business, connected.<br><span>Open Source and free.</span>","heroLead":"CRM, sales, projects, documents, finance and EMAILSEND for email marketing. Download the source, install it on your server and customize your platform.","heroSecondary":"EXPLORE EMAILSEND","heroNote":"OPEN SOURCE: source code available to download. CLEAN and DEMO are free, with no license fees; supporting the developer is voluntary.","heroTrust1":"Downloadable source code","heroTrust2":"Free CLEAN and DEMO","heroTrust3":"EMAILSEND included","ossSignatureTitle":"OPEN SOURCE · SOURCE CODE","ossSignatureText":"Source code · Self-hosted · Free download","emailFeatureKicker":"EMAILSEND · INCLUDED IN ONE HUB OPEN SOURCE","emailFeatureTitle":"EMAILSEND: professional campaigns.<br><span>Inside ONE HUB Open Source.</span>","emailFeatureDesc":"EMAILSEND is the email marketing module included with ONE HUB Community. Centralize contacts, build lists, create newsletters in the visual editor, schedule SMTP campaigns and review open and click reports. Included with free CLEAN and DEMO downloads.","emailOpensourceLine":"INCLUDED WITH ONE HUB COMMUNITY · OPEN SOURCE · FREE DOWNLOAD","emailDemoNote":"DEMO: fictional campaigns without real email sending. CLEAN: configure your own database and SMTP.","emailFlowBadge":"HOW DOES IT WORK?","emailFlowTitle":"From your contacts to measurable campaigns.","emailFlowIntro":"An email marketing tool integrated into an open-source business platform.","emailFlow1Title":"CONTACTS","emailFlow1Text":"Import and organize recipients into lists.","emailFlow2Title":"DESIGN","emailFlow2Text":"Create newsletters and campaigns in the visual editor.","emailFlow3Title":"SCHEDULE","emailFlow3Text":"Configure SMTP and schedule campaigns.","emailFlow4Title":"RESULTS","emailFlow4Text":"Review deliveries, opens and clicks.","ossEmailIncluded":"✓ CRM + EMAILSEND included","ossTitle":"ONE HUB is OPEN SOURCE.<br><span>Download CLEAN or DEMO for free.</span>","footerText":"ONE HUB Community · OPEN SOURCE. CRM, projects, documents, finance and EMAILSEND with no license fees.","footerCommercial":"Open Source Community"});


  /* ONE HUB v1.5: navegación ordenada y terminología EMAILSEND en ES / PT / EN. */
  Object.assign(i18n.es, {navHome:'INICIO',navPlatform:'PLATAFORMA',navModules:'MÓDULOS',navEmailMarketing:'EMAILSEND',navCompare:'COMPARAR',navSecurity:'SEGURIDAD',navFaq:'FAQ',ossCleanBtn:'DESCARGAR CLEAN',ossDemoBtn:'DESCARGAR DEMO',ossManualBtn:'LEER MANUAL DE INSTALACIÓN',emailModuleLink:'CONOCER EMAILSEND',emailFooterLink:'EMAILSEND'});
  Object.assign(i18n.pt, {navHome:'INÍCIO',navPlatform:'PLATAFORMA',navModules:'MÓDULOS',navEmailMarketing:'EMAILSEND',navCompare:'COMPARAR',navSecurity:'SEGURANÇA',navFaq:'FAQ',ossCleanBtn:'BAIXAR CLEAN',ossDemoBtn:'BAIXAR DEMO',ossManualBtn:'LER MANUAL DE INSTALAÇÃO',emailModuleLink:'CONHECER EMAILSEND',emailFooterLink:'EMAILSEND'});
  Object.assign(i18n.en, {navHome:'HOME',navPlatform:'PLATFORM',navModules:'MODULES',navEmailMarketing:'EMAILSEND',navCompare:'COMPARE',navSecurity:'SECURITY',navFaq:'FAQ',ossCleanBtn:'DOWNLOAD CLEAN',ossDemoBtn:'DOWNLOAD DEMO',ossManualBtn:'READ INSTALLATION MANUAL',emailModuleLink:'EXPLORE EMAILSEND',emailFooterLink:'EMAILSEND'});

  /* GitHub Sponsors + Stripe + Mercado Pago — aportes voluntarios (ES / PT-BR / EN). */
  Object.assign(i18n.es, {
    ossSupportC:'GitHub Sponsors, Stripe y Mercado Pago activos',
    ossRCNote:'ONE HUB Community v1.0.0 · Software gratuito y de código abierto. Los aportes son siempre voluntarios.',
    fundingSeeOptions:'VER FORMAS DE APOYAR', fundingFooterLink:'APOYAR EL PROYECTO',
    fundingKicker:'APOYA EL SOFTWARE LIBRE',
    fundingTitle:'Ayúdanos a seguir creando.<br><span>Tecnología abierta para todos.</span>',
    fundingIntro:'Tu contribución voluntaria impulsa el mantenimiento, la seguridad, la documentación y nuevas funciones de ONE HUB Community. El software seguirá siendo gratuito para todos.',
    fundingGitHubTag:'APOYO A CÓDIGO ABIERTO', fundingGitHubDesc:'Patrocina el trabajo de Sócrates Creative Studio desde GitHub. Elige tu contribución y ayuda a sostener nuestros proyectos Open Source.', fundingGitHubCta:'PATROCINAR EN GITHUB',
    fundingStripeTag:'APOYO INTERNACIONAL', fundingStripeDesc:'Realiza un aporte voluntario a través de nuestra página de Stripe y contribuye al desarrollo de herramientas gratuitas.', fundingStripeCta:'APORTAR CON STRIPE',
    fundingMercadoTag:'APOYOS DESDE BRASIL', fundingMercadoDesc:'Colabora desde Brasil mediante nuestro enlace de Mercado Pago y ayuda a mantener el proyecto en evolución.', fundingMercadoCta:'APORTAR CON MERCADO PAGO',
    fundingNote:'Aportes 100 % voluntarios. No se desbloquean funciones ni se exige pago para descargar o utilizar ONE HUB Community.'
  });
  Object.assign(i18n.pt, {
    ossSupportC:'GitHub Sponsors, Stripe e Mercado Pago disponíveis',
    ossRCNote:'ONE HUB Community v1.0.0 · Software gratuito e de código aberto. As contribuições são sempre voluntárias.',
    fundingSeeOptions:'VER FORMAS DE APOIAR', fundingFooterLink:'APOIAR O PROJETO',
    fundingKicker:'APOIE O SOFTWARE LIVRE',
    fundingTitle:'Ajude-nos a continuar criando.<br><span>Tecnologia aberta para todos.</span>',
    fundingIntro:'Sua contribuição voluntária ajuda a manter a segurança, a documentação e a desenvolver novas funções do ONE HUB Community. O software continuará gratuito para todos.',
    fundingGitHubTag:'APOIO AO CÓDIGO ABERTO', fundingGitHubDesc:'Patrocine o trabalho do Sócrates Creative Studio pelo GitHub. Escolha sua contribuição e ajude a manter nossos projetos Open Source.', fundingGitHubCta:'PATROCINAR NO GITHUB',
    fundingStripeTag:'APOIO INTERNACIONAL', fundingStripeDesc:'Faça uma contribuição voluntária pela nossa página do Stripe e ajude a desenvolver ferramentas gratuitas.', fundingStripeCta:'CONTRIBUIR COM STRIPE',
    fundingMercadoTag:'CONTRIBUIÇÕES DO BRASIL', fundingMercadoDesc:'Apoie nosso trabalho no Brasil pelo link do Mercado Pago e ajude a manter o projeto em evolução.', fundingMercadoCta:'CONTRIBUIR COM MERCADO PAGO',
    fundingNote:'Contribuições 100% voluntárias. Nenhuma função é bloqueada e nenhum pagamento é exigido para baixar ou utilizar o ONE HUB Community.'
  });
  Object.assign(i18n.en, {
    ossSupportC:'GitHub Sponsors, Stripe and Mercado Pago available',
    ossRCNote:'ONE HUB Community v1.0.0 · Free and open-source software. Contributions are always optional.',
    fundingSeeOptions:'EXPLORE SUPPORT OPTIONS', fundingFooterLink:'SUPPORT THE PROJECT',
    fundingKicker:'SUPPORT OPEN SOURCE',
    fundingTitle:'Help us keep building.<br><span>Open technology for everyone.</span>',
    fundingIntro:'Your voluntary support helps fund maintenance, security, documentation, and new ONE HUB Community features. The software remains free for everyone.',
    fundingGitHubTag:'OPEN-SOURCE SPONSORSHIP', fundingGitHubDesc:'Sponsor Sócrates Creative Studio on GitHub. Choose your contribution and help us sustain our open-source projects.', fundingGitHubCta:'SPONSOR ON GITHUB',
    fundingStripeTag:'INTERNATIONAL SUPPORT', fundingStripeDesc:'Make a voluntary contribution through our Stripe page and help us build free tools.', fundingStripeCta:'SUPPORT VIA STRIPE',
    fundingMercadoTag:'SUPPORT FROM BRAZIL', fundingMercadoDesc:'Support the project from Brazil through our Mercado Pago link and help it grow.', fundingMercadoCta:'SUPPORT VIA MERCADO PAGO',
    fundingNote:'Contributions are entirely voluntary. No features are locked and no payment is required to download or use ONE HUB Community.'
  });


  /* Descargas v1.0.0 directas de GitHub — solicitud de nombre/email antes de mostrar los enlaces. */
  Object.assign(i18n.es, {
    ossKicker:'OPEN SOURCE · DESCARGAS OFICIALES DE GITHUB',
    ossIntro:'Elige CLEAN o DEMO, registra tu nombre y correo y descarga los paquetes oficiales desde GitHub. Sin pagos obligatorios.',
    ossCleanBtn:'ACCEDER A CLEAN',ossDemoBtn:'ACCEDER A DEMO',
    downloadGateIntro:'Nombre y correo antes de descargar · ZIP oficiales en GitHub · Sin publicidad obligatoria',
    downloadGateStart:'IR AL FORMULARIO'
  });
  Object.assign(i18n.pt, {
    ossKicker:'CÓDIGO ABERTO · DOWNLOADS OFICIAIS NO GITHUB',
    ossIntro:'Escolha CLEAN ou DEMO, informe nome e e-mail e baixe os pacotes oficiais pelo GitHub. Sem pagamentos obrigatórios.',
    ossCleanBtn:'ACESSAR CLEAN',ossDemoBtn:'ACESSAR DEMO',
    downloadGateIntro:'Nome e e-mail antes do download · ZIPs oficiais no GitHub · Sem anúncios obrigatórios',
    downloadGateStart:'IR AO FORMULÁRIO'
  });
  Object.assign(i18n.en, {
    ossKicker:'OPEN SOURCE · OFFICIAL GITHUB DOWNLOADS',
    ossIntro:'Choose CLEAN or DEMO, register your name and email, and download the official files from GitHub. No payment required.',
    ossCleanBtn:'GET CLEAN',ossDemoBtn:'GET DEMO',
    downloadGateIntro:'Name and email before download · Official GitHub ZIPs · No mandatory marketing',
    downloadGateStart:'OPEN DOWNLOAD FORM'
  });

  const buttonIcon = (element) => {
    const key = element.getAttribute('data-i18n') || '';
    const href = element.getAttribute('href') || '';
    if (href === '#contribuir' || /funding/i.test(key) || /github\.com\/sponsors|donate\.stripe\.com|link\.mercadopago\.com\.br/i.test(href)) return 'heart';
    if (href.includes('manual.php') || /Manual|manual/i.test(key)) return 'book';
    if (href.includes('mailto:')) return 'mail';
    if (element.hasAttribute('download') || href === '#descargas' || /Cta|Download|CleanBtn|DemoBtn|requestDemo/i.test(key)) return 'download';
    if (href === '#emailsend' || /emailModule/i.test(key)) return 'mail';
    if (href === '#comparar') return 'compare';
    if (href === '#seguridad') return 'shield';
    if (href === '#faq') return 'help';
    if (href === '#plataforma' || href === '#modulos') return 'modules';
    if (key === 'cookieAccept') return 'check';
    if (element.matches('[data-assistant-question]')) return 'chat';
    return 'arrow';
  };
  const decorateButtons = () => {
    document.querySelectorAll('.oh-btn, .oh-email-module-link, .oh-assistant-prompts button, .oh-assistant-float-actions button').forEach(element => {
      if (element.querySelector(':scope > svg.oh-icon')) return;
      const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
      svg.setAttribute('class','oh-icon');
      svg.setAttribute('viewBox','0 0 24 24');
      svg.setAttribute('aria-hidden','true');
      svg.setAttribute('focusable','false');
      const use = document.createElementNS('http://www.w3.org/2000/svg','use');
      use.setAttribute('href','#oh-ico-'+buttonIcon(element));
      svg.appendChild(use);
      element.prepend(svg);
    });
  };

  function applyLanguage(lang){
    current = normalize(lang);
    document.documentElement.lang = current === 'pt' ? 'pt-BR' : current;
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.dataset.i18n;
      const value = i18n[current]?.[key];
      if (value !== undefined) el.innerHTML = value;
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.dataset.i18nPlaceholder;
      const value = i18n[current]?.[key];
      if (value !== undefined) el.setAttribute('placeholder', value);
    });
    const dynamicAssistantAnswer = document.querySelector('.oh-assistant-demo-answer[data-answer-key]');
    if (dynamicAssistantAnswer) {
      const answerKey = dynamicAssistantAnswer.dataset.answerKey;
      if (i18n[current]?.[answerKey]) dynamicAssistantAnswer.textContent = i18n[current][answerKey];
    }
    document.querySelectorAll('.oh-lang').forEach(btn => btn.classList.toggle('active', btn.dataset.lang === current));
    const langInput = document.getElementById('landing-language');
    if (langInput) langInput.value = current;
    document.querySelectorAll('.oh-legal-links a').forEach(a => { const base = a.getAttribute('href')?.split('?')[0]; if (base) a.setAttribute('href', `${base}?lang=${current}`); });
    document.querySelectorAll('[data-gated-download]').forEach(a => { const edition = a.dataset.gatedDownload || 'clean'; a.href = `descargar.php?lang=${current}&edition=${edition}`; });
    try { localStorage.setItem('onehub_public_language', current); } catch(_) {}
    decorateButtons();
    showFormStatus();
  }

  document.querySelectorAll('.oh-lang').forEach(btn => btn.addEventListener('click', () => applyLanguage(btn.dataset.lang)));
  applyLanguage(current);

  const menuBtn = document.querySelector('.oh-menu-toggle');
  const mobileMenu = document.querySelector('.oh-mobile-menu');
  if (menuBtn && mobileMenu) {
    const closeMenu = () => {
      mobileMenu.hidden = true;
      menuBtn.setAttribute('aria-expanded','false');
      document.body.classList.remove('menu-open');
    };
    const openMenu = () => {
      mobileMenu.hidden = false;
      menuBtn.setAttribute('aria-expanded','true');
      document.body.classList.add('menu-open');
    };
    menuBtn.addEventListener('click', () => {
      const open = menuBtn.getAttribute('aria-expanded') === 'true';
      open ? closeMenu() : openMenu();
    });
    mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
    window.addEventListener('resize', () => { if (window.innerWidth > 1180) closeMenu(); }, { passive:true });
  }

  const observer = 'IntersectionObserver' in window ? new IntersectionObserver(entries => {
    entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('in'); observer.unobserve(entry.target); } });
  }, { threshold:.12 }) : null;
  document.querySelectorAll('.reveal').forEach(el => observer ? observer.observe(el) : el.classList.add('in'));

  const lightbox = document.querySelector('.oh-lightbox');
  const lightboxImg = lightbox?.querySelector('img');
  function openLightbox(src){
    if (!lightbox || !lightboxImg) return;
    lightboxImg.src = src;
    lightbox.hidden = false;
    lightbox.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
  }
  function closeLightbox(){
    if (!lightbox) return;
    lightbox.hidden = true;
    lightbox.setAttribute('aria-hidden','true');
    document.body.style.overflow='';
  }
  document.querySelectorAll('[data-lightbox]').forEach(el => el.addEventListener('click', () => openLightbox(el.dataset.lightbox)));
  lightbox?.querySelector('.oh-lightbox-close')?.addEventListener('click', closeLightbox);
  lightbox?.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

  function showFormStatus(){
    const status = document.body.dataset.contactStatus;
    const box = document.querySelector('.oh-form-status');
    if (!box || !status) return;
    const map = { sent:['statusSent','success'], error:['statusError','error'], invalid:['statusInvalid','error'], rate:['statusRate','error'] };
    const cfg = map[status];
    if (!cfg) return;
    box.textContent = i18n[current]?.[cfg[0]] || '';
    box.className = `oh-form-status show ${cfg[1]}`;
  }
  showFormStatus();

  const assistantForm = document.getElementById('assistant-demo-form');
  const assistantInput = document.getElementById('assistant-demo-input');
  const assistantAnswer = document.querySelector('.oh-assistant-demo-answer');
  const assistantFloatTrigger = document.getElementById('assistant-float-trigger');
  const assistantFloatPanel = document.getElementById('assistant-float-panel');
  const assistantFloatClose = document.getElementById('assistant-float-close');

  function setAssistantAnswer(key){
    if (!assistantAnswer) return;
    assistantAnswer.textContent = i18n[current]?.[key] || i18n[current]?.assistantDemoReply || '';
    assistantAnswer.dataset.answerKey = key || 'assistantDemoReply';
    assistantAnswer.classList.add('active');
  }
  document.querySelectorAll('[data-assistant-question]').forEach(btn => btn.addEventListener('click', () => {
    const key = btn.dataset.assistantQuestion || 'assistantDemoReply';
    const floatContext = btn.closest('.oh-assistant-float-panel');
    if (floatContext) {
      const p = floatContext.querySelector('.oh-assistant-float-body p');
      if (p) p.textContent = i18n[current]?.[key] || i18n[current]?.assistantDemoReply || '';
      return;
    }
    if (assistantInput) assistantInput.value = btn.textContent.trim();
    setAssistantAnswer(key);
  }));
  assistantForm?.addEventListener('submit', e => {
    e.preventDefault();
    if (!assistantInput?.value.trim()) return;
    setAssistantAnswer('assistantDemoReply');
  });
  function closeAssistantFloat(){
    assistantFloatPanel?.classList.remove('open');
    assistantFloatPanel?.setAttribute('aria-hidden','true');
  }
  assistantFloatTrigger?.addEventListener('click', () => {
    if (!assistantFloatPanel) return;
    const open = assistantFloatPanel.classList.toggle('open');
    assistantFloatPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
  });
  assistantFloatClose?.addEventListener('click', closeAssistantFloat);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAssistantFloat(); });
  const toggleAssistantVisibility = () => {
    if (!assistantFloatTrigger) return;
    assistantFloatTrigger.classList.toggle('visible', window.scrollY > 560);
  };
  toggleAssistantVisibility();
  window.addEventListener('scroll', toggleAssistantVisibility, { passive:true });

  const cookieBox = document.getElementById('oh-cookie');
  const cookieAccept = document.getElementById('cookie-accept');
  try {
    if (cookieBox && localStorage.getItem('onehub_cookie_ok') !== '1') cookieBox.hidden = false;
  } catch (_) { if (cookieBox) cookieBox.hidden = false; }
  cookieAccept?.addEventListener('click', () => {
    try { localStorage.setItem('onehub_cookie_ok','1'); } catch (_) {}
    if (cookieBox) cookieBox.hidden = true;
  });

  document.querySelectorAll('details').forEach(detail => detail.addEventListener('toggle', () => {
    if (!detail.open) return;
    const parent = detail.parentElement;
    parent?.querySelectorAll('details').forEach(other => { if (other !== detail) other.open = false; });
  }));
})();
