# 🚨 Quick Fix Guide - Database Setup

## Problem: "Database connection failed. Please contact administrator."

This means the database hasn't been set up yet. Follow these steps on your VM:

---

## ✅ Step-by-Step Database Setup

### Step 1: SSH into Your VM
```bash
ssh user@172.19.12.158
```

### Step 2: Check if MySQL is Running
```bash
sudo systemctl status mysql
```

**If it's not running:**
```bash
sudo systemctl start mysql
```

### Step 3: Login to MySQL
```bash
mysql -u root -p
```
Enter your MySQL root password.

### Step 4: Create the Database Manually

Paste these SQL commands one by one:

```sql
-- Create the database
CREATE DATABASE IF NOT EXISTS secure_website;
USE secure_website;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sessions table
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

-- Insert demo user (password: DemoPass123!)
INSERT INTO users (username, password, email) VALUES
('demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com')
ON DUPLICATE KEY UPDATE username=username;

-- Verify the data
SELECT * FROM users;
```

You should see the demo user listed!

### Step 5: Exit MySQL
```sql
EXIT;
```

### Step 6: Update config.php with Your MySQL Credentials

```bash
sudo nano /var/www/html/config.php
```

Find these lines and update them:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'secure_website');
define('DB_USER', 'root');           // ← Your MySQL username
define('DB_PASS', 'YOUR_PASSWORD');  // ← Your MySQL password (the one you used to login)
```

Save and exit (Ctrl+X, then Y, then Enter)

### Step 7: Test the Connection

Try logging in again at: `http://172.19.12.158/index.php`
- Username: `demo`
- Password: `DemoPass123!`

---

## 🔧 Alternative: Using the SQL File

If you have the files on your VM:

```bash
# Navigate to where you copied the files
cd /var/www/html/

# Run the SQL script
mysql -u root -p < database_setup.sql
```

Then update config.php as shown in Step 6.

---

## ✅ Verify Everything is Working

After setup, check:

1. **Database exists:**
```bash
mysql -u root -p -e "SHOW DATABASES;" | grep secure_website
```

2. **Tables exist:**
```bash
mysql -u root -p -e "USE secure_website; SHOW TABLES;"
```

3. **Demo user exists:**
```bash
mysql -u root -p -e "USE secure_website; SELECT username, email FROM users;"
```

You should see:
```
+----------+------------------+
| username | email            |
+----------+------------------+
| demo     | demo@example.com |
+----------+------------------+
```

---

## 🐛 Still Getting Errors?

### Check PHP MySQL Extension
```bash
php -m | grep -i pdo
php -m | grep -i mysql
```

You should see:
- PDO
- pdo_mysql
- mysqli

**If not installed:**
```bash
sudo apt install php-mysql
sudo systemctl restart apache2
```

### Check Apache Error Logs
```bash
sudo tail -f /var/log/apache2/error.log
```

This will show you the exact error happening.

### Enable PHP Error Display (Temporary - for debugging)
```bash
# Create a test file
sudo nano /var/www/html/test_db.php
```

Paste this:
```php
<?php
// Test database connection
$host = 'localhost';
$db = 'secure_website';
$user = 'root';  // Change to your username
$pass = '';      // Change to your password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    echo "✅ Database connection successful!<br>";

    $stmt = $pdo->query("SELECT username, email FROM users");
    echo "<br>Users in database:<br>";
    while ($row = $stmt->fetch()) {
        echo "- " . $row['username'] . " (" . $row['email'] . ")<br>";
    }
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
```

Visit: `http://172.19.12.158/test_db.php`

This will tell you exactly what's wrong!

---

## 📝 Common Issues

### "Access denied for user 'root'@'localhost'"
- Your MySQL password is wrong in config.php
- Update it with the correct password

### "Unknown database 'secure_website'"
- The database wasn't created
- Run Step 4 again

### "Table 'secure_website.users' doesn't exist"
- Tables weren't created
- Run the CREATE TABLE commands from Step 4

---

Once the database is set up, try logging in again and the error should be gone! 🎉
