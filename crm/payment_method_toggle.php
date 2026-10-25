<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_permission('payment_methods.manage');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT name,is_active FROM payment_methods WHERE id=?');
$stmt->execute([$id]);
$method = $stmt->fetch();
if (!$method) { flash('error', 'Forma de pago no encontrada.'); redirect('payment_methods.php'); }
$new = $method['is_active'] ? 0 : 1;
db()->prepare('UPDATE payment_methods SET is_active=? WHERE id=?')->execute([$new, $id]);
log_activity($new ? 'Forma de pago activada' : 'Forma de pago desactivada', null, null, $method['name']);
flash('success', $new ? 'Forma de pago activada.' : 'Forma de pago desactivada.');
redirect('payment_methods.php');
