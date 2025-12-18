<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$db = getDB();

try {
    $db->users->createIndex(['username' => 1], ['unique' => true]);
    $db->users->createIndex(['email' => 1], ['unique' => true]);
    $db->posts->createIndex(['user_id' => 1]);
    $db->posts->createIndex(['created_at' => -1]);
    $db->conversations->createIndex(['key' => 1], ['unique' => true]);
    $db->messages->createIndex(['conversation_id' => 1]);
    echo "OK: indexes created.";
} catch (Throwable $t) {
    echo "Error: " . $t->getMessage();
}
