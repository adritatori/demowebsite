<?php
/**
 * Login Handler
 * SECURITY FEATURES IMPLEMENTED:
 * #1 - Input Validation & Sanitization
 * #2 - SQL Injection Protection (PDO prepared statements)
 * #3 - CSRF Token Protection
 * #4 - Secure Session Management
 */

require_once 'includes/db.php';
require_once 'includes/session.php';

start_secure_session();

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// SECURITY FEATURE #3: Validate CSRF token
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    log_security_event('CSRF token validation failed for login attempt', 'WARNING');
    header('Location: index.php?error=csrf_invalid');
    exit;
}

// SECURITY FEATURE #1: Input Validation & Sanitization
$username = sanitize_input($_POST['username'] ?? '');
$password = $_POST['password'] ?? ''; // Don't sanitize password (may contain special chars)

// Validate inputs
if (empty($username) || empty($password)) {
    header('Location: index.php?error=invalid_credentials');
    exit;
}

// Additional validation
if (!validate_username($username)) {
    log_security_event("Invalid username format attempted: $username", 'WARNING');
    header('Location: index.php?error=invalid_credentials');
    exit;
}

try {
    // SECURITY FEATURE #2: SQL Injection Protection using PDO prepared statements
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Valid credentials - create session
        login_user($user['id'], $user['username'], $user['role']);

        // Update last login time
        $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update_stmt->execute([$user['id']]);

        // Log to activity table
        $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (?, ?, ?)");
        $log_stmt->execute([$user['id'], 'login', get_client_ip()]);

        // Redirect to dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        // Invalid credentials
        log_security_event("Failed login attempt for username: $username from IP: " . get_client_ip(), 'WARNING');

        // Sleep to prevent brute force attacks
        sleep(2);

        header('Location: index.php?error=invalid_credentials');
        exit;
    }
} catch (PDOException $e) {
    // Log database errors
    log_security_event("Database error during login: " . $e->getMessage(), 'ERROR');
    error_log("Login DB Error: " . $e->getMessage());

    header('Location: index.php?error=system');
    exit;
}
?>
