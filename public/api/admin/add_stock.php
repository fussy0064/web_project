<?php
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $product_id = $data['product_id'] ?? 0;
    $quantity = $data['quantity'] ?? 0;
    $supplier = $data['supplier'] ?? '';
    $cost_price = $data['cost_price'] ?? 0;
    $notes = $data['notes'] ?? '';

    if (empty($product_id) || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid product or quantity']);
        exit;
    }

    $conn = getDBConnection();

    // Get current stock
    $checkStmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $checkStmt->bind_param("i", $product_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
        exit;
    }

    $product = $result->fetch_assoc();
    $previous_quantity = $product['stock_quantity'];
    $new_quantity = $previous_quantity + $quantity;

    $checkStmt->close();

    // Update product stock
    $updateStmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $new_quantity, $product_id);

    if ($updateStmt->execute()) {
        // Log inventory change
        $logStmt = $conn->prepare("INSERT INTO inventory_logs (product_id, user_id, type, quantity, previous_quantity, new_quantity, notes) VALUES (?, ?, 'add', ?, ?, ?, ?)");
        $logStmt->bind_param("iiiiis", $product_id, $_SESSION['user_id'], $quantity, $previous_quantity, $new_quantity, $notes);
        $logStmt->execute();

        // Create notification for seller
        $sellerStmt = $conn->prepare("SELECT seller_id FROM products WHERE id = ?");
        $sellerStmt->bind_param("i", $product_id);
        $sellerStmt->execute();
        $sellerResult = $sellerStmt->get_result();

        if ($sellerResult->num_rows > 0) {
            $seller = $sellerResult->fetch_assoc();
            $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Stock Added', CONCAT('Admin added ', ?, ' units to your product'), 'system')");
            $notifStmt->bind_param("ii", $seller['seller_id'], $quantity);
            $notifStmt->execute();
            $notifStmt->close();
        }

        $sellerStmt->close();
        $logStmt->close();

        echo json_encode(['message' => 'Stock added successfully']);
    }
    else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to add stock']);
    }

    $updateStmt->close();
    $conn->close();
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>