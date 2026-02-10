<?php
require_once '../config.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Please login to place order']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $user_id = $_SESSION['user_id'];
    $items = $data['items'] ?? [];
    $subtotal = $data['subtotal'] ?? 0;
    $shipping = $data['shipping'] ?? 0;
    $tax = $data['tax'] ?? 0;
    $total = $data['total'] ?? 0;
    $shipping_address = $data['shipping_address'] ?? '';
    $phone = $data['phone'] ?? '';
    $city = $data['city'] ?? '';
    $payment_method = $data['payment_method'] ?? 'COD';

    if (empty($items) || empty($shipping_address)) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid order data']);
        exit;
    }

    if (!isset($conn)) {
        $conn = getDBConnection();
    }

    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Start transaction
    $conn->beginTransaction();

    try {
        // Create order
        $orderStmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, shipping, tax, total, shipping_address, phone, city, payment_method) 
                                   VALUES (:user_id, :order_number, :subtotal, :shipping, :tax, :total, :shipping_address, :phone, :city, :payment_method)");

        $orderStmt->execute([
            ':user_id' => $user_id,
            ':order_number' => $order_number,
            ':subtotal' => $subtotal,
            ':shipping' => $shipping,
            ':tax' => $tax,
            ':total' => $total,
            ':shipping_address' => $shipping_address,
            ':phone' => $phone,
            ':city' => $city,
            ':payment_method' => $payment_method
        ]);

        $order_id = $conn->lastInsertId();

        // Create order items
        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, total) VALUES (:order_id, :product_id, :seller_id, :quantity, :price, :total)");

        foreach ($items as $item) {
            // Get product details
            $productStmt = $conn->prepare("SELECT seller_id, price FROM products WHERE id = :id");
            $productStmt->execute([':id' => $item['id']]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $item_total = $item['price'] * $item['quantity'];

                $itemStmt->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $item['id'],
                    ':seller_id' => $product['seller_id'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price'],
                    ':total' => $item_total
                ]);

                // Update product stock
                $updateStmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :id");
                $updateStmt->execute([
                    ':quantity' => $item['quantity'],
                    ':id' => $item['id']
                ]);

                // Send notification to seller
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:user_id, 'New Order', :message, 'new_order')");
                $message = 'New order #' . $order_id . ' received';
                $notifStmt->execute([
                    ':user_id' => $product['seller_id'],
                    ':message' => $message
                ]);
            }
        }

        // Clear user's cart
        $cartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
        $cartStmt->execute([':user_id' => $user_id]);

        // Log order creation
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'order_create', :description)");
        $description = 'Created order #' . $order_id;
        $logStmt->execute([
            ':user_id' => $user_id,
            ':description' => $description
        ]);

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'message' => 'Order placed successfully',
            'order' => [
                'id' => $order_id,
                'order_number' => $order_number,
                'total' => $total
            ]
        ]);

    }
    catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        http_response_code(500);
        echo json_encode(['message' => 'Order failed: ' . $e->getMessage()]);
    }
// PDO connection closes automatically when script ends or variable destroyed

}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>