#!/bin/bash

##############################################
# Secure Website Demo - Installation Script
##############################################

echo "=========================================="
echo "  Secure Website Demo - Installation"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "⚠️  Please run as root or with sudo"
    echo "Usage: sudo bash install.sh"
    exit 1
fi

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Check dependencies
echo -e "${YELLOW}[1/6] Checking dependencies...${NC}"

# Check Apache
if ! command -v apache2 &> /dev/null; then
    echo -e "${RED}❌ Apache2 is not installed${NC}"
    echo "Install with: sudo apt install apache2"
    exit 1
fi
echo -e "${GREEN}✓ Apache2 found${NC}"

# Check MySQL
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}❌ MySQL is not installed${NC}"
    echo "Install with: sudo apt install mysql-server"
    exit 1
fi
echo -e "${GREEN}✓ MySQL found${NC}"

# Check PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP is not installed${NC}"
    echo "Install with: sudo apt install php libapache2-mod-php php-mysql"
    exit 1
fi
echo -e "${GREEN}✓ PHP found${NC}"

echo ""

# Step 2: Copy files
echo -e "${YELLOW}[2/6] Copying files to /var/www/html/...${NC}"

# Create backup of existing files
if [ -d "/var/www/html" ]; then
    BACKUP_DIR="/var/www/html_backup_$(date +%Y%m%d_%H%M%S)"
    echo "Creating backup at: $BACKUP_DIR"
    cp -r /var/www/html "$BACKUP_DIR"
fi

# Copy website files
cp -f index.php /var/www/html/
cp -f login.php /var/www/html/
cp -f dashboard.php /var/www/html/
cp -f logout.php /var/www/html/
cp -f config.php /var/www/html/
cp -f database_setup.sql /var/www/html/

echo -e "${GREEN}✓ Files copied${NC}"
echo ""

# Step 3: Set permissions
echo -e "${YELLOW}[3/6] Setting file permissions...${NC}"
chown -R www-data:www-data /var/www/html/
chmod -R 755 /var/www/html/
chmod 644 /var/www/html/*.php
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# Step 4: Setup database
echo -e "${YELLOW}[4/6] Setting up database...${NC}"
echo "Please enter your MySQL root password:"
read -s MYSQL_ROOT_PASSWORD

# Test MySQL connection
if ! mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1;" &> /dev/null; then
    echo -e "${RED}❌ Failed to connect to MySQL. Check your password.${NC}"
    exit 1
fi

# Create database and tables
mysql -u root -p"$MYSQL_ROOT_PASSWORD" < database_setup.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database created successfully${NC}"
else
    echo -e "${RED}❌ Failed to create database${NC}"
    exit 1
fi
echo ""

# Step 5: Configure database credentials
echo -e "${YELLOW}[5/6] Configuring database credentials...${NC}"
echo "Do you want to use root user for the website? (y/n)"
read USE_ROOT

if [ "$USE_ROOT" = "y" ]; then
    DB_USER="root"
    DB_PASS="$MYSQL_ROOT_PASSWORD"
else
    echo "Enter database username (will be created):"
    read DB_USER
    echo "Enter database password:"
    read -s DB_PASS

    # Create database user
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "GRANT SELECT, INSERT, UPDATE, DELETE ON secure_website.* TO '${DB_USER}'@'localhost';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"
    echo -e "${GREEN}✓ Database user created${NC}"
fi

# Update config.php with credentials
sed -i "s/define('DB_USER', 'root');/define('DB_USER', '${DB_USER}');/" /var/www/html/config.php
sed -i "s/define('DB_PASS', '');/define('DB_PASS', '${DB_PASS}');/" /var/www/html/config.php

echo -e "${GREEN}✓ Configuration updated${NC}"
echo ""

# Step 6: Restart Apache
echo -e "${YELLOW}[6/6] Restarting Apache...${NC}"
systemctl restart apache2

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Apache restarted${NC}"
else
    echo -e "${RED}❌ Failed to restart Apache${NC}"
    exit 1
fi
echo ""

# Get server IP
SERVER_IP=$(hostname -I | awk '{print $1}')

# Installation complete
echo "=========================================="
echo -e "${GREEN}✅ Installation Complete!${NC}"
echo "=========================================="
echo ""
echo "🌐 Access your website at:"
echo "   http://${SERVER_IP}/index.php"
echo ""
echo "🔑 Demo Credentials:"
echo "   Username: demo"
echo "   Password: DemoPass123!"
echo ""
echo "📚 Documentation:"
echo "   - README.md - Overview and quick start"
echo "   - SECURITY_TESTING_GUIDE.md - Test all 5 security features"
echo ""
echo "🔒 Security Features Implemented:"
echo "   ✓ Input Validation & Sanitization"
echo "   ✓ SQL Injection Protection (PDO)"
echo "   ✓ CSRF Token Protection"
echo "   ✓ Secure Session Management"
echo "   ✓ HTTPS/SSL Support"
echo ""
echo "⚠️  Note: For production use:"
echo "   1. Enable HTTPS/SSL"
echo "   2. Change demo user password"
echo "   3. Review security settings in config.php"
echo ""
echo "Happy testing! 🚀"
echo "=========================================="
