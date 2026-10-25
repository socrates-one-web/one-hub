<?php
// v0.9.51 · Compatibility layer for shared hosting still running PHP 7.4.
// EMAILSEND uses these PHP 8 string helpers throughout the UI; defining them
// here prevents a fatal error on older-but-supported PHP 7.4 installations.
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle === '' || strpos((string)$haystack, (string)$needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        return $needle === '' || substr($haystack, 0, strlen($needle)) === $needle;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        $len = strlen($needle);
        return $len <= strlen($haystack) && substr($haystack, -$len) === $needle;
    }
}

// v0.9.54 · Custom session handler.
// Bypasses the hosting-wide PHP session.save_path completely.
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/session-handler.php';
$emailsendSessionHandler = emailsend_start_session();

error_reporting(E_ALL);
ini_set('display_errors', '0');

$configFile = APP_ROOT . '/config.php';
if (!is_file($configFile)) {
    // El instalador web se excluyó para preservar la seguridad.
    // Nunca redirigir a /install/: Apache lo protege con un 403.
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    exit('EMAILSEND ONE aun no esta configurado. Entra a ONE HUB > CRM y ventas > EMAILSEND ONE para ver las instrucciones protegidas.');
}
$config = file_exists($configFile) ? require $configFile : [];

define('APP_NAME', $config['app_name'] ?? 'EMAILSEND ONE');
// Public demo is always read-only for external email delivery, regardless of config.php.
define('APP_DEMO_MODE', true);

$appUrl = rtrim((string)($config['app_url'] ?? ''), '/');
define('APP_URL', $appUrl);
define('APP_KEY', $config['app_key'] ?? 'change-me');

require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/smtp.php';

// Upgrade-in-place. Never blocks login if the hosting restricts ALTER/SHOW operations.
if (file_exists($configFile)) {
    try {
        $exists=db()->query("SHOW TABLES LIKE 'report_clients'")->fetchColumn();
        if(!$exists){
            $sql=file_get_contents(APP_ROOT.'/install/upgrade-0.2.sql');
            if($sql) db()->exec($sql);
        }

        // v0.5 profile + deliverability fields. We check each column before altering so this is safe to run repeatedly.
        $userCols=[];
        foreach(db()->query('SHOW COLUMNS FROM users') as $col) $userCols[$col['Field']]=true;
        foreach([
            'avatar_url'=>"ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL AFTER role",
            'phone'=>"ALTER TABLE users ADD COLUMN phone VARCHAR(80) NULL AFTER avatar_url",
            'job_title'=>"ALTER TABLE users ADD COLUMN job_title VARCHAR(140) NULL AFTER phone",
            'company'=>"ALTER TABLE users ADD COLUMN company VARCHAR(180) NULL AFTER job_title",
            'timezone'=>"ALTER TABLE users ADD COLUMN timezone VARCHAR(80) NULL AFTER company"
        ] as $name=>$sql) if(empty($userCols[$name])) db()->exec($sql);

        $settingsCols=[];
        foreach(db()->query('SHOW COLUMNS FROM settings') as $col) $settingsCols[$col['Field']]=true;
        foreach([
            'dkim_selector'=>"ALTER TABLE settings ADD COLUMN dkim_selector VARCHAR(80) NULL AFTER default_from_email",
            'reply_to_email'=>"ALTER TABLE settings ADD COLUMN reply_to_email VARCHAR(190) NULL AFTER dkim_selector"
        ] as $name=>$sql) if(empty($settingsCols[$name])) db()->exec($sql);

        // v0.8.1: listas asignables a clientes + audiencias de múltiples listas.
        $listCols=[];
        foreach(db()->query('SHOW COLUMNS FROM contact_lists') as $col) $listCols[$col['Field']]=true;
        if(empty($listCols['client_id'])) db()->exec('ALTER TABLE contact_lists ADD COLUMN client_id INT UNSIGNED NULL AFTER tenant_id');
        try { db()->exec('ALTER TABLE contact_lists ADD INDEX idx_contact_lists_client_id (client_id)'); } catch (Throwable $ignore) {}
        // Existing clients receive an empty dedicated list if they do not have one yet.
        db()->exec("INSERT INTO contact_lists(tenant_id,client_id,name,created_at) SELECT rc.tenant_id,rc.id,CONCAT(rc.company,' · Contactos'),NOW() FROM report_clients rc WHERE NOT EXISTS (SELECT 1 FROM contact_lists l WHERE l.tenant_id=rc.tenant_id AND l.client_id=rc.id)");
        $campaignLists=db()->query("SHOW TABLES LIKE 'campaign_lists'")->fetchColumn();
        if(!$campaignLists){
            db()->exec('CREATE TABLE campaign_lists (campaign_id BIGINT UNSIGNED NOT NULL,list_id INT UNSIGNED NOT NULL,PRIMARY KEY(campaign_id,list_id),INDEX(list_id),FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            db()->exec('INSERT IGNORE INTO campaign_lists(campaign_id,list_id) SELECT id,list_id FROM campaigns WHERE list_id IS NOT NULL');
        }


        // v0.8.8: ficha de contacto/empresa ampliada para importaciones masivas.
        $contactCols=[];
        foreach(db()->query('SHOW COLUMNS FROM contacts') as $col) $contactCols[$col['Field']]=true;
        foreach([
            'company_name'=>"ALTER TABLE contacts ADD COLUMN company_name VARCHAR(220) NULL AFTER name",
            'sales_band'=>"ALTER TABLE contacts ADD COLUMN sales_band VARCHAR(120) NULL AFTER company_name",
            'company_type'=>"ALTER TABLE contacts ADD COLUMN company_type VARCHAR(120) NULL AFTER sales_band",
            'employees'=>"ALTER TABLE contacts ADD COLUMN employees VARCHAR(60) NULL AFTER company_type",
            'annual_income'=>"ALTER TABLE contacts ADD COLUMN annual_income VARCHAR(140) NULL AFTER employees",
            'economic_activity'=>"ALTER TABLE contacts ADD COLUMN economic_activity VARCHAR(255) NULL AFTER annual_income",
            'region'=>"ALTER TABLE contacts ADD COLUMN region VARCHAR(180) NULL AFTER economic_activity",
            'province'=>"ALTER TABLE contacts ADD COLUMN province VARCHAR(160) NULL AFTER region",
            'commune'=>"ALTER TABLE contacts ADD COLUMN commune VARCHAR(160) NULL AFTER province",
            'legal_representative'=>"ALTER TABLE contacts ADD COLUMN legal_representative VARCHAR(220) NULL AFTER commune",
            'phone_fixed'=>"ALTER TABLE contacts ADD COLUMN phone_fixed VARCHAR(180) NULL AFTER legal_representative",
            'phone_mobile'=>"ALTER TABLE contacts ADD COLUMN phone_mobile VARCHAR(220) NULL AFTER phone_fixed",
            'source_data_json'=>"ALTER TABLE contacts ADD COLUMN source_data_json LONGTEXT NULL AFTER phone_mobile"
        ] as $name=>$sql) if(empty($contactCols[$name])) { try { db()->exec($sql); } catch (Throwable $ignore) {} }

        // v0.9.0: usuarios internos con acceso por listas + importador inteligente y campos personalizados.
        try { db()->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','client') NOT NULL DEFAULT 'user'"); } catch (Throwable $ignore) {}
        $userCols=[];
        foreach(db()->query('SHOW COLUMNS FROM users') as $col) $userCols[$col['Field']]=true;
        if(empty($userCols['list_access_mode'])) {
            try { db()->exec("ALTER TABLE users ADD COLUMN list_access_mode ENUM('all','assigned') NOT NULL DEFAULT 'all' AFTER role"); } catch (Throwable $ignore) {}
        }
        db()->exec("CREATE TABLE IF NOT EXISTS user_contact_lists (
            user_id INT UNSIGNED NOT NULL,
            list_id INT UNSIGNED NOT NULL,
            assigned_at DATETIME NOT NULL,
            PRIMARY KEY(user_id,list_id),
            INDEX(list_id),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS contact_custom_fields (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            name VARCHAR(180) NOT NULL,
            field_key VARCHAR(190) NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_contact_custom_field (tenant_id,field_key),
            INDEX(tenant_id),
            FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS contact_custom_values (
            contact_id BIGINT UNSIGNED NOT NULL,
            field_id INT UNSIGNED NOT NULL,
            value TEXT NULL,
            PRIMARY KEY(contact_id,field_id),
            INDEX(field_id),
            FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
            FOREIGN KEY(field_id) REFERENCES contact_custom_fields(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS contact_import_aliases (
            tenant_id INT UNSIGNED NOT NULL,
            normalized_header VARCHAR(190) NOT NULL,
            source_header VARCHAR(255) NULL,
            target_type ENUM('standard','custom','ignore') NOT NULL,
            target_key VARCHAR(190) NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(tenant_id,normalized_header),
            INDEX(tenant_id),
            FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v0.9.2: permisos granulares por usuario y panel.
        db()->exec("CREATE TABLE IF NOT EXISTS user_permissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            module VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            allowed TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            UNIQUE KEY uq_user_permission(user_id,module,action),
            INDEX(user_id),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        // Manual fallback is documented in install/upgrade-0.8.1.sql.
    }
}
