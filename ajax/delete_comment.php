<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit();
}

require_once __DIR__ . '/../includes/post_function.php';

$postId = trim((string)($_POST['post_id'] ?? ''));
$commentId = trim((string)($_POST['comment_id'] ?? ''));

if ($postId === '' || $commentId === '') {
  echo json_encode(['success'=>false,'error'=>'post_id/comment_id kosong']); exit();
}

$pf = new PostFunctions();
$res = $pf->deleteComment($postId, $commentId, (string)$_SESSION['user_id']);

echo json_encode($res);
