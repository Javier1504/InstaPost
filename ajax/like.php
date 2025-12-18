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

    require_once __DIR__ . '/../includes/post_function.php';
    require_once __DIR__ . '/../includes/notification_function.php';
    require_once __DIR__ . '/../config/database.php';

    $postId = trim((string)($_POST['post_id'] ?? ''));
    if ($postId === '') {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'post_id kosong']);
        exit();
    }

    $meId = (string)$_SESSION['user_id'];

    $pf = new PostFunctions();
    $nf = new NotificationFunctions();
    $db = getDB();

    $res = $pf->toggleLike($postId, $meId);

    if (!($res['success'] ?? false)) {
        http_response_code(400);
        ob_clean();
        echo json_encode($res);
        exit();
    }

    $post = $db->posts->findOne(['_id' => new MongoDB\BSON\ObjectId($postId)]);

    if ($post) {
        $ownerId = (string)$post['user_id'];
        $postImg = (string)($post['image_url'] ?? '');

        if (!empty($res['liked'])) {
            $fromUsername = (string)($_SESSION['username'] ?? '');
            $fromPic      = (string)($_SESSION['profile_pic'] ?? 'default.png');

            $nf->notifyLike($ownerId, $meId, $fromUsername, $fromPic, $postId, $postImg);
        } else {
           
            $db->notifications->deleteMany([
                'type' => 'like',
                'is_read' => false,
                'user_id' => new MongoDB\BSON\ObjectId($ownerId),
                'from_user_id' => new MongoDB\BSON\ObjectId($meId),
                'post_id' => new MongoDB\BSON\ObjectId($postId),
            ]);
        }
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
