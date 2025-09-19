<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

if (isAuthenticated()) {
    redirectToHome();
}

$username = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if ($username === '') {
        $errors[] = 'ユーザー名を入力してください。';
    }

    if ($password === '') {
        $errors[] = 'パスワードを入力してください。';
    }

    if (!$errors) {
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('SELECT id, username, password_hash, role, display_name FROM app_users WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['auth_user'] = [
                    'id' => (int) $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'display_name' => $user['display_name'],
                ];
                redirectToHome();
            }

            $errors[] = 'ユーザー名またはパスワードが正しくありません。';
        } catch (Throwable $exception) {
            $errors[] = 'ログイン処理中にエラーが発生しました。後でもう一度お試しください。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ログイン</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-login">
  <main class="login-main">
    <h1>T-link</h1>
    <p class="login-description">管理者は重要・その他のお知らせを投稿でき、一般ユーザーは閲覧と会議室予約が可能です。</p>
    <?php if ($errors): ?>
      <div class="alert alert-error">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form class="login-form" method="post" action="login.php" autocomplete="off">
      <div class="form-field">
        <label for="username">ユーザー名</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="form-field">
        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="cta-button">ログイン</button>
    </form>
    <section class="login-hint">
      <h2>サンプルアカウント</h2>
      <ul>
        <li><strong>管理者：</strong>ユーザー名 <code>admin</code> / パスワード <code>adminpass</code></li>
        <li><strong>一般ユーザー：</strong>ユーザー名 <code>user</code> / パスワード <code>userpass</code></li>
      </ul>
    </section>
  </main>
</body>
</html>
