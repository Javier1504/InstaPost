<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/includes/user_function.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$meId = (string)$_SESSION['user_id'];
$toUsername = trim((string)($_GET['u'] ?? ''));
if ($toUsername === '') { echo "Target chat tidak valid"; exit(); }

$uf = new UserFunctions();
$target = $uf->getByUsername($toUsername);
if (!$target) { echo "User target tidak ditemukan"; exit(); }

$targetId = (string)$target['_id'];
if (!$uf->isMutual($meId, $targetId)) {
    echo "Chat hanya untuk akun yang saling follow.";
    exit();
}
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Chat</title></head>
<body style="font-family:system-ui;background:#fafafa;margin:0">
  <div style="background:#fff;border-bottom:1px solid #ddd;padding:12px 16px;display:flex;gap:12px;align-items:center;position:sticky;top:0">
    <a href="profile.php?u=<?= urlencode($toUsername) ?>">← Profil</a>
    <b>Chat @<?= e($toUsername) ?></b>
  </div>

  <div style="max-width:760px;margin:16px auto;padding:0 12px">
    <div style="background:#fff;border:1px solid #ddd;border-radius:10px;overflow:hidden">
      <div id="msgs" style="height:60vh;overflow:auto;padding:12px;display:flex;flex-direction:column;gap:8px"></div>

      <form id="f" style="display:flex;gap:10px;padding:12px;border-top:1px solid #eee">
        <input id="t" placeholder="Tulis pesan..." style="flex:1;padding:10px;border:1px solid #ddd;border-radius:10px">
        <button type="submit" style="padding:10px 14px;border:none;border-radius:10px;background:#0095f6;color:#fff">Kirim</button>
      </form>
    </div>
  </div>

<script>
const toUsername = <?= json_encode($toUsername) ?>;
let meId = null;

async function load(){
  const res = await fetch('ajax/chat_fetch.php?u=' + encodeURIComponent(toUsername));
  const txt = await res.text();
  const data = JSON.parse(txt);
  if(!data.success) return;

  meId = data.me;
  const box = document.getElementById('msgs');
  box.innerHTML = '';
  for(const m of data.messages){
    const div = document.createElement('div');
    div.textContent = m.text;
    div.style.maxWidth = '75%';
    div.style.padding = '10px 12px';
    div.style.borderRadius = '12px';
    div.style.background = (m.sender_id === meId) ? '#0095f6' : '#f1f1f1';
    div.style.color = (m.sender_id === meId) ? '#fff' : '#111';
    div.style.alignSelf = (m.sender_id === meId) ? 'flex-end' : 'flex-start';
    box.appendChild(div);
  }
  box.scrollTop = box.scrollHeight;
}

document.getElementById('f').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const input = document.getElementById('t');
  const text = (input.value || '').trim();
  if(!text) return;

  const fd = new FormData();
  fd.append('to_username', toUsername);
  fd.append('text', text);

  const res = await fetch('ajax/chat_send.php', { method:'POST', body: fd });
  const txt = await res.text();
  const data = JSON.parse(txt);

  if(!data.success){ alert(data.error || 'Gagal'); return; }
  input.value = '';
  await load();
});

load();
setInterval(load, 2500);
</script>
</body>
</html>
