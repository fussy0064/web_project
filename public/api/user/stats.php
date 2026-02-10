<?php
require_once '../config.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get order count
$orderStmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
$orderStmt->bind_param("i", $user_id);
$orderStmt->execute();
$orderResult = $orderStmt->get_result()->fetch_assoc();

// Get cart items count
$cartStmt = $conn->prepare("SELECT SUM(quantity) as cart_items FROM cart WHERE user_id = ?");
$cartStmt->bind_param("i", $user_id);
$cartStmt->execute();
$cartResult = $cartStmt->get_result()->fetch_assoc();

echo json_encode([
    'order_count' => $orderResult['order_count'] ?? 0,
    'cart_items' => $cartResult['cart_items'] ?? 0
]);

$orderStmt->close();
$cartStmt->close();
$conn->close();
?>