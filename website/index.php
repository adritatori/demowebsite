<?php
/**
 * Home Page with Login Form
 * SECURITY FEATURES IMPLEMENTED:
 * #1 - Input Validation & Sanitization
 * #3 - CSRF Token Protection
 * #4 - Secure Session Management
 */

require_once 'includes/session.php';

start_secure_session();

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Handle error messages
$error_message = '';
$success_message = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $error_message = 'Invalid username or password.';
            break;
        case 'login_required':
            $error_message = 'Please login to access that page.';
            break;
        case 'session_invalid':
            $error_message = 'Your session has expired. Please login again.';
            break;
        case 'csrf_invalid':
            $error_message = 'Security token validation failed. Please try again.';
            break;
        default:
            $error_message = 'An error occurred. Please try again.';
    }
}

if (isset($_GET['timeout'])) {
    $error_message = 'Your session has timed out due to inactivity. Please login again.';
}

if (isset($_GET['logout'])) {
    $success_message = 'You have been logged out successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Secure Login System with Multiple Security Features">
    <title>Secure Login - Home</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🔒 Secure Web Application</h1>
            <p class="subtitle">Demonstrating 5 Security Features</p>
        </header>

        <main>
            <!-- Security Features Info -->
            <div class="info-box">
                <h2>Implemented Security Features:</h2>
                <ul class="security-features">
                    <li>✅ <strong>Input Validation & Sanitization</strong> - Prevents XSS attacks</li>
                    <li>✅ <strong>SQL Injection Protection</strong> - PDO prepared statements</li>
                    <li>✅ <strong>CSRF Token Protection</strong> - Prevents cross-site request forgery</li>
                    <li>✅ <strong>Secure Session Management</strong> - HttpOnly cookies, session timeout</li>
                    <li>✅ <strong>HTTPS/SSL Support</strong> - Encrypted communication (when enabled)</li>
                </ul>
            </div>

            <!-- Login Form -->
            <div class="login-container">
                <h2>Login to Your Account</h2>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <?php echo escape_output($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo escape_output($success_message); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="login-form" id="loginForm">
                    <!-- CSRF Token (Security Feature #3) -->
                    <?php echo csrf_token_field(); ?>

                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            autocomplete="username"
                            placeholder="Enter your username"
                            pattern="[a-zA-Z0-9_]{3,20}"
                            title="Username must be 3-20 characters (letters, numbers, underscore only)"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            minlength="8"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary">Login</button>
                </form>

                <div class="demo-credentials">
                    <h3>Demo Credentials:</h3>
                    <div class="credentials-box">
                        <p><strong>Admin Account:</strong><br>
                        Username: <code>admin</code><br>
                        Password: <code>Password123!</code></p>

                        <p><strong>User Account:</strong><br>
                        Username: <code>testuser</code><br>
                        Password: <code>Password123!</code></p>
                    </div>
                </div>
            </div>

            <!-- Testing Instructions -->
            <div class="testing-box">
                <h2>🔍 Security Testing Instructions</h2>
                <details>
                    <summary>Click to view testing guide</summary>
                    <div class="testing-content">
                        <h3>Test Input Validation (Security Feature #1):</h3>
                        <p>Try entering: <code>&lt;script&gt;alert('XSS')&lt;/script&gt;</code> in the username field</p>
                        <p>Expected: Input is sanitized and harmless</p>

                        <h3>Test SQL Injection (Security Feature #2):</h3>
                        <p>Try username: <code>' OR '1'='1</code></p>
                        <p>Expected: Login fails, SQL injection prevented</p>

                        <h3>Test CSRF Protection (Security Feature #3):</h3>
                        <p>Use browser dev tools to remove the csrf_token field</p>
                        <p>Expected: Form submission is rejected</p>

                        <h3>Test Session Management (Security Feature #4):</h3>
                        <p>Login, then wait 30 minutes without activity</p>
                        <p>Expected: Session times out automatically</p>

                        <h3>Test HTTPS (Security Feature #5):</h3>
                        <p>Access via: <code>https://172.19.12.158/</code></p>
                        <p>Expected: Secure connection with SSL certificate</p>
                    </div>
                </details>
            </div>
        </main>

        <footer>
            <p>&copy; 2024 Secure Web Application | Student Project</p>
            <p class="vm-info">VM IP: <strong>172.19.12.158</strong></p>
        </footer>
    </div>

    <script src="assets/script.js"></script>
</body>
</html>
