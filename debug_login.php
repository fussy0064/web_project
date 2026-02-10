<?php
// Debug script to test login functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Login Debug Script ===\n\n";

// Test 1: Check if config file exists
echo "1. Checking config file...\n";
if (file_exists('public/api/config.php')) {
    echo "   ✓ Config file exists\n";
    require_once 'public/api/config.php';
} else {
    echo "   ✗ Config file not found\n";
    exit(1);
}

// Test 2: Check database connection
echo "\n2. Testing database connection...\n";
try {
    $conn = getDBConnection();
    echo "   ✓ Database connection successful\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check if users table exists
echo "\n3. Checking users table...\n";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Users table exists\n";
    } else {
        echo "   ✗ Users table not found\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ✗ Error checking table: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check users table structure
echo "\n4. Checking users table structure...\n";
try {
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasPasswordHash = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'password_hash') {
            $hasPasswordHash = true;
            echo "   ✓ password_hash column exists\n";
            break;
        }
    }
    if (!$hasPasswordHash) {
        echo "   ✗ password_hash column not found\n";
        echo "   Available columns: ";
        foreach ($columns as $col) {
            echo $col['Field'] . " ";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking structure: " . $e->getMessage() . "\n";
}

// Test 5: Check if admin user exists
echo "\n5. Checking admin user...\n";
try {
    $stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE email = :email");
    $stmt->execute([':email' => 'admin@electroshop.com']);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✓ Admin user found\n";
        echo "   - ID: " . $user['id'] . "\n";
        echo "   - Username: " . $user['username'] . "\n";
        echo "   - Email: " . $user['email'] . "\n";
        echo "   - Role: " . $user['role'] . "\n";
        echo "   - Password hash: " . substr($user['password_hash'], 0, 20) . "...\n";
        
        // Test 6: Test password verification
        echo "\n6. Testing password verification...\n";
        $testPassword = 'admin123';
        if (password_verify($testPassword, $user['password_hash'])) {
            echo "   ✓ Password 'admin123' verified successfully\n";
        } else {
            echo "   ✗ Password verification failed\n";
            echo "   Trying to create correct hash...\n";
            $correctHash = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "   Correct hash for 'admin123': $correctHash\n";
        }
    } else {
        echo "   ✗ Admin user not found\n";
        echo "   Creating admin user...\n";
        
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES ('admin', 'admin@electroshop.com', :hash, 'admin')");
        $insertStmt->execute([':hash' => $hash]);
        echo "   ✓ Admin user created with password 'admin123'\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 7: Test session functionality
echo "\n7. Testing session...\n";
session_start();
$_SESSION['test'] = 'value';
if (isset($_SESSION['test'])) {
    echo "   ✓ Session working\n";
} else {
    echo "   ✗ Session not working\n";
}

echo "\n=== Debug Complete ===\n";
echo "\nIf all tests passed, try logging in again.\n";
echo "Email: admin@electroshop.com\n";
echo "Password: admin123\n";
?>
