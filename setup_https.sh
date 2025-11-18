#!/usr/bin/env bash
#
# HTTPS/SSL Setup Script
# SECURITY FEATURE #5: HTTPS with Self-Signed Certificate
#
# This script sets up HTTPS for the secure web application
# Run as root: sudo ./setup_https.sh

set -euo pipefail

echo "=========================================="
echo "HTTPS/SSL Setup for Secure Web Application"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: This script must be run as root (use sudo)"
    exit 1
fi

echo "[1/6] Checking prerequisites..."
if ! command -v openssl &> /dev/null; then
    echo "Installing OpenSSL..."
    apt update && apt install -y openssl
fi

echo ""
echo "[2/6] Creating SSL directory..."
mkdir -p /etc/ssl/private
chmod 700 /etc/ssl/private

echo ""
echo "[3/6] Generating self-signed SSL certificate..."
echo "Note: You'll be asked for certificate information (you can press Enter to skip most fields)"
echo ""

# Generate certificate
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/apache-selfsigned.key \
    -out /etc/ssl/certs/apache-selfsigned.crt \
    -subj "/C=AU/ST=NSW/L=Sydney/O=Student Project/OU=IT Department/CN=172.19.12.158"

chmod 600 /etc/ssl/private/apache-selfsigned.key
chmod 644 /etc/ssl/certs/apache-selfsigned.crt

echo "✅ Certificate generated successfully!"
echo "   Certificate: /etc/ssl/certs/apache-selfsigned.crt"
echo "   Private Key: /etc/ssl/private/apache-selfsigned.key"

echo ""
echo "[4/6] Enabling SSL module in Apache..."
a2enmod ssl
a2enmod headers

echo ""
echo "[5/6] Configuring Apache SSL VirtualHost..."

# Create SSL VirtualHost configuration
cat > /etc/apache2/sites-available/default-ssl.conf <<'EOF'
<IfModule mod_ssl.c>
    <VirtualHost _default_:443>
        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/html

        # SSL Engine
        SSLEngine on
        SSLCertificateFile /etc/ssl/certs/apache-selfsigned.crt
        SSLCertificateKeyFile /etc/ssl/private/apache-selfsigned.key

        # Security Headers
        Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"

        # Directory Configuration
        <Directory /var/www/html>
            Options -Indexes +FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>

        # Logging
        ErrorLog ${APACHE_LOG_DIR}/ssl_error.log
        CustomLog ${APACHE_LOG_DIR}/ssl_access.log combined

        # SSL Protocol Configuration
        SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
        SSLCipherSuite HIGH:!aNULL:!MD5:!3DES
        SSLHonorCipherOrder on
    </VirtualHost>
</IfModule>
EOF

# Enable the SSL site
a2ensite default-ssl

# Optional: Redirect HTTP to HTTPS
echo ""
echo "Do you want to redirect all HTTP traffic to HTTPS? (recommended) [Y/n]"
read -r redirect_choice

if [[ "$redirect_choice" != "n" && "$redirect_choice" != "N" ]]; then
    echo "Configuring HTTP to HTTPS redirect..."

    # Update default HTTP VirtualHost to redirect to HTTPS
    cat > /etc/apache2/sites-available/000-default.conf <<'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    # Redirect all HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

    a2enmod rewrite
    echo "✅ HTTP to HTTPS redirect configured"
else
    echo "Skipping HTTP to HTTPS redirect"
fi

echo ""
echo "[6/6] Restarting Apache..."
systemctl restart apache2

echo ""
echo "=========================================="
echo "✅ HTTPS/SSL Setup Complete!"
echo "=========================================="
echo ""
echo "Certificate Details:"
echo "  - Type: Self-Signed Certificate"
echo "  - Validity: 365 days"
echo "  - Certificate Location: /etc/ssl/certs/apache-selfsigned.crt"
echo "  - Private Key Location: /etc/ssl/private/apache-selfsigned.key"
echo ""
echo "Access your site:"
echo "  - HTTPS: https://172.19.12.158/"
echo "  - HTTP:  http://172.19.12.158/ (may redirect to HTTPS)"
echo ""
echo "⚠️  Important Notes:"
echo "  1. Your browser will show a security warning because this is a self-signed certificate"
echo "  2. This is NORMAL for local development"
echo "  3. Click 'Advanced' and 'Proceed' to accept the certificate"
echo "  4. For production, use a certificate from a trusted CA (like Let's Encrypt)"
echo ""
echo "To enable secure cookies, uncomment this line in includes/session.php:"
echo "  ini_set('session.cookie_secure', 1);"
echo ""
echo "Firewall: Make sure port 443 is open"
echo "  sudo ufw allow 443/tcp"
echo ""
echo "=========================================="
