<?php
declare(strict_types=1);
// ONE HUB Community — complete legal notice; authored HTML, no user HTML.
$content = [
  'es' => [
    'title' => "Política de cookies",
    'html' => <<<'ONEHUB_ES_LEGAL'
<section class="oh-policy-section" id="descripcion"><h2>Qué tecnologías usa el sitio</h2><p>La landing utiliza almacenamiento local del navegador para recordar idioma y preferencia sobre el aviso de cookies. Los formularios de descarga y el panel administrativo necesitan cookies de sesión PHP para proteger solicitudes y mantener la autenticación. Las preferencias no son imprescindibles para obtener el código directamente desde GitHub.</p></section>
<section class="oh-policy-section" id="tipos"><h2>Tecnologías esenciales y preferencias</h2><ul><li><strong>Sesiones técnicas PHP:</strong> protegen el formulario y la sesión del administrador; suelen expirar al cerrar el navegador o finalizar la sesión.</li><li><strong>Almacenamiento local:</strong> registra la preferencia de idioma y si aceptaste el aviso de cookies; permanece hasta que borres los datos del sitio.</li><li><strong>Registros del alojamiento:</strong> permiten operación y seguridad, y no son una cookie publicitaria.</li></ul></section>
<section class="oh-policy-section" id="terceros"><h2>Terceros y enlaces</h2><p>Para mostrar fuentes web, el navegador puede conectarse a Google Fonts. Al abrir GitHub, GitHub Sponsors, Stripe o Mercado Pago, pasarás a otras plataformas con cookies y avisos propios; el sitio ONE HUB no controla las cookies que estos servicios colocan bajo sus dominios.</p></section>
<section class="oh-policy-section" id="publicidad"><h2>Publicidad y cambios</h2><p>El código de la landing revisado no habilita por defecto cookies publicitarias ni píxeles de seguimiento comercial. Si en el futuro se añaden herramientas analíticas o de publicidad que lo requieran, esta política y los mecanismos de consentimiento deberán actualizarse antes de activarlas.</p></section>
<section class="oh-policy-section" id="control"><h2>Cómo gestionar tus preferencias</h2><p>Puedes eliminar cookies y almacenamiento local desde la configuración de tu navegador. Esto puede borrar el idioma elegido o cerrar la sesión de descarga y administración. Consulta además nuestra <a href="privacidad.php?lang=es">Política de Privacidad</a> y escribe a <a href="mailto:info@socrates.la">info@socrates.la</a> ante cualquier pregunta.</p></section>
ONEHUB_ES_LEGAL
  ],
  'pt' => [
    'title' => "Política de cookies",
    'html' => <<<'ONEHUB_PT_LEGAL'
<section class="oh-policy-section" id="descripcion"><h2>Tecnologias usadas neste site</h2><p>A landing usa armazenamento local para lembrar o idioma e a preferência sobre o aviso de cookies. O formulário de download e o painel administrativo utilizam cookies de sessão PHP para proteger solicitações e autenticação. Essas preferências não são necessárias para acessar o código diretamente no GitHub.</p></section>
<section class="oh-policy-section" id="tipos"><h2>Sessões essenciais e preferências</h2><ul><li><strong>Sessões técnicas PHP:</strong> protegem o cadastro e a administração; normalmente terminam ao encerrar o navegador ou a sessão.</li><li><strong>Armazenamento local:</strong> guarda o idioma e a escolha sobre o aviso de cookies até a limpeza dos dados pelo navegador.</li><li><strong>Logs da hospedagem:</strong> podem apoiar operação e segurança; não são cookies de publicidade.</li></ul></section>
<section class="oh-policy-section" id="terceros"><h2>Serviços externos</h2><p>O navegador poderá solicitar fontes no Google Fonts. Ao acessar GitHub, GitHub Sponsors, Stripe ou Mercado Pago, você visita sites que possuem cookies e políticas próprias. O ONE HUB não controla as tecnologias instaladas sob os domínios desses fornecedores.</p></section>
<section class="oh-policy-section" id="publicidad"><h2>Publicidade e alterações</h2><p>O código revisado da landing não instala, por padrão, cookies de publicidade nem pixels de rastreamento comercial. Caso sejam adicionadas ferramentas de análise ou publicidade que exijam consentimento, esta política e os mecanismos de escolha deverão ser atualizados antes da ativação.</p></section>
<section class="oh-policy-section" id="control"><h2>Como gerenciar os dados</h2><p>Cookies e armazenamento local podem ser removidos nas configurações do navegador, o que pode redefinir idiomas ou encerrar sessões. Veja a <a href="privacidad.php?lang=pt">Política de Privacidade</a> e entre em contato pelo <a href="mailto:info@socrates.la">info@socrates.la</a>.</p></section>
ONEHUB_PT_LEGAL
  ],
  'en' => [
    'title' => "Cookie Policy",
    'html' => <<<'ONEHUB_EN_LEGAL'
<section class="oh-policy-section" id="descripcion"><h2>Technologies used by this site</h2><p>The landing uses browser local storage to remember language and cookie-notice choices. Download forms and the admin panel use PHP session cookies to protect submissions and authentication. These preferences are not required to obtain the source code directly from GitHub.</p></section>
<section class="oh-policy-section" id="tipos"><h2>Essential sessions and preferences</h2><ul><li><strong>Technical PHP sessions:</strong> help secure registration and administration; they generally expire when the browser or session ends.</li><li><strong>Local storage:</strong> saves selected language and the cookie-notice choice until website data is cleared.</li><li><strong>Hosting logs:</strong> can support operations and security; they are not advertising cookies.</li></ul></section>
<section class="oh-policy-section" id="terceros"><h2>External services</h2><p>Your browser may request fonts from Google Fonts. Following links to GitHub, GitHub Sponsors, Stripe or Mercado Pago takes you to other websites with their own cookies and notices. ONE HUB does not control cookies set under those third-party domains.</p></section>
<section class="oh-policy-section" id="publicidad"><h2>Advertising and future changes</h2><p>The reviewed landing code does not enable advertising cookies or marketing pixels by default. If analytics or advertising tools requiring consent are introduced, this policy and relevant preference controls will need to be updated before they are activated.</p></section>
<section class="oh-policy-section" id="control"><h2>Managing your choices</h2><p>You can remove cookies and local storage through your browser settings. Doing so may reset preferences or sign you out. See our <a href="privacidad.php?lang=en">Privacy Policy</a> or contact <a href="mailto:info@socrates.la">info@socrates.la</a>.</p></section>
ONEHUB_EN_LEGAL
  ],
];
require __DIR__ . '/legal_template.php';
