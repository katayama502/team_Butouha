<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database.php';

try {
    $pdo = getPdo();
    $stmt = $pdo->query('SELECT id, title, pdf_path, audio_path, created_at FROM posts ORDER BY created_at DESC');
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
