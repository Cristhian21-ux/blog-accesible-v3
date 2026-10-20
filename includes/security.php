<?php
declare(strict_types=1);

const SESSION_IDLE_SECONDS = 1800;  // se cierra tras 30 min sin actividad
const SESSION_MAX_SECONDS  = 28800; // y como máximo a las 8 horas

function is_https(): bool
{
    if (getenv('BLOG_FORCE_HTTPS') === '1') {
        return true;
    }
    $https = $_SERVER['HTTPS'] ?? '';
    return ($https !== '' && $https !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function csp_policy(): string
{
    return implode('; ', [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self'",
        "img-src 'self' https: data:",
        "media-src 'self' https:",
        "font-src 'self'",
        "connect-src 'self'",
        "manifest-src 'self'",
        "worker-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
    ]);
}

function send_security_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    header('Content-Security-Policy: ' . csp_policy());
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header_remove('X-Powered-By');
}

/** Páginas con sesión, formularios o mensajes: que el navegador no las guarde. */
function no_store(): void
{
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('Cache-Control: no-store');
    }
}

function app_cookie_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return ($dir === '.' || $dir === '') ? '/' : rtrim($dir, '/') . '/';
}

function start_secure_session(): void
{
    if (PHP_SAPI === 'cli') {
        $_SESSION ??= [];
        return;
    }
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('blogsid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => app_cookie_path(),
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Borra la sesión y su cookie. Con $restart deja una sesión nueva para mostrar avisos. */
function destroy_session(bool $restart = true): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (!headers_sent()) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $cookie['path'],
                'secure'   => $cookie['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        if ($restart) {
            session_start();
            session_regenerate_id(true);
        }
    }
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(mixed $token): bool
{
    $expected = $_SESSION['csrf'] ?? '';
    return is_string($token) && is_string($expected) && $expected !== '' && hash_equals($expected, $token);
}

function csrf_check(): void
{
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        abort(400, 'Solicitud no válida (token CSRF). Vuelve atrás, recarga la página e inténtalo de nuevo.');
    }
}

function login_user(int $userId): void
{
    if (PHP_SAPI !== 'cli') {
        session_regenerate_id(true);
    }
    $now = time();
    $_SESSION['uid'] = $userId;
    $_SESSION['started'] = $now;
    $_SESSION['last'] = $now;
    unset($_SESSION['csrf']);
}

function is_admin(?int $now = null): bool
{
    if (empty($_SESSION['uid'])) {
        return false;
    }
    $now ??= time();
    $idle = $now - (int) ($_SESSION['last'] ?? 0);
    $age  = $now - (int) ($_SESSION['started'] ?? 0);
    if ($idle > SESSION_IDLE_SECONDS || $age > SESSION_MAX_SECONDS) {
        destroy_session();
        return false;
    }
    $_SESSION['last'] = $now;
    return true;
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect(URL_LOGIN);
    }
}

function is_loopback(): bool
{
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

/** La creación del primer administrador solo se permite desde este mismo equipo. */
function setup_allowed(): bool
{
    return is_loopback() || getenv('BLOG_ALLOW_REMOTE_SETUP') === '1';
}
