<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$user = getAuthenticatedUser();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>その他のお知らせ一覧</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="page-contribution" data-category="other">
  <header class="page-header">
    <a class="back-link" href="index.php">&larr; トップへ戻る</a>
    <h1 class="page-title">その他のお知らせ</h1>
    <div class="user-menu">
      <span class="user-menu__label"><?= htmlspecialchars(($user['display_name'] ?? '') . '（' . (($user['role'] ?? '') === 'admin' ? '管理者' : '一般ユーザー') . '）', ENT_QUOTES, 'UTF-8') ?></span>
      <a class="user-menu__link" href="admin_calendar.php">会議室予約</a>
      <a class="user-menu__link" href="logout.php">ログアウト</a>
    </div>
  </header>

  <main class="contribution-main">
    <div class="contribution-label">その他</div>

    <div class="posts-container" id="postsContainer" aria-live="polite" data-endpoint="posts.php?category=other">
      <p class="post-message">投稿を読み込んでいます...</p>
    </div>
    <noscript>
      <p class="post-message">投稿一覧を表示するにはJavaScriptを有効にしてください。</p>
    </noscript>
  </main>

  <?php if (($user['role'] ?? '') === 'admin'): ?>
    <button class="post-button" id="postButton" type="button" data-target-form="post_form.php?category=other">＋ 投稿</button>
  <?php endif; ?>

  <script src="script.js"></script>
</body>
</html>

