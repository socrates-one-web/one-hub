<?php
/**
 * ONE HUB Community — solicitud de descarga y acceso a GitHub Releases.
 * No envía correos ni pide consentimiento publicitario. Requiere PHP 8.1+.
 * Los contactos se guardan en _private_downloads/lead_requests.csv, protegido por Apache.
 */
declare(strict_types=1);

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000');
}
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'self'");

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (PHP_SESSION_NONE === session_status()) {
    session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'secure'=>$https, 'httponly'=>true, 'samesite'=>'Lax']);
    session_start();
}
function ohEscape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function ohLang(mixed $value): string {
    return in_array($value, ['es', 'pt', 'en'], true) ? (string)$value : 'es';
}
function ohEdition(mixed $value): string {
    return in_array($value, ['clean', 'demo', 'completo'], true) ? (string)$value : 'clean';
}
$lang = ohLang($_POST['lang'] ?? $_GET['lang'] ?? 'es');
$edition = ohEdition($_POST['edition'] ?? $_GET['edition'] ?? 'clean');
$T = [
'es' => [
    'lang'=>'Español','page'=>'Descargas oficiales','eyebrow'=>'ONE HUB COMMUNITY · OPEN SOURCE','title'=>'DESCARGA ONE HUB COMMUNITY','subtitle'=>'Antes de acceder a los instaladores oficiales, indícanos tu nombre y correo electrónico. ONE HUB es gratuito.','name'=>'Nombre completo','email'=>'Correo electrónico','choose'=>'Edición que deseas descargar','confirm'=>'He leído la política de privacidad y entiendo que mis datos se registran exclusivamente para gestionar esta solicitud de descarga.','privacy'=>'Política de privacidad','submit'=>'CONTINUAR A DESCARGAS','source'=>'Ver código fuente público en GitHub','back'=>'VOLVER A ONE HUB','notice'=>'No utilizaremos tu correo para publicidad ni boletines sin solicitarte un consentimiento independiente.','thank'=>'¡DESCARGA HABILITADA!','thanktext'=>'Gracias por registrarte. Selecciona una edición; los archivos se descargan directamente desde la Release estable de GitHub.','download'=>'DESCARGAR DESDE GITHUB','clean'=>'CLEAN · Instalación limpia','demo'=>'DEMO · Datos de ejemplo','completo'=>'COMPLETO · Sitio y ambas ediciones','secure'=>'Distribución oficial · v1.0.0 Stable · GNU AGPL-3.0-only','invalid'=>'Revisa el nombre, correo electrónico y la confirmación de privacidad.','retry'=>'No fue posible guardar la solicitud de forma segura. Comprueba que la carpeta privada tenga permisos de escritura e inténtalo de nuevo.','wait'=>'Espera unos segundos antes de volver a enviar el formulario.','token'=>'Tu sesión expiró. Recarga la página e inténtalo de nuevo.','private'=>'Los datos se guardan en un registro privado del servidor; puedes solicitar su acceso o eliminación escribiendo a info@socrates.la.','noads'=>'Descargar es gratis. Las donaciones son opcionales.'],
'pt' => [
    'lang'=>'Português','page'=>'Downloads oficiais','eyebrow'=>'ONE HUB COMMUNITY · CÓDIGO ABERTO','title'=>'BAIXE O ONE HUB COMMUNITY','subtitle'=>'Antes de acessar os instaladores oficiais, informe seu nome e e-mail. O ONE HUB é gratuito.','name'=>'Nome completo','email'=>'E-mail','choose'=>'Edição que deseja baixar','confirm'=>'Li a política de privacidade e entendo que meus dados serão registrados exclusivamente para gerenciar esta solicitação de download.','privacy'=>'Política de privacidade','submit'=>'CONTINUAR PARA DOWNLOADS','source'=>'Ver código-fonte público no GitHub','back'=>'VOLTAR AO ONE HUB','notice'=>'Não usaremos seu e-mail para publicidade ou newsletters sem um consentimento separado.','thank'=>'DOWNLOAD LIBERADO!','thanktext'=>'Obrigado pelo cadastro. Selecione uma edição; os arquivos são baixados diretamente da versão estável no GitHub.','download'=>'BAIXAR PELO GITHUB','clean'=>'CLEAN · Instalação limpa','demo'=>'DEMO · Dados de exemplo','completo'=>'COMPLETO · Site e ambas as edições','secure'=>'Distribuição oficial · v1.0.0 estável · GNU AGPL-3.0-only','invalid'=>'Confira o nome, e-mail e a confirmação de privacidade.','retry'=>'Não foi possível registrar a solicitação com segurança. Verifique a permissão de gravação da pasta privada e tente novamente.','wait'=>'Aguarde alguns segundos antes de enviar novamente.','token'=>'Sua sessão expirou. Atualize a página e tente novamente.','private'=>'Os dados ficam em um registro privado no servidor. Solicite acesso ou exclusão em info@socrates.la.','noads'=>'O download é gratuito. Contribuições são opcionais.'],
'en' => [
    'lang'=>'English','page'=>'Official downloads','eyebrow'=>'ONE HUB COMMUNITY · OPEN SOURCE','title'=>'DOWNLOAD ONE HUB COMMUNITY','subtitle'=>'Before accessing the official installers, please provide your name and email address. ONE HUB is free.','name'=>'Full name','email'=>'Email address','choose'=>'Edition to download','confirm'=>'I have read the privacy policy and understand my details will be recorded solely to manage this download request.','privacy'=>'Privacy policy','submit'=>'CONTINUE TO DOWNLOADS','source'=>'View public source code on GitHub','back'=>'BACK TO ONE HUB','notice'=>'We will not use your email for advertising or newsletters without separate consent.','thank'=>'DOWNLOADS UNLOCKED!','thanktext'=>'Thank you for registering. Select an edition; files download directly from the stable GitHub Release.','download'=>'DOWNLOAD FROM GITHUB','clean'=>'CLEAN · Fresh installation','demo'=>'DEMO · Sample data','completo'=>'COMPLETE · Website and both editions','secure'=>'Official distribution · v1.0.0 Stable · GNU AGPL-3.0-only','invalid'=>'Check your name, email and privacy acknowledgment.','retry'=>'We could not securely save your request. Verify write permission for the private folder and try again.','wait'=>'Please wait a few seconds before trying again.','token'=>'Your session expired. Refresh the page and try again.','private'=>'Details are stored in a private server log. Request access or deletion via info@socrates.la.','noads'=>'Downloads are free. Contributions are optional.'],
][$lang];
$downloads = [
    'clean'=>'https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0/ONE_HUB_COMMUNITY_CLEAN_v1.0.0.zip',
    'demo'=>'https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0/ONE_HUB_COMMUNITY_DEMO_v1.0.0.zip',
    'completo'=>'https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0/ONE_HUB_COMMUNITY_COMPLETO_v1.0.0.zip',
];
if (empty($_SESSION['onehub_download_csrf'])) $_SESSION['onehub_download_csrf'] = bin2hex(random_bytes(24));
if (empty($_SESSION['onehub_form_loaded'])) $_SESSION['onehub_form_loaded'] = time();
$error='';
$name='';
$email='';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name=trim((string)($_POST['full_name'] ?? ''));
    $email=trim((string)($_POST['email'] ?? ''));
    $token=(string)($_POST['csrf'] ?? '');
    $hidden=trim((string)($_POST['website'] ?? ''));
    $nameLen = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : preg_match_all('/./us', $name);
    $nameValid=is_int($nameLen) && $nameLen>=2 && $nameLen<=100 && preg_match('/[\x00-\x1F\x7F]/u',$name)===0;
    $emailValid=strlen($email)<=254 && filter_var($email,FILTER_VALIDATE_EMAIL)!==false;
    if (!hash_equals((string)$_SESSION['onehub_download_csrf'],$token)) {
        $error=$T['token'];
    } elseif ($hidden!=='' || time()-(int)$_SESSION['onehub_form_loaded']<2 || (int)($_SESSION['onehub_last_submission']??0)>time()-4) {
        $error=$T['wait'];
    } elseif (!$nameValid || !$emailValid || ($_POST['privacy_ack']??'')!=='yes') {
        $error=$T['invalid'];
    } else {
        // The log directory is under Apache deny-all rules. Deployers MUST verify HTTP 403.
        // No IP address, password, card information, or tracking cookies are stored.
        $privateDir=__DIR__.'/_private_downloads';
        $csv=$privateDir.'/lead_requests.csv';
        $ok=is_dir($privateDir) && is_file($privateDir.'/.htaccess') && is_writable($privateDir);
        if ($ok) {
            $fp=@fopen($csv,'ab');
            if ($fp!==false) {
                if (flock($fp,LOCK_EX)) {
                    $stat=fstat($fp);
                    if ((int)($stat['size']??0)===0) {
                        $ok=fputcsv($fp,['created_utc','full_name','email','edition','lang'])!==false;
                    }
                    // Add an apostrophe to spreadsheet formula prefixes to prevent CSV injection.
                    $safeName=preg_match('/^[=+\-@]/u',$name)?"'".$name:$name;
                    if ($ok) $ok=fputcsv($fp,[gmdate('Y-m-d\TH:i:s\Z'),$safeName,$email,$edition,$lang])!==false;
                    fflush($fp);
                    flock($fp,LOCK_UN);
                } else $ok=false;
                fclose($fp);
                @chmod($csv,0600);
            } else $ok=false;
        }
        if ($ok) {
            $_SESSION['onehub_last_submission']=time();
            $_SESSION['onehub_download_unlocked_until']=time()+3600;
            $_SESSION['onehub_download_csrf']=bin2hex(random_bytes(24));
            header('Location: descargar.php?ready=1&lang='.$lang.'&edition='.$edition,true,303);
            exit;
        }
        $error=$T['retry'];
    }
}
$unlocked=(int)($_SESSION['onehub_download_unlocked_until']??0)>time();
?>
<!doctype html><html lang="<?=ohEscape($lang==='pt'?'pt-BR':$lang)?>"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,follow"><meta name="theme-color" content="#002f57">
<title><?=ohEscape($T['page'])?> · ONE HUB Community</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800;900&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="assets/css/landing.css?v=1.6.2-downloads" rel="stylesheet">
</head><body class="oh-landing oh-download-page">
<header class="oh-download-header"><div class="oh-container oh-download-header-inner">
<a href="index.php" class="oh-download-brand"><img src="assets/img/one-hub-logo.png" alt="ONE HUB"></a>
<nav class="oh-download-languages" aria-label="Language">
<?php foreach(['es'=>'ES','pt'=>'PT','en'=>'EN'] as $code=>$short):?><a href="descargar.php?lang=<?=$code?>&edition=<?=ohEscape($edition)?>" <?= $code===$lang?'aria-current="page"':'' ?>><?=ohEscape($short)?></a><?php endforeach; ?>
</nav></div></header>
<main class="oh-download-main"><div class="oh-container">
<div class="oh-download-panel"><span class="oh-kicker"><?=ohEscape($T['eyebrow'])?></span>
<h1><?=ohEscape($unlocked?$T['thank']:$T['title'])?></h1>
<p class="oh-download-subtitle"><?=ohEscape($unlocked?$T['thanktext']:$T['subtitle'])?></p>
<?php if ($unlocked): ?>
<div class="oh-download-choices">
<?php foreach(['clean','demo','completo'] as $key): ?><article class="oh-download-option <?= $key===$edition?'oh-download-option-chosen':'' ?>">
<h2><?=ohEscape($T[$key])?></h2><a class="oh-btn oh-btn-primary" href="<?=ohEscape($downloads[$key])?>" rel="noopener noreferrer" target="_blank"><?=ohEscape($T['download'])?></a>
</article><?php endforeach; ?>
</div>
<?php else: ?>
<?php if ($error!==''): ?><p class="oh-download-error" role="alert"><?=ohEscape($error)?></p><?php endif; ?>
<form method="post" action="descargar.php" class="oh-download-form" autocomplete="on">
<input type="hidden" name="csrf" value="<?=ohEscape((string)$_SESSION['onehub_download_csrf'])?>">
<input type="hidden" name="lang" value="<?=ohEscape($lang)?>">
<div class="oh-download-field oh-download-honeypot" aria-hidden="true"><label for="oh-website">Website</label><input type="text" name="website" id="oh-website" tabindex="-1" autocomplete="off"></div>
<label class="oh-download-field"><span><?=ohEscape($T['name'])?></span><input name="full_name" type="text" maxlength="100" value="<?=ohEscape($name)?>" autocomplete="name" required></label>
<label class="oh-download-field"><span><?=ohEscape($T['email'])?></span><input name="email" type="email" maxlength="254" value="<?=ohEscape($email)?>" autocomplete="email" required></label>
<label class="oh-download-field"><span><?=ohEscape($T['choose'])?></span><select name="edition" required><?php foreach(['clean','demo','completo'] as $key): ?><option value="<?=$key?>" <?=$key===$edition?'selected':''?>><?=ohEscape($T[$key])?></option><?php endforeach; ?></select></label>
<label class="oh-download-consent"><input name="privacy_ack" type="checkbox" value="yes" required><span><?=ohEscape($T['confirm'])?> <a href="privacidad.php?lang=<?=ohEscape($lang)?>" target="_blank" rel="noopener noreferrer"><?=ohEscape($T['privacy'])?></a>.</span></label>
<button type="submit" class="oh-btn oh-btn-primary oh-download-submit"><?=ohEscape($T['submit'])?></button>
</form>
<p class="oh-download-small"><?=ohEscape($T['notice'])?></p>
<?php endif; ?>
<div class="oh-download-footer"><p><?=ohEscape($T['noads'])?></p><a href="https://github.com/socrates-one-web/one-hub" rel="noopener noreferrer" target="_blank"><?=ohEscape($T['source'])?></a><a href="index.php"><?=ohEscape($T['back'])?></a></div>
</div></div></main>
<footer class="oh-download-copyright">© 2026 Sócrates Creative Studio · ONE HUB Community · <?=ohEscape($T['secure'])?></footer>
</body></html>
