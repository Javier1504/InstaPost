<?php
require_once 'vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    
    echo "<h2>MongoDB Connection Test</h2>";
    echo "Connection successful!<br>";
 
    $databases = $client->listDatabases();
    echo "<h3>Databases:</h3>";
    foreach ($databases as $database) {
        echo "- " . $database->getName() . "<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo $e->getMessage();
}
?>