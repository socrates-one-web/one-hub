CREATE TABLE IF NOT EXISTS tenants (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','user','client') NOT NULL DEFAULT 'user',
 list_access_mode ENUM('all','assigned') NOT NULL DEFAULT 'all',
 avatar_url VARCHAR(500) NULL,
 phone VARCHAR(80) NULL,
 job_title VARCHAR(140) NULL,
 company VARCHAR(180) NULL,
 timezone VARCHAR(80) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_lists (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 client_id INT UNSIGNED NULL,
 name VARCHAR(160) NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX(tenant_id), INDEX(client_id),
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS user_contact_lists (
 user_id INT UNSIGNED NOT NULL,
 list_id INT UNSIGNED NOT NULL,
 assigned_at DATETIME NOT NULL,
 PRIMARY KEY(user_id,list_id),
 INDEX(list_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_custom_fields (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_import_aliases (
 tenant_id INT UNSIGNED NOT NULL,
 normalized_header VARCHAR(190) NOT NULL,
 source_header VARCHAR(255) NULL,
 target_type ENUM('standard','custom','ignore') NOT NULL,
 target_key VARCHAR(190) NULL,
 updated_at DATETIME NOT NULL,
 PRIMARY KEY(tenant_id,normalized_header),
 INDEX(tenant_id),
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contacts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 list_id INT UNSIGNED NULL,
 name VARCHAR(160) NULL,
 company_name VARCHAR(220) NULL,
 sales_band VARCHAR(120) NULL,
 company_type VARCHAR(120) NULL,
 employees VARCHAR(60) NULL,
 annual_income VARCHAR(140) NULL,
 economic_activity VARCHAR(255) NULL,
 region VARCHAR(180) NULL,
 province VARCHAR(160) NULL,
 commune VARCHAR(160) NULL,
 legal_representative VARCHAR(220) NULL,
 phone_fixed VARCHAR(180) NULL,
 phone_mobile VARCHAR(220) NULL,
 source_data_json LONGTEXT NULL,
 email VARCHAR(190) NOT NULL,
 status ENUM('active','bounced','unsubscribed') NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_tenant_email(tenant_id,email),
 INDEX(tenant_id), INDEX(list_id),
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
 FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS contact_custom_values (
 contact_id BIGINT UNSIGNED NOT NULL,
 field_id INT UNSIGNED NOT NULL,
 value TEXT NULL,
 PRIMARY KEY(contact_id,field_id),
 INDEX(field_id),
 FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
 FOREIGN KEY(field_id) REFERENCES contact_custom_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaigns (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 name VARCHAR(180) NOT NULL,
 subject VARCHAR(220) NOT NULL,
 preheader VARCHAR(255) NULL,
 from_name VARCHAR(160) NULL,
 from_email VARCHAR(190) NULL,
 list_id INT UNSIGNED NULL,
 blocks_json LONGTEXT NULL,
 html LONGTEXT NULL,
 status ENUM('draft','queued','sending','sent','paused') NOT NULL DEFAULT 'draft',
 scheduled_at DATETIME NULL,
 sent_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX(tenant_id), INDEX(list_id),
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
 FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaign_lists (
 campaign_id BIGINT UNSIGNED NOT NULL,
 list_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(campaign_id,list_id),
 INDEX(list_id),
 FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
 FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deliveries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 campaign_id BIGINT UNSIGNED NOT NULL,
 contact_id BIGINT UNSIGNED NOT NULL,
 email VARCHAR(190) NOT NULL,
 token CHAR(48) NOT NULL UNIQUE,
 status ENUM('queued','sent','delivered','bounced','failed','unsubscribed') NOT NULL DEFAULT 'queued',
 sent_at DATETIME NULL,
 delivered_at DATETIME NULL,
 first_open_at DATETIME NULL,
 last_open_at DATETIME NULL,
 opens INT UNSIGNED NOT NULL DEFAULT 0,
 clicks INT UNSIGNED NOT NULL DEFAULT 0,
 last_click_at DATETIME NULL,
 error_text TEXT NULL,
 created_at DATETIME NOT NULL,
 INDEX(campaign_id), INDEX(contact_id), INDEX(tenant_id),
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
 FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
 FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS click_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 delivery_id BIGINT UNSIGNED NOT NULL,
 url TEXT NOT NULL,
 clicked_at DATETIME NOT NULL,
 ip_hash CHAR(64) NULL,
 user_agent VARCHAR(500) NULL,
 INDEX(delivery_id), FOREIGN KEY(delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
 tenant_id INT UNSIGNED PRIMARY KEY,
 smtp_host VARCHAR(190) NULL,
 smtp_port INT NULL DEFAULT 587,
 smtp_user VARCHAR(190) NULL,
 smtp_pass TEXT NULL,
 smtp_secure ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
 default_from_name VARCHAR(160) NULL,
 default_from_email VARCHAR(190) NULL,
 dkim_selector VARCHAR(80) NULL,
 reply_to_email VARCHAR(190) NULL,
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- v0.2: clientes finales con acceso SOLO a reportes autorizados
CREATE TABLE IF NOT EXISTS report_clients (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT UNSIGNED NOT NULL,
 company VARCHAR(180) NOT NULL,
 contact_name VARCHAR(160) NULL,
 email VARCHAR(190) NULL,
 phone VARCHAR(80) NULL,
 logo_url VARCHAR(500) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 INDEX(tenant_id),
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS report_users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id INT UNSIGNED NOT NULL,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 last_login_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 INDEX(client_id),
 FOREIGN KEY(client_id) REFERENCES report_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaign_report_access (
 campaign_id BIGINT UNSIGNED NOT NULL,
 client_id INT UNSIGNED NOT NULL,
 granted_at DATETIME NOT NULL,
 PRIMARY KEY(campaign_id,client_id),
 INDEX(client_id),
 FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
 FOREIGN KEY(client_id) REFERENCES report_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS user_permissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 module VARCHAR(50) NOT NULL,
 action VARCHAR(50) NOT NULL,
 allowed TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NULL,
 UNIQUE KEY uq_user_permission(user_id,module,action),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
