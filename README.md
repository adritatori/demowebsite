# 🔒 Secure Website Demo

A demonstration of a secure 3-page PHP website implementing 5 critical security features.

## 📋 Features

This project demonstrates a complete secure web application with:

### 3-Page Website
- **index.php** - Login page with security features listed
- **dashboard.php** - Protected dashboard (requires authentication)
- **login.php** - Secure authentication handler

### 🛡️ 5 Security Features Implemented

1. **✅ Input Validation & Sanitization**
   - XSS attack prevention
   - Data integrity validation
   - Client and server-side validation

2. **✅ SQL Injection Protection**
   - PDO prepared statements
   - Parameter binding
   - No raw SQL queries

3. **✅ CSRF Token Protection**
   - Token generation and validation
   - Form-based protection
   - Token expiry management

4. **✅ Secure Session Management**
   - HTTPOnly cookies
   - Secure flag for HTTPS
   - SameSite attribute
   - Session regeneration
   - Database session tracking

5. **✅ HTTPS/SSL Support**
   - Configured for SSL/TLS
   - Secure cookie transmission
   - Mixed content prevention

## 🚀 Quick Setup

### 1. Copy Files to Web Server
```bash
sudo cp -r * /var/www/html/
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

### 2. Setup Database
```bash
mysql -u root -p < database_setup.sql
```

### 3. Configure Database Connection
Edit `config.php` with your MySQL credentials:
```php
define('DB_USER', 'root');         // Your MySQL username
define('DB_PASS', 'your_password'); // Your MySQL password
```

### 4. Access the Website
Navigate to: `http://your-server-ip/index.php`

**Demo Credentials:**
- Username: `demo`
- Password: `DemoPass123!`

## 📖 Documentation

- **[SECURITY_TESTING_GUIDE.md](SECURITY_TESTING_GUIDE.md)** - Comprehensive guide to test all 5 security features
- **[SECURITY_FEATURES_PLAN.md](SECURITY_FEATURES_PLAN.md)** - Detailed security implementation plan
- **[VM_SETUP_CHECKLIST.md](VM_SETUP_CHECKLIST.md)** - VM setup instructions

## 📁 File Structure

```
├── index.php              # Login page with security features
├── login.php              # Authentication handler
├── dashboard.php          # Protected dashboard
├── logout.php             # Secure logout handler
├── config.php             # Security configuration & functions
├── database_setup.sql     # Database schema
├── SECURITY_TESTING_GUIDE.md  # Testing instructions
└── README.md             # This file
```

## 🔍 Testing Security Features

See [SECURITY_TESTING_GUIDE.md](SECURITY_TESTING_GUIDE.md) for detailed testing instructions for each security feature.

### Quick Test Checklist

1. **Input Validation**: Try XSS payloads in login form
2. **SQL Injection**: Attempt SQL injection in username field
3. **CSRF Protection**: Submit form without CSRF token
4. **Session Security**: Inspect cookies in browser DevTools
5. **HTTPS Support**: Access via HTTPS and verify SSL status

## 🛠️ Technology Stack

- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Database
- **Apache 2.4+** - Web server
- **PDO** - Database abstraction layer
- **bcrypt** - Password hashing

## 📊 Database Schema

### Users Table
- id (Primary Key)
- username (Unique)
- password (bcrypt hashed)
- email
- created_at
- last_login

### User Sessions Table
- id (Primary Key)
- user_id (Foreign Key)
- session_token (Unique)
- ip_address
- user_agent
- created_at
- expires_at
- is_active

## 🔐 Security Best Practices Implemented

### Password Security
- Passwords hashed with `password_hash()` using bcrypt
- Minimum 8 characters required
- Never stored in plain text

### Session Security
- Session IDs regenerated every 30 minutes
- Session timeout after 1 hour
- IP address tracking for session validation
- Database-backed session storage

### Input Security
- All inputs validated and sanitized
- `htmlspecialchars()` for output encoding
- Regex validation for usernames
- Email validation with `filter_var()`

### Database Security
- PDO with prepared statements
- No dynamic SQL queries
- Parameter type binding
- Error logging without exposure

## ⚠️ Important Notes

### For Development
- The demo user password is intentionally simple for testing
- Error logging is enabled for debugging
- IP-based session validation can be strict

### For Production
- Change all database credentials
- Enable HTTPS/SSL
- Remove demo user or change password
- Disable error display
- Set appropriate session lifetimes
- Implement rate limiting
- Add logging and monitoring

## 🧪 Testing Tools

- Browser DevTools (F12)
- OWASP ZAP
- Burp Suite
- sqlmap (for SQL injection testing)
- curl (for request testing)

## 📝 Common Issues

### Database Connection Failed
- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `config.php`
- Ensure database exists

### Session Not Working
- Check PHP session directory permissions
- Verify Apache can write to session directory
- Check `session_save_path()`

### HTTPS Not Detected
- Enable SSL module: `sudo a2enmod ssl`
- Configure SSL certificate
- Restart Apache: `sudo systemctl restart apache2`

## 🎯 Learning Objectives

This project demonstrates:
- Secure coding practices in PHP
- OWASP Top 10 vulnerability prevention
- Session management best practices
- Database security with PDO
- CSRF protection implementation
- Input validation and sanitization
- HTTPS/SSL configuration

## 📚 Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Session Security](https://www.php.net/manual/en/session.security.php)

## 🤝 Contributing

This is a demo project for educational purposes. Feel free to:
- Report security issues
- Suggest improvements
- Add additional security features
- Create pull requests

## 📄 License

This project is open source and available for educational purposes.

## ⚡ Quick Commands

```bash
# Start MySQL
sudo systemctl start mysql

# Start Apache
sudo systemctl start apache2

# View Apache error logs
sudo tail -f /var/log/apache2/error.log

# View PHP error logs
sudo tail -f /var/log/apache2/error.log

# Check Apache status
sudo systemctl status apache2

# Restart services after configuration changes
sudo systemctl restart apache2
sudo systemctl restart mysql
```

## 🎉 Credits

Created as a comprehensive security demonstration project.

---

**Remember:** Security is not a feature, it's a mindset! 🔒
