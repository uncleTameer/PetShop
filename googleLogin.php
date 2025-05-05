<?php
ob_start(); // Start output buffering
error_reporting(E_ALL & ~E_DEPRECATED); // Hide deprecation warnings

// 1. Always load autoload.php FIRST
require_once __DIR__ . '/vendor/autoload.php';

// 2. THEN start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. THEN load your DB connection
require_once __DIR__ . '/php/dbConnect.php';

// 4. NOW you can safely use Google Client
$client = new Google_Client();
$client->setClientId('441326189251-dfmo63tji30jgd83mer8vr7b20k8o28e.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/PetShop/googleCallback.php');
$client->addScope('email');
$client->addScope('profile');

// 5. Redirect to Google OAuth
$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;

ob_end_flush(); // End output buffering
?>
