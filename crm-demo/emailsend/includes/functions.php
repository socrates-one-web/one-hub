<?php
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) { http_response_code(419); exit('Sesión expirada.'); }
}
function redirect(string $url): never { header('Location: ' . $url); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = compact('type','message'); }
function pull_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function now(): string { return date('Y-m-d H:i:s'); }
function random_token(int $bytes=24): string { return bin2hex(random_bytes($bytes)); }
function current_tenant_id(): ?int {
    $u = current_user();
    if (!$u) return null;
    if ($u['role']==='admin' && !empty($_SESSION['effective_tenant_id'])) return (int)$_SESSION['effective_tenant_id'];
    return (int)$u['tenant_id'];
}
function current_tenant_name(): string {
    $id=current_tenant_id(); if(!$id) return '';
    $st=db()->prepare('SELECT name FROM tenants WHERE id=?'); $st->execute([$id]);
    return (string)($st->fetchColumn() ?: '');
}
function is_admin(): bool { $u=current_user(); return $u && $u['role']==='admin'; }
function app_url(string $path=''): string {
    $base=(string)APP_URL;
    if($base===''){
        $host=trim((string)($_SERVER['HTTP_HOST']??''));
        $forwarded=strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??'')));
        $https=(!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS'])!=='off') || $forwarded==='https';
        $script=(string)($_SERVER['SCRIPT_NAME']??'/');
        $dir=rtrim(str_replace('\\','/',dirname($script)),'/');
        if($dir==='.'||$dir==='/')$dir='';
        $base=$host!==''?(($https?'https':'http').'://'.$host.$dir):'';
    }
    return rtrim($base,'/').'/'.ltrim($path,'/');
}


// v0.8.8 · Importador de bases comerciales grandes (CSV UTF-8/Windows-1252).
function contact_utf8(mixed $value): string {
    $value=(string)$value;
    if($value==='') return '';
    $value=preg_replace('/^\xEF\xBB\xBF/','',$value) ?? $value;
    if(function_exists('mb_check_encoding') && mb_check_encoding($value,'UTF-8')) return trim($value);
    if(function_exists('mb_convert_encoding')){
        $enc=function_exists('mb_detect_encoding')?(mb_detect_encoding($value,['UTF-8','Windows-1252','ISO-8859-1'],true)?:'Windows-1252'):'Windows-1252';
        return trim((string)mb_convert_encoding($value,'UTF-8',$enc));
    }
    if(function_exists('iconv')){
        $v=@iconv('Windows-1252','UTF-8//IGNORE',$value);
        if($v!==false) return trim($v);
    }
    return trim($value);
}
function contact_normalize_header(string $value): string {
    $value=contact_utf8($value);
    if(function_exists('mb_strtolower')) $value=mb_strtolower($value,'UTF-8'); else $value=strtolower($value);
    if(function_exists('iconv')){$ascii=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);if($ascii!==false)$value=$ascii;}
    $value=preg_replace('/[^a-z0-9]+/','',$value) ?? '';
    return strtolower($value);
}
function contact_header_field(string $header): ?string {
    $h=contact_normalize_header($header);
    if($h==='') return null;
    if(str_contains($h,'email') || str_contains($h,'correo') || $h==='mail' || $h==='mails') return 'email';
    if(str_contains($h,'nombreempresa') || $h==='empresa' || $h==='razonsocial') return 'company_name';
    if(str_contains($h,'tramosegunventas') || str_contains($h,'tramoventa') || $h==='ventas') return 'sales_band';
    if(str_contains($h,'tipoempresa') || $h==='tamanoempresa') return 'company_type';
    if(str_contains($h,'trabajador') || str_contains($h,'empleados')) return 'employees';
    if(str_contains($h,'ingreso') || str_contains($h,'facturacion')) return 'annual_income';
    if(str_contains($h,'actividadeconom') || str_contains($h,'giro') || $h==='actividad') return 'economic_activity';
    if($h==='region' || $h==='region1' || str_starts_with($h,'region')) return 'region';
    if(str_contains($h,'provincia')) return 'province';
    if(str_contains($h,'comuna') || str_contains($h,'ciudad')) return 'commune';
    if(str_contains($h,'nombrerlegal') || str_contains($h,'representantelegal') || str_contains($h,'representante')) return 'legal_representative';
    if(str_contains($h,'fonosfijos') || str_contains($h,'telefonofijo') || str_contains($h,'fonofijo')) return 'phone_fixed';
    if(str_contains($h,'fonosmoviles') || str_contains($h,'telefonomovil') || str_contains($h,'celular') || str_contains($h,'movil')) return 'phone_mobile';
    if($h==='nombre' || $h==='name' || $h==='contacto' || $h==='nombrecontacto') return 'name';
    return null;
}
function contact_import_map_headers(array $headers): array {
    $map=[];
    foreach($headers as $i=>$header){
        $field=contact_header_field((string)$header);
        if($field!==null && !array_key_exists($field,$map)) $map[$field]=(int)$i; // first compatible column wins
    }
    return $map;
}
function contact_extract_emails(string $raw): array {
    $raw=contact_utf8($raw);
    if($raw==='') return [];
    preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',$raw,$matches);
    $out=[];
    foreach($matches[0]??[] as $email){$email=strtolower(trim($email," \t\n\r\0\x0B.,;:/"));if(filter_var($email,FILTER_VALIDATE_EMAIL))$out[$email]=true;}
    return array_keys($out);
}
function contact_csv_delimiter(string $path): string {
    $fh=@fopen($path,'rb');$line=$fh?(string)fgets($fh):'';if($fh)fclose($fh);
    $delims=[','=>substr_count($line,','),';'=>substr_count($line,';'),"\t"=>substr_count($line,"\t"),'|'=>substr_count($line,'|')];
    arsort($delims);$d=(string)array_key_first($delims);return (($delims[$d]??0)>0)?$d:',';
}
function contact_import_dir(): string {
    $dir=APP_ROOT.'/storage/imports';if(!is_dir($dir))@mkdir($dir,0755,true);return $dir;
}
function contact_import_job_file(string $job): string { return contact_import_dir().'/'.preg_replace('/[^a-f0-9]/','',$job).'.json'; }
function contact_import_load_job(string $job): ?array {
    $file=contact_import_job_file($job);if(!is_file($file))return null;$data=json_decode((string)@file_get_contents($file),true);return is_array($data)?$data:null;
}
function contact_import_save_job(array $job): void {
    @file_put_contents(contact_import_job_file((string)$job['id']),json_encode($job,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);
}
function contact_row_value(array $row,array $map,string $field): string {
    if(!array_key_exists($field,$map))return '';$idx=(int)$map[$field];return contact_utf8($row[$idx]??'');
}
function contact_original_row(array $headers,array $row): array {
    $out=[];foreach($headers as $i=>$h){$key=contact_utf8($h);if($key==='')$key='Columna '.($i+1);$out[$key]=contact_utf8($row[$i]??'');}return $out;
}

// v0.9.0 · Campos flexibles, aprendizaje de mapeos y permisos por listas.
function contact_standard_fields(): array {
    return [
        'email'=>'Email','company_name'=>'Empresa / Razón social','name'=>'Nombre / contacto',
        'phone_mobile'=>'Teléfono móvil','phone_fixed'=>'Teléfono fijo','legal_representative'=>'Representante legal',
        'company_type'=>'Tipo de empresa','sales_band'=>'Tramo de ventas','employees'=>'Trabajadores',
        'annual_income'=>'Ingresos anuales','economic_activity'=>'Actividad económica',
        'region'=>'Región','province'=>'Provincia','commune'=>'Comuna'
    ];
}
function contact_custom_fields(int $tenantId,bool $onlyActive=true): array {
    $sql='SELECT * FROM contact_custom_fields WHERE tenant_id=?'.($onlyActive?' AND active=1':'').' ORDER BY sort_order,name,id';
    $st=db()->prepare($sql);$st->execute([$tenantId]);return $st->fetchAll();
}
function contact_custom_field_by_id(int $tenantId,int $id): ?array {
    $st=db()->prepare('SELECT * FROM contact_custom_fields WHERE id=? AND tenant_id=?');$st->execute([$id,$tenantId]);$r=$st->fetch();return $r?:null;
}
function contact_custom_field_key(string $name): string {
    $k=contact_normalize_header($name);if($k==='')$k='campo'.substr(hash('sha256',$name),0,12);return substr($k,0,180);
}
function contact_find_or_create_custom_field(int $tenantId,string $name): int {
    $name=trim(contact_utf8($name));if($name==='')$name='Campo personalizado';
    $key=contact_custom_field_key($name);
    $st=db()->prepare('SELECT id FROM contact_custom_fields WHERE tenant_id=? AND field_key=? LIMIT 1');$st->execute([$tenantId,$key]);$id=(int)($st->fetchColumn()?:0);
    if($id)return $id;
    $pos=(int)db()->query('SELECT COALESCE(MAX(sort_order),0)+10 FROM contact_custom_fields WHERE tenant_id='.(int)$tenantId)->fetchColumn();
    try{$st=db()->prepare('INSERT INTO contact_custom_fields(tenant_id,name,field_key,active,sort_order,created_at) VALUES(?,?,?,1,?,NOW())');$st->execute([$tenantId,$name,$key,$pos]);return (int)db()->lastInsertId();}
    catch(Throwable $e){$st=db()->prepare('SELECT id FROM contact_custom_fields WHERE tenant_id=? AND field_key=? LIMIT 1');$st->execute([$tenantId,$key]);return (int)$st->fetchColumn();}
}
function contact_import_alias(int $tenantId,string $header): ?array {
    $norm=contact_normalize_header($header);if($norm==='')return null;
    $st=db()->prepare('SELECT * FROM contact_import_aliases WHERE tenant_id=? AND normalized_header=?');$st->execute([$tenantId,$norm]);$r=$st->fetch();return $r?:null;
}
function contact_import_save_alias(int $tenantId,string $header,string $targetType,?string $targetKey): void {
    $norm=contact_normalize_header($header);if($norm==='')return;
    if(!in_array($targetType,['standard','custom','ignore'],true))$targetType='ignore';
    $st=db()->prepare('INSERT INTO contact_import_aliases(tenant_id,normalized_header,source_header,target_type,target_key,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE source_header=VALUES(source_header),target_type=VALUES(target_type),target_key=VALUES(target_key),updated_at=NOW()');
    $st->execute([$tenantId,$norm,substr(contact_utf8($header),0,255),$targetType,$targetKey]);
}
function contact_import_suggestion(int $tenantId,string $header,array $customFields=[]): string {
    $alias=contact_import_alias($tenantId,$header);
    if($alias){
        if($alias['target_type']==='standard' && isset(contact_standard_fields()[$alias['target_key']])) return 'std:'.$alias['target_key'];
        if($alias['target_type']==='custom' && contact_custom_field_by_id($tenantId,(int)$alias['target_key'])) return 'custom:'.(int)$alias['target_key'];
        if($alias['target_type']==='ignore') return 'ignore';
    }
    $std=contact_header_field($header);if($std!==null)return 'std:'.$std;
    $norm=contact_normalize_header($header);
    foreach($customFields as $f)if(contact_normalize_header((string)$f['name'])===$norm || (string)$f['field_key']===$norm)return 'custom:'.(int)$f['id'];
    return 'new';
}
function contact_import_sample_rows(string $file,string $delimiter,int $limit=6): array {
    $out=[];$fh=@fopen($file,'rb');if(!$fh)return $out;fgetcsv($fh,0,$delimiter);
    while(count($out)<$limit && ($row=fgetcsv($fh,0,$delimiter))!==false)$out[]=array_map('contact_utf8',$row);
    fclose($fh);return $out;
}
function contact_excel_column_index(string $ref): int {
    if(!preg_match('/^([A-Z]+)/i',$ref,$m))return 0;$letters=strtoupper($m[1]);$n=0;
    for($i=0,$l=strlen($letters);$i<$l;$i++)$n=$n*26+(ord($letters[$i])-64);
    return max(0,$n-1);
}
function contact_zip_entry_fallback(string $path,string $entry): string|false {
    $fh=@fopen($path,'rb');if(!$fh)return false;$size=@filesize($path);if(!$size){fclose($fh);return false;}
    $tailLen=min((int)$size,66000);fseek($fh,$size-$tailLen);$tail=(string)fread($fh,$tailLen);$pos=strrpos($tail,"PK\x05\x06");
    if($pos===false){fclose($fh);return false;}$eocd=substr($tail,$pos,22);if(strlen($eocd)<22){fclose($fh);return false;}
    $u=unpack('VcdSize/VcdOffset',substr($eocd,12,8));$cdOffset=(int)($u['cdOffset']??0);fseek($fh,$cdOffset);
    while(!feof($fh)){
        $hdr=fread($fh,46);if(strlen($hdr)<46 || substr($hdr,0,4)!=="PK\x01\x02")break;
        $method=unpack('v',substr($hdr,10,2))[1];$compSize=unpack('V',substr($hdr,20,4))[1];$nameLen=unpack('v',substr($hdr,28,2))[1];$extraLen=unpack('v',substr($hdr,30,2))[1];$commentLen=unpack('v',substr($hdr,32,2))[1];$localOffset=unpack('V',substr($hdr,42,4))[1];
        $name=(string)fread($fh,$nameLen);if($name===$entry){
            $returnPos=ftell($fh)+$extraLen+$commentLen;fseek($fh,$localOffset);$lh=fread($fh,30);if(strlen($lh)<30 || substr($lh,0,4)!=="PK\x03\x04"){fclose($fh);return false;}
            $ln=unpack('v',substr($lh,26,2))[1];$lx=unpack('v',substr($lh,28,2))[1];fseek($fh,$ln+$lx,SEEK_CUR);$compressed='';
            $remaining=$compSize;while($remaining>0 && !feof($fh)){$chunk=fread($fh,min(1048576,$remaining));if($chunk==='')break;$compressed.=$chunk;$remaining-=strlen($chunk);}
            fclose($fh);if($method===0)return $compressed;if($method===8){$out=@gzinflate($compressed);return $out===false?false:$out;}return false;
        }
        fseek($fh,$extraLen+$commentLen,SEEK_CUR);
    }
    fclose($fh);return false;
}
function contact_zip_entry(string $path,string $entry): string|false {
    if(class_exists('ZipArchive')){
        $zip=new ZipArchive();if($zip->open($path)!==true)return false;$data=$zip->getFromName($entry);$zip->close();return $data;
    }
    if(!function_exists('gzinflate'))return false;
    return contact_zip_entry_fallback($path,$entry);
}
function contact_xlsx_shared_strings_from_xml(string|false $xml): array {
    if($xml===false || $xml==='')return [];
    $reader=new XMLReader();if(!$reader->XML($xml,null,LIBXML_NONET|LIBXML_COMPACT))return [];
    $out=[];
    while($reader->read()){
        if($reader->nodeType===XMLReader::ELEMENT && $reader->localName==='si'){
            $outer=$reader->readOuterXML();$sx=@simplexml_load_string($outer);$text='';
            if($sx!==false){
                $ns=$sx->getNamespaces(true);$main=$ns['']??'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                $sx->registerXPathNamespace('x',$main);$nodes=$sx->xpath('.//x:t')?:[];
                foreach($nodes as $t)$text.=(string)$t;
            }
            $out[]=contact_utf8($text);
        }
    }
    $reader->close();return $out;
}
function contact_xlsx_to_csv(string $xlsx,string $csv): void {
    if(!class_exists('XMLReader') || !function_exists('simplexml_load_string'))throw new RuntimeException('El servidor no tiene habilitadas las extensiones XML necesarias para leer Excel .xlsx. Puedes guardar el archivo como CSV UTF-8.');
    $shared=contact_xlsx_shared_strings_from_xml(contact_zip_entry($xlsx,'xl/sharedStrings.xml'));
    $sheetXml=contact_zip_entry($xlsx,'xl/worksheets/sheet1.xml');
    if($sheetXml===false)throw new RuntimeException('No encontramos la primera hoja del Excel.');
    $tmp=contact_import_dir().'/sheet-'.random_token(8).'.xml';if(@file_put_contents($tmp,$sheetXml)===false)throw new RuntimeException('No se pudo preparar la hoja de Excel.');unset($sheetXml);
    $csvh=@fopen($csv,'wb');if(!$csvh){@unlink($tmp);throw new RuntimeException('No se pudo crear el archivo temporal de importación.');}
    $reader=new XMLReader();if(!$reader->open($tmp,null,LIBXML_NONET|LIBXML_COMPACT)){fclose($csvh);@unlink($tmp);throw new RuntimeException('No se pudo leer la hoja de Excel.');}
    while($reader->read()){
        if($reader->nodeType!==XMLReader::ELEMENT || $reader->localName!=='row')continue;
        $outer=$reader->readOuterXML();$sx=@simplexml_load_string($outer);if($sx===false)continue;
        $ns=$sx->getNamespaces(true);$main=$ns['']??'http://schemas.openxmlformats.org/spreadsheetml/2006/main';$sx->registerXPathNamespace('x',$main);
        $cells=$sx->xpath('./x:c')?:[];$row=[];
        foreach($cells as $c){
            $attrs=$c->attributes();$ref=(string)($attrs['r']??'');$idx=contact_excel_column_index($ref);$type=(string)($attrs['t']??'');$value='';
            $c->registerXPathNamespace('x',$main);
            if($type==='inlineStr'){$nodes=$c->xpath('.//x:t')?:[];foreach($nodes as $n)$value.=(string)$n;}
            else{$v=$c->xpath('./x:v');$raw=$v?(string)$v[0]:'';$value=($type==='s' && $raw!=='')?($shared[(int)$raw]??''):$raw;}
            $row[$idx]=contact_utf8($value);
        }
        if($row){$max=max(array_keys($row));$dense=[];for($i=0;$i<=$max;$i++)$dense[]=$row[$i]??'';fputcsv($csvh,$dense);}
    }
    $reader->close();fclose($csvh);@unlink($tmp);
}
function current_user_list_access_mode(): string {
    $u=current_user();return $u ? (string)($u['list_access_mode']??'all') : 'assigned';
}
function user_allowed_list_ids(int $tenantId,?int $userId=null): ?array {
    $u=current_user();if(!$userId)$userId=(int)($u['id']??0);
    if(!$userId)return [];
    $st=db()->prepare('SELECT tenant_id,role,list_access_mode FROM users WHERE id=? AND active=1');$st->execute([$userId]);$target=$st->fetch();
    if(!$target || (int)$target['tenant_id']!==$tenantId)return [];
    if(($target['role']==='admin') && (($target['list_access_mode']??'all')==='all'))return null;
    if(($target['list_access_mode']??'assigned')==='all')return null;
    $q=db()->prepare('SELECT ucl.list_id FROM user_contact_lists ucl JOIN contact_lists l ON l.id=ucl.list_id WHERE ucl.user_id=? AND l.tenant_id=? ORDER BY ucl.list_id');
    $q->execute([$userId,$tenantId]);return array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
}
function current_user_can_access_list(int $listId,int $tenantId): bool {
    if($listId<=0)return current_user_list_access_mode()==='all';
    $ids=user_allowed_list_ids($tenantId);return $ids===null || in_array($listId,$ids,true);
}
function current_user_allowed_lists(int $tenantId): array {
    $ids=user_allowed_list_ids($tenantId);
    $sql='SELECT l.*,rc.company client_company,(SELECT COUNT(*) FROM contacts c WHERE c.list_id=l.id) contact_count FROM contact_lists l LEFT JOIN report_clients rc ON rc.id=l.client_id WHERE l.tenant_id=?';
    $params=[$tenantId];
    if($ids!==null){if(!$ids)return [];$sql.=' AND l.id IN ('.implode(',',array_fill(0,count($ids),'?')).')';$params=array_merge($params,$ids);}
    $sql.=' ORDER BY COALESCE(rc.company,"ZZZZ"),l.name';$st=db()->prepare($sql);$st->execute($params);return $st->fetchAll();
}
function campaign_lists_are_authorized(int $campaignId,int $tenantId): bool {
    $allowed=user_allowed_list_ids($tenantId);if($allowed===null)return true;
    $ids=campaign_selected_list_ids($campaignId,null);
    // A restricted user must never inherit the legacy "all active contacts" audience.
    if(!$ids)return false;
    foreach($ids as $id)if(!in_array((int)$id,$allowed,true))return false;
    return true;
}

function save_user_list_permissions(int $userId,int $tenantId,array $listIds): void {
    $listIds=array_values(array_unique(array_filter(array_map('intval',$listIds))));
    db()->prepare('DELETE FROM user_contact_lists WHERE user_id=?')->execute([$userId]);
    if(!$listIds)return;
    $ph=implode(',',array_fill(0,count($listIds),'?'));$q=db()->prepare('SELECT id FROM contact_lists WHERE tenant_id=? AND id IN ('.$ph.')');$q->execute([$tenantId,...$listIds]);
    $valid=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));$ins=db()->prepare('INSERT IGNORE INTO user_contact_lists(user_id,list_id,assigned_at) VALUES(?,?,NOW())');
    foreach($valid as $lid)$ins->execute([$userId,$lid]);
}
function user_assigned_list_ids(int $userId): array {
    $st=db()->prepare('SELECT list_id FROM user_contact_lists WHERE user_id=? ORDER BY list_id');$st->execute([$userId]);return array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN));
}
// v0.9.2 · Permisos granulares por módulo y acción.
function permission_modules(): array {
    return [
        'dashboard'=>['label'=>'Dashboard','actions'=>['view'=>'Ver']],
        'contacts'=>['label'=>'Contactos y listas','actions'=>['view'=>'Ver','create'=>'Crear contactos/listas','edit'=>'Editar','delete'=>'Eliminar','import'=>'Importar bases']],
        'campaigns'=>['label'=>'Campañas y diseñador','actions'=>['view'=>'Ver','create'=>'Crear','edit'=>'Editar/diseñar','delete'=>'Eliminar','send'=>'Enviar']],
        'reports'=>['label'=>'Reportes','actions'=>['view'=>'Ver']],
        'clients'=>['label'=>'Clientes / Portal','actions'=>['view'=>'Ver','edit'=>'Gestionar']],
        'help'=>['label'=>'Centro de ayuda','actions'=>['view'=>'Ver']],
    ];
}
function user_permissions(int $userId): array {
    static $cache=[]; if(isset($cache[$userId])) return $cache[$userId];
    try{$st=db()->prepare('SELECT module,action,allowed FROM user_permissions WHERE user_id=?');$st->execute([$userId]);$out=[];foreach($st as $r)$out[$r['module']][$r['action']]=(bool)$r['allowed'];return $cache[$userId]=$out;}catch(Throwable $e){return $cache[$userId]=[];}
}
function current_user_can(string $module,string $action='view'): bool {
    $u=current_user(); if(!$u)return false; if(($u['role']??'')==='admin')return true;
    $p=user_permissions((int)$u['id']);
    // Usuarios heredados sin filas de permisos conservan acceso operativo para no romper instalaciones existentes.
    if(!$p)return in_array($module,['dashboard','contacts','campaigns','reports','help'],true);
    return !empty($p[$module][$action]);
}
function require_permission(string $module,string $action='view'): void { require_login(); if(!current_user_can($module,$action)){http_response_code(403);exit('No tienes permiso para realizar esta acción.');} }
function save_user_permissions(int $userId,array $posted): void {
    db()->prepare('DELETE FROM user_permissions WHERE user_id=?')->execute([$userId]);
    $mods=permission_modules();$ins=db()->prepare('INSERT INTO user_permissions(user_id,module,action,allowed,created_at) VALUES(?,?,?,?,NOW())');
    foreach($mods as $module=>$meta){foreach($meta['actions'] as $action=>$label){$allowed=!empty($posted[$module][$action])?1:0;$ins->execute([$userId,$module,$action,$allowed]);}}
}

function css_value(array $style,string $key,string $default=''): string {
    $v=(string)($style[$key]??$default);
    return preg_replace('/[^#a-zA-Z0-9.,%()\-\s]/','',$v) ?? $default;
}

// v0.13.5 · Sanitizador CSS para valores directos del documento/email.
// compile_campaign_html() usa valores que no vienen dentro de un array de estilos
// (outerPadding, contentRadius, backgroundSize, backgroundPosition). La versión
// 0.13.3 llamaba css_safe() sin definirla, provocando Error 500 al guardar.
function css_safe(string $value,string $default=''): string {
    $value=trim($value);
    if($value==='') return $default;
    $safe=preg_replace('/[^#a-zA-Z0-9.,%()\-\s\/]/','',$value);
    if($safe===null || trim($safe)==='') return $default;
    return trim($safe);
}
function email_font_stack(array $style,string $key='fontFamily',string $default='Ubuntu Sans'): string {
    $fonts=[
        // Corporate / branded. Email clients that do not load web fonts use the safe fallback.
        'Exo 2'=>'&quot;Exo 2&quot;,Arial,Helvetica,sans-serif',
        'Ubuntu Sans'=>'&quot;Ubuntu Sans&quot;,Arial,Helvetica,sans-serif',

        // Email-safe sans serif.
        'Arial'=>'Arial,Helvetica,sans-serif',
        'Helvetica'=>'Helvetica,Arial,sans-serif',
        'Verdana'=>'Verdana,Geneva,Arial,sans-serif',
        'Tahoma'=>'Tahoma,Verdana,Arial,sans-serif',
        'Trebuchet MS'=>'&quot;Trebuchet MS&quot;,Arial,sans-serif',
        'Arial Black'=>'&quot;Arial Black&quot;,Arial,Helvetica,sans-serif',

        // Email-safe serif / monospace.
        'Georgia'=>'Georgia,&quot;Times New Roman&quot;,serif',
        'Times New Roman'=>'&quot;Times New Roman&quot;,Times,serif',
        'Courier New'=>'&quot;Courier New&quot;,Courier,monospace',
    ];
    $name=(string)($style[$key]??$default);
    return $fonts[$name]??$fonts[$default]??$fonts['Ubuntu Sans'];
}
function email_block_width(array $style): int {
    $raw=(string)($style['blockWidth']??'100%');
    $n=(int)round((float)preg_replace('/[^0-9.]/','',$raw));
    return max(30,min(100,$n?:100));
}
function email_block_min_height(array $style): int {
    $raw=(string)($style['minHeight']??'');
    $n=(int)round((float)preg_replace('/[^0-9.]/','',$raw));
    return $n>0?max(40,min(1200,$n)):0;
}
function email_min_height_css(array $style): string {
    $h=email_block_min_height($style);
    return $h>0?'min-height:'.$h.'px;':'';
}
function email_wrap_block(string $piece,array $style): string {
    if($piece==='')return '';
    $w=email_block_width($style);
    $align=(string)($style['blockAlign']??'center');if(!in_array($align,['left','center','right'],true))$align='center';
    $alignCss=$align==='left'?'margin-left:0;margin-right:auto;':($align==='right'?'margin-left:auto;margin-right:0;':'margin-left:auto;margin-right:auto;');
    $wrapped=$piece;
    if($w<100)$wrapped='<table role="presentation" width="'.$w.'%" align="'.$align.'" cellpadding="0" cellspacing="0" border="0" style="width:'.$w.'%;max-width:100%;'.$alignCss.'"><tr><td>'.$piece.'</td></tr></table>';
    $margin=css_value($style,'margin','0px');
    if($margin!=='' && !preg_match('/^(?:0|0px)(?:\s+(?:0|0px)){0,3}$/i',trim($margin))){
        // Email clients are more consistent with outer TD padding than CSS margin.
        $wrapped='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse"><tr><td style="padding:'.$margin.'">'.$wrapped.'</td></tr></table>';
    }
    return $wrapped;
}
function emailsend_upload_proxy_url(string $relative): string {
    $relative=ltrim(str_replace('\\','/',$relative),'/');
    if(preg_match('~^uploads/(\d{4}/\d{2}/[a-f0-9]{16,128}\.(?:jpg|jpeg|png|gif|webp))$~i',$relative,$m)){
        return app_url('media.php?f='.rawurlencode($m[1]));
    }
    return app_url($relative);
}

function emailsend_normalize_asset_url(string $url): string {
    $url=trim($url);
    if($url==='') return '';

    if(preg_match('~builder3-media\.php\?show=([a-f0-9]{32}\.(?:jpg|jpeg|png|gif|webp))~i',$url,$m)){
        return app_url('builder3-media.php?show='.rawurlencode($m[1]));
    }

    if(preg_match('~builder-media\.php\?show=([a-f0-9]{24}\.(?:jpg|jpeg|png|gif|webp))~i',$url,$m)){
        return app_url('builder-media.php?show='.rawurlencode($m[1]));
    }

    // v0.14.1: builder-media.php only serves campaign files through ?show=.
    // Historical ?f= URLs and regular /uploads/YYYY/MM/... assets must go through media.php.
    if(preg_match('~builder-media\.php\?f=([^&]+)~i',$url,$m)){
        return app_url('media.php?f='.rawurlencode(rawurldecode($m[1])));
    }

    if(preg_match('~(?:image\.php\?f=|media\.php\?f=|index\.php\?route=image_asset&f=)([^&]+)~i',$url,$m)){
        return app_url('media.php?f='.rawurlencode(rawurldecode($m[1])));
    }

    // Campaign Builder 3 uploads live in /uploads/campaigns3/<32hex>.<ext>.
    if(preg_match('~/uploads/campaigns3/([a-f0-9]{32}\.(?:jpg|jpeg|png|gif|webp))~i',$url,$m)){
        return app_url('builder3-media.php?show='.rawurlencode($m[1]));
    }

    // Campaign Builder uploads live in /uploads/campaigns/<24hex>.<ext> and are
    // publicly served by builder-media.php?show=<filename>.
    if(preg_match('~/uploads/campaigns/([a-f0-9]{24}\.(?:jpg|jpeg|png|gif|webp))~i',$url,$m)){
        return app_url('builder-media.php?show='.rawurlencode($m[1]));
    }

    // Main editor uploads live in /uploads/YYYY/MM/<24hex>.<ext> and are served
    // by media.php. Keep the route public, cacheable and independent of login/session.
    if(preg_match('~/sendmail/uploads/(\d{4}/\d{2}/[a-f0-9]{24}\.(?:jpg|jpeg|png|gif|webp))~i',$url,$m)){
        return app_url('media.php?f='.rawurlencode($m[1]));
    }
    if(preg_match('~/uploads/(\d{4}/\d{2}/[a-f0-9]{24}\.(?:jpg|jpeg|png|gif|webp))~i',$url,$m)){
        return app_url('media.php?f='.rawurlencode($m[1]));
    }

    return $url;
}

function email_safe_background_url(string $url): string {
    $url=emailsend_normalize_asset_url($url); if($url==='') return '';
    if(preg_match('#^(?:javascript|data):#i',$url)) return '';
    if(str_starts_with($url,'//')) $url='https:'.$url;
    elseif(!preg_match('#^https?://#i',$url)) $url=app_url(ltrim($url,'/'));
    return str_replace(["'",'(',')'],['%27','%28','%29'],e($url));
}

function email_safe_asset_url(string $url): string {
    $url=emailsend_normalize_asset_url($url); if($url==='') return '';
    if(preg_match('#^(?:javascript):#i',$url)) return '';
    if(str_starts_with($url,'//')) $url='https:'.$url;
    elseif(!preg_match('#^(?:https?:|data:image/)#i',$url)) $url=app_url(ltrim($url,'/'));
    return e($url);
}
function email_width_attr(string $value,string $fallback='100%'): string {
    $value=trim($value);
    if(preg_match('/^([0-9]{1,4})px$/i',$value,$m)) return (string)max(1,min(1200,(int)$m[1]));
    if(preg_match('/^([0-9]{1,3}(?:\\.[0-9]+)?)%$/',$value,$m)) return rtrim(rtrim($m[1],'0'),'.').'%';
    if(preg_match('/^[0-9]{1,4}$/',$value)) return (string)max(1,min(1200,(int)$value));
    return $fallback;
}
function email_hex_color(string $value,string $fallback='#ffffff'): string {
    $value=trim($value); return preg_match('/^#[0-9a-f]{6}$/i',$value)?$value:$fallback;
}
function email_element_scale(array $style): float {
    $n=(float)($style['elementScale']??100); if($n<=0)$n=100; return max(10,min(100,$n))/100;
}
function email_scaled_dimension(string $value,float $scale,string $fallback='100%'): string {
    $value=trim($value); if($value==='')$value=$fallback;
    if(preg_match('/^([0-9]+(?:\.[0-9]+)?)(px|%)$/i',$value,$m)){
        $n=max(0,(float)$m[1]*$scale);$n=round($n,2);$txt=rtrim(rtrim(number_format($n,2,'.',''),'0'),'.');return $txt.$m[2];
    }
    return $value;
}
function email_scaled_px(string $value,float $scale,string $fallback): string {
    $raw=trim($value);if(!preg_match('/^([0-9]+(?:\.[0-9]+)?)px$/i',$raw,$m))preg_match('/^([0-9]+(?:\.[0-9]+)?)px$/i',$fallback,$m);
    $n=isset($m[1])?(float)$m[1]:14;return rtrim(rtrim(number_format(max(1,$n*$scale),2,'.',''),'0'),'.').'px';
}
function email_scaled_padding(string $value,float $scale,string $fallback='12px 18px'): string {
    $value=trim($value);if($value==='')$value=$fallback;
    $parts=preg_split('/\s+/', $value)?:[];$out=[];
    foreach($parts as $part){if(preg_match('/^([0-9]+(?:\.[0-9]+)?)px$/i',$part,$m)){$n=round((float)$m[1]*$scale,2);$out[]=rtrim(rtrim(number_format($n,2,'.',''),'0'),'.').'px';}else{$out[]=$part;}}
    return $out?implode(' ',$out):$fallback;
}
function email_button_html(string $label,string $href,array $style,string $align='left'): string {
    if(!in_array($align,['left','center','right'],true)) $align='left';
    $bg=email_hex_color((string)($style['buttonBg']??'#ff5300'),'#ff5300');
    $color=email_hex_color((string)($style['buttonColor']??'#ffffff'),'#ffffff');
    $scale=email_element_scale($style);
    $pad=email_scaled_padding(css_value($style,'buttonPadding','12px 18px'),$scale,'12px 18px');
    $radius=email_scaled_px(css_value($style,'buttonRadius',$style['radius']??'4px'),$scale,'4px');
    $font=email_font_stack($style,'buttonFontFamily',(string)($style['fontFamily']??'Ubuntu Sans'));
    $size=email_scaled_px(css_value($style,'buttonSize',$style['fontSize']??'14px'),$scale,'14px');
    $weight=css_value($style,'buttonWeight',$style['fontWeight']??'700');
    $line=css_value($style,'buttonLineHeight',$style['lineHeight']??'1.2');
    $letter=css_value($style,'buttonLetterSpacing',$style['letterSpacing']??'0px');
    return '<table role="presentation" align="'.$align.'" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="'.$bg.'" style="background-color:'.$bg.';border-radius:'.$radius.';padding:'.$pad.';mso-padding-alt:0"><a href="'.e($href).'" style="display:inline-block;color:'.$color.';text-decoration:none;font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.';mso-line-height-rule:exactly">'.e(strip_tags($label)).'</a></td></tr></table>';
}
function email_preheader_html(string $preheader): string {
    $preheader=trim($preheader); if($preheader==='') return '';
    return '<div style="display:none!important;visibility:hidden;mso-hide:all;opacity:0;color:transparent;height:0;width:0;max-height:0;max-width:0;overflow:hidden;font-size:1px;line-height:1px">'.e($preheader).'&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>';
}
function email_inject_preheader(string $html,string $preheader): string {
    $block=email_preheader_html($preheader); if($block==='') return $html;
    if(preg_match('/<body\\b[^>]*>/i',$html)) return preg_replace('/(<body\\b[^>]*>)/i','$1'.$block,$html,1) ?? ($block.$html);
    return $block.$html;
}
function email_local_asset_path(string $source): string {
    $source=trim($source); if($source==='') return '';
    $root=realpath(dirname(__DIR__)) ?: dirname(__DIR__);
    $path=$source;
    if(preg_match('#^https?://#i',$source)){
        $parts=parse_url($source); $path=(string)($parts['path']??'');
        if($path==='') return '';
        // Prefer the app-relative part when URLs contain /uploads/ or /storage/uploads/.
        foreach(['/storage/uploads/','/uploads/'] as $marker){
            $pos=strpos($path,$marker);
            if($pos!==false){$path=substr($path,$pos+1);break;}
        }
    }
    $path=rawurldecode($path);
    $path=ltrim(str_replace('\\','/',$path),'/');
    $candidate=$root.'/'.$path;
    if(is_file($candidate)) return $candidate;
    // Last fallback: uploaded assets may be referenced only by basename.
    $base=basename($path);
    foreach([$root.'/uploads/'.$base,$root.'/storage/uploads/'.$base] as $try){if(is_file($try)) return $try;}
    return '';
}
function email_outlook_background_height(string $source,int $width,int $fallback=300): int {
    $width=max(1,min(1200,$width));
    $local=email_local_asset_path($source);
    if($local!=='' && function_exists('getimagesize')){
        $info=@getimagesize($local);
        if(is_array($info) && !empty($info[0]) && !empty($info[1])){
            $h=(int)round($width*((int)$info[1]/max(1,(int)$info[0])));
            return max(80,min(1400,$h));
        }
    }
    return max(80,min(1400,$fallback));
}
function email_outlook_circle_icon(string $symbol,string $bg,string $color,int $size=25): string {
    $size=max(16,min(48,$size));
    $bg=email_hex_color($bg,'#eef9e7');$color=email_hex_color($color,'#73bc25');
    $symbol=e(strip_tags($symbol!==''?$symbol:'✓'));
    $fontSize=max(11,(int)round($size*.58));
    $mso='<!--[if mso]><v:oval xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" style="width:'.$size.'px;height:'.$size.'px;" fillcolor="'.$bg.'" stroked="f" o:allowincell="true"><v:textbox inset="0,0,0,0"><div style="font-family:Arial,Helvetica,sans-serif;font-size:'.$fontSize.'px;line-height:'.$size.'px;color:'.$color.';text-align:center;font-weight:bold;">'.$symbol.'</div></v:textbox></v:oval><![endif]-->';
    $modern='<!--[if !mso]><!--><span style="display:inline-block;width:'.$size.'px;height:'.$size.'px;line-height:'.$size.'px;text-align:center;border-radius:50%;background-color:'.$bg.';color:'.$color.';font-family:Arial,Helvetica,sans-serif;font-size:'.$fontSize.'px;font-weight:800;">'.$symbol.'</span><!--<![endif]-->';
    return $mso.$modern;
}
function email_outlook_vml_open(string $url,string $bg,int $height,int $width=680,string $size='cover'): string {
    // Outlook desktop (Word engine) ignores CSS background-image. VML is the reliable fallback.
    // When no explicit min-height exists we use mso-fit-shape-to-text so the VML rectangle grows
    // with the real content instead of disappearing (the old code returned an empty string).
    if($url==='') return '';
    $bg=email_hex_color($bg,'#ffffff');
    $width=max(1,min(1200,$width));
    $height=max(0,min(2400,$height));
    $size=strtolower(trim($size));
    $aspect=$size==='contain'?'atmost':'atleast';
    $rectStyle='width:'.$width.'px;'.($height>0?'height:'.$height.'px;':'');
    $textboxStyle=$height>0?'':' style="mso-fit-shape-to-text:true"';
    return '<!--[if gte mso 9]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" fill="true" stroke="false" o:allowincell="true" style="'.$rectStyle.'"><v:fill type="frame" src="'.$url.'" color="'.$bg.'" aspect="'.$aspect.'"/><v:textbox inset="0,0,0,0"'.$textboxStyle.'><![endif]-->';
}
function email_outlook_vml_close(string $url,int $height=0): string {
    return $url!==''?'<!--[if gte mso 9]></v:textbox></v:rect><![endif]-->':'';
}
function email_overlay_rgba(string $hex,float $alpha): string {
    if(!preg_match('/^#([0-9a-f]{6})$/i',trim($hex),$m)) $hex='#000000';
    $h=ltrim($hex,'#');$r=hexdec(substr($h,0,2));$g=hexdec(substr($h,2,2));$b=hexdec(substr($h,4,2));$a=max(0,min(1,$alpha));
    return 'rgba('.$r.','.$g.','.$b.','.rtrim(rtrim(number_format($a,3,'.',''),'0'),'.').')';
}
function email_background_css(array $style,string $default='#ffffff'): string {
    $bg=css_value($style,'background',$default);$css='background-color:'.$bg.';';
    $url=email_safe_background_url((string)($style['backgroundImage']??''));
    if($url!==''){
        $size=css_value($style,'backgroundSize','cover'); if(!in_array($size,['cover','contain','auto'],true))$size='cover';
        $pos=css_value($style,'backgroundPosition','center center');
        $repeat=css_value($style,'backgroundRepeat','no-repeat'); if(!in_array($repeat,['no-repeat','repeat','repeat-x','repeat-y'],true))$repeat='no-repeat';
        $op=max(0,min(1,(float)($style['overlayOpacity']??0)));$overlay='';
        if($op>0){$overlay=email_overlay_rgba((string)($style['overlayColor']??'#000000'),$op);$overlay='linear-gradient('.$overlay.','.$overlay.'),';}
        $css.="background-image:{$overlay}url('{$url}');background-size:{$size};background-position:{$pos};background-repeat:{$repeat};";
    }
    return $css;
}

function email_html_has_visible_content(string $html): bool {
    $plain=html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8');
    $plain=str_replace(["\xC2\xA0","&nbsp;","\u{200B}"],'',$plain);
    return trim($plain)!=='';
}
function email_default_check_icon_html(string $symbol,string $bg,string $color,int $size=25): string {
    // EMAILSEND ONE v0.12.3
    // Render icons inline instead of loading a PNG from /assets.
    // This avoids broken icons in preview, Gmail, Outlook and other clients
    // when the public asset URL is unavailable or the hostname changes.
    $symbol=trim($symbol);
    $bg=trim($bg);
    $color=trim($color);

    if($symbol==='') $symbol='✓';
    if($bg==='') $bg='#eef9e7';
    if($color==='') $color='#73bc25';

    return email_outlook_circle_icon($symbol,$bg,$color,$size);
}

function email_columns_cells(array $block): array {
    if(!empty($block['cells']) && is_array($block['cells'])) return array_values(array_filter($block['cells'],'is_array'));
    $style=is_array($block['style']??null)?$block['style']:[];
    $type=(string)($block['type']??'columns');
    $vals=$type==='columns1'
        ?[$block['content']??'<h3>Columna</h3><p>Contenido editable.</p>']
        :($type==='columns3'
            ?[$block['one']??'',$block['two']??'',$block['three']??'']
            :[$block['left']??'',$block['right']??'']);
    $cellStyle=[
        'background'=>'transparent','padding'=>$style['padding']??'18px 20px','color'=>$style['color']??'#1f2937',
        'align'=>$style['align']??'left','fontFamily'=>$style['fontFamily']??'Ubuntu Sans','fontSize'=>$style['fontSize']??'16px',
        'fontWeight'=>$style['fontWeight']??'400','lineHeight'=>$style['lineHeight']??'1.5','letterSpacing'=>$style['letterSpacing']??'0px','margin'=>'0px','borderWidth'=>'0px','borderStyle'=>'solid','borderColor'=>'#dce5ec','verticalAlign'=>'top','minHeight'=>'0px','radius'=>'0'
    ];
    return array_map(fn($v)=>['content'=>$v,'style'=>$cellStyle,'nested'=>null],$vals);
}
function compile_campaign_fragment(array $blocks): string {
    return $blocks ? compile_campaign_blocks($blocks) : '';
}
function compile_email_columns(array $block,int $depth=1,int $containerWidth=680): string {
    $s=is_array($block['style']??null)?$block['style']:[];$cells=email_columns_cells($block);if(!$cells)return '';
    $hasNewCells=!empty($block['cells'])&&is_array($block['cells']);
    $parentPad=$hasNewCells?css_value($s,'padding','0'):'0';
    $parentBg=email_background_css($s,'transparent');$parentHeight=email_block_min_height($s);$parentHeightCss=$parentHeight>0?'min-height:'.$parentHeight.'px;':'';$parentHeightAttr=$parentHeight>0?' height="'.$parentHeight.'"':'';$n=max(1,count($cells));
    $rawWidths=array_map(static fn($c)=>(float)($c['width']??0),$cells);$validWidths=count(array_filter($rawWidths,static fn($v)=>$v>0))===$n;
    if($validWidths){$sum=array_sum($rawWidths);$widths=$sum>0?array_map(static fn($v)=>$v/$sum*100,$rawWidths):array_fill(0,$n,100/$n);}else{$widths=array_fill(0,$n,100/$n);}
    $inner='<table role="presentation" class="email-columns-table" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse"><tr>';
    foreach($cells as $cellIndex=>$cell){
        $width=$widths[$cellIndex]??(100/$n);
        $cs=is_array($cell['style']??null)?$cell['style']:[];
        $pad=css_value($cs,'padding',$hasNewCells?'18px 20px':($s['padding']??'18px 20px'));
        $margin=css_value($cs,'margin','0px');
        $color=css_value($cs,'color',$s['color']??'#1f2937');$align=css_value($cs,'align',$s['align']??'left');
        $vAlign=css_value($cs,'verticalAlign','top');if(!in_array($vAlign,['top','middle','bottom'],true))$vAlign='top';
        $size=css_value($cs,'fontSize',$s['fontSize']??'16px');$weight=css_value($cs,'fontWeight',$s['fontWeight']??'400');$line=css_value($cs,'lineHeight',$s['lineHeight']??'1.5');$letter=css_value($cs,'letterSpacing',$s['letterSpacing']??'0px');
        $radius=css_value($cs,'radius','0');$font=email_font_stack($cs,'fontFamily',(string)($s['fontFamily']??'Ubuntu Sans'));
        $borderWidth=css_value($cs,'borderWidth','0px');$borderStyle=css_value($cs,'borderStyle','solid');if(!in_array($borderStyle,['solid','dashed','dotted','double','none'],true))$borderStyle='solid';$borderColor=css_value($cs,'borderColor','#dce5ec');
        $cellHeight=email_block_min_height($cs);$cellHeightCss=$cellHeight>0?'min-height:'.$cellHeight.'px;':'';$cellHeightAttr=$cellHeight>0?' height="'.$cellHeight.'"':'';
        $cellBg=email_background_css($cs,'transparent');$bgUrl=email_safe_background_url((string)($cs['backgroundImage']??''));$bgAttr=$bgUrl!==''?' background="'.$bgUrl.'"':'';
        $mobileBg=(string)($cs['backgroundSizeMobile']??'cover');if(!in_array($mobileBg,['cover','contain','width'],true))$mobileBg='cover';
        $bgClass=$bgUrl!==''?' class="email-bg-responsive email-bg-mobile-'.$mobileBg.'"':'';
        $content=(string)($cell['content']??'');
        if(!empty($cell['items'])&&is_array($cell['items'])) $content.=compile_campaign_fragment(array_values(array_filter($cell['items'],'is_array')));
        if(!empty($cell['nested'])&&is_array($cell['nested'])&&$depth<3){
            $nestedWidth=max(1,(int)round($containerWidth*($width/100)));
            $content.=compile_email_columns($cell['nested'],$depth+1,$nestedWidth);
        }
        $cellPixelWidth=max(1,(int)round($containerWidth*($width/100)));
        $hasStructuredItems=!empty($cell['items'])&&is_array($cell['items']);
        $hasNested=!empty($cell['nested'])&&is_array($cell['nested']);
        $overlayOpacity=max(0,min(1,(float)($cs['overlayOpacity']??0)));
        $pureVisualBackground=$bgUrl!=='' && !$hasStructuredItems && !$hasNested && !email_html_has_visible_content((string)($cell['content']??'')) && $cellHeight===0 && $overlayOpacity<=0;
        if($pureVisualBackground){
            // Outlook is dramatically more reliable with a real IMG than with CSS/VML when a column is purely visual.
            // Use the background image as structural content so the received email matches the builder instead of showing a blank color block.
            $imageAlt=e((string)($cell['alt']??''));
            $surface='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse"><tr><td bgcolor="'.email_hex_color((string)($cs['background']??'#ffffff'),'#ffffff').'" style="padding:0;border:'.$borderWidth.' '.$borderStyle.' '.$borderColor.';"><img src="'.$bgUrl.'" alt="'.$imageAlt.'" width="'.$cellPixelWidth.'" border="0" style="display:block;width:100%;max-width:'.$cellPixelWidth.'px;height:auto;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic"></td></tr></table>';
        } else {
            $cellVmlHeight=$cellHeight>0?$cellHeight:($bgUrl!==''?email_outlook_background_height((string)($cs['backgroundImage']??''),$cellPixelWidth,max(180,(int)round($cellPixelWidth*.52))):0);
            $cellVmlOpen=email_outlook_vml_open($bgUrl,(string)($cs['background']??'transparent'),$cellVmlHeight,$cellPixelWidth,(string)($cs['backgroundSize']??'cover'));
            $cellVmlClose=email_outlook_vml_close($bgUrl,$cellVmlHeight);
            $surface='<table role="presentation" width="100%"'.$cellHeightAttr.' cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:separate;'.$cellHeightCss.'"><tr><td'.$bgClass.$bgAttr.$cellHeightAttr.' valign="'.$vAlign.'" style="'.$cellBg.$cellHeightCss.'padding:'.$pad.';color:'.$color.';text-align:'.$align.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.';border:'.$borderWidth.' '.$borderStyle.' '.$borderColor.';border-radius:'.$radius.';box-sizing:border-box">'.$cellVmlOpen.$content.$cellVmlClose.'</td></tr></table>';
        }
        $inner.='<td class="email-col" width="'.number_format($width,4,'.','').'%' . '" valign="'.$vAlign.'" style="width:'.number_format($width,4,'.','').'%;padding:'.$margin.';box-sizing:border-box">'.$surface.'</td>';
    }
    $inner.='</tr></table>';
    $bgUrl=email_safe_background_url((string)($s['backgroundImage']??''));$bgAttr=$bgUrl!==''?' background="'.$bgUrl.'"':'';
    $parentVmlHeight=$parentHeight>0?$parentHeight:($bgUrl!==''?email_outlook_background_height((string)($s['backgroundImage']??''),$containerWidth,max(220,(int)round($containerWidth*.50))):0);
    $vmlOpen=email_outlook_vml_open($bgUrl,(string)($s['background']??'#ffffff'),$parentVmlHeight,$containerWidth,(string)($s['backgroundSize']??'cover'));
    $vmlClose=email_outlook_vml_close($bgUrl,$parentVmlHeight);
    return '<table role="presentation" width="100%"'.$parentHeightAttr.' cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;'.$parentHeightCss.'"><tr><td'.$bgAttr.$parentHeightAttr.' valign="top" style="'.$parentBg.$parentHeightCss.'padding:'.$parentPad.'">'.$vmlOpen.$inner.$vmlClose.'</td></tr></table>';
}
function email_part_hidden(array $block,string $part): bool { return !empty($block['hiddenParts']) && is_array($block['hiddenParts']) && !empty($block['hiddenParts'][$part]); }
function email_default_image_placeholder(string $src): bool {
    $v=strtolower(trim($src));
    return str_contains($v,'placehold.co/680x300') && (str_contains($v,'tu+imagen') || str_contains($v,'tu%20imagen') || str_contains($v,'tu-imagen'));
}
function email_effective_image_src(array $block): string {
    $src=trim((string)($block['src']??''));
    $style=is_array($block['style']??null)?$block['style']:[];
    $bg=trim((string)($style['backgroundImage']??''));
    if(($src==='' || email_default_image_placeholder($src)) && $bg!=='') return $bg;
    if(email_default_image_placeholder($src)) return '';
    return $src;
}

function email_clean_legal_from_nonfooter(string $value): string {
    $v=$value;
    $v=preg_replace('~\s*<a[^>]*>\s*Pol[ií]tica de Privacidad\s*</a>\s*~iu',' ',$v)??$v;
    $v=preg_replace('~\s*<a[^>]*>\s*Darse de baja\s*</a>\s*~iu',' ',$v)??$v;
    $v=preg_replace('~\s*Pol[ií]tica de Privacidad\s*[·|\-–—]?\s*Darse de baja\s*~iu',' ',$v)??$v;
    $v=preg_replace('~\s*Pol[ií]tica de Privacidad\s*~iu',' ',$v)??$v;
    $v=preg_replace('~\s*Darse de baja\s*~iu',' ',$v)??$v;
    return trim(preg_replace('/\s{2,}/u',' ',$v)??$v);
}

function compile_campaign_blocks(array $blocks): string {
    $out='';
    foreach ($blocks as $b) {
        $type=$b['type']??'text'; $content=$b['content']??''; $s=is_array($b['style']??null)?$b['style']:[];$piece='';
        if($type!=='footer')$content=email_clean_legal_from_nonfooter((string)$content);
        $color=css_value($s,'color','#1f2937');$pad=css_value($s,'padding','12px 0');
        $align=css_value($s,'align','left');$size=css_value($s,'fontSize','16px');$radius=css_value($s,'radius','0');$weight=css_value($s,'fontWeight','400');$line=css_value($s,'lineHeight','1.6');$letter=css_value($s,'letterSpacing','0px');
        $font=email_font_stack($s,'fontFamily',in_array($type,['title','hero','card'],true)?'Exo 2':'Ubuntu Sans');$bgCss=email_background_css($s,'#ffffff');$heightCss=email_min_height_css($s);
        if ($type==='text') { if(!email_part_hidden($b,'content')) $piece='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse"><tr><td style="font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.';color:'.$color.';padding:'.$pad.';'.$bgCss.$heightCss.'text-align:'.$align.';border-radius:'.$radius.';mso-line-height-rule:exactly">'.$content.'</td></tr></table>'; }
        elseif ($type==='title') { if(!email_part_hidden($b,'content')) $piece='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse"><tr><td style="font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.';color:'.$color.';padding:'.$pad.';'.$bgCss.$heightCss.'text-align:'.$align.';border-radius:'.$radius.';mso-line-height-rule:exactly">'.$content.'</td></tr></table>'; }
        elseif ($type==='image') {
            $rawSrc=email_effective_image_src($b);$src=email_safe_asset_url($rawSrc);$alt=e($b['alt']??'');$scale=email_element_scale($s);$w=email_scaled_dimension(css_value($s,'width','100%'),$scale,'100%');$wAttr=email_width_attr($w,'100%');$r=css_value($s,'radius','0');
            $legacyBg=trim((string)($s['backgroundImage']??''));
            $imageBgCss=$bgCss;
            if($rawSrc!=='' && $legacyBg!=='' && ($rawSrc===$legacyBg || email_default_image_placeholder((string)($b['src']??'')))){
                $tmp=$s;$tmp['backgroundImage']='';$tmp['overlayOpacity']=0;$imageBgCss=email_background_css($tmp,'#ffffff');
            }
            if($src&&!email_part_hidden($b,'image'))$piece='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="'.$align.'" style="padding:'.$pad.';'.$imageBgCss.$heightCss.'"><img src="'.$src.'" alt="'.$alt.'" width="'.$wAttr.'" border="0" style="display:block;width:'.$w.';max-width:100%;height:auto;border:0;outline:none;text-decoration:none;border-radius:'.$r.';-ms-interpolation-mode:bicubic"></td></tr></table>';
        }
        elseif ($type==='button') { if(!email_part_hidden($b,'button')) { $btnStyle=$s;$btnStyle['buttonFontFamily']=$s['fontFamily']??'Ubuntu Sans';$btnStyle['buttonSize']=$s['fontSize']??'14px';$btnStyle['buttonWeight']=$s['fontWeight']??'700';$btnStyle['buttonLineHeight']=$s['lineHeight']??'1.2';$btnStyle['buttonLetterSpacing']=$s['letterSpacing']??'0px';$btnStyle['buttonRadius']=$s['radius']??'4px';$piece='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:'.$pad.';'.$bgCss.$heightCss.'" align="'.$align.'">'.email_button_html((string)($content?:'Ver más'),(string)($b['href']??'#'),$btnStyle,$align).'</td></tr></table>'; } }
        elseif ($type==='divider') { if(!email_part_hidden($b,'divider')) $piece=email_divider_block_html($b,$s,$pad,$bgCss,$heightCss); }
        elseif ($type==='spacer') { if(!email_part_hidden($b,'spacer')) { $space=(int)preg_replace('/[^0-9]/','',css_value($s,'height','30px'));$mh=email_block_min_height($s);$space=max($space,$mh);$piece='<div style="height:'.$space.'px;'.$bgCss.'font-size:1px;line-height:1px">&nbsp;</div>'; } }
        elseif (in_array($type,['columns1','columns','columns3'],true)) $piece=compile_email_columns($b,1);
        elseif ($type==='logo') { $src=email_safe_asset_url((string)($b['src']??''));$alt=e($b['alt']??'Logo');$scale=email_element_scale($s);$w=email_scaled_dimension(css_value($s,'width','180px'),$scale,'180px');$wAttr=email_width_attr($w,'180');if($src&&!email_part_hidden($b,'logo'))$piece='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="'.$align.'" style="padding:'.$pad.';'.$bgCss.$heightCss.'"><img src="'.$src.'" alt="'.$alt.'" width="'.$wAttr.'" border="0" style="display:block;width:'.$w.';max-width:100%;height:auto;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic"></td></tr></table>'; }
        elseif ($type==='quote') { if(!email_part_hidden($b,'content')) $piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'text-align:'.$align.'"><div style="border-left:4px solid '.css_value($s,'accent','#31b4e7').';padding:4px 0 4px 18px;font-family:'.$font.';font-size:'.$size.';font-style:italic;font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.';color:'.$color.'">'.$content.'</div></div>'; }
        elseif ($type==='card') { $title=$b['title']??'Título';$text=$b['text']??'';$href=e($b['href']??'#');$label=e(strip_tags($b['button']??'VER MÁS'));$textFont=email_font_stack($s,'textFontFamily','Ubuntu Sans');$buttonFont=email_font_stack($s,'buttonFontFamily','Ubuntu Sans');$inside='';if(!email_part_hidden($b,'title'))$inside.='<h3 style="margin:0 0 10px;font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.';color:'.$color.'">'.$title.'</h3>';if(!email_part_hidden($b,'text'))$inside.='<div style="font-family:'.$textFont.';font-size:'.css_value($s,'textSize','16px').';font-weight:'.css_value($s,'textWeight','400').';line-height:'.css_value($s,'textLineHeight','1.6').';letter-spacing:'.css_value($s,'textLetterSpacing','0px').';color:'.css_value($s,'textColor','#516579').'">'.$text.'</div>';if(!email_part_hidden($b,'button'))$inside.='<p style="margin:18px 0 0"><a href="'.$href.'" style="background:'.css_value($s,'buttonBg','#0074c7').';color:'.css_value($s,'buttonColor','#ffffff').';text-decoration:none;padding:11px 16px;border-radius:'.css_value($s,'buttonRadius','4px').';display:inline-block;font-family:'.$buttonFont.';font-size:'.css_value($s,'buttonSize','14px').';font-weight:'.css_value($s,'buttonWeight','700').';line-height:'.css_value($s,'buttonLineHeight','1.2').';letter-spacing:'.css_value($s,'buttonLetterSpacing','0px').'">'.$label.'</a></p>';if($inside!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'"><div style="border:1px solid '.css_value($s,'border','#dce5ec').';border-radius:'.$radius.';padding:24px;background:'.css_value($s,'cardBg','#ffffff').'">'.$inside.'</div></div>'; }
        elseif ($type==='footer') {
            $inside='';
            if(!email_part_hidden($b,'content'))$inside.=$content;
            $links=[];
            $showPrivacy=!array_key_exists('showPrivacy',$b)||!empty($b['showPrivacy']);
            $showUnsub=!array_key_exists('showUnsubscribe',$b)||!empty($b['showUnsubscribe']);
            if($showPrivacy){
                $privacy=(string)($b['privacyUrl']??app_url('privacy.php'));
                $links[]='<a href="'.e($privacy).'" style="color:inherit;text-decoration:underline">'.e((string)($b['privacyLabel']??'Política de Privacidad')).'</a>';
            }
            if($showUnsub)$links[]='<a href="{{unsubscribe_url}}" style="color:inherit;text-decoration:underline">'.e((string)($b['unsubscribeLabel']??'Darse de baja')).'</a>';
            if($links)$inside.='<div style="margin-top:9px">'.implode(' &nbsp;·&nbsp; ',$links).'</div>';
            if($inside!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'color:'.$color.';text-align:'.$align.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.$inside.'</div>';
        }
        elseif ($type==='iconbox') { $inside='';$iconFont=email_font_stack($s,'fontFamily','Exo 2');$textFont=email_font_stack($s,'textFontFamily','Ubuntu Sans');if(!email_part_hidden($b,'icon'))$inside.='<td width="68" valign="top"><div style="width:52px;height:52px;line-height:52px;text-align:center;border-radius:12px;background:'.css_value($s,'iconBg','#eaf6fd').';color:'.css_value($s,'iconColor','#0074c7').';font-family:'.$iconFont.';font-size:'.css_value($s,'iconSize','26px').';font-weight:800">'.e(strip_tags((string)($b['icon']??'★'))).'</div></td>'; $copy='';if(!email_part_hidden($b,'title'))$copy.='<h3 style="margin:0 0 8px;color:'.$color.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.($b['title']??'').'</h3>';if(!email_part_hidden($b,'text'))$copy.='<div style="color:'.css_value($s,'textColor','#516579').';font-family:'.$textFont.';font-size:'.css_value($s,'textSize','15px').';font-weight:'.css_value($s,'textWeight','400').';line-height:'.css_value($s,'textLineHeight','1.55').';letter-spacing:'.css_value($s,'textLetterSpacing','0px').'">'.($b['text']??'').'</div>';if($copy!=='')$inside.='<td valign="top">'.$copy.'</td>';if($inside!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:'.css_value($s,'cardBg','#f8fbfd').';border:1px solid '.css_value($s,'border','#dce8f0').';border-radius:'.$radius.'"><tr><td style="padding:22px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'.$inside.'</tr></table></td></tr></table></div>'; }
        elseif ($type==='video') { $inside='';$thumb=email_safe_asset_url((string)($b['thumbnail']??''));$href=e($b['href']??'#');if($thumb&&!email_part_hidden($b,'image')){$imgW=email_scaled_dimension('100%',email_element_scale($s),'100%');$inside.='<a href="'.$href.'" style="text-decoration:none"><img src="'.$thumb.'" alt="Video" style="display:block;width:'.$imgW.';max-width:100%;height:auto;border-radius:'.$radius.'"></a>';}if(!email_part_hidden($b,'title'))$inside.='<h3 style="margin:18px 0 8px;color:'.$color.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.($b['title']??'').'</h3>';if(!email_part_hidden($b,'text'))$inside.='<div style="color:'.css_value($s,'textColor','#516579').';font-family:Arial,sans-serif;font-size:'.css_value($s,'textSize','15px').';font-weight:'.css_value($s,'textWeight','400').';line-height:'.css_value($s,'textLineHeight','1.55').';letter-spacing:'.css_value($s,'textLetterSpacing','0px').'">'.($b['text']??'').'</div>';if(!email_part_hidden($b,'button'))$inside.='<p style="margin:16px 0 0"><a href="'.$href.'" style="display:inline-block;background:'.css_value($s,'buttonBg','#0074c7').';color:'.css_value($s,'buttonColor','#fff').';text-decoration:none;padding:11px 18px;border-radius:'.css_value($s,'buttonRadius','999px').';font-family:Arial,sans-serif;font-size:'.css_value($s,'buttonSize','13px').';font-weight:'.css_value($s,'buttonWeight','700').';line-height:'.css_value($s,'buttonLineHeight','1.2').';letter-spacing:'.css_value($s,'buttonLetterSpacing','0px').'">▶ '.e(strip_tags((string)($b['button']??'REPRODUCIR VIDEO'))).'</a></p>';if($inside!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'text-align:'.$align.'">'.$inside.'</div>'; }
        elseif ($type==='product') { $inside='';$href=e($b['href']??'#');$src=email_safe_asset_url((string)($b['src']??''));if($src&&!email_part_hidden($b,'image')){$imgW=email_scaled_dimension('100%',email_element_scale($s),'100%');$inside.='<img src="'.$src.'" alt="Producto" style="display:block;width:'.$imgW.';max-width:100%;height:auto;border-radius:'.$radius.'">';}if(!email_part_hidden($b,'title'))$inside.='<h3 style="margin:18px 0 7px;color:'.$color.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.($b['name']??'').'</h3>';if(!email_part_hidden($b,'price'))$inside.='<strong style="color:'.css_value($s,'priceColor','#ff5300').';font-family:'.$font.';font-size:'.css_value($s,'priceSize','24px').';font-weight:'.css_value($s,'priceWeight','800').';line-height:'.css_value($s,'priceLineHeight','1.1').';letter-spacing:'.css_value($s,'priceLetterSpacing','0px').'">'.e(strip_tags((string)($b['price']??''))).'</strong>';if(!email_part_hidden($b,'oldPrice')&&!empty($b['oldPrice']))$inside.=' <span style="color:#94a3b8;text-decoration:line-through;font-family:'.email_font_stack($s,'oldPriceFontFamily','Ubuntu Sans').';font-size:'.css_value($s,'oldPriceSize','14px').';font-weight:'.css_value($s,'oldPriceWeight','400').';line-height:'.css_value($s,'oldPriceLineHeight','1.2').';letter-spacing:'.css_value($s,'oldPriceLetterSpacing','0px').'">'.e(strip_tags((string)$b['oldPrice'])).'</span>';if(!email_part_hidden($b,'text'))$inside.='<div style="margin-top:10px;color:'.css_value($s,'textColor','#516579').';font-family:'.email_font_stack($s,'textFontFamily','Ubuntu Sans').';font-size:'.css_value($s,'textSize','15px').';font-weight:'.css_value($s,'textWeight','400').';line-height:'.css_value($s,'textLineHeight','1.55').';letter-spacing:'.css_value($s,'textLetterSpacing','0px').'">'.($b['text']??'').'</div>';if(!email_part_hidden($b,'button'))$inside.='<p style="margin:16px 0 0"><a href="'.$href.'" style="display:inline-block;background:'.css_value($s,'buttonBg','#0074c7').';color:'.css_value($s,'buttonColor','#fff').';text-decoration:none;padding:12px 19px;border-radius:'.css_value($s,'buttonRadius','999px').';font-family:'.email_font_stack($s,'buttonFontFamily','Ubuntu Sans').';font-size:'.css_value($s,'buttonSize','13px').';font-weight:'.css_value($s,'buttonWeight','700').';line-height:'.css_value($s,'buttonLineHeight','1.2').';letter-spacing:'.css_value($s,'buttonLetterSpacing','0px').'">'.e(strip_tags((string)($b['button']??'COMPRAR'))).'</a></p>';if($inside!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'"><div style="padding:18px;background:'.css_value($s,'cardBg','#fff').';border:1px solid '.css_value($s,'border','#dce8f0').';border-radius:'.$radius.'">'.$inside.'</div></div>'; }
        elseif ($type==='testimonial') { $inside='';if(!email_part_hidden($b,'stars')){$n=max(0,min(5,(int)($b['stars']??5)));$inside.='<div style="color:'.css_value($s,'accent','#f6bd00').';font-size:18px;letter-spacing:2px">'.str_repeat('★',$n).str_repeat('☆',5-$n).'</div>';}if(!email_part_hidden($b,'text'))$inside.='<div style="margin:8px 0 16px;color:'.$color.';font-family:'.$font.';font-size:'.$size.';font-style:italic;line-height:'.$line.';letter-spacing:'.$letter.'">'.($b['quote']??'').'</div>';$profile='';$avatar=email_safe_asset_url((string)($b['avatar']??''));if($avatar&&!email_part_hidden($b,'avatar'))$profile.='<td width="76"><img src="'.$avatar.'" alt="" width="64" height="64" style="display:block;width:64px;height:64px;border-radius:50%;object-fit:cover"></td>';$who='';if(!email_part_hidden($b,'author'))$who.='<strong style="display:block;color:#073b68;font-family:Arial,sans-serif;font-size:14px">'.e(strip_tags((string)($b['author']??''))).'</strong>';if(!email_part_hidden($b,'role'))$who.='<span style="color:#7b8e9d;font-family:Arial,sans-serif;font-size:12px">'.e(strip_tags((string)($b['role']??''))).'</span>';if($who!=='')$profile.='<td valign="middle">'.$who.'</td>';if($profile!=='')$inside.='<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'.$profile.'</tr></table>';if($inside!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'"><div style="padding:24px;background:'.css_value($s,'cardBg','#f8fbfd').';border:1px solid '.css_value($s,'border','#dce8f0').';border-radius:'.$radius.'">'.$inside.'</div></div>'; }
        elseif ($type==='stats') { $cells='';for($i=1;$i<=3;$i++){if(email_part_hidden($b,'stat'.$i))continue;$cells.='<td width="33.333%" valign="top" style="padding:6px"><div style="padding:18px;text-align:center;background:'.css_value($s,'cardBg','#f8fbfd').';border:1px solid '.css_value($s,'border','#dce8f0').';border-radius:'.$radius.'"><strong style="display:block;color:'.$color.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.e(strip_tags((string)($b['value'.$i]??''))).'</strong><span style="display:block;margin-top:5px;color:'.css_value($s,'textColor','#6b7f8f').';font-family:Arial,sans-serif;font-size:'.css_value($s,'textSize','12px').';font-weight:600">'.e(strip_tags((string)($b['label'.$i]??''))).'</span></div></td>';}if($cells!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'.$cells.'</tr></table></div>'; }
        elseif ($type==='iconlist') { if(!email_part_hidden($b,'items')){$rows='';$items=preg_split('/\r?\n+/',(string)($b['items']??''),-1,PREG_SPLIT_NO_EMPTY);$iconHtml=email_default_check_icon_html((string)($b['icon']??'✓'),(string)($s['iconBg']??'#eef9e7'),(string)($s['iconColor']??'#73bc25'),25);foreach($items as $item)$rows.='<tr><td width="34" valign="middle" style="padding:5px 8px 5px 0">'.$iconHtml.'</td><td valign="middle" style="padding:5px 0;color:'.$color.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.e(trim($item)).'</td></tr>';$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'.$rows.'</table></div>';} }
        elseif ($type==='banner') { $img=email_safe_background_url((string)($b['src']??''));$op=max(0,min(.9,(float)($s['overlay']??.58)));$bannerBg=email_background_css(array_merge($s,$img!==''?['backgroundImage'=>$b['src']??'','overlayOpacity'=>$s['overlayOpacity']??$s['overlay']??.58,'overlayColor'=>$s['overlayColor']??'#002340']:[]),'#00365f');$inside='';if(!email_part_hidden($b,'title'))$inside.='<h2 style="margin:0 0 10px;color:'.css_value($s,'color','#fff').';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.($b['title']??'').'</h2>';if(!email_part_hidden($b,'text'))$inside.='<div style="color:'.css_value($s,'textColor','#e6f2f8').';font-family:Arial,sans-serif;font-size:'.css_value($s,'textSize','16px').';font-weight:'.css_value($s,'textWeight','400').';line-height:'.css_value($s,'textLineHeight','1.55').';letter-spacing:'.css_value($s,'textLetterSpacing','0px').'">'.($b['text']??'').'</div>';if(!email_part_hidden($b,'button'))$inside.='<p style="margin:18px 0 0"><a href="'.e($b['href']??'#').'" style="display:inline-block;background:'.css_value($s,'buttonBg','#ff5300').';color:'.css_value($s,'buttonColor','#fff').';text-decoration:none;padding:12px 20px;border-radius:'.css_value($s,'buttonRadius','999px').';font-family:Arial,sans-serif;font-size:'.css_value($s,'buttonSize','13px').';font-weight:'.css_value($s,'buttonWeight','700').';line-height:'.css_value($s,'buttonLineHeight','1.2').';letter-spacing:'.css_value($s,'buttonLetterSpacing','0px').'">'.e(strip_tags((string)($b['button']??'CONOCER MÁS'))).'</a></p>';if($inside!=='')$piece='<div style="'.$bannerBg.$heightCss.'padding:'.css_value($s,'padding','54px 38px').';text-align:'.$align.';border-radius:'.$radius.'">'.$inside.'</div>'; }
        elseif ($type==='pricing') { $inside='';if(!email_part_hidden($b,'badge')&&!empty($b['badge']))$inside.='<span style="display:inline-block;background:'.css_value($s,'badgeBg','#ff5300').';color:'.css_value($s,'badgeColor','#fff').';padding:6px 10px;border-radius:999px;font-family:Arial,sans-serif;font-size:10px;font-weight:800">'.e(strip_tags((string)$b['badge'])).'</span>';if(!email_part_hidden($b,'plan'))$inside.='<h3 style="margin:14px 0 8px;color:'.$color.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.($b['plan']??'').'</h3>';if(!email_part_hidden($b,'price'))$inside.='<strong style="color:'.css_value($s,'priceColor','#0074c7').';font-family:'.$font.';font-size:'.css_value($s,'priceSize','34px').';font-weight:'.css_value($s,'priceWeight','800').';line-height:'.css_value($s,'priceLineHeight','1.1').';letter-spacing:'.css_value($s,'priceLetterSpacing','0px').'">'.e(strip_tags((string)($b['price']??''))).'</strong>';if(!email_part_hidden($b,'period'))$inside.='<span style="color:#7b8e9d;font-family:Arial,sans-serif;font-size:13px"> '.e(strip_tags((string)($b['period']??''))).'</span>';if(!email_part_hidden($b,'features')){$features=preg_split('/\r?\n+/',(string)($b['features']??''),-1,PREG_SPLIT_NO_EMPTY);$inside.='<div style="margin:18px 0;text-align:left;color:'.css_value($s,'textColor','#516579').';font-family:Arial,sans-serif;font-size:'.css_value($s,'textSize','15px').';font-weight:'.css_value($s,'textWeight','400').';line-height:'.css_value($s,'textLineHeight','1.5').';letter-spacing:'.css_value($s,'textLetterSpacing','0px').'">';foreach($features as $f)$inside.='<div style="margin:8px 0">✓ '.e(trim($f)).'</div>';$inside.='</div>';}if(!email_part_hidden($b,'button'))$inside.='<p style="margin:16px 0 0"><a href="'.e($b['href']??'#').'" style="display:inline-block;background:'.css_value($s,'buttonBg','#0074c7').';color:'.css_value($s,'buttonColor','#fff').';text-decoration:none;padding:12px 20px;border-radius:'.css_value($s,'buttonRadius','999px').';font-family:Arial,sans-serif;font-size:'.css_value($s,'buttonSize','13px').';font-weight:'.css_value($s,'buttonWeight','700').';line-height:'.css_value($s,'buttonLineHeight','1.2').';letter-spacing:'.css_value($s,'buttonLetterSpacing','0px').'">'.e(strip_tags((string)($b['button']??'COMENZAR'))).'</a></p>';if($inside!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'text-align:'.$align.'"><div style="padding:26px;background:'.css_value($s,'cardBg','#fff').';border:1px solid '.css_value($s,'border','#bcd9e9').';border-radius:'.$radius.'">'.$inside.'</div></div>'; }
        elseif ($type==='qr') { $inside='';if(!email_part_hidden($b,'qr')){$qr='https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=8&data='.rawurlencode((string)($b['data']??''));$qrW=email_scaled_dimension(css_value($s,'qrSize','170px'),email_element_scale($s),'170px');$inside.='<img src="'.e($qr).'" alt="QR" style="display:inline-block;width:'.$qrW.';max-width:100%;height:auto;padding:8px;background:'.css_value($s,'qrBg','#fff').';border-radius:'.$radius.'">';}if(!email_part_hidden($b,'text'))$inside.='<div style="margin-top:10px;color:'.$color.';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.e(strip_tags((string)($b['label']??''))).'</div>';if($inside!=='')$piece='<div style="padding:'.$pad.';'.$bgCss.$heightCss.'text-align:'.$align.'">'.$inside.'</div>'; }
        elseif ($type==='header') { $left='';$src=email_safe_asset_url((string)($b['src']??''));if($src&&!email_part_hidden($b,'logo'))$left='<img src="'.$src.'" alt="'.e($b['alt']??'Logo').'" style="display:block;max-width:100%;width:'.css_value($s,'logoWidth','170px').';height:auto">';$right='';for($i=1;$i<=3;$i++){if(!email_part_hidden($b,'menu'.$i)&&!empty($b['menu'.$i]))$right.='<a href="'.e($b['url'.$i]??'#').'" style="display:inline-block;margin:0 7px;color:'.$color.';text-decoration:none;font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';letter-spacing:'.$letter.'">'.e(strip_tags((string)$b['menu'.$i])).'</a>';}if(!email_part_hidden($b,'button'))$right.='<a href="'.e($b['href']??'#').'" style="display:inline-block;margin-left:8px;padding:10px 15px;background:'.css_value($s,'buttonBg','#ff5300').';color:'.css_value($s,'buttonColor','#fff').';text-decoration:none;border-radius:'.css_value($s,'buttonRadius','999px').';font-family:Arial,sans-serif;font-size:'.css_value($s,'buttonSize','11px').';font-weight:'.css_value($s,'buttonWeight','700').';line-height:'.css_value($s,'buttonLineHeight','1.2').';letter-spacing:'.css_value($s,'buttonLetterSpacing','0px').'">'.e(strip_tags((string)($b['button']??'HABLEMOS'))).'</a>';if($left!==''||$right!=='')$piece='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="'.$bgCss.$heightCss.'border-bottom:1px solid '.css_value($s,'border','#e0e8ee').'"><tr><td valign="middle" style="padding:'.$pad.'">'.$left.'</td><td valign="middle" align="right" style="padding:'.$pad.'">'.$right.'</td></tr></table>'; }
        elseif ($type==='hero') { $img=email_safe_background_url((string)($b['src']??''));$title=$b['title']??'Tu gran mensaje';$text=$b['text']??'';$heroBg=email_background_css(array_merge($s,$img!==''?['backgroundImage'=>$b['src']??'','overlayOpacity'=>$s['overlayOpacity']??.72,'overlayColor'=>$s['overlayColor']??'#002f57']:[]),'#002f57');$textFont=email_font_stack($s,'textFontFamily','Ubuntu Sans');$inside='';if(!email_part_hidden($b,'title'))$inside.='<h1 style="font-family:'.$font.';color:'.css_value($s,'color','#ffffff').';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.';margin:0 0 12px;mso-line-height-rule:exactly">'.$title.'</h1>';if(!email_part_hidden($b,'text'))$inside.='<div style="font-family:'.$textFont.';color:'.css_value($s,'textColor','#ffffff').';font-size:'.css_value($s,'textSize','16px').';font-weight:'.css_value($s,'textWeight','400').';line-height:'.css_value($s,'textLineHeight','1.6').';letter-spacing:'.css_value($s,'textLetterSpacing','0px').'">'.$text.'</div>';if(!email_part_hidden($b,'button')){$inside.='<div style="height:18px;line-height:18px;font-size:1px">&nbsp;</div>'.email_button_html((string)($b['button']??'Ver más'),(string)($b['href']??'#'),$s,$align);} $heroH=email_block_min_height($s);$heroBgAttr=$img!==''?' background="'.$img.'"':'';$heroVmlH=$heroH>0?$heroH:($img!==''?email_outlook_background_height((string)($b['src']??''),680,320):0);$vmlOpen=email_outlook_vml_open($img,(string)($s['background']??'#002f57'),$heroVmlH,680,(string)($s['backgroundSize']??'cover'));$vmlClose=email_outlook_vml_close($img,$heroVmlH);$piece='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td'.$heroBgAttr.' valign="middle" style="'.$heroBg.$heightCss.'padding:'.css_value($s,'padding','48px 30px').';text-align:'.$align.';border-radius:'.$radius.'">'.$vmlOpen.$inside.$vmlClose.'</td></tr></table>'; }
        elseif ($type==='social') { $piece=email_social_block_html($b,$s,$font,$pad,$align,$bgCss,$heightCss,$size,$weight,$line,$letter); }
        elseif ($type==='html') $piece=$heightCss?'<div style="'.$heightCss.'">'.$content.'</div>':$content;
        $out.=email_wrap_block($piece,$s);
    }
    return $out;
}

function email_document_settings(array $blocks): array {
    $doc=[
        'backgroundColor'=>'#eef5f8',
        'backgroundImage'=>'',
        'backgroundSize'=>'cover',
        'backgroundPosition'=>'center top',
        'contentBackground'=>'#ffffff',
        'contentBackgroundImage'=>'',
        'contentWidth'=>680,
        'outerPadding'=>'24px 16px',
        'contentRadius'=>'0px',
    ];
    foreach($blocks as $b){
        if(($b['type']??'')==='__document'){
            $src=is_array($b['settings']??null)?$b['settings']:[];
            foreach($doc as $k=>$v){
                if(array_key_exists($k,$src))$doc[$k]=$src[$k];
            }
            break;
        }
    }
    $doc['contentWidth']=max(480,min(800,(int)($doc['contentWidth']??680)));
    $doc['backgroundColor']=email_hex_color((string)($doc['backgroundColor']??''),'#eef5f8');
    $doc['contentBackground']=email_hex_color((string)($doc['contentBackground']??''),'#ffffff');
    return $doc;
}

function email_developer_footer_html(): string {
    $logo=app_url('assets/socrates-developer.png');
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%;background:#ffffff;border-collapse:collapse">'
        .'<tr><td align="center" style="padding:18px 20px 20px;border-top:1px solid #e3ebf0">'
        .'<div style="font-family:Arial,Helvetica,sans-serif;font-size:9px;font-weight:700;letter-spacing:1.2px;color:#8a9baa;margin:0 0 7px">DESARROLLADO POR</div>'
        .'<a href="https://www.socrates.la/" target="_blank" style="display:inline-block;text-decoration:none">'
        .'<img src="'.$logo.'" width="118" alt="Sócrates Creative Studio" border="0" style="display:block;width:118px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none">'
        .'</a></td></tr></table>';
}

function email_social_icon_badge(string $network,string $shape='circle',int $size=30): string {
    $size=max(22,min(48,$size));
    $data=[
        'instagram'=>['◎','#E1306C'],
        'facebook'=>['f','#1877F2'],
        'linkedin'=>['in','#0A66C2'],
        'website'=>['↗','#0074C7'],
    ];
    [$txt,$bg]=$data[$network]??['•','#0074C7'];
    $radius=$shape==='square'?'5px':($shape==='rounded'?'9px':'50%');
    return '<span style="display:inline-block;width:'.$size.'px;height:'.$size.'px;line-height:'.$size.'px;text-align:center;background:'.$bg.';color:#ffffff;border-radius:'.$radius.';font-family:Arial,Helvetica,sans-serif;font-size:'.max(11,(int)round($size*.45)).'px;font-weight:800">'.$txt.'</span>';
}

function email_social_block_html(array $b,array $s,string $font,string $pad,string $align,string $bgCss,string $heightCss,string $size,string $weight,string $line,string $letter): string {
    $mode=(string)($b['displayMode']??'iconsText');
    if(!in_array($mode,['text','icons','iconsText'],true))$mode='iconsText';
    $shape=(string)($b['iconShape']??'circle');
    if(!in_array($shape,['circle','rounded','square'],true))$shape='circle';
    $iconSize=max(22,min(48,(int)($b['iconSize']??30)));
    $items=[
        'instagram'=>['Instagram',(string)($b['instagram']??'')],
        'facebook'=>['Facebook',(string)($b['facebook']??'')],
        'linkedin'=>['LinkedIn',(string)($b['linkedin']??'')],
        'website'=>['Web',(string)($b['website']??'')],
    ];
    $cells='';
    foreach($items as $key=>[$label,$url]){
        if($url===''||email_part_hidden($b,$key))continue;
        $icon=$mode==='text'?'':email_social_icon_badge($key,$shape,$iconSize);
        $text=$mode==='icons'?'':'<span style="display:inline-block;margin-left:'.($mode==='iconsText'?'7px':'0').';vertical-align:middle;color:'.css_value($s,'color','#0074c7').';font-family:'.$font.';font-size:'.$size.';font-weight:'.$weight.';line-height:'.$line.';letter-spacing:'.$letter.'">'.$label.'</span>';
        $cells.='<td valign="middle" style="padding:5px 8px"><a href="'.e($url).'" style="display:inline-block;text-decoration:none;white-space:nowrap">'.$icon.$text.'</a></td>';
    }
    if($cells==='')return '';
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="'.$align.'" style="padding:'.$pad.';'.$bgCss.$heightCss.'"><table role="presentation" cellpadding="0" cellspacing="0" border="0" align="'.$align.'"><tr>'.$cells.'</tr></table></td></tr></table>';
}

function email_divider_block_html(array $b,array $s,string $pad,string $bgCss,string $heightCss): string {
    $lineColor=css_value($s,'color','#dbe3ea');
    $thickness=max(1,min(8,(int)($b['lineThickness']??1)));
    $enabled=!empty($b['iconEnabled']);
    if(!$enabled)return '<div style="padding:'.$pad.';'.$bgCss.$heightCss.'"><hr style="border:none;border-top:'.$thickness.'px solid '.$lineColor.';margin:0"></div>';
    $pos=(string)($b['iconPosition']??'center');
    if(!in_array($pos,['start','center','end'],true))$pos='center';
    $icon=email_outlook_circle_icon((string)($b['icon']??'◆'),(string)($b['iconBg']??'#ffffff'),(string)($b['iconColor']??'#ff5300'),max(18,min(44,(int)($b['iconSize']??26))));
    $line='<td style="width:100%;font-size:1px;line-height:1px;border-top:'.$thickness.'px solid '.$lineColor.'">&nbsp;</td>';
    $iconCell='<td width="42" align="center" valign="middle" style="padding:0 8px">'.$icon.'</td>';
    if($pos==='start')$row=$iconCell.$line;
    elseif($pos==='end')$row=$line.$iconCell;
    else $row=$line.$iconCell.$line;
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:'.$pad.';'.$bgCss.$heightCss.'"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'.$row.'</tr></table></td></tr></table>';
}

function compile_campaign_html(array $blocks): string {
    $doc=email_document_settings($blocks);
    $out=compile_campaign_blocks($blocks).email_developer_footer_html();

    $bodyBg=$doc['backgroundColor'];
    $shellBg=$doc['contentBackground'];
    $width=(int)$doc['contentWidth'];
    $outerPad=css_safe((string)$doc['outerPadding'],'24px 16px');
    $radius=css_safe((string)$doc['contentRadius'],'0px');

    $bodyImage=email_safe_asset_url((string)($doc['backgroundImage']??''));
    $shellImage=email_safe_asset_url((string)($doc['contentBackgroundImage']??''));
    $bodyImageCss=$bodyImage!==''?'background-image:url(&quot;'.$bodyImage.'&quot;);background-size:'.css_safe((string)($doc['backgroundSize']??'cover'),'cover').';background-position:'.css_safe((string)($doc['backgroundPosition']??'center top'),'center top').';background-repeat:no-repeat;':'';
    $shellImageCss=$shellImage!==''?'background-image:url(&quot;'.$shellImage.'&quot;);background-size:cover;background-position:center top;background-repeat:no-repeat;':'';

    $css='html,body{margin:0!important;padding:0!important;width:100%!important}table,td{mso-table-lspace:0pt!important;mso-table-rspace:0pt!important;border-spacing:0}img{display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;max-width:100%;height:auto}a{text-decoration:none}@media only screen and (max-width:620px){.email-shell{width:100%!important;max-width:100%!important}.email-outer-pad{padding:10px!important}.email-columns-table,.email-columns-table>tbody,.email-columns-table>tbody>tr{width:100%!important}.email-col{display:block!important;width:100%!important;max-width:100%!important;box-sizing:border-box!important}.email-bg-responsive{background-repeat:no-repeat!important}.email-bg-mobile-cover{background-size:cover!important;background-position:center center!important}.email-bg-mobile-contain{background-size:contain!important;background-position:center center!important}.email-bg-mobile-width{background-size:100% auto!important;background-position:center top!important}}';

    return '<!doctype html><html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="X-UA-Compatible" content="IE=edge"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@300;400;500;600;700;800;900&family=Ubuntu+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"><!--[if mso]><style>body,table,td,a,h1,h2,h3,p,div{font-family:Arial,Helvetica,sans-serif!important}</style><![endif]--><style>'.$css.'</style></head>'
        .'<body bgcolor="'.$bodyBg.'" style="margin:0!important;padding:0!important;background-color:'.$bodyBg.';'.$bodyImageCss.'width:100%!important">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="'.$bodyBg.'" style="width:100%;background-color:'.$bodyBg.';'.$bodyImageCss.'"><tr>'
        .'<td class="email-outer-pad" align="center" valign="top" style="padding:'.$outerPad.'">'
        .'<!--[if mso]><table role="presentation" width="'.$width.'" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->'
        .'<table role="presentation" class="email-shell" width="'.$width.'" cellpadding="0" cellspacing="0" border="0" bgcolor="'.$shellBg.'" style="width:100%;max-width:'.$width.'px;background-color:'.$shellBg.';'.$shellImageCss.'border-collapse:separate;border-radius:'.$radius.';overflow:hidden"><tr><td valign="top">'.$out.'</td></tr></table>'
        .'<!--[if mso]></td></tr></table><![endif]-->'
        .'</td></tr></table></body></html>';
}

function tracking_pixel_url(string $token): string { return app_url('tracking/open.php?t='.urlencode($token)); }
function campaign_selected_list_ids(int $campaignId, ?int $legacyListId=null): array {
    try {
        $st=db()->prepare('SELECT list_id FROM campaign_lists WHERE campaign_id=? ORDER BY list_id');
        $st->execute([$campaignId]);
        $ids=array_values(array_unique(array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN))));
        if($ids) return $ids;
    } catch (Throwable $e) {}
    return $legacyListId ? [(int)$legacyListId] : [];
}
function campaign_audience_count(int $campaignId,int $tenantId,?int $legacyListId=null): int {
    $ids=campaign_selected_list_ids($campaignId,$legacyListId);
    $sql='SELECT COUNT(*) FROM contacts WHERE tenant_id=? AND status="active"';$params=[$tenantId];
    if($ids){$sql.=' AND list_id IN ('.implode(',',array_fill(0,count($ids),'?')).')';$params=array_merge($params,$ids);}
    $st=db()->prepare($sql);$st->execute($params);return (int)$st->fetchColumn();
}
function campaign_audience_contacts(int $campaignId,int $tenantId,?int $legacyListId=null): PDOStatement {
    $ids=campaign_selected_list_ids($campaignId,$legacyListId);
    $sql='SELECT id,email FROM contacts WHERE tenant_id=? AND status="active"';$params=[$tenantId];
    if($ids){$sql.=' AND list_id IN ('.implode(',',array_fill(0,count($ids),'?')).')';$params=array_merge($params,$ids);}
    $sql.=' ORDER BY id';$st=db()->prepare($sql);$st->execute($params);return $st;
}
function campaign_audience_label(int $campaignId,?int $legacyListId=null): string {
    $ids=campaign_selected_list_ids($campaignId,$legacyListId);
    if(!$ids) return 'Todos los contactos activos';
    $st=db()->prepare('SELECT name FROM contact_lists WHERE id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY name');$st->execute($ids);
    $names=$st->fetchAll(PDO::FETCH_COLUMN);return $names?implode(' · ',$names):'Listas seleccionadas';
}
function campaign_metrics(int $campaignId): array {
    $st=db()->prepare('SELECT COUNT(*) total, SUM(status IN("sent","delivered")) sent, SUM(status="delivered") delivered, SUM(first_open_at IS NOT NULL) unique_opens, COALESCE(SUM(opens),0) total_opens, SUM(clicks>0) unique_clicks, COALESCE(SUM(clicks),0) total_clicks, SUM(status="bounced") bounced, SUM(status="failed") failed, SUM(status="unsubscribed") unsub FROM deliveries WHERE campaign_id=?');
    $st->execute([$campaignId]);$m=$st->fetch()?:[];
    foreach(['total','sent','delivered','unique_opens','total_opens','unique_clicks','total_clicks','bounced','failed','unsub'] as $k)$m[$k]=(int)($m[$k]??0);
    $m['open_rate']=$m['sent']?round($m['unique_opens']/$m['sent']*100,1):0;
    $m['ctr']=$m['sent']?round($m['unique_clicks']/$m['sent']*100,1):0;
    $m['bounce_rate']=$m['sent']?round($m['bounced']/$m['sent']*100,1):0;
    $m['unsub_rate']=$m['sent']?round($m['unsub']/$m['sent']*100,1):0;
    $m['delivery_rate']=$m['sent']?round($m['delivered']/$m['sent']*100,1):0;
    return $m;
}
function campaign_top_links(int $campaignId,int $limit=10): array {
    $st=db()->prepare('SELECT ce.url,COUNT(*) clicks,COUNT(DISTINCT d.id) people FROM click_events ce JOIN deliveries d ON d.id=ce.delivery_id WHERE d.campaign_id=? GROUP BY ce.url ORDER BY clicks DESC LIMIT '.max(1,min(50,$limit)));
    $st->execute([$campaignId]); return $st->fetchAll();
}
function campaign_activity(int $campaignId): array {
    $st=db()->prepare('SELECT DATE_FORMAT(first_open_at,"%m-%d") label, COUNT(*) value FROM deliveries WHERE campaign_id=? AND first_open_at IS NOT NULL AND first_open_at>=DATE_SUB(NOW(),INTERVAL 14 DAY) GROUP BY DATE(first_open_at) ORDER BY DATE(first_open_at)');
    $st->execute([$campaignId]); return $st->fetchAll();
}

function dashboard_summary(int $tenantId): array {
    $st=db()->prepare('SELECT COUNT(*) FROM campaigns WHERE tenant_id=?');$st->execute([$tenantId]);$campaigns=(int)$st->fetchColumn();
    $st=db()->prepare('SELECT COUNT(*) FROM contacts WHERE tenant_id=?');$st->execute([$tenantId]);$contacts=(int)$st->fetchColumn();
    $st=db()->prepare('SELECT COUNT(*) total, SUM(status IN("sent","delivered")) sent, SUM(status="delivered") delivered, SUM(first_open_at IS NOT NULL) unique_opens, SUM(clicks>0) unique_clicks, SUM(status="bounced") bounced, SUM(status="unsubscribed") unsub FROM deliveries WHERE tenant_id=?');$st->execute([$tenantId]);$m=$st->fetch()?:[];
    foreach(['total','sent','delivered','unique_opens','unique_clicks','bounced','unsub'] as $k)$m[$k]=(int)($m[$k]??0);
    $m['campaigns']=$campaigns;$m['contacts']=$contacts;
    $m['open_rate']=$m['sent']?round($m['unique_opens']/$m['sent']*100,1):0;
    $m['ctr']=$m['sent']?round($m['unique_clicks']/$m['sent']*100,1):0;
    $m['bounce_rate']=$m['total']?round($m['bounced']/$m['total']*100,1):0;
    $m['unsub_rate']=$m['sent']?round($m['unsub']/$m['sent']*100,1):0;
    $m['delivery_rate']=$m['sent']?round($m['delivered']/$m['sent']*100,1):0;
    $q=db()->prepare('SELECT SUM(status="sent") sent_campaigns FROM campaigns WHERE tenant_id=?');$q->execute([$tenantId]);$m['sent_campaigns']=(int)($q->fetchColumn()?:0);
    $m['campaign_sent_rate']=$campaigns?round($m['sent_campaigns']/$campaigns*100,1):0;
    return $m;
}
function dashboard_series(int $tenantId,int $days=14): array {
    $days=max(7,min(31,$days));$start=(new DateTimeImmutable('today'))->modify('-'.($days-1).' days');
    $rows=[];for($i=0;$i<$days;$i++){ $d=$start->modify('+'.$i.' days');$key=$d->format('Y-m-d');$rows[$key]=['date'=>$key,'label'=>$d->format('d/m'),'sent'=>0,'opens'=>0,'clicks'=>0]; }
    $st=db()->prepare('SELECT DATE(sent_at) d,COUNT(*) n FROM deliveries WHERE tenant_id=? AND sent_at>=DATE_SUB(CURDATE(),INTERVAL '.($days-1).' DAY) GROUP BY DATE(sent_at)');$st->execute([$tenantId]);foreach($st as $r)if(isset($rows[$r['d']]))$rows[$r['d']]['sent']=(int)$r['n'];
    $st=db()->prepare('SELECT DATE(first_open_at) d,COUNT(*) n FROM deliveries WHERE tenant_id=? AND first_open_at>=DATE_SUB(CURDATE(),INTERVAL '.($days-1).' DAY) GROUP BY DATE(first_open_at)');$st->execute([$tenantId]);foreach($st as $r)if(isset($rows[$r['d']]))$rows[$r['d']]['opens']=(int)$r['n'];
    $st=db()->prepare('SELECT DATE(last_click_at) d,COUNT(*) n FROM deliveries WHERE tenant_id=? AND last_click_at>=DATE_SUB(CURDATE(),INTERVAL '.($days-1).' DAY) GROUP BY DATE(last_click_at)');$st->execute([$tenantId]);foreach($st as $r)if(isset($rows[$r['d']]))$rows[$r['d']]['clicks']=(int)$r['n'];
    return array_values($rows);
}
function sparkline_points(array $values,int $w=640,int $h=190,int $pad=18): string {
    if(!$values)return '';$max=max(1,max($values));$n=count($values);$step=$n>1?($w-$pad*2)/($n-1):0;$pts=[];
    foreach($values as $i=>$v){$x=$pad+$i*$step;$y=$h-$pad-((float)$v/$max)*($h-$pad*2);$pts[]=round($x,1).','.round($y,1);}return implode(' ',$pts);
}

function secret_key_bytes(): string {
    return hash('sha256', APP_KEY, true);
}
function encrypt_secret(?string $plain): string {
    $plain=(string)$plain;
    if($plain==='') return '';
    if(str_starts_with($plain,'enc:')) return $plain;
    if(function_exists('sodium_crypto_secretbox')){
        $nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher=sodium_crypto_secretbox($plain,$nonce,secret_key_bytes());
        return 'enc:sodium:'.base64_encode($nonce.$cipher);
    }
    if(function_exists('openssl_encrypt')){
        $iv=random_bytes(12);$tag='';
        $cipher=openssl_encrypt($plain,'aes-256-gcm',secret_key_bytes(),OPENSSL_RAW_DATA,$iv,$tag,'EMAILSEND');
        if($cipher!==false) return 'enc:gcm:'.base64_encode($iv.$tag.$cipher);
    }
    return $plain;
}
function decrypt_secret(?string $stored): string {
    $stored=(string)$stored;
    if($stored==='' || !str_starts_with($stored,'enc:')) return $stored; // backwards compatible with v0.4 plaintext
    try{
        if(str_starts_with($stored,'enc:sodium:') && function_exists('sodium_crypto_secretbox_open')){
            $raw=base64_decode(substr($stored,11),true);if($raw===false)return '';
            $n=SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;$nonce=substr($raw,0,$n);$cipher=substr($raw,$n);
            $plain=sodium_crypto_secretbox_open($cipher,$nonce,secret_key_bytes());return $plain===false?'':$plain;
        }
        if(str_starts_with($stored,'enc:gcm:') && function_exists('openssl_decrypt')){
            $raw=base64_decode(substr($stored,8),true);if($raw===false)return '';
            $iv=substr($raw,0,12);$tag=substr($raw,12,16);$cipher=substr($raw,28);
            $plain=openssl_decrypt($cipher,'aes-256-gcm',secret_key_bytes(),OPENSSL_RAW_DATA,$iv,$tag,'EMAILSEND');return $plain===false?'':$plain;
        }
    }catch(Throwable $e){}
    return '';
}
function email_domain(?string $email): string {
    $email=strtolower(trim((string)$email));$pos=strrpos($email,'@');return $pos===false?'':substr($email,$pos+1);
}
function dns_txt_records(string $host): array {
    if(!function_exists('dns_get_record') || !$host) return [];
    try{$records=@dns_get_record($host,DNS_TXT);$out=[];foreach($records?:[] as $r){$txt=$r['txt']??($r['entries'][0]??'');if($txt!=='')$out[]=$txt;}return $out;}catch(Throwable $e){return [];}
}
function normalize_dkim_selector(string $selector,string $domain=''): string {
    $selector=strtolower(trim($selector));
    $selector=rtrim($selector,'.');
    if($selector==='')return '';
    $domain=strtolower(trim($domain));
    if($domain!==''){
        $suffix='._domainkey.'.$domain;
        if(str_ends_with($selector,$suffix))$selector=substr($selector,0,-strlen($suffix));
    }
    if(str_ends_with($selector,'._domainkey'))$selector=substr($selector,0,-strlen('._domainkey'));
    $p=strpos($selector,'._domainkey.');
    if($p!==false)$selector=substr($selector,0,$p);
    return preg_replace('/[^a-z0-9_-]/','',$selector)?:'';
}
function dns_delivery_status(string $domain,string $selector=''): array {
    $result=['domain'=>$domain,'spf'=>['ok'=>false,'value'=>''],'dmarc'=>['ok'=>false,'value'=>''],'dkim'=>['ok'=>false,'value'=>'','selector'=>'','host'=>'','auto'=>false],'dns_available'=>function_exists('dns_get_record')];
    if(!$domain)return $result;
    foreach(dns_txt_records($domain) as $txt) if(stripos($txt,'v=spf1')===0){$result['spf']=['ok'=>true,'value'=>$txt];break;}
    foreach(dns_txt_records('_dmarc.'.$domain) as $txt) if(stripos($txt,'v=DMARC1')===0){$result['dmarc']=['ok'=>true,'value'=>$txt];break;}
    $normalized=normalize_dkim_selector($selector,$domain);
    $candidates=[];
    if($normalized!=='')$candidates[]=$normalized;
    foreach(['mail','default','selector1','selector2','google','dkim','smtp','s1','s2'] as $candidate)if(!in_array($candidate,$candidates,true))$candidates[]=$candidate;
    foreach($candidates as $candidate){
        $host=$candidate.'._domainkey.'.$domain;
        foreach(dns_txt_records($host) as $txt){
            if(stripos($txt,'v=DKIM1')!==false || preg_match('/(?:^|;)\s*p=/i',$txt)){
                $result['dkim']=['ok'=>true,'value'=>$txt,'selector'=>$candidate,'host'=>$host,'auto'=>($normalized==='' || $candidate!==$normalized)];
                break 2;
            }
        }
    }
    if(!$result['dkim']['ok'] && $normalized!==''){
        $result['dkim']['selector']=$normalized;
        $result['dkim']['host']=$normalized.'._domainkey.'.$domain;
    }
    return $result;
}
function dashboard_campaign_comparison(int $tenantId,int $limit=5): array {
    $limit=max(1,min(10,$limit));
    $st=db()->prepare('SELECT id,name,subject,status,COALESCE(sent_at,created_at) date_ref FROM campaigns WHERE tenant_id=? ORDER BY COALESCE(sent_at,created_at) DESC LIMIT '.$limit);
    $st->execute([$tenantId]);$rows=[];
    foreach($st->fetchAll() as $c){$m=campaign_metrics((int)$c['id']);$c['metrics']=$m;$rows[]=$c;}
    return $rows;
}
