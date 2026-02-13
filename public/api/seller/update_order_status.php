<?php
require_once '../config.php';

// Check if user is seller or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'] ?? 0;
    $status = $data['status'] ?? '';

    $validStatuses = ['pending', 'processing', 'ready', 'shipped', 'delivered', 'cancelled'];

    if (empty($order_id) || !in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid data']);
        exit;
    }

    $conn = getDBConnection();

    try {
        // Verify order belongs to seller (or at least contains items from seller)
        // Strictly speaking, an order might have items from multiple sellers.
        // Updating the MAIN order status might be restricted to platform admin or require consensus.
        // For this system, let's assume if seller has items in the order, they can update the status.
        // Or maybe just check if order exists. 
        // Better: Check if seller has items in this order.

        if ($_SESSION['role'] === 'seller') {
            $checkStmt = $conn->prepare("SELECT 1 FROM order_items WHERE order_id = :order_id AND seller_id = :seller_id");
            $checkStmt->execute([':order_id' => $order_id, ':seller_id' => $_SESSION['user_id']]);

            if ($checkStmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['message' => 'Order not found or access denied']);
                exit;
            }
        }

        $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $order_id]);

        // Log action (optional)
        // ...

        echo json_encode(['message' => 'Order status updated successfully']);

    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error updating order: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>