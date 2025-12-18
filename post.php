<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/post_function.php';

$pf = new PostFunctions();
$err = ''; $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $caption = trim((string)($_POST['caption'] ?? ''));

  if (empty($_FILES['image']['name'])) {
    $err = 'Pilih gambar dulu.';
  } else {
    $dir = __DIR__ . '/uploads/';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);

    $tmp = $_FILES['image']['tmp_name'];
    $size = (int)($_FILES['image']['size'] ?? 0);
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if ($size > 5 * 1024 * 1024) $err = 'Ukuran gambar maksimal 5MB.';
    elseif (!in_array($ext, $allowed, true)) $err = 'Format harus jpg/png/webp.';
    else {
      $filename = 'post_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
      if (move_uploaded_file($tmp, $dir . $filename)) {
        $imageUrl = 'uploads/' . $filename;
        $res = $pf->createPost((string)$_SESSION['user_id'], $caption, $imageUrl);

        if (!($res['success'] ?? false)) $err = (string)($res['error'] ?? 'Gagal upload');
        else { $ok = 'Postingan berhasil dibuat!'; header("Location: dashboard.php"); exit(); }
      } else $err = 'Gagal upload gambar.';
    }
  }
}

render_header('Create Post');
?>
<div class="auth-wrap">
  <div class="glass card auth-card">
    <div class="auth-hero">
      <div class="logo">+</div>
      <div>
        <b style="font-size:18px">Buat Postingan</b>
        <div class="muted2" style="font-size:13px">Upload foto terbaikmu</div>
      </div>
    </div>
    <?php if ($err): ?><div class="comment" style="border-color:rgba(239,68,68,.25)"><?= e($err) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" style="display:grid;gap:10px">
      <input class="input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" id="imgInput" required>
      <div class="tile" id="previewWrap" style="display:none">
        <img id="previewImg" alt="preview">
      </div>
      <textarea class="textarea" name="caption" placeholder="Tulis caption... boleh pakai #hashtag"></textarea>
      <button class="btn primary" type="submit">Upload</button>
      <a class="btn" href="dashboard.php" style="text-decoration:none;text-align:center">Kembali</a>
    </form>
  </div>
</div>

<script>
const inp = document.getElementById('imgInput');
const wrap = document.getElementById('previewWrap');
const img = document.getElementById('previewImg');

inp.addEventListener('change', () => {
  const f = inp.files && inp.files[0];
  if(!f){ wrap.style.display='none'; return; }
  const url = URL.createObjectURL(f);
  img.src = url;
  wrap.style.display = 'block';
});
</script>
<?php render_footer(); ?>
