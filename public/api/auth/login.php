<?php
// Start output buffering before any output
ob_start();

// Suppress all errors to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(0);

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

function returnJSON($data, $code = 200)
{
    // Clear any previous output
    if (ob_get_length())
        ob_clean();

    // Set headers
    http_response_code($code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    echo json_encode($data);
    exit;
}

try {
    // Handle OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        returnJSON(['message' => 'Method not allowed'], 405);
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        returnJSON(['message' => 'Invalid JSON input'], 400);
    }

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        returnJSON(['message' => 'Email and password are required'], 400);
    }

    $conn = getDBConnection();

    if (!$conn) {
        returnJSON(['message' => 'Database connection failed'], 500);
    }

    $stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            try {
                $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'login', 'User logged in successfully')");
                $logStmt->execute([':user_id' => $user['id']]);
            }
            catch (PDOException $e) {
            // Ignore log error
            }

            returnJSON([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        }
        else {
            returnJSON(['message' => 'Invalid credentials'], 401);
        }
    }
    else {
        returnJSON(['message' => 'Invalid credentials'], 401);
    }

}
catch (Exception $e) {
    returnJSON(['message' => 'Server error'], 500);
}
?>