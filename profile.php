<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/user_function.php';
require_once __DIR__ . '/includes/post_function.php';

if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

$uf = new UserFunctions();
$pf = new PostFunctions();

$meId = (string)$_SESSION['user_id'];
$u = trim((string)($_GET['u'] ?? ''));

$user = ($u !== '') ? $uf->getByUsername($u) : $uf->getById($meId);
if (!$user) { http_response_code(404); echo "User tidak ditemukan."; exit(); }

$targetId = (string)$user['_id'];
$isMe = ($targetId === $meId);

$followers = is_array($user['followers'] ?? null) ? $user['followers'] : [];
$following = is_array($user['following'] ?? null) ? $user['following'] : [];
$postsCount = (int)($user['posts_count'] ?? 0);

$isFollowing = !$isMe ? $uf->isFollowing($meId, $targetId) : false;
$isMutual    = !$isMe ? $uf->isMutual($meId, $targetId) : false;

$err = ''; $ok = '';
if ($isMe && $_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'save_profile') {
  $fullName = trim((string)($_POST['full_name'] ?? ''));
  $bio      = trim((string)($_POST['bio'] ?? ''));

  $uf->updateProfile($meId, $fullName !== '' ? $fullName : (string)$user['username'], $bio, null);
  $ok = 'Profil berhasil diperbarui.';

  $user = $uf->getById($meId);
  $followers = is_array($user['followers'] ?? null) ? $user['followers'] : [];
  $following = is_array($user['following'] ?? null) ? $user['following'] : [];
  $postsCount = (int)($user['posts_count'] ?? 0);
}

if ($isMe && $_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'upload_pp') {
  if (empty($_FILES['profile_pic']['name'])) {
    $err = 'Pilih foto terlebih dahulu.';
  } else {
    $dir = __DIR__ . '/uploads/';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);

    $tmp  = $_FILES['profile_pic']['tmp_name'];
    $size = (int)($_FILES['profile_pic']['size'] ?? 0);

    $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if ($size > 2 * 1024 * 1024) $err = 'Ukuran foto maksimal 2MB.';
    elseif (!in_array($ext, $allowed, true)) $err = 'Format foto harus jpg/png/webp.';
    else {
      $filename = 'pp_' . $meId . '_' . time() . '.' . $ext;
      if (move_uploaded_file($tmp, $dir . $filename)) {
        $_SESSION['profile_pic'] = $filename;

        $uf->updateProfile(
          $meId,
          (string)($user['full_name'] ?? $user['username']),
          (string)($user['bio'] ?? ''),
          $filename
        );
        $ok = 'Foto profil berhasil diperbarui.';
        $user = $uf->getById($meId);
      } else $err = 'Gagal upload foto.';
    }
  }
}

$userPosts = $pf->getUserPosts($targetId, 90);

render_header('Profile');

$pp = (string)($user['profile_pic'] ?? 'default.png');
$full = (string)($user['full_name'] ?? $user['username']);
$bio  = (string)($user['bio'] ?? '');
$username = (string)$user['username'];
?>
<style>
.stats-row{margin-top:14px; display:grid; grid-template-columns: repeat(3,1fr); gap:10px;}
@media (max-width:700px){.stats-row{grid-template-columns:1fr}}
.statcard{padding:12px 14px;border-radius:16px;border:1px solid rgba(255,255,255,.10);background: rgba(255,255,255,.05)}
.statcard b{font-size:18px}
.statcard .t{color:rgba(232,236,255,.72);font-size:12px;margin-top:2px}

.statlink{cursor:pointer; transition: transform .15s ease, background .2s ease, border .2s ease, box-shadow .2s ease;}
.statlink:hover{transform:translateY(-1px); background:rgba(255,255,255,.07); border-color:rgba(255,255,255,.14); box-shadow:0 12px 24px rgba(0,0,0,.22);}
.statlink:active{transform:none}

.p-avatar{cursor:<?= $isMe ? 'pointer' : 'default' ?>;}
.pp-hint{font-size:12px;color:rgba(232,236,255,.68);margin-top:6px}

.modal-card{max-width:760px;width:100%;}
.modal-top{padding:14px;display:flex;justify-content:space-between;align-items:center;gap:10px}
.modal-title b{font-size:16px}
.modal-title .muted2{font-size:12px}

.follow-tabs{display:flex;gap:8px}
.pill{border:1px solid rgba(255,255,255,.10); background:rgba(255,255,255,.05); padding:8px 10px; border-radius:999px; cursor:pointer; user-select:none; font-weight:700; font-size:13px;}
.pill.active{background:linear-gradient(135deg, rgba(124,58,237,.92), rgba(6,182,212,.82)); border-color:rgba(255,255,255,.14);}

.follow-list{display:grid;gap:10px; max-height:55vh; overflow:auto; padding-right:6px;}
.rowitem{display:flex;justify-content:space-between;align-items:center;gap:10px; padding:12px; border-radius:16px; border:1px solid rgba(255,255,255,.10); background:rgba(255,255,255,.05);}
.rowitem a{display:flex;align-items:center;gap:10px;text-decoration:none}
.rowitem small{display:block;margin-top:2px}

.pp-drop{border:1px dashed rgba(255,255,255,.18); border-radius:16px; padding:14px; background:rgba(255,255,255,.04); display:grid; gap:10px;}
.pp-preview{display:flex;gap:12px;align-items:center;}
.pp-preview .avatar{width:56px;height:56px;border-radius:18px}

.lb-side{display:flex; flex-direction:column;}
.lb-comments{max-height:40vh; overflow:auto; padding-right:6px;}
</style>

<div class="glass card profile-shell">

  <div class="profile-hero">
    <div class="profile-hero-inner">
      <div class="profile-id">
        <div class="p-avatar" id="ppTrigger" <?= $isMe ? 'title="Klik untuk ganti foto"' : '' ?>>
          <div class="in">
            <img id="ppImg" src="uploads/<?= e($pp) ?>" onerror="this.src='uploads/default.png'" alt="avatar">
          </div>
        </div>
        <div class="p-meta">
          <b>@<?= e($username) ?></b>
          <div class="sub"><?= e($full) ?></div>
          <?php if($isMe): ?><div class="pp-hint">Klik foto profil untuk mengganti</div><?php endif; ?>
        </div>
      </div>

      <div class="profile-actions">
        <?php if (!$isMe): ?>
          <button class="btn <?= $isFollowing ? '' : 'primary' ?>" id="followBtn"
                  data-target-id="<?= e($targetId) ?>"
                  data-following="<?= $isFollowing ? '1' : '0' ?>">
            <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
          </button>

          <a class="btn <?= $isMutual ? 'primary' : '' ?>"
             href="<?= $isMutual ? ('chat.php?u=' . urlencode($username)) : '#' ?>"
             onclick="<?= $isMutual ? '' : "UI.toast('Info','Harus mutual follow untuk DM'); return false;" ?>">
            DM
          </a>

          <a class="btn" href="search.php" style="text-decoration:none">Cari</a>
        <?php else: ?>
          <a class="btn primary" href="post.php" style="text-decoration:none">Post</a>
          <a class="btn" href="search.php" style="text-decoration:none">Cari</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="stats-row">
      <div class="statcard">
        <b><?= (int)$postsCount ?></b>
        <div class="t">Posts</div>
      </div>

      <div class="statcard statlink" role="button" tabindex="0"
           data-follow-open="followers"
           data-target-id="<?= e($targetId) ?>"
           data-username="<?= e($username) ?>">
        <b><?= count((array)$followers) ?></b>
        <div class="t">Followers</div>
      </div>

      <div class="statcard statlink" role="button" tabindex="0"
           data-follow-open="following"
           data-target-id="<?= e($targetId) ?>"
           data-username="<?= e($username) ?>">
        <b><?= count((array)$following) ?></b>
        <div class="t">Following</div>
      </div>
    </div>

    <div class="profile-bio">
      <div class="name"><?= e($full) ?></div>
      <div class="desc"><?= e($bio) ?></div>
    </div>
  </div>

  <?php if ($err): ?><div class="sep"></div><div class="comment" style="border-color:rgba(239,68,68,.25)"><?= e($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="sep"></div><div class="comment" style="border-color:rgba(34,197,94,.25)"><?= e($ok) ?></div><?php endif; ?>

  <?php if ($isMe): ?>
    <div class="collapse" id="editBox">
      <div class="head" onclick="document.getElementById('editBox').classList.toggle('open')">
        <b>Edit Profil</b>
        <span class="muted2">klik untuk edit profilmu</span>
      </div>
      <div class="body">
        <form method="post" style="display:grid;gap:10px">
          <input type="hidden" name="action" value="save_profile">
          <input class="input" name="full_name" placeholder="Nama lengkap" value="<?= e((string)($user['full_name'] ?? '')) ?>">
          <textarea class="textarea" name="bio" placeholder="Bio..."><?= e((string)($user['bio'] ?? '')) ?></textarea>
          <button class="btn primary" type="submit">Simpan</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <div id="tab_posts">
    <?php if (empty($userPosts)): ?>
      <div class="sep"></div>
      <div class="muted">Belum ada postingan.</div>
    <?php else: ?>
      <div class="p-grid">
        <?php foreach ($userPosts as $p): ?>
          <?php
            $pid = (string)($p['_id'] ?? '');
            $img = (string)($p['image_url'] ?? '');
            $lc  = (int)($p['likes_count'] ?? 0);
            $cc  = (int)($p['comments_count'] ?? 0);
            $ownerPp = (string)($p['profile_pic'] ?? $pp);
            $ownerU  = (string)($p['username'] ?? $username);
          ?>
          <div class="p-tile"
               data-pid="<?= e($pid) ?>"
               data-img="<?= e($img) ?>"
               data-owner-id="<?= e($targetId) ?>"
               data-username="<?= e($ownerU) ?>"
               data-profile-pic="<?= e($ownerPp) ?>"
               data-lc="<?= (int)$lc ?>"
               data-cc="<?= (int)$cc ?>">
            <img src="<?= e($img) ?>" alt="post">
            <div class="ov">
              <span>♥ <?= (int)$lc ?> · 💬 <?= (int)$cc ?></span>
              <b>View</b>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="lb" id="followLb">
  <div class="lb-card modal-card">
    <div class="modal-top">
      <div class="modal-title">
        <b id="followTitle">Followers</b>
        <div class="muted2" id="followSub">Memuat...</div>
      </div>

      <div style="display:flex;align-items:center;gap:10px">
        <div class="follow-tabs">
          <div class="pill active" id="pillFollowers" onclick="FollowUI.switchTo('followers')">Followers</div>
          <div class="pill" id="pillFollowing" onclick="FollowUI.switchTo('following')">Following</div>
        </div>
        <button class="btn" type="button" onclick="FollowUI.close()">✕</button>
      </div>
    </div>

    <div class="sep" style="margin:0"></div>

    <div style="padding:14px">
      <input class="input" id="followSearch" placeholder="Cari username..." autocomplete="off">
      <div class="sep"></div>

      <div id="followList" class="follow-list"></div>
      <div id="followEmpty" class="muted2" style="display:none;margin-top:8px">Tidak ada data.</div>

      <div style="display:flex;gap:10px;margin-top:12px">
        <button class="btn" id="followMoreBtn" type="button" style="display:none" onclick="FollowUI.more()">Load more</button>
      </div>
    </div>
  </div>
</div>

<?php if($isMe): ?>
<div class="lb" id="ppLb">
  <div class="lb-card" style="max-width:560px;width:100%">
    <div class="modal-top">
      <div class="modal-title">
        <b>Update Foto Profil</b>
        <div class="muted2">JPG/PNG/WEBP • max 2MB</div>
      </div>
      <button class="btn" type="button" onclick="PPModal.close()">✕</button>
    </div>

    <div class="sep" style="margin:0"></div>

    <div style="padding:14px">
      <form method="post" enctype="multipart/form-data" id="ppForm" style="display:grid;gap:10px">
        <input type="hidden" name="action" value="upload_pp">

        <div class="pp-drop" id="ppDrop">
          <div class="pp-preview">
            <span class="avatar">
              <img id="ppPreviewImg" src="uploads/<?= e($pp) ?>" onerror="this.src='uploads/default.png'">
            </span>
            <div>
              <b>Preview</b>
              <div class="muted2" style="font-size:12px">Klik “Pilih File” atau drag & drop di area ini.</div>
            </div>
          </div>

          <input class="input" type="file" name="profile_pic" id="ppFile" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end">
          <button class="btn" type="button" onclick="PPModal.close()">Batal</button>
          <button class="btn primary" type="submit">Simpan Foto</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="lb" id="postLb">
  <div class="lb-card">
    <div class="lb-grid">
      <div class="lb-media"><img id="pvImg" alt="post"></div>

      <div class="lb-side">
        <div class="lb-top">
          <div class="user">
            <div class="avatar"><img id="pvPp" src="uploads/default.png" onerror="this.src='uploads/default.png'"></div>
            <div class="meta">
              <b>@<a class="link" id="pvUserLink" href="#"><span id="pvUser"></span></a></b>
              <small class="muted2" id="pvTime"></small>
            </div>
          </div>
          <div class="lb-close btn" onclick="PostViewer.close()">✕</div>
        </div>

        <div class="sep"></div>

        <div class="lb-cap">
          <b>@<span id="pvUser2"></span></b> <span id="pvCap"></span>
        </div>

        <div class="sep"></div>

        <div class="actions" style="margin:0">
          <button class="iconbtn" id="pvLikeBtn" type="button">
            <span class="ic" id="pvLikeIc">♡</span>
            <span class="count" id="pvLikeCount">0</span>
          </button>

          <div class="iconbtn" title="Jumlah komentar">
            <span class="ic">💬</span>
            <span class="count" id="pvCommentCount">0</span>
          </div>
        </div>

        <div class="sep"></div>

        <div id="pvComments" class="comments lb-comments"></div>
        <div id="pvCommentsEmpty" class="muted2" style="display:none">Belum ada komentar.</div>

        <div class="sep"></div>

        <form id="pvCommentForm" class="comment-form" autocomplete="off">
          <input class="input" id="pvCommentInput" name="comment" placeholder="Tulis komentar..." maxlength="500">
          <button class="btn" id="pvCommentSend" type="submit">Kirim</button>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
function escHtml(s){
  return String(s)
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

(() => {
  const btn = document.getElementById('followBtn');
  if(!btn) return;
  btn.addEventListener('click', async () => {
    const targetId = btn.dataset.targetId;
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
      UI.toast('Sukses', isFollowing ? 'berhasil unfollow' : 'berhasil follow');
      setTimeout(()=>location.reload(), 350);
    }catch(e){
      UI.toast('Error', e.message || 'Terjadi error');
    }finally{
      btn.disabled = false;
    }
  });
})();

const FollowUI = (() => {
  const lb = document.getElementById('followLb');
  const title = document.getElementById('followTitle');
  const sub = document.getElementById('followSub');
  const list = document.getElementById('followList');
  const empty = document.getElementById('followEmpty');
  const moreBtn = document.getElementById('followMoreBtn');
  const search = document.getElementById('followSearch');

  const pillFollowers = document.getElementById('pillFollowers');
  const pillFollowing = document.getElementById('pillFollowing');

  let state = { targetId:'', username:'', type:'followers', offset:0, limit:30, total:0, cache:[] };

  function open(targetId, username, type){
    state.targetId = targetId;
    state.username = username;
    state.type = type;
    state.offset = 0;
    state.total = 0;
    state.cache = [];
    search.value = '';
    list.innerHTML = '';
    empty.style.display = 'none';
    moreBtn.style.display = 'none';
    setPills();
    setTitle();
    lb.classList.add('show');
    load();
  }

  function setTitle(){
    title.textContent = (state.type === 'followers') ? 'Followers' : 'Following';
    sub.textContent = `@${state.username} • Memuat...`;
  }

  function setPills(){
    pillFollowers.classList.toggle('active', state.type === 'followers');
    pillFollowing.classList.toggle('active', state.type === 'following');
  }

  function close(){ lb.classList.remove('show'); }
  lb.addEventListener('click', (e)=>{ if(e.target === lb) close(); });

  async function load(){
    try{
      const qs = new URLSearchParams({
        target_id: state.targetId,
        type: state.type,
        offset: String(state.offset),
        limit: String(state.limit)
      });

      const data = await fetch('ajax/follow_list.php?' + qs.toString(), { credentials:'same-origin' }).then(r=>r.json());
      if(!data.success) throw new Error(data.error || 'Gagal memuat');

      state.total = data.total || 0;
      const items = Array.isArray(data.items) ? data.items : [];
      state.cache = state.cache.concat(items);
      state.offset += items.length;

      render();
      sub.textContent = `@${state.username} • ${state.total} akun`;
      moreBtn.style.display = (state.offset < state.total) ? '' : 'none';

    }catch(e){
      UI.toast('Error', e.message || 'Terjadi error');
      sub.textContent = `@${state.username}`;
    }
  }

  function render(){
    const q = (search.value || '').trim().toLowerCase();
    const filtered = q ? state.cache.filter(x => (x.username||'').toLowerCase().includes(q)) : state.cache;

    list.innerHTML = '';
    if(filtered.length === 0){
      empty.style.display = '';
      return;
    }
    empty.style.display = 'none';

    filtered.forEach(u=>{
      const row = document.createElement('div');
      row.className = 'rowitem';

      const left = document.createElement('a');
      left.href = 'profile.php?u=' + encodeURIComponent(u.username || '');
      left.innerHTML = `
        <span class="avatar" style="width:42px;height:42px;border-radius:16px">
          <img src="uploads/${escHtml(u.profile_pic || 'default.png')}" onerror="this.src='uploads/default.png'">
        </span>
        <span>
          <b>@${escHtml(u.username || '')}</b>
          <small class="muted2">${escHtml(u.full_name || '')}</small>
        </span>
      `;

      const right = document.createElement('div');
      right.style.display = 'flex';
      right.style.gap = '8px';

      if(!u.is_me){
        const b = document.createElement('button');
        b.className = 'btn ' + (u.is_following ? '' : 'primary');
        b.textContent = u.is_following ? 'Following' : 'Follow';
        b.onclick = async (ev)=>{
          ev.preventDefault(); ev.stopPropagation();
          b.disabled = true;
          try{
            const fd = new FormData();
            fd.append('action', u.is_following ? 'unfollow' : 'follow');
            fd.append('target_id', u.id);

            const res = await UI.postJSON('ajax/follow.php', fd);
            if(!res.success) throw new Error(res.error || 'Gagal');

            u.is_following = !u.is_following;
            b.textContent = u.is_following ? 'Following' : 'Follow';
            b.classList.toggle('primary', !u.is_following);
          }catch(e){
            UI.toast('Error', e.message || 'Terjadi error');
          }finally{
            b.disabled = false;
          }
        };
        right.appendChild(b);
      }

      row.appendChild(left);
      row.appendChild(right);
      list.appendChild(row);
    });
  }

  function more(){ load(); }

  function switchTo(type){
    if(type !== 'followers' && type !== 'following') return;
    if(state.type === type) return;
    state.type = type;
    state.offset = 0;
    state.total = 0;
    state.cache = [];
    list.innerHTML = '';
    empty.style.display = 'none';
    moreBtn.style.display = 'none';
    setPills();
    setTitle();
    load();
  }

  document.querySelectorAll('.statlink[data-follow-open]').forEach(el => {
    const openIt = () => open(el.dataset.targetId, el.dataset.username, el.dataset.followOpen);
    el.addEventListener('click', openIt);
    el.addEventListener('keydown', (e)=>{ if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openIt(); } });
  });

  search.addEventListener('input', ()=>render());

  return { open, close, more, switchTo };
})();

<?php if($isMe): ?>
const PPModal = (() => {
  const lb = document.getElementById('ppLb');
  const trigger = document.getElementById('ppTrigger');
  const file = document.getElementById('ppFile');
  const preview = document.getElementById('ppPreviewImg');
  const drop = document.getElementById('ppDrop');

  function open(){ lb.classList.add('show'); }
  function close(){ lb.classList.remove('show'); }

  lb.addEventListener('click', (e)=>{ if(e.target === lb) close(); });
  if(trigger) trigger.addEventListener('click', ()=> open());

  function previewFile(f){
    if(!f) return;
    preview.src = URL.createObjectURL(f);
  }
  file.addEventListener('change', ()=> previewFile(file.files && file.files[0]));

  drop.addEventListener('dragover', (e)=>{ e.preventDefault(); drop.style.borderColor = 'rgba(255,255,255,.32)'; });
  drop.addEventListener('dragleave', ()=>{ drop.style.borderColor = 'rgba(255,255,255,.18)'; });
  drop.addEventListener('drop', (e)=>{
    e.preventDefault();
    drop.style.borderColor = 'rgba(255,255,255,.18)';
    const f = e.dataTransfer.files && e.dataTransfer.files[0];
    if(!f) return;
    file.files = e.dataTransfer.files;
    previewFile(f);
  });

  return { open, close };
})();
<?php endif; ?>
  const PostViewer = (() => {
  const lb = document.getElementById('postLb');
  const img = document.getElementById('pvImg');
  const pp = document.getElementById('pvPp');
  const user = document.getElementById('pvUser');
  const user2 = document.getElementById('pvUser2');
  const userLink = document.getElementById('pvUserLink');
  const cap = document.getElementById('pvCap');
  const time = document.getElementById('pvTime');
  const likeBtn = document.getElementById('pvLikeBtn');
  const likeIc = document.getElementById('pvLikeIc');
  const likeCount = document.getElementById('pvLikeCount');
  const commentCount = document.getElementById('pvCommentCount');
  const commentsBox = document.getElementById('pvComments');
  const commentsEmpty = document.getElementById('pvCommentsEmpty');
  const form = document.getElementById('pvCommentForm');
  const input = document.getElementById('pvCommentInput');
  const sendBtn = document.getElementById('pvCommentSend');

  let state = { postId:'', ownerId:'', isLiked:false };

  function close(){ lb.classList.remove('show'); state.postId=''; }
  lb.addEventListener('click', (e)=>{ if(e.target === lb) close(); });

  function setLikeUI(isLiked, count){
    state.isLiked = !!isLiked;
    likeBtn.classList.toggle('liked', !!isLiked);
    likeIc.textContent = isLiked ? '♥' : '♡';
    likeCount.textContent = String(count ?? 0);
  }

  function renderComments(items){
    commentsBox.innerHTML = '';
    if(!Array.isArray(items) || items.length === 0){
      commentsEmpty.style.display = '';
      return;
    }
    commentsEmpty.style.display = 'none';

    items.forEach(c=>{
      const row = document.createElement('div');
      row.className = 'comment';
      row.dataset.comment = c._id || '';
      row.style.display = 'flex';
      row.style.justifyContent = 'space-between';
      row.style.gap = '10px';
      row.style.alignItems = 'flex-start';

      const left = document.createElement('div');
      left.innerHTML = `<b>@${escHtml(c.username || '')}</b> ${escHtml(c.comment || '')}`;
      row.appendChild(left);

      if(c.can_delete && c._id){
        const del = document.createElement('button');
        del.className = 'btn danger';
        del.type = 'button';
        del.style.padding = '6px 10px';
        del.textContent = '✕';
        del.onclick = ()=>deleteComment(state.postId, c._id);
        row.appendChild(del);
      }
      commentsBox.appendChild(row);
    });
  }

  async function openFromTile(tile){
    state.postId = tile.dataset.pid || '';
    state.ownerId = tile.dataset.ownerId || '';

    img.src = tile.dataset.img || '';
    user.textContent = tile.dataset.username || '';
    user2.textContent = tile.dataset.username || '';
    userLink.href = 'profile.php?u=' + encodeURIComponent(tile.dataset.username || '');
    pp.src = 'uploads/' + (tile.dataset.profilePic || 'default.png');
    pp.onerror = ()=> pp.src='uploads/default.png';

    cap.textContent = '';
    time.textContent = '';
    setLikeUI(false, tile.dataset.lc || 0);
    commentCount.textContent = String(tile.dataset.cc || 0);
    commentsBox.innerHTML = '';
    commentsEmpty.style.display = 'none';
    input.value = '';

    lb.classList.add('show');
    await loadDetail();
  }

  async function loadDetail(){
    if(!state.postId) return;
    try{
      const qs = new URLSearchParams({ post_id: state.postId, owner_id: state.ownerId });
      const data = await fetch('ajax/post_detail.php?' + qs.toString(), { credentials:'same-origin' }).then(r=>r.json());
      if(!data.success) throw new Error(data.error || 'Gagal load post');

      const p = data.post || {};
      if(p.image_url) img.src = p.image_url;
      if(p.username){
        user.textContent = p.username;
        user2.textContent = p.username;
        userLink.href = 'profile.php?u=' + encodeURIComponent(p.username);
      }
      if(p.profile_pic){
        pp.src = 'uploads/' + p.profile_pic;
        pp.onerror = ()=> pp.src='uploads/default.png';
      }
      if(p.caption != null) cap.textContent = p.caption;
      if(p.created_at){
        try{ time.textContent = new Date(p.created_at).toLocaleString(); }catch(_){}
      }

      setLikeUI(!!p.is_liked, p.likes_count || 0);
      commentCount.textContent = String(p.comments_count || 0);
      renderComments(p.comments || []);
    }catch(e){
      UI.toast('Error', e.message || 'Terjadi error');
    }
  }

  likeBtn.addEventListener('click', async ()=>{
    if(!state.postId) return;
    likeBtn.disabled = true;
    try{
      const fd = new FormData();
      fd.append('post_id', state.postId);
      const data = await UI.postJSON('ajax/like.php', fd);
      if(!data.success) throw new Error(data.error || 'Gagal like');

      setLikeUI(!!data.liked, data.likes_count || 0);
    }catch(e){
      UI.toast('Error', e.message || 'Terjadi error');
    }finally{
      likeBtn.disabled = false;
    }
  });

  form.addEventListener('submit', async (ev)=>{
    ev.preventDefault();
    if(!state.postId) return;
    const text = (input.value || '').trim();
    if(!text) return;

    sendBtn.disabled = true;
    try{
      const fd = new FormData();
      fd.append('post_id', state.postId);
      fd.append('comment', text);

      const data = await UI.postJSON('ajax/comment.php', fd);
      if(!data.success) throw new Error(data.error || 'Gagal komentar');

      const c = data.comment || {};
      const div = document.createElement('div');
      div.className = 'comment';
      div.dataset.comment = c._id || '';
      div.style.display = 'flex';
      div.style.justifyContent = 'space-between';
      div.style.gap = '10px';
      div.style.alignItems = 'flex-start';

      const left = document.createElement('div');
      left.innerHTML = `<b>@${escHtml(c.username || '')}</b> ${escHtml(c.comment || '')}`;
      div.appendChild(left);

      const del = document.createElement('button');
      del.className = 'btn danger';
      del.type = 'button';
      del.style.padding = '6px 10px';
      del.textContent = '✕';
      del.onclick = ()=>deleteComment(state.postId, c._id);
      div.appendChild(del);

      commentsBox.appendChild(div);
      commentsEmpty.style.display = 'none';
      commentCount.textContent = String((parseInt(commentCount.textContent || '0',10)||0) + 1);

      input.value = '';
    }catch(e){
      UI.toast('Error', e.message || 'Terjadi error');
    }finally{
      sendBtn.disabled = false;
    }
  });

  async function deleteComment(postId, commentId){
    if(!confirm("Hapus komentar ini?")) return;
    try{
      const fd = new FormData();
      fd.append("post_id", postId);
      fd.append("comment_id", commentId);

      const data = await UI.postJSON("ajax/delete_comment.php", fd);
      if(!data.success) throw new Error(data.error || "Gagal menghapus komentar");

      const el = commentsBox.querySelector(`[data-comment="${commentId}"]`);
      if(el) el.remove();
      commentCount.textContent = String(Math.max(0, (parseInt(commentCount.textContent || "0",10)||0) - 1));
    }catch(e){
      UI.toast("Error", e.message || "Terjadi error");
    }
  }

  document.querySelectorAll('.p-tile').forEach(tile=>{
    tile.addEventListener('click', ()=>openFromTile(tile));
  });

  return { close };
})();
</script>

<?php render_footer(); ?>
