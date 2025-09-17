<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// データ読み込み
$dataFile = __DIR__ . '/data.json';
$posts = [];
if (file_exists($dataFile)) {
    $json = file_get_contents($dataFile);
    $posts = json_decode($json, true) ?: [];
}

// カウント
$counts = ['重要'=>0,'地域貢献'=>0,'その他'=>0];
foreach ($posts as $p) {
  if ($p['category'] === 'ボランティア') {
    $counts['地域貢献']++;
  } elseif (isset($counts[$p['category']])) {
    $counts[$p['category']]++;
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>お知らせアプリ</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <h1 class="main-title">お知らせ</h1>
  <div class="tabs">
  <!-- 「全部」タブは削除しました -->
  <button class="tab" data-cat="重要">重要 (<?= $counts['重要'] ?>)</button>
  <button class="tab" data-cat="地域貢献">地域貢献 (<?= $counts['地域貢献'] ?>)</button>
  <button class="tab" data-cat="その他">その他 (<?= $counts['その他'] ?>)</button>
  </div>
</header>

<main>
  <!-- 投稿一覧と新規投稿フォームは完全に削除しました -->