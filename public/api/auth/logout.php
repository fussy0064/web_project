<?php
// Start output buffering
ob_start();

// Suppress errors
ini_set('display_errors', 0);
error_reporting(0);

// Use the shared config (session, CORS, JSON header) instead of a separate
// hardcoded DB connection block (this file didn't even need a DB connection).
require_once __DIR__ . '/../config.php';

try {
    // Destroy session
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    echo json_encode(['message' => 'Logged out successfully']);
}
catch (Exception $e) {
    echo json_encode(['message' => 'Logout completed']);
}
?>
