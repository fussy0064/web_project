<?php
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$conn = getDBConnection();

// Get all users with their details
$query = "SELECT 
    id, 
    username, 
    email, 
    role, 
    status, 
    created_at,
    (SELECT role FROM users WHERE id = ?) as current_user_role
    FROM users 
    ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    // Check if user is admin (simplified - you might have a separate is_admin field)
    $row['is_admin'] = ($row['role'] === 'admin' && $row['id'] == 1); // Assuming user ID 1 is super admin

    $users[] = $row;
}

echo json_encode($users);

$stmt->close();
$conn->close();
?>