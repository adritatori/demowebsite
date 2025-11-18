<?php
/**
 * Secure Logout Handler
 *
 * Properly terminates user session and cleans up
 */

define('SECURE_ACCESS', true);
require_once 'config.php';

// Mark session as inactive in database if exists
if (isset($_SESSION['user_id']) && isset($_SESSION['db_session_token'])) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = FALSE WHERE session_token = :token");
        $stmt->bindParam(':token', $_SESSION['db_session_token'], PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Logout Error: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Start a new session for the success message
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';

// Redirect to login page
header('Location: index.php');
exit();
?>
