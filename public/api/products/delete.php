<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
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
    $product_id = $data['id'];

    // If user is seller, verify ownership
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

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    echo json_encode(['message' => 'Product deleted']);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error deleting product']);
}
?>