<?php
require_once __DIR__ . '/vendor/autoload.php';

session_start();

// Setup the client
$client = new Google_Client();
$client->setClientId('YOUR_CLIENT_ID_HERE');
$client->setClientSecret('YOUR_CLIENT_SECRET_HERE');
$client->setRedirectUri('http://localhost/PetShopProject/googleCallback.php');
$client->addScope('email');
$client->addScope('profile');

// Redirect to Google's OAuth 2.0 server
$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
?>
