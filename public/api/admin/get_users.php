<?php
require_once '../config.php';

// Check if user is admin1
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$conn = getDBConnection();

try {
    // Get all users with their details
    $query = "SELECT 
        id, 
        username, 
        email, 
        role, 
        status, 
        created_at
        FROM users 
        WHERE status = 'active'
        ORDER BY created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add is_admin flag
    foreach ($users as &$user) {
        $user['is_admin'] = ($user['role'] === 'admin');
    }

    echo json_encode($users);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error fetching users']);
}
?>