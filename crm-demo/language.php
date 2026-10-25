<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
$lang=(string)($_GET['lang']??'es');
if(!isset(crm_supported_languages()[$lang]))$lang='es';
$_SESSION['crm_language']=$lang;
if(!empty($_SESSION['user_id'])){
    try{db()->prepare('UPDATE users SET language=? WHERE id=?')->execute([$lang,(int)$_SESSION['user_id']]);}catch(Throwable){}
}
$back=(string)($_SERVER['HTTP_REFERER']??(!empty($_SESSION['user_id'])?'dashboard.php':'login.php'));
$base=app_url('');
if(!str_starts_with($back,$base))$back=!empty($_SESSION['user_id'])?'dashboard.php':'login.php';
redirect($back);
