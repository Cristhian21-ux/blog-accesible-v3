<?php
/*
 * Mini sustituto de PHPUnit\Framework\TestCase, solo con las aserciones que usan estas pruebas.
 * Se carga únicamente cuando PHPUnit no está instalado.
 */
declare(strict_types=1);

namespace PHPUnit\Framework;

final class ShimAssertionFailed extends \Exception
{
}

abstract class TestCase
{
    public static int $assertions = 0;

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    public function runShimTest(string $method): void
    {
        $this->setUp();
        try {
            $this->$method();
        } finally {
            $this->tearDown();
        }
    }

    private static function ok(bool $condition, string $message): void
    {
        self::$assertions++;
        if (!$condition) {
            throw new ShimAssertionFailed($message);
        }
    }

    private static function show(mixed $value): string
    {
        return is_string($value) ? "'" . $value . "'" : var_export($value, true);
    }

    public static function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        self::ok($expected === $actual, $message ?: 'Se esperaba ' . self::show($expected) . ' y se obtuvo ' . self::show($actual));
    }

    public static function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        self::ok($expected == $actual, $message ?: 'Se esperaba ' . self::show($expected) . ' y se obtuvo ' . self::show($actual));
    }

    public static function assertTrue(mixed $condition, string $message = ''): void
    {
        self::ok($condition === true, $message ?: 'Se esperaba true');
    }

    public static function assertFalse(mixed $condition, string $message = ''): void
    {
        self::ok($condition === false, $message ?: 'Se esperaba false');
    }

    public static function assertNull(mixed $value, string $message = ''): void
    {
        self::ok($value === null, $message ?: 'Se esperaba null');
    }

    public static function assertNotNull(mixed $value, string $message = ''): void
    {
        self::ok($value !== null, $message ?: 'No se esperaba null');
    }

    public static function assertCount(int $expected, \Countable|array $items, string $message = ''): void
    {
        self::ok(count($items) === $expected, $message ?: 'Se esperaban ' . $expected . ' elementos y hay ' . count($items));
    }

    public static function assertEmpty(mixed $value, string $message = ''): void
    {
        self::ok(empty($value), $message ?: 'Se esperaba vacío');
    }

    public static function assertNotEmpty(mixed $value, string $message = ''): void
    {
        self::ok(!empty($value), $message ?: 'No se esperaba vacío');
    }

    public static function assertGreaterThan(int|float $expected, int|float $actual, string $message = ''): void
    {
        self::ok($actual > $expected, $message ?: $actual . ' no es mayor que ' . $expected);
    }

    public static function assertArrayHasKey(int|string $key, array $array, string $message = ''): void
    {
        self::ok(array_key_exists($key, $array), $message ?: 'Falta la clave ' . $key);
    }

    public static function assertArrayNotHasKey(int|string $key, array $array, string $message = ''): void
    {
        self::ok(!array_key_exists($key, $array), $message ?: 'No debería existir la clave ' . $key);
    }

    public static function assertContains(mixed $needle, iterable $haystack, string $message = ''): void
    {
        $found = false;
        foreach ($haystack as $item) {
            $found = $found || $item === $needle;
        }
        self::ok($found, $message ?: 'No se encontró ' . self::show($needle));
    }

    public static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        self::ok(str_contains($haystack, $needle), $message ?: 'No se encontró "' . $needle . '" en el texto');
    }

    public static function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
    {
        self::ok(!str_contains($haystack, $needle), $message ?: 'No debería aparecer "' . $needle . '" en el texto');
    }

    public static function assertStringStartsWith(string $prefix, string $string, string $message = ''): void
    {
        self::ok(str_starts_with($string, $prefix), $message ?: 'El texto no empieza con "' . $prefix . '"');
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        self::ok(preg_match($pattern, $string) === 1, $message ?: 'El texto no cumple ' . $pattern);
    }

    public static function fail(string $message = ''): never
    {
        self::$assertions++;
        throw new ShimAssertionFailed($message ?: 'Fallo forzado');
    }
}
