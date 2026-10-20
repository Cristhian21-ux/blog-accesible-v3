<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/news_admin.php';

/** En producción no se muestran errores al visitante; se guardan en el registro de PHP. */
function configure_errors(): void
{
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('display_errors', getenv('BLOG_DEBUG') === '1' ? '1' : '0');
}

function handle_uncaught(Throwable $error): void
{
    error_log('[blog] ' . $error::class . ': ' . $error->getMessage() . ' en ' . $error->getFile() . ':' . $error->getLine());
    if (headers_sent()) {
        return;
    }
    http_response_code(500);
    $wantsJson = str_ends_with((string) ($_SERVER['SCRIPT_NAME'] ?? ''), 'api.php');
    header('Content-Type: ' . ($wantsJson ? 'application/json' : 'text/plain') . '; charset=utf-8');
    echo $wantsJson
        ? '{"error":"Error interno. Inténtalo de nuevo."}'
        : 'Ocurrió un error interno. Inténtalo de nuevo más tarde.';
}

if (PHP_SAPI !== 'cli') {
    configure_errors();
    set_exception_handler('handle_uncaught');
    send_security_headers();
}
if (!defined('BLOG_NO_SESSION')) {
    start_secure_session();
}
