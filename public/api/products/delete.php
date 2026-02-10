<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Product ID required']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(['message' => 'Product deleted']);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error deleting product']);
}
?>