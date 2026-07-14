-- Creating a database
CREATE DATABASE electronics_db;
USE electronics_db;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table (must be created before products table)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    category_id INT,
    brand VARCHAR(100),
    model VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    image_url TEXT,
    `condition` ENUM('New', 'Like New', 'Used - Excellent', 'Used - Good', 'Refurbished'),
    warranty VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    phone VARCHAR(20),
    city VARCHAR(100),
    payment_method VARCHAR(50),
    status ENUM('pending', 'processing', 'ready', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    seller_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- Cart table 
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('new_order', 'low_stock', 'customer_message', 'system') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System logs table
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Inventory logs
CREATE TABLE inventory_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('add', 'remove', 'adjust') NOT NULL,
    quantity INT NOT NULL,
    previous_quantity INT,
    new_quantity INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Login attempts (for brute-force rate limiting on auth/login.php)
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempted_at)
);

-- Contact messages table (used by public/api/contact_submit.php)
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PHP sessions table. On a stateless host (e.g. Vercel serverless functions),
-- PHP's default file-based session storage doesn't survive between requests,
-- since each request can run on a different ephemeral instance with no
-- shared disk. config.php registers a custom session handler (see
-- DbSessionHandler in config.php) that reads/writes session data here
-- instead, so login stays working regardless of which instance handles the
-- next request. Harmless (just unused) on a traditional single-server setup.
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    data MEDIUMTEXT,
    last_activity INT NOT NULL,
    INDEX idx_last_activity (last_activity)
);

-- Insert default categories
INSERT INTO categories (id, name) VALUES 
(1, 'Laptops & Computers'),
(2, 'Smartphones & Tablets'),
(3, 'Audio & Headphones'),
(4, 'Wearables & Smartwatches'),
(5, 'Cameras & Photography'),
(6, 'Gaming Consoles'),
(7, 'Accessories'),
(8, 'Home Electronics'),
(9, 'TVs & Monitors'),
(10, 'Other Electronics');

-- Insert default admin user (password: admin123)
-- BUG FIX: the previous hash here did not actually verify against
-- 'admin123' (confirmed with password_verify()) — the documented default
-- admin login never worked. Regenerated and verified below.
INSERT INTO users (id, username, email, password_hash, role) VALUES 
(1, 'admin', 'admin@electroshop.com', '$2y$10$WdKytziVgvvmIYx60V4qdew7CzjpJEpZzvcJnkeyBsJOIUeLIfj1W', 'admin');