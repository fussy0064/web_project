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

// Get seller's total products
$productStmt = $conn->prepare("SELECT COUNT(*) as my_products FROM products WHERE seller_id = ?");
$productStmt->bind_param("i", $seller_id);
$productStmt->execute();
$productResult = $productStmt->get_result()->fetch_assoc();

// Get active orders for seller
$orderStmt = $conn->prepare("SELECT COUNT(DISTINCT o.id) as active_orders 
                           FROM orders o 
                           JOIN order_items oi ON o.id = oi.order_id 
                           WHERE oi.seller_id = ? AND o.status IN ('pending', 'processing')");
$orderStmt->bind_param("i", $seller_id);
$orderStmt->execute();
$orderResult = $orderStmt->get_result()->fetch_assoc();

// Get total stock
$stockStmt = $conn->prepare("SELECT SUM(stock_quantity) as total_stock FROM products WHERE seller_id = ?");
$stockStmt->bind_param("i", $seller_id);
$stockStmt->execute();
$stockResult = $stockStmt->get_result()->fetch_assoc();

// Get total revenue
$revenueStmt = $conn->prepare("SELECT SUM(oi.total) as my_revenue 
                             FROM order_items oi 
                             JOIN orders o ON o.id = oi.order_id 
                             WHERE oi.seller_id = ? AND o.status = 'delivered'");
$revenueStmt->bind_param("i", $seller_id);
$revenueStmt->execute();
$revenueResult = $revenueStmt->get_result()->fetch_assoc();

echo json_encode([
    'my_products' => $productResult['my_products'] ?? 0,
    'active_orders' => $orderResult['active_orders'] ?? 0,
    'total_stock' => $stockResult['total_stock'] ?? 0,
    'my_revenue' => $revenueResult['my_revenue'] ?? 0
]);

// Close statements
$productStmt->close();
$orderStmt->close();
$stockStmt->close();
$revenueStmt->close();
$conn->close();
?>