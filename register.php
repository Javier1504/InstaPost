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
$ok = '';
$username = '';
$email = '';
$fullName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim((string)($_POST['username'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $fullName = trim((string)($_POST['full_name'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $password2 = (string)($_POST['password2'] ?? '');

  if ($password !== $password2) {
    $err = 'Konfirmasi password tidak sama.';
  } else {
    $res = $uf->register($username, $email, $password, $fullName);
    if (!($res['success'] ?? false)) {
      $err = (string)($res['error'] ?? 'Register gagal');
    } else {
      $login = $uf->login($email, $password);
      if (($login['success'] ?? false) && !empty($login['user'])) {
        $u = $login['user'];
        $_SESSION['user_id'] = (string)$u['_id'];
        $_SESSION['username'] = (string)$u['username'];
        $_SESSION['full_name'] = (string)($u['full_name'] ?? $u['username']);
        $_SESSION['profile_pic'] = (string)($u['profile_pic'] ?? 'default.png');
        header('Location: dashboard.php');
        exit();
      }

      $ok = 'Akun berhasil dibuat. Silakan login.';
    }
  }
}

render_header('Register');
?>
<div class="auth-wrap">
  <div class="glass card auth-card">
    <div class="auth-hero">
      <div class="logo">+</div>
      <div>
        <b style="font-size:20px">Buat Akun</b>
        <div class="muted2" style="font-size:13px">Join Instapost dan mulai posting</div>
      </div>
    </div>

    <?php if ($err): ?>
      <div class="comment" style="border-color:rgba(239,68,68,.25)">
        <b>Gagal</b><br><span class="muted2"><?= e($err) ?></span>
      </div>
      <div style="height:10px"></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="comment" style="border-color:rgba(34,197,94,.25)">
        <b>Berhasil</b><br><span class="muted2"><?= e($ok) ?></span>
      </div>
      <div style="height:10px"></div>
    <?php endif; ?>

    <form method="post" style="display:grid;gap:10px">
      <label class="muted2" style="font-size:13px">Username</label>
      <input class="input" name="username" value="<?= e($username) ?>" placeholder="contoh: javier" autocomplete="username" required>

      <label class="muted2" style="font-size:13px">Nama Lengkap</label>
      <input class="input" name="full_name" value="<?= e($fullName) ?>" placeholder="contoh: Javier Nararya">

      <label class="muted2" style="font-size:13px">Email</label>
      <input class="input" type="email" name="email" value="<?= e($email) ?>" placeholder="contoh: javier@email.com" autocomplete="email" required>

      <label class="muted2" style="font-size:13px">Password</label>
      <input class="input" type="password" name="password" placeholder="minimal 6 karakter" autocomplete="new-password" required>

      <label class="muted2" style="font-size:13px">Ulangi Password</label>
      <input class="input" type="password" name="password2" placeholder="konfirmasi password" autocomplete="new-password" required>

      <button class="btn primary" type="submit">Daftar</button>

      <div class="sep"></div>
      <div class="muted2" style="font-size:13px;text-align:center">
        Sudah punya akun?
        <a class="link" href="login.php">Login</a>
      </div>
    </form>
  </div>
</div>
<?php render_footer(); ?>
