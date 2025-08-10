<?php
require_once __DIR__ . '/dbConnect.php'; // This already pulls in ..//vendor/autoload.php

ob_start(); // Start output buffering
error_reporting(E_ALL & ~E_DEPRECATED); // Hide deprecation warnings

// NOTE: Autoloader already loaded via dbConnect.php. If that ever changes, uncomment below.
// require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Setup the Google Client
$client = new Google_Client();
$client->setClientId('441326189251-dfmo63tji30jgd83mer8vr7b20k8o28e.apps.googleusercontent.com'); // TODO: move to env var
$client->setClientSecret('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'); // TODO: move to env var / secret manager
$client->setRedirectUri('http://localhost/PetShop/php/googleCallback.php'); // TODO: derive dynamically / config
$client->addScope('email');
$client->addScope('profile');

// Redirect to Google's OAuth 2.0 server
$authUrl = $client->createAuthUrl();
// Optional: add &state for CSRF protection
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;

ob_end_flush(); // End output buffering
?>
