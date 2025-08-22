<?php
/**
 * Session Configuration and Initialization
 * This file must be included BEFORE calling session_start() anywhere in the application
 */

// Configure session settings BEFORE starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Set session timeout (30 minutes)
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 1800);

// Set session name
ini_set('session.name', 'PHPSESSID');

// Enable session garbage collection
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

/**
 * Initialize session with security settings
 * Call this function instead of session_start() directly
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Set session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Secure session cleanup
 */
function secureSessionCleanup() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Clear sensitive data
        unset($_SESSION['temp_data']);
        unset($_SESSION['form_data']);
        
        // Keep only essential session data
        $essential_keys = ['user', 'csrf_token', 'last_activity', 'last_regeneration'];
        foreach ($_SESSION as $key => $value) {
            if (!in_array($key, $essential_keys)) {
                unset($_SESSION[$key]);
            }
        }
    }
}
?>
