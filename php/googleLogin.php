<?php
require_once __DIR__ . '/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

use Google\Client as GoogleClient;

/* --- Config: use env when present, fall back to your local dev keys --- */
$CLIENT_ID     = getenv('GOOGLE_CLIENT_ID')     ?: '441326189251-dfmo63tji30jgd83mer8vr7b20k8o28e.apps.googleusercontent.com';
$CLIENT_SECRET = getenv('GOOGLE_CLIENT_SECRET') ?: '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';
$REDIRECT_URI  = 'http://localhost/PetShop/php/googleCallback.php'; // HTTPS in prod

/* --- Google OAuth client --- */
$client = new GoogleClient();
$client->setClientId($CLIENT_ID);
$client->setClientSecret($CLIENT_SECRET);
$client->setRedirectUri($REDIRECT_URI);
$client->setScopes(['email', 'profile']);
$client->setPrompt('select_account'); // optional: always show account picker

$ca = 'C:/wamp64/bin/php/php8.1.32/extras/ssl/cacert.pem';
$client->setHttpClient(new \GuzzleHttp\Client([
    'verify' => $ca,
]));


/* --- CSRF state --- */
$_SESSION['oauth2state'] = bin2hex(random_bytes(16));
$client->setState($_SESSION['oauth2state']);

/* --- Optional safe post-login redirect (?next=/php/cart.php) --- */
if (isset($_GET['next']) && is_string($_GET['next'])) {
    $next = $_GET['next'];
    if (preg_match('~^/[A-Za-z0-9/_\-.]*$~', $next)) {
        $_SESSION['post_login_redirect'] = $next;
    }
}

/* --- Kick to Google --- */
$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
