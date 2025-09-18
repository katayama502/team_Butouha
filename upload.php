<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';

$categories = require __DIR__ . '/categories.php';
$categoryKey = isset($_POST['category']) ? (string) $_POST['category'] : '';
$categoryConfig = $categories[$categoryKey] ?? null;
$categoryLabel = $categoryConfig['label'] ?? '未設定';
$listPage = $categoryConfig['listPage'] ?? 'index.php';
$formPage = $categoryConfig ? 'post_form.php?category=' . $categoryKey : 'post_form.php';
$targetTable = $categoryConfig['table'] ?? null;

$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$titleEscaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

$storedPdfPath = null;
$storedAudioPath = null;
$uploadedPdf = null;
$uploadedAudio = null;
$errors = [];
$dbError = null;

if ($categoryConfig === null) {
    $errors[] = '投稿カテゴリが選択されていません。';
}

if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $pdfTmpPath = $_FILES['pdf']['tmp_name'];
        $pdfOriginalName = $_FILES['pdf']['name'];
        $pdfExtension = strtolower(pathinfo($pdfOriginalName, PATHINFO_EXTENSION));

        if ($pdfExtension !== 'pdf') {
            $errors[] = 'PDFファイルのみアップロードできます。';
        } else {
            $uniquePdfName = 'document_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
            $targetPdf = $uploadDir . $uniquePdfName;

            if (move_uploaded_file($pdfTmpPath, $targetPdf)) {
                $storedPdfPath = 'uploads/' . $uniquePdfName;
                $uploadedPdf = $uniquePdfName;
            } else {
                $errors[] = 'PDFファイルの保存に失敗しました。';
            }
        }
    } else {
        $errors[] = 'PDFファイルのアップロード中にエラーが発生しました。';
    }
} else {
    $errors[] = 'PDFファイルが選択されていません。';
}

if (!empty($_POST['audioData']) && preg_match('/^data:audio\/webm;base64,/', $_POST['audioData'])) {
    $audioData = substr($_POST['audioData'], strpos($_POST['audioData'], ',') + 1);
    $audioBinary = base64_decode($audioData);

    if ($audioBinary !== false) {
        $audioName = 'voice_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.webm';
        $audioPath = $uploadDir . $audioName;

        if (file_put_contents($audioPath, $audioBinary) !== false) {
            $storedAudioPath = 'uploads/' . $audioName;
            $uploadedAudio = $audioName;
        } else {
            $errors[] = '音声ファイルの保存に失敗しました。';
        }
    } else {
        $errors[] = '音声データを解析できませんでした。';
    }
}

if (empty($errors)) {
    try {
        $pdo = getPdo();
        if ($targetTable === null) {
            throw new RuntimeException('登録先のカテゴリが見つかりません。');
        }

        $stmt = $pdo->prepare(sprintf('INSERT INTO `%s` (title, pdf_path, audio_path) VALUES (:title, :pdf_path, :audio_path)', $targetTable));
        $stmt->execute([
            ':title' => $title,
            ':pdf_path' => $storedPdfPath,
            ':audio_path' => $storedAudioPath,
        ]);
    } catch (Throwable $exception) {
        $dbError = $exception->getMessage();
    }
}

$hasErrors = !empty($errors);
$successfullyStored = !$hasErrors && $dbError === null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>アップロード結果</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-upload">
  <main class="upload-main">
    <h1><?= $successfullyStored ? 'アップロードが完了しました。' : ($hasErrors ? 'アップロードでエラーが発生しました。' : '投稿データの保存に失敗しました。') ?></h1>

    <?php if ($hasErrors): ?>
      <div class="upload-errors" role="alert">
        <p>以下の理由でファイルを保存できませんでした。</p>
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($dbError !== null): ?>
      <p class="upload-warning" role="status">データベースへの登録に失敗しました。サーバーログを確認してください。<br><small><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></small></p>
    <?php endif; ?>

    <p class="upload-title">投稿タイトル：<strong><?= $titleEscaped ?: '（タイトル未入力）' ?></strong></p>
    <p class="upload-category">カテゴリ：<strong><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></strong></p>

    <ul class="upload-summary">
      <li>PDF：<?= $uploadedPdf ? htmlspecialchars($uploadedPdf, ENT_QUOTES, 'UTF-8') : '未アップロード' ?></li>
      <li>音声：<?= $uploadedAudio ? htmlspecialchars($uploadedAudio, ENT_QUOTES, 'UTF-8') : '未アップロード' ?></li>
    </ul>

    <div class="cta-section">
      <a class="cta-button" href="<?= htmlspecialchars($listPage, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?>一覧に戻る</a>
      <a class="cta-button secondary" href="<?= htmlspecialchars($formPage, ENT_QUOTES, 'UTF-8') ?>">続けて投稿する</a>
    </div>
  </main>
</body>
</html>
