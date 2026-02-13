# ElectroHub - Access Guide

## 🚀 How to Access the Electronics Ordering System

### Prerequisites
- XAMPP/LAMPP installed at `/opt/lampp/`
- MySQL database running
- Apache web server running

---

## 1️⃣ Start XAMPP/LAMPP

Open terminal and run:

```bash
sudo /opt/lampp/lampp start
```

This will start:
- ✅ Apache Web Server (Port 80)
- ✅ MySQL Database (Port 3306)

To check status:
```bash
sudo /opt/lampp/lampp status
```

To stop (when done):
```bash
sudo /opt/lampp/lampp stop
```

---

## 2️⃣ Setup Database (First Time Only)

### Option A: Using phpMyAdmin (Recommended)
1. Open browser: `http://localhost/phpmyadmin`
2. Click "Import" tab
3. Choose file: `/opt/lampp/htdocs/Electronics_Ordering_System/web_project/database.sql`
4. Click "Go" to import

### Option B: Using Command Line
```bash
# Login to MySQL
sudo /opt/lampp/bin/mysql -u root -p

# Create and import database
source /opt/lampp/htdocs/Electronics_Ordering_System/web_project/database.sql;
exit;
```

---

## 3️⃣ Access the Application

### 🌐 Main Application (Public Site)
Open your browser and navigate to:

```
http://localhost/Electronics_Ordering_System/web_project/public/
```

**Key Pages:**
- **Homepage**: `http://localhost/Electronics_Ordering_System/web_project/public/index.html`
- **Login**: `http://localhost/Electronics_Ordering_System/web_project/public/login.html`
- **Register**: `http://localhost/Electronics_Ordering_System/web_project/public/register.html`
- **Cart**: `http://localhost/Electronics_Ordering_System/web_project/public/cart.html`
- **Products**: `http://localhost/Electronics_Ordering_System/web_project/public/product_details.html`

### 📱 Converted Repository Pages (Alternative Frontend)
```
http://localhost/Electronics_Ordering_System/web_project/electronics_ordering_system/html/
```

**Key Pages:**
- **Homepage**: `http://localhost/Electronics_Ordering_System/web_project/electronics_ordering_system/html/index.html`
- **Login**: `http://localhost/Electronics_Ordering_System/web_project/electronics_ordering_system/html/login.html`
- **Register**: `http://localhost/Electronics_Ordering_System/web_project/electronics_ordering_system/html/register.html`
- **Cart**: `http://localhost/Electronics_Ordering_System/web_project/electronics_ordering_system/html/cart.html`
- **Admin**: `http://localhost/Electronics_Ordering_System/web_project/electronics_ordering_system/html/admin.html`

---

## 4️⃣ Default Login Credentials

### Admin Account
- **Username**: `admin`
- **Email**: `admin@electroshop.com`
- **Password**: `admin123`
- **Role**: Administrator

### Test Accounts
After registration, you can create:
- **Customer** accounts (for shopping)
- **Seller** accounts (for selling products)

---

## 5️⃣ Dashboard Access

After logging in, you'll be redirected based on your role:

### 👨‍💼 Admin Dashboard
```
http://localhost/Electronics_Ordering_System/web_project/public/admin_dashboard.html
```

**Features:**
- Manage all users
- Manage all products
- View all orders
- System statistics
- Manage categories

### 🏪 Seller Dashboard
```
http://localhost/Electronics_Ordering_System/web_project/public/seller_dashboard.html
```

**Features:**
- Add/Edit/Delete your products
- View your orders
- Manage inventory
- View sales statistics
- Notifications

### 🛍️ Customer Dashboard
```
http://localhost/Electronics_Ordering_System/web_project/public/customer_dashboard.html
```

**Features:**
- View order history
- Track orders
- Manage profile
- View notifications

---

## 6️⃣ API Endpoints

The backend API is accessible at:
```
http://localhost/Electronics_Ordering_System/web_project/public/api/
```

### Available Endpoints:

#### Authentication
- `POST /api/auth/login.php` - User login
- `POST /api/auth/register.php` - User registration
- `POST /api/auth/logout.php` - User logout

#### Products
- `GET /api/products/` - List all products
- `GET /api/products/read.php` - Get product details
- `POST /api/products/create.php` - Create product (seller)
- `PUT /api/products/update.php` - Update product (seller)
- `DELETE /api/products/delete.php` - Delete product (seller)

#### Orders
- `GET /api/orders/` - List orders
- `POST /api/orders/create.php` - Create order
- `PUT /api/orders/update.php` - Update order status

#### Seller
- `GET /api/seller/stats.php` - Seller statistics
- `GET /api/seller/products.php` - Seller's products
- `GET /api/seller/orders.php` - Seller's orders

#### Admin
- `GET /api/admin/users.php` - Manage users
- `GET /api/admin/products.php` - Manage all products
- `GET /api/admin/orders.php` - Manage all orders
- `GET /api/admin/stats.php` - System statistics

---

## 7️⃣ Troubleshooting

### Apache Not Starting
```bash
# Check if port 80 is in use
sudo netstat -tulpn | grep :80

# Stop conflicting service
sudo systemctl stop apache2

# Start LAMPP
sudo /opt/lampp/lampp start
```

### MySQL Not Starting
```bash
# Check MySQL status
sudo /opt/lampp/lampp status

# Restart MySQL
sudo /opt/lampp/lampp restart
```

### Database Connection Error
1. Check `config.php` file at:
   ```
   /opt/lampp/htdocs/Electronics_Ordering_System/web_project/public/api/config.php
   ```
2. Verify database credentials:
   - Host: `localhost`
   - Database: `electronics_db`
   - Username: `root`
   - Password: (empty or your MySQL password)

### Page Not Found (404)
1. Ensure LAMPP is running: `sudo /opt/lampp/lampp status`
2. Check file path is correct
3. Verify `.htaccess` file exists in public folder

### API Not Working
1. Check Apache error logs:
   ```bash
   tail -f /opt/lampp/logs/error_log
   ```
2. Verify PHP is enabled in Apache
3. Check file permissions:
   ```bash
   sudo chmod -R 755 /opt/lampp/htdocs/Electronics_Ordering_System/
   ```

---

## 8️⃣ Quick Start Commands

```bash
# Start everything
sudo /opt/lampp/lampp start

# Open browser to main site
xdg-open http://localhost/Electronics_Ordering_System/web_project/public/

# View Apache logs
tail -f /opt/lampp/logs/access_log

# View PHP errors
tail -f /opt/lampp/logs/error_log

# Stop everything (when done)
sudo /opt/lampp/lampp stop
```

---

## 9️⃣ File Structure

```
/opt/lampp/htdocs/Electronics_Ordering_System/web_project/
├── database.sql                    # Database schema
├── public/                         # Main application
│   ├── index.html                 # Homepage
│   ├── api/                       # Backend API
│   │   ├── auth/                  # Authentication
│   │   ├── products/              # Product management
│   │   ├── orders/                # Order management
│   │   ├── seller/                # Seller operations
│   │   └── admin/                 # Admin operations
│   ├── admin_dashboard.html       # Admin panel
│   ├── seller_dashboard.html      # Seller panel
│   └── customer_dashboard.html    # Customer panel
└── electronics_ordering_system/   # Converted frontend
    └── html/                      # Alternative UI pages
```

---

## 🎯 Next Steps

1. ✅ Start LAMPP: `sudo /opt/lampp/lampp start`
2. ✅ Import database (if not done)
3. ✅ Open browser: `http://localhost/Electronics_Ordering_System/web_project/public/`
4. ✅ Login with admin credentials or register new account
5. ✅ Start exploring!

---

## 📞 Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review Apache/PHP error logs
3. Verify database connection in `config.php`
4. Ensure all file permissions are correct

---

**System**: ElectroHub - Electronics Ordering System  
**Version**: 1.0  
**Market**: Tanzania (East Africa)  
**Currency**: Tanzanian Shilling (TSh)  
**Last Updated**: February 12, 2026
