<?php
/**
 * EMAILSEND ONE v0.9.54
 * Custom file-backed PHP session handler.
 *
 * Reason:
 * Some shared hosting/PHP-FPM environments force a global session.save_path.
 * EMAILSEND bypasses that global handler and stores session data in
 * /storage/sessions using its own SessionHandlerInterface implementation.
 */

final class EmailSendSessionHandler implements SessionHandlerInterface
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/\\');

        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0750, true);
        }
    }

    private function safeId(string $id): string
    {
        return preg_replace('/[^A-Za-z0-9,-]/', '', $id) ?: '';
    }

    public function pathForId(string $id): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . 'sess_' . $this->safeId($id);
    }

    public function open(string $path, string $name): bool
    {
        return is_dir($this->dir) && is_writable($this->dir);
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $file = $this->pathForId($id);

        if (!is_file($file)) {
            return '';
        }

        $fp = @fopen($file, 'rb');
        if (!$fp) {
            return '';
        }

        $data = '';
        if (@flock($fp, LOCK_SH)) {
            $data = stream_get_contents($fp);
            @flock($fp, LOCK_UN);
        }
        fclose($fp);

        return is_string($data) ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        $file = $this->pathForId($id);

        $fp = @fopen($file, 'c+b');
        if (!$fp) {
            return false;
        }

        $ok = false;
        if (@flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            $written = fwrite($fp, $data);
            fflush($fp);
            @flock($fp, LOCK_UN);
            $ok = ($written !== false);
        }

        fclose($fp);
        @chmod($file, 0600);

        return $ok;
    }

    public function destroy(string $id): bool
    {
        $file = $this->pathForId($id);
        return !is_file($file) || @unlink($file);
    }

    public function gc(int $max_lifetime): int|false
    {
        $deleted = 0;
        $limit = time() - max(300, $max_lifetime);

        foreach (glob($this->dir . DIRECTORY_SEPARATOR . 'sess_*') ?: [] as $file) {
            if (is_file($file) && @filemtime($file) < $limit && @unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

/**
 * Starts EMAILSEND's isolated session.
 * Returns the active custom handler instance.
 */
function emailsend_start_session(): EmailSendSessionHandler
{
    static $handler = null;

    if ($handler instanceof EmailSendSessionHandler && session_status() === PHP_SESSION_ACTIVE) {
        return $handler;
    }

    $dir = dirname(__DIR__) . '/storage/sessions';

    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    $handler = new EmailSendSessionHandler($dir);

    // Do not rely on Ferozo's global files handler or session.save_path.
    @ini_set('session.use_cookies', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.use_strict_mode', '0');
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    @ini_set('session.gc_maxlifetime', '7200');

    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    if ($https) {
        @ini_set('session.cookie_secure', '1');
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('EMAILSENDDEMOID');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Explicitly re-use the host-only cookie if PHP-FPM did not bind it yet.
        if (!empty($_COOKIE['EMAILSENDDEMOID'])
            && preg_match('/^[A-Za-z0-9,-]{16,128}$/', (string)$_COOKIE['EMAILSENDDEMOID'])) {
            session_id((string)$_COOKIE['EMAILSENDDEMOID']);
        }

        session_set_save_handler($handler, true);

        if (!session_start()) {
            throw new RuntimeException('No fue posible iniciar la sesión aislada de EMAILSEND.');
        }
    }

    return $handler;
}
