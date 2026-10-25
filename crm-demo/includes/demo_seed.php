<?php
declare(strict_types=1);

/**
 * SÓCRATES ONE CRM · DEMO COMPLETA
 * Datos ficticios para presentaciones comerciales. No usar como datos reales.
 */
function crm_seed_demo(PDO $pdo, int $adminUserId): array
{
    $version = '1.9.1-demo-20260809';
    $check = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key='demo_seed_version' LIMIT 1");
    $check->execute();
    if ((string)($check->fetchColumn() ?: '') === $version) {
        return ['already' => true, 'version' => $version];
    }

    $today = new DateTimeImmutable('today');
    $d = static fn(string $modifier): string => $today->modify($modifier)->format('Y-m-d');
    $dt = static fn(string $modifier, string $time = '10:00:00'): string => $today->modify($modifier)->format('Y-m-d') . ' ' . $time;
    $insertId = static function(PDO $pdo, string $sql, array $params): int {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$pdo->lastInsertId();
    };

    $started = !$pdo->inTransaction();
    try {
        if ($started) $pdo->beginTransaction();

        // -----------------------------------------------------------------
        // Equipo ficticio
        // -----------------------------------------------------------------
        $randomHash = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
        $teamRows = [
            ['Ana Martínez Demo','ana.manager@demo.local','manager','+56 9 5555 1001'],
            ['Lucas Pereira Demo','lucas.comercial@demo.local','sales','+55 45 99999 2002'],
            ['Camila Torres Demo','camila.proyectos@demo.local','manager','+56 9 5555 3003'],
            ['Diego Fernández Demo','diego.diseno@demo.local','viewer','+56 9 5555 4004'],
        ];
        $team = ['admin' => $adminUserId];
        $userUpsert = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,permissions,whatsapp,is_active,language) VALUES (?,?,?,?,NULL,?,1,'es') ON DUPLICATE KEY UPDATE name=VALUES(name),role=VALUES(role),whatsapp=VALUES(whatsapp),is_active=1");
        foreach ($teamRows as $index => $u) {
            $userUpsert->execute([$u[0],$u[1],$randomHash,$u[2],$u[3]]);
            $q=$pdo->prepare('SELECT id FROM users WHERE email=?');$q->execute([$u[1]]);$team['u'.($index+1)] = (int)$q->fetchColumn();
        }

        // -----------------------------------------------------------------
        // Clientes ficticios
        // -----------------------------------------------------------------
        $clientsData = [
            ['Andes Tech Demo SpA','Andes Tech','76.555.101-1','Chile','Santiago','Tecnología','Valentina Rojas','Gerente de Marketing','usuario.demo14@example.invalid','+56 9 5111 1001','es','Proyecto digital integral y soporte mensual.'],
            ['GreenWave Brasil Demo Ltda','GreenWave','55.555.222/0001-10','Brasil','Curitiba','Sustentabilidad','Mariana Costa','Diretora Comercial','usuario.demo07@example.invalid','+55 41 99911-2202','pt','Conta demonstrativa para projetos web e marketing.'],
            ['Pacífico Retail Demo SpA','Pacífico Retail','77.333.404-2','Chile','Viña del Mar','Retail','Tomás Silva','E-commerce Manager','usuario.demo13@example.invalid','+56 9 5222 3303','es','E-commerce, performance y automatización comercial.'],
            ['Lumina Arquitectura Demo','Lumina','78.121.009-5','Chile','Concepción','Arquitectura','Fernanda Pérez','Socia Directora','usuario.demo05@example.invalid','+56 9 5333 4404','es','Rediseño de marca y nuevo sitio corporativo.'],
            ['NovaCare Demo Health','NovaCare','99-5555555','Estados Unidos','Miami','Salud','Emily Carter','Operations Director','usuario.demo04@example.invalid','+1 305 555 1205','en','Demo account for multilingual website and automation.'],
            ['Vertex Academy Demo','Vertex Academy','79.020.115-8','Chile','Santiago','Educación','Javier Morales','Director Académico','usuario.demo06@example.invalid','+56 9 5444 5505','es','Plataforma educativa y campañas de captación.'],
            ['UrbanMove Demo Ltda','UrbanMove','56.777.321/0001-20','Brasil','São Paulo','Movilidad','Rafael Lima','Head de Produto','usuario.demo10@example.invalid','+55 11 98888-6606','pt','Produto digital, UX e integrações.'],
            ['Nativa Foods Demo','Nativa Foods','76.918.441-7','Chile','Puerto Montt','Alimentos','Carolina Soto','Gerente General','usuario.demo03@example.invalid','+56 9 5666 7707','es','Branding, packaging y presencia digital.'],
        ];
        $clientIds=[];
        $clientInsert=$pdo->prepare("INSERT INTO clients(company_name,trade_name,tax_id,country,city,industry,status,start_date,contact_name,contact_role,email,phone,whatsapp,preferred_language,preferred_contact,notes,owner_user_id) VALUES (?,?,?,?,?,?,'active',?,?,?,?,?,?,?,'email',?,?)");
        foreach($clientsData as $i=>$c){
            $owner = [$adminUserId,$team['u2'],$team['u1'],$team['u3']][$i%4];
            $clientInsert->execute([$c[0],$c[1],$c[2],$c[3],$c[4],$c[5],$d('-'.(20+$i*9).' days'),$c[6],$c[7],$c[8],$c[9],$c[9],$c[10],$c[11].' · CONTENIDO DEMO',$owner]);
            $clientIds[]=(int)$pdo->lastInsertId();
        }

        // Contactos secundarios
        $contactInsert=$pdo->prepare("INSERT INTO contacts(client_id,first_name,last_name,job_title,department,email,phone,whatsapp,language,status,source,notes,owner_user_id) VALUES (?,?,?,?,?,?,?,?,?,'active','Demo','Contacto ficticio para demostración',?)");
        $contactIds=[];
        $contactNames=[['Nicolás','Vega','CTO','Tecnología'],['João','Almeida','Gerente de Marketing','Marketing'],['Paula','Contreras','Jefa de Operaciones','Operaciones'],['Ignacio','Fuentes','Arquitecto','Dirección'],['Michael','Brooks','Marketing Manager','Marketing'],['Daniela','Reyes','Coordinadora','Admisiones'],['Bruno','Souza','Product Manager','Producto'],['Felipe','Navarro','Brand Manager','Marketing']];
        foreach($clientIds as $i=>$cid){$n=$contactNames[$i];$lang=$clientsData[$i][10];$email=strtolower($n[0].'.'.$n[1]).'@demo.local';$contactInsert->execute([$cid,$n[0],$n[1],$n[2],$n[3],$email,$clientsData[$i][9],$clientsData[$i][9],$lang,[$adminUserId,$team['u2'],$team['u1']][$i%3]]);$contactIds[]=(int)$pdo->lastInsertId();}

        // -----------------------------------------------------------------
        // Servicios, renovaciones y facturación
        // -----------------------------------------------------------------
        $services = [
            [0,'Hosting Cloud + Mantenimiento','Hosting','annual',1200,'USD','paid','+320 days'],
            [0,'Marketing Digital Mensual','Marketing','monthly',950,'USD','paid','+12 days'],
            [1,'Gestão de Redes Sociais','Marketing','monthly',3900,'BRL','pending','+6 days'],
            [2,'Soporte E-commerce','E-commerce','monthly',650,'USD','pending','-5 days'],
            [3,'Hosting + Dominio','Hosting','annual',480000,'CLP','paid','+25 days'],
            [4,'Website Care Plan','Web','monthly',780,'USD','pending','+3 days'],
            [5,'Campañas de Captación','Marketing','monthly',1200000,'CLP','paid','+18 days'],
            [6,'UX Product Retainer','UX/UI','monthly',6500,'BRL','pending','+9 days'],
            [7,'Hosting Corporativo','Hosting','annual',390000,'CLP','pending','-2 days'],
        ];
        $serviceIds=[];
        $serviceInsert=$pdo->prepare("INSERT INTO client_services(client_id,name,category,description,contract_type,start_date,expiration_date,amount,currency,payment_method,payment_status,paid_at,auto_renew,status,next_billing_date,last_billing_date,billing_status,alert_days) VALUES (?,?,?,? ,?,?,?,?,?,'Transferencia / Tarjeta',?,?,1,'active',?,?,?,'30,15,7,3,1,0')");
        foreach($services as $i=>$s){
            $paid=$s[6]==='paid'; $exp=$d($s[7]);
            $serviceInsert->execute([$clientIds[$s[0]],$s[1],$s[2],'Servicio demostrativo activo.',$s[3],$d('-120 days'),$exp,$s[4],$s[5],$s[6],$paid?$d('-18 days'):null,$exp,$paid?$d('-18 days'):null,$paid?'paid':'pending']);
            $sid=(int)$pdo->lastInsertId();$serviceIds[]=$sid;
            if($i<4){$pdo->prepare('INSERT INTO service_renewals(service_id,previous_expiration_date,new_expiration_date,amount,notes,created_by) VALUES (?,?,?,?,?,?)')->execute([$sid,$d('-365 days'),$exp,$s[4],'Renovación demostrativa',$adminUserId]);}
        }
        // Cargos y pagos para gráficos
        $chargeInsert=$pdo->prepare("INSERT INTO billing_charges(service_id,client_id,due_date,amount,currency,status,paid_amount,paid_at,notes) VALUES (?,?,?,?,?,?,?,?,?)");
        foreach($services as $i=>$s){
            $sid=$serviceIds[$i];$cid=$clientIds[$s[0]];$status=$s[6]==='paid'?'paid':($d($s[7])<$d('0 days')?'overdue':'pending');$paid=$status==='paid'?$s[4]:0;
            $chargeInsert->execute([$sid,$cid,$d($s[7]),$s[4],$s[5],$status,$paid,$status==='paid'?$d('-12 days'):null,'Ciclo demo']);
        }
        $payInsert=$pdo->prepare('INSERT INTO payments(client_id,service_id,payment_date,amount,currency,payment_method,reference,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?)');
        $paymentSamples=[[0,0,'-5 days',1200,'USD'],[0,1,'-12 days',950,'USD'],[1,2,'-34 days',3900,'BRL'],[3,4,'-45 days',480000,'CLP'],[5,6,'-8 days',1200000,'CLP'],[4,5,'-62 days',780,'USD'],[6,7,'-75 days',6500,'BRL'],[2,3,'-92 days',650,'USD']];
        foreach($paymentSamples as $p){$payInsert->execute([$clientIds[$p[0]],$serviceIds[$p[1]],$d($p[2]),$p[3],$p[4],'Transferencia','DEMO-'.strtoupper(bin2hex(random_bytes(3))),'Ingreso ficticio para gráficos',$adminUserId]);}

        // -----------------------------------------------------------------
        // Pipeline y negocios
        // -----------------------------------------------------------------
        $stageByName=[];$st=$pdo->query("SELECT id,name_es FROM pipeline_stages WHERE is_active=1");foreach($st->fetchAll() as$r)$stageByName[(string)$r['name_es']]=(int)$r['id'];
        $dealRows=[
            ['Nuevo portal corporativo',0,0,'Primer contacto',8500,'USD',10,'+28 days','LinkedIn','open','Agendar discovery'],
            ['Campaña Performance Q4',1,1,'Propuesta enviada',24000,'BRL',60,'+14 days','Referido','open','Revisar propuesta'],
            ['Rediseño E-commerce 2026',2,2,'Negociación',12500,'USD',75,'+9 days','Cliente actual','open','Definir alcance final'],
            ['Branding + Web Lumina',3,3,'Cerrado ganado',6900000,'CLP',100,'-18 days','Referido','won','Proyecto iniciado'],
            ['NovaCare Automation',4,4,'Propuesta enviada',11000,'USD',60,'+21 days','Web','open','Follow-up dirección'],
            ['Campus Virtual Vertex',5,5,'Cerrado ganado',9800000,'CLP',100,'-45 days','Networking','won','En ejecución'],
            ['UrbanMove Product Sprint',6,6,'Negociación',32000,'BRL',75,'+7 days','Evento','open','Validar cronograma'],
            ['Packaging Nativa Foods',7,7,'Cerrado perdido',4200000,'CLP',0,'-35 days','Outbound','lost','Cerrado por presupuesto'],
        ];
        $dealIds=[];$dealInsert=$pdo->prepare("INSERT INTO deals(title,client_id,contact_id,owner_user_id,stage_id,amount,currency,probability,expected_close_date,source,status,next_action,next_action_at,notes,closed_at,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach($dealRows as $i=>$x){$stage=$stageByName[$x[3]]??array_values($stageByName)[0];$owner=[$adminUserId,$team['u2'],$team['u1'],$team['u3']][$i%4];$closed=in_array($x[9],['won','lost'],true)?$dt($x[7],'16:00:00'):null;$dealInsert->execute([$x[0],$clientIds[$x[1]],$contactIds[$x[2]],$owner,$stage,$x[4],$x[5],$x[6],$d($x[7]),$x[8],$x[9],$x[10],$dt('+3 days'),$x[0].' · oportunidad ficticia demo',$closed,$adminUserId]);$dealIds[]=(int)$pdo->lastInsertId();}

        // Actividades CRM, incluyendo vencidas
        $activityRows=[
            [0,0,0,'call','Llamar para discovery','-2 days','urgent','pending'],
            [1,1,1,'email','Enviar caso de éxito','+1 day','high','pending'],
            [2,2,2,'meeting','Revisión final de alcance','+2 days','high','pending'],
            [4,4,4,'task','Preparar demo de automatización','+4 days','normal','pending'],
            [6,6,6,'meeting','Workshop de producto','+6 days','normal','pending'],
            [3,3,3,'note','Kickoff realizado','-10 days','normal','completed'],
        ];
        $a=$pdo->prepare("INSERT INTO activities(client_id,contact_id,deal_id,created_by,assigned_to,activity_type,subject,details,due_at,completed_at,status,priority) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach($activityRows as$i=>$x){$status=$x[7];$a->execute([$clientIds[$x[0]],$contactIds[$x[1]],$dealIds[$x[2]],$adminUserId,[$adminUserId,$team['u2'],$team['u1']][$i%3],$x[3],$x[4],'Actividad demostrativa',$dt($x[5]),$status==='completed'?$dt($x[5]):null,$status,$x[6]]);}

        // -----------------------------------------------------------------
        // Cotizaciones, ítems y contratos
        // -----------------------------------------------------------------
        $quoteRows=[
            ['DEMO-COT-001',0,0,'USD','-3 days','+12 days','sent',8500],
            ['DEMO-COT-002',1,1,'BRL','-7 days','+8 days','accepted',24000],
            ['DEMO-COT-003',2,2,'USD','-12 days','+3 days','accepted',12500],
            ['DEMO-COT-004',4,4,'USD','-1 day','+14 days','draft',11000],
            ['DEMO-COT-005',6,6,'BRL','-18 days','-3 days','expired',32000],
        ];
        $quoteIds=[];$qIns=$pdo->prepare("INSERT INTO quotations(quotation_number,client_id,recipient_name,recipient_email,language,currency,issue_date,valid_until,status,notes,terms,subtotal,tax_amount,total,sent_at,accepted_at,owner_user_id,deal_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach($quoteRows as$i=>$q){$ci=$q[1];$lang=$clientsData[$ci][10];$sent=in_array($q[6],['sent','accepted','rejected','expired'],true)?$dt($q[4],'11:30:00'):null;$accepted=$q[6]==='accepted'?$dt('-4 days','15:00:00'):null;$qIns->execute([$q[0],$clientIds[$ci],$clientsData[$ci][6],$clientsData[$ci][8],$lang,$q[3],$d($q[4]),$d($q[5]),$q[6],'Cotización demostrativa','Condiciones comerciales de demostración.',$q[7],0,$q[7],$sent,$accepted,$adminUserId,$dealIds[$q[2]],$adminUserId]);$qid=(int)$pdo->lastInsertId();$quoteIds[]=$qid;
            $item=$pdo->prepare('INSERT INTO quotation_items(quotation_id,description,quantity,unit_price,line_total,sort_order) VALUES (?,?,?,?,?,?)');$item->execute([$qid,'Estrategia y planificación',1,round($q[7]*0.25,2),round($q[7]*0.25,2),10]);$item->execute([$qid,'Diseño, desarrollo e implementación',1,round($q[7]*0.75,2),round($q[7]*0.75,2),20]);
        }
        $contractIns=$pdo->prepare("INSERT INTO contracts(contract_number,client_id,title,language,recipient_name,recipient_email,content,status,public_token,issue_date,approved_at,signed_at,approver_name,sent_at,owner_user_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $contracts=[[3,'DEMO-CON-001','Contrato de Branding y Desarrollo Web','signed','-28 days'],[5,'DEMO-CON-002','Contrato Plataforma Educativa','approved','-50 days'],[0,'DEMO-CON-003','Acuerdo de Servicios Digitales','sent','-4 days']];
        foreach($contracts as$i=>$c){$ci=$c[0];$status=$c[3];$approved=in_array($status,['approved','signed'],true)?$dt('-20 days'):null;$signed=$status==='signed'?$dt('-18 days'):null;$contractIns->execute([$c[1],$clientIds[$ci],$c[2],$clientsData[$ci][10],$clientsData[$ci][6],$clientsData[$ci][8],"CONTRATO DEMOSTRATIVO\n\nCliente: {$clientsData[$ci][0]}\n\nEste documento contiene información ficticia creada únicamente para mostrar el funcionamiento del sistema.",$status,hash('sha256','demo-contract-'.$i.'-'.bin2hex(random_bytes(8))),$d($c[4]),$approved,$signed,$clientsData[$ci][6],$dt($c[4]),$adminUserId,$adminUserId]);}

        // -----------------------------------------------------------------
        // Marketing, formularios y leads
        // -----------------------------------------------------------------
        $leadCamp=$insertId($pdo,"INSERT INTO lead_campaigns(name,channel,objective,budget,currency,start_date,end_date,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)",['Demo · Captación empresas 2026','LinkedIn + Landing','Generar reuniones calificadas',2500,'USD',$d('-25 days'),$d('+35 days'),'active','Campaña ficticia para demostración',$adminUserId]);
        $formToken=hash('sha256','demo-form-'.bin2hex(random_bytes(12)));
        $leadForm=$insertId($pdo,"INSERT INTO lead_forms(campaign_id,name,title,description,language,fields_json,style_json,success_message,public_token,is_active,created_by) VALUES (?,?,?,?,?,?,?,?,?,1,?)",[$leadCamp,'Formulario Demo · Diagnóstico Digital','Solicita un diagnóstico digital','Formulario de captación demostrativo','es',json_encode([['type'=>'text','name'=>'name','label'=>'Nombre'],['type'=>'email','name'=>'email','label'=>'Email'],['type'=>'text','name'=>'company','label'=>'Empresa']],JSON_UNESCAPED_UNICODE),json_encode(['theme'=>'socrates','button'=>'Solicitar diagnóstico'],JSON_UNESCAPED_UNICODE),'Gracias. Te contactaremos pronto.',$formToken,$adminUserId]);
        $leadIns=$pdo->prepare('INSERT INTO lead_submissions(form_id,data_json,name,email,phone,country,created_at) VALUES (?,?,?,?,?,?,?)');
        $leadNames=[['Alejandra Núñez','usuario.demo01@example.invalid','Chile'],['Carlos Mendes','usuario.demo02@example.invalid','Brasil'],['Sofía Herrera','usuario.demo12@example.invalid','Chile'],['Martin Blake','usuario.demo08@example.invalid','Estados Unidos'],['Renata Alves','usuario.demo11@example.invalid','Brasil'],['Pedro Salas','usuario.demo09@example.invalid','Chile']];
        foreach($leadNames as$i=>$l){$leadIns->execute([$leadForm,json_encode(['name'=>$l[0],'email'=>$l[1],'company'=>'Empresa Prospect Demo'],JSON_UNESCAPED_UNICODE),$l[0],$l[1],'+00 000 000 000',$l[2],$dt('-'.($i*3).' days','09:'.str_pad((string)(10+$i),2,'0',STR_PAD_LEFT).':00')]);}
        $mailId=$insertId($pdo,"INSERT INTO mailing_campaigns(name,subject,language,html_content,status,audience_type,sent_count,failed_count,sent_at,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)",['Demo · Novedades del mes','Novedades y proyectos destacados','es','<h1>Contenido demostrativo</h1><p>Esta campaña existe para mostrar el módulo de mailing.</p>','sent','both',8,0,$dt('-6 days','12:00:00'),$adminUserId]);
        $mr=$pdo->prepare("INSERT INTO mailing_recipients(campaign_id,client_id,email,recipient_name,status,sent_at) VALUES (?,?,?,?, 'sent',?)");foreach($clientIds as$i=>$cid)$mr->execute([$mailId,$cid,$clientsData[$i][8],$clientsData[$i][6],$dt('-6 days','12:05:00')]);

        // Agenda
        $appt=$pdo->prepare("INSERT INTO appointments(client_id,contact_id,deal_id,assigned_to,title,start_at,end_at,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $apptRows=[[0,0,0,$adminUserId,'Discovery · Andes Tech','+1 day','scheduled'],[2,2,2,$team['u1'],'Revisión E-commerce','+2 days','scheduled'],[6,6,6,$team['u3'],'Workshop UrbanMove','+5 days','scheduled'],[3,3,3,$adminUserId,'Kickoff Lumina','-12 days','completed']];
        foreach($apptRows as$x){$start=$dt($x[5],'14:00:00');$end=(new DateTimeImmutable($start))->modify('+1 hour')->format('Y-m-d H:i:s');$appt->execute([$clientIds[$x[0]],$contactIds[$x[1]],$dealIds[$x[2]],$x[3],$x[4],$start,$end,$x[6],'Reunión ficticia demo',$adminUserId]);}

        // -----------------------------------------------------------------
        // PROJECT MANAGER · Portafolio completo
        // -----------------------------------------------------------------
        $projectDefs=[
            ['PRJ-DEMO-001','Rediseño E-commerce Pacífico',$clientIds[2],$dealIds[2],$team['u1'],'active','at_risk','high','-35 days','+32 days',12500,'USD','fixed',62,'Nueva experiencia de compra, catálogo, checkout y analítica.'],
            ['PRJ-DEMO-002','Branding + Web Lumina',$clientIds[3],$dealIds[3],$adminUserId,'active','on_track','normal','-28 days','+24 days',6900000,'CLP','fixed',58,'Identidad visual, sistema gráfico y sitio corporativo.'],
            ['PRJ-DEMO-003','Campus Virtual Vertex',$clientIds[5],$dealIds[5],$team['u3'],'active','off_track','urgent','-52 days','+12 days',9800000,'CLP','fixed',71,'MVP de plataforma educativa con panel docente y alumnos.'],
            ['PRJ-DEMO-004','GreenWave Growth Sprint',$clientIds[1],$dealIds[1],$team['u2'],'active','on_track','normal','-18 days','+42 days',24000,'BRL','retainer',45,'Campaña de crecimiento y optimización de conversión.'],
            ['PRJ-DEMO-005','NovaCare Automation Lab',$clientIds[4],$dealIds[4],$adminUserId,'planning','on_track','high','+7 days','+70 days',11000,'USD','fixed',12,'Automatización de leads, soporte y reporting.'],
            ['PRJ-DEMO-006','Nativa Packaging 2026',$clientIds[7],null,$team['u1'],'completed','on_track','normal','-95 days','-20 days',4200000,'CLP','fixed',100,'Proyecto completado de packaging y lineamientos de marca.'],
        ];
        $projIns=$pdo->prepare("INSERT INTO projects(client_id,deal_id,owner_user_id,name,code,description,status,health,priority,start_date,due_date,completed_at,progress,budget,currency,billing_model,hourly_rate,color,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $projectIds=[];
        foreach($projectDefs as$i=>$p){$completed=$p[5]==='completed'?$dt('-20 days','17:00:00'):null;$projIns->execute([$p[2],$p[3],$p[4],$p[1],$p[0],$p[14].' CONTENIDO DEMO.',$p[5],$p[6],$p[7],$d($p[8]),$d($p[9]),$completed,$p[13],$p[10],$p[11],$p[12],$p[11]==='USD'?120:($p[11]==='BRL'?350:45000),['#0074c7','#ff5300','#73bc25','#31b4e7','#f6bd00','#00358f'][$i],'Datos ficticios para demostración comercial.',$adminUserId]);$projectIds[]=(int)$pdo->lastInsertId();}

        // Miembros
        $member=$pdo->prepare('INSERT INTO project_members(project_id,user_id,role_name,allocation_percent,hourly_cost) VALUES (?,?,?,?,?)');
        foreach($projectIds as$i=>$pid){$members=[[$projectDefs[$i][4],'Project Manager',70,45],[$team['u1'],'Diseño / UX',60,35],[$team['u3'],'Desarrollo / Producción',55,30],[$team['u2'],'Cliente / Comercial',25,25]];$seen=[];foreach($members as$m){if(isset($seen[$m[0]]))continue;$seen[$m[0]]=1;$member->execute([$pid,$m[0],$m[1],$m[2],$m[3]]);}}

        // Hitos por proyecto
        $milestoneIds=[];$mile=$pdo->prepare("INSERT INTO project_milestones(project_id,name,description,due_date,status,sort_order,completed_at,created_by) VALUES (?,?,?,?,?,?,?,?)");
        foreach($projectIds as$i=>$pid){$milestoneIds[$pid]=[];$defs=[['Descubrimiento y alcance','Alineación, objetivos y requerimientos','-18 days','completed'],['Diseño / Construcción','Producción principal del proyecto','+10 days',$projectDefs[$i][5]==='completed'?'completed':'in_progress'],['QA y validación','Pruebas, ajustes y aprobación','+24 days',$projectDefs[$i][5]==='completed'?'completed':'pending'],['Lanzamiento / Cierre','Publicación, entrega y retrospectiva',$projectDefs[$i][9],$projectDefs[$i][5]==='completed'?'completed':'pending']];foreach($defs as$j=>$m){$mile->execute([$pid,$m[0],$m[1],$d($m[2]),$m[3],($j+1)*10,$m[3]==='completed'?$dt($m[2],'17:00:00'):null,$adminUserId]);$milestoneIds[$pid][]=(int)$pdo->lastInsertId();}}

        // Tareas con variedad de estados, fechas, horas y responsables
        $taskIns=$pdo->prepare("INSERT INTO project_tasks(project_id,milestone_id,parent_task_id,assigned_to,title,description,status,priority,start_date,due_date,progress,planned_hours,sort_order,color,created_by,completed_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $allTasks=[];
        foreach($projectIds as$i=>$pid){
            $completedProject=$projectDefs[$i][5]==='completed';
            $base=[
                [0,'Reunión de kickoff','Objetivos, stakeholders y restricciones','done','high','-28 days','-26 days',100,3,$projectDefs[$i][4]],
                [0,'Research y benchmark','Referentes, competencia y oportunidades','done','normal','-25 days','-20 days',100,8,$team['u1']],
                [0,'Definir alcance funcional','Backlog y criterios de aceptación','done','high','-22 days','-17 days',100,6,$projectDefs[$i][4]],
                [1,'Arquitectura de información','Flujos y estructura principal',$completedProject?'done':'done','normal','-16 days','-10 days',100,10,$team['u1']],
                [1,'Diseño UI sistema principal','Pantallas clave y design system',$completedProject?'done':'in_progress','high','-10 days','+4 days',$completedProject?100:65,28,$team['u1']],
                [1,'Desarrollo / implementación','Construcción del entregable principal',$completedProject?'done':($i===2?'blocked':'in_progress'),'urgent','-6 days','+8 days',$completedProject?100:($i===2?40:55),42,$team['u3']],
                [1,'Integraciones y automatizaciones','Conexiones externas y flujos',$completedProject?'done':'todo','high','+2 days','+13 days',$completedProject?100:10,18,$team['u3']],
                [2,'QA funcional','Pruebas de casos críticos',$completedProject?'done':'todo','normal','+9 days','+17 days',$completedProject?100:0,12,$team['u3']],
                [2,'Revisión con cliente','Validación ejecutiva y feedback',$completedProject?'done':'review','high','+13 days','+19 days',$completedProject?100:80,5,$projectDefs[$i][4]],
                [2,'Ajustes finales','Correcciones posteriores a QA',$completedProject?'done':'backlog','normal','+18 days','+23 days',$completedProject?100:0,10,$team['u1']],
                [3,'Preparar lanzamiento','Checklist, respaldos y documentación',$completedProject?'done':'backlog','high','+22 days','+28 days',$completedProject?100:0,8,$projectDefs[$i][4]],
                [3,'Cierre y retrospectiva','Entrega final, métricas y próximos pasos',$completedProject?'done':'backlog','normal','+29 days',$projectDefs[$i][9],$completedProject?100:0,4,$projectDefs[$i][4]],
            ];
            // Forzamos algunas tareas atrasadas en proyectos con riesgo
            if($i===0){$base[5][6]='-2 days';$base[5][3]='in_progress';$base[5][7]=70;}
            if($i===2){$base[5][6]='-6 days';$base[6][6]='-1 day';}
            if($i===4){foreach($base as $bi=>&$b){$b[3]='backlog';$b[7]=0;$b[5]='+'.(7+$bi*3).' days';$b[6]='+'.(12+$bi*4).' days';}unset($b);}
            $allTasks[$pid]=[];
            foreach($base as$j=>$t){$start=$d($t[5]);$due=$d($t[6]);$completed=$t[3]==='done'?$dt($t[6],'17:00:00'):null;$taskIns->execute([$pid,$milestoneIds[$pid][$t[0]],null,$t[9],$t[1],$t[2].' · Demo',$t[3],$t[3]==='blocked'?'urgent':$t[4],$start,$due,$t[7],$t[8],($j+1)*10,null,$adminUserId,$completed]);$allTasks[$pid][]=(int)$pdo->lastInsertId();}
        }

        // Subtareas, dependencias, checklist y comentarios para el proyecto principal
        $mainPid=$projectIds[0];$mainTasks=$allTasks[$mainPid];
        $sub1=$insertId($pdo,"INSERT INTO project_tasks(project_id,milestone_id,parent_task_id,assigned_to,title,description,status,priority,start_date,due_date,progress,planned_hours,sort_order,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[$mainPid,$milestoneIds[$mainPid][1],$mainTasks[4],$team['u1'],'Diseñar página de producto','Subtarea demostrativa','in_progress','high',$d('-5 days'),$d('+2 days'),70,8,10,$adminUserId]);
        $sub2=$insertId($pdo,"INSERT INTO project_tasks(project_id,milestone_id,parent_task_id,assigned_to,title,description,status,priority,start_date,due_date,progress,planned_hours,sort_order,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[$mainPid,$milestoneIds[$mainPid][1],$mainTasks[4],$team['u1'],'Diseñar checkout','Subtarea demostrativa','review','high',$d('-3 days'),$d('+4 days'),90,7,20,$adminUserId]);
        $dep=$pdo->prepare('INSERT INTO project_task_dependencies(task_id,depends_on_task_id,dependency_type,lag_days) VALUES (?,?,?,?)');$dep->execute([$mainTasks[5],$mainTasks[4],'finish_start',0]);$dep->execute([$mainTasks[7],$mainTasks[5],'finish_start',1]);$dep->execute([$mainTasks[10],$mainTasks[8],'finish_start',0]);
        $checkIns=$pdo->prepare('INSERT INTO project_task_checklist(task_id,item_text,is_done,sort_order,completed_at) VALUES (?,?,?,?,?)');$checkItems=[['Responsive desktop',1],['Responsive móvil',1],['Estados hover/focus',0],['Validación accesibilidad',0]];foreach($checkItems as$j=>$x)$checkIns->execute([$mainTasks[4],$x[0],$x[1],($j+1)*10,$x[1]?$dt('-1 day'):null]);
        $comment=$pdo->prepare('INSERT INTO project_comments(project_id,task_id,user_id,body,created_at) VALUES (?,?,?,?,?)');$comment->execute([$mainPid,$mainTasks[4],$team['u1'],'Primera ronda de UI lista. Falta validar checkout móvil.',$dt('-2 days','16:10:00')]);$comment->execute([$mainPid,$mainTasks[5],$team['u3'],'La integración de stock está avanzando; necesitamos cerrar el endpoint del proveedor.',$dt('-1 day','11:25:00')]);$comment->execute([$projectIds[2],$allTasks[$projectIds[2]][5],$team['u3'],'Bloqueo demo: dependencia externa aún no disponible.',$dt('-2 days','09:40:00')]);

        // Horas para carga de trabajo y timesheets
        $timeIns=$pdo->prepare('INSERT INTO project_time_entries(project_id,task_id,user_id,work_date,hours,billable,hourly_rate,description) VALUES (?,?,?,?,?,?,?,?)');
        $rates=[$adminUserId=>120,$team['u1']=>95,$team['u2']=>80,$team['u3']=>105];
        foreach(array_slice($projectIds,0,5) as$i=>$pid){for($k=0;$k<8;$k++){$uid=[$adminUserId,$team['u1'],$team['u3'],$team['u2']][$k%4];$task=$allTasks[$pid][min($k+2,count($allTasks[$pid])-1)];$hours=[2.5,4,5.5,3,6,4.5,7,2][$k];$timeIns->execute([$pid,$task,$uid,$d('-'.($k*2+$i).' days'),$hours,1,$rates[$uid],'Trabajo demostrativo registrado']);}}

        // Riesgos
        $risk=$pdo->prepare("INSERT INTO project_risks(project_id,title,description,category,probability,impact,status,owner_user_id,mitigation,due_date,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $risk->execute([$projectIds[0],'Integración con ERP puede retrasarse','API del proveedor aún no entrega ambiente estable.','Tecnología',70,70,'mitigating',$team['u3'],'Trabajar con mock API y reunión técnica diaria.',$d('+5 days'),$adminUserId]);
        $risk->execute([$projectIds[2],'Retraso de contenido académico','Parte de los cursos aún no está aprobada.','Contenido',90,80,'open',$team['u3'],'Priorizar módulos críticos y carga progresiva.',$d('+3 days'),$adminUserId]);
        $risk->execute([$projectIds[3],'Costo de adquisición sobre objetivo','Las primeras campañas requieren optimización.','Marketing',45,50,'mitigating',$team['u2'],'Reasignar presupuesto a audiencias de mejor conversión.',$d('+10 days'),$adminUserId]);
        $risk->execute([$projectIds[4],'Cambio de alcance','El cliente puede incorporar nuevos flujos.','Alcance',35,60,'open',$adminUserId,'Congelar MVP y cotizar fase 2.',$d('+18 days'),$adminUserId]);

        // Gastos y costos
        $expense=$pdo->prepare('INSERT INTO project_expenses(project_id,expense_date,category,description,amount,currency,billable,created_by) VALUES (?,?,?,?,?,?,?,?)');
        $expenses=[[0,'-20 days','Licencias','Plugins y licencias de desarrollo',420,'USD',1],[0,'-8 days','Servicios','Testing externo',180,'USD',0],[1,'-14 days','Producción','Banco de imágenes premium',185000,'CLP',1],[2,'-10 days','Infraestructura','Servidor de pruebas',290000,'CLP',0],[3,'-6 days','Publicidad','Test inicial de campañas',1800,'BRL',1],[4,'-1 day','Software','Herramienta de automatización',260,'USD',1]];foreach($expenses as$x)$expense->execute([$projectIds[$x[0]],$d($x[1]),$x[2],$x[3].' · Demo',$x[4],$x[5],$x[6],$adminUserId]);

        // Archivos demo descargables
        $demoDir=dirname(__DIR__).'/uploads/demo-projects';if(!is_dir($demoDir))@mkdir($demoDir,0755,true);
        $files=[['brief-proyecto-demo.txt',"BRIEF DE PROYECTO · DEMO\n\nObjetivos, alcance, stakeholders y métricas de éxito ficticias para presentación."],['notas-kickoff-demo.txt',"NOTAS DE KICKOFF · DEMO\n\n1. Objetivo principal\n2. Alcance inicial\n3. Próximos pasos\n4. Riesgos y dependencias"],['checklist-lanzamiento-demo.txt',"CHECKLIST DE LANZAMIENTO · DEMO\n\n[ ] QA\n[ ] Backup\n[ ] Analítica\n[ ] SEO\n[ ] Aprobación cliente"]];
        $fileIns=$pdo->prepare('INSERT INTO project_files(project_id,task_id,file_name,file_path,mime_type,file_size,uploaded_by) VALUES (?,?,?,?,?,?,?)');foreach($files as$i=>$f){$path=$demoDir.'/'.$f[0];@file_put_contents($path,$f[1]);$rel='uploads/demo-projects/'.$f[0];$fileIns->execute([$mainPid,$i===0?null:$mainTasks[min($i+3,count($mainTasks)-1)],$f[0],$rel,'text/plain',is_file($path)?filesize($path):strlen($f[1]),$adminUserId]);}

        // Notificaciones y actividad reciente
        $notif=$pdo->prepare('INSERT INTO user_notifications(user_id,title,message,type,link,is_read,created_at) VALUES (?,?,?,?,?,?,?)');$notif->execute([$adminUserId,'Proyecto en riesgo','Campus Virtual Vertex tiene tareas atrasadas.','warning','project_view.php?id='.$projectIds[2],0,$dt('-1 day')]);$notif->execute([$adminUserId,'Cotización aceptada','GreenWave aceptó la cotización DEMO-COT-002.','success','quotations.php',0,$dt('-3 days')]);
        $log=$pdo->prepare('INSERT INTO activity_logs(user_id,client_id,service_id,quotation_id,action,details,created_at) VALUES (?,?,?,?,?,?,?)');$logRows=[[$clientIds[2],null,$quoteIds[2],'Cotización actualizada','Se actualizó alcance y cronograma demo','-1 day'],[$clientIds[3],null,null,'Proyecto iniciado','Se inició Branding + Web Lumina','-2 days'],[$clientIds[0],$serviceIds[1],null,'Pago registrado','Se registró pago de servicio mensual','-5 days'],[$clientIds[5],null,null,'Hito completado','Discovery del Campus Virtual completado','-7 days']];foreach($logRows as$x)$log->execute([$adminUserId,$x[0],$x[1],$x[2],$x[3],$x[4],$dt($x[5],'13:20:00')]);

        // Marca demo y versión del sistema
        $setting=$pdo->prepare('INSERT INTO settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $setting->execute(['demo_seed_version',$version]);
        $setting->execute(['demo_mode','1']);
        $setting->execute(['demo_notice','Todos los nombres, empresas, montos y actividades marcados como Demo son ficticios.']);
        
        if ($started) $pdo->commit();
        return [
            'already'=>false,'version'=>$version,
            'clients'=>count($clientIds),'contacts'=>count($contactIds),'projects'=>count($projectIds),
            'tasks'=>array_sum(array_map('count',$allTasks))+2,'quotes'=>count($quoteIds),'deals'=>count($dealIds),
            'team'=>count($teamRows),'leads'=>count($leadNames),
        ];
    } catch(Throwable $e) {
        if ($started && $pdo->inTransaction()) $pdo->rollBack();
        throw new RuntimeException('No fue posible crear el contenido DEMO: '.$e->getMessage(),0,$e);
    }
}
