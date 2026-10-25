<?php
declare(strict_types=1);
$lang = strtolower((string)($_GET['lang'] ?? 'es'));
if (!in_array($lang, ['es', 'pt', 'en'], true)) $lang = 'es';
$labels = [
  'es'=>['back'=>'VOLVER A ONE HUB','updated'=>'Última revisión: 25 de octubre de 2026','contents'=>'ÍNDICE DE CONTENIDOS','print'=>'IMPRIMIR','privacy'=>'PRIVACIDAD','terms'=>'TÉRMINOS DE USO','cookies'=>'COOKIES','contact'=>'CONTACTO','note'=>'Este documento se aplica al sitio ONE HUB Community. Los derechos de código abierto se rigen por la licencia AGPL-3.0-only.'],
  'pt'=>['back'=>'VOLTAR AO ONE HUB','updated'=>'Última atualização: 25 de outubro de 2026','contents'=>'ÍNDICE','print'=>'IMPRIMIR','privacy'=>'PRIVACIDADE','terms'=>'TERMOS DE USO','cookies'=>'COOKIES','contact'=>'CONTATO','note'=>'Este documento se aplica ao site ONE HUB Community. Os direitos de código aberto são regidos pela licença AGPL-3.0-only.'],
  'en'=>['back'=>'BACK TO ONE HUB','updated'=>'Last updated: 25 October 2026','contents'=>'CONTENTS','print'=>'PRINT','privacy'=>'PRIVACY','terms'=>'TERMS','cookies'=>'COOKIES','contact'=>'CONTACT','note'=>'This document applies to the ONE HUB Community website. Open-source code rights are governed by the AGPL-3.0-only license.'],
];
$current = $content[$lang] ?? $content['es'];
// Our fixed legal text is authored HTML only; never render untrusted user HTML here.
$outline = [];
if (preg_match_all('~<section class="oh-policy-section" id="([a-z0-9-]+)"><h2>([^<]+)</h2>~u', $current['html'], $matches, PREG_SET_ORDER)) {
  foreach ($matches as $match) $outline[] = ['id'=>$match[1], 'title'=>html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')];
}
function legalEsc(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?><!doctype html>
<html lang="<?= $lang === 'pt' ? 'pt-BR' : $lang ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#003865"><meta name="robots" content="index,follow">
<title><?=legalEsc($current['title'])?> · ONE HUB Community</title>
<meta name="description" content="<?=legalEsc($current['title'].' — ONE HUB Community · Sócrates Creative Studio')?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700;800;900&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/landing.css?v=1.7.1-hero-support-badge">
<style>
  body.oh-legal-page{background:#f5f9fc;color:#163b58;font-family:'Ubuntu Sans',sans-serif;margin:0}
  .oh-legal-header{background:#fff;border-bottom:1px solid #d9e8f1;position:sticky;top:0;z-index:20;box-shadow:0 3px 15px rgba(0,47,87,.04)}
  .oh-legal-header .oh-container{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:13px 0;max-width:1220px}
  .oh-legal-header img{max-width:154px;height:auto}
  .oh-legal-toplinks{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
  .oh-legal-header a{color:#003865;font-weight:750;text-decoration:none}
  .oh-legal-back{font-size:12px;white-space:nowrap}
  .oh-legal-langs{display:flex;align-items:center;gap:3px}
  .oh-legal-langs a{padding:6px 9px;border:1px solid #dae8f1;border-radius:8px;font-size:12px}
  .oh-legal-langs a[aria-current="page"]{background:#003865;color:#fff}
  .oh-legal-main{padding:45px 0 72px}
  .oh-legal-main>.oh-container{max-width:1220px;display:grid;grid-template-columns:minmax(0,245px) minmax(0,1fr);gap:26px;align-items:start}
  .oh-legal-nav{position:sticky;top:100px;background:#fff;border-radius:17px;border:1px solid #dfecf4;padding:20px 16px;box-shadow:0 10px 34px rgba(0,47,87,.05);max-height:calc(100vh - 118px);overflow:auto}
  .oh-legal-nav h2{font:800 13px/1.4 'Exo 2',sans-serif;color:#003865;margin:0 0 12px;text-transform:uppercase}
  .oh-legal-nav a{display:block;padding:6px 4px;font:600 12px/1.45 'Ubuntu Sans',sans-serif;color:#486982;text-decoration:none;border-radius:5px}
  .oh-legal-nav a:hover,.oh-legal-nav a:focus-visible{color:#ff5300;background:#f8fbff}
  .oh-legal-card{background:#fff;border:1px solid #e0ecf3;border-radius:20px;padding:clamp(24px,4vw,55px);box-shadow:0 15px 45px rgba(0,47,87,.06);min-width:0}
  .oh-legal-card h1{font:800 clamp(27px,3vw,40px)/1.2 'Exo 2',sans-serif;letter-spacing:0;color:#003865;margin:0 0 10px}
  .oh-legal-meta{color:#627e93;font-size:13px;margin-bottom:10px}
  .oh-legal-note{padding:13px 16px;border-left:4px solid #ed8b00;background:#fff8ea;border-radius:9px;margin:15px 0 25px;color:#31526b;font-size:13px;line-height:1.55}
  .oh-policy-section{scroll-margin-top:100px;padding:19px 0 18px;border-bottom:1px solid #e3eef4}
  .oh-policy-section:last-child{border-bottom:0}
  .oh-legal-card .oh-policy-section h2{font:800 clamp(17px,2vw,22px)/1.35 'Exo 2',sans-serif;color:#003865;letter-spacing:0;margin:0 0 11px}
  .oh-policy-section p,.oh-policy-section li{font-size:15px;line-height:1.8;color:#344f63}
  .oh-policy-section p{margin:0 0 14px}
  .oh-policy-section ul{padding-left:23px;margin:5px 0 15px;list-style:disc}
  .oh-policy-section li{margin:7px 0}
  .oh-policy-section a{color:#006ca9;text-decoration:underline;text-underline-offset:2px;overflow-wrap:anywhere}
  .oh-policy-section strong{color:#183b57}
  .oh-legal-footer{border-top:1px solid #e2edf4;margin-top:24px;padding-top:18px;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;color:#577388;font-size:13px}
  .oh-legal-footer a{color:#006ca9;text-decoration:underline}
  @media(max-width:900px){.oh-legal-main>.oh-container{grid-template-columns:1fr}.oh-legal-nav{position:static;max-height:none;display:flex;align-items:center;gap:6px;flex-wrap:wrap}.oh-legal-nav h2{width:100%;margin:0 0 7px}.oh-legal-nav a{font-size:11px;padding:6px 10px;background:#f4f9fc}}
  @media(max-width:650px){.oh-legal-header .oh-container{padding:12px 0;flex-wrap:wrap}.oh-legal-header img{max-width:130px}.oh-legal-back{display:none}.oh-legal-main{padding:20px 0 44px}.oh-legal-card{padding:24px 19px}.oh-legal-nav{padding:13px}.oh-policy-section p,.oh-policy-section li{font-size:14px}}
  @media print{.oh-legal-header,.oh-legal-nav,.oh-legal-actions{display:none!important}.oh-legal-main>.oh-container{display:block}.oh-legal-card{border:0;box-shadow:none;padding:0}.oh-legal-main{padding:0}.oh-policy-section{break-inside:avoid}.oh-legal-card h1{font-size:24pt}}
</style>
</head>
<body class="oh-legal-page">
<header class="oh-legal-header"><div class="oh-container">
  <a href="index.php" aria-label="ONE HUB"><img src="assets/img/one-hub-logo.png" alt="ONE HUB"></a>
  <div class="oh-legal-toplinks"><div class="oh-legal-langs" aria-label="Languages">
  <?php foreach (['es','pt','en'] as $code): ?><a href="?lang=<?=$code?>" <?= $code===$lang?'aria-current="page"':'' ?>><?=strtoupper($code)?></a><?php endforeach; ?>
  </div><a class="oh-legal-back" href="index.php">← <?=legalEsc($labels[$lang]['back'])?></a></div>
</div></header>
<main class="oh-legal-main"><div class="oh-container">
<aside class="oh-legal-nav" aria-label="<?=legalEsc($labels[$lang]['contents'])?>"><h2><?=legalEsc($labels[$lang]['contents'])?></h2>
<?php foreach ($outline as $item): ?><a href="#<?=legalEsc($item['id'])?>"><?=legalEsc($item['title'])?></a><?php endforeach; ?>
</aside>
<article class="oh-legal-card"><h1><?=legalEsc($current['title'])?></h1><div class="oh-legal-meta"><?=legalEsc($labels[$lang]['updated'])?></div>
<div class="oh-legal-note"><?=legalEsc($labels[$lang]['note'])?></div>
<?=$current['html']?>
<div class="oh-legal-footer">
 <div><a href="privacidad.php?lang=<?=$lang?>"><?=legalEsc($labels[$lang]['privacy'])?></a> · <a href="terminos.php?lang=<?=$lang?>"><?=legalEsc($labels[$lang]['terms'])?></a> · <a href="cookies.php?lang=<?=$lang?>"><?=legalEsc($labels[$lang]['cookies'])?></a></div>
 <div><a href="mailto:info@socrates.la"><?=legalEsc($labels[$lang]['contact'])?>: info@socrates.la</a></div>
</div></article>
</div></main></body></html>
