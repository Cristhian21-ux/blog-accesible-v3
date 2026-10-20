<?php
defined('BLOG_NO_SESSION') || define('BLOG_NO_SESSION', true);
require_once __DIR__ . '/includes/bootstrap.php';

$pdo = db();
$action = input_str($_GET, 'a');

if ($action === 'search') {
    $rows = query_news($pdo, input_str($_GET, 'q', 100), input_int($_GET, 'cat'));
} elseif ($action === 'saved') {
    $ids = clean_ids(input_str($_GET, 'ids', 600));
    $rows = $ids === [] ? [] : query_news($pdo, '', 0, $ids);
} else {
    json_response(['error' => 'Acción no válida'], 400);
    return;
}
json_response(['count' => count($rows), 'items' => array_map('news_to_array', $rows)]);
