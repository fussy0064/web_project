<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'electronics_db'); // Changed from electroshop_db to electronics_db
define('DB_USER', 'fussy'); // Changed from root to fussy
define('DB_PASS', 'fussy'); // Changed from empty to fussy
define('BASE_URL', 'http://localhost/electroshop');

function getDBConnection()
{
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    }
    catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Enable CORS
header("Access-Control-Allow-Origin: " . BASE_URL);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Start session
session_start();

// Set JSON response header
header('Content-Type: application/json');

// Handle OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>