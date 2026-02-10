<?php
/**
 * API Endpoint Testing Script
 * Run this script to verify all API endpoints are accessible and working
 */

echo "=== ElectroShop API Endpoint Test ===\n\n";

// Test database connection
echo "1. Testing Database Connection...\n";
try {
    require_once 'public/api/config.php';
    echo "   ✓ Database connection successful\n";
    echo "   Database: " . DB_NAME . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test database schema
echo "2. Checking Database Schema...\n";
try {
    // Check users table
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Users table has 'password_hash' column\n";
    } else {
        echo "   ✗ Users table missing 'password_hash' column\n";
        echo "   → Run: php update_database.php\n";
    }
    
    // Check all required tables
    $tables = ['users', 'products', 'categories', 'orders', 'order_items', 'cart', 'notifications', 'system_logs'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "   ✓ All required tables exist (" . count($tables) . " tables)\n\n";
    } else {
        echo "   ✗ Missing tables: " . implode(', ', $missingTables) . "\n";
        echo "   → Import database.sql\n\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Schema check failed: " . $e->getMessage() . "\n\n";
}

// Test admin user
echo "3. Checking Admin User...\n";
try {
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "   ✓ Admin user exists\n";
        echo "   Username: " . $admin['username'] . "\n";
        echo "   Email: " . $admin['email'] . "\n";
        echo "   Role: " . $admin['role'] . "\n\n";
    } else {
        echo "   ✗ Admin user not found\n";
        echo "   → Import database.sql to create admin user\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Admin check failed: " . $e->getMessage() . "\n\n";
}

// Test categories
echo "4. Checking Categories...\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "   ✓ Categories table populated (" . $result['count'] . " categories)\n\n";
    } else {
        echo "   ⚠ No categories found\n";
        echo "   → Import database.sql to add default categories\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Category check failed: " . $e->getMessage() . "\n\n";
}

// Test helper functions
echo "5. Testing Helper Functions...\n";
try {
    // Test sanitizeInput
    if (function_exists('sanitizeInput')) {
        $test = sanitizeInput('<script>alert("xss")</script>');
        echo "   ✓ sanitizeInput() function exists\n";
    } else {
        echo "   ✗ sanitizeInput() function missing\n";
    }
    
    // Test validateEmail
    if (function_exists('validateEmail')) {
        echo "   ✓ validateEmail() function exists\n";
    } else {
        echo "   ✗ validateEmail() function missing\n";
    }
    
    // Test validateRequired
    if (function_exists('validateRequired')) {
        echo "   ✓ validateRequired() function exists\n";
    } else {
        echo "   ✗ validateRequired() function missing\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Helper function test failed: " . $e->getMessage() . "\n\n";
}

// Check API file structure
echo "6. Checking API File Structure...\n";
$apiFiles = [
    'public/api/config.php' => 'Configuration',
    'public/api/auth/login.php' => 'Login endpoint',
    'public/api/auth/register.php' => 'Register endpoint',
    'public/api/auth/check_session.php' => 'Session check endpoint',
    'public/api/auth/logout.php' => 'Logout endpoint',
    'public/api/products/read.php' => 'Products list endpoint',
    'public/api/products/get_product.php' => 'Single product endpoint',
    'public/api/products/get_products.php' => 'Products filter endpoint',
    'public/api/products/create.php' => 'Create product endpoint',
    'public/api/orders/create.php' => 'Create order endpoint',
    'public/api/orders/history.php' => 'Order history endpoint',
    'public/api/user/stats.php' => 'User stats endpoint',
];

$missingFiles = [];
foreach ($apiFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ Missing: $description ($file)\n";
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    echo "   ✓ All API files present\n\n";
} else {
    echo "\n   ⚠ Missing " . count($missingFiles) . " API file(s)\n\n";
}

// Check frontend files
echo "7. Checking Frontend Files...\n";
$frontendFiles = [
    'public/index.html' => 'Homepage',
    'public/login.html' => 'Login page',
    'public/register.html' => 'Register page',
    'public/cart.html' => 'Shopping cart',
    'public/dashboard.html' => 'User dashboard',
    'public/js/app.js' => 'Main JavaScript',
    'public/css/style.css' => 'Stylesheet',
];

$missingFrontend = [];
foreach ($frontendFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ Missing: $description ($file)\n";
        $missingFrontend[] = $file;
    }
}

if (empty($missingFrontend)) {
    echo "   ✓ All frontend files present\n\n";
} else {
    echo "\n   ⚠ Missing " . count($missingFrontend) . " frontend file(s)\n\n";
}

// Summary
echo str_repeat("=", 50) . "\n";
echo "Test Summary\n";
echo str_repeat("=", 50) . "\n";

$allGood = empty($missingTables) && empty($missingFiles) && empty($missingFrontend);

if ($allGood) {
    echo "✅ All tests passed! System is ready to use.\n";
    echo "\nNext steps:\n";
    echo "1. Start your web server\n";
    echo "2. Navigate to http://localhost:8000 (or your server URL)\n";
    echo "3. Login with admin@electroshop.com / admin123\n";
    echo "4. Change the admin password immediately!\n";
} else {
    echo "⚠️  Some issues found. Please review the output above.\n";
    echo "\nCommon fixes:\n";
    echo "- Import database: mysql -u user -p < database.sql\n";
    echo "- Update schema: php update_database.php\n";
    echo "- Check file permissions\n";
}

echo str_repeat("=", 50) . "\n";
?>
