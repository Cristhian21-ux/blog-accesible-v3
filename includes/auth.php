<?php
declare(strict_types=1);

const LOGIN_WINDOW_SECONDS = 900; // ventana de 15 minutos
const LOGIN_MAX_PER_USER   = 5;   // fallos por IP + usuario
const LOGIN_MAX_PER_IP     = 20;  // fallos por IP en total

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : 'unknown';
}

function login_key(string $username): string
{
    return str_cut(str_lower($username), 64);
}

function count_failures(PDO $pdo, string $ip, ?string $username, int $since): int
{
    if ($username === null) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?');
        $st->execute([$ip, $since]);
    } else {
        $st = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND username = ? AND attempted_at >= ?');
        $st->execute([$ip, $username, $since]);
    }
    return (int) $st->fetchColumn();
}

function login_blocked(PDO $pdo, string $ip, string $username, ?int $now = null): bool
{
    $since = ($now ?? time()) - LOGIN_WINDOW_SECONDS;
    return count_failures($pdo, $ip, null, $since) >= LOGIN_MAX_PER_IP
        || count_failures($pdo, $ip, login_key($username), $since) >= LOGIN_MAX_PER_USER;
}

function record_login_failure(PDO $pdo, string $ip, string $username, ?int $now = null): void
{
    $now ??= time();
    $pdo->prepare('INSERT INTO login_attempts(ip, username, attempted_at) VALUES(?,?,?)')
        ->execute([$ip, login_key($username), $now]);
    $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')
        ->execute([$now - LOGIN_WINDOW_SECONDS * 4]);
}

function clear_login_failures(PDO $pdo, string $ip, string $username): void
{
    $pdo->prepare('DELETE FROM login_attempts WHERE ip = ? AND username = ?')
        ->execute([$ip, login_key($username)]);
}

/** Hash falso para gastar el mismo tiempo cuando el usuario no existe (evita adivinar usuarios por tiempo). */
function decoy_hash(): string
{
    static $hash = null;
    $hash ??= password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    return $hash;
}

/** @return array{status: string, user: ?array} status: ok | invalid | blocked */
function attempt_login(PDO $pdo, string $ip, string $username, string $password, ?int $now = null): array
{
    if (login_blocked($pdo, $ip, $username, $now)) {
        return ['status' => 'blocked', 'user' => null];
    }
    $st = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
    $st->execute([$username]);
    $user = $st->fetch();

    $valid = password_verify($password, $user === false ? decoy_hash() : (string) $user['password_hash']);
    if ($user === false || !$valid) {
        record_login_failure($pdo, $ip, $username, $now);
        return ['status' => 'invalid', 'user' => null];
    }
    clear_login_failures($pdo, $ip, $username);
    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    return ['status' => 'ok', 'user' => $user];
}

/** Crea el primer administrador. Devuelve false si ya existía algún usuario (evita carreras). */
function create_first_admin(PDO $pdo, string $username, string $password): bool
{
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        if (has_users($pdo)) {
            $pdo->exec('ROLLBACK');
            return false;
        }
        $pdo->prepare('INSERT INTO users(username, password_hash) VALUES(?, ?)')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $pdo->exec('COMMIT');
        return true;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
        throw $error;
    }
}

/** Cambia la contraseña de un usuario o lo crea (lo usa bin/create-admin.php). */
function set_user_password(PDO $pdo, string $username, string $password): void
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
    $st->execute([$hash, $username]);
    if ($st->rowCount() === 0) {
        $pdo->prepare('INSERT INTO users(username, password_hash) VALUES(?, ?)')->execute([$username, $hash]);
    }
}
