<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

requireLogin();

$user = getAuthenticatedUser();
$isAdmin = ($user['role'] ?? '') === 'admin';
if ($isAdmin) {
    header('Location: index.php');
    exit;
}

$displayName = $user['display_name'] ?? '';

$validRooms = ['small' => '小会議室', 'large' => '大会議室'];
$daysOfWeek = [
    1 => '月',
    2 => '火',
    3 => '水',
    4 => '木',
    5 => '金',
    6 => '土',
    7 => '日',
];

$weeklySchedules = array_fill_keys(array_keys($daysOfWeek), []);
$scheduleError = null;

try {
    $pdo = getPdo();

    $today = new DateTimeImmutable('today');
    $weekStart = $today->modify('monday this week')->setTime(0, 0, 0);
    $weekEnd = $weekStart->modify('+6 days')->setTime(23, 59, 59);

    $stmt = $pdo->prepare(
        'SELECT room, reserved_at, reserved_for, note FROM reservations
        WHERE user_id = :user_id AND reserved_at BETWEEN :start AND :end
        ORDER BY reserved_at'
    );
    $stmt->execute([
        ':user_id' => $user['id'],
        ':start' => $weekStart->format('Y-m-d H:i:s'),
        ':end' => $weekEnd->format('Y-m-d H:i:s'),
    ]);

    foreach ($stmt->fetchAll() as $reservation) {
        $reservedAt = new DateTimeImmutable($reservation['reserved_at']);
        $dayNumber = (int) $reservedAt->format('N');

        if (!isset($weeklySchedules[$dayNumber])) {
            continue;
        }

        $weeklySchedules[$dayNumber][] = [
            'time' => $reservedAt->format('H:i'),
            'room_label' => $validRooms[$reservation['room']] ?? $reservation['room'],
            'reserved_for' => $reservation['reserved_for'],
            'note' => $reservation['note'],
        ];
    }
} catch (Throwable $exception) {
    $scheduleError = '今週の予定を取得できませんでした。時間をおいて再度お試しください。';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ホーム | 高橋建設</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-user-home">
  <header class="user-home-header">
    <div class="user-home-header__inner">
      <div class="user-menu">
        <span class="user-menu__label">ようこそ、<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>さん（一般ユーザー）</span>
        <a class="user-menu__link" href="reservations_select.php">会議室を予約</a>
        <a class="user-menu__link" href="logout.php">ログアウト</a>
      </div>
      <h1 class="user-home-title">高橋建設</h1>
      <p class="user-home-subtitle">日々の業務やお知らせに素早くアクセスできる社内ポータルです。</p>
    </div>
  </header>

  <main class="user-home-main">
    <section class="user-home-actions" aria-labelledby="quickLinks"><br>
      <div class="user-home-buttons">
        <a class="user-home-button user-home-button--reserve" href="reservations_select.php">会議室を予約</a>
        <a class="user-home-button user-home-button--important" href="form.php">重要なお知らせを見る</a>
        <a class="user-home-button user-home-button--other" href="sonota.php">その他のお知らせを見る</a>
      </div>
    </section>

    <section class="user-home-schedule" aria-labelledby="weeklySchedule">
      <h2 id="weeklySchedule" class="user-home-section-title">🗓 今週の予定</h2>
      <?php if ($scheduleError !== null): ?>
        <p class="user-home-schedule__item"><?= htmlspecialchars($scheduleError, ENT_QUOTES, 'UTF-8') ?></p>
      <?php else: ?>
        <div id="scheduleList" class="user-home-schedule__list" aria-live="polite">
          <?php foreach ($daysOfWeek as $dayNumber => $dayLabel): ?>
            <div class="user-home-schedule__item user-home-schedule__item--day">【<?= htmlspecialchars($dayLabel, ENT_QUOTES, 'UTF-8') ?>】</div>
            <?php if (!empty($weeklySchedules[$dayNumber])): ?>
              <?php foreach ($weeklySchedules[$dayNumber] as $item): ?>
                <div class="user-home-schedule__item">
                  ・<?= htmlspecialchars($item['time'], ENT_QUOTES, 'UTF-8') ?>
                  <?= htmlspecialchars($item['room_label'], ENT_QUOTES, 'UTF-8') ?>（利用者：<?= htmlspecialchars($item['reserved_for'], ENT_QUOTES, 'UTF-8') ?>）
                  <?php if ($item['note'] !== null && $item['note'] !== ''): ?>
                    / 備考：<?= htmlspecialchars($item['note'], ENT_QUOTES, 'UTF-8') ?>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="user-home-schedule__item">・予定なし</div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
