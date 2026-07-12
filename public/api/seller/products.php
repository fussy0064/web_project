<?php
require_once '../config.php';

// Check if user is seller or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$conn = getDBConnection();
$seller_id = $_SESSION['user_id'];

try {
    $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
    $params = [];

    if ($_SESSION['role'] === 'seller') {
        $query .= " WHERE p.seller_id = :seller_id";
        $params[':seller_id'] = $seller_id;
    }

    $query .= " ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add base URL to images if local
    foreach ($products as &$product) {
        if (!empty($product['image_url']) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
            $product['image_url'] = BASE_URL . '/' . $product['image_url'];
        }
    }

    echo json_encode($products);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching products: ' . $e->getMessage()]);
}
?>
