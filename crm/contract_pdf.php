<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/pdf_documents.php';
require_permission('contracts.view');
$id=(int)($_GET['id']??0);
$stmt=db()->prepare('SELECT ct.*,c.company_name,c.tax_id,c.country,c.city,c.address FROM contracts ct JOIN clients c ON c.id=ct.client_id WHERE ct.id=?');
$stmt->execute([$id]);$contract=$stmt->fetch();
if(!$contract){http_response_code(404);exit('Contrato no encontrado.');}
$pdf=contract_pdf_bytes($contract);
$name=preg_replace('/[^A-Za-z0-9._-]+/','-',(string)$contract['contract_number']).'.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$name.'"');
header('Content-Length: '.strlen($pdf));
echo $pdf;exit;
