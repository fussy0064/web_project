<?php
// config.php
// Suppress error display to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'electronics_db');
define('DB_USER', 'root'); // Standard XAMPP user
define('DB_PASS', ''); // Standard XAMPP password (usually empty)
// define('DB_USER', 'fussy');  // Old user
// define('DB_PASS', 'fussy');  // Old password
// define('BASE_URL', 'http://localhost/Electronics_Ordering_System/web_project/public');
define('BASE_URL', '/Electronics_Ordering_System/web_project/public');

function getDBConnection()
{
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->setAttribute(PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT, false);
        return $conn;
    }
    catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// SECURITY FIX: this used to reflect *any* requester's Origin header back
// as an allowed origin while also allowing credentials (cookies). That
// combination lets any malicious website make credentialed requests using
// a logged-in visitor's session (their browser would attach the session
// cookie automatically) and read the response. Restrict to known origins.
//
// Add your real production domain(s) here (e.g. 'https://your-domain.com').
$allowedOrigins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:3000',
    'http://localhost:5173',
    // 'https://your-production-domain.com',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Handle OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize database connection
$conn = getDBConnection();

// Helper function for input sanitization
function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Helper function to validate email
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Helper function to validate required fields
function validateRequired($fields, $data)
{
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = ucfirst($field) . ' is required';
        }
    }
    return $errors;
}

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Suppress errors to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Helper function to send error response
function sendError($message, $statusCode = 400)
{
    http_response_code($statusCode);
    echo json_encode(['message' => $message]);
    exit;
}