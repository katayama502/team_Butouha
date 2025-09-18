<?php
require_once __DIR__ . '/auth.php';

/** @var string $pageTitle */
/** @var string $forbiddenMessage */
$pageTitle = $pageTitle ?? 'アクセス権限がありません';
$forbiddenMessage = $forbiddenMessage ?? 'このページにはアクセスできません。';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-forbidden">
  <main class="forbidden-main">
    <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= nl2br(htmlspecialchars($forbiddenMessage, ENT_QUOTES, 'UTF-8')) ?></p>
    <?php if (isAuthenticated()): ?>
      <div class="cta-section">
        <a class="cta-button" href="index.php">トップへ戻る</a>
      </div>
    <?php else: ?>
      <div class="cta-section">
        <a class="cta-button" href="login.php">ログインページへ</a>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
