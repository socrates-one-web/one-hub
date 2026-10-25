<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_permission('payment_methods.manage');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT name FROM payment_methods WHERE id=?');
$stmt->execute([$id]);
$method = $stmt->fetch();
if (!$method) { flash('error', 'Forma de pago no encontrada.'); redirect('payment_methods.php'); }
$stmt = db()->prepare('SELECT COUNT(*) FROM quotations WHERE payment_method_id=?');
$stmt->execute([$id]);
$used = (int)$stmt->fetchColumn();
if ($used > 0) {
    db()->prepare('UPDATE payment_methods SET is_active=0 WHERE id=?')->execute([$id]);
    flash('success', 'La forma de pago estaba asociada a cotizaciones. Se desactivó para conservar el historial.');
    log_activity('Forma de pago desactivada por uso histórico', null, null, $method['name']);
} else {
    db()->prepare('DELETE FROM payment_methods WHERE id=?')->execute([$id]);
    flash('success', 'Forma de pago eliminada.');
    log_activity('Forma de pago eliminada', null, null, $method['name']);
}
redirect('payment_methods.php');
