<?php
// config.php
// Suppress error display to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

// DB connection settings come from environment variables when present
// (e.g. set in your Vercel project's Environment Variables, or in a real
// server's environment), falling back to XAMPP-friendly local defaults so
// nothing changes for local development.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'electronics_db');
define('DB_USER', getenv('DB_USER') ?: 'root'); // Standard XAMPP user
define('DB_PASS', getenv('DB_PASS') ?: ''); // Standard XAMPP password (usually empty)

// BASE_URL is only used to prefix OLD-style relative image_url values
// (e.g. "uploads/xyz.jpg") that were saved before switching to external
// storage. New uploads (via Vercel Blob or similar) are stored as full
// URLs already and skip this prefixing entirely (see products/*.php).
// Set BASE_URL as an env var for your real deployment; defaults to the
// local XAMPP subfolder path for backward compatibility.
define('BASE_URL', getenv('BASE_URL') ?: '/Electronics_Ordering_System/web_project/public');

// Detect whether we're running on a stateless serverless host (no shared
// local disk / long-lived process between requests) where PHP's default
// file-based sessions won't survive across requests. Vercel sets VERCEL=1
// in its function environment; this also lets you force DB-backed sessions
// anywhere by setting USE_DB_SESSIONS=1 yourself.
define('USE_DB_SESSIONS', getenv('VERCEL') || getenv('USE_DB_SESSIONS'));

// Shared limits used across auth/products endpoints.
// Vercel Functions have a hard 4.5MB request body limit at the platform
// level (separate from and smaller than a typical php.ini post_max_size),
// so on Vercel we cap uploads below that to fail with our own clear error
// instead of a raw platform-level rejection. Override with UPLOAD_MAX_MB.
$defaultUploadMb = getenv('VERCEL') ? 4 : 5;
define('MAX_UPLOAD_BYTES', (getenv('UPLOAD_MAX_MB') ?: $defaultUploadMb) * 1024 * 1024);
define('MIN_PASSWORD_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

function getDBConnection()
{
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
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
// Add your real production domain(s) here (e.g. your Vercel domain).
$allowedOrigins = array_filter([
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:3000',
    'http://localhost:5173',
    getenv('APP_ORIGIN') ?: null, // e.g. https://electro-hub-topaz.vercel.app
]);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Session cookie hardening. Detects HTTPS (including behind a reverse proxy
// via X-Forwarded-Proto, common on EC2/load balancers, and on Vercel) so
// cookies get the Secure flag automatically once you're on HTTPS, without
// breaking local HTTP development. SameSite=Lax blocks the cookie being
// sent on cross-site requests (CSRF-style), while still allowing normal
// top-level navigation (e.g. following a link into the site).
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

// Initialize database connection (needed by the DB session handler too,
// so this has to happen before session_start()).
$conn = getDBConnection();

// DB-BACKED SESSIONS: on a stateless host like Vercel, PHP's default
// file-based session storage doesn't survive between requests (each
// request can run on a different ephemeral instance with no shared disk),
// which would silently log users out constantly. When USE_DB_SESSIONS is
// on, store session data in the `sessions` table instead. On a normal
// single-server setup this stays off and PHP's default file sessions are
// used as before (no behavior change).
if (USE_DB_SESSIONS) {
    class DbSessionHandler implements SessionHandlerInterface
    {
        private $conn;

        public function __construct($conn)
        {
            $this->conn = $conn;
        }

        public function open($savePath, $sessionName): bool
        {
            return true;
        }

        public function close(): bool
        {
            return true;
        }

        public function read($id): string
        {
            try {
                $stmt = $this->conn->prepare("SELECT data FROM sessions WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? $row['data'] : '';
            }
            catch (PDOException $e) {
                return '';
            }
        }

        public function write($id, $data): bool
        {
            try {
                $stmt = $this->conn->prepare(
                    "INSERT INTO sessions (id, data, last_activity) VALUES (:id, :data, :time)
                     ON DUPLICATE KEY UPDATE data = :data2, last_activity = :time2"
                );
                $now = time();
                return $stmt->execute([
                    ':id' => $id,
                    ':data' => $data,
                    ':time' => $now,
                    ':data2' => $data,
                    ':time2' => $now,
                ]);
            }
            catch (PDOException $e) {
                return false;
            }
        }

        public function destroy($id): bool
        {
            try {
                $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id = :id");
                return $stmt->execute([':id' => $id]);
            }
            catch (PDOException $e) {
                return false;
            }
        }

        public function gc($max_lifetime): int|false
        {
            try {
                $stmt = $this->conn->prepare("DELETE FROM sessions WHERE last_activity < :cutoff");
                $stmt->execute([':cutoff' => time() - $max_lifetime]);
                return $stmt->rowCount();
            }
            catch (PDOException $e) {
                return false;
            }
        }
    }

    session_set_save_handler(new DbSessionHandler($conn), true);
}

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
