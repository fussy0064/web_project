<?php
require_once '../config.php';

$conn = getDBConnection();

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Build query
$query = "SELECT p.*, u.username as seller_name 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.status = 'active'";

$params = [];

if (!empty($category) && $category !== 'all') {
    $query .= " AND p.category_id = :category";
    $params[':category'] = $category;
}

if (!empty($location) && $location !== 'all') {
    $query .= " AND p.city = :location";
    $params[':location'] = $location;
}

if (!empty($min_price)) {
    $query .= " AND p.price >= :min_price";
    $params[':min_price'] = $min_price;
}

if (!empty($max_price)) {
    $query .= " AND p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.brand LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add base URL to images if local
foreach ($products as &$product) {
    if (!empty($product['image_url']) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
        // If relative path (local upload), prepend base URL
        $product['image_url'] = BASE_URL . '/' . $product['image_url'];
    }
}

echo json_encode($products);
?>