<?php
require_once __DIR__ . '/../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!is_array($data) || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'User ID is required']);
        exit;
    }

    $user_id = $data['user_id'];

    if ($user_id == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['message' => 'Cannot delete yourself']);
        exit;
    }

    $conn = getDBConnection();

    try {
        // Start transaction for safety
        $conn->beginTransaction();

        // Check if user exists first
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = :id");
        $checkStmt->execute([':id' => $user_id]);
        if (!$checkStmt->fetch()) {
             $conn->rollBack();
             http_response_code(404);
             echo json_encode(['message' => 'User not found']);
             exit;
        }

        // Soft Delete: Set user to inactive
        $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = :id");
        $stmt->execute([':id' => $user_id]);

        // Also deactivate their products
        // This ensures the site doesn't show products from blocked users
        $prodStmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE seller_id = :id");
        $prodStmt->execute([':id' => $user_id]);
        
        $affectedProducts = $prodStmt->rowCount();

        // Log action
        try {
            $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'deactivate_user', :description)");
            $description = "Deactivated user ID $user_id and $affectedProducts products";
            $logStmt->execute([':user_id' => $_SESSION['user_id'], ':description' => $description]);
        }
        catch (PDOException $e) {
            // Ignore log error
        }

        $conn->commit();
        echo json_encode(['message' => 'User and their products deactivated successfully']);

    }
    catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    }
    catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        http_response_code(500);
        echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>