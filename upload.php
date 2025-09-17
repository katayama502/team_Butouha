<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';

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

if (!empty($_POST['audioData'])) {
    $audioDataUri = $_POST['audioData'];
    $audioPattern = '/^data:audio\/([a-z0-9.+-]+)(;codecs=[^;]+)?;base64,/i';

    if (preg_match($audioPattern, $audioDataUri, $matches)) {
        $audioData = substr($audioDataUri, strpos($audioDataUri, ',') + 1);
        $audioBinary = base64_decode($audioData, true);

        if ($audioBinary !== false) {
            $mimeSubtype = strtolower($matches[1]);
            $extensionMap = [
                'webm' => 'webm',
                'ogg' => 'ogg',
                'mpeg' => 'mp3',
                'mp3' => 'mp3',
                'mp4' => 'm4a',
                'x-m4a' => 'm4a',
                '3gpp' => '3gp',
                'wav' => 'wav',
            ];
            $audioExtension = $extensionMap[$mimeSubtype] ?? 'webm';
            $audioName = 'voice_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $audioExtension;
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
    } else {
        $errors[] = 'サポートされていない音声データ形式です。';
    }
}

if (empty($errors)) {
    try {
        $pdo = getPdo();
        $stmt = $pdo->prepare('INSERT INTO posts (title, pdf_path, audio_path) VALUES (:title, :pdf_path, :audio_path)');
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

    <ul class="upload-summary">
      <li>PDF：<?= $uploadedPdf ? htmlspecialchars($uploadedPdf, ENT_QUOTES, 'UTF-8') : '未アップロード' ?></li>
      <li>音声：<?= $uploadedAudio ? htmlspecialchars($uploadedAudio, ENT_QUOTES, 'UTF-8') : '未アップロード' ?></li>
    </ul>

    <div class="cta-section">
      <a class="cta-button" href="form.php">一覧に戻る</a>
      <a class="cta-button secondary" href="style.html">続けて投稿する</a>
    </div>
  </main>
</body>
</html>
