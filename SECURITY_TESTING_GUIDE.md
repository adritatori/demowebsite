# Security Testing Guide for Secure Website Demo

This guide explains how to test all 5 security features implemented in the website from your local environment.

## 🚀 Quick Setup Instructions

### 1. Deploy Files to VM
First, copy all website files to your web server directory:

```bash
# On your VM (172.19.12.158)
sudo cp /path/to/files/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

### 2. Setup Database
Run the database setup script:

```bash
# On your VM
mysql -u root -p < /var/www/html/database_setup.sql
```

Or manually in MySQL:
```bash
mysql -u root -p
```
Then paste the contents of `database_setup.sql`

### 3. Configure Database Credentials
Edit `config.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'secure_website');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', 'your_password'); // Your MySQL password
```

### 4. Access the Website
Open your browser and navigate to:
```
http://172.19.12.158/index.php
```

**Demo Credentials:**
- Username: `demo`
- Password: `DemoPass123!`

---

## 🔒 Testing the 5 Security Features

### 1️⃣ Input Validation & Sanitization

**What it does:** Prevents XSS attacks and ensures data integrity by validating and sanitizing all user inputs.

#### Test Methods:

**Test A: XSS Prevention Test**
1. Go to the login page: `http://172.19.12.158/index.php`
2. Try entering malicious JavaScript in the username field:
   ```
   <script>alert('XSS')</script>
   ```
3. Submit the form
4. **Expected Result:** The script should NOT execute. Instead, you'll see an error message about invalid username format.

**Test B: Browser Developer Tools Check**
1. Login successfully with demo credentials
2. Open Browser Developer Tools (F12)
3. Go to the Console tab
4. Check the page source (Ctrl+U)
5. Search for your username in the HTML
6. **Expected Result:** Username should be displayed as:
   ```html
   &lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;
   ```
   NOT as executable JavaScript

**Test C: Username Validation**
1. Try logging in with invalid usernames:
   - `demo@123` (special characters not allowed)
   - `ab` (too short, minimum 3 characters)
   - `a_very_long_username_that_exceeds_fifty_characters_limit_test` (too long)
2. **Expected Result:** Error message: "Invalid username format"

**How to Verify:**
- Open `config.php:76` - Check the `sanitizeInput()` function
- Open `login.php:35` - See validation using `validateUsername()`
- Open `index.php` - See `htmlspecialchars()` usage throughout

---

### 2️⃣ SQL Injection Protection (PDO)

**What it does:** Uses PDO prepared statements to prevent SQL injection attacks.

#### Test Methods:

**Test A: Classic SQL Injection Attempt**
1. Go to the login page
2. Try classic SQL injection in username field:
   ```
   admin' OR '1'='1
   ```
3. Password: anything
4. **Expected Result:** Login should fail with "Invalid username or password" message

**Test B: Advanced SQL Injection Tests**
Try these in the username field:
```
' OR 1=1--
' OR '1'='1' /*
admin'--
' UNION SELECT NULL--
```
**Expected Result:** All should fail. The username validation will reject them first.

**Test C: Database Query Inspection**
1. SSH into your VM
2. Enable MySQL query logging:
   ```bash
   sudo mysql -u root -p
   ```
   ```sql
   SET GLOBAL general_log = 'ON';
   SET GLOBAL log_output = 'TABLE';
   ```
3. Try logging in with username: `testuser`
4. Check the query log:
   ```sql
   SELECT * FROM mysql.general_log ORDER BY event_time DESC LIMIT 10;
   ```
5. **Expected Result:** You should see a prepared statement like:
   ```sql
   SELECT id, username, password, email FROM users WHERE username = 'testuser' LIMIT 1
   ```
   NOT raw concatenated SQL

**How to Verify:**
- Open `config.php:33` - See PDO configuration with `PDO::ATTR_EMULATE_PREPARES => false`
- Open `login.php:46-48` - See prepared statement usage:
  ```php
  $stmt = $pdo->prepare("SELECT id, username, password, email FROM users WHERE username = :username LIMIT 1");
  $stmt->bindParam(':username', $username, PDO::PARAM_STR);
  ```

---

### 3️⃣ CSRF Token Protection

**What it does:** Prevents Cross-Site Request Forgery attacks by requiring valid tokens with each form submission.

#### Test Methods:

**Test A: Missing CSRF Token Test**
1. Open Browser Developer Tools (F12)
2. Go to the login page
3. In the Console tab, paste this JavaScript:
   ```javascript
   // Remove CSRF token from form
   document.querySelector('input[name="csrf_token"]').remove();

   // Submit the form
   document.querySelector('form').submit();
   ```
4. **Expected Result:** Login should fail with "Invalid security token. Please try again."

**Test B: Invalid CSRF Token Test**
1. Open Developer Tools (F12)
2. Go to Elements/Inspector tab
3. Find the hidden CSRF token input field
4. Change its value to something random: `invalid_token_12345`
5. Fill in valid credentials and submit
6. **Expected Result:** Login should fail with security token error

**Test C: CSRF Token Expiry Test**
1. Login to the dashboard
2. Note the CSRF token displayed
3. Wait for 1 hour (or modify `CSRF_TOKEN_EXPIRY` in config.php to 60 seconds for faster testing)
4. Try to perform an action with the old token
5. **Expected Result:** Should fail due to token expiry

**Test D: Token Reuse Prevention**
1. Login successfully
2. Open Browser Network tab (F12 → Network)
3. Submit the login form
4. Right-click on the POST request to `login.php`
5. Select "Replay" or "Resend"
6. **Expected Result:** May fail or require new token (depending on session state)

**How to Verify:**
- Open `config.php:63-83` - See CSRF token generation and validation functions
- Open `index.php:23` - See token generation
- Open `login.php:25-27` - See token validation before processing login

---

### 4️⃣ Secure Session Management

**What it does:** Implements secure session handling with HTTPOnly, Secure, and SameSite cookies.

#### Test Methods:

**Test A: Cookie Security Inspection**
1. Open Browser Developer Tools (F12)
2. Go to Application/Storage tab → Cookies
3. Login to the website
4. Inspect the session cookie (SECURE_SESSION)
5. **Expected Result:** Cookie should have these flags:
   - `HttpOnly`: ✓ (prevents JavaScript access)
   - `Secure`: ✓ (if using HTTPS)
   - `SameSite`: Strict

**Test B: JavaScript Cookie Access Test**
1. Login to the dashboard
2. Open Console (F12)
3. Try to access the session cookie:
   ```javascript
   document.cookie
   ```
4. **Expected Result:** The SECURE_SESSION cookie should NOT appear in the output due to HttpOnly flag

**Test C: Session Hijacking Prevention Test**
1. Login to the dashboard from Browser 1
2. Copy the session cookie value from Developer Tools
3. Open Browser 2 (or Incognito mode)
4. Try to manually set the cookie using Console:
   ```javascript
   document.cookie = "SECURE_SESSION=<copied_value>";
   ```
5. Navigate to dashboard.php
6. **Expected Result:** Access should be denied due to IP address mismatch (check `config.php:98`)

**Test D: Session Expiry Test**
1. Login to the dashboard
2. Note the "Session Expires In" time
3. Wait for the session to expire (default: 1 hour, or modify `SESSION_LIFETIME` in config.php to 60 seconds for testing)
4. Try to access dashboard.php after expiry
5. **Expected Result:** Redirect to login page with "Please login to access this page"

**Test E: Session Regeneration Test**
1. Login to dashboard
2. Open Developer Tools → Application → Cookies
3. Note the session ID
4. Stay logged in for 30 minutes
5. Check the session ID again
6. **Expected Result:** Session ID should be regenerated (see `config.php:57-59`)

**How to Verify:**
- Open `config.php:48-62` - See secure session configuration
- Open `config.php:95-106` - See session validation in `checkUserSession()`
- Open `login.php:63` - See session regeneration after login

---

### 5️⃣ HTTPS/SSL Support

**What it does:** Ensures the application works with HTTPS and marks cookies as secure.

#### Test Methods:

**Test A: Check Current SSL Status**
1. Login to the website
2. Look at the bottom of the login page
3. **Expected Result:** You'll see either:
   - 🔒 "HTTPS Enabled - Your connection is secure" (if SSL is configured)
   - ⚠️ "HTTPS Not Detected - Consider enabling SSL for production" (if not)

**Test B: Enable SSL on Your VM** (if not already enabled)

```bash
# On your VM
sudo a2enmod ssl
sudo a2ensite default-ssl
sudo systemctl restart apache2
```

**Test C: Self-Signed Certificate Setup** (for testing)

```bash
# Generate self-signed certificate
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/apache-selfsigned.key \
  -out /etc/ssl/certs/apache-selfsigned.crt

# Update Apache SSL config
sudo nano /etc/apache2/sites-available/default-ssl.conf
```

Add these lines:
```apache
SSLCertificateFile /etc/ssl/certs/apache-selfsigned.crt
SSLCertificateKeyFile /etc/ssl/private/apache-selfsigned.key
```

Restart Apache:
```bash
sudo systemctl restart apache2
```

**Test D: Access via HTTPS**
1. Navigate to: `https://172.19.12.158/index.php`
2. Accept the self-signed certificate warning (for testing only)
3. Login to the dashboard
4. **Expected Result:**
   - SSL indicator should show "HTTPS Enabled"
   - Session cookie should have `Secure` flag enabled

**Test E: Mixed Content Test**
1. Access the site via HTTPS
2. Open Developer Console (F12)
3. Check for mixed content warnings
4. **Expected Result:** No warnings (all resources loaded via HTTPS)

**Test F: Force HTTPS Redirect** (Optional Production Setup)

Add to `/var/www/html/.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**How to Verify:**
- Open `config.php:52` - See secure flag in session configuration
- Open `index.php:193-199` - See HTTPS detection and indicator
- Open `dashboard.php:196-200` - See HTTPS status display

---

## 🔍 Additional Security Tests

### Database Session Storage
Check if sessions are stored in database:

```bash
mysql -u root -p
```
```sql
USE secure_website;
SELECT * FROM user_sessions ORDER BY created_at DESC;
```

You should see active sessions with:
- user_id
- session_token
- ip_address
- user_agent
- expires_at
- is_active status

### Password Hashing Verification
Check password storage:

```sql
SELECT id, username, password FROM users WHERE username = 'demo';
```

**Expected Result:** Password should be a bcrypt hash starting with `$2y$`

---

## 📊 Testing Summary Checklist

Use this checklist to verify all security features:

- [ ] **Input Validation**: XSS attempts are blocked and sanitized
- [ ] **SQL Injection**: SQL injection attempts fail safely
- [ ] **CSRF Protection**: Forms require valid CSRF tokens
- [ ] **Session Security**: Sessions use HTTPOnly, Secure, SameSite flags
- [ ] **HTTPS Support**: Application works with SSL/TLS
- [ ] **Password Hashing**: Passwords stored as bcrypt hashes
- [ ] **Session Expiry**: Sessions expire after timeout
- [ ] **Session Regeneration**: Session IDs regenerate periodically
- [ ] **Database Sessions**: Sessions tracked in database
- [ ] **Secure Logout**: Sessions properly destroyed on logout

---

## 🛠️ Troubleshooting

### Database Connection Issues
If you get "Database connection failed":
1. Check MySQL is running: `sudo systemctl status mysql`
2. Verify credentials in `config.php`
3. Ensure database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### Permission Issues
If you get permission denied:
```bash
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

### Session Issues
If sessions don't work:
1. Check PHP session directory: `ls -la /var/lib/php/sessions/`
2. Ensure Apache can write: `sudo chown -R www-data:www-data /var/lib/php/sessions/`

### Enable Error Logging
For debugging, enable PHP errors:
```bash
sudo nano /etc/php/7.x/apache2/php.ini
```
Set:
```ini
display_errors = On
error_reporting = E_ALL
```
Then restart Apache: `sudo systemctl restart apache2`

---

## 📝 Testing Tools

### Browser Extensions
- **Firefox Developer Tools**: Built-in security testing
- **Chrome DevTools**: Built-in security analysis
- **OWASP ZAP**: Web application security scanner
- **Burp Suite Community**: Intercept and modify requests

### Command Line Tools
```bash
# Test SQL injection with sqlmap
sqlmap -u "http://172.19.12.158/login.php" --data="username=test&password=test" --level=5 --risk=3

# Test with curl
curl -X POST http://172.19.12.158/login.php \
  -d "username=admin&password=test" \
  -v

# Check SSL certificate
openssl s_client -connect 172.19.12.158:443 -showcerts
```

---

## 🎯 Quick Reference

**File Locations:**
- Login Page: `http://172.19.12.158/index.php`
- Dashboard: `http://172.19.12.158/dashboard.php`
- Logout: `http://172.19.12.158/logout.php`

**Demo Credentials:**
- Username: `demo`
- Password: `DemoPass123!`

**Key Files:**
- `config.php` - Security configuration and functions
- `login.php` - Authentication handler with all security features
- `dashboard.php` - Protected page demonstrating session security
- `database_setup.sql` - Database schema with security features

---

## 🚀 Next Steps

After testing all security features:

1. Review security code in each file
2. Understand how each feature works
3. Try breaking the security (ethical hacking)
4. Implement in your own projects
5. Stay updated with security best practices

**Remember:** Security is an ongoing process, not a one-time implementation!
