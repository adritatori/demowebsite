# 🔧 Issue Fixes & Solutions

## Issue #1: XSS Test Not Showing Error Message

### ✅ This is Actually Working Correctly!

When you enter `<script>alert('XSS')</script>` in the username field, you don't see an error because:

**The HTML5 client-side validation blocks it BEFORE it reaches the server!**

See this line in `index.php:267`:
```html
pattern="[a-zA-Z0-9_]{3,50}"
```

This pattern only allows alphanumeric characters and underscores, so `<script>` gets blocked by the browser itself.

### How to Test Server-Side Validation

To see the server-side error messages, you need to bypass client-side validation:

#### **Method 1: Remove Pattern Validation (Easiest)**

1. Open `http://172.19.12.158/index.php`
2. Press F12 to open Developer Tools
3. Go to **Inspector/Elements** tab
4. Find the username input field
5. Right-click on it → Edit as HTML
6. Remove the `pattern="[a-zA-Z0-9_]{3,50}"` attribute
7. Now try entering: `<script>alert('XSS')</script>`
8. **Expected:** Server-side error: "Invalid username format. Use 3-50 alphanumeric characters or underscore."

#### **Method 2: Use Browser Console**

1. Open the login page
2. Press F12 → Console
3. Paste this JavaScript:
```javascript
// Disable HTML5 validation
document.querySelector('form').setAttribute('novalidate', 'novalidate');

// Now submit the form with XSS payload
document.querySelector('#username').value = '<script>alert("XSS")</script>';
document.querySelector('#password').value = 'testpassword';
document.querySelector('form').submit();
```
4. **Expected:** Server-side validation error appears

#### **Method 3: Using curl (Command Line)**

From your VM or local machine:
```bash
curl -X POST http://172.19.12.158/login.php \
  -d "username=<script>alert('XSS')</script>" \
  -d "password=test123" \
  -d "csrf_token=invalid" \
  -v
```

**Expected:** You'll see the error in the response

### What This Proves

This demonstrates **defense in depth**:
1. **First layer:** Client-side validation (HTML5 pattern) - blocks simple attacks
2. **Second layer:** Server-side validation (PHP regex) - validates even if client-side is bypassed
3. **Third layer:** Output sanitization (htmlspecialchars) - prevents XSS if bad data gets through

All three layers protect you! ✅

---

## Issue #2: "Database connection failed. Please contact administrator."

### 🚨 This Needs to Be Fixed!

This error means the database hasn't been set up yet on your VM.

### Quick Fix (2 Minutes):

#### **On Your VM (SSH to 172.19.12.158):**

```bash
# 1. Login to MySQL
mysql -u root -p
```

#### **In MySQL, paste these commands:**

```sql
CREATE DATABASE IF NOT EXISTS secure_website;
USE secure_website;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, password, email) VALUES
('demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com')
ON DUPLICATE KEY UPDATE username=username;

SELECT * FROM users;
EXIT;
```

#### **Update config.php:**

```bash
sudo nano /var/www/html/config.php
```

Find these lines (around line 12-13):
```php
define('DB_USER', 'root');  // Your MySQL username
define('DB_PASS', '');      // ← PUT YOUR MYSQL PASSWORD HERE!
```

Change `DB_PASS` to your actual MySQL root password:
```php
define('DB_PASS', 'your_actual_password');
```

Save (Ctrl+X → Y → Enter)

#### **Test Again:**

Go to `http://172.19.12.158/index.php`
- Username: `demo`
- Password: `DemoPass123!`

Should work now! ✅

---

## 🧪 Create a Test Connection File

To diagnose database issues, create this file:

```bash
sudo nano /var/www/html/test_connection.php
```

Paste:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

$host = 'localhost';
$db = 'secure_website';
$user = 'root';
$pass = '';  // ← PUT YOUR PASSWORD HERE

echo "<p><strong>Testing connection to:</strong></p>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>Database: $db</li>";
echo "<li>User: $user</li>";
echo "<li>Password: " . (empty($pass) ? '<span style="color:red;">EMPTY (This is probably wrong!)</span>' : '<span style="color:green;">Set</span>') . "</li>";
echo "</ul>";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p style='color:green; font-weight:bold;'>✅ Database connection successful!</p>";

    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✅ 'users' table exists</p>";

        // Check if demo user exists
        $stmt = $pdo->query("SELECT username, email FROM users WHERE username = 'demo'");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo "<p style='color:green;'>✅ Demo user found:</p>";
            echo "<ul>";
            echo "<li>Username: " . htmlspecialchars($user['username']) . "</li>";
            echo "<li>Email: " . htmlspecialchars($user['email']) . "</li>";
            echo "</ul>";
            echo "<p style='color:green; font-weight:bold;'>🎉 Everything is set up correctly! You can login now.</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ Demo user not found. Run the INSERT command from the setup guide.</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ 'users' table does not exist. Run the CREATE TABLE commands.</p>";
    }

    // Show all tables
    echo "<p><strong>Tables in database:</strong></p>";
    $stmt = $pdo->query("SHOW TABLES");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ Connection failed!</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";

    echo "<h3>Common Fixes:</h3>";
    echo "<ul>";
    echo "<li>If error says 'Access denied': Wrong password in config.php</li>";
    echo "<li>If error says 'Unknown database': Run CREATE DATABASE command</li>";
    echo "<li>If error says 'Connection refused': MySQL is not running (sudo systemctl start mysql)</li>";
    echo "</ul>";
}
?>
```

Then visit: `http://172.19.12.158/test_connection.php`

This will tell you EXACTLY what's wrong! 🔍

---

## ✅ Verification Checklist

After fixing, verify:

- [ ] MySQL is running: `sudo systemctl status mysql`
- [ ] Database exists: `mysql -u root -p -e "SHOW DATABASES;" | grep secure_website`
- [ ] Tables exist: `mysql -u root -p -e "USE secure_website; SHOW TABLES;"`
- [ ] Demo user exists: `mysql -u root -p -e "USE secure_website; SELECT * FROM users;"`
- [ ] config.php has correct password
- [ ] Can login at `http://172.19.12.158/index.php` with `demo` / `DemoPass123!`
- [ ] test_connection.php shows all green checkmarks

---

## 📞 Still Having Issues?

Run this diagnostic command on your VM:

```bash
echo "=== System Check ==="
echo "MySQL Status:"
sudo systemctl status mysql | grep Active

echo -e "\nPHP Modules:"
php -m | grep -i pdo
php -m | grep -i mysql

echo -e "\nApache Status:"
sudo systemctl status apache2 | grep Active

echo -e "\nFile Permissions:"
ls -la /var/www/html/*.php

echo -e "\nApache Error Log (last 10 lines):"
sudo tail -10 /var/log/apache2/error.log
```

Send me the output if you need more help!
