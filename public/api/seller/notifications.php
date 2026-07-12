<?php
require_once '../config.php';

// Check if user is seller or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$conn = getDBConnection();
$seller_id = $_SESSION['user_id'];

try {
    // Get notifications
    $stmt = $conn->prepare("SELECT id, title, message, type, is_read, created_at 
                           FROM notifications 
                           WHERE user_id = :seller_id 
                           ORDER BY created_at DESC 
                           LIMIT 20");
    $stmt->execute([':seller_id' => $seller_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['notifications' => $notifications]);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching notifications']);
}
?>