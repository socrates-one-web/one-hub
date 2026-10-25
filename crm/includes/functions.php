<?php
declare(strict_types=1);

function crm_substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) return mb_substr($value, $start, $length, 'UTF-8');
    if ($length === null) return substr($value, $start);
    return substr($value, $start, $length);
}

function crm_upper(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
}

function crm_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function crm_strimwidth(string $value, int $width, string $trimMarker = '…'): string
{
    if (function_exists('mb_strimwidth')) return mb_strimwidth($value, 0, $width, $trimMarker, 'UTF-8');
    $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($characters) || count($characters) <= $width) return $value;
    return implode('', array_slice($characters, 0, max(0, $width - 1))) . $trimMarker;
}

function crm_mime_header(string $value): string
{
    if (function_exists('mb_encode_mimeheader')) return mb_encode_mimeheader($value, 'UTF-8');
    if (preg_match('/^[\x20-\x7E]*$/', $value)) return $value;
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function crm_log_exception(Throwable $exception): string
{
    $code = date('YmdHis') . '-' . substr(hash('sha256', $exception->getMessage() . $exception->getFile() . $exception->getLine()), 0, 8);
    $directory = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($directory)) @mkdir($directory, 0755, true);
    $entry = sprintf(
        "[%s] %s | %s in %s:%d\n%s\n\n",
        date('c'),
        $code,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    @file_put_contents($directory . '/crm.log', $entry, FILE_APPEND | LOCK_EX);
    return $code;
}

function config(?string $key = null, mixed $default = null): mixed
{
    global $config;
    if ($key === null) return $config;
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) return $default;
        $value = $value[$segment];
    }
    return $value;
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $db = config('db');
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function db_table_exists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) return $cache[$table];
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $stmt->execute([$table]);
        return $cache[$table] = (bool)$stmt->fetchColumn();
    } catch (Throwable) { return false; }
}

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function app_url(string $path = ''): string { return rtrim((string)config('app_url', ''), '/') . '/' . ltrim($path, '/'); }
function redirect(string $path): never { if (!preg_match('~^https?://~i', $path)) $path = app_url($path); header('Location: ' . $path); exit; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrf_token(), $token)) { http_response_code(419); exit('La sesión expiró. Regresa e inténtalo nuevamente.'); }
}
function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type'=>$type,'message'=>$message]; }
function pull_flashes(): array { $messages=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $messages; }
function old(string $key, mixed $default=''): mixed { return $_POST[$key] ?? $default; }

function setting(string $key, mixed $default = null): mixed
{
    $cache =& $GLOBALS['crm_setting_cache'];
    if (!is_array($cache)) $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $stmt=db()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $stmt->execute([$key]);
        $value=$stmt->fetchColumn();
        return $cache[$key]=$value!==false?$value:$default;
    } catch (Throwable) { return $default; }
}
function set_setting(string $key, ?string $value): void
{
    db()->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$key,$value]);
    $GLOBALS['crm_setting_cache'][$key]=$value;
}


function crm_app_version(): string
{
    static $version = null;
    if ($version !== null) return $version;
    $file = dirname(__DIR__) . '/VERSION';
    $value = is_file($file) ? trim((string)@file_get_contents($file)) : '';
    return $version = ($value !== '' ? $value : '2.1.0');
}

function crm_license_info(): array
{
    $hasFictitiousData = (string)setting('demo_mode','0') === '1';
    return [
        'status' => $hasFictitiousData ? 'demo' : 'active',
        'label' => $hasFictitiousData ? 'DEMO · COMMUNITY' : 'OPEN SOURCE',
        'class' => $hasFictitiousData ? 'demo' : 'active',
        'plan' => 'Community',
        'customer' => '',
        'expires_at' => '',
        'instance_id' => '',
    ];
}
function crm_company_name(): string
{
    return trim((string)setting('company_name', '')) ?: 'Mi empresa';
}
function crm_company_logo_path(bool $absolute=false): string
{
    $path=trim((string)setting('company_logo_path',''));
    if($path==='') return '';
    return $absolute ? app_url($path) : $path;
}
function crm_upload_brand_logo(array $file): string
{
    if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return '';
    if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new RuntimeException('No fue posible subir el logo.');
    if(($file['size']??0)>4*1024*1024)throw new RuntimeException('El logo no puede superar 4 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$map=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];if(!isset($map[$mime]))throw new RuntimeException('Usa PNG, JPG o WEBP.');
    $dir=dirname(__DIR__).'/uploads/branding';if(!is_dir($dir)&&!@mkdir($dir,0775,true)&&!is_dir($dir))throw new RuntimeException('No fue posible crear uploads/branding.');
    $name='company-logo-'.date('YmdHis').'-'.bin2hex(random_bytes(3)).'.'.$map[$mime];if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))throw new RuntimeException('No fue posible guardar el logo.');return 'uploads/branding/'.$name;
}
/** Community edition: no activation server and no paid license needed. */
function crm_license_validation_endpoint(): string { return ''; }
function crm_validate_license_code(string $code): array {
    return ['valid'=>false,'error'=>'ONE HUB Community no necesita activación de licencia.'];
}
function crm_apply_validated_license(array $result): void {
    throw new RuntimeException('La activación de licencias no se utiliza en la edición Community.');
}

/**
 * Obtiene tasas de referencia USD/BRL y USD/CLP con caché local.
 * La fuente pública no requiere API key. Si la consulta falla, conserva
 * la última respuesta válida y nunca impide cargar el CRM.
 */
function crm_exchange_rates(int $cacheSeconds = 86400): array
{
    $fallback = ['ok'=>false,'base'=>'USD','BRL'=>null,'CLP'=>null,'updated_at'=>null,'source'=>'ExchangeRate-API'];
    $cacheDir = dirname(__DIR__) . '/storage/cache';
    $cacheFile = $cacheDir . '/exchange-rates-usd.json';
    $readCache = static function () use ($cacheFile): ?array {
        if (!is_file($cacheFile)) return null;
        $data = json_decode((string)@file_get_contents($cacheFile), true);
        if (!is_array($data) || empty($data['rates']['BRL']) || empty($data['rates']['CLP'])) return null;
        return $data;
    };

    $cached = $readCache();
    if ($cached && (time() - (int)($cached['cached_at'] ?? 0)) < $cacheSeconds) {
        return ['ok'=>true,'base'=>'USD','BRL'=>(float)$cached['rates']['BRL'],'CLP'=>(float)$cached['rates']['CLP'],'updated_at'=>$cached['updated_at'] ?? null,'source'=>'ExchangeRate-API'];
    }

    $url = 'https://open.er-api.com/v6/latest/USD';
    $body = false;
    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>4,CURLOPT_USERAGENT=>'SocratesOneCRM/1.7']);
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            if ($status < 200 || $status >= 300) $body = false;
        } elseif (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
            $context = stream_context_create(['http'=>['timeout'=>4,'user_agent'=>'SocratesOneCRM/1.7']]);
            $body = @file_get_contents($url, false, $context);
        }
    } catch (Throwable) { $body = false; }

    if (is_string($body) && $body !== '') {
        $api = json_decode($body, true);
        if (is_array($api) && ($api['result'] ?? '') === 'success' && !empty($api['rates']['BRL']) && !empty($api['rates']['CLP'])) {
            $payload = [
                'cached_at'=>time(),
                'updated_at'=>$api['time_last_update_utc'] ?? date(DATE_ATOM),
                'rates'=>['BRL'=>(float)$api['rates']['BRL'],'CLP'=>(float)$api['rates']['CLP']],
            ];
            if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
            @file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_SLASHES), LOCK_EX);
            return ['ok'=>true,'base'=>'USD','BRL'=>$payload['rates']['BRL'],'CLP'=>$payload['rates']['CLP'],'updated_at'=>$payload['updated_at'],'source'=>'ExchangeRate-API'];
        }
    }

    if ($cached) {
        return ['ok'=>true,'base'=>'USD','BRL'=>(float)$cached['rates']['BRL'],'CLP'=>(float)$cached['rates']['CLP'],'updated_at'=>$cached['updated_at'] ?? null,'source'=>'ExchangeRate-API'];
    }
    return $fallback;
}

function encrypt_secret(string $plain): string
{
    if ($plain==='') return '';
    $key=hash('sha256',(string)config('app_key'),true);
    if (in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
        $iv=random_bytes(12);$tag='';
        $encrypted=openssl_encrypt($plain,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'SOCONECRM',16);
        if($encrypted===false)throw new RuntimeException('No fue posible cifrar la información.');
        return 'gcm:'.base64_encode($iv.$tag.$encrypted);
    }
    $iv=random_bytes(16);$encrypted=openssl_encrypt($plain,'AES-256-CBC',$key,OPENSSL_RAW_DATA,$iv);
    if($encrypted===false)throw new RuntimeException('No fue posible cifrar la información.');
    return 'enc:'.base64_encode($iv.$encrypted);
}
function decrypt_secret(?string $stored): string
{
    if(!$stored)return '';
    $key=hash('sha256',(string)config('app_key'),true);
    if(str_starts_with($stored,'gcm:')){
        $raw=base64_decode(substr($stored,4),true);if($raw===false||strlen($raw)<29)return '';
        $iv=substr($raw,0,12);$tag=substr($raw,12,16);$cipher=substr($raw,28);
        return (string)(openssl_decrypt($cipher,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'SOCONECRM')?:'');
    }
    if(!str_starts_with($stored,'enc:'))return $stored;
    $raw=base64_decode(substr($stored,4),true);if($raw===false||strlen($raw)<17)return '';
    return (string)(openssl_decrypt(substr($raw,16),'AES-256-CBC',$key,OPENSSL_RAW_DATA,substr($raw,0,16))?:'');
}

function log_activity(string $action, ?int $clientId=null, ?int $serviceId=null, ?string $details=null, ?int $quotationId=null): void
{
    try { db()->prepare('INSERT INTO activity_logs (user_id,client_id,service_id,quotation_id,action,details) VALUES (?,?,?,?,?,?)')->execute([$_SESSION['user_id']??null,$clientId,$serviceId,$quotationId,$action,$details]); }
    catch (Throwable) {
        try { db()->prepare('INSERT INTO activity_logs (user_id,client_id,service_id,action,details) VALUES (?,?,?,?,?)')->execute([$_SESSION['user_id']??null,$clientId,$serviceId,$action,$details]); } catch (Throwable) {}
    }
    if(function_exists('crm_security_audit')) crm_security_audit('crm_activity','info',$action.($details?' · '.$details:''),$quotationId?'quotation':($serviceId?'service':($clientId?'client':null)),$quotationId??$serviceId??$clientId);
}

function format_date_time(?string $date, string $fallback='—'): string
{
    if(!$date)return $fallback;
    try{return(new DateTimeImmutable($date))->format('d/m/Y H:i');}catch(Throwable){return$fallback;}
}

function format_date(?string $date, string $fallback='—'): string
{
    if (!$date||$date==='0000-00-00') return $fallback;
    try { return (new DateTimeImmutable($date))->format('d/m/Y'); } catch (Throwable) { return $fallback; }
}
function currency_symbol(string $currency): string { return match ($currency) {'CLP'=>'$','BRL'=>'R$','USD'=>'US$',default=>$currency}; }
function currency_decimals(string $currency): int { return $currency==='CLP'?0:2; }
function format_money(mixed $amount, string $currency='USD'): string
{
    if ($amount===null||$amount==='') return '—';
    return currency_symbol($currency).' '.number_format((float)$amount,currency_decimals($currency),',','.');
}
function contract_label(string $type): string { return crm_translate_text(match($type){'monthly'=>'Mensual','annual'=>'Anual','one_time'=>'Única vez','custom'=>'Personalizado',default=>ucfirst($type)}); }
function client_status_label(string $status): string { return crm_translate_text(match($status){'active'=>'Activo','inactive'=>'Inactivo','prospect'=>'Prospecto',default=>ucfirst($status)}); }
function language_label(string $lang): string { return crm_translate_text(match($lang){'es'=>'Español','pt'=>'Português','en'=>'English',default=>$lang}); }
function quote_status_label(string $status): string { return crm_translate_text(match($status){'draft'=>'Borrador','sent'=>'Enviada','accepted'=>'Aceptada','rejected'=>'Rechazada','expired'=>'Vencida',default=>ucfirst($status)}); }
function quote_status_class(string $status): string { return match($status){'accepted'=>'green','sent'=>'blue','rejected'=>'red','expired'=>'gray',default=>'orange'}; }

function computed_service_status(array $service): array
{
    $manual=$service['status']??'active';
    if($manual==='cancelled')return['key'=>'cancelled','label'=>'Cancelado','class'=>'gray'];
    if($manual==='completed')return['key'=>'completed','label'=>'Finalizado','class'=>'blue'];
    if($manual==='pending')return['key'=>'pending','label'=>'Pendiente','class'=>'orange'];
    $expiration=$service['expiration_date']??null;
    if(!$expiration)return['key'=>'active','label'=>'Activo','class'=>'green'];
    $days=(int)(new DateTimeImmutable('today'))->diff(new DateTimeImmutable($expiration))->format('%r%a');
    if($days<0)return['key'=>'expired','label'=>'Vencido','class'=>'red'];
    if($days===0)return['key'=>'today','label'=>'Vence hoy','class'=>'red'];
    if($days<=7)return['key'=>'upcoming','label'=>'Próximo a vencer','class'=>'yellow'];
    return['key'=>'active','label'=>'Activo','class'=>'green'];
}
function days_until(?string $date): ?int { return $date?(int)(new DateTimeImmutable('today'))->diff(new DateTimeImmutable($date))->format('%r%a'):null; }

function upload_client_logo(array $file, ?string $existing=null): ?string
{
    if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return $existing;
    if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new RuntimeException('No fue posible subir el logo.');
    if(($file['size']??0)>3*1024*1024)throw new RuntimeException('El logo no puede superar 3 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!isset($allowed[$mime]))throw new RuntimeException('Formato inválido. Usa JPG, PNG o WEBP.');
    $name=bin2hex(random_bytes(12)).'.'.$allowed[$mime]; $target=dirname(__DIR__).'/uploads/'.$name;
    if(!move_uploaded_file($file['tmp_name'],$target))throw new RuntimeException('No fue posible guardar el logo.');
    if($existing&&str_starts_with($existing,'uploads/')){ $old=dirname(__DIR__).'/'.$existing; if(is_file($old))@unlink($old); }
    return 'uploads/'.$name;
}

function catalog_localized(array $service, string $language): array
{
    $language=in_array($language,['es','pt','en'],true)?$language:'es';
    $name=trim((string)($service['name_'.$language]??'')) ?: trim((string)($service['name_es']??''));
    $description=trim((string)($service['description_'.$language]??'')) ?: trim((string)($service['description_es']??''));
    return ['name'=>$name,'description'=>$description];
}
function catalog_price(array $service, string $currency): ?float
{
    $field='price_'.strtolower($currency); $value=$service[$field]??null;
    return $value===null||$value===''?null:(float)$value;
}

function quote_labels(string $language): array
{
    return match($language){
        'pt'=>[
            'quotation'=>'COTAÇÃO','number'=>'Número','date'=>'Data','valid_until'=>'Válida até','client'=>'Cliente','tax_id'=>'CNPJ / ID fiscal',
            'description'=>'Descrição','quantity'=>'Qtd.','unit_price'=>'Valor unitário','total'=>'Total','subtotal'=>'Subtotal','discount'=>'Desconto',
            'tax'=>'Impostos','grand_total'=>'TOTAL','notes'=>'Observações','terms'=>'Condições','payment_method'=>'Forma de pagamento',
            'payment_details'=>'Dados para pagamento','prepared_by'=>'Preparado por','validity'=>'Esta cotação é válida até','subject'=>'Cotação',
            'currency'=>'Moeda','greeting'=>'Olá','intro'=>'Segue a cotação solicitada.','view'=>'Abrir cotação online','attachment_note'=>'A cotação completa está anexada em PDF.'
        ],
        'en'=>[
            'quotation'=>'QUOTATION','number'=>'Number','date'=>'Date','valid_until'=>'Valid until','client'=>'Client','tax_id'=>'Tax ID',
            'description'=>'Description','quantity'=>'Qty.','unit_price'=>'Unit price','total'=>'Total','subtotal'=>'Subtotal','discount'=>'Discount',
            'tax'=>'Taxes','grand_total'=>'TOTAL','notes'=>'Notes','terms'=>'Terms','payment_method'=>'Payment method',
            'payment_details'=>'Payment details','prepared_by'=>'Prepared by','validity'=>'This quotation is valid until','subject'=>'Quotation',
            'currency'=>'Currency','greeting'=>'Hello','intro'=>'Please find the requested quotation below.','view'=>'Open quotation online','attachment_note'=>'The complete quotation is attached as a PDF.'
        ],
        default=>[
            'quotation'=>'COTIZACIÓN','number'=>'Número','date'=>'Fecha','valid_until'=>'Válida hasta','client'=>'Cliente','tax_id'=>'RUT / ID fiscal',
            'description'=>'Descripción','quantity'=>'Cant.','unit_price'=>'Valor unitario','total'=>'Total','subtotal'=>'Subtotal','discount'=>'Descuento',
            'tax'=>'Impuestos','grand_total'=>'TOTAL','notes'=>'Observaciones','terms'=>'Condiciones','payment_method'=>'Forma de pago',
            'payment_details'=>'Datos para el pago','prepared_by'=>'Preparado por','validity'=>'Esta cotización es válida hasta','subject'=>'Cotización',
            'currency'=>'Moneda','greeting'=>'Hola','intro'=>'Adjuntamos a continuación la cotización solicitada.','view'=>'Abrir cotización en línea','attachment_note'=>'La cotización completa se adjunta en formato PDF.'
        ],
    };
}

function parse_payment_methods(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') return [];
    $blocks = preg_split('/\R\s*---+\s*\R|\R\s*===+\s*\R/', $raw) ?: [];
    $items = [];
    foreach ($blocks as $block) {
        $block = trim((string)$block);
        if ($block === '') continue;
        $lines = preg_split('/\R/', $block) ?: [];
        $title = trim((string)array_shift($lines));
        $content = trim(implode("
", $lines));
        if ($title === '') continue;
        if ($content === '') $content = $title;
        $items[] = [
            'id' => substr(sha1($title . '|' . $content), 0, 16),
            'title' => $title,
            'content' => $content,
        ];
    }
    return $items;
}

function payment_methods_for_currency(string $currency): array
{
    $currency = in_array($currency, ['CLP','USD','BRL'], true) ? $currency : 'CLP';
    return parse_payment_methods((string)setting('quote_payment_methods_' . strtolower($currency), ''));
}

function quote_brand_logo_path(bool $email = false): string
{
    $path = 'assets/img/logo-creative-cotizaciones.png';
    return $email ? app_url($path) : $path;
}

function calculate_quote_totals(array $items, float $discount, float $taxRate): array
{
    $subtotal=0.0;
    foreach($items as $item)$subtotal+=(float)$item['line_total'];
    $discount=max(0,min($discount,$subtotal));
    $taxable=max(0,$subtotal-$discount);
    $taxAmount=$taxable*max(0,$taxRate)/100;
    return ['subtotal'=>$subtotal,'discount'=>$discount,'tax_amount'=>$taxAmount,'total'=>$taxable+$taxAmount];
}

function quote_document_html(array $quote, array $items, bool $email=false): string
{
    $l = quote_labels((string)$quote['language']);
    $currency = (string)$quote['currency'];
    $logo = quote_brand_logo_path($email);
    $clientLogo = !empty($quote['logo_path']) ? ($email ? app_url((string)$quote['logo_path']) : (string)$quote['logo_path']) : '';
    $showPayment = (int)($quote['show_payment_method'] ?? 1) === 1;
    $paymentTitle = $showPayment ? trim((string)($quote['payment_method_title'] ?? '')) : '';
    $paymentDetails = $showPayment ? trim((string)($quote['payment_method_details'] ?? '')) : '';
    ob_start(); ?>
<div class="quote-document">
  <div class="quote-accent-bar"></div>
  <div class="quote-doc-header">
    <div class="quote-branding">
      <img class="quote-brand-logo" src="<?=e($logo)?>" alt="Sócrates Creative Studio">
      <div class="quote-brand-copy">
        <strong>Diseño con alma, impulsado por IA</strong>
        <span>Creative Studio · www.socrates.la</span>
      </div>
    </div>
    <div class="quote-doc-title-card">
      <small><?=e($l['quotation'])?></small>
      <strong><?=e($quote['quotation_number'])?></strong>
      <span><?=e(format_date($quote['issue_date']))?></span>
    </div>
  </div>
  <div class="quote-doc-meta">
    <div class="quote-client-block">
      <?php if($clientLogo):?><img src="<?=e($clientLogo)?>" alt="" class="quote-client-logo"><?php endif;?>
      <div>
        <small><?=e($l['client'])?></small>
        <h2><?=e($quote['company_name'])?></h2>
        <?php if(!empty($quote['recipient_name'])):?><p><?=e($quote['recipient_name'])?></p><?php endif;?>
        <?php if(!empty($quote['tax_id'])):?><p><?=e($l['tax_id'])?>: <?=e($quote['tax_id'])?></p><?php endif;?>
        <?php if(!empty($quote['address']) || !empty($quote['city']) || !empty($quote['country'])):?><p><?=e(trim(implode(', ', array_filter([(string)($quote['address'] ?? ''),(string)($quote['city'] ?? ''),(string)($quote['country'] ?? '')]))))?></p><?php endif;?>
        <?php if(!empty($quote['recipient_email'])):?><p><?=e($quote['recipient_email'])?></p><?php endif;?>
      </div>
    </div>
    <div class="quote-dates-grid">
      <div class="quote-date-card"><small><?=e($l['number'])?></small><strong><?=e($quote['quotation_number'])?></strong></div>
      <div class="quote-date-card"><small><?=e($l['date'])?></small><strong><?=e(format_date($quote['issue_date']))?></strong></div>
      <?php if(!empty($quote['valid_until'])):?><div class="quote-date-card"><small><?=e($l['valid_until'])?></small><strong><?=e(format_date($quote['valid_until']))?></strong></div><?php endif;?>
      <div class="quote-date-card"><small><?=e($l['currency'])?></small><strong><?=e($quote['currency'])?></strong></div>
    </div>
  </div>

  <div class="quote-table-wrap">
    <table class="quote-items">
      <thead><tr><th><?=e($l['description'])?></th><th><?=e($l['quantity'])?></th><th><?=e($l['unit_price'])?></th><th><?=e($l['total'])?></th></tr></thead>
      <tbody>
      <?php foreach($items as $item):?><tr><td><?=nl2br(e($item['description']))?></td><td><?=e(number_format((float)$item['quantity'],2,',','.'))?></td><td><?=e(format_money($item['unit_price'],$currency))?></td><td><?=e(format_money($item['line_total'],$currency))?></td></tr><?php endforeach;?>
      </tbody>
    </table>
  </div>

  <div class="quote-summary">
    <div class="quote-extra">
      <?php if(!empty($quote['notes'])):?><section class="quote-info-card"><h3><?=e($l['notes'])?></h3><p><?=nl2br(e($quote['notes']))?></p></section><?php endif;?>
      <?php if($paymentTitle !== '' || $paymentDetails !== ''):?><section class="quote-info-card quote-payment-card"><h3><?=e($l['payment_method'])?></h3><?php if($paymentTitle !== ''):?><strong><?=e($paymentTitle)?></strong><?php endif;?><?php if($paymentDetails !== ''):?><p><?=nl2br(e($paymentDetails))?></p><?php endif;?></section><?php endif;?>
      <?php if(!empty($quote['terms'])):?><section class="quote-info-card"><h3><?=e($l['terms'])?></h3><p><?=nl2br(e($quote['terms']))?></p></section><?php endif;?>
    </div>
    <div class="quote-totals-card">
      <p><span><?=e($l['subtotal'])?></span><strong><?=e(format_money($quote['subtotal'],$currency))?></strong></p>
      <?php if((float)$quote['discount_amount']>0):?><p><span><?=e($l['discount'])?></span><strong>- <?=e(format_money($quote['discount_amount'],$currency))?></strong></p><?php endif;?>
      <?php if((float)$quote['tax_rate']>0):?><p><span><?=e($l['tax'])?> (<?=e(number_format((float)$quote['tax_rate'],2,',','.'))?>%)</span><strong><?=e(format_money($quote['tax_amount'],$currency))?></strong></p><?php endif;?>
      <p class="grand"><span><?=e($l['grand_total'])?></span><strong><?=e(format_money($quote['total'],$currency))?></strong></p>
    </div>
  </div>

  <div class="quote-doc-footer">
    <div><strong>SÓCRATES ONE · Creative Studio</strong><span>socrates@socrates.la · www.socrates.la</span></div>
    <?php if(!empty($quote['valid_until'])):?><div class="quote-validity"><?=e($l['validity'])?> <strong><?=e(format_date($quote['valid_until']))?></strong></div><?php endif;?>
  </div>
</div>
<?php return (string)ob_get_clean();
}

function quote_document_css(): string
{
    return <<<'CSS'
.quote-document{max-width:1080px;margin:auto;background:#fff;color:#153149;font-family:Arial,Helvetica,sans-serif;border:1px solid #d9e5ed;border-radius:26px;overflow:hidden;box-shadow:0 24px 80px rgba(0,46,83,.16);position:relative}.quote-document *{box-sizing:border-box}.quote-accent-bar{height:10px;background:linear-gradient(90deg,#005fb2 0%,#0087dc 42%,#ff5a00 72%,#ff7b22 100%)}.quote-doc-header{padding:30px 36px 25px;display:flex;align-items:flex-start;justify-content:space-between;gap:28px;background:radial-gradient(circle at 88% 5%,rgba(255,90,0,.12),transparent 28%),linear-gradient(180deg,#fff 0%,#f8fbfd 100%);position:relative}.quote-doc-header:after{content:"";position:absolute;right:-70px;top:-110px;width:230px;height:230px;border:1px solid rgba(255,90,0,.15);border-radius:50%}.quote-branding{display:flex;gap:20px;align-items:center;min-width:0;position:relative;z-index:1}.quote-brand-logo{width:310px;max-width:100%;max-height:94px;object-fit:contain;object-position:left center}.quote-brand-copy{padding-left:18px;border-left:1px solid #d8e5ed}.quote-brand-copy strong{display:block;color:#002f57;font-size:15px}.quote-brand-copy span{display:block;color:#728798;font-size:12px;margin-top:5px}.quote-doc-title-card{min-width:225px;background:linear-gradient(145deg,#002f57 0%,#005b96 100%);color:#fff;padding:20px 23px;border-radius:22px;text-align:right;box-shadow:0 16px 36px rgba(0,47,87,.26);position:relative;z-index:1;overflow:hidden}.quote-doc-title-card:before{content:"";position:absolute;width:100px;height:100px;border-radius:50%;background:rgba(255,90,0,.24);right:-46px;top:-52px}.quote-doc-title-card small{display:block;opacity:.72;text-transform:uppercase;letter-spacing:1.6px;font-size:10px;position:relative}.quote-doc-title-card strong{display:block;font-size:27px;line-height:1.15;margin:9px 0 5px;position:relative}.quote-doc-title-card span{color:#cfeaff;font-size:13px;position:relative}.quote-doc-meta{padding:4px 36px 30px;display:flex;justify-content:space-between;gap:30px;border-bottom:1px solid #e0ebf2;background:#f8fbfd}.quote-client-block{display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #dbe7ee;border-radius:20px;padding:20px 22px;flex:1;min-width:0;box-shadow:0 8px 24px rgba(0,47,87,.05)}.quote-client-logo{width:78px;height:78px;object-fit:contain;border:1px solid #d9e4eb;border-radius:17px;padding:7px;background:#fff;flex:0 0 auto}.quote-client-block small,.quote-date-card small{color:#748898;text-transform:uppercase;font-size:10px;font-weight:700;letter-spacing:1px}.quote-client-block h2{margin:5px 0 9px;color:#002f57;font-size:27px;line-height:1.1}.quote-client-block p{margin:4px 0;color:#4c6577;line-height:1.45;font-size:13px}.quote-dates-grid{display:grid;grid-template-columns:repeat(2,minmax(138px,1fr));gap:12px;min-width:320px}.quote-date-card{background:#fff;border:1px solid #dce8ef;border-radius:17px;padding:15px 16px;box-shadow:0 7px 20px rgba(0,47,87,.04)}.quote-date-card strong{display:block;margin-top:6px;color:#14344c;font-size:15px}.quote-table-wrap{padding:28px 36px 0}.quote-items{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border:1px solid #dbe6ed;border-radius:18px}.quote-items th{background:#002f57;color:#fff;text-align:left;padding:15px 16px;border-bottom:0;font-size:10px;text-transform:uppercase;letter-spacing:.8px}.quote-items th:first-child{border-radius:17px 0 0 0}.quote-items th:last-child{border-radius:0 17px 0 0}.quote-items td{padding:16px;border-bottom:1px solid #e2ebf1;vertical-align:top;line-height:1.48;font-size:13px}.quote-items tbody tr:nth-child(even){background:#f8fbfd}.quote-items tbody tr:last-child td{border-bottom:0}.quote-items th:nth-child(n+2),.quote-items td:nth-child(n+2){text-align:right;white-space:nowrap}.quote-summary{display:grid;grid-template-columns:minmax(0,1fr) 335px;gap:28px;padding:30px 36px}.quote-extra{display:grid;gap:16px;align-content:start}.quote-info-card{background:#f8fbfd;border:1px solid #dbe7ee;border-radius:19px;padding:19px 20px 17px;break-inside:avoid}.quote-info-card h3{margin:0 0 11px;color:#ff5a00;font-size:11px;text-transform:uppercase;letter-spacing:1.1px}.quote-info-card strong{display:block;color:#002f57;font-size:16px;margin-bottom:7px}.quote-info-card p{margin:0;line-height:1.58;color:#486173;font-size:13px;white-space:normal}.quote-payment-card{background:linear-gradient(135deg,#fff7f0 0%,#fff 75%);border-color:#ffd4bb;border-left:6px solid #ff5a00}.quote-totals-card{background:linear-gradient(160deg,#002f57 0%,#064f82 100%);border-radius:23px;padding:21px 23px;color:#fff;box-shadow:0 18px 42px rgba(0,47,87,.24);align-self:start;break-inside:avoid}.quote-totals-card p{display:flex;justify-content:space-between;gap:18px;margin:0;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.14);font-size:13px}.quote-totals-card p:last-child{border-bottom:0}.quote-totals-card span{color:#d2e9ff}.quote-totals-card strong{color:#fff}.quote-totals-card .grand{font-size:21px;margin:10px -8px -8px;padding:18px 8px 10px;border-top:2px solid #ff6a16;border-bottom:0}.quote-totals-card .grand span{color:#fff}.quote-totals-card .grand strong{color:#ffb47f}.quote-doc-footer{background:#eef4f8;padding:20px 36px;display:flex;justify-content:space-between;align-items:center;gap:20px;color:#5d7485;font-size:12px;border-top:1px solid #dce7ee}.quote-doc-footer strong{display:block;color:#002f57;font-size:14px}.quote-doc-footer span{display:block;margin-top:3px}.quote-validity{background:#fff;border:1px solid #d9e6ee;border-radius:999px;padding:9px 14px}.quote-validity strong{color:#ff5a00}@media(max-width:860px){.quote-doc-header,.quote-doc-meta,.quote-doc-footer{flex-direction:column;align-items:flex-start}.quote-doc-title-card{text-align:left;width:100%;min-width:0}.quote-dates-grid{min-width:0;width:100%;grid-template-columns:repeat(2,minmax(0,1fr))}.quote-summary{grid-template-columns:1fr}.quote-client-block{width:100%}.quote-brand-copy{display:none}}@media(max-width:640px){.quote-document{border-radius:18px}.quote-doc-header,.quote-doc-meta,.quote-table-wrap,.quote-summary,.quote-doc-footer{padding-left:18px;padding-right:18px}.quote-brand-logo{width:250px}.quote-client-block h2{font-size:22px}.quote-dates-grid{grid-template-columns:1fr}.quote-items{display:block;overflow:auto}.quote-totals-card .grand{font-size:18px}}

/* Salida A4 estable: usada tanto por impresión como por descarga PDF. */
.quote-print-layout,.quote-exporting-pdf{width:194mm!important;max-width:194mm!important;min-width:194mm!important;margin:0 auto!important;padding:0!important;background:#fff!important;overflow:visible!important}
.quote-print-layout .quote-document,.quote-exporting-pdf .quote-document{width:194mm!important;max-width:194mm!important;min-width:194mm!important;margin:0!important;border-radius:0!important;box-shadow:none!important;overflow:visible!important}
.quote-print-layout .quote-doc-header,.quote-exporting-pdf .quote-doc-header{display:flex!important;flex-direction:row!important;align-items:flex-start!important;justify-content:space-between!important;gap:4mm!important;padding:5mm 6mm 4mm!important}
.quote-print-layout .quote-branding,.quote-exporting-pdf .quote-branding{display:flex!important;flex:1 1 auto!important;align-items:center!important;gap:3.5mm!important;min-width:0!important}
.quote-print-layout .quote-brand-logo,.quote-exporting-pdf .quote-brand-logo{width:58mm!important;max-width:58mm!important;height:auto!important;max-height:19mm!important;object-fit:contain!important;object-position:left center!important;flex:0 0 auto!important}
.quote-print-layout .quote-brand-copy,.quote-exporting-pdf .quote-brand-copy{display:block!important;flex:1 1 auto!important;padding-left:3.5mm!important;min-width:0!important}
.quote-print-layout .quote-brand-copy strong,.quote-exporting-pdf .quote-brand-copy strong{font-size:10px!important;line-height:1.25!important}
.quote-print-layout .quote-brand-copy span,.quote-exporting-pdf .quote-brand-copy span{font-size:8px!important;line-height:1.25!important;margin-top:1.2mm!important}
.quote-print-layout .quote-doc-title-card,.quote-exporting-pdf .quote-doc-title-card{width:42mm!important;min-width:42mm!important;max-width:42mm!important;padding:4mm!important;border-radius:5mm!important;text-align:right!important;flex:0 0 42mm!important}
.quote-print-layout .quote-doc-title-card strong,.quote-exporting-pdf .quote-doc-title-card strong{font-size:18px!important;margin:2mm 0 1.2mm!important}
.quote-print-layout .quote-doc-title-card small,.quote-exporting-pdf .quote-doc-title-card small{font-size:7px!important}
.quote-print-layout .quote-doc-title-card span,.quote-exporting-pdf .quote-doc-title-card span{font-size:8px!important}
.quote-print-layout .quote-doc-meta,.quote-exporting-pdf .quote-doc-meta{display:flex!important;flex-direction:row!important;align-items:stretch!important;justify-content:space-between!important;gap:4mm!important;padding:1mm 6mm 5mm!important}
.quote-print-layout .quote-client-block,.quote-exporting-pdf .quote-client-block{display:flex!important;flex:1 1 auto!important;width:auto!important;min-width:0!important;padding:4mm!important;border-radius:4mm!important;gap:3mm!important}
.quote-print-layout .quote-client-logo,.quote-exporting-pdf .quote-client-logo{width:16mm!important;height:16mm!important;border-radius:3mm!important;padding:1.5mm!important}
.quote-print-layout .quote-client-block h2,.quote-exporting-pdf .quote-client-block h2{font-size:17px!important;line-height:1.15!important;margin:1.5mm 0 2mm!important}
.quote-print-layout .quote-client-block p,.quote-exporting-pdf .quote-client-block p{font-size:8.4px!important;line-height:1.4!important;margin:.8mm 0!important}
.quote-print-layout .quote-client-block small,.quote-exporting-pdf .quote-client-block small,.quote-print-layout .quote-date-card small,.quote-exporting-pdf .quote-date-card small{font-size:6.5px!important}
.quote-print-layout .quote-dates-grid,.quote-exporting-pdf .quote-dates-grid{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:2.2mm!important;width:59mm!important;min-width:59mm!important;max-width:59mm!important;flex:0 0 59mm!important}
.quote-print-layout .quote-date-card,.quote-exporting-pdf .quote-date-card{padding:3mm!important;border-radius:3.5mm!important;min-width:0!important}
.quote-print-layout .quote-date-card strong,.quote-exporting-pdf .quote-date-card strong{font-size:9px!important;line-height:1.25!important;margin-top:1.5mm!important;overflow-wrap:anywhere!important}
.quote-print-layout .quote-table-wrap,.quote-exporting-pdf .quote-table-wrap{padding:5mm 6mm 0!important;overflow:visible!important}
.quote-print-layout .quote-items,.quote-exporting-pdf .quote-items{display:table!important;width:100%!important;table-layout:fixed!important;overflow:visible!important;border-radius:4mm!important}
.quote-print-layout .quote-items th,.quote-exporting-pdf .quote-items th{padding:3mm!important;font-size:6.7px!important;line-height:1.2!important}
.quote-print-layout .quote-items td,.quote-exporting-pdf .quote-items td{padding:3mm!important;font-size:8.3px!important;line-height:1.45!important;overflow-wrap:anywhere!important;word-break:normal!important}
.quote-print-layout .quote-items th:nth-child(1),.quote-exporting-pdf .quote-items th:nth-child(1),.quote-print-layout .quote-items td:nth-child(1),.quote-exporting-pdf .quote-items td:nth-child(1){width:60%!important;text-align:left!important;white-space:normal!important}
.quote-print-layout .quote-items th:nth-child(2),.quote-exporting-pdf .quote-items th:nth-child(2),.quote-print-layout .quote-items td:nth-child(2),.quote-exporting-pdf .quote-items td:nth-child(2){width:10%!important}
.quote-print-layout .quote-items th:nth-child(3),.quote-exporting-pdf .quote-items th:nth-child(3),.quote-print-layout .quote-items td:nth-child(3),.quote-exporting-pdf .quote-items td:nth-child(3){width:15%!important}
.quote-print-layout .quote-items th:nth-child(4),.quote-exporting-pdf .quote-items th:nth-child(4),.quote-print-layout .quote-items td:nth-child(4),.quote-exporting-pdf .quote-items td:nth-child(4){width:15%!important}
.quote-print-layout .quote-summary,.quote-exporting-pdf .quote-summary{display:grid!important;grid-template-columns:minmax(0,1fr) 60mm!important;gap:4mm!important;padding:5mm 6mm!important}
.quote-print-layout .quote-extra,.quote-exporting-pdf .quote-extra{display:grid!important;gap:3mm!important;min-width:0!important}
.quote-print-layout .quote-info-card,.quote-exporting-pdf .quote-info-card{padding:3.5mm!important;border-radius:4mm!important}
.quote-print-layout .quote-info-card h3,.quote-exporting-pdf .quote-info-card h3{font-size:7px!important;margin:0 0 2mm!important}
.quote-print-layout .quote-info-card strong,.quote-exporting-pdf .quote-info-card strong{font-size:10px!important;margin-bottom:1.5mm!important}
.quote-print-layout .quote-info-card p,.quote-exporting-pdf .quote-info-card p{font-size:8px!important;line-height:1.5!important;overflow-wrap:anywhere!important}
.quote-print-layout .quote-totals-card,.quote-exporting-pdf .quote-totals-card{width:60mm!important;padding:4mm!important;border-radius:5mm!important}
.quote-print-layout .quote-totals-card p,.quote-exporting-pdf .quote-totals-card p{font-size:8px!important;padding:2.5mm 0!important}
.quote-print-layout .quote-totals-card .grand,.quote-exporting-pdf .quote-totals-card .grand{font-size:14px!important;margin:2mm -1.5mm -1.5mm!important;padding:4mm 1.5mm 2mm!important}
.quote-print-layout .quote-doc-footer,.quote-exporting-pdf .quote-doc-footer{display:flex!important;flex-direction:row!important;align-items:center!important;justify-content:space-between!important;gap:4mm!important;padding:4mm 6mm!important;font-size:7.5px!important}
.quote-print-layout .quote-doc-footer strong,.quote-exporting-pdf .quote-doc-footer strong{font-size:8.5px!important}
.quote-print-layout .quote-validity,.quote-exporting-pdf .quote-validity{padding:2mm 3mm!important;white-space:nowrap!important}
.quote-print-layout .quote-doc-header,.quote-exporting-pdf .quote-doc-header,.quote-print-layout .quote-doc-meta,.quote-exporting-pdf .quote-doc-meta,.quote-print-layout .quote-info-card,.quote-exporting-pdf .quote-info-card,.quote-print-layout .quote-totals-card,.quote-exporting-pdf .quote-totals-card,.quote-print-layout .quote-doc-footer,.quote-exporting-pdf .quote-doc-footer{break-inside:avoid!important;page-break-inside:avoid!important}
.quote-print-layout .quote-items thead,.quote-exporting-pdf .quote-items thead{display:table-header-group!important}
.quote-print-layout .quote-items tr,.quote-exporting-pdf .quote-items tr{break-inside:avoid!important;page-break-inside:avoid!important}

@page{size:A4 portrait;margin:8mm}
@media print{
  html,body{margin:0!important;padding:0!important;background:#fff!important;width:auto!important;min-width:0!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
  body{overflow:visible!important}
  .print-actions{display:none!important}
  #quote-pdf-source{width:194mm!important;max-width:194mm!important;min-width:194mm!important;margin:0 auto!important;padding:0!important;background:#fff!important;overflow:visible!important}
  .quote-document{width:194mm!important;max-width:194mm!important;min-width:194mm!important;margin:0!important;box-shadow:none!important;border-radius:0!important;overflow:visible!important}
}
CSS;
}

function email_mime_payload(string $html, array $attachments = []): array
{
    $encode = static function (string $content): string {
        return rtrim(chunk_split(base64_encode($content), 76, "\r\n"));
    };

    if ($attachments === []) {
        return [
            'content_type' => 'text/html; charset=UTF-8',
            'transfer_encoding' => 'base64',
            'body' => $encode($html),
        ];
    }

    $boundary = '=_soc_' . bin2hex(random_bytes(16));
    $body = '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= $encode($html) . "\r\n";

    foreach ($attachments as $attachment) {
        $filename = preg_replace('/[^A-Za-z0-9._ -]+/u', '_', (string)($attachment['filename'] ?? 'archivo')) ?: 'archivo';
        $mime = trim((string)($attachment['mime'] ?? 'application/octet-stream')) ?: 'application/octet-stream';
        $content = (string)($attachment['content'] ?? '');
        if ($content === '') continue;
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Type: ' . $mime . '; name="' . addcslashes($filename, '"\\') . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= 'Content-Disposition: attachment; filename="' . addcslashes($filename, '"\\') . "\"\r\n\r\n";
        $body .= rtrim(chunk_split(base64_encode($content), 76, "\r\n")) . "\r\n";
    }
    $body .= '--' . $boundary . "--\r\n";

    return [
        'content_type' => 'multipart/mixed; boundary="' . $boundary . '"',
        'transfer_encoding' => null,
        'body' => $body,
    ];
}

function crm_valid_email(string $email): bool
{
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false && !preg_match('/[\r\n]/', $email);
}

function crm_mail_from_config(): array
{
    $fromEmail = strtolower(trim((string)setting('mail_from_email', 'socrates@socrates.la')));
    $fromName = trim((string)setting('mail_from_name', 'SÓCRATES CRM'));
    $smtpUser = trim((string)setting('smtp_username', ''));
    $forceUser = (string)setting('smtp_force_username_from', '1') === '1';

    if ($forceUser && crm_valid_email($smtpUser)) $fromEmail = strtolower($smtpUser);
    if (!crm_valid_email($fromEmail) && crm_valid_email($smtpUser)) $fromEmail = strtolower($smtpUser);
    if ($fromName === '') $fromName = 'SÓCRATES CRM';

    return [$fromEmail, $fromName];
}

function send_system_email(string $to, string $subject, string $html, array $attachments = []): array
{
    $to = strtolower(trim($to));
    $subject = trim(preg_replace('/[\r\n]+/', ' ', $subject) ?? '');
    if (!crm_valid_email($to)) return ['success'=>false,'error'=>'El destinatario de correo no es válido.','diagnostic'=>['stage'=>'recipient']];

    [$fromEmail, $fromName] = crm_mail_from_config();
    if (!crm_valid_email($fromEmail)) return ['success'=>false,'error'=>'El email remitente no es válido. Revísalo en Configuración.','diagnostic'=>['stage'=>'sender']];

    if ((string)setting('mail_mode', 'mail') === 'smtp') {
        return smtp_send($to, $subject, $html, $fromEmail, $fromName, $attachments);
    }

    $mime = email_mime_payload($html, $attachments);
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: ' . $mime['content_type'],
        'From: ' . crm_mime_header($fromName) . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
    ];
    if (!empty($mime['transfer_encoding'])) $headers[] = 'Content-Transfer-Encoding: ' . $mime['transfer_encoding'];
    $ok = @mail($to, crm_mime_header($subject), $mime['body'], implode("\r\n", $headers));
    return [
        'success' => $ok,
        'error' => $ok ? null : 'La función mail() del servidor devolvió un error.',
        'diagnostic' => ['mode'=>'mail','stage'=>$ok?'sent':'mail_function'],
    ];
}

function smtp_server_name(): string
{
    $name = trim((string)($_SERVER['SERVER_NAME'] ?? ''));
    if ($name === '' || !preg_match('/^[A-Za-z0-9.-]+$/', $name)) $name = 'localhost.localdomain';
    return $name;
}

function smtp_stream_context(string $host): mixed
{
    return stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ],
    ]);
}

function smtp_read_response($socket): array
{
    $response = '';
    $code = 0;
    while (($line = fgets($socket, 8192)) !== false) {
        $response .= $line;
        if (preg_match('/^(\d{3})([ -])/', $line, $m)) {
            $code = (int)$m[1];
            if ($m[2] === ' ') break;
        } elseif ($response !== '') {
            break;
        }
    }
    $meta = stream_get_meta_data($socket);
    if (!empty($meta['timed_out'])) throw new RuntimeException('Tiempo de espera agotado esperando respuesta del servidor SMTP.');
    if ($response === '') throw new RuntimeException('El servidor SMTP cerró la conexión sin responder.');
    return ['code'=>$code,'response'=>trim($response)];
}

function smtp_expect($socket, array $expected): string
{
    $r = smtp_read_response($socket);
    if (!in_array($r['code'], $expected, true)) {
        $message = $r['response'];
        if ($r['code'] === 535) $message .= ' · Autenticación rechazada: revisa usuario/contraseña o si el proveedor permite SMTP autenticado.';
        if ($r['code'] === 550 || $r['code'] === 553) $message .= ' · El servidor rechazó remitente o destinatario.';
        throw new RuntimeException('SMTP respondió: ' . $message);
    }
    return $r['response'];
}

function smtp_command($socket, string $command, array $expected): string
{
    $safe = preg_replace('/[\r\n]+/', '', $command) ?? '';
    if (@fwrite($socket, $safe . "\r\n") === false) throw new RuntimeException('No fue posible escribir en la conexión SMTP.');
    return smtp_expect($socket, $expected);
}

function smtp_capabilities(string $ehlo): array
{
    $caps = [];
    foreach (preg_split('/\r?\n/', strtoupper($ehlo)) ?: [] as $line) {
        $line = preg_replace('/^250[- ]/', '', trim($line)) ?? '';
        if ($line !== '') $caps[] = $line;
    }
    return $caps;
}

function smtp_auth_mechanisms(array $caps): array
{
    $methods = [];
    foreach ($caps as $cap) {
        if (preg_match('/^AUTH(?:=|\s+)(.+)$/i', $cap, $m)) {
            foreach (preg_split('/\s+/', trim($m[1])) ?: [] as $method) {
                if ($method !== '') $methods[] = strtoupper($method);
            }
        }
    }
    return array_values(array_unique($methods));
}

function smtp_connect_and_authenticate(bool $authenticate = true): array
{
    $host = trim((string)setting('smtp_host', ''));
    $port = (int)setting('smtp_port', '587');
    $encryption = strtolower(trim((string)setting('smtp_encryption', 'tls')));
    $username = trim((string)setting('smtp_username', ''));
    $storedPassword = (string)setting('smtp_password', '');
    $password = decrypt_secret($storedPassword);
    $timeout = max(5, min(60, (int)setting('smtp_timeout', '20')));

    if ($host === '' || $port <= 0) throw new RuntimeException('Configuración SMTP incompleta: falta host o puerto.');
    if (!in_array($encryption, ['none','tls','ssl'], true)) throw new RuntimeException('Tipo de seguridad SMTP no válido.');
    if ($authenticate && $username !== '' && $storedPassword !== '' && $password === '') {
        throw new RuntimeException('La contraseña SMTP guardada no pudo descifrarse. Vuelve a escribirla y guarda la configuración.');
    }
    if ($authenticate && $username !== '' && $password === '') throw new RuntimeException('Falta la contraseña SMTP.');

    $transport = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $context = smtp_stream_context($host);
    $errno = 0; $errstr = '';
    $socket = @stream_socket_client($transport, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        $last = error_get_last();
        $detail = $errstr ?: (string)($last['message'] ?? 'Error desconocido');
        throw new RuntimeException("No se pudo conectar a {$host}:{$port}: {$detail} ({$errno}).");
    }
    stream_set_timeout($socket, $timeout);

    $greeting = smtp_expect($socket, [220]);
    $ehlo = smtp_command($socket, 'EHLO ' . smtp_server_name(), [250]);
    $caps = smtp_capabilities($ehlo);

    if ($encryption === 'tls') {
        if (!array_filter($caps, static fn($c) => str_starts_with($c, 'STARTTLS'))) {
            @fclose($socket);
            throw new RuntimeException('El servidor no anuncia STARTTLS. Revisa puerto/seguridad (normalmente 587 + STARTTLS o 465 + SSL/TLS).');
        }
        smtp_command($socket, 'STARTTLS', [220]);
        $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($crypto !== true) {
            $last = error_get_last();
            @fclose($socket);
            throw new RuntimeException('No fue posible establecer TLS' . (!empty($last['message']) ? ': ' . $last['message'] : '.'));
        }
        $ehlo = smtp_command($socket, 'EHLO ' . smtp_server_name(), [250]);
        $caps = smtp_capabilities($ehlo);
    }

    $authUsed = 'none';
    if ($authenticate && $username !== '') {
        $methods = smtp_auth_mechanisms($caps);
        if (in_array('LOGIN', $methods, true) || $methods === []) {
            smtp_command($socket, 'AUTH LOGIN', [334]);
            smtp_command($socket, base64_encode($username), [334]);
            smtp_command($socket, base64_encode($password), [235]);
            $authUsed = 'LOGIN';
        } elseif (in_array('PLAIN', $methods, true)) {
            smtp_command($socket, 'AUTH PLAIN ' . base64_encode("\0{$username}\0{$password}"), [235]);
            $authUsed = 'PLAIN';
        } else {
            @fclose($socket);
            throw new RuntimeException('El servidor SMTP no ofrece AUTH LOGIN ni AUTH PLAIN. Métodos anunciados: ' . implode(', ', $methods));
        }
    }

    return [
        'socket'=>$socket,
        'host'=>$host,
        'port'=>$port,
        'encryption'=>$encryption,
        'username'=>$username,
        'greeting'=>$greeting,
        'ehlo'=>$ehlo,
        'capabilities'=>$caps,
        'auth'=>$authUsed,
    ];
}

function smtp_test_connection(): array
{
    try {
        $conn = smtp_connect_and_authenticate(true);
        $socket = $conn['socket'];
        try { smtp_command($socket, 'QUIT', [221]); } catch (Throwable) { @fwrite($socket, "QUIT\r\n"); }
        @fclose($socket);
        unset($conn['socket']);
        return ['success'=>true,'error'=>null,'diagnostic'=>$conn];
    } catch (Throwable $ex) {
        return ['success'=>false,'error'=>$ex->getMessage(),'diagnostic'=>['stage'=>'connect_auth']];
    }
}

function smtp_send(string $to, string $subject, string $html, string $fromEmail, string $fromName, array $attachments = []): array
{
    try {
        $conn = smtp_connect_and_authenticate(true);
        $socket = $conn['socket'];
        $envelopeFrom = $fromEmail;
        $username = (string)$conn['username'];
        $forceUser = (string)setting('smtp_force_username_from', '1') === '1';
        if ($forceUser && crm_valid_email($username)) $envelopeFrom = strtolower($username);

        smtp_command($socket, 'MAIL FROM:<' . $envelopeFrom . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $mime = email_mime_payload($html, $attachments);
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . preg_replace('/[^a-z0-9.-]/i', '', (string)$conn['host']) . '>',
            'From: ' . crm_mime_header($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'To: <' . $to . '>',
            'Subject: ' . crm_mime_header($subject),
            'MIME-Version: 1.0',
            'Content-Type: ' . $mime['content_type'],
        ];
        if (!empty($mime['transfer_encoding'])) $headers[] = 'Content-Transfer-Encoding: ' . $mime['transfer_encoding'];
        $body = str_replace(["\r\n", "\r"], "\n", (string)$mime['body']);
        $body = str_replace("\n", "\r\n", $body);
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . preg_replace('/^\./m', '..', $body) . "\r\n.";
        if (@fwrite($socket, $payload . "\r\n") === false) throw new RuntimeException('No fue posible transmitir el mensaje al servidor SMTP.');
        smtp_expect($socket, [250]);
        try { smtp_command($socket, 'QUIT', [221]); } catch (Throwable) { @fwrite($socket, "QUIT\r\n"); }
        @fclose($socket);
        unset($conn['socket']);
        return ['success'=>true,'error'=>null,'diagnostic'=>array_merge($conn,['stage'=>'sent','envelope_from'=>$envelopeFrom])];
    } catch (Throwable $ex) {
        if (isset($socket) && is_resource($socket)) { @fwrite($socket, "QUIT\r\n"); @fclose($socket); }
        return ['success'=>false,'error'=>$ex->getMessage(),'diagnostic'=>['stage'=>'smtp_send']];
    }
}


function upload_user_avatar(array $file, ?string $existing = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $existing;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('No fue posible subir la foto de perfil.');
    if (($file['size'] ?? 0) > 4 * 1024 * 1024) throw new RuntimeException('La foto no puede superar 4 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Formato inválido. Usa JPG, PNG o WEBP.');
    $directory = dirname(__DIR__) . '/uploads/avatars';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('No fue posible crear la carpeta de fotos.');
    }
    $name = bin2hex(random_bytes(14)) . '.' . $allowed[$mime];
    $target = $directory . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('No fue posible guardar la foto.');
    if ($existing && str_starts_with($existing, 'uploads/avatars/')) {
        $old = dirname(__DIR__) . '/' . $existing;
        if (is_file($old)) @unlink($old);
    }
    return 'uploads/avatars/' . $name;
}

function user_avatar_html(array $user, string $class = 'avatar'): string
{
    $name = trim((string)($user['name'] ?? 'Usuario'));
    $path = trim((string)($user['avatar_path'] ?? ''));
    if ($path !== '') {
        return '<span class="' . e($class) . ' avatar-photo"><img src="' . e($path) . '" alt="' . e($name) . '"></span>';
    }
    $initial = crm_upper(crm_substr($name !== '' ? $name : 'U', 0, 1));
    return '<span class="' . e($class) . '">' . e($initial) . '</span>';
}

function parse_alert_days(string|array|null $value): array
{
    $parts = is_array($value) ? $value : preg_split('/[^0-9]+/', (string)$value);
    $days = [];
    foreach ($parts ?: [] as $part) {
        $day = (int)$part;
        if ($day >= 0 && $day <= 365) $days[] = $day;
    }
    $days = array_values(array_unique($days));
    sort($days);
    return $days ?: [1];
}

function create_in_app_notification(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
{
    if (!db_table_exists('user_notifications')) return;
    db()->prepare('INSERT INTO user_notifications(user_id,title,message,type,link) VALUES (?,?,?,?,?)')
        ->execute([$userId,$title,$message,$type,$link]);
}

function unread_notifications_count(?int $userId = null): int
{
    $userId ??= (int)($_SESSION['user_id'] ?? 0);
    if (!$userId || !db_table_exists('user_notifications')) return 0;
    $stmt = db()->prepare('SELECT COUNT(*) FROM user_notifications WHERE user_id=? AND is_read=0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function send_whatsapp_cloud_message(string $to, string $message): array
{
    if ((string)setting('whatsapp_cloud_enabled', '0') !== '1') {
        return ['success'=>false,'error'=>'WhatsApp Cloud API no está activada.'];
    }
    if (!function_exists('curl_init')) return ['success'=>false,'error'=>'La extensión cURL no está disponible.'];
    $phoneNumberId = trim((string)setting('whatsapp_phone_number_id', ''));
    $token = decrypt_secret((string)setting('whatsapp_access_token', ''));
    $version = trim((string)setting('whatsapp_graph_version', 'v23.0'));
    $phone = preg_replace('/\D+/', '', $to);
    if ($phoneNumberId === '' || $token === '' || $phone === '') return ['success'=>false,'error'=>'Configuración de WhatsApp incompleta.'];

    $url = 'https://graph.facebook.com/' . rawurlencode($version) . '/' . rawurlencode($phoneNumberId) . '/messages';
    $payload = json_encode([
        'messaging_product'=>'whatsapp',
        'recipient_type'=>'individual',
        'to'=>$phone,
        'type'=>'text',
        'text'=>['preview_url'=>false,'body'=>$message],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>$payload,
        CURLOPT_TIMEOUT=>25,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $status < 200 || $status >= 300) {
        return ['success'=>false,'error'=>$error !== '' ? $error : ('WhatsApp respondió HTTP ' . $status . ': ' . crm_strimwidth((string)$response, 240))];
    }
    return ['success'=>true,'error'=>null];
}

function complete_user_login(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    crm_register_session($userId);
    try {
        $languageStmt = db()->prepare('SELECT language FROM users WHERE id=?');
        $languageStmt->execute([$userId]);
        $savedLanguage = (string)($languageStmt->fetchColumn() ?: 'es');
        $_SESSION['crm_language'] = isset(crm_supported_languages()[$savedLanguage]) ? $savedLanguage : 'es';
    } catch (Throwable) {
        $_SESSION['crm_language'] = $_SESSION['crm_language'] ?? 'es';
    }
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_method'], $_SESSION['two_factor_code_hash'], $_SESSION['two_factor_expires_at'], $_SESSION['two_factor_attempts'], $_SESSION['two_factor_last_sent'], $_SESSION['security_setup_required']);
    try{db()->prepare('UPDATE users SET last_login_at=NOW(),last_login_ip=?,last_login_user_agent=? WHERE id=?')->execute([crm_client_ip(),crm_user_agent(),$userId]);}
    catch(Throwable){db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$userId]);}
    crm_security_audit('login_completed','info','Acceso completado','user',$userId,$userId);
    log_activity('Inicio de sesión');
}

function begin_two_factor_login(array $user): array
{
    $code = (string)random_int(100000, 999999);
    $_SESSION['pending_2fa_user_id'] = (int)$user['id'];
    $_SESSION['two_factor_code_hash'] = password_hash($code, PASSWORD_DEFAULT);
    $_SESSION['two_factor_expires_at'] = time() + 600;
    $_SESSION['two_factor_attempts'] = 0;
    $_SESSION['two_factor_last_sent'] = time();

    $subject = 'Código de acceso · Sócrates Creative Studio';
    $html = '<!doctype html><html><body style="margin:0;background:#edf3f7;font-family:Arial,sans-serif;color:#183247">'
        . '<div style="max-width:560px;margin:35px auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 15px 45px rgba(0,47,87,.12)">'
        . '<div style="height:6px;background:#ff5300"></div><div style="padding:30px">'
        . '<h2 style="margin:0;color:#002f57">Verificación de acceso</h2>'
        . '<p>Hola ' . e((string)$user['name']) . ', utiliza este código para ingresar:</p>'
        . '<div style="font-size:34px;letter-spacing:8px;font-weight:bold;color:#0074c7;background:#f2f7fa;padding:18px;text-align:center;border-radius:12px">' . e($code) . '</div>'
        . '<p style="color:#657b8c;font-size:13px">El código vence en 10 minutos. Si no intentaste ingresar, cambia tu contraseña.</p>'
        . '</div></div></body></html>';
    return send_system_email((string)$user['email'], $subject, $html);
}

function begin_secure_two_factor_login(array $user): array
{
    $method=(string)($user['two_factor_method']??'email');
    $_SESSION['pending_2fa_user_id']=(int)$user['id'];
    $_SESSION['pending_2fa_method']=$method;
    $_SESSION['two_factor_expires_at']=time()+600;
    $_SESSION['two_factor_attempts']=0;
    if($method==='totp'&&!empty($user['totp_secret'])){
        return ['success'=>true,'error'=>null];
    }
    $_SESSION['pending_2fa_method']='email';
    return begin_two_factor_login($user);
}

function next_ticket_number(): string
{
    $prefix = 'SOP-' . date('Ym') . '-';
    if (!db_table_exists('support_tickets')) return $prefix . '0001';
    $stmt = db()->prepare('SELECT MAX(id) FROM support_tickets');
    $stmt->execute();
    return $prefix . str_pad((string)(((int)$stmt->fetchColumn()) + 1), 4, '0', STR_PAD_LEFT);
}

function payment_status_label(string $status): string
{
    return match($status){'paid'=>'Pagado','unpaid'=>'Sin pagar','pending'=>'Pendiente','partial'=>'Pago parcial',default=>ucfirst($status)};
}

function payment_status_class(string $status): string
{
    return match($status){'paid'=>'green','unpaid'=>'red','partial'=>'yellow',default=>'orange'};
}

function quote_email_document_html(array $quote, array $items): string
{
    $l = quote_labels((string)$quote['language']);
    $currency = (string)$quote['currency'];
    $logo = quote_brand_logo_path(true);
    $address = trim(implode(', ', array_filter([(string)($quote['address'] ?? ''),(string)($quote['city'] ?? ''),(string)($quote['country'] ?? '')])));
    $showPayment = (int)($quote['show_payment_method'] ?? 1) === 1;
    $paymentTitle = $showPayment ? trim((string)($quote['payment_method_title'] ?? '')) : '';
    $paymentDetails = $showPayment ? trim((string)($quote['payment_method_details'] ?? '')) : '';
    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr>'
            . '<td style="padding:13px 12px;border-bottom:1px solid #dbe7ee;vertical-align:top;line-height:1.5">' . nl2br(e((string)$item['description'])) . '</td>'
            . '<td style="padding:13px 8px;border-bottom:1px solid #dbe7ee;text-align:center;vertical-align:top">' . e(number_format((float)$item['quantity'],2,',','.')) . '</td>'
            . '<td style="padding:13px 8px;border-bottom:1px solid #dbe7ee;text-align:right;vertical-align:top;white-space:nowrap">' . e(format_money($item['unit_price'],$currency)) . '</td>'
            . '<td style="padding:13px 12px;border-bottom:1px solid #dbe7ee;text-align:right;vertical-align:top;font-weight:bold;white-space:nowrap">' . e(format_money($item['line_total'],$currency)) . '</td>'
            . '</tr>';
    }
    $extras = '';
    if (!empty($quote['notes'])) $extras .= '<div style="margin-bottom:12px;border:1px solid #d8e3ea;border-radius:12px;padding:14px"><div style="font-size:11px;color:#ff5300;font-weight:bold;text-transform:uppercase">' . e($l['notes']) . '</div><p style="margin:7px 0 0;line-height:1.5">' . nl2br(e((string)$quote['notes'])) . '</p></div>';
    if ($paymentTitle !== '' || $paymentDetails !== '') $extras .= '<div style="margin-bottom:12px;border:1px solid #ffb38f;border-radius:12px;padding:14px"><div style="font-size:11px;color:#ff5300;font-weight:bold;text-transform:uppercase">' . e($l['payment_method']) . '</div><strong style="display:block;margin-top:7px">' . e($paymentTitle) . '</strong><p style="margin:7px 0 0;line-height:1.5">' . nl2br(e($paymentDetails)) . '</p></div>';
    if (!empty($quote['terms'])) $extras .= '<div style="border:1px solid #d8e3ea;border-radius:12px;padding:14px"><div style="font-size:11px;color:#ff5300;font-weight:bold;text-transform:uppercase">' . e($l['terms']) . '</div><p style="margin:7px 0 0;line-height:1.5">' . nl2br(e((string)$quote['terms'])) . '</p></div>';

    $discount = (float)$quote['discount_amount'] > 0 ? '<tr><td style="padding:7px;color:#c94d14">' . e($l['discount']) . '</td><td style="padding:7px;text-align:right;font-weight:bold">- ' . e(format_money($quote['discount_amount'],$currency)) . '</td></tr>' : '';
    $tax = (float)$quote['tax_rate'] > 0 ? '<tr><td style="padding:7px">' . e($l['tax']) . ' (' . e(number_format((float)$quote['tax_rate'],2,',','.')) . '%)</td><td style="padding:7px;text-align:right;font-weight:bold">' . e(format_money($quote['tax_amount'],$currency)) . '</td></tr>' : '';

    return '<div style="max-width:900px;margin:0 auto;background:#fff;border-radius:18px;overflow:hidden;border:1px solid #d8e3ea">'
        . '<div style="height:6px;background:#ff5300"></div>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse"><tr>'
        . '<td style="padding:24px 28px"><img src="' . e($logo) . '" alt="Sócrates Creative Studio" style="display:block;max-width:240px;width:100%;height:auto"></td>'
        . '<td style="padding:24px 28px;text-align:right"><div style="display:inline-block;background:#003e69;color:#fff;border-radius:14px;padding:14px 18px"><div style="font-size:10px;letter-spacing:1px">' . e($l['quotation']) . '</div><div style="font-size:20px;font-weight:bold;margin-top:4px">' . e($quote['quotation_number']) . '</div><div style="font-size:11px;margin-top:4px">' . e(format_date($quote['issue_date'])) . '</div></div></td>'
        . '</tr></table>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:14px 0;padding:0 14px 18px"><tr>'
        . '<td width="65%" style="background:#f6fafc;border:1px solid #d8e3ea;border-radius:14px;padding:17px;vertical-align:top"><div style="font-size:10px;color:#657b8c;text-transform:uppercase">' . e($l['client']) . '</div><div style="font-size:20px;font-weight:bold;color:#002f57;margin:6px 0">' . e($quote['company_name']) . '</div>'
        . (!empty($quote['recipient_name']) ? '<div>' . e($quote['recipient_name']) . '</div>' : '')
        . (!empty($quote['tax_id']) ? '<div style="font-size:12px;color:#657b8c;margin-top:4px">' . e($l['tax_id']) . ': ' . e($quote['tax_id']) . '</div>' : '')
        . ($address !== '' ? '<div style="font-size:12px;color:#657b8c;margin-top:4px">' . e($address) . '</div>' : '')
        . '</td><td width="35%" style="background:#f6fafc;border:1px solid #d8e3ea;border-radius:14px;padding:17px;vertical-align:top"><div style="font-size:11px;color:#657b8c">' . e($l['valid_until']) . '</div><strong style="display:block;color:#002f57;margin:5px 0 12px">' . e(format_date($quote['valid_until'])) . '</strong><div style="font-size:11px;color:#657b8c">' . e($l['currency']) . '</div><strong style="display:block;color:#002f57;margin-top:5px">' . e($currency) . '</strong></td>'
        . '</tr></table>'
        . '<div style="padding:0 28px 20px"><table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #d8e3ea;border-radius:12px;overflow:hidden">'
        . '<thead><tr style="background:#003e69;color:#fff"><th style="padding:11px 12px;text-align:left;font-size:11px">' . e($l['description']) . '</th><th style="padding:11px 8px;font-size:11px">' . e($l['quantity']) . '</th><th style="padding:11px 8px;text-align:right;font-size:11px">' . e($l['unit_price']) . '</th><th style="padding:11px 12px;text-align:right;font-size:11px">' . e($l['total']) . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:18px 0;padding:0 10px 24px"><tr>'
        . '<td width="64%" style="vertical-align:top">' . $extras . '</td>'
        . '<td width="36%" style="vertical-align:top"><table width="100%" style="background:#003e69;color:#fff;border-radius:14px;padding:10px;border-collapse:separate"><tr><td style="padding:7px">' . e($l['subtotal']) . '</td><td style="padding:7px;text-align:right;font-weight:bold">' . e(format_money($quote['subtotal'],$currency)) . '</td></tr>' . $discount . $tax . '<tr><td style="padding:14px 7px 7px;border-top:2px solid #ff5300;font-size:18px;font-weight:bold">' . e($l['grand_total']) . '</td><td style="padding:14px 7px 7px;border-top:2px solid #ff5300;text-align:right;color:#ff8a4f;font-size:20px;font-weight:bold">' . e(format_money($quote['total'],$currency)) . '</td></tr></table></td>'
        . '</tr></table>'
        . '<div style="background:#edf4f8;padding:17px 28px;font-size:12px;color:#657b8c"><strong style="color:#002f57">SÓCRATES ONE · Creative Studio</strong><br>socrates@socrates.la · www.socrates.la</div>'
        . '</div>';
}

/* ==========================================================
   CRM 1.5.0 — idioma, iconos, archivos y utilidades comerciales
   ========================================================== */
function crm_supported_languages(): array
{
    return ['es'=>'Español','pt'=>'Português','en'=>'English'];
}

function crm_current_language(): string
{
    $session = (string)($_SESSION['crm_language'] ?? '');
    if (isset(crm_supported_languages()[$session])) return $session;
    try {
        $user = function_exists('current_user') ? current_user() : null;
        $lang = (string)($user['language'] ?? '');
        if (isset(crm_supported_languages()[$lang])) return $lang;
    } catch (Throwable) {}
    return 'es';
}

function crm_translations(): array
{
    return [
        'es'=>[
            'dashboard'=>'Dashboard','clients'=>'Clientes','contacts'=>'Contactos','activities'=>'Actividades','services'=>'Servicios de clientes',
            'expirations'=>'Vencimientos','catalog'=>'Catálogo','service_catalog'=>'Servicios disponibles','categories'=>'Categorías',
            'payment_methods'=>'Formas de pago','quotations'=>'Cotizaciones','contracts'=>'Contratos','client_contracts'=>'Contratos de clientes',
            'templates'=>'Plantillas editables','deals'=>'Negocios','pipeline'=>'Pipeline','marketing'=>'Marketing','mailing'=>'Mailing',
            'lead_campaigns'=>'Campañas de leads','lead_forms'=>'Formularios de captación','appointments'=>'Citas y reuniones','reports'=>'Informes',
            'billing'=>'Facturación','income'=>'Ingresos registrados','data_tools'=>'Importar y exportar','support'=>'Soporte','manual'=>'Manual de uso',
            'assistant'=>'SÓCRATES Copilot','users'=>'Usuarios','settings'=>'Configuración','profile'=>'Mi perfil y alertas','my_expirations'=>'Mis vencimientos',
            'notifications'=>'Notificaciones','logout'=>'Cerrar sesión','new_quote'=>'+ Cotización','new_client'=>'+ Cliente','search'=>'Buscar',
            'create'=>'Crear','edit'=>'Editar','save'=>'Guardar','cancel'=>'Cancelar','delete'=>'Eliminar','export'=>'Exportar','import'=>'Importar',
            'status'=>'Estado','owner'=>'Responsable','country'=>'País','currency'=>'Moneda','amount'=>'Valor','date'=>'Fecha','actions'=>'Acciones',
        ],
        'pt'=>[
            'dashboard'=>'Painel','clients'=>'Clientes','contacts'=>'Contatos','activities'=>'Atividades','services'=>'Serviços dos clientes',
            'expirations'=>'Vencimentos','catalog'=>'Catálogo','service_catalog'=>'Serviços disponíveis','categories'=>'Categorias',
            'payment_methods'=>'Formas de pagamento','quotations'=>'Cotações','contracts'=>'Contratos','client_contracts'=>'Contratos de clientes',
            'templates'=>'Modelos editáveis','deals'=>'Negócios','pipeline'=>'Pipeline','marketing'=>'Marketing','mailing'=>'E-mail marketing',
            'lead_campaigns'=>'Campanhas de leads','lead_forms'=>'Formulários de captação','appointments'=>'Agendamentos e reuniões','reports'=>'Relatórios',
            'billing'=>'Faturamento','income'=>'Receitas registradas','data_tools'=>'Importar e exportar','support'=>'Suporte','manual'=>'Manual de uso',
            'assistant'=>'SÓCRATES Copilot','users'=>'Usuários','settings'=>'Configurações','profile'=>'Meu perfil e alertas','my_expirations'=>'Meus vencimentos',
            'notifications'=>'Notificações','logout'=>'Sair','new_quote'=>'+ Cotação','new_client'=>'+ Cliente','search'=>'Pesquisar',
            'create'=>'Criar','edit'=>'Editar','save'=>'Salvar','cancel'=>'Cancelar','delete'=>'Excluir','export'=>'Exportar','import'=>'Importar',
            'status'=>'Status','owner'=>'Responsável','country'=>'País','currency'=>'Moeda','amount'=>'Valor','date'=>'Data','actions'=>'Ações',
        ],
        'en'=>[
            'dashboard'=>'Dashboard','clients'=>'Clients','contacts'=>'Contacts','activities'=>'Activities','services'=>'Client services',
            'expirations'=>'Expirations','catalog'=>'Catalog','service_catalog'=>'Available services','categories'=>'Categories',
            'payment_methods'=>'Payment methods','quotations'=>'Quotations','contracts'=>'Contracts','client_contracts'=>'Client contracts',
            'templates'=>'Editable templates','deals'=>'Deals','pipeline'=>'Pipeline','marketing'=>'Marketing','mailing'=>'Email campaigns',
            'lead_campaigns'=>'Lead campaigns','lead_forms'=>'Lead forms','appointments'=>'Appointments and meetings','reports'=>'Reports',
            'billing'=>'Billing','income'=>'Recorded income','data_tools'=>'Import and export','support'=>'Support','manual'=>'User manual',
            'assistant'=>'SÓCRATES Copilot','users'=>'Users','settings'=>'Settings','profile'=>'My profile and alerts','my_expirations'=>'My expirations',
            'notifications'=>'Notifications','logout'=>'Sign out','new_quote'=>'+ Quotation','new_client'=>'+ Client','search'=>'Search',
            'create'=>'Create','edit'=>'Edit','save'=>'Save','cancel'=>'Cancel','delete'=>'Delete','export'=>'Export','import'=>'Import',
            'status'=>'Status','owner'=>'Owner','country'=>'Country','currency'=>'Currency','amount'=>'Amount','date'=>'Date','actions'=>'Actions',
        ],
    ];
}

function t(string $key, ?string $fallback = null): string
{
    $lang = crm_current_language();
    $translations = crm_translations();
    return (string)($translations[$lang][$key] ?? $translations['es'][$key] ?? $fallback ?? $key);
}

function crm_icon(string $name, string $class = 'ui-icon'): string
{
    $paths = [
        'dashboard'=>'<path d="M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z"/>',
        'clients'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'contact'=>'<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M6 16c.9-2 5.1-2 6 0M15 8h3M15 12h3M15 16h3"/>',
        'activity'=>'<path d="M3 12h4l2-7 4 14 2-7h6"/>',
        'services'=>'<path d="m12 15 4-4m-9.5 9.5 4.1-4.1M14 4l6 6-3 3-6-6 3-3ZM4 14l6 6-3 3-6-6 3-3Z"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'catalog'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'payment'=>'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
        'quote'=>'<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h8M9 17h8"/>',
        'contract'=>'<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h6M9 17h4"/><path d="m15 19 2 2 4-5"/>',
        'project'=>'<path d="M3 7h18v13H3z"/><path d="M8 7V4h8v3M3 12h18"/><path d="m9 16 2 2 4-4"/>',
        'gantt'=>'<path d="M4 5h7v3H4zM8 11h10v3H8zM12 17h8v3h-8z"/><path d="M3 3v18"/>',
        'risk'=>'<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v4M12 17h.01"/>',
        'time'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/>',
        'resources'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11h5M19.5 8.5v5"/>',
        'deal'=>'<path d="M3 7h18v13H3z"/><path d="M8 7V4h8v3M3 12h18M10 12v2h4v-2"/>',
        'kanban'=>'<rect x="3" y="4" width="5" height="16" rx="1"/><rect x="10" y="4" width="5" height="10" rx="1"/><rect x="17" y="4" width="4" height="13" rx="1"/>',
        'mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'whatsapp'=>'<path d="M20.5 11.7a8.3 8.3 0 0 1-12.3 7.2L3 20l1.2-5A8.3 8.3 0 1 1 20.5 11.7Z"/><path d="M8.4 7.8c.2-.4.4-.4.7-.4h.5c.2 0 .4.1.5.4l.8 1.9c.1.3.1.5-.1.7l-.6.7c-.2.2-.2.4-.1.6.5 1 1.3 1.8 2.3 2.3.2.1.4.1.6-.1l.8-1c.2-.2.4-.3.7-.2l1.9.9c.3.1.4.3.4.6 0 .8-.4 1.6-1.1 2-1 .6-2.4.5-4.3-.4-1.9-.9-3.5-2.4-4.5-4.2-1-1.8-1.1-3.2-.5-4.1Z"/>',
        'edit'=>'<path d="M4 20h4l11-11-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'trash'=>'<path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6"/>',
        'toggle'=>'<rect x="3" y="7" width="18" height="10" rx="5"/><circle cx="9" cy="12" r="3"/>',
        'open'=>'<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 13v7H4V6h7"/>',
        'share'=>'<circle cx="18" cy="5" r="2"/><circle cx="6" cy="12" r="2"/><circle cx="18" cy="19" r="2"/><path d="m8 11 8-5M8 13l8 5"/>',
        'route'=>'<path d="M5 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm14 16a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM5 6v5c0 2 2 3 4 3h6c2 0 4 1 4 3v1"/>',
        'lead'=>'<path d="M12 2a7 7 0 0 0-4 12.7V18h8v-3.3A7 7 0 0 0 12 2Z"/><path d="M9 22h6M9 18h6"/>',
        'form'=>'<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h2M14 14h2M8 18h2"/>',
        'report'=>'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'billing'=>'<path d="M4 2h16v20l-3-2-3 2-3-2-3 2-4-2V2Z"/><path d="M8 7h8M8 11h8M8 15h5"/>',
        'import'=>'<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
        'upload'=>'<path d="M12 21V9M7 14l5-5 5 5M4 3h16"/>',
        'download'=>'<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
        'history'=>'<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
        'link'=>'<path d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1-.1l-2 2A5 5 0 0 0 12 20l1.1-1.1"/>',
        'support'=>'<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.2 2.4c-.7.3-.7 1.1-.7 1.6M12 17h.01"/>',
        'manual'=>'<path d="M4 4h6a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H4V4Zm16 0h-6a2 2 0 0 0-2 2v14a2 2 0 0 1 2-2h6V4Z"/>',
        'assistant'=>'<path d="M5 5.5h10.5a3.5 3.5 0 0 1 3.5 3.5v5a3.5 3.5 0 0 1-3.5 3.5H11l-4.5 3v-3H5A3.5 3.5 0 0 1 1.5 14V9A3.5 3.5 0 0 1 5 5.5Z"/><path d="M8 10h.01M13 10h.01M7.5 13.5h6"/><path d="M19 2v4M17 4h4"/>',
        'users'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'shield'=>'<path d="M12 2 20 5v6c0 5.2-3.4 9.4-8 11-4.6-1.6-8-5.8-8-11V5l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
        'settings'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1A1.7 1.7 0 0 0 8 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 3.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2v-4h.1A1.7 1.7 0 0 0 3.6 8a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8 3.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V2h4v.1A1.7 1.7 0 0 0 15 3.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 20.4 8c.38.57.98.94 1.67 1H22v4h-.1A1.7 1.7 0 0 0 20.4 15Z"/>',
        'bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'chevron'=>'<path d="m9 18 6-6-6-6"/>',
        'globe'=>'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>',
        'search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'star'=>'<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'documents'=>'<path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M4 7H3v14h11"/>',
    ];
    $path = $paths[$name] ?? $paths['catalog'];
    return '<svg class="'.e($class).' icon-'.e($name).'" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$path.'</svg>';
}

function crm_page_icon(string $script): string
{
    return match ($script) {
        'dashboard.php'=>'dashboard','clients.php','client_form.php','client_view.php'=>'clients','contacts.php','contact_form.php','contact_view.php'=>'contact',
        'activities.php','activity_form.php'=>'activity','deals.php','deal_flow.php','deal_form.php','deal_view.php','deal_convert_service.php'=>'deal','pipeline.php'=>'kanban',
        'services.php','service_form.php','service_renew.php'=>'services','expirations.php','my_expirations.php'=>'clock',
        'quote_catalog.php','service_catalog.php','service_catalog_form.php','service_categories.php','service_category_form.php'=>'catalog',
        'payment_methods.php','payment_method_form.php','payment_method_send.php'=>'payment','quotations.php','quotation_form.php','quotation_view.php','quotation_send.php','quotation_assign.php','quotation_print.php'=>'quote',
        'contracts.php','contract_form.php','contract_view.php','contract_templates.php','contract_template_form.php'=>'contract',
        'mailing.php','mailing_form.php','mailing_view.php','mailing_send.php'=>'mail','lead_campaigns.php','lead_campaign_form.php'=>'lead','lead_forms.php','lead_form_builder.php','lead_submissions.php','lead_form_public.php'=>'form',
        'appointments.php','appointment_form.php','appointment_send.php'=>'calendar','reports.php','reports_export.php'=>'report','billing.php','billing_services.php','billing_charge_pay.php','payments.php','payment_form.php'=>'billing',
        'project_dashboard.php','projects.php','project_form.php','project_view.php','project_members.php','project_milestone_form.php'=>'project',
        'project_kanban.php','project_task_form.php','project_task_action.php'=>'kanban','project_gantt.php'=>'gantt','project_calendar.php'=>'calendar',
        'project_timesheet.php','project_time_form.php'=>'time','project_risks.php','project_risk_form.php'=>'risk','project_expenses.php','project_expense_form.php'=>'billing',
        'document_dashboard.php','documents.php','document_view.php'=>'contract','document_upload.php'=>'import','document_approvals.php'=>'shield','document_folders.php'=>'contract','document_routes.php'=>'route','document_audit.php'=>'report','document_trash.php'=>'trash',
        'global_search.php'=>'search','data_tools.php'=>'import','support.php','support_ticket_form.php','support_ticket_view.php'=>'support','manual.php'=>'manual','tour.php'=>'route','assistant.php'=>'assistant',
        'users.php','user_form.php'=>'users','security_center.php','security_2fa.php'=>'shield','settings.php','license.php'=>'settings', default=>'catalog',
    };
}

function crm_upload_document(array $file, string $folder = 'documents', int $maxMb = 12): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('No fue posible subir el archivo.');
    if (($file['size'] ?? 0) > $maxMb * 1024 * 1024) throw new RuntimeException('El archivo no puede superar '.$maxMb.' MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = [
        'application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',
        'application/msword'=>'doc','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx',
        'application/vnd.ms-excel'=>'xls','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx',
        'text/plain'=>'txt','text/csv'=>'csv','application/zip'=>'zip',
    ];
    if (!isset($allowed[$mime])) throw new RuntimeException('Tipo de archivo no permitido.');
    $safeFolder = preg_replace('/[^a-z0-9_-]+/i','',$folder) ?: 'documents';
    $directory = dirname(__DIR__).'/uploads/'.$safeFolder;
    if (!is_dir($directory) && !mkdir($directory,0750,true) && !is_dir($directory)) throw new RuntimeException('No fue posible crear la carpeta de archivos.');
    @file_put_contents($directory.'/.htaccess',"Require all denied\n",LOCK_EX);
    @file_put_contents($directory.'/web.config','<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><security><authorization><remove users="*" roles="" verbs=""/><add accessType="Deny" users="*"/></authorization></security></system.webServer></configuration>',LOCK_EX);
    $stored = bin2hex(random_bytes(16)).'.'.$allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'],$directory.'/'.$stored)) throw new RuntimeException('No fue posible guardar el archivo.');@chmod($directory.'/'.$stored,0640);
    if(function_exists('crm_security_audit'))crm_security_audit('file_uploaded','info','Archivo cargado: '.(string)($file['name']??$stored));
    return ['file_name'=>(string)($file['name'] ?? $stored),'file_path'=>'uploads/'.$safeFolder.'/'.$stored,'mime_type'=>$mime,'file_size'=>(int)($file['size'] ?? 0)];
}

function crm_activity_type_label(string $type): string
{
    return crm_translate_text(match($type){'task'=>'Tarea','event'=>'Evento','call'=>'Llamada','email'=>'Correo','meeting'=>'Reunión','note'=>'Nota',default=>ucfirst($type)});
}

function crm_deal_status_label(string $status): string
{
    return crm_translate_text(match($status){'open'=>'Abierto','won'=>'Ganado','lost'=>'Perdido','cancelled'=>'Cancelado',default=>ucfirst($status)});
}

function crm_stage_localized(array $stage, ?string $lang = null): string
{
    $lang ??= crm_current_language();
    $field = 'name_'.(in_array($lang,['es','pt','en'],true)?$lang:'es');
    return trim((string)($stage[$field] ?? '')) ?: trim((string)($stage['name_es'] ?? 'Etapa'));
}

function crm_safe_csv_cell(mixed $value): string
{
    $value = (string)$value;
    if ($value !== '' && in_array($value[0], ['=','+','-','@'], true)) $value = "'".$value;
    return $value;
}

/**
 * Traducción progresiva de la interfaz heredada.
 * Solo reemplaza etiquetas exactas de la aplicación; nunca traduce datos escritos
 * por clientes, nombres de empresas, contratos, cotizaciones ni campos editables.
 */
function crm_ui_phrasebook(): array
{
    return [
        'pt' => [
            'Bienvenido'=>'Bem-vindo','Clientes, servicios, cotizaciones y renovaciones bajo control.'=>'Clientes, serviços, orçamentos e renovações sob controle.',
            'Contraseña'=>'Senha','INGRESAR'=>'ENTRAR','Código de verificación'=>'Código de verificação','Código'=>'Código',
            'VERIFICAR E INGRESAR'=>'VERIFICAR E ENTRAR','REENVIAR CÓDIGO'=>'REENVIAR CÓDIGO','Cancelar acceso'=>'Cancelar acesso',
            'Enviamos un código de 6 dígitos a'=>'Enviamos um código de 6 dígitos para','Vence en 10 minutos.'=>'Expira em 10 minutos.',
            'Dashboard'=>'Dashboard','Clientes'=>'Clientes','Contactos'=>'Contatos','Actividades'=>'Atividades','Negocios'=>'Negócios',
            'Servicios de clientes'=>'Serviços de clientes','Vencimientos'=>'Vencimentos','Servicios disponibles'=>'Serviços disponíveis',
            'Categorías'=>'Categorias','Formas de pago'=>'Formas de pagamento','Cotizaciones'=>'Orçamentos','Contratos'=>'Contratos',
            'Contratos de clientes'=>'Contratos de clientes','Plantillas editables'=>'Modelos editáveis','Marketing'=>'Marketing',
            'Mailing a clientes'=>'E-mail marketing','Campañas de leads'=>'Campanhas de leads','Formularios de captación'=>'Formulários de captação',
            'Citas y reuniones'=>'Compromissos e reuniões','Informes'=>'Relatórios','Facturación'=>'Faturamento','Ingresos registrados'=>'Receitas registradas',
            'Importar y exportar'=>'Importar e exportar','Soporte'=>'Suporte','Manual de uso'=>'Manual de uso','Asistente del CRM'=>'Assistente do CRM',
            'Usuarios'=>'Usuários','Configuración'=>'Configurações','Mi perfil y alertas'=>'Meu perfil e alertas','Mis vencimientos'=>'Meus vencimentos',
            'Notificaciones'=>'Notificações','Cerrar sesión'=>'Sair','Buscar'=>'Pesquisar','Filtrar'=>'Filtrar','Todos'=>'Todos','Todas'=>'Todas',
            'Activo'=>'Ativo','Activos'=>'Ativos','Inactivo'=>'Inativo','Inactivos'=>'Inativos','Pendiente'=>'Pendente','Pendientes'=>'Pendentes',
            'Completada'=>'Concluída','Completadas'=>'Concluídas','Cancelada'=>'Cancelada','Canceladas'=>'Canceladas','Atrasada'=>'Atrasada','Atrasadas'=>'Atrasadas',
            'Crear'=>'Criar','Editar'=>'Editar','Guardar'=>'Salvar','Cancelar'=>'Cancelar','Eliminar'=>'Excluir','Exportar'=>'Exportar','Importar'=>'Importar',
            'Estado'=>'Status','Responsable'=>'Responsável','País'=>'País','Moneda'=>'Moeda','Valor'=>'Valor','Fecha'=>'Data','Acciones'=>'Ações',
            'Cliente'=>'Cliente','Contacto'=>'Contato','Servicio'=>'Serviço','Descripción'=>'Descrição','Cantidad'=>'Quantidade','Total'=>'Total',
            'Nombre'=>'Nome','Empresa'=>'Empresa','Email'=>'E-mail','Teléfono'=>'Telefone','WhatsApp'=>'WhatsApp','Ciudad'=>'Cidade','Dirección'=>'Endereço',
            'Idioma'=>'Idioma','Español'=>'Espanhol','Português'=>'Português','English'=>'English','Sin asignar'=>'Não atribuído','Sin cliente'=>'Sem cliente',
            'Sin contacto'=>'Sem contato','Sin negocio'=>'Sem negócio','Sin fecha'=>'Sem data','Sin campaña'=>'Sem campanha','Sin país'=>'Sem país',
            'Crear cliente'=>'Criar cliente','Nuevo cliente'=>'Novo cliente','Guardar cliente'=>'Salvar cliente','Crear contacto'=>'Criar contato','Nuevo contacto'=>'Novo contato',
            'Crear actividad'=>'Criar atividade','Registrar actividad'=>'Registrar atividade','Guardar actividad'=>'Salvar atividade','Crear negocio'=>'Criar negócio',
            'Nuevo negocio'=>'Novo negócio','Guardar negocio'=>'Salvar negócio','Nueva cotización'=>'Novo orçamento','Crear cotización'=>'Criar orçamento',
            'Guardar cotización'=>'Salvar orçamento','Enviar cotización'=>'Enviar orçamento','Transferir'=>'Transferir','Adjuntos'=>'Anexos','Archivo'=>'Arquivo',
            'Crear contrato'=>'Criar contrato','Nuevo contrato'=>'Novo contrato','Guardar contrato'=>'Salvar contrato','Enviar contrato'=>'Enviar contrato',
            'Crear campaña'=>'Criar campanha','Nueva campaña'=>'Nova campanha','Crear formulario'=>'Criar formulário','Nuevo formulario'=>'Novo formulário',
            'Programar reunión'=>'Agendar reunião','Nueva reunión'=>'Nova reunião','Panel de informes'=>'Painel de relatórios','Datos e importación'=>'Dados e importação',
            'Personalizar dashboard'=>'Personalizar dashboard','Guardar disposición'=>'Salvar layout','Ver todos'=>'Ver todos','Ver todas'=>'Ver todas',
            'No hay datos.'=>'Não há dados.','No hay resultados.'=>'Nenhum resultado.','Sin datos registrados todavía'=>'Ainda não há dados registrados',
            'Tarea'=>'Tarefa','Evento'=>'Evento','Llamada'=>'Ligação','Correo'=>'E-mail','Reunión'=>'Reunião','Nota'=>'Nota',
            'Baja'=>'Baixa','Normal'=>'Normal','Alta'=>'Alta','Urgente'=>'Urgente','Abierto'=>'Aberto','Ganado'=>'Ganho','Perdido'=>'Perdido','Cancelado'=>'Cancelado','Mensual'=>'Mensal','Anual'=>'Anual','Única vez'=>'Uma vez','Personalizado'=>'Personalizado','Prospecto'=>'Prospecto','Aceptada'=>'Aceita','Finalizado'=>'Finalizado','Vencido'=>'Vencido','Vence hoy'=>'Vence hoje','Próximo a vencer'=>'Próximo do vencimento',
            'Forma de pago'=>'Forma de pagamento','Observaciones'=>'Observações','Condiciones'=>'Condições','Validez'=>'Validade','Aprobada'=>'Aprovada',
            'Rechazada'=>'Rejeitada','En espera'=>'Aguardando','Borrador'=>'Rascunho','Enviada'=>'Enviada','Vencida'=>'Vencida',
            'ACTUALIZAR'=>'ATUALIZAR','GUARDAR'=>'SALVAR','CREAR'=>'CRIAR','EDITAR'=>'EDITAR','VOLVER'=>'VOLTAR','ENVIAR'=>'ENVIAR',
            'Ayuda interna'=>'Ajuda interna','Preguntas rápidas'=>'Perguntas rápidas','PREGUNTAR'=>'PERGUNTAR','Alcance'=>'Escopo',
            'Línea de tiempo de actividades'=>'Linha do tempo de atividades','Actividad reciente'=>'Atividade recente','Mis próximas actividades'=>'Minhas próximas atividades',
            'Próximos vencimientos'=>'Próximos vencimentos','Clientes activos'=>'Clientes ativos','Negocios abiertos'=>'Negócios abertos',
            'Tareas atrasadas'=>'Tarefas atrasadas','Cotizaciones del mes'=>'Orçamentos do mês','Leads del mes'=>'Leads do mês',
            'Pronóstico ponderado del pipeline'=>'Previsão ponderada do pipeline','Negocios por país y moneda'=>'Negócios por país e moeda',
            'Ingresos de los últimos 12 meses'=>'Receitas dos últimos 12 meses','Actividad de los últimos 90 días'=>'Atividade dos últimos 90 dias',
        ],
        'en' => [
            'Bienvenido'=>'Welcome','Clientes, servicios, cotizaciones y renovaciones bajo control.'=>'Clients, services, quotations and renewals under control.',
            'Contraseña'=>'Password','INGRESAR'=>'SIGN IN','Código de verificación'=>'Verification code','Código'=>'Code',
            'VERIFICAR E INGRESAR'=>'VERIFY AND SIGN IN','REENVIAR CÓDIGO'=>'RESEND CODE','Cancelar acceso'=>'Cancel sign-in',
            'Enviamos un código de 6 dígitos a'=>'We sent a 6-digit code to','Vence en 10 minutos.'=>'It expires in 10 minutes.',
            'Dashboard'=>'Dashboard','Clientes'=>'Clients','Contactos'=>'Contacts','Actividades'=>'Activities','Negocios'=>'Deals',
            'Servicios de clientes'=>'Client services','Vencimientos'=>'Expirations','Servicios disponibles'=>'Available services',
            'Categorías'=>'Categories','Formas de pago'=>'Payment methods','Cotizaciones'=>'Quotations','Contratos'=>'Contracts',
            'Contratos de clientes'=>'Client contracts','Plantillas editables'=>'Editable templates','Marketing'=>'Marketing',
            'Mailing a clientes'=>'Email campaigns','Campañas de leads'=>'Lead campaigns','Formularios de captación'=>'Lead forms',
            'Citas y reuniones'=>'Appointments and meetings','Informes'=>'Reports','Facturación'=>'Billing','Ingresos registrados'=>'Recorded income',
            'Importar y exportar'=>'Import and export','Soporte'=>'Support','Manual de uso'=>'User manual','Asistente del CRM'=>'CRM assistant',
            'Usuarios'=>'Users','Configuración'=>'Settings','Mi perfil y alertas'=>'My profile and alerts','Mis vencimientos'=>'My expirations',
            'Notificaciones'=>'Notifications','Cerrar sesión'=>'Sign out','Buscar'=>'Search','Filtrar'=>'Filter','Todos'=>'All','Todas'=>'All',
            'Activo'=>'Active','Activos'=>'Active','Inactivo'=>'Inactive','Inactivos'=>'Inactive','Pendiente'=>'Pending','Pendientes'=>'Pending',
            'Completada'=>'Completed','Completadas'=>'Completed','Cancelada'=>'Cancelled','Canceladas'=>'Cancelled','Atrasada'=>'Overdue','Atrasadas'=>'Overdue',
            'Crear'=>'Create','Editar'=>'Edit','Guardar'=>'Save','Cancelar'=>'Cancel','Eliminar'=>'Delete','Exportar'=>'Export','Importar'=>'Import',
            'Estado'=>'Status','Responsable'=>'Owner','País'=>'Country','Moneda'=>'Currency','Valor'=>'Amount','Fecha'=>'Date','Acciones'=>'Actions',
            'Cliente'=>'Client','Contacto'=>'Contact','Servicio'=>'Service','Descripción'=>'Description','Cantidad'=>'Quantity','Total'=>'Total',
            'Nombre'=>'Name','Empresa'=>'Company','Email'=>'Email','Teléfono'=>'Phone','WhatsApp'=>'WhatsApp','Ciudad'=>'City','Dirección'=>'Address',
            'Idioma'=>'Language','Español'=>'Spanish','Português'=>'Portuguese','English'=>'English','Sin asignar'=>'Unassigned','Sin cliente'=>'No client',
            'Sin contacto'=>'No contact','Sin negocio'=>'No deal','Sin fecha'=>'No date','Sin campaña'=>'No campaign','Sin país'=>'No country',
            'Crear cliente'=>'Create client','Nuevo cliente'=>'New client','Guardar cliente'=>'Save client','Crear contacto'=>'Create contact','Nuevo contacto'=>'New contact',
            'Crear actividad'=>'Create activity','Registrar actividad'=>'Log activity','Guardar actividad'=>'Save activity','Crear negocio'=>'Create deal',
            'Nuevo negocio'=>'New deal','Guardar negocio'=>'Save deal','Nueva cotización'=>'New quotation','Crear cotización'=>'Create quotation',
            'Guardar cotización'=>'Save quotation','Enviar cotización'=>'Send quotation','Transferir'=>'Transfer','Adjuntos'=>'Attachments','Archivo'=>'File',
            'Crear contrato'=>'Create contract','Nuevo contrato'=>'New contract','Guardar contrato'=>'Save contract','Enviar contrato'=>'Send contract',
            'Crear campaña'=>'Create campaign','Nueva campaña'=>'New campaign','Crear formulario'=>'Create form','Nuevo formulario'=>'New form',
            'Programar reunión'=>'Schedule meeting','Nueva reunión'=>'New meeting','Panel de informes'=>'Reports dashboard','Datos e importación'=>'Data and import',
            'Personalizar dashboard'=>'Customize dashboard','Guardar disposición'=>'Save layout','Ver todos'=>'View all','Ver todas'=>'View all',
            'No hay datos.'=>'No data.','No hay resultados.'=>'No results.','Sin datos registrados todavía'=>'No data recorded yet',
            'Tarea'=>'Task','Evento'=>'Event','Llamada'=>'Call','Correo'=>'Email','Reunión'=>'Meeting','Nota'=>'Note',
            'Baja'=>'Low','Normal'=>'Normal','Alta'=>'High','Urgente'=>'Urgent','Abierto'=>'Open','Ganado'=>'Won','Perdido'=>'Lost','Cancelado'=>'Cancelled','Mensual'=>'Monthly','Anual'=>'Annual','Única vez'=>'One-time','Personalizado'=>'Custom','Prospecto'=>'Prospect','Aceptada'=>'Accepted','Finalizado'=>'Completed','Vencido'=>'Expired','Vence hoy'=>'Expires today','Próximo a vencer'=>'Expiring soon',
            'Forma de pago'=>'Payment method','Observaciones'=>'Notes','Condiciones'=>'Terms','Validez'=>'Validity','Aprobada'=>'Approved',
            'Rechazada'=>'Rejected','En espera'=>'Pending','Borrador'=>'Draft','Enviada'=>'Sent','Vencida'=>'Expired',
            'ACTUALIZAR'=>'UPDATE','GUARDAR'=>'SAVE','CREAR'=>'CREATE','EDITAR'=>'EDIT','VOLVER'=>'BACK','ENVIAR'=>'SEND',
            'Ayuda interna'=>'Internal help','Preguntas rápidas'=>'Quick questions','PREGUNTAR'=>'ASK','Alcance'=>'Scope',
            'Línea de tiempo de actividades'=>'Activity timeline','Actividad reciente'=>'Recent activity','Mis próximas actividades'=>'My upcoming activities',
            'Próximos vencimientos'=>'Upcoming expirations','Clientes activos'=>'Active clients','Negocios abiertos'=>'Open deals',
            'Tareas atrasadas'=>'Overdue tasks','Cotizaciones del mes'=>'Quotations this month','Leads del mes'=>'Leads this month',
            'Pronóstico ponderado del pipeline'=>'Weighted pipeline forecast','Negocios por país y moneda'=>'Deals by country and currency',
            'Ingresos de los últimos 12 meses'=>'Income over the last 12 months','Actividad de los últimos 90 días'=>'Activity over the last 90 days',
        ],
    ];
}

function crm_translate_text(string $text, ?string $lang = null): string
{
    $lang ??= crm_current_language();
    if ($lang === 'es') return $text;
    $book = array_replace(crm_ui_phrasebook()[$lang] ?? [], crm_ui_phrasebook_extra()[$lang] ?? []);
    $trimmed = trim($text);
    if ($trimmed === '') return $text;
    $translated = $book[$trimmed] ?? null;
    if ($translated === null) return $text;
    $leading = substr($text, 0, strlen($text) - strlen(ltrim($text)));
    $trailing = substr($text, strlen(rtrim($text)));
    return $leading.$translated.$trailing;
}

function crm_translate_rendered_html(string $html): string
{
    if (crm_current_language() === 'es' || $html === '') return $html;
    $protected = [];
    $html = preg_replace_callback('~<(script|style|textarea|pre|code)\b[^>]*>.*?</\1>~isu', static function(array $m) use (&$protected): string {
        $key = '___CRM_PROTECTED_'.count($protected).'___';
        $protected[$key] = $m[0];
        return $key;
    }, $html) ?? $html;
    $html = preg_replace_callback('/>([^<>]+)</u', static fn(array $m): string => '>'.crm_translate_text($m[1]).'<', $html) ?? $html;
    return $protected ? strtr($html, $protected) : $html;
}

// Aliases conservados para vistas heredadas.
function activity_type_label(string $type): string { return crm_activity_type_label($type); }
function deal_status_label(string $status): string { return crm_deal_status_label($status); }

function crm_ui_phrasebook_extra(): array
{
    return [
        'pt'=>['INICIO'=>'INÍCIO','Documentos'=>'Documentos','GESTIÓN DOCUMENTAL'=>'GESTÃO DOCUMENTAL','Biblioteca, versiones, permisos, aprobaciones y trazabilidad.'=>'Biblioteca, versões, permissões, aprovações e rastreabilidade.','Biblioteca, versiones, permisos, aprobaciones y trazabilidad'=>'Biblioteca, versões, permissões, aprovações e rastreabilidade','Dashboard documental'=>'Painel documental','Todos los documentos'=>'Todos os documentos','Biblioteca de documentos'=>'Biblioteca de documentos','Subir documento'=>'Enviar documento','Subir documentos'=>'Enviar documentos','Mis documentos'=>'Meus documentos','Compartidos conmigo'=>'Compartilhados comigo','Favoritos'=>'Favoritos','Por vencer'=>'A vencer','Aprobaciones pendientes'=>'Aprovações pendentes','Rutas de aprobación'=>'Fluxos de aprovação','Carpetas y organización'=>'Pastas e organização','Organización y rutas'=>'Organização e fluxos','Auditoría documental'=>'Auditoria documental','Papelera'=>'Lixeira','Gestión documental'=>'Gestão documental','Biblioteca documental'=>'Biblioteca documental','Todo el ciclo documental, en un solo lugar.'=>'Todo o ciclo documental em um só lugar.','Actividad reciente'=>'Atividade recente','Mis aprobaciones'=>'Minhas aprovações','Salud documental'=>'Saúde documental','Carpetas y estructura documental'=>'Pastas e estrutura documental','Carpetas y subcarpetas'=>'Pastas e subpastas','Crear carpeta'=>'Criar pasta','Nueva carpeta'=>'Nova pasta','Editar carpeta'=>'Editar pasta','Registro de actividad'=>'Registro de atividade','Papelera documental'=>'Lixeira documental','Documentos eliminados'=>'Documentos excluídos','Nuevo documento'=>'Novo documento','Vista previa'=>'Visualização','Historial de versiones'=>'Histórico de versões','Nueva versión'=>'Nova versão','Enlace seguro externo'=>'Link externo seguro','Comentarios'=>'Comentários','Privado'=>'Privado','Toda la empresa'=>'Toda a empresa','En revisión'=>'Em revisão','Aprobado'=>'Aprovado','Rechazado'=>'Rejeitado','Archivado'=>'Arquivado','COMERCIAL'=>'COMERCIAL','VENTAS'=>'VENDAS','OPERACIÓN'=>'OPERAÇÃO','CONTROL'=>'CONTROLE','AYUDA'=>'AJUDA','SISTEMA'=>'SISTEMA','CRM Y VENTAS'=>'CRM E VENDAS','Relaciones, oportunidades y captación'=>'Relacionamentos, oportunidades e captação','Actividades y tareas'=>'Atividades e tarefas','Negocios / Pipeline'=>'Negócios / Pipeline','MARKETING Y CAPTACIÓN'=>'MARKETING E CAPTAÇÃO','Campañas de leads'=>'Campanhas de leads','Formularios de captación'=>'Formulários de captação','Citas y reuniones'=>'Compromissos e reuniões','COTIZACIONES Y DOCUMENTOS'=>'COTAÇÕES E DOCUMENTOS','Catálogo, propuestas y contratos'=>'Catálogo, propostas e contratos','Resumen del catálogo'=>'Resumo do catálogo','PROPUESTAS'=>'PROPOSTAS','Plantillas de contrato'=>'Modelos de contrato','OPERACIÓN Y ENTREGA'=>'OPERAÇÃO E ENTREGA','Servicios recurrentes + Project Manager'=>'Serviços recorrentes + Project Manager','Servicios contratados'=>'Serviços contratados','Vencimientos y renovaciones'=>'Vencimentos e renovações','Portafolio de proyectos'=>'Portfólio de projetos','Kanban de tareas'=>'Kanban de tarefas','Gantt / Project'=>'Gantt / Project','Calendario'=>'Calendário','Riesgos'=>'Riscos','Costos y gastos'=>'Custos e despesas','FINANZAS E INFORMES'=>'FINANÇAS E RELATÓRIOS','Cobros, ingresos y visión ejecutiva'=>'Cobranças, receitas e visão executiva','Ciclos de cobro y renovaciones'=>'Ciclos de cobrança e renovações','Ingresos registrados'=>'Receitas registradas','FACTURACIÓN EXTERNA'=>'FATURAMENTO EXTERNO','HERRAMIENTAS Y AYUDA'=>'FERRAMENTAS E AJUDA','Datos, soporte y aprendizaje'=>'Dados, suporte e aprendizagem','Soporte / Tickets'=>'Suporte / Tickets','Centro de ayuda visual'=>'Central de ajuda visual','ADMINISTRACIÓN'=>'ADMINISTRAÇÃO','Accesos, seguridad y configuración'=>'Acessos, segurança e configurações','Usuarios y permisos'=>'Usuários e permissões','Licencia y versión'=>'Licença e versão','Gestión comercial y proyectos'=>'Gestão comercial e projetos','Centro de mando de proyectos'=>'Central de comando de projetos','Cronograma global'=>'Cronograma global','Registro de riesgos'=>'Registro de riscos','Horas y carga de trabajo'=>'Horas e carga de trabalho','Costos y gastos'=>'Custos e despesas','Importar y exportar'=>'Importar e exportar','Centro de soporte'=>'Central de suporte','Panel de configuración modular'=>'Painel de configuração modular','Empresa e identidad'=>'Empresa e identidade','Correo, SMTP y alertas'=>'E-mail, SMTP e alertas','Soporte y SLA'=>'Suporte e SLA','Acceso y seguridad'=>'Acesso e segurança','CRM y ventas'=>'CRM e vendas','Cotizaciones y documentos'=>'Cotações e documentos','Servicios y proyectos'=>'Serviços e projetos','Finanzas e informes'=>'Finanças e relatórios','Herramientas y ayuda'=>'Ferramentas e ajuda','Administración'=>'Administração','Dashboard de proyectos'=>'Painel de projetos','Calendario de proyectos'=>'Calendário de projetos','Horas y recursos'=>'Horas e recursos','Riesgos de proyectos'=>'Riscos de projetos','Costos de proyectos'=>'Custos de projetos','Importar y exportar'=>'Importar e exportar','Centro de soporte'=>'Central de suporte','Centro de Seguridad'=>'Central de Segurança','Licencia del sistema'=>'Licença do sistema','Configuración'=>'Configurações','Productos y servicios'=>'Produtos e serviços','Vencimientos'=>'Vencimentos',
            'Archivos del proyecto'=>'Arquivos do projeto','Auditoría de seguridad'=>'Auditoria de segurança','Carga de trabajo · últimos 30 días'=>'Carga de trabalho · últimos 30 dias','Checklist'=>'Checklist','Cola de tickets'=>'Fila de tickets','Comentarios'=>'Comentários','Costos y gastos de proyectos'=>'Custos e despesas de projetos','Cronograma'=>'Cronograma','Dependencias'=>'Dependências','Detalle de movimientos'=>'Detalhe dos movimentos','Detalle del período'=>'Detalhe do período','Equipo'=>'Equipe','Equipo del proyecto'=>'Equipe do projeto','Estado de tareas'=>'Status das tarefas','Hitos'=>'Marcos','Horas y carga de trabajo'=>'Horas e carga de trabalho','Importar, exportar y respaldar información'=>'Importar, exportar e fazer backup de dados','Matriz operativa'=>'Matriz operacional','Módulos de trabajo'=>'Módulos de trabalho','Políticas de acceso'=>'Políticas de acesso','Proyectos que requieren seguimiento'=>'Projetos que precisam de acompanhamento','Próximas tareas'=>'Próximas tarefas','Registrar gasto'=>'Registrar despesa','Registrar horas'=>'Registrar horas','Registro y seguimiento de riesgos'=>'Registro e acompanhamento de riscos','Salud del portafolio'=>'Saúde do portfólio','Seguridad de acceso personal'=>'Segurança de acesso pessoal','Sesiones activas'=>'Sessões ativas','¿Qué opción debo usar?'=>'Qual opção devo usar?','Nuevo usuario'=>'Novo usuário','Editar usuario'=>'Editar usuário','Crear acceso al CRM'=>'Criar acesso ao CRM','Actualizar usuario'=>'Atualizar usuário',
            'Foto de perfil'=>'Foto do perfil','Nombre completo'=>'Nome completo','Email de acceso'=>'E-mail de acesso','Perfil'=>'Perfil','Usuario activo'=>'Usuário ativo',
            'Seguridad y alertas'=>'Segurança e alertas','Autenticación de dos factores por email'=>'Autenticação de dois fatores por e-mail',
            'Recibir alertas por email'=>'Receber alertas por e-mail','Recibir alertas por WhatsApp'=>'Receber alertas por WhatsApp','Recibir alertas dentro del CRM'=>'Receber alertas no CRM',
            'Días de anticipación'=>'Dias de antecedência','El mismo día'=>'No mesmo dia','Permisos individuales'=>'Permissões individuais','GUARDAR USUARIO'=>'SALVAR USUÁRIO',
            'Nuevo contacto'=>'Novo contato','Editar contacto'=>'Editar contato','Crear contacto'=>'Criar contato','Actualizar contacto'=>'Atualizar contato','GUARDAR CONTACTO'=>'SALVAR CONTATO',
            'Base de datos de personas'=>'Base de dados de pessoas','Ficha individual'=>'Ficha individual','Nombre completo *'=>'Nome completo *','Email principal'=>'E-mail principal',
            'Nuevo negocio'=>'Novo negócio','Editar negocio'=>'Editar negócio','Crear oportunidad comercial'=>'Criar oportunidade comercial','Actualizar oportunidad'=>'Atualizar oportunidade',
            'Nombre del negocio *'=>'Nome do negócio *','Etapa'=>'Etapa','Origen'=>'Origem','Probabilidad %'=>'Probabilidade %','Fecha estimada de cierre'=>'Data estimada de fechamento',
            'Próxima acción'=>'Próxima ação','Fecha próxima acción'=>'Data da próxima ação','GUARDAR NEGOCIO'=>'SALVAR NEGÓCIO','Embudo de ventas'=>'Funil de vendas',
            'Flow comercial'=>'Fluxo comercial','Seguimiento lineal de oportunidades'=>'Acompanhamento linear de oportunidades','Etapa y avance'=>'Etapa e avanço','Actividad pendiente'=>'Atividade pendente',
            'Nueva actividad'=>'Nova atividade','Editar actividad'=>'Editar atividade','Registrar actividad'=>'Registrar atividade','Actualizar actividad'=>'Atualizar atividade','Asunto *'=>'Assunto *',
            'Fecha y hora'=>'Data e hora','Asignar a'=>'Atribuir a','Prioridad'=>'Prioridade','Enlace de reunión'=>'Link da reunião','Detalles'=>'Detalhes','GUARDAR ACTIVIDAD'=>'SALVAR ATIVIDADE',
            'Nueva cotización'=>'Novo orçamento','Editar cotización'=>'Editar orçamento','Cliente y presentación'=>'Cliente e apresentação','Fecha de emisión'=>'Data de emissão',
            'Válida hasta'=>'Válida até','Negocio relacionado'=>'Negócio relacionado','Servicios e ítems'=>'Serviços e itens','Ítem personalizado'=>'Item personalizado',
            'Cantidad *'=>'Quantidade *','Valor unitario *'=>'Valor unitário *','Total línea'=>'Total da linha','+ AGREGAR ÍTEM'=>'+ ADICIONAR ITEM',
            'Totales y condiciones'=>'Totais e condições','Subtotal'=>'Subtotal','Descuento'=>'Desconto','Impuestos (%)'=>'Impostos (%)','Mostrar la forma de pago en la cotización, el PDF y el email'=>'Mostrar a forma de pagamento no orçamento, PDF e e-mail',
            'Documentos adicionales'=>'Documentos adicionais','Agregar documento'=>'Adicionar documento','GUARDAR COTIZACIÓN'=>'SALVAR ORÇAMENTO','Enviar por email'=>'Enviar por e-mail',
            'Transferir cotización'=>'Transferir orçamento','Nuevo responsable'=>'Novo responsável','Transferir también el negocio relacionado'=>'Transferir também o negócio relacionado','TRANSFERIR'=>'TRANSFERIR',
            'Nuevo contrato'=>'Novo contrato','Editar contrato'=>'Editar contrato','Crear contrato desde una plantilla'=>'Criar contrato a partir de um modelo','Actualizar contrato'=>'Atualizar contrato',
            'Nombre del destinatario'=>'Nome do destinatário','Email del destinatario'=>'E-mail do destinatário','Contenido del contrato *'=>'Conteúdo do contrato *',
            'APLICAR PLANTILLA Y DATOS DEL CLIENTE'=>'APLICAR MODELO E DADOS DO CLIENTE','Administrar plantillas'=>'Gerenciar modelos','GUARDAR CONTRATO'=>'SALVAR CONTRATO',
            'Contratos enviados a clientes'=>'Contratos enviados aos clientes','ENVIAR AL CLIENTE'=>'ENVIAR AO CLIENTE','DESCARGAR PDF PARA FIRMA'=>'BAIXAR PDF PARA ASSINATURA',
            'Campañas de email'=>'Campanhas de e-mail','Nueva campaña'=>'Nova campanha','Crear campaña'=>'Criar campanha','Contenido y audiencia'=>'Conteúdo e público',
            'Contenido del email *'=>'Conteúdo do e-mail *','Puedes usar {{NOMBRE}} y {{EMPRESA}} para personalizar el correo.'=>'Use {{NOMBRE}} e {{EMPRESA}} para personalizar o e-mail.',
            'Seleccionar todos'=>'Selecionar todos','Enviar campaña'=>'Enviar campanha','GUARDAR CAMPAÑA'=>'SALVAR CAMPANHA',
            'Generación de leads'=>'Geração de leads','Información de campaña'=>'Informações da campanha','Objetivo'=>'Objetivo','Presupuesto'=>'Orçamento','Inicio *'=>'Início *',
            'Creador de formularios'=>'Criador de formulários','Diseño del formulario'=>'Design do formulário','Nombre interno *'=>'Nome interno *','Título público *'=>'Título público *',
            'Formulario activo'=>'Formulário ativo','Campos'=>'Campos','Obligatorio'=>'Obrigatório','Quitar'=>'Remover','Mensaje de confirmación'=>'Mensagem de confirmação','GUARDAR FORMULARIO'=>'SALVAR FORMULÁRIO',
            'Respuestas de formularios'=>'Respostas de formulários','Convertir'=>'Converter','Convertido'=>'Convertido','Programar cita'=>'Agendar compromisso','Editar cita'=>'Editar compromisso',
            'Datos de la reunión'=>'Dados da reunião','Programada'=>'Agendada','No asistió'=>'Não compareceu','Fin'=>'Fim','Enlace de la reunión'=>'Link da reunião','Enlace de reserva'=>'Link de reserva','GUARDAR CITA'=>'SALVAR COMPROMISSO',
            'Panel de informes'=>'Painel de relatórios','Pronóstico comercial'=>'Previsão comercial','Pronóstico ponderado'=>'Previsão ponderada','Pipeline por responsable'=>'Pipeline por responsável',
            'Cantidad'=>'Quantidade','Ponderado'=>'Ponderado','Importación masiva'=>'Importação em massa','Exportación y respaldo'=>'Exportação e backup','Archivo CSV'=>'Arquivo CSV',
            'Descargar plantilla'=>'Baixar modelo','IMPORTAR CSV'=>'IMPORTAR CSV','CSV para Excel'=>'CSV para Excel','Importación finalizada:'=>'Importação concluída:',
            'Asistente de uso'=>'Assistente de uso','Pregunta dónde está una función o cómo realizar una tarea dentro del CRM.'=>'Pergunte onde está uma função ou como realizar uma tarefa no CRM.',
            'Manual operativo del CRM'=>'Manual operacional do CRM','Flujo comercial recomendado'=>'Fluxo comercial recomendado','Inicio rápido'=>'Início rápido',
            'Actividad reciente'=>'Atividade recente','Dinero ingresado por mes'=>'Receita mensal','Clientes por país'=>'Clientes por país','Modalidades de contratación'=>'Modalidades de contratação',
            'Vencidos y por vencer'=>'Vencidos e próximos do vencimento','Por vencer 30 días'=>'Vencem em 30 dias','Vencidos sin pagar'=>'Vencidos sem pagamento',
            'Aprobadas'=>'Aprovadas','Rechazadas'=>'Rejeitadas','En espera'=>'Aguardando','CLIENTES ACTIVOS'=>'CLIENTES ATIVOS','VENCIMIENTOS 7 DÍAS'=>'VENCIMENTOS EM 7 DIAS',
            'VENCIMIENTOS 30 DÍAS'=>'VENCIMENTOS EM 30 DIAS','VENCIDOS SIN PAGAR'=>'VENCIDOS SEM PAGAMENTO','NEGOCIOS ABIERTOS'=>'NEGÓCIOS ABERTOS','TAREAS ATRASADAS'=>'TAREFAS ATRASADAS',
            'LEADS DEL MES'=>'LEADS DO MÊS','COTIZACIONES DEL MES'=>'ORÇAMENTOS DO MÊS','Tu panel de trabajo'=>'Seu painel de trabalho',
            'Facturación y renovaciones'=>'Faturamento e renovações','Servicios activos y renovaciones'=>'Serviços ativos e renovações','Resumen de facturación'=>'Resumo do faturamento','Servicios facturables'=>'Serviços faturáveis',
            'Próximos cobros y vencidos'=>'Próximas cobranças e vencidas','Facturación esperada este mes'=>'Faturamento previsto neste mês','Ingresado este mes'=>'Recebido neste mês','Ingresos recurrentes activos'=>'Receitas recorrentes ativas',
            'COBROS DEL MES'=>'COBRANÇAS DO MÊS','COBROS VENCIDOS'=>'COBRANÇAS VENCIDAS','COBROS PRÓXIMOS 7 DÍAS'=>'COBRANÇAS NOS PRÓXIMOS 7 DIAS','COBROS PRÓXIMOS 30 DÍAS'=>'COBRANÇAS NOS PRÓXIMOS 30 DIAS',
            'Próximo cobro'=>'Próxima cobrança','Estado de facturación'=>'Status do faturamento','Días de alerta'=>'Dias de alerta','Avisar por email'=>'Avisar por e-mail','Avisar dentro del CRM'=>'Avisar no CRM','Avisar por WhatsApp'=>'Avisar por WhatsApp',
            'Convertir negocio en servicio activo'=>'Converter negócio em serviço ativo','CONVERTIR EN SERVICIO ACTIVO'=>'CONVERTER EM SERVIÇO ATIVO','CREAR SERVICIO ACTIVO'=>'CRIAR SERVIÇO ATIVO','REGISTRAR PAGO'=>'REGISTRAR PAGAMENTO',
        ],
        'en'=>['INICIO'=>'HOME','Documentos'=>'Documents','GESTIÓN DOCUMENTAL'=>'DOCUMENT MANAGEMENT','Biblioteca, versiones, permisos, aprobaciones y trazabilidad.'=>'Library, versions, permissions, approvals and traceability.','Biblioteca, versiones, permisos, aprobaciones y trazabilidad'=>'Library, versions, permissions, approvals and traceability','Dashboard documental'=>'Document dashboard','Todos los documentos'=>'All documents','Biblioteca de documentos'=>'Document library','Subir documento'=>'Upload document','Subir documentos'=>'Upload documents','Mis documentos'=>'My documents','Compartidos conmigo'=>'Shared with me','Favoritos'=>'Favorites','Por vencer'=>'Expiring','Aprobaciones pendientes'=>'Pending approvals','Rutas de aprobación'=>'Approval workflows','Carpetas y organización'=>'Folders & organization','Organización y rutas'=>'Organization & workflows','Auditoría documental'=>'Document audit','Papelera'=>'Trash','Gestión documental'=>'Document management','Biblioteca documental'=>'Document library','Todo el ciclo documental, en un solo lugar.'=>'The entire document lifecycle in one place.','Actividad reciente'=>'Recent activity','Mis aprobaciones'=>'My approvals','Salud documental'=>'Document health','Carpetas y estructura documental'=>'Folders & document structure','Carpetas y subcarpetas'=>'Folders & subfolders','Crear carpeta'=>'Create folder','Nueva carpeta'=>'New folder','Editar carpeta'=>'Edit folder','Registro de actividad'=>'Activity log','Papelera documental'=>'Document trash','Documentos eliminados'=>'Deleted documents','Nuevo documento'=>'New document','Vista previa'=>'Preview','Historial de versiones'=>'Version history','Nueva versión'=>'New version','Enlace seguro externo'=>'Secure external link','Comentarios'=>'Comments','Privado'=>'Private','Toda la empresa'=>'Company-wide','En revisión'=>'In review','Aprobado'=>'Approved','Rechazado'=>'Rejected','Archivado'=>'Archived','COMERCIAL'=>'SALES','VENTAS'=>'QUOTES','OPERACIÓN'=>'OPERATIONS','CONTROL'=>'CONTROL','AYUDA'=>'HELP','SISTEMA'=>'SYSTEM','CRM Y VENTAS'=>'CRM & SALES','Relaciones, oportunidades y captación'=>'Relationships, opportunities and lead capture','Actividades y tareas'=>'Activities & tasks','Negocios / Pipeline'=>'Deals / Pipeline','MARKETING Y CAPTACIÓN'=>'MARKETING & LEAD CAPTURE','Campañas de leads'=>'Lead campaigns','Formularios de captación'=>'Lead forms','Citas y reuniones'=>'Appointments & meetings','COTIZACIONES Y DOCUMENTOS'=>'QUOTES & DOCUMENTS','Catálogo, propuestas y contratos'=>'Catalog, proposals and contracts','Resumen del catálogo'=>'Catalog overview','PROPUESTAS'=>'PROPOSALS','Plantillas de contrato'=>'Contract templates','OPERACIÓN Y ENTREGA'=>'OPERATIONS & DELIVERY','Servicios recurrentes + Project Manager'=>'Recurring services + Project Manager','Servicios contratados'=>'Client services','Vencimientos y renovaciones'=>'Expirations & renewals','Portafolio de proyectos'=>'Project portfolio','Kanban de tareas'=>'Task Kanban','Gantt / Project'=>'Gantt / Project','Calendario'=>'Calendar','Riesgos'=>'Risks','Costos y gastos'=>'Costs & expenses','FINANZAS E INFORMES'=>'FINANCE & REPORTS','Cobros, ingresos y visión ejecutiva'=>'Billing, income and executive view','Ciclos de cobro y renovaciones'=>'Billing cycles & renewals','Ingresos registrados'=>'Recorded income','FACTURACIÓN EXTERNA'=>'EXTERNAL BILLING','HERRAMIENTAS Y AYUDA'=>'TOOLS & HELP','Datos, soporte y aprendizaje'=>'Data, support and learning','Soporte / Tickets'=>'Support / Tickets','Centro de ayuda visual'=>'Visual Help Center','ADMINISTRACIÓN'=>'ADMINISTRATION','Accesos, seguridad y configuración'=>'Access, security and settings','Usuarios y permisos'=>'Users & permissions','Licencia y versión'=>'License & version','Gestión comercial y proyectos'=>'Sales and project management','Centro de mando de proyectos'=>'Project command center','Cronograma global'=>'Global timeline','Registro de riesgos'=>'Risk register','Horas y carga de trabajo'=>'Hours & workload','Costos y gastos'=>'Costs & expenses','Centro de soporte'=>'Support center','Panel de configuración modular'=>'Modular settings dashboard','Empresa e identidad'=>'Company & identity','Correo, SMTP y alertas'=>'Email, SMTP & alerts','Soporte y SLA'=>'Support & SLA','Acceso y seguridad'=>'Access & security','CRM y ventas'=>'CRM & Sales','Cotizaciones y documentos'=>'Quotes & Documents','Servicios y proyectos'=>'Services & Projects','Finanzas e informes'=>'Finance & Reports','Herramientas y ayuda'=>'Tools & Help','Administración'=>'Administration','Dashboard de proyectos'=>'Project dashboard','Calendario de proyectos'=>'Project calendar','Horas y recursos'=>'Time & resources','Riesgos de proyectos'=>'Project risks','Costos de proyectos'=>'Project costs','Importar y exportar'=>'Import & export','Centro de soporte'=>'Support center','Centro de Seguridad'=>'Security Center','Licencia del sistema'=>'System license','Configuración'=>'Settings','Productos y servicios'=>'Products & services','Vencimientos'=>'Expirations',
            'Archivos del proyecto'=>'Project files','Auditoría de seguridad'=>'Security audit','Carga de trabajo · últimos 30 días'=>'Workload · last 30 days','Checklist'=>'Checklist','Cola de tickets'=>'Ticket queue','Comentarios'=>'Comments','Costos y gastos de proyectos'=>'Project costs and expenses','Cronograma'=>'Timeline','Dependencias'=>'Dependencies','Detalle de movimientos'=>'Movement details','Detalle del período'=>'Period details','Equipo'=>'Team','Equipo del proyecto'=>'Project team','Estado de tareas'=>'Task status','Hitos'=>'Milestones','Horas y carga de trabajo'=>'Hours & workload','Importar, exportar y respaldar información'=>'Import, export and back up data','Matriz operativa'=>'Operational matrix','Módulos de trabajo'=>'Work modules','Políticas de acceso'=>'Access policies','Proyectos que requieren seguimiento'=>'Projects requiring follow-up','Próximas tareas'=>'Upcoming tasks','Registrar gasto'=>'Record expense','Registrar horas'=>'Log hours','Registro y seguimiento de riesgos'=>'Risk register and tracking','Salud del portafolio'=>'Portfolio health','Seguridad de acceso personal'=>'Personal access security','Sesiones activas'=>'Active sessions','¿Qué opción debo usar?'=>'Which option should I use?','Nuevo usuario'=>'New user','Editar usuario'=>'Edit user','Crear acceso al CRM'=>'Create CRM access','Actualizar usuario'=>'Update user',
            'Foto de perfil'=>'Profile photo','Nombre completo'=>'Full name','Email de acceso'=>'Login email','Perfil'=>'Role','Usuario activo'=>'Active user',
            'Seguridad y alertas'=>'Security and alerts','Autenticación de dos factores por email'=>'Email two-factor authentication',
            'Recibir alertas por email'=>'Receive email alerts','Recibir alertas por WhatsApp'=>'Receive WhatsApp alerts','Recibir alertas dentro del CRM'=>'Receive in-app alerts',
            'Días de anticipación'=>'Advance notice days','El mismo día'=>'Same day','Permisos individuales'=>'Individual permissions','GUARDAR USUARIO'=>'SAVE USER',
            'Nuevo contacto'=>'New contact','Editar contacto'=>'Edit contact','Crear contacto'=>'Create contact','Actualizar contacto'=>'Update contact','GUARDAR CONTACTO'=>'SAVE CONTACT',
            'Base de datos de personas'=>'People database','Ficha individual'=>'Individual record','Nombre completo *'=>'Full name *','Email principal'=>'Primary email',
            'Nuevo negocio'=>'New deal','Editar negocio'=>'Edit deal','Crear oportunidad comercial'=>'Create sales opportunity','Actualizar oportunidad'=>'Update opportunity',
            'Nombre del negocio *'=>'Deal name *','Etapa'=>'Stage','Origen'=>'Source','Probabilidad %'=>'Probability %','Fecha estimada de cierre'=>'Expected close date',
            'Próxima acción'=>'Next action','Fecha próxima acción'=>'Next action date','GUARDAR NEGOCIO'=>'SAVE DEAL','Embudo de ventas'=>'Sales pipeline',
            'Flow comercial'=>'Sales flow','Seguimiento lineal de oportunidades'=>'Linear opportunity tracking','Etapa y avance'=>'Stage and progress','Actividad pendiente'=>'Pending activity',
            'Nueva actividad'=>'New activity','Editar actividad'=>'Edit activity','Registrar actividad'=>'Log activity','Actualizar actividad'=>'Update activity','Asunto *'=>'Subject *',
            'Fecha y hora'=>'Date and time','Asignar a'=>'Assign to','Prioridad'=>'Priority','Enlace de reunión'=>'Meeting link','Detalles'=>'Details','GUARDAR ACTIVIDAD'=>'SAVE ACTIVITY',
            'Nueva cotización'=>'New quotation','Editar cotización'=>'Edit quotation','Cliente y presentación'=>'Client and presentation','Fecha de emisión'=>'Issue date',
            'Válida hasta'=>'Valid until','Negocio relacionado'=>'Related deal','Servicios e ítems'=>'Services and items','Ítem personalizado'=>'Custom item',
            'Cantidad *'=>'Quantity *','Valor unitario *'=>'Unit price *','Total línea'=>'Line total','+ AGREGAR ÍTEM'=>'+ ADD ITEM',
            'Totales y condiciones'=>'Totals and terms','Subtotal'=>'Subtotal','Descuento'=>'Discount','Impuestos (%)'=>'Taxes (%)','Mostrar la forma de pago en la cotización, el PDF y el email'=>'Show payment method in quotation, PDF and email',
            'Documentos adicionales'=>'Additional documents','Agregar documento'=>'Add document','GUARDAR COTIZACIÓN'=>'SAVE QUOTATION','Enviar por email'=>'Send by email',
            'Transferir cotización'=>'Transfer quotation','Nuevo responsable'=>'New owner','Transferir también el negocio relacionado'=>'Also transfer the related deal','TRANSFERIR'=>'TRANSFER',
            'Nuevo contrato'=>'New contract','Editar contrato'=>'Edit contract','Crear contrato desde una plantilla'=>'Create contract from a template','Actualizar contrato'=>'Update contract',
            'Nombre del destinatario'=>'Recipient name','Email del destinatario'=>'Recipient email','Contenido del contrato *'=>'Contract content *',
            'APLICAR PLANTILLA Y DATOS DEL CLIENTE'=>'APPLY TEMPLATE AND CLIENT DATA','Administrar plantillas'=>'Manage templates','GUARDAR CONTRATO'=>'SAVE CONTRACT',
            'Contratos enviados a clientes'=>'Contracts sent to clients','ENVIAR AL CLIENTE'=>'SEND TO CLIENT','DESCARGAR PDF PARA FIRMA'=>'DOWNLOAD SIGNATURE PDF',
            'Campañas de email'=>'Email campaigns','Nueva campaña'=>'New campaign','Crear campaña'=>'Create campaign','Contenido y audiencia'=>'Content and audience',
            'Contenido del email *'=>'Email content *','Puedes usar {{NOMBRE}} y {{EMPRESA}} para personalizar el correo.'=>'Use {{NOMBRE}} and {{EMPRESA}} to personalize the email.',
            'Seleccionar todos'=>'Select all','Enviar campaña'=>'Send campaign','GUARDAR CAMPAÑA'=>'SAVE CAMPAIGN',
            'Generación de leads'=>'Lead generation','Información de campaña'=>'Campaign information','Objetivo'=>'Objective','Presupuesto'=>'Budget','Inicio *'=>'Start *',
            'Creador de formularios'=>'Form builder','Diseño del formulario'=>'Form design','Nombre interno *'=>'Internal name *','Título público *'=>'Public title *',
            'Formulario activo'=>'Active form','Campos'=>'Fields','Obligatorio'=>'Required','Quitar'=>'Remove','Mensaje de confirmación'=>'Confirmation message','GUARDAR FORMULARIO'=>'SAVE FORM',
            'Respuestas de formularios'=>'Form submissions','Convertir'=>'Convert','Convertido'=>'Converted','Programar cita'=>'Schedule appointment','Editar cita'=>'Edit appointment',
            'Datos de la reunión'=>'Meeting details','Programada'=>'Scheduled','No asistió'=>'No-show','Fin'=>'End','Enlace de la reunión'=>'Meeting link','Enlace de reserva'=>'Booking link','GUARDAR CITA'=>'SAVE APPOINTMENT',
            'Panel de informes'=>'Reports dashboard','Pronóstico comercial'=>'Sales forecast','Pronóstico ponderado'=>'Weighted forecast','Pipeline por responsable'=>'Pipeline by owner',
            'Cantidad'=>'Quantity','Ponderado'=>'Weighted','Importación masiva'=>'Bulk import','Exportación y respaldo'=>'Export and backup','Archivo CSV'=>'CSV file',
            'Descargar plantilla'=>'Download template','IMPORTAR CSV'=>'IMPORT CSV','CSV para Excel'=>'CSV for Excel','Importación finalizada:'=>'Import completed:',
            'Asistente de uso'=>'Usage assistant','Pregunta dónde está una función o cómo realizar una tarea dentro del CRM.'=>'Ask where a feature is or how to perform a task in the CRM.',
            'Manual operativo del CRM'=>'CRM operating manual','Flujo comercial recomendado'=>'Recommended sales workflow','Inicio rápido'=>'Quick start',
            'Actividad reciente'=>'Recent activity','Dinero ingresado por mes'=>'Monthly income','Clientes por país'=>'Clients by country','Modalidades de contratación'=>'Contract types',
            'Vencidos y por vencer'=>'Expired and upcoming','Por vencer 30 días'=>'Due within 30 days','Vencidos sin pagar'=>'Expired and unpaid',
            'Aprobadas'=>'Approved','Rechazadas'=>'Rejected','En espera'=>'Pending','CLIENTES ACTIVOS'=>'ACTIVE CLIENTS','VENCIMIENTOS 7 DÍAS'=>'EXPIRING IN 7 DAYS',
            'VENCIMIENTOS 30 DÍAS'=>'EXPIRING IN 30 DAYS','VENCIDOS SIN PAGAR'=>'EXPIRED AND UNPAID','NEGOCIOS ABIERTOS'=>'OPEN DEALS','TAREAS ATRASADAS'=>'OVERDUE TASKS',
            'LEADS DEL MES'=>'LEADS THIS MONTH','COTIZACIONES DEL MES'=>'QUOTATIONS THIS MONTH','Tu panel de trabajo'=>'Your workspace',
            'Facturación y renovaciones'=>'Billing and renewals','Servicios activos y renovaciones'=>'Active services and renewals','Resumen de facturación'=>'Billing overview','Servicios facturables'=>'Billable services',
            'Próximos cobros y vencidos'=>'Upcoming and overdue charges','Facturación esperada este mes'=>'Expected billing this month','Ingresado este mes'=>'Received this month','Ingresos recurrentes activos'=>'Active recurring revenue',
            'COBROS DEL MES'=>'CHARGES THIS MONTH','COBROS VENCIDOS'=>'OVERDUE CHARGES','COBROS PRÓXIMOS 7 DÍAS'=>'CHARGES DUE IN 7 DAYS','COBROS PRÓXIMOS 30 DÍAS'=>'CHARGES DUE IN 30 DAYS',
            'Próximo cobro'=>'Next charge','Estado de facturación'=>'Billing status','Días de alerta'=>'Alert days','Avisar por email'=>'Notify by email','Avisar dentro del CRM'=>'Notify in CRM','Avisar por WhatsApp'=>'Notify by WhatsApp',
            'Convertir negocio en servicio activo'=>'Convert deal to active service','CONVERTIR EN SERVICIO ACTIVO'=>'CONVERT TO ACTIVE SERVICE','CREAR SERVICIO ACTIVO'=>'CREATE ACTIVE SERVICE','REGISTRAR PAGO'=>'RECORD PAYMENT',
        ],
    ];
}


// CRM 1.8.0 - facturación recurrente y renovaciones.
function billing_next_date(?string $date, string $contractType, ?int $customMonths = null): ?string
{
    if (!$date) return null;
    try { $base = new DateTimeImmutable($date); } catch (Throwable) { return null; }
    return match ($contractType) {
        'monthly' => $base->modify('+1 month')->format('Y-m-d'),
        'annual' => $base->modify('+1 year')->format('Y-m-d'),
        'custom' => $base->modify('+' . max(1, (int)$customMonths) . ' months')->format('Y-m-d'),
        default => null,
    };
}

function billing_status_label(string $status): string
{
    return crm_translate_text(match ($status) {
        'paid' => 'Pagado', 'partial' => 'Pago parcial', 'overdue' => 'Vencido',
        'cancelled' => 'Cancelado', 'not_billable' => 'No facturable', default => 'Pendiente',
    });
}

function billing_status_class(string $status): string
{
    return match ($status) { 'paid'=>'green','partial'=>'orange','overdue'=>'red','cancelled'=>'gray','not_billable'=>'gray',default=>'yellow' };
}

function billing_sync_charge(array $service): ?int
{
    if (!db_table_exists('billing_charges')) return null;
    if (($service['status'] ?? '') !== 'active') return null;
    $due = $service['next_billing_date'] ?? $service['expiration_date'] ?? null;
    if (!$due || ($service['billing_status'] ?? '') === 'not_billable') return null;
    $stmt=db()->prepare('SELECT id FROM billing_charges WHERE service_id=? AND due_date=? LIMIT 1');
    $stmt->execute([(int)$service['id'],$due]);
    $existing=(int)($stmt->fetchColumn()?:0);
    if($existing) return $existing;
    db()->prepare("INSERT INTO billing_charges(service_id,client_id,due_date,amount,currency,status,created_at) VALUES (?,?,?,?,?,'pending',NOW())")
      ->execute([(int)$service['id'],(int)$service['client_id'],$due,(float)($service['amount']??0),(string)($service['currency']??'USD')]);
    return (int)db()->lastInsertId();
}

function billing_advance_service(int $serviceId, string $paidDate): ?string
{
    $stmt=db()->prepare('SELECT * FROM client_services WHERE id=?');$stmt->execute([$serviceId]);$s=$stmt->fetch();if(!$s)return null;
    $current=$s['next_billing_date'] ?: $s['expiration_date'] ?: $paidDate;
    $next=billing_next_date($current,(string)$s['contract_type'],$s['custom_period_months']!==null?(int)$s['custom_period_months']:null);
    if($next){
        db()->prepare("UPDATE client_services SET payment_status='pending',paid_at=?,last_billing_date=?,next_billing_date=?,expiration_date=?,billing_status='pending',alert_sent_for_date=NULL WHERE id=?")
          ->execute([$paidDate,$current,$next,$next,$serviceId]);
    }else{
        db()->prepare("UPDATE client_services SET payment_status='paid',paid_at=?,last_billing_date=?,billing_status='paid',next_billing_date=NULL WHERE id=?")
          ->execute([$paidDate,$current,$serviceId]);
    }
    return $next;
}
