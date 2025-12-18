<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

final class NotificationFunctions {
  private $db;

  public function __construct() {
    $this->db = getDB();
  }

  private function oid(string $id): ?ObjectId {
    try { return new ObjectId($id); } catch (\Throwable $e) { return null; }
  }

  private function arr($v): array {
    if (is_array($v)) return $v;
    if ($v instanceof Traversable) return iterator_to_array($v, false);
    if ($v === null) return [];
    return (array)$v;
  }

  private function docToArray($doc): array {
    if (is_object($doc) && method_exists($doc, 'getArrayCopy')) {
      return $doc->getArrayCopy();
    }
    return (array)$doc;
  }

  public function getUnreadCount(string $userId): int {
    $uid = $this->oid($userId);
    if (!$uid) return 0;

    return (int)$this->db->notifications->countDocuments([
      'user_id' => $uid,
      'is_read' => false
    ]);
  }

  public function listNotifications(string $userId, int $limit = 60): array {
    $uid = $this->oid($userId);
    if (!$uid) return [];

    $cursor = $this->db->notifications->find(
      ['user_id' => $uid],
      ['sort' => ['created_at' => -1], 'limit' => $limit]
    );

    $out = [];
    foreach ($cursor as $n) {
      $a = $this->docToArray($n);

      $a['_id'] = isset($a['_id']) ? (string)$a['_id'] : '';
      $a['user_id'] = isset($a['user_id']) ? (string)$a['user_id'] : '';
      $a['from_user_id'] = isset($a['from_user_id']) ? (string)$a['from_user_id'] : '';
      $a['post_id'] = isset($a['post_id']) ? (string)$a['post_id'] : '';
      $a['is_read'] = (bool)($a['is_read'] ?? false);

      if (isset($a['created_at']) && $a['created_at'] instanceof UTCDateTime) {
        $a['created_at_iso'] = $a['created_at']->toDateTime()->format('c');
      } else {
        $a['created_at_iso'] = null;
      }

      $out[] = $a;
    }

    return $out;
  }

  public function markRead(string $userId, string $notifId): array {
    $uid = $this->oid($userId);
    $nid = $this->oid($notifId);
    if (!$uid || !$nid) return ['success' => false, 'error' => 'Invalid id'];

    $res = $this->db->notifications->updateOne(
      ['_id' => $nid, 'user_id' => $uid],
      ['$set' => ['is_read' => true]]
    );

    return ['success' => true, 'modified' => (int)$res->getModifiedCount()];
  }

  public function markAllRead(string $userId): array {
    $uid = $this->oid($userId);
    if (!$uid) return ['success' => false, 'error' => 'Invalid user'];

    $res = $this->db->notifications->updateMany(
      ['user_id' => $uid, 'is_read' => false],
      ['$set' => ['is_read' => true]]
    );

    return ['success' => true, 'modified' => (int)$res->getModifiedCount()];
  }

  public function notifyFollow(string $toUserId, string $fromUserId, string $fromUsername, string $fromPic): void {
    if ($toUserId === $fromUserId) return;

    $to = $this->oid($toUserId);
    $from = $this->oid($fromUserId);
    if (!$to || !$from) return;

    $now = new UTCDateTime();

    $filter = [
      'user_id' => $to,
      'type' => 'follow',
      'from_user_id' => $from,
      'is_read' => false
    ];

    $update = [
      '$set' => [
        'from_username' => $fromUsername,
        'from_profile_pic' => $fromPic,
        'created_at' => $now,
      ],
      '$setOnInsert' => [
        '_id' => new ObjectId(),
        'user_id' => $to,
        'type' => 'follow',
        'from_user_id' => $from,
        'post_id' => null,
        'post_image' => null,
        'comment' => null,
        'is_read' => false
      ]
    ];

    $this->db->notifications->updateOne($filter, $update, ['upsert' => true]);
  }

  public function notifyLike(string $toUserId, string $fromUserId, string $fromUsername, string $fromPic, string $postId, string $postImage): void {
    if ($toUserId === $fromUserId) return;

    $to = $this->oid($toUserId);
    $from = $this->oid($fromUserId);
    $pid = $this->oid($postId);
    if (!$to || !$from || !$pid) return;

    $now = new UTCDateTime();

    $filter = [
      'user_id' => $to,
      'type' => 'like',
      'from_user_id' => $from,
      'post_id' => $pid,
      'is_read' => false
    ];

    $update = [
      '$set' => [
        'from_username' => $fromUsername,
        'from_profile_pic' => $fromPic,
        'post_image' => $postImage,
        'created_at' => $now,
      ],
      '$setOnInsert' => [
        '_id' => new ObjectId(),
        'user_id' => $to,
        'type' => 'like',
        'from_user_id' => $from,
        'post_id' => $pid,
        'comment' => null,
        'is_read' => false
      ]
    ];

    $this->db->notifications->updateOne($filter, $update, ['upsert' => true]);
  }

  public function notifyComment(string $toUserId, string $fromUserId, string $fromUsername, string $fromPic, string $postId, string $postImage, string $commentText): void {
    if ($toUserId === $fromUserId) return;

    $to = $this->oid($toUserId);
    $from = $this->oid($fromUserId);
    $pid = $this->oid($postId);
    if (!$to || !$from || !$pid) return;

    $now = new UTCDateTime();

    $doc = [
      '_id' => new ObjectId(),
      'user_id' => $to,
      'type' => 'comment',
      'from_user_id' => $from,
      'from_username' => $fromUsername,
      'from_profile_pic' => $fromPic,
      'post_id' => $pid,
      'post_image' => $postImage,
      'comment' => $commentText,
      'created_at' => $now,
      'is_read' => false
    ];

    $this->db->notifications->insertOne($doc);
  }
}
