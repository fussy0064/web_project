<?php
/**
 * Database Update Script
 * This script updates the database schema from old version to new version
 * Run this once to migrate existing installations
 */

require_once 'public/api/config.php';

echo "Starting database update...\n\n";

try {
    // Check if password column exists (old schema)
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
    
    if ($checkColumn->rowCount() > 0) {
        echo "Found old 'password' column. Starting migration...\n";
        
        // Step 1: Add new password_hash column
        echo "1. Adding password_hash column...\n";
        $conn->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER email");
        echo "   ✓ password_hash column added\n";
        
        // Step 2: Migrate existing passwords (hash them)
        echo "2. Migrating existing passwords...\n";
        $users = $conn->query("SELECT id, password FROM users WHERE password IS NOT NULL");
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        
        $count = 0;
        foreach ($users as $user) {
            // Hash the existing password
            $hash = password_hash($user['password'], PASSWORD_DEFAULT);
            $updateStmt->execute([
                ':hash' => $hash,
                ':id' => $user['id']
            ]);
            $count++;
        }
        echo "   ✓ Migrated $count user passwords\n";
        
        // Step 3: Make password_hash NOT NULL
        echo "3. Setting password_hash as NOT NULL...\n";
        $conn->exec("ALTER TABLE users MODIFY password_hash VARCHAR(255) NOT NULL");
        echo "   ✓ password_hash set to NOT NULL\n";
        
        // Step 4: Drop old password column
        echo "4. Removing old password column...\n";
        $conn->exec("ALTER TABLE users DROP COLUMN password");
        echo "   ✓ Old password column removed\n";
        
        echo "\n✅ Database migration completed successfully!\n";
        
    } else {
        // Check if password_hash exists
        $checkNewColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
        
        if ($checkNewColumn->rowCount() > 0) {
            echo "✅ Database is already up to date (password_hash column exists)\n";
        } else {
            echo "❌ Error: Neither 'password' nor 'password_hash' column found!\n";
            echo "   Please check your database schema.\n";
        }
    }
    
    // Update admin password if it's still plain text
    echo "\n5. Checking admin user password...\n";
    $adminStmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = 'admin' LIMIT 1");
    $adminStmt->execute();
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Check if password is hashed (bcrypt hashes start with $2y$)
        if (!str_starts_with($admin['password_hash'], '$2y$')) {
            echo "   Admin password appears to be plain text. Updating...\n";
            $newHash = password_hash('admin123', PASSWORD_DEFAULT);
            $updateAdmin = $conn->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $updateAdmin->execute([
                ':hash' => $newHash,
                ':id' => $admin['id']
            ]);
            echo "   ✓ Admin password updated (password: admin123)\n";
        } else {
            echo "   ✓ Admin password is already hashed\n";
        }
    } else {
        echo "   ℹ No admin user found\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Database update completed!\n";
    echo "You can now use the application with the updated schema.\n";
    echo str_repeat("=", 50) . "\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error during migration: " . $e->getMessage() . "\n";
    echo "Please check the error and try again.\n";
    exit(1);
}
?>
