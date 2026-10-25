<?php
declare(strict_types=1);

function crm_client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function crm_user_agent(): string
{
    return crm_strimwidth((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'), 500, '');
}

function crm_security_headers(): void
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-site');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline'; connect-src 'self'; media-src 'self' data: blob:");
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header('Cache-Control: no-store, private');
}

function crm_argon2id_available(): bool
{
    return defined('PASSWORD_ARGON2ID')
        && function_exists('password_algos')
        && in_array('argon2id', password_algos(), true);
}

function crm_argon2id_options(): array
{
    // Algunos proveedores compilan Argon2 sin soporte para varios hilos.
    // Un solo hilo mantiene Argon2id compatible y evita ValueError en esos servidores.
    return [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 1,
    ];
}

function crm_password_hash(string $password): string
{
    if (crm_argon2id_available()) {
        try {
            $hash = password_hash($password, PASSWORD_ARGON2ID, crm_argon2id_options());
            if (is_string($hash) && $hash !== '') return $hash;
        } catch (Throwable $e) {
            // Continúa con bcrypt cuando la implementación Argon2 del hosting es limitada.
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    if (!is_string($hash) || $hash === '') {
        throw new RuntimeException('El servidor no pudo generar una contraseña segura.');
    }
    return $hash;
}

function crm_password_needs_rehash(string $hash): bool
{
    if (crm_argon2id_available()) {
        try {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, crm_argon2id_options());
        } catch (Throwable $e) {
            // Si Argon2 falla en este hosting, conserva o migra a bcrypt.
        }
    }
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

function crm_password_errors(string $password, ?string $email = null, ?string $name = null): array
{
    $errors = [];
    $minimum = max(12, (int)setting('password_min_length', '12'));
    if (strlen($password) < $minimum) $errors[] = "La contraseña debe tener al menos {$minimum} caracteres.";
    if (strlen($password) > 128) $errors[] = 'La contraseña no puede superar 128 caracteres.';
    $normalized = crm_lower(trim($password));
    $common = ['password','password123','123456789','1234567890','qwerty123','admin123','socrates123','contraseña','bienvenido','welcome123'];
    if (in_array($normalized, $common, true)) $errors[] = 'La contraseña es demasiado común.';
    foreach ([$email ? strstr($email, '@', true) : '', $name] as $piece) {
        $piece = crm_lower(trim((string)$piece));
        if (strlen($piece) >= 4 && str_contains($normalized, $piece)) {
            $errors[] = 'La contraseña no debe contener tu nombre o email.';
            break;
        }
    }
    return array_values(array_unique($errors));
}

function crm_security_audit(string $event, string $severity = 'info', ?string $details = null, ?string $targetType = null, ?int $targetId = null, ?int $userId = null): void
{
    try {
        if (!db_table_exists('security_audit_logs')) return;
        $userId ??= !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        db()->prepare('INSERT INTO security_audit_logs(user_id,event,severity,target_type,target_id,details,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())')
            ->execute([$userId,$event,$severity,$targetType,$targetId,$details,crm_client_ip(),crm_user_agent()]);
    } catch (Throwable) {}
}

function crm_login_rate_status(string $email): array
{
    $max = max(3, (int)setting('login_max_attempts', '5'));
    $minutes = max(5, (int)setting('login_lock_minutes', '15'));
    try {
        if (!db_table_exists('login_attempts')) return ['allowed'=>true,'retry_after'=>0];
        $identifier = hash('sha256', crm_lower(trim($email)));
        $stmt = db()->prepare("SELECT COUNT(*) attempts, MIN(attempted_at) first_at FROM login_attempts WHERE identifier_hash=? AND ip_address=? AND success=0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)");
        $stmt->execute([$identifier,crm_client_ip()]);
        $row = $stmt->fetch() ?: [];
        $ipStmt=db()->prepare("SELECT COUNT(*) attempts,MIN(attempted_at) first_at FROM login_attempts WHERE ip_address=? AND success=0 AND attempted_at>=DATE_SUB(NOW(),INTERVAL {$minutes} MINUTE)");
        $ipStmt->execute([crm_client_ip()]);$ipRow=$ipStmt->fetch()?:[];
        $identifierBlocked=(int)($row['attempts']??0)>=$max;$ipBlocked=(int)($ipRow['attempts']??0)>=($max*4);
        if(!$identifierBlocked&&!$ipBlocked)return ['allowed'=>true,'retry_after'=>0];
        $first = strtotime((string)(($identifierBlocked?$row['first_at']:$ipRow['first_at']) ?? 'now')) ?: time();
        $retry = max(60, ($first + ($minutes * 60)) - time());
        return ['allowed'=>false,'retry_after'=>$retry];
    } catch (Throwable) {
        return ['allowed'=>true,'retry_after'=>0];
    }
}

function crm_record_login_attempt(string $email, bool $success, ?int $userId = null): void
{
    try {
        if (db_table_exists('login_attempts')) {
            db()->prepare('INSERT INTO login_attempts(identifier_hash,ip_address,user_id,success,user_agent,attempted_at) VALUES (?,?,?,?,?,NOW())')
                ->execute([hash('sha256', crm_lower(trim($email))),crm_client_ip(),$userId,$success?1:0,crm_user_agent()]);
            if (random_int(1, 30) === 1) db()->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        }
        if ($userId && db_table_exists('users')) {
            if ($success) {
                db()->prepare('UPDATE users SET failed_login_count=0,locked_until=NULL,last_login_ip=?,last_login_user_agent=? WHERE id=?')
                    ->execute([crm_client_ip(),crm_user_agent(),$userId]);
            } else {
                $max = max(3, (int)setting('login_max_attempts', '5'));
                $minutes = max(5, (int)setting('login_lock_minutes', '15'));
                db()->prepare("UPDATE users SET failed_login_count=failed_login_count+1, locked_until=CASE WHEN failed_login_count+1>=? THEN DATE_ADD(NOW(), INTERVAL {$minutes} MINUTE) ELSE locked_until END WHERE id=?")
                    ->execute([$max,$userId]);
                $countStmt=db()->prepare('SELECT failed_login_count,email,name FROM users WHERE id=?');$countStmt->execute([$userId]);$lockedUser=$countStmt->fetch();
                if($lockedUser&&(int)$lockedUser['failed_login_count']===$max&&function_exists('send_system_email')){
                    $alert=trim((string)setting('security_alert_email','info@socrates.la'));
                    if(filter_var($alert,FILTER_VALIDATE_EMAIL)){
                        send_system_email($alert,'Alerta de seguridad · cuenta bloqueada','<div style="font-family:Arial,sans-serif"><h2 style="color:#002f57">Cuenta bloqueada temporalmente</h2><p><strong>Usuario:</strong> '.e((string)$lockedUser['name']).' · '.e((string)$lockedUser['email']).'</p><p><strong>IP:</strong> '.e(crm_client_ip()).'</p><p>Se alcanzó el límite de intentos fallidos y la cuenta fue bloqueada durante '.(int)$minutes.' minutos.</p></div>');
                    }
                }
            }
        }
    } catch (Throwable) {}
    crm_security_audit($success ? 'login_success' : 'login_failed', $success ? 'info' : 'warning', 'Intento de acceso', 'user', $userId, $userId);
}

function crm_register_session(int $userId): void
{
    $_SESSION['crm_login_at'] = time();
    $_SESSION['crm_last_activity'] = time();
    $_SESSION['crm_session_token'] = hash('sha256', session_id());
    try {
        if (!db_table_exists('user_sessions')) return;
        $hours=max(1,(int)setting('session_absolute_hours','8'));
        db()->prepare("INSERT INTO user_sessions(user_id,session_hash,ip_address,user_agent,started_at,last_activity_at,expires_at,is_revoked) VALUES (?,?,?,?,NOW(),NOW(),DATE_ADD(NOW(), INTERVAL {$hours} HOUR),0) ON DUPLICATE KEY UPDATE ip_address=VALUES(ip_address),user_agent=VALUES(user_agent),last_activity_at=NOW(),expires_at=VALUES(expires_at),is_revoked=0")
            ->execute([$userId,$_SESSION['crm_session_token'],crm_client_ip(),crm_user_agent()]);
    } catch (Throwable) {}
}

function crm_revoke_current_session(): void
{
    try {
        if (db_table_exists('user_sessions')) {
            $hash = (string)($_SESSION['crm_session_token'] ?? hash('sha256', session_id()));
            db()->prepare('UPDATE user_sessions SET is_revoked=1,revoked_at=NOW() WHERE session_hash=?')->execute([$hash]);
        }
    } catch (Throwable) {}
}

function crm_revoke_user_sessions(int $userId, ?string $exceptHash = null): void
{
    if (!db_table_exists('user_sessions')) return;
    if ($exceptHash) db()->prepare('UPDATE user_sessions SET is_revoked=1,revoked_at=NOW() WHERE user_id=? AND session_hash<>?')->execute([$userId,$exceptHash]);
    else db()->prepare('UPDATE user_sessions SET is_revoked=1,revoked_at=NOW() WHERE user_id=?')->execute([$userId]);
    crm_security_audit('sessions_revoked','warning','Sesiones cerradas remotamente','user',$userId);
}

function crm_validate_session(): bool
{
    if (empty($_SESSION['user_id'])) return false;
    $now = time();
    $idle = max(5, (int)setting('session_idle_minutes','30')) * 60;
    $absolute = max(1, (int)setting('session_absolute_hours','8')) * 3600;
    $last = (int)($_SESSION['crm_last_activity'] ?? $now);
    $loginAt = (int)($_SESSION['crm_login_at'] ?? $now);
    if (($now - $last) > $idle || ($now - $loginAt) > $absolute) {
        crm_security_audit('session_expired','info','Sesión expirada por política');
        crm_revoke_current_session();
        return false;
    }
    try {
        if (db_table_exists('user_sessions')) {
            if (empty($_SESSION['crm_session_token'])) crm_register_session((int)$_SESSION['user_id']);
            $hash = (string)($_SESSION['crm_session_token'] ?? hash('sha256', session_id()));
            $stmt = db()->prepare('SELECT is_revoked,expires_at,user_agent FROM user_sessions WHERE user_id=? AND session_hash=? LIMIT 1');
            $stmt->execute([(int)$_SESSION['user_id'],$hash]);
            $session = $stmt->fetch();
            if (!$session || (int)$session['is_revoked'] === 1 || (!empty($session['expires_at']) && strtotime((string)$session['expires_at']) < $now)) return false;
            if (!hash_equals(hash('sha256',(string)$session['user_agent']),hash('sha256',crm_user_agent()))) {
                crm_security_audit('session_user_agent_changed','warning','Cambió el navegador durante la sesión');
            }
            if (($now - $last) >= 60) db()->prepare('UPDATE user_sessions SET last_activity_at=NOW(),ip_address=? WHERE session_hash=?')->execute([crm_client_ip(),$hash]);
        }
    } catch (Throwable) {}
    $_SESSION['crm_last_activity'] = $now;
    return true;
}

function crm_base32_encode(string $data): string
{
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits=''; $out='';
    foreach (str_split($data) as $char) $bits .= str_pad(decbin(ord($char)),8,'0',STR_PAD_LEFT);
    foreach (str_split($bits,5) as $chunk) { $chunk=str_pad($chunk,5,'0',STR_PAD_RIGHT); $out.=$alphabet[bindec($chunk)]; }
    return $out;
}

function crm_base32_decode(string $input): string
{
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $input=strtoupper(preg_replace('/[^A-Z2-7]/i','',$input) ?? ''); $bits=''; $out='';
    foreach (str_split($input) as $char) { $pos=strpos($alphabet,$char); if($pos===false) continue; $bits.=str_pad(decbin($pos),5,'0',STR_PAD_LEFT); }
    foreach (str_split($bits,8) as $chunk) { if(strlen($chunk)<8) break; $out.=chr(bindec($chunk)); }
    return $out;
}

function crm_totp_secret(): string { return crm_base32_encode(random_bytes(20)); }

function crm_totp_code(string $secret, ?int $timestamp = null): string
{
    $decoded = crm_base32_decode($secret);
    if ($decoded === '') return '';
    $counter = intdiv(max(0, $timestamp ?? time()), 30);
    // TOTP/HOTP requiere un contador de 8 bytes en big-endian. N2 es más portable entre hostings.
    $binary = pack('N2', 0, $counter);
    $hash = hash_hmac('sha1', $binary, $decoded, true);
    $offset = ord($hash[19]) & 0x0f;
    $value = ((ord($hash[$offset]) & 0x7f) << 24)
        | ((ord($hash[$offset + 1]) & 0xff) << 16)
        | ((ord($hash[$offset + 2]) & 0xff) << 8)
        | (ord($hash[$offset + 3]) & 0xff);
    return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
}

function crm_totp_verify(string $secret, string $code, int $window = 1, int $timeOffsetSeconds = 0): bool
{
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== 6) return false;
    $baseTime = time() + $timeOffsetSeconds;
    for ($i = -$window; $i <= $window; $i++) {
        $expected = crm_totp_code($secret, $baseTime + ($i * 30));
        if ($expected !== '' && hash_equals($expected, $code)) return true;
    }
    return false;
}

function crm_totp_find_time_offset(string $secret, string $code, int $maxSteps = 120): ?int
{
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== 6) return null;
    for ($distance = 0; $distance <= $maxSteps; $distance++) {
        $steps = $distance === 0 ? [0] : [-$distance, $distance];
        foreach ($steps as $step) {
            $offset = $step * 30;
            $expected = crm_totp_code($secret, time() + $offset);
            if ($expected !== '' && hash_equals($expected, $code)) return $offset;
        }
    }
    return null;
}

function crm_totp_user_time_offset(int $userId): int
{
    $offset = (int)setting('totp_time_offset_user_' . $userId, '0');
    return max(-3600, min(3600, $offset));
}

function crm_totp_uri(string $secret, string $email): string
{
    $issuer='Socrates ONE CRM';
    return 'otpauth://totp/'.rawurlencode($issuer.':'.$email).'?secret='.rawurlencode($secret).'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
}

function crm_generate_backup_codes(int $count = 10): array
{
    $plain=[]; $hashes=[];
    for($i=0;$i<$count;$i++){ $code=strtoupper(substr(bin2hex(random_bytes(5)),0,10)); $formatted=substr($code,0,5).'-'.substr($code,5); $plain[]=$formatted; $hashes[]=password_hash(str_replace('-','',$formatted),PASSWORD_DEFAULT); }
    return ['plain'=>$plain,'hashes'=>$hashes];
}

function crm_consume_backup_code(int $userId, string $code): bool
{
    try {
        $stmt=db()->prepare('SELECT backup_codes FROM users WHERE id=?');$stmt->execute([$userId]);$hashes=json_decode((string)$stmt->fetchColumn(),true);
        if(!is_array($hashes)) return false; $normalized=strtoupper(str_replace(['-',' '],'',$code));
        foreach($hashes as $i=>$hash){ if(password_verify($normalized,(string)$hash)){ unset($hashes[$i]); db()->prepare('UPDATE users SET backup_codes=? WHERE id=?')->execute([json_encode(array_values($hashes)),$userId]); crm_security_audit('backup_code_used','warning','Se usó un código de recuperación','user',$userId,$userId); return true; }}
    } catch(Throwable) {}
    return false;
}

function crm_integrity_files(): array
{
    $root=dirname(__DIR__); $files=[];
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file){ if(!$file->isFile()) continue; $path=$file->getPathname(); $relative=str_replace('\\','/',substr($path,strlen($root)+1));
        if(preg_match('~^(uploads|storage|vendor)/~',$relative) || $relative==='includes/config.php' || str_ends_with($relative,'.zip')) continue;
        if(!preg_match('/\.(php|js|css|sql)$/i',$relative)) continue; $files[$relative]=hash_file('sha256',$path); }
    ksort($files); return $files;
}

function crm_integrity_save(): array
{
    $dir=dirname(__DIR__).'/storage/security'; if(!is_dir($dir)) @mkdir($dir,0750,true); $files=crm_integrity_files();
    file_put_contents($dir.'/integrity.json',json_encode(['created_at'=>date(DATE_ATOM),'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);
    crm_security_audit('integrity_baseline_created','warning','Nueva línea base de integridad: '.count($files).' archivos');
    return ['ok'=>true,'count'=>count($files)];
}

function crm_integrity_check(): array
{
    $path=dirname(__DIR__).'/storage/security/integrity.json'; if(!is_file($path)) return ['ok'=>false,'baseline'=>false,'changed'=>[],'missing'=>[],'new'=>[]];
    $stored=json_decode((string)file_get_contents($path),true); $old=is_array($stored['files']??null)?$stored['files']:[]; $now=crm_integrity_files();
    $changed=[];foreach($old as$p=>$h)if(isset($now[$p])&&!hash_equals((string)$h,(string)$now[$p]))$changed[]=$p;
    $missing=array_values(array_diff(array_keys($old),array_keys($now)));$new=array_values(array_diff(array_keys($now),array_keys($old)));
    return ['ok'=>!$changed&&!$missing,'baseline'=>true,'changed'=>$changed,'missing'=>$missing,'new'=>$new,'created_at'=>$stored['created_at']??null];
}

function crm_security_score(): array
{
    $checks=[];
    $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https');
    $checks[]=['label'=>'HTTPS activo','ok'=>$https,'weight'=>15];
    $checks[]=['label'=>'SMTP autenticado','ok'=>(string)setting('mail_mode','mail')==='smtp','weight'=>8];
    $checks[]=['label'=>'Bloqueo de fuerza bruta','ok'=>(int)setting('login_max_attempts','5')<=5,'weight'=>12];
    $checks[]=['label'=>'Sesiones con vencimiento','ok'=>(int)setting('session_idle_minutes','30')<=30,'weight'=>10];
    $checks[]=['label'=>'Contraseñas de 12+ caracteres','ok'=>(int)setting('password_min_length','12')>=12,'weight'=>10];
    $checks[]=['label'=>'Clave privada fuerte','ok'=>strlen((string)config('app_key',''))>=48,'weight'=>10];
    $checks[]=['label'=>'Instalador eliminado o bloqueado','ok'=>!is_file(dirname(__DIR__).'/install.php'),'weight'=>8];
    $checks[]=['label'=>'Línea base de integridad','ok'=>is_file(dirname(__DIR__).'/storage/security/integrity.json'),'weight'=>10];
    $admin2fa=true;
    try{ $admin2fa=(int)db()->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1 AND (two_factor_enabled=0 OR two_factor_method<>'totp')")->fetchColumn()===0; }catch(Throwable){$admin2fa=false;}
    $checks[]=['label'=>'Administradores con TOTP','ok'=>$admin2fa,'weight'=>17];
    $score=0;foreach($checks as$c)if($c['ok'])$score+=$c['weight'];
    return ['score'=>min(100,$score),'checks'=>$checks];
}

function crm_database_backup(): array
{
    $root=dirname(__DIR__);$dir=$root.'/storage/backups';if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('No fue posible crear la carpeta de respaldos.');
    $sql="-- SÓCRATES CRM encrypted database backup\n-- Created: ".date(DATE_ATOM)."\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n";
    $tables=db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach($tables as$table){
        $safe=str_replace('`','``',(string)$table);$create=db()->query("SHOW CREATE TABLE `{$safe}`")->fetch();$createSql=(string)(array_values($create)[1]??'');
        $sql.="DROP TABLE IF EXISTS `{$safe}`;\n{$createSql};\n";
        $stmt=db()->query("SELECT * FROM `{$safe}`",PDO::FETCH_ASSOC);
        while($row=$stmt->fetch()){
            $columns=array_map(fn($c)=>'`'.str_replace('`','``',(string)$c).'`',array_keys($row));
            $values=[];foreach($row as$value)$values[]=$value===null?'NULL':db()->quote((string)$value);
            $sql.='INSERT INTO `'.$safe.'` ('.implode(',',$columns).') VALUES ('.implode(',',$values).');' . "\n";
            if(strlen($sql)>100*1024*1024)throw new RuntimeException('El respaldo supera el límite interno de 100 MB. Usa mysqldump desde el servidor.');
        }
        $sql.="\n";
    }
    $sql.="SET FOREIGN_KEY_CHECKS=1;\n";$compressed=function_exists('gzencode')?gzencode($sql,9):$sql;$format=function_exists('gzencode')?'sql.gz':'sql';
    $encrypted=encrypt_secret($compressed);$name='crm-db-'.date('Ymd-His').'.crmbackup';$path=$dir.'/'.$name;
    $payload=json_encode(['version'=>1,'created_at'=>date(DATE_ATOM),'format'=>$format,'data'=>$encrypted],JSON_UNESCAPED_SLASHES);
    if(file_put_contents($path,$payload,LOCK_EX)===false)throw new RuntimeException('No fue posible guardar el respaldo.');@chmod($path,0640);
    set_setting('security_backup_last_run',date(DATE_ATOM));crm_security_audit('database_backup_created','warning','Respaldo cifrado creado: '.$name);
    return ['name'=>$name,'path'=>$path,'size'=>filesize($path)?:0];
}

function crm_backup_files(): array
{
    $dir=dirname(__DIR__).'/storage/backups';if(!is_dir($dir))return[];$files=[];
    foreach(glob($dir.'/crm-db-*.crmbackup')?:[] as$path)$files[]=['name'=>basename($path),'size'=>filesize($path)?:0,'modified_at'=>date('Y-m-d H:i:s',filemtime($path)?:time())];
    usort($files,fn($a,$b)=>strcmp($b['name'],$a['name']));return$files;
}

function crm_backup_payload(string $name): array
{
    if(!preg_match('/^crm-db-\d{8}-\d{6}\.crmbackup$/',$name))throw new RuntimeException('Nombre de respaldo inválido.');
    $path=dirname(__DIR__).'/storage/backups/'.$name;if(!is_file($path))throw new RuntimeException('Respaldo no encontrado.');
    $payload=json_decode((string)file_get_contents($path),true);if(!is_array($payload)||empty($payload['data']))throw new RuntimeException('Respaldo inválido.');
    $raw=decrypt_secret((string)$payload['data']);if($raw==='')throw new RuntimeException('No fue posible descifrar el respaldo.');
    return ['data'=>$raw,'format'=>(string)($payload['format']??'sql'),'created_at'=>$payload['created_at']??null];
}
