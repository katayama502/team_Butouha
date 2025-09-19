<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/reservation_service.php';

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

$reservationId = isset($input['reservation_id']) ? filter_var($input['reservation_id'], FILTER_VALIDATE_INT) : null;
if (!$reservationId || $reservationId <= 0) {
    http_response_code(422);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '削除する予約を正しく指定してください。']);
    exit;
}

try {
    $pdo = getPdo();
    $result = deleteReservation($pdo, $reservationId, $user);

    if (!$result['success']) {
        $statusCode = 400;
        if ($result['error_code'] === 'not_found') {
            $statusCode = 404;
        } elseif ($result['error_code'] === 'forbidden') {
            $statusCode = 403;
        } elseif ($result['error_code'] === 'exception') {
            $statusCode = 500;
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => $result['error'] !== '' ? $result['error'] : '予約を削除できませんでした。',
        ]);
        exit;
    }

    $reservation = $result['reservation'];
    $validRooms = [
        'large' => '大会議室',
        'small' => '小会議室',
        'other' => 'その他',
    ];

    $roomLabel = $validRooms[$reservation['room']] ?? $reservation['room'];
    $reservationDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $reservation['reserved_at'], new DateTimeZone('Asia/Tokyo'));
    $reservationLabel = $reservationDate instanceof DateTimeImmutable ? $reservationDate->format('Y/m/d H:i') : '';
    $message = $reservationLabel !== ''
        ? sprintf('%sの予約（%s）を削除しました。', $roomLabel, $reservationLabel)
        : sprintf('%sの予約を削除しました。', $roomLabel);

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'reservation_id' => $reservationId,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => '予約の削除に失敗しました。時間をおいて再度お試しください。']);
}
