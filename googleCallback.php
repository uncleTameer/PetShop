
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/php/dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Create Google Client
    $googleClient = new Google_Client();
    $googleClient->setClientId('441326189251-dfmo63tji30jgd83mer8vr7b20k8o28e.apps.googleusercontent.com');
    $googleClient->setClientSecret('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');
    $googleClient->setRedirectUri('http://localhost/PetShop/googleCallback.php');
    $googleClient->addScope('email');
    $googleClient->addScope('profile');

    if (!isset($_GET['code'])) {
        header('Location: php/login.php?error=google_login');
        exit;
    }

    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception('Error fetching access token: ' . $token['error_description']);
    }

    $googleClient->setAccessToken($token);

    $oauth2 = new Google_Service_Oauth2($googleClient);
    $userInfo = $oauth2->userinfo->get();

    $email = $userInfo->email ?? '';
    $fullName = $userInfo->name ?? '';

    if (empty($email)) {
        throw new Exception('Google account missing email.');
    }

    // MongoDB client
    $mongoClient = new MongoDB\Client;
    $collection = $mongoClient->PetShopProject->users;
    $existingUser = $collection->findOne(['email' => $email]);
if (!$existingUser) {
    $insertResult = $collection->insertOne([
        'fullName' => $fullName,
        'email' => $email,
        'password' => '',
        'authProvider' => 'google',
        'createdAt' => new MongoDB\BSON\UTCDateTime()
    ]);
    $existingUser = $collection->findOne(['_id' => $insertResult->getInsertedId()]);
}
$_SESSION['user'] = [
    'id' => (string)$existingUser['_id'], // 🔥 this is what myOrders.php needs
    'name' => $existingUser['fullName'],
    'email' => $existingUser['email'],
    'isAdmin' => $existingUser['isAdmin'] ?? false
];

    // Redirect depending on role
    if ($_SESSION['user']['isAdmin']) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;

} catch (Exception $e) {
    error_log('Google Login Error: ' . $e->getMessage());
    header('Location: php/login.php?error=google_login');
    exit;
}
?>
