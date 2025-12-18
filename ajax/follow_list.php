<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Unauthorized']);
  exit();
}

require_once __DIR__ . '/../includes/user_function.php';

$uf = new UserFunctions();

$meId = (string)$_SESSION['user_id'];

$targetId = trim((string)($_GET['target_id'] ?? ''));
$type = (string)($_GET['type'] ?? 'followers'); 
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit = (int)($_GET['limit'] ?? 30);
if ($limit < 1) $limit = 30;
if ($limit > 60) $limit = 60;

if ($targetId === '') {
  echo json_encode(['success'=>false,'error'=>'target_id kosong']);
  exit();
}
if ($type !== 'followers' && $type !== 'following') {
  echo json_encode(['success'=>false,'error'=>'type tidak valid']);
  exit();
}

$user = $uf->getById($targetId);
if (!$user) {
  echo json_encode(['success'=>false,'error'=>'User tidak ditemukan']);
  exit();
}

$ids = [];
if ($type === 'followers') {
  $ids = is_array($user['followers'] ?? null) ? $user['followers'] : [];
} else {
  $ids = is_array($user['following'] ?? null) ? $user['following'] : [];
}

$total = count($ids);
$slice = array_slice($ids, $offset, $limit);

$items = [];
foreach ($slice as $id) {
  $id = (string)$id;
  if ($id === '') continue;

  $u = $uf->getById($id);
  if (!$u) continue;

  $uid = (string)($u['_id'] ?? '');
  $uname = (string)($u['username'] ?? '');
  $pp = (string)($u['profile_pic'] ?? 'default.png');
  $full = (string)($u['full_name'] ?? $uname);

  $isMe = ($uid === $meId);
  $isFollowing = (!$isMe) ? $uf->isFollowing($meId, $uid) : false;

  $items[] = [
    'id' => $uid,
    'username' => $uname,
    'profile_pic' => $pp,
    'full_name' => $full,
    'is_me' => $isMe,
    'is_following' => $isFollowing
  ];
}

echo json_encode([
  'success' => true,
  'total' => $total,
  'items' => $items
]);
