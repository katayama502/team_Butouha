<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function getAuthenticatedUser(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function isAuthenticated(): bool
{
    return getAuthenticatedUser() !== null;
}

function requireLogin(): void
{
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    $user = getAuthenticatedUser();
    if (($user['role'] ?? null) !== 'admin') {
        renderForbiddenPage('管理者のみがこのページへアクセスできます。');
    }
}

function renderForbiddenPage(string $message): void
{
    http_response_code(403);
    $pageTitle = 'アクセス権限がありません';
    $forbiddenMessage = $message;
    include __DIR__ . '/forbidden.php';
    exit;
}

function redirectToHome(): void
{
    $user = getAuthenticatedUser();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    $destination = ($user['role'] ?? '') === 'admin' ? 'index.php' : 'me.php';
    header('Location: ' . $destination);
    exit;
}
