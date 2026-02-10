# ElectroShop - Electronics E-Commerce Platform

A complete functional web-based electronics marketplace built with PHP, MySQL, and vanilla JavaScript.

## 🚀 Features

### Core Functionality
- **User Authentication**: Registration, login, and session management with password hashing
- **Role-Based Access Control**: Admin, Seller, and Customer roles
- **Product Management**: Full CRUD operations for products
- **Shopping Cart**: Add to cart, update quantities, remove items
- **Order Management**: Place orders, view order history, track status
- **Search & Filter**: Search products by name, filter by category
- **Notifications**: Real-time notifications for sellers on new orders
- **Inventory Management**: Stock tracking and low stock alerts

### Security Features
- Password hashing using PHP's `password_hash()` with bcrypt
- Input sanitization and validation
- SQL injection prevention using PDO prepared statements
- XSS protection with `htmlspecialchars()`
- Session-based authentication
- CORS headers configuration

### Database Design
- **8 Normalized Tables** (3NF):
  1. `users` - User accounts and authentication
  2. `products` - Product catalog
  3. `categories` - Product categories
  4. `orders` - Order information
  5. `order_items` - Order line items
  6. `cart` - Shopping cart items
  7. `notifications` - User notifications
  8. `system_logs` - Activity logging
  9. `inventory_logs` - Stock movement tracking

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

## 🛠️ Installation

### 1. Clone or Download the Project
```bash
cd /path/to/web_project
```

### 2. Database Setup

#### Option A: Fresh Installation
```bash
# Import the database schema
mysql -u your_username -p < database.sql
```

#### Option B: Update Existing Database
If you have an old version with `password` column instead of `password_hash`:
```bash
# Run the update script
php update_database.php
```

### 3. Configure Database Connection

Edit `public/api/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'electronics_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Set Up Web Server

#### Apache (.htaccess already included)
```apache
DocumentRoot /path/to/web_project/public
```

#### Nginx
```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/web_project/public;
    index index.html;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 5. Start the Server
```bash
# Using PHP built-in server (for development)
cd public
php -S localhost:8000
```

## 👤 Default Admin Account

After database setup, you can login with:
- **Email**: admin@electroshop.com
- **Password**: admin123

**⚠️ Important**: Change the admin password immediately after first login!

## 📁 Project Structure

```
web_project/
├── public/                      # Public web root
│   ├── index.html              # Homepage
│   ├── login.html              # Login page
│   ├── register.html           # Registration page
│   ├── cart.html               # Shopping cart
│   ├── dashboard.html          # User dashboard
│   ├── admin_dashboard.html    # Admin panel
│   ├── seller_dashboard.html   # Seller panel
│   ├── api/                    # Backend API
│   │   ├── config.php          # Database & helper functions
│   │   ├── auth/               # Authentication endpoints
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── logout.php
│   │   │   └── check_session.php
│   │   ├── products/           # Product endpoints
│   │   │   ├── create.php
│   │   │   ├── read.php
│   │   │   ├── get_product.php
│   │   │   ├── get_products.php
│   │   │   └── delete.php
│   │   ├── orders/             # Order endpoints
│   │   │   ├── create.php
│   │   │   └── history.php
│   │   ├── user/               # User endpoints
│   │   │   └── stats.php
│   │   └── admin/              # Admin endpoints
│   ├── css/
│   │   └── style.css           # Styles
│   └── js/
│       └── app.js              # Frontend JavaScript
├── database.sql                # Database schema
├── update_database.php         # Migration script
├── TODO.md                     # Development checklist
└── README.md                   # This file
```

## 🔧 Recent Fixes (Latest Update)

### Database Schema
- ✅ Renamed `password` column to `password_hash` for clarity
- ✅ Updated admin user with properly hashed password
- ✅ Verified all foreign key relationships

### API Standardization
- ✅ Converted `check_session.php` from mysqli to PDO
- ✅ Fixed role names: `admin1`→`admin`, `admin2`→`seller`, `client`→`customer`
- ✅ Added input sanitization helper functions
- ✅ Standardized error responses

### New API Endpoints
- ✅ Created `api/products/get_product.php` - Fetch single product
- ✅ Created `api/user/stats.php` - User statistics

### Frontend Fixes
- ✅ Updated role checking in `app.js`
- ✅ Fixed field references in `dashboard.html`
- ✅ Corrected API endpoint calls

### Security Enhancements
- ✅ Implemented password hashing in database seed
- ✅ Added comprehensive input sanitization
- ✅ Improved session security

## 🎯 User Roles & Permissions

### Customer
- Browse and search products
- Add items to cart
- Place orders
- View order history
- Manage profile

### Seller
- All customer permissions
- Add/edit/delete own products
- View own product orders
- Manage inventory
- Receive order notifications

### Admin
- All seller permissions
- Manage all users
- View all orders
- Access system logs
- Manage categories
- View analytics

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/register.php` - Register new user
- `POST /api/auth/login.php` - User login
- `GET /api/auth/check_session.php` - Check authentication
- `POST /api/auth/logout.php` - User logout

### Products
- `GET /api/products/read.php` - List all products
- `GET /api/products/get_products.php?category=1&search=laptop` - Filter products
- `GET /api/products/get_product.php?id=1` - Get single product
- `POST /api/products/create.php` - Create product (seller/admin)
- `DELETE /api/products/delete.php` - Delete product (seller/admin)

### Orders
- `POST /api/orders/create.php` - Create new order
- `GET /api/orders/history.php` - Get user's orders
- `GET /api/orders/history.php?all=true` - Get all orders (admin)

### User
- `GET /api/user/stats.php` - Get user statistics

## 🧪 Testing

### Test User Registration
1. Navigate to `/register.html`
2. Fill in username, email, password
3. Submit form
4. Verify user is created in database
5. Check password is hashed

### Test User Login
1. Navigate to `/login.html`
2. Enter credentials
3. Verify redirect to homepage
4. Check session is created

### Test Product Listing
1. Navigate to `/index.html`
2. Verify products are displayed
3. Test category filtering
4. Test search functionality

### Test Shopping Cart
1. Add products to cart
2. Update quantities
3. Remove items
4. Proceed to checkout

### Test Order Creation
1. Fill shipping details
2. Place order
3. Verify order in database
4. Check stock is updated
5. Verify seller notification

## 🐛 Troubleshooting

### Database Connection Error
```
Error: Connection failed: SQLSTATE[HY000] [1045] Access denied
```
**Solution**: Check database credentials in `public/api/config.php`

### Password Column Error
```
Error: Unknown column 'password_hash' in 'field list'
```
**Solution**: Run `php update_database.php` to migrate schema

### Session Not Working
**Solution**: Ensure `session_start()` is called in `config.php` and PHP sessions are enabled

### CORS Errors
**Solution**: Check CORS headers in `config.php` match your domain

## 📝 Development Notes

### Adding New Features
1. Create API endpoint in appropriate directory
2. Use PDO for database operations
3. Include `config.php` for database connection
4. Use helper functions for validation
5. Return JSON responses
6. Update frontend to consume API

### Code Standards
- Use PDO prepared statements for all queries
- Sanitize all user inputs
- Hash passwords with `password_hash()`
- Use meaningful variable names
- Add comments for complex logic
- Follow PSR-12 coding standards

## 🔐 Security Best Practices

1. **Never store plain text passwords**
2. **Always use prepared statements**
3. **Validate and sanitize all inputs**
4. **Use HTTPS in production**
5. **Keep PHP and MySQL updated**
6. **Set proper file permissions**
7. **Disable error display in production**
8. **Implement rate limiting for login**
9. **Use secure session settings**
10. **Regular security audits**

## 📄 License

This project is for educational purposes.

## 👥 Support

For issues or questions:
1. Check the TODO.md file
2. Review error logs
3. Verify database schema
4. Check API responses in browser console

## 🎓 Academic Requirements Met

✅ MySQL database with 8+ related tables
✅ Normalized to 3NF
✅ Backend in PHP with PDO
✅ Frontend in vanilla JavaScript (no frameworks)
✅ User registration and login
✅ Full CRUD operations
✅ Data validation (client & server)
✅ Search and filtering
✅ Role-based access control
✅ Password hashing (bcrypt)
✅ Input sanitization
✅ Fetch API/AJAX integration
✅ Responsive design
✅ Session management
✅ Error handling

---

**Last Updated**: 2024
**Version**: 2.0 (Fixed API Logic & Communication)
