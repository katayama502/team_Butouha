<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>地域貢献のお知らせ一覧</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="page-contribution" data-category="contribution">
  <header class="page-header">
    <a class="back-link" href="index.php">&larr; トップへ戻る</a>
    <h1 class="page-title">地域貢献のお知らせ</h1>
  </header>

  <main class="contribution-main">
    <div class="important-label">地域貢献</div>

    <div class="posts-container" id="postsContainer" aria-live="polite" data-endpoint="posts.php?category=contribution">
      <p class="post-message">投稿を読み込んでいます...</p>
    </div>
    <noscript>
      <p class="post-message">投稿一覧を表示するにはJavaScriptを有効にしてください。</p>
    </noscript>
  </main>

  <button class="post-button" id="postButton" type="button" data-target-form="post_form.php?category=contribution">＋ 投稿</button>

  <script src="script.js"></script>
</body>
</html>

