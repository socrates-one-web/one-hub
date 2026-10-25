<?php

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/document_manager.php';

require_permission('documents.view');
$id=(int)($_GET['id']??0);
$d=dms_require_document($id);
$versionId=(int)($_GET['version']??0);
if($versionId){
    $st=db()->prepare('SELECT * FROM document_versions WHERE id=? AND document_id=?');
    $st->execute([$versionId,$id]);
    $v=$st->fetch();
}else{
    $v=dms_latest_version($id);
}
if(!$v){http_response_code(404);exit('Archivo no disponible.');}
$path=dms_safe_path((string)$v['stored_path']);
if(!is_file($path)){http_response_code(404);exit('Archivo físico no disponible.');}
$name=(string)($v['original_name']??'documento');
$mime=(string)($v['mime_type']??'application/octet-stream');
$mode=dms_preview_mode($name,$mime);
dms_audit($id,'preview','Vista previa v'.($v['version_no']??'').' · '.$mode);

// Preview is embedded only by CRM itself. Restrict frame ancestors to the same origin.
// Must override crm_security_headers() CSP (frame-ancestors 'none').
header_remove('Content-Security-Policy');
header("Content-Security-Policy: default-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'none'");
header_remove('X-Frame-Options');
header("X-Frame-Options: SAMEORIGIN");
header('Referrer-Policy: same-origin');
header('Cache-Control: private, max-age=120');

if($mode==='image'){
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($path));
    header("Content-Disposition: inline; filename*=UTF-8''".rawurlencode($name));
    readfile($path);exit;
}
if($mode==='pdf'){
    header('Content-Type: application/pdf');
    header('Accept-Ranges: bytes');
    header('Content-Length: '.filesize($path));
    header("Content-Disposition: inline; filename*=UTF-8''".rawurlencode($name));
    readfile($path);exit;
}

header("Content-Security-Policy: default-src 'self' 'unsafe-inline' data: blob:; img-src 'self' data: blob:; frame-ancestors 'self'; object-src 'none'; base-uri 'none'");
function pv_h(string $s): string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function pv_utf8(string $s): string{
    if($s==='')return '';
    if(function_exists('mb_check_encoding')&&mb_check_encoding($s,'UTF-8'))return $s;
    if(@preg_match('//u',$s)===1)return $s;
    if(function_exists('mb_detect_encoding')){
        $enc=mb_detect_encoding($s,['UTF-8','Windows-1252','ISO-8859-1','ASCII'],true);
        if($enc&&strtoupper($enc)!=='UTF-8')return mb_convert_encoding($s,'UTF-8',$enc);
    }
    return iconv('Windows-1252','UTF-8//IGNORE',$s)?:$s;
}
function pv_read_limited(string $path,int $max=1572864): array{
    $size=filesize($path)?:0;
    $fh=fopen($path,'rb'); if(!$fh)return ['',false];
    $data=(string)fread($fh,$max); fclose($fh);
    return [pv_utf8($data),$size>$max];
}
function pv_zip_open(string $path): ?array{
    if(class_exists('ZipArchive')){
        $z=new ZipArchive();
        if($z->open($path)===true)return ['type'=>'zip','obj'=>$z,'path'=>$path];
    }
    if(class_exists('PharData')){
        try{return ['type'=>'phar','obj'=>new PharData($path),'path'=>$path];}catch(Throwable){}
    }
    return null;
}
function pv_zip_get(array $h,string $name): ?string{
    if(($h['type']??'')==='zip'){
        $v=$h['obj']->getFromName($name);return $v===false?null:(string)$v;
    }
    try{
        $p=$h['obj'];if(isset($p[$name])&&!$p[$name]->isDir())return (string)$p[$name]->getContent();
    }catch(Throwable){}
    return null;
}
function pv_zip_names(array $h): array{
    $out=[];
    if(($h['type']??'')==='zip'){
        for($i=0;$i<$h['obj']->numFiles;$i++){$n=$h['obj']->getNameIndex($i);if(is_string($n))$out[]=$n;}
        return $out;
    }
    try{
        $prefix='phar://'.str_replace('\\','/',(string)$h['path']).'/';
        foreach(new RecursiveIteratorIterator($h['obj']) as$f){$pn=str_replace('\\','/',(string)$f->getPathName());if(str_starts_with($pn,$prefix))$out[]=substr($pn,strlen($prefix));}
    }catch(Throwable){}
    return $out;
}
function pv_zip_stat(array $h,string $name): array{
    if(($h['type']??'')==='zip'){
        $idx=$h['obj']->locateName($name);if($idx!==false){$s=$h['obj']->statIndex($idx);return is_array($s)?$s:[];}
        return [];
    }
    try{$p=$h['obj'];if(isset($p[$name]))return ['name'=>$name,'size'=>(int)$p[$name]->getSize()];}catch(Throwable){}
    return [];
}
function pv_zip_close(?array $h): void{if($h&&($h['type']??'')==='zip')$h['obj']->close();}
function pv_xml_text(string $xml,array $paragraphClosers=[]): string{
    foreach($paragraphClosers as$tag)$xml=str_replace($tag,"\n",$xml);
    $xml=preg_replace('~<(w:tab|a:tab)[^>]*/>~i',"\t",$xml)??$xml;
    $xml=preg_replace('~<(w:br|a:br)[^>]*/>~i',"\n",$xml)??$xml;
    return trim(pv_utf8(html_entity_decode(strip_tags($xml),ENT_QUOTES|ENT_XML1,'UTF-8')));
}
function pv_docx(string $path): ?string{
    $z=pv_zip_open($path); if(!$z)return null;
    $xml=pv_zip_get($z,'word/document.xml'); pv_zip_close($z);
    return $xml===null?null:pv_xml_text($xml,['</w:p>','</w:tr>']);
}
function pv_pptx(string $path): ?array{
    $z=pv_zip_open($path); if(!$z)return null;
    $slides=[];
    foreach(pv_zip_names($z) as$n){if(preg_match('~^ppt/slides/slide(\d+)\.xml$~',$n,$m))$slides[(int)$m[1]]=$n;}
    ksort($slides,SORT_NUMERIC);$out=[];
    foreach(array_slice($slides,0,80,true) as$num=>$n){$xml=pv_zip_get($z,$n);if($xml!==null)$out[$num]=pv_xml_text($xml,['</a:p>']);}
    pv_zip_close($z);return $out;
}
function pv_col_index(string $ref): int{
    if(!preg_match('/^([A-Z]+)/i',$ref,$m))return 0;$letters=strtoupper($m[1]);$n=0;
    for($i=0;$i<strlen($letters);$i++)$n=$n*26+(ord($letters[$i])-64);
    return max(0,$n-1);
}
function pv_xlsx(string $path): ?array{
    $z=pv_zip_open($path); if(!$z)return null;
    $shared=[];$ss=pv_zip_get($z,'xl/sharedStrings.xml');
    if($ss!==null && preg_match_all('~<si[^>]*>(.*?)</si>~s',$ss,$mm)){
        foreach($mm[1] as$si){preg_match_all('~<t(?:\s[^>]*)?>(.*?)</t>~s',$si,$tm);$shared[]=pv_utf8(html_entity_decode(implode('',$tm[1]??[]),ENT_QUOTES|ENT_XML1,'UTF-8'));}
    }
    $sheetName='xl/worksheets/sheet1.xml';$xml=pv_zip_get($z,$sheetName);
    if($xml===null){foreach(pv_zip_names($z) as$n){if(preg_match('~^xl/worksheets/sheet\d+\.xml$~',$n)){$xml=pv_zip_get($z,$n);break;}}}
    pv_zip_close($z);if($xml===null)return null;
    $rows=[];
    if(preg_match_all('~<row[^>]*>(.*?)</row>~s',$xml,$rm)){
        foreach(array_slice($rm[1],0,250) as$rxml){$row=[];
            if(preg_match_all('~<c\b([^>]*)>(.*?)</c>~s',$rxml,$cm,PREG_SET_ORDER)){
                foreach($cm as$c){$attrs=$c[1];$body=$c[2];$ref='';$type='';
                    if(preg_match('~\br="([^"]+)"~',$attrs,$m))$ref=$m[1];
                    if(preg_match('~\bt="([^"]+)"~',$attrs,$m))$type=$m[1];
                    $idx=pv_col_index($ref);if($idx>49)continue;$val='';
                    if($type==='inlineStr'){preg_match_all('~<t(?:\s[^>]*)?>(.*?)</t>~s',$body,$tm);$val=implode('',$tm[1]??[]);} elseif(preg_match('~<v>(.*?)</v>~s',$body,$vm)){$val=$vm[1];if($type==='s')$val=$shared[(int)$val]??$val;}
                    $row[$idx]=pv_utf8(html_entity_decode((string)$val,ENT_QUOTES|ENT_XML1,'UTF-8'));
                }
            }
            if($row){ksort($row);$rows[]=$row;}
        }
    }
    return $rows;
}
function pv_zip_list(string $path): ?array{
    $z=pv_zip_open($path);if(!$z)return null;$out=[];
    foreach(array_slice(pv_zip_names($z),0,400) as$n){$st=pv_zip_stat($z,$n);$out[]=['name'=>$n,'size'=>(int)($st['size']??0)];}
    pv_zip_close($z);return $out;
}
function pv_format_bytes(int $b): string{if($b>=1048576)return number_format($b/1048576,1,',','.').' MB';if($b>=1024)return number_format($b/1024,0,',','.').' KB';return $b.' B';}
function pv_shell_start(string $name,string $label): void{
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.pv_h($name).'</title><style>';
    echo ':root{color-scheme:light dark;--bg:#f3f7fa;--card:#fff;--text:#092e4a;--muted:#657c8d;--line:#d8e4ec;--blue:#0074c7;--cyan:#31b4e7;--orange:#ff5300}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px/1.55 system-ui,-apple-system,Segoe UI,Arial,sans-serif}.bar{position:sticky;top:0;z-index:3;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;background:rgba(255,255,255,.96);border-bottom:1px solid var(--line);backdrop-filter:blur(12px)}.bar b{display:block;font-size:13px}.bar small{color:var(--muted)}.badge{padding:5px 9px;border-radius:999px;background:#e8f5ff;color:var(--blue);font-size:10px;font-weight:800;letter-spacing:.5px}.content{padding:22px}.paper{max-width:1100px;margin:auto;background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:0 12px 34px rgba(0,47,87,.08);overflow:auto}.text{padding:28px 34px;white-space:pre-wrap;word-break:break-word;font:13px/1.65 ui-monospace,SFMono-Regular,Consolas,monospace}.doc{padding:38px 48px;white-space:pre-wrap;word-break:break-word;font:15px/1.7 Georgia,serif}.note{padding:12px 16px;background:#fff6ec;border-bottom:1px solid #f3d3b4;color:#8a4800;font-size:12px}.empty{padding:80px 26px;text-align:center}.empty strong{font-size:20px}.empty p{color:var(--muted);max-width:560px;margin:10px auto 0}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid var(--line);padding:8px 10px;vertical-align:top;min-width:90px}tr:nth-child(even){background:rgba(0,116,199,.035)}.slide{padding:30px 34px;border-bottom:1px solid var(--line)}.slide:last-child{border-bottom:0}.slide h3{margin:0 0 12px;color:var(--blue);font-size:12px;text-transform:uppercase;letter-spacing:.8px}.slide pre{white-space:pre-wrap;font:15px/1.6 system-ui;margin:0}.ziplist{list-style:none;margin:0;padding:0}.ziplist li{display:flex;justify-content:space-between;gap:20px;padding:10px 16px;border-bottom:1px solid var(--line)}.ziplist li:last-child{border-bottom:0}.ziplist small{color:var(--muted);white-space:nowrap}@media(prefers-color-scheme:dark){:root{--bg:#07141e;--card:#0c1f2d;--text:#e8f4fb;--muted:#91a9b9;--line:#1d3a4c}.bar{background:rgba(12,31,45,.96)}.badge{background:#0d3550;color:#64c7f0}.note{background:#3b2817;border-color:#644429;color:#ffd2a6}tr:nth-child(even){background:rgba(49,180,231,.04)}}@media(max-width:600px){.content{padding:10px}.doc,.text{padding:20px}.bar{align-items:flex-start}.bar .badge{display:none}}';
    echo '</style></head><body><div class="bar"><div><b>'.pv_h($name).'</b><small>Vista previa segura · '.pv_h($label).'</small></div><span class="badge">SÓCRATES DOCUMENTS</span></div><main class="content"><section class="paper">';
}
function pv_shell_end(): void{echo '</section></main></body></html>';}

try{
    if($mode==='text'){
        [$txt,$truncated]=pv_read_limited($path);pv_shell_start($name,'Texto');
        if($truncated)echo '<div class="note">Vista previa limitada a los primeros 1,5 MB. Descarga el archivo para verlo completo.</div>';
        echo '<pre class="text">'.pv_h($txt).'</pre>';pv_shell_end();exit;
    }
    if($mode==='csv'){
        [$raw,$truncated]=pv_read_limited($path,2097152);$lines=preg_split('/\R/u',$raw)?:[];$sample=implode("\n",array_slice($lines,0,5));
        $delims=[','=>substr_count($sample,','),';'=>substr_count($sample,';'),"\t"=>substr_count($sample,"\t"),'|'=>substr_count($sample,'|')];arsort($delims);$delim=(string)array_key_first($delims);
        pv_shell_start($name,'CSV / tabla');if($truncated)echo '<div class="note">Vista previa limitada. Descarga el CSV para ver todas las filas.</div>';
        echo '<table><tbody>';foreach(array_slice($lines,0,300) as$line){if($line==='')continue;$cols=str_getcsv($line,$delim);echo '<tr>';foreach(array_slice($cols,0,60) as$c)echo '<td>'.pv_h((string)$c).'</td>';echo '</tr>';}echo '</tbody></table>';pv_shell_end();exit;
    }
    if($mode==='docx'){
        $txt=pv_docx($path);pv_shell_start($name,'Microsoft Word');
        if($txt===null)echo '<div class="empty"><strong>No fue posible generar la vista de Word.</strong><p>El visor no pudo abrir la estructura interna del DOCX. El archivo sigue disponible para descarga.</p></div>';else echo '<div class="doc">'.nl2br(pv_h($txt)).'</div>';
        pv_shell_end();exit;
    }
    if($mode==='xlsx'){
        $rows=pv_xlsx($path);pv_shell_start($name,'Microsoft Excel');
        if($rows===null)echo '<div class="empty"><strong>No fue posible generar la vista de Excel.</strong><p>El visor no pudo abrir la estructura interna del XLSX.</p></div>';else{echo '<div class="note">Se muestran hasta 250 filas y 50 columnas de la primera hoja.</div><table><tbody>';foreach($rows as$row){$max=$row?max(array_keys($row)):0;echo '<tr>';for($i=0;$i<=$max;$i++)echo '<td>'.pv_h((string)($row[$i]??'')).'</td>';echo '</tr>';}echo '</tbody></table>';}
        pv_shell_end();exit;
    }
    if($mode==='pptx'){
        $slides=pv_pptx($path);pv_shell_start($name,'Microsoft PowerPoint');
        if($slides===null)echo '<div class="empty"><strong>No fue posible generar la vista de PowerPoint.</strong><p>El visor no pudo abrir la estructura interna del PPTX.</p></div>';else foreach($slides as$num=>$txt)echo '<article class="slide"><h3>Diapositiva '.(int)$num.'</h3><pre>'.pv_h($txt).'</pre></article>';
        pv_shell_end();exit;
    }
    if($mode==='archive'){
        $items=pv_zip_list($path);pv_shell_start($name,'Archivo comprimido');
        if($items===null)echo '<div class="empty"><strong>No fue posible leer el contenido del ZIP.</strong><p>El visor no pudo abrir la estructura interna del ZIP.</p></div>';else{echo '<ul class="ziplist">';foreach($items as$it)echo '<li><span>'.pv_h((string)$it['name']).'</span><small>'.pv_h(pv_format_bytes((int)$it['size'])).'</small></li>';echo '</ul>';}
        pv_shell_end();exit;
    }
    pv_shell_start($name,'Archivo '.strtoupper(dms_file_extension($name)));
    echo '<div class="empty"><strong>Archivo disponible y protegido.</strong><p>Este formato binario no puede representarse de forma segura dentro del navegador. Usa DESCARGAR para abrirlo con su aplicación original. La vista previa ya no muestra un visor roto.</p></div>';
    pv_shell_end();exit;
}catch(Throwable $e){
    http_response_code(200);pv_shell_start($name,'Vista previa');
    echo '<div class="empty"><strong>No fue posible construir la vista previa.</strong><p>El archivo está disponible para descarga. Código de diagnóstico: PREVIEW-'.substr(hash('sha256',$e->getMessage()),0,10).'</p></div>';pv_shell_end();exit;
}
