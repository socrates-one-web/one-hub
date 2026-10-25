<?php
declare(strict_types=1);

function dms_storage_root(): string { return dirname(__DIR__).'/storage/documents'; }
function dms_ensure_storage(): void {
    $dir=dms_storage_root();
    if(!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) throw new RuntimeException('No fue posible crear el almacenamiento documental.');
    @file_put_contents($dir.'/.htaccess',"Require all denied\nOptions -Indexes\n",LOCK_EX);
    @file_put_contents($dir.'/index.html','',LOCK_EX);
}
function dms_safe_path(string $relative): string {
    $relative=str_replace('\\','/',$relative);
    $relative=ltrim($relative,'/');
    if($relative==='' || str_contains($relative,'..') || preg_match('~(^|/)\.(/|$)~',$relative)) throw new RuntimeException('Ruta documental inválida.');
    $root=realpath(dms_storage_root()) ?: dms_storage_root();
    $path=$root.'/'.$relative;
    $parent=realpath(dirname($path));
    if($parent===false || !str_starts_with($parent,$root)) throw new RuntimeException('Ruta documental fuera del almacenamiento permitido.');
    return $path;
}
function dms_user_department(?array $user=null): string { $user??=current_user(); return trim((string)($user['department']??'')); }
function dms_access_clause(array $user, string $alias='d'): array {
    if(($user['role']??'')==='admin' || has_permission('documents.manage')) return ['1=1',[]];
    $uid=(int)$user['id']; $role=(string)($user['role']??''); $dept=dms_user_department($user);
    $sql="($alias.visibility='company' OR $alias.owner_user_id=? OR EXISTS(SELECT 1 FROM document_permissions dp WHERE dp.document_id=$alias.id AND ((dp.subject_type='user' AND dp.subject_value=?) OR (dp.subject_type='role' AND dp.subject_value=?)";
    $params=[$uid,(string)$uid,$role];
    if($dept!==''){ $sql.=" OR (dp.subject_type='department' AND dp.subject_value=?)"; $params[]=$dept; }
    $sql.=")) OR EXISTS(SELECT 1 FROM document_approval_steps das JOIN document_approvals da ON da.id=das.approval_id WHERE da.document_id=$alias.id AND da.status='pending' AND das.status='pending' AND das.approver_user_id=?))";
    $params[]=$uid;
    return [$sql,$params];
}
function dms_can_view(array $doc, ?array $user=null): bool {
    $user??=current_user(); if(!$user) return false;
    if(($user['role']??'')==='admin'||has_permission('documents.manage')) return true;
    if((int)$doc['owner_user_id']===(int)$user['id'] || ($doc['visibility']??'private')==='company') return true;
    $stmt=db()->prepare("SELECT COUNT(*) FROM document_permissions WHERE document_id=? AND ((subject_type='user' AND subject_value=?) OR (subject_type='role' AND subject_value=?) OR (subject_type='department' AND subject_value=?))");
    $stmt->execute([(int)$doc['id'],(string)$user['id'],(string)$user['role'],dms_user_department($user)]);
    if((int)$stmt->fetchColumn()>0) return true;
    $stmt=db()->prepare("SELECT COUNT(*) FROM document_approval_steps s JOIN document_approvals a ON a.id=s.approval_id WHERE a.document_id=? AND a.status='pending' AND s.status='pending' AND s.approver_user_id=?");
    $stmt->execute([(int)$doc['id'],(int)$user['id']]);
    return (int)$stmt->fetchColumn()>0;
}
function dms_permission_flags(int $documentId, ?array $user=null): array {
    $user??=current_user();
    if(!$user) return ['view'=>false,'edit'=>false,'share'=>false];
    if(($user['role']??'')==='admin' || has_permission('documents.manage')) return ['view'=>true,'edit'=>true,'share'=>true];
    $uid=(int)($user['id']??0); $role=(string)($user['role']??''); $dept=dms_user_department($user);
    $sql="SELECT MAX(can_edit) can_edit,MAX(can_share) can_share,COUNT(*) matches FROM document_permissions WHERE document_id=? AND ((subject_type='user' AND subject_value=?) OR (subject_type='role' AND subject_value=?)";
    $params=[$documentId,(string)$uid,$role];
    if($dept!==''){ $sql.=" OR (subject_type='department' AND subject_value=?)"; $params[]=$dept; }
    $sql.=")";
    $st=db()->prepare($sql);$st->execute($params);$row=$st->fetch()?:[];
    return ['view'=>((int)($row['matches']??0)>0),'edit'=>((int)($row['can_edit']??0)===1),'share'=>((int)($row['can_share']??0)===1)];
}
function dms_can_edit(array $doc, ?array $user=null): bool {
    $user??=current_user(); if(!$user || !has_permission('documents.edit')) return false;
    if(($user['role']??'')==='admin'||has_permission('documents.manage')||(int)$doc['owner_user_id']===(int)$user['id']) return true;
    return dms_permission_flags((int)$doc['id'],$user)['edit'];
}
function dms_can_share(array $doc, ?array $user=null): bool {
    $user??=current_user(); if(!$user || !has_permission('documents.share')) return false;
    if(($user['role']??'')==='admin'||has_permission('documents.manage')||(int)$doc['owner_user_id']===(int)$user['id']) return true;
    return dms_permission_flags((int)$doc['id'],$user)['share'];
}
function dms_can_delete(array $doc, ?array $user=null): bool {
    $user??=current_user(); if(!$user || !has_permission('documents.delete')) return false;
    return (($user['role']??'')==='admin'||has_permission('documents.manage')||(int)$doc['owner_user_id']===(int)$user['id']);
}
function dms_document(int $id): ?array { $st=db()->prepare("SELECT d.*,f.name folder_name,u.name owner_name FROM documents d LEFT JOIN document_folders f ON f.id=d.folder_id LEFT JOIN users u ON u.id=d.owner_user_id WHERE d.id=? LIMIT 1");$st->execute([$id]);return $st->fetch()?:null; }
function dms_require_document(int $id): array { $d=dms_document($id); if(!$d){http_response_code(404);exit('Documento no encontrado.');} if(!dms_can_view($d)){http_response_code(403);exit('No tienes acceso a este documento.');} return $d; }
function dms_latest_version(int $documentId): ?array { $st=db()->prepare('SELECT * FROM document_versions WHERE document_id=? ORDER BY version_no DESC,id DESC LIMIT 1');$st->execute([$documentId]);return $st->fetch()?:null; }
function dms_mime_allowed(string $mime): bool { return in_array($mime,['application/pdf','image/jpeg','image/png','image/webp','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','text/plain','text/csv','application/zip','application/x-zip-compressed'],true); }
function dms_store_upload(array $file,int $documentId,int $versionNo): array {
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Selecciona un archivo válido.');
    $maxMb=max(1,(int)setting('document_max_mb','50')); if((int)($file['size']??0)>$maxMb*1024*1024) throw new RuntimeException('El archivo supera el máximo de '.$maxMb.' MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']) ?: 'application/octet-stream'; if(!dms_mime_allowed($mime)) throw new RuntimeException('Tipo de archivo no permitido en Document Manager.');
    $original=preg_replace('/[\x00-\x1F\x7F]+/u','',basename((string)($file['name']??'documento'))) ?: 'documento';
    $ext=strtolower((string)pathinfo($original,PATHINFO_EXTENSION)); $safeExt=preg_replace('/[^a-z0-9]/','',$ext); if($safeExt==='')$safeExt='bin';
    dms_ensure_storage(); $sub=date('Y/m'); $dir=dms_storage_root().'/'.$sub; if(!is_dir($dir))mkdir($dir,0750,true);
    $stored=$sub.'/'.sprintf('d%d-v%d-%s.%s',$documentId,$versionNo,bin2hex(random_bytes(12)),$safeExt);
    $target=dms_storage_root().'/'.$stored; if(!move_uploaded_file((string)$file['tmp_name'],$target)) throw new RuntimeException('No fue posible guardar el archivo.'); @chmod($target,0640);
    return ['original_name'=>$original,'stored_path'=>$stored,'mime_type'=>$mime,'file_size'=>(int)$file['size'],'sha256'=>hash_file('sha256',$target)];
}
function dms_add_version(int $documentId,array $file,int $userId,string $notes=''): int {
    $pdo=db(); $pdo->beginTransaction(); $path=null;
    try{$st=$pdo->prepare('SELECT COALESCE(MAX(version_no),0)+1 FROM document_versions WHERE document_id=? FOR UPDATE');$st->execute([$documentId]);$v=(int)$st->fetchColumn();$meta=dms_store_upload($file,$documentId,$v);$path=$meta['stored_path'];
        $ins=$pdo->prepare('INSERT INTO document_versions(document_id,version_no,original_name,stored_path,mime_type,file_size,sha256,notes,created_by) VALUES(?,?,?,?,?,?,?,?,?)');$ins->execute([$documentId,$v,$meta['original_name'],$meta['stored_path'],$meta['mime_type'],$meta['file_size'],$meta['sha256'],$notes,$userId]);
        $pdo->prepare('UPDATE documents SET current_version_id=?,updated_at=NOW() WHERE id=?')->execute([(int)$pdo->lastInsertId(),$documentId]);$pdo->commit(); dms_audit($documentId,'version_added','Nueva versión v'.$v,$userId);return $v;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if($path){$f=dms_storage_root().'/'.$path;if(is_file($f))@unlink($f);}throw $e;}
}
function dms_audit(?int $documentId,string $action,string $details='',?int $userId=null): void { try{if($userId===null&&function_exists('current_user')){$cu=current_user();$userId=$cu?(int)($cu['id']??0):null;}if($userId!==null&&$userId<1)$userId=null;$st=db()->prepare('INSERT INTO document_audit(document_id,user_id,action,details,ip_address,user_agent) VALUES(?,?,?,?,?,?)');$st->execute([$documentId,$userId,$action,$details,(string)($_SERVER['REMOTE_ADDR']??''),substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,250)]);}catch(Throwable){} }
function dms_status_label(string $s): string {return match($s){'draft'=>'Borrador','review'=>'En revisión','approved'=>'Aprobado','rejected'=>'Rechazado','archived'=>'Archivado',default=>ucfirst($s)};}
function dms_status_badge(string $s): string {return match($s){'approved'=>'green','review'=>'orange','rejected'=>'red','archived'=>'gray',default=>'blue'};}
function dms_file_extension(?string $name): string {
    $ext=strtolower((string)pathinfo((string)$name,PATHINFO_EXTENSION));
    return $ext!==''?$ext:'file';
}
function dms_file_kind(?string $name,?string $mime=null): string {
    $ext=dms_file_extension($name);
    $mime=strtolower((string)$mime);
    if($ext==='pdf'||$mime==='application/pdf')return 'pdf';
    if(in_array($ext,['doc','docx','odt','rtf'],true)||str_contains($mime,'word'))return 'word';
    if(in_array($ext,['xls','xlsx','csv','ods'],true)||str_contains($mime,'excel')||str_contains($mime,'spreadsheet')||$mime==='text/csv')return 'sheet';
    if(in_array($ext,['ppt','pptx','odp'],true)||str_contains($mime,'powerpoint')||str_contains($mime,'presentation'))return 'slides';
    if(in_array($ext,['jpg','jpeg','png','webp','gif'],true)||str_starts_with($mime,'image/'))return 'image';
    if(in_array($ext,['zip','rar','7z','gz','tar'],true)||str_contains($mime,'zip')||str_contains($mime,'compressed'))return 'archive';
    if(in_array($ext,['txt','md'],true)||str_starts_with($mime,'text/'))return 'text';
    return 'file';
}
function dms_file_icon(?string $name,?string $mime=null,string $class='dms-type-svg'): string {
    $kind=dms_file_kind($name,$mime);
    $icon=match($kind){'word'=>'manual','sheet'=>'report','slides'=>'quote','image'=>'open','archive'=>'import','text'=>'form',default=>'contract'};
    return crm_icon($icon,$class.' dms-type-icon dms-type-'.$kind);
}
function dms_file_type_label(?string $name,?string $mime=null): string {
    $ext=strtoupper(dms_file_extension($name));
    return $ext==='FILE'?'ARCHIVO':substr($ext,0,5);
}
function dms_format_bytes(int $bytes): string {
    if($bytes<=0)return '—';
    if($bytes>=1073741824)return number_format($bytes/1073741824,1,',','.').' GB';
    if($bytes>=1048576)return number_format($bytes/1048576,1,',','.').' MB';
    if($bytes>=1024)return number_format($bytes/1024,0,',','.').' KB';
    return $bytes.' B';
}
function dms_preview_mode(?string $name, ?string $mime=null): string {
    $ext=dms_file_extension($name);
    $mime=strtolower(trim((string)$mime));
    if($ext==='pdf'||$mime==='application/pdf') return 'pdf';
    if(in_array($ext,['jpg','jpeg','png','webp','gif','bmp','svg'],true)||str_starts_with($mime,'image/')) return 'image';
    if($ext==='csv'||$mime==='text/csv'||$mime==='application/csv') return 'csv';
    if(in_array($ext,['txt','md','log','ini','conf','css','js','php','sql','json','xml','yml','yaml'],true)||str_starts_with($mime,'text/')) return 'text';
    if($ext==='docx'||$mime==='application/vnd.openxmlformats-officedocument.wordprocessingml.document') return 'docx';
    if($ext==='xlsx'||$mime==='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') return 'xlsx';
    if($ext==='pptx'||$mime==='application/vnd.openxmlformats-officedocument.presentationml.presentation') return 'pptx';
    if(in_array($ext,['zip','jar'],true)||in_array($mime,['application/zip','application/x-zip-compressed'],true)) return 'archive';
    return 'fallback';
}
function dms_can_preview(?string $mime, ?string $name=null): bool {
    return trim((string)$mime)!=='' || trim((string)$name)!=='';
}
function dms_is_native_inline_preview(?string $mime, ?string $name=null): bool {
    return in_array(dms_preview_mode($name,$mime),['pdf','image'],true);
}
function dms_entity_label(string $type,int $id): string {
    if(!$id)return '—';
    $queries=[
        'client'=>"SELECT company_name FROM clients WHERE id=?",
        'contact'=>"SELECT TRIM(CONCAT_WS(' ',first_name,last_name)) FROM contacts WHERE id=?",
        'project'=>"SELECT name FROM projects WHERE id=?",
        'quotation'=>"SELECT quotation_number FROM quotations WHERE id=?",
        'contract'=>"SELECT title FROM contracts WHERE id=?",
        'ticket'=>"SELECT subject FROM support_tickets WHERE id=?",
        'service'=>"SELECT name FROM client_services WHERE id=?",
    ];
    if(!isset($queries[$type]))return '#'.$id;
    try{$st=db()->prepare($queries[$type]);$st->execute([$id]);$v=$st->fetchColumn();return $v!==false&&trim((string)$v)!==''?(string)$v:'#'.$id;}catch(Throwable){return '#'.$id;}
}
function dms_create_share_token(int $documentId,int $versionId,?string $password,?string $expiresAt,int $limit,bool $allowDownload): string {
    $token=bin2hex(random_bytes(24));$hash=hash('sha256',$token);$pwd=$password!==null&&$password!==''?password_hash($password,PASSWORD_DEFAULT):null;
    $st=db()->prepare('INSERT INTO document_shares(document_id,version_id,token_hash,password_hash,expires_at,download_limit,allow_download,created_by) VALUES(?,?,?,?,?,?,?,?)');$st->execute([$documentId,$versionId,$hash,$pwd,$expiresAt,$limit>0?$limit:null,$allowDownload?1:0,(int)current_user()['id']]); dms_audit($documentId,'share_created','Enlace externo creado');return $token;
}

function dms_related_panel(string $type,int $entityId,string $title='Documentos relacionados'): string {
    if($entityId<1 || !has_permission('documents.view')) return '';
    $u=current_user(); if(!$u)return '';
    [$acl,$params]=dms_access_clause($u,'d');
    try{$sql="SELECT d.*,v.original_name,v.mime_type,v.version_no FROM document_links l JOIN documents d ON d.id=l.document_id LEFT JOIN document_versions v ON v.id=d.current_version_id WHERE l.entity_type=? AND l.entity_id=? AND d.is_deleted=0 AND $acl ORDER BY d.updated_at DESC LIMIT 12";$st=db()->prepare($sql);$st->execute(array_merge([$type,$entityId],$params));$rows=$st->fetchAll();}catch(Throwable){return '';}
    ob_start();?>
    <section class="panel dms-related-panel" style="margin-top:20px"><div class="panel-header"><div><h2><?=crm_icon('contract')?> <?=e($title)?></h2><span class="table-sub">Archivos vinculados desde Gestión Documental.</span></div><div class="actions"><?php if(has_permission('documents.upload')):?><a class="btn btn-light btn-small" href="document_upload.php?entity_type=<?=e($type)?>&entity_id=<?=$entityId?>">+ DOCUMENTO</a><?php endif;?><a class="btn btn-light btn-small" href="documents.php">BIBLIOTECA</a></div></div><div class="panel-body">
    <?php if(!$rows):?><div class="empty">No hay documentos relacionados todavía.</div><?php else:?><div class="dms-related-grid"><?php foreach($rows as$r):?><a class="dms-related-item" href="document_view.php?id=<?=(int)$r['id']?>"><span class="dms-file-iconbox dms-kind-<?=e(dms_file_kind($r['original_name']??null,$r['mime_type']??null))?>"><?=dms_file_icon($r['original_name']??null,$r['mime_type']??null)?></span><span><strong><?=e($r['title'])?></strong><small><?=e(dms_status_label((string)$r['status']))?> · v<?=(int)($r['version_no']??0)?> · <?=e(format_date($r['updated_at']))?></small></span><b>→</b></a><?php endforeach;?></div><?php endif;?>
    </div></section>
    <?php return (string)ob_get_clean();
}
