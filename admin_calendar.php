<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

requireAdmin();

$user = getAuthenticatedUser();
$displayName = $user['display_name'] ?? '';

$validRooms = [
    'large' => '大会議室',
    'small' => '小会議室',
];

$timezone = new DateTimeZone('Asia/Tokyo');
$today = new DateTimeImmutable('today', $timezone);

$yearParam = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$monthParam = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
if ($yearParam === false || $yearParam === null) {
    $yearParam = (int) $today->format('Y');
}
if ($monthParam === false || $monthParam === null || $monthParam < 1 || $monthParam > 12) {
    $monthParam = (int) $today->format('n');
}

try {
    $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $yearParam, $monthParam), $timezone);
} catch (Exception $exception) {
    $monthStart = $today->modify('first day of this month');
}

$monthEnd = $monthStart->modify('+1 month');

$selectedDateParam = filter_input(INPUT_GET, 'selected_date', FILTER_UNSAFE_RAW);
$selectedDate = null;
if ($selectedDateParam) {
    $candidate = DateTimeImmutable::createFromFormat('Y-m-d', $selectedDateParam, $timezone);
    if ($candidate instanceof DateTimeImmutable) {
        $selectedDate = $candidate;
    }
}
if (!$selectedDate) {
    $selectedDate = $today->format('Y-m') === $monthStart->format('Y-m') ? $today : $monthStart;
}

$errors = [];
$successMessage = null;

$selectedRoomValue = isset($_POST['room']) ? (string) $_POST['room'] : 'large';
$reservedForValue = isset($_POST['reserved_for']) ? trim((string) $_POST['reserved_for']) : $displayName;
$noteValue = isset($_POST['note']) ? trim((string) $_POST['note']) : '';
$reservedAtValue = isset($_POST['reserved_at']) ? (string) $_POST['reserved_at'] : $selectedDate->format('Y-m-d') . 'T09:00';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room = $selectedRoomValue;
    $reservedAtInput = $reservedAtValue;
    $reservedFor = $reservedForValue;
    $note = $noteValue;
    $formYear = isset($_POST['current_year']) ? (int) $_POST['current_year'] : (int) $monthStart->format('Y');
    $formMonth = isset($_POST['current_month']) ? (int) $_POST['current_month'] : (int) $monthStart->format('n');
    $selectedDateInput = isset($_POST['selected_date']) ? (string) $_POST['selected_date'] : $selectedDate->format('Y-m-d');

    if (!isset($validRooms[$room])) {
        $errors[] = '会議室の選択が正しくありません。';
        $selectedRoomValue = 'large';
    } else {
        $selectedRoomValue = $room;
    }

    if ($reservedAtInput === '') {
        $errors[] = '予約日時を入力してください。';
    }

    $reservedAt = null;
    if ($reservedAtInput !== '') {
        $reservedAt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $reservedAtInput, $timezone);
        if (!$reservedAt) {
            $errors[] = '予約日時の形式が正しくありません。';
        }
    }

    if ($reservedAt instanceof DateTimeImmutable) {
        if ($reservedAt < (new DateTimeImmutable('now', $timezone))->modify('-1 hour')) {
            $errors[] = '過去の日時は予約できません。';
        }
    }

    if ($reservedFor === '') {
        $errors[] = '利用者名を入力してください。';
    }

    $reservedForValue = $reservedFor;
    $noteValue = $note;

    $documentPath = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['document']['tmp_name'];
            $originalName = $_FILES['document']['name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension !== 'pdf') {
                $errors[] = 'PDFファイルのみアップロードできます。';
            } else {
                $uploadDir = __DIR__ . '/uploads/reservations/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                try {
                    $uniqueName = sprintf('reservation_%s_%s.pdf', date('YmdHis'), bin2hex(random_bytes(4)));
                } catch (Exception $randomException) {
                    $uniqueName = null;
                    $errors[] = 'ファイル名の生成に失敗しました。時間をおいて再度お試しください。';
                }
                if ($uniqueName !== null) {
                    $targetPath = $uploadDir . $uniqueName;
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $documentPath = 'uploads/reservations/' . $uniqueName;
                    } else {
                        $errors[] = 'PDFファイルの保存に失敗しました。';
                    }
                }
            }
        } else {
            $errors[] = 'PDFファイルのアップロード中にエラーが発生しました。';
        }
    }

    if (!$errors && $reservedAt instanceof DateTimeImmutable) {
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('INSERT INTO reservations (room, reserved_at, user_id, reserved_for, note, document_path) VALUES (:room, :reserved_at, :user_id, :reserved_for, :note, :document_path)');
            $stmt->execute([
                ':room' => $room,
                ':reserved_at' => $reservedAt->format('Y-m-d H:i:s'),
                ':user_id' => $user['id'],
                ':reserved_for' => $reservedFor,
                ':note' => $note !== '' ? $note : null,
                ':document_path' => $documentPath,
            ]);

            $successMessage = sprintf('%sを%sに予約しました。', $validRooms[$room], $reservedAt->format('Y/m/d H:i'));

            $redirectQuery = http_build_query([
                'year' => $formYear,
                'month' => $formMonth,
                'selected_date' => $reservedAt->format('Y-m-d'),
                'success' => 1,
                'message' => $successMessage,
            ]);
            header('Location: admin_calendar.php?' . $redirectQuery);
            exit;
        } catch (PDOException $exception) {
            if ($documentPath !== null) {
                @unlink(__DIR__ . '/' . $documentPath);
            }
            if ((int) $exception->getCode() === 23000) {
                $errors[] = '指定した日時はすでに予約されています。別の時間を選択してください。';
            } else {
                $errors[] = '予約の登録に失敗しました。時間をおいて再度お試しください。';
            }
        } catch (Throwable $exception) {
            if ($documentPath !== null) {
                @unlink(__DIR__ . '/' . $documentPath);
            }
            $errors[] = '予期しないエラーが発生しました。時間をおいて再度お試しください。';
        }
    }

    try {
        $candidate = new DateTimeImmutable($selectedDateInput, $timezone);
        $selectedDate = $candidate;
    } catch (Exception $exception) {
        // ignore
    }

    if (!empty($errors) && $documentPath !== null) {
        @unlink(__DIR__ . '/' . $documentPath);
        $documentPath = null;
    }
}

if (isset($_GET['success']) && $_GET['success'] !== '') {
    $messageParam = filter_input(INPUT_GET, 'message', FILTER_UNSAFE_RAW);
    $successMessage = $messageParam !== null ? $messageParam : '予約を登録しました。';
}

$calendarStart = $monthStart->modify('-' . (int) $monthStart->format('w') . ' days');
$calendarDays = [];
for ($i = 0; $i < 42; $i++) {
    $calendarDays[] = $calendarStart->modify('+' . $i . ' days');
}

$reservationsByDate = [];
$reservationsForSelectedDate = [];
try {
    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT id, room, reserved_at, reserved_for, note, document_path FROM reservations WHERE reserved_at >= :start AND reserved_at < :end ORDER BY reserved_at');
    $stmt->execute([
        ':start' => $monthStart->format('Y-m-d H:i:s'),
        ':end' => $monthEnd->format('Y-m-d H:i:s'),
    ]);
    foreach ($stmt->fetchAll() as $row) {
        $reservedAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['reserved_at'], $timezone);
        if (!$reservedAt) {
            continue;
        }
        $dateKey = $reservedAt->format('Y-m-d');
        $reservationItem = [
            'time' => $reservedAt->format('H:i'),
            'room' => $row['room'],
            'room_label' => $validRooms[$row['room']] ?? $row['room'],
            'reserved_for' => $row['reserved_for'],
            'note' => $row['note'] ?? '',
            'document_path' => $row['document_path'],
        ];
        $reservationsByDate[$dateKey][] = $reservationItem;
    }

    $selectedKey = $selectedDate->format('Y-m-d');
    $reservationsForSelectedDate = $reservationsByDate[$selectedKey] ?? [];
} catch (Throwable $exception) {
    $errors[] = 'カレンダーの情報を取得できませんでした。';
}

$prevMonth = $monthStart->modify('-1 month');
$nextMonth = $monthStart->modify('+1 month');
$prevSelectedDate = $selectedDate->modify('-1 month');
$nextSelectedDate = $selectedDate->modify('+1 month');

$reservedAtValue = $selectedDate->format('Y-m-d') . 'T09:00';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserved_at'])) {
    $reservedAtValue = (string) $_POST['reserved_at'];
}

$weekdayLabels = ['日', '月', '火', '水', '木', '金', '土'];
$selectedDayLabel = $selectedDate->format('Y年n月j日') . '（' . $weekdayLabels[(int) $selectedDate->format('w')] . '）';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会議室カレンダー | 高橋建設</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-admin-calendar">
  <header class="admin-calendar-header">
    <div class="admin-calendar-header__inner">
      <a class="back-link" href="index.php">&larr; 管理トップに戻る</a>
      <div class="admin-calendar-header__titles">
        <h1 class="admin-calendar-title">会議室カレンダー</h1>
        <p class="admin-calendar-subtitle">カレンダーから日付を選んで、PDF資料付きで予約を登録できます。</p>
      </div>
      <div class="user-menu">
        <span class="user-menu__label">ようこそ、<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>さん（管理者）</span>
        <a class="user-menu__link" href="logout.php">ログアウト</a>
      </div>
    </div>
  </header>

  <main class="admin-calendar-main">
    <?php if ($successMessage): ?>
      <div class="admin-calendar-alert admin-calendar-alert--success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="admin-calendar-alert admin-calendar-alert--error" role="alert">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="admin-calendar-layout">
      <section class="admin-calendar-panel" aria-labelledby="calendarHeading">
        <div class="admin-calendar-month-bar">
          <a class="admin-calendar-month-bar__link" href="admin_calendar.php?year=<?= $prevMonth->format('Y') ?>&amp;month=<?= $prevMonth->format('n') ?>&amp;selected_date=<?= htmlspecialchars($prevSelectedDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" aria-label="前の月へ">&larr;</a>
          <h2 id="calendarHeading" class="admin-calendar-month-bar__title"><?= $monthStart->format('Y年n月') ?></h2>
          <a class="admin-calendar-month-bar__link" href="admin_calendar.php?year=<?= $nextMonth->format('Y') ?>&amp;month=<?= $nextMonth->format('n') ?>&amp;selected_date=<?= htmlspecialchars($nextSelectedDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" aria-label="次の月へ">&rarr;</a>
        </div>
        <div class="admin-calendar-grid" role="grid">
          <?php foreach (['日','月','火','水','木','金','土'] as $weekday): ?>
            <div class="admin-calendar-grid__header" role="columnheader"><?= $weekday ?></div>
          <?php endforeach; ?>
          <?php foreach ($calendarDays as $day): ?>
            <?php
              $isCurrentMonth = $day->format('Y-m') === $monthStart->format('Y-m');
              $dateKey = $day->format('Y-m-d');
              $hasReservations = !empty($reservationsByDate[$dateKey]);
              $isSelected = $selectedDate->format('Y-m-d') === $dateKey;
              $weekdayLabel = $weekdayLabels[(int) $day->format('w')];
              $ariaLabel = $day->format('n月j日') . '（' . $weekdayLabel . '）の予定を確認';
            ?>
            <button
              type="button"
              class="admin-calendar-grid__cell<?= $isCurrentMonth ? '' : ' is-outside' ?><?= $hasReservations ? ' has-reservation' : '' ?><?= $isSelected ? ' is-selected' : '' ?>"
              data-date="<?= htmlspecialchars($dateKey, ENT_QUOTES, 'UTF-8') ?>"
              aria-pressed="<?= $isSelected ? 'true' : 'false' ?>"
              aria-label="<?= htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') ?>"
            >
              <span class="admin-calendar-grid__date"><?= (int) $day->format('j') ?></span>
              <?php if ($hasReservations): ?>
                <span class="admin-calendar-grid__badge">予約<?= count($reservationsByDate[$dateKey]) ?>件</span>
              <?php endif; ?>
            </button>
          <?php endforeach; ?>
        </div>
      </section>

      <aside class="admin-calendar-side">
        <section class="admin-calendar-details" aria-labelledby="dayDetailHeading">
          <h2 id="dayDetailHeading" class="admin-calendar-details__title"><?= htmlspecialchars($selectedDayLabel, ENT_QUOTES, 'UTF-8') ?>の予定</h2>
          <?php if ($reservationsForSelectedDate): ?>
            <ul class="admin-calendar-reservations">
              <?php foreach ($reservationsForSelectedDate as $reservation): ?>
                <li class="admin-calendar-reservations__item">
                  <div class="admin-calendar-reservations__time"><?= htmlspecialchars($reservation['time'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="admin-calendar-reservations__body">
                    <div class="admin-calendar-reservations__room"><?= htmlspecialchars($reservation['room_label'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="admin-calendar-reservations__meta">利用者：<?= htmlspecialchars($reservation['reserved_for'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($reservation['note'] !== ''): ?>
                      <div class="admin-calendar-reservations__note">備考：<?= nl2br(htmlspecialchars($reservation['note'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($reservation['document_path'])): ?>
                      <a class="admin-calendar-reservations__attachment" href="<?= htmlspecialchars($reservation['document_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">📄 添付PDF</a>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="admin-calendar-details__empty">予定はまだ登録されていません。</p>
          <?php endif; ?>
        </section>

        <section class="admin-calendar-form" id="reservationFormSection" aria-labelledby="reservationFormHeading">
          <h2 id="reservationFormHeading" class="admin-calendar-form__title">予約を登録</h2>
          <form method="post" enctype="multipart/form-data" class="admin-calendar-form__form" novalidate>
            <input type="hidden" name="current_year" value="<?= (int) $monthStart->format('Y') ?>">
            <input type="hidden" name="current_month" value="<?= (int) $monthStart->format('n') ?>">
            <input type="hidden" id="selectedDateInput" name="selected_date" value="<?= htmlspecialchars($selectedDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">

            <label class="admin-calendar-form__field">
              <span class="admin-calendar-form__label">会議室</span>
              <select name="room" id="room" required>
                <?php foreach ($validRooms as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedRoomValue === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="admin-calendar-form__field">
              <span class="admin-calendar-form__label">日時</span>
              <input type="datetime-local" id="reservedAtField" name="reserved_at" value="<?= htmlspecialchars($reservedAtValue, ENT_QUOTES, 'UTF-8') ?>" required>
            </label>

            <label class="admin-calendar-form__field">
              <span class="admin-calendar-form__label">利用者名</span>
              <input type="text" name="reserved_for" value="<?= htmlspecialchars($reservedForValue, ENT_QUOTES, 'UTF-8') ?>" required>
            </label>

            <label class="admin-calendar-form__field">
              <span class="admin-calendar-form__label">備考</span>
              <textarea name="note" rows="3" placeholder="共有事項や会議の目的などを入力してください。"><?= htmlspecialchars($noteValue, ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>

            <label class="admin-calendar-form__field">
              <span class="admin-calendar-form__label">添付PDF</span>
              <input type="file" name="document" id="documentUpload" accept="application/pdf">
              <small class="admin-calendar-form__hint">議事次第などの資料をアップロードできます。</small>
            </label>

            <div id="pdfPreviewContainer" class="admin-calendar-pdf" hidden>
              <p class="admin-calendar-pdf__title">PDFプレビュー</p>
              <embed id="pdfPreview" type="application/pdf" width="100%" height="220">
            </div>

            <div class="admin-calendar-form__actions">
              <button type="submit" class="reservation-button">予約を登録する</button>
            </div>
          </form>
        </section>
      </aside>
    </div>
  </main>

  <script>
    (function() {
      const dayButtons = document.querySelectorAll('.admin-calendar-grid__cell');
      const selectedDateInput = document.getElementById('selectedDateInput');
      const reservedAtField = document.getElementById('reservedAtField');
      const pdfUpload = document.getElementById('documentUpload');
      const pdfContainer = document.getElementById('pdfPreviewContainer');
      const pdfPreview = document.getElementById('pdfPreview');

      dayButtons.forEach((button) => {
        button.addEventListener('click', () => {
          const date = button.getAttribute('data-date');
          if (!date) {
            return;
          }
          dayButtons.forEach((btn) => btn.classList.remove('is-selected'));
          button.classList.add('is-selected');
          if (selectedDateInput) {
            selectedDateInput.value = date;
          }
          if (reservedAtField) {
            const current = reservedAtField.value;
            const time = current && current.includes('T') ? current.split('T')[1] : '09:00';
            reservedAtField.value = `${date}T${time}`;
          }
          const params = new URLSearchParams(window.location.search);
          params.set('year', date.substring(0, 4));
          params.set('month', String(parseInt(date.substring(5, 7), 10)));
          params.set('selected_date', date);
          params.delete('success');
          const newUrl = `${window.location.pathname}?${params.toString()}`;
          window.location.assign(newUrl + '#calendarHeading');
        });
      });

      if (pdfUpload && pdfContainer && pdfPreview) {
        pdfUpload.addEventListener('change', (event) => {
          const file = event.target.files && event.target.files[0];
          if (!file || file.type !== 'application/pdf') {
            pdfContainer.hidden = true;
            pdfPreview.removeAttribute('src');
            return;
          }
          const fileURL = URL.createObjectURL(file);
          pdfPreview.src = fileURL;
          pdfContainer.hidden = false;
        });
      }
    })();
  </script>
</body>
</html>
