<?php
declare(strict_types=1);

final class PagesTest extends BlogTestCase
{
    public function testPublicPagesRender(): void
    {
        $home = $this->page('index.php');
        self::assertSame(200, $home['status']);
        self::assertStringContainsString('<search', $home['body']);
        self::assertStringNotContainsString('<script>', $home['body']);
        $filtered = $this->page('index.php', ['q' => '<script>alert(1)</script>', 'cat' => '2']);
        self::assertStringNotContainsString('<script>alert(1)</script>', $filtered['body']);
        $id = (int) query_news($this->pdo())[0]['id'];
        $article = $this->page('article.php', ['id' => (string) $id]);
        self::assertSame(200, $article['status']);
        self::assertStringContainsString('<h1', $article['body']);
        self::assertSame(200, $this->page('saved.php')['status']);
    }

    public function testMissingArticleIs404(): void
    {
        $r = $this->page('article.php', ['id' => '99999']);
        self::assertTrue($r['aborted'] === 404 || $r['status'] === 404);
        $r = $this->page('article.php', ['id' => ['x']]);
        self::assertTrue($r['aborted'] === 404 || $r['status'] === 404);
    }

    public function testLoginRedirectsToSetupWhenNoUsers(): void
    {
        self::assertSame(URL_SETUP, $this->page('login.php')['redirect']);
    }

    public function testSetupFlow(): void
    {
        $form = $this->page('setup.php');
        self::assertStringContainsString('Crear cuenta de administrador', $form['body']);
        $_SESSION['csrf'] = 'token-de-prueba';
        $bad = $this->post('setup.php', ['username' => 'profe', 'password' => 'corta', 'password2' => 'corta']);
        self::assertStringContainsString('field-error', $bad['body']);
        $noCsrf = $this->page('setup.php', [], ['username' => 'profe'], 'POST');
        self::assertSame(400, $noCsrf['aborted']);
        $ok = $this->post('setup.php', ['username' => 'profe', 'password' => 'ClaveSegura#2026', 'password2' => 'ClaveSegura#2026']);
        self::assertSame(URL_LOGIN, $ok['redirect']);
        self::assertTrue(has_users($this->pdo()));
        self::assertSame(URL_LOGIN, $this->page('setup.php')['redirect']);
    }

    public function testSetupBlockedFromRemote(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $_SESSION['csrf'] = 'token-de-prueba';
        $r = $this->post('setup.php', ['username' => 'hacker', 'password' => 'ClaveSegura#2026', 'password2' => 'ClaveSegura#2026']);
        self::assertSame(403, $r['status']);
        self::assertFalse(has_users($this->pdo()));
    }

    public function testLoginFlowAndBlock(): void
    {
        $this->makeAdmin();
        $_SESSION['csrf'] = 'token-de-prueba';
        $bad = $this->post('login.php', ['username' => 'profe', 'password' => 'mala']);
        self::assertStringContainsString('incorrectos', $bad['body']);
        $arr = $this->post('login.php', ['username' => ['x'], 'password' => ['y']]);
        self::assertStringContainsString('incorrectos', $arr['body']);
        for ($i = 0; $i < LOGIN_MAX_PER_USER; $i++) {
            $blocked = $this->post('login.php', ['username' => 'profe', 'password' => 'mala']);
        }
        self::assertSame(429, $blocked['status']);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.9';
        $ok = $this->post('login.php', ['username' => 'profe', 'password' => 'ClaveSegura#2026']);
        self::assertSame(URL_ADMIN, $ok['redirect']);
        self::assertTrue(is_admin());
        self::assertSame(URL_ADMIN, $this->page('login.php')['redirect']);
    }

    public function testAdminRequiresLogin(): void
    {
        $this->makeAdmin();
        self::assertSame(URL_LOGIN, $this->page('admin.php')['redirect']);
    }

    public function testAdminCrud(): void
    {
        $this->loginAsAdmin();
        self::assertSame(200, $this->page('admin.php')['status']);
        $fields = ['action' => 'save', 'id' => '0'] + $this->validNews(['title' => 'Mi <b>noticia</b>']);
        self::assertSame(URL_ADMIN, $this->post('admin.php', $fields)['redirect']);
        $row = query_news($this->pdo(), 'noticia')[0];
        $id = (string) $row['id'];

        $list = $this->page('admin.php', ['edit' => $id]);
        self::assertStringNotContainsString('<b>noticia</b>', $list['body']);

        $invalid = $this->post('admin.php', ['action' => 'save', 'id' => $id, 'title' => '']);
        self::assertStringContainsString('obligatorio', $invalid['body']);

        $edit = $this->post('admin.php', ['action' => 'save', 'id' => $id] + $this->validNews(['title' => 'Editada']));
        self::assertSame(URL_ADMIN, $edit['redirect']);
        self::assertSame('Editada', get_news($this->pdo(), (int) $id)['title']);

        $ghost = $this->post('admin.php', ['action' => 'save', 'id' => '99999'] + $this->validNews());
        self::assertSame(URL_ADMIN, $ghost['redirect']);

        self::assertSame(URL_ADMIN, $this->post('admin.php', ['action' => 'delete', 'id' => $id])['redirect']);
        self::assertNull(get_news($this->pdo(), (int) $id));
        self::assertSame(URL_ADMIN, $this->post('admin.php', ['action' => 'delete', 'id' => $id])['redirect']);
        self::assertSame(200, $this->post('admin.php', ['action' => 'otra'])['status']);
    }

    public function testAdminPostNeedsCsrf(): void
    {
        $this->loginAsAdmin();
        $r = $this->page('admin.php', [], ['action' => 'delete', 'id' => '1', 'csrf' => 'falso'], 'POST');
        self::assertSame(400, $r['aborted']);
        self::assertNotNull(get_news($this->pdo(), (int) query_news($this->pdo())[0]['id']));
    }

    public function testLogout(): void
    {
        $this->loginAsAdmin();
        self::assertSame(URL_HOME, $this->page('logout.php')['redirect']);
        self::assertTrue(is_admin());
        self::assertSame(400, $this->page('logout.php', [], ['csrf' => 'mal'], 'POST')['aborted']);
        self::assertSame(URL_HOME, $this->post('logout.php', [])['redirect']);
        self::assertFalse(is_admin());
    }

    public function testApi(): void
    {
        $r = json_decode($this->page('api.php', ['a' => 'search', 'q' => ''])['body'], true);
        self::assertGreaterThan(0, $r['count']);
        self::assertCount($r['count'], $r['items']);
        $none = json_decode($this->page('api.php', ['a' => 'search', 'q' => "%' OR 1=1 --"])['body'], true);
        self::assertSame(0, $none['count']);
        $id = (string) $r['items'][0]['id'];
        $saved = json_decode($this->page('api.php', ['a' => 'saved', 'ids' => $id . ',x,0'])['body'], true);
        self::assertSame(1, $saved['count']);
        $empty = json_decode($this->page('api.php', ['a' => 'saved', 'ids' => 'x'])['body'], true);
        self::assertSame(0, $empty['count']);
        $bad = $this->page('api.php', ['a' => 'drop']);
        self::assertSame(400, $bad['status']);
        self::assertArrayHasKey('error', json_decode($bad['body'], true));
    }

    public function testCaptions(): void
    {
        $this->pdo()->exec("UPDATE news SET transcript = 'Hola mundo' WHERE id = 1");
        self::assertStringStartsWith('WEBVTT', $this->page('captions.php', ['id' => '1'])['body']);
        $this->pdo()->exec("UPDATE news SET transcript = '' WHERE id = 1");
        self::assertSame(404, $this->page('captions.php', ['id' => '1'])['aborted']);
        self::assertSame(404, $this->page('captions.php', ['id' => '9999'])['aborted']);
    }

    public function testStoredXssIsEscapedEverywhere(): void
    {
        $this->loginAsAdmin();
        $xss = '"><script>alert(1)</script>';
        $this->post('admin.php', ['action' => 'save', 'id' => '0'] + $this->validNews([
            'title' => $xss, 'summary' => $xss, 'body' => $xss, 'tags' => $xss,
            'image_url' => 'https://x.com/a.png', 'image_alt' => $xss,
            'audio_url' => 'https://x.com/a.mp3', 'transcript' => $xss,
        ]));
        $id = (string) query_news($this->pdo(), 'script')[0]['id'];
        foreach ([['index.php', []], ['article.php', ['id' => $id]], ['admin.php', ['edit' => $id]]] as [$file, $get]) {
            $body = $this->page($file, $get)['body'];
            self::assertStringNotContainsString('<script>alert(1)</script>', $body, $file);
        }
    }
}
