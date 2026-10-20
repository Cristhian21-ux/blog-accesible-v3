<?php
/* Subtítulos (WebVTT) generados a partir de la transcripción de la noticia. */
defined('BLOG_NO_SESSION') || define('BLOG_NO_SESSION', true);
require_once __DIR__ . '/includes/bootstrap.php';

$news = get_news(db(), input_int($_GET, 'id'));
if ($news === null || trim((string) $news['transcript']) === '') {
    abort(404, 'No encontrado');
}
if (!testing()) {
    header('Content-Type: text/vtt; charset=utf-8');
}
echo vtt_from_transcript((string) $news['transcript']);
