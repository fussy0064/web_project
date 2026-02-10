<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get order count
    $orderStmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = :user_id");
    $orderStmt->execute([':user_id' => $user_id]);
    $orderResult = $orderStmt->fetch(PDO::FETCH_ASSOC);
    $order_count = $orderResult['order_count'] ?? 0;
    
    // Get cart items count
    $cartStmt = $conn->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = :user_id");
    $cartStmt->execute([':user_id' => $user_id]);
    $cartResult = $cartStmt->fetch(PDO::FETCH_ASSOC);
    $cart_items = $cartResult['cart_count'] ?? 0;
    
    // Get total spent (optional)
    $spentStmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total_spent FROM orders WHERE user_id = :user_id AND status != 'cancelled'");
    $spentStmt->execute([':user_id' => $user_id]);
    $spentResult = $spentStmt->fetch(PDO::FETCH_ASSOC);
    $total_spent = $spentResult['total_spent'] ?? 0;
    
    // Get pending orders count
    $pendingStmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM orders WHERE user_id = :user_id AND status = 'pending'");
    $pendingStmt->execute([':user_id' => $user_id]);
    $pendingResult = $pendingStmt->fetch(PDO::FETCH_ASSOC);
    $pending_orders = $pendingResult['pending_count'] ?? 0;
    
    echo json_encode([
        'order_count' => (int)$order_count,
        'cart_items' => (int)$cart_items,
        'total_spent' => (float)$total_spent,
        'pending_orders' => (int)$pending_orders
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching user stats', 'error' => $e->getMessage()]);
}
?>
