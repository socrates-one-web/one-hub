<?php
declare(strict_types=1);
$lang = strtolower((string)($_GET['lang'] ?? 'es'));
if (!in_array($lang, ['es','pt','en'], true)) $lang = 'es';
$labels = [
    'es' => ['back'=>'Volver a ONE HUB','updated'=>'Actualizado: octubre de 2026'],
    'pt' => ['back'=>'Voltar ao ONE HUB','updated'=>'Atualizado: outubro de 2026'],
    'en' => ['back'=>'Back to ONE HUB','updated'=>'Updated: October 2026'],
];
$current = $content[$lang] ?? $content['es'];
?><!doctype html>
<html lang="<?= $lang === 'pt' ? 'pt-BR' : $lang ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#002f57"><title><?=htmlspecialchars($current['title'])?> · ONE HUB</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700;800;900&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/landing.css?v=1.2.2-os">
</head>
<body class="oh-legal-page">
<header class="oh-legal-header"><div class="oh-container"><a href="index.php"><img src="assets/img/one-hub-logo.png" alt="ONE HUB"></a><div class="oh-legal-langs"><a href="?lang=es">ES</a> · <a href="?lang=pt">PT</a> · <a href="?lang=en">EN</a></div><a class="oh-legal-back" href="index.php">← <?=htmlspecialchars($labels[$lang]['back'])?></a></div></header>
<main class="oh-legal-main"><div class="oh-container"><article class="oh-legal-card"><h1><?=htmlspecialchars($current['title'])?></h1><div class="oh-legal-meta"><?=htmlspecialchars($labels[$lang]['updated'])?></div><?=$current['html']?></article></div></main>
</body></html>
