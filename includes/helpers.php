<?php
declare(strict_types=1);

const URL_HOME  = 'index.php';
const URL_LOGIN = 'login.php';
const URL_ADMIN = 'admin.php';
const URL_SETUP = 'setup.php';

/** Señal usada solo en las pruebas automáticas en lugar de header()+exit. */
final class RedirectSignal extends RuntimeException
{
    public function __construct(public readonly string $to)
    {
        parent::__construct('redirect:' . $to);
    }
}

/** Señal usada solo en las pruebas automáticas en lugar de exit con error HTTP. */
final class HttpAbort extends RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message, $status);
    }
}

function testing(): bool
{
    return defined('BLOG_TESTING');
}

function redirect(string $to): never
{
    $to = str_replace(["\r", "\n"], '', $to);
    if (testing()) {
        throw new RedirectSignal($to);
    }
    header('Location: ' . $to);
    exit;
}

function abort(int $status, string $message): never
{
    if (testing()) {
        throw new HttpAbort($status, $message);
    }
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    exit($message);
}

function e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Lee un texto de $_GET/$_POST. Si llega un arreglo u otro tipo, devuelve ''. */
function input_str(array $source, string $key, int $max = 0): string
{
    $value = $source[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    return $max > 0 ? str_cut($value, $max) : $value;
}

/** Igual que input_str pero sin recortar espacios (para contraseñas). */
function input_raw(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? $value : '';
}

function input_int(array $source, string $key): int
{
    $value = $source[$key] ?? '';
    return is_string($value) && ctype_digit($value) ? (int) $value : 0;
}

function count_message(int $count): string
{
    return $count === 0
        ? 'No hay resultados. Prueba con otra palabra o categoría.'
        : $count . ' noticia(s) encontrada(s).';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($items) ? $items : [];
}

function news_tags(string $raw): array
{
    return array_values(array_filter(
        array_map('trim', explode(',', $raw)),
        static fn (string $tag): bool => $tag !== ''
    ));
}

/** Datos públicos de una noticia, con las URL ya verificadas. */
function news_to_array(array $row): array
{
    $image = safe_url((string) ($row['image_url'] ?? ''));
    return [
        'id'        => (int) $row['id'],
        'title'     => (string) $row['title'],
        'summary'   => (string) $row['summary'],
        'category'  => (string) ($row['category'] ?? ''),
        'tags'      => news_tags((string) ($row['tags'] ?? '')),
        'image_url' => $image,
        'image_alt' => $image === '' ? '' : (string) ($row['image_alt'] ?? ''),
        'url'       => 'article.php?id=' . (int) $row['id'],
    ];
}

function render_card(array $row): string
{
    $item = news_to_array($row);
    $id = (string) $item['id'];
    $html = '<li class="card" data-id="' . $id . '"><article aria-labelledby="t' . $id . '">';
    if ($item['image_url'] !== '') {
        $html .= '<img src="' . e($item['image_url']) . '" alt="' . e($item['image_alt'])
            . '" loading="lazy" width="400" height="225">';
    }
    $html .= '<p class="cat">Categoría: ' . e($item['category']) . '</p>'
        . '<h2 id="t' . $id . '"><a href="' . e($item['url']) . '">' . e($item['title']) . '</a></h2>'
        . '<p>' . e($item['summary']) . '</p>';
    if ($item['tags'] !== []) {
        $html .= '<ul class="tags" aria-label="Etiquetas">';
        foreach ($item['tags'] as $tag) {
            $html .= '<li>' . e($tag) . '</li>';
        }
        $html .= '</ul>';
    }
    return $html . '<button type="button" class="btn secondary save-btn" data-id="' . $id
        . '" aria-pressed="false">Guardar para leer después</button></article></li>';
}

/** Convierte la transcripción en un archivo WebVTT (un solo bloque, sin sincronizar). */
function vtt_from_transcript(string $transcript): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $transcript);
    $text = preg_replace('/\n{2,}/', "\n", $text) ?? $text;
    $text = str_replace('-->', '→', trim($text));
    $text = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    return "WEBVTT\n\n00:00:00.000 --> 99:59:59.000\n" . $text . "\n";
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    if (!testing()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
}
