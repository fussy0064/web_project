<?php
require_once '../config.php';

// Check if user is seller or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notification_id = $data['notification_id'] ?? 0;

    if (empty($notification_id)) {
        http_response_code(400);
        echo json_encode(['message' => 'Notification ID required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $notification_id, ':user_id' => $_SESSION['user_id']]);

        echo json_encode(['message' => 'Notification marked as read']);

    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error updating notification: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>