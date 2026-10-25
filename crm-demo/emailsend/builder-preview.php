<?php
declare(strict_types=1);

require __DIR__.'/includes/bootstrap.php';
require_login();

if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    exit('Método no permitido');
}

try{
    verify_csrf();
}catch(Throwable $e){
    http_response_code(403);
    exit('Sesión expirada');
}

$raw=(string)($_POST['blocks_json']??'[]');
$blocks=json_decode($raw,true);
if(!is_array($blocks)) $blocks=[];

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
echo compile_campaign_html($blocks);
