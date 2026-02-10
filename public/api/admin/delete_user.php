<?php
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? 0;

    if (empty($user_id)) {
        http_response_code(400);
        echo json_encode(['message' => 'User ID is required']);
        exit;
    }

    if ($user_id == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['message' => 'Cannot delete yourself']);
        exit;
    }

    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);

        if ($stmt->rowCount() > 0) {
            // Log action
            try {
                $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'delete_user', :description)");
                $description = "Deleted user ID $user_id";
                $logStmt->execute([':user_id' => $_SESSION['user_id'], ':description' => $description]);
            }
            catch (PDOException $e) {
            }

            echo json_encode(['message' => 'User deleted successfully']);
        }
        else {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
        }

    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error deleting user: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>