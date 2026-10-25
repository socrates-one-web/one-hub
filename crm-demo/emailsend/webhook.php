<?php
require __DIR__.'/includes/bootstrap.php';
header('Content-Type: application/json');
$key=$_SERVER['HTTP_X_EMAILSEND_KEY']??($_GET['key']??'');if(!hash_equals(APP_KEY,$key)){http_response_code(403);echo json_encode(['ok'=>false]);exit;}
$data=json_decode(file_get_contents('php://input'),true)?:[];$token=$data['token']??'';$event=$data['event']??'';
if(!preg_match('/^[a-f0-9]{48}$/',$token)||!in_array($event,['delivered','bounced'],true)){http_response_code(422);echo json_encode(['ok'=>false]);exit;}
$st=db()->prepare('SELECT id,contact_id FROM deliveries WHERE token=?');$st->execute([$token]);$d=$st->fetch();if(!$d){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
if($event==='delivered')db()->prepare('UPDATE deliveries SET status="delivered",delivered_at=NOW() WHERE id=?')->execute([$d['id']]);
if($event==='bounced'){db()->beginTransaction();db()->prepare('UPDATE deliveries SET status="bounced",error_text=? WHERE id=?')->execute([substr($data['reason']??'Bounce',0,1000),$d['id']]);db()->prepare('UPDATE contacts SET status="bounced" WHERE id=?')->execute([$d['contact_id']]);db()->commit();}
echo json_encode(['ok'=>true]);
