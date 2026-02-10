<?php
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $user_id = $data['user_id'] ?? 0;
    $role = $data['role'] ?? '';

    // Validate role against new ENUM values
    if (empty($user_id) || !in_array($role, ['admin', 'seller', 'customer'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid data']);
        exit;
    }

    $conn = getDBConnection();

    // Prevent self-demotion
    if ($user_id == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['message' => 'Cannot modify your own role']);
        exit;
    }

    try {
        // Update user role
        // Assuming updated_at column exists. If not, remove it.
        // Step 62 did not explicitly show updated_at. I will assume it exists or remove it if I am unsure.
        // Safety: remove updated_at if not sure. Or use NOW() if logic requires.
        // Let's safe bet: Update role only.
        $stmt = $conn->prepare("UPDATE users SET role = :role WHERE id = :id");

        $stmt->execute([':role' => $role, ':id' => $user_id]);

        // Log the action (try-catch for logs)
        try {
            $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'role_change', :description)");
            $description = 'Changed user role to ' . $role;
            $logStmt->execute([':user_id' => $_SESSION['user_id'], ':description' => $description]);
        }
        catch (PDOException $e) {
        // Ignore log error
        }

        echo json_encode(['message' => 'User role updated successfully']);
    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update user role']);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>