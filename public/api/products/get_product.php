<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Get product ID from query parameter
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid product ID']);
    exit;
}

try {
    // Fetch single product with seller information
    $query = "SELECT p.*, 
                     c.name as category_name,
                     u.username as seller_name,
                     u.email as seller_email
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id
              LEFT JOIN users u ON p.seller_id = u.id
              WHERE p.id = :product_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching product', 'error' => $e->getMessage()]);
}
?>
