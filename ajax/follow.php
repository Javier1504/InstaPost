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
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }

    require_once __DIR__ . '/../includes/user_function.php';
    require_once __DIR__ . '/../includes/notification_function.php';

    $action   = trim((string)($_POST['action'] ?? ''));
    $targetId = trim((string)($_POST['target_id'] ?? ''));

    if ($targetId === '' || !in_array($action, ['follow', 'unfollow'], true)) {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Bad request']);
        exit();
    }

    $meId = (string)$_SESSION['user_id'];

    if ($meId === $targetId) {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Tidak bisa follow diri sendiri']);
        exit();
    }

    $uf = new UserFunctions();
    $nf = new NotificationFunctions();

    if ($action === 'follow') {
        $res = $uf->follow($meId, $targetId);

    
        if (($res['success'] ?? false) === true) {
            $fromUsername = (string)($_SESSION['username'] ?? '');
            $fromPic      = (string)($_SESSION['profile_pic'] ?? 'default.png');
            $nf->notifyFollow($targetId, $meId, $fromUsername, $fromPic);
        }
    } else {
        $res = $uf->unfollow($meId, $targetId);
    }

    
    if (($res['success'] ?? false) === true) {
        $res['is_following'] = $uf->isFollowing($meId, $targetId);
        $res['is_mutual']    = $uf->isMutual($meId, $targetId);
    }

    ob_clean();
    echo json_encode($res);
    exit();

} catch (Throwable $t) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Server error']);
    exit();
}
