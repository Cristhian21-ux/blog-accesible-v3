<?php
declare(strict_types=1);

final class AuthTest extends BlogTestCase
{
    public function testLoginOkAndInvalid(): void
    {
        $this->makeAdmin('profe', 'ClaveSegura#2026');
        $pdo = $this->pdo();
        self::assertSame('ok', attempt_login($pdo, '1.1.1.1', 'profe', 'ClaveSegura#2026')['status']);
        self::assertSame('invalid', attempt_login($pdo, '1.1.1.1', 'profe', 'mala')['status']);
        self::assertSame('invalid', attempt_login($pdo, '1.1.1.1', 'nadie', 'x')['status']);
    }

    public function testRateLimitPerUserAndExpiry(): void
    {
        $this->makeAdmin();
        $pdo = $this->pdo();
        $now = 1_000_000;
        for ($i = 0; $i < LOGIN_MAX_PER_USER; $i++) {
            self::assertSame('invalid', attempt_login($pdo, '2.2.2.2', 'profe', 'mala', $now)['status']);
        }
        self::assertSame('blocked', attempt_login($pdo, '2.2.2.2', 'profe', 'ClaveSegura#2026', $now)['status']);
        // otra IP no queda bloqueada
        self::assertSame('ok', attempt_login($pdo, '3.3.3.3', 'profe', 'ClaveSegura#2026', $now)['status']);
        // pasada la ventana se libera
        self::assertSame('ok', attempt_login($pdo, '2.2.2.2', 'profe', 'ClaveSegura#2026', $now + LOGIN_WINDOW_SECONDS + 1)['status']);
    }

    public function testRateLimitPerIp(): void
    {
        $pdo = $this->pdo();
        $now = 2_000_000;
        for ($i = 0; $i < LOGIN_MAX_PER_IP; $i++) {
            record_login_failure($pdo, '4.4.4.4', 'user' . $i, $now);
        }
        self::assertTrue(login_blocked($pdo, '4.4.4.4', 'otro', $now));
        self::assertFalse(login_blocked($pdo, '5.5.5.5', 'otro', $now));
    }

    public function testSuccessClearsFailures(): void
    {
        $this->makeAdmin();
        $pdo = $this->pdo();
        attempt_login($pdo, '6.6.6.6', 'profe', 'mala');
        attempt_login($pdo, '6.6.6.6', 'profe', 'ClaveSegura#2026');
        self::assertSame(0, count_failures($pdo, '6.6.6.6', 'profe', 0));
    }

    public function testRehashOnLogin(): void
    {
        $pdo = $this->pdo();
        $old = password_hash('ClaveSegura#2026', PASSWORD_BCRYPT, ['cost' => 4]);
        $pdo->prepare('INSERT INTO users(username, password_hash) VALUES(?, ?)')->execute(['profe', $old]);
        attempt_login($pdo, '7.7.7.7', 'profe', 'ClaveSegura#2026');
        $new = (string) $pdo->query('SELECT password_hash FROM users')->fetchColumn();
        self::assertFalse(password_needs_rehash($new, PASSWORD_DEFAULT));
    }

    public function testFirstAdminOnlyOnce(): void
    {
        $pdo = $this->pdo();
        self::assertTrue(create_first_admin($pdo, 'uno', 'ClaveSegura#2026'));
        self::assertFalse(create_first_admin($pdo, 'dos', 'ClaveSegura#2026'));
        self::assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
    }

    public function testSetUserPasswordCreatesAndUpdates(): void
    {
        $pdo = $this->pdo();
        set_user_password($pdo, 'profe', 'ClaveSegura#2026');
        set_user_password($pdo, 'profe', 'OtraClaveSegura#1');
        self::assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
        self::assertSame('ok', attempt_login($pdo, '8.8.8.8', 'profe', 'OtraClaveSegura#1')['status']);
    }

    public function testClientIpUsesRemoteAddrOnly(): void
    {
        $_SERVER['REMOTE_ADDR'] = '9.9.9.9';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
        self::assertSame('9.9.9.9', client_ip());
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        self::assertSame(login_key('Profe'), login_key('Profe'));
    }
}
