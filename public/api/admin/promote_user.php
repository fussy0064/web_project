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

    // Update user role
    $stmt = $conn->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $role, $user_id);

    if ($stmt->execute()) {
        // Log the action
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (?, 'role_change', CONCAT('Changed user role to ', ?))");
        $logStmt->bind_param("is", $_SESSION['user_id'], $role);
        $logStmt->execute();
        $logStmt->close();

        echo json_encode(['message' => 'User role updated successfully']);
    }
    else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to update user role']);
    }

    $stmt->close();
    $conn->close();
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>