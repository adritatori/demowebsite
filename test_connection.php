<?php
/**
 * Database Connection Test Script
 *
 * Use this file to diagnose database connection issues.
 * Visit: http://your-ip/test_connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        h1 { color: #667eea; margin-top: 0; }
        h2 { color: #764ba2; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0; }
        .test-item { padding: 10px; margin: 10px 0; background: #f8f9fa; border-radius: 5px; }
        ul { line-height: 1.8; }
        code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.1em;
        }
        .status-box.success {
            background: #d4edda;
            border: 2px solid #28a745;
        }
        .status-box.error {
            background: #f8d7da;
            border: 2px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Connection Test</h1>

        <?php
        // Configuration (matches lamp_lab.sh setup)
        $host = 'localhost';
        $db = 'studentdb';       // Database from lamp_lab.sh
        $user = 'student';       // User from lamp_lab.sh
        $pass = 'Password123!';  // Password from lamp_lab.sh

        echo "<h2>Configuration Details</h2>";
        echo "<div class='test-item'>";
        echo "<ul>";
        echo "<li><strong>Host:</strong> " . htmlspecialchars($host) . "</li>";
        echo "<li><strong>Database:</strong> " . htmlspecialchars($db) . "</li>";
        echo "<li><strong>User:</strong> " . htmlspecialchars($user) . "</li>";
        echo "<li><strong>Password:</strong> ";
        if (empty($pass)) {
            echo "<span class='error'>❌ EMPTY</span>";
        } else {
            echo "<span class='success'>✅ Set (" . strlen($pass) . " characters - from lamp_lab.sh)</span>";
        }
        echo "</li>";
        echo "</ul>";
        echo "</div>";

        // PHP Extensions Check
        echo "<h2>PHP Extensions</h2>";
        echo "<div class='test-item'>";
        $extensions = ['PDO', 'pdo_mysql'];
        $all_extensions_ok = true;
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<span class='success'>✅ $ext loaded</span><br>";
            } else {
                echo "<span class='error'>❌ $ext NOT loaded</span><br>";
                $all_extensions_ok = false;
            }
        }
        if (!$all_extensions_ok) {
            echo "<div class='info' style='margin-top:10px;'>";
            echo "<strong>Fix:</strong> Install PHP MySQL extension:<br>";
            echo "<code>sudo apt install php-mysql && sudo systemctl restart apache2</code>";
            echo "</div>";
        }
        echo "</div>";

        // Connection Test
        echo "<h2>Connection Test</h2>";

        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $pdo = new PDO($dsn, $user, $pass, $options);

            echo "<div class='status-box success'>";
            echo "<span class='success'>🎉 DATABASE CONNECTION SUCCESSFUL!</span>";
            echo "</div>";

            // Check tables
            echo "<h2>Database Tables</h2>";
            echo "<div class='test-item'>";

            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($tables) > 0) {
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li><span class='success'>✅</span> " . htmlspecialchars($table) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<span class='error'>❌ No tables found in database</span>";
                echo "<div class='info' style='margin-top:10px;'>";
                echo "<strong>Fix:</strong> Run the database_setup.sql script";
                echo "</div>";
            }
            echo "</div>";

            // Check required tables
            echo "<h2>Required Tables Check</h2>";
            echo "<div class='test-item'>";
            $required_tables = ['users', 'user_sessions'];
            $all_tables_exist = true;

            foreach ($required_tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "<span class='success'>✅ Table '$table' exists</span><br>";
                } else {
                    echo "<span class='error'>❌ Table '$table' is missing</span><br>";
                    $all_tables_exist = false;
                }
            }

            if (!$all_tables_exist) {
                echo "<div class='info' style='margin-top:10px;'>";
                echo "<strong>Fix:</strong> Run the CREATE TABLE commands from database_setup.sql";
                echo "</div>";
            }
            echo "</div>";

            // Check demo user
            echo "<h2>Demo User Check</h2>";
            echo "<div class='test-item'>";

            if (in_array('users', $tables)) {
                $stmt = $pdo->query("SELECT id, username, email, created_at FROM users WHERE username = 'demo'");
                $demo_user = $stmt->fetch();

                if ($demo_user) {
                    echo "<span class='success'>✅ Demo user found!</span>";
                    echo "<ul>";
                    echo "<li><strong>ID:</strong> " . htmlspecialchars($demo_user['id']) . "</li>";
                    echo "<li><strong>Username:</strong> " . htmlspecialchars($demo_user['username']) . "</li>";
                    echo "<li><strong>Email:</strong> " . htmlspecialchars($demo_user['email']) . "</li>";
                    echo "<li><strong>Created:</strong> " . htmlspecialchars($demo_user['created_at']) . "</li>";
                    echo "</ul>";

                    echo "<div class='status-box success'>";
                    echo "<strong>✅ ALL CHECKS PASSED!</strong><br><br>";
                    echo "You can now login at: <a href='index.php'>index.php</a><br>";
                    echo "<strong>Username:</strong> demo<br>";
                    echo "<strong>Password:</strong> DemoPass123!";
                    echo "</div>";
                } else {
                    echo "<span class='error'>❌ Demo user not found</span>";
                    echo "<div class='info' style='margin-top:10px;'>";
                    echo "<strong>Fix:</strong> Run this SQL command:<br>";
                    echo "<pre>INSERT INTO users (username, password, email) VALUES
('demo', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com');</pre>";
                    echo "</div>";
                }
            } else {
                echo "<span class='error'>❌ Cannot check - 'users' table doesn't exist</span>";
            }
            echo "</div>";

            // Show all users (for debugging)
            if (in_array('users', $tables)) {
                echo "<h2>All Users in Database</h2>";
                echo "<div class='test-item'>";
                $stmt = $pdo->query("SELECT id, username, email FROM users");
                $all_users = $stmt->fetchAll();

                if (count($all_users) > 0) {
                    echo "<table style='width:100%; border-collapse: collapse;'>";
                    echo "<tr style='background:#667eea; color:white;'>";
                    echo "<th style='padding:10px; text-align:left;'>ID</th>";
                    echo "<th style='padding:10px; text-align:left;'>Username</th>";
                    echo "<th style='padding:10px; text-align:left;'>Email</th>";
                    echo "</tr>";
                    foreach ($all_users as $u) {
                        echo "<tr style='border-bottom: 1px solid #ddd;'>";
                        echo "<td style='padding:8px;'>" . htmlspecialchars($u['id']) . "</td>";
                        echo "<td style='padding:8px;'>" . htmlspecialchars($u['username']) . "</td>";
                        echo "<td style='padding:8px;'>" . htmlspecialchars($u['email']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<span class='warning'>⚠️ No users in database</span>";
                }
                echo "</div>";
            }

        } catch (PDOException $e) {
            echo "<div class='status-box error'>";
            echo "<span class='error'>❌ DATABASE CONNECTION FAILED!</span>";
            echo "</div>";

            echo "<div class='test-item'>";
            echo "<strong>Error Message:</strong><br>";
            echo "<code>" . htmlspecialchars($e->getMessage()) . "</code>";
            echo "</div>";

            echo "<h2>Common Solutions</h2>";
            echo "<div class='test-item'>";
            echo "<ul>";
            echo "<li><strong>Access denied for user:</strong> Wrong password in this file or config.php</li>";
            echo "<li><strong>Unknown database 'secure_website':</strong> Database not created. Run:<br><code>mysql -u root -p -e \"CREATE DATABASE secure_website;\"</code></li>";
            echo "<li><strong>Can't connect to MySQL server:</strong> MySQL not running. Run:<br><code>sudo systemctl start mysql</code></li>";
            echo "<li><strong>SQLSTATE[HY000] [2002]:</strong> MySQL socket issue. Check MySQL status:<br><code>sudo systemctl status mysql</code></li>";
            echo "</ul>";
            echo "</div>";

            echo "<h2>Quick Fix Commands</h2>";
            echo "<div class='test-item'>";
            echo "<pre>";
            echo "# Start MySQL\n";
            echo "sudo systemctl start mysql\n\n";
            echo "# Login to MySQL\n";
            echo "mysql -u root -p\n\n";
            echo "# Create database\n";
            echo "CREATE DATABASE IF NOT EXISTS secure_website;\n";
            echo "USE secure_website;\n\n";
            echo "# Then run the commands from database_setup.sql";
            echo "</pre>";
            echo "</div>";
        }
        ?>

        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h3>📚 Helpful Resources</h3>
            <ul>
                <li><a href="DATABASE_SETUP_FIX.md">DATABASE_SETUP_FIX.md</a> - Complete setup guide</li>
                <li><a href="ISSUE_FIXES.md">ISSUE_FIXES.md</a> - Common issues and solutions</li>
                <li><a href="SECURITY_TESTING_GUIDE.md">SECURITY_TESTING_GUIDE.md</a> - Security testing guide</li>
            </ul>
        </div>

        <div style="margin-top: 20px; text-align: center; color: #666;">
            <small>Test run at: <?php echo date('Y-m-d H:i:s'); ?></small>
        </div>
    </div>
</body>
</html>
