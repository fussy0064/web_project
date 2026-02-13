<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing order ID']);
        exit;
    }

    $id = intval($data['id']);
    $conn = getDBConnection();

    try {
        $conn->beginTransaction();

        // Delete order items first
        $stmtItems = $conn->prepare("DELETE FROM order_items WHERE order_id = :id");
        $stmtItems->execute([':id' => $id]);

        // Delete order
        $stmtOrder = $conn->prepare("DELETE FROM orders WHERE id = :id");
        $stmtOrder->execute([':id' => $id]);

        $conn->commit();

        echo json_encode(['message' => 'Order deleted successfully']);
    }
    catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['message' => 'Error deleting order: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>