<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
$isCli=PHP_SAPI==='cli';
if(!$isCli){header('Content-Type: text/plain; charset=UTF-8');$provided=(string)($_GET['key']??'');if($provided===''||!hash_equals((string)config('cron_key'),$provided)){http_response_code(403);exit("Acceso denegado.\n");}}
$today=new DateTimeImmutable('today');$globalRecipient=(string)setting('notification_email','socrates@socrates.la');
$services=db()->query("SELECT s.*,c.company_name,c.email client_email,c.whatsapp client_whatsapp,c.owner_user_id,u.email owner_email,u.whatsapp owner_whatsapp,u.notify_email owner_notify_email,u.notify_whatsapp owner_notify_whatsapp,u.notify_inapp owner_notify_inapp FROM client_services s JOIN clients c ON c.id=s.client_id LEFT JOIN users u ON u.id=c.owner_user_id WHERE s.status='active' AND c.status<>'inactive' AND COALESCE(s.next_billing_date,s.expiration_date) IS NOT NULL AND COALESCE(s.billing_status,'pending')<>'not_billable'")->fetchAll();
$sent=0;$failed=0;$inapp=0;$wa=0;
foreach($services as$service){
    billing_sync_charge($service);
    $due=new DateTimeImmutable((string)($service['next_billing_date']?:$service['expiration_date']));
    $diff=(int)$today->diff($due)->format('%r%a');
    $days=array_values(array_unique(array_filter(array_map('intval',preg_split('/\s*,\s*/',(string)($service['alert_days']?:'30,15,7,3,1,0'))),fn($n)=>$n>=-30&&$n<=365)));
    if(!in_array($diff,$days,true))continue;
    $type=$diff<0?'billing_overdue':'billing_due';$subject=$diff<0?'Cobro vencido – '.$service['company_name']:'Próximo cobro – '.$service['company_name'];
    $already=db()->prepare("SELECT COUNT(*) FROM notifications WHERE service_id=? AND type=? AND DATE(created_at)=CURDATE() AND subject=?");$already->execute([(int)$service['id'],$type,$subject]);if((int)$already->fetchColumn()>0)continue;
    $when=$diff===0?'vence hoy':($diff>0?'vence en '.$diff.' día'.($diff===1?'':'s'):'venció hace '.abs($diff).' día'.(abs($diff)===1?'':'s'));
    $url=app_url('billing_services.php?q='.rawurlencode((string)$service['company_name']));
    $html='<div style="font-family:Arial,sans-serif;max-width:680px;margin:auto;background:#f3f6f8;padding:24px"><div style="background:#fff;border-radius:14px;overflow:hidden;border:1px solid #dbe6ed"><div style="background:#002f57;color:#fff;padding:24px"><strong>SÓCRATES CRM</strong><h2 style="margin:6px 0 0">'.e($subject).'</h2></div><div style="padding:24px"><p>El servicio <strong>'.e($service['name']).'</strong> de <strong>'.e($service['company_name']).'</strong> '.$when.'.</p><p><strong>Valor:</strong> '.e(format_money($service['amount'],$service['currency'])).'<br><strong>Fecha:</strong> '.e(format_date($due->format('Y-m-d'))).'<br><strong>Modalidad:</strong> '.e(contract_label($service['contract_type'])).'</p><p><a href="'.e($url).'" style="background:#ff5300;color:#fff;padding:12px 18px;text-decoration:none;border-radius:4px">ABRIR FACTURACIÓN</a></p></div></div></div>';
    if((int)($service['billing_notify_email']??1)===1){$recipient=$service['owner_email']?:$globalRecipient;$r=send_system_email($recipient,$subject,$html);db()->prepare("INSERT INTO notifications(service_id,recipient,type,subject,status,error_message,sent_at) VALUES (?,?,?,?,?,?,?)")->execute([(int)$service['id'],$recipient,$type,$subject,$r['success']?'sent':'failed',$r['success']?null:($r['error']??'Error'),$r['success']?date('Y-m-d H:i:s'):null]);$r['success']?$sent++:$failed++;}
    if((int)($service['billing_notify_inapp']??1)===1 && !empty($service['owner_user_id'])){create_in_app_notification((int)$service['owner_user_id'],$subject,$service['name'].' · '.format_money($service['amount'],$service['currency']).' · '.$when,'warning','billing_services.php?q='.rawurlencode((string)$service['company_name']));$inapp++;}
    if((int)($service['billing_notify_whatsapp']??0)===1 && !empty($service['owner_whatsapp'])){$msg="SÓCRATES CRM\n{$subject}\nServicio: {$service['name']}\nValor: ".format_money($service['amount'],$service['currency'])."\nFecha: ".format_date($due->format('Y-m-d'))."\n{$url}";$r=send_whatsapp_cloud_message((string)$service['owner_whatsapp'],$msg);if($r['success'])$wa++;else$failed++;}
    log_activity('Alerta de facturación procesada',(int)$service['client_id'],(int)$service['id'],$when);
}
if(db_table_exists('billing_charges'))db()->exec("UPDATE billing_charges SET status='overdue' WHERE status='pending' AND due_date<CURDATE()");
echo 'SÓCRATES CRM Billing - '.date('Y-m-d H:i:s')."\nEmail: {$sent}\nCRM: {$inapp}\nWhatsApp: {$wa}\nErrores: {$failed}\n";
