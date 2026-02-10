<?php
require_once '../config.php';

// Check if user is seller or super admin
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

    // Insert product using PDO
    $stmt = $conn->prepare("INSERT INTO products (seller_id, name, category_id, brand, model, description, price, stock_quantity, image_url, `condition`, warranty) 
                           VALUES (:seller_id, :name, :category_id, :brand, :model, :description, :price, :stock_quantity, :image_url, :condition, :warranty)");

    $stmt->bindParam(':seller_id', $seller_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':brand', $brand);
    $stmt->bindParam(':model', $model);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':stock_quantity', $stock_quantity);
    $stmt->bindParam(':image_url', $image_url);
    $stmt->bindParam(':condition', $condition);
    $stmt->bindParam(':warranty', $warranty);

    try {
        $stmt->execute();
        $product_id = $conn->lastInsertId();

        // Log product creation
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'product_create', CONCAT('Created product: ', :name))");
        $logStmt->bindParam(':user_id', $seller_id);
        $logStmt->bindParam(':name', $name);
        $logStmt->execute();

        echo json_encode([
            'message' => 'Product created successfully',
            'product_id' => $product_id
        ]);
    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to create product: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>