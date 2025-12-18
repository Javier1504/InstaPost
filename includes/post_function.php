<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

final class PostFunctions {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }
    private function oidSafe(string $id): ?ObjectId {
        $id = trim($id);
        if ($id === '') return null;
        try { return new ObjectId($id); }
        catch (\Throwable $e) { return null; }
    }

    private function oid(string $id): ObjectId {
        return new ObjectId($id);
    }

    private function arr($v): array {
        if (is_array($v)) return $v;
        if ($v instanceof Traversable) return iterator_to_array($v, false);
        if ($v === null) return [];
        return (array)$v;
    }

    public function createPost(string $userId, string $caption, string $imageUrl): array {
        $uoid = $this->oidSafe($userId);
        if (!$uoid) return ['success' => false, 'error' => 'UserId tidak valid'];

        $user = $this->db->users->findOne(['_id' => $uoid]);
        if (!$user) return ['success' => false, 'error' => 'User tidak ditemukan'];

        preg_match_all('/#(\w+)/u', $caption, $m);
        $hashtags = array_map('strtolower', $m[1] ?? []);

        $now = new UTCDateTime();

        $doc = [
            '_id' => new ObjectId(),
            'user_id' => $uoid,
            'username' => (string)$user['username'],
            'profile_pic' => (string)($user['profile_pic'] ?? 'default.png'),
            'caption' => $caption,
            'image_url' => $imageUrl, 
            'hashtags' => $hashtags,
            'likes' => [], 
            'comments' => [],
            'likes_count' => 0,
            'comments_count' => 0,
            'created_at' => $now,
            'updated_at' => $now
        ];

        $this->db->posts->insertOne($doc);

        $this->db->users->updateOne(
            ['_id' => $uoid],
            ['$inc' => ['posts_count' => 1], '$set' => ['updated_at' => $now]]
        );

        return ['success' => true, 'post_id' => (string)$doc['_id']];
    }

    public function getUserPosts(string $userId, int $limit = 30): array {
        $uoid = $this->oidSafe($userId);
        if (!$uoid) return [];

        $cursor = $this->db->posts->find(
            ['user_id' => $uoid],
            ['sort' => ['created_at' => -1], 'limit' => $limit]
        );

        $out = [];
        foreach ($cursor as $p) {
            $p['_id'] = (string)$p['_id'];
            $p['user_id'] = (string)$p['user_id'];
            $p['likes'] = array_map('strval', $this->arr($p['likes'] ?? []));
            $p['comments'] = $this->arr($p['comments'] ?? []);
            $out[] = $p;
        }
        return $out;
    }

    public function getFeed(string $userId, int $limit = 20): array {
        $meOid = $this->oidSafe($userId);
        if (!$meOid) return [];

        $me = $this->db->users->findOne(['_id' => $meOid]);
        if (!$me) return [];

        $following = $this->arr($me['following'] ?? []);

        $followingIds = [];
        foreach ($following as $id) {
            try {
                if ($id instanceof ObjectId) $followingIds[] = $id;
                else $followingIds[] = new ObjectId((string)$id);
            } catch (\Throwable $e) {
               
            }
        }
        $followingIds[] = $meOid; 

        $cursor = $this->db->posts->find(
            ['user_id' => ['$in' => $followingIds]],
            ['sort' => ['created_at' => -1], 'limit' => $limit]
        );

        $out = [];
        foreach ($cursor as $p) {
            $p['_id'] = (string)$p['_id'];
            $p['user_id'] = (string)$p['user_id'];
            $p['likes'] = array_map('strval', $this->arr($p['likes'] ?? []));
            $p['comments'] = $this->arr($p['comments'] ?? []);
            $p['is_liked'] = in_array($userId, $p['likes'], true);
            $out[] = $p;
        }
        return $out;
    }

    public function toggleLike(string $postId, string $userId): array {
        $poid = $this->oidSafe($postId);
        if (!$poid) return ['success' => false, 'error' => 'post_id tidak valid'];

        $post = $this->db->posts->findOne(['_id' => $poid]);
        if (!$post) return ['success' => false, 'error' => 'Post tidak ditemukan'];

        $likes = array_map('strval', $this->arr($post['likes'] ?? []));
        $liked = in_array($userId, $likes, true);

        if ($liked) {
            $likes = array_values(array_diff($likes, [$userId]));
            $liked = false;
        } else {
            $likes[] = $userId;
            $liked = true;
        }

        $this->db->posts->updateOne(
            ['_id' => $poid],
            ['$set' => ['likes' => $likes, 'likes_count' => count($likes), 'updated_at' => new UTCDateTime()]]
        );

        return ['success' => true, 'liked' => $liked, 'likes_count' => count($likes)];
    }

    public function addComment(string $postId, string $userId, string $text): array {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > 500) {
            return ['success' => false, 'error' => 'Komentar kosong/terlalu panjang'];
        }

        $poid = $this->oidSafe($postId);
        $uoid = $this->oidSafe($userId);
        if (!$poid || !$uoid) return ['success' => false, 'error' => 'ID tidak valid'];

        $post = $this->db->posts->findOne(['_id' => $poid]);
        if (!$post) return ['success' => false, 'error' => 'Post tidak ditemukan'];

        $user = $this->db->users->findOne(['_id' => $uoid]);
        if (!$user) return ['success' => false, 'error' => 'User tidak ditemukan'];

        $comment = [
            '_id' => new ObjectId(),
            'user_id' => $uoid,
            'username' => (string)$user['username'],
            'profile_pic' => (string)($user['profile_pic'] ?? 'default.png'),
            'comment' => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'created_at' => new UTCDateTime()
        ];

        $this->db->posts->updateOne(
            ['_id' => $poid],
            [
                '$push' => ['comments' => $comment],
                '$inc' => ['comments_count' => 1],
                '$set' => ['updated_at' => new UTCDateTime()]
            ]
        );

        $comment['_id'] = (string)$comment['_id'];
        $comment['user_id'] = (string)$comment['user_id'];

        return ['success' => true, 'comment' => $comment];
    }

    public function deleteComment(string $postId, string $commentId, string $userId): array {
        $poid = $this->oidSafe($postId);
        $coid = $this->oidSafe($commentId);
        if (!$poid || !$coid) return ['success' => false, 'error' => 'ID tidak valid'];

        $post = $this->db->posts->findOne(['_id' => $poid]);
        if (!$post) return ['success' => false, 'error' => 'Post tidak ditemukan'];

        $isPostOwner = ((string)$post['user_id'] === (string)$userId);

        $found = null;
        foreach ($this->arr($post['comments'] ?? []) as $c) {
            if (isset($c['_id']) && (string)$c['_id'] === (string)$coid) {
                $found = $c;
                break;
            }
        }
        if (!$found) return ['success' => false, 'error' => 'Komentar tidak ditemukan'];

        $isCommentOwner = (isset($found['user_id']) && (string)$found['user_id'] === (string)$userId);

        if (!$isPostOwner && !$isCommentOwner) {
            return ['success' => false, 'error' => 'Tidak punya izin menghapus komentar ini'];
        }

        $res = $this->db->posts->updateOne(
            ['_id' => $poid],
            [
                '$pull' => ['comments' => ['_id' => $coid]],
                '$inc' => ['comments_count' => -1],
                '$set' => ['updated_at' => new UTCDateTime()]
            ]
        );

        if ($res->getModifiedCount() <= 0) {
            return ['success' => false, 'error' => 'Gagal menghapus komentar'];
        }

        return ['success' => true];
    }

    public function deletePost(string $postId, string $userId): array {
        $poid = $this->oidSafe($postId);
        $uoid = $this->oidSafe($userId);
        if (!$poid || !$uoid) return ['success' => false, 'error' => 'ID tidak valid'];

        $post = $this->db->posts->findOne(['_id' => $poid]);
        if (!$post) return ['success' => false, 'error' => 'Post tidak ditemukan'];

        if ((string)$post['user_id'] !== (string)$userId) {
            return ['success' => false, 'error' => 'Tidak punya izin menghapus post ini'];
        }

        $imageUrl = (string)($post['image_url'] ?? '');

        $res = $this->db->posts->deleteOne(['_id' => $poid, 'user_id' => $uoid]);

        if ($res->getDeletedCount() <= 0) {
            return ['success' => false, 'error' => 'Gagal menghapus post'];
        }

        $this->db->users->updateOne(
            ['_id' => $uoid],
            ['$inc' => ['posts_count' => -1], '$set' => ['updated_at' => new UTCDateTime()]]
        );

        if ($imageUrl !== '' && str_starts_with($imageUrl, 'uploads/')) {
            $path = realpath(__DIR__ . '/../' . $imageUrl);
            $uploadsDir = realpath(__DIR__ . '/../uploads');
            if ($path && $uploadsDir && str_starts_with($path, $uploadsDir)) {
                @unlink($path);
            }
        }

        return ['success' => true];
    }
}
