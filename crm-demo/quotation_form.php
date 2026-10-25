<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

$id = max(0, (int)($_GET['id'] ?? $_POST['id'] ?? 0));
require_permission($id ? 'quotes.edit' : 'quotes.create');

$quote = null;
$existingItems = [];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM quotations WHERE id=?');
    $stmt->execute([$id]);
    $quote = $stmt->fetch();
    if (!$quote) {
        http_response_code(404);
        exit('Cotización no encontrada.');
    }
    $stmt = db()->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order,id');
    $stmt->execute([$id]);
    $existingItems = $stmt->fetchAll();
}

$pageTitle = $quote ? 'Editar cotización' : 'Nueva cotización';
$clients = db()->query("SELECT * FROM clients ORDER BY COALESCE(NULLIF(country,''),'Sin país'), company_name")->fetchAll();
$users = db()->query("SELECT id,name FROM users WHERE is_active=1 ORDER BY name")->fetchAll();
$deals = db()->query("SELECT id,title,client_id,owner_user_id FROM deals WHERE status='open' ORDER BY updated_at DESC,title")->fetchAll();
$catalog = db()->query("SELECT * FROM service_catalog WHERE is_active=1 ORDER BY item_type, COALESCE(NULLIF(category,''),'Sin categoría'), name_es")->fetchAll();
$paymentStmt = db()->prepare("SELECT * FROM payment_methods WHERE is_active=1 OR id=? ORDER BY is_default DESC, COALESCE(NULLIF(country,''),'Todos los países'), currency, language, sort_order, name");
$paymentStmt->execute([(int)($quote['payment_method_id'] ?? 0)]);
$paymentMethods = $paymentStmt->fetchAll();
$errors = [];

$selectedClientId = max(0, (int)($_GET['client_id'] ?? $_POST['client_id'] ?? ($quote['client_id'] ?? 0)));
$selectedClient = null;
foreach ($clients as $client) {
    if ((int)$client['id'] === $selectedClientId) {
        $selectedClient = $client;
        break;
    }
}

function country_currency(?string $country): string
{
    $c = crm_lower(trim((string)$country));
    if (str_contains($c, 'chile')) return 'CLP';
    if (str_contains($c, 'brasil') || str_contains($c, 'brazil')) return 'BRL';
    return 'USD';
}

$catalogJson = [];
foreach ($catalog as $service) $catalogJson[(string)$service['id']] = $service;
$paymentMethodsJson = [];
foreach ($paymentMethods as $method) $paymentMethodsJson[(string)$method['id']] = $method;

$get = function (string $key, mixed $default = '') use ($quote, $selectedClient) {
    if (array_key_exists($key, $_POST)) return $_POST[$key];
    if ($quote && array_key_exists($key, $quote)) return $quote[$key];
    return match ($key) {
        'client_id' => $selectedClient['id'] ?? 0,
        'recipient_name' => $selectedClient['contact_name'] ?? '',
        'recipient_email' => $selectedClient['email'] ?? '',
        'language' => $selectedClient['preferred_language'] ?? 'es',
        'currency' => country_currency($selectedClient['country'] ?? ''),
        'issue_date' => date('Y-m-d'),
        'valid_until' => (new DateTimeImmutable('+15 days'))->format('Y-m-d'),
        'status' => 'draft',
        'owner_user_id' => (int)($_SESSION['user_id'] ?? 0),
        'deal_id' => max(0,(int)($_GET['deal_id'] ?? 0)),
        'tax_rate' => 0,
        'discount_amount' => 0,
        'show_payment_method' => 1,
        'terms' => (string)setting('quote_terms_' . ($selectedClient['preferred_language'] ?? 'es'), ''),
        default => $default,
    };
};

$initialCurrency = (string)$get('currency', 'CLP');
$initialLanguage = (string)$get('language', 'es');
$initialCountry = trim((string)($selectedClient['country'] ?? ''));
$defaultPaymentMethod = null;
if (!$quote && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $bestScore = -1;
    foreach ($paymentMethods as $method) {
        if (!(int)$method['is_active'] || $method['currency'] !== $initialCurrency) continue;
        $methodCountry = trim((string)$method['country']);
        $score = 1;
        if ($method['language'] === $initialLanguage) $score += 4;
        if ($methodCountry === '') $score += 1;
        elseif ($initialCountry !== '' && crm_lower($methodCountry) === crm_lower($initialCountry)) $score += 8;
        else continue;
        if ((int)$method['is_default']) $score += 16;
        $score -= min(10, (int)$method['sort_order']) / 100;
        if ($score > $bestScore) { $bestScore = $score; $defaultPaymentMethod = $method; }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $clientId = (int)($_POST['client_id'] ?? 0);
    $language = (string)($_POST['language'] ?? 'es');
    $currency = (string)($_POST['currency'] ?? 'CLP');
    $issueDate = (string)($_POST['issue_date'] ?? date('Y-m-d'));
    $validUntil = ($_POST['valid_until'] ?? '') ?: null;
    $status = (string)($_POST['status'] ?? 'draft');
    $recipientName = trim((string)($_POST['recipient_name'] ?? ''));
    $recipientEmail = strtolower(trim((string)($_POST['recipient_email'] ?? '')));
    $notes = trim((string)($_POST['notes'] ?? ''));
    $terms = trim((string)($_POST['terms'] ?? ''));
    $discount = max(0, (float)($_POST['discount_amount'] ?? 0));
    $taxRate = max(0, (float)($_POST['tax_rate'] ?? 0));
    $paymentMethodId = max(0, (int)($_POST['payment_method_id'] ?? 0)) ?: null;
    $paymentMethodTitle = trim((string)($_POST['payment_method_title'] ?? ''));
    $paymentMethodDetails = trim((string)($_POST['payment_method_details'] ?? ''));
    $showPaymentMethod = isset($_POST['show_payment_method']) ? 1 : 0;
    $ownerUserId = max(0,(int)($_POST['owner_user_id'] ?? 0)) ?: null;
    $dealId = max(0,(int)($_POST['deal_id'] ?? 0)) ?: null;
    if ($paymentMethodId) {
        $selectedPaymentStmt = db()->prepare('SELECT name,details FROM payment_methods WHERE id=?');
        $selectedPaymentStmt->execute([$paymentMethodId]);
        $selectedPayment = $selectedPaymentStmt->fetch();
        if (!$selectedPayment) {
            $paymentMethodId = null;
        } else {
            if ($paymentMethodTitle === '') $paymentMethodTitle = (string)$selectedPayment['name'];
            if ($paymentMethodDetails === '') $paymentMethodDetails = (string)$selectedPayment['details'];
        }
    }

    if (!in_array($language, ['es', 'pt', 'en'], true)) $language = 'es';
    if (!in_array($currency, ['CLP', 'USD', 'BRL'], true)) $currency = 'CLP';
    if (!in_array($status, ['draft', 'sent', 'accepted', 'rejected', 'expired'], true)) $status = 'draft';

    if ($clientId < 1) $errors[] = 'Selecciona un cliente.';
    if ($recipientEmail !== '' && !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'El email del destinatario no es válido.';
    if (!$issueDate) $errors[] = 'La fecha de emisión es obligatoria.';

    $catalogIds = $_POST['catalog_service_id'] ?? [];
    $descriptions = $_POST['description'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_price'] ?? [];

    $items = [];
    $count = max(count($descriptions), count($quantities), count($prices));
    for ($i = 0; $i < $count; $i++) {
        $description = trim((string)($descriptions[$i] ?? ''));
        $qty = (float)($quantities[$i] ?? 0);
        $price = (float)($prices[$i] ?? 0);
        if ($description === '' && $qty <= 0 && $price <= 0) continue;
        if ($description === '') {
            $errors[] = 'Todos los ítems deben tener una descripción.';
            continue;
        }
        if ($qty <= 0) {
            $errors[] = 'La cantidad de cada ítem debe ser mayor que cero.';
            continue;
        }
        $items[] = [
            'catalog_service_id' => !empty($catalogIds[$i]) ? (int)$catalogIds[$i] : null,
            'description' => $description,
            'quantity' => $qty,
            'unit_price' => max(0, $price),
            'line_total' => $qty * max(0, $price),
        ];
    }
    if (!$items) $errors[] = 'Agrega al menos un servicio o ítem a la cotización.';

    $totals = calculate_quote_totals($items, $discount, $taxRate);

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($id) {
                $pdo->prepare('UPDATE quotations SET client_id=?, recipient_name=?, recipient_email=?, language=?, currency=?, issue_date=?, valid_until=?, status=?, notes=?, terms=?, payment_method_id=?, payment_method_title=?, payment_method_details=?, show_payment_method=?, discount_amount=?, tax_rate=?, subtotal=?, tax_amount=?, total=?, owner_user_id=?, deal_id=? WHERE id=?')
                    ->execute([$clientId, $recipientName, $recipientEmail, $language, $currency, $issueDate, $validUntil, $status, $notes, $terms, $paymentMethodId, $paymentMethodTitle, $paymentMethodDetails, $showPaymentMethod, $totals['discount'], $taxRate, $totals['subtotal'], $totals['tax_amount'], $totals['total'], $ownerUserId, $dealId, $id]);
                $pdo->prepare('DELETE FROM quotation_items WHERE quotation_id=?')->execute([$id]);
            } else {
                $tmp = 'TMP-' . bin2hex(random_bytes(8));
                $pdo->prepare('INSERT INTO quotations (quotation_number, client_id, recipient_name, recipient_email, language, currency, issue_date, valid_until, status, notes, terms, payment_method_id, payment_method_title, payment_method_details, show_payment_method, discount_amount, tax_rate, subtotal, tax_amount, total, owner_user_id, deal_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$tmp, $clientId, $recipientName, $recipientEmail, $language, $currency, $issueDate, $validUntil, $status, $notes, $terms, $paymentMethodId, $paymentMethodTitle, $paymentMethodDetails, $showPaymentMethod, $totals['discount'], $taxRate, $totals['subtotal'], $totals['tax_amount'], $totals['total'], $ownerUserId ?: (int)$_SESSION['user_id'], $dealId, $_SESSION['user_id']]);
                $id = (int)$pdo->lastInsertId();
                $number = (string)setting('quote_prefix', 'COT') . '-' . date('Y', strtotime($issueDate)) . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
                $pdo->prepare('UPDATE quotations SET quotation_number=? WHERE id=?')->execute([$number, $id]);
            }

            $itemStmt = $pdo->prepare('INSERT INTO quotation_items (quotation_id, catalog_service_id, description, quantity, unit_price, line_total, sort_order) VALUES (?,?,?,?,?,?,?)');
            foreach ($items as $index => $item) {
                $itemStmt->execute([$id, $item['catalog_service_id'], $item['description'], $item['quantity'], $item['unit_price'], $item['line_total'], $index]);
            }

            if (!empty($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name'])) {
                $attachmentStmt = $pdo->prepare('INSERT INTO quotation_attachments(quotation_id,file_name,file_path,mime_type,file_size,created_by) VALUES (?,?,?,?,?,?)');
                foreach ($_FILES['attachments']['name'] as $fileIndex => $fileName) {
                    if ((string)$fileName === '') continue;
                    $file = [
                        'name'=>$fileName,
                        'type'=>$_FILES['attachments']['type'][$fileIndex]??'',
                        'tmp_name'=>$_FILES['attachments']['tmp_name'][$fileIndex]??'',
                        'error'=>$_FILES['attachments']['error'][$fileIndex]??UPLOAD_ERR_NO_FILE,
                        'size'=>$_FILES['attachments']['size'][$fileIndex]??0,
                    ];
                    $uploaded = crm_upload_document($file,'quotation-attachments',15);
                    if ($uploaded) $attachmentStmt->execute([$id,$uploaded['file_name'],$uploaded['file_path'],$uploaded['mime_type'],$uploaded['file_size'],(int)$_SESSION['user_id']]);
                }
            }

            $pdo->commit();
            log_activity($quote ? 'Cotización actualizada' : 'Cotización creada', $clientId, null, null, $id);
            flash('success', $quote ? 'Cotización actualizada correctamente.' : 'Cotización creada correctamente.');
            redirect('quotation_view.php?id=' . $id);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formItems = [];
    $catalogIds = $_POST['catalog_service_id'] ?? [];
    $desc = $_POST['description'] ?? [];
    $qty = $_POST['quantity'] ?? [];
    $price = $_POST['unit_price'] ?? [];
    for ($i = 0; $i < max(1, count($desc)); $i++) {
        $formItems[] = [
            'catalog_service_id' => $catalogIds[$i] ?? '',
            'description' => $desc[$i] ?? '',
            'quantity' => $qty[$i] ?? 1,
            'unit_price' => $price[$i] ?? 0,
        ];
    }
} elseif ($existingItems) {
    $formItems = $existingItems;
} else {
    $formItems = [['catalog_service_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0]];
}

$currentPaymentMethodId = max(0, (int)($_POST['payment_method_id'] ?? ($quote['payment_method_id'] ?? ($defaultPaymentMethod['id'] ?? 0))));
$currentPaymentTitle = (string)($_POST['payment_method_title'] ?? ($quote['payment_method_title'] ?? ($defaultPaymentMethod['name'] ?? '')));
$currentPaymentDetails = (string)($_POST['payment_method_details'] ?? ($quote['payment_method_details'] ?? ($defaultPaymentMethod['details'] ?? '')));

require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $quote ? 'Actualizar cotización' : 'Crear cotización multimoneda' ?></h2>
            <span class="table-sub">Selecciona idioma, moneda y forma de pago según el país del cliente.</span>
        </div>
        <a class="btn btn-light btn-small" href="<?= $quote ? 'quotation_view.php?id=' . (int)$quote['id'] : 'quotations.php' ?>">Cancelar</a>
    </div>

    <div class="panel-body form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-section full form-grid three">
            <h3>Cliente y presentación</h3>
            <label>Cliente *
                <select name="client_id" required data-quote-client>
                    <option value="">Seleccionar...</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int)$client['id'] ?>"
                                data-contact="<?= e($client['contact_name']) ?>"
                                data-email="<?= e($client['email']) ?>"
                                data-language="<?= e($client['preferred_language']) ?>"
                                data-currency="<?= e(country_currency($client['country'])) ?>"
                                data-country="<?= e($client['country']) ?>"
                            <?= (int)$get('client_id') === (int)$client['id'] ? 'selected' : '' ?>>
                            <?= e(($client['country'] ?: 'Sin país') . ' · ' . $client['company_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Idioma
                <select name="language">
                    <option value="es" <?= $get('language', 'es') === 'es' ? 'selected' : '' ?>>Español</option>
                    <option value="pt" <?= $get('language') === 'pt' ? 'selected' : '' ?>>Português</option>
                    <option value="en" <?= $get('language') === 'en' ? 'selected' : '' ?>>English</option>
                </select>
            </label>
            <label>Moneda
                <select name="currency">
                    <option value="CLP" <?= $get('currency', 'CLP') === 'CLP' ? 'selected' : '' ?>>Pesos chilenos (CLP)</option>
                    <option value="USD" <?= $get('currency') === 'USD' ? 'selected' : '' ?>>Dólares (USD)</option>
                    <option value="BRL" <?= $get('currency') === 'BRL' ? 'selected' : '' ?>>Reales (BRL)</option>
                </select>
            </label>
            <label>Nombre del destinatario
                <input name="recipient_name" value="<?= e($get('recipient_name')) ?>">
            </label>
            <label>Email del destinatario
                <input type="email" name="recipient_email" value="<?= e($get('recipient_email')) ?>">
            </label>
            <label>Estado
                <select name="status">
                    <?php foreach (['draft', 'sent', 'accepted', 'rejected', 'expired'] as $status): ?>
                        <option value="<?= $status ?>" <?= $get('status', 'draft') === $status ? 'selected' : '' ?>><?= e(quote_status_label($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Responsable
                <select name="owner_user_id"><option value="0">Sin asignar</option><?php foreach($users as$userOption):?><option value="<?=(int)$userOption['id']?>" <?=(int)$get('owner_user_id',(int)($_SESSION['user_id']??0))===(int)$userOption['id']?'selected':''?>><?=e($userOption['name'])?></option><?php endforeach;?></select>
            </label>
            <label>Negocio relacionado
                <select name="deal_id"><option value="0">Sin negocio relacionado</option><?php foreach($deals as$dealOption):?><option value="<?=(int)$dealOption['id']?>" <?=(int)$get('deal_id',0)===(int)$dealOption['id']?'selected':''?>><?=e($dealOption['title'])?></option><?php endforeach;?></select>
            </label>
            <label>Fecha de emisión
                <input type="date" name="issue_date" required value="<?= e($get('issue_date')) ?>">
            </label>
            <label>Válida hasta
                <input type="date" name="valid_until" value="<?= e($get('valid_until')) ?>">
            </label>
        </div>

        <div class="form-section full">
            <h3>Productos, servicios e ítems</h3>
            <p class="table-sub">Al seleccionar un producto o servicio del catálogo, el sistema cargará su texto en el idioma y su precio en la moneda elegida.</p>
            <div class="quote-editor-items" data-quote-items style="margin-top:14px">
                <?php foreach ($formItems as $item): ?>
                    <div class="quote-item-row" data-quote-row>
                        <label class="catalog-select">Producto / servicio del catálogo
                            <select name="catalog_service_id[]" data-item-catalog>
                                <option value="">Ítem personalizado</option>
                                <?php foreach ($catalog as $service): ?>
                                    <option value="<?= (int)$service['id'] ?>" <?= (int)($item['catalog_service_id'] ?? 0) === (int)$service['id'] ? 'selected' : '' ?>>
                                        <?= e(($service['item_type']==='product'?'Producto':'Servicio') . ' · ' . ($service['category'] ?: 'General') . ' · ' . $service['name_es']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="item-description">Descripción *
                            <textarea name="description[]" data-item-description required><?= e((string)($item['description'] ?? '')) ?></textarea>
                        </label>
                        <label>Cantidad *
                            <input type="number" name="quantity[]" min="0.01" step="0.01" value="<?= e((string)($item['quantity'] ?? 1)) ?>" data-item-quantity required>
                        </label>
                        <label>Valor unitario *
                            <input type="number" name="unit_price[]" min="0" step="0.01" value="<?= e((string)($item['unit_price'] ?? 0)) ?>" data-item-price required>
                        </label>
                        <label>Total línea
                            <div class="fake-input" data-line-total>0</div>
                        </label>
                        <button type="button" class="btn btn-light btn-small" data-remove-item>Quitar</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-actions" style="margin-top:14px">
                <button type="button" class="btn btn-secondary btn-small" data-add-quote-item>+ AGREGAR ÍTEM</button>
            </div>
        </div>

        <div class="form-section full form-grid two">
            <h3>Forma de pago</h3>
            <label>Forma de pago guardada
                <select name="payment_method_id" data-payment-method-select data-current-payment="<?= (int)$currentPaymentMethodId ?>">
                    <option value="">Seleccionar / personalizada</option>
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= (int)$method['id'] ?>" <?= $currentPaymentMethodId === (int)$method['id'] ? 'selected' : '' ?>><?= e(($method['country'] ?: 'Todos los países') . ' · ' . $method['currency'] . ' · ' . $method['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="table-sub">Se muestran primero las opciones compatibles con el país, moneda e idioma. <a href="payment_methods.php" target="_blank">Administrar formas de pago</a>.</span>
            </label>
            <label>Título visible de la forma de pago
                <input name="payment_method_title" value="<?= e($currentPaymentTitle) ?>" data-payment-method-title placeholder="Ej: PIX, Transferencia bancaria Chile, Stripe payment link">
            </label>
            <label class="full">Información / instrucciones de pago
                <textarea name="payment_method_details" rows="7" data-payment-method-details placeholder="Datos bancarios, llave PIX, enlace de pago, condiciones, etc."><?= e($currentPaymentDetails) ?></textarea>
            </label>
            <label class="checkbox-row full"><input type="checkbox" name="show_payment_method" value="1" <?= (int)$get('show_payment_method',1)===1?'checked':'' ?>> Mostrar la forma de pago en la cotización, el PDF y el email</label>
            <p class="table-sub full">Desmarca esta opción para clientes frecuentes que ya conocen los datos de pago. La información permanece guardada en el CRM, pero no se muestra en el documento.</p>
        </div>

        <div class="form-section full form-grid">
            <h3>Documentos adicionales</h3>
            <label class="full">Adjuntar muestras, presentaciones u otros documentos
                <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip">
                <span class="table-sub">Los archivos se enviarán junto al PDF de la cotización. Máximo 15 MB por archivo.</span>
            </label>
        </div>

        <div class="form-section full form-grid two">
            <h3>Totales y condiciones</h3>
            <label>Descuento
                <input type="number" min="0" step="0.01" name="discount_amount" value="<?= e((string)$get('discount_amount', '0')) ?>">
            </label>
            <label>Impuestos (%)
                <input type="number" min="0" step="0.01" name="tax_rate" value="<?= e((string)$get('tax_rate', '0')) ?>">
            </label>
            <label class="full">Observaciones
                <textarea name="notes" rows="5"><?= e((string)$get('notes', '')) ?></textarea>
            </label>
            <label class="full">Condiciones
                <textarea name="terms" rows="6"><?= e((string)$get('terms', '')) ?></textarea>
            </label>
            <div class="quote-summary-board full">
                <div><span>Subtotal</span><strong data-summary-subtotal>0</strong></div>
                <div><span>Descuento</span><strong data-summary-discount>0</strong></div>
                <div><span>Impuestos</span><strong data-summary-tax>0</strong></div>
                <div class="grand"><span>Total</span><strong data-summary-total>0</strong></div>
            </div>
        </div>

        <div class="full form-actions">
            <button class="btn btn-primary"><?= $quote ? 'ACTUALIZAR COTIZACIÓN' : 'GUARDAR COTIZACIÓN' ?></button>
        </div>
    </div>
</form>

<template id="quote-item-template">
    <div class="quote-item-row" data-quote-row>
        <label class="catalog-select">Producto / servicio del catálogo
            <select name="catalog_service_id[]" data-item-catalog>
                <option value="">Ítem personalizado</option>
                <?php foreach ($catalog as $service): ?>
                    <option value="<?= (int)$service['id'] ?>"><?= e(($service['item_type']==='product'?'Producto':'Servicio') . ' · ' . ($service['category'] ?: 'General') . ' · ' . $service['name_es']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="item-description">Descripción *
            <textarea name="description[]" data-item-description required></textarea>
        </label>
        <label>Cantidad *
            <input type="number" name="quantity[]" min="0.01" step="0.01" value="1" data-item-quantity required>
        </label>
        <label>Valor unitario *
            <input type="number" name="unit_price[]" min="0" step="0.01" value="0" data-item-price required>
        </label>
        <label>Total línea
            <div class="fake-input" data-line-total>0</div>
        </label>
        <button type="button" class="btn btn-light btn-small" data-remove-item>Quitar</button>
    </div>
</template>

<script type="application/json" id="catalog-data"><?= json_encode($catalogJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/json" id="payment-methods-data"><?= json_encode($paymentMethodsJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
