# 🚀 Deployment Guide
## How to Deploy Your Secure Website to the VM

**VM IP:** 172.19.12.158

---

## 📋 Quick Start

Follow these steps to get your website running on the VM:

### Step 1: Access Your VM

```bash
# SSH into your VM (if SSH is set up)
ssh your_username@172.19.12.158

# OR use the VM console directly in VirtualBox/VMware
```

### Step 2: Transfer Files to VM

Choose one of these methods:

#### **Method A: Using Git (Recommended)**

On your VM:
```bash
# Install git if not already installed
sudo apt install -y git

# Clone the repository
cd /tmp
git clone https://github.com/adritatori/demowebsite.git
cd demowebsite

# Copy website files to web root
sudo rm -rf /var/www/html/*
sudo cp -r website/* /var/www/html/
```

#### **Method B: Using SCP from Your Host Machine**

From your host machine (where you have the files):
```bash
# Navigate to the demowebsite folder
cd /path/to/demowebsite

# Copy website folder to VM
scp -r website/* your_username@172.19.12.158:/tmp/website/

# Then on VM, move files:
# sudo mv /tmp/website/* /var/www/html/
```

#### **Method C: Manual Copy (VirtualBox Shared Folder)**

1. In VirtualBox: Settings → Shared Folders
2. Add folder pointing to your `demowebsite/website` directory
3. In VM, mount and copy:
```bash
sudo mount -t vboxsf shared_folder_name /mnt
sudo cp -r /mnt/* /var/www/html/
```

---

### Step 3: Setup Database

```bash
# Navigate to web root
cd /var/www/html

# Run database setup script
mysql -u student -p studentdb < setup_database.sql
# Password: Password123!

# Verify database was created
mysql -u student -p studentdb -e "SHOW TABLES; SELECT * FROM users;"
```

**Expected Output:**
```
+----------------------+
| Tables_in_studentdb  |
+----------------------+
| activity_log         |
| users               |
+----------------------+

+----+----------+-------------------+--------------------------------------------------------------+-------+
| id | username | email             | password_hash                                                | role  |
+----+----------+-------------------+--------------------------------------------------------------+-------+
|  1 | admin    | admin@example.com | $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi | admin |
|  2 | testuser | user@example.com  | $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi | user  |
+----+----------+-------------------+--------------------------------------------------------------+-------+
```

---

### Step 4: Set Correct Permissions

```bash
# Set ownership to Apache user
sudo chown -R www-data:www-data /var/www/html

# Set directory permissions
sudo find /var/www/html -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html -type f -exec chmod 644 {} \;

# Verify permissions
ls -la /var/www/html
```

---

### Step 5: Verify File Structure

```bash
cd /var/www/html
tree -L 2
# Or if tree is not installed:
ls -R
```

**Expected Structure:**
```
/var/www/html/
├── index.php
├── login.php
├── dashboard.php
├── logout.php
├── setup_database.sql
├── includes/
│   ├── db.php
│   ├── security.php
│   └── session.php
├── assets/
│   ├── style.css
│   └── script.js
└── adminer/
    └── index.php
```

---

### Step 6: Restart Apache

```bash
sudo systemctl restart apache2
sudo systemctl status apache2
```

**Expected:** Should show `active (running)` in green.

---

### Step 7: Test the Website

1. Open browser on your host machine
2. Go to: **http://172.19.12.158/**
3. You should see the secure login page

**Test Login:**
- Username: `admin`
- Password: `Password123!`

If successful, you'll be redirected to the dashboard!

---

## 🔐 Optional: Enable HTTPS (Security Feature #5)

### Step 1: Copy HTTPS Setup Script

```bash
# Make sure setup_https.sh is in a accessible location
cd /path/to/demowebsite
chmod +x setup_https.sh
```

If you used git to clone, the script is already there. If not, copy it:
```bash
# From your host machine
scp setup_https.sh your_username@172.19.12.158:/tmp/
```

### Step 2: Run HTTPS Setup

On your VM:
```bash
cd /tmp
sudo ./setup_https.sh
```

Answer `Y` when asked about redirecting HTTP to HTTPS.

### Step 3: Enable Secure Cookie Flag

```bash
sudo nano /var/www/html/includes/session.php
```

Find line ~23 and uncomment:
```php
ini_set('session.cookie_secure', 1);
```

Save: `Ctrl+O`, `Enter`, `Ctrl+X`

### Step 4: Test HTTPS

1. Open browser
2. Go to: **https://172.19.12.158/**
3. Accept the self-signed certificate warning
4. Website should load via HTTPS!

---

## ✅ Verification Checklist

After deployment, verify:

- [ ] Website loads at http://172.19.12.158/
- [ ] Login page displays correctly with all styling
- [ ] Can see the 5 security features listed on the home page
- [ ] Can login with `admin` / `Password123!`
- [ ] Dashboard loads and shows user information
- [ ] Session timeout countdown is visible
- [ ] Can logout successfully
- [ ] Can login with `testuser` / `Password123!`
- [ ] Adminer accessible at http://172.19.12.158/adminer/
- [ ] Database connection works (green checkmark on homepage)

### Database Verification
- [ ] `users` table exists with 2 users
- [ ] `activity_log` table exists
- [ ] Can query users table via Adminer

### HTTPS Verification (if enabled)
- [ ] Can access https://172.19.12.158/
- [ ] HTTP redirects to HTTPS
- [ ] Certificate details viewable in browser
- [ ] Cookie has "Secure" flag set

---

## 🐛 Troubleshooting

### Problem: "Database connection failed"

**Solution:**
```bash
# Check if MariaDB is running
sudo systemctl status mariadb
sudo systemctl restart mariadb

# Test database connection manually
mysql -u student -p
# Password: Password123!

# If connection works, check credentials in includes/db.php
sudo nano /var/www/html/includes/db.php
```

### Problem: "Permission denied" errors

**Solution:**
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo systemctl restart apache2
```

### Problem: Page shows PHP code instead of rendering

**Solution:**
```bash
# Ensure PHP is installed and enabled
sudo apt install -y php php-mysql
sudo systemctl restart apache2

# Check if .php files are being processed
sudo nano /etc/apache2/mods-enabled/php*.conf
```

### Problem: Adminer not working

**Solution:**
```bash
# Re-download Adminer
cd /var/www/html
sudo mkdir -p adminer
sudo curl -fsSL -o adminer/index.php "https://www.adminer.org/latest.php"
sudo chown -R www-data:www-data adminer
```

### Problem: CSS/JS not loading

**Solution:**
```bash
# Check if files exist
ls -la /var/www/html/assets/

# Check Apache error log
sudo tail -f /var/log/apache2/error.log

# Ensure mod_rewrite is enabled
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Problem: HTTPS not working

**Solution:**
```bash
# Check if SSL module is enabled
sudo a2enmod ssl
sudo a2enmod headers

# Check if SSL site is enabled
sudo a2ensite default-ssl

# Check if port 443 is open
sudo ufw status
sudo ufw allow 443/tcp

# Restart Apache
sudo systemctl restart apache2

# Check SSL error log
sudo tail -f /var/log/apache2/ssl_error.log
```

---

## 📝 Post-Deployment Tasks

### Update CHANGELOG.md

Document your deployment:
```markdown
### [2024-XX-XX HH:MM] - Website Deployed to VM
- **Type**: Deployment
- **Description**: Deployed all website files to VM at 172.19.12.158
- **Reason**: Complete security features implementation
- **Files**: All website files copied to /var/www/html/
- **Tested**: Yes - All pages load correctly
- **Notes**: Database setup successful, 2 test users created
```

### Test All Security Features

Follow the **TESTING_GUIDE.md** to test all 5 security features.

### Take Screenshots

Capture screenshots of:
1. Login page
2. Dashboard page
3. Browser DevTools showing cookies
4. Page source showing CSRF token
5. HTTPS certificate (if enabled)
6. Failed XSS/SQL injection attempts
7. Adminer database view

### Prepare for Burp Suite Testing

On your Kali Linux machine:
1. Start Burp Suite
2. Configure browser proxy
3. Set target scope to 172.19.12.158
4. Begin security testing

---

## 📚 Next Steps

1. ✅ Deploy website (you're here!)
2. ✅ Test all security features manually (see TESTING_GUIDE.md)
3. ✅ Enable HTTPS (optional but recommended)
4. ✅ Test with Burp Suite from Kali Linux
5. ✅ Document all findings with screenshots
6. ✅ Prepare report for instructor

---

## 📞 Need Help?

If you encounter issues:

1. Check Apache error logs:
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

2. Check PHP errors:
   ```bash
   sudo tail -f /var/log/apache2/error.log | grep PHP
   ```

3. Test database connection:
   ```bash
   mysql -u student -p studentdb
   ```

4. Verify file permissions:
   ```bash
   ls -la /var/www/html/
   ```

---

**Happy Deploying! 🚀🔒**
