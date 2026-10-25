<?php
require dirname(__DIR__).'/includes/bootstrap.php';
if(defined('APP_DEMO_MODE') && APP_DEMO_MODE){ http_response_code(403); exit('MODO DEMO: worker desactivado.'); }
if(PHP_SAPI!=='cli'){
 $key=$_GET['key']??''; if(!hash_equals(APP_KEY,$key)){http_response_code(403);exit('Forbidden');}
}
$limit=max(1,min(100,(int)($_GET['limit']??25)));$delayMs=max(0,min(5000,(int)($_GET['delay_ms']??250)));
$st=db()->prepare('SELECT d.*,c.subject,c.preheader,c.from_name,c.from_email,c.html,c.blocks_json,c.tenant_id FROM deliveries d JOIN campaigns c ON c.id=d.campaign_id WHERE d.status="queued" AND c.status IN("queued","sending") ORDER BY d.id ASC LIMIT '.$limit);
$st->execute();$rows=$st->fetchAll();$sent=0;$failed=0;$compiledCache=[];
foreach($rows as $d){
 try{
  $ss=db()->prepare('SELECT * FROM settings WHERE tenant_id=?');$ss->execute([$d['tenant_id']]);$cfg=$ss->fetch();if(!$cfg||empty($cfg['smtp_host']))throw new RuntimeException('SMTP no configurado');
  $from=$d['from_email']?:$cfg['default_from_email'];if(!$from)throw new RuntimeException('Remitente no configurado');
  $cid=(int)$d['campaign_id'];
  if(!array_key_exists($cid,$compiledCache)){
   $decoded=json_decode((string)($d['blocks_json']??'[]'),true);
   $compiledCache[$cid]=(is_array($decoded)&&$decoded)?compile_campaign_html($decoded):(string)($d['html']??'');
  }
  $html=email_inject_preheader((string)$compiledCache[$cid],(string)($d['preheader']??''));
  $unsub=app_url('tracking/unsubscribe.php?t='.$d['token']);
  $html=str_replace('{{unsubscribe_url}}',$unsub,$html);
  if(class_exists('DOMDocument')){
   $dom=new DOMDocument();libxml_use_internal_errors(true);$dom->loadHTML($html,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);foreach($dom->getElementsByTagName('a') as $a){$href=$a->getAttribute('href');if(!$href||strpos($href,'mailto:')===0||strpos($href,'tel:')===0||strpos($href,'#')===0)continue;$u=rtrim(strtr(base64_encode($href),'+/','-_'),'=');$sig=hash_hmac('sha256',$d['token'].'|'.$u,APP_KEY);$a->setAttribute('href',app_url('tracking/click.php?t='.$d['token'].'&u='.$u.'&s='.$sig));}$html=$dom->saveHTML();libxml_clear_errors();
  }
  $tail='<img src="'.tracking_pixel_url($d['token']).'" width="1" height="1" alt="" style="display:block;width:1px;height:1px;opacity:0">'
      .'<p style="font-family:Arial,sans-serif;font-size:11px;color:#789;text-align:center;margin:28px 0 12px">Si no deseas recibir más mensajes, <a href="'.$unsub.'">desuscríbete aquí</a>.</p>';
  if(stripos($html,'</body>')!==false)$html=preg_replace('/<\/body>/i',$tail.'</body>',$html,1);else $html.=$tail;
  $smtp=new SimpleSMTP();$smtp->send(['host'=>$cfg['smtp_host'],'port'=>$cfg['smtp_port'],'user'=>$cfg['smtp_user'],'pass'=>decrypt_secret($cfg['smtp_pass']),'secure'=>$cfg['smtp_secure'],'from_name'=>$d['from_name']?:($cfg['default_from_name']??'')],$from,$d['email'],$d['subject'],$html,['List-Unsubscribe'=>'<'.$unsub.'>','List-Unsubscribe-Post'=>'List-Unsubscribe=One-Click','Reply-To'=>($cfg['reply_to_email']??'')?:$from]);
  db()->prepare('UPDATE deliveries SET status="sent",sent_at=NOW(),error_text=NULL WHERE id=?')->execute([$d['id']]);db()->prepare('UPDATE campaigns SET status="sending" WHERE id=?')->execute([$d['campaign_id']]);$sent++;if($delayMs>0)usleep($delayMs*1000);
 }catch(Throwable $e){db()->prepare('UPDATE deliveries SET status="failed",error_text=? WHERE id=?')->execute([substr($e->getMessage(),0,1000),$d['id']]);$failed++;}
}
$campaigns=db()->query('SELECT id FROM campaigns WHERE status="sending"')->fetchAll();foreach($campaigns as $c){$q=db()->prepare('SELECT COUNT(*) FROM deliveries WHERE campaign_id=? AND status="queued"');$q->execute([$c['id']]);if((int)$q->fetchColumn()===0)db()->prepare('UPDATE campaigns SET status="sent",sent_at=NOW() WHERE id=?')->execute([$c['id']]);}
echo "EMAILSEND ONE worker: sent=$sent failed=$failed\n";
