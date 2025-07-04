<?php
header('Content-Type: application/json');

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
    die(json_encode(['error' => 'Configuration error: ' . $e->getMessage()]));
}

$servername = $_GET['servername'];
$query = "SELECT timestamp, cpu_usage_percent, ram_usage_percent 
          FROM server_stats 
          WHERE servername = ? AND timestamp >= NOW() - INTERVAL 7 DAY
          ORDER BY timestamp";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $servername);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($data);
?>
