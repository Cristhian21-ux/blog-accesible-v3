<?php
declare(strict_types=1);

final class ValidationTest extends BlogTestCase
{
    public function testSafeUrlAllowlist(): void
    {
        self::assertTrue(is_safe_media_url('https://example.com/a.jpg'));
        self::assertTrue(is_safe_media_url('assets/img/a.png'));
        foreach (['javascript:alert(1)', 'http://example.com/a.jpg', 'data:text/html,x', '//evil.com/x',
                  'assets/../data/blog.sqlite', 'assets//x', 'https://user:pw@example.com/a', '', 'ftp://x.com/a',
                  'https://' . str_repeat('a', 600) . '.com'] as $bad) {
            self::assertFalse(is_safe_media_url($bad), $bad);
        }
        self::assertSame('', safe_url(null));
        self::assertSame('', safe_url('javascript:alert(1)'));
        self::assertSame('assets/a.png', safe_url('assets/a.png'));
    }

    public function testParseTagsAndIds(): void
    {
        self::assertSame(['a', 'b'], parse_tags(" a , b ,, a,\x01"));
        self::assertSame([3, 5], clean_ids('3,5,x,3,0,-2, 5,1234567890'));
        self::assertCount(2, clean_ids('1,2,3,4', 2));
        self::assertSame('a\\%b\\_c\\\\', like_escape('a%b_c\\'));
    }

    public function testStringHelpers(): void
    {
        self::assertSame(3, str_len('añ€'));
        self::assertSame('añ', str_cut('añ€xyz', 2));
        self::assertSame('ñandú', str_lower('ÑANDÚ'));
    }

    public function testValidNewsPasses(): void
    {
        $r = validate_news($this->validNews(), [1, 2]);
        self::assertSame([], $r['errors']);
        self::assertSame('uno,dos', $r['data']['tags']);
    }

    public function testNewsRequiredAndLimits(): void
    {
        $r = validate_news(['title' => '', 'summary' => '', 'body' => '', 'category_id' => '99'], [1]);
        foreach (['title', 'summary', 'body', 'category_id'] as $k) {
            self::assertArrayHasKey($k, $r['errors']);
        }
        $r = validate_news($this->validNews(['title' => str_repeat('x', MAX_TITLE + 1)]), [1]);
        self::assertArrayHasKey('title', $r['errors']);
        $r = validate_news($this->validNews(['tags' => implode(',', range(1, 11))]), [1]);
        self::assertArrayHasKey('tags', $r['errors']);
        $r = validate_news($this->validNews(['tags' => str_repeat('t', 31)]), [1]);
        self::assertArrayHasKey('tags', $r['errors']);
    }

    public function testNewsMediaRules(): void
    {
        $r = validate_news($this->validNews(['image_url' => 'https://x.com/a.png']), [1]);
        self::assertArrayHasKey('image_alt', $r['errors']);
        $r = validate_news($this->validNews(['audio_url' => 'https://x.com/a.mp3']), [1]);
        self::assertArrayHasKey('transcript', $r['errors']);
        $r = validate_news($this->validNews(['image_url' => 'javascript:alert(1)', 'audio_url' => 'http://x.com/a']), [1]);
        self::assertArrayHasKey('image_url', $r['errors']);
        self::assertArrayHasKey('audio_url', $r['errors']);
    }

    public function testNewsRejectsArrayInput(): void
    {
        $r = validate_news(['title' => ['x'], 'summary' => 's', 'body' => 'b', 'category_id' => [1]], [1]);
        self::assertArrayHasKey('title', $r['errors']);
        self::assertArrayHasKey('category_id', $r['errors']);
    }

    public function testNewAdminRules(): void
    {
        self::assertSame([], validate_new_admin('profe', 'ClaveSegura#2026', 'ClaveSegura#2026'));
        self::assertArrayHasKey('username', validate_new_admin('a b', 'ClaveSegura#2026', 'ClaveSegura#2026'));
        self::assertArrayHasKey('password', validate_new_admin('profe', 'corta', 'corta'));
        self::assertArrayHasKey('password', validate_new_admin('profe', str_repeat('a', 73), str_repeat('a', 73)));
        self::assertArrayHasKey('password', validate_new_admin('profe', 'password123', 'password123'));
        self::assertArrayHasKey('password', validate_new_admin('profesor1234', 'PROFESOR1234', 'PROFESOR1234'));
        self::assertArrayHasKey('password2', validate_new_admin('profe', 'ClaveSegura#2026', 'otra'));
    }
}
