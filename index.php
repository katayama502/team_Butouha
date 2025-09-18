<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';

$counts = ['重要' => 0, '地域貢献' => 0, 'その他' => 0];
$dbError = null;

try {
    $pdo = getPdo();
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM posts');
    $row = $stmt->fetch();
    if ($row && isset($row['total'])) {
        $counts['重要'] = (int) $row['total'];
    }
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
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
      <a class="tab tab-link" data-cat="重要" href="form.php">重要 (<?= $counts['重要'] ?>)</a>
      <a class="tab tab-link" data-cat="地域貢献" href="boranthia.php">地域貢献 (<?= $counts['地域貢献'] ?>)</a>
      <a class="tab tab-link" data-cat="その他" href="sonota.php">その他 (<?= $counts['その他'] ?>)</a>
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
      <?php if ($dbError): ?>
        <p class="home-error">データベースに接続できませんでした。設定を確認してください。<br><small><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></small></p>
      <?php endif; ?>
    </section>

    <section class="cta-section">
      <a href="form.php" class="cta-button">重要なお知らせ一覧を見る</a>
    </section>
  </main>
</body>
</html>
