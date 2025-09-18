<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
requireLogin();
$user = getAuthenticatedUser();
require_once __DIR__ . '/database.php';

$categories = require __DIR__ . '/categories.php';
$categoryKey = isset($_GET['category']) ? (string) $_GET['category'] : 'important';

if (!isset($categories[$categoryKey])) {
    http_response_code(400);
    echo json_encode([
        'error' => '指定されたカテゴリは存在しません。',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$table = $categories[$categoryKey]['table'];

try {
    $pdo = getPdo();
    $stmt = $pdo->query(sprintf('SELECT id, title, pdf_path, audio_path, created_at FROM `%s` ORDER BY created_at DESC', $table));
    $rows = $stmt->fetchAll();

    echo json_encode([
        'posts' => array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'pdf_path' => $row['pdf_path'],
                'audio_path' => $row['audio_path'],
                'created_at' => $row['created_at'],
            ];
        }, $rows),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'データベースから投稿を取得できませんでした。',
        'details' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
