#!/bin/bash

echo "=== ElectroShop Quick Setup Script ==="
echo ""

# Check if MariaDB is running
echo "1. Checking MariaDB status..."
if sudo systemctl is-active --quiet mariadb; then
    echo "   ✓ MariaDB is running"
else
    echo "   ✗ MariaDB is not running"
    echo "   Attempting to start MariaDB..."
    
    # Try to fix common issues
    sudo chown -R mysql:mysql /var/lib/mysql
    sudo chmod 755 /var/lib/mysql
    
    # Start MariaDB
    sudo systemctl start mariadb
    
    if sudo systemctl is-active --quiet mariadb; then
        echo "   ✓ MariaDB started successfully"
    else
        echo "   ✗ MariaDB failed to start"
        echo "   Trying alternative method..."
        sudo mysqld_safe --skip-grant-tables &
        sleep 3
    fi
fi

echo ""
echo "2. Importing database..."
mysql -u fussy -pfussy < fresh_install.sql 2>&1

if [ $? -eq 0 ]; then
    echo "   ✓ Database imported successfully"
else
    echo "   ✗ Database import failed"
    echo "   Error details above"
    exit 1
fi

echo ""
echo "3. Verifying installation..."
php debug_login.php 2>&1 | grep -E "✓|✗|Admin user"

echo ""
echo "4. Starting PHP development server..."
echo "   Server will start at: http://localhost:8000"
echo "   Press Ctrl+C to stop"
echo ""
cd public && php -S localhost:8000
