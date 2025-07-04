<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'includes/config.php';

session_start();

try {
    $client = new Google_Client();
    $client->setClientId(Config::getRequired('GOOGLE_CLIENT_ID'));
    $client->setClientSecret(Config::getRequired('GOOGLE_CLIENT_SECRET'));
    $client->setRedirectUri(Config::getRequired('GOOGLE_REDIRECT_URI'));
    $client->addScope("email");
    $client->addScope("profile");

    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} catch (Exception $e) {
    die("Configuration error: " . $e->getMessage());
}
?>
