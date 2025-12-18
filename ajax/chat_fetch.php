<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_clean();
        echo json_encode(['success'=>false,'error'=>'Unauthorized']);
        exit();
    }

    require_once __DIR__ . '/../includes/user_function.php';
    require_once __DIR__ . '/../includes/chat_function.php';

    $toUsername = trim((string)($_GET['u'] ?? ''));
    if ($toUsername === '') {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success'=>false,'error'=>'Target kosong']);
        exit();
    }

    $uf = new UserFunctions();
    $target = $uf->getByUsername($toUsername);
    if (!$target) {
        http_response_code(404);
        ob_clean();
        echo json_encode(['success'=>false,'error'=>'User target tidak ditemukan']);
        exit();
    }

    $meId = (string)$_SESSION['user_id'];
    $targetId = (string)$target['_id'];

    if (!$uf->isMutual($meId, $targetId)) {
        http_response_code(403);
        ob_clean();
        echo json_encode(['success'=>false,'error'=>'Chat hanya untuk akun yang saling follow']);
        exit();
    }

    $cf = new ChatFunctions();
    $convId = $cf->getOrCreateConversation($meId, $targetId);
    $messages = $cf->fetch($convId, 200);

    ob_clean();
    echo json_encode(['success'=>true,'me'=>$meId,'messages'=>$messages]);
    exit();

} catch (Throwable $t) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['success'=>false,'error'=>$t->getMessage()]);
    exit();
}
