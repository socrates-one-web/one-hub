<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_permission('services.view');

$pageTitle = 'Servicios de clientes';
$q = trim((string)($_GET['q'] ?? ''));
$contract = (string)($_GET['contract'] ?? '');
$view = (string)($_GET['view'] ?? 'all');
$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(s.name LIKE ? OR s.category LIKE ? OR c.company_name LIKE ? OR c.country LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($contract, ['monthly', 'annual', 'one_time', 'custom'], true)) {
    $where[] = 's.contract_type = ?';
    $params[] = $contract;
}
$where[] = match ($view) {
    'active' => "s.status='active' AND (s.expiration_date IS NULL OR s.expiration_date >= CURDATE())",
    'upcoming' => "s.status='active' AND s.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
    'expired' => "s.status='active' AND s.expiration_date < CURDATE()",
    'closed' => "s.status IN ('completed','cancelled')",
    default => '1=1',
};

$sql = "SELECT s.*, c.company_name, c.country
        FROM client_services s
        JOIN clients c ON c.id = s.client_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY COALESCE(NULLIF(c.country,''),'Sin país'), c.company_name, s.expiration_date";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="alert alert-info service-explainer">
    <strong>¿Qué se administra aquí?</strong>
    Esta sección muestra los servicios que cada cliente realmente tiene contratados, con su valor, modalidad, fecha de inicio, vencimiento y estado. El <a href="service_catalog.php"><strong>Catálogo</strong></a> es la lista maestra de servicios disponibles para asignar o cotizar.
</div>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Servicios asignados a clientes</h2>
            <span class="table-sub"><?= count($services) ?> registros encontrados, ordenados por país y cliente.</span>
        </div>
        <div class="actions">
            <?php if (has_permission('catalog.view')): ?><a class="btn btn-light" href="service_catalog.php">VER CATÁLOGO</a><?php endif; ?>
            <?php if (has_permission('services.assign')): ?><a class="btn btn-primary" href="service_form.php">+ ASIGNAR SERVICIO</a><?php endif; ?>
        </div>
    </div>
    <div class="panel-body">
        <form method="get" class="filters">
            <input name="q" value="<?= e($q) ?>" placeholder="Buscar servicio, cliente o país">
            <select name="contract">
                <option value="">Todos los contratos</option>
                <option value="monthly" <?= $contract === 'monthly' ? 'selected' : '' ?>>Mensual</option>
                <option value="annual" <?= $contract === 'annual' ? 'selected' : '' ?>>Anual</option>
                <option value="one_time" <?= $contract === 'one_time' ? 'selected' : '' ?>>Única vez</option>
                <option value="custom" <?= $contract === 'custom' ? 'selected' : '' ?>>Personalizado</option>
            </select>
            <select name="view">
                <option value="all" <?= $view === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="active" <?= $view === 'active' ? 'selected' : '' ?>>Activos</option>
                <option value="upcoming" <?= $view === 'upcoming' ? 'selected' : '' ?>>Próximos a vencer</option>
                <option value="expired" <?= $view === 'expired' ? 'selected' : '' ?>>Vencidos</option>
                <option value="closed" <?= $view === 'closed' ? 'selected' : '' ?>>Finalizados</option>
            </select>
            <button class="btn btn-secondary btn-small">Filtrar</button>
        </form>
    </div>
    <div class="table-wrap">
        <?php if (!$services): ?>
            <div class="empty">No se encontraron servicios asignados a clientes.</div>
        <?php else: ?>
            <table class="data-table">
                <thead><tr><th>País</th><th>Cliente / servicio</th><th>Modalidad</th><th>Inicio</th><th>Vencimiento</th><th>Valor</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($services as $service): $state = computed_service_status($service); ?>
                    <tr>
                        <td><?= e($service['country'] ?: 'Sin país') ?></td>
                        <td><a class="table-title" href="client_view.php?id=<?= (int)$service['client_id'] ?>"><?= e($service['company_name']) ?></a><span class="table-sub"><?= e($service['name']) ?></span></td>
                        <td><?= e(contract_label($service['contract_type'])) ?></td>
                        <td><?= e(format_date($service['start_date'])) ?></td>
                        <td><?= e(format_date($service['expiration_date'])) ?></td>
                        <td><?= e(format_money($service['amount'], $service['currency'])) ?></td>
                        <td><span class="badge badge-<?= e($state['class']) ?>"><?= e($state['label']) ?></span></td>
                        <td><div class="actions">
                            <?php if (has_permission('services.edit')): ?><a class="btn btn-light btn-small" href="service_form.php?id=<?= (int)$service['id'] ?>">Editar</a><?php endif; ?>
                            <?php if (has_permission('services.renew')): ?><a class="btn btn-secondary btn-small" href="service_renew.php?id=<?= (int)$service['id'] ?>">Renovar</a><?php endif; ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
