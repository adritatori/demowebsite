<?php
/**
 * Secure Login Page
 *
 * This page demonstrates all 5 security features:
 * 1. Input Validation & Sanitization
 * 2. SQL Injection Protection (PDO)
 * 3. CSRF Token Protection
 * 4. Secure Session Management
 * 5. HTTPS/SSL Support
 */

define('SECURE_ACCESS', true);
require_once 'config.php';

// Redirect if already logged in
if (checkUserSession()) {
    header('Location: dashboard.php');
    exit();
}

// Generate CSRF token for the form
$csrf_token = generateCSRFToken();

// Check for error/success messages
$error_message = $_SESSION['error'] ?? '';
$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Secure Login - Security Demo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            width: 100%;
        }

        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .card-body {
            padding: 40px;
        }

        .security-features {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .security-features h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .security-features ul {
            list-style: none;
            padding: 0;
        }

        .security-features li {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
        }

        .security-features li:last-child {
            border-bottom: none;
        }

        .security-features li::before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
            font-size: 1.2em;
        }

        .login-form {
            max-width: 400px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .demo-credentials {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }

        .demo-credentials strong {
            color: #856404;
        }

        .ssl-indicator {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background: <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? '#d4edda' : '#fff3cd'; ?>;
            border-radius: 5px;
            font-size: 0.9em;
        }

        .ssl-indicator::before {
            content: "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? '🔒' : '⚠️'; ?>";
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="card-header">
                <h1>🔒 Secure Website Demo</h1>
                <p>Login Page with Advanced Security Features</p>
            </div>

            <div class="card-body">
                <!-- Security Features List -->
                <div class="security-features">
                    <h2>🛡️ Security Features Implemented</h2>
                    <ul>
                        <li><strong>Input Validation & Sanitization</strong> - All user inputs are validated and sanitized</li>
                        <li><strong>SQL Injection Protection</strong> - PDO with prepared statements</li>
                        <li><strong>CSRF Token Protection</strong> - Token-based form validation</li>
                        <li><strong>Secure Session Management</strong> - HTTPOnly, Secure, SameSite cookies</li>
                        <li><strong>HTTPS/SSL Support</strong> - Encrypted communication ready</li>
                    </ul>
                </div>

                <!-- Login Form -->
                <div class="login-form">
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" autocomplete="off">
                        <!-- CSRF Token (Hidden) -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required
                                   placeholder="Enter your username"
                                   pattern="[a-zA-Z0-9_]{3,50}"
                                   title="3-50 characters, alphanumeric and underscore only">
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required
                                   placeholder="Enter your password"
                                   minlength="8">
                        </div>

                        <button type="submit" class="btn-login">Login Securely</button>
                    </form>

                    <div class="demo-credentials">
                        <strong>Demo Credentials:</strong><br>
                        Username: <code>demo</code><br>
                        Password: <code>DemoPass123!</code>
                    </div>

                    <div class="ssl-indicator">
                        <?php if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'): ?>
                            <strong>HTTPS Enabled</strong> - Your connection is secure
                        <?php else: ?>
                            <strong>HTTPS Not Detected</strong> - Consider enabling SSL for production
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
