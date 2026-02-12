<?php
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied. Admin only.']);
    exit;
}

$conn = getDBConnection();

try {
    // Get all orders with user and item details
    $query = "SELECT 
                o.*,
                u.username as customer_name,
                u.email as customer_email,
                COUNT(oi.id) as item_count
              FROM orders o
              JOIN users u ON o.user_id = u.id
              LEFT JOIN order_items oi ON o.id = oi.order_id
              GROUP BY o.id
              ORDER BY o.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get items for each order
    foreach ($orders as &$order) {
        $itemStmt = $conn->prepare("
            SELECT 
                oi.*,
                p.name as product_name,
                p.image_url
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $itemStmt->execute([':order_id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($orders);

}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching orders: ' . $e->getMessage()]);
}
?>