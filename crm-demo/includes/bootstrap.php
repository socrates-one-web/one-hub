<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('ONEHUBDEMOSESSID');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script !== 'install.php') {
        header('Location: install.php');
        exit;
    }
    return;
}

$config = require $configFile;
if (!is_array($config)) {
    http_response_code(500);
    exit('Configuración inválida.');
}

date_default_timezone_set($config['timezone'] ?? 'America/Sao_Paulo');

if (!empty($config['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/schema_manager.php';
crm_security_headers();

set_exception_handler(static function (Throwable $exception) use ($config): void {
    $errorCode = crm_log_exception($exception);
    http_response_code(500);

    if (!empty($config['debug'])) {
        echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error del CRM</title><body style="font-family:Arial;padding:35px;background:#f2f6f8;color:#173247">';
        echo '<h1>Error del CRM</h1><p><strong>' . e($exception->getMessage()) . '</strong></p>';
        echo '<p>' . e($exception->getFile()) . ':' . (int)$exception->getLine() . '</p>';
        echo '<pre style="white-space:pre-wrap;background:#fff;padding:18px;border-radius:10px">' . e($exception->getTraceAsString()) . '</pre></body></html>';
        return;
    }

    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Error del CRM</title>';
    echo '<body style="font-family:Arial;padding:35px;background:#edf3f7;color:#173247"><div style="max-width:720px;margin:50px auto;background:#fff;border-radius:18px;padding:30px;box-shadow:0 18px 50px rgba(0,47,87,.12)">';
    echo '<h1 style="color:#002f57">El CRM encontró un problema</h1><p>El error fue registrado para poder identificarlo sin dejar una pantalla en blanco.</p>';
    echo '<p><strong>Código:</strong> ' . e($errorCode) . '</p>';
    if (!empty($_SESSION['user_id'])) {
        echo '<p><a style="display:inline-block;padding:12px 18px;border-radius:9px;background:#0074c7;color:#fff;text-decoration:none;font-weight:bold" href="repair.php">REVISAR Y REPARAR BASE DE DATOS</a></p>';
    }
    echo '</div></body></html>';
});

$script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
$schemaCheckExcluded = ['install.php', 'login.php', 'logout.php', 'repair.php', 'upgrade.php'];

if (!empty($_SESSION['user_id']) && !in_array($script, $schemaCheckExcluded, true)) {
    try {
        $health = crm_schema_health(db());
        if (!$health['ok']) {
            $_SESSION['crm_repair_return'] = $script . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            $_SESSION['crm_schema_missing'] = $health['missing'];
            header('Location: ' . app_url('repair.php'));
            exit;
        }
    } catch (Throwable $exception) {
        throw $exception;
    }
}
