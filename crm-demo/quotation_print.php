<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/quote_pdf_payload.php';
require_permission('quotes.view');

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT q.*, c.company_name, c.trade_name, c.tax_id, c.country, c.city, c.address, c.logo_path, c.email client_email FROM quotations q JOIN clients c ON c.id=q.client_id WHERE q.id=?');
$stmt->execute([$id]);
$quote = $stmt->fetch();
if (!$quote) {
    http_response_code(404);
    exit('Cotización no encontrada.');
}
$stmt = db()->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order,id');
$stmt->execute([$id]);
$items = $stmt->fetchAll();
$pdfPayload = quote_pdf_payload($quote, $items);
$autoPrint = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($quote['quotation_number']) ?></title>
<style>
body{margin:0;padding:30px;background:#e8eff3}
.print-actions{max-width:1080px;margin:0 auto 16px;display:flex;flex-wrap:wrap;gap:10px}
.print-actions button,.print-actions a{border:0;border-radius:9px;padding:11px 15px;background:#0074c7;color:#fff;font:700 14px Arial;text-decoration:none;cursor:pointer}
.print-actions .alt{background:#f15a24}
<?= quote_document_css() ?>
@page{size:A4;margin:8mm}@media print{body{background:#fff;padding:0}.print-actions{display:none}.quote-document{box-shadow:none;border:0;max-width:none;border-radius:0}}.quote-exporting-pdf .quote-document{box-shadow:none;border-radius:0}
</style>
<script src="assets/js/quote-pdf.js?v=2.0.0-local"></script>
</head>
<body>
<div class="print-actions">
    <button onclick="window.print()">IMPRIMIR</button>
    <button class="alt" type="button" id="downloadPdf">DESCARGAR PDF</button>
    <a href="quotation_view.php?id=<?= $id ?>">VOLVER</a>
</div>
<script type="application/json" id="quote-pdf-data"><?= json_encode($pdfPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<div id="quote-pdf-source" class="quote-print-layout"><?= quote_document_html($quote, $items) ?></div>
<script>
const button = document.getElementById('downloadPdf');
button?.addEventListener('click', () => {
  window.SocratesQuotePdf?.download(
    document.getElementById('quote-pdf-data'),
    <?= json_encode($quote['quotation_number'] . '.pdf') ?>,
    button
  );
});
<?php if ($autoPrint): ?>
window.addEventListener('load', () => setTimeout(() => window.print(), 350));
<?php endif; ?>
</script>
</body>
</html>
