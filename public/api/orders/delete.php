<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// SECURITY FIX: this endpoint previously had no authentication check at all,
// meaning anyone (even logged-out visitors) could delete any order by ID.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied. Admin only.']);
    exit;
}

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

        try {
            $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'order_delete', :description)");
            $logStmt->execute([':user_id' => $_SESSION['user_id'], ':description' => 'Deleted order #' . $id]);
        }
        catch (PDOException $e) {
            // Ignore log error
        }

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