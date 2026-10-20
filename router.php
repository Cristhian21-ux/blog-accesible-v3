<?php
/*
 * Enrutador para el servidor integrado de PHP:  php -S localhost:8000 router.php
 * Bloquea la base de datos, el código interno y los archivos de configuración
 * (en Apache hace lo mismo el archivo .htaccess).
 */
declare(strict_types=1);

$path = rawurldecode((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));

$internalFolder = preg_match('#(^|/)(data|includes|tests|bin|vendor|build|node_modules)(/|$)#i', $path) === 1;
$hiddenFile     = preg_match('#(^|/)\.#', $path) === 1;
$sensitiveFile  = preg_match('#\.(sqlite3?|db|sql|bak|log|md|ya?ml|properties|lock|xml)$#i', $path) === 1
    || preg_match('#(^|/)(router\.php|package(-lock)?\.json|composer\.json)$#i', $path) === 1;

if ($internalFolder || $hiddenFile || $sensitiveFile) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No encontrado';
    return true;
}
return false;
