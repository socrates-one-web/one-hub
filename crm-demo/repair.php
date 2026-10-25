<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
if (($user['role'] ?? '') !== 'admin' && !has_permission('settings.manage')) {
    http_response_code(403);
    exit('Solo un administrador puede reparar o actualizar el CRM.');
}

$pageTitle = 'Reparar CRM';
$errors = [];
$warnings = [];
$report = [];
$pdo = db();
$before = crm_schema_health($pdo);
$completed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $result = crm_apply_schema($pdo);
        $report = $result['report'];
        $warnings = $result['warnings'];
        $completed = true;
        unset($_SESSION['crm_schema_missing']);
        log_activity('CRM reparado y actualizado', null, null, 'Versión 2.1.0 Document Manager');
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
        crm_log_exception($exception);
    }
}

$after = crm_schema_health($pdo);
$returnUrl = (string)($_SESSION['crm_repair_return'] ?? 'dashboard.php');
if ($completed && $after['ok']) unset($_SESSION['crm_repair_return']);

require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><strong>No se pudo completar la reparación:</strong><br><?= e($error) ?></div>
<?php endforeach; ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Diagnóstico y reparación del CRM</h2>
            <span class="table-sub">Actualiza la estructura de forma aditiva: catálogo, Project Manager, Helpdesk y Gestión Documental, sin eliminar clientes, proyectos, archivos ni configuraciones.</span>
        </div>
        <span class="badge badge-<?= $after['ok'] ? 'green' : 'red' ?>"><?= $after['ok'] ? 'Base de datos correcta' : 'Reparación necesaria' ?></span>
    </div>
    <div class="panel-body">
        <?php if ($completed && $after['ok']): ?>
            <div class="alert alert-success"><strong>Reparación completada.</strong> Las nuevas páginas ya están disponibles.</div>
            <?php if ($warnings): ?>
                <div class="alert alert-warning"><strong>Advertencias no bloqueantes:</strong><ul><?php foreach ($warnings as $warning): ?><li><?= e($warning) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <div class="actions">
                <a class="btn btn-primary" href="<?= e($returnUrl) ?>">VOLVER AL CRM</a>
                <a class="btn btn-secondary" href="service_catalog.php">ABRIR MANTENEDOR DE SERVICIOS</a>
                <a class="btn btn-secondary" href="payment_methods.php">ABRIR FORMAS DE PAGO</a>
                <a class="btn btn-secondary" href="service_categories.php">ABRIR CATEGORÍAS</a>
                <a class="btn btn-secondary" href="contract_templates.php">ABRIR CONTRATOS</a>
                <a class="btn btn-secondary" href="deals.php">ABRIR PIPELINE</a>
                <a class="btn btn-secondary" href="contacts.php">ABRIR CONTACTOS</a>
                <a class="btn btn-secondary" href="billing.php">ABRIR FACTURACIÓN</a><a class="btn btn-secondary" href="security_center.php">ABRIR CENTRO DE SEGURIDAD</a><a class="btn btn-secondary" href="manual.php">ABRIR MANUAL</a><a class="btn btn-primary" href="document_dashboard.php">ABRIR DOCUMENT MANAGER</a>
            </div>
        <?php elseif ($after['ok']): ?>
            <div class="alert alert-success">La estructura de la base de datos está correcta.</div>
            <p>La base está correcta. Puedes volver a ejecutar la actualización 2.1.0 para verificar Document Manager, permisos, departamento y configuración sin eliminar información. No se borrarán clientes, negocios, servicios ni cotizaciones.</p>
            <form method="post" style="margin-top:18px">
                <?= csrf_field() ?>
                <button class="btn btn-primary">VERIFICAR / APLICAR ACTUALIZACIÓN 2.1.0</button>
                <a class="btn btn-secondary" href="dashboard.php">VOLVER AL DASHBOARD</a>
            </form>
        <?php else: ?>
            <p>La aplicación detectó que la actualización anterior no terminó completamente. Faltan los siguientes elementos:</p>
            <ul>
                <?php foreach ($after['missing'] as $missing): ?><li><?= e($missing) ?></li><?php endforeach; ?>
            </ul>
            <form method="post" style="margin-top:22px">
                <?= csrf_field() ?>
                <button class="btn btn-primary">REPARAR Y ACTUALIZAR AHORA</button>
            </form>
            <p class="table-sub" style="margin-top:14px">La actualización conserva la información existente y agrega los servicios que todavía no estén en el catálogo.</p>
        <?php endif; ?>
    </div>
</section>

<?php if ($report): ?>
<section class="panel" style="margin-top:20px">
    <div class="panel-header"><h2>Acciones realizadas</h2></div>
    <div class="panel-body"><ul><?php foreach ($report as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul></div>
</section>
<?php endif; ?>

<section class="panel" style="margin-top:20px">
    <div class="panel-header"><h2>Compatibilidad del servidor</h2></div>
    <div class="panel-body">
        <div class="table-responsive"><table><tbody>
            <tr><th>PHP</th><td><?= e(PHP_VERSION) ?></td><td><span class="badge badge-<?= version_compare(PHP_VERSION, '8.1.0', '>=') ? 'green' : 'red' ?>"><?= version_compare(PHP_VERSION, '8.1.0', '>=') ? 'Compatible' : 'Requiere PHP 8.1+' ?></span></td></tr>
            <tr><th>PDO MySQL</th><td><?= extension_loaded('pdo_mysql') ? 'Disponible' : 'No disponible' ?></td><td><span class="badge badge-<?= extension_loaded('pdo_mysql') ? 'green' : 'red' ?>"><?= extension_loaded('pdo_mysql') ? 'Correcto' : 'Activar extensión' ?></span></td></tr>
            <tr><th>OpenSSL</th><td><?= extension_loaded('openssl') ? 'Disponible' : 'No disponible' ?></td><td><span class="badge badge-<?= extension_loaded('openssl') ? 'green' : 'yellow' ?>"><?= extension_loaded('openssl') ? 'Correcto' : 'Necesario para SMTP/cifrado' ?></span></td></tr>
            <tr><th>Fileinfo</th><td><?= extension_loaded('fileinfo') ? 'Disponible' : 'No disponible' ?></td><td><span class="badge badge-<?= extension_loaded('fileinfo') ? 'green' : 'yellow' ?>"><?= extension_loaded('fileinfo') ? 'Correcto' : 'Necesario para logos' ?></span></td></tr>
            <tr><th>Mbstring</th><td><?= extension_loaded('mbstring') ? 'Disponible' : 'No disponible' ?></td><td><span class="badge badge-green"><?= extension_loaded('mbstring') ? 'Correcto' : 'Se usa compatibilidad interna' ?></span></td></tr>
        </tbody></table></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
