<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
if(crm_current_language()!=='es') ob_start('crm_translate_rendered_html');
if(current_user()){
    if(crm_validate_session()) redirect('dashboard.php');
    $_SESSION=[];
    session_regenerate_id(true);
}
$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $email=strtolower(trim((string)($_POST['email']??'')));
    $password=(string)($_POST['password']??'');
    $rate=crm_login_rate_status($email);
    if(!$rate['allowed']){
        $minutes=max(1,(int)ceil(((int)$rate['retry_after'])/60));
        $error='Demasiados intentos. Espera '.$minutes.' minuto(s) antes de volver a intentar.';
        crm_security_audit('login_rate_limited','warning','Acceso limitado temporalmente');
    }else{
        $stmt=db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user=$stmt->fetch();
        $userId=$user?(int)$user['id']:null;
        $locked=$user&&!empty($user['locked_until'])&&strtotime((string)$user['locked_until'])>time();
        $valid=$user&&(int)$user['is_active']===1&&!$locked&&password_verify($password,(string)$user['password_hash']);
        if($valid){
            if(crm_password_needs_rehash((string)$user['password_hash'])){
                try{
                    db()->prepare('UPDATE users SET password_hash=?,password_changed_at=COALESCE(password_changed_at,NOW()) WHERE id=?')->execute([crm_password_hash($password),$userId]);
                }catch(Throwable){
                    db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([crm_password_hash($password),$userId]);
                }
            }
            crm_record_login_attempt($email,true,$userId);
            $forceAdmin=(string)setting('force_admin_totp','0')==='1'&&(string)$user['role']==='admin';
            $needsTotpSetup=$forceAdmin&&((string)($user['two_factor_method']??'email')!=='totp'||empty($user['totp_secret']));
            if($needsTotpSetup){
                $_SESSION['force_totp_setup_after_login']=1;
                $user['two_factor_method']='email';
                $_SESSION['pending_2fa_method']='email';
                crm_security_audit('admin_totp_required','warning','Administrador debe configurar TOTP después de ingresar','user',$userId,$userId);
            }
            $twoFactor=((string)setting('two_factor_required','0')==='1')||(bool)($user['two_factor_enabled']??false)||$forceAdmin;
            if($twoFactor){
                $result=$needsTotpSetup?begin_two_factor_login($user):begin_secure_two_factor_login($user);
                if($result['success']) redirect('verify_2fa.php');
                unset($_SESSION['pending_2fa_user_id'],$_SESSION['pending_2fa_method'],$_SESSION['two_factor_code_hash'],$_SESSION['two_factor_expires_at']);
                $error='La contraseña es correcta, pero no fue posible iniciar la verificación: '.$result['error'];
            }else{
                complete_user_login($userId);
                redirect('dashboard.php');
            }
        }else{
            crm_record_login_attempt($email,false,$userId);
            $error=$locked?'La cuenta está temporalmente bloqueada por seguridad. Intenta nuevamente más tarde.':'Email o contraseña incorrectos.';
        }
    }
}
$license=crm_license_info();
$companyName=crm_company_name();
$companyLogo=crm_company_logo_path();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#002f57">
<title>Acceso · SÓCRATES CRM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700;800&family=Ubuntu+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css?v=2.0.2">
</head>
<body class="login-body login-v201">
<main class="login201-shell">
    <section class="login201-story">
        <div class="login201-glow glow-one"></div>
        <div class="login201-glow glow-two"></div>

        <div class="login201-brandrow">
            <img src="assets/img/logo-creative-blanco.png" alt="SÓCRATES ONE" class="login201-socrates-logo">
            <span class="login201-product-pill">BUSINESS OPERATING SYSTEM</span>
        </div>

        <div class="login201-copy">
            <span class="login201-kicker">SÓCRATES CRM</span>
            <h1>Tu negocio, conectado<br>en un solo lugar.</h1>
            <p>Clientes, ventas, proyectos, documentos, soporte, finanzas y decisiones trabajando dentro del mismo ecosistema.</p>
        </div>

        <div class="login201-flow" aria-label="Flujo operativo">
            <div><span>01</span><strong>Captar</strong><small>Clientes y leads</small></div>
            <i>→</i>
            <div><span>02</span><strong>Vender</strong><small>Pipeline y propuestas</small></div>
            <i>→</i>
            <div><span>03</span><strong>Ejecutar</strong><small>Proyectos y equipo</small></div>
            <i>→</i>
            <div><span>04</span><strong>Cobrar</strong><small>Facturación y pagos</small></div>
            <i>→</i>
            <div><span>05</span><strong>Renovar</strong><small>Alertas y ciclos</small></div>
        </div>

        <div class="login201-capabilities">
            <article>
                <span class="login201-cap-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg>
                </span>
                <div><strong>CRM & Ventas</strong><small>Pipeline, contactos, cotizaciones y contratos.</small></div>
            </article>
            <article>
                <span class="login201-cap-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 18V6m0 12h16M8 15l3-4 3 2 4-6"/></svg>
                </span>
                <div><strong>Project Manager</strong><small>Kanban, Gantt, calendario, horas, riesgos y costos.</small></div>
            </article>
            <article>
                <span class="login201-cap-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v13H9l-4 3V4zm4 5h6m-6 4h4"/></svg>
                </span>
                <div><strong>Helpdesk & Control</strong><small>Tickets, SLA, seguridad, auditoría e informes.</small></div>
            </article>
        </div>

        <div class="login201-story-footer">
            <span>Diseño con Alma, impulsado por IA.</span>
            <span>SÓCRATES ONE · info@socrates.la</span>
        </div>
    </section>

    <section class="login201-access">
        <div class="login201-access-top">
            <div class="login201-client-brand">
                <?php if($companyLogo): ?>
                    <img src="<?=e($companyLogo)?>" alt="<?=e($companyName)?>">
                <?php else: ?>
                    <span class="login201-client-monogram"><?=e(crm_upper(crm_substr($companyName,0,1)))?></span>
                <?php endif; ?>
                <div><small>ESPACIO DE TRABAJO</small><strong><?=e($companyName)?></strong></div>
            </div>
            <nav class="login201-languages" aria-label="Idioma">
                <a href="language.php?lang=es" title="Español"><img src="assets/img/flag-es.png" alt="ES"></a>
                <a href="language.php?lang=pt" title="Português"><img src="assets/img/flag-pt.png" alt="PT"></a>
                <a href="language.php?lang=en" title="English"><img src="assets/img/flag-en.png" alt="EN"></a>
            </nav>
        </div>

        <div class="login201-form-wrap">
            <div class="login201-secure-mark">
                <span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-2.9 8.1-7 10-4.1-1.9-7-5.5-7-10V6l7-3zm-3 9 2 2 4-5"/></svg>
                </span>
                <div><small>ACCESO PROTEGIDO</small><strong>Entorno empresarial seguro</strong></div>
            </div>

            <h2>Bienvenido de nuevo</h2>
            <p class="login201-intro">Ingresa tus credenciales para continuar al centro de control.</p>

            <?php if($error): ?><div class="alert alert-error login201-alert"><?=e($error)?></div><?php endif; ?>

            <form method="post" class="login201-form">
                <?=csrf_field()?>
                <label>
                    <span>Email</span>
                    <div class="login201-inputbox">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v12H3zM3 7l9 7 9-7"/></svg>
                        <input type="email" name="email" required autofocus autocomplete="username" placeholder="nombre@empresa.com" value="<?=e($_POST['email']??'')?>">
                    </div>
                </label>
                <label>
                    <span>Contraseña</span>
                    <div class="login201-inputbox">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2m-11 0h12v10H6z"/></svg>
                        <input id="loginPassword" type="password" name="password" required autocomplete="current-password" placeholder="Tu contraseña">
                        <button type="button" class="login201-password-toggle" id="toggleLoginPassword" aria-label="Mostrar contraseña" title="Mostrar contraseña">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12zm10-3a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>
                        </button>
                    </div>
                </label>
                <button class="login201-submit" type="submit">
                    <span>INGRESAR AL SISTEMA</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
                </button>
            </form>

            <div class="login201-security-row">
                <span><i></i> Conexión segura</span>
                <span>2FA compatible</span>
                <span>Sesión auditada</span>
            </div>
        </div>

        <div class="login201-access-footer">
            <div><strong>SÓCRATES CRM</strong><small>v<?=e(crm_app_version())?></small></div>
            <span class="login201-license license-<?=e($license['class'])?>"><?=e($license['label'])?></span>
        </div>
    </section>
</main>
<script>
(function(){
    var input=document.getElementById('loginPassword');
    var btn=document.getElementById('toggleLoginPassword');
    if(!input||!btn)return;
    btn.addEventListener('click',function(){
        var show=input.type==='password';
        input.type=show?'text':'password';
        btn.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');
        btn.setAttribute('title',show?'Ocultar contraseña':'Mostrar contraseña');
        btn.classList.toggle('is-visible',show);
    });
})();
</script>
</body>
</html>
