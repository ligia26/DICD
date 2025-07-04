<?php
require_once __DIR__ . '/config.php';

try {
    $servername = Config::getRequired('DB_HOST');
    $username = Config::getRequired('DB_USERNAME');
    $password = Config::getRequired('DB_PASSWORD');
    $dbname = Config::getRequired('DB_NAME');

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Configuration error: " . $e->getMessage());
}
?>
