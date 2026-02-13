#!/bin/bash

# AWS Deployment Script for Electronics Ordering System
# Compatible with Amazon Linux 2023 (AL2023)

echo "--- Starting Deployment on Amazon Linux ---"

# 1. System Update
echo "[1/6] Updating System..."
dnf update -y

# 2. Install LAMP Stack (Amazon Linux specific)
echo "[2/6] Installing LAMP Stack..."
dnf install httpd php php-mysqlnd php-gd php-xml php-mbstring mariadb105-server git -y

# Start Services
systemctl start httpd
systemctl enable httpd
systemctl start mariadb
systemctl enable mariadb

# 3. Secure MySQL (Automated)
echo "[3/6] Configuring Database..."
APP_DB="electronics_db"
APP_USER="electro_user"
APP_PASS="StrongPassword123!"

# Secure installation (defaults) and create DB
mysql -e "CREATE DATABASE IF NOT EXISTS $APP_DB;"
mysql -e "CREATE USER IF NOT EXISTS '$APP_USER'@'localhost' IDENTIFIED BY '$APP_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON $APP_DB.* TO '$APP_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "Database '$APP_DB' created."

# 4. Clone Project
echo "[4/6] Deploying Code..."
WEB_ROOT="/var/www/html"
PROJECT_DIR="$WEB_ROOT/electronics"
REPO_URL="https://github.com/fussy0064/web_project.git"

# Fix permissions for /var/www
usermod -a -G apache ec2-user
chown -R ec2-user:apache /var/www
chmod 2775 /var/www
find /var/www -type d -exec chmod 2775 {} \;
find /var/www -type f -exec chmod 0664 {} \;

if [ -d "$PROJECT_DIR" ]; then
    echo "Directory exists. Pulling latest changes..."
    cd $PROJECT_DIR
    git pull origin main
else
    echo "Cloning repository..."
    cd $WEB_ROOT
    git clone $REPO_URL electronics
fi

# 5. Configure Project
echo "[5/6] Configuration..."

CONFIG_FILE="$PROJECT_DIR/public/api/config.php"
cat > $CONFIG_FILE <<EOF
<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

define('DB_HOST', 'localhost');
define('DB_NAME', '$APP_DB');
define('DB_USER', '$APP_USER');
define('DB_PASS', '$APP_PASS');
define('BASE_URL', ''); 

function getDBConnection() {
    try {
        \$conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        \$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return \$conn;
    } catch (PDOException \$e) {
        die("Connection failed: " . \$e->getMessage());
    }
}

// CORS
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
mysql -u $APP_USER -p$APP_PASS $APP_DB < "$PROJECT_DIR/database.sql"

# 6. Configure Apache
echo "[6/6] Apache Config..."

# Allow overrides (.htaccess)
sed -i '/<Directory "\/var\/www\/html">/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/httpd/conf/httpd.conf

# Set DocumentRoot to public folder
sed -i 's|DocumentRoot "/var/www/html"|DocumentRoot "/var/www/html/electronics/public"|' /etc/httpd/conf/httpd.conf

# Set Permissions
chown -R apache:apache $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 "$PROJECT_DIR/public/uploads"

# Restart Apache
systemctl restart httpd

echo "--- Deployment Complete! ---"
echo "Your website should be live at: http://$(curl -s http://checkip.amazonaws.com)"

