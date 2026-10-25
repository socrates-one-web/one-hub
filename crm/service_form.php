<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

$id = max(0, (int)($_GET['id'] ?? $_POST['id'] ?? 0));
require_permission($id ? 'services.edit' : 'services.assign');
$clientId = max(0, (int)($_GET['client_id'] ?? $_POST['client_id'] ?? 0));
$service = null;

if ($id) {
    $stmt = db()->prepare('SELECT s.*, c.company_name FROM client_services s JOIN clients c ON c.id=s.client_id WHERE s.id=?');
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    if (!$service) {
        http_response_code(404);
        exit('Servicio no encontrado.');
    }
    $clientId = (int)$service['client_id'];
}

$clients = db()->query("SELECT id,company_name,country FROM clients WHERE status<>'inactive' OR id=" . (int)$clientId . " ORDER BY COALESCE(NULLIF(country,''),'Sin país'),company_name")->fetchAll();
$catalog = db()->query("SELECT * FROM service_catalog WHERE is_active=1" . ($service && !empty($service['catalog_service_id']) ? ' OR id=' . (int)$service['catalog_service_id'] : '') . " ORDER BY category,name_es")->fetchAll();
$pageTitle = $service ? 'Editar servicio contratado' : 'Asignar servicio';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $contractType = (string)($_POST['contract_type'] ?? 'one_time');
    if (!in_array($contractType, ['monthly','annual','one_time','custom'], true)) $contractType = 'one_time';
    $currency = (string)($_POST['currency'] ?? 'USD');
    if (!in_array($currency, ['CLP','USD','BRL'], true)) $currency = 'USD';
    $status = (string)($_POST['status'] ?? 'active');
    if (!in_array($status, ['active','pending','completed','cancelled'], true)) $status = 'active';
    $paymentStatus = (string)($_POST['payment_status'] ?? 'pending');
    if (!in_array($paymentStatus, ['pending','unpaid','partial','paid'], true)) $paymentStatus = 'pending';

    $startDate = trim((string)($_POST['start_date'] ?? '')) ?: null;
    $expirationDate = trim((string)($_POST['expiration_date'] ?? '')) ?: null;
    $customMonths = ($_POST['custom_period_months'] ?? '') !== '' ? max(1, (int)$_POST['custom_period_months']) : null;
    if (!$expirationDate && $startDate) {
        $base = new DateTimeImmutable($startDate);
        $expirationDate = match ($contractType) {
            'monthly' => $base->modify('+1 month')->format('Y-m-d'),
            'annual' => $base->modify('+1 year')->format('Y-m-d'),
            'custom' => $base->modify('+' . max(1, (int)$customMonths) . ' months')->format('Y-m-d'),
            default => null,
        };
    }

    $paidAt = trim((string)($_POST['paid_at'] ?? '')) ?: null;
    if ($paymentStatus === 'paid' && !$paidAt) $paidAt = date('Y-m-d');
    if ($paymentStatus !== 'paid') $paidAt = null;

    $data = [
        'client_id' => max(0, (int)($_POST['client_id'] ?? 0)),
        'catalog_service_id' => ($_POST['catalog_service_id'] ?? '') !== '' ? (int)$_POST['catalog_service_id'] : null,
        'name' => trim((string)($_POST['name'] ?? '')),
        'category' => trim((string)($_POST['category'] ?? '')) ?: null,
        'description' => trim((string)($_POST['description'] ?? '')) ?: null,
        'contract_type' => $contractType,
        'custom_period_months' => $contractType === 'custom' ? $customMonths : null,
        'start_date' => $startDate,
        'expiration_date' => $expirationDate,
        'amount' => ($_POST['amount'] ?? '') !== '' ? max(0, (float)$_POST['amount']) : null,
        'currency' => $currency,
        'payment_method' => trim((string)($_POST['payment_method'] ?? '')) ?: null,
        'payment_status' => $paymentStatus,
        'paid_at' => $paidAt,
        'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0,
        'next_billing_date' => trim((string)($_POST['next_billing_date'] ?? '')) ?: ($expirationDate ?: ($contractType === 'one_time' ? $startDate : null)),
        'billing_status' => in_array((string)($_POST['billing_status'] ?? 'pending'), ['pending','paid','partial','overdue','cancelled','not_billable'], true) ? (string)$_POST['billing_status'] : 'pending',
        'alert_days' => trim((string)($_POST['alert_days'] ?? '30,15,7,3,1,0')) ?: '30,15,7,3,1,0',
        'billing_notify_email' => isset($_POST['billing_notify_email']) ? 1 : 0,
        'billing_notify_whatsapp' => isset($_POST['billing_notify_whatsapp']) ? 1 : 0,
        'billing_notify_inapp' => isset($_POST['billing_notify_inapp']) ? 1 : 0,
        'status' => $status,
        'notes' => trim((string)($_POST['notes'] ?? '')) ?: null,
    ];

    if ($data['client_id'] < 1) $errors[] = 'Selecciona un cliente.';
    if ($data['name'] === '') $errors[] = 'El nombre del servicio es obligatorio.';
    if ($contractType === 'custom' && !$customMonths) $errors[] = 'Indica la cantidad de meses del período personalizado.';
    if ($startDate && $expirationDate && $expirationDate < $startDate) $errors[] = 'La fecha de vencimiento no puede ser anterior a la fecha de inicio.';

    if (!$errors) {
        if ($id) {
            $sql = 'UPDATE client_services SET client_id=?,catalog_service_id=?,name=?,category=?,description=?,contract_type=?,custom_period_months=?,start_date=?,expiration_date=?,amount=?,currency=?,payment_method=?,payment_status=?,paid_at=?,auto_renew=?,next_billing_date=?,billing_status=?,alert_days=?,billing_notify_email=?,billing_notify_whatsapp=?,billing_notify_inapp=?,status=?,notes=?,alert_sent_for_date=IF(next_billing_date<=>?,alert_sent_for_date,NULL) WHERE id=?';
            db()->prepare($sql)->execute([
                $data['client_id'],$data['catalog_service_id'],$data['name'],$data['category'],$data['description'],$data['contract_type'],$data['custom_period_months'],$data['start_date'],$data['expiration_date'],$data['amount'],$data['currency'],$data['payment_method'],$data['payment_status'],$data['paid_at'],$data['auto_renew'],$data['next_billing_date'],$data['billing_status'],$data['alert_days'],$data['billing_notify_email'],$data['billing_notify_whatsapp'],$data['billing_notify_inapp'],$data['status'],$data['notes'],$data['next_billing_date'],$id,
            ]);
            log_activity('Servicio actualizado', $data['client_id'], $id, $data['name']);
            flash('success', 'Servicio actualizado correctamente.');
        } else {
            $sql = 'INSERT INTO client_services (client_id,catalog_service_id,name,category,description,contract_type,custom_period_months,start_date,expiration_date,amount,currency,payment_method,payment_status,paid_at,auto_renew,next_billing_date,billing_status,alert_days,billing_notify_email,billing_notify_whatsapp,billing_notify_inapp,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            db()->prepare($sql)->execute(array_values($data));
            $id = (int)db()->lastInsertId();
            log_activity('Servicio agregado', $data['client_id'], $id, $data['name']);
            flash('success', 'Servicio agregado correctamente.');
        }
        if (db_table_exists('billing_charges')) {
            if ($data['next_billing_date']) db()->prepare("UPDATE billing_charges SET status='cancelled' WHERE service_id=? AND status IN ('pending','overdue') AND due_date<>?")->execute([$id,$data['next_billing_date']]);
            $bs=db()->prepare('SELECT * FROM client_services WHERE id=?');$bs->execute([$id]);$billingService=$bs->fetch();if($billingService)billing_sync_charge($billingService);
        }
        redirect('client_view.php?id=' . $data['client_id']);
    }
}

$value = static fn(string $key, mixed $default = ''): mixed => $_POST[$key] ?? ($service[$key] ?? $default);
$catalogJson = [];
foreach ($catalog as $row) $catalogJson[(string)$row['id']] = $row;
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="panel">
    <div class="panel-header">
        <div><h2><?= $service ? 'Actualizar servicio' : 'Asignar servicio a un cliente' ?></h2><span class="table-sub">Selecciona un servicio del mantenedor o registra uno personalizado.</span></div>
        <a class="btn btn-light btn-small" href="<?= $clientId ? 'client_view.php?id=' . $clientId : 'services.php' ?>">Cancelar</a>
    </div>
    <div class="panel-body form-grid">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <label>Cliente *<select name="client_id" required><option value="">Seleccionar</option><?php foreach ($clients as $client): ?><option value="<?= (int)$client['id'] ?>" <?= (int)$value('client_id',$clientId)===(int)$client['id']?'selected':'' ?>><?= e(($client['country'] ?: 'Sin país') . ' · ' . $client['company_name']) ?></option><?php endforeach; ?></select></label>
        <label>Servicio del mantenedor<select name="catalog_service_id" data-service-catalog><option value="">Servicio personalizado</option><?php foreach ($catalog as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$value('catalog_service_id')===(int)$item['id']?'selected':'' ?>><?= e(($item['category'] ?: 'General') . ' · ' . $item['name_es']) ?></option><?php endforeach; ?></select></label>
        <label>Nombre del servicio *<input name="name" required value="<?= e($value('name')) ?>" data-service-name></label>
        <label>Categoría<input name="category" value="<?= e($value('category')) ?>" data-service-category></label>
        <label class="full">Descripción<textarea name="description" data-service-description><?= e($value('description')) ?></textarea></label>

        <div class="form-section full form-grid">
            <h3>Contrato y vigencia</h3>
            <label>Modalidad<select name="contract_type" data-service-contract><option value="monthly" <?= $value('contract_type')==='monthly'?'selected':'' ?>>Mensual</option><option value="annual" <?= $value('contract_type')==='annual'?'selected':'' ?>>Anual</option><option value="one_time" <?= $value('contract_type','one_time')==='one_time'?'selected':'' ?>>Única vez</option><option value="custom" <?= $value('contract_type')==='custom'?'selected':'' ?>>Personalizada</option></select></label>
            <label data-custom-period>Meses del período personalizado<input type="number" min="1" name="custom_period_months" value="<?= e($value('custom_period_months')) ?>" data-service-custom></label>
            <label>Fecha de inicio<input type="date" name="start_date" value="<?= e($value('start_date')) ?>"></label>
            <label>Fecha de vencimiento<input type="date" name="expiration_date" value="<?= e($value('expiration_date')) ?>"><span class="table-sub">Si queda vacía, se calcula según la modalidad.</span></label>
            <label class="checkbox-row"><input type="checkbox" name="auto_renew" value="1" <?= $value('auto_renew')?'checked':'' ?>> Renovación automática</label>
        </div>

        <div class="form-section full form-grid">
            <h3>Valor, cobro y estado</h3>
            <label>Moneda<select name="currency" data-service-currency><option value="CLP" <?= $value('currency')==='CLP'?'selected':'' ?>>Pesos chilenos (CLP)</option><option value="USD" <?= $value('currency','USD')==='USD'?'selected':'' ?>>Dólares (USD)</option><option value="BRL" <?= $value('currency')==='BRL'?'selected':'' ?>>Reales (BRL)</option></select></label>
            <label>Valor<input type="number" step="0.01" min="0" name="amount" value="<?= e($value('amount')) ?>" data-service-amount></label>
            <label>Forma de pago<input name="payment_method" value="<?= e($value('payment_method')) ?>" placeholder="Transferencia, tarjeta, PIX..."></label>
            <label>Estado del pago<select name="payment_status"><option value="pending" <?= $value('payment_status','pending')==='pending'?'selected':'' ?>>Pendiente</option><option value="unpaid" <?= $value('payment_status')==='unpaid'?'selected':'' ?>>Sin pagar</option><option value="partial" <?= $value('payment_status')==='partial'?'selected':'' ?>>Pago parcial</option><option value="paid" <?= $value('payment_status')==='paid'?'selected':'' ?>>Pagado</option></select></label>
            <label>Fecha de pago<input type="date" name="paid_at" value="<?= e($value('paid_at')) ?>"><span class="table-sub">Se completa automáticamente con hoy al marcar Pagado.</span></label>
            <label>Próximo cobro<input type="date" name="next_billing_date" value="<?= e($value('next_billing_date',$value('expiration_date'))) ?>"><span class="table-sub">Fecha que alimenta Facturación y alertas.</span></label>
            <label>Estado de facturación<select name="billing_status"><?php foreach(['pending'=>'Pendiente','paid'=>'Pagado','partial'=>'Pago parcial','overdue'=>'Vencido','not_billable'=>'No facturable','cancelled'=>'Cancelado'] as$bs=>$bl):?><option value="<?=$bs?>" <?=$value('billing_status','pending')===$bs?'selected':''?>><?=$bl?></option><?php endforeach;?></select></label>
            <label>Días de alerta<input name="alert_days" value="<?= e($value('alert_days','30,15,7,3,1,0')) ?>" placeholder="30,15,7,3,1,0"><span class="table-sub">Separados por coma. 0 = día del vencimiento.</span></label>
            <label class="checkbox-row"><input type="checkbox" name="billing_notify_email" value="1" <?= $value('billing_notify_email',1)?'checked':'' ?>> Avisar por email</label>
            <label class="checkbox-row"><input type="checkbox" name="billing_notify_inapp" value="1" <?= $value('billing_notify_inapp',1)?'checked':'' ?>> Avisar dentro del CRM</label>
            <label class="checkbox-row"><input type="checkbox" name="billing_notify_whatsapp" value="1" <?= $value('billing_notify_whatsapp',0)?'checked':'' ?>> Avisar por WhatsApp</label>
            <label>Estado del servicio<select name="status"><option value="active" <?= $value('status','active')==='active'?'selected':'' ?>>Activo</option><option value="pending" <?= $value('status')==='pending'?'selected':'' ?>>Pendiente</option><option value="completed" <?= $value('status')==='completed'?'selected':'' ?>>Finalizado</option><option value="cancelled" <?= $value('status')==='cancelled'?'selected':'' ?>>Cancelado</option></select></label>
            <label class="full">Notas internas<textarea name="notes"><?= e($value('notes')) ?></textarea></label>
        </div>

        <div class="full form-actions">
            <?php if ($service && has_permission('services.delete')): ?><button class="btn btn-danger" type="submit" formaction="service_delete.php" data-confirm="¿Eliminar definitivamente este servicio?">Eliminar</button><?php endif; ?>
            <a class="btn btn-light" href="<?= $clientId ? 'client_view.php?id=' . $clientId : 'services.php' ?>">Cancelar</a>
            <button class="btn btn-primary">GUARDAR SERVICIO</button>
        </div>
    </div>
</form>
<script type="application/json" id="service-catalog-data"><?= json_encode($catalogJson, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
