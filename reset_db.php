<?php
require_once 'config/db.php';
$conn = getDBConnection();

try {
    // Disable foreign key checks to allow truncation
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = ['users', 'products', 'orders', 'order_items', 'system_logs', 'notifications', 'inventory_logs', 'settings'];

    foreach ($tables as $table) {
        try {
            // Check if table exists before truncating
            $check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($check->rowCount() > 0) {
                $conn->exec("TRUNCATE TABLE $table");
                echo "Truncated $table.\n";
            }
        }
        catch (PDOException $e) {
            echo "Error truncating $table: " . $e->getMessage() . "\n";
        }
    }

    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Database reset successfully.\n";

}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>