<?php
declare(strict_types=1);
require_once __DIR__ . '/simple_pdf.php';

function pdf_brand_logo_jpg(): string
{
    return dirname(__DIR__) . '/assets/img/logo-creative-cotizaciones.jpg';
}

function quote_pdf_bytes(array $quote, array $items): string
{
    $pdf = new SimplePdf([
        'Title' => (string)($quote['quotation_number'] ?? 'Cotización'),
        'Author' => 'SÓCRATES ONE · Creative Studio',
        'Subject' => 'Cotización para ' . (string)($quote['company_name'] ?? ''),
        'Creator' => 'SÓCRATES CRM',
    ]);
    $navy=[0,47,87]; $blue=[0,116,199]; $orange=[241,90,36]; $text=[21,49,73]; $muted=[92,116,133]; $line=[217,230,238]; $light=[248,251,253]; $white=[255,255,255];
    $l=quote_labels((string)($quote['language']??'es')); $currency=(string)($quote['currency']??'CLP');
    $left=40; $right=555; $width=$right-$left;

    $header = function(bool $compact=false) use($pdf,$navy,$blue,$orange,$text,$muted,$line,$light,$white,$quote,$l,$left,$right,$width): float {
        $pdf->rect($left,28,$width,4,$blue);
        $pdf->rect($left+$width*.62,28,$width*.38,4,$orange);
        $logo=pdf_brand_logo_jpg();
        if(!$pdf->jpeg($logo,$left+5,40,$compact?128:165,$compact?32:42)){
            $pdf->text($left+5,58,'SOCRATES',22,true,$blue);
            $pdf->text($left+5,72,'AI Creative Studio',8,true,$orange);
        }
        if($compact){
            $pdf->text($right,58,(string)$quote['quotation_number'],13,true,$navy,'right');
            $pdf->line($left,80,$right,80,$line,0.7);
            return 94;
        }
        $pdf->rect($right-160,42,160,70,$navy);
        $pdf->text($right-12,58,crm_upper((string)$l['quotation']),7,false,[190,222,244],'right');
        $pdf->text($right-12,82,(string)$quote['quotation_number'],17,true,$white,'right');
        $pdf->text($right-12,100,format_date((string)$quote['issue_date']),8,false,[207,234,255],'right');
        $pdf->text($left+185,54,'Diseño con alma, impulsado por IA',8,true,$navy);
        $pdf->text($left+185,69,'Creative Studio · www.socrates.la',7,false,$muted);
        return 128;
    };

    $tableHeader = function(float $y) use($pdf,$navy,$white,$l,$left,$width): float {
        $pdf->rect($left,$y,$width,25,$navy);
        $pdf->text($left+9,$y+16,crm_upper((string)$l['description']),7,true,$white);
        $pdf->text($left+345,$y+16,crm_upper((string)$l['quantity']),7,true,$white,'center');
        $pdf->text($left+440,$y+16,crm_upper((string)$l['unit_price']),7,true,$white,'right');
        $pdf->text($left+$width-9,$y+16,crm_upper((string)$l['total']),7,true,$white,'right');
        return $y+25;
    };

    $y=$header(false);
    $clientW=330; $metaX=$left+$clientW+14; $metaW=$width-$clientW-14;
    $pdf->rect($left,$y,$clientW,92,$white,$line,0.6);
    $pdf->text($left+12,$y+17,crm_upper((string)$l['client']),7,true,$muted);
    $pdf->text($left+12,$y+39,(string)$quote['company_name'],15,true,$navy);
    $detailY=$y+55;
    foreach(array_filter([(string)($quote['recipient_name']??''),!empty($quote['tax_id'])?$l['tax_id'].': '.$quote['tax_id']:'',trim(implode(', ',array_filter([(string)($quote['address']??''),(string)($quote['city']??''),(string)($quote['country']??'')]))),(string)($quote['recipient_email']??$quote['client_email']??'')]) as $detail){
        $lines=$pdf->wrap((string)$detail,$clientW-24,7,false);
        foreach(array_slice($lines,0,2) as $lineText){$pdf->text($left+12,$detailY,(string)$lineText,7,false,$muted);$detailY+=11;}
        if($detailY>$y+83)break;
    }
    $pdf->rect($metaX,$y,$metaW,92,$light,$line,0.6);
    $pdf->text($metaX+12,$y+18,crm_upper((string)$l['valid_until']),7,true,$muted);
    $pdf->text($metaX+12,$y+37,format_date((string)($quote['valid_until']??'')),10,true,$navy);
    $pdf->text($metaX+12,$y+58,crm_upper((string)$l['currency']),7,true,$muted);
    $pdf->text($metaX+12,$y+77,$currency,11,true,$navy);
    $y+=112;
    $y=$tableHeader($y);

    foreach($items as $index=>$item){
        $description=(string)($item['description']??'');
        $lines=$pdf->wrap($description,315,8,false);
        $rowH=max(31,14+count($lines)*11);
        if($y+$rowH>730){
            $pdf->addPage();
            $y=$header(true);
            $y=$tableHeader($y);
        }
        $fill=$index%2===0?$white:$light;
        $pdf->rect($left,$y,$width,$rowH,$fill,$line,0.35);
        $pdf->multiText($left+9,$y+16,$lines,315,8,11,false,$text);
        $pdf->text($left+345,$y+18,number_format((float)$item['quantity'],2,',','.'),8,false,$text,'center');
        $pdf->text($left+440,$y+18,format_money($item['unit_price'],$currency),8,false,$text,'right');
        $pdf->text($left+$width-9,$y+18,format_money($item['line_total'],$currency),8,true,$text,'right');
        $y+=$rowH;
    }

    $showPayment=(int)($quote['show_payment_method']??1)===1;
    $blocks=[];
    if(trim((string)($quote['notes']??''))!=='')$blocks[]=[(string)$l['notes'],(string)$quote['notes']];
    if($showPayment&&(trim((string)($quote['payment_method_title']??''))!==''||trim((string)($quote['payment_method_details']??''))!=='')){
        $blocks[]=[(string)$l['payment_method'],trim((string)($quote['payment_method_title']??''))."\n".trim((string)($quote['payment_method_details']??''))];
    }
    if(trim((string)($quote['terms']??''))!=='')$blocks[]=[(string)$l['terms'],(string)$quote['terms']];

    $summaryH=120;
    foreach($blocks as $block){$summaryH+=min(90,count($pdf->wrap($block[1],300,7,false))*10+28);}
    if($y+$summaryH>745){$pdf->addPage();$y=$header(true);}
    $y+=18;
    $extraW=330; $totX=$left+$extraW+18; $totW=$width-$extraW-18;
    foreach($blocks as [$title,$body]){
        $lines=$pdf->wrap((string)$body,$extraW-24,7,false);$h=max(48,26+count($lines)*10);
        $pdf->rect($left,$y,$extraW,$h,$light,$line,0.45);
        $pdf->text($left+12,$y+16,crm_upper((string)$title),7,true,$orange);
        $pdf->multiText($left+12,$y+32,$lines,$extraW-24,7,10,false,$muted);
        $y+=$h+10;
    }
    $totY=$y-(count($blocks)?array_sum(array_map(fn($b)=>max(48,26+count($pdf->wrap((string)$b[1],$extraW-24,7,false))*10)+10,$blocks)):0);
    if(!$blocks)$totY=$y;
    $pdf->rect($totX,$totY,$totW,145,$navy);
    $rows=[[$l['subtotal'],format_money($quote['subtotal'],$currency)]];
    if((float)$quote['discount_amount']>0)$rows[]=[$l['discount'],'- '.format_money($quote['discount_amount'],$currency)];
    if((float)$quote['tax_rate']>0)$rows[]=[$l['tax'].' ('.number_format((float)$quote['tax_rate'],2,',','.').'%)',format_money($quote['tax_amount'],$currency)];
    $ty=$totY+22;
    foreach($rows as [$label,$value]){$pdf->text($totX+13,$ty,(string)$label,8,false,[210,233,249]);$pdf->text($totX+$totW-13,$ty,(string)$value,8,true,$white,'right');$ty+=22;}
    $pdf->line($totX+12,$totY+88,$totX+$totW-12,$totY+88,$orange,1.5);
    $pdf->text($totX+13,$totY+109,(string)$l['grand_total'],11,true,$white);
    $pdf->text($totX+$totW-13,$totY+132,format_money($quote['total'],$currency),14,true,[255,168,106],'right');
    $footerY=max($y,$totY+158);
    if($footerY>785){$pdf->addPage();$footerY=$header(true);}
    $pdf->line($left,$footerY,$right,$footerY,$line,0.6);
    $pdf->text($left,$footerY+18,'SÓCRATES ONE · Creative Studio',8,true,$navy);
    $pdf->text($left,$footerY+31,'socrates@socrates.la · www.socrates.la',7,false,$muted);
    if(!empty($quote['valid_until']))$pdf->text($right,$footerY+24,$l['validity'].' '.format_date((string)$quote['valid_until']),7,true,$orange,'right');
    return $pdf->output();
}

function contract_status_label(string $status): string
{
    return match($status){'draft'=>'Borrador','sent'=>'Enviado','approved'=>'Aprobado','rejected'=>'Rechazado','signed'=>'Firmado','cancelled'=>'Cancelado',default=>ucfirst($status)};
}
function contract_status_class(string $status): string
{
    return match($status){'approved','signed'=>'green','sent'=>'blue','rejected','cancelled'=>'red',default=>'orange'};
}
function render_contract_template(string $content, array $client, array $values=[]): string
{
    $replacements=[
        '{{EMPRESA_PRESTADORA}}'=>(string)setting('contract_company_name','SÓCRATES ONE · Creative Studio'),
        '{{ID_PRESTADOR}}'=>(string)setting('contract_company_id',''),
        '{{DIRECCION_PRESTADOR}}'=>(string)setting('contract_company_address',''),
        '{{CLIENTE}}'=>(string)($client['company_name']??''),
        '{{ID_CLIENTE}}'=>(string)($client['tax_id']??''),
        '{{PAIS}}'=>(string)($client['country']??''),
        '{{DIRECCION_CLIENTE}}'=>trim(implode(', ',array_filter([(string)($client['address']??''),(string)($client['city']??''),(string)($client['country']??'')]))),
        '{{CONTACTO}}'=>(string)($client['contact_name']??''),
        '{{EMAIL_CLIENTE}}'=>(string)($client['email']??''),
        '{{SERVICIOS}}'=>(string)($values['services']??'____________________________'),
        '{{VALOR}}'=>(string)($values['value']??'____________________________'),
        '{{FECHA_INICIO}}'=>(string)($values['start_date']??format_date(date('Y-m-d'))),
        '{{FECHA}}'=>format_date(date('Y-m-d')),
    ];
    return strtr($content,$replacements);
}

function contract_pdf_bytes(array $contract): string
{
    $pdf=new SimplePdf(['Title'=>(string)$contract['contract_number'],'Author'=>'SÓCRATES ONE · Creative Studio','Subject'=>(string)$contract['title'],'Creator'=>'SÓCRATES CRM']);
    $navy=[0,47,87];$blue=[0,116,199];$orange=[241,90,36];$text=[21,49,73];$muted=[92,116,133];$line=[217,230,238];$light=[248,251,253];$white=[255,255,255];
    $left=48;$right=547;$width=$right-$left;
    $newHeader=function(bool $compact=false)use($pdf,$left,$right,$width,$blue,$orange,$navy,$muted,$line,$contract):float{
        $pdf->rect($left,28,$width,4,$blue);$pdf->rect($left+$width*.62,28,$width*.38,4,$orange);
        if(!$pdf->jpeg(pdf_brand_logo_jpg(),$left+4,40,$compact?125:160,$compact?31:40))$pdf->text($left+4,59,'SOCRATES',20,true,$blue);
        $pdf->text($right,54,(string)$contract['contract_number'],11,true,$navy,'right');
        $pdf->text($right,69,format_date((string)$contract['issue_date']),7,false,$muted,'right');
        $pdf->line($left,$compact?83:91,$right,$compact?83:91,$line,0.7);
        return $compact?99:108;
    };
    $y=$newHeader(false);
    $pdf->text(($left+$right)/2,$y,(string)$contract['title'],15,true,$navy,'center');$y+=27;
    $pdf->rect($left,$y,$width,54,$light,$line,0.5);
    $pdf->text($left+12,$y+17,'CLIENTE',7,true,$muted);
    $pdf->text($left+12,$y+36,(string)$contract['company_name'],11,true,$navy);
    $pdf->text($right-12,$y+17,'ESTADO',7,true,$muted,'right');
    $pdf->text($right-12,$y+36,contract_status_label((string)$contract['status']),10,true,$orange,'right');
    $y+=76;
    $paragraphs=preg_split('/\R\s*\R/u',trim((string)$contract['content']))?:[];
    foreach($paragraphs as $paragraph){
        $lines=$pdf->wrap(trim((string)$paragraph),$width,9,false);$height=max(15,count($lines)*14)+10;
        if($y+$height>755){$pdf->addPage();$y=$newHeader(true);}
        $y=$pdf->multiText($left,$y,$lines,$width,9,14,false,$text)+10;
    }
    if($y+125>760){$pdf->addPage();$y=$newHeader(true);}
    $y+=24;$pdf->line($left,$y,$right,$y,$line,0.6);$y+=28;
    $boxW=230;
    $pdf->rect($left,$y,$boxW,92,$white,$line,0.6);$pdf->rect($right-$boxW,$y,$boxW,92,$white,$line,0.6);
    $pdf->text($left+12,$y+18,'POR EL PRESTADOR',7,true,$muted);$pdf->text($left+12,$y+71,'Firma: __________________________',8,false,$text);$pdf->text($left+12,$y+86,'Nombre: ________________________',8,false,$text);
    $pdf->text($right-$boxW+12,$y+18,'POR EL CLIENTE',7,true,$muted);$pdf->text($right-$boxW+12,$y+71,'Firma: __________________________',8,false,$text);$pdf->text($right-$boxW+12,$y+86,'Nombre: '.((string)($contract['approver_name']??'________________________')),8,false,$text);
    $pdf->text($left,$y+116,'Documento generado por SÓCRATES CRM · www.socrates.la',7,false,$muted);
    return $pdf->output();
}
