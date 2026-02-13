<?php
// products/delete.php
require_once __DIR__ . '/../config.php';

// Session already started in config.php

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Product ID required']);
    exit;
}

try {
    $conn = getDBConnection();
    $product_id = $data['id'];

    if ($_SESSION['role'] === 'seller') {
        $check = $conn->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ?");
        $check->execute([$product_id, $_SESSION['user_id']]);
        if (!$check->fetch()) {
            http_response_code(403);
            echo json_encode(['message' => 'Access denied: You can only delete your own products']);
            exit;
        }
    }
    else if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['message' => 'Access denied']);
        exit;
    }

    // Soft delete: set status to inactive to preserve order history
    $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$product_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Product blocked/deactivated successfully (Soft Delete)']);
    }
    else {
        http_response_code(404);
        echo json_encode(['message' => 'Product not found or already inactive']);
    }

}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
}
?>