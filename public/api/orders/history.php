<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    if ($role === 'admin' && isset($_GET['all']) && $_GET['all'] === 'true') {
        // Admin viewing all orders
        $stmt = $conn->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
        $stmt->execute();
    }
    else {
        // Customer viewing their own errors
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
    }

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for each order (simplified: normally would do join or separate call)
    foreach ($orders as &$order) {
        $stmtItems = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmtItems->execute([$order['id']]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($orders);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching orders']);
}
?>