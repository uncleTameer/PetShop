<?php
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/dbConnect.php'; // includes vendor/autoload.php
if (session_status() === PHP_SESSION_NONE) session_start();

use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleServiceOauth2;
use MongoDB\BSON\UTCDateTime;

/* --- Config: same source as googleLogin.php --- */
$CLIENT_ID     = getenv('GOOGLE_CLIENT_ID')     ?: '441326189251-dfmo63tji30jgd83mer8vr7b20k8o28e.apps.googleusercontent.com';
$CLIENT_SECRET = getenv('GOOGLE_CLIENT_SECRET') ?: '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';
$REDIRECT_URI  = 'http://localhost/PetShop/php/googleCallback.php';

$client = new GoogleClient();
$client->setClientId($CLIENT_ID);
$client->setClientSecret($CLIENT_SECRET);
$client->setRedirectUri($REDIRECT_URI);
$client->setScopes(['email','profile']);

/* If you STILL hit SSL/cacert issues, uncomment and point to your pem: */
// $client->setHttpClient(new \GuzzleHttp\Client(['verify' => 'C:/wamp64/bin/php/php8.1.32/extras/ssl/cacert.pem']));

$ca = 'C:/wamp64/bin/php/php8.1.32/extras/ssl/cacert.pem';
$client->setHttpClient(new \GuzzleHttp\Client([
    'verify' => $ca,
]));

/* --- CSRF state check (only if we set it) --- */
if (!empty($_SESSION['oauth2state'])) {
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
        $_SESSION['error_message'] = "Invalid OAuth state. Please try again.";
        header('Location: login.php');
        exit;
    }
    unset($_SESSION['oauth2state']); // one-time use
}

/* --- Must have ?code= --- */
if (empty($_GET['code'])) {
    $_SESSION['error_message'] = "❌ No authorization code returned from Google.";
    header('Location: login.php');
    exit;
}

/* --- Exchange code for token --- */
try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
} catch (\Throwable $e) {
    $_SESSION['error_message'] = "❌ Error fetching token: " . $e->getMessage();
    header('Location: login.php');
    exit;
}

if (isset($token['error'])) {
    $_SESSION['error_message'] = "❌ Error fetching token: " . ($token['error_description'] ?? $token['error']);
    header('Location: login.php');
    exit;
}

$client->setAccessToken($token['access_token']);

/* --- Fetch profile --- */
$oauth  = new GoogleServiceOauth2($client);
$gUser  = $oauth->userinfo->get();   // id, email, name, picture, verifiedEmail

$email    = strtolower(trim((string)$gUser->email));
$name     = trim((string)$gUser->name);
$gid      = (string)$gUser->id;
$gpic     = (string)($gUser->picture ?? '');
$verified = (bool)($gUser->verifiedEmail ?? true);

if ($email === '') {
    $_SESSION['error_message'] = "❌ Google did not return an email for this account.";
    header('Location: login.php');
    exit;
}

/* --- Find or create user --- */
$existing = $db->users->findOne(
    ['email' => $email],
    ['projection' => ['_id'=>1,'role'=>1,'preferences'=>1,'profilePicture'=>1,'oauth'=>1]]
);

$nowUtc = new UTCDateTime();
$ip     = $_SERVER['REMOTE_ADDR'] ?? null;
$ua     = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);

if (!$existing) {
    $role = ($email === 'admin@admin.com') ? 'admin' : 'user';
    $ins = $db->users->insertOne([
        'fullName'     => $name ?: $email,
        'email'        => $email,
        'password'     => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT), // placeholder
        'role'         => $role,
        'createdBy'    => 'google',
        'oauth'        => ['googleId' => $gid, 'verifiedEmail' => $verified],
        'googleAvatar' => $gpic ?: null,
        'createdAt'    => $nowUtc,
        'createdIp'    => $ip,
        'createdUA'    => $ua,
        'lastLoginAt'  => $nowUtc,
        'lastLoginIp'  => $ip,
        'lastLoginUA'  => $ua,
    ]);
    $userId     = $ins->getInsertedId();
    $prefs      = [];
    $profilePic = null;
} else {
    $db->users->updateOne(
        ['_id' => $existing->_id],
        ['$set' => [
            'lastLoginAt' => $nowUtc,
            'lastLoginIp' => $ip,
            'lastLoginUA' => $ua,
            'oauth.googleId' => $gid,
            'oauth.verifiedEmail' => $verified,
            'googleAvatar' => $gpic ?: ($existing->googleAvatar ?? null)
        ]]
    );

    $userId     = $existing->_id;
    $role       = $existing->role ?? 'user';
    $prefs      = $existing->preferences ?? [];
    $profilePic = $existing->profilePicture ?? null;
}

/* --- Session --- */
$_SESSION['user'] = [
    'id'             => (string)$userId,
    'name'           => $name ?: $email,
    'email'          => $email,
    'role'           => $role,
    'profilePicture' => $profilePic
];
if (!empty($prefs['defaultCategoryId'])) {
    $_SESSION['preferences']['defaultCategoryId'] = (string)$prefs['defaultCategoryId'];
}
$_SESSION['csrf'] = bin2hex(random_bytes(32));
$_SESSION['success_message'] = "🎉 Welcome, " . htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') . "!";

/* --- Redirect (prefer ?next from login step) --- */
$dest = $_SESSION['post_login_redirect'] ?? null;
unset($_SESSION['post_login_redirect']);

if ($dest) {
    header('Location: ' . $dest);
} elseif ($role === 'admin') {
    header('Location: ../admin/dashboard.php');
} else {
    header('Location: index.php'); // /php/index.php
}
exit;

ob_end_flush();
