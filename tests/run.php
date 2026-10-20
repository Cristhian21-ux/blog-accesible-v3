<?php
/*
 * Ejecutor mínimo de pruebas cuando PHPUnit no está instalado:  php tests/run.php
 * (Con PHPUnit instalado se usa:  vendor/bin/phpunit)
 */
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

error_reporting(E_ALL);
set_error_handler(static function (int $level, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $level, $file, $line);
});

$passed = 0;
$failures = [];
foreach (glob(__DIR__ . '/php/*Test.php') ?: [] as $file) {
    $before = get_declared_classes();
    require_once $file;
    foreach (array_diff(get_declared_classes(), $before) as $class) {
        if (!is_subclass_of($class, TestCase::class)) {
            continue;
        }
        foreach (get_class_methods($class) as $method) {
            if (!str_starts_with($method, 'test')) {
                continue;
            }
            try {
                (new $class())->runShimTest($method);
                $passed++;
            } catch (Throwable $error) {
                $failures[] = $class . '::' . $method . ' -> ' . $error->getMessage()
                    . ' (' . basename($error->getFile()) . ':' . $error->getLine() . ')';
            }
        }
    }
}

echo PHP_EOL . 'Pruebas correctas: ' . $passed . ' | con fallas: ' . count($failures)
    . ' | aserciones: ' . TestCase::$assertions . PHP_EOL;
foreach ($failures as $failure) {
    echo ' - ' . $failure . PHP_EOL;
}
exit($failures === [] ? 0 : 1);
