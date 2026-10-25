<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/auth.php';require_permission('quotes.view');
$id=(int)($_GET['id']??0);$stmt=db()->prepare('SELECT a.*,q.client_id FROM quotation_attachments a JOIN quotations q ON q.id=a.quotation_id WHERE a.id=?');$stmt->execute([$id]);$file=$stmt->fetch();if(!$file){http_response_code(404);exit('Archivo no encontrado.');}
$relative=ltrim((string)$file['file_path'],'/');$root=realpath(__DIR__.'/uploads');$absolute=realpath(__DIR__.'/'.$relative);if(!$root||!$absolute||!str_starts_with($absolute,$root.DIRECTORY_SEPARATOR)||!is_file($absolute)){http_response_code(404);exit('Archivo no disponible.');}
crm_security_audit('quotation_attachment_downloaded','info','Documento descargado: '.(string)$file['file_name'],'quotation',(int)$file['quotation_id']);
header('Content-Type: '.(string)($file['mime_type']?:'application/octet-stream'));header('Content-Disposition: attachment; filename="'.addcslashes(basename((string)$file['file_name']),'"\\').'"');header('Content-Length: '.filesize($absolute));readfile($absolute);exit;
