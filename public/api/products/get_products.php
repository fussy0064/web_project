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
$types = "";

if ($category !== 'all') {
    $query .= " AND p.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);

$stmt->close();
$conn->close();
?>