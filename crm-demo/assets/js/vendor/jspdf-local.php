<?php
/**
 * ONE HUB Community — bootstrap seguro de jsPDF local.
 * No permite URLs arbitrarias: descarga únicamente el archivo oficial fijado a 4.2.1.
 * Primera descarga desde GitHub oficial (o unpkg como alternativa), comprueba SHA Git.
 * Las visitas siguientes reciben el archivo local, sin usar CDNs en el navegador.
 * Licencia de jsPDF: MIT; ver copyright dentro del archivo de upstream.
 */
declare(strict_types=1);

const ONEHUB_JSPDF_VERSION = '4.2.1';
const ONEHUB_JSPDF_SHA1 = '0e600d49d2b2bf81dd97c1509e22d108c2ddd3b4';
const ONEHUB_JSPDF_MAX_SIZE = 1200000;
const ONEHUB_JSPDF_SOURCE = [
    'https://raw.githubusercontent.com/parallax/jsPDF/v4.2.1/dist/jspdf.umd.min.js',
    'https://unpkg.com/jspdf@4.2.1/dist/jspdf.umd.min.js',
];

$target = __DIR__ . '/jspdf.umd.min.js';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');

function oh_lib_valid(string $content): bool {
    if (strlen($content) < 300000 || strlen($content) > ONEHUB_JSPDF_MAX_SIZE) return false;
    if (strpos($content, 'jsPDF - PDF Document creation from JavaScript') === false) return false;
    if (strpos($content, 'Version 4.2.1') === false) return false;
    // SHA of the Git blob at tag v4.2.1, not SHA of a mutable CDN URL.
    return sha1('blob ' . strlen($content) . "\0" . $content) === ONEHUB_JSPDF_SHA1;
}

function oh_fetch_library(string $url): ?string {
    // Limit remote time, redirects and content size. No user-controlled URL input.
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 7,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'ONE-HUB-Community/2.0 (jsPDF local installer)',
                CURLOPT_MAXFILESIZE => ONEHUB_JSPDF_MAX_SIZE,
            ]);
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status === 200 && is_string($body) && strlen($body) <= ONEHUB_JSPDF_MAX_SIZE) return $body;
        }
        // No repeated transport retries when cURL is available.
        return null;
    }
    if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $context = stream_context_create(['http' => [
            'timeout' => 7,
            'follow_location' => 1,
            'max_redirects' => 3,
            'user_agent' => 'ONE-HUB-Community/2.0 (jsPDF local installer)',
        ]]);
        $body = @file_get_contents($url, false, $context, 0, ONEHUB_JSPDF_MAX_SIZE + 1);
        if (is_string($body) && strlen($body) <= ONEHUB_JSPDF_MAX_SIZE) return $body;
    }
    return null;
}

$body = is_file($target) ? @file_get_contents($target) : null;
$valid = is_string($body) && oh_lib_valid($body);

// Health-check never initiates network downloads. Anyone can see whether asset is installed.
if (isset($_GET['check'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode([
        'component' => 'jsPDF ONE HUB',
        'version' => ONEHUB_JSPDF_VERSION,
        'local_file_valid' => $valid,
        'local_file_bytes' => $valid ? strlen((string)$body) : 0,
        'next_step' => $valid ? 'Archivo local correcto.' : 'Subir jspdf.umd.min.js oficial a este directorio o permitir descarga HTTPS desde servidor.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$valid) {
    foreach (ONEHUB_JSPDF_SOURCE as $url) {
        $downloaded = oh_fetch_library($url);
        if (!is_string($downloaded) || !oh_lib_valid($downloaded)) continue;
        $body = $downloaded;
        $valid = true;
        // Save atomically where Apache/PHP have permission; if unwritable, can serve once.
        $tmp = @tempnam(__DIR__, '.jspdf_tmp_');
        if ($tmp !== false) {
            $bytes = @file_put_contents($tmp, $body, LOCK_EX);
            if ($bytes === strlen($body)) {
                @chmod($tmp, 0644);
                if (!@rename($tmp, $target)) @unlink($tmp);
            } else {
                @unlink($tmp);
            }
        }
        break;
    }
}

if (!$valid || !is_string($body)) {
    http_response_code(503);
    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: no-store');
    echo 'console.error("[ONE HUB PDF] jsPDF 4.2.1 no disponible en el servidor. Subir assets/js/vendor/jspdf.umd.min.js desde GitHub oficial.");';
    exit;
}
header('Content-Type: application/javascript; charset=utf-8');
header('X-ONEHUB-JSPDF: local-validated-v4.2.1');
header('Content-Length: ' . strlen($body));
echo $body;
