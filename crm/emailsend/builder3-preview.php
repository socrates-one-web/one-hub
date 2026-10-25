<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_login();
require_permission('campaigns','view');

if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Método no permitido');}
verify_csrf();
$blocks=json_decode((string)($_POST['blocks_json']??'[]'),true);
if(!is_array($blocks))$blocks=[];
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
echo compile_campaign_html($blocks);
