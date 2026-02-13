#!/bin/bash

# AWS Deployment Script for Electronics Ordering System
# Run this script on your Ubuntu 22.04 / 24.04 EC2 Instance

# Usage:
# 1. SSH into your server: ssh -i key.pem ubuntu@ip
# 2. curl -O https://raw.githubusercontent.com/fussy0064/web_project/main/aws_deploy.sh
# 3. chmod +x aws_deploy.sh
# 4. sudo ./aws_deploy.sh

echo "--- Starting Deployment ---"

# 1. System Update
echo "[1/6] Updating System..."
apt update && apt upgrade -y

# 2. Install LAMP Stack
echo "[2/6] Installing LAMP Stack..."
apt install apache2 mysql-server php libapache2-mod-php php-mysql php-cli php-curl php-gd php-mbstring php-xml php-zip unzip -y

# 3. Secure MySQL (Automated)
echo "[3/6] Configuring MySQL..."
# Note: In production, you should run mysql_secure_installation manually.
# Here we setup a default database and user for the app.
APP_DB="electronics_db"
APP_USER="electro_user"
APP_PASS="StrongPassword123!"

sudo mysql -e "CREATE DATABASE IF NOT EXISTS $APP_DB;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$APP_USER'@'localhost' IDENTIFIED BY '$APP_PASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $APP_DB.* TO '$APP_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

echo "Database '$APP_DB' created with user '$APP_USER'."

# 4. Clone Project
echo "[4/6] Deploying Code..."
WEB_ROOT="/var/www/html/electronics"
REPO_URL="https://github.com/fussy0064/web_project.git"

if [ -d "$WEB_ROOT" ]; then
    echo "Directory exists. Pulling latest changes..."
    cd $WEB_ROOT
    git pull origin main
else
    echo "Cloning repository..."
    git clone $REPO_URL $WEB_ROOT
fi

# 5. Configure Apache
echo "[5/6] Configuration..."

# Create production config.php
CONFIG_FILE="$WEB_ROOT/public/api/config.php"
cat > $CONFIG_FILE <<EOF
<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

define('DB_HOST', 'localhost');
define('DB_NAME', '$APP_DB');
define('DB_USER', '$APP_USER');
define('DB_PASS', '$APP_PASS');
define('BASE_URL', ''); // Root domain

function getDBConnection() {
    try {
        \$conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        \$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        \$conn->setAttribute(PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT, false);
        return \$conn;
    } catch (PDOException \$e) {
        die("Connection failed: " . \$e->getMessage());
    }
}

// CORS & Headers
\$origin = \$_SERVER['HTTP_ORIGIN'] ?? 'http://localhost';
header("Access-Control-Allow-Origin: " . \$origin);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (isset(\$_SERVER['REQUEST_METHOD']) && \$_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200); exit();
}

\$conn = getDBConnection();
?>
EOF

# Import Database Schema
echo "Importing Schema..."
sudo mysql -u $APP_USER -p$APP_PASS $APP_DB < "$WEB_ROOT/database.sql"

# Set Permissions
chown -R www-data:www-data $WEB_ROOT
chmod -R 755 $WEB_ROOT
chmod -R 775 "$WEB_ROOT/public/uploads"

# Configure Apache VHost
VHOST_FILE="/etc/apache2/sites-available/electronics.conf"
cat > $VHOST_FILE <<EOF
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot $WEB_ROOT/public

    <Directory $WEB_ROOT/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Enable Site
a2dissite 000-default.conf
a2ensite electronics.conf
a2enmod rewrite
systemctl restart apache2

echo "--- Deployment Complete! ---"
echo "Your website should be live at your server IP."
