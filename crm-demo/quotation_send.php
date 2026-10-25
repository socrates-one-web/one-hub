<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/pdf_documents.php';
require_permission('quotes.send');

$id=(int)($_GET['id']??$_POST['id']??0);
$stmt=db()->prepare('SELECT q.*,c.company_name,c.trade_name,c.tax_id,c.country,c.city,c.address,c.logo_path,c.email client_email FROM quotations q JOIN clients c ON c.id=q.client_id WHERE q.id=?');
$stmt->execute([$id]);
$quote=$stmt->fetch();
if(!$quote){http_response_code(404);exit('Cotización no encontrada.');}
$stmt=db()->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order,id');
$stmt->execute([$id]);
$items=$stmt->fetchAll();
$stmt=db()->prepare('SELECT * FROM quotation_attachments WHERE quotation_id=? ORDER BY id');
$stmt->execute([$id]);
$additionalAttachments=$stmt->fetchAll();
$pageTitle='Enviar '.$quote['quotation_number'];
$errors=[];
$labels=quote_labels((string)$quote['language']);

function quotation_email_summary(array $quote,array $items,array $labels): string
{
    $rows='';
    foreach($items as$item){
        $description=trim((string)$item['description']);
        $firstLine=preg_split('/\R/u',$description)[0]??$description;
        $rows.='<tr><td style="padding:12px;border-bottom:1px solid #dbe7ee;line-height:1.45">'.e(crm_strimwidth($firstLine,110)).'</td>'
            .'<td style="padding:12px;border-bottom:1px solid #dbe7ee;text-align:center">'.e(number_format((float)$item['quantity'],2,',','.')).'</td>'
            .'<td style="padding:12px;border-bottom:1px solid #dbe7ee;text-align:right;font-weight:bold;white-space:nowrap">'.e(format_money($item['line_total'],(string)$quote['currency'])).'</td></tr>';
    }
    return '<div style="max-width:760px;margin:18px auto;background:#fff;border:1px solid #d8e3ea;border-radius:16px;overflow:hidden">'
        .'<div style="height:6px;background:#ff5300"></div>'
        .'<div style="padding:24px 26px 12px"><h2 style="margin:0;color:#002f57">'.e($quote['quotation_number']).'</h2><p style="margin:7px 0;color:#657b8c">'.e($quote['company_name']).' · '.e((string)$quote['currency']).'</p></div>'
        .'<div style="padding:0 26px 24px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #d8e3ea">'
        .'<thead><tr style="background:#003e69;color:#fff"><th style="padding:11px;text-align:left">'.e($labels['description']).'</th><th style="padding:11px">'.e($labels['quantity']).'</th><th style="padding:11px;text-align:right">'.e($labels['total']).'</th></tr></thead><tbody>'.$rows.'</tbody></table>'
        .'<div style="margin-top:16px;background:#003e69;color:#fff;border-radius:12px;padding:16px 18px;text-align:right"><span style="font-size:12px;color:#cfe7f8">'.e($labels['grand_total']).'</span><strong style="display:block;font-size:24px;color:#ff9a61;margin-top:4px">'.e(format_money($quote['total'],(string)$quote['currency'])).'</strong></div>'
        .'<p style="color:#657b8c;font-size:12px;line-height:1.5;margin:15px 0 0">'.e($labels['attachment_note']??'PDF adjunto.').'</p></div></div>';
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $to=strtolower(trim((string)($_POST['to']??'')));
    $subject=trim((string)($_POST['subject']??''));
    $message=trim((string)($_POST['message']??''));
    if(!filter_var($to,FILTER_VALIDATE_EMAIL))$errors[]='El email de destino no es válido.';
    if($subject==='')$errors[]='El asunto es obligatorio.';
    if(!$errors){
        try{$pdfBytes=quote_pdf_bytes($quote,$items);}catch(Throwable$exception){$errors[]='No fue posible generar el PDF adjunto: '.$exception->getMessage();$pdfBytes='';}
        if(!$errors){
            $greeting=e($labels['greeting'].($quote['recipient_name']?', '.$quote['recipient_name']:''));
            $intro=nl2br(e($message!==''?$message:$labels['intro']));
            $html='<!doctype html><html><body style="margin:0;padding:24px;background:#edf3f7;font-family:Arial,sans-serif;color:#183247">'
                .'<div style="max-width:760px;margin:0 auto;background:#fff;padding:22px 26px;border-radius:14px;border:1px solid #d8e3ea"><p style="margin-top:0"><strong>'.$greeting.',</strong></p><p style="line-height:1.6">'.$intro.'</p></div>'
                .quotation_email_summary($quote,$items,$labels)
                .'<div style="max-width:760px;margin:0 auto;color:#657b8c;font-size:12px;text-align:center">SÓCRATES ONE · Creative Studio · www.socrates.la</div></body></html>';
            $filename=preg_replace('/[^A-Za-z0-9._-]+/','-',(string)$quote['quotation_number']).'.pdf';
            $emailAttachments=[['filename'=>$filename,'mime'=>'application/pdf','content'=>$pdfBytes]];
            foreach($additionalAttachments as$attachment){
                $absolute=__DIR__.'/'.ltrim((string)$attachment['file_path'],'/');
                if(is_file($absolute)){
                    $content=@file_get_contents($absolute);
                    if($content!==false)$emailAttachments[]=['filename'=>(string)$attachment['file_name'],'mime'=>(string)$attachment['mime_type'],'content'=>$content];
                }
            }
            $result=send_system_email($to,$subject,$html,$emailAttachments);
            db()->prepare('INSERT INTO notifications (quotation_id,recipient,type,subject,status,error_message,sent_at) VALUES (?,?,?,?,?,?,?)')->execute([$id,$to,'quotation_pdf',$subject,$result['success']?'sent':'failed',$result['error'],$result['success']?date('Y-m-d H:i:s'):null]);
            if($result['success']){
                db()->prepare("UPDATE quotations SET status='sent',sent_at=NOW(),recipient_email=? WHERE id=?")->execute([$to,$id]);
                log_activity('Cotización enviada con PDF',(int)$quote['client_id'],null,$to,$id);
                flash('success','Cotización enviada correctamente con el PDF adjunto a '.$to.'.');
                redirect('quotation_view.php?id='.$id);
            }
            $errors[]='No se pudo enviar: '.$result['error'];
        }
    }
}
$defaultSubject=$labels['subject'].' '.$quote['quotation_number'].' · '.$quote['company_name'];
require __DIR__.'/includes/header.php';
?>
<?php foreach($errors as$error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
<div class="dashboard-grid">
<form method="post" class="panel"><div class="panel-header"><div><h2>Enviar cotización por email</h2><span class="table-sub">El cliente recibirá un resumen de servicios y el PDF completo adjunto.</span></div></div><div class="panel-body form-grid"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><label>Destinatario *<input type="email" name="to" required value="<?=e($_POST['to']??$quote['recipient_email']??$quote['client_email'])?>"></label><label class="full">Asunto *<input name="subject" required value="<?=e($_POST['subject']??$defaultSubject)?>"></label><label class="full">Mensaje introductorio<textarea name="message" placeholder="<?=e($labels['intro'])?>"><?=e($_POST['message']??'')?></textarea></label><div class="delivery-checklist full"><div class="delivery-check"><b>✓</b><div><strong>Resumen dentro del email</strong><span class="table-sub">Servicios, cantidades, subtotales y total.</span></div></div><div class="delivery-check"><b>✓</b><div><strong>PDF y documentos adjuntos</strong><span class="table-sub"><?=e($quote['quotation_number'])?>.pdf<?=count($additionalAttachments)?' + '.count($additionalAttachments).' documento(s) adicional(es)':''?>.</span></div></div></div><div class="full form-actions"><a class="btn btn-light" href="quotation_view.php?id=<?=$id?>">Cancelar</a><button class="btn btn-primary">ENVIAR COTIZACIÓN Y PDF</button></div></div></form>
<aside class="panel"><div class="panel-header"><h2>Resumen</h2></div><div class="panel-body metric-list"><div class="metric-row"><span>Número</span><strong><?=e($quote['quotation_number'])?></strong></div><div class="metric-row"><span>Cliente</span><strong><?=e($quote['company_name'])?></strong></div><div class="metric-row"><span>Servicios</span><strong><?=count($items)?></strong></div><div class="metric-row"><span>Total</span><strong><?=e(format_money($quote['total'],$quote['currency']))?></strong></div><div class="metric-row"><span>Forma de pago visible</span><strong><?=((int)($quote['show_payment_method']??1)===1)?'Sí':'No'?></strong></div></div></aside>
</div>
<?php require __DIR__.'/includes/footer.php';?>
