<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_permission('clients.delete');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('clients.php');
verify_csrf();
$id=(int)($_POST['id']??0);
$stmt=db()->prepare('SELECT company_name,logo_path FROM clients WHERE id=?');$stmt->execute([$id]);$client=$stmt->fetch();
if($client){db()->prepare('DELETE FROM clients WHERE id=?')->execute([$id]);if($client['logo_path']&&str_starts_with($client['logo_path'],'uploads/')){@unlink(__DIR__.'/'.$client['logo_path']);}log_activity('Cliente eliminado',null,null,$client['company_name']);flash('success','Cliente eliminado.');}
redirect('clients.php');
