<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** Base de las pruebas: base de datos temporal, sesión simulada y ejecución de páginas completas. */
abstract class BlogTestCase extends TestCase
{
    protected string $dbFile = '';

    protected function setUp(): void
    {
        $this->dbFile = sys_get_temp_dir() . '/blogtest-' . bin2hex(random_bytes(6)) . '.sqlite';
        putenv('BLOG_DB_PATH=' . $this->dbFile);
        putenv('BLOG_ALLOW_REMOTE_SETUP');
        putenv('BLOG_FORCE_HTTPS');
        db(true);
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        foreach ([$this->dbFile, $this->dbFile . '-journal'] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        putenv('BLOG_DB_PATH');
    }

    protected function pdo(): PDO
    {
        return db();
    }

    /** Crea el administrador y devuelve su id. */
    protected function makeAdmin(string $username = 'profe', string $password = 'ClaveSegura#2026'): int
    {
        set_user_password($this->pdo(), $username, $password);
        return (int) $this->pdo()->query("SELECT id FROM users WHERE username = '" . $username . "'")->fetchColumn();
    }

    protected function loginAsAdmin(): void
    {
        login_user($this->makeAdmin());
        $_SESSION['csrf'] = 'token-de-prueba';
    }

    protected function validNews(array $override = []): array
    {
        return $override + [
            'title' => 'Titulo de prueba',
            'summary' => 'Resumen corto.',
            'body' => "Primer parrafo.\n\nSegundo parrafo.",
            'category_id' => '1',
            'tags' => 'uno,dos',
            'image_url' => '',
            'image_alt' => '',
            'audio_url' => '',
            'transcript' => '',
        ];
    }

    /**
     * Ejecuta una página PHP completa con datos simulados.
     * @return array{body: string, redirect: ?string, aborted: ?int, status: int}
     */
    protected function page(string $file, array $get = [], array $post = [], string $method = 'GET'): array
    {
        $_GET = $get;
        $_POST = $post;
        $_SERVER['REQUEST_METHOD'] = $method;
        http_response_code(200);
        $result = ['body' => '', 'redirect' => null, 'aborted' => null, 'status' => 200];

        ob_start();
        try {
            include dirname(__DIR__, 2) . '/' . $file;
        } catch (RedirectSignal $signal) {
            $result['redirect'] = $signal->to;
        } catch (HttpAbort $abort) {
            $result['aborted'] = $abort->status;
        } finally {
            $result['body'] = (string) ob_get_clean();
        }
        $code = http_response_code();
        $result['status'] = is_int($code) ? $code : 200;
        return $result;
    }

    protected function post(string $file, array $fields): array
    {
        return $this->page($file, [], $fields + ['csrf' => $_SESSION['csrf'] ?? 'token-de-prueba'], 'POST');
    }
}
