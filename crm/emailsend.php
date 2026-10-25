<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_login();
require_permission('mailing.view');
$user=current_user();

// Diagnóstico previo protegido por la sesión y el permiso de ONE HUB.
// No muestra contraseñas, DSN ni detalles internos de las excepciones.
$emailsendSetupIssues = [];
$emailsendConfigPath = __DIR__ . '/emailsend/config.php';
if (!is_file($emailsendConfigPath) || !is_readable($emailsendConfigPath)) {
    $emailsendSetupIssues[] = 'Falta el archivo emailsend/config.php. Copia config.example.php y completa la conexión a una base MySQL independiente.';
} else {
    try {
        $emailsendConfig = require $emailsendConfigPath;
        if (!is_array($emailsendConfig)) {
            $emailsendSetupIssues[] = 'emailsend/config.php debe devolver un arreglo de configuración PHP válido.';
        } else {
            $emailsendKey = (string)($emailsendConfig['app_key'] ?? '');
            if (strlen($emailsendKey) < 32 || stripos($emailsendKey, 'CAMBIAR') !== false || stripos($emailsendKey, 'CHANGE') !== false) {
                $emailsendSetupIssues[] = 'Configura una app_key privada aleatoria de al menos 32 caracteres en emailsend/config.php.';
            }
            if (($emailsendConfig['demo_mode'] ?? null) !== true) {
                $emailsendSetupIssues[] = 'Para esta instalación DEMO, demo_mode debe estar activado (true), así se impiden envíos reales.';
            }
            $emailsendDb = $emailsendConfig['db'] ?? [];
            $emailsendFields = ['host', 'name', 'user', 'pass'];
            $emailsendDbValid = is_array($emailsendDb);
            foreach ($emailsendFields as $field) {
                if (!$emailsendDbValid || trim((string)($emailsendDb[$field] ?? '')) === '' || preg_match('/^(?:NOMBRE_DE_|USUARIO_DE_|CONTRASENA_DE_)/', (string)($emailsendDb[$field] ?? ''))) {
                    $emailsendDbValid = false;
                }
            }
            if (!$emailsendDbValid) {
                $emailsendSetupIssues[] = 'Completa host, nombre, usuario y contraseña de la base de datos EMAILSEND en config.php.';
            } elseif (!extension_loaded('pdo_mysql')) {
                $emailsendSetupIssues[] = 'El servidor PHP debe tener habilitada la extensión pdo_mysql.';
            } else {
                try {
                    $emailsendPort = (string)($emailsendDb['port'] ?? '3306');
                    if (!ctype_digit($emailsendPort) || (int)$emailsendPort < 1 || (int)$emailsendPort > 65535) {
                        throw new RuntimeException('Puerto MySQL inválido.');
                    }
                    $emailsendDsn = 'mysql:host=' . (string)$emailsendDb['host'] . ';port=' . $emailsendPort . ';dbname=' . (string)$emailsendDb['name'] . ';charset=utf8mb4';
                    $emailsendPdo = new PDO($emailsendDsn, (string)$emailsendDb['user'], (string)$emailsendDb['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT => 4,
                    ]);
                    $existingTables = $emailsendPdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                    $requiredTables = ['tenants', 'users', 'settings', 'contacts', 'contact_lists', 'campaigns', 'deliveries'];
                    $missingTables = array_diff($requiredTables, $existingTables);
                    if ($missingTables) {
                        $emailsendSetupIssues[] = 'Faltan tablas de EMAILSEND. Importa emailsend/install/schema.sql en la base independiente desde phpMyAdmin.';
                    }
                } catch (Throwable $ex) {
                    error_log('ONE HUB EMAILSEND preflight: ' . $ex->getMessage());
                    $emailsendSetupIssues[] = 'No se pudo comprobar la base MySQL de EMAILSEND. Revisa las credenciales, permisos de acceso y la importación del esquema.';
                }
            }
        }
    } catch (Throwable $ex) {
        error_log('ONE HUB EMAILSEND configuración: ' . $ex->getMessage());
        $emailsendSetupIssues[] = 'No fue posible leer la configuración de EMAILSEND. Revisa su sintaxis PHP y sus permisos.';
    }
}
$emailsendSessionPath = __DIR__ . '/emailsend/storage/sessions';
if (is_dir($emailsendSessionPath) && !is_writable($emailsendSessionPath)) {
    $emailsendSetupIssues[] = 'PHP necesita permiso de escritura en emailsend/storage/sessions para la sesión aislada de EMAILSEND.';
}
$key=(string)config('app_key');
if(strlen($key)<32 || str_contains($key,'CHANGE_ME')){http_response_code(503);exit('Configura una clave segura de ONE HUB antes de habilitar EMAILSEND.');}
$payload=[
 'v'=>1,'iss'=>'onehub-emailsend','id'=>(int)$user['id'],
 'email'=>strtolower(trim((string)$user['email'])),
 'name'=>(string)$user['name'],
 'admin'=>($user['role']==='admin' && has_permission('mailing.manage'))?1:0,
 'exp'=>time()+90,'nonce'=>bin2hex(random_bytes(20))
];
$encoded=rtrim(strtr(base64_encode(json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),'+/','-_'),'=');
$signature=hash_hmac('sha256',$encoded,$key.'|onehub-emailsend-sso-v1');
$pageTitle='EMAILSEND ONE · Marketing';
require __DIR__.'/includes/header.php';
?>
<style>.es-launch{max-width:860px;margin:10vh auto 6vh;padding:36px;background:var(--panel);border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow);text-align:center}.es-launch h2{font:800 clamp(26px,3.5vw,37px)/1.15 'Exo 2';margin:12px 0;color:var(--text)}.es-launch p{color:var(--muted);line-height:1.7}.es-launch .btn{margin:16px auto 0}.es-launch .es-note{font-size:12px}.es-launch .es-demo{display:inline-block;padding:7px 13px;background:color-mix(in srgb,var(--green) 16%,var(--panel));border-radius:20px;color:var(--text);font-weight:800;font-size:11px}</style>
<section class="es-launch">
  <span class="es-demo">ONE HUB · EMAILSEND ONE · DEMO</span>
  <h2>Campañas y email marketing en ONE HUB</h2>
  <p>Editor de campañas, contactos, listas, automatización de envíos, reportes y seguimiento. El entorno demo funciona con información ficticia y sin correos salientes.</p>
  <?php if ($emailsendSetupIssues): ?>
  <div class="es-setup-alert" role="alert" style="margin:22px auto 12px;max-width:670px;padding:24px;text-align:left;border:1px solid #f0c780;border-radius:12px;background:#fff8ed;color:#513d19">
    <h3 style="margin:0 0 12px;font-size:18px">Configuración pendiente de EMAILSEND</h3>
    <?php if (has_permission('mailing.manage')): ?>
      <ol style="margin:0;padding-left:23px;line-height:1.7">
      <?php foreach ($emailsendSetupIssues as $setupIssue): ?><li><?=e($setupIssue)?></li><?php endforeach; ?>
      </ol>
      <p style="font-size:13px;margin:10px 0 0">No abras /emailsend/install/ en el navegador: está bloqueado intencionalmente. Importa el archivo SQL desde phpMyAdmin. Después, vuelve a cargar esta página.</p>
    <?php else: ?>
      <p>La integración aún no está configurada. Solicita al administrador que complete la instalación de EMAILSEND.</p>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <form id="emailsend-bridge" method="post" action="emailsend/sso.php">
    <input type="hidden" name="payload" value="<?=e($encoded)?>">
    <input type="hidden" name="signature" value="<?=e($signature)?>">
    <button class="btn btn-primary" type="submit">ENTRAR A EMAILSEND ONE →</button>
  </form>
  <?php if (!$emailsendSetupIssues): ?><script>document.getElementById("emailsend-bridge").submit();</script><?php endif; ?>
  <?php endif; ?>
  <p class="es-note">La primera apertura requiere que el administrador haya creado la base de datos independiente y configurado <code>emailsend/config.php</code>.</p>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
