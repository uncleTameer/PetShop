<?php
require_once __DIR__ . '/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setClientId('YOUR_CLIENT_ID_HERE');
$client->setClientSecret('YOUR_CLIENT_SECRET_HERE');
$client->setRedirectUri('http://localhost/PetShopProject/googleCallback.php');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        // Get user profile info
        $oauth = new Google_Service_Oauth2($client);
        $google_account_info = $oauth->userinfo->get();

        // Extract email and name
        $email = $google_account_info->email;
        $name = $google_account_info->name;

        // TODO: Check if user exists in DB, if not insert. Then start session
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;

        // Redirect to homepage or dashboard
        header('Location: index.php');
        exit;
    } else {
        echo "Error retrieving token: " . $token['error_description'];
    }
} else {
    echo "No code returned from Google.";
}
?>
