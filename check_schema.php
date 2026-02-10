<?php
require_once 'config/db.php';
$conn = getDBConnection();

try {
    $tables = ['order_items'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW CREATE TABLE $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        print_r($result);
    }
}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>