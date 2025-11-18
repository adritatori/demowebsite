# 🔒 Secure 3-Page Web Application
## Student Security Project - LAMP Stack

**VM IP:** 172.19.12.158
**Project:** 3-Page Website with 5 Security Features
**Stack:** Linux, Apache, MariaDB, PHP (LAMP)
**Testing:** Kali Linux + Burp Suite

---

## 📋 Project Overview

This project demonstrates **5 essential web security features** implemented in a functional 3-page website:

1. ✅ **Input Validation & Sanitization** - Prevents XSS attacks
2. ✅ **SQL Injection Protection** - PDO prepared statements
3. ✅ **CSRF Token Protection** - Prevents cross-site request forgery
4. ✅ **Secure Session Management** - HttpOnly cookies, session timeout, ID regeneration
5. ✅ **HTTPS/SSL Encryption** - Self-signed certificate for local environment

---

## 🌐 Website Structure

### Page 1: `index.php` - Home/Login Page
- Public-facing login form
- Lists all implemented security features
- Includes CSRF token protection
- Client-side and server-side validation

### Page 2: `dashboard.php` - Secure Dashboard
- Requires authentication to access
- Displays user account information
- Shows session security details
- Demonstrates all security features in action
- Real-time session timeout counter

### Page 3: `login.php` - Authentication Handler
- Processes login requests securely
- Implements all security features
- Logs activity to database
- Prevents brute force attacks (2-second delay on failure)

**Additional Pages:**
- `logout.php` - Secure logout handler
- `/adminer/` - Database management interface

---

## 🚀 Quick Start Guide

### 1️⃣ **Set Up Your VM**
Follow: **[VM_SETUP_CHECKLIST.md](VM_SETUP_CHECKLIST.md)**

Required steps:
- Create VM with Ubuntu 22.04 LTS
- Run `lamp_lab.sh` script
- Note your VM IP address

### 2️⃣ **Deploy the Website**
Follow: **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)**

Quick commands:
```bash
# On your VM
cd /tmp
git clone https://github.com/adritatori/demowebsite.git
cd demowebsite

# Deploy files
sudo rm -rf /var/www/html/*
sudo cp -r website/* /var/www/html/

# Setup database
mysql -u student -p studentdb < /var/www/html/setup_database.sql

# Set permissions
sudo chown -R www-data:www-data /var/www/html
sudo systemctl restart apache2
```

### 3️⃣ **Test Security Features**
Follow: **[TESTING_GUIDE.md](TESTING_GUIDE.md)**

Test each security feature manually before using Burp Suite.

### 4️⃣ **Enable HTTPS (Optional)**
```bash
chmod +x setup_https.sh
sudo ./setup_https.sh
```

---

## 📁 Project Structure

```
demowebsite/
├── README.md                       # This file
├── VM_SETUP_CHECKLIST.md          # VM setup guide
├── DEPLOYMENT_GUIDE.md            # Deployment instructions
├── TESTING_GUIDE.md               # Security testing guide
├── SECURITY_FEATURES_PLAN.md      # Detailed security implementation plan
├── CHANGELOG.md                   # Change tracking log
├── lamp_lab.sh                    # LAMP stack setup script
├── setup_https.sh                 # HTTPS/SSL setup script
└── website/                       # Website files (deploy to /var/www/html/)
    ├── index.php                  # Home page with login form
    ├── login.php                  # Login handler
    ├── dashboard.php              # Protected dashboard
    ├── logout.php                 # Logout handler
    ├── setup_database.sql         # Database schema
    ├── includes/
    │   ├── db.php                 # Database connection (PDO)
    │   ├── security.php           # Security functions
    │   └── session.php            # Session management
    └── assets/
        ├── style.css              # Styling
        └── script.js              # Client-side enhancements
```

---

## 🔐 Security Features Implementation

### Feature #1: Input Validation & Sanitization
**Location:** `website/includes/security.php`

**Functions:**
- `sanitize_input()` - Removes HTML tags and special characters
- `validate_email()` - Validates email format
- `validate_username()` - Validates username format (alphanumeric, 3-20 chars)
- `validate_password()` - Validates password strength
- `escape_output()` - Escapes output for safe HTML display

**Usage:**
```php
$username = sanitize_input($_POST['username']);
echo escape_output($username);
```

**Test:** Try entering `<script>alert('XSS')</script>` in the username field.

---

### Feature #2: SQL Injection Protection
**Location:** `website/includes/db.php`, `website/login.php`

**Implementation:**
```php
// Secure - PDO prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
```

**Why it's secure:**
- User input is NEVER concatenated into SQL queries
- PDO prepared statements use parameter binding
- `PDO::ATTR_EMULATE_PREPARES => false` ensures real prepared statements

**Test:** Try username: `' OR '1'='1` - login should fail.

---

### Feature #3: CSRF Token Protection
**Location:** `website/includes/security.php`, used in all forms

**Implementation:**
```php
// Generate token
$token = generate_csrf_token();

// In form
echo csrf_token_field();

// Validate on submission
if (!validate_csrf_token($_POST['csrf_token'])) {
    die("CSRF validation failed");
}
```

**How it works:**
- Unique token generated per session
- Token included in all forms as hidden field
- Server validates token on form submission
- Uses `hash_equals()` to prevent timing attacks

**Test:** Remove the `csrf_token` field using browser DevTools, then submit form.

---

### Feature #4: Secure Session Management
**Location:** `website/includes/session.php`

**Security measures:**
```php
// HttpOnly - prevents JavaScript access
ini_set('session.cookie_httponly', 1);

// SameSite - CSRF protection
ini_set('session.cookie_samesite', 'Strict');

// Secure - only send over HTTPS (after SSL setup)
ini_set('session.cookie_secure', 1);

// Session timeout - 30 minutes
ini_set('session.gc_maxlifetime', 1800);
```

**Features:**
- ✅ Session timeout after 30 minutes of inactivity
- ✅ Session ID regeneration every 5 minutes (prevents fixation)
- ✅ Session integrity validation (user agent check)
- ✅ Secure session destruction on logout

**Test:** Check cookie flags in browser DevTools → Application → Cookies.

---

### Feature #5: HTTPS with Self-Signed Certificate
**Location:** `setup_https.sh`

**What it does:**
- Generates self-signed SSL certificate (valid 365 days)
- Configures Apache for HTTPS on port 443
- Adds security headers (HSTS, X-Frame-Options, etc.)
- Optionally redirects HTTP to HTTPS

**Security headers added:**
```apache
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

**Test:** Access https://172.19.12.158/ and view certificate details.

---

## 🧪 Testing with Burp Suite

### Setup
1. Start Burp Suite on Kali Linux
2. Configure browser proxy (127.0.0.1:8080)
3. Add 172.19.12.158 to target scope

### Tests to Perform

#### 1. Spider/Crawl
Map all pages and endpoints automatically.

#### 2. SQL Injection Testing
- Use **Intruder** with SQL injection payloads
- Test login form with: `' OR '1'='1`, `admin'--`, `' UNION SELECT...`
- **Expected:** All attempts should fail

#### 3. XSS Testing
- Test username/password fields with XSS payloads
- Try: `<script>alert(1)</script>`, `<img src=x onerror=alert(1)>`
- **Expected:** Input sanitized, no execution

#### 4. CSRF Testing
- Intercept login POST request
- Remove or modify `csrf_token` parameter
- Replay request
- **Expected:** Request rejected with CSRF error

#### 5. Session Management Testing
- Test session fixation (session ID should regenerate after login)
- Test session timeout (should expire after 30 min)
- Try to access `/dashboard.php` without authentication
- **Expected:** Redirected to login

#### 6. HTTPS/SSL Testing
- Analyze SSL certificate
- Check for mixed content warnings
- Verify security headers present
- **Expected:** All traffic encrypted, headers present

---

## 📊 Demo Credentials

### Admin Account
- **Username:** `admin`
- **Password:** `Password123!`
- **Role:** Administrator

### User Account
- **Username:** `testuser`
- **Password:** `Password123!`
- **Role:** User

### Database Access (Adminer)
- **URL:** http://172.19.12.158/adminer/
- **System:** MySQL
- **Server:** localhost
- **Username:** student
- **Password:** Password123!
- **Database:** studentdb

---

## 📸 Required Screenshots

Capture screenshots of:

1. ✅ Login page showing all 5 security features listed
2. ✅ Successful login and dashboard view
3. ✅ Browser DevTools showing HttpOnly cookie flag
4. ✅ Page source showing CSRF token
5. ✅ Failed XSS attempt (input sanitized)
6. ✅ Failed SQL injection attempt
7. ✅ Failed login with invalid CSRF token
8. ✅ Session timeout message after 30 minutes
9. ✅ HTTPS connection with certificate details
10. ✅ Burp Suite showing intercepted requests
11. ✅ Security headers in Network tab
12. ✅ Database view in Adminer

---

## 📝 Documentation Files

| File | Purpose |
|------|---------|
| `README.md` | Main project documentation |
| `VM_SETUP_CHECKLIST.md` | Step-by-step VM setup guide |
| `DEPLOYMENT_GUIDE.md` | Website deployment instructions |
| `TESTING_GUIDE.md` | Detailed testing procedures for all 5 security features |
| `SECURITY_FEATURES_PLAN.md` | In-depth security implementation details |
| `CHANGELOG.md` | Track all changes made during the project |

---

## 🎯 Learning Outcomes

By completing this project, you will:

1. ✅ Understand common web vulnerabilities (XSS, SQL injection, CSRF)
2. ✅ Know how to implement secure coding practices in PHP
3. ✅ Learn session management best practices
4. ✅ Understand HTTPS/SSL and certificate management
5. ✅ Gain experience with security testing tools (Burp Suite)
6. ✅ Practice documenting security implementations
7. ✅ Learn to prove security through testing

---

## 🐛 Troubleshooting

### Website not loading?
```bash
sudo systemctl status apache2
sudo systemctl restart apache2
```

### Database errors?
```bash
mysql -u student -p studentdb
# Check if tables exist
```

### Permission errors?
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

### HTTPS not working?
```bash
sudo a2enmod ssl
sudo a2ensite default-ssl
sudo systemctl restart apache2
```

See **DEPLOYMENT_GUIDE.md** for detailed troubleshooting.

---

## 📚 Additional Resources

- **OWASP Top 10:** https://owasp.org/www-project-top-ten/
- **PHP Security Guide:** https://www.php.net/manual/en/security.php
- **Burp Suite Docs:** https://portswigger.net/burp/documentation
- **OWASP CSRF:** https://owasp.org/www-community/attacks/csrf
- **OWASP XSS:** https://owasp.org/www-community/attacks/xss/

---

## ✅ Project Checklist

- [ ] VM setup completed
- [ ] LAMP stack installed
- [ ] Website deployed to VM
- [ ] Database created and populated
- [ ] All 5 security features implemented
- [ ] Manual testing completed (all features)
- [ ] HTTPS enabled (optional)
- [ ] Burp Suite testing completed
- [ ] Screenshots captured for all tests
- [ ] CHANGELOG.md updated with all changes
- [ ] Final report prepared for instructor

---

## 📞 Support

If you encounter issues:

1. Check the relevant documentation file
2. Review troubleshooting section
3. Check Apache/PHP error logs
4. Verify file permissions
5. Test database connection

---

## 👨‍🎓 Academic Integrity

This project is for educational purposes. All security features are implemented following industry best practices and OWASP guidelines.

**Note:** The self-signed certificate is suitable for development/testing only. Production systems should use certificates from trusted Certificate Authorities.

---

**Project Status:** ✅ Complete and Ready for Testing

**Last Updated:** 2024-11-18

---

## 🎓 For Your Instructor

This project demonstrates:
- Secure coding practices in PHP
- Understanding of common web vulnerabilities
- Proper implementation of security controls
- Ability to test and validate security measures
- Professional documentation practices

All security features are production-ready and follow OWASP recommendations.

---

**Good luck with your project! 🔒🎓**
