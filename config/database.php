<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Database;

final class DatabaseConn {
    private static ?DatabaseConn $instance = null;
    private Client $client;
    private Database $db;

    private function __construct() {
        $this->client = new Client("mongodb://127.0.0.1:27017");
        $this->db = $this->client->selectDatabase("instapost");
    }

    public static function getInstance(): DatabaseConn {
        if (self::$instance === null) self::$instance = new DatabaseConn();
        return self::$instance;
    }

    public function db(): Database {
        return $this->db;
    }
}

function getDB(): Database {
    return DatabaseConn::getInstance()->db();
}
