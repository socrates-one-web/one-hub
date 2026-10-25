<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/auth.php';
require dirname(__DIR__).'/includes/document_manager.php';
// Scheduled worker: prevent unauthenticated HTTP invocations. CLI runs remain supported.
if(PHP_SAPI!=='cli'){
    $expectedKey=trim((string)config('cron_key',''));
    $providedKey=(string)($_GET['key']??'');
    if($expectedKey==='' || str_starts_with($expectedKey,'CHANGE_ME') || $providedKey==='' || !hash_equals($expectedKey,$providedKey)){
        http_response_code(403);exit('Acceso restringido.');
    }
}

$days=array_values(array_unique(array_filter(array_map('intval',explode(',',(string)setting('document_expiry_alert_days','30,15,7,3,1'))),fn($n)=>$n>=0&&$n<=365)));
if(!$days)exit("No alert days configured\n");
$users=db()->query("SELECT id,name,role,permissions,department FROM users WHERE is_active=1")->fetchAll();$sent=0;
foreach($users as$user){
  $GLOBALS['dms_cron_user']=$user;
  $uid=(int)$user['id'];
  $where="d.is_deleted=0 AND d.expires_at IS NOT NULL AND DATEDIFF(d.expires_at,CURDATE()) IN (".implode(',',$days).")";
  // Cron intentionally not impersonating auth permissions: notify owner plus explicit user/role/department permissions.
  $sql="SELECT DISTINCT d.id,d.title,d.expires_at FROM documents d LEFT JOIN document_permissions p ON p.document_id=d.id WHERE $where AND (d.visibility='company' OR d.owner_user_id=? OR (p.subject_type='user' AND p.subject_value=?) OR (p.subject_type='role' AND p.subject_value=?) OR (p.subject_type='department' AND p.subject_value=?))";
  $st=db()->prepare($sql);$st->execute([$uid,(string)$uid,(string)$user['role'],(string)($user['department']??'')]);
  foreach($st->fetchAll() as$d){$daysLeft=(int)((strtotime($d['expires_at'])-strtotime(date('Y-m-d')))/86400);$title='Documento por vencer';$message=$d['title'].' vence '.($daysLeft===0?'hoy':'en '.$daysLeft.' día(s)').'.';$link='document_view.php?id='.(int)$d['id'];$dup=db()->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id=? AND title=? AND message=? AND DATE(created_at)=CURDATE()");$dup->execute([$uid,$title,$message]);if(!(int)$dup->fetchColumn()){db()->prepare('INSERT INTO user_notifications(user_id,title,message,type,link) VALUES(?,?,?,?,?)')->execute([$uid,$title,$message,'warning',$link]);$sent++;}}
}
echo "Document alerts created: $sent\n";
