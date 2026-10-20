<?php
declare(strict_types=1);

final class HelpersTest extends BlogTestCase
{
    public function testEscapeAndInputs(): void
    {
        self::assertSame('&lt;script&gt;&quot;&#039;', e('<script>"\''));
        self::assertSame('', e(null));
        self::assertSame('abc', input_str(['k' => '  abcdef '], 'k', 3));
        self::assertSame('', input_str(['k' => ['x']], 'k'));
        self::assertSame('', input_str([], 'k'));
        self::assertSame(' p ', input_raw(['k' => ' p '], 'k'));
        self::assertSame('', input_raw(['k' => []], 'k'));
        self::assertSame(12, input_int(['k' => '12'], 'k'));
        self::assertSame(0, input_int(['k' => '12abc'], 'k'));
        self::assertSame(0, input_int(['k' => ['1']], 'k'));
    }

    public function testCountMessage(): void
    {
        self::assertStringContainsString('No hay resultados', count_message(0));
        self::assertStringContainsString('1 noticia', count_message(1));
        self::assertStringContainsString('5 noticia', count_message(5));
    }

    public function testFlash(): void
    {
        flash('ok', 'Hola');
        $all = flashes();
        self::assertCount(1, $all);
        self::assertSame([], flashes());
    }

    public function testRedirectStripsNewlines(): void
    {
        try {
            redirect("a.php\r\nSet-Cookie: x=1");
            self::fail('Debía redirigir');
        } catch (RedirectSignal $signal) {
            self::assertSame('a.phpSet-Cookie: x=1', $signal->to);
        }
    }

    public function testCardEscapesAndFiltersUrls(): void
    {
        $row = ['id' => 1, 'title' => '<img src=x onerror=alert(1)>', 'summary' => '<b>s</b>', 'category_name' => 'C',
                'tags' => 'a,<i>b', 'image_url' => 'javascript:alert(1)', 'image_alt' => '', 'audio_url' => '',
                'transcript' => '', 'created_at' => '2026-01-01 10:00:00', 'category_id' => 1, 'body' => 'b'];
        $html = render_card($row);
        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertSame('', news_to_array($row)['image_url']);
        self::assertSame(['a', '<i>b'], news_tags($row['tags']));
    }

    public function testVtt(): void
    {
        $vtt = vtt_from_transcript("Hola\nmundo");
        self::assertStringStartsWith('WEBVTT', $vtt);
        self::assertStringContainsString('Hola', $vtt);
        self::assertStringNotContainsString('-->' . "\nHola\n\n-->", $vtt);
    }

    public function testJsonResponse(): void
    {
        ob_start();
        json_response(['a' => '<é>'], 200);
        $out = (string) ob_get_clean();
        self::assertSame(['a' => '<é>'], json_decode($out, true));
    }
}
