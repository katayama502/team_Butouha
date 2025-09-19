<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
requireLogin();

$user = getAuthenticatedUser();
$displayName = $user['display_name'] ?? '';
$roleLabel = ($user['role'] ?? '') === 'admin' ? '管理者' : '一般ユーザー';

$validRooms = [
    'large' => '大会議室',
    'small' => '小会議室',
    'other' => 'その他',
];

$room = isset($_GET['room']) ? (string) $_GET['room'] : '';
if (!isset($validRooms[$room])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>会議室が見つかりません</title></head><body><p>指定された会議室は存在しません。</p></body></html>';
    exit;
}

$timezone = new DateTimeZone('Asia/Tokyo');
$today = new DateTimeImmutable('today', $timezone);
$weekStartParam = isset($_GET['week_start']) ? (string) $_GET['week_start'] : '';
$baseDate = $today;
if ($weekStartParam !== '') {
    $candidate = DateTimeImmutable::createFromFormat('Y-m-d', $weekStartParam, $timezone);
    if ($candidate instanceof DateTimeImmutable) {
        $baseDate = $candidate;
    }
}

$weekStart = $baseDate->setTime(0, 0)->modify('Monday this week');
$weekEnd = $weekStart->modify('+5 days');

$days = [];
for ($i = 0; $i < 5; $i++) {
    $day = $weekStart->modify("+{$i} days");
    $days[] = $day;
}

$timeSlots = [];
for ($hour = 8; $hour <= 16; $hour++) {
    $timeSlots[] = sprintf('%02d:00', $hour);
}

$reservationMap = [];
$errorMessage = '';
try {
    $pdo = getPdo();
$stmt = $pdo->prepare('SELECT id, user_id, reserved_at, reserved_for, note, document_path FROM reservations WHERE room = :room AND reserved_at >= :start AND reserved_at < :end ORDER BY reserved_at');
    $stmt->execute([
        ':room' => $room,
        ':start' => $weekStart->format('Y-m-d H:i:s'),
        ':end' => $weekEnd->format('Y-m-d H:i:s'),
    ]);
    foreach ($stmt->fetchAll() as $row) {
        $reservedAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['reserved_at'], $timezone);
        if (!$reservedAt) {
            continue;
        }
        $slotKey = $reservedAt->format('Y-m-d\TH:i');
        $reservationMap[$slotKey] = [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'reserved_for' => $row['reserved_for'],
            'note' => $row['note'] ?? '',
            'document_path' => $row['document_path'] ?? null,
            'reserved_at' => $reservedAt,
        ];
    }
} catch (Throwable $exception) {
    $errorMessage = '予約情報を取得できませんでした。時間をおいて再度お試しください。';
}

function formatDayLabel(DateTimeImmutable $date): string
{
    static $weekdayMap = ['日','月', '火', '水', '木', '金','土'];
    return $date->format('n/j') . ' (' . $weekdayMap[(int) $date->format('w')] . ')';
}

function formatRangeLabel(DateTimeImmutable $start, DateTimeImmutable $end): string
{
    static $weekdayMap = ['日','月', '火', '水', '木', '金','土'];
    $endPrev = $end->modify('-1 day');
    return $start->format('Y/m/d') . ' (' . $weekdayMap[(int) $start->format('w')] . ') ~ ' . $endPrev->format('Y/m/d') . ' (' . $weekdayMap[(int) $endPrev->format('w')] . ')';
}

$weekRangeLabel = formatRangeLabel($weekStart, $weekEnd);
$prevWeekUrl = 'room_calendar.php?room=' . urlencode($room) . '&week_start=' . $weekStart->modify('-7 days')->format('Y-m-d');
$nextWeekUrl = 'room_calendar.php?room=' . urlencode($room) . '&week_start=' . $weekStart->modify('+7 days')->format('Y-m-d');
$currentWeekUrl = 'room_calendar.php?room=' . urlencode($room);

$todayKey = $today->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($validRooms[$room], ENT_QUOTES, 'UTF-8') ?>の予約カレンダー | 高橋建設</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-room-calendar">
  <header class="calendar-header">
    <div class="calendar-header__inner">
      <div class="calendar-header__breadcrumbs">
        <a class="back-link" href="reservations_select.php">&larr; 会議室選択に戻る</a>
      </div>
      <div class="calendar-header__title-area">
        <h1 class="calendar-title"><?= htmlspecialchars($validRooms[$room], ENT_QUOTES, 'UTF-8') ?>の予約カレンダー</h1>
        <p class="calendar-range" aria-live="polite"><?= htmlspecialchars($weekRangeLabel, ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div class="user-menu">
        <span class="user-menu__label">ようこそ、<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>さん（<?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>）</span>
        <a class="user-menu__link" href="logout.php">ログアウト</a>
      </div>
    </div>
  </header>

  <main class="calendar-main">
    <section class="calendar-controls" aria-label="週の切り替え">
      <div class="calendar-controls__buttons">
        <a class="calendar-nav-button" href="<?= htmlspecialchars($prevWeekUrl, ENT_QUOTES, 'UTF-8') ?>">前の週</a>
        <a class="calendar-nav-button calendar-nav-button--today" href="<?= htmlspecialchars($currentWeekUrl, ENT_QUOTES, 'UTF-8') ?>">今週</a>
        <a class="calendar-nav-button" href="<?= htmlspecialchars($nextWeekUrl, ENT_QUOTES, 'UTF-8') ?>">次の週</a>
      </div>
      <button type="button" class="calendar-nav-button calendar-nav-button--refresh" data-calendar-refresh>最新の状態に更新</button>
    </section>

    <?php if ($errorMessage !== ''): ?>
      <p class="calendar-alert" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="calendar-wrapper">
      <table class="calendar-table" aria-describedby="calendarLegend">
        <thead>
          <tr>
            <th scope="col" class="calendar-table__time">時間</th>
            <?php foreach ($days as $day): ?>
              <?php $isToday = $day->format('Y-m-d') === $todayKey; ?>
              <th scope="col" class="calendar-table__day<?= $isToday ? ' is-today' : '' ?>">
                <?= htmlspecialchars(formatDayLabel($day), ENT_QUOTES, 'UTF-8') ?>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($timeSlots as $slot): ?>
            <tr>
              <th scope="row" class="calendar-table__time"><?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?></th>
              <?php foreach ($days as $day): ?>
                <?php
                  $slotDateTime = $day->format('Y-m-d') . 'T' . $slot;
                  $reservation = $reservationMap[$slotDateTime] ?? null;
                  $cellClasses = 'calendar-cell';
                  if ($reservation) {
                      $cellClasses .= ' calendar-cell--reserved';
                  } else {
                      $cellClasses .= ' calendar-cell--available';
                  }
                  if ($day->format('Y-m-d') === $todayKey) {
                      $cellClasses .= ' is-today';
                  }
                ?>
                <td class="<?= $cellClasses ?>">
                  <?php if ($reservation): ?>
                    <?php
                      $initial = mb_substr($reservation['reserved_for'], 0, 1, 'UTF-8');
                      $note = $reservation['note'] !== '' ? $reservation['note'] : $reservation['reserved_for'];
                      $reservedAtObject = $reservation['reserved_at'] instanceof DateTimeImmutable ? $reservation['reserved_at'] : null;
                      $reservedAtLabel = $reservedAtObject ? $reservedAtObject->format('Y/m/d H:i') : '';
                      $canDelete = (($user['role'] ?? '') === 'admin') || ((int) ($reservation['user_id'] ?? 0) === (int) ($user['id'] ?? 0));
                    ?>
                    <div class="reservation-chip" title="<?= htmlspecialchars($reservation['reserved_for'], ENT_QUOTES, 'UTF-8') ?>">
                      <span class="reservation-chip__icon" aria-hidden="true"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
                      <div class="reservation-chip__content">
                        <span class="reservation-chip__title"><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="reservation-chip__meta">予約者：<?= htmlspecialchars($reservation['reserved_for'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($reservation['document_path'])): ?>
                          <a class="reservation-chip__attachment" href="<?= htmlspecialchars($reservation['document_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">📄 資料を見る</a>
                        <?php endif; ?>
                        <?php if ($canDelete && isset($reservation['id'])): ?>
                          <div class="reservation-chip__actions">
                            <button
                              type="button"
                              class="reservation-chip__delete"
                              data-reservation-delete="<?= (int) $reservation['id'] ?>"
                              data-reservation-label="<?= htmlspecialchars($reservedAtLabel, ENT_QUOTES, 'UTF-8') ?>"
                              aria-label="<?= htmlspecialchars(($reservedAtLabel !== '' ? $reservedAtLabel . 'の予約を削除' : '予約を削除'), ENT_QUOTES, 'UTF-8') ?>"
                            >削除</button>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php else: ?>
                    <button
                      type="button"
                      class="calendar-slot"
                      data-slot
                      data-status="available"
                      data-room="<?= htmlspecialchars($room, ENT_QUOTES, 'UTF-8') ?>"
                      data-datetime="<?= htmlspecialchars($slotDateTime, ENT_QUOTES, 'UTF-8') ?>"
                      aria-label="<?= htmlspecialchars($day->format('n月j日'), ENT_QUOTES, 'UTF-8') ?>の<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>を予約する"
                    >
                      ○
                    </button>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p id="calendarLegend" class="calendar-legend">○の枠をクリックすると予約を登録できます。青いカードは既に予約済みの時間です。</p>
    </div>

    <div class="calendar-message" role="status" aria-live="polite"></div>
  </main>

  <div class="reservation-modal" id="reservationModal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="reservation-modal__overlay" data-close-modal></div>
    <div class="reservation-modal__content">
      <h2 class="reservation-modal__title">予約の作成</h2>
      <form
        id="reservationForm"
        class="reservation-form"
        data-room="<?= htmlspecialchars($room, ENT_QUOTES, 'UTF-8') ?>"
        data-endpoint="reservation_calendar_api.php"
        data-delete-endpoint="reservation_delete_api.php"
        data-default-reserved-for="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
      >
        <label class="reservation-form__field">
          <span class="reservation-form__label">日時</span>
          <input type="datetime-local" id="reservationDateTime" name="reserved_at" required>
        </label>
        <label class="reservation-form__field">
          <span class="reservation-form__label">予約者</span>
          <input
            type="text"
            id="reservationReservedFor"
            name="reserved_for"
            value="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
            required
          >
        </label>
        <label class="reservation-form__field">
          <span class="reservation-form__label">メモ</span>
          <textarea id="reservationNote" name="note" rows="3" placeholder="打ち合わせ名や共有事項を入力してください"></textarea>
        </label>
        <div class="reservation-form__actions">
          <button type="button" class="reservation-button reservation-button--sub" data-close-modal>閉じる</button>
          <button type="submit" class="reservation-button">保存</button>
        </div>
      </form>
    </div>
  </div>

  <script src="calendar.js" defer></script>
</body>
</html>
