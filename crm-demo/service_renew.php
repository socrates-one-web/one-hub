<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_permission('services.renew');

$id = max(1, (int)($_GET['id'] ?? $_POST['id'] ?? 0));
$stmt = db()->prepare('SELECT s.*,c.company_name FROM client_services s JOIN clients c ON c.id=s.client_id WHERE s.id=?');
$stmt->execute([$id]);
$service = $stmt->fetch();
if (!$service) { http_response_code(404); exit('Servicio no encontrado.'); }

$pageTitle = 'Renovar servicio';
$errors = [];
$base = $service['expiration_date'] && $service['expiration_date'] >= date('Y-m-d') ? new DateTimeImmutable($service['expiration_date']) : new DateTimeImmutable('today');
$suggested = match ($service['contract_type']) {
    'monthly' => $base->modify('+1 month')->format('Y-m-d'),
    'annual' => $base->modify('+1 year')->format('Y-m-d'),
    'custom' => $base->modify('+' . max(1,(int)$service['custom_period_months']) . ' months')->format('Y-m-d'),
    default => $base->format('Y-m-d'),
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $newDate = (string)($_POST['new_expiration_date'] ?? '');
    $amount = ($_POST['amount'] ?? '') !== '' ? (float)$_POST['amount'] : null;
    $paymentStatus = (string)($_POST['payment_status'] ?? 'pending');
    if (!in_array($paymentStatus, ['pending','unpaid','partial','paid'], true)) $paymentStatus = 'pending';
    $paidAt = trim((string)($_POST['paid_at'] ?? '')) ?: null;
    if ($paymentStatus === 'paid' && !$paidAt) $paidAt = date('Y-m-d');
    if ($paymentStatus !== 'paid') $paidAt = null;
    $notes = trim((string)($_POST['notes'] ?? ''));

    if (!$newDate) $errors[] = 'Selecciona la nueva fecha de vencimiento.';
    elseif ($service['expiration_date'] && $newDate <= $service['expiration_date']) $errors[] = 'La nueva fecha debe ser posterior al vencimiento anterior.';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO service_renewals (service_id,previous_expiration_date,new_expiration_date,amount,notes,created_by) VALUES (?,?,?,?,?,?)')
                ->execute([$id,$service['expiration_date'],$newDate,$amount,$notes,$_SESSION['user_id']]);
            $pdo->prepare("UPDATE client_services SET expiration_date=?,next_billing_date=?,amount=COALESCE(?,amount),status='active',payment_status=?,billing_status=?,paid_at=?,alert_sent_for_date=NULL WHERE id=?")
                ->execute([$newDate,$newDate,$amount,$paymentStatus,$paymentStatus==='paid'?'paid':'pending',$paidAt,$id]);
            $pdo->commit();
            if(db_table_exists('billing_charges')){ $pdo->prepare("UPDATE billing_charges SET status='cancelled' WHERE service_id=? AND status IN ('pending','overdue') AND due_date<>?")->execute([$id,$newDate]); $bs=$pdo->prepare('SELECT * FROM client_services WHERE id=?');$bs->execute([$id]);$row=$bs->fetch();if($row)billing_sync_charge($row); }
            log_activity('Servicio renovado',(int)$service['client_id'],$id,$service['name'].' hasta '.format_date($newDate));
            flash('success','Servicio renovado y registrado en el historial.');
            redirect('client_view.php?id='.$service['client_id']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

$historyStmt = db()->prepare('SELECT r.*,u.name user_name FROM service_renewals r LEFT JOIN users u ON u.id=r.created_by WHERE r.service_id=? ORDER BY r.created_at DESC');
$historyStmt->execute([$id]);
$history = $historyStmt->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
<div class="dashboard-grid">
<form method="post" class="panel">
    <div class="panel-header"><div><h2><?= e($service['name']) ?></h2><span class="table-sub"><?= e($service['company_name']) ?></span></div></div>
    <div class="panel-body form-grid">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <label>Vencimiento actual<input value="<?= e(format_date($service['expiration_date'])) ?>" disabled></label>
        <label>Nueva fecha de vencimiento *<input type="date" name="new_expiration_date" required value="<?= e($_POST['new_expiration_date'] ?? $suggested) ?>"></label>
        <label>Nuevo valor (opcional)<input type="number" step="0.01" min="0" name="amount" value="<?= e($_POST['amount'] ?? $service['amount']) ?>"></label>
        <label>Moneda<input value="<?= e($service['currency']) ?>" disabled></label>
        <label>Estado del pago<select name="payment_status"><option value="pending" <?= ($_POST['payment_status']??'pending')==='pending'?'selected':'' ?>>Pendiente</option><option value="unpaid" <?= ($_POST['payment_status']??'')==='unpaid'?'selected':'' ?>>Sin pagar</option><option value="partial" <?= ($_POST['payment_status']??'')==='partial'?'selected':'' ?>>Pago parcial</option><option value="paid" <?= ($_POST['payment_status']??'')==='paid'?'selected':'' ?>>Pagado</option></select></label>
        <label>Fecha de pago<input type="date" name="paid_at" value="<?= e($_POST['paid_at'] ?? '') ?>"></label>
        <label class="full">Observaciones<textarea name="notes" placeholder="Detalles de pago, negociación o renovación..."><?= e($_POST['notes'] ?? '') ?></textarea></label>
        <div class="full form-actions"><a class="btn btn-light" href="client_view.php?id=<?= (int)$service['client_id'] ?>">Cancelar</a><button class="btn btn-primary">CONFIRMAR RENOVACIÓN</button></div>
    </div>
</form>
<section class="panel"><div class="panel-header"><h2>Historial de renovaciones</h2></div><div class="panel-body timeline">
<?php if (!$history): ?><div class="empty">Este servicio todavía no tiene renovaciones.</div><?php endif; ?>
<?php foreach ($history as $row): ?><div class="timeline-item"><strong><?= e(format_date($row['previous_expiration_date'])) ?> → <?= e(format_date($row['new_expiration_date'])) ?></strong><small><?= e($row['user_name'] ?: 'Sistema') ?> · <?= e((new DateTimeImmutable($row['created_at']))->format('d/m/Y H:i')) ?></small><?php if ($row['amount'] !== null): ?><div class="table-sub">Valor: <?= e(format_money($row['amount'],$service['currency'])) ?></div><?php endif; ?><?php if ($row['notes']): ?><div class="table-sub"><?= e($row['notes']) ?></div><?php endif; ?></div><?php endforeach; ?>
</div></section></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
