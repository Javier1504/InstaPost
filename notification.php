<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

require_once __DIR__ . '/includes/layout.php';

$items = [];
$loadErr = '';

try {
  require_once __DIR__ . '/includes/notification_function.php';
  $nf = new NotificationFunctions();
  $meId = (string)$_SESSION['user_id'];
  $items = $nf->listNotifications($meId, 80);
} catch (Throwable $t) {
  $loadErr = 'Gagal memuat notifikasi. Cek file notification_function.php / koneksi MongoDB.';
}

render_header('Notifikasi');
?>
<div class="glass card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <div>
      <h2 style="margin:0">Notifikasi</h2>
      <div class="muted2" style="font-size:13px">Follow, like, dan komentar terbaru</div>
    </div>
    <button class="btn" id="markAll">Mark all as read</button>
  </div>

  <div class="sep"></div>

  <?php if ($loadErr !== ''): ?>
    <div class="msg err" style="display:block">
      <?= e($loadErr) ?>
      <div class="muted2" style="margin-top:6px;font-size:13px">
        Tip: coba buka Apache error log di XAMPP → “Logs”.
      </div>
    </div>
  <?php elseif (empty($items)): ?>
    <div class="muted">Belum ada notifikasi.</div>
  <?php else: ?>
    <div style="display:grid;gap:10px">
      <?php foreach ($items as $n): ?>
        <?php
          $type    = (string)($n['type'] ?? '');
          $fromU   = (string)($n['from_username'] ?? '');
          $fromPic = (string)($n['from_profile_pic'] ?? 'default.png');
          $isRead  = (bool)($n['is_read'] ?? false);
          $postId  = (string)($n['post_id'] ?? '');
          $postImg = (string)($n['post_image'] ?? '');
          $comment = (string)($n['comment'] ?? '');
          $timeIso = (string)($n['created_at_iso'] ?? '');

          if ($type === 'follow') $msg = 'mulai mengikuti kamu';
          elseif ($type === 'like') $msg = 'menyukai postingan kamu';
          elseif ($type === 'comment') $msg = 'mengomentari postingan kamu';
          else $msg = 'aktivitas baru';
        ?>
        <div class="comment"
             data-notif="<?= e((string)$n['_id']) ?>"
             style="display:flex;justify-content:space-between;align-items:center;gap:10px;border-color:<?= $isRead ? 'rgba(255,255,255,.10)' : 'rgba(6,182,212,.30)' ?>;background:<?= $isRead ? 'rgba(255,255,255,.05)' : 'rgba(6,182,212,.08)' ?>">

          <div style="display:flex;align-items:center;gap:10px;min-width:0">
            <div class="avatar">
              <img src="uploads/<?= e($fromPic) ?>" onerror="this.src='uploads/default.png'" alt="pp">
            </div>

            <div style="min-width:0">
              <div style="line-height:1.35">
                <b><a class="link" href="profile.php?u=<?= urlencode($fromU) ?>">@<?= e($fromU) ?></a></b>
                <span class="muted2"><?= e($msg) ?></span>
              </div>

              <?php if ($type === 'comment' && $comment !== ''): ?>
                <div class="muted2" style="font-size:13px;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px">
                  “<?= e($comment) ?>”
                </div>
              <?php endif; ?>

              <?php if ($timeIso): ?>
                <div class="muted2" style="font-size:12px;margin-top:4px"><?= e($timeIso) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div style="display:flex;align-items:center;gap:10px">
            <?php if ($postId !== '' && $postImg !== ''): ?>
              <a href="dashboard.php#post_<?= e($postId) ?>" class="tile" style="width:54px;height:54px;padding-top:0;display:block;border-radius:14px">
                <img src="<?= e($postImg) ?>" alt="post">
              </a>
            <?php endif; ?>

            <a class="btn" href="<?= $type === 'follow' ? ('profile.php?u=' . urlencode($fromU)) : ('dashboard.php' . ($postId ? '#post_' . $postId : '')) ?>"
               data-open>
              Buka
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
async function markRead(notifId){
  const fd = new FormData();
  fd.append('notif_id', notifId);
  try{ await UI.postJSON('ajax/notif_read.php', fd); }catch(_){}
}

document.querySelectorAll('[data-open]').forEach(a=>{
  a.addEventListener('click', async ()=>{
    const card = a.closest('[data-notif]');
    if(card) await markRead(card.dataset.notif);
  });
});

document.querySelectorAll('[data-notif]').forEach(card=>{
  card.addEventListener('click', async (e)=>{
    if(e.target.closest('a,button,input,textarea')) return;
    await markRead(card.dataset.notif);
  });
});

document.getElementById('markAll')?.addEventListener('click', async ()=>{
  try{
    const data = await UI.postJSON('ajax/notif_all_read.php', new FormData());
    UI.toast('OK', `Ditandai terbaca: ${data.modified || 0}`);
    setTimeout(()=>location.reload(), 250);
  }catch(e){
    UI.toast('Error', e.message || 'Gagal');
  }
});
</script>

<?php render_footer(); ?>
