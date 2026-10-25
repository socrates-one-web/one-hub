<?php
require dirname(__DIR__).'/includes/bootstrap.php';
$token=$_GET['t']??'';
if(preg_match('/^[a-f0-9]{48}$/',$token)){
 try{$st=db()->prepare('SELECT id FROM deliveries WHERE token=? LIMIT 1');$st->execute([$token]);$id=$st->fetchColumn();if($id){$st=db()->prepare('UPDATE deliveries SET opens=opens+1,first_open_at=COALESCE(first_open_at,NOW()),last_open_at=NOW() WHERE id=?');$st->execute([$id]);}}catch(Throwable $e){}
}
header('Content-Type:image/gif');header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
