<?php
require_once '../config.php';

// This endpoint returns all products
// GET /api/products/ - Get all products

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = getDBConnection();

    try {
        // Get all products with seller information
        $query = "SELECT 
            p.id,
            p.seller_id,
            p.name,
            p.category_id,
            p.brand,
            p.model,
            p.description,
            p.price,
            p.stock_quantity,
            p.image_url,
            p.condition,
            p.warranty,
            p.status,
            p.created_at,
            u.username as seller_name,
            c.name as category_name
        FROM products p
        LEFT JOIN users u ON p.seller_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format products for response
        foreach ($products as &$product) {
            $product['is_available'] = ($product['stock_quantity'] > 0) ? 1 : 0;
            $product['price'] = floatval($product['price']);
            $product['stock_quantity'] = intval($product['stock_quantity']);

            // Add base URL to images if local
            if (!empty($product['image_url']) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                $product['image_url'] = BASE_URL . '/' . $product['image_url'];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($products);
    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error fetching products: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>