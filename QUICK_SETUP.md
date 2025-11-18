# ⚡ QUICK SETUP - 5 Minutes to Working Website

Follow these exact steps to get your secure website running!

---

## 📋 Prerequisites Check

On your VM (172.19.12.158), verify:

```bash
# Check Apache is running
sudo systemctl status apache2 | grep Active

# Check MySQL is running
sudo systemctl status mysql | grep Active

# Check PHP is installed
php -v
```

All should show as running/installed. If not:
```bash
sudo systemctl start apache2
sudo systemctl start mysql
```

---

## 🚀 Step 1: Copy Website Files (30 seconds)

```bash
# Navigate to your repository (adjust path as needed)
cd /path/to/demowebsite

# Copy all PHP files to web directory
sudo cp index.php login.php dashboard.php logout.php config.php test_connection.php /var/www/html/

# Set permissions
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

---

## 🗄️ Step 2: Setup Database (2 minutes)

### Login to MySQL (use sudo - no password needed!)
```bash
sudo mysql
```

### Create Database and Tables
Paste ALL of this into MySQL:

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

CREATE USER IF NOT EXISTS 'webuser'@'localhost' IDENTIFIED BY 'SecurePass456!';
GRANT SELECT, INSERT, UPDATE, DELETE ON secure_website.* TO 'webuser'@'localhost';
FLUSH PRIVILEGES;

SELECT 'Database setup complete!' as Status;
SELECT * FROM users;
EXIT;
```

You should see "Database setup complete!" and the demo user listed!

---

## ⚙️ Step 3: Update Config File (30 seconds)

```bash
sudo nano /var/www/html/config.php
```

Find lines 12-13 and change:

**FROM:**
```php
define('DB_USER', 'root');
define('DB_PASS', '');
```

**TO:**
```php
define('DB_USER', 'webuser');
define('DB_PASS', 'SecurePass456!');
```

Save and exit: **Ctrl+X** → **Y** → **Enter**

---

## ✅ Step 4: Verify Setup (1 minute)

### Test Database Connection
```bash
mysql -u webuser -p
# Password: SecurePass456!
```

If you can login, type:
```sql
USE secure_website;
SELECT * FROM users;
EXIT;
```

Should show the demo user!

### Test Website

Visit: **http://172.19.12.158/test_connection.php**

Should show all **green checkmarks** ✅

---

## 🎉 Step 5: Login to Your Secure Website!

Visit: **http://172.19.12.158/index.php**

**Login with:**
- Username: `demo`
- Password: `DemoPass123!`

You should see the beautiful dashboard! 🎯

---

## 🧪 Test the Security Features

Now that it's working, test the 5 security features:

### 1️⃣ Input Validation Test
```javascript
// Open browser console (F12) on index.php
document.querySelector('form').setAttribute('novalidate', 'novalidate');
document.querySelector('#username').value = '<script>alert("XSS")</script>';
document.querySelector('#password').value = 'test';
document.querySelector('form').submit();
```
**Expected:** Error about invalid username format

### 2️⃣ SQL Injection Test
Try logging in with username: `admin' OR '1'='1`
**Expected:** Login fails

### 3️⃣ CSRF Token Test
```javascript
// In browser console
document.querySelector('input[name="csrf_token"]').value = 'invalid';
// Now try to login
```
**Expected:** Invalid security token error

### 4️⃣ Session Security Check
After login, check cookies in DevTools (F12 → Application → Cookies)
**Expected:** SECURE_SESSION cookie has HttpOnly and SameSite flags

### 5️⃣ HTTPS Support
Look at the bottom of login page
**Shows:** Current HTTPS status

---

## 🐛 Troubleshooting

### "Database connection failed"
```bash
# Check test_connection.php credentials match config.php
sudo nano /var/www/html/test_connection.php
# Change line 18: $user = 'webuser';
# Change line 20: $pass = 'SecurePass456!';
```

### "Access denied for user 'webuser'"
```bash
# Recreate the user
sudo mysql -e "CREATE USER IF NOT EXISTS 'webuser'@'localhost' IDENTIFIED BY 'SecurePass456!';"
sudo mysql -e "GRANT ALL ON secure_website.* TO 'webuser'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### "Table 'secure_website.users' doesn't exist"
```bash
# Re-run the CREATE TABLE commands from Step 2
sudo mysql < /var/www/html/database_setup.sql
```

### Page shows PHP code instead of running
```bash
# Restart Apache
sudo systemctl restart apache2

# Check PHP is installed
php -v

# Install if needed
sudo apt install php libapache2-mod-php php-mysql
sudo systemctl restart apache2
```

---

## 📊 Summary of What You Created

✅ **3-Page Secure Website:**
- Login page (index.php)
- Dashboard (dashboard.php)
- Authentication handler (login.php)

✅ **5 Security Features:**
1. Input Validation & Sanitization
2. SQL Injection Protection (PDO)
3. CSRF Token Protection
4. Secure Session Management
5. HTTPS/SSL Support

✅ **Database:**
- secure_website database
- users table with demo user
- user_sessions table for tracking
- webuser account (principle of least privilege)

---

## 📚 Documentation Files

- **MYSQL_ERROR_1698_FIX.md** - Fix for MySQL access denied error
- **SECURITY_TESTING_GUIDE.md** - Complete testing guide for all 5 features
- **ISSUE_FIXES.md** - Common issues and solutions
- **DATABASE_SETUP_FIX.md** - Detailed database setup
- **README.md** - Project overview

---

## 🎯 Next Steps

1. ✅ Test all 5 security features (see SECURITY_TESTING_GUIDE.md)
2. ✅ Try to break the security (ethical hacking practice)
3. ✅ Enable HTTPS/SSL for production use
4. ✅ Customize the website for your needs
5. ✅ Learn from the code and apply to your projects!

---

**Total Setup Time: ~5 minutes** ⏱️

**Need Help?** Check the documentation files or run the diagnostic:
- http://172.19.12.158/test_connection.php

---

🎉 **Congratulations! Your secure website is now running!** 🎉
