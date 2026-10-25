<?php
require dirname(__DIR__).'/includes/bootstrap.php';
$t=$_GET['t']??'';$u=$_GET['u']??'';$s=$_GET['s']??'';
$expected=hash_hmac('sha256',$t.'|'.$u,APP_KEY);
if(!preg_match('/^[a-f0-9]{48}$/',$t)||!hash_equals($expected,$s)){http_response_code(400);exit('Enlace inválido');}
$url=base64_decode(strtr($u,'-_','+/'),true);if(!$url||!filter_var($url,FILTER_VALIDATE_URL)){http_response_code(400);exit('URL inválida');}
$scheme=parse_url($url,PHP_URL_SCHEME);if(!in_array($scheme,['http','https'],true)){http_response_code(400);exit('URL inválida');}
try{$st=db()->prepare('SELECT id FROM deliveries WHERE token=? LIMIT 1');$st->execute([$t]);$id=$st->fetchColumn();if($id){$ip=$_SERVER['REMOTE_ADDR']??'';$hash=$ip?hash('sha256',$ip.APP_KEY):null;$ua=substr($_SERVER['HTTP_USER_AGENT']??'',0,500);db()->prepare('UPDATE deliveries SET clicks=clicks+1,last_click_at=NOW() WHERE id=?')->execute([$id]);db()->prepare('INSERT INTO click_events(delivery_id,url,clicked_at,ip_hash,user_agent) VALUES(?,?,NOW(),?,?)')->execute([$id,$url,$hash,$ua]);}}catch(Throwable $e){}
header('Location: '.$url, true, 302);exit;
