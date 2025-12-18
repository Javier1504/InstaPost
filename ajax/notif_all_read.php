<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit(); }

require_once __DIR__ . '/../includes/notification_function.php';

$nf = new NotificationFunctions();
echo json_encode($nf->markAllRead((string)$_SESSION['user_id']));
