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

    $seller_id = $_SESSION['user_id'];
    $name = $data['name'] ?? '';
    $category_id = $data['category_id'] ?? 0;
    $brand = $data['brand'] ?? '';
    $model = $data['model'] ?? '';
    $description = $data['description'] ?? '';
    $price = $data['price'] ?? 0;
    $stock_quantity = $data['stock_quantity'] ?? 0;
    $image_url = $data['image_url'] ?? '';
    $condition = $data['condition'] ?? 'New';
    $warranty = $data['warranty'] ?? 'No warranty';

    // Validation
    if (empty($name) || empty($description) || $price <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Required fields missing or invalid']);
        exit;
    }

    $conn = getDBConnection();

    // Insert product
    $stmt = $conn->prepare("INSERT INTO products (seller_id, name, category_id, brand, model, description, price, stock_quantity, image_url, condition, warranty) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisssdissss", $seller_id, $name, $category_id, $brand, $model, $description, $price, $stock_quantity, $image_url, $condition, $warranty);

    if ($stmt->execute()) {
        $product_id = $conn->insert_id;

        // Log product creation
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (?, 'product_create', CONCAT('Created product: ', ?))");
        $logStmt->bind_param("is", $seller_id, $name);
        $logStmt->execute();
        $logStmt->close();

        echo json_encode([
            'message' => 'Product created successfully',
            'product_id' => $product_id
        ]);
    }
    else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to create product']);
    }

    $stmt->close();
    $conn->close();
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>