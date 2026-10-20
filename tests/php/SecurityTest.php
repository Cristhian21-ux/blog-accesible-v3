<?php
declare(strict_types=1);

final class SecurityTest extends BlogTestCase
{
    public function testCsrf(): void
    {
        $token = csrf_token();
        self::assertSame($token, csrf_token());
        self::assertTrue(csrf_valid($token));
        self::assertFalse(csrf_valid('x'));
        self::assertFalse(csrf_valid(['a']));
        self::assertFalse(csrf_valid(null));
        $_SESSION = [];
        self::assertFalse(csrf_valid(''));
    }

    public function testCsrfCheckAborts(): void
    {
        $_POST = ['csrf' => 'mal'];
        try {
            csrf_check();
            self::fail('Debía abortar');
        } catch (HttpAbort $abort) {
            self::assertSame(400, $abort->status);
        }
    }

    public function testSessionTimeouts(): void
    {
        $this->loginAsAdmin();
        self::assertTrue(is_admin());
        $start = $_SESSION['started'];
        self::assertFalse(is_admin($start + SESSION_IDLE_SECONDS + 5));
        self::assertArrayNotHasKey('uid', $_SESSION);

        $_SESSION = ['uid' => 1, 'started' => 1000, 'last' => 1000 + SESSION_MAX_SECONDS];
        self::assertFalse(is_admin(1000 + SESSION_MAX_SECONDS + 1));
        self::assertFalse(is_admin());
    }

    public function testRequireAdminRedirects(): void
    {
        try {
            require_admin();
            self::fail('Debía redirigir');
        } catch (RedirectSignal $signal) {
            self::assertSame(URL_LOGIN, $signal->to);
        }
        $this->loginAsAdmin();
        require_admin();
        self::assertTrue(is_admin());
    }

    public function testLoginRotatesCsrf(): void
    {
        $_SESSION['csrf'] = 'viejo';
        login_user(1);
        self::assertArrayNotHasKey('csrf', $_SESSION);
    }

    public function testSetupOnlyLocal(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        self::assertTrue(setup_allowed());
        $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
        self::assertFalse(setup_allowed());
        putenv('BLOG_ALLOW_REMOTE_SETUP=1');
        self::assertTrue(setup_allowed());
        putenv('BLOG_ALLOW_REMOTE_SETUP');
    }

    public function testCspHasNoInlineScripts(): void
    {
        $csp = csp_policy();
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringContainsString("object-src 'none'", $csp);
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
        self::assertStringNotContainsString('unsafe-inline', explode('script-src', $csp)[1] ?? '');
    }

    public function testHttpsDetection(): void
    {
        unset($_SERVER['HTTPS']);
        self::assertFalse(is_https());
        $_SERVER['HTTPS'] = 'on';
        self::assertTrue(is_https());
        unset($_SERVER['HTTPS']);
    }

    public function testCookiePath(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/Examen parcial/blog/index.php';
        self::assertSame('/Examen parcial/blog/', app_cookie_path());
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        self::assertSame('/', app_cookie_path());
    }
}
