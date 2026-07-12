<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $seller_id = $data['seller_id'] ?? 0;
    $order_id = $data['order_id'] ?? 0;
    $message = $data['message'] ?? 'New order received';
    $items = $data['items'] ?? [];

    if (empty($seller_id)) {
        http_response_code(400);
        echo json_encode(['message' => 'Seller ID required']);
        exit;
    }

    $conn = getDBConnection();

    try {
        // Create notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:user_id, 'New Order', :message, 'new_order')");
        $stmt->execute([':user_id' => $seller_id, ':message' => $message]);

        // Also update product stock if needed
        if (!empty($items)) {
            foreach ($items as $item) {
                if (isset($item['id']) && isset($item['quantity'])) {
                    // Note: This logic seems to deduct stock again? 
                    // Usually stock is deducted at order creation. 
                    // This file might be redundant if order creation handles stock.
                    // But assuming this is a separate logic requested by legacy code, we keep it but ensure PDO.

                    $updateStmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :id AND seller_id = :seller_id");
                    $updateStmt->execute([
                        ':quantity' => $item['quantity'],
                        ':id' => $item['id'],
                        ':seller_id' => $seller_id
                    ]);
                }
            }
        }

        echo json_encode(['message' => 'Notification sent']);

    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error sending notification: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>