<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

requireLogin();
$user = getAuthenticatedUser();
$isAdmin = ($user['role'] ?? '') === 'admin';

if (!$isAdmin) {
    header('Location: me.php');
    exit;
}

$categories = require __DIR__ . '/categories.php';
$counts = [];
foreach ($categories as $category) {
    $counts[$category['label']] = 0;
}

$dbError = null;

try {
    $pdo = getPdo();
    foreach ($categories as $category) {
        $table = $category['table'];
        $stmt = $pdo->query(sprintf('SELECT COUNT(*) AS total FROM `%s`', $table));
        $row = $stmt->fetch();
        if ($row && isset($row['total'])) {
            $counts[$category['label']] = (int) $row['total'];
        }
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
    <div class="user-menu">
      <span class="user-menu__label">ようこそ、<?= htmlspecialchars($user['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>さん（<?= ($user['role'] ?? '') === 'admin' ? '管理者' : '一般ユーザー' ?>）</span>
      <a class="user-menu__link" href="admin_calendar.php">会議室を予約</a>
      <a class="user-menu__link" href="logout.php">ログアウト</a>
    </div>
    <h1 class="main-title">お知らせ</h1>
    <nav class="tabs" aria-label="カテゴリ切り替え">
      <?php foreach ($categories as $category): ?>
        <a
          class="tab tab-link"
          data-cat="<?= htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8') ?>"
          href="<?= htmlspecialchars($category['listPage'], ENT_QUOTES, 'UTF-8') ?>"
        >
          <?= htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8') ?> (<?= $counts[$category['label']] ?>)
        </a>
      <?php endforeach; ?>
    </nav>
  </header>

  <main class="home-main">
    <section class="home-summary">
      <p class="home-description">
        最新のお知らせをカテゴリ別に確認できます。重要なお知らせは下のボタンから一覧ページへ移動してください。
      </p>
      <ul class="count-list">
        <?php foreach ($categories as $category): ?>
          <li>
            <span class="label"><?= htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="value"><?= $counts[$category['label']] ?>件</span>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($dbError): ?>
        <p class="home-error">データベースに接続できませんでした。設定を確認してください。<br><small><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></small></p>
      <?php endif; ?>
    </section>

    <section class="cta-section">
      <a href="form.php" class="cta-button">重要なお知らせ一覧を見る</a>
      <a href="admin_calendar.php" class="cta-button secondary">会議室の予約状況</a>
    </section>
  </main>
</body>
</html>
