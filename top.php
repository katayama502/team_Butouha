<?php
// アップロード先フォルダ（書き込み可能にしておくこと）
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// タイトル（使ってないけど受け取る）
$title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');

// PDF保存
if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $pdfTmpPath = $_FILES['pdf']['tmp_name'];
    $pdfName = basename($_FILES['pdf']['name']);
    move_uploaded_file($pdfTmpPath, $uploadDir . $pdfName);
}

// 音声保存
if (!empty($_POST['audioData'])) {
    $audioData = $_POST['audioData'];

    // base64データをデコードして保存
    if (preg_match('/^data:audio\/webm;base64,/', $audioData)) {
        $audioData = substr($audioData, strpos($audioData, ',') + 1);
        $audioData = base64_decode($audioData);

        $filename = 'voice_' . time() . '.webm';
        file_put_contents($uploadDir . $filename, $audioData);
    }
}

echo "<h2>アップロードが完了しました。</h2>";
echo "<p><a href='index.html'>戻る</a></p>";
