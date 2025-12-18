<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit();
}

require_once __DIR__ . '/../includes/story_function.php';

$storyId = trim((string)($_POST['story_id'] ?? ''));
if ($storyId === '') { echo json_encode(['success'=>false,'error'=>'story_id required']); exit(); }

$sf = new StoryFunctions();
$res = $sf->markViewed($storyId, (string)$_SESSION['user_id']);
echo json_encode($res);
