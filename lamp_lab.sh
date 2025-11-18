#!/usr/bin/env bash

set -euo pipefail



#############################

# Configurable variables

#############################

APP_ROOT="/var/www/html"

DB_NAME="studentdb"

DB_USER="student"

DB_PASS="Password123!"       # <- change if you want

TIMEZONE="Australia/Sydney"  # <- change if needed



echo "[*] Starting LAMP lab provisioning..."



#############################

# Apt & base packages

#############################

export DEBIAN_FRONTEND=noninteractive

sudo apt update -y

sudo apt upgrade -y

sudo apt install -y apache2 mariadb-server \

  php php-cli php-mysql php-xml php-mbstring php-curl php-zip php-gd \

  curl unzip git ufw crudini



# Set PHP timezone (CLI + Apache)

sudo crudini --set /etc/php/*/apache2/php.ini Date date.timezone "$TIMEZONE"

sudo crudini --set /etc/php/*/cli/php.ini     Date date.timezone "$TIMEZONE"



#############################

# Apache configuration

#############################

sudo a2enmod rewrite

# Allow .htaccess overrides in web root

AP_CFG='/etc/apache2/sites-available/000-default.conf'

if ! grep -q "AllowOverride All" "$AP_CFG"; then

  sudo sed -i 's#</VirtualHost>#  <Directory /var/www/html/>\n    AllowOverride All\n    Require all granted\n  </Directory>\n</VirtualHost>#' "$AP_CFG"

fi

sudo systemctl enable --now apache2



#############################

# MariaDB: secure & create DB

#############################

sudo systemctl enable --now mariadb



# Harden: remove anonymous users, test DB

sudo mysql <<'SQL'

DELETE FROM mysql.user WHERE User='';

DROP DATABASE IF EXISTS test;

DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%';

FLUSH PRIVILEGES;

SQL



# Create student DB/user

sudo mysql <<SQL

CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';

GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';

FLUSH PRIVILEGES;

SQL



#############################

# Project scaffold - MINIMAL

#############################

sudo mkdir -p "$APP_ROOT"/{includes,assets}



# Database connection helper (optional for students to use)

sudo tee "$APP_ROOT"/includes/db_example.php >/dev/null <<PHP

<?php

// Example database connection - rename to db.php and modify as needed

\$dsn = 'mysql:host=localhost;dbname=$DB_NAME;charset=utf8mb4';

\$username = '$DB_USER';

\$password = '$DB_PASS';



try {

  \$pdo = new PDO(\$dsn, \$username, \$password, [

    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

  ]);

} catch (PDOException \$e) {

  die("Database connection error: " . \$e->getMessage());

}

?>

PHP



# Basic CSS starter (optional)

sudo tee "$APP_ROOT"/assets/style.css >/dev/null <<'CSS'

/* Basic starter styles - customize as needed */

body {

  font-family: system-ui, -apple-system, Arial, sans-serif;

  margin: 2rem;

  max-width: 900px;

}

CSS



# Simple welcome page with instructions

sudo tee "$APP_ROOT"/index.php >/dev/null <<PHP

<!DOCTYPE html>

<html lang="en">

<head>

  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Student Development Environment</title>

  <link rel="stylesheet" href="/assets/style.css">

</head>

<body>

  <h1>LAMP Development Environment Ready!</h1>

  <p>Your development environment is set up. You can now build your website.</p>

  

  <h2>Available Resources:</h2>

  <ul>

    <li><strong>Web Root:</strong> /var/www/html/</li>

    <li><strong>Database:</strong> $DB_NAME</li>

    <li><strong>Database User:</strong> $DB_USER</li>

    <li><strong>Database Password:</strong> $DB_PASS</li>

    <li><strong>Adminer (Database UI):</strong> <a href="/adminer/">/adminer/</a></li>

  </ul>



  <h2>Quick Start:</h2>

  <ol>

    <li>Edit this file (index.php) to create your homepage</li>

    <li>Create additional pages (e.g., about.php, contact.php)</li>

    <li>Use /includes/db_example.php as a template for database connections</li>

    <li>Test your PHP: <?php echo "PHP is working! Version: " . phpversion(); ?></li>

  </ol>



  <h2>Database Connection Test:</h2>

  <?php

  try {

    \$pdo = new PDO('mysql:host=localhost;dbname=$DB_NAME', '$DB_USER', '$DB_PASS');

    echo "<p style='color: green;'>✓ Database connection successful!</p>";

  } catch (PDOException \$e) {

    echo "<p style='color: red;'>✗ Database connection failed</p>";

  }

  ?>

</body>

</html>

PHP



#############################

# Create empty database tables (optional starter schema)

#############################

sudo mysql "$DB_NAME" <<'SQL'

-- Example table structure - students can modify or create their own tables

-- Uncomment and customize as needed:



-- CREATE TABLE IF NOT EXISTS users (

--   id INT AUTO_INCREMENT PRIMARY KEY,

--   username VARCHAR(50) UNIQUE NOT NULL,

--   email VARCHAR(100) UNIQUE NOT NULL,

--   password_hash VARCHAR(255) NOT NULL,

--   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



SQL



#############################

# Adminer (DB admin web UI)

#############################

sudo mkdir -p "$APP_ROOT"/adminer

sudo curl -fsSL -o "$APP_ROOT"/adminer/index.php "https://www.adminer.org/latest.php"



#############################

# Permissions

#############################

sudo chown -R www-data:www-data "$APP_ROOT"

sudo find "$APP_ROOT" -type d -exec chmod 755 {} \;

sudo find "$APP_ROOT" -type f -exec chmod 644 {} \;



sudo systemctl restart apache2



#############################

# UFW (optional firewall)

#############################

sudo ufw allow 80/tcp >/dev/null 2>&1 || true

sudo ufw allow 443/tcp >/dev/null 2>&1 || true



#############################

# Summary

#############################

echo ""

echo "=========================================="

echo "[✔] LAMP Environment Setup Complete!"

echo "=========================================="

echo ""

echo "Access your site at: http://<VM-IP>/"

echo ""

echo "Database Details:"

echo "  - Database: $DB_NAME"

echo "  - User: $DB_USER"

echo "  - Password: $DB_PASS"

echo ""

echo "Adminer (Database UI): http://<VM-IP>/adminer/"

echo "  - System: MySQL"

echo "  - Server: localhost"

echo ""

echo "File Locations:"

echo "  - Web root: $APP_ROOT"

echo "  - Place your PHP files in: $APP_ROOT"

echo ""

echo "Students can now start building their website!"

echo "=========================================="
