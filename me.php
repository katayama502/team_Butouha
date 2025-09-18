<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$user = getAuthenticatedUser();
$isAdmin = ($user['role'] ?? '') === 'admin';
if ($isAdmin) {
    header('Location: index.php');
    exit;
}

$displayName = $user['display_name'] ?? '';
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
        <a class="user-menu__link" href="reservations.php">会議室を予約</a>
        <a class="user-menu__link" href="logout.php">ログアウト</a>
      </div>
      <h1 class="user-home-title">高橋建設</h1>
      <p class="user-home-subtitle">日々の業務やお知らせに素早くアクセスできる社内ポータルです。</p>
    </div>
  </header>

  <main class="user-home-main">
    <section class="user-home-actions" aria-labelledby="quickLinks">
      <h2 id="quickLinks" class="user-home-section-title">クイックアクセス</h2>
      <div class="user-home-buttons">
        <a class="user-home-button user-home-button--reserve" href="reservations.php">会議室を予約</a>
        <a class="user-home-button user-home-button--important" href="form.php">重要なお知らせを見る</a>
        <a class="user-home-button user-home-button--other" href="sonota.php">その他のお知らせを見る</a>
      </div>
    </section>

    <section class="user-home-schedule" aria-labelledby="weeklySchedule">
      <h2 id="weeklySchedule" class="user-home-section-title">🗓 今週の予定</h2>
      <div id="scheduleList" class="user-home-schedule__list" aria-live="polite"></div>
    </section>
  </main>

  <script>
    const schedules = {
      "月": ["10:00 会議", "15:00 現場確認"],
      "火": ["9:00 資材発注", "13:00 顧客打ち合わせ"],
      "水": ["終日 現場作業"],
      "木": ["11:00 進捗報告", "16:00 設計レビュー"],
      "金": ["9:30 チームミーティング", "14:00 書類提出"],
      "土": ["休み"],
      "日": ["休み"]
    };

    const days = ["月", "火", "水", "木", "金", "土", "日"];
    const scheduleList = document.getElementById("scheduleList");

    days.forEach(day => {
      const items = schedules[day] || ["予定なし"];
      const dayTitle = document.createElement("div");
      dayTitle.className = "user-home-schedule__item user-home-schedule__item--day";
      dayTitle.textContent = `【${day}】`;
      scheduleList.appendChild(dayTitle);

      items.forEach(item => {
        const div = document.createElement("div");
        div.className = "user-home-schedule__item";
        div.textContent = `・${item}`;
        scheduleList.appendChild(div);
      });
    });
  </script>
</body>
</html>
