# Security Features Implementation Plan

## Project Overview
- **Objective**: Create a 3-page website with 5 security features
- **Technology Stack**: HTML, CSS, JavaScript, PHP, MariaDB
- **Testing Tools**: Kali Linux, Burp Suite

---

## 🌐 Website Structure

### Page 1: Home/Landing Page (index.php)
- Public-facing page
- Login form
- Basic information about the site

### Page 2: Login/Authentication (login.php)
- User authentication
- Session creation
- CSRF protection

### Page 3: Dashboard/Secure Area (dashboard.php)
- Requires authentication
- Displays user-specific data
- Role-based access control

---

## 🔒 5 Security Features to Implement

### 1️⃣ Input Validation & Sanitization
**Implementation Details**:
- Validate all user inputs (email, username, passwords)
- Sanitize HTML output to prevent XSS
- Use PHP `filter_var()`, `htmlspecialchars()`, `strip_tags()`

**Code Example**:
```php
// Sanitize input
$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email format");
}

// Escape output
echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
```

**Burp Suite Test**:
- Try injecting: `<script>alert('XSS')</script>`
- Try injecting: `<img src=x onerror=alert('XSS')>`
- Expected: Input should be sanitized/rejected

---

### 2️⃣ SQL Injection Protection (PDO Prepared Statements)
**Implementation Details**:
- Use PDO with prepared statements for ALL database queries
- Never concatenate user input into SQL queries
- Use parameter binding

**Code Example**:
```php
// SECURE - Using prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND email = ?");
$stmt->execute([$username, $email]);

// INSECURE - DO NOT USE
// $query = "SELECT * FROM users WHERE username = '$username'";
```

**Burp Suite Test**:
- Try SQL injection: `' OR '1'='1`
- Try: `admin'--`
- Try: `' UNION SELECT NULL, NULL--`
- Expected: All attempts should fail

---

### 3️⃣ CSRF (Cross-Site Request Forgery) Protection
**Implementation Details**:
- Generate unique token for each session
- Include token in all forms
- Validate token on form submission

**Code Example**:
```php
// Generate CSRF token
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In form
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Validate on submit
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("CSRF token validation failed");
}
```

**Burp Suite Test**:
- Intercept form submission
- Remove or modify CSRF token
- Expected: Request should be rejected

---

### 4️⃣ Secure Session Management
**Implementation Details**:
- Use httpOnly and secure flags for cookies
- Regenerate session ID after login
- Implement session timeout
- Proper logout functionality

**Code Example**:
```php
// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // For HTTPS
ini_set('session.use_only_cookies', 1);
session_start();

// Regenerate session ID on login
session_regenerate_id(true);

// Set session timeout (30 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();
```

**Burp Suite Test**:
- Check cookie flags (httpOnly, secure)
- Test session timeout
- Try session hijacking
- Expected: Cookies should have security flags set

---

### 5️⃣ HTTPS with Self-Signed Certificate
**Implementation Details**:
- Generate self-signed SSL certificate
- Configure Apache to use HTTPS
- Redirect HTTP to HTTPS

**Setup Commands**:
```bash
# Generate self-signed certificate
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/apache-selfsigned.key \
  -out /etc/ssl/certs/apache-selfsigned.crt

# Enable SSL module
sudo a2enmod ssl
sudo a2ensite default-ssl
sudo systemctl restart apache2
```

**Apache Configuration** (`/etc/apache2/sites-available/default-ssl.conf`):
```apache
<VirtualHost *:443>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/apache-selfsigned.crt
    SSLCertificateKeyFile /etc/ssl/private/apache-selfsigned.key
</VirtualHost>
```

**Burp Suite Test**:
- Access site via HTTPS
- Inspect certificate details
- Check for HTTP to HTTPS redirect
- Analyze encrypted traffic
- Expected: All traffic encrypted, certificate visible

---

## 🧪 Burp Suite Testing Checklist

### Setup Burp Suite on Kali
- [ ] Start Burp Suite
- [ ] Configure browser proxy (127.0.0.1:8080)
- [ ] Import Burp's CA certificate
- [ ] Configure VM as target

### Test Security Feature #1: Input Validation
- [ ] Test XSS in username field
- [ ] Test XSS in email field
- [ ] Test HTML injection
- [ ] Document results with screenshots

### Test Security Feature #2: SQL Injection
- [ ] Test basic SQL injection (`' OR '1'='1`)
- [ ] Test comment-based injection (`admin'--`)
- [ ] Test UNION-based injection
- [ ] Document results with screenshots

### Test Security Feature #3: CSRF Protection
- [ ] Intercept form submission
- [ ] Modify CSRF token
- [ ] Remove CSRF token
- [ ] Replay request
- [ ] Document results with screenshots

### Test Security Feature #4: Session Management
- [ ] Check cookie security flags
- [ ] Test session timeout
- [ ] Test session fixation
- [ ] Test concurrent sessions
- [ ] Document results with screenshots

### Test Security Feature #5: HTTPS
- [ ] Verify HTTPS connection
- [ ] Inspect certificate
- [ ] Test HTTP to HTTPS redirect
- [ ] Analyze encrypted traffic
- [ ] Document results with screenshots

---

## 📊 Testing Documentation Template

For each security feature, document:

### Feature Name: _______________

**Test Date**: _______________
**Tester**: _______________

**Test Case 1**: _______________
- Attack Attempted: _______________
- Expected Result: _______________
- Actual Result: _______________
- Screenshot: _______________
- Status: [ ] Pass [ ] Fail

**Test Case 2**: _______________
- Attack Attempted: _______________
- Expected Result: _______________
- Actual Result: _______________
- Screenshot: _______________
- Status: [ ] Pass [ ] Fail

---

## 📁 Project File Structure

```
/var/www/html/
├── index.php           (Home page with login form)
├── login.php           (Login handler)
├── dashboard.php       (Protected page)
├── logout.php          (Logout handler)
├── includes/
│   ├── db.php         (Database connection)
│   ├── security.php   (Security functions)
│   └── session.php    (Session management)
├── assets/
│   ├── style.css      (Styling)
│   └── script.js      (JavaScript)
└── adminer/           (Database management)
```

---

## ✅ Implementation Checklist

- [ ] Database schema created with users table
- [ ] Input validation functions created
- [ ] PDO prepared statements implemented
- [ ] CSRF token system implemented
- [ ] Secure session management configured
- [ ] HTTPS certificate generated and configured
- [ ] All 3 pages created and functional
- [ ] All 5 security features tested with Burp Suite
- [ ] Documentation completed with screenshots
- [ ] Final report prepared for instructor

---

**Notes**:
- Keep all Burp Suite screenshots organized by security feature
- Document any issues encountered during implementation
- Note any additional security measures you added
- Record all configuration changes made to Apache/PHP
