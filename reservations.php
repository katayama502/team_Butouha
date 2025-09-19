<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

requireLogin();
$user = getAuthenticatedUser();

$errors = [];
$successMessage = null;
$room = isset($_POST['room']) ? (string) $_POST['room'] : 'small';
$reservedAtInput = isset($_POST['reserved_at']) ? (string) $_POST['reserved_at'] : '';
$reservedFor = isset($_POST['reserved_for']) ? trim((string) $_POST['reserved_for']) : ($user['display_name'] ?? '');
$note = isset($_POST['note']) ? trim((string) $_POST['note']) : '';

$validRooms = [
    'small' => '小会議室',
    'large' => '大会議室',
    'other' => 'その他',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($validRooms[$room])) {
        $errors[] = '会議室の選択が正しくありません。';
    }

    if ($reservedAtInput === '') {
        $errors[] = '予約日時を入力してください。';
    }

    $reservedAt = null;
    if ($reservedAtInput !== '') {
        try {
            $reservedAt = new DateTimeImmutable($reservedAtInput);
        } catch (Exception $exception) {
            $errors[] = '予約日時の形式が正しくありません。';
        }
    }

    if ($reservedAt instanceof DateTimeImmutable) {
        if ($reservedAt < new DateTimeImmutable('-1 hour')) {
            $errors[] = '過去の日時は予約できません。';
        }
    }

    if ($reservedFor === '') {
        $errors[] = '予約者名を入力してください。';
    }

    if (!$errors && $reservedAt instanceof DateTimeImmutable) {
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('INSERT INTO reservations (room, reserved_at, user_id, reserved_for, note) VALUES (:room, :reserved_at, :user_id, :reserved_for, :note)');
            $stmt->execute([
                ':room' => $room,
                ':reserved_at' => $reservedAt->format('Y-m-d H:i:s'),
                ':user_id' => $user['id'],
                ':reserved_for' => $reservedFor,
                ':note' => $note !== '' ? $note : null,
            ]);
            $successMessage = sprintf('%sを%sに予約しました。', $validRooms[$room], $reservedAt->format('Y/m/d H:i'));
            $reservedAtInput = '';
            $note = '';
        } catch (PDOException $exception) {
            if ((int) $exception->getCode() === 23000) {
                $errors[] = '指定した日時はすでに予約されています。別の日時を選択してください。';
            } else {
                $errors[] = '予約の登録に失敗しました。時間をおいて再度お試しください。';
            }
        }
    }
}

$reservations = [];
try {
    $pdo = getPdo();
    $stmt = $pdo->query('SELECT r.id, r.room, r.reserved_at, r.reserved_for, r.note, r.document_path, r.created_at, u.display_name, u.role FROM reservations r INNER JOIN app_users u ON u.id = r.user_id ORDER BY r.reserved_at ASC');
    $reservations = $stmt->fetchAll();
} catch (Throwable $exception) {
    $errors[] = '予約一覧を取得できませんでした。';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>会議室予約</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-reservations">
  <header class="page-header">
    <a class="back-link" href="index.php">&larr; トップへ戻る</a>
    <h1 class="page-title">会議室予約</h1>
    <div class="user-menu">
      <span class="user-menu__label"><?= htmlspecialchars(($user['display_name'] ?? '') . '（' . ($user['role'] === 'admin' ? '管理者' : '一般ユーザー') . '）', ENT_QUOTES, 'UTF-8') ?></span>
      <a class="user-menu__link" href="logout.php">ログアウト</a>
    </div>
  </header>

  <main class="reservation-main">
    <?php if ($successMessage): ?>
      <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <section class="reservation-form">
      <h2>新規予約</h2>
      <form method="post" action="reservations.php" class="reservation-form__form">
        <div class="form-field">
          <label for="room">会議室</label>
          <select id="room" name="room">
            <?php foreach ($validRooms as $value => $label): ?>
              <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $room === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="reserved_at">予約日時</label>
          <input type="datetime-local" id="reserved_at" name="reserved_at" value="<?= htmlspecialchars($reservedAtInput, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-field">
          <label for="reserved_for">利用者名</label>
          <input type="text" id="reserved_for" name="reserved_for" value="<?= htmlspecialchars($reservedFor, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-field">
          <label for="note">備考</label>
          <textarea id="note" name="note" rows="3" placeholder="任意で入力してください。"><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="cta-button">予約する</button>
      </form>
    </section>

    <section class="reservation-list">
      <h2>予約状況</h2>
      <?php if ($reservations): ?>
        <ul class="reservation-list__items">
          <?php foreach ($reservations as $reservation): ?>
            <li class="reservation-card">
              <div class="reservation-card__header">
                <span class="reservation-room"><?= htmlspecialchars($validRooms[$reservation['room']] ?? $reservation['room'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="reservation-datetime"><?= htmlspecialchars((new DateTime($reservation['reserved_at']))->format('Y/m/d H:i'), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <div class="reservation-card__body">
                <p>利用者：<?= htmlspecialchars($reservation['reserved_for'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>登録者：<?= htmlspecialchars($reservation['display_name'], ENT_QUOTES, 'UTF-8') ?>（<?= $reservation['role'] === 'admin' ? '管理者' : '一般ユーザー' ?>）</p>
                <?php if (!empty($reservation['note'])): ?>
                  <p>備考：<?= nl2br(htmlspecialchars($reservation['note'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
                <?php if (!empty($reservation['document_path'])): ?>
                  <p><a class="reservation-card__attachment" href="<?= htmlspecialchars($reservation['document_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">📄 添付PDFを開く</a></p>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="reservation-empty">まだ予約はありません。</p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
