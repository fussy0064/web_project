# ElectroShop - Deployment Checklist

Use this checklist to ensure proper deployment of the ElectroShop system.

---

## 📋 Pre-Deployment Checklist

### 1. System Requirements
- [ ] PHP 7.4 or higher installed
- [ ] MySQL 5.7 or higher installed
- [ ] Apache/Nginx web server configured
- [ ] PDO MySQL extension enabled
- [ ] mod_rewrite enabled (Apache)

### 2. File Permissions
```bash
# Set proper permissions
chmod 755 public/
chmod 644 public/*.html
chmod 644 public/api/*.php
chmod 644 public/css/*.css
chmod 644 public/js/*.js
```

### 3. Database Setup

#### Option A: Fresh Installation
```bash
# Create database and import schema
mysql -u root -p
CREATE DATABASE electronics_db;
exit;

mysql -u root -p electronics_db < database.sql
```

#### Option B: Update Existing Database
```bash
# Run migration script
php update_database.php
```

### 4. Configuration

- [ ] Update database credentials in `public/api/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'electronics_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('BASE_URL', 'http://your-domain.com');
```

- [ ] Update CORS settings if needed
- [ ] Set proper timezone in PHP configuration

### 5. Security Configuration

- [ ] Change default admin password
- [ ] Disable error display in production:
```php
// In php.ini or .htaccess
display_errors = Off
log_errors = On
error_log = /path/to/error.log
```

- [ ] Enable HTTPS (recommended)
- [ ] Set secure session settings:
```php
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = Strict
```

---

## 🧪 Testing Checklist

### Run Automated Tests
```bash
# Test API endpoints
php test_api_endpoints.php
```

Expected output:
```
✓ Database connection successful
✓ All required tables exist
✓ Admin user exists
✓ Categories table populated
✓ All helper functions exist
✓ All API files present
✓ All frontend files present
```

### Manual Testing

#### 1. User Registration
- [ ] Navigate to `/register.html`
- [ ] Register with valid credentials
- [ ] Verify user created in database
- [ ] Check password is hashed
- [ ] Test duplicate email/username prevention

#### 2. User Login
- [ ] Navigate to `/login.html`
- [ ] Login with registered credentials
- [ ] Verify redirect to homepage
- [ ] Check session is created
- [ ] Test invalid credentials handling

#### 3. Product Listing
- [ ] Navigate to `/index.html`
- [ ] Verify products are displayed
- [ ] Test category filtering
- [ ] Test search functionality
- [ ] Check product images load

#### 4. Shopping Cart
- [ ] Add products to cart
- [ ] Update quantities
- [ ] Remove items
- [ ] Verify cart count updates
- [ ] Test stock validation

#### 5. Order Creation
- [ ] Fill shipping details
- [ ] Place order
- [ ] Verify order in database
- [ ] Check stock is updated
- [ ] Verify order confirmation

#### 6. Role-Based Access
- [ ] Login as customer
  - [ ] Can view products
  - [ ] Can place orders
  - [ ] Cannot access seller/admin features
  
- [ ] Login as seller
  - [ ] Can add products
  - [ ] Can view own orders
  - [ ] Can manage inventory
  - [ ] Cannot access admin features
  
- [ ] Login as admin
  - [ ] Can access all features
  - [ ] Can manage users
  - [ ] Can view all orders
  - [ ] Can view system logs

---

## 🚀 Deployment Steps

### Development Server (Testing)
```bash
cd public
php -S localhost:8000
```

### Production Server

#### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/electroshop/public
    
    <Directory /var/www/electroshop/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/electroshop-error.log
    CustomLog ${APACHE_LOG_DIR}/electroshop-access.log combined
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/electroshop/public;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## 🔒 Post-Deployment Security

### 1. Change Default Credentials
```sql
-- Login as admin and change password via UI, or:
UPDATE users 
SET password_hash = '$2y$10$NEW_HASH_HERE' 
WHERE username = 'admin';
```

### 2. Secure File Permissions
```bash
# Restrict access to sensitive files
chmod 600 public/api/config.php
chmod 600 database.sql
```

### 3. Enable HTTPS
```bash
# Using Let's Encrypt (recommended)
sudo certbot --apache -d your-domain.com
# or
sudo certbot --nginx -d your-domain.com
```

### 4. Database Security
```sql
-- Create dedicated database user
CREATE USER 'electroshop_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON electronics_db.* TO 'electroshop_user'@'localhost';
FLUSH PRIVILEGES;
```

### 5. Backup Strategy
```bash
# Create backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u username -p electronics_db > backup_$DATE.sql
tar -czf files_$DATE.tar.gz /var/www/electroshop/
```

---

## 📊 Monitoring Checklist

### 1. Error Logging
- [ ] Check PHP error logs regularly
- [ ] Monitor MySQL slow query log
- [ ] Review Apache/Nginx access logs

### 2. Performance Monitoring
- [ ] Monitor database query performance
- [ ] Check server resource usage
- [ ] Monitor page load times

### 3. Security Monitoring
- [ ] Review failed login attempts
- [ ] Monitor for SQL injection attempts
- [ ] Check for unusual traffic patterns

---

## 🔄 Maintenance Tasks

### Daily
- [ ] Check error logs
- [ ] Monitor system performance
- [ ] Verify backup completion

### Weekly
- [ ] Review user registrations
- [ ] Check order processing
- [ ] Update product inventory

### Monthly
- [ ] Update PHP/MySQL if needed
- [ ] Review and optimize database
- [ ] Security audit
- [ ] Performance optimization

---

## 🆘 Troubleshooting Guide

### Issue: Database Connection Failed
**Solution**:
1. Check credentials in `config.php`
2. Verify MySQL service is running
3. Check database exists
4. Verify user permissions

### Issue: Session Not Working
**Solution**:
1. Check PHP session configuration
2. Verify session directory is writable
3. Check session cookie settings
4. Clear browser cookies

### Issue: Products Not Displaying
**Solution**:
1. Check database has products
2. Verify API endpoint is accessible
3. Check browser console for errors
4. Verify CORS settings

### Issue: Orders Not Creating
**Solution**:
1. Check user is logged in
2. Verify cart has items
3. Check stock availability
4. Review error logs

### Issue: Role-Based Access Not Working
**Solution**:
1. Verify user role in database
2. Check session data
3. Review role checking logic
4. Clear session and re-login

---

## ✅ Final Verification

Before going live, verify:

- [ ] All tests pass
- [ ] Admin password changed
- [ ] HTTPS enabled
- [ ] Error logging configured
- [ ] Backups configured
- [ ] Database optimized
- [ ] Security headers set
- [ ] Performance tested
- [ ] Documentation complete
- [ ] Support plan in place

---

## 📞 Support Resources

### Documentation
- `README.md` - Complete system documentation
- `FIXES_SUMMARY.md` - Detailed fix documentation
- `TODO.md` - Development checklist

### Testing
- `test_api_endpoints.php` - API testing script
- `update_database.php` - Database migration script

### Default Credentials
- Email: admin@electroshop.com
- Password: admin123
- **⚠️ CHANGE IMMEDIATELY AFTER FIRST LOGIN**

---

## 🎉 Deployment Complete!

Once all items are checked:
1. ✅ System is ready for production
2. ✅ All features are functional
3. ✅ Security measures in place
4. ✅ Monitoring configured
5. ✅ Backup strategy implemented

**Good luck with your deployment! 🚀**
