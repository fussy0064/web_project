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

    // Create notification
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New Order', ?, 'new_order')");
    $stmt->bind_param("is", $seller_id, $message);

    if ($stmt->execute()) {
        // Also update product stock if needed
        if (!empty($items)) {
            foreach ($items as $item) {
                if (isset($item['id']) && isset($item['quantity'])) {
                    $updateStmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND seller_id = ?");
                    $updateStmt->bind_param("iii", $item['quantity'], $item['id'], $seller_id);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
        }

        echo json_encode(['message' => 'Notification sent']);
    }
    else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to send notification']);
    }

    $stmt->close();
    $conn->close();
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>