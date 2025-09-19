<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Delete a reservation if the current user has permission.
 *
 * @param PDO   $pdo
 * @param int   $reservationId
 * @param array $currentUser
 *
 * @return array{success:bool,error:string,error_code:?("not_found"|"forbidden"|"exception"),reservation:array|null}
 */
function deleteReservation(PDO $pdo, int $reservationId, array $currentUser): array
{
    $result = [
        'success' => false,
        'error' => '',
        'error_code' => null,
        'reservation' => null,
    ];

    $stmt = $pdo->prepare('SELECT id, room, reserved_at, user_id, document_path FROM reservations WHERE id = :id');
    $stmt->execute([':id' => $reservationId]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $result['error'] = '予約が見つかりませんでした。';
        $result['error_code'] = 'not_found';
        return $result;
    }

    $currentUserId = isset($currentUser['id']) ? (int) $currentUser['id'] : 0;
    $isAdmin = ($currentUser['role'] ?? '') === 'admin';

    if (!$isAdmin && $currentUserId !== (int) $reservation['user_id']) {
        $result['error'] = 'この予約を削除する権限がありません。';
        $result['error_code'] = 'forbidden';
        return $result;
    }

    $startedTransaction = false;

    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        $deleteStmt = $pdo->prepare('DELETE FROM reservations WHERE id = :id');
        $deleteStmt->execute([':id' => $reservationId]);

        if ($startedTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $result['error'] = '予約の削除に失敗しました。時間をおいて再度お試しください。';
        $result['error_code'] = 'exception';
        return $result;
    }

    if (!empty($reservation['document_path'])) {
        $filePath = __DIR__ . '/' . ltrim((string) $reservation['document_path'], '/');
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    $result['success'] = true;
    $result['reservation'] = $reservation;

    return $result;
}
