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

    $conn = getDBConnection();

    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create order
        $orderStmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, shipping, tax, total, shipping_address, phone, city, payment_method) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $orderStmt->bind_param("isddddssss", $user_id, $order_number, $subtotal, $shipping, $tax, $total, $shipping_address, $phone, $city, $payment_method);

        if (!$orderStmt->execute()) {
            throw new Exception('Failed to create order');
        }

        $order_id = $conn->insert_id;
        $orderStmt->close();

        // Create order items
        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($items as $item) {
            // Get product details
            $productStmt = $conn->prepare("SELECT seller_id, price FROM products WHERE id = ?");
            $productStmt->bind_param("i", $item['id']);
            $productStmt->execute();
            $productResult = $productStmt->get_result();

            if ($productResult->num_rows > 0) {
                $product = $productResult->fetch_assoc();
                $item_total = $item['price'] * $item['quantity'];

                $itemStmt->bind_param("iiiiid", $order_id, $item['id'], $product['seller_id'], $item['quantity'], $item['price'], $item_total);
                $itemStmt->execute();

                // Update product stock
                $updateStmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $updateStmt->bind_param("ii", $item['quantity'], $item['id']);
                $updateStmt->execute();
                $updateStmt->close();

                // Send notification to seller
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New Order', CONCAT('New order #', ?, ' received'), 'new_order')");
                $notifStmt->bind_param("ii", $product['seller_id'], $order_id);
                $notifStmt->execute();
                $notifStmt->close();
            }
            $productStmt->close();
        }

        $itemStmt->close();

        // Clear user's cart
        $cartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $cartStmt->bind_param("i", $user_id);
        $cartStmt->execute();
        $cartStmt->close();

        // Log order creation
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (?, 'order_create', CONCAT('Created order #', ?))");
        $logStmt->bind_param("ii", $user_id, $order_id);
        $logStmt->execute();
        $logStmt->close();

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
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['message' => 'Order failed: ' . $e->getMessage()]);
    }

    $conn->close();
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>