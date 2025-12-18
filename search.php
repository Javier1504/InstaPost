<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/user_function.php';

$uf = new UserFunctions();
$meId = (string)$_SESSION['user_id'];

$q = trim((string)($_GET['q'] ?? ''));
$results = $q !== '' ? $uf->searchUsers($q, 30) : [];

render_header('Search');
?>
<div class="glass card">
  <h2 style="margin:0 0 8px">Cari Akun</h2>
  <div class="muted2" style="margin-bottom:12px">Ketik username untuk menemukan user lain.</div>

  <form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input class="input" name="q" placeholder="Cari username..." value="<?= e($q) ?>" style="flex:1">
    <button class="btn primary" type="submit">Search</button>
  </form>

  <div class="sep"></div>

  <?php if ($q === ''): ?>
    <div class="muted">Mulai cari dengan mengetik username.</div>
  <?php elseif (empty($results)): ?>
    <div class="muted">Tidak ada user ditemukan untuk “<?= e($q) ?>”.</div>
  <?php else: ?>
    <div style="display:grid;gap:10px">
      <?php foreach ($results as $u): ?>
        <?php
          $uid = (string)$u['_id'];
          if ($uid === $meId) continue;
          $isFollowing = $uf->isFollowing($meId, $uid);
        ?>
        <div class="comment" style="display:flex;justify-content:space-between;align-items:center;gap:10px">
          <div style="display:flex;align-items:center;gap:10px;min-width:0">
            <div class="avatar">
              <img src="uploads/<?= e((string)($u['profile_pic'] ?? 'default.png')) ?>" onerror="this.src='uploads/default.png'">
            </div>
            <div style="min-width:0">
              <b style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <a class="link" href="profile.php?u=<?= urlencode((string)$u['username']) ?>">@<?= e((string)$u['username']) ?></a>
              </b>
              <div class="muted2" style="font-size:13px"><?= e((string)($u['full_name'] ?? '')) ?></div>
            </div>
          </div>

          <button class="btn <?= $isFollowing ? '' : 'primary' ?>"
                  data-follow-btn
                  data-following="<?= $isFollowing ? '1' : '0' ?>"
                  data-target="<?= e($uid) ?>">
            <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('[data-follow-btn]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const targetId = btn.dataset.target;
    const isFollowing = btn.dataset.following === '1';
    const action = isFollowing ? 'unfollow' : 'follow';

    btn.disabled = true;
    try{
      const fd = new FormData();
      fd.append('action', action);
      fd.append('target_id', targetId);

      const data = await UI.postJSON('ajax/follow.php', fd);
      if(!data.success) throw new Error(data.error || 'Gagal');

      btn.dataset.following = isFollowing ? '0' : '1';
      btn.textContent = isFollowing ? 'Follow' : 'Unfollow';
      btn.classList.toggle('primary', isFollowing);
      UI.toast('Sukses', isFollowing ? 'Unfollow berhasil.' : 'Follow berhasil.');
    }catch(e){
      UI.toast('Error', e.message || 'Terjadi error');
    }finally{
      btn.disabled = false;
    }
  });
});
</script>
<?php render_footer(); ?>
