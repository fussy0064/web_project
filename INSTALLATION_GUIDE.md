# ElectroShop - Installation Guide

## ⚠️ Important: Database Installation Options

You have **TWO options** for setting up the database:

---

## Option 1: Fresh Installation (Recommended)

**Use this if:**
- You're installing for the first time
- You want to start with a clean database
- You don't have existing data to preserve

**Steps:**
```bash
# This will DROP the existing database and create a new one
mysql -u your_username -p < fresh_install.sql
```

**What it does:**
- Drops existing `electronics_db` database (if exists)
- Creates new database with correct schema
- Creates all tables with proper foreign keys
- Inserts default categories
- Creates admin user with hashed password

---

## Option 2: Update Existing Database

**Use this if:**
- You have an existing database with old schema
- You want to preserve existing data
- You're migrating from old `password` column to `password_hash`

**Steps:**
```bash
# Run the migration script
php update_database.php
```

**What it does:**
- Checks for old `password` column
- Creates new `password_hash` column
- Migrates and hashes existing passwords
- Removes old `password` column
- Updates admin password

---

## Error: "Unknown column 'password_hash'"

If you see this error:
```
#1054 - Unknown column 'password_hash' in 'field list'
```

**This means:** You're trying to use `database.sql` on an existing database that has the old schema.

**Solution:** Use **Option 1** (Fresh Installation) instead:

```bash
# Drop and recreate the database
mysql -u your_username -p < fresh_install.sql
```

**OR** if you need to keep data, use **Option 2**:

```bash
# Migrate the existing database
php update_database.php
```

---

## Complete Installation Steps

### 1. Choose Your Installation Method

**For New Installation:**
```bash
mysql -u fussy -p < fresh_install.sql
```

**For Existing Database:**
```bash
php update_database.php
```

### 2. Configure Database Connection

Edit `public/api/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'electronics_db');
define('DB_USER', 'fussy');
define('DB_PASS', 'fussy');
```

### 3. Verify Installation

```bash
php test_api_endpoints.php
```

Expected output:
```
✓ Database connection successful
✓ Users table has 'password_hash' column
✓ All required tables exist (8 tables)
✓ Admin user exists
✓ Categories table populated (10 categories)
```

### 4. Start the Server

```bash
cd public
php -S localhost:8000
```

### 5. Access the Application

Open browser: http://localhost:8000

**Default Admin Login:**
- Email: admin@electroshop.com
- Password: admin123
- **⚠️ CHANGE THIS PASSWORD IMMEDIATELY!**

---

## Database Schema Overview

The correct schema includes:

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,  -- ← Correct column name
    role ENUM('admin', 'seller', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Products Table (with proper foreign keys)
```sql
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    category_id INT,  -- ← Links to categories table
    -- ... other columns ...
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

**Note:** Categories table is created BEFORE products table to satisfy foreign key constraint.

---

## Troubleshooting

### Problem: "Table already exists"
**Solution:** Use `fresh_install.sql` which drops existing tables

### Problem: "Cannot add foreign key constraint"
**Solution:** Ensure tables are created in correct order (use `fresh_install.sql`)

### Problem: "Access denied for user"
**Solution:** Check database credentials in `public/api/config.php`

### Problem: "Unknown database 'electronics_db'"
**Solution:** Run the installation script to create the database

### Problem: Login fails with correct password
**Solution:** 
1. Check if password is hashed in database
2. Run `fresh_install.sql` to reset admin password
3. Or manually update: `UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';`

---

## File Reference

- `fresh_install.sql` - Clean installation (drops existing database)
- `database.sql` - Schema with IF NOT EXISTS (for reference)
- `update_database.php` - Migration script for existing databases
- `test_api_endpoints.php` - Verification script

---

## Quick Reference Commands

```bash
# Fresh install
mysql -u fussy -p < fresh_install.sql

# Update existing
php update_database.php

# Test installation
php test_api_endpoints.php

# Start server
cd public && php -S localhost:8000

# Check database
mysql -u fussy -p electronics_db -e "SHOW TABLES;"

# Verify admin user
mysql -u fussy -p electronics_db -e "SELECT id, username, email, role FROM users WHERE username='admin';"
```

---

## Next Steps After Installation

1. ✅ Verify database is created
2. ✅ Test API endpoints
3. ✅ Start web server
4. ✅ Login as admin
5. ✅ Change admin password
6. ✅ Create test products
7. ✅ Test user registration
8. ✅ Test shopping cart
9. ✅ Test order placement
10. ✅ Review documentation

---

**Need Help?** Check the README.md for complete documentation.
