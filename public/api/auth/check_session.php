<?php
require_once '../config.php';

if (isset($_SESSION['user_id'])) {
    try {
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
        } else {
            session_destroy();
            echo json_encode(['authenticated' => false]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['authenticated' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['authenticated' => false]);
}
?>
