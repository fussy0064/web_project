<?php
require_once '../config.php';

$conn = getDBConnection();

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT p.*, u.username as seller_name 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.status = 'active'";

$params = [];

if ($category !== 'all') {
    $query .= " AND p.category_id = :category";
    $params[':category'] = $category;
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

echo json_encode($products);
?>