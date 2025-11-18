<?php
/**
 * Session Management
 * SECURITY FEATURE #4: Secure Session Management
 */

// Prevent direct access
if (!defined('SESSION_INITIALIZED')) {
    define('SESSION_INITIALIZED', true);
}

/**
 * Configure secure session settings
 */
function configure_session() {
    // Session configuration for security
    ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1);     // Only use cookies, not URL parameters
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection

    // For HTTPS (will be enabled after SSL setup)
    // Uncomment this line after enabling HTTPS:
    // ini_set('session.cookie_secure', 1);      // Only send cookie over HTTPS

    // Session timeout: 30 minutes
    ini_set('session.gc_maxlifetime', 1800);
    ini_set('session.cookie_lifetime', 1800);

    // Use strong session ID
    ini_set('session.sid_length', 48);
    ini_set('session.sid_bits_per_character', 6);
}

/**
 * Start secure session
 */
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        configure_session();
        session_start();

        // Check for session timeout (30 minutes of inactivity)
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
            session_destroy_secure();
            header('Location: index.php?timeout=1');
            exit;
        }

        // Update last activity time
        $_SESSION['LAST_ACTIVITY'] = time();

        // Validate session integrity
        if (!validate_session()) {
            session_destroy_secure();
            header('Location: index.php?error=session_invalid');
            exit;
        }

        // Regenerate session ID periodically (every 5 minutes)
        if (!isset($_SESSION['CREATED'])) {
            $_SESSION['CREATED'] = time();
        } else if (time() - $_SESSION['CREATED'] > 300) {
            session_regenerate_id(true);
            $_SESSION['CREATED'] = time();
        }
    }
}

/**
 * Validate session integrity
 */
function validate_session() {
    // First time session is created
    if (!isset($_SESSION['USER_AGENT']) || !isset($_SESSION['IP_ADDRESS'])) {
        $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $_SESSION['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        return true;
    }

    // Validate user agent (basic fingerprinting)
    if ($_SESSION['USER_AGENT'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN')) {
        log_security_event('Session hijacking attempt detected: User agent mismatch', 'WARNING');
        return false;
    }

    // Validate IP address (optional - may cause issues with dynamic IPs)
    // Uncomment if you want strict IP validation:
    // if ($_SESSION['IP_ADDRESS'] !== ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN')) {
    //     return false;
    // }

    return true;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    start_secure_session();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Require login (redirect if not logged in)
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php?error=login_required');
        exit;
    }
}

/**
 * Login user (create session)
 */
function login_user($user_id, $username, $role = 'user') {
    start_secure_session();

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['login_time'] = time();
    $_SESSION['CREATED'] = time();

    // Log login
    log_security_event("User login successful: $username (ID: $user_id)", 'INFO');
}

/**
 * Logout user (destroy session)
 */
function logout_user() {
    start_secure_session();

    $username = $_SESSION['username'] ?? 'unknown';

    // Log logout
    log_security_event("User logout: $username", 'INFO');

    // Destroy session
    session_destroy_secure();
}

/**
 * Securely destroy session
 */
function session_destroy_secure() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Unset all session variables
        $_SESSION = array();

        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        // Destroy session
        session_destroy();
    }
}

/**
 * Check if user has admin role
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 */
function get_current_username() {
    return $_SESSION['username'] ?? 'Guest';
}

// Include security functions
require_once __DIR__ . '/security.php';
?>
