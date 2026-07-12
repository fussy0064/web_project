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
    // Fetch all orders containing products from this seller
    // Fetch all orders
    $query = "SELECT o.id, o.created_at, o.total, o.status, u.username as customer_name, u.id as customer_id,
              COUNT(oi.id) as item_count 
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              JOIN users u ON o.user_id = u.id";

    // If seller, filter by seller_id
    if ($_SESSION['role'] === 'seller') {
        $query .= " WHERE oi.seller_id = :seller_id";
    }

    $query .= " GROUP BY o.id ORDER BY o.created_at DESC";

    $stmt = $conn->prepare($query);
    if ($_SESSION['role'] === 'seller') {
        $stmt->bindValue(':seller_id', $seller_id);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($orders);

}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching orders: ' . $e->getMessage()]);
}
?>