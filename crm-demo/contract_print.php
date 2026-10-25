<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('contracts.view');
$id=(int)($_GET['id']??0);
$stmt=db()->prepare('SELECT ct.*,c.company_name,c.tax_id,c.country,c.city,c.address,c.email client_email,c.contact_name client_contact FROM contracts ct JOIN clients c ON c.id=ct.client_id WHERE ct.id=?');
$stmt->execute([$id]);$contract=$stmt->fetch();
if(!$contract){http_response_code(404);exit('Contrato no encontrado.');}
$provider=(string)setting('contract_company_name',crm_company_name());
?><!doctype html>
<html lang="<?=e((string)$contract['language'])?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($contract['contract_number'])?> · <?=e($contract['title'])?></title>
<style>
*{box-sizing:border-box}body{margin:0;background:#eaf1f5;color:#172f43;font-family:Arial,sans-serif;line-height:1.62}
.toolbar{position:sticky;top:0;z-index:5;display:flex;justify-content:center;gap:8px;padding:12px;background:#002f57}
.toolbar button,.toolbar a{border:0;border-radius:8px;padding:11px 16px;font-weight:700;text-decoration:none;cursor:pointer}.toolbar button{background:#ff5300;color:#fff}.toolbar a{background:#fff;color:#002f57}
.page{width:min(210mm,calc(100% - 24px));min-height:297mm;margin:22px auto;background:#fff;padding:22mm 20mm;box-shadow:0 18px 50px #002f5720}
.brand{font-size:11px;color:#60788a;text-transform:uppercase;letter-spacing:1px}.page h1{font-size:24px;color:#002f57;border-bottom:3px solid #ff5300;padding-bottom:13px;margin:7px 0 8px}.meta{display:flex;justify-content:space-between;gap:20px;color:#60788a;font-size:11px;margin-bottom:25px}.copy{white-space:pre-wrap;font-size:12.5px}.foot{margin-top:35px;padding-top:12px;border-top:1px solid #d8e3ea;color:#718595;font-size:10px}
@media print{body{background:#fff}.toolbar{display:none}.page{width:auto;min-height:0;margin:0;padding:12mm 14mm;box-shadow:none}@page{size:A4;margin:0}}
</style></head>
<body><div class="toolbar"><button onclick="window.print()">IMPRIMIR / GUARDAR PDF</button><a href="contract_view.php?id=<?=$id?>">VOLVER AL CONTRATO</a></div>
<article class="page"><div class="brand"><?=e($provider)?></div><h1><?=e($contract['title'])?></h1>
<div class="meta"><span><?=e($contract['contract_number'])?> · <?=e(format_date($contract['issue_date']))?></span><span><?=e($contract['company_name'])?></span></div>
<div class="copy"><?=e($contract['content'])?></div>
<div class="foot">Documento generado por SÓCRATES CRM · <?=e(date('d/m/Y H:i'))?></div></article></body></html>
