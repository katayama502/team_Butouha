<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

try {
    requireLogin();
} catch (Throwable $exception) {
    http_response_code(401);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '認証が必要です。']);
    exit;
}

$user = getAuthenticatedUser();
$displayName = $user['display_name'] ?? '';

$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
} else {
    $input = $_POST;
}

$validRooms = [
    'large' => '大会議室',
    'small' => '小会議室',
    'other' => 'その他',
];
$room = isset($input['room']) ? (string) $input['room'] : '';
$reservedAtInput = isset($input['reserved_at']) ? (string) $input['reserved_at'] : '';
$reservedFor = isset($input['reserved_for']) ? trim((string) $input['reserved_for']) : '';
$note = isset($input['note']) ? trim((string) $input['note']) : '';

$errors = [];
if (!isset($validRooms[$room])) {
    $errors[] = '会議室の指定が正しくありません。';
}

$timezone = new DateTimeZone('Asia/Tokyo');
$reservedAt = null;
if ($reservedAtInput === '') {
    $errors[] = '予約日時を入力してください。';
} else {
    $reservedAt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $reservedAtInput, $timezone);
    if (!$reservedAt) {
        $alt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $reservedAtInput, $timezone);
        if ($alt) {
            $reservedAt = $alt;
        }
    }
    if (!$reservedAt) {
        $errors[] = '予約日時の形式が正しくありません。';
    }
}

if ($reservedAt instanceof DateTimeImmutable) {
    $now = new DateTimeImmutable('now', $timezone);
    if ($reservedAt < $now->modify('-1 hour')) {
        $errors[] = '過去の日時は予約できません。';
    }
}

if ($reservedFor === '') {
    if ($displayName !== '') {
        $reservedFor = $displayName;
    } else {
        $errors[] = '予約者を入力してください。';
    }
}

if ($errors) {
    http_response_code(422);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => implode('\n', $errors)]);
    exit;
}

try {
    $pdo = getPdo();
    $stmt = $pdo->prepare('INSERT INTO reservations (room, reserved_at, user_id, reserved_for, note, document_path) VALUES (:room, :reserved_at, :user_id, :reserved_for, :note, :document_path)');
    $stmt->execute([
        ':room' => $room,
        ':reserved_at' => $reservedAt->format('Y-m-d H:i:s'),
        ':user_id' => $user['id'],
        ':reserved_for' => $reservedFor,
        ':note' => $note !== '' ? $note : null,
        ':document_path' => null,
    ]);

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'reservation' => [
            'room' => $room,
            'reserved_at' => $reservedAt->format('Y-m-d\TH:i'),
            'reserved_for' => $reservedFor,
            'note' => $note,
            'document_path' => null,
        ],
        'message' => sprintf('%sを%sに予約しました。', $validRooms[$room], $reservedAt->format('Y/m/d H:i')),
    ]);
} catch (PDOException $exception) {
    $code = (int) $exception->getCode();
    $message = '予約の登録に失敗しました。時間をおいて再度お試しください。';
    if ($code === 23000) {
        $message = '指定した時間帯はすでに予約されています。別の時間を選択してください。';
    }
    http_response_code(409);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => $message]);
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '予期しないエラーが発生しました。']);
}
