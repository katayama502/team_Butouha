<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
$user = getAuthenticatedUser();

$categories = require __DIR__ . '/categories.php';
$categoryKey = isset($_GET['category']) ? (string) $_GET['category'] : 'important';

if (!isset($categories[$categoryKey])) {
    http_response_code(404);
    $categoryKey = 'important';
}

$category = $categories[$categoryKey];
$categoryLabel = $category['label'];
$listPage = $category['listPage'];
$formTitle = $category['formTitle'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="page-post" data-category="<?= htmlspecialchars($categoryKey, ENT_QUOTES, 'UTF-8') ?>">
  <header class="page-header">
    <a class="back-link" href="<?= htmlspecialchars($listPage, ENT_QUOTES, 'UTF-8') ?>">&larr; <?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?>一覧に戻る</a>
    <h1 class="page-title">投稿フォーム</h1>
    <div class="user-menu">
      <span class="user-menu__label"><?= htmlspecialchars(($user['display_name'] ?? '') . '（管理者）', ENT_QUOTES, 'UTF-8') ?></span>
      <a class="user-menu__link" href="admin_calendar.php">会議室予約</a>
      <a class="user-menu__link" href="logout.php">ログアウト</a>
    </div>
  </header>

  <main class="post-main">
    <div class="container">
      <p class="post-category">投稿カテゴリ：<strong><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></strong></p>
      <form id="postForm" action="upload.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="category" value="<?= htmlspecialchars($categoryKey, ENT_QUOTES, 'UTF-8') ?>">
        <section class="title-section">
          <label for="title">タイトル：</label>
          <input type="text" id="title" name="title" required>
        </section>

        <section class="pdf-section">
          <label for="pdf">PDFを添付：</label>
          <input type="file" id="pdf" name="pdf" accept="application/pdf" required>
        </section>

        <section class="audio-section">
          <h2>🎤 音声を吹き込む</h2>
          <div class="audio-controls">
            <button type="button" id="startBtn">録音開始</button>
            <button type="button" id="stopBtn" disabled>録音停止</button>
          </div>
          <audio id="audioPlayback" controls></audio>
          <input type="hidden" name="audioData" id="audioData">
        </section>

        <div class="submit-btn">
          <button type="submit">投稿する</button>
        </div>
      </form>
    </div>
  </main>

  <script>
    const CATEGORY_LABELS = {
      important: '重要',
      contribution: '地域貢献',
      other: 'その他',
    };

    (function synchronizeCategorySelection() {
      const categoryInput = document.querySelector('input[name="category"]');
      const categoryLabelElement = document.querySelector('.post-category strong');
      let storedCategory = null;

      try {
        storedCategory = sessionStorage.getItem('selectedCategory');
      } catch (error) {
        storedCategory = null;
      }

      const urlParams = new URLSearchParams(window.location.search);
      const urlCategory = urlParams.get('category');

      const resolvedCategory = urlCategory || storedCategory || (categoryInput ? categoryInput.value : '');

      if (categoryInput && resolvedCategory && categoryInput.value !== resolvedCategory) {
        categoryInput.value = resolvedCategory;
      }

      if (categoryLabelElement && resolvedCategory && CATEGORY_LABELS[resolvedCategory]) {
        categoryLabelElement.textContent = CATEGORY_LABELS[resolvedCategory];
      }

      if (storedCategory) {
        try {
          sessionStorage.removeItem('selectedCategory');
        } catch (removeError) {
          // sessionStorage が使用できない場合は無視
        }
      }
    })();

    let mediaRecorder;
    let audioChunks = [];

    const startBtn = document.getElementById("startBtn");
    const stopBtn = document.getElementById("stopBtn");
    const audioPlayback = document.getElementById("audioPlayback");
    const audioDataInput = document.getElementById("audioData");

    startBtn.onclick = async () => {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);

        audioChunks = [];
        mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
        mediaRecorder.onstop = () => {
          const blob = new Blob(audioChunks, { type: 'audio/webm' });
          const reader = new FileReader();
          reader.onloadend = () => {
            audioDataInput.value = reader.result; // base64で送信
          };
          reader.readAsDataURL(blob);
          audioPlayback.src = URL.createObjectURL(blob);
        };

        mediaRecorder.start();
        startBtn.disabled = true;
        stopBtn.disabled = false;
      } catch (error) {
        console.error(error);
        startBtn.disabled = false;
        stopBtn.disabled = true;
      }
    };

    stopBtn.onclick = () => {
      if (!mediaRecorder) {
        return;
      }

      mediaRecorder.stop();
      startBtn.disabled = false;
      stopBtn.disabled = true;
    };
  </script>
</body>
</html>
