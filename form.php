<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>重要なお知らせ一覧</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="page-important">
  <header class="page-header">
    <a class="back-link" href="index.php">&larr; トップへ戻る</a>
    <h1 class="page-title">重要なお知らせ</h1>
  </header>

  <main class="important-main">
    <div class="important-label">重要</div>

    <div class="posts-container" id="postsContainer" aria-live="polite">
      <p class="post-message">投稿を読み込んでいます...</p>
    </div>
    <noscript>
      <p class="post-message">投稿一覧を表示するにはJavaScriptを有効にしてください。</p>
    </noscript>
  </main>

  <button class="post-button" id="postButton" type="button">＋ 投稿</button>

  <script src="script.js"></script>
</body>
</html>
