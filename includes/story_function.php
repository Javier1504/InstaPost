<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

final class StoryFunctions {
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

  public function createStory(string $userId, string $mediaUrl, string $caption=''): array {
    $uid = $this->oid($userId);
    if (!$uid) return ['success'=>false,'error'=>'UserId invalid'];

    $user = $this->db->users->findOne(['_id'=>$uid]);
    if (!$user) return ['success'=>false,'error'=>'User tidak ditemukan'];

    $now = new UTCDateTime();
    $expires = new UTCDateTime((int)((microtime(true) + 24*3600) * 1000));

    $doc = [
      '_id' => new ObjectId(),
      'user_id' => $uid,
      'username' => (string)$user['username'],
      'profile_pic' => (string)($user['profile_pic'] ?? 'default.png'),
      'media_url' => $mediaUrl,
      'caption' => $caption,
      'viewers' => [],          
      'views_count' => 0,
      'created_at' => $now,
      'expires_at' => $expires
    ];

    $this->db->stories->insertOne($doc);
    return ['success'=>true,'story_id'=>(string)$doc['_id']];
  }
  public function getReelsForUser(string $meId, int $limitUsers=30, int $limitPerUser=20): array {
    $meOid = $this->oid($meId);
    if (!$meOid) return [];

    $me = $this->db->users->findOne(['_id'=>$meOid], ['projection'=>['following'=>1,'followers'=>1,'username'=>1,'profile_pic'=>1]]);
    if (!$me) return [];

    $following = array_map('strval', $this->arr($me['following'] ?? []));
    $followers = array_map('strval', $this->arr($me['followers'] ?? []));

    $mutualIds = array_values(array_intersect($following, $followers));
    $mutualIds[] = $meId;
    $now = new UTCDateTime();
    $idsOid = [];
    foreach ($mutualIds as $sid) {
      $o = $this->oid((string)$sid);
      if ($o) $idsOid[] = $o;
    }
    if (!$idsOid) return [];

    $cursor = $this->db->stories->find(
      [
        'user_id' => ['$in' => $idsOid],
        'expires_at' => ['$gt' => $now]
      ],
      ['sort'=>['created_at'=>-1]]
    );

    $byUser = [];
    foreach ($cursor as $s) {
      $uid = (string)$s['user_id'];
      if (!isset($byUser[$uid])) $byUser[$uid] = [
        'user_id' => $uid,
        'username' => (string)$s['username'],
        'profile_pic' => (string)($s['profile_pic'] ?? 'default.png'),
        'stories' => [],
        'has_unseen' => false
      ];

      $viewers = array_map('strval', $this->arr($s['viewers'] ?? []));
      $isSeen = in_array($meId, $viewers, true);

      $byUser[$uid]['stories'][] = [
        '_id' => (string)$s['_id'],
        'media_url' => (string)$s['media_url'],
        'caption' => (string)($s['caption'] ?? ''),
        'created_at' => isset($s['created_at']) ? $s['created_at']->toDateTime()->format('c') : null,
        'expires_at' => isset($s['expires_at']) ? $s['expires_at']->toDateTime()->format('c') : null,
        'is_seen' => $isSeen
      ];

      if (!$isSeen) $byUser[$uid]['has_unseen'] = true;
    }
    $users = array_values($byUser);
    usort($users, function($a,$b){
      if ($a['has_unseen'] === $b['has_unseen']) return 0;
      return $a['has_unseen'] ? -1 : 1;
    });

    return array_slice($users, 0, $limitUsers);
  }
  public function markViewed(string $storyId, string $meId): array {
    $sid = $this->oid($storyId);
    if (!$sid) return ['success'=>false,'error'=>'StoryId invalid'];

    $now = new UTCDateTime();

    $s = $this->db->stories->findOne(['_id'=>$sid]);
    if (!$s) return ['success'=>false,'error'=>'Story tidak ditemukan'];
    if (isset($s['expires_at']) && $s['expires_at'] <= $now) return ['success'=>false,'error'=>'Story sudah expire'];

    $viewers = array_map('strval', $this->arr($s['viewers'] ?? []));
    if (!in_array($meId, $viewers, true)) {
      $viewers[] = $meId;
      $this->db->stories->updateOne(
        ['_id'=>$sid],
        ['$set'=>['viewers'=>$viewers,'views_count'=>count($viewers)]]
      );
    }

    return ['success'=>true,'views_count'=>count($viewers)];
  }
}
