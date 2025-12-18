<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/user_function.php';

if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit();
}

$uf = new UserFunctions();
$err = '';
$identity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identity = trim((string)($_POST['identity'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  $res = $uf->login($identity, $password);
  if (!($res['success'] ?? false)) {
    $err = (string)($res['error'] ?? 'Login gagal');
  } else {
    $u = $res['user'];

    $_SESSION['user_id'] = (string)$u['_id'];
    $_SESSION['username'] = (string)$u['username'];
    $_SESSION['full_name'] = (string)($u['full_name'] ?? $u['username']);
    $_SESSION['profile_pic'] = (string)($u['profile_pic'] ?? 'default.png');

    header('Location: dashboard.php');
    exit();
  }
}

render_header('Login');
?>
<div class="auth-wrap">
  <div class="glass card auth-card">
    <div class="auth-hero">
      <div class="logo">I</div>
      <div>
        <b style="font-size:20px">Instapost</b>
        <div class="muted2" style="font-size:13px">Login untuk lanjut ke feed kamu</div>
      </div>
    </div>

    <?php if ($err): ?>
      <div class="comment" style="border-color:rgba(239,68,68,.25)">
        <b>Gagal</b><br><span class="muted2"><?= e($err) ?></span>
      </div>
      <div style="height:10px"></div>
    <?php endif; ?>

    <form method="post" style="display:grid;gap:10px">
      <label class="muted2" style="font-size:13px">Email / Username</label>
      <input class="input" name="identity" value="<?= e($identity) ?>" placeholder="contoh: admin / admin@email.com" autocomplete="username" required>

      <label class="muted2" style="font-size:13px">Password</label>
      <input class="input" type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>

      <button class="btn primary" type="submit">Masuk</button>

      <div class="sep"></div>
      <div class="muted2" style="font-size:13px;text-align:center">
        Belum punya akun?
        <a class="link" href="register.php">Daftar sekarang</a>
      </div>
    </form>
  </div>
</div>
<?php render_footer(); ?>
