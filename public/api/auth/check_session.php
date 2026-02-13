<?php
// Start output buffering
ob_start();

// Suppress errors
ini_set('display_errors', 0);
error_reporting(0);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'electronics_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection()
{
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    }
    catch (PDOException $e) {
        return null;
    }
}

try {
    if (isset($_SESSION['user_id'])) {
        $conn = getDBConnection();

        if (!$conn) {
            echo json_encode(['authenticated' => false]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, username, email, role, status FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode([
                'authenticated' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ],
                'role' => $user['role']
            ]);
        }
        else {
            session_destroy();
            echo json_encode(['authenticated' => false]);
        }
    }
    else {
        echo json_encode(['authenticated' => false]);
    }
}
catch (Exception $e) {
    echo json_encode(['authenticated' => false, 'error' => 'Server error']);
}
?>