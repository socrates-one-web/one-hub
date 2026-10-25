<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('services.delete');
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('services.php');verify_csrf();$id=(int)($_POST['id']??0);$stmt=db()->prepare('SELECT client_id,name FROM client_services WHERE id=?');$stmt->execute([$id]);$service=$stmt->fetch();if($service){db()->prepare('DELETE FROM client_services WHERE id=?')->execute([$id]);log_activity('Servicio eliminado',(int)$service['client_id'],null,$service['name']);flash('success','Servicio eliminado.');redirect('client_view.php?id='.$service['client_id']);}redirect('services.php');
