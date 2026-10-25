<?php declare(strict_types=1); // ONE HUB Community Landing v1.5.0 — HEADER RESPONSIVE + EMAILSEND ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#002f57">
<title>ONE HUB · Open Source · Business Operating System</title>
<meta name="description" content="ONE HUB COMMUNITY OPEN SOURCE: plataforma empresarial gratuita con CRM, gestión documental, proyectos, finanzas y EMAILSEND para crear campañas de email marketing. Código fuente y manual.">
<meta property="og:title" content="ONE HUB · Business Operating System">
<meta property="og:description" content="ONE HUB Community: software empresarial gratuito y de código abierto, CLEAN y DEMO.">
<meta property="og:type" content="website">
<link rel="canonical" href="https://os.socrates.la/">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800;900&family=Ubuntu+Condensed&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/landing.css?v=1.7.0-community-admin">
</head>
<body class="oh-landing" data-contact-status="">
<svg class="oh-icon-sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
  <symbol id="oh-ico-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9"/><path d="M9 21v-7h6v7"/></symbol>
  <symbol id="oh-ico-platform" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 9v12"/></symbol>
  <symbol id="oh-ico-modules" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
  <symbol id="oh-ico-mail" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m3 6 9 7 9-7"/></symbol>
  <symbol id="oh-ico-compare" viewBox="0 0 24 24"><path d="M12 3v18M5 6h14M5 6l-3 6h6L5 6zM19 6l-3 6h6l-3-6zM4 21h16"/></symbol>
  <symbol id="oh-ico-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></symbol>
  <symbol id="oh-ico-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.1-3 4"/><path d="M12 17h.01"/></symbol>
  <symbol id="oh-ico-download" viewBox="0 0 24 24"><path d="M12 3v12m-5-5 5 5 5-5"/><path d="M4 17v4h16v-4"/></symbol>
  <symbol id="oh-ico-book" viewBox="0 0 24 24"><path d="M12 7c-3-2-6-2-9-1v14c3-1 6-1 9 1 3-2 6-2 9-1V6c-3-1-6-1-9 1Z"/><path d="M12 7v14"/></symbol>
  <symbol id="oh-ico-heart" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 1 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></symbol>
  <symbol id="oh-ico-monitor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></symbol>
  <symbol id="oh-ico-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
  <symbol id="oh-ico-send" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4L22 2Z"/><path d="M22 2 11 13"/></symbol>
  <symbol id="oh-ico-sparkles" viewBox="0 0 24 24"><path d="m12 3 1.6 5.4L19 10l-5.4 1.6L12 17l-1.6-5.4L5 10l5.4-1.6L12 3Z"/><path d="m19 17 .5 1.5L21 19l-1.5.5L19 21l-.5-1.5L17 19l1.5-.5L19 17Z"/></symbol>
  <symbol id="oh-ico-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></symbol>
  <symbol id="oh-ico-chat" viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-8 8 10 10 0 0 1-4-.8L3 21l1.8-6A8 8 0 1 1 21 12Z"/></symbol>
  <symbol id="oh-ico-eye" viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></symbol>
</svg>
<a class="skip-link" href="#main" data-i18n="skipContent">Saltar al contenido</a>

<header class="oh-header" id="top">
    <div class="oh-container oh-header-inner">
        <a href="#top" class="oh-brand" aria-label="ONE HUB">
            <img src="assets/img/one-hub-logo.png" alt="ONE HUB">
        </a>

        <nav class="oh-nav" aria-label="Navegación principal">
            <a href="#producto"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-home"></use></svg><span data-i18n="navHome">INICIO</span></a>
            <a href="#plataforma"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-platform"></use></svg><span data-i18n="navPlatform">PLATAFORMA</span></a>
            <a href="#modulos"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-modules"></use></svg><span data-i18n="navModules">MÓDULOS</span></a>
            <a href="#emailsend"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-mail"></use></svg><span data-i18n="navEmailMarketing">EMAILSEND</span></a>
            <a href="#comparar"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-compare"></use></svg><span data-i18n="navCompare">COMPARAR</span></a>
            <a href="#seguridad"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-shield"></use></svg><span data-i18n="navSecurity">SEGURIDAD</span></a>
            <a href="#faq"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-help"></use></svg><span data-i18n="navFaq">FAQ</span></a>
            <a class="oh-nav-support" href="#contribuir"><svg class="oh-icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#oh-ico-heart"></use></svg><span data-i18n="navSupport">APOYAR</span></a>
        </nav>

        <div class="oh-header-actions">
            <div class="oh-langs" aria-label="Idioma / Language">
                <button type="button" class="oh-lang active" data-lang="es" aria-label="Español"><img src="assets/img/flag-es.png" alt=""></button>
                <button type="button" class="oh-lang" data-lang="pt" aria-label="Português"><img src="assets/img/flag-pt.png" alt=""></button>
                <button type="button" class="oh-lang" data-lang="en" aria-label="English"><img src="assets/img/flag-en.png" alt=""></button>
            </div>
            <a class="oh-btn oh-btn-primary oh-demo-link" href="descargar.php" data-gated-download="clean" data-i18n="requestDemo">Descargar gratis</a>
            <button class="oh-menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <div class="oh-mobile-menu" hidden>
        <div class="oh-mobile-menu-head">
            <span class="oh-mobile-lang-label">ES · PT · EN</span>
            <div class="oh-mobile-langs" aria-label="Idioma / Language">
                <button type="button" class="oh-lang active" data-lang="es" aria-label="Español"><img src="assets/img/flag-es.png" alt=""></button>
                <button type="button" class="oh-lang" data-lang="pt" aria-label="Português"><img src="assets/img/flag-pt.png" alt=""></button>
                <button type="button" class="oh-lang" data-lang="en" aria-label="English"><img src="assets/img/flag-en.png" alt=""></button>
            </div>
        </div>
        <a href="#producto"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-home"></use></svg><span data-i18n="navHome">INICIO</span></a>
        <a href="#plataforma"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-platform"></use></svg><span data-i18n="navPlatform">PLATAFORMA</span></a>
        <a href="#modulos"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-modules"></use></svg><span data-i18n="navModules">MÓDULOS</span></a>
        <a href="#emailsend"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-mail"></use></svg><span data-i18n="navEmailMarketing">EMAILSEND</span></a>
        <a href="#comparar"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-compare"></use></svg><span data-i18n="navCompare">COMPARAR</span></a>
        <a href="#seguridad"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-shield"></use></svg><span data-i18n="navSecurity">SEGURIDAD</span></a>
        <a href="#faq"><svg class="oh-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><use href="#oh-ico-help"></use></svg><span data-i18n="navFaq">FAQ</span></a>
        <a class="oh-mobile-support-link" href="#contribuir"><svg class="oh-icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#oh-ico-heart"></use></svg><span data-i18n="navSupport">APOYAR</span></a>
        <div class="oh-mobile-cta">
            <a class="oh-btn oh-btn-primary" href="#descargas" data-i18n="requestDemo">Descargar gratis</a>
        </div>
    </div>
</header>

<main id="main">
    <section class="oh-hero" id="producto">
        <div class="oh-hero-glow oh-hero-glow-a"></div>
        <div class="oh-hero-glow oh-hero-glow-b"></div>
        <div class="oh-container oh-hero-grid">
            <div class="oh-hero-copy reveal">
                <div class="oh-eyebrow">
                    <span class="oh-eyebrow-dot"></span>
                    <span data-i18n="heroEyebrow">ONE HUB COMMUNITY · OPEN SOURCE · CÓDIGO ABIERTO</span>
                </div>
                <h1 data-i18n="heroTitle">Todo tu negocio conectado.<br><span>Open Source y gratuito.</span></h1>
                <p class="oh-hero-lead" data-i18n="heroLead">CRM, ventas, proyectos, documentos, finanzas y EMAILSEND para email marketing. Descarga el código fuente, instálalo en tu servidor y personaliza tu plataforma.</p>
                <div class="oh-hero-actions">
                    <a href="descargar.php" data-gated-download="clean" class="oh-btn oh-btn-primary oh-btn-lg" data-i18n="heroCta">Descargar gratis</a>
                    <a href="#emailsend" class="oh-btn oh-btn-light oh-btn-lg" data-i18n="heroSecondary">CONOCER EMAILSEND</a>
                </div>
                <a href="#contribuir" class="oh-hero-support-ribbon"><svg class="oh-icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#oh-ico-heart"></use></svg><span><strong data-i18n="heroSupportTitle">TU APOYO HACE CRECER EL CÓDIGO LIBRE</strong><small data-i18n="heroSupportText">Contribuye voluntariamente · GitHub · Stripe · Mercado Pago</small></span><span class="oh-hero-support-arrow" aria-hidden="true">↗</span></a>
                <p class="oh-hero-note" data-i18n="heroNote">OPEN SOURCE: código disponible para descargar. CLEAN y DEMO gratis, sin pagos de licencia; las contribuciones al desarrollador son voluntarias.</p>
                <div class="oh-open-source-signature" aria-label="ONE HUB Community Open Source"><span aria-hidden="true">&lt;/&gt;</span><div><strong data-i18n="ossSignatureTitle">OPEN SOURCE · CÓDIGO ABIERTO</strong><small data-i18n="ossSignatureText">Código fuente · Instalación propia · Descarga gratuita</small></div></div>
                <div class="oh-hero-trust" aria-label="Ventajas de implementación">
                    <span><i>✓</i><b data-i18n="heroTrust1">Código fuente descargable</b></span>
                    <span><i>✓</i><b data-i18n="heroTrust2">CLEAN y DEMO gratuitas</b></span>
                    <span><i>✓</i><b data-i18n="heroTrust3">EMAILSEND incluido</b></span>
                </div>
                <div class="oh-chip-row">
                    <span>ES · PT · EN</span>
                    <span data-i18n="chipResponsive">Desktop + móvil</span>
                    <span data-i18n="chipMulti">Multiempresa</span>
                    <span data-i18n="chipSecurity">2FA + auditoría</span>
                    <span data-i18n="chipRoles">Roles y permisos</span>
                </div>
            </div>

            <div class="oh-hero-visual reveal reveal-delay">
                <div class="oh-browser hero-browser" data-lightbox="assets/landing/dashboard.png">
                    <div class="oh-browser-bar">
                        <div class="oh-browser-dots"><i></i><i></i><i></i></div>
                        <div class="oh-browser-url">ONE HUB · Dashboard</div>
                        <span class="oh-live"><i></i> LIVE</span>
                    </div>
                    <div class="oh-browser-screen">
                        <img src="assets/landing/dashboard.png" alt="Dashboard real de ONE HUB">
                    </div>
                </div>
                <div class="oh-floating-card oh-float-a">
                    <span class="oh-mini-icon orange">↗</span>
                    <div><small data-i18n="floatPipeline">Pipeline</small><strong data-i18n="floatPipelineValue">Ventas visibles</strong></div>
                </div>
                <div class="oh-floating-card oh-float-b">
                    <span class="oh-mini-icon green">✓</span>
                    <div><small data-i18n="floatProjects">Proyectos</small><strong data-i18n="floatProjectsValue">Control de punta a punta</strong></div>
                </div>
            </div>
        </div>
    </section>

    

    <!-- EMAILSEND: módulo de marketing integrado en ONE HUB -->
    <section class="oh-section oh-emailsend-section" id="emailsend" aria-labelledby="emailsend-section-title">
        <div class="oh-container">
            <div class="oh-emailsend-feature reveal">
                <div class="oh-emailsend-content">
                    <span class="oh-emailsend-kicker" data-i18n="emailFeatureKicker">EMAILSEND · INCLUIDO EN ONE HUB OPEN SOURCE</span>
                    <h2 id="emailsend-section-title" data-i18n="emailFeatureTitle">EMAILSEND: campañas profesionales.<br><span>Dentro de ONE HUB Open Source.</span></h2>
                    <p data-i18n="emailFeatureDesc">EMAILSEND es el módulo de email marketing incorporado en ONE HUB Community. Centraliza tus contactos, organiza listas, diseña newsletters con su editor visual, programa campañas mediante SMTP y consulta reportes de aperturas y clics. Incluido en las descargas gratuitas CLEAN y DEMO.</p>
                    <div class="oh-emailsend-benefits" role="list">
                        <div role="listitem"><span aria-hidden="true">✓</span><span data-i18n="emailBenefit1">Contactos y listas organizadas</span></div>
                        <div role="listitem"><span aria-hidden="true">✓</span><span data-i18n="emailBenefit2">Editor visual de campañas</span></div>
                        <div role="listitem"><span aria-hidden="true">✓</span><span data-i18n="emailBenefit3">Programación y configuración SMTP</span></div>
                        <div role="listitem"><span aria-hidden="true">✓</span><span data-i18n="emailBenefit4">Métricas de aperturas y clics</span></div>
                    </div>
                    <p class="oh-emailsend-opensource" data-i18n="emailOpensourceLine">INCLUIDO EN ONE HUB COMMUNITY · OPEN SOURCE · DESCARGA GRATIS</p>
                    <div class="oh-emailsend-actions">
                        <a href="#descargas" class="oh-btn oh-btn-primary oh-btn-lg" data-i18n="emailFeatureCta">DESCARGAR ONE HUB GRATIS</a>
                        <span data-i18n="emailDemoNote">DEMO: campañas ficticias sin envío real. CLEAN: configura tu propia base de datos y SMTP.</span>
                    </div>
                </div>
                <div class="oh-emailsend-visual" aria-hidden="true">
                    <div class="oh-email-ui">
                        <div class="oh-email-ui-top"><div class="oh-email-ui-dots"><i></i><i></i><i></i></div><b>EMAILSEND <em>ONE</em></b><span class="oh-email-ui-chip">CRM</span></div>
                        <div class="oh-email-ui-body">
                            <div class="oh-email-ui-heading"><span>✉</span><div><strong data-i18n="emailVisualTitle">Campañas de email</strong><small data-i18n="emailVisualCaption">Marketing y comunicaciones</small></div></div>
                            <div class="oh-email-ui-stats">
                                <div><span>◎</span><small data-i18n="emailVisualMetric1">Contactos</small><b>16</b></div>
                                <div><span>▤</span><small data-i18n="emailVisualMetric2">Listas</small><b>3</b></div>
                                <div><span>✉</span><small data-i18n="emailVisualMetric3">Campañas</small><b>4</b></div>
                            </div>
                            <div class="oh-email-ui-list">
                                <span data-i18n="emailVisualRecent">CAMPAÑAS DE EJEMPLO</span>
                                <div><i></i><b data-i18n="emailVisualCampaign1">Bienvenida a ONE HUB</b><em data-i18n="emailVisualDraft">Borrador</em></div>
                                <div><i></i><b data-i18n="emailVisualCampaign2">Newsletter de novedades</b><em data-i18n="emailVisualSample">Ejemplo</em></div>
                            </div>
                        </div>
                    </div>
                    <span class="oh-email-demo-stamp" data-i18n="emailVisualDemo">VISTA ILUSTRATIVA · DATOS FICTICIOS</span>
                </div>
            </div>
            <div class="oh-email-process" aria-label="Flujo de EMAILSEND">
              <div class="oh-email-process-header">
                <span class="oh-kicker" data-i18n="emailFlowBadge">¿CÓMO FUNCIONA?</span>
                <h3 data-i18n="emailFlowTitle">De tus contactos a una campaña medible.</h3>
                <p data-i18n="emailFlowIntro">Una herramienta de marketing por correo integrada en una plataforma empresarial de código abierto.</p>
              </div>
              <div class="oh-email-process-grid">
                <article><span>01</span><h4 data-i18n="emailFlow1Title">CONTACTOS</h4><p data-i18n="emailFlow1Text">Importa y organiza destinatarios en listas.</p></article>
                <article><span>02</span><h4 data-i18n="emailFlow2Title">DISEÑO</h4><p data-i18n="emailFlow2Text">Crea newsletters y campañas con editor visual.</p></article>
                <article><span>03</span><h4 data-i18n="emailFlow3Title">PROGRAMACIÓN</h4><p data-i18n="emailFlow3Text">Configura SMTP y agenda las campañas.</p></article>
                <article><span>04</span><h4 data-i18n="emailFlow4Title">RESULTADOS</h4><p data-i18n="emailFlow4Text">Consulta entregas, aperturas y clics.</p></article>
              </div>
            </div>
        </div>
    </section>


<section class="oh-section oh-oss-downloads" id="descargas">
  <div class="oh-container">
    <div class="oh-section-head centered reveal">
      <span class="oh-kicker" data-i18n="ossKicker">OPEN SOURCE · DESCARGAS OFICIALES DE GITHUB</span>
      <h2 data-i18n="ossTitle">ONE HUB es OPEN SOURCE.<br><span>Descarga gratis CLEAN o DEMO.</span></h2>
      <p data-i18n="ossIntro">Selecciona CLEAN o DEMO, registra tu nombre y correo y accede a la descarga oficial desde GitHub. El software es gratuito y los aportes son voluntarios.</p>
    </div>
    <div class="oh-download-gate-intro"><svg class="oh-icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#oh-ico-shield"></use></svg><span data-i18n="downloadGateIntro">Nombre y correo antes de la descarga · ZIP oficiales en GitHub · Sin pagos ni publicidad obligatoria</span><a href="descargar.php" data-gated-download="clean" data-i18n="downloadGateStart">IR AL FORMULARIO</a></div>
    <div class="oh-oss-grid">
      <article class="oh-oss-card reveal"><span class="oh-oss-step">01</span><div class="oh-kicker">COMMUNITY</div><h3>CLEAN</h3><span class="oh-oss-email-check" data-i18n="ossEmailIncluded">✓ CRM + EMAILSEND incluidos</span><p data-i18n="ossCleanDesc">Para comenzar desde cero: CRM, proyectos, documentos, finanzas y EMAILSEND, sin registros empresariales.</p><ul><li data-i18n="ossCleanA">Código PHP + MySQL</li><li data-i18n="ossCleanB">Instalador desde cero</li><li data-i18n="ossCleanC">Sin datos de clientes</li></ul><a class="oh-btn oh-btn-primary" href="descargar.php?edition=clean" data-gated-download="clean" data-i18n="ossCleanBtn">DESCARGAR CLEAN</a></article>
      <article class="oh-oss-card reveal"><span class="oh-oss-step">02</span><div class="oh-kicker">COMMUNITY</div><h3>DEMO</h3><span class="oh-oss-email-check" data-i18n="ossEmailIncluded">✓ CRM + EMAILSEND incluidos</span><p data-i18n="ossDemoDesc">Para conocer el ecosistema: clientes, negocios, proyectos, cotizaciones, métricas y campañas ficticias.</p><ul><li data-i18n="ossDemoA">Datos ficticios de muestra</li><li data-i18n="ossDemoB">Activar durante el instalador</li><li data-i18n="ossDemoC">Sin envío real de campañas</li></ul><a class="oh-btn oh-btn-primary" href="descargar.php?edition=demo" data-gated-download="demo" data-i18n="ossDemoBtn">DESCARGAR DEMO</a></article>
      <article class="oh-oss-card oh-oss-card-support reveal" id="apoyar"><span class="oh-oss-step">03</span><div class="oh-kicker" data-i18n="ossSupportTag">COLABORA</div><h3 data-i18n="ossSupportTitle">Apoya el desarrollo</h3><p data-i18n="ossSupportDesc">ONE HUB es gratuito. La comunidad puede contribuir voluntariamente con mejoras, traducciones, documentación o patrocinios.</p><ul><li data-i18n="ossSupportA">Sin funciones bloqueadas</li><li data-i18n="ossSupportB">Soporte profesional opcional</li><li data-i18n="ossSupportC">Plataformas de aporte disponibles</li></ul><a class="oh-btn oh-btn-light" href="#contribuir" data-i18n="fundingSeeOptions">VER FORMAS DE APOYAR</a></article>
    </div>
    <a href="#contribuir" class="oh-download-support-strip"><svg class="oh-icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#oh-ico-heart"></use></svg><span data-i18n="downloadSupportCta">Descargas gratuitas, desarrollo impulsado por la comunidad. APOYA EL PROYECTO</span><b aria-hidden="true">↗</b></a>
    <p class="oh-oss-beta" data-i18n="ossRCNote">ONE HUB Community v1.0.0 es gratuito y de código abierto. Los aportes son siempre voluntarios.</p>
  </div>
</section>

<!-- ONE HUB Community · Aportes voluntarios · ES/PT/EN -->
<section class="oh-section oh-funding-section" id="contribuir" aria-labelledby="funding-section-title">
  <div class="oh-container">
    <div class="oh-section-head centered reveal">
      <div class="oh-support-seal" aria-label="Open Source · Community Supported"><svg class="oh-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#oh-ico-heart"></use></svg><span>OPEN SOURCE<br><strong>COMMUNITY<br>SUPPORTED</strong></span></div>
      <span class="oh-kicker" data-i18n="fundingKicker">APOYA EL SOFTWARE LIBRE</span>
      <h2 id="funding-section-title" data-i18n="fundingTitle">Ayúdanos a seguir creando.<br><span>Tecnología abierta para todos.</span></h2>
      <p data-i18n="fundingIntro">Tu contribución voluntaria impulsa el mantenimiento, la seguridad, la documentación y nuevas funciones de ONE HUB Community. El software seguirá siendo gratuito para todos.</p>
    </div>
    <div class="oh-funding-grid">
      <article class="oh-funding-card reveal">
        <div class="oh-funding-platform-head"><span class="oh-funding-platform-icon" aria-hidden="true">GH</span><div><strong>GITHUB SPONSORS</strong><small data-i18n="fundingGitHubTag">APOYO A CÓDIGO ABIERTO</small></div></div>
        <p data-i18n="fundingGitHubDesc">Patrocina el trabajo de Sócrates Creative Studio desde GitHub. Elige tu contribución y ayuda a sostener nuestros proyectos Open Source.</p>
        <a class="oh-btn oh-btn-primary oh-funding-action" href="https://github.com/sponsors/socrates-one-web" target="_blank" rel="noopener noreferrer" data-i18n="fundingGitHubCta">PATROCINAR EN GITHUB</a>
      </article>
      <article class="oh-funding-card reveal">
        <div class="oh-funding-platform-head"><span class="oh-funding-platform-icon oh-funding-icon-stripe" aria-hidden="true">S</span><div><strong>STRIPE</strong><small data-i18n="fundingStripeTag">APOYO INTERNACIONAL</small></div></div>
        <p data-i18n="fundingStripeDesc">Realiza un aporte voluntario a través de nuestra página de Stripe y contribuye al desarrollo de herramientas gratuitas.</p>
        <a class="oh-btn oh-btn-light oh-funding-action" href="https://donate.stripe.com/4gMfZj4Z1aFPaZQa4Cdwc00" target="_blank" rel="noopener noreferrer" data-i18n="fundingStripeCta">APORTAR CON STRIPE</a>
      </article>
      <article class="oh-funding-card reveal">
        <div class="oh-funding-platform-head"><span class="oh-funding-platform-icon oh-funding-icon-mercado" aria-hidden="true">MP</span><div><strong>MERCADO PAGO</strong><small data-i18n="fundingMercadoTag">APOYOS DESDE BRASIL</small></div></div>
        <p data-i18n="fundingMercadoDesc">Colabora desde Brasil mediante nuestro enlace de Mercado Pago y ayuda a mantener el proyecto en evolución.</p>
        <a class="oh-btn oh-btn-light oh-funding-action" href="https://link.mercadopago.com.br/socratesone" target="_blank" rel="noopener noreferrer" data-i18n="fundingMercadoCta">APORTAR CON MERCADO PAGO</a>
      </article>
    </div>
    <p class="oh-funding-note" data-i18n="fundingNote">Aportes 100 % voluntarios. No se desbloquean funciones ni se exige pago para descargar o utilizar ONE HUB Community.</p>
  </div>
</section>

<section class="oh-section oh-assistant-section" id="asistente">
        <div class="oh-container">
            <div class="oh-assistant-grid">
                <div class="oh-assistant-copy reveal">
                    <span class="oh-kicker" data-i18n="assistantKicker">Ayuda integrada dentro de ONE HUB</span>
                    <h2 data-i18n="assistantTitle">Asistente del CRM.<br><span>Encuentra funciones sin perder tiempo.</span></h2>
                    <p data-i18n="assistantLead">Orienta a tus usuarios dentro del sistema, ayuda a encontrar funciones y acelera el trabajo diario con respuestas guiadas.</p>
                    <div class="oh-assistant-benefits">
                        <div><i>01</i><span><b data-i18n="assistantB1Title">Orientación dentro del sistema</b><small data-i18n="assistantB1Text">Indica dónde realizar una tarea o encontrar una función.</small></span></div>
                        <div><i>02</i><span><b data-i18n="assistantB2Title">Menos fricción para el usuario</b><small data-i18n="assistantB2Text">Reduce búsquedas, dudas repetitivas y tiempo de aprendizaje.</small></span></div>
                        <div><i>03</i><span><b data-i18n="assistantB3Title">Disponible mientras trabajas</b><small data-i18n="assistantB3Text">Se abre desde el propio ONE HUB sin abandonar la operación.</small></span></div>
                    </div>
                    <div class="oh-assistant-prompts" aria-label="Ejemplos de preguntas">
                        <button type="button" data-assistant-question="assistantA1" data-i18n="assistantQ1">¿Dónde creo una cotización?</button>
                        <button type="button" data-assistant-question="assistantA2" data-i18n="assistantQ2">¿Cómo registro un vencimiento?</button>
                        <button type="button" data-assistant-question="assistantA3" data-i18n="assistantQ3">¿Dónde veo mis cobros?</button>
                    </div>
                </div>

                <div class="oh-assistant-demo reveal reveal-delay" aria-label="Vista demostrativa del asistente">
                    <div class="oh-assistant-orbit orbit-one"></div>
                    <div class="oh-assistant-orbit orbit-two"></div>
                    <div class="oh-assistant-window">
                        <div class="oh-assistant-window-head">
                            <div class="oh-assistant-bot-icon">▣</div>
                            <div><strong data-i18n="assistantWindowTitle">Asistente del CRM</strong><span data-i18n="assistantWindowSub">Pregunta cómo usar o dónde encontrar una función</span></div>
                            <span class="oh-assistant-window-close" aria-hidden="true">×</span>
                        </div>
                        <div class="oh-assistant-window-body">
                            <div class="oh-assistant-message" data-i18n="assistantGreeting">Hola. Puedo orientarte dentro de ONE HUB. Por ejemplo: “¿Dónde creo una cotización?” o “¿Cómo registro un vencimiento?”.</div>
                            <div class="oh-assistant-demo-answer" data-i18n="assistantDemoHint">Selecciona una pregunta de ejemplo para ver cómo te orienta.</div>
                        </div>
                        <form class="oh-assistant-input-row" id="assistant-demo-form">
                            <input id="assistant-demo-input" type="text" data-i18n-placeholder="assistantPlaceholder" placeholder="Escribe tu pregunta..." autocomplete="off">
                            <button type="submit" aria-label="Enviar">➤</button>
                        </form>
                    </div>
                    <div class="oh-assistant-side-note"><span data-i18n="assistantSideNote">Tu guía dentro de ONE HUB.</span><b>↘</b></div>
                    <button class="oh-assistant-orb-button" type="button" aria-label="Asistente ONE HUB">▣</button>
                </div>
            </div>

            <div class="oh-quick-modules reveal">
                <div class="oh-quick-title"><strong data-i18n="quickModulesTitle">Módulos que impulsan tu negocio</strong><span data-i18n="quickModulesText">Todo conectado desde un mismo entorno.</span></div>
                <article><i class="green">●</i><div><b data-i18n="quick1Title">CRM & Ventas</b><span data-i18n="quick1Text">Clientes y oportunidades</span></div></article>
                <article><i class="blue">◆</i><div><b data-i18n="quick2Title">Project Manager</b><span data-i18n="quick2Text">Proyectos y equipos</span></div></article>
                <article><i class="orange">▤</i><div><b data-i18n="quick3Title">Cotizaciones</b><span data-i18n="quick3Text">Propuestas y contratos</span></div></article>
                <article><i class="pink">◉</i><div><b data-i18n="quick4Title">Helpdesk</b><span data-i18n="quick4Text">Tickets y SLA</span></div></article>
                <article><i class="purple">$</i><div><b data-i18n="quick5Title">Finanzas</b><span data-i18n="quick5Text">Cobros y vencimientos</span></div></article>
                <article><i class="cyan">▥</i><div><b data-i18n="quick6Title">Reportes</b><span data-i18n="quick6Text">Datos para decidir</span></div></article>
            </div>

            <div class="oh-language-strip reveal">
                <div class="oh-language-intro"><span class="oh-globe">◎</span><strong data-i18n="languageStripTitle">ONE HUB está disponible en 3 idiomas</strong></div>
                <div class="oh-language-items">
                    <span><img src="assets/img/flag-es.png" alt=""><b>Español</b><small>ES</small></span>
                    <span><img src="assets/img/flag-pt.png" alt=""><b>Português</b><small>PT-BR</small></span>
                    <span><img src="assets/img/flag-en.png" alt=""><b>English</b><small>EN</small></span>
                </div>
                <em data-i18n="languageStripNote">La misma plataforma, en el idioma de tu equipo.</em>
            </div>
        </div>
    </section>

    <section class="oh-proof-strip">
        <div class="oh-container oh-proof-grid">
            <div><strong data-i18n="proof1Title">Un solo dato</strong><span data-i18n="proof1Text">compartido entre ventas, proyectos y finanzas.</span></div>
            <div><strong data-i18n="proof2Title">Un solo flujo</strong><span data-i18n="proof2Text">desde el lead hasta la renovación.</span></div>
            <div><strong data-i18n="proof3Title">Una sola vista</strong><span data-i18n="proof3Text">para saber qué necesita atención hoy.</span></div>
        </div>
    </section>

    <section class="oh-section oh-flow-section" id="plataforma">
        <div class="oh-container">
            <div class="oh-section-head centered reveal">
                <span class="oh-kicker" data-i18n="flowKicker">Operación conectada</span>
                <h2 data-i18n="flowTitle">Menos herramientas sueltas.<br><span>Más control en un solo lugar.</span></h2>
                <p data-i18n="flowLead">Cada etapa alimenta la siguiente y mantiene el contexto de tu empresa.</p>
            </div>

            <div class="oh-flow reveal">
                <article><b>01</b><div class="oh-flow-icon">◎</div><h3 data-i18n="flow1Title">Captar</h3><p data-i18n="flow1Text">Leads, clientes y contactos.</p></article>
                <span class="oh-flow-arrow">→</span>
                <article><b>02</b><div class="oh-flow-icon">↗</div><h3 data-i18n="flow2Title">Vender</h3><p data-i18n="flow2Text">Negocios, propuestas y contratos.</p></article>
                <span class="oh-flow-arrow">→</span>
                <article><b>03</b><div class="oh-flow-icon">◇</div><h3 data-i18n="flow3Title">Ejecutar</h3><p data-i18n="flow3Text">Proyectos, tareas, horas y riesgos.</p></article>
                <span class="oh-flow-arrow">→</span>
                <article><b>04</b><div class="oh-flow-icon">$</div><h3 data-i18n="flow4Title">Cobrar</h3><p data-i18n="flow4Text">Ciclos, pagos y vencimientos.</p></article>
                <span class="oh-flow-arrow">→</span>
                <article><b>05</b><div class="oh-flow-icon">↻</div><h3 data-i18n="flow5Title">Renovar</h3><p data-i18n="flow5Text">Alertas, continuidad e historial.</p></article>
            </div>
        </div>
    </section>

    <section class="oh-section oh-screens-section">
        <div class="oh-container">
            <div class="oh-section-head reveal">
                <span class="oh-kicker" data-i18n="screensKicker">Pantallas reales del ecosistema</span>
                <h2 data-i18n="screensTitle">ONE HUB desde cada nivel de la operación.</h2>
                <p data-i18n="screensLead">Haz clic en cualquier pantalla para verla ampliada.</p>
            </div>

            <div class="oh-screen-grid">
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/dashboard.png">
                    <div class="oh-screen-media"><img src="assets/landing/dashboard.png" alt="Dashboard ejecutivo ONE HUB"></div>
                    <div class="oh-screen-copy"><span>01</span><div><h3 data-i18n="screen1Title">Dashboard ejecutivo</h3><p data-i18n="screen1Text">KPIs, vencimientos, negocios, cobros y alertas en una sola vista.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/crm-ventas.jpg">
                    <div class="oh-screen-media"><img src="assets/landing/crm-ventas.jpg" alt="CRM y ventas ONE HUB"></div>
                    <div class="oh-screen-copy"><span>02</span><div><h3 data-i18n="screen2Title">CRM y ventas</h3><p data-i18n="screen2Text">Clientes, contactos, actividades y navegación comercial conectada.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/cotizaciones.png">
                    <div class="oh-screen-media"><img src="assets/landing/cotizaciones.png" alt="Cotizaciones multimoneda ONE HUB"></div>
                    <div class="oh-screen-copy"><span>03</span><div><h3 data-i18n="screen3Title">Cotizaciones multimoneda</h3><p data-i18n="screen3Text">ES/PT/EN, CLP/USD/BRL, catálogo y seguimiento comercial.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/project-dashboard.png">
                    <div class="oh-screen-media"><img src="assets/landing/project-dashboard.png" alt="Dashboard de proyectos ONE HUB"></div>
                    <div class="oh-screen-copy"><span>04</span><div><h3 data-i18n="screen4Title">Dashboard de proyectos</h3><p data-i18n="screen4Text">Portafolio, estado de avance, tareas atrasadas y control operativo.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/project-calendar.png">
                    <div class="oh-screen-media"><img src="assets/landing/project-calendar.png" alt="Calendario de proyectos ONE HUB"></div>
                    <div class="oh-screen-copy"><span>05</span><div><h3 data-i18n="screen5Title">Calendario de proyectos</h3><p data-i18n="screen5Text">Fechas de entrega, tareas y coordinación del equipo en vista mensual.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/finanzas-cobros.jpg">
                    <div class="oh-screen-media"><img src="assets/landing/finanzas-cobros.jpg" alt="Finanzas y cobranzas ONE HUB"></div>
                    <div class="oh-screen-copy"><span>06</span><div><h3 data-i18n="screen6Title">Finanzas y cobranzas</h3><p data-i18n="screen6Text">Ingresos, cuentas por cobrar y seguimiento de pagos desde el CRM.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/project-risks.png">
                    <div class="oh-screen-media"><img src="assets/landing/project-risks.png" alt="Riesgos de proyectos ONE HUB"></div>
                    <div class="oh-screen-copy"><span>07</span><div><h3 data-i18n="screen7Title">Riesgos de proyectos</h3><p data-i18n="screen7Text">Registro, seguimiento y priorización de riesgos para cada iniciativa.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/document-manager.png">
                    <div class="oh-screen-media"><img src="assets/landing/document-manager.png" alt="Gestión documental ONE HUB"></div>
                    <div class="oh-screen-copy"><span>08</span><div><h3 data-i18n="screen8Title">Gestión documental</h3><p data-i18n="screen8Text">Biblioteca, versiones, metadatos y control documental integrado.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/reports-control.jpg">
                    <div class="oh-screen-media"><img src="assets/landing/reports-control.jpg" alt="Reportes y control ONE HUB"></div>
                    <div class="oh-screen-copy"><span>09</span><div><h3 data-i18n="screen9Title">Reportes y control</h3><p data-i18n="screen9Text">Indicadores comerciales, proyectos y finanzas en un solo centro de análisis.</p></div></div>
                </article>
                <article class="oh-screen-card reveal" data-lightbox="assets/landing/emailsend-overview.png">
                    <div class="oh-screen-media"><img src="assets/landing/emailsend-overview.png" alt="EMAILSEND en ONE HUB"></div>
                    <div class="oh-screen-copy"><span>10</span><div><h3 data-i18n="screen10Title">EMAILSEND</h3><p data-i18n="screen10Text">Listas, campañas, configuración técnica y métricas del módulo incluido.</p></div></div>
                </article>
            </div>
        </div>
    </section>

    <section class="oh-section oh-modules-section" id="modulos">
        <div class="oh-container">
            <div class="oh-section-head centered reveal">
                <span class="oh-kicker" data-i18n="modulesKicker">Un sistema. Múltiples áreas.</span>
                <h2 data-i18n="modulesTitle">Todo lo que tu operación necesita,<br><span>sin perder el hilo entre módulos.</span></h2>
            </div>

            <div class="oh-module-grid">
                <article class="oh-module reveal"><div class="oh-module-icon cyan">◎</div><h3 data-i18n="m1Title">CRM & Ventas</h3><p data-i18n="m1Text">Clientes, contactos, oportunidades, pipeline y actividades comerciales.</p><ul><li data-i18n="m1a">Búsqueda global</li><li data-i18n="m1b">Historial comercial</li><li data-i18n="m1c">Seguimiento de negocios</li></ul></article>
                <article class="oh-module reveal"><div class="oh-module-icon orange">▤</div><h3 data-i18n="m2Title">Cotizaciones & Contratos</h3><p data-i18n="m2Text">Propuestas profesionales, multimoneda y multidioma, con trazabilidad.</p><ul><li>ES · PT · EN</li><li>CLP · USD · BRL</li><li data-i18n="m2c">Aprobación y documentos</li></ul></article>
                <article class="oh-module reveal"><div class="oh-module-icon green">◇</div><h3 data-i18n="m3Title">Project Manager</h3><p data-i18n="m3Text">Kanban, Gantt, calendario, tareas, horas, hitos, riesgos y costos.</p><ul><li data-i18n="m3a">Equipos y responsables</li><li data-i18n="m3b">Horas y recursos</li><li data-i18n="m3c">Riesgos y gastos</li></ul></article>
                <article class="oh-module reveal"><div class="oh-module-icon blue">$</div><h3 data-i18n="m4Title">Finanzas & Cobros</h3><p data-i18n="m4Text">Pagos, vencimientos, ciclos de cobro e indicadores financieros.</p><ul><li data-i18n="m4a">Calendario de cobros</li><li data-i18n="m4b">Alertas de vencimiento</li><li data-i18n="m4c">Reportes por moneda</li></ul></article>
                <article class="oh-module reveal"><div class="oh-module-icon cyan">◫</div><h3 data-i18n="m5Title">Helpdesk & Soporte</h3><p data-i18n="m5Text">Tickets, prioridades, SLA, responsables, respuestas y línea de tiempo.</p><ul><li data-i18n="m5a">Flujo de atención</li><li data-i18n="m5b">Notas internas</li><li data-i18n="m5c">Seguimiento completo</li></ul></article>
                <article class="oh-module reveal"><div class="oh-module-icon orange">◈</div><h3 data-i18n="m6Title">Reportes & Control</h3><p data-i18n="m6Text">Indicadores operativos y comerciales para entender qué está pasando.</p><ul><li data-i18n="m6a">Dashboard configurable</li><li data-i18n="m6b">Gráficos por área</li><li data-i18n="m6c">Exportación de datos</li></ul></article>
                <article class="oh-module reveal"><div class="oh-module-icon green">✦</div><h3 data-i18n="m7Title">Asistente</h3><p data-i18n="m7Text">Un punto de apoyo dentro del sistema para consultar y acelerar tareas.</p><ul><li data-i18n="m7a">Contexto operativo</li><li data-i18n="m7b">Acceso centralizado</li><li data-i18n="m7c">Preparado para evolucionar</li></ul></article>
                <article class="oh-module reveal"><div class="oh-module-icon blue">⌁</div><h3 data-i18n="m8Title">Multiusuario & Permisos</h3><p data-i18n="m8Text">Cada persona ve y gestiona únicamente lo que le corresponde.</p><ul><li data-i18n="m8a">Roles granulares</li><li data-i18n="m8b">Permisos por función</li><li data-i18n="m8c">Registro de actividad</li></ul></article>
                <article class="oh-module oh-module-emailsend reveal" aria-labelledby="emailsend-module-title">
                    <div class="oh-module-icon orange" aria-hidden="true">✉</div>
                    <span class="oh-emailsend-mini-tag" data-i18n="emailModuleBadge">INCLUIDO EN ONE HUB</span>
                    <h3 id="emailsend-module-title" data-i18n="emailModuleTitle">EMAILSEND</h3>
                    <p data-i18n="emailModuleText">Crea campañas de correo, organiza tus contactos y analiza resultados desde ONE HUB.</p>
                    <ul>
                        <li data-i18n="emailModule1">Listas y segmentación de contactos</li>
                        <li data-i18n="emailModule2">Diseño y programación de campañas</li>
                        <li data-i18n="emailModule3">Aperturas, clics y reportes</li>
                    </ul>
                    <a href="#emailsend" class="oh-email-module-link" data-i18n="emailModuleLink">CONOCER EMAILSEND</a>
                </article>
            </div>
        </div>
    </section>

    <section class="oh-section oh-compare-section" id="comparar">
        <div class="oh-container">
            <div class="oh-section-head centered reveal">
                <span class="oh-kicker" data-i18n="compareKicker">Comparación clara</span>
                <h2 data-i18n="compareTitle">No pagas para ir desbloqueando el sistema.<br><span>ONE HUB se implementa completo.</span></h2>
                <p data-i18n="compareLead">El producto base se cotiza con un solo valor de implementación e incluye todos los módulos disponibles de ONE HUB. Si tu empresa necesita algo especial, podemos desarrollarlo y cotizarlo aparte.</p>
            </div>

            <div class="oh-market-cards reveal">
                <article class="oh-market-card featured">
                    <div class="oh-market-head"><span class="oh-market-brand">ONE HUB</span><em data-i18n="compareOurBadge">CÓDIGO ABIERTO</em></div>
                    <strong data-i18n="compareOurPrice">DESCARGA GRATUITA</strong>
                    <p data-i18n="compareOurText">Producto base completo + instalación + configuración acordada para tu empresa.</p>
                    <ul>
                        <li data-i18n="compareOur1">Todos los módulos actuales incluidos</li>
                        <li data-i18n="compareOur2">Instalado en el dominio del cliente</li>
                        <li data-i18n="compareOur3">Identidad y configuración personalizada</li>
                        <li data-i18n="compareOur4">Desarrollos especiales disponibles</li>
                    </ul>
                </article>
                <article class="oh-market-card">
                    <div class="oh-market-head"><span class="oh-market-brand">Bitrix24</span><em data-i18n="compareSubBadge">SUSCRIPCIÓN</em></div>
                    <strong data-i18n="compareBitrixPrice">POR PLAN</strong>
                    <p data-i18n="compareBitrixText">El precio y las funciones cambian según el plan y el límite de usuarios. La edición On-Premise se licencia aparte.</p>
                    <div class="oh-market-ref"><span data-i18n="compareBitrixRef">Referencia Brasil: Basic R$399/mes · Standard R$799 · Professional R$1.599</span></div>
                </article>
                <article class="oh-market-card">
                    <div class="oh-market-head"><span class="oh-market-brand">HubSpot</span><em data-i18n="compareSubBadge">SUSCRIPCIÓN</em></div>
                    <strong data-i18n="compareHubPrice">POR HUB + EDICIÓN + LICENCIA</strong>
                    <p data-i18n="compareHubText">Ventas, servicio y otras áreas se comercializan por productos y niveles, con precios por licencia en varios planes.</p>
                    <div class="oh-market-ref"><span data-i18n="compareHubRef">Referencia: Sales Hub Pro desde US$90/mes/licencia con pago anual</span></div>
                </article>
            </div>

            <div class="oh-compare-wrap reveal" role="region" aria-label="Comparación de ONE HUB con otras plataformas" tabindex="0">
                <table class="oh-compare-table">
                    <thead>
                        <tr><th data-i18n="compareFeature">Característica</th><th class="one">ONE HUB</th><th>Bitrix24</th><th>HubSpot</th></tr>
                    </thead>
                    <tbody>
                        <tr><th data-i18n="cmpPrice">Modelo de precio</th><td data-col="ONE HUB" class="one good" data-i18n="cmpPriceOne">Gratis · Código abierto</td><td data-col="Bitrix24" data-i18n="cmpPriceBitrix">Suscripción mensual/anual por plan</td><td data-col="HubSpot" data-i18n="cmpPriceHub">Suscripción por Hub, edición y licencia</td></tr>
                        <tr><th data-i18n="cmpModules">Módulos del producto base</th><td data-col="ONE HUB" class="one good" data-i18n="cmpModulesOne">Todos incluidos</td><td data-col="Bitrix24" data-i18n="cmpModulesBitrix">Se amplían según plan</td><td data-col="HubSpot" data-i18n="cmpModulesHub">Se distribuyen por Hubs y ediciones</td></tr>
                        <tr><th data-i18n="cmpCRM">CRM y pipeline</th><td data-col="ONE HUB" class="one good" data-i18n="cmpIncluded">Incluido</td><td data-col="Bitrix24" class="good" data-i18n="cmpIncluded">Incluido</td><td data-col="HubSpot" class="good" data-i18n="cmpIncluded">Incluido</td></tr>
                        <tr><th data-i18n="cmpQuotes">Cotizaciones y contratos</th><td data-col="ONE HUB" class="one good" data-i18n="cmpIncluded">Incluido</td><td data-col="Bitrix24" data-i18n="cmpByPlan">Según plan</td><td data-col="HubSpot" data-i18n="cmpByProduct">Según producto/edición</td></tr>
                        <tr><th data-i18n="cmpProjects">Gestión de proyectos</th><td data-col="ONE HUB" class="one good" data-i18n="cmpProjectOne">Kanban, Gantt, calendario, horas, riesgos y costos</td><td data-col="Bitrix24" class="good" data-i18n="cmpProjectBitrix">Incluida, con alcance según plan</td><td data-col="HubSpot" data-i18n="cmpProjectHub">No es un módulo PM integral equivalente</td></tr>
                        <tr><th data-i18n="cmpHelpdesk">Helpdesk y SLA</th><td data-col="ONE HUB" class="one good" data-i18n="cmpIncluded">Incluido</td><td data-col="Bitrix24" data-i18n="cmpByPlan">Según plan</td><td data-col="HubSpot" data-i18n="cmpServiceHub">Service Hub</td></tr>
                        <tr><th data-i18n="cmpBilling">Cobros, vencimientos y pagos</th><td data-col="ONE HUB" class="one good" data-i18n="cmpIncluded">Incluido</td><td data-col="Bitrix24" data-i18n="cmpByPlan">Según plan</td><td data-col="HubSpot" data-i18n="cmpCommerce">Según producto, plan y región</td></tr>
                        <tr><th data-i18n="cmpReports">Dashboard y reportes</th><td data-col="ONE HUB" class="one good" data-i18n="cmpIncluded">Incluido</td><td data-col="Bitrix24" data-i18n="cmpByPlan">Según plan</td><td data-col="HubSpot" data-i18n="cmpByPlan">Según plan</td></tr>
                        <tr><th data-i18n="cmpDomain">Instalación en dominio/servidor del cliente</th><td data-col="ONE HUB" class="one good" data-i18n="cmpYes">Sí</td><td data-col="Bitrix24" data-i18n="cmpBitrixDomain">Cloud: no · On-Premise: edición aparte</td><td data-col="HubSpot" data-i18n="cmpCloudOnly">No, plataforma cloud</td></tr>
                        <tr><th data-i18n="cmpBrand">Identidad visual de la empresa</th><td data-col="ONE HUB" class="one good" data-i18n="cmpBrandOne">Personalizada en la implementación</td><td data-col="Bitrix24" data-i18n="cmpBrandOthers">Opciones según edición/plan</td><td data-col="HubSpot" data-i18n="cmpBrandOthers">Opciones según edición/plan</td></tr>
                        <tr><th data-i18n="cmpCustom">Función especial para tu empresa</th><td data-col="ONE HUB" class="one good" data-i18n="cmpCustomOne">Sí, desarrollo directo a medida</td><td data-col="Bitrix24" data-i18n="cmpCustomOthers">Apps, API y partners</td><td data-col="HubSpot" data-i18n="cmpCustomOthers">Apps, API y partners</td></tr>
                        <tr><th data-i18n="cmpInfra">Control del entorno de alojamiento</th><td data-col="ONE HUB" class="one good" data-i18n="cmpInfraOne">En el hosting/servidor acordado con el cliente</td><td data-col="Bitrix24" data-i18n="cmpInfraBitrix">Cloud o edición On-Premise separada</td><td data-col="HubSpot" data-i18n="cmpInfraHub">Infraestructura cloud de HubSpot</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="oh-compare-note reveal">
                <div><strong data-i18n="compareBottomTitle">La diferencia no es solo el software.</strong><span data-i18n="compareBottomText">ONE HUB se instala, configura y adapta para que tu empresa empiece con una plataforma completa, no con una lista de módulos por desbloquear.</span></div>
                <a href="#descargas" class="oh-btn oh-btn-primary oh-btn-lg" data-i18n="compareCta">Descargar ONE HUB</a>
            </div>
            <p class="oh-compare-sources" data-i18n="compareSources">Referencias públicas consultadas el 08/09/2026. Los precios y condiciones de terceros pueden cambiar.</p>
            <div class="oh-compare-source-links">
                <a href="https://www.bitrix24.com.br/prices/" target="_blank" rel="noopener noreferrer">Bitrix24 Cloud</a>
                <a href="https://www.bitrix24.com.br/prices/self-hosted_buy.php" target="_blank" rel="noopener noreferrer">Bitrix24 On-Premise</a>
                <a href="https://www.hubspot.es/pricing/sales" target="_blank" rel="noopener noreferrer">HubSpot Sales Hub</a>
                <a href="https://www.hubspot.es/pricing/service" target="_blank" rel="noopener noreferrer">HubSpot Service Hub</a>
            </div>
        </div>
    </section>

    <section class="oh-section oh-focus-section">
        <div class="oh-container oh-focus-grid">
            <div class="oh-focus-copy reveal">
                <span class="oh-kicker" data-i18n="focusKicker">Información que se encuentra</span>
                <h2 data-i18n="focusTitle">Busca una vez.<br><span>Encuentra el contexto completo.</span></h2>
                <p data-i18n="focusLead">ONE HUB reduce la fricción de trabajar con datos repartidos. Clientes, contactos, documentos, servicios y negocios permanecen conectados.</p>
                <div class="oh-focus-list">
                    <div><i>✓</i><span data-i18n="focus1">Busca clientes por nombre, empresa, email, documento, WhatsApp, ciudad y más.</span></div>
                    <div><i>✓</i><span data-i18n="focus2">Relaciona cotizaciones con clientes y negocios.</span></div>
                    <div><i>✓</i><span data-i18n="focus3">Convierte oportunidades en servicios y proyectos sin perder historial.</span></div>
                    <div><i>✓</i><span data-i18n="focus4">Mantén vencimientos y renovaciones visibles antes de que se transformen en urgencias.</span></div>
                </div>
            </div>
            <div class="oh-focus-visual reveal">
                <div class="oh-search-demo">
                    <div class="oh-search-head"><span data-i18n="searchDemoTitle">Búsqueda inteligente de cliente</span><kbd>⌘ K</kbd></div>
                    <div class="oh-search-input"><span>⌕</span><strong data-i18n="searchDemoQuery">Nombre, RUT, email, WhatsApp, ciudad...</strong></div>
                    <div class="oh-result active"><div class="oh-avatar">A</div><div><b>ACME DIGITAL</b><span>contacto@acme.com · +56 9 5555 0000</span></div><em data-i18n="searchDemoClientLabel">Cliente</em></div>
                    <div class="oh-result"><div class="oh-avatar">N</div><div><b>NORTE INDUSTRIAL</b><span>76.123.456-7 · Santiago · Chile</span></div><em data-i18n="searchDemoClientLabel">Cliente</em></div>
                    <div class="oh-result"><div class="oh-avatar">B</div><div><b>BIOPARK LABS</b><span>compras@biopark.com.br · Toledo · Brasil</span></div><em data-i18n="searchDemoClientLabel">Cliente</em></div>
                </div>
            </div>
        </div>
    </section>

    <section class="oh-section oh-install-section" id="implementacion">
        <div class="oh-container">
            <div class="oh-section-head centered reveal">
                <span class="oh-kicker" data-i18n="installKicker">Implementado para tu empresa</span>
                <h2 data-i18n="installTitle">No es otro SaaS genérico.<br><span>Lo instalamos y configuramos contigo.</span></h2>
                <p data-i18n="installLead">ONE HUB se entrega como una implementación preparada para tu organización, con acompañamiento de Sócrates ONE desde el diagnóstico hasta la puesta en marcha.</p>
            </div>
            <div class="oh-install-grid">
                <article class="oh-install-card reveal"><b>01</b><div class="oh-module-icon blue">◎</div><h3 data-i18n="install1Title">Diagnóstico</h3><p data-i18n="install1Text">Revisamos cómo gestionas hoy clientes, ventas, proyectos, soporte y cobros.</p></article>
                <article class="oh-install-card reveal"><b>02</b><div class="oh-module-icon orange">◇</div><h3 data-i18n="install2Title">Instalación</h3><p data-i18n="install2Text">Preparamos ONE HUB en el entorno acordado para tu empresa y dejamos la plataforma operativa.</p></article>
                <article class="oh-install-card reveal"><b>03</b><div class="oh-module-icon green">↗</div><h3 data-i18n="install3Title">Configuración</h3><p data-i18n="install3Text">Ajustamos identidad, usuarios, permisos, datos iniciales, monedas, correo y flujos clave.</p></article>
                <article class="oh-install-card reveal"><b>04</b><div class="oh-module-icon cyan">✓</div><h3 data-i18n="install4Title">Puesta en marcha</h3><p data-i18n="install4Text">Te acompañamos en el inicio de uso, capacitación y ajustes necesarios para comenzar con orden.</p></article>
            </div>
            <div class="oh-install-cta reveal"><div><strong data-i18n="installCtaTitle">¿Quieres ver cómo quedaría ONE HUB en tu empresa?</strong><span data-i18n="installCtaText">Agenda una demostración y revisamos tu caso antes de cotizar la implementación.</span></div><a href="#descargas" class="oh-btn oh-btn-primary oh-btn-lg" data-i18n="installCtaButton">Solicitar demostración</a></div>
        </div>
    </section>

    <section class="oh-section oh-security-section" id="seguridad">
        <div class="oh-container oh-security-card reveal">
            <div class="oh-security-copy">
                <span class="oh-kicker light" data-i18n="securityKicker">Seguridad incorporada</span>
                <h2 data-i18n="securityTitle">Tu operación necesita velocidad.<br><span>También necesita control.</span></h2>
                <p data-i18n="securityLead">ONE HUB incorpora controles de acceso y trazabilidad para que la información empresarial permanezca protegida y auditable.</p>
                <a href="#descargas" class="oh-btn oh-btn-white" data-i18n="securityCta">Conocer ONE HUB</a>
            </div>
            <div class="oh-security-grid">
                <article><i>01</i><b data-i18n="sec1Title">2FA</b><span data-i18n="sec1Text">Verificación adicional de acceso.</span></article>
                <article><i>02</i><b data-i18n="sec2Title">Roles</b><span data-i18n="sec2Text">Permisos por usuario y función.</span></article>
                <article><i>03</i><b data-i18n="sec3Title">Auditoría</b><span data-i18n="sec3Text">Registro de actividad y eventos.</span></article>
                <article><i>04</i><b data-i18n="sec4Title">Sesiones</b><span data-i18n="sec4Text">Controles de expiración y bloqueo.</span></article>
                <article><i>05</i><b data-i18n="sec5Title">Recuperación</b><span data-i18n="sec5Text">Restablecimiento seguro de contraseña.</span></article>
                <article><i>06</i><b data-i18n="sec6Title">HTTPS</b><span data-i18n="sec6Text">Preparado para operación cifrada.</span></article>
            </div>
        </div>
    </section>

    <section class="oh-section oh-faq-section" id="faq">
        <div class="oh-container">
            <div class="oh-section-head centered reveal">
                <span class="oh-kicker">FAQ</span>
                <h2 data-i18n="faqTitle">Preguntas frecuentes.</h2>
            </div>
            <div class="oh-faq-grid reveal">
                <details open><summary data-i18n="faq1q">¿ONE HUB reemplaza un CRM tradicional?</summary><p data-i18n="faq1a">Va más allá de un CRM: conecta clientes y ventas con proyectos, documentos, soporte, cobros, renovaciones y control operativo.</p></details>
                <details><summary data-i18n="faq2q">¿Puedo usarlo con varios usuarios?</summary><p data-i18n="faq2a">Sí. ONE HUB permite usuarios, roles y permisos para definir qué puede ver y hacer cada persona.</p></details>
                <details><summary data-i18n="faq3q">¿Trabaja con distintas monedas e idiomas?</summary><p data-i18n="faq3a">Sí. Las cotizaciones están preparadas para ES/PT/EN y monedas como CLP, USD y BRL.</p></details>
                <details><summary data-i18n="faq4q">¿Puedo gestionar proyectos dentro del mismo sistema?</summary><p data-i18n="faq4a">Sí. Incluye Kanban, Gantt, calendario, tareas, horas, hitos, riesgos, archivos, costos y responsables.</p></details>
                <details><summary data-i18n="faq5q">¿Incluye seguimiento de cobros y vencimientos?</summary><p data-i18n="faq5a">Sí. Permite registrar ciclos de cobro, pagos, vencimientos, alertas y visualizar indicadores en el dashboard.</p></details>
                <details><summary data-i18n="faq6q">¿La interfaz funciona en celular?</summary><p data-i18n="faq6a">Sí. La plataforma está diseñada para escritorio y dispositivos móviles, manteniendo accesos y vistas adaptadas.</p></details>
                <details><summary data-i18n="faq7q">¿ONE HUB se contrata y se instala para cada empresa?</summary><p data-i18n="faq7a">Sí. Sócrates ONE realiza la implementación y configuración de ONE HUB según el entorno y necesidades acordadas con cada organización.</p></details>
                <details><summary data-i18n="faq8q">¿Necesito instalarlo yo mismo?</summary><p data-i18n="faq8a">No. Nuestro equipo se encarga de la instalación, configuración inicial y puesta en marcha. Tu empresa recibe el sistema listo para comenzar a operar.</p></details>
                <details><summary data-i18n="faq9q">¿El precio de ONE HUB incluye todos los módulos?</summary><p data-i18n="faq9a">Sí. El producto base se cotiza como una implementación completa e incluye los módulos disponibles de ONE HUB. No necesitas subir de plan para desbloquear áreas del sistema.</p></details>
                <details><summary data-i18n="faq10q">¿Pueden desarrollar una función especial para mi empresa?</summary><p data-i18n="faq10a">Sí. Si necesitas una integración, flujo o función que no forme parte del producto base, podemos evaluarla y cotizar ese desarrollo adicional por separado.</p></details>
            </div>
        </div>
    </section>

    <section class="oh-section oh-contact-section" id="contacto">
        <div class="oh-container oh-contact-card reveal">
            <div class="oh-contact-copy">
                <span class="oh-kicker" data-i18n="contactKicker">Implementa ONE HUB en tu empresa</span>
                <h2 data-i18n="contactTitle">¿Tu empresa necesita dejar de trabajar con información dispersa?</h2>
                <p data-i18n="contactLead">Cuéntanos cómo trabaja tu empresa hoy. Te mostramos ONE HUB, revisamos tu flujo y preparamos una propuesta de implementación.</p>
                <div class="oh-contact-points">
                    <div><i>✓</i><span data-i18n="contactPoint1">Demostración guiada</span></div>
                    <div><i>✓</i><span data-i18n="contactPoint2">Revisión de tu flujo actual</span></div>
                    <div><i>✓</i><span data-i18n="contactPoint3">Propuesta de implementación</span></div>
                </div>
                <a href="mailto:info@socrates.la" class="oh-contact-email">info@socrates.la</a>
            </div>

            <div class="oh-contact-form oh-community-contact" aria-label="Recursos de la comunidad">
  <div class="oh-community-contact-head"><span class="oh-kicker" data-i18n="ossContactBoxKicker">ACCESO GRATUITO</span><h3 data-i18n="ossContactBoxTitle">Elige cómo empezar con ONE HUB</h3><p data-i18n="ossContactBoxText">Descarga la versión sin datos o prueba la versión con contenido ficticio. Ambas utilizan el mismo código abierto.</p></div>
  <a class="oh-btn oh-btn-primary oh-btn-lg oh-oss-full" href="descargar.php?edition=clean" data-gated-download="clean" data-i18n="ossCleanBtn">DESCARGAR CLEAN</a>
  <a class="oh-btn oh-btn-light oh-btn-lg oh-oss-full" href="descargar.php?edition=demo" data-gated-download="demo" data-i18n="ossDemoBtn">DESCARGAR DEMO</a>
  <a class="oh-btn oh-btn-light oh-btn-lg oh-oss-full" href="manual.php" data-i18n="ossManualBtn">LEER MANUAL DE INSTALACIÓN</a>
  <p class="oh-oss-muted" data-i18n="ossHelpText">El software es gratuito. Si deseas una instalación administrada o desarrollo personalizado, puedes contactar al desarrollador.</p>
  <a href="mailto:info@socrates.la?subject=ONE%20HUB%20Community" class="oh-contact-email">info@socrates.la</a>
</div>
        </div>
    </section>
</main>

<footer class="oh-footer">
    <div class="oh-container oh-footer-top">
        <div class="oh-footer-product">
            <img src="assets/img/one-hub-logo.png" alt="ONE HUB">
            <p data-i18n="footerText">ONE HUB Community · OPEN SOURCE. CRM, proyectos, documentos, finanzas y EMAILSEND sin pagos de licencia.</p>
        </div>
        <div class="oh-footer-links">
            <strong data-i18n="footerProduct">Producto</strong>
            <a href="#plataforma" data-i18n="navPlatform">Plataforma</a>
            <a href="#modulos" data-i18n="navModules">Módulos</a>
            <a href="#emailsend" data-i18n="emailFooterLink">EMAILSEND</a>
            <a href="#asistente" data-i18n="navAssistant">Asistente</a>
            <a href="#comparar" data-i18n="navCompare">Comparar</a>
            <a href="#seguridad" data-i18n="navSecurity">Seguridad</a>
        </div>
        <div class="oh-footer-links">
            <strong data-i18n="footerCommercial">Comunidad Open Source</strong>
            <a href="#descargas" data-i18n="requestDemo">Descargar gratis</a>
            <a class="oh-footer-support-action" href="#contribuir"><svg class="oh-icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#oh-ico-heart"></use></svg><span data-i18n="fundingFooterLink">APOYAR EL PROYECTO</span></a>
            <a href="#descargas" data-i18n="navImplementation">Implementación</a>
            <a href="mailto:info@socrates.la">info@socrates.la</a>
        </div>
        <div class="oh-footer-support-mini"><div class="oh-footer-support-icon" aria-hidden="true"><svg class="oh-icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#oh-ico-heart"></use></svg></div><strong data-i18n="footerSupportHeadline">TECNOLOGÍA LIBRE, APOYO VOLUNTARIO</strong><p data-i18n="footerSupportText">Ayuda a mantener ONE HUB gratuito para todos.</p><a href="https://github.com/sponsors/socrates-one-web" target="_blank" rel="noopener noreferrer" data-i18n="footerSupportButton">PATROCINAR EN GITHUB ↗</a></div>
        <div class="oh-footer-developer">
            <span data-i18n="developedBy">Desarrollado por</span>
            <img src="assets/img/logo-creative-blanco.png" alt="Sócrates AI Creative Studio">
        </div>
    </div>
    <div class="oh-container oh-footer-bottom">
        <span>© 2026 ONE HUB · Business Operating System</span>
        <div class="oh-legal-links"><a href="privacidad.php" data-i18n="legalPrivacy">Privacidad</a><a href="cookies.php" data-i18n="legalCookies">Cookies</a><a href="terminos.php" data-i18n="legalTerms">Términos</a></div>
        <div class="oh-footer-langs">
            <button data-lang="es" class="oh-lang active" aria-label="Español"><img src="assets/img/flag-es.png" alt=""></button>
            <button data-lang="pt" class="oh-lang" aria-label="Português"><img src="assets/img/flag-pt.png" alt=""></button>
            <button data-lang="en" class="oh-lang" aria-label="English"><img src="assets/img/flag-en.png" alt=""></button>
        </div>
    </div>
</footer>

<button class="oh-assistant-float-trigger" id="assistant-float-trigger" type="button" aria-label="Asistente ONE HUB">▣</button>
<div class="oh-assistant-float-panel" id="assistant-float-panel" aria-hidden="true">
    <div class="oh-assistant-float-head">
        <div class="oh-assistant-bot-icon">▣</div>
        <div><b data-i18n="assistantWindowTitle">Asistente del CRM</b><span data-i18n="assistantWindowSub">Pregunta cómo usar o dónde encontrar una función</span></div>
        <button type="button" id="assistant-float-close" aria-label="Cerrar">×</button>
    </div>
    <div class="oh-assistant-float-body">
        <p data-i18n="assistantFloatText">En ONE HUB el asistente acompaña al usuario mientras trabaja y le indica dónde encontrar las funciones que necesita.</p>
        <div class="oh-assistant-float-actions">
            <button type="button" data-assistant-question="assistantA1" data-i18n="assistantQ1">¿Dónde creo una cotización?</button>
            <button type="button" data-assistant-question="assistantA2" data-i18n="assistantQ2">¿Cómo registro un vencimiento?</button>
        </div>
    </div>
</div>

<div class="oh-lightbox" hidden aria-hidden="true">
    <button class="oh-lightbox-close" type="button" aria-label="Cerrar">×</button>
    <img src="" alt="Vista ampliada">
</div>

<div class="oh-cookie" id="oh-cookie" hidden><div><strong data-i18n="cookieTitle">Tu privacidad importa</strong><span data-i18n="cookieText">Usamos únicamente cookies técnicas y de preferencia necesarias para el funcionamiento del sitio.</span></div><div class="oh-cookie-actions"><a href="cookies.php" data-i18n="cookieMore">Ver política</a><button type="button" id="cookie-accept" class="oh-btn oh-btn-primary" data-i18n="cookieAccept">Aceptar</button></div></div>
<script src="assets/js/landing.js?v=1.7.0-community-admin" defer></script>
</body>
</html>
