<?php
/**
 * Protected Dashboard Page
 *
 * Demonstrates secure session management and access control
 */

define('SECURE_ACCESS', true);
require_once 'config.php';

// Check if user is logged in
if (!checkUserSession()) {
    $_SESSION['error'] = 'Please login to access this page.';
    header('Location: index.php');
    exit();
}

// Get user information
$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
$user_id = $_SESSION['user_id'];
$login_time = $_SESSION['login_time'] ?? time();
$session_duration = time() - $login_time;

// Get additional user info from database
$pdo = getDatabaseConnection();
$stmt = $pdo->prepare("SELECT email, created_at, last_login FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user_info = $stmt->fetch();

// Generate CSRF token for any forms
$csrf_token = generateCSRFToken();

// Calculate session expiry
$session_remaining = SESSION_LIFETIME - $session_duration;
$minutes_remaining = floor($session_remaining / 60);

// Check for success message
$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Secure Website</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .dashboard-header h1 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #666;
            font-size: 1.1em;
        }

        .logout-btn {
            padding: 12px 30px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s, transform 0.2s;
            display: inline-block;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
        }

        .card h2::before {
            content: "📊";
            margin-right: 10px;
            font-size: 1.2em;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .security-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .security-card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .security-card h2::before {
            content: "🛡️";
            margin-right: 10px;
        }

        .security-feature {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }

        .security-feature:last-child {
            margin-bottom: 0;
        }

        .security-feature h3 {
            color: #28a745;
            margin-bottom: 8px;
            font-size: 1.1em;
        }

        .security-feature p {
            color: #666;
            line-height: 1.6;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #28a745;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .session-warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border: 1px solid #ffeaa7;
        }

        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
            }

            .logout-btn {
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-header">
            <div>
                <h1>🎯 Secure Dashboard</h1>
                <p>Welcome back, <strong><?php echo $username; ?></strong>!</p>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div class="dashboard-grid">
            <!-- User Information Card -->
            <div class="card">
                <h2>User Information</h2>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo $username; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">User ID:</span>
                    <span class="info-value">#<?php echo $user_id; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Created:</span>
                    <span class="info-value"><?php echo date('M d, Y', strtotime($user_info['created_at'])); ?></span>
                </div>
            </div>

            <!-- Session Information Card -->
            <div class="card">
                <h2>Session Information</h2>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-indicator"></span>Active
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Login Time:</span>
                    <span class="info-value"><?php echo date('H:i:s', $login_time); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Session Duration:</span>
                    <span class="info-value"><?php echo floor($session_duration / 60); ?> minutes</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Session Expires In:</span>
                    <span class="info-value"><?php echo $minutes_remaining; ?> minutes</span>
                </div>
                <div class="info-row">
                    <span class="info-label">IP Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SESSION['ip_address'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login:</span>
                    <span class="info-value"><?php echo $user_info['last_login'] ? date('M d, Y H:i', strtotime($user_info['last_login'])) : 'First login'; ?></span>
                </div>
            </div>
        </div>

        <!-- Security Features Card -->
        <div class="security-card">
            <h2>Active Security Features</h2>

            <div class="security-feature">
                <h3>✓ Input Validation & Sanitization</h3>
                <p>All user inputs are validated and sanitized before processing. This prevents XSS attacks and ensures data integrity.</p>
                <div class="code-block">Example: htmlspecialchars($username, ENT_QUOTES, 'UTF-8')</div>
            </div>

            <div class="security-feature">
                <h3>✓ SQL Injection Protection (PDO)</h3>
                <p>All database queries use PDO prepared statements with parameter binding, preventing SQL injection attacks.</p>
                <div class="code-block">Example: $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT)</div>
            </div>

            <div class="security-feature">
                <h3>✓ CSRF Token Protection</h3>
                <p>All forms include CSRF tokens to prevent Cross-Site Request Forgery attacks. Tokens expire after 1 hour.</p>
                <div class="code-block">Current CSRF Token: <?php echo substr($csrf_token, 0, 16); ?>...</div>
            </div>

            <div class="security-feature">
                <h3>✓ Secure Session Management</h3>
                <p>Sessions use HTTPOnly, Secure, and SameSite cookies. Session IDs are regenerated periodically to prevent fixation.</p>
                <div class="code-block">Session ID regenerated every 30 minutes</div>
            </div>

            <div class="security-feature">
                <h3>✓ HTTPS/SSL Support</h3>
                <p>Application is configured to work with HTTPS. Secure flag ensures cookies are only transmitted over encrypted connections.</p>
                <div class="code-block">
                    HTTPS Status: <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? '🔒 Enabled' : '⚠️ Not detected (enable in production)'; ?>
                </div>
            </div>

            <?php if ($minutes_remaining < 10): ?>
            <div class="session-warning">
                ⚠️ <strong>Warning:</strong> Your session will expire in <?php echo $minutes_remaining; ?> minutes. Please save your work.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
