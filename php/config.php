<?php
// PetShop Enhanced Configuration File

// Database Configuration
define('DB_NAME', 'PetShopProject');
define('DB_HOST', 'mongodb+srv://TandJ:HrsNdCml@cluster0.mjqwdkf.mongodb.net/');

// Security Configuration
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds
define('SESSION_TIMEOUT', 3600); // 1 hour
define('GUEST_CART_EXPIRY', 1800); // 30 minutes for guest carts

// File Upload Configuration
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
define('UPLOAD_PATH', realpath(__DIR__ . '/../uploads'));
define('UPLOAD_WEB_PATH', '../uploads');

// Loyalty Points Configuration
define('LOYALTY_POINTS_PERCENTAGE', 10); // 10% of order value
define('MIN_LOYALTY_POINTS_REDEMPTION', 100); // Minimum points needed to redeem

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@petshop.com');
define('FROM_NAME', 'PetShop');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'your-google-client-id');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
define('GOOGLE_REDIRECT_URI', 'http://localhost/PetShop/php/googleCallback.php');

// Default Settings
define('DEFAULT_LOW_STOCK_THRESHOLD', 5);
define('DEFAULT_COUNTRY', 'United States');
define('DEFAULT_CURRENCY', 'USD');

// Pagination
define('ITEMS_PER_PAGE', 12);
define('REVIEWS_PER_PAGE', 5);
define('QA_PER_PAGE', 10);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 300); // 5 minutes

// Notification Settings
define('EMAIL_NOTIFICATIONS_ENABLED', true);
define('PUSH_NOTIFICATIONS_ENABLED', false);

// Error Reporting (set to false in production)
define('DEBUG_MODE', true);

// Timezone
date_default_timezone_set('UTC');

// Helper Functions
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function formatPrice($price, $currency = DEFAULT_CURRENCY) {
    return $currency . ' ' . number_format($price, 2);
}

function formatDate($date) {
    if (is_object($date) && method_exists($date, 'toDateTime')) {
        return $date->toDateTime()->format('M j, Y g:i A');
    }
    return date('M j, Y g:i A', strtotime($date));
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Error Handler
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session Security - Configured in dbConnect.php before session_start()

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateSecureToken();
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCSRFToken() {
    return $_SESSION['csrf_token'];
}
?>
