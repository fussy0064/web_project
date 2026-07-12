<?php
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied. Admin only.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'] ?? null;

    if (!$order_id) {
        http_response_code(400);
        echo json_encode(['message' => 'Order ID is required']);
        exit;
    }

    $conn = getDBConnection();
    $conn->beginTransaction();

    try {
        // Delete order items first (foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $order_id]);

        // Delete the order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = :order_id");
        $stmt->execute([':order_id' => $order_id]);

        // Log the action
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'order_delete', :description)");
        $logStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':description' => 'Deleted order #' . $order_id
        ]);

        $conn->commit();
        echo json_encode(['message' => 'Order deleted successfully']);

    }
    catch (Exception $e) {
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