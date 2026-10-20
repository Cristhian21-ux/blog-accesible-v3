<?php
declare(strict_types=1);

define('BLOG_TESTING', true);

$root = dirname(__DIR__);
if (is_file($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}
foreach (['helpers', 'validation', 'security', 'db', 'auth', 'news_admin'] as $module) {
    require_once $root . '/includes/' . $module . '.php';
}
if (!class_exists(\PHPUnit\Framework\TestCase::class)) {
    // Sin PHPUnit instalado: se usa un ejecutor mínimo compatible (php tests/run.php).
    require_once __DIR__ . '/support/phpunit-shim.php';
}
require_once __DIR__ . '/support/BlogTestCase.php';
