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

// Shared limits used across auth/products endpoints.
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5MB app-level cap, independent of php.ini
define('MIN_PASSWORD_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

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

// Session cookie hardening. Detects HTTPS (including behind a reverse proxy
// via X-Forwarded-Proto, common on EC2/load balancers) so cookies get the
// Secure flag automatically once you're on HTTPS, without breaking local
// HTTP development. SameSite=Lax blocks the cookie being sent on
// cross-site requests (CSRF-style), while still allowing normal top-level
// navigation (e.g. following a link into the site).
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
    (($_SERVER['SERVER_PORT'] ?? '') == 443)
);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

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