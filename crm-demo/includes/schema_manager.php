<?php
declare(strict_types=1);

function crm_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function crm_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

function crm_column_data_type(PDO $pdo, string $table, string $column): ?string
{
    $stmt = $pdo->prepare('SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $stmt->execute([$table, $column]);
    $value = $stmt->fetchColumn();
    return $value === false ? null : strtolower((string)$value);
}

function crm_index_exists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $stmt->execute([$table, $index]);
    return (bool)$stmt->fetchColumn();
}

function crm_constraint_exists(PDO $pdo, string $table, string $constraint): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?');
    $stmt->execute([$table, $constraint]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Split a SQL file into executable statements without breaking quoted strings.
 */
function crm_split_sql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $quote = null;
    $escaped = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($quote === null && $char === '-' && $next === '-') {
            while ($i < $length && $sql[$i] !== "\n") $i++;
            $buffer .= "\n";
            continue;
        }

        if ($quote === null && $char === '#') {
            while ($i < $length && $sql[$i] !== "\n") $i++;
            $buffer .= "\n";
            continue;
        }

        if ($quote === null && $char === '/' && $next === '*') {
            $i += 2;
            while ($i + 1 < $length && !($sql[$i] === '*' && $sql[$i + 1] === '/')) $i++;
            $i++;
            continue;
        }

        if ($quote !== null) {
            $buffer .= $char;
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            if ($char === $quote) $quote = null;
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }

        if ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '') $statements[] = $statement;
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') $statements[] = $statement;
    return $statements;
}


function crm_service_catalog_seed_version(): string
{
    return '1.2.1-202-services';
}

function crm_read_setting(PDO $pdo, string $key): ?string
{
    if (!crm_table_exists($pdo, 'settings')) return null;
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? null : (string)$value;
}

/**
 * Preload the multilingual service catalog from the packaged data file.
 * Existing services are preserved; only missing translations are completed.
 */
function crm_seed_service_catalog(PDO $pdo): array
{
    if (!crm_table_exists($pdo, 'service_catalog') || !crm_table_exists($pdo, 'settings')) {
        throw new RuntimeException('No están disponibles las tablas necesarias para precargar el catálogo.');
    }

    $seedPath = dirname(__DIR__) . '/data/service_catalog_seed_1.2.1.json';
    $raw = @file_get_contents($seedPath);
    if ($raw === false) {
        throw new RuntimeException('No se encontró data/service_catalog_seed_1.2.1.json.');
    }

    try {
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException('El archivo de servicios precargados no es válido: ' . $exception->getMessage(), 0, $exception);
    }

    $rows = $payload['services'] ?? null;
    if (!is_array($rows) || $rows === []) {
        throw new RuntimeException('El archivo de servicios no contiene registros.');
    }

    $select = $pdo->prepare('SELECT id, name_pt, name_en FROM service_catalog WHERE category = ? AND name_es = ? LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO service_catalog
        (category,name_es,name_pt,name_en,description_es,description_pt,description_en,default_contract_type,custom_period_months,price_clp,price_usd,price_brl,is_active)
        VALUES (?,?,?,?,NULL,NULL,NULL,\'one_time\',NULL,NULL,NULL,NULL,1)'
    );
    $update = $pdo->prepare('UPDATE service_catalog SET name_pt = ?, name_en = ? WHERE id = ?');
    $setting = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );

    $inserted = 0;
    $updated = 0;
    $preserved = 0;
    $startedTransaction = !$pdo->inTransaction();

    try {
        if ($startedTransaction) $pdo->beginTransaction();

        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $category = trim((string)($row['category'] ?? ''));
            $nameEs = trim((string)($row['name_es'] ?? ''));
            $namePt = trim((string)($row['name_pt'] ?? ''));
            $nameEn = trim((string)($row['name_en'] ?? ''));

            if ($category === '' || $nameEs === '') continue;

            $select->execute([$category, $nameEs]);
            $existing = $select->fetch();

            if (!$existing) {
                $insert->execute([$category, $nameEs, $namePt !== '' ? $namePt : null, $nameEn !== '' ? $nameEn : null]);
                $inserted++;
                continue;
            }

            $finalPt = trim((string)($existing['name_pt'] ?? '')) !== '' ? (string)$existing['name_pt'] : $namePt;
            $finalEn = trim((string)($existing['name_en'] ?? '')) !== '' ? (string)$existing['name_en'] : $nameEn;

            if ($finalPt !== (string)($existing['name_pt'] ?? '') || $finalEn !== (string)($existing['name_en'] ?? '')) {
                $update->execute([$finalPt !== '' ? $finalPt : null, $finalEn !== '' ? $finalEn : null, (int)$existing['id']]);
                $updated++;
            } else {
                $preserved++;
            }
        }

        $setting->execute(['service_catalog_seed_version', crm_service_catalog_seed_version()]);
        $setting->execute(['service_catalog_seed_count', (string)count($rows)]);

        if ($startedTransaction) $pdo->commit();
    } catch (Throwable $exception) {
        if ($startedTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }

    return [
        'total' => count($rows),
        'inserted' => $inserted,
        'updated' => $updated,
        'preserved' => $preserved,
    ];
}


function crm_contract_template_library(): array
{
    $sharedEs = "NOVENO: PROTECCIÓN DE DATOS. Las partes tratarán los datos personales exclusivamente para ejecutar la relación contractual y aplicarán medidas razonables de seguridad.\n\nDÉCIMO: FUERZA MAYOR. Ninguna parte responderá por retrasos causados por hechos imprevisibles o ajenos a su control razonable.\n\nDÉCIMO PRIMERO: LIMITACIÓN DE RESPONSABILIDAD. La responsabilidad total del PRESTADOR no excederá los honorarios efectivamente pagados por el servicio que originó el reclamo, salvo dolo o prohibición legal.\n\nDÉCIMO SEGUNDO: LEY APLICABLE Y SOLUCIÓN DE CONTROVERSIAS. Las partes procurarán una solución directa y de buena fe antes de acudir a los tribunales competentes indicados en la versión final.\n\nAVISO: esta plantilla es una base operativa y debe ser revisada por asesoría jurídica del país correspondiente antes de la firma.";
    $sharedPt = "NONA: PROTEÇÃO DE DADOS. As partes utilizarão dados pessoais somente para executar a relação contratual e adotarão medidas razoáveis de segurança.\n\nDÉCIMA: FORÇA MAIOR. Nenhuma parte responderá por atrasos decorrentes de eventos imprevisíveis ou fora de seu controle razoável.\n\nDÉCIMA PRIMEIRA: LIMITAÇÃO DE RESPONSABILIDADE. A responsabilidade total do PRESTADOR não excederá os valores efetivamente pagos pelo serviço relacionado à reclamação, salvo dolo ou proibição legal.\n\nDÉCIMA SEGUNDA: LEI APLICÁVEL E CONTROVÉRSIAS. As partes buscarão solução amigável antes de recorrer ao foro competente definido na versão final.\n\nAVISO: este modelo é uma base operacional e deve ser revisado por assessoria jurídica do país aplicável antes da assinatura.";
    $sharedEn = "9. DATA PROTECTION. The parties will process personal data only to perform the contractual relationship and will apply reasonable security measures.\n\n10. FORCE MAJEURE. Neither party is liable for delay caused by unforeseeable events beyond its reasonable control.\n\n11. LIMITATION OF LIABILITY. The PROVIDER'S total liability will not exceed the fees actually paid for the service giving rise to the claim, except where prohibited by law or in cases of wilful misconduct.\n\n12. GOVERNING LAW AND DISPUTES. The parties will first seek a good-faith negotiated solution before using the competent courts specified in the final version.\n\nNOTICE: this template is an operational starting point and should be reviewed by qualified legal counsel in the relevant jurisdiction before signature.";
    return [
        ['name'=>'Contrato integral de servicios digitales','language'=>'es','title'=>'CONTRATO INTEGRAL DE SERVICIOS DIGITALES','content'=>"Entre {{EMPRESA_PRESTADORA}}, en adelante EL PRESTADOR, y {{CLIENTE}}, identificación {{ID_CLIENTE}}, en adelante EL CLIENTE, se celebra el presente contrato.\n\n1. OBJETO. EL PRESTADOR ejecutará {{SERVICIOS}} conforme a la cotización aprobada, que forma parte integral de este contrato.\n\n2. ALCANCE Y ENTREGABLES. Solo se consideran incluidos los entregables expresamente descritos. Solicitudes adicionales requieren aprobación y cotización separada.\n\n3. PLAZO. El servicio comenzará el {{FECHA_INICIO}}. Los plazos dependen de la entrega oportuna de contenidos, accesos y aprobaciones.\n\n4. HONORARIOS. El valor es {{VALOR}}. La mora podrá suspender entregas, soporte o publicación.\n\n5. REVISIONES Y ACEPTACIÓN. El cliente revisará cada entrega dentro de cinco días hábiles. Sin observaciones específicas, podrá considerarse aceptada.\n\n6. PROPIEDAD INTELECTUAL. Tras el pago total se transfieren los derechos de uso de los entregables finales, excluyendo componentes previos, software de terceros, licencias, tipografías y material de stock.\n\n7. CONFIDENCIALIDAD. Ambas partes protegerán información técnica, comercial y credenciales.\n\n8. TERMINACIÓN. La terminación no elimina la obligación de pagar el trabajo ejecutado, licencias adquiridas y compromisos no cancelables.\n\n".$sharedEs],
        ['name'=>'Contrato de hosting, dominio y correo','language'=>'es','title'=>'CONTRATO DE HOSTING, DOMINIO Y CORREO CORPORATIVO','content'=>"1. SERVICIOS. EL PRESTADOR administrará {{SERVICIOS}} para {{CLIENTE}}.\n\n2. VIGENCIA Y RENOVACIÓN. La vigencia comienza el {{FECHA_INICIO}}. Dominios, certificados, licencias y hosting dependen de ciclos de terceros y deben pagarse antes de su vencimiento.\n\n3. DISPONIBILIDAD. No se garantiza disponibilidad absoluta; mantenimientos, fallas de terceros, ataques y fuerza mayor pueden afectar el servicio.\n\n4. RESPALDOS. Los respaldos son una medida de contingencia y no sustituyen la conservación de copias propias del CLIENTE.\n\n5. USO ACEPTABLE. Se prohíbe spam, malware, phishing, contenido ilícito o actividades que comprometan la infraestructura.\n\n6. SUSPENSIÓN. La falta de pago o el uso indebido permite suspender el servicio. La recuperación posterior puede generar costos.\n\n7. VALOR. {{VALOR}}.\n\n8. CREDENCIALES. EL CLIENTE debe proteger accesos y avisar inmediatamente cualquier incidente.\n\n".$sharedEs],
        ['name'=>'Contrato de marketing y redes sociales','language'=>'es','title'=>'CONTRATO DE MARKETING DIGITAL Y REDES SOCIALES','content'=>"1. OBJETO. Gestión de {{SERVICIOS}} para {{CLIENTE}}.\n\n2. PLAN DE CONTENIDOS. La frecuencia, canales y piezas serán los definidos en la propuesta aprobada.\n\n3. APROBACIONES. EL CLIENTE es responsable de verificar datos, precios, afirmaciones y derechos de uso antes de publicar.\n\n4. RESULTADOS. No se garantizan ventas, alcance, posicionamiento o conversiones específicos, pues dependen del mercado, plataformas, presupuesto y respuesta del público.\n\n5. PUBLICIDAD PAGADA. El presupuesto de medios no está incluido salvo indicación expresa y debe ser financiado por EL CLIENTE.\n\n6. ACCESOS. EL CLIENTE facilitará permisos adecuados y mantendrá la titularidad de sus cuentas.\n\n7. VALOR Y VIGENCIA. {{VALOR}}, desde {{FECHA_INICIO}}.\n\n8. PORTAFOLIO. EL PRESTADOR podrá mostrar piezas públicas como referencia, salvo acuerdo de confidencialidad.\n\n".$sharedEs],
        ['name'=>'Contrato completo de serviços digitais','language'=>'pt','title'=>'CONTRATO COMPLETO DE SERVIÇOS DIGITAIS','content'=>"Entre {{EMPRESA_PRESTADORA}}, doravante PRESTADOR, e {{CLIENTE}}, identificação {{ID_CLIENTE}}, doravante CLIENTE.\n\n1. OBJETO. Execução de {{SERVICIOS}} conforme a proposta aprovada.\n\n2. ESCOPO. Somente entregas expressamente descritas estão incluídas. Demandas adicionais serão orçadas separadamente.\n\n3. PRAZO. Início em {{FECHA_INICIO}}, condicionado ao envio de conteúdos, acessos e aprovações.\n\n4. REMUNERAÇÃO. {{VALOR}}. O atraso poderá suspender entregas e suporte.\n\n5. REVISÕES E ACEITE. O CLIENTE analisará cada entrega em até cinco dias úteis.\n\n6. PROPRIEDADE INTELECTUAL. Após pagamento integral, o CLIENTE recebe direitos de uso dos materiais finais, exceto componentes prévios e licenças de terceiros.\n\n7. CONFIDENCIALIDADE. As partes protegerão informações comerciais, técnicas e credenciais.\n\n8. RESCISÃO. Permanecem devidos os serviços executados e compromissos não canceláveis.\n\n".$sharedPt],
        ['name'=>'Contrato de hospedagem, domínio e e-mail','language'=>'pt','title'=>'CONTRATO DE HOSPEDAGEM, DOMÍNIO E E-MAIL CORPORATIVO','content'=>"1. SERVIÇOS. Administração de {{SERVICIOS}} para {{CLIENTE}}.\n\n2. VIGÊNCIA E RENOVAÇÃO. Início em {{FECHA_INICIO}}. Domínios, certificados, licenças e hospedagem dependem de ciclos de terceiros e devem ser pagos antes do vencimento.\n\n3. DISPONIBILIDADE. Não há garantia de disponibilidade absoluta.\n\n4. BACKUPS. Cópias de segurança são contingenciais e não substituem backups próprios do CLIENTE.\n\n5. USO ACEITÁVEL. É proibido spam, malware, phishing e conteúdo ilegal.\n\n6. SUSPENSÃO. Inadimplência ou uso indevido poderá causar suspensão.\n\n7. VALOR. {{VALOR}}.\n\n".$sharedPt],
        ['name'=>'Contrato de marketing digital e redes sociais','language'=>'pt','title'=>'CONTRATO DE MARKETING DIGITAL E REDES SOCIAIS','content'=>"1. OBJETO. Gestão de {{SERVICIOS}} para {{CLIENTE}}.\n\n2. PLANO DE CONTEÚDO. Frequência, canais e peças seguirão a proposta aprovada.\n\n3. APROVAÇÕES. O CLIENTE validará dados, preços, alegações e direitos antes da publicação.\n\n4. RESULTADOS. Não são garantidos números específicos de vendas, alcance ou conversão.\n\n5. MÍDIA PAGA. O orçamento de anúncios não está incluído salvo indicação expressa.\n\n6. ACESSOS. O CLIENTE fornecerá permissões adequadas e manterá a titularidade das contas.\n\n7. VALOR E VIGÊNCIA. {{VALOR}}, a partir de {{FECHA_INICIO}}.\n\n".$sharedPt],
        ['name'=>'Comprehensive Digital Services Agreement','language'=>'en','title'=>'COMPREHENSIVE DIGITAL SERVICES AGREEMENT','content'=>"This agreement is entered into by {{EMPRESA_PRESTADORA}}, the PROVIDER, and {{CLIENTE}}, ID {{ID_CLIENTE}}, the CLIENT.\n\n1. SERVICES. The PROVIDER will perform {{SERVICIOS}} under the approved quotation.\n\n2. SCOPE. Only expressly listed deliverables are included. Additional requests require a separate written quotation.\n\n3. SCHEDULE. Work begins on {{FECHA_INICIO}} and depends on timely access, content and approvals.\n\n4. FEES. {{VALOR}}. Late payment may suspend delivery, support or publication.\n\n5. REVIEWS AND ACCEPTANCE. The CLIENT will review each delivery within five business days.\n\n6. INTELLECTUAL PROPERTY. After full payment, the CLIENT receives usage rights to final deliverables, excluding pre-existing components and third-party licenses.\n\n7. CONFIDENTIALITY. Both parties will protect commercial, technical and access information.\n\n8. TERMINATION. Fees for completed work and non-cancellable commitments remain payable.\n\n".$sharedEn],
        ['name'=>'Hosting, Domain and Corporate Email Agreement','language'=>'en','title'=>'HOSTING, DOMAIN AND CORPORATE EMAIL AGREEMENT','content'=>"1. SERVICES. The PROVIDER will administer {{SERVICIOS}} for {{CLIENTE}}.\n\n2. TERM AND RENEWAL. Services start on {{FECHA_INICIO}}. Domains, certificates, licenses and hosting follow third-party billing cycles and must be paid before expiration.\n\n3. AVAILABILITY. Absolute uptime is not guaranteed.\n\n4. BACKUPS. Backups are contingency measures and do not replace the CLIENT'S own copies.\n\n5. ACCEPTABLE USE. Spam, malware, phishing and unlawful content are prohibited.\n\n6. SUSPENSION. Non-payment or misuse may result in suspension.\n\n7. FEES. {{VALOR}}.\n\n".$sharedEn],
        ['name'=>'Digital Marketing and Social Media Agreement','language'=>'en','title'=>'DIGITAL MARKETING AND SOCIAL MEDIA AGREEMENT','content'=>"1. SERVICES. Management of {{SERVICIOS}} for {{CLIENTE}}.\n\n2. CONTENT PLAN. Frequency, channels and deliverables follow the approved proposal.\n\n3. APPROVALS. The CLIENT is responsible for validating facts, prices, claims and usage rights before publication.\n\n4. RESULTS. No specific sales, reach, ranking or conversion result is guaranteed.\n\n5. PAID MEDIA. Advertising spend is excluded unless expressly stated.\n\n6. ACCESS. The CLIENT will provide proper permissions and retain ownership of its accounts.\n\n7. FEES AND TERM. {{VALOR}}, starting {{FECHA_INICIO}}.\n\n".$sharedEn],
    ];
}

function crm_schema_health(PDO $pdo): array
{
    $missing = [];
    $requiredTables = [
        'users', 'clients', 'client_services', 'service_renewals', 'notifications',
        'settings', 'activity_logs', 'service_categories', 'service_catalog', 'payment_methods', 'quotations', 'quotation_items',
        'payments', 'user_notifications', 'support_tickets', 'support_ticket_messages', 'support_ticket_events', 'contract_templates', 'contracts',
        'contacts','pipeline_stages','deals','activities','quotation_attachments','mailing_campaigns','mailing_recipients',
        'lead_campaigns','lead_forms','lead_submissions','appointments','dashboard_preferences',
        'login_attempts','user_sessions','security_audit_logs','billing_charges',
        'projects','project_members','project_milestones','project_tasks','project_task_dependencies','project_task_checklist','project_comments','project_time_entries','project_files','project_risks','project_expenses',
        'document_folders','documents','document_versions','document_links','document_permissions','document_comments','document_approval_routes','document_approval_route_steps','document_approvals','document_approval_steps','document_shares','document_audit',
    ];

    foreach ($requiredTables as $table) {
        if (!crm_table_exists($pdo, $table)) $missing[] = 'Tabla: ' . $table;
    }

    $requiredColumns = [
        ['users', 'permissions'],
        ['users', 'avatar_path'],
        ['users', 'whatsapp'],
        ['users', 'notify_email'],
        ['users', 'notify_whatsapp'],
        ['users', 'notify_inapp'],
        ['users', 'alert_days'],
        ['users', 'two_factor_enabled'],
        ['users', 'two_factor_method'],
        ['users', 'totp_secret'],
        ['users', 'backup_codes'],
        ['users', 'failed_login_count'],
        ['users', 'locked_until'],
        ['users', 'password_changed_at'],
        ['users', 'last_login_ip'],
        ['users', 'last_login_user_agent'],
        ['users', 'language'],
        ['users', 'department'],
        ['service_catalog', 'category_id'],
        ['client_services', 'catalog_service_id'],
        ['client_services', 'payment_status'],
        ['client_services', 'paid_at'],
        ['notifications', 'quotation_id'],
        ['activity_logs', 'quotation_id'],
        ['quotations', 'payment_method_id'],
        ['quotations', 'payment_method_title'],
        ['quotations', 'payment_method_details'],
        ['quotations', 'show_payment_method'],
        ['clients', 'owner_user_id'],
        ['quotations', 'owner_user_id'],
        ['quotations', 'deal_id'],
        ['contracts', 'owner_user_id'],
        ['lead_forms', 'style_json'],
        ['client_services', 'deal_id'],
        ['client_services', 'next_billing_date'],
        ['client_services', 'last_billing_date'],
        ['client_services', 'billing_status'],
        ['client_services', 'alert_days'],
        ['client_services', 'billing_notify_email'],
        ['client_services', 'billing_notify_whatsapp'],
        ['client_services', 'billing_notify_inapp'],
        ['support_tickets', 'assigned_to'],
        ['support_tickets', 'department'],
        ['support_tickets', 'stage'],
        ['support_tickets', 'sla_due_at'],
        ['support_tickets', 'next_follow_up_at'],
        ['support_tickets', 'resolution'],
        ['support_tickets', 'last_reply_by'],
        ['support_tickets', 'closed_at'],
        ['support_ticket_messages', 'is_internal'],
    ];

    foreach ($requiredColumns as [$table, $column]) {
        if (crm_table_exists($pdo, $table) && !crm_column_exists($pdo, $table, $column)) {
            $missing[] = 'Columna: ' . $table . '.' . $column;
        }
    }

    if (crm_table_exists($pdo, 'users') && crm_column_data_type($pdo, 'users', 'role') === 'enum') {
        $missing[] = 'Formato de columna: users.role';
    }

    if (crm_table_exists($pdo, 'settings') && crm_table_exists($pdo, 'service_catalog')) {
        $appVersion = crm_read_setting($pdo, 'app_version');
        if ($appVersion === null || version_compare($appVersion, '2.1.0', '<')) {
            $missing[] = 'Actualización del CRM: versión 2.1.0';
        }

        $seedVersion = crm_read_setting($pdo, 'service_catalog_seed_version');
        if ($seedVersion !== crm_service_catalog_seed_version()) {
            $missing[] = 'Precarga del catálogo: 202 servicios en 12 categorías';
        }
    }

    return ['ok' => $missing === [], 'missing' => $missing];
}

function crm_apply_schema(PDO $pdo): array
{
    $report = [];
    $warnings = [];

    $run = static function (string $label, callable $callback, bool $fatal = true) use (&$report, &$warnings): void {
        try {
            $callback();
            $report[] = $label;
        } catch (Throwable $e) {
            if ($fatal) throw new RuntimeException($label . ': ' . $e->getMessage(), 0, $e);
            $warnings[] = $label . ': ' . $e->getMessage();
        }
    };

    $schemaPath = dirname(__DIR__) . '/sql/schema.sql';
    $schema = @file_get_contents($schemaPath);
    if ($schema === false) throw new RuntimeException('No se encontró el archivo sql/schema.sql.');

    foreach (crm_split_sql($schema) as $statement) {
        if (!str_starts_with(strtoupper(ltrim($statement)), 'CREATE TABLE IF NOT EXISTS')) continue;
        if (!preg_match('/CREATE TABLE IF NOT EXISTS\s+`?([a-z0-9_]+)`?/i', $statement, $match)) continue;
        $table = $match[1];
        $run('Tabla verificada: ' . $table, static fn() => $pdo->exec($statement));
    }

    if (crm_table_exists($pdo, 'users')) {
        $run('Roles de usuario actualizados', static fn() => $pdo->exec("ALTER TABLE users MODIFY role VARCHAR(30) NOT NULL DEFAULT 'viewer'"));
        if (!crm_column_exists($pdo, 'users', 'permissions')) {
            $run('Permisos de usuario agregados', static fn() => $pdo->exec('ALTER TABLE users ADD permissions LONGTEXT NULL AFTER role'));
        }
        $userColumns = [
            'avatar_path' => "ALTER TABLE users ADD avatar_path VARCHAR(255) NULL AFTER permissions",
            'whatsapp' => "ALTER TABLE users ADD whatsapp VARCHAR(60) NULL AFTER avatar_path",
            'notify_email' => "ALTER TABLE users ADD notify_email TINYINT(1) NOT NULL DEFAULT 1 AFTER whatsapp",
            'notify_whatsapp' => "ALTER TABLE users ADD notify_whatsapp TINYINT(1) NOT NULL DEFAULT 0 AFTER notify_email",
            'notify_inapp' => "ALTER TABLE users ADD notify_inapp TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_whatsapp",
            'alert_days' => "ALTER TABLE users ADD alert_days VARCHAR(100) NOT NULL DEFAULT '1' AFTER notify_inapp",
            'two_factor_enabled' => "ALTER TABLE users ADD two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER alert_days",
            'two_factor_method' => "ALTER TABLE users ADD two_factor_method VARCHAR(20) NOT NULL DEFAULT 'email' AFTER two_factor_enabled",
            'totp_secret' => "ALTER TABLE users ADD totp_secret TEXT NULL AFTER two_factor_method",
            'backup_codes' => "ALTER TABLE users ADD backup_codes LONGTEXT NULL AFTER totp_secret",
            'failed_login_count' => "ALTER TABLE users ADD failed_login_count SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER backup_codes",
            'locked_until' => "ALTER TABLE users ADD locked_until DATETIME NULL AFTER failed_login_count",
            'password_changed_at' => "ALTER TABLE users ADD password_changed_at DATETIME NULL AFTER locked_until",
            'last_login_ip' => "ALTER TABLE users ADD last_login_ip VARCHAR(45) NULL AFTER password_changed_at",
            'last_login_user_agent' => "ALTER TABLE users ADD last_login_user_agent VARCHAR(500) NULL AFTER last_login_ip",
            'language' => "ALTER TABLE users ADD language VARCHAR(5) NOT NULL DEFAULT 'es' AFTER last_login_user_agent",
            'department' => "ALTER TABLE users ADD department VARCHAR(120) NULL AFTER language",
        ];
        foreach ($userColumns as $column => $sql) {
            if (!crm_column_exists($pdo, 'users', $column)) {
                $run('Campo de usuario agregado: ' . $column, static fn() => $pdo->exec($sql));
            }
        }
        $run('Usuarios anteriores convertidos a roles válidos', static function () use ($pdo): void {
            $pdo->exec("UPDATE users SET role='manager' WHERE role='user'");
            $pdo->exec("UPDATE users SET role='viewer' WHERE role NOT IN ('admin','manager','sales','viewer','custom')");
        });
    }

    if (crm_table_exists($pdo, 'lead_forms') && !crm_column_exists($pdo, 'lead_forms', 'style_json')) {
        $run('Estilos visuales agregados a formularios de captación', static fn() => $pdo->exec('ALTER TABLE lead_forms ADD style_json LONGTEXT NULL AFTER fields_json'));
    }

    if (crm_table_exists($pdo, 'clients') && !crm_index_exists($pdo, 'clients', 'idx_clients_country')) {
        $run('Índice de países agregado', static fn() => $pdo->exec('ALTER TABLE clients ADD INDEX idx_clients_country (country)'));
    }

    if (crm_table_exists($pdo, 'service_catalog') && crm_table_exists($pdo, 'service_categories')) {
        if (!crm_column_exists($pdo, 'service_catalog', 'category_id')) {
            $run('Vínculo de categorías agregado al catálogo', static fn() => $pdo->exec('ALTER TABLE service_catalog ADD category_id INT UNSIGNED NULL AFTER category'));
        }
        if (!crm_column_exists($pdo, 'service_catalog', 'item_type')) {
            $run('Tipo producto/servicio agregado al catálogo', static fn() => $pdo->exec("ALTER TABLE service_catalog ADD item_type ENUM('service','product') NOT NULL DEFAULT 'service' AFTER category_id"));
        }
        if (!crm_index_exists($pdo, 'service_catalog', 'idx_catalog_category_id')) {
            $run('Índice de categorías agregado al catálogo', static fn() => $pdo->exec('ALTER TABLE service_catalog ADD INDEX idx_catalog_category_id (category_id)'));
        }
        if (!crm_constraint_exists($pdo, 'service_catalog', 'fk_catalog_category')) {
            $run('Relación de categorías agregada al catálogo', static fn() => $pdo->exec('ALTER TABLE service_catalog ADD CONSTRAINT fk_catalog_category FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE SET NULL'), false);
        }
        $run('Categorías actuales migradas al mantenedor', static function () use ($pdo): void {
            $categories = $pdo->query("SELECT DISTINCT TRIM(category) category FROM service_catalog WHERE category IS NOT NULL AND TRIM(category)<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
            $insert = $pdo->prepare('INSERT IGNORE INTO service_categories(name,is_active,sort_order) VALUES (?,1,?)');
            foreach ($categories as $index => $name) $insert->execute([(string)$name, (int)$index]);
            $pdo->exec("UPDATE service_catalog sc JOIN service_categories c ON LOWER(TRIM(c.name))=LOWER(TRIM(sc.category)) SET sc.category_id=c.id WHERE sc.category_id IS NULL");
            $pdo->exec("UPDATE service_catalog sc JOIN service_categories c ON c.id=sc.category_id SET sc.category=c.name");
        }, false);
    }

    if (crm_table_exists($pdo, 'client_services')) {
        if (!crm_column_exists($pdo, 'client_services', 'catalog_service_id')) {
            $run('Vínculo al catálogo agregado en servicios asignados a clientes', static fn() => $pdo->exec('ALTER TABLE client_services ADD catalog_service_id INT UNSIGNED NULL AFTER client_id'));
        }
        if (!crm_index_exists($pdo, 'client_services', 'idx_services_catalog')) {
            $run('Índice de catálogo agregado en servicios asignados a clientes', static fn() => $pdo->exec('ALTER TABLE client_services ADD INDEX idx_services_catalog (catalog_service_id)'));
        }
        $run('Valores monetarios ampliados en servicios asignados a clientes', static fn() => $pdo->exec("ALTER TABLE client_services MODIFY amount DECIMAL(15,2) NULL, MODIFY currency VARCHAR(10) NOT NULL DEFAULT 'USD'"));
        if (!crm_column_exists($pdo, 'client_services', 'payment_status')) {
            $run('Estado de pago agregado en servicios', static fn() => $pdo->exec("ALTER TABLE client_services ADD payment_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER payment_method"));
        }
        if (!crm_column_exists($pdo, 'client_services', 'paid_at')) {
            $run('Fecha de pago agregada en servicios', static fn() => $pdo->exec("ALTER TABLE client_services ADD paid_at DATE NULL AFTER payment_status"));
        }
        if (crm_table_exists($pdo, 'service_catalog') && !crm_constraint_exists($pdo, 'client_services', 'fk_services_catalog')) {
            $run('Relación entre servicios y catálogo agregada', static fn() => $pdo->exec('ALTER TABLE client_services ADD CONSTRAINT fk_services_catalog FOREIGN KEY (catalog_service_id) REFERENCES service_catalog(id) ON DELETE SET NULL'), false);
        }
    }

    if (crm_table_exists($pdo, 'service_renewals')) {
        $run('Valores monetarios ampliados en renovaciones', static fn() => $pdo->exec('ALTER TABLE service_renewals MODIFY amount DECIMAL(15,2) NULL'));
    }

    if (crm_table_exists($pdo, 'notifications')) {
        if (!crm_column_exists($pdo, 'notifications', 'quotation_id')) {
            $run('Vínculo de cotizaciones agregado en notificaciones', static fn() => $pdo->exec('ALTER TABLE notifications ADD quotation_id INT UNSIGNED NULL AFTER service_id'));
        }
        if (!crm_index_exists($pdo, 'notifications', 'idx_notifications_quote')) {
            $run('Índice de cotizaciones agregado en notificaciones', static fn() => $pdo->exec('ALTER TABLE notifications ADD INDEX idx_notifications_quote (quotation_id)'));
        }
        if (crm_table_exists($pdo, 'quotations') && !crm_constraint_exists($pdo, 'notifications', 'fk_notifications_quote')) {
            $run('Relación de cotizaciones agregada en notificaciones', static fn() => $pdo->exec('ALTER TABLE notifications ADD CONSTRAINT fk_notifications_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL'), false);
        }
    }

    if (crm_table_exists($pdo, 'activity_logs')) {
        if (!crm_column_exists($pdo, 'activity_logs', 'quotation_id')) {
            $run('Vínculo de cotizaciones agregado en actividad', static fn() => $pdo->exec('ALTER TABLE activity_logs ADD quotation_id INT UNSIGNED NULL AFTER service_id'));
        }
        if (!crm_index_exists($pdo, 'activity_logs', 'idx_logs_quote')) {
            $run('Índice de cotizaciones agregado en actividad', static fn() => $pdo->exec('ALTER TABLE activity_logs ADD INDEX idx_logs_quote (quotation_id)'));
        }
        if (crm_table_exists($pdo, 'quotations') && !crm_constraint_exists($pdo, 'activity_logs', 'fk_logs_quote')) {
            $run('Relación de cotizaciones agregada en actividad', static fn() => $pdo->exec('ALTER TABLE activity_logs ADD CONSTRAINT fk_logs_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL'), false);
        }
    }

    if (crm_table_exists($pdo, 'quotations')) {
        if (!crm_column_exists($pdo, 'quotations', 'payment_method_id')) {
            $run('Vínculo con formas de pago agregado en cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD payment_method_id INT UNSIGNED NULL AFTER terms'));
        }
        if (!crm_index_exists($pdo, 'quotations', 'idx_quotes_payment_method')) {
            $run('Índice de formas de pago agregado en cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD INDEX idx_quotes_payment_method (payment_method_id)'));
        }
        if (crm_table_exists($pdo, 'payment_methods') && !crm_constraint_exists($pdo, 'quotations', 'fk_quotes_payment_method')) {
            $run('Relación entre cotizaciones y formas de pago agregada', static fn() => $pdo->exec('ALTER TABLE quotations ADD CONSTRAINT fk_quotes_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL'), false);
        }
        if (!crm_column_exists($pdo, 'quotations', 'payment_method_title')) {
            $run('Título de forma de pago agregado en cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD payment_method_title VARCHAR(190) NULL AFTER terms'));
        }
        if (!crm_column_exists($pdo, 'quotations', 'payment_method_details')) {
            $run('Detalles de forma de pago agregados en cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD payment_method_details TEXT NULL AFTER payment_method_title'));
        }
        if (!crm_column_exists($pdo, 'quotations', 'show_payment_method')) {
            $run('Opción para mostrar forma de pago agregada', static fn() => $pdo->exec('ALTER TABLE quotations ADD show_payment_method TINYINT(1) NOT NULL DEFAULT 1 AFTER payment_method_details'));
        }
    }

    if (crm_table_exists($pdo, 'settings')) {
        $defaults = [
            'quote_prefix' => 'COT',
            'quote_terms_es' => 'Validez indicada en la cotización. El inicio del servicio se coordina después de la aceptación y del pago acordado.',
            'quote_terms_pt' => 'Validade indicada na cotação. O início do serviço será coordenado após a aprovação e o pagamento acordado.',
            'quote_terms_en' => 'Validity as shown in the quotation. Service begins after acceptance and the agreed payment.',
            'quote_payment_methods_clp' => "Transferencia bancaria Chile
Banco: 
Tipo de cuenta: 
Cuenta: 
Titular: 
RUT: 
Email: 
---
Webpay / Link de pago
Pago por link enviado por email o WhatsApp.",
            'quote_payment_methods_usd' => "Bank transfer USA
Bank: 
Account type: 
Account number: 
Beneficiary: 
SWIFT / ABA: 
---
Stripe payment link
Secure payment via Stripe link.",
            'quote_payment_methods_brl' => "PIX
Chave PIX: 
Favorecido: 
CPF/CNPJ: 
---
Transferência bancária Brasil
Banco: 
Agência: 
Conta: ",
            'support_email' => 'info@socrates.la',
            'smtp_force_username_from' => '1',
            'smtp_timeout' => '20',
            'contract_prefix' => 'CON',
            'contract_company_name' => 'SÓCRATES ONE · Creative Studio',
            'contract_company_id' => '',
            'contract_company_address' => '',
            'two_factor_required' => '0',
            'whatsapp_cloud_enabled' => '0',
            'whatsapp_graph_version' => 'v23.0',
            'whatsapp_phone_number_id' => '',
            'whatsapp_access_token' => '',
            'session_idle_minutes' => '30',
            'session_absolute_hours' => '8',
            'password_min_length' => '12',
            'login_max_attempts' => '5',
            'login_lock_minutes' => '15',
            'security_alert_email' => 'info@socrates.la',
            'force_admin_totp' => '0',
            'security_headers_enabled' => '1',
            'company_name' => 'Mi empresa',
            'company_logo_path' => '',
            'project_default_currency' => 'USD',
            'project_default_hourly_rate' => '75',
            'calendar_share_enabled' => '1',
            'support_default_department' => 'Soporte',
            'support_sla_urgent_hours' => '4',
            'support_sla_high_hours' => '8',
            'support_sla_normal_hours' => '24',
            'support_sla_low_hours' => '48',
            'license_server_url' => '',
            'license_status' => 'active',
            'license_plan' => 'Community',
            'license_customer' => '',
            'license_expires_at' => '',
            'demo_mode' => '0',
            'developer_contact_email' => 'info@socrates.la',
            'document_max_mb' => '50',
            'document_expiry_alert_days' => '30,15,7,3,1',
            'document_default_visibility' => 'private',
            'app_version' => '2.1.0',
        ];
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($defaults as $key => $value) {
            if ($key === 'app_version') {
                $stmt->execute([$key, $value]);
                continue;
            }
            $check = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE setting_key = ?');
            $check->execute([$key]);
            if (!(bool)$check->fetchColumn()) $stmt->execute([$key, $value]);
        }
        $report[] = 'Configuración de cotizaciones verificada';
    }

    if (crm_table_exists($pdo, 'service_catalog') && crm_table_exists($pdo, 'settings')) {
        try {
            $seedSummary = crm_seed_service_catalog($pdo);
            $report[] = sprintf(
                'Catálogo precargado: %d servicios verificados; %d nuevos, %d traducciones completadas y %d registros conservados',
                $seedSummary['total'],
                $seedSummary['inserted'],
                $seedSummary['updated'],
                $seedSummary['preserved']
            );
            if (crm_table_exists($pdo, 'service_categories') && crm_column_exists($pdo, 'service_catalog', 'category_id')) {
                $pdo->exec("INSERT IGNORE INTO service_categories (name, sort_order, is_active)
                            SELECT DISTINCT TRIM(category), 100, 1 FROM service_catalog
                            WHERE category IS NOT NULL AND TRIM(category) <> ''");
                $pdo->exec("UPDATE service_catalog sc
                            JOIN service_categories cat ON cat.name = sc.category
                            SET sc.category_id = cat.id
                            WHERE sc.category_id IS NULL OR sc.category_id <> cat.id");
                $report[] = 'Categorías del catálogo vinculadas a los servicios precargados';
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('Precarga del catálogo de servicios: ' . $exception->getMessage(), 0, $exception);
        }
    }

    if (crm_table_exists($pdo, 'payment_methods') && crm_table_exists($pdo, 'settings')) {
        $run('Formas de pago anteriores migradas al nuevo mantenedor', static function () use ($pdo): void {
            $count = (int)$pdo->query('SELECT COUNT(*) FROM payment_methods')->fetchColumn();
            if ($count > 0) return;

            $profiles = [
                'CLP' => ['key' => 'quote_payment_methods_clp', 'country' => 'Chile', 'language' => 'es'],
                'USD' => ['key' => 'quote_payment_methods_usd', 'country' => 'Estados Unidos', 'language' => 'en'],
                'BRL' => ['key' => 'quote_payment_methods_brl', 'country' => 'Brasil', 'language' => 'pt'],
            ];
            $read = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
            $insert = $pdo->prepare('INSERT INTO payment_methods (name,country,currency,language,details,is_default,is_active,sort_order) VALUES (?,?,?,?,?,?,1,?)');
            foreach ($profiles as $currency => $profile) {
                $read->execute([$profile['key']]);
                $raw = trim((string)($read->fetchColumn() ?: ''));
                if ($raw === '') continue;
                $blocks = preg_split('/\R\s*---+\s*\R|\R\s*===+\s*\R/', $raw) ?: [];
                $sort = 0;
                foreach ($blocks as $block) {
                    $block = trim((string)$block);
                    if ($block === '') continue;
                    $lines = preg_split('/\R/', $block) ?: [];
                    $name = trim((string)array_shift($lines));
                    $details = trim(implode("\n", $lines));
                    if ($name === '') continue;
                    if ($details === '') $details = $name;
                    $insert->execute([$name, $profile['country'], $currency, $profile['language'], $details, $sort === 0 ? 1 : 0, $sort++]);
                }
            }
        }, false);
    }

    if (crm_table_exists($pdo, 'users') && crm_column_exists($pdo, 'users', 'permissions')) {
        $run('Permisos de formas de pago incorporados a los perfiles existentes', static function () use ($pdo): void {
            $rows = $pdo->query("SELECT id, role, permissions FROM users WHERE role IN ('manager','sales','viewer') AND permissions IS NOT NULL AND permissions <> ''")->fetchAll();
            $update = $pdo->prepare('UPDATE users SET permissions=? WHERE id=?');
            foreach ($rows as $row) {
                $permissions = json_decode((string)$row['permissions'], true);
                if (!is_array($permissions)) continue;
                $required = [(string)$row['role'] === 'manager' ? 'payment_methods.manage' : 'payment_methods.view'];
                if ((string)$row['role'] === 'manager') $required[] = 'payment_methods.view';
                $newPermissions = array_values(array_unique(array_merge(array_map('strval', $permissions), $required)));
                if ($newPermissions !== array_values(array_map('strval', $permissions))) {
                    $update->execute([json_encode($newPermissions, JSON_UNESCAPED_UNICODE), (int)$row['id']]);
                }
            }
        }, false);
    }

    if (crm_table_exists($pdo, 'users') && crm_column_exists($pdo, 'users', 'permissions')) {
        $run('Permisos de la versión 1.3 incorporados a perfiles existentes', static function () use ($pdo): void {
            $rows = $pdo->query("SELECT id, role, permissions FROM users WHERE role IN ('manager','sales','viewer') AND permissions IS NOT NULL AND permissions <> ''")->fetchAll();
            $update = $pdo->prepare('UPDATE users SET permissions=? WHERE id=?');
            foreach ($rows as $row) {
                $permissions = json_decode((string)$row['permissions'], true);
                if (!is_array($permissions)) continue;
                $role = (string)$row['role'];
                $required = ['billing.view','tickets.view','tickets.create','manual.view','notifications.view'];
                if ($role === 'manager') {
                    $required = array_merge($required, ['payments.view','payments.manage','tickets.manage']);
                } elseif ($role === 'sales') {
                    $required = array_merge($required, ['payments.view']);
                }
                $new = array_values(array_unique(array_merge(array_map('strval', $permissions), $required)));
                $update->execute([json_encode($new, JSON_UNESCAPED_UNICODE), (int)$row['id']]);
            }
        }, false);
    }

    if (crm_table_exists($pdo, 'contract_templates')) {
        $run('Plantillas iniciales de contratos verificadas', static function () use ($pdo): void {
            $count = (int)$pdo->query('SELECT COUNT(*) FROM contract_templates')->fetchColumn();
            if ($count > 0) return;
            $templates = [
                ['Contrato general de prestación de servicios','es','CONTRATO DE PRESTACIÓN DE SERVICIOS',"Entre {{EMPRESA_PRESTADORA}}, en adelante “EL PRESTADOR”, y {{CLIENTE}}, identificación {{ID_CLIENTE}}, en adelante “EL CLIENTE”, se acuerda el presente contrato.\n\nPRIMERO: OBJETO. EL PRESTADOR realizará los siguientes servicios: {{SERVICIOS}}.\n\nSEGUNDO: PLAZO. El servicio comenzará el {{FECHA_INICIO}} y tendrá la vigencia indicada en la propuesta comercial o hasta su terminación conforme a este contrato.\n\nTERCERO: HONORARIOS Y PAGO. El valor acordado es {{VALOR}}. La forma y fechas de pago serán las indicadas en la cotización aprobada.\n\nCUARTO: OBLIGACIONES DEL CLIENTE. EL CLIENTE entregará oportunamente accesos, contenidos, aprobaciones e información necesaria. Los retrasos imputables al CLIENTE podrán modificar los plazos.\n\nQUINTO: PROPIEDAD INTELECTUAL. Una vez pagado el total, EL CLIENTE recibirá los derechos de uso de los entregables finales, excepto licencias de terceros, herramientas internas y componentes previamente desarrollados.\n\nSEXTO: CONFIDENCIALIDAD. Las partes mantendrán reserva sobre la información no pública conocida durante el servicio.\n\nSÉPTIMO: TERMINACIÓN. Cualquiera de las partes podrá terminar el contrato mediante aviso escrito, pagando los trabajos ejecutados y compromisos ya asumidos.\n\nOCTAVO: ACEPTACIÓN. La aprobación electrónica de este documento expresa la conformidad del CLIENTE y permite generar el PDF para firma."],
                ['Contrato de mantenimiento mensual','es','CONTRATO DE MANTENIMIENTO Y SOPORTE MENSUAL',"Entre {{EMPRESA_PRESTADORA}} y {{CLIENTE}} se acuerda un servicio mensual de mantenimiento y soporte.\n\n1. SERVICIOS INCLUIDOS: {{SERVICIOS}}.\n\n2. VIGENCIA: desde {{FECHA_INICIO}}, con renovación mensual mientras ninguna parte comunique su término.\n\n3. VALOR: {{VALOR}}, pagadero conforme a la cotización aprobada.\n\n4. ALCANCE: las solicitudes fuera del plan serán cotizadas separadamente. Los tiempos de respuesta dependen de la prioridad y disponibilidad.\n\n5. ACCESOS Y RESPALDOS: EL CLIENTE autoriza el acceso técnico necesario y se compromete a informar cambios de credenciales.\n\n6. TERMINACIÓN: cualquiera de las partes podrá terminar con aviso escrito, quedando pendientes los valores correspondientes al período en curso."],
                ['Contrato de desarrollo web','es','CONTRATO DE DISEÑO Y DESARROLLO WEB',"Entre {{EMPRESA_PRESTADORA}} y {{CLIENTE}} se celebra este contrato de diseño y desarrollo web.\n\n1. OBJETO: desarrollo de {{SERVICIOS}}.\n\n2. ETAPAS: planificación, diseño visual, programación, carga de contenidos suministrados por EL CLIENTE, pruebas y publicación.\n\n3. PLAZOS: comienzan el {{FECHA_INICIO}} y dependen de la entrega oportuna de contenido y aprobaciones.\n\n4. VALOR: {{VALOR}}, conforme al calendario de pagos aceptado.\n\n5. REVISIONES: se incluyen las revisiones especificadas en la cotización. Cambios de alcance serán cotizados adicionalmente.\n\n6. DOMINIO, HOSTING Y LICENCIAS: se regirán por sus propios períodos de renovación y condiciones de terceros.\n\n7. ENTREGA: después del pago total se entregarán accesos y archivos finales que correspondan al alcance contratado."],
                ['Contrato geral de prestação de serviços','pt','CONTRATO DE PRESTAÇÃO DE SERVIÇOS',"Entre {{EMPRESA_PRESTADORA}}, doravante “PRESTADOR”, e {{CLIENTE}}, identificação {{ID_CLIENTE}}, doravante “CLIENTE”, fica acordado o presente contrato.\n\nPRIMEIRA: OBJETO. O PRESTADOR realizará os seguintes serviços: {{SERVICIOS}}.\n\nSEGUNDA: PRAZO. O serviço começará em {{FECHA_INICIO}} e terá a vigência indicada na proposta aprovada.\n\nTERCEIRA: VALOR E PAGAMENTO. O valor acordado é {{VALOR}}, conforme as condições da cotação aprovada.\n\nQUARTA: OBRIGAÇÕES DO CLIENTE. O CLIENTE fornecerá acessos, conteúdos, aprovações e informações necessárias dentro dos prazos.\n\nQUINTA: PROPRIEDADE INTELECTUAL. Após o pagamento integral, o CLIENTE receberá os direitos de uso dos materiais finais, exceto licenças de terceiros e componentes previamente desenvolvidos.\n\nSEXTA: CONFIDENCIALIDADE. As partes manterão sigilo sobre informações não públicas.\n\nSÉTIMA: ACEITE. A aprovação eletrônica representa concordância e permite gerar o PDF para assinatura."],
                ['General Service Agreement','en','GENERAL SERVICE AGREEMENT',"This Service Agreement is entered into by {{EMPRESA_PRESTADORA}}, hereinafter the “PROVIDER”, and {{CLIENTE}}, identification {{ID_CLIENTE}}, hereinafter the “CLIENT”.\n\n1. SERVICES. The PROVIDER will perform: {{SERVICIOS}}.\n\n2. TERM. Services begin on {{FECHA_INICIO}} and continue for the period stated in the approved proposal.\n\n3. FEES. The agreed value is {{VALOR}}, payable under the terms of the approved quotation.\n\n4. CLIENT RESPONSIBILITIES. The CLIENT will provide timely access, content, approvals and information.\n\n5. INTELLECTUAL PROPERTY. After full payment, the CLIENT receives usage rights to the final deliverables, excluding third-party licenses and pre-existing components.\n\n6. CONFIDENTIALITY. Both parties will protect non-public information.\n\n7. ACCEPTANCE. Electronic approval confirms acceptance and enables generation of the PDF for signature."],
            ];
            $stmt = $pdo->prepare('INSERT INTO contract_templates(name,language,title,content,is_active,sort_order) VALUES (?,?,?,?,1,?)');
            foreach ($templates as $index => $template) $stmt->execute([$template[0],$template[1],$template[2],$template[3],$index]);
        }, false);
    }

    if (crm_table_exists($pdo, 'users') && crm_column_exists($pdo, 'users', 'permissions')) {
        $run('Permisos de contratos y categorías incorporados', static function () use ($pdo): void {
            $rows = $pdo->query("SELECT id, role, permissions FROM users WHERE role IN ('manager','sales','viewer') AND permissions IS NOT NULL AND permissions <> ''")->fetchAll();
            $update = $pdo->prepare('UPDATE users SET permissions=? WHERE id=?');
            foreach ($rows as $row) {
                $permissions = json_decode((string)$row['permissions'], true);
                if (!is_array($permissions)) continue;
                $role = (string)$row['role'];
                $required = ['catalog_categories.view','contracts.view','contract_templates.view'];
                if ($role === 'manager') $required = array_merge($required,['catalog_categories.manage','contracts.create','contracts.edit','contracts.send','contracts.status','contract_templates.manage','payment_methods.send']);
                elseif ($role === 'sales') $required = array_merge($required,['contracts.create','contracts.edit','contracts.send','contracts.status','payment_methods.send']);
                $new = array_values(array_unique(array_merge(array_map('strval',$permissions),$required)));
                $update->execute([json_encode($new,JSON_UNESCAPED_UNICODE),(int)$row['id']]);
            }
        }, false);
    }

    // CRM 1.5.0: propietario, pipeline, contactos, campañas, formularios y agenda.
    if (crm_table_exists($pdo, 'clients')) {
        if (!crm_column_exists($pdo, 'clients', 'owner_user_id')) {
            $run('Responsable agregado a clientes', static fn() => $pdo->exec('ALTER TABLE clients ADD owner_user_id INT UNSIGNED NULL AFTER notes'));
        }
        if (!crm_index_exists($pdo, 'clients', 'idx_clients_owner')) {
            $run('Índice de responsable agregado a clientes', static fn() => $pdo->exec('ALTER TABLE clients ADD INDEX idx_clients_owner (owner_user_id)'));
        }
        if (!crm_constraint_exists($pdo, 'clients', 'fk_clients_owner')) {
            $run('Relación de responsable agregada a clientes', static fn() => $pdo->exec('ALTER TABLE clients ADD CONSTRAINT fk_clients_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL'), false);
        }
    }
    if (crm_table_exists($pdo, 'quotations')) {
        if (!crm_column_exists($pdo, 'quotations', 'owner_user_id')) {
            $run('Responsable agregado a cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD owner_user_id INT UNSIGNED NULL AFTER accepted_at'));
        }
        if (!crm_column_exists($pdo, 'quotations', 'deal_id')) {
            $run('Negocio asociado agregado a cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD deal_id INT UNSIGNED NULL AFTER owner_user_id'));
        }
        if (!crm_index_exists($pdo, 'quotations', 'idx_quotes_owner')) {
            $run('Índice de responsable agregado a cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD INDEX idx_quotes_owner (owner_user_id)'));
        }
        if (!crm_index_exists($pdo, 'quotations', 'idx_quotes_deal')) {
            $run('Índice de negocio agregado a cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD INDEX idx_quotes_deal (deal_id)'));
        }
        if (!crm_constraint_exists($pdo, 'quotations', 'fk_quotes_owner')) {
            $run('Relación de responsable agregada a cotizaciones', static fn() => $pdo->exec('ALTER TABLE quotations ADD CONSTRAINT fk_quotes_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL'), false);
        }
    }
    if (crm_table_exists($pdo, 'contracts')) {
        if (!crm_column_exists($pdo, 'contracts', 'owner_user_id')) {
            $run('Responsable agregado a contratos', static fn() => $pdo->exec('ALTER TABLE contracts ADD owner_user_id INT UNSIGNED NULL AFTER sent_at'));
        }
        if (!crm_index_exists($pdo, 'contracts', 'idx_contracts_owner')) {
            $run('Índice de responsable agregado a contratos', static fn() => $pdo->exec('ALTER TABLE contracts ADD INDEX idx_contracts_owner (owner_user_id)'));
        }
        if (!crm_constraint_exists($pdo, 'contracts', 'fk_contracts_owner')) {
            $run('Relación de responsable agregada a contratos', static fn() => $pdo->exec('ALTER TABLE contracts ADD CONSTRAINT fk_contracts_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL'), false);
        }
    }

    if (crm_table_exists($pdo, 'pipeline_stages')) {
        $run('Etapas iniciales del pipeline verificadas', static function () use ($pdo): void {
            $stages = [
                ['Primer contacto','Primeiro contato','First contact','#7c3aed',10,10,0,0],
                ['Propuesta enviada','Proposta enviada','Proposal sent','#f59e0b',60,20,0,0],
                ['Negociación','Negociação','Negotiation','#f97316',75,30,0,0],
                ['Cerrado ganado','Fechado ganho','Closed won','#22c55e',100,40,1,0],
                ['Cerrado perdido','Fechado perdido','Closed lost','#ef4444',0,50,0,1],
            ];
            $select=$pdo->prepare('SELECT id FROM pipeline_stages WHERE name_es=? LIMIT 1');
            $insert=$pdo->prepare('INSERT INTO pipeline_stages(name_es,name_pt,name_en,color,probability,sort_order,is_closed_won,is_closed_lost,is_active) VALUES (?,?,?,?,?,?,?,?,1)');
            foreach($stages as $stage){$select->execute([$stage[0]]);if(!$select->fetchColumn())$insert->execute($stage);}
        }, false);

        // CRM 1.6.1: simplifica el pipeline sin eliminar historial.
        $run('Etapas Análisis y Lead cualificado retiradas del pipeline', static function () use ($pdo): void {
            $find=$pdo->prepare('SELECT id FROM pipeline_stages WHERE name_es=? LIMIT 1');
            $find->execute(['Primer contacto']);$firstId=(int)($find->fetchColumn()?:0);
            $find->execute(['Propuesta enviada']);$proposalId=(int)($find->fetchColumn()?:0);
            $find->execute(['Análisis']);$analysisId=(int)($find->fetchColumn()?:0);
            $find->execute(['Lead cualificado']);$qualifiedId=(int)($find->fetchColumn()?:0);

            if (crm_table_exists($pdo,'deals')) {
                if ($analysisId>0 && $firstId>0) {
                    $move=$pdo->prepare("UPDATE deals SET stage_id=?,probability=10,status='open',closed_at=NULL WHERE stage_id=? AND status='open'");
                    $move->execute([$firstId,$analysisId]);
                }
                if ($qualifiedId>0 && $proposalId>0) {
                    $move=$pdo->prepare("UPDATE deals SET stage_id=?,probability=60,status='open',closed_at=NULL WHERE stage_id=? AND status='open'");
                    $move->execute([$proposalId,$qualifiedId]);
                }
            }

            $disable=$pdo->prepare('UPDATE pipeline_stages SET is_active=0 WHERE name_es IN (?,?)');
            $disable->execute(['Análisis','Lead cualificado']);
            $orders=['Primer contacto'=>10,'Propuesta enviada'=>20,'Negociación'=>30,'Cerrado ganado'=>40,'Cerrado perdido'=>50];
            $orderUpdate=$pdo->prepare('UPDATE pipeline_stages SET sort_order=? WHERE name_es=?');
            foreach($orders as$name=>$order)$orderUpdate->execute([$order,$name]);
        }, false);
    }

    if (crm_table_exists($pdo, 'contract_templates')) {
        $run('Biblioteca legal multidioma ampliada', static function () use ($pdo): void {
            $templates = crm_contract_template_library();
            $select=$pdo->prepare('SELECT id FROM contract_templates WHERE name=? AND language=? LIMIT 1');
            $insert=$pdo->prepare('INSERT INTO contract_templates(name,language,title,content,is_active,sort_order) VALUES (?,?,?,?,1,?)');
            foreach($templates as $index=>$template){$select->execute([$template['name'],$template['language']]);if(!$select->fetchColumn())$insert->execute([$template['name'],$template['language'],$template['title'],$template['content'],100+$index]);}
        }, false);
    }

    if (crm_table_exists($pdo, 'users') && crm_column_exists($pdo, 'users', 'permissions')) {
        $run('Permisos comerciales de la versión 1.5 incorporados', static function () use ($pdo): void {
            $rows=$pdo->query("SELECT id,role,permissions FROM users WHERE role IN ('manager','sales','viewer') AND permissions IS NOT NULL AND permissions<>''")->fetchAll();
            $update=$pdo->prepare('UPDATE users SET permissions=? WHERE id=?');
            foreach($rows as $row){
                $permissions=json_decode((string)$row['permissions'],true);if(!is_array($permissions))continue;
                $role=(string)$row['role'];
                $required=['contacts.view','activities.view','deals.view','reports.view','appointments.view','assistant.use','imports.view'];
                if($role==='manager')$required=array_merge($required,['contacts.manage','activities.manage','deals.manage','deals.transfer','mailing.view','mailing.manage','leads.view','leads.manage','forms.view','forms.manage','appointments.manage','imports.manage','reports.export','dashboard.customize']);
                elseif($role==='sales')$required=array_merge($required,['contacts.manage','activities.manage','deals.manage','mailing.view','leads.view','forms.view','appointments.manage','dashboard.customize']);
                $new=array_values(array_unique(array_merge(array_map('strval',$permissions),$required)));
                $update->execute([json_encode($new,JSON_UNESCAPED_UNICODE),(int)$row['id']]);
            }
        }, false);
    }

    if (crm_table_exists($pdo, 'users') && crm_column_exists($pdo, 'users', 'permissions')) {
        $run('Permisos del Centro de Seguridad incorporados', static function () use ($pdo): void {
            $rows=$pdo->query("SELECT id,role,permissions FROM users WHERE role IN ('manager','sales','viewer','custom') AND permissions IS NOT NULL AND permissions<>''")->fetchAll();
            $update=$pdo->prepare('UPDATE users SET permissions=? WHERE id=?');
            foreach($rows as $row){
                $permissions=json_decode((string)$row['permissions'],true);if(!is_array($permissions))continue;
                if((string)$row['role']==='manager')$permissions[]='security.view';
                $permissions=array_values(array_unique(array_map('strval',$permissions)));
                $update->execute([json_encode($permissions,JSON_UNESCAPED_UNICODE),(int)$row['id']]);
            }
        }, false);
        $run('Contraseñas anteriores marcadas para control', static fn() => $pdo->exec('UPDATE users SET password_changed_at=COALESCE(password_changed_at,updated_at,created_at) WHERE password_changed_at IS NULL'), false);
    }



    // CRM 1.8.0: facturación recurrente, ciclos de cobro y alertas por servicio.
    if (crm_table_exists($pdo, 'client_services')) {
        $billingColumns = [
            'deal_id' => "ALTER TABLE client_services ADD deal_id INT UNSIGNED NULL AFTER catalog_service_id",
            'next_billing_date' => "ALTER TABLE client_services ADD next_billing_date DATE NULL AFTER expiration_date",
            'last_billing_date' => "ALTER TABLE client_services ADD last_billing_date DATE NULL AFTER next_billing_date",
            'billing_status' => "ALTER TABLE client_services ADD billing_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER payment_status",
            'alert_days' => "ALTER TABLE client_services ADD alert_days VARCHAR(100) NOT NULL DEFAULT '30,15,7,3,1,0' AFTER billing_status",
            'billing_notify_email' => "ALTER TABLE client_services ADD billing_notify_email TINYINT(1) NOT NULL DEFAULT 1 AFTER alert_days",
            'billing_notify_whatsapp' => "ALTER TABLE client_services ADD billing_notify_whatsapp TINYINT(1) NOT NULL DEFAULT 0 AFTER billing_notify_email",
            'billing_notify_inapp' => "ALTER TABLE client_services ADD billing_notify_inapp TINYINT(1) NOT NULL DEFAULT 1 AFTER billing_notify_whatsapp",
        ];
        foreach ($billingColumns as $column=>$sql) if (!crm_column_exists($pdo,'client_services',$column)) $run('Facturación: '.$column.' agregado', static fn()=>$pdo->exec($sql));
        $run('Fechas de próximo cobro inicializadas', static fn()=>$pdo->exec("UPDATE client_services SET next_billing_date=expiration_date WHERE next_billing_date IS NULL AND expiration_date IS NOT NULL AND status='active'"), false);
        if (!crm_index_exists($pdo,'client_services','idx_services_next_billing')) $run('Índice de próximos cobros agregado', static fn()=>$pdo->exec('ALTER TABLE client_services ADD INDEX idx_services_next_billing (next_billing_date,billing_status)'));
        if (!crm_index_exists($pdo,'client_services','idx_services_deal')) $run('Índice de negocio en servicios agregado', static fn()=>$pdo->exec('ALTER TABLE client_services ADD INDEX idx_services_deal (deal_id)'));
        if (crm_table_exists($pdo,'deals') && !crm_constraint_exists($pdo,'client_services','fk_services_deal')) $run('Relación negocio-servicio agregada', static fn()=>$pdo->exec('ALTER TABLE client_services ADD CONSTRAINT fk_services_deal FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE SET NULL'), false);
    }
    if (!crm_table_exists($pdo,'billing_charges')) {
        $run('Historial de ciclos de cobro creado', static fn()=>$pdo->exec("CREATE TABLE IF NOT EXISTS billing_charges (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,service_id INT UNSIGNED NOT NULL,client_id INT UNSIGNED NOT NULL,due_date DATE NOT NULL,amount DECIMAL(15,2) NOT NULL DEFAULT 0,currency VARCHAR(10) NOT NULL DEFAULT 'USD',status VARCHAR(20) NOT NULL DEFAULT 'pending',paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,paid_at DATE NULL,payment_id INT UNSIGNED NULL,notes TEXT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,CONSTRAINT fk_billing_charge_service FOREIGN KEY(service_id) REFERENCES client_services(id) ON DELETE CASCADE,CONSTRAINT fk_billing_charge_client FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,CONSTRAINT fk_billing_charge_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE SET NULL,UNIQUE KEY uq_billing_service_due(service_id,due_date),INDEX idx_billing_due_status(due_date,status),INDEX idx_billing_client(client_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"));
    }
    if (crm_table_exists($pdo, 'users') && crm_column_exists($pdo, 'users', 'permissions')) {
        $run('Permisos de Project Manager incorporados', static function () use ($pdo): void {
            $rows=$pdo->query("SELECT id,role,permissions FROM users WHERE role IN ('manager','sales','viewer') AND permissions IS NOT NULL AND permissions<>''")->fetchAll();
            $update=$pdo->prepare('UPDATE users SET permissions=? WHERE id=?');
            foreach($rows as $row){
                $permissions=json_decode((string)$row['permissions'],true);if(!is_array($permissions))continue;
                $role=(string)$row['role'];
                $required=['projects.view','project_tasks.view','project_time.view','project_risks.view'];
                if($role==='manager')$required=array_merge($required,['projects.manage','project_tasks.manage','project_time.manage','project_finance.view','project_finance.manage','project_risks.manage']);
                elseif($role==='sales')$required=array_merge($required,['project_tasks.manage','project_time.manage']);
                $new=array_values(array_unique(array_merge(array_map('strval',$permissions),$required)));
                $update->execute([json_encode($new,JSON_UNESCAPED_UNICODE),(int)$row['id']]);
            }
        }, false);
    }

    // CRM 2.0: helpdesk, branding y configuración comercial.
    if (crm_table_exists($pdo,'support_tickets')) {
        $cols=[
            'assigned_to'=>"ALTER TABLE support_tickets ADD assigned_to INT UNSIGNED NULL AFTER user_id",
            'department'=>"ALTER TABLE support_tickets ADD department VARCHAR(80) NOT NULL DEFAULT 'Soporte' AFTER category",
            'stage'=>"ALTER TABLE support_tickets ADD stage VARCHAR(30) NOT NULL DEFAULT 'new' AFTER status",
            'sla_due_at'=>"ALTER TABLE support_tickets ADD sla_due_at DATETIME NULL AFTER stage",
            'next_follow_up_at'=>"ALTER TABLE support_tickets ADD next_follow_up_at DATETIME NULL AFTER sla_due_at",
            'resolution'=>"ALTER TABLE support_tickets ADD resolution TEXT NULL AFTER next_follow_up_at",
            'last_reply_by'=>"ALTER TABLE support_tickets ADD last_reply_by INT UNSIGNED NULL AFTER resolution",
            'closed_at'=>"ALTER TABLE support_tickets ADD closed_at DATETIME NULL AFTER last_reply_by",
        ];
        foreach($cols as$column=>$sql) if(!crm_column_exists($pdo,'support_tickets',$column)) $run('Helpdesk: '.$column.' agregado',static fn()=>$pdo->exec($sql));
        if(!crm_index_exists($pdo,'support_tickets','idx_support_tickets_assigned'))$run('Helpdesk: índice responsable',static fn()=>$pdo->exec('ALTER TABLE support_tickets ADD INDEX idx_support_tickets_assigned (assigned_to)'),false);
        if(!crm_index_exists($pdo,'support_tickets','idx_support_tickets_stage'))$run('Helpdesk: índice etapa',static fn()=>$pdo->exec('ALTER TABLE support_tickets ADD INDEX idx_support_tickets_stage (stage)'),false);
        if(!crm_index_exists($pdo,'support_tickets','idx_support_tickets_sla'))$run('Helpdesk: índice SLA',static fn()=>$pdo->exec('ALTER TABLE support_tickets ADD INDEX idx_support_tickets_sla (sla_due_at)'),false);
        if(!crm_index_exists($pdo,'support_tickets','idx_support_tickets_followup'))$run('Helpdesk: índice seguimiento',static fn()=>$pdo->exec('ALTER TABLE support_tickets ADD INDEX idx_support_tickets_followup (next_follow_up_at)'),false);
        if(crm_table_exists($pdo,'users')&&!crm_constraint_exists($pdo,'support_tickets','fk_support_tickets_assigned'))$run('Helpdesk: relación responsable',static fn()=>$pdo->exec('ALTER TABLE support_tickets ADD CONSTRAINT fk_support_tickets_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL'),false);
        if(crm_table_exists($pdo,'users')&&!crm_constraint_exists($pdo,'support_tickets','fk_support_tickets_lastreply'))$run('Helpdesk: relación última respuesta',static fn()=>$pdo->exec('ALTER TABLE support_tickets ADD CONSTRAINT fk_support_tickets_lastreply FOREIGN KEY (last_reply_by) REFERENCES users(id) ON DELETE SET NULL'),false);
        $run('Helpdesk: estados inicializados',static fn()=>$pdo->exec("UPDATE support_tickets SET stage=CASE WHEN status='closed' THEN 'closed' WHEN status='resolved' THEN 'resolved' ELSE 'new' END WHERE stage IS NULL OR stage=''"),false);
    }
    if(crm_table_exists($pdo,'support_ticket_messages')&&!crm_column_exists($pdo,'support_ticket_messages','is_internal'))$run('Helpdesk: notas internas habilitadas',static fn()=>$pdo->exec('ALTER TABLE support_ticket_messages ADD is_internal TINYINT(1) NOT NULL DEFAULT 0 AFTER message'));
    if(!crm_table_exists($pdo,'support_ticket_events'))$run('Helpdesk: línea de tiempo creada',static fn()=>$pdo->exec("CREATE TABLE support_ticket_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,ticket_id INT UNSIGNED NOT NULL,user_id INT UNSIGNED NULL,event_type VARCHAR(40) NOT NULL,title VARCHAR(190) NOT NULL,details TEXT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_support_events_ticket FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,CONSTRAINT fk_support_events_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_support_events_ticket(ticket_id),INDEX idx_support_events_created(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"));

    // SÓCRATES CRM 2.1.0 - Document Manager
    if (crm_table_exists($pdo,'users') && !crm_column_exists($pdo,'users','department')) $run('Usuarios: departamento agregado', static fn()=>$pdo->exec("ALTER TABLE users ADD department VARCHAR(120) NULL AFTER language"));
    // Las tablas documentales se crean desde sql/schema.sql al iniciar la reparación.
    if (crm_table_exists($pdo,'users') && crm_column_exists($pdo,'users','permissions')) {
        $run('Document Manager: permisos incorporados', static function() use($pdo):void {
            $rows=$pdo->query("SELECT id,role,permissions FROM users WHERE role IN ('manager','sales','viewer') AND permissions IS NOT NULL AND permissions<>''")->fetchAll();
            $up=$pdo->prepare('UPDATE users SET permissions=? WHERE id=?');
            foreach($rows as $row){$per=json_decode((string)$row['permissions'],true);if(!is_array($per))continue;$req=['documents.view'];if($row['role']==='manager')$req=array_merge($req,['documents.upload','documents.edit','documents.delete','documents.share','documents.approve','documents.manage','documents.audit']);elseif($row['role']==='sales')$req=array_merge($req,['documents.upload','documents.edit','documents.share','documents.approve']);$new=array_values(array_unique(array_merge(array_map('strval',$per),$req)));$up->execute([json_encode($new,JSON_UNESCAPED_UNICODE),(int)$row['id']]);}
        }, false);
    }

    if (crm_table_exists($pdo,'settings')) {
        $run('Document Manager: configuración inicial', static function() use($pdo):void {
            $stmt=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=setting_value");
            foreach(['document_max_mb'=>'50','document_expiry_alert_days'=>'30,15,7,3,1','document_default_visibility'=>'private'] as $k=>$v)$stmt->execute([$k,$v]);
        }, false);
        $run('Document Manager: datos DEMO verificados', static function() use($pdo):void {
            $demo=(string)(crm_read_setting($pdo,'demo_mode')??'0')==='1' || (string)(crm_read_setting($pdo,'license_status')??'')==='demo';
            if(!$demo || (int)$pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn()>0)return;
            $uid=(int)($pdo->query("SELECT id FROM users WHERE is_active=1 ORDER BY (role='admin') DESC,id LIMIT 1")->fetchColumn()?:0);if(!$uid)return;
            $pdo->prepare("INSERT INTO document_folders(name,description,created_by) VALUES('Comercial','Cotizaciones, propuestas y contratos',?)")->execute([$uid]);$commercial=(int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO document_folders(name,description,created_by) VALUES('Proyectos','Entregables y documentación de proyectos',?)")->execute([$uid]);$projects=(int)$pdo->lastInsertId();
            $firstDoc=0;
            $samples=[
              [$commercial,'Propuesta Comercial — Implementación SÓCRATES CRM','Propuesta comercial de ejemplo','Propuesta','review',date('Y-m-d',strtotime('+20 days'))],
              [$commercial,'Contrato de Servicios Digitales','Contrato listo para aprobación','Contrato','approved',date('Y-m-d',strtotime('+180 days'))],
              [$projects,'Plan de Proyecto — Migración y Puesta en Marcha','Plan de trabajo y responsables','Proyecto','draft',null],
              [$projects,'Checklist de Entrega','Lista de verificación de entrega','Operaciones','approved',null],
              [$commercial,'Certificado de Servicio','Documento demostrativo próximo a vencer','Certificado','approved',date('Y-m-d',strtotime('+7 days'))],
              [null,'Reporte Ejecutivo Mensual','Resumen ejecutivo de indicadores','Reporte','approved',null],
            ];
            $root=dirname(__DIR__).'/storage/documents/demo';if(!is_dir($root))@mkdir($root,0750,true);@file_put_contents(dirname(__DIR__).'/storage/documents/.htaccess',"Require all denied\nOptions -Indexes\n");
            $ins=$pdo->prepare('INSERT INTO documents(folder_id,title,description,category,status,visibility,owner_user_id,expires_at,is_favorite) VALUES(?,?,?,?,?,\'company\',?,?,?)');
            $ver=$pdo->prepare('INSERT INTO document_versions(document_id,version_no,original_name,stored_path,mime_type,file_size,sha256,notes,created_by) VALUES(?,?,?,?,?,?,?,?,?)');
            foreach($samples as$i=>$x){$ins->execute([$x[0],$x[1],$x[2],$x[3],$x[4],$uid,$x[5],$i===0?1:0]);$did=(int)$pdo->lastInsertId();if($i===0)$firstDoc=$did;$content="SÓCRATES CRM DEMO DOCUMENT\n\n{$x[1]}\n\n{$x[2]}\n\nArchivo ilustrativo generado automáticamente para demostrar Document Manager.\n";$rel='demo/document-'.$did.'-v1.txt';file_put_contents(dirname(__DIR__).'/storage/documents/'.$rel,$content);$ver->execute([$did,1,'documento-demo-'.$did.'.txt',$rel,'text/plain',strlen($content),hash('sha256',$content),'Versión inicial DEMO',$uid]);$vid=(int)$pdo->lastInsertId();$pdo->prepare('UPDATE documents SET current_version_id=? WHERE id=?')->execute([$vid,$did]);if($i===0){$content2=$content."\nRevisión comercial v2: condiciones y alcance actualizados.\n";$rel2='demo/document-'.$did.'-v2.txt';file_put_contents(dirname(__DIR__).'/storage/documents/'.$rel2,$content2);$ver->execute([$did,2,'propuesta-comercial-v2.txt',$rel2,'text/plain',strlen($content2),hash('sha256',$content2),'Condiciones comerciales ajustadas',$uid]);$pdo->prepare('UPDATE documents SET current_version_id=? WHERE id=?')->execute([(int)$pdo->lastInsertId(),$did]);}}
            $users=$pdo->query('SELECT id FROM users WHERE is_active=1 ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);if($users){$pdo->prepare("INSERT INTO document_approval_routes(name,description,created_by) VALUES('Aprobación Comercial','Ruta DEMO para revisión de propuestas',?)")->execute([$uid]);$rid=(int)$pdo->lastInsertId();$rs=$pdo->prepare('INSERT INTO document_approval_route_steps(route_id,step_order,approver_user_id,label) VALUES(?,?,?,?)');foreach($users as$i=>$au)$rs->execute([$rid,$i+1,(int)$au,$i===0?'Revisión comercial':'Aprobación final']);if($firstDoc>0){$pdo->prepare("INSERT INTO document_approvals(document_id,route_id,status,started_by) VALUES(?,?,'pending',?)")->execute([$firstDoc,$rid,$uid]);$aid=(int)$pdo->lastInsertId();$asi=$pdo->prepare('INSERT INTO document_approval_steps(approval_id,step_order,approver_user_id,label,status) VALUES(?,?,?,?,?)');foreach($users as$i=>$au)$asi->execute([$aid,$i+1,(int)$au,$i===0?'Revisión comercial':'Aprobación final',$i===0?'pending':'waiting']);}}
        }, false);
    }

    if (crm_table_exists($pdo,'settings')) {
        $run('Versión 1.8.0 registrada', static function() use($pdo):void { $stmt=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('app_version','1.8.0') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");$stmt->execute(); }, false);
        $run('Versión 1.9.0 Project Manager registrada', static function() use($pdo):void { $stmt=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('app_version','1.9.0') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");$stmt->execute(); }, false);
        $run('Versión 2.0.0 registrada', static function() use($pdo):void { $stmt=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('app_version','2.0.0') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");$stmt->execute(); }, false);
        $run('Versión 2.1.0 Document Manager registrada', static function() use($pdo):void { $stmt=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('app_version','2.1.0') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");$stmt->execute(); }, false);
    }

    $health = crm_schema_health($pdo);
    if (!$health['ok']) {
        throw new RuntimeException('La reparación quedó incompleta. Faltan: ' . implode(', ', $health['missing']));
    }

    return ['report' => $report, 'warnings' => $warnings, 'health' => $health];
}
