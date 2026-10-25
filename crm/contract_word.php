<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('contracts.view');
$id=(int)($_GET['id']??0);
$stmt=db()->prepare('SELECT ct.*,c.company_name,c.tax_id,c.country,c.city,c.address FROM contracts ct JOIN clients c ON c.id=ct.client_id WHERE ct.id=?');
$stmt->execute([$id]);$contract=$stmt->fetch();
if(!$contract){http_response_code(404);exit('Contrato no encontrado.');}
$base=preg_replace('/[^A-Za-z0-9._-]+/','-',(string)$contract['contract_number'])?:'contrato';
$provider=(string)setting('contract_company_name',crm_company_name());

if(class_exists('ZipArchive')){
    $xmlEscape=static fn(string $v):string=>htmlspecialchars($v,ENT_XML1|ENT_QUOTES,'UTF-8');
    $paras=[];
    $paras[]='<w:p><w:pPr><w:spacing w:after="200"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="002F57"/></w:rPr><w:t>'.$xmlEscape((string)$contract['title']).'</w:t></w:r></w:p>';
    $paras[]='<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>'.$xmlEscape((string)$contract['contract_number'].' · '.$contract['company_name']).'</w:t></w:r></w:p>';
    $paras[]='<w:p><w:r><w:rPr><w:color w:val="657B8C"/></w:rPr><w:t>'.$xmlEscape($provider.' · '.format_date($contract['issue_date'])).'</w:t></w:r></w:p>';
    foreach(preg_split('/\R/u',(string)$contract['content'])?:[] as $line){
        if(trim($line)===''){$paras[]='<w:p/>';continue;}
        $bold=preg_match('/^(?:\d+\.|[A-ZÁÉÍÓÚÑÜ ]{5,}:|PRIMERO|SEGUNDO|TERCERO|CUARTO|QUINTO|SEXTO|SÉPTIMO|OCTAVO|NOVENO|DÉCIMO)/u',trim($line))===1;
        $paras[]='<w:p><w:pPr><w:spacing w:after="110"/></w:pPr><w:r>'.($bold?'<w:rPr><w:b/></w:rPr>':'').'<w:t xml:space="preserve">'.$xmlEscape($line).'</w:t></w:r></w:p>';
    }
    $document='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.implode('',$paras).'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr></w:body></w:document>';
    $contentTypes='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    $rels='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    $core='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>'.$xmlEscape((string)$contract['title']).'</dc:title><dc:creator>SÓCRATES CRM</dc:creator><dc:subject>'.$xmlEscape((string)$contract['contract_number']).'</dc:subject><dcterms:created xsi:type="dcterms:W3CDTF">'.gmdate('Y-m-d\TH:i:s\Z').'</dcterms:created></cp:coreProperties>';
    $app='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>SÓCRATES CRM</Application></Properties>';
    $tmp=tempnam(sys_get_temp_dir(),'socdoc_');$zip=new ZipArchive();
    if($tmp!==false&&$zip->open($tmp,ZipArchive::OVERWRITE)===true){
        $zip->addFromString('[Content_Types].xml',$contentTypes);$zip->addFromString('_rels/.rels',$rels);$zip->addFromString('word/document.xml',$document);$zip->addFromString('docProps/core.xml',$core);$zip->addFromString('docProps/app.xml',$app);$zip->close();
        $bytes=file_get_contents($tmp);@unlink($tmp);
        if($bytes!==false){header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');header('Content-Disposition: attachment; filename="'.$base.'.docx"');header('Content-Length: '.strlen($bytes));echo $bytes;exit;}
    }
}
$html='<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#183247}h1{color:#002f57;border-bottom:3px solid #ff5300;padding-bottom:10px}pre{font-family:Arial,sans-serif;white-space:pre-wrap}</style></head><body><small>'.e($provider).'</small><h1>'.e($contract['title']).'</h1><p><strong>'.e($contract['contract_number']).'</strong> · '.e($contract['company_name']).'</p><pre>'.e($contract['content']).'</pre></body></html>';
header('Content-Type: application/msword; charset=UTF-8');header('Content-Disposition: attachment; filename="'.$base.'.doc"');echo $html;exit;
