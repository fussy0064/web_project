<?php
require_once '../config.php';

// Check if user is seller or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$seller_id = $_SESSION['user_id'];
$conn = getDBConnection();

try {
    // Fetch recent orders containing products from this seller
    $query = "SELECT DISTINCT o.id, o.created_at, o.total, o.status, u.username as customer_name 
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              JOIN users u ON o.user_id = u.id
              WHERE oi.seller_id = :seller_id
              ORDER BY o.created_at DESC
              LIMIT 5";

    $stmt = $conn->prepare($query);
    $stmt->execute([':seller_id' => $seller_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($orders);

}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching orders: ' . $e->getMessage()]);
}
?>