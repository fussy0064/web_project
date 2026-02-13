<?php
require_once '../config.php';

// Check if user is admin1
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

    try {
        // Get current stock
        $checkStmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = :id");
        $checkStmt->execute([':id' => $product_id]);

        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['message' => 'Product not found']);
            exit;
        }

        $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $previous_quantity = $product['stock_quantity'];
        $new_quantity = $previous_quantity + $quantity;

        // Update product stock
        $updateStmt = $conn->prepare("UPDATE products SET stock_quantity = :quantity WHERE id = :id");
        $updateStmt->execute([':quantity' => $new_quantity, ':id' => $product_id]);

        // Log inventory change
        try {
            $logStmt = $conn->prepare("INSERT INTO inventory_logs (product_id, user_id, type, quantity, previous_quantity, new_quantity, notes) VALUES (:product_id, :user_id, 'add', :quantity, :prev_qty, :new_qty, :notes)");
            $logStmt->execute([
                ':product_id' => $product_id,
                ':user_id' => $_SESSION['user_id'],
                ':quantity' => $quantity,
                ':prev_qty' => $previous_quantity,
                ':new_qty' => $new_quantity,
                ':notes' => $notes
            ]);
        }
        catch (PDOException $e) {
        }

        // Create notification for seller
        $sellerStmt = $conn->prepare("SELECT seller_id FROM products WHERE id = :id");
        $sellerStmt->execute([':id' => $product_id]);
        $seller = $sellerStmt->fetch(PDO::FETCH_ASSOC);

        if ($seller) {
            try {
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:user_id, 'Stock Added', :message, 'system')");
                $message = "Admin added $quantity units to your product";
                $notifStmt->execute([':user_id' => $seller['seller_id'], ':message' => $message]);
            }
            catch (PDOException $e) {
            }
        }

        echo json_encode(['message' => 'Stock added successfully']);

    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error adding stock: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>