CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'viewer',
    permissions LONGTEXT NULL,
    avatar_path VARCHAR(255) NULL,
    whatsapp VARCHAR(60) NULL,
    notify_email TINYINT(1) NOT NULL DEFAULT 1,
    notify_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    notify_inapp TINYINT(1) NOT NULL DEFAULT 1,
    alert_days VARCHAR(100) NOT NULL DEFAULT '1',
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    two_factor_method ENUM('email','totp') NOT NULL DEFAULT 'email',
    totp_secret TEXT NULL,
    backup_codes LONGTEXT NULL,
    failed_login_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    password_changed_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    last_login_user_agent VARCHAR(500) NULL,
    language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    department VARCHAR(120) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(190) NOT NULL,
    trade_name VARCHAR(190) NULL,
    tax_id VARCHAR(80) NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    address VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    industry VARCHAR(150) NULL,
    status ENUM('active','inactive','prospect') NOT NULL DEFAULT 'active',
    start_date DATE NULL,
    logo_path VARCHAR(255) NULL,
    contact_name VARCHAR(190) NULL,
    contact_role VARCHAR(150) NULL,
    email VARCHAR(190) NULL,
    secondary_email VARCHAR(190) NULL,
    phone VARCHAR(80) NULL,
    whatsapp VARCHAR(80) NULL,
    preferred_language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    preferred_contact ENUM('email','whatsapp','phone') NOT NULL DEFAULT 'email',
    notes TEXT NULL,
    owner_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_clients_owner (owner_user_id),
    INDEX idx_clients_company (company_name),
    INDEX idx_clients_country (country),
    INDEX idx_clients_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_categories_active (is_active),
    INDEX idx_service_categories_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_catalog (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(150) NULL,
    category_id INT UNSIGNED NULL,
    item_type ENUM('service','product') NOT NULL DEFAULT 'service',
    name_es VARCHAR(190) NOT NULL,
    name_pt VARCHAR(190) NULL,
    name_en VARCHAR(190) NULL,
    description_es TEXT NULL,
    description_pt TEXT NULL,
    description_en TEXT NULL,
    default_contract_type ENUM('monthly','annual','one_time','custom') NOT NULL DEFAULT 'one_time',
    custom_period_months SMALLINT UNSIGNED NULL,
    price_clp DECIMAL(15,2) NULL,
    price_usd DECIMAL(15,2) NULL,
    price_brl DECIMAL(15,2) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_catalog_category FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE SET NULL,
    INDEX idx_catalog_category (category),
    INDEX idx_catalog_category_id (category_id),
    INDEX idx_catalog_active (is_active),
    INDEX idx_catalog_name_es (name_es)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    catalog_service_id INT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(150) NULL,
    description TEXT NULL,
    contract_type ENUM('monthly','annual','one_time','custom') NOT NULL DEFAULT 'one_time',
    custom_period_months SMALLINT UNSIGNED NULL,
    start_date DATE NULL,
    expiration_date DATE NULL,
    amount DECIMAL(15,2) NULL,
    currency ENUM('CLP','USD','BRL') NOT NULL DEFAULT 'USD',
    payment_method VARCHAR(100) NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    paid_at DATE NULL,
    auto_renew TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','pending','completed','cancelled') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    alert_sent_for_date DATE NULL,
    deal_id INT UNSIGNED NULL,
    next_billing_date DATE NULL,
    last_billing_date DATE NULL,
    billing_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    alert_days VARCHAR(100) NOT NULL DEFAULT '30,15,7,3,1,0',
    billing_notify_email TINYINT(1) NOT NULL DEFAULT 1,
    billing_notify_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    billing_notify_inapp TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_services_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_services_catalog FOREIGN KEY (catalog_service_id) REFERENCES service_catalog(id) ON DELETE SET NULL,
    INDEX idx_services_client (client_id),
    INDEX idx_services_catalog (catalog_service_id),
    INDEX idx_services_expiration (expiration_date),
    INDEX idx_services_next_billing (next_billing_date,billing_status),
    INDEX idx_services_deal (deal_id),
    INDEX idx_services_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_renewals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    previous_expiration_date DATE NULL,
    new_expiration_date DATE NOT NULL,
    amount DECIMAL(15,2) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_renewals_service FOREIGN KEY (service_id) REFERENCES client_services(id) ON DELETE CASCADE,
    CONSTRAINT fk_renewals_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_renewals_service (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS payment_methods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    country VARCHAR(100) NULL,
    currency ENUM('CLP','USD','BRL') NOT NULL DEFAULT 'USD',
    language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    details LONGTEXT NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payment_methods_country (country),
    INDEX idx_payment_methods_currency (currency),
    INDEX idx_payment_methods_language (language),
    INDEX idx_payment_methods_active (is_active),
    INDEX idx_payment_methods_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_number VARCHAR(50) NOT NULL UNIQUE,
    client_id INT UNSIGNED NOT NULL,
    recipient_name VARCHAR(190) NULL,
    recipient_email VARCHAR(190) NULL,
    language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    currency ENUM('CLP','USD','BRL') NOT NULL DEFAULT 'CLP',
    issue_date DATE NOT NULL,
    valid_until DATE NULL,
    status ENUM('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    terms TEXT NULL,
    payment_method_id INT UNSIGNED NULL,
    payment_method_title VARCHAR(190) NULL,
    payment_method_details TEXT NULL,
    show_payment_method TINYINT(1) NOT NULL DEFAULT 1,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(6,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,
    sent_at DATETIME NULL,
    accepted_at DATETIME NULL,
    owner_user_id INT UNSIGNED NULL,
    deal_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quotes_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_quotes_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
    CONSTRAINT fk_quotes_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_quotes_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_quotes_owner (owner_user_id),
    INDEX idx_quotes_deal (deal_id),
    INDEX idx_quotes_client (client_id),
    INDEX idx_quotes_payment_method (payment_method_id),
    INDEX idx_quotes_number (quotation_number),
    INDEX idx_quotes_status (status),
    INDEX idx_quotes_issue_date (issue_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotation_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT UNSIGNED NOT NULL,
    catalog_service_id INT UNSIGNED NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_quote_items_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
    CONSTRAINT fk_quote_items_catalog FOREIGN KEY (catalog_service_id) REFERENCES service_catalog(id) ON DELETE SET NULL,
    INDEX idx_quote_items_quote (quotation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS contract_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contract_templates_active (is_active),
    INDEX idx_contract_templates_language (language)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(50) NOT NULL UNIQUE,
    client_id INT UNSIGNED NOT NULL,
    template_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    recipient_name VARCHAR(190) NULL,
    recipient_email VARCHAR(190) NULL,
    content LONGTEXT NOT NULL,
    status ENUM('draft','sent','approved','rejected','signed','cancelled') NOT NULL DEFAULT 'draft',
    public_token CHAR(64) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    approved_at DATETIME NULL,
    rejected_at DATETIME NULL,
    signed_at DATETIME NULL,
    approver_name VARCHAR(190) NULL,
    approver_comment TEXT NULL,
    sent_at DATETIME NULL,
    owner_user_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contracts_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_contracts_template FOREIGN KEY (template_id) REFERENCES contract_templates(id) ON DELETE SET NULL,
    CONSTRAINT fk_contracts_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_contracts_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_contracts_owner (owner_user_id),
    INDEX idx_contracts_client (client_id),
    INDEX idx_contracts_status (status),
    INDEX idx_contracts_issue_date (issue_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NULL,
    quotation_id INT UNSIGNED NULL,
    recipient VARCHAR(190) NOT NULL,
    type VARCHAR(80) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('sent','failed') NOT NULL,
    error_message TEXT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_service FOREIGN KEY (service_id) REFERENCES client_services(id) ON DELETE SET NULL,
    CONSTRAINT fk_notifications_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL,
    INDEX idx_notifications_service (service_id),
    INDEX idx_notifications_quote (quotation_id),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(190) NOT NULL UNIQUE,
    setting_value LONGTEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    client_id INT UNSIGNED NULL,
    service_id INT UNSIGNED NULL,
    quotation_id INT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_logs_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_logs_service FOREIGN KEY (service_id) REFERENCES client_services(id) ON DELETE SET NULL,
    CONSTRAINT fk_logs_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL,
    INDEX idx_logs_created (created_at),
    INDEX idx_logs_client (client_id),
    INDEX idx_logs_quote (quotation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NULL,
    quotation_id INT UNSIGNED NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    payment_method VARCHAR(150) NULL,
    reference VARCHAR(190) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_service FOREIGN KEY (service_id) REFERENCES client_services(id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payments_date (payment_date),
    INDEX idx_payments_currency (currency),
    INDEX idx_payments_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(60) NOT NULL DEFAULT 'info',
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications_user_read (user_id, is_read),
    INDEX idx_user_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(40) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NULL,
    subject VARCHAR(190) NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'general',
    department VARCHAR(80) NOT NULL DEFAULT 'Soporte',
    priority VARCHAR(20) NOT NULL DEFAULT 'normal',
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    stage VARCHAR(30) NOT NULL DEFAULT 'new',
    sla_due_at DATETIME NULL,
    next_follow_up_at DATETIME NULL,
    resolution TEXT NULL,
    last_reply_by INT UNSIGNED NULL,
    closed_at DATETIME NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_support_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_support_tickets_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_support_tickets_lastreply FOREIGN KEY (last_reply_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_support_tickets_user (user_id), INDEX idx_support_tickets_assigned (assigned_to), INDEX idx_support_tickets_status (status), INDEX idx_support_tickets_stage (stage), INDEX idx_support_tickets_sla (sla_due_at), INDEX idx_support_tickets_followup (next_follow_up_at), INDEX idx_support_tickets_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    message TEXT NOT NULL,
    is_internal TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_support_messages_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_support_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_support_messages_ticket (ticket_id), INDEX idx_support_messages_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    event_type VARCHAR(40) NOT NULL,
    title VARCHAR(190) NOT NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_support_events_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_support_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_support_events_ticket (ticket_id), INDEX idx_support_events_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NULL,
    first_name VARCHAR(120) NOT NULL,
    last_name VARCHAR(120) NULL,
    job_title VARCHAR(150) NULL,
    department VARCHAR(150) NULL,
    email VARCHAR(190) NULL,
    secondary_email VARCHAR(190) NULL,
    phone VARCHAR(80) NULL,
    whatsapp VARCHAR(80) NULL,
    language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    status ENUM('active','inactive','lead') NOT NULL DEFAULT 'active',
    source VARCHAR(120) NULL,
    notes TEXT NULL,
    owner_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contacts_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_contacts_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_contacts_client (client_id),
    INDEX idx_contacts_owner (owner_user_id),
    INDEX idx_contacts_email (email),
    INDEX idx_contacts_status (status),
    INDEX idx_contacts_name (first_name,last_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pipeline_stages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_es VARCHAR(120) NOT NULL,
    name_pt VARCHAR(120) NULL,
    name_en VARCHAR(120) NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#0074c7',
    probability TINYINT UNSIGNED NOT NULL DEFAULT 10,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_closed_won TINYINT(1) NOT NULL DEFAULT 0,
    is_closed_lost TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pipeline_stages_sort (sort_order),
    INDEX idx_pipeline_stages_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    client_id INT UNSIGNED NULL,
    contact_id INT UNSIGNED NULL,
    owner_user_id INT UNSIGNED NULL,
    stage_id INT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    currency ENUM('CLP','USD','BRL') NOT NULL DEFAULT 'USD',
    probability TINYINT UNSIGNED NOT NULL DEFAULT 10,
    expected_close_date DATE NULL,
    source VARCHAR(120) NULL,
    status ENUM('open','won','lost','cancelled') NOT NULL DEFAULT 'open',
    next_action VARCHAR(190) NULL,
    next_action_at DATETIME NULL,
    notes TEXT NULL,
    closed_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_deals_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_deals_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    CONSTRAINT fk_deals_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_deals_stage FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id) ON DELETE RESTRICT,
    CONSTRAINT fk_deals_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_deals_stage (stage_id),
    INDEX idx_deals_owner (owner_user_id),
    INDEX idx_deals_client (client_id),
    INDEX idx_deals_status (status),
    INDEX idx_deals_close (expected_close_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NULL,
    contact_id INT UNSIGNED NULL,
    deal_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    assigned_to INT UNSIGNED NULL,
    activity_type ENUM('task','event','call','email','meeting','note') NOT NULL DEFAULT 'task',
    subject VARCHAR(190) NOT NULL,
    details TEXT NULL,
    due_at DATETIME NULL,
    completed_at DATETIME NULL,
    status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    meeting_url VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_activities_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_activities_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_deal FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    CONSTRAINT fk_activities_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_assignee FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activities_client (client_id),
    INDEX idx_activities_contact (contact_id),
    INDEX idx_activities_deal (deal_id),
    INDEX idx_activities_assignee (assigned_to),
    INDEX idx_activities_due (due_at),
    INDEX idx_activities_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotation_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quote_attachments_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
    CONSTRAINT fk_quote_attachments_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_quote_attachments_quote (quotation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mailing_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    html_content LONGTEXT NOT NULL,
    status ENUM('draft','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
    audience_type ENUM('clients','contacts','both') NOT NULL DEFAULT 'both',
    filter_country VARCHAR(100) NULL,
    filter_status VARCHAR(30) NULL,
    sent_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    sent_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mailing_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mailing_status (status),
    INDEX idx_mailing_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mailing_recipients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NULL,
    contact_id INT UNSIGNED NULL,
    email VARCHAR(190) NOT NULL,
    recipient_name VARCHAR(190) NULL,
    status ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
    error_message TEXT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mailing_recipients_campaign FOREIGN KEY (campaign_id) REFERENCES mailing_campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_mailing_recipients_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_mailing_recipients_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    UNIQUE KEY uq_campaign_email (campaign_id,email),
    INDEX idx_mailing_recipient_status (campaign_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    channel VARCHAR(80) NOT NULL DEFAULT 'website',
    objective VARCHAR(255) NULL,
    budget DECIMAL(15,2) NULL,
    currency ENUM('CLP','USD','BRL') NOT NULL DEFAULT 'USD',
    start_date DATE NULL,
    end_date DATE NULL,
    status ENUM('draft','active','paused','completed') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lead_campaign_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lead_campaign_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_forms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    language ENUM('es','pt','en') NOT NULL DEFAULT 'es',
    fields_json LONGTEXT NOT NULL,
    style_json LONGTEXT NULL,
    success_message TEXT NULL,
    public_token CHAR(64) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lead_forms_campaign FOREIGN KEY (campaign_id) REFERENCES lead_campaigns(id) ON DELETE SET NULL,
    CONSTRAINT fk_lead_forms_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lead_forms_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id INT UNSIGNED NOT NULL,
    data_json LONGTEXT NOT NULL,
    name VARCHAR(190) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(80) NULL,
    country VARCHAR(100) NULL,
    converted_contact_id INT UNSIGNED NULL,
    converted_deal_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lead_submissions_form FOREIGN KEY (form_id) REFERENCES lead_forms(id) ON DELETE CASCADE,
    CONSTRAINT fk_lead_submissions_contact FOREIGN KEY (converted_contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    CONSTRAINT fk_lead_submissions_deal FOREIGN KEY (converted_deal_id) REFERENCES deals(id) ON DELETE SET NULL,
    INDEX idx_lead_submissions_form (form_id),
    INDEX idx_lead_submissions_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NULL,
    contact_id INT UNSIGNED NULL,
    deal_id INT UNSIGNED NULL,
    assigned_to INT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NULL,
    timezone VARCHAR(80) NOT NULL DEFAULT 'America/Sao_Paulo',
    meeting_url VARCHAR(500) NULL,
    external_booking_url VARCHAR(500) NULL,
    status ENUM('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointments_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointments_deal FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointments_assignee FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointments_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_appointments_start (start_at),
    INDEX idx_appointments_assignee (assigned_to),
    INDEX idx_appointments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dashboard_preferences (
    user_id INT UNSIGNED PRIMARY KEY,
    layout_json LONGTEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dashboard_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_id INT UNSIGNED NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    user_agent VARCHAR(500) NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_identifier_time (identifier_hash, attempted_at),
    INDEX idx_login_ip_time (ip_address, attempted_at),
    INDEX idx_login_user (user_id),
    CONSTRAINT fk_login_attempt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_hash CHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    is_revoked TINYINT(1) NOT NULL DEFAULT 0,
    revoked_at DATETIME NULL,
    INDEX idx_sessions_user_active (user_id, is_revoked),
    INDEX idx_sessions_expiry (expires_at),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    event VARCHAR(120) NOT NULL,
    severity ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
    target_type VARCHAR(80) NULL,
    target_id BIGINT UNSIGNED NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_event (event),
    INDEX idx_audit_severity (severity),
    INDEX idx_audit_user (user_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS billing_charges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    due_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    paid_at DATE NULL,
    payment_id INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_billing_charge_service FOREIGN KEY (service_id) REFERENCES client_services(id) ON DELETE CASCADE,
    CONSTRAINT fk_billing_charge_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_billing_charge_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    UNIQUE KEY uq_billing_service_due (service_id,due_date),
    INDEX idx_billing_due_status (due_date,status),
    INDEX idx_billing_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SÓCRATES ONE CRM 1.9.0 · PROJECT MANAGER
-- ==========================================================
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NULL,
    deal_id INT UNSIGNED NULL,
    owner_user_id INT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    code VARCHAR(60) NULL,
    description TEXT NULL,
    status ENUM('planning','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'planning',
    health ENUM('on_track','at_risk','off_track') NOT NULL DEFAULT 'on_track',
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    start_date DATE NULL,
    due_date DATE NULL,
    completed_at DATETIME NULL,
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    budget DECIMAL(15,2) NOT NULL DEFAULT 0,
    currency ENUM('CLP','USD','BRL') NOT NULL DEFAULT 'USD',
    billing_model ENUM('fixed','hourly','retainer','internal') NOT NULL DEFAULT 'fixed',
    hourly_rate DECIMAL(15,2) NOT NULL DEFAULT 0,
    color VARCHAR(20) NOT NULL DEFAULT '#0074c7',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_deal FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_projects_code (code),
    INDEX idx_projects_client (client_id), INDEX idx_projects_owner (owner_user_id),
    INDEX idx_projects_status (status), INDEX idx_projects_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_members (
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role_name VARCHAR(120) NULL,
    allocation_percent TINYINT UNSIGNED NOT NULL DEFAULT 100,
    hourly_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id,user_id),
    CONSTRAINT fk_project_members_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_members_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_milestones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    due_date DATE NULL,
    status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    sort_order INT NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_milestones_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_milestones_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_milestones_project (project_id), INDEX idx_project_milestones_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    milestone_id INT UNSIGNED NULL,
    parent_task_id BIGINT UNSIGNED NULL,
    assigned_to INT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    status ENUM('backlog','todo','in_progress','review','blocked','done') NOT NULL DEFAULT 'todo',
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    start_date DATE NULL,
    due_date DATE NULL,
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    planned_hours DECIMAL(10,2) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    color VARCHAR(20) NULL,
    created_by INT UNSIGNED NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_tasks_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_tasks_milestone FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE SET NULL,
    CONSTRAINT fk_project_tasks_parent FOREIGN KEY (parent_task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_tasks_assignee FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_project_tasks_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_tasks_project_status (project_id,status), INDEX idx_project_tasks_assignee (assigned_to),
    INDEX idx_project_tasks_due (due_date), INDEX idx_project_tasks_milestone (milestone_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_task_dependencies (
    task_id BIGINT UNSIGNED NOT NULL,
    depends_on_task_id BIGINT UNSIGNED NOT NULL,
    dependency_type ENUM('finish_start','start_start','finish_finish','start_finish') NOT NULL DEFAULT 'finish_start',
    lag_days INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id,depends_on_task_id),
    CONSTRAINT fk_ptd_task FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_ptd_dependency FOREIGN KEY (depends_on_task_id) REFERENCES project_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_task_checklist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    item_text VARCHAR(255) NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ptc_task FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    INDEX idx_ptc_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    CONSTRAINT fk_project_comments_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_comments_task FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_comments_project (project_id), INDEX idx_project_comments_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_time_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    hours DECIMAL(8,2) NOT NULL DEFAULT 0,
    billable TINYINT(1) NOT NULL DEFAULT 1,
    hourly_rate DECIMAL(15,2) NOT NULL DEFAULT 0,
    description VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_time_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_time_task FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE SET NULL,
    CONSTRAINT fk_project_time_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_time_project_date (project_id,work_date), INDEX idx_project_time_user_date (user_id,work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_files_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_files_task FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE SET NULL,
    CONSTRAINT fk_project_files_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_files_project (project_id), INDEX idx_project_files_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_risks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    category VARCHAR(80) NULL,
    probability TINYINT UNSIGNED NOT NULL DEFAULT 20,
    impact TINYINT UNSIGNED NOT NULL DEFAULT 20,
    status ENUM('open','mitigating','accepted','closed') NOT NULL DEFAULT 'open',
    owner_user_id INT UNSIGNED NULL,
    mitigation TEXT NULL,
    due_date DATE NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_risks_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_risks_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_project_risks_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_risks_project_status (project_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    expense_date DATE NOT NULL,
    category VARCHAR(100) NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    currency ENUM('CLP','USD','BRL') NOT NULL DEFAULT 'USD',
    billable TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_expenses_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_expenses_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_expenses_project_date (project_id,expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SÓCRATES CRM v2.1.0 Document Manager

CREATE TABLE IF NOT EXISTS document_folders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,parent_id INT UNSIGNED NULL,name VARCHAR(190) NOT NULL,description VARCHAR(255) NULL,created_by INT UNSIGNED NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_document_folders_parent(parent_id),CONSTRAINT fk_document_folders_parent FOREIGN KEY(parent_id) REFERENCES document_folders(id) ON DELETE CASCADE,CONSTRAINT fk_document_folders_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,folder_id INT UNSIGNED NULL,title VARCHAR(255) NOT NULL,description TEXT NULL,category VARCHAR(120) NULL,status VARCHAR(30) NOT NULL DEFAULT 'draft',visibility VARCHAR(20) NOT NULL DEFAULT 'private',owner_user_id INT UNSIGNED NULL,current_version_id BIGINT UNSIGNED NULL,expires_at DATE NULL,is_favorite TINYINT(1) NOT NULL DEFAULT 0,is_deleted TINYINT(1) NOT NULL DEFAULT 0,deleted_at DATETIME NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_documents_folder(folder_id),INDEX idx_documents_owner(owner_user_id),INDEX idx_documents_status(status),INDEX idx_documents_expires(expires_at),INDEX idx_documents_deleted(is_deleted),CONSTRAINT fk_documents_folder FOREIGN KEY(folder_id) REFERENCES document_folders(id) ON DELETE SET NULL,CONSTRAINT fk_documents_owner FOREIGN KEY(owner_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_versions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,document_id BIGINT UNSIGNED NOT NULL,version_no INT UNSIGNED NOT NULL,original_name VARCHAR(255) NOT NULL,stored_path VARCHAR(500) NOT NULL,mime_type VARCHAR(190) NOT NULL,file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,sha256 CHAR(64) NULL,notes TEXT NULL,created_by INT UNSIGNED NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_document_version(document_id,version_no),INDEX idx_document_versions_doc(document_id),CONSTRAINT fk_document_versions_doc FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE,CONSTRAINT fk_document_versions_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_links (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,document_id BIGINT UNSIGNED NOT NULL,entity_type VARCHAR(30) NOT NULL,entity_id BIGINT UNSIGNED NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_document_link(document_id,entity_type,entity_id),INDEX idx_document_links_entity(entity_type,entity_id),CONSTRAINT fk_document_links_doc FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_permissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,document_id BIGINT UNSIGNED NOT NULL,subject_type VARCHAR(20) NOT NULL,subject_value VARCHAR(190) NOT NULL,can_edit TINYINT(1) NOT NULL DEFAULT 0,can_share TINYINT(1) NOT NULL DEFAULT 0,created_by INT UNSIGNED NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_document_permission(document_id,subject_type,subject_value),CONSTRAINT fk_document_permissions_doc FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE,CONSTRAINT fk_document_permissions_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_comments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,document_id BIGINT UNSIGNED NOT NULL,user_id INT UNSIGNED NULL,comment TEXT NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_document_comments_doc FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE,CONSTRAINT fk_document_comments_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_approval_routes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(190) NOT NULL,description TEXT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_by INT UNSIGNED NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_document_routes_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_approval_route_steps (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,route_id INT UNSIGNED NOT NULL,step_order INT UNSIGNED NOT NULL,approver_user_id INT UNSIGNED NOT NULL,label VARCHAR(190) NULL,UNIQUE KEY uq_document_route_step(route_id,step_order),CONSTRAINT fk_document_route_steps_route FOREIGN KEY(route_id) REFERENCES document_approval_routes(id) ON DELETE CASCADE,CONSTRAINT fk_document_route_steps_user FOREIGN KEY(approver_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_approvals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,document_id BIGINT UNSIGNED NOT NULL,route_id INT UNSIGNED NULL,status VARCHAR(20) NOT NULL DEFAULT 'pending',started_by INT UNSIGNED NULL,started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,completed_at DATETIME NULL,INDEX idx_document_approvals_doc(document_id),CONSTRAINT fk_document_approvals_doc FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE,CONSTRAINT fk_document_approvals_route FOREIGN KEY(route_id) REFERENCES document_approval_routes(id) ON DELETE SET NULL,CONSTRAINT fk_document_approvals_user FOREIGN KEY(started_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_approval_steps (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,approval_id BIGINT UNSIGNED NOT NULL,step_order INT UNSIGNED NOT NULL,approver_user_id INT UNSIGNED NOT NULL,label VARCHAR(190) NULL,status VARCHAR(20) NOT NULL DEFAULT 'pending',decision_notes TEXT NULL,decided_at DATETIME NULL,UNIQUE KEY uq_document_approval_step(approval_id,step_order),INDEX idx_document_approval_user(approver_user_id,status),CONSTRAINT fk_document_approval_steps_approval FOREIGN KEY(approval_id) REFERENCES document_approvals(id) ON DELETE CASCADE,CONSTRAINT fk_document_approval_steps_user FOREIGN KEY(approver_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_shares (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,document_id BIGINT UNSIGNED NOT NULL,version_id BIGINT UNSIGNED NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,password_hash VARCHAR(255) NULL,expires_at DATETIME NULL,download_limit INT UNSIGNED NULL,download_count INT UNSIGNED NOT NULL DEFAULT 0,allow_download TINYINT(1) NOT NULL DEFAULT 1,is_active TINYINT(1) NOT NULL DEFAULT 1,created_by INT UNSIGNED NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,last_accessed_at DATETIME NULL,CONSTRAINT fk_document_shares_doc FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE,CONSTRAINT fk_document_shares_version FOREIGN KEY(version_id) REFERENCES document_versions(id) ON DELETE CASCADE,CONSTRAINT fk_document_shares_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS document_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,document_id BIGINT UNSIGNED NULL,user_id INT UNSIGNED NULL,action VARCHAR(80) NOT NULL,details TEXT NULL,ip_address VARCHAR(64) NULL,user_agent VARCHAR(255) NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_document_audit_doc(document_id),INDEX idx_document_audit_created(created_at),CONSTRAINT fk_document_audit_doc FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE SET NULL,CONSTRAINT fk_document_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
