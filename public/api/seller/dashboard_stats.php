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
    // Get seller's total products
    $stmt = $conn->prepare("SELECT COUNT(*) as my_products FROM products WHERE seller_id = :seller_id");
    $stmt->bindParam(':seller_id', $seller_id);
    $stmt->execute();
    $productResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get active orders for seller
    // Get active orders for seller
    // Count orders that have items from this seller and are in active state
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT o.id) as active_orders 
                           FROM orders o 
                           JOIN order_items oi ON o.id = oi.order_id 
                           WHERE oi.seller_id = :seller_id AND o.status IN ('pending', 'processing')");
    $stmt->execute([':seller_id' => $seller_id]);
    $orderResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get total stock
    $stmt = $conn->prepare("SELECT COALESCE(SUM(stock_quantity), 0) as total_stock FROM products WHERE seller_id = :seller_id");
    $stmt->bindParam(':seller_id', $seller_id);
    $stmt->execute();
    $stockResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get total revenue
    $stmt = $conn->prepare("SELECT COALESCE(SUM(oi.price_at_purchase * oi.quantity), 0) as my_revenue 
                           FROM order_items oi 
                           JOIN orders o ON o.id = oi.order_id 
                           WHERE oi.seller_id = :seller_id AND o.status = 'delivered'");
    $stmt->bindParam(':seller_id', $seller_id);
    $stmt->execute();
    $revenueResult = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'my_products' => $productResult['my_products'] ?? 0,
        'active_orders' => $orderResult['active_orders'] ?? 0,
        'total_stock' => $stockResult['total_stock'] ?? 0,
        'my_revenue' => $revenueResult['my_revenue'] ?? 0
    ]);

}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
?>