<?php
/**
 * Dashboard - Protected Page
 * SECURITY FEATURES IMPLEMENTED:
 * #1 - Input Validation & Sanitization (output escaping)
 * #4 - Secure Session Management (requires login)
 */

require_once 'includes/db.php';
require_once 'includes/session.php';

// Require authentication to access this page
require_login();

$username = get_current_username();
$user_id = get_current_user_id();
$is_admin = is_admin();

// Fetch user details
try {
    $stmt = $pdo->prepare("SELECT username, email, role, created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();

    // Fetch recent activity
    $activity_stmt = $pdo->prepare("
        SELECT action, ip_address, created_at
        FROM activity_log
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $activity_stmt->execute([$user_id]);
    $activities = $activity_stmt->fetchAll();

    // Get total user count (admin only)
    if ($is_admin) {
        $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total_users = $count_stmt->fetch()['total'];
    }
} catch (PDOException $e) {
    log_security_event("Dashboard DB error: " . $e->getMessage(), 'ERROR');
    $user_data = null;
    $activities = [];
}

// Session info for security demonstration
$session_created = isset($_SESSION['CREATED']) ? date('Y-m-d H:i:s', $_SESSION['CREATED']) : 'Unknown';
$last_activity = isset($_SESSION['LAST_ACTIVITY']) ? date('Y-m-d H:i:s', $_SESSION['LAST_ACTIVITY']) : 'Unknown';
$time_remaining = isset($_SESSION['LAST_ACTIVITY']) ? 1800 - (time() - $_SESSION['LAST_ACTIVITY']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dashboard - Secure Area</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🔒 Secure Dashboard</h1>
            <p class="subtitle">Welcome, <?php echo escape_output($username); ?>!</p>
        </header>

        <nav class="dashboard-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </nav>

        <main class="dashboard-main">
            <!-- User Info Card -->
            <div class="card">
                <h2>👤 Your Account Information</h2>
                <?php if ($user_data): ?>
                    <table class="info-table">
                        <tr>
                            <th>Username:</th>
                            <td><?php echo escape_output($user_data['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo escape_output($user_data['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Role:</th>
                            <td>
                                <span class="badge badge-<?php echo $user_data['role']; ?>">
                                    <?php echo escape_output(strtoupper($user_data['role'])); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Account Created:</th>
                            <td><?php echo escape_output($user_data['created_at']); ?></td>
                        </tr>
                        <tr>
                            <th>Last Login:</th>
                            <td><?php echo escape_output($user_data['last_login'] ?? 'Never'); ?></td>
                        </tr>
                    </table>
                <?php else: ?>
                    <p class="error">Unable to load user data.</p>
                <?php endif; ?>
            </div>

            <!-- Session Security Info -->
            <div class="card">
                <h2>🔐 Session Security Information</h2>
                <p class="info-text">This demonstrates <strong>Security Feature #4: Secure Session Management</strong></p>

                <table class="info-table">
                    <tr>
                        <th>Session ID:</th>
                        <td><code><?php echo substr(session_id(), 0, 20) . '...'; ?></code></td>
                    </tr>
                    <tr>
                        <th>Session Created:</th>
                        <td><?php echo $session_created; ?></td>
                    </tr>
                    <tr>
                        <th>Last Activity:</th>
                        <td><?php echo $last_activity; ?></td>
                    </tr>
                    <tr>
                        <th>Time Until Timeout:</th>
                        <td id="timeout-counter"><?php echo gmdate('i:s', $time_remaining); ?> minutes</td>
                    </tr>
                    <tr>
                        <th>Cookie Security:</th>
                        <td>
                            <span class="badge badge-success">HttpOnly ✓</span>
                            <span class="badge badge-success">SameSite=Strict ✓</span>
                        </td>
                    </tr>
                </table>

                <div class="alert alert-info">
                    <strong>⚠️ Security Note:</strong> Your session will automatically expire after 30 minutes of inactivity.
                    Session IDs are regenerated every 5 minutes to prevent session fixation attacks.
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <h2>📊 Recent Activity Log</h2>
                <?php if (!empty($activities)): ?>
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo escape_output($activity['action']); ?></td>
                                    <td><code><?php echo escape_output($activity['ip_address']); ?></code></td>
                                    <td><?php echo escape_output($activity['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="info-text">No recent activity.</p>
                <?php endif; ?>
            </div>

            <!-- Admin Section -->
            <?php if ($is_admin): ?>
                <div class="card admin-card">
                    <h2>👑 Admin Statistics</h2>
                    <div class="stat-box">
                        <div class="stat">
                            <span class="stat-label">Total Users:</span>
                            <span class="stat-value"><?php echo $total_users ?? 0; ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Your Role:</span>
                            <span class="stat-value">Administrator</span>
                        </div>
                    </div>
                    <p class="info-text">✨ You have administrator privileges</p>
                </div>
            <?php endif; ?>

            <!-- Security Features Demo -->
            <div class="card">
                <h2>🛡️ Security Features Demonstrated</h2>
                <div class="security-demo">
                    <div class="security-item">
                        <h3>1️⃣ Input Validation & Sanitization</h3>
                        <p>All user inputs on this page are sanitized using <code>htmlspecialchars()</code> to prevent XSS attacks.</p>
                        <p class="test-tip">💡 Try viewing page source - any HTML/JS is escaped</p>
                    </div>

                    <div class="security-item">
                        <h3>2️⃣ SQL Injection Protection</h3>
                        <p>All database queries use PDO prepared statements to prevent SQL injection.</p>
                        <p class="test-tip">💡 Login attempts with SQL injection fail safely</p>
                    </div>

                    <div class="security-item">
                        <h3>3️⃣ CSRF Protection</h3>
                        <p>All forms include CSRF tokens validated server-side.</p>
                        <p class="test-tip">💡 Check login form source for hidden csrf_token field</p>
                    </div>

                    <div class="security-item">
                        <h3>4️⃣ Secure Session Management</h3>
                        <p>Sessions use httpOnly cookies, automatic timeout, and ID regeneration.</p>
                        <p class="test-tip">💡 Check browser cookies - should have httpOnly flag</p>
                    </div>

                    <div class="security-item">
                        <h3>5️⃣ HTTPS/SSL Encryption</h3>
                        <p>Communication is encrypted when accessed via HTTPS.</p>
                        <p class="test-tip">💡 Access via https://172.19.12.158/ after SSL setup</p>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2024 Secure Web Application | Student Project</p>
            <p class="vm-info">VM IP: <strong>172.19.12.158</strong></p>
        </footer>
    </div>

    <script src="assets/script.js"></script>
    <script>
        // Update timeout counter every second
        let timeRemaining = <?php echo $time_remaining; ?>;
        setInterval(() => {
            if (timeRemaining > 0) {
                timeRemaining--;
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                document.getElementById('timeout-counter').textContent =
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0') + ' minutes';
            } else {
                document.getElementById('timeout-counter').textContent = 'Session expired';
                document.getElementById('timeout-counter').style.color = 'red';
            }
        }, 1000);
    </script>
</body>
</html>
