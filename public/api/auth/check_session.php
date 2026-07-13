<?php
// Start output buffering
ob_start();

// Suppress errors
ini_set('display_errors', 0);
error_reporting(0);

// Use the shared config (DB connection, session, CORS, JSON header) instead
// of a separate hardcoded DB connection pointed at a different host.
require_once __DIR__ . '/../config.php';

try {
    if (isset($_SESSION['user_id'])) {
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
