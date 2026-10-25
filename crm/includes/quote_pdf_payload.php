<?php
declare(strict_types=1);

/**
 * Construye los datos ya formateados que usa el generador PDF vectorial.
 * No modifica la base de datos ni los datos de la cotización.
 */
function quote_pdf_payload(array $quote, array $items): array
{
    $labels = quote_labels((string)($quote['language'] ?? 'es'));
    $currency = (string)($quote['currency'] ?? 'CLP');
    $address = trim(implode(', ', array_filter([
        (string)($quote['address'] ?? ''),
        (string)($quote['city'] ?? ''),
        (string)($quote['country'] ?? ''),
    ], static fn(string $value): bool => trim($value) !== '')));

    $formattedItems = [];
    foreach ($items as $item) {
        $formattedItems[] = [
            'description' => (string)($item['description'] ?? ''),
            'quantity' => number_format((float)($item['quantity'] ?? 0), 2, ',', '.'),
            'unit_price' => format_money((float)($item['unit_price'] ?? 0), $currency),
            'line_total' => format_money((float)($item['line_total'] ?? 0), $currency),
        ];
    }

    return [
        'quotation_number' => (string)($quote['quotation_number'] ?? ''),
        'language' => (string)($quote['language'] ?? 'es'),
        'currency' => $currency,
        'issue_date' => format_date((string)($quote['issue_date'] ?? '')),
        'valid_until' => !empty($quote['valid_until']) ? format_date((string)$quote['valid_until']) : '',
        'brand_logo' => quote_brand_logo_path(false),
        'brand_name' => 'SÓCRATES ONE · Creative Studio',
        'brand_tagline' => 'Diseño con alma, impulsado por IA',
        'brand_subtitle' => 'Creative Studio · www.socrates.la',
        'brand_contact' => 'socrates@socrates.la · www.socrates.la',
        'client' => [
            'company_name' => (string)($quote['company_name'] ?? ''),
            'recipient_name' => (string)($quote['recipient_name'] ?? ''),
            'tax_id' => (string)($quote['tax_id'] ?? ''),
            'address' => $address,
            'email' => (string)($quote['recipient_email'] ?? $quote['client_email'] ?? ''),
            'logo' => (string)($quote['logo_path'] ?? ''),
        ],
        'items' => $formattedItems,
        'notes' => trim((string)($quote['notes'] ?? '')),
        'payment_method_title' => (int)($quote['show_payment_method'] ?? 1) === 1 ? trim((string)($quote['payment_method_title'] ?? '')) : '',
        'payment_method_details' => (int)($quote['show_payment_method'] ?? 1) === 1 ? trim((string)($quote['payment_method_details'] ?? '')) : '',
        'terms' => trim((string)($quote['terms'] ?? '')),
        'totals' => [
            'subtotal' => format_money((float)($quote['subtotal'] ?? 0), $currency),
            'discount' => (float)($quote['discount_amount'] ?? 0) > 0
                ? '- ' . format_money((float)$quote['discount_amount'], $currency)
                : '',
            'tax_label' => (float)($quote['tax_rate'] ?? 0) > 0
                ? $labels['tax'] . ' (' . number_format((float)$quote['tax_rate'], 2, ',', '.') . '%)'
                : '',
            'tax_amount' => (float)($quote['tax_rate'] ?? 0) > 0
                ? format_money((float)($quote['tax_amount'] ?? 0), $currency)
                : '',
            'total' => format_money((float)($quote['total'] ?? 0), $currency),
        ],
        'labels' => $labels,
    ];
}
