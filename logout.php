<?php
session_start();
require_once 'includes/config.php';

// Clear the login_token cookie
if (isset($_COOKIE['login_token'])) {
    // Clear the token from the database
    try {
        $host = Config::getRequired('DB_HOST');
        $dbUsername = Config::getRequired('DB_USERNAME');
        $dbPassword = Config::getRequired('DB_PASSWORD');
        $dbname = Config::getRequired('DB_NAME');
        
        // Create connection
        $conn = new mysqli($host, $dbUsername, $dbPassword, $dbname);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    } catch (Exception $e) {
        die("Configuration error: " . $e->getMessage());
    }

    // Assuming user_id is stored in session
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE users SET login_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }

    // Clear the cookie
    setcookie('login_token', '', time() - 3600, "/"); // Expire the cookie by setting it to a time in the past
}

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
