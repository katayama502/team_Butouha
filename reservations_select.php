<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$user = getAuthenticatedUser();
$displayName = $user['display_name'] ?? '';
$roleLabel = ($user['role'] ?? '') === 'admin' ? '管理者' : '一般ユーザー';

$roomPages = [
    'large' => [
        'label' => '大会議室',
        'description' => '最大20名まで利用できる広々とした会議スペースです。',
        'link' => 'room_calendar.php?room=large',
    ],
    'small' => [
        'label' => '小会議室',
        'description' => '4〜6名での打ち合わせに最適なコンパクトな会議室です。',
        'link' => 'room_calendar.php?room=small',
    ],
    'other' => [
        'label' => 'その他',
        'description' => 'フリースペースや臨時利用スペースの予定を確認できます。',
        'link' => 'room_calendar.php?room=other',
    ],
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会議室を選択 | 高橋建設</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-room-selection">
  <header class="selection-header">
    <div class="selection-header__inner">
      <a class="back-link" href="me.php">&larr; ホームに戻る</a>
      <div class="selection-header__title-area">
        <h1 class="selection-title">会議室予約</h1>
        <p class="selection-subtitle">利用したい会議室を選んでください。</p>
      </div>
      <div class="user-menu">
        <span class="user-menu__label">ようこそ、<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>さん（<?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>）</span>
        <a class="user-menu__link" href="logout.php">ログアウト</a>
      </div>
    </div>
  </header>

  <main class="selection-main" aria-labelledby="roomSelectionHeading">
    <h2 id="roomSelectionHeading" class="selection-main__title">会議室を選択</h2>
    <div class="room-options" role="list">
      <?php foreach ($roomPages as $roomKey => $room): ?>
        <a class="room-option" role="listitem" href="<?= htmlspecialchars($room['link'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="room-option__icon" aria-hidden="true">
            <?php
              $icon = '📌';
              if ($roomKey === 'large') {
                  $icon = '🏢';
              } elseif ($roomKey === 'other') {
                  $icon = '🗂️';
              }
            ?>
            <?= $icon ?>
          </div>
          <div class="room-option__content">
            <h3 class="room-option__title"><?= htmlspecialchars($room['label'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="room-option__description"><?= htmlspecialchars($room['description'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="room-option__chevron" aria-hidden="true">&rarr;</div>
        </a>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
