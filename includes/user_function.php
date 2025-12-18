<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Regex;

final class UserFunctions {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }
    private function oid(string $id): ?ObjectId {
        $id = trim($id);
        if ($id === '') return null;
        try { return new ObjectId($id); }
        catch (\Throwable $e) { return null; }
    }

    private function arr($v): array {
        if (is_array($v)) return $v;
        if ($v instanceof \Traversable) return iterator_to_array($v, false);
        if ($v === null) return [];
        return (array)$v;
    }

    private function idToString($v): string {
        if ($v instanceof ObjectId) return (string)$v;
        return (string)$v;
    }

    private function normalizeUserDoc($u): array {
        if (!$u) return [];

        $u['_id'] = $this->idToString($u['_id']);

        $u['followers'] = array_values(array_unique(array_map(
            fn($x) => $this->idToString($x),
            $this->arr($u['followers'] ?? [])
        )));

        $u['following'] = array_values(array_unique(array_map(
            fn($x) => $this->idToString($x),
            $this->arr($u['following'] ?? [])
        )));

        $u['profile_pic'] = (string)($u['profile_pic'] ?? 'default.png');
        $u['full_name']   = (string)($u['full_name'] ?? ($u['username'] ?? ''));
        $u['bio']         = (string)($u['bio'] ?? '');
        $u['posts_count'] = (int)($u['posts_count'] ?? 0);

        return $u;
    }
    public function getById(string $userId): ?array {
        $oid = $this->oid($userId);
        if (!$oid) return null;

        $u = $this->db->users->findOne(['_id' => $oid]);
        if (!$u) return null;

        return $this->normalizeUserDoc((array)$u);
    }

    public function getByUsername(string $username): ?array {
        $username = trim($username);
        if ($username === '') return null;
        $rx = new Regex('^' . preg_quote($username, '/') . '$', 'i');
        $u = $this->db->users->findOne(['username' => $rx]);
        if (!$u) return null;

        return $this->normalizeUserDoc((array)$u);
    }
    public function register(string $username, string $email, string $password, string $fullName = ''): array {
        $username = trim($username);
        $email = trim(mb_strtolower($email));
        $fullName = trim($fullName);

        if ($username === '' || mb_strlen($username) < 3) {
            return ['success' => false, 'error' => 'Username minimal 3 karakter'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email tidak valid'];
        }
        if (mb_strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password minimal 6 karakter'];
        }

        $rxUser = new Regex('^' . preg_quote($username, '/') . '$', 'i');
        $existUser = $this->db->users->findOne(['username' => $rxUser], ['projection' => ['_id' => 1]]);
        if ($existUser) return ['success' => false, 'error' => 'Username sudah dipakai'];

        $existEmail = $this->db->users->findOne(['email' => $email], ['projection' => ['_id' => 1]]);
        if ($existEmail) return ['success' => false, 'error' => 'Email sudah terdaftar'];

        $now = new UTCDateTime();

        $doc = [
            '_id' => new ObjectId(),
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'full_name' => $fullName !== '' ? $fullName : $username,
            'bio' => '',
            'profile_pic' => 'default.png',
            'followers' => [],
            'following' => [],
            'posts_count' => 0,
            'created_at' => $now,
            'updated_at' => $now
        ];

        $this->db->users->insertOne($doc);

        return ['success' => true, 'user_id' => (string)$doc['_id']];
    }

    public function login(string $identity, string $password): array {
        $identity = trim($identity);

        if ($identity === '' || $password === '') {
            return ['success' => false, 'error' => 'Data login tidak lengkap'];
        }

        $q = [
            '$or' => [
                ['email' => mb_strtolower($identity)],
                ['username' => new Regex('^' . preg_quote($identity, '/') . '$', 'i')],
            ]
        ];

        $u = $this->db->users->findOne($q);
        if (!$u) return ['success' => false, 'error' => 'Akun tidak ditemukan'];

        $uArr = (array)$u;
        $hash = (string)($uArr['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return ['success' => false, 'error' => 'Password salah'];
        }

        $user = $this->normalizeUserDoc($uArr);
        return ['success' => true, 'user' => $user];
    }
    public function updateProfile(string $userId, string $fullName, string $bio, ?string $profilePic = null): array {
        $oid = $this->oid($userId);
        if (!$oid) return ['success' => false, 'error' => 'UserId tidak valid'];

        $set = [
            'full_name' => trim($fullName),
            'bio' => trim($bio),
            'updated_at' => new UTCDateTime()
        ];
        if ($profilePic !== null && $profilePic !== '') {
            $set['profile_pic'] = $profilePic;
        }

        $this->db->users->updateOne(['_id' => $oid], ['$set' => $set]);
        return ['success' => true];
    }
    public function isFollowing(string $meId, string $targetId): bool {
        $meOid = $this->oid($meId);
        if (!$meOid) return false;

        $targetOid = $this->oid($targetId);

        $cond = ['_id' => $meOid, '$or' => []];
        $cond['$or'][] = ['following' => $targetId];
        if ($targetOid) $cond['$or'][] = ['following' => $targetOid];

        $u = $this->db->users->findOne($cond, ['projection' => ['_id' => 1]]);
        return $u !== null;
    }

    public function isMutual(string $meId, string $targetId): bool {
        return $this->isFollowing($meId, $targetId) && $this->isFollowing($targetId, $meId);
    }

    public function follow(string $meId, string $targetId): array {
        if ($meId === $targetId) return ['success' => false, 'error' => 'Tidak bisa follow diri sendiri'];

        $meOid = $this->oid($meId);
        $targetOid = $this->oid($targetId);
        if (!$meOid || !$targetOid) return ['success' => false, 'error' => 'UserId tidak valid'];
        $this->db->users->updateOne(
            ['_id' => $meOid],
            ['$addToSet' => ['following' => $targetId], '$set' => ['updated_at' => new UTCDateTime()]]
        );
        $this->db->users->updateOne(
            ['_id' => $targetOid],
            ['$addToSet' => ['followers' => $meId], '$set' => ['updated_at' => new UTCDateTime()]]
        );

        return ['success' => true];
    }

    public function unfollow(string $meId, string $targetId): array {
        if ($meId === $targetId) return ['success' => false, 'error' => 'Tidak bisa unfollow diri sendiri'];

        $meOid = $this->oid($meId);
        $targetOid = $this->oid($targetId);
        if (!$meOid || !$targetOid) return ['success' => false, 'error' => 'UserId tidak valid'];
        $pullTarget = ['$in' => array_values(array_filter([$targetId, $targetOid]))];
        $pullMe = ['$in' => array_values(array_filter([$meId, $meOid]))];

        $this->db->users->updateOne(
            ['_id' => $meOid],
            ['$pull' => ['following' => $pullTarget], '$set' => ['updated_at' => new UTCDateTime()]]
        );
        $this->db->users->updateOne(
            ['_id' => $targetOid],
            ['$pull' => ['followers' => $pullMe], '$set' => ['updated_at' => new UTCDateTime()]]
        );

        return ['success' => true];
    }
    public function searchUsers(string $q, int $limit = 30): array {
        $q = trim($q);
        if ($q === '') return [];

        $escaped = preg_quote($q, '/');
        $regex = new Regex($escaped, 'i');

        $cursor = $this->db->users->find(
            [
                '$or' => [
                    ['username' => $regex],
                    ['full_name' => $regex],
                ]
            ],
            [
                'projection' => [
                    'username' => 1,
                    'full_name' => 1,
                    'profile_pic' => 1,
                    'followers' => 1,
                    'following' => 1,
                    'posts_count' => 1,
                ],
                'limit' => $limit,
                'sort'  => ['username' => 1]
            ]
        );

        $out = [];
        foreach ($cursor as $u) {
            $uArr = (array)$u;
            $uArr['_id'] = (string)$uArr['_id'];
            $uArr['profile_pic'] = (string)($uArr['profile_pic'] ?? 'default.png');
            $uArr['full_name'] = (string)($uArr['full_name'] ?? '');
            $out[] = $uArr;
        }
        return $out;
    }
}
