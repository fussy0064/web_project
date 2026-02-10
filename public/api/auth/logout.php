<?php
require_once '../config.php';

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    $conn = getDBConnection();
    $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (?, 'logout', 'User logged out')");
    $logStmt->bind_param("i", $_SESSION['user_id']);
    $logStmt->execute();
    $logStmt->close();
    $conn->close();
}

// Destroy session
session_destroy();

echo json_encode(['message' => 'Logout successful']);
?>