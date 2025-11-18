<?php
/**
 * Logout Handler
 * SECURITY FEATURE #4: Secure Session Management
 */

require_once 'includes/session.php';

start_secure_session();

// Logout user and destroy session
logout_user();

// Redirect to home page
header('Location: index.php?logout=1');
exit;
?>
