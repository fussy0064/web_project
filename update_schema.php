<?php
require_once 'config/db.php';
$conn = getDBConnection();

try {
    // Enable foreign key checks? maybe disable first
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Alter table to change ENUM
    // Use ignore to avoid error if already exists (but modify should handle it)
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    // If type is not exact match, alter it. Or just run alter always.
    $conn->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'seller', 'customer') DEFAULT 'customer'");

    // Add status to users
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive', 'banned') DEFAULT 'active'");
        echo "Added status to users.\n";
    }

    echo "Users schema updated.\n";

    // Add total to orders
    $stmt = $conn->query("SHOW COLUMNS FROM orders LIKE 'total'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN total DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER user_id"); // adjust position if needed
        echo "Added total to orders.\n";
    }

    // Add status to orders if missing (schema snippet implied it exists but let's be safe)
    $stmt = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending'");
        echo "Added status to orders.\n";
    }

    // Add seller_id to products
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'seller_id'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE products ADD COLUMN seller_id INT DEFAULT NULL");
        $conn->exec("ALTER TABLE products ADD CONSTRAINT fk_products_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "Added seller_id to products.\n";
    }

    // Add seller_id to order_items
    $stmt = $conn->query("SHOW COLUMNS FROM order_items LIKE 'seller_id'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE order_items ADD COLUMN seller_id INT DEFAULT NULL"); // Should be NOT NULL ideally but for existing data...
        $conn->exec("ALTER TABLE order_items ADD CONSTRAINT fk_order_items_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "Added seller_id to order_items.\n";
    }

    // Also create system_logs table if not exists
    $sqlLogs = "CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $conn->exec($sqlLogs);
    echo "system_logs table created/checked.\n";

    // Also create notifications table if not exists
    $sqlNotif = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(100),
        message TEXT,
        type VARCHAR(50),
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sqlNotif);
    echo "notifications table created/checked.\n";

    // Also create inventory_logs table if not exists
    $sqlInv = "CREATE TABLE IF NOT EXISTS inventory_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT,
        user_id INT,
        type ENUM('add', 'remove', 'adjustment'),
        quantity INT,
        previous_quantity INT,
        new_quantity INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $conn->exec($sqlInv);
    echo "inventory_logs table created/checked.\n";

    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>