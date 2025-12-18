<?php
declare(strict_types=1);

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function asset_ver(string $path, string $fallback = '1'): string {
  $full = __DIR__ . '/../' . ltrim($path, '/');
  return is_file($full) ? (string)filemtime($full) : $fallback;
}

function render_header(string $title): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
  }

  $loggedIn = isset($_SESSION['user_id']);
  $userId   = (string)($_SESSION['user_id'] ?? '');
  $username = (string)($_SESSION['username'] ?? '');
  $pp       = (string)($_SESSION['profile_pic'] ?? 'default.png');

  $unreadNotif = 0;
  if ($loggedIn) {
    $notifPath = __DIR__ . '/notification_function.php';
    if (is_file($notifPath)) {
      require_once $notifPath;
      try {
        $nf = new NotificationFunctions();
        $unreadNotif = (int)$nf->getUnreadCount($userId);
      } catch (Throwable $t) {
        $unreadNotif = 0;
      }
    }
  }

  $cssV = asset_ver('assets/app.css', '3');
  $jsV  = asset_ver('assets/app.js', '1');
  ?>
  <!doctype html>
  <html lang="id">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title><?= e($title) ?> — Instapost</title>
    <meta name="description" content="Instapost — Waktunya Narsis! Bagikan momen, like, dan komentar.">
    <meta name="theme-color" content="#0b1020">
    <meta name="color-scheme" content="dark">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="apple-touch-icon" href="assets/img/logo.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="assets/app.css?v=<?= e($cssV) ?>">

    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
      .brand-txt b{font-size:18px; letter-spacing:.2px}
      .brand-txt span{font-size:12px; color:var(--muted2); display:block; margin-top:2px}

      .logo-img{
        width:38px;height:38px;border-radius:14px;display:block;object-fit:cover;
        border:1px solid rgba(255,255,255,0.14);
        box-shadow:0 12px 28px rgba(0,0,0,0.22);
      }

      .chip .ic{display:inline-flex; width:18px; height:18px; align-items:center; justify-content:center; opacity:.95}
      .chip .ic svg{width:18px;height:18px}

      .notif-badge{
        position:absolute; top:-6px; right:-6px;
        min-width:18px; height:18px; padding:0 6px;
        border-radius:999px;
        display:inline-flex; align-items:center; justify-content:center;
        font-size:12px; font-weight:800;
        background:rgba(239,68,68,.95); color:#fff;
        border:1px solid rgba(255,255,255,.18);
      }
    </style>
  </head>

  <body>
    <div class="nav">
      <div class="nav-inner">
        <a class="brand" href="<?= $loggedIn ? 'dashboard.php' : 'login.php' ?>" aria-label="Instapost Home">
          <!-- Logo PNG utama + fallback -->
          <img class="logo-img"
               src="assets/img/instapost-logo.png"
               width="38" height="38"
               alt="Instapost"
               onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

          <div class="logo" style="display:none" aria-hidden="true">I</div>

          <div class="brand-txt">
            <b>Instapost</b>
            <span>Waktunya Narsis!</span>
          </div>
        </a>

        <div class="nav-actions">
          <?php if ($loggedIn): ?>
            <a class="chip" href="dashboard.php" title="Home">
              <span class="ic" data-lucide="home"></span>Home
            </a>

            <a class="chip" href="notification.php" title="Notifikasi" style="position:relative">
              <span class="ic" data-lucide="bell"></span>Notification
              <?php if ($unreadNotif > 0): ?>
                <span class="notif-badge"><?= (int)$unreadNotif ?></span>
              <?php endif; ?>
            </a>

            <a class="chip" href="profile.php" title="Profil" style="display:inline-flex;align-items:center;gap:8px">
              <span class="avatar" style="width:26px;height:26px;border-radius:999px">
                <img src="uploads/<?= e($pp) ?>" onerror="this.src='uploads/default.png'" alt="pp">
              </span>
              <span>@<?= e($username) ?></span>
            </a>

            <a class="chip" href="logout.php" title="Logout">
              <span class="ic" data-lucide="log-out"></span>Logout
            </a>
          <?php else: ?>
            <a class="chip" href="login.php">
              <span class="ic" data-lucide="log-in"></span>Login
            </a>
            <a class="chip primary" href="register.php">
              <span class="ic" data-lucide="user-plus"></span>Register
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="container">
      <div id="toast" class="toast" aria-live="polite" aria-atomic="true">
        <div class="t"></div>
        <div class="d"></div>
      </div>

      <script>
        document.addEventListener('DOMContentLoaded', () => {
          if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
          }
        });
      </script>
  <?php
}

function render_footer(): void {
  $jsV = asset_ver('assets/app.js', '1');
  ?>
    </div>

    <script src="assets/app.js?v=<?= e($jsV) ?>"></script>
  </body>
  </html>
  <?php
}
