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

// カテゴリ別カウント
$counts = ['重要' => 0, '地域貢献' => 0, 'その他' => 0];
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>お知らせアプリ</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-home">
  <header class="home-header">
    <h1 class="main-title">お知らせ</h1>
    <nav class="tabs" aria-label="カテゴリ切り替え">
      <a class="tab tab-link" data-cat="重要" href="form.html">重要 (<?= $counts['重要'] ?>)</a>
      <span class="tab tab-disabled" data-cat="地域貢献" aria-disabled="true">地域貢献 (<?= $counts['地域貢献'] ?>)</span>
      <span class="tab tab-disabled" data-cat="その他" aria-disabled="true">その他 (<?= $counts['その他'] ?>)</span>
    </nav>
  </header>

  <main class="home-main">
    <section class="home-summary">
      <p class="home-description">
        最新のお知らせをカテゴリ別に確認できます。重要なお知らせは下のボタンから一覧ページへ移動してください。
      </p>
      <ul class="count-list">
        <li><span class="label">重要</span><span class="value"><?= $counts['重要'] ?>件</span></li>
        <li><span class="label">地域貢献</span><span class="value"><?= $counts['地域貢献'] ?>件</span></li>
        <li><span class="label">その他</span><span class="value"><?= $counts['その他'] ?>件</span></li>
      </ul>
    </section>

    <section class="cta-section">
      <a href="form.html" class="cta-button">重要なお知らせ一覧を見る</a>
    </section>
  </main>
</body>
</html>
