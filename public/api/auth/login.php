<?php
// Start output buffering before any output
ob_start();

// Suppress all errors to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(0);

// Use the shared config (DB connection, session, CORS, JSON header) instead
// of a separate hardcoded DB connection. This used to define its own
// DB_HOST/DB_USER/DB_PASS constants (pointed at 'localhost' instead of
// config.php's '127.0.0.1'), so changing DB credentials in config.php for
// production would silently NOT apply here.
require_once __DIR__ . '/../config.php';

function returnJSON($data, $code = 200)
{
    // Clear any previous output
    if (ob_get_length())
        ob_clean();

    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Records a failed attempt so it counts toward the lockout window.
function recordFailedAttempt($conn, $email, $ip)
{
    try {
        $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)");
        $stmt->execute([':email' => $email, ':ip' => $ip]);
    }
    catch (PDOException $e) {
        // Never let logging failures block the response
    }
}

try {
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
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (empty($email) || empty($password)) {
        returnJSON(['message' => 'Email and password are required'], 400);
    }

    // BRUTE-FORCE PROTECTION: block further attempts if this email or this
    // IP has racked up too many failed logins recently. Checked before the
    // password is even verified.
    try {
        $windowStart = date('Y-m-d H:i:s', strtotime('-' . LOGIN_LOCKOUT_MINUTES . ' minutes'));

        $checkStmt = $conn->prepare(
            "SELECT COUNT(*) as attempts FROM login_attempts
             WHERE attempted_at > :window AND (email = :email OR ip_address = :ip)"
        );
        $checkStmt->execute([':window' => $windowStart, ':email' => $email, ':ip' => $ip]);
        $attemptCount = (int) $checkStmt->fetch(PDO::FETCH_ASSOC)['attempts'];

        if ($attemptCount >= LOGIN_MAX_ATTEMPTS) {
            returnJSON([
                'message' => 'Too many failed login attempts. Please try again in ' . LOGIN_LOCKOUT_MINUTES . ' minutes.'
            ], 429);
        }
    }
    catch (PDOException $e) {
        // If the rate-limit check itself fails, don't block real logins —
        // fail open on this check specifically.
    }

    // $conn is already established by config.php
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

            // Successful login clears this account's recent failed attempts.
            try {
                $clearStmt = $conn->prepare("DELETE FROM login_attempts WHERE email = :email");
                $clearStmt->execute([':email' => $email]);
            }
            catch (PDOException $e) {
            }

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
            recordFailedAttempt($conn, $email, $ip);
            returnJSON(['message' => 'Invalid credentials'], 401);
        }
    }
    else {
        recordFailedAttempt($conn, $email, $ip);
        returnJSON(['message' => 'Invalid credentials'], 401);
    }

}
catch (Exception $e) {
    returnJSON(['message' => 'Server error'], 500);
}
?>
