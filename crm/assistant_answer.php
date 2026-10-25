<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('assistant.use');
require_once __DIR__.'/includes/assistant_engine.php';
header('Content-Type: application/json; charset=UTF-8');
try{
    verify_csrf();
    $question=trim((string)($_POST['question']??''));
    $context=['page'=>(string)($_POST['page']??''),'title'=>(string)($_POST['title']??''),'url'=>(string)($_POST['url']??'')];
    $result=assistant_handle_question($question,$context);
    $result['ok']=true;$result['context_page']=basename($context['page']);
    echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
}catch(Throwable $e){
    $code=crm_log_exception($e);http_response_code(500);
    echo json_encode(['ok'=>false,'answer'=>'No pude completar esta consulta. Código de diagnóstico: <strong>'.e($code).'</strong>.','actions'=>[],'suggestions'=>[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
