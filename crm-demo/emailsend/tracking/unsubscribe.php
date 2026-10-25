<?php
require dirname(__DIR__).'/includes/bootstrap.php';
$t=$_GET['t']??'';$ok=false;$email='';
if(preg_match('/^[a-f0-9]{48}$/',$t)){
 try{$st=db()->prepare('SELECT d.id,d.contact_id,d.email FROM deliveries d WHERE d.token=? LIMIT 1');$st->execute([$t]);$d=$st->fetch();if($d){db()->beginTransaction();db()->prepare('UPDATE contacts SET status="unsubscribed" WHERE id=?')->execute([$d['contact_id']]);db()->prepare('UPDATE deliveries SET status="unsubscribed" WHERE id=?')->execute([$d['id']]);db()->commit();$ok=true;$email=$d['email'];}}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();}
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Baja</title><style>body{font-family:Arial;background:#f4f7fa;color:#17324a;display:grid;place-items:center;min-height:100vh;margin:0}.box{background:#fff;padding:30px;border-radius:10px;max-width:520px;border:1px solid #dce5ec}h1{color:#002f57}</style></head><body><div class="box"><h1>EMAILSEND ONE</h1><?php if($ok):?><p><strong><?=e($email)?></strong> fue eliminado de futuros envíos.</p><?php else:?><p>No pudimos procesar esta solicitud.</p><?php endif?></div></body></html>
