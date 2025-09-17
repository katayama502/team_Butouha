<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$titleEscaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

$uploadedPdf = null;
$uploadedAudio = null;

if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $pdfTmpPath = $_FILES['pdf']['tmp_name'];
    $pdfName = basename($_FILES['pdf']['name']);
    $targetPdf = $uploadDir . $pdfName;
    if (move_uploaded_file($pdfTmpPath, $targetPdf)) {
        $uploadedPdf = $pdfName;
    }
}

if (!empty($_POST['audioData']) && preg_match('/^data:audio\/webm;base64,/', $_POST['audioData'])) {
    $audioData = substr($_POST['audioData'], strpos($_POST['audioData'], ',') + 1);
    $audioBinary = base64_decode($audioData);
    if ($audioBinary !== false) {
        $audioName = 'voice_' . time() . '.webm';
        file_put_contents($uploadDir . $audioName, $audioBinary);
        $uploadedAudio = $audioName;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>アップロード完了</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-upload">
  <main class="upload-main">
    <h1>アップロードが完了しました。</h1>
    <?php if ($titleEscaped) : ?>
      <p class="upload-title">投稿タイトル：<strong><?= $titleEscaped ?></strong></p>
    <?php endif; ?>

    <ul class="upload-summary">
      <li>PDF：<?= $uploadedPdf ? htmlspecialchars($uploadedPdf, ENT_QUOTES, 'UTF-8') : '未アップロード' ?></li>
      <li>音声：<?= $uploadedAudio ? htmlspecialchars($uploadedAudio, ENT_QUOTES, 'UTF-8') : '未アップロード' ?></li>
    </ul>

    <div class="cta-section">
      <a class="cta-button" href="form.html">一覧に戻る</a>
      <a class="cta-button secondary" href="style.html">続けて投稿する</a>
    </div>
  </main>
</body>
</html>
