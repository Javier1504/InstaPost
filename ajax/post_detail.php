<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit();
}

require_once __DIR__ . '/../includes/post_function.php';

$meId = (string)$_SESSION['user_id'];
$postId = trim((string)($_GET['post_id'] ?? ''));
$ownerId = trim((string)($_GET['owner_id'] ?? ''));

if($postId === ''){
  echo json_encode(['success'=>false,'error'=>'post_id kosong']); exit();
}

$pf = new PostFunctions();

try{
  if (method_exists($pf, 'getPostDetail')) {
    $post = $pf->getPostDetail($postId, $meId);
  } else {
    $post = null;
    if($ownerId !== ''){
      $arr = $pf->getUserPosts($ownerId, 300);
      if(is_array($arr)){
        foreach($arr as $p){
          if((string)($p['_id'] ?? '') === $postId){ $post = $p; break; }
        }
      }
    }
    if(!$post) {
      echo json_encode(['success'=>false,'error'=>'Post tidak ditemukan']); exit();
    }

  
    $likesCount = (int)($post['likes_count'] ?? 0);
    $comments = is_array($post['comments'] ?? null) ? $post['comments'] : [];
    $commentsCount = (int)($post['comments_count'] ?? count($comments));
    $isLiked = (bool)($post['is_liked'] ?? false);

    $normComments = [];
    foreach($comments as $c){
      $cid = (string)($c['_id'] ?? '');
      $cuid = (string)($c['user_id'] ?? '');
      $owner = (string)($post['user_id'] ?? '');
      $normComments[] = [
        '_id' => $cid,
        'user_id' => $cuid,
        'username' => (string)($c['username'] ?? ''),
        'comment' => (string)($c['comment'] ?? ''),
        'can_delete' => ($cuid === $meId) || ($owner === $meId),
      ];
    }

    $post = [
      '_id' => (string)($post['_id'] ?? ''),
      'user_id' => (string)($post['user_id'] ?? $ownerId),
      'username' => (string)($post['username'] ?? ''),
      'profile_pic' => (string)($post['profile_pic'] ?? 'default.png'),
      'image_url' => (string)($post['image_url'] ?? ''),
      'caption' => (string)($post['caption'] ?? ''),
      'created_at' => $post['created_at'] ?? null,
      'likes_count' => $likesCount,
      'comments_count' => $commentsCount,
      'is_liked' => $isLiked,
      'comments' => $normComments,
    ];
  }

  echo json_encode(['success'=>true, 'post'=>$post]);
}catch(Throwable $e){
  echo json_encode(['success'=>false,'error'=>'Server error']);
}
