<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_permission('payment_methods.view');

$pageTitle = 'Formas de pago';
$q = trim((string)($_GET['q'] ?? ''));
$country = trim((string)($_GET['country'] ?? ''));
$currency = (string)($_GET['currency'] ?? '');
$state = (string)($_GET['state'] ?? 'active');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR details LIKE ? OR country LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($country !== '') {
    $where[] = 'country = ?';
    $params[] = $country;
}
if (in_array($currency, ['CLP','USD','BRL'], true)) {
    $where[] = 'currency = ?';
    $params[] = $currency;
}
if ($state === 'active') $where[] = 'is_active = 1';
elseif ($state === 'inactive') $where[] = 'is_active = 0';

$sql = 'SELECT * FROM payment_methods' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY COALESCE(NULLIF(country,''),'Todos los países'), currency, sort_order, name";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$methods = $stmt->fetchAll();
$countries = db()->query("SELECT DISTINCT country FROM payment_methods WHERE country IS NOT NULL AND country <> '' ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);

$languageLabels = ['es' => 'Español', 'pt' => 'Português', 'en' => 'English'];
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Mantenedor de formas de pago</h2>
            <span class="table-sub"><?= count($methods) ?> opciones encontradas. Guarda datos bancarios, PIX, Stripe, PayPal, links de pago o cualquier instrucción.</span>
        </div>
        <?php if (has_permission('payment_methods.manage')): ?>
            <a class="btn btn-primary" href="payment_method_form.php">+ CREAR FORMA DE PAGO</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <form method="get" class="filters">
            <input name="q" value="<?= e($q) ?>" placeholder="Buscar nombre, país o información">
            <select name="country">
                <option value="">Todos los países</option>
                <?php foreach ($countries as $item): ?>
                    <option value="<?= e($item) ?>" <?= $country === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="currency">
                <option value="">Todas las monedas</option>
                <?php foreach (['CLP','USD','BRL'] as $item): ?>
                    <option value="<?= $item ?>" <?= $currency === $item ? 'selected' : '' ?>><?= $item ?></option>
                <?php endforeach; ?>
            </select>
            <select name="state">
                <option value="active" <?= $state === 'active' ? 'selected' : '' ?>>Activas</option>
                <option value="all" <?= $state === 'all' ? 'selected' : '' ?>>Todas</option>
                <option value="inactive" <?= $state === 'inactive' ? 'selected' : '' ?>>Inactivas</option>
            </select>
            <button class="btn btn-secondary btn-small">Filtrar</button>
        </form>
    </div>
</section>

<?php if (!$methods): ?>
    <section class="panel"><div class="empty">Todavía no hay formas de pago guardadas.<?php if (has_permission('payment_methods.manage')): ?> <a href="payment_method_form.php">Crea la primera</a>.<?php endif; ?></div></section>
<?php else: ?>
    <div class="payment-method-grid" style="margin-top:20px">
        <?php foreach ($methods as $method): ?>
            <article class="payment-method-card">
                <div class="payment-method-card-top">
                    <div>
                        <span class="category"><?= e($method['country'] ?: 'Todos los países') ?></span>
                        <h3><?= e($method['name']) ?></h3>
                    </div>
                    <div class="actions">
                        <?php if ($method['is_default']): ?><span class="badge badge-blue">Predeterminada</span><?php endif; ?>
                        <span class="badge badge-<?= $method['is_active'] ? 'green' : 'gray' ?>"><?= $method['is_active'] ? 'Activa' : 'Inactiva' ?></span>
                    </div>
                </div>
                <div class="payment-method-meta">
                    <span><?= e($method['currency']) ?></span>
                    <span><?= e($languageLabels[$method['language']] ?? $method['language']) ?></span>
                    <span>Orden <?= (int)$method['sort_order'] ?></span>
                </div>
                <pre class="payment-method-preview"><?= e(crm_strimwidth((string)$method['details'], 420, '…')) ?></pre>
                <?php if (has_permission('payment_methods.manage') || has_permission('payment_methods.send')): ?>
                    <div class="actions" style="margin-top:16px">
                        <?php if (has_permission('payment_methods.send')): ?><a class="btn btn-secondary btn-small" href="payment_method_send.php?id=<?= (int)$method['id'] ?>">Enviar</a><?php endif; ?>
                        <?php if (has_permission('payment_methods.manage')): ?><a class="btn btn-light btn-small" href="payment_method_form.php?id=<?= (int)$method['id'] ?>">Editar</a>
                        <form method="post" action="payment_method_toggle.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$method['id'] ?>">
                            <button class="btn btn-small <?= $method['is_active'] ? 'btn-danger' : 'btn-success' ?>" data-confirm="¿Confirmar el cambio de estado?"><?= $method['is_active'] ? 'Desactivar' : 'Activar' ?></button>
                        </form><?php endif; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
