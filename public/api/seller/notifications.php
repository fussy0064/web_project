<?php
require_once '../config.php';

// Check if user is seller
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$conn = getDBConnection();
$seller_id = $_SESSION['user_id'];

// Get notifications
$stmt = $conn->prepare("SELECT id, title, message, type, is_read, created_at 
                       FROM notifications 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC 
                       LIMIT 20");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode(['notifications' => $notifications]);

$stmt->close();
$conn->close();
?>