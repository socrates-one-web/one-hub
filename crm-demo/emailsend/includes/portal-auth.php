<?php
/**
 * Shared client portal authentication for EMAILSEND ONE v0.8.2.
 * One client credential grants access to Home, Campaigns, Reports and Help.
 * Administrators can preview a client portal without knowing the client password.
 */
function portal_logged_user(): ?array {
    if (empty($_SESSION['report_user_id'])) return null;
    $st=db()->prepare('SELECT ru.*,rc.company,rc.logo_url,rc.tenant_id,rc.contact_name,rc.phone FROM report_users ru JOIN report_clients rc ON rc.id=ru.client_id WHERE ru.id=? AND ru.active=1 AND rc.active=1');
    $st->execute([(int)$_SESSION['report_user_id']]);
    return $st->fetch() ?: null;
}

function portal_context(): ?array {
    if (is_admin() && !empty($_GET['admin_client'])) {
        $clientId=(int)$_GET['admin_client'];
        $st=db()->prepare('SELECT ru.*,rc.company,rc.logo_url,rc.tenant_id,rc.contact_name,rc.phone FROM report_clients rc LEFT JOIN report_users ru ON ru.client_id=rc.id WHERE rc.id=? AND rc.tenant_id=? AND rc.active=1 LIMIT 1');
        $st->execute([$clientId,current_tenant_id()]);
        $row=$st->fetch();
        if ($row) {
            $row['client_id']=$clientId;
            $row['_admin_preview']=true;
            $row['name']=$row['name'] ?: ($row['contact_name'] ?: $row['company']);
            return $row;
        }
    }
    $row=portal_logged_user();
    if ($row) $row['_admin_preview']=false;
    return $row;
}

function portal_handle_login(string $returnTo): string {
    $error='';
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verify_csrf();
        $email=strtolower(trim($_POST['email']??''));
        $st=db()->prepare('SELECT * FROM report_users WHERE email=? AND active=1 LIMIT 1');
        $st->execute([$email]);
        $u=$st->fetch();
        if ($u && password_verify($_POST['password']??'',$u['password_hash'])) {
            $_SESSION['report_user_id']=$u['id'];
            db()->prepare('UPDATE report_users SET last_login_at=NOW() WHERE id=?')->execute([$u['id']]);
            session_regenerate_id(true);
            redirect($returnTo);
        }
        $error='Email o contraseña incorrectos.';
    }
    return $error;
}

function portal_logout(string $returnTo): never {
    unset($_SESSION['report_user_id']);
    session_regenerate_id(true);
    redirect($returnTo);
}

function portal_allowed_campaigns(array $ctx): array {
    $st=db()->prepare('SELECT c.* FROM campaigns c JOIN campaign_report_access a ON a.campaign_id=c.id WHERE a.client_id=? AND c.tenant_id=? ORDER BY COALESCE(c.sent_at,c.updated_at,c.created_at) DESC,c.id DESC');
    $st->execute([(int)$ctx['client_id'],(int)$ctx['tenant_id']]);
    return $st->fetchAll();
}

function portal_pick_campaign(array $allowed,int $id): ?array {
    if (!$id) return count($allowed)===1 ? $allowed[0] : null;
    foreach($allowed as $c) if((int)$c['id']===$id) return $c;
    return null;
}

function portal_query(array $ctx,array $extra=[]): string {
    if (empty($ctx['_admin_preview'])) return $extra ? '?'.http_build_query($extra) : '';
    $extra=['admin_client'=>(int)$ctx['client_id']]+$extra;
    return '?'.http_build_query($extra);
}

function portal_url(string $view,array $ctx=[],array $extra=[]): string {
    $params=['portal'=>$view];
    if(!empty($ctx['_admin_preview']) && !empty($ctx['client_id'])) $params['admin_client']=(int)$ctx['client_id'];
    foreach($extra as $k=>$v){
        if($v!==null && $v!=='') $params[$k]=$v;
    }
    return 'index.php?'.http_build_query($params);
}

function portal_campaign_rollup(array $campaigns): array {
    $out=[
        'campaigns'=>count($campaigns),'sent'=>0,'delivered'=>0,'unique_opens'=>0,'total_opens'=>0,
        'unique_clicks'=>0,'total_clicks'=>0,'bounced'=>0,'failed'=>0,'unsub'=>0,
        'delivery_rate'=>0,'open_rate'=>0,'ctr'=>0,'bounce_rate'=>0,'unsub_rate'=>0,
        'draft'=>0,'queued'=>0,'sending'=>0,'sent_campaigns'=>0,'rows'=>[]
    ];
    foreach($campaigns as $c){
        $m=campaign_metrics((int)$c['id']);
        $row=$c;$row['metrics']=$m;$out['rows'][]=$row;
        foreach(['sent','delivered','unique_opens','total_opens','unique_clicks','total_clicks','bounced','failed','unsub'] as $k)$out[$k]+=(int)$m[$k];
        $status=strtolower((string)($c['status']??'draft'));
        if($status==='draft')$out['draft']++;
        elseif($status==='queued')$out['queued']++;
        elseif($status==='sending')$out['sending']++;
        elseif($status==='sent')$out['sent_campaigns']++;
    }
    if($out['sent']>0){
        $out['delivery_rate']=round($out['delivered']/$out['sent']*100,1);
        $out['open_rate']=round($out['unique_opens']/$out['sent']*100,1);
        $out['ctr']=round($out['unique_clicks']/$out['sent']*100,1);
        $out['bounce_rate']=round($out['bounced']/$out['sent']*100,1);
        $out['unsub_rate']=round($out['unsub']/$out['sent']*100,1);
    }
    return $out;
}

function portal_svg(string $name): string {
    $icons=[
        'home'=>'<svg viewBox="0 0 24 24"><path d="M3 11.2 12 4l9 7.2v8.3a.5.5 0 0 1-.5.5h-5.2v-6h-6.6v6H3.5a.5.5 0 0 1-.5-.5z"/></svg>',
        'mail'=>'<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
        'chart'=>'<svg viewBox="0 0 24 24"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>',
        'help'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.8 9a2.4 2.4 0 0 1 4.6 1c0 2-2.4 2.1-2.4 4M12 18h.01"/></svg>',
        'back'=>'<svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>',
        'logout'=>'<svg viewBox="0 0 24 24"><path d="M10 4H4v16h6M14 8l4 4-4 4M8 12h10"/></svg>',
        'eye'=>'<svg viewBox="0 0 24 24"><path d="M2.5 12s3.2-6 9.5-6 9.5 6 9.5 6-3.2 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>'
    ];
    return $icons[$name]??'';
}

function portal_login_page(string $kind,string $error=''): never {
    $config=[
        'home'=>['Portal del Cliente','Todo tu email marketing, más claro','Consulta campañas, resultados y ayuda desde un único espacio privado.','Entrar a mi portal'],
        'campaigns'=>['Portal de Campañas','Tus campañas, en un solo lugar','Revisa las campañas habilitadas para tu empresa, su estado y la pieza final.','Ver mis campañas'],
        'reports'=>['Portal de Reportes','Resultados claros de tus campañas','Consulta aperturas, clics, entregas y rendimiento de las campañas autorizadas.','Ver mis reportes'],
        'help'=>['Centro de Ayuda','Respuestas rápidas cuando las necesitas','Entiende el portal, las campañas y cada métrica sin depender del administrador.','Abrir centro de ayuda']
    ];
    [$title,$headline,$copy,$button]=$config[$kind]??$config['home'];
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/app.css"><title><?=e($title)?> · EMAILSEND ONE</title></head><body class="auth-screen portal-auth-screen"><div class="auth-effects" aria-hidden="true"><span class="fx-orb fx-orb-a"></span><span class="fx-orb fx-orb-b"></span><span class="fx-orb fx-orb-c"></span><span class="fx-wave fx-wave-a"></span><span class="fx-wave fx-wave-b"></span><span class="fx-grid"></span><span class="fx-mailmark"></span></div><main class="auth-stage"><section class="auth-card portal-auth-card"><img src="assets/logo-emailsend.png" alt="EMAILSEND" class="auth-logo"><div class="auth-kicker"><?=e(strtoupper($title))?></div><h1><?=e($headline)?></h1><p class="auth-lead"><?=e($copy)?></p><?php if($error):?><div class="flash error"><?=e($error)?></div><?php endif?><form method="post" class="auth-form"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><label>Email<div class="input-with-icon"><span aria-hidden="true">✉</span><input type="email" name="email" placeholder="Ingresa tu email" autocomplete="username" required autofocus></div></label><label>Contraseña<div class="input-with-icon"><span aria-hidden="true">⌑</span><input id="portal-pass" type="password" name="password" placeholder="Ingresa tu contraseña" autocomplete="current-password" required><button class="password-toggle" type="button" data-toggle-password="portal-pass" aria-label="Mostrar contraseña">◉</button></div></label><button class="btn auth-submit" type="submit"><?=e($button)?></button></form><div class="auth-divider"><span>Accesos del cliente</span></div><div class="auth-portal-links"><a href="index.php?portal=campanas"><b>Campañas</b><small>Revisa las piezas</small><span>→</span></a><a href="index.php?portal=reportes"><b>Reportes</b><small>Consulta resultados</small><span>→</span></a></div><a class="auth-back-link" href="index.php?route=login">Acceso administrador</a></section><aside class="auth-promo"><div class="auth-promo-badge"><?=e($title)?></div><h2>Tu información, sin complejidad.</h2><p>Un portal privado para entender qué se envió, qué ocurrió y qué acciones generó cada campaña.</p><div class="promo-pills"><span>Campañas</span><span>Reportes reales</span><span>Centro de ayuda</span></div><div class="auth-developer"><span>Desarrollado por</span><img src="assets/developer-logo-yellow.png" alt="Sócrates AI Creative Studio"></div></aside></main><script>(function(){document.querySelectorAll('[data-toggle-password]').forEach(function(b){b.addEventListener('click',function(){var i=document.getElementById(b.getAttribute('data-toggle-password'));if(!i)return;var show=i.type==='password';i.type=show?'text':'password';b.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');});});})();</script></body></html><?php exit;
}

function portal_header(array $ctx,string $active): void {
    $admin=!empty($ctx['_admin_preview']);
    $homeUrl=portal_url('inicio',$ctx);
    $campaignsUrl=portal_url('campanas',$ctx);
    $reportsUrl=portal_url('reportes',$ctx);
    $helpUrl=portal_url('ayuda',$ctx);
    if(!empty($_GET['manual'])) $active='help'; // old manual URLs remain visually coherent.
    ?><header class="client-portal-top"><a class="client-portal-brand" href="<?=e($homeUrl)?>"><img src="assets/logo-emailsend.png" alt="EMAILSEND"></a><nav class="client-portal-tabs" aria-label="Portal del cliente"><a class="<?=$active==='home'?'active':''?>" href="<?=e($homeUrl)?>"><?=portal_svg('home')?><span>Inicio</span></a><a class="<?=$active==='campaigns'?'active':''?>" href="<?=e($campaignsUrl)?>"><?=portal_svg('mail')?><span>Campañas</span></a><a class="<?=$active==='reports'?'active':''?>" href="<?=e($reportsUrl)?>"><?=portal_svg('chart')?><span>Reportes</span></a><a class="<?=$active==='help'?'active help-active':''?>" href="<?=e($helpUrl)?>"><?=portal_svg('help')?><span>Ayuda</span></a></nav><div class="client-portal-user"><?php if($admin):?><span class="admin-preview-badge">Vista Administrador</span><?php endif?><span><strong><?=e($ctx['company'])?></strong><small><?=e($ctx['name']??$ctx['contact_name']??'Cliente')?></small></span><?php if($admin):?><a class="btn portal-admin-back" href="index.php?route=clients&edit=<?=(int)$ctx['client_id']?>"><?=portal_svg('back')?>Volver al Admin</a><?php else:?><a class="btn portal-logout" href="index.php?portal=inicio&amp;logout=1"><?=portal_svg('logout')?>Salir</a><?php endif?></div></header><?php
}
