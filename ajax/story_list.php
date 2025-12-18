<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit();
}

require_once __DIR__ . '/../includes/story_function.php';

$sf = new StoryFunctions();
$reels = $sf->getReelsForUser((string)$_SESSION['user_id'], 40, 20);

echo json_encode(['success'=>true,'reels'=>$reels]);
