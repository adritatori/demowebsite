# 🔧 Fix MySQL Error 1698 - Access Denied for Root

## Error Message
```
Error 1698 (28000): Access denied for user 'root'@'localhost'
```

## 🎯 What's Happening?

On Ubuntu/Debian systems, MySQL root user is configured to use **`auth_socket`** authentication instead of password authentication. This means:
- ✅ Root can login via `sudo mysql` (no password needed)
- ❌ Root CANNOT login via `mysql -u root -p` (password won't work)

---

## ⚡ Solution 1: Use sudo (FASTEST - Recommended)

This is the quickest way to get your database set up!

### Step 1: Login to MySQL with sudo
```bash
sudo mysql
```
**No password needed!** You'll see the MySQL prompt: `mysql>`

### Step 2: Create the Database
Paste these commands one by one:

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

-- Insert demo user (password: DemoPass123!)
INSERT INTO users (username, password, email) VALUES
('demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com')
ON DUPLICATE KEY UPDATE username=username;

-- Verify it worked
SELECT * FROM users;
```

You should see the demo user!

### Step 3: Create a Database User for the Website

While still in MySQL (after running the commands above), create a dedicated user:

```sql
-- Create a user for the website
CREATE USER IF NOT EXISTS 'webuser'@'localhost' IDENTIFIED BY 'SecurePass456!';

-- Grant permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON secure_website.* TO 'webuser'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Test the new user
EXIT;
```

### Step 4: Test the New User
```bash
mysql -u webuser -p
# Enter password: SecurePass456!
```

If you can login, it worked! Type `EXIT;` to exit.

### Step 5: Update config.php

```bash
sudo nano /var/www/html/config.php
```

Change these lines (around line 12-13):
```php
define('DB_USER', 'webuser');        // ← Changed from 'root'
define('DB_PASS', 'SecurePass456!'); // ← Your new password
```

Save: **Ctrl+X** → **Y** → **Enter**

### Step 6: Test the Website!

Visit: `http://172.19.12.158/index.php`
- Username: `demo`
- Password: `DemoPass123!`

**Should work now!** ✅

---

## 🔐 Solution 2: Change Root to Use Password (Alternative)

If you want to keep using root with a password:

### Step 1: Login with sudo
```bash
sudo mysql
```

### Step 2: Change Authentication Method
```sql
-- Check current authentication method
SELECT user, host, plugin FROM mysql.user WHERE user = 'root';

-- Change to password authentication
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'YourNewRootPassword';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify the change
SELECT user, host, plugin FROM mysql.user WHERE user = 'root';

EXIT;
```

### Step 3: Test Login with Password
```bash
mysql -u root -p
# Enter the password you just set: YourNewRootPassword
```

### Step 4: Run Database Setup
Now you can run the database setup commands from the previous guide.

### Step 5: Update config.php
```bash
sudo nano /var/www/html/config.php
```

Change:
```php
define('DB_USER', 'root');
define('DB_PASS', 'YourNewRootPassword'); // ← The password you set
```

---

## 🚀 Solution 3: One-Line Database Setup (Super Fast!)

If you just want to get it working quickly:

```bash
sudo mysql -e "
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
('demo', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com')
ON DUPLICATE KEY UPDATE username=username;

CREATE USER IF NOT EXISTS 'webuser'@'localhost' IDENTIFIED BY 'SecurePass456!';
GRANT SELECT, INSERT, UPDATE, DELETE ON secure_website.* TO 'webuser'@'localhost';
FLUSH PRIVILEGES;
"
```

Then update config.php:
```bash
sudo nano /var/www/html/config.php
```

Set:
```php
define('DB_USER', 'webuser');
define('DB_PASS', 'SecurePass456!');
```

Done! 🎉

---

## ✅ Verify Everything Worked

### Check Database Exists
```bash
sudo mysql -e "SHOW DATABASES;" | grep secure_website
```

### Check Tables Exist
```bash
sudo mysql -e "USE secure_website; SHOW TABLES;"
```

### Check Demo User Exists
```bash
sudo mysql -e "USE secure_website; SELECT username, email FROM users;"
```

Should output:
```
+----------+------------------+
| username | email            |
+----------+------------------+
| demo     | demo@example.com |
+----------+------------------+
```

### Test Website Connection
Update `test_connection.php`:
```bash
sudo nano /var/www/html/test_connection.php
```

Change line 20:
```php
$pass = 'SecurePass456!';  // ← Your webuser password
```

And line 18:
```php
$user = 'webuser';  // ← Changed from 'root'
```

Visit: `http://172.19.12.158/test_connection.php`

Should show all green checkmarks! ✅

---

## 🎯 Recommended Approach

**For best security, use Solution 1 (create webuser):**
- ✅ Follows principle of least privilege
- ✅ Website doesn't need root access
- ✅ More secure if website is compromised
- ✅ Root stays protected with auth_socket

**Use Solution 2 only if:**
- You specifically need root password access
- You're in a development/testing environment

---

## 📝 Common Issues After Setup

### "Access denied for user 'webuser'@'localhost'"
You didn't create the webuser or password is wrong in config.php

**Fix:**
```bash
sudo mysql -e "CREATE USER IF NOT EXISTS 'webuser'@'localhost' IDENTIFIED BY 'SecurePass456!';"
sudo mysql -e "GRANT SELECT, INSERT, UPDATE, DELETE ON secure_website.* TO 'webuser'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### "Unknown database 'secure_website'"
Database wasn't created

**Fix:**
```bash
sudo mysql -e "CREATE DATABASE secure_website;"
```

### Still getting Error 1698
You're trying to use password authentication for root without changing the plugin

**Fix:** Use `sudo mysql` instead of `mysql -u root -p`

---

## 🔍 Troubleshooting Commands

### Check MySQL is Running
```bash
sudo systemctl status mysql
```

### Check Current User Plugins
```bash
sudo mysql -e "SELECT user, host, plugin FROM mysql.user;"
```

### View MySQL Error Log
```bash
sudo tail -50 /var/log/mysql/error.log
```

### Reset MySQL Root Password (if needed)
```bash
sudo systemctl stop mysql
sudo mysqld_safe --skip-grant-tables &
sudo mysql -e "FLUSH PRIVILEGES; ALTER USER 'root'@'localhost' IDENTIFIED BY 'NewPassword'; FLUSH PRIVILEGES;"
sudo systemctl restart mysql
```

---

## 📞 Quick Reference

**Login Commands:**
- Root with sudo: `sudo mysql`
- Root with password: `mysql -u root -p` (only if you changed auth method)
- Webuser: `mysql -u webuser -p`

**Config.php Settings (webuser):**
```php
define('DB_USER', 'webuser');
define('DB_PASS', 'SecurePass456!');
```

**Config.php Settings (root with password):**
```php
define('DB_USER', 'root');
define('DB_PASS', 'YourRootPassword');
```

---

**Try Solution 1 first - it's the fastest and most secure!** 🚀
