# ElectroHub Database Information

## 📊 Database Details

### **Database Name:**
```
electronics_db
```

### **Database SQL File Location:**
```
/opt/lampp/htdocs/Electronics_Ordering_System/web_project/database.sql
```

### **Database Configuration:**
**File**: `/opt/lampp/htdocs/Electronics_Ordering_System/web_project/public/api/config.php`

**Connection Settings:**
- **Host**: `localhost`
- **Database**: `electronics_db`
- **Username**: `root`
- **Password**: `` (empty - default XAMPP)
- **Port**: `3306` (default MySQL port)

---

## 🗄️ Database Tables

The `electronics_db` database contains **9 tables**:

### 1. **users**
Stores all user accounts (admin, sellers, customers)
- `id` - Primary key
- `username` - Unique username
- `email` - Unique email
- `password_hash` - Encrypted password
- `role` - ENUM('admin', 'seller', 'customer')
- `status` - ENUM('active', 'inactive')
- `created_at`, `updated_at` - Timestamps

### 2. **categories**
Product categories (pre-populated with 10 electronics categories)
- `id` - Primary key
- `name` - Category name
- `description` - Category description
- `created_at` - Timestamp

**Default Categories:**
1. Laptops & Computers
2. Smartphones & Tablets
3. Audio & Headphones
4. Wearables & Smartwatches
5. Cameras & Photography
6. Gaming Consoles
7. Accessories
8. Home Electronics
9. TVs & Monitors
10. Other Electronics

### 3. **products**
All electronics products
- `id` - Primary key
- `seller_id` - Foreign key to users
- `name` - Product name
- `category_id` - Foreign key to categories
- `brand` - Product brand
- `model` - Product model
- `description` - Product description
- `price` - Product price (DECIMAL)
- `stock_quantity` - Available stock
- `image_url` - Product image path
- `condition` - ENUM('New', 'Like New', 'Used - Excellent', 'Used - Good', 'Refurbished')
- `warranty` - Warranty information
- `status` - ENUM('active', 'inactive')
- `created_at` - Timestamp

### 4. **orders**
Customer orders
- `id` - Primary key
- `user_id` - Foreign key to users
- `order_number` - Unique order number
- `subtotal` - Order subtotal
- `shipping` - Shipping cost
- `tax` - Tax amount
- `total` - Total amount
- `shipping_address` - Delivery address
- `phone` - Contact phone
- `city` - City
- `payment_method` - Payment method
- `status` - ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled')
- `created_at` - Timestamp

### 5. **order_items**
Individual items in orders
- `id` - Primary key
- `order_id` - Foreign key to orders
- `product_id` - Foreign key to products
- `seller_id` - Foreign key to users
- `quantity` - Quantity ordered
- `price` - Price at time of order
- `total` - Item total

### 6. **cart**
Shopping cart items
- `id` - Primary key
- `user_id` - Foreign key to users
- `product_id` - Foreign key to products
- `quantity` - Quantity in cart
- `created_at` - Timestamp

### 7. **notifications**
User notifications
- `id` - Primary key
- `user_id` - Foreign key to users
- `title` - Notification title
- `message` - Notification message
- `type` - ENUM('new_order', 'low_stock', 'customer_message', 'system')
- `is_read` - Boolean
- `created_at` - Timestamp

### 8. **system_logs**
System activity logs
- `id` - Primary key
- `user_id` - Foreign key to users
- `action` - Action performed
- `description` - Action description
- `ip_address` - User IP
- `user_agent` - Browser info
- `created_at` - Timestamp

### 9. **inventory_logs**
Product inventory changes
- `id` - Primary key
- `product_id` - Foreign key to products
- `user_id` - Foreign key to users
- `type` - ENUM('add', 'remove', 'adjust')
- `quantity` - Quantity changed
- `previous_quantity` - Stock before change
- `new_quantity` - Stock after change
- `notes` - Change notes
- `created_at` - Timestamp

---

## 🔧 How to Import/Setup Database

### **Method 1: Using phpMyAdmin (Easiest)**

1. **Start LAMPP:**
   ```bash
   sudo /opt/lampp/lampp start
   ```

2. **Open phpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```

3. **Import Database:**
   - Click "Import" tab
   - Click "Choose File"
   - Select: `/opt/lampp/htdocs/Electronics_Ordering_System/web_project/database.sql`
   - Click "Go"

### **Method 2: Using Command Line**

```bash
# Start LAMPP
sudo /opt/lampp/lampp start

# Import database
sudo /opt/lampp/bin/mysql -u root < /opt/lampp/htdocs/Electronics_Ordering_System/web_project/database.sql

# Verify import
sudo /opt/lampp/bin/mysql -u root -e "USE electronics_db; SHOW TABLES;"
```

### **Method 3: Using MySQL Console**

```bash
# Login to MySQL
sudo /opt/lampp/bin/mysql -u root

# In MySQL console:
source /opt/lampp/htdocs/Electronics_Ordering_System/web_project/database.sql;
SHOW DATABASES;
USE electronics_db;
SHOW TABLES;
exit;
```

---

## 🔍 Verify Database

### **Check if database exists:**
```bash
sudo /opt/lampp/bin/mysql -u root -e "SHOW DATABASES LIKE 'electronics_db';"
```

### **View all tables:**
```bash
sudo /opt/lampp/bin/mysql -u root -e "USE electronics_db; SHOW TABLES;"
```

### **Check table structure:**
```bash
sudo /opt/lampp/bin/mysql -u root -e "USE electronics_db; DESCRIBE users;"
```

### **View categories:**
```bash
sudo /opt/lampp/bin/mysql -u root -e "USE electronics_db; SELECT * FROM categories;"
```

### **Check admin user:**
```bash
sudo /opt/lampp/bin/mysql -u root -e "USE electronics_db; SELECT id, username, email, role FROM users WHERE role='admin';"
```

---

## 👤 Default Admin Account

After importing the database, you'll have this default admin account:

- **ID**: 1
- **Username**: `admin`
- **Email**: `admin@electroshop.com`
- **Password**: `admin123`
- **Role**: `admin`

**Password Hash**: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`

---

## 🔐 Database Security

### **Change MySQL Root Password (Recommended):**
```bash
sudo /opt/lampp/bin/mysqladmin -u root password 'your_new_password'
```

### **Update config.php after changing password:**
Edit: `/opt/lampp/htdocs/Electronics_Ordering_System/web_project/public/api/config.php`
```php
define('DB_PASS', 'your_new_password');
```

---

## 🛠️ Database Management Tools

### **1. phpMyAdmin**
```
http://localhost/phpmyadmin
```
- Visual interface
- Easy table browsing
- Query builder
- Import/Export

### **2. MySQL Command Line**
```bash
sudo /opt/lampp/bin/mysql -u root electronics_db
```
- Direct SQL queries
- Scripting
- Backup/Restore

### **3. MySQL Workbench** (If installed)
- Connection: `localhost:3306`
- User: `root`
- Password: (empty or your password)

---

## 📦 Backup Database

### **Full Backup:**
```bash
sudo /opt/lampp/bin/mysqldump -u root electronics_db > electronics_db_backup_$(date +%Y%m%d).sql
```

### **Backup with Compression:**
```bash
sudo /opt/lampp/bin/mysqldump -u root electronics_db | gzip > electronics_db_backup_$(date +%Y%m%d).sql.gz
```

### **Restore from Backup:**
```bash
sudo /opt/lampp/bin/mysql -u root electronics_db < electronics_db_backup_20260212.sql
```

---

## 🔄 Reset Database

If you need to reset the database to its original state:

```bash
# Drop existing database
sudo /opt/lampp/bin/mysql -u root -e "DROP DATABASE IF EXISTS electronics_db;"

# Re-import
sudo /opt/lampp/bin/mysql -u root < /opt/lampp/htdocs/Electronics_Ordering_System/web_project/database.sql
```

---

## 📊 Database Size & Performance

### **Check Database Size:**
```bash
sudo /opt/lampp/bin/mysql -u root -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'electronics_db' GROUP BY table_schema;"
```

### **Check Table Sizes:**
```bash
sudo /opt/lampp/bin/mysql -u root -e "SELECT table_name AS 'Table', ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'electronics_db' ORDER BY (data_length + index_length) DESC;"
```

---

## 🚨 Troubleshooting

### **Database Connection Error:**
1. Check if MySQL is running:
   ```bash
   sudo /opt/lampp/lampp status
   ```

2. Verify credentials in `config.php`

3. Check MySQL error log:
   ```bash
   tail -f /opt/lampp/logs/mysql_error.log
   ```

### **Table Doesn't Exist:**
- Re-import database.sql
- Check table name spelling (case-sensitive on Linux)

### **Permission Denied:**
- Grant permissions:
  ```bash
  sudo /opt/lampp/bin/mysql -u root -e "GRANT ALL PRIVILEGES ON electronics_db.* TO 'root'@'localhost';"
  ```

---

## 📍 Quick Reference

| Item | Value |
|------|-------|
| **Database Name** | `electronics_db` |
| **SQL File** | `/opt/lampp/htdocs/Electronics_Ordering_System/web_project/database.sql` |
| **Config File** | `/opt/lampp/htdocs/Electronics_Ordering_System/web_project/public/api/config.php` |
| **Host** | `localhost` |
| **Port** | `3306` |
| **User** | `root` |
| **Password** | (empty) |
| **phpMyAdmin** | `http://localhost/phpmyadmin` |
| **Tables** | 9 tables |
| **Default Admin** | admin / admin123 |

---

**Last Updated**: February 12, 2026  
**System**: ElectroHub - Electronics Ordering System
