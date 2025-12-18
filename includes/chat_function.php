<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

final class ChatFunctions {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    private function convKey(string $a, string $b): string {
        $pair = [$a, $b];
        sort($pair);
        return $pair[0] . '_' . $pair[1];
    }

    public function getOrCreateConversation(string $a, string $b): string {
        $key = $this->convKey($a, $b);

        $conv = $this->db->conversations->findOne(['key' => $key]);
        if ($conv) return (string)$conv['_id'];

        $doc = [
            '_id' => new ObjectId(),
            'key' => $key,
            'participants' => [$a, $b], 
            'created_at' => new UTCDateTime(),
            'updated_at' => new UTCDateTime(),
            'last_message' => null
        ];
        $this->db->conversations->insertOne($doc);
        return (string)$doc['_id'];
    }

    public function send(string $conversationId, string $senderId, string $text): array {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > 1000) {
            return ['success' => false, 'error' => 'Pesan kosong/terlalu panjang'];
        }

        $msg = [
            '_id' => new ObjectId(),
            'conversation_id' => $conversationId, // string
            'sender_id' => $senderId,             // string
            'text' => $text,
            'created_at' => new UTCDateTime()
        ];

        $this->db->messages->insertOne($msg);

        $this->db->conversations->updateOne(
            ['_id' => new ObjectId($conversationId)],
            ['$set' => ['updated_at' => new UTCDateTime(), 'last_message' => $text]]
        );

        return ['success' => true];
    }

    public function fetch(string $conversationId, int $limit = 200): array {
        $cursor = $this->db->messages->find(
            ['conversation_id' => $conversationId],
            ['sort' => ['created_at' => 1], 'limit' => $limit]
        );

        $out = [];
        foreach ($cursor as $m) {
            $out[] = [
                'id' => (string)$m['_id'],
                'sender_id' => (string)$m['sender_id'],
                'text' => (string)$m['text'],
            ];
        }
        return $out;
    }
}
