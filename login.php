<?php
/**
 * Secure Authentication Handler
 *
 * Demonstrates all 5 security features:
 * 1. Input Validation & Sanitization
 * 2. SQL Injection Protection (PDO with prepared statements)
 * 3. CSRF Token Protection
 * 4. Secure Session Management
 * 5. HTTPS/SSL Support
 */

define('SECURE_ACCESS', true);
require_once 'config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Initialize response
$error = '';
$success = false;

try {
    // SECURITY FEATURE 3: CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token. Please try again.');
    }

    // SECURITY FEATURE 1: Input Validation & Sanitization
    if (empty($_POST['username']) || empty($_POST['password'])) {
        throw new Exception('Username and password are required.');
    }

    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password, will be hashed

    // Validate username format
    if (!validateUsername($username)) {
        throw new Exception('Invalid username format. Use 3-50 alphanumeric characters or underscore.');
    }

    // Password length check
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long.');
    }

    // SECURITY FEATURE 2: SQL Injection Protection using PDO
    $pdo = getDatabaseConnection();

    // Prepared statement prevents SQL injection
    $stmt = $pdo->prepare("SELECT id, username, password, email FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch();

    // Verify user exists and password is correct
    if (!$user || !password_verify($password, $user['password'])) {
        // Generic error message to prevent user enumeration
        throw new Exception('Invalid username or password.');
    }

    // SECURITY FEATURE 4: Secure Session Management
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Update last login time
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :user_id");
    $updateStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
    $updateStmt->execute();

    // Optional: Store session in database for additional security
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

    $sessionStmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
        VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at)
    ");

    $sessionStmt->execute([
        ':user_id' => $user['id'],
        ':session_token' => $sessionToken,
        ':ip_address' => $_SERVER['REMOTE_ADDR'],
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        ':expires_at' => $expiresAt
    ]);

    $_SESSION['db_session_token'] = $sessionToken;

    // Successful login
    $success = true;

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Login Error: " . $e->getMessage()); // Log for debugging
}

// Redirect based on result
if ($success) {
    $_SESSION['success'] = 'Login successful! Welcome back, ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '!';
    header('Location: dashboard.php');
    exit();
} else {
    $_SESSION['error'] = $error;
    header('Location: index.php');
    exit();
}
?>
