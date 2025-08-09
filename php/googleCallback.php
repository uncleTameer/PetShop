<?php
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/dbConnect.php'; // Connect to your MongoDB Atlas

session_start();

// Setup Google client
$client = new Google_Client();
$client->setClientId('441326189251-dfmo63tji30jgd83mer8vr7b20k8o28e.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/PetShop/php/googleCallback.php');
$client->addScope('email');
$client->addScope('profile');

// Handling the code returned from Google
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $oauth = new Google_Service_Oauth2($client);
        $googleUser = $oauth->userinfo->get();

        $email = $googleUser->email;
        $name = $googleUser->name;

        // Try to find the user
        $existingUser = $db->users->findOne(['email' => $email]);

        if (!$existingUser) {
            // If user not found, create one
            $insertResult = $db->users->insertOne([
                'fullName' => $name,
                'email' => $email,
                'password' => password_hash(uniqid(), PASSWORD_DEFAULT),
                'createdBy' => 'google',
                'createdAt' => new MongoDB\BSON\UTCDateTime()
            ]);

            // Fetch the newly created user
            $userId = $insertResult->getInsertedId();
        } else {
            // Use existing user ID
            $userId = $existingUser->_id;
        }

        // Set full session
        $_SESSION['user'] = [
            'id' => (string) $userId,
            'email' => $email,
            'name' => $name,
            'isAdmin' => $email === 'admin@admin.com'
        ];

        // Redirect to appropriate page
        header('Location: ' . ($_SESSION['user']['isAdmin'] ? 'admin/dashboard.php' : 'index.php'));
        exit;
    } else {
        echo "❌ Error fetching token: " . htmlspecialchars($token['error_description']);
    }
} else {
    echo "❌ No authorization code returned from Google.";
}

ob_end_flush();
?>
