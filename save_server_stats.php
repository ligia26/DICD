<?php
require_once 'includes/config.php';

try {
    $servername = Config::getRequired('DB_HOST');
    $username = Config::getRequired('DB_USERNAME');
    $password = Config::getRequired('DB_PASSWORD');
    $dbname = Config::getRequired('DB_NAME_REPORTS');

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Configuration error: " . $e->getMessage());
}

$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['servername'], $data['cpu_usage_percent'], $data['ram_usage']['usage_percent'])) {
    $servername = $data['servername'];
    $cpu_usage_percent = $data['cpu_usage_percent'];
    $ram_usage_percent = $data['ram_usage']['usage_percent'];

    // Insert new data
    $stmt = $conn->prepare("INSERT INTO server_stats (servername, cpu_usage_percent, ram_usage_percent) VALUES (?, ?, ?)");
    $stmt->bind_param("sdd", $servername, $cpu_usage_percent, $ram_usage_percent);
    $stmt->execute();
    $stmt->close();

    // Delete data older than 7 days
    $stmt = $conn->prepare("DELETE FROM server_stats WHERE timestamp < NOW() - INTERVAL 7 DAY");
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>
