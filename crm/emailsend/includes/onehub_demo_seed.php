<?php
declare(strict_types=1);
/** Dataset ficticio; no contiene ni importa contactos del cliente. */
function onehub_seed_emailsend_demo(PDO $pdo,int $tenant):void {
  $lists=['Prospectos demo','Clientes demo','Newsletter demo'];
  $listIds=[];
  $insertList=$pdo->prepare('INSERT INTO contact_lists (tenant_id,name,created_at) VALUES (?,?,NOW())');
  foreach($lists as $name){$insertList->execute([$tenant,$name]);$listIds[]=(int)$pdo->lastInsertId();}
  $people=[
   ['Ana Demo','Estudio Aurora',0],['Luis Ejemplo','Grupo Pionero',0],['María Muestra','Nova Digital',0],['Carlos Demo','Comercial Horizonte',0],
   ['Claudia Ejemplo','Oficina Vértice',1],['Pedro Muestra','Litoral Consulting',1],['Camila Demo','Alfa Web',1],['Andrés Ejemplo','Soluciones Acme',1],
   ['Sofía Muestra','Eco Media',2],['Tomás Demo','Vertex Labs',2],['Laura Ejemplo','Prisma Taller',2],['Diego Muestra','Nexo Tecnologías',2],
   ['Isabel Demo','Río Sur',0],['Marco Ejemplo','Faro Estudio',0],['Natalia Muestra','Centro Nova',1],['Rubén Demo','Punto Creativo',2]
  ];
  $stmt=$pdo->prepare('INSERT INTO contacts (tenant_id,list_id,name,company_name,email,status,created_at) VALUES (?,?,?,?,?,"active",NOW())');
  $contactIds=[];
  foreach($people as $i=>$person){$mail='contacto'.str_pad((string)($i+1),2,'0',STR_PAD_LEFT).'@onehub.test';$stmt->execute([$tenant,$listIds[$person[2]],$person[0],$person[1],$mail]);$contactIds[$person[2]][]=[(int)$pdo->lastInsertId(),$mail];}
  $campaigns=[
   ['Bienvenida · ONE HUB','Conoce nuestro ecosistema','draft',0],
   ['Newsletter de novedades','Descubre las novedades de octubre','sent',2],
   ['Campaña comercial de ejemplo','Presentación de servicios','sent',1],
   ['Invitación a demostración','Te invitamos a conocer ONE HUB','draft',0],
  ];
  $create=$pdo->prepare('INSERT INTO campaigns (tenant_id,name,subject,preheader,from_name,from_email,list_id,blocks_json,html,status,sent_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
  $map=$pdo->prepare('INSERT INTO campaign_lists (campaign_id,list_id) VALUES (?,?)');
  $del=$pdo->prepare('INSERT INTO deliveries (tenant_id,campaign_id,contact_id,email,token,status,opens,clicks,first_open_at,last_open_at,created_at,sent_at) VALUES (?,?,?,?,?,"sent",?,?,IF(?=1,NOW(),NULL),IF(?=1,NOW(),NULL),NOW(),NOW())');
  foreach($campaigns as $j=>$c){
   $list=$c[3]===0?$listIds[0]:$listIds[$c[3]];
   $html='<html><body style="font-family:Arial,sans-serif;padding:35px;color:#173247"><h1 style="color:#0074c7">'.htmlspecialchars($c[0],ENT_QUOTES,'UTF-8').'</h1><p>Esta campaña es una muestra ficticia de ONE HUB + EMAILSEND ONE.</p><a href="https://example.org/">Ver ejemplo</a><p style="font-size:12px;color:#657b8c">DEMOSTRACIÓN: NO SE HA ENVIADO NINGÚN EMAIL REAL</p></body></html>';
   $create->execute([$tenant,$c[0],$c[1],'Datos de ejemplo','ONE HUB · Demo','demo@onehub.test',$list,'[]',$html,$c[2],$c[2]==='sent'?date('Y-m-d H:i:s'):null]);
   $campaign=(int)$pdo->lastInsertId();$map->execute([$campaign,$list]);
   if($c[2]==='sent')foreach(($contactIds[$c[3]]??[]) as $i=>$contact){$opened=($i%3)!==0?1:0;$del->execute([$tenant,$campaign,$contact[0],$contact[1],bin2hex(random_bytes(24)),$opened,$opened&&($i%2===0)?1:0,$opened,$opened]);}
  }
}
