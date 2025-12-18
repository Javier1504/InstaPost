<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/post_function.php';

$meId = (string)$_SESSION['user_id'];

$pf = new PostFunctions();
$feed = $pf->getFeed($meId, 30);

render_header('Dashboard');

function esc(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
  <symbol id="i-plus" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M11 19a8 8 0 1 1 8-8a8 8 0 0 1-8 8z"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0M12 11a4 4 0 1 0-4-4a4 4 0 0 0 4 4z"/></symbol>
  <symbol id="i-trash" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4h8v2M7 6l1 16h8l1-16M10 11v7M14 11v7"/></symbol>
  <symbol id="i-heart" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></symbol>
  <symbol id="i-heart-fill" viewBox="0 0 24 24"><path fill="currentColor" d="M12 21s-7-4.6-9.2-9.1C1.3 8.6 3.4 5 7.2 4.4c2-.3 3.7.6 4.8 1.9 1.1-1.3 2.8-2.2 4.8-1.9C20.6 5 22.7 8.6 21.2 11.9 19 16.4 12 21 12 21z"/></symbol>
  <symbol id="i-comment" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></symbol>
  <symbol id="i-x" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></symbol>
  <symbol id="i-chevron-left" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></symbol>
  <symbol id="i-chevron-right" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></symbol>
</svg>
<style>
  .i{width:18px;height:18px;display:inline-block;vertical-align:-0.18em}
  .btn.icon{padding:10px 12px;display:inline-flex;align-items:center;gap:8px;justify-content:center}
  .btn.danger{border-color: rgba(239,68,68,0.38); background: rgba(239,68,68,0.10)}
  .btn.danger:hover{background: rgba(239,68,68,0.14)}
  .iconbtn.primary{background: linear-gradient(135deg, rgba(109,40,217,.22), rgba(20,184,166,.18)); border-color: rgba(255,255,255,.14)}
  .story-row{display:flex;gap:10px;overflow:auto;padding-bottom:6px}
  .story-pill{display:inline-flex;align-items:center;gap:10px;white-space:nowrap}
  .story-user{font-weight:700}
  .meta small{color: var(--muted2); font-size:12px}
  .post-head .meta small{display:block;margin-top:2px}
  .badge{letter-spacing:.2px}
  .post-media{background:#05070f}
</style>

<div class="grid">
  <div class="feed">

    <?php if (empty($feed)): ?>
      <div class="glass card">
        <h2>Feed kamu masih sepi</h2>
        <div class="muted">Cari pertemanan sebanyak-banyaknya dong biar nggak sepi</div>
        <div class="sep"></div>
        <a class="btn primary icon" href="search.php" style="text-decoration:none">
          <svg class="i"><use href="#i-search"></use></svg>
          Cari Akun
        </a>
      </div>
    <?php endif; ?>
    <div class="glass card" style="margin-bottom:14px">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <div>
          <b style="font-size:16px">Stories</b>
          <div class="muted2" style="font-size:12px">Mutualan dulu baru bisa saling liat story!</div>
        </div>
        <a class="btn primary icon" href="story.php" style="text-decoration:none">
          <svg class="i"><use href="#i-plus"></use></svg>
          Upload Story
        </a>
      </div>
      <div class="sep"></div>
      <div id="storyRow" class="story-row"></div>
      <div id="storyEmpty" class="muted2" style="display:none">Belum ada story dari temenmu</div>
    </div>
    <div class="lb" id="storyLb">
      <div class="lb-card">
        <div class="lb-grid">
          <div class="lb-media" style="display:grid;place-items:center">
            <img id="storyMedia" alt="story" style="max-height:78vh;object-fit:contain">
          </div>
          <div class="lb-side">
            <div class="lb-top">
              <div class="user">
                <div class="avatar"><img id="storyPp" onerror="this.src='uploads/default.png'"></div>
                <div class="meta">
                  <b>@<span id="storyUser"></span></b>
                  <small class="muted2" id="storyTime"></small>
                </div>
              </div>
              <button class="btn icon" type="button" onclick="Story.close()" aria-label="Close">
                <svg class="i"><use href="#i-x"></use></svg>
              </button>
            </div>

            <div class="sep"></div>

            <div class="lb-cap"><b>@<span id="storyUser2"></span></b> <span id="storyCap"></span></div>

            <div class="sep"></div>

            <div style="display:flex;gap:10px">
              <button class="btn icon" onclick="Story.prev()">
                <svg class="i"><use href="#i-chevron-left"></use></svg>
                Prev
              </button>
              <button class="btn primary icon" onclick="Story.next()">
                Next
                <svg class="i"><use href="#i-chevron-right"></use></svg>
              </button>
            </div>

          </div>
        </div>
      </div>
    </div>
    <?php foreach ($feed as $p): ?>
      <?php
        $postId = (string)($p['_id'] ?? '');
        $postOwnerId = (string)($p['user_id'] ?? '');
        $isMine = ($postOwnerId === $meId);

        $hashtags = $p['hashtags'] ?? [];
        $hashtagsText = '';
        if (is_array($hashtags) && count($hashtags) > 0) {
          $hashtagsText = '#' . implode(' #', array_map('strval', $hashtags));
        }
      ?>

      <div class="glass post" id="post_<?= esc((string)$p['_id']) ?>" data-post="<?= esc((string)$p['_id']) ?>">
        <div class="post-head">
          <div class="user">
            <div class="avatar">
              <img src="uploads/<?= esc((string)($p['profile_pic'] ?? 'default.png')) ?>"
                   onerror="this.src='uploads/default.png'" alt="pp">
            </div>
            <div class="meta">
              <b>
                <a class="link" href="profile.php?u=<?= urlencode((string)($p['username'] ?? '')) ?>">
                  @<?= esc((string)($p['username'] ?? '')) ?>
                </a>
              </b>
              <?php if ($hashtagsText !== ''): ?>
                <small class="muted2"><?= esc($hashtagsText) ?></small>
              <?php endif; ?>
            </div>
          </div>

          <div style="display:flex;gap:8px;align-items:center">
            <span class="badge">Public</span>

            <?php if ($isMine): ?>
              <button class="btn danger icon" type="button"
                      style="padding:8px 10px"
                      onclick="deletePost('<?= esc($postId) ?>')" aria-label="Delete post">
                <svg class="i"><use href="#i-trash"></use></svg>
              </button>
            <?php endif; ?>
          </div>
        </div>

        <div class="post-media">
          <img src="<?= esc((string)($p['image_url'] ?? '')) ?>" alt="post">
        </div>

        <div class="post-body">
          <div class="actions">
            <button class="iconbtn likeBtn <?= !empty($p['is_liked']) ? 'liked' : '' ?>"
                    type="button"
                    data-id="<?= esc($postId) ?>"
                    aria-label="Like">
              <span class="ic">
                <svg class="i">
                  <use href="<?= !empty($p['is_liked']) ? '#i-heart-fill' : '#i-heart' ?>"></use>
                </svg>
              </span>
              <span class="count" id="lc_<?= esc($postId) ?>"><?= (int)($p['likes_count'] ?? 0) ?></span>
            </button>

            <div class="iconbtn" title="Jumlah komentar">
              <span class="ic">
                <svg class="i"><use href="#i-comment"></use></svg>
              </span>
              <span class="count" id="cc_<?= esc($postId) ?>"><?= (int)($p['comments_count'] ?? 0) ?></span>
            </div>
          </div>

          <div class="caption">
            <b>@<?= esc((string)($p['username'] ?? '')) ?></b>
            <?= esc((string)($p['caption'] ?? '')) ?>
          </div>

          <div class="comments" id="comments_<?= esc($postId) ?>">
            <?php
              $comments = $p['comments'] ?? [];
              if (!is_array($comments)) $comments = [];
            ?>
            <?php foreach ($comments as $c): ?>
              <?php
                $cid = (string)($c['_id'] ?? '');
                $cuid = (string)($c['user_id'] ?? '');
                $canDeleteComment = $isMine || ($cuid === $meId);
              ?>
              <div class="comment" data-comment="<?= esc($cid) ?>" style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                <div>
                  <b>@<?= esc((string)($c['username'] ?? '')) ?></b>
                  <?= esc((string)($c['comment'] ?? '')) ?>
                </div>

                <?php if ($canDeleteComment && $cid !== ''): ?>
                  <button class="btn danger icon" type="button" style="padding:6px 10px"
                          onclick="deleteComment('<?= esc($postId) ?>','<?= esc($cid) ?>')" aria-label="Delete comment">
                    <svg class="i"><use href="#i-x"></use></svg>
                  </button>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <form class="comment-form" data-id="<?= esc($postId) ?>" autocomplete="off">
            <input class="input" name="comment" placeholder="Tulis komentar..." maxlength="500">
            <button class="btn icon" type="submit">
              <svg class="i"><use href="#i-comment"></use></svg>
              Kirim
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>

  </div>

  <aside class="glass card">
    <h2>Quick Actions</h2>
    <div class="muted"></div>
    <div class="sep"></div>

    <a class="btn primary icon" href="post.php" style="text-decoration:none;display:flex;justify-content:center;margin-bottom:10px">
      <svg class="i"><use href="#i-plus"></use></svg>
      Buat Post
    </a>

    <a class="btn icon" href="search.php" style="text-decoration:none;display:flex;justify-content:center;margin-bottom:10px">
      <svg class="i"><use href="#i-search"></use></svg>
      Cari Akun
    </a>

    <a class="btn icon" href="profile.php" style="text-decoration:none;display:flex;justify-content:center">
      <svg class="i"><use href="#i-user"></use></svg>
      Profil Saya
    </a>

    <div class="sep"></div>
    <div class="muted2" style="font-size:13px">
      
    </div>
  </aside>
</div>

<script>
/** Escape untuk aman saat inject string ke HTML */
function escHtml(s){
  return String(s)
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

document.querySelectorAll('.likeBtn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const postId = btn.dataset.id;
    const fd = new FormData();
    fd.append('post_id', postId);

    btn.disabled = true;
    try{
      const data = await UI.postJSON('ajax/like.php', fd);
      if(!data.success) throw new Error(data.error || 'Gagal like');

      btn.classList.toggle('liked', data.liked);

      // switch icon
      const useEl = btn.querySelector('use');
      if(useEl) useEl.setAttribute('href', data.liked ? '#i-heart-fill' : '#i-heart');

      const lc = document.getElementById('lc_' + postId);
      if (lc) lc.textContent = data.likes_count;

      UI.toast('Berhasil', data.liked ? 'Kamu like postingan ini' : 'Kamu tidak jadi like');
    }catch(e){
      UI.toast('Error', e.message || 'Maaf lagi error');
    } finally {
      btn.disabled = false;
    }
  });
});

document.querySelectorAll('.comment-form').forEach(f => {
  f.addEventListener('submit', async (ev) => {
    ev.preventDefault();

    const postId = f.dataset.id;
    const input = f.querySelector('input[name="comment"]');
    const text = (input.value || '').trim();
    if(!text) return;

    const fd = new FormData();
    fd.append('post_id', postId);
    fd.append('comment', text);

    const submitBtn = f.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try{
      const data = await UI.postJSON('ajax/comment.php', fd);
      if(!data.success) throw new Error(data.error || 'Gagal komentar');

      const box = document.getElementById('comments_' + postId);
      if (box) {
        const div = document.createElement('div');
        div.className = 'comment';
        div.setAttribute('data-comment', data.comment._id);
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.gap = '10px';
        div.style.alignItems = 'flex-start';

        const left = document.createElement('div');
        left.innerHTML = `<b>@${escHtml(data.comment.username)}</b> ${escHtml(data.comment.comment)}`;

        const del = document.createElement('button');
        del.className = 'btn danger icon';
        del.type = 'button';
        del.style.padding = '6px 10px';
        del.innerHTML = `<svg class="i"><use href="#i-x"></use></svg>`;
        del.onclick = () => deleteComment(postId, data.comment._id);

        div.appendChild(left);
        div.appendChild(del);
        box.appendChild(div);
      }

      const cc = document.getElementById('cc_' + postId);
      if (cc) cc.textContent = String((parseInt(cc.textContent || "0", 10) || 0) + 1);

      input.value = '';
      UI.toast('Terkirim', 'Berhasil berkomentar');
    }catch(e){
      UI.toast('Error', e.message || 'Maaf, terjadi error');
    } finally {
      submitBtn.disabled = false;
    }
  });
});

async function deletePost(postId){
  if(!confirm("Hapus postingan ini?")) return;

  const fd = new FormData();
  fd.append("post_id", postId);

  try{
    const data = await UI.postJSON("ajax/delete_post.php", fd);
    if(!data.success) throw new Error(data.error || "Gagal menghapus post");

    const el = document.querySelector(`[data-post="${postId}"]`);
    if(el) el.remove();
    UI.toast("Terhapus", "Postingan berhasil dihapus.");
  }catch(e){
    UI.toast("Error", e.message || "Terjadi error");
  }
}

async function deleteComment(postId, commentId){
  if(!confirm("Hapus komentar ini?")) return;

  const fd = new FormData();
  fd.append("post_id", postId);
  fd.append("comment_id", commentId);

  try{
    const data = await UI.postJSON("ajax/delete_comment.php", fd);
    if(!data.success) throw new Error(data.error || "Gagal menghapus komentar");

    const cEl = document.querySelector(`[data-post="${postId}"] [data-comment="${commentId}"]`);
    if (cEl) cEl.remove();

    const cc = document.getElementById('cc_' + postId);
    if (cc) cc.textContent = String(Math.max(0, (parseInt(cc.textContent || "0", 10) || 0) - 1));

    UI.toast("Terhapus", "Komentar berhasil dihapus.");
  }catch(e){
    UI.toast("Error", e.message || "Terjadi error");
  }
}
</script>

<?php render_footer(); ?>

<script>
const Story = (() => {
  let reels = [];
  let uIndex = 0;
  let sIndex = 0;

  const row = document.getElementById('storyRow');
  const empty = document.getElementById('storyEmpty');
  const lb = document.getElementById('storyLb');

  const media = document.getElementById('storyMedia');
  const pp = document.getElementById('storyPp');
  const user = document.getElementById('storyUser');
  const user2 = document.getElementById('storyUser2');
  const cap = document.getElementById('storyCap');
  const time = document.getElementById('storyTime');

  function close(){ lb.classList.remove('show'); }
  lb.addEventListener('click', (e)=>{ if(e.target===lb) close(); });

  function renderRow(){
    row.innerHTML = '';
    if(!reels.length){
      empty.style.display = '';
      return;
    }
    empty.style.display = 'none';

    reels.forEach((r, idx) => {
      const btn = document.createElement('button');
      btn.className = 'iconbtn story-pill ' + (r.has_unseen ? 'primary' : '');
      btn.innerHTML = `
        <span class="avatar" style="width:34px;height:34px;border-radius:999px">
          <img src="uploads/${escHtml(r.profile_pic)}" onerror="this.src='uploads/default.png'">
        </span>
        <span class="story-user">@${escHtml(r.username)}</span>
      `;
      btn.onclick = () => openUser(idx);
      row.appendChild(btn);
    });
  }

  async function load(){
    try{
      const data = await fetch('ajax/story_list.php').then(r=>r.json());
      if(!data.success) throw new Error(data.error || 'Gagal load story');
      reels = data.reels || [];
      renderRow();
    }catch(e){
      UI.toast('Error', e.message || 'Story error');
    }
  }

  function openUser(idx){
    uIndex = idx;
    sIndex = 0;

    const stories = reels[uIndex].stories || [];
    const firstUnseen = stories.findIndex(x => !x.is_seen);
    if(firstUnseen >= 0) sIndex = firstUnseen;

    open();
  }

  async function markViewed(storyId){
    const fd = new FormData();
    fd.append('story_id', storyId);
    try{ await UI.postJSON('ajax/story_view.php', fd); }catch(_){}
  }

  async function open(){
    const r = reels[uIndex];
    const s = (r.stories || [])[sIndex];
    if(!s){ close(); return; }

    lb.classList.add('show');
    pp.src = 'uploads/' + r.profile_pic;
    user.textContent = r.username;
    user2.textContent = r.username;
    cap.textContent = s.caption || '';
    time.textContent = s.created_at ? new Date(s.created_at).toLocaleString() : '';

    media.src = s.media_url;

    s.is_seen = true;
    r.has_unseen = (r.stories || []).some(x => !x.is_seen);
    renderRow();

    markViewed(s._id);
  }

  function next(){
    const r = reels[uIndex];
    const stories = r.stories || [];
    if(sIndex < stories.length - 1){
      sIndex++;
      open();
      return;
    }
    if(uIndex < reels.length - 1){
      uIndex++;
      sIndex = 0;
      open();
      return;
    }
    close();
  }

  function prev(){
    if(sIndex > 0){
      sIndex--;
      open();
      return;
    }
    if(uIndex > 0){
      uIndex--;
      const stories = reels[uIndex].stories || [];
      sIndex = Math.max(0, stories.length - 1);
      open();
      return;
    }
  }

  return { load, openUser, next, prev, close };
})();

Story.load();
</script>
