<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

// Logger function
function log_message($message) {
    $logfile = 'log.txt';
    $current_time = date('Y-m-d H:i:s');
    file_put_contents($logfile, "$current_time - $message\n", FILE_APPEND);
}

log_message('Script started');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    log_message('Authorization code received: ' . $_GET['code']);

    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            log_message('Error fetching access token: ' . $token['error_description']);
            die('Error fetching access token: ' . $token['error_description']);
        }
        $client->setAccessToken($token['access_token']);
        log_message('Access token set: ' . json_encode($token));

        // Get user profile information
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        log_message("User info retrieved: $email, $name");

        // Database connection
        $conn = new mysqli('localhost', 'root', '0955321170', 'dashboard');

        if ($conn->connect_error) {
            log_message('Database connection failed: ' . $conn->connect_error);
            die("Connection failed: " . $conn->connect_error);
        }
        log_message('Database connected');

        // Check if user exists
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists, log them in
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['admin']; // Assuming the 'role' column exists in the 'users' table

            log_message("User logged in: " . $user['id']);
        } else {
            // User doesn't exist, insert them with a default password
            $default_password = ''; // Set a default password value if needed
            $sql = "INSERT INTO users (email, name, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $email, $name, $default_password);
            if ($stmt->execute()) {
                $insert_id = $stmt->insert_id;
                log_message("New user inserted with ID: $insert_id");

                $_SESSION['user_id'] = $insert_id;
                $_SESSION['user_email'] = $email;

            } else {
                log_message("Error inserting new user: " . $stmt->error);
                die("Error inserting new user: " . $stmt->error);
            }
        }

        // Debugging information
        if (!isset($_SESSION['user_id'])) {
            log_message('Session user_id not set');
            die('Session user_id not set');
        }
        if (!isset($_SESSION['user_email'])) {
            log_message('Session user_email not set');
            die('Session user_email not set');
        }

        log_message('Session variables set. Redirecting to index.php');
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        log_message('Exception: ' . $e->getMessage());
        die('Exception: ' . $e->getMessage());
    }
} else {
    // Redirect to Google's OAuth 2.0 server
    $auth_url = $client->createAuthUrl();
    log_message('Redirecting to Google for authentication: ' . $auth_url);
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit();
}
?>
