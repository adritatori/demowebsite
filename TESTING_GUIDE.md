# 🔍 Security Testing Guide
## How to Test All 5 Security Features from Your Local Environment

**VM IP:** 172.19.12.158
**Before testing with Burp Suite, you can test manually using these methods:**

---

## 🛠️ Setup Instructions

### 1. Copy Website Files to VM

On your VM, run:
```bash
# Navigate to web root
cd /var/www/html

# Backup existing files
sudo mkdir -p /var/www/html_backup
sudo mv * /var/www/html_backup/ 2>/dev/null || true

# Copy all website files here (from the 'website' folder in the repo)
# You can use SCP, Git, or copy manually
```

### 2. Setup Database

```bash
# On your VM, run:
cd /var/www/html
mysql -u student -p studentdb < setup_database.sql
# Password: Password123!
```

### 3. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo systemctl restart apache2
```

### 4. Verify Website is Working

Open browser and go to: **http://172.19.12.158/**

You should see the login page with security features listed.

---

## 🔒 Security Feature #1: Input Validation & Sanitization

### What It Does
Prevents XSS (Cross-Site Scripting) attacks by sanitizing user input and escaping output.

### How to Test Manually (Without Burp Suite)

#### Test 1: XSS Attempt in Login Form
1. Go to http://172.19.12.158/
2. In the **username** field, enter: `<script>alert('XSS')</script>`
3. Enter any password and submit
4. **Expected Result:**
   - Login fails (invalid credentials)
   - No JavaScript alert pops up
   - If you login successfully with valid credentials, the username is displayed safely (HTML escaped)

#### Test 2: HTML Injection Attempt
1. Try username: `<h1>HACKED</h1>`
2. **Expected Result:** The HTML tags are escaped and displayed as plain text, not rendered as HTML

#### Test 3: View Page Source
1. Login with valid credentials (username: `admin`, password: `Password123!`)
2. On the dashboard, right-click → "View Page Source"
3. Search for your username in the source
4. **Expected Result:** You'll see `htmlspecialchars()` has converted special characters:
   - `<` becomes `&lt;`
   - `>` becomes `&gt;`
   - `"` becomes `&quot;`

### How to Test with Browser DevTools

1. Open browser DevTools (F12)
2. Go to **Console** tab
3. Type: `securityTest.testXSS()`
4. Follow the instructions shown

### Code Location
- **Sanitization:** `includes/security.php` lines 15-56
- **Usage:** `index.php`, `login.php`, `dashboard.php`

---

## 🔒 Security Feature #2: SQL Injection Protection

### What It Does
Uses PDO prepared statements to prevent SQL injection attacks.

### How to Test Manually

#### Test 1: Classic SQL Injection
1. Go to http://172.19.12.158/
2. Username: `' OR '1'='1`
3. Password: `anything`
4. Click Login
5. **Expected Result:** Login fails with "Invalid username or password" message

#### Test 2: Comment-Based Injection
1. Username: `admin'--`
2. Password: `anything`
3. **Expected Result:** Login fails

#### Test 3: UNION-Based Injection
1. Username: `' UNION SELECT NULL, NULL, NULL--`
2. Password: `anything`
3. **Expected Result:** Login fails (or invalid username format error)

### How to Verify (Check the Code)
1. Open `login.php` in a text editor
2. Look for lines 47-50:
```php
$stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
```
3. **Why it's secure:** The `?` placeholder prevents SQL injection because user input is never concatenated into the SQL query

### How to Test with Browser Console
```javascript
securityTest.testSQLi()
```

### Code Location
- **Database Connection:** `includes/db.php` lines 17-25
- **Usage:** `login.php` lines 47-50, `dashboard.php`

---

## 🔒 Security Feature #3: CSRF Token Protection

### What It Does
Prevents Cross-Site Request Forgery by validating tokens on form submissions.

### How to Test Manually

#### Test 1: View CSRF Token
1. Go to http://172.19.12.158/
2. Right-click on the page → "View Page Source"
3. Search for: `csrf_token`
4. **Expected Result:** You'll see a hidden input field like:
   ```html
   <input type="hidden" name="csrf_token" value="a1b2c3d4e5f6...">
   ```

#### Test 2: Remove CSRF Token with DevTools
1. Go to http://172.19.12.158/
2. Open DevTools (F12) → **Elements** tab
3. Find the login form
4. Find the `<input type="hidden" name="csrf_token"...>` line
5. Right-click → Delete Element
6. Enter valid credentials: `admin` / `Password123!`
7. Submit the form
8. **Expected Result:**
   - You get redirected to index with error: "Security token validation failed"
   - Login is rejected

#### Test 3: Modify CSRF Token Value
1. Go to http://172.19.12.158/
2. Open DevTools (F12) → **Elements** tab
3. Find the CSRF token input
4. Double-click on the value and change it to: `invalid_token_12345`
5. Submit the form with valid credentials
6. **Expected Result:** Login is rejected due to invalid CSRF token

### How to Test with Browser Console
```javascript
securityTest.testCSRF()
```

### Code Location
- **Token Generation:** `includes/security.php` lines 66-78
- **Token Validation:** `includes/security.php` lines 83-93
- **Usage:** `login.php` line 31

---

## 🔒 Security Feature #4: Secure Session Management

### What It Does
- Uses HttpOnly cookies (prevents JavaScript access)
- Implements session timeout (30 minutes of inactivity)
- Regenerates session ID to prevent session fixation
- Validates session integrity

### How to Test Manually

#### Test 1: Check Cookie Security Flags
1. Go to http://172.19.12.158/
2. Login with: `admin` / `Password123!`
3. Open DevTools (F12) → **Application** tab (Chrome) or **Storage** tab (Firefox)
4. Click on **Cookies** → `http://172.19.12.158`
5. Find the `PHPSESSID` cookie
6. **Expected Result:** You should see:
   - `HttpOnly` ✓ (checked)
   - `SameSite`: `Strict`
   - (After HTTPS setup) `Secure` ✓

#### Test 2: Try to Access Cookie with JavaScript
1. Login to the dashboard
2. Open DevTools Console
3. Type: `document.cookie`
4. **Expected Result:**
   - You might see an empty string or cookies without PHPSESSID
   - This is because HttpOnly prevents JavaScript access (good!)

#### Test 3: Session Timeout
1. Login to the dashboard
2. Note the "Time Until Timeout" counter
3. Wait and watch it count down from 30:00 minutes
4. **To test faster:** Modify `includes/session.php` line 28, change `1800` to `60` (1 minute)
5. Wait 1 minute without any activity
6. Try to refresh the page or click anything
7. **Expected Result:** You're logged out and redirected to login page with "session timeout" message

#### Test 4: Session Regeneration
1. Login to dashboard
2. Open DevTools → Application → Cookies
3. Note the `PHPSESSID` value (e.g., `abc123...`)
4. Wait 5 minutes (session ID regenerates every 5 minutes)
5. Refresh the page
6. Check the cookie again
7. **Expected Result:** The `PHPSESSID` value has changed (session ID regenerated)

### How to View Session Info
Once logged in, the dashboard shows:
- Session ID
- Session created time
- Last activity time
- Time remaining until timeout

### Code Location
- **Session Configuration:** `includes/session.php` lines 14-30
- **Session Start:** `includes/session.php` lines 35-62
- **Session Validation:** `includes/session.php` lines 67-90

---

## 🔒 Security Feature #5: HTTPS with Self-Signed Certificate

### How to Set It Up

#### Step 1: Run the HTTPS Setup Script
On your VM:
```bash
chmod +x setup_https.sh
sudo ./setup_https.sh
```

Follow the prompts. When asked about redirecting HTTP to HTTPS, choose **Y** (Yes).

#### Step 2: Open Firewall Port (if UFW is enabled)
```bash
sudo ufw allow 443/tcp
sudo ufw status
```

#### Step 3: Enable Secure Cookie Flag
Edit `includes/session.php`:
```bash
sudo nano /var/www/html/includes/session.php
```

Find line 23 (around there) and **uncomment** it:
```php
// Before:
// ini_set('session.cookie_secure', 1);

// After:
ini_set('session.cookie_secure', 1);
```

Save (Ctrl+O, Enter, Ctrl+X).

### How to Test Manually

#### Test 1: Access via HTTPS
1. Open browser and go to: **https://172.19.12.158/**
2. **Expected Result:**
   - Browser shows a security warning: "Your connection is not private" or "Certificate not trusted"
   - This is NORMAL for self-signed certificates
3. Click **Advanced** → **Proceed to 172.19.12.158 (unsafe)** or **Accept the Risk**
4. You should now see the website loaded via HTTPS

#### Test 2: View Certificate Details
1. In the address bar, click the padlock icon (⚠️ or 🔒)
2. Click **Certificate** or **Certificate Information**
3. **Expected Result:** You should see:
   - Issued to: `172.19.12.158`
   - Issued by: `172.19.12.158` (self-signed)
   - Valid for: 365 days
   - Organization: `Student Project`

#### Test 3: Check HTTPS Enforcement
1. Try to access: http://172.19.12.158/ (HTTP, not HTTPS)
2. **Expected Result:**
   - You're automatically redirected to https://172.19.12.158/
   - The browser URL changes from `http://` to `https://`

#### Test 4: Verify Secure Cookie Flag
1. Access site via HTTPS: https://172.19.12.158/
2. Login with credentials
3. Open DevTools → Application → Cookies
4. Find `PHPSESSID` cookie
5. **Expected Result:**
   - `Secure` flag is checked ✓
   - Cookie only sent over HTTPS connections

#### Test 5: Check Security Headers
1. While on the HTTPS site, open DevTools
2. Go to **Network** tab
3. Refresh the page
4. Click on the first request (the HTML document)
5. Scroll down to **Response Headers**
6. **Expected Result:** You should see these security headers:
   - `Strict-Transport-Security: max-age=31536000; includeSubDomains`
   - `X-Frame-Options: SAMEORIGIN`
   - `X-Content-Type-Options: nosniff`
   - `X-XSS-Protection: 1; mode=block`

### How to Test with Browser Console
```javascript
securityTest.testHTTPS()
```

### Code Location
- **HTTPS Setup Script:** `setup_https.sh`
- **Apache SSL Config:** `/etc/apache2/sites-available/default-ssl.conf`
- **Certificate:** `/etc/ssl/certs/apache-selfsigned.crt`

---

## 📊 Complete Testing Checklist

Before using Burp Suite, complete this checklist:

### Security Feature #1: Input Validation
- [ ] Tested XSS injection in username field
- [ ] Tested HTML injection
- [ ] Verified output is escaped in page source
- [ ] Took screenshot of failed XSS attempt

### Security Feature #2: SQL Injection Protection
- [ ] Tested `' OR '1'='1` injection
- [ ] Tested `admin'--` injection
- [ ] Tested UNION-based injection
- [ ] Verified code uses PDO prepared statements
- [ ] Took screenshot of failed SQL injection attempt

### Security Feature #3: CSRF Protection
- [ ] Located CSRF token in page source
- [ ] Tested form submission without token (removed via DevTools)
- [ ] Tested form submission with invalid token
- [ ] Verified request was rejected
- [ ] Took screenshot showing CSRF token in source

### Security Feature #4: Session Management
- [ ] Verified HttpOnly flag on cookie
- [ ] Verified SameSite=Strict on cookie
- [ ] Tested session timeout (waited 30 min or modified timeout value)
- [ ] Verified cannot access cookie via JavaScript
- [ ] Took screenshot of cookie security flags in DevTools

### Security Feature #5: HTTPS/SSL
- [ ] Ran setup_https.sh script successfully
- [ ] Accessed site via https://172.19.12.158/
- [ ] Viewed and verified certificate details
- [ ] Verified HTTP redirects to HTTPS
- [ ] Checked security headers in Network tab
- [ ] Verified Secure flag on cookies
- [ ] Took screenshot of certificate and HTTPS connection

---

## 🎯 Advanced Testing with Burp Suite (Next Step)

Once you've completed manual testing above, you can use Burp Suite from Kali Linux for more advanced testing:

### Setup Burp Suite
1. On Kali Linux, start Burp Suite
2. Configure your browser to use Burp proxy (127.0.0.1:8080)
3. Set target scope to: 172.19.12.158

### What to Test with Burp Suite
1. **Intercept Requests** - View all HTTP/HTTPS traffic
2. **Modify Requests** - Change POST data, headers, tokens
3. **Automated Scanning** - Let Burp scan for vulnerabilities
4. **Intruder** - Automated SQL injection and XSS testing
5. **Repeater** - Replay and modify specific requests

### Recommended Burp Suite Tests
- **Spider/Crawl** the website to map all pages
- **Active Scan** to automatically detect vulnerabilities
- **Intruder** attack on login form (test SQL injection patterns)
- **Repeater** to manually test CSRF token validation
- **Proxy → HTTP History** to analyze all requests/responses

---

## 📸 Documentation Requirements

For each security feature, document:

1. **What you tested**
2. **How you tested it** (steps)
3. **Screenshot of the test**
4. **Expected result**
5. **Actual result**
6. **Code reference** (which file/line implements the security)

### Screenshot Checklist
- [ ] Login page showing security features list
- [ ] Page source showing CSRF token
- [ ] Browser DevTools showing cookie security flags
- [ ] Certificate details from browser
- [ ] Failed XSS attempt (sanitized input)
- [ ] Failed SQL injection attempt
- [ ] Failed CSRF attempt (token removed)
- [ ] Session timeout message
- [ ] HTTPS connection with security headers
- [ ] Burp Suite showing intercepted requests

---

## 🎓 Explaining to Your Instructor

### For Each Security Feature, Be Ready to Explain:

1. **What is the vulnerability?** (XSS, SQLi, CSRF, etc.)
2. **How does your code prevent it?** (specific functions/methods)
3. **Where in the code is it implemented?** (file and line numbers)
4. **How did you test it?** (manual testing + Burp Suite)
5. **What would happen without this protection?** (demonstrate the attack)

---

## ❓ Troubleshooting

### Website Not Loading
```bash
sudo systemctl status apache2
sudo systemctl restart apache2
sudo tail -f /var/log/apache2/error.log
```

### Database Errors
```bash
mysql -u student -p
# Password: Password123!
USE studentdb;
SHOW TABLES;
SELECT * FROM users;
```

### Permission Errors
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

### HTTPS Not Working
```bash
sudo a2enmod ssl
sudo a2ensite default-ssl
sudo systemctl restart apache2
sudo ufw allow 443/tcp
```

---

## 📚 Additional Resources

- OWASP Top 10: https://owasp.org/www-project-top-ten/
- PHP Security Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html
- Burp Suite Documentation: https://portswigger.net/burp/documentation

---

**Good luck with your testing! 🔒🎓**
