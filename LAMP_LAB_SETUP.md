# 🚀 Setup Guide for lamp_lab.sh Environment

This guide is for setting up the secure website on a VM that was provisioned with `lamp_lab.sh`.

---

## ✅ Prerequisites

Your VM should already have (from lamp_lab.sh):
- ✅ Apache2 installed and running
- ✅ MySQL/MariaDB installed and running
- ✅ PHP installed with necessary extensions
- ✅ Database: `studentdb` created
- ✅ Database user: `student` with password `Password123!`

---

## ⚡ Quick Setup (3 Minutes)

### Step 1: Copy Website Files to VM

```bash
# On your VM, navigate to the repository directory
cd /path/to/demowebsite

# Copy all website files to web root
sudo cp index.php login.php dashboard.php logout.php config.php test_connection.php /var/www/html/

# Set proper permissions
sudo chown -R www-data:www-data /var/www/html/
sudo chmod 644 /var/www/html/*.php
```

### Step 2: Create Database Tables

The config.php is already configured to use your lamp_lab.sh database credentials:
- Database: `studentdb`
- User: `student`
- Password: `Password123!`

Now just create the tables:

```bash
# Login to MySQL using sudo (no password needed!)
sudo mysql studentdb
```

Then paste this SQL:

```sql
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

-- Verify it worked
SELECT * FROM users;
EXIT;
```

You should see the demo user listed!

### Step 3: Verify Setup

Visit: **http://172.19.12.158/test_connection.php**

You should see all **green checkmarks** ✅

### Step 4: Login!

Visit: **http://172.19.12.158/index.php**

**Login Credentials:**
- Username: `demo`
- Password: `DemoPass123!`

🎉 **You're done!** You should now see the secure dashboard!

---

## 🗄️ Alternative: Use SQL File

If you prefer to use the SQL file instead of copying/pasting:

```bash
# Option 1: Via sudo mysql
sudo mysql studentdb < /var/www/html/database_setup.sql

# Option 2: Via student user
mysql -u student -pPassword123! studentdb < database_setup.sql
```

---

## ✅ Verify Everything

### Check Database Tables
```bash
sudo mysql -e "USE studentdb; SHOW TABLES;"
```

Should show:
```
+---------------------+
| Tables_in_studentdb |
+---------------------+
| users               |
| user_sessions       |
+---------------------+
```

### Check Demo User
```bash
sudo mysql -e "USE studentdb; SELECT username, email FROM users;"
```

Should show:
```
+----------+------------------+
| username | email            |
+----------+------------------+
| demo     | demo@example.com |
+----------+------------------+
```

### Test Website Connection
Visit: `http://172.19.12.158/test_connection.php`

Should display:
- ✅ Database connection successful!
- ✅ 'users' table exists
- ✅ 'user_sessions' table exists
- ✅ Demo user found

---

## 🧪 Test Security Features

### 1. Input Validation & Sanitization
Open browser console (F12) on the login page:
```javascript
document.querySelector('form').setAttribute('novalidate', 'novalidate');
document.querySelector('#username').value = '<script>alert("XSS")</script>';
document.querySelector('#password').value = 'test';
document.querySelector('form').submit();
```
**Expected:** Server-side error about invalid username format

### 2. SQL Injection Protection
Try logging in with:
- Username: `admin' OR '1'='1`
- Password: anything

**Expected:** Login fails with "Invalid username or password"

### 3. CSRF Token Protection
Open console (F12) and try:
```javascript
document.querySelector('input[name="csrf_token"]').value = 'invalid';
// Then submit the form normally
```
**Expected:** "Invalid security token" error

### 4. Secure Session Management
Login successfully, then open DevTools (F12) → Application → Cookies

**Check:** SECURE_SESSION cookie has:
- HttpOnly: ✓
- SameSite: Strict
- Secure: ✓ (if using HTTPS)

### 5. HTTPS/SSL Support
Check the SSL indicator at the bottom of the login page

To enable HTTPS on your VM:
```bash
sudo a2enmod ssl
sudo a2ensite default-ssl
sudo systemctl restart apache2
```

---

## 🎯 Database Credentials Reference

All these are configured automatically in config.php:

| Setting | Value |
|---------|-------|
| Database Host | localhost |
| Database Name | studentdb |
| Database User | student |
| Database Password | Password123! |
| Web Root | /var/www/html/ |

These credentials come from your `lamp_lab.sh` script (lines 15-19).

---

## 🐛 Troubleshooting

### "Database connection failed"
**Check if MySQL is running:**
```bash
sudo systemctl status mysql
```

**If not running:**
```bash
sudo systemctl start mysql
```

### "Access denied for user 'student'"
The lamp_lab.sh script should have created this user. Verify:
```bash
sudo mysql -e "SELECT user, host FROM mysql.user WHERE user='student';"
```

**If user doesn't exist, recreate:**
```bash
sudo mysql -e "CREATE USER 'student'@'localhost' IDENTIFIED BY 'Password123!';"
sudo mysql -e "GRANT ALL ON studentdb.* TO 'student'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### "Unknown database 'studentdb'"
The lamp_lab.sh script should have created it. Verify:
```bash
sudo mysql -e "SHOW DATABASES;" | grep studentdb
```

**If it doesn't exist, create it:**
```bash
sudo mysql -e "CREATE DATABASE studentdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Tables don't exist
Run the SQL commands from Step 2 again.

### Can't login with demo/DemoPass123!
**Verify demo user exists:**
```bash
sudo mysql -e "USE studentdb; SELECT username FROM users WHERE username='demo';"
```

**If not, insert it:**
```bash
sudo mysql -e "USE studentdb; INSERT INTO users (username, password, email) VALUES ('demo', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com');"
```

---

## 📁 File Structure

```
/var/www/html/
├── index.php           # Login page
├── login.php           # Authentication handler
├── dashboard.php       # Protected dashboard
├── logout.php          # Logout handler
├── config.php          # Database config (using lamp_lab.sh credentials)
├── test_connection.php # Diagnostic tool
└── adminer/            # Database admin UI (from lamp_lab.sh)
    └── index.php
```

---

## 🔐 Security Features Implemented

1. **Input Validation & Sanitization**
   - Client-side HTML5 validation
   - Server-side regex validation
   - Output encoding with htmlspecialchars()

2. **SQL Injection Protection**
   - PDO with prepared statements
   - Parameter binding
   - No raw SQL queries

3. **CSRF Token Protection**
   - Token generation per session
   - Token validation on form submit
   - 1-hour token expiry

4. **Secure Session Management**
   - HTTPOnly cookies (no JavaScript access)
   - SameSite=Strict (CSRF protection)
   - Secure flag (HTTPS only)
   - Session regeneration every 30 min
   - Database session tracking

5. **HTTPS/SSL Support**
   - Ready for SSL/TLS
   - Secure cookie flags
   - Mixed content prevention

---

## 📚 Documentation

- **SECURITY_TESTING_GUIDE.md** - Detailed testing guide for all 5 security features
- **README.md** - Project overview
- **MYSQL_ERROR_1698_FIX.md** - Fix MySQL authentication issues
- **ISSUE_FIXES.md** - Common problems and solutions

---

## 🌐 Using Adminer

Your lamp_lab.sh setup includes Adminer (database management UI).

Visit: **http://172.19.12.158/adminer/**

**Login with:**
- System: MySQL
- Server: localhost
- Username: student
- Password: Password123!
- Database: studentdb

You can view/edit the users and user_sessions tables from here!

---

## 🎓 Next Steps

1. ✅ Test all 5 security features (see SECURITY_TESTING_GUIDE.md)
2. ✅ Try to break the security (ethical hacking practice)
3. ✅ Review the code to understand each security feature
4. ✅ Customize the website for your needs
5. ✅ Enable HTTPS for production use

---

**Total Setup Time: ~3 minutes** ⏱️

🎉 **Your secure website is ready!** 🎉
