<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_permission('payment_methods.manage');

$id = max(0, (int)($_GET['id'] ?? $_POST['id'] ?? 0));
$method = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM payment_methods WHERE id = ?');
    $stmt->execute([$id]);
    $method = $stmt->fetch();
    if (!$method) {
        http_response_code(404);
        exit('Forma de pago no encontrada.');
    }
}

$pageTitle = $method ? 'Editar forma de pago' : 'Nueva forma de pago';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'country' => trim((string)($_POST['country'] ?? '')),
        'currency' => (string)($_POST['currency'] ?? 'USD'),
        'language' => (string)($_POST['language'] ?? 'es'),
        'details' => trim((string)($_POST['details'] ?? '')),
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'sort_order' => max(0, (int)($_POST['sort_order'] ?? 0)),
    ];

    if ($data['name'] === '') $errors[] = 'El nombre visible es obligatorio.';
    if ($data['details'] === '') $errors[] = 'Agrega la información o instrucciones de pago.';
    if (!in_array($data['currency'], ['CLP','USD','BRL'], true)) $data['currency'] = 'USD';
    if (!in_array($data['language'], ['es','pt','en'], true)) $data['language'] = 'es';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($data['is_default']) {
                if ($data['country'] === '') {
                    $stmt = $pdo->prepare("UPDATE payment_methods SET is_default=0 WHERE currency=? AND language=? AND (country IS NULL OR country='') AND id<>?");
                } else {
                    $stmt = $pdo->prepare('UPDATE payment_methods SET is_default=0 WHERE currency=? AND language=? AND country=? AND id<>?');
                }
                $params = $data['country'] === ''
                    ? [$data['currency'], $data['language'], $id]
                    : [$data['currency'], $data['language'], $data['country'], $id];
                $stmt->execute($params);
            }

            if ($id) {
                $pdo->prepare('UPDATE payment_methods SET name=?, country=?, currency=?, language=?, details=?, is_default=?, is_active=?, sort_order=? WHERE id=?')
                    ->execute([...array_values($data), $id]);
                log_activity('Forma de pago actualizada', null, null, $data['name']);
                flash('success', 'Forma de pago actualizada correctamente.');
            } else {
                $pdo->prepare('INSERT INTO payment_methods (name,country,currency,language,details,is_default,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute(array_values($data));
                $id = (int)$pdo->lastInsertId();
                log_activity('Forma de pago creada', null, null, $data['name']);
                flash('success', 'Forma de pago creada. Ya puede seleccionarse en las cotizaciones.');
            }
            $pdo->commit();
            redirect('payment_methods.php');
        } catch (Throwable $exception) {
            $pdo->rollBack();
            $errors[] = $exception->getMessage();
        }
    }
}

$v = fn(string $key, mixed $default = ''): mixed => $_POST[$key] ?? ($method[$key] ?? $default);
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $method ? 'Actualizar forma de pago' : 'Crear forma de pago reutilizable' ?></h2>
            <span class="table-sub">La información queda guardada para insertarla en futuras cotizaciones con un solo clic.</span>
        </div>
        <a class="btn btn-light btn-small" href="payment_methods.php">Cancelar</a>
    </div>
    <div class="panel-body form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-section full form-grid two">
            <h3>Identificación</h3>
            <label>Nombre visible *
                <input name="name" required value="<?= e($v('name')) ?>" placeholder="PIX, Transferencia Banco de Chile, Stripe USA...">
            </label>
            <label>País
                <input name="country" list="payment-country-list" value="<?= e($v('country')) ?>" placeholder="Chile, Brasil, Estados Unidos o vacío para todos">
                <datalist id="payment-country-list"><option value="Chile"><option value="Brasil"><option value="Estados Unidos"><option value="Paraguay"><option value="Argentina"><option value="Colombia"><option value="Perú"><option value="Uruguay"><option value="México"><option value="España"></datalist>
                <span class="table-sub">Déjalo vacío cuando la opción pueda utilizarse en cualquier país.</span>
            </label>
            <label>Moneda
                <select name="currency">
                    <option value="CLP" <?= $v('currency') === 'CLP' ? 'selected' : '' ?>>Pesos chilenos (CLP)</option>
                    <option value="USD" <?= $v('currency', 'USD') === 'USD' ? 'selected' : '' ?>>Dólares (USD)</option>
                    <option value="BRL" <?= $v('currency') === 'BRL' ? 'selected' : '' ?>>Reales (BRL)</option>
                </select>
            </label>
            <label>Idioma de la información
                <select name="language">
                    <option value="es" <?= $v('language', 'es') === 'es' ? 'selected' : '' ?>>Español</option>
                    <option value="pt" <?= $v('language') === 'pt' ? 'selected' : '' ?>>Português</option>
                    <option value="en" <?= $v('language') === 'en' ? 'selected' : '' ?>>English</option>
                </select>
            </label>
            <label>Orden
                <input type="number" min="0" name="sort_order" value="<?= e($v('sort_order', 0)) ?>">
            </label>
            <div class="form-toggle-stack">
                <label class="checkbox-row"><input type="checkbox" name="is_default" value="1" <?= $v('is_default', 0) ? 'checked' : '' ?>> Predeterminada para este país, moneda e idioma</label>
                <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?= $v('is_active', 1) ? 'checked' : '' ?>> Forma de pago activa</label>
            </div>
        </div>

        <div class="form-section full">
            <h3>Información que aparecerá en la cotización</h3>
            <label>Datos e instrucciones *</label>
            <textarea name="details" rows="14" required placeholder="Banco:&#10;Tipo de cuenta:&#10;Número de cuenta:&#10;Titular:&#10;RUT / CNPJ / Tax ID:&#10;Email:&#10;&#10;También puedes agregar links, plazos, instrucciones o cualquier información necesaria."><?= e($v('details')) ?></textarea>
            <p class="table-sub">Se respetarán los saltos de línea. Esta información se copia a la cotización, por lo que una cotización antigua no cambia aunque luego edites la forma de pago.</p>
        </div>

        <div class="full form-actions">
            <?php if ($method): ?>
                <button class="btn btn-danger" type="submit" formaction="payment_method_delete.php" data-confirm="¿Eliminar esta forma de pago? Si ya está usada se desactivará para conservar las cotizaciones antiguas.">Eliminar</button>
            <?php endif; ?>
            <a class="btn btn-light" href="payment_methods.php">Cancelar</a>
            <button class="btn btn-primary">GUARDAR FORMA DE PAGO</button>
        </div>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
