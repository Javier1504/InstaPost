<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit();
}

require_once __DIR__ . '/../includes/post_function.php';

$postId = trim((string)($_POST['post_id'] ?? ''));
if ($postId === '') {
  echo json_encode(['success'=>false,'error'=>'post_id kosong']); exit();
}

$pf = new PostFunctions();
$res = $pf->deletePost($postId, (string)$_SESSION['user_id']);

echo json_encode($res);
