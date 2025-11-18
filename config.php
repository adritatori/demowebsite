<?php
/**
 * Secure Configuration File
 *
 * Security Features Implemented:
 * - PDO for SQL Injection Protection
 * - Secure session configuration
 * - CSRF token generation
 * - Environment-based configuration
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

// Database Configuration (from lamp_lab.sh)
define('DB_HOST', 'localhost');
define('DB_NAME', 'studentdb');      // Using database from lamp_lab.sh
define('DB_USER', 'student');        // Using user from lamp_lab.sh
define('DB_PASS', 'Password123!');   // Using password from lamp_lab.sh
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour in seconds

// PDO Database Connection with SQL Injection Protection
function getDatabaseConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,  // Real prepared statements
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error securely, don't expose to user
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }

    return $pdo;
}

// Secure Session Configuration
function initSecureSession() {
    // Prevent session fixation
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // HTTPS only
            'httponly' => true,  // Prevent JavaScript access
            'samesite' => 'Strict'  // CSRF protection
        ]);

        session_name('SECURE_SESSION');
        session_start();

        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// CSRF Token Generation and Validation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token']) ||
        !isset($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    // Check token expiry
    if (isset($_SESSION['csrf_token_time']) &&
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        return false;
    }

    // Timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Input Sanitization Functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateUsername($username) {
    // Only alphanumeric and underscore, 3-50 characters
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

// Session Security Check
function checkUserSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        return false;
    }

    // Verify IP address hasn't changed (optional, can be too strict)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        // IP changed, possible session hijacking
        session_destroy();
        return false;
    }

    return true;
}

// Initialize secure session
initSecureSession();
?>
