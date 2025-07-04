<?php
require_once 'includes/config.php';

try {
    $host = Config::getRequired('DB_HOST');
    $dbUsername = Config::getRequired('DB_USERNAME');
    $dbPassword = Config::getRequired('DB_PASSWORD');
    $dbname = Config::getRequired('DB_NAME');

    $conn = new mysqli($host, $dbUsername, $dbPassword, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Configuration error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    $stmt = $conn->prepare("SELECT email FROM users WHERE reset_token = ? AND reset_expires_at > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE email = ?");
        $stmt->bind_param('ss', $hashed_password, $email);
        if ($stmt->execute()) {
            echo "Password has been reset successfully.";
        } else {
            echo "Failed to reset password.";
        }
    } else {
        echo "Invalid or expired token.";
    }
    $stmt->close();
}

$conn->close();
?>
