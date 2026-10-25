<?php
declare(strict_types=1);

function require_login(): void
{
    if (empty($_SESSION['user_id']) || !crm_validate_session()) {
        if (!empty($_SESSION['user_id'])) crm_revoke_current_session();
        $_SESSION = [];
        flash('error', 'Tu sesión finalizó. Inicia sesión nuevamente.');
        redirect('login.php');
    }
    $script=basename((string)($_SERVER['SCRIPT_NAME']??''));
    if((string)setting('force_admin_totp','0')==='1'&&!in_array($script,['security_2fa.php','logout.php','repair.php'],true)){
        try{$forceStmt=db()->prepare('SELECT role,two_factor_method,totp_secret FROM users WHERE id=?');$forceStmt->execute([(int)$_SESSION['user_id']]);$forceUser=$forceStmt->fetch();if($forceUser&&(string)$forceUser['role']==='admin'&&((string)$forceUser['two_factor_method']!=='totp'||empty($forceUser['totp_secret'])))$_SESSION['force_totp_setup_after_login']=1;}catch(Throwable){}
    }
    if(!empty($_SESSION['force_totp_setup_after_login'])&&!in_array($script,['security_2fa.php','logout.php'],true)) redirect('security_2fa.php?setup=1');
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) return $user;
    if (empty($_SESSION['user_id'])) return $user = null;

    $columns = 'id,name,email,role,permissions,avatar_path,whatsapp,notify_email,notify_whatsapp,notify_inapp,alert_days,two_factor_enabled,two_factor_method,totp_secret,backup_codes,password_changed_at,last_login_ip,last_login_user_agent,language,department,is_active';
    try {
        $stmt = db()->prepare("SELECT {$columns} FROM users WHERE id=? AND is_active=1");
        $stmt->execute([(int)$_SESSION['user_id']]);
        return $user = ($stmt->fetch() ?: null);
    } catch (Throwable) {
        $stmt = db()->prepare('SELECT id,name,email,role,is_active FROM users WHERE id=? AND is_active=1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $legacy = $stmt->fetch();
        if ($legacy) {
            $legacy += [
                'permissions'=>null,'avatar_path'=>null,'whatsapp'=>null,
                'notify_email'=>1,'notify_whatsapp'=>0,'notify_inapp'=>1,
                'alert_days'=>'1','two_factor_enabled'=>0,'two_factor_method'=>'email','totp_secret'=>null,'backup_codes'=>null,'password_changed_at'=>null,'last_login_ip'=>null,'last_login_user_agent'=>null,'language'=>'es','department'=>'',
            ];
        }
        return $user = ($legacy ?: null);
    }
}

function permission_catalog(): array
{
    return [
        'General' => [
            'dashboard.view' => 'Ver dashboard',
            'notifications.view' => 'Ver notificaciones internas',
            'manual.view' => 'Ver manual de uso',
        ],
        'Clientes' => [
            'clients.view' => 'Ver clientes',
            'clients.create' => 'Crear clientes',
            'clients.edit' => 'Editar clientes',
            'clients.delete' => 'Eliminar clientes',
        ],
        'Servicios de clientes' => [
            'services.view' => 'Ver servicios y vencimientos',
            'services.assign' => 'Asignar servicios a clientes',
            'services.edit' => 'Editar servicios asignados a clientes',
            'services.renew' => 'Renovar servicios',
            'services.delete' => 'Eliminar servicios asignados a clientes',
        ],
        'Catálogo de cotizaciones' => [
            'catalog.view' => 'Ver catálogo de productos y servicios',
            'catalog.manage' => 'Crear, editar, activar, desactivar y eliminar productos/servicios',
            'catalog_categories.view' => 'Ver categorías del catálogo',
            'catalog_categories.manage' => 'Crear, editar, activar, desactivar y eliminar categorías',
        ],
        'Formas de pago' => [
            'payment_methods.view' => 'Ver formas de pago',
            'payment_methods.manage' => 'Crear, editar y desactivar formas de pago',
            'payment_methods.send' => 'Enviar formas de pago por email o WhatsApp',
        ],
        'Documentos' => [
            'documents.view' => 'Ver documentos, carpetas, versiones e historial',
            'documents.upload' => 'Subir documentos y crear nuevas versiones',
            'documents.edit' => 'Editar metadatos, relaciones y permisos',
            'documents.delete' => 'Enviar documentos a papelera y restaurarlos',
            'documents.share' => 'Compartir documentos con enlaces protegidos',
            'documents.approve' => 'Aprobar o rechazar documentos',
            'documents.manage' => 'Administrar carpetas, permisos y rutas de aprobación',
            'documents.audit' => 'Ver auditoría documental',
        ],
        'Contratos' => [
            'contracts.view' => 'Ver contratos',
            'contracts.create' => 'Crear contratos',
            'contracts.edit' => 'Editar contratos',
            'contracts.send' => 'Enviar contratos al cliente',
            'contracts.status' => 'Cambiar estado de contratos',
            'contract_templates.view' => 'Ver plantillas de contratos',
            'contract_templates.manage' => 'Crear, editar y desactivar plantillas',
        ],
        'Cotizaciones' => [
            'quotes.view' => 'Ver cotizaciones',
            'quotes.create' => 'Crear cotizaciones',
            'quotes.edit' => 'Editar cotizaciones',
            'quotes.send' => 'Enviar cotizaciones por email',
            'quotes.status' => 'Cambiar estado de cotizaciones',
            'quotes.delete' => 'Eliminar cotizaciones',
        ],
        'Facturación e ingresos' => [
            'billing.view' => 'Abrir sistemas de facturación',
            'payments.view' => 'Ver ingresos registrados',
            'payments.manage' => 'Registrar y editar ingresos',
        ],
        'Soporte' => [
            'tickets.view' => 'Ver tickets propios',
            'tickets.create' => 'Crear tickets de soporte',
            'tickets.manage' => 'Administrar todos los tickets',
        ],
        'Contactos y actividades' => [
            'contacts.view' => 'Ver contactos',
            'contacts.manage' => 'Crear, editar e importar contactos',
            'activities.view' => 'Ver actividades',
            'activities.manage' => 'Crear, editar y completar actividades',
        ],
        'Negocios y pipeline' => [
            'deals.view' => 'Ver negocios y Kanban',
            'deals.manage' => 'Crear, editar y mover negocios',
            'deals.transfer' => 'Transferir negocios y cotizaciones',
        ],
        'Marketing y leads' => [
            'mailing.view' => 'Ver campañas de mailing',
            'mailing.manage' => 'Crear y enviar campañas de mailing',
            'leads.view' => 'Ver campañas y leads',
            'leads.manage' => 'Administrar campañas de generación de leads',
            'forms.view' => 'Ver formularios de captación',
            'forms.manage' => 'Crear formularios y administrar respuestas',
        ],
        'Gestión de proyectos' => [
            'projects.view' => 'Ver proyectos y dashboard de proyectos',
            'projects.manage' => 'Crear y editar proyectos, hitos y miembros',
            'project_tasks.view' => 'Ver tareas, Kanban, Gantt y calendario',
            'project_tasks.manage' => 'Crear, editar, mover y completar tareas',
            'project_time.view' => 'Ver horas registradas',
            'project_time.manage' => 'Registrar y editar horas',
            'project_finance.view' => 'Ver presupuesto, costos y gastos',
            'project_finance.manage' => 'Registrar gastos del proyecto',
            'project_risks.view' => 'Ver riesgos del proyecto',
            'project_risks.manage' => 'Crear y gestionar riesgos',
        ],
        'Agenda e informes' => [
            'appointments.view' => 'Ver citas y reuniones',
            'appointments.manage' => 'Programar y editar reuniones',
            'reports.view' => 'Ver panel de informes',
            'reports.export' => 'Exportar informes',
            'dashboard.customize' => 'Personalizar el dashboard',
        ],
        'Datos y ayuda' => [
            'imports.view' => 'Ver importación y exportación',
            'imports.manage' => 'Importar y respaldar datos',
            'assistant.use' => 'Usar el asistente del CRM',
        ],
        'Administración' => [
            'users.view' => 'Ver usuarios',
            'users.manage' => 'Crear, editar, activar y desactivar usuarios',
            'settings.manage' => 'Administrar correo, alertas y configuración',
            'security.view' => 'Ver el Centro de Seguridad y auditoría',
            'security.manage' => 'Administrar políticas, sesiones e integridad',
        ],
    ];
}

function all_permission_keys(): array
{
    $keys = [];
    foreach (permission_catalog() as $permissions) $keys = array_merge($keys, array_keys($permissions));
    return $keys;
}

function default_permissions_for_role(string $role): array
{
    $all = all_permission_keys();
    return match ($role) {
        'admin' => $all,
        'manager' => array_values(array_filter($all, fn(string $p): bool => !in_array($p, ['users.manage','settings.manage','security.manage'], true))),
        'sales' => [
            'dashboard.view','notifications.view','manual.view',
            'clients.view','clients.create','clients.edit',
            'services.view','services.assign','services.edit','services.renew',
            'catalog.view','catalog_categories.view','payment_methods.view','payment_methods.send',
            'contracts.view','contracts.create','contracts.edit','contracts.send','contracts.status','contract_templates.view',
            'quotes.view','quotes.create','quotes.edit','quotes.send','quotes.status',
            'billing.view','payments.view','tickets.view','tickets.create',
            'contacts.view','contacts.manage','activities.view','activities.manage','deals.view','deals.manage',
            'mailing.view','leads.view','forms.view','appointments.view','appointments.manage','reports.view','dashboard.customize','imports.view','assistant.use',
            'projects.view','project_tasks.view','project_tasks.manage','project_time.view','project_time.manage','project_risks.view','documents.view','documents.upload','documents.edit','documents.share','documents.approve',
        ],
        'viewer' => [
            'dashboard.view','notifications.view','manual.view','clients.view','services.view',
            'catalog.view','catalog_categories.view','payment_methods.view','payment_methods.send',
            'contracts.view','contracts.create','contracts.edit','contracts.send','contracts.status','contract_templates.view','quotes.view','billing.view','tickets.view','tickets.create',
            'contacts.view','activities.view','deals.view','appointments.view','reports.view','imports.view','assistant.use',
            'projects.view','project_tasks.view','project_time.view','project_risks.view','documents.view',
        ],
        default => ['dashboard.view','manual.view'],
    };
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrador',
        'manager' => 'Gestor',
        'sales' => 'Comercial',
        'viewer' => 'Solo lectura',
        'custom' => 'Personalizado',
        default => ucfirst($role),
    };
}

function user_permission_list(?array $user = null): array
{
    $user ??= current_user();
    if (!$user) return [];
    if (($user['role'] ?? '') === 'admin') return all_permission_keys();
    $decoded = json_decode((string)($user['permissions'] ?? ''), true);
    if (!is_array($decoded)) return default_permissions_for_role((string)($user['role'] ?? 'viewer'));
    return array_values(array_intersect(all_permission_keys(), array_map('strval', $decoded)));
}

function has_permission(string $permission): bool
{
    $user = current_user();
    if (!$user) return false;
    if (($user['role'] ?? '') === 'admin') return true;
    return in_array($permission, user_permission_list($user), true);
}

function has_any_permission(array $permissions): bool
{
    foreach ($permissions as $permission) if (has_permission($permission)) return true;
    return false;
}

function require_permission(string $permission): void
{
    require_login();
    if (!has_permission($permission)) {
        http_response_code(403);
        exit('<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso restringido</title><body style="font-family:Arial;padding:40px"><h1>Acceso restringido</h1><p>Tu usuario no tiene permiso para realizar esta acción.</p><p><a href="dashboard.php">Volver al CRM</a></p></body></html>');
    }
}
