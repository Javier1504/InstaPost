<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/story_function.php';

$sf = new StoryFunctions();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $caption = trim((string)($_POST['caption'] ?? ''));

  if (empty($_FILES['media']['name'])) {
    $err = 'Pilih gambar/video dulu.';
  } else {
    $dir = __DIR__ . '/uploads/stories/';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);

    $tmp = $_FILES['media']['tmp_name'];
    $size = (int)($_FILES['media']['size'] ?? 0);
    $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','webp','mp4','webm'];

    if ($size > 15 * 1024 * 1024) $err = 'Ukuran maksimal 15MB.';
    elseif (!in_array($ext, $allowed, true)) $err = 'Format harus jpg/png/webp/mp4/webm.';
    else {
      $filename = 'story_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
      if (move_uploaded_file($tmp, $dir . $filename)) {
        $url = 'uploads/stories/' . $filename;
        $res = $sf->createStory((string)$_SESSION['user_id'], $url, $caption);
        if (!($res['success'] ?? false)) $err = (string)($res['error'] ?? 'Gagal buat story');
        else { header('Location: dashboard.php'); exit(); }
      } else $err = 'Upload gagal.';
    }
  }
}

render_header('Story');
?>
<div class="auth-wrap">
  <div class="glass card auth-card">
    <div class="auth-hero">
      <div class="logo">S</div>
      <div>
        <b style="font-size:20px">Buat Story</b>
        <div class="muted2" style="font-size:13px">Berlaku 24 jam, hanya mutual follow yang bisa lihat</div>
      </div>
    </div>

    <?php if ($err): ?>
      <div class="comment" style="border-color:rgba(239,68,68,.25)"><?= e($err) ?></div>
      <div style="height:10px"></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" style="display:grid;gap:10px">
      <input class="input" type="file" name="media" accept=".jpg,.jpeg,.png,.webp,.mp4,.webm" required>
      <textarea class="textarea" name="caption" placeholder="Caption story (opsional)"></textarea>
      <button class="btn primary" type="submit">Upload Story</button>
      <a class="btn" href="dashboard.php" style="text-decoration:none;text-align:center">Kembali</a>
    </form>
  </div>
</div>
<?php render_footer(); ?>
