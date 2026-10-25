<?php
function current_user(): ?array {
    static $user = false;
    if ($user !== false) return $user ?: null;
    if (empty($_SESSION['user_id'])) { $user=null; return null; }
    $st=db()->prepare('SELECT u.*, t.name tenant_name FROM users u JOIN tenants t ON t.id=u.tenant_id WHERE u.id=? AND u.active=1');
    $st->execute([$_SESSION['user_id']]);
    $user=$st->fetch() ?: null;
    return $user;
}
function require_login(): void { if (!current_user()) redirect('index.php?route=login'); }
function require_admin(): void { require_login(); if (!is_admin()) { http_response_code(403); exit('No autorizado'); } }
function attempt_login(string $email,string $password): bool {
    $st=db()->prepare('SELECT * FROM users WHERE email=? AND active=1 LIMIT 1'); $st->execute([strtolower(trim($email))]); $u=$st->fetch();
    if ($u && password_verify($password,$u['password_hash'])) { $_SESSION['user_id']=$u['id']; session_regenerate_id(true); return true; }
    return false;
}
