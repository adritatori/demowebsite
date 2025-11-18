<?php
/**
 * Security Helper Functions
 * Implements multiple security features:
 * - Input validation and sanitization (Feature #1)
 * - CSRF token management (Feature #3)
 */

/**
 * SECURITY FEATURE #1: Input Validation and Sanitization
 */

/**
 * Sanitize string input
 * Removes HTML tags and special characters
 */
function sanitize_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
        return $data;
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 */
function validate_email($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate username (alphanumeric, underscore, 3-20 chars)
 */
function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

/**
 * Validate password strength
 * At least 8 characters, 1 uppercase, 1 lowercase, 1 number
 */
function validate_password($password) {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/**
 * Escape output for safe HTML display
 */
function escape_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * SECURITY FEATURE #3: CSRF Token Protection
 */

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token']) || !isset($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token HTML input field
 */
function csrf_token_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Additional Security Functions
 */

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'UNKNOWN';
}

/**
 * Log security events
 */
function log_security_event($message, $level = 'INFO') {
    $log_file = '/var/log/security_app.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = get_client_ip();
    $log_message = "[$timestamp] [$level] [IP: $ip] $message" . PHP_EOL;

    // In production, ensure proper file permissions
    @error_log($log_message, 3, $log_file);
}

/**
 * Prevent XSS in JSON responses
 */
function json_response($data, $status = 200) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    http_response_code($status);
    echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}
?>
