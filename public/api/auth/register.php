<?php
ob_start();
require_once '../config.php';

function returnJSON($data, $code = 200)
{
    ob_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        returnJSON(['message' => 'Method not allowed'], 405);
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        returnJSON(['message' => 'Invalid JSON input'], 400);
    }

    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        returnJSON(['message' => 'All fields are required'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        returnJSON(['message' => 'Invalid email format'], 400);
    }

    if (strlen($password) < 6) {
        returnJSON(['message' => 'Password must be at least 6 characters'], 400);
    }

    $conn = getDBConnection();

    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
    $checkStmt->execute([':email' => $email, ':username' => $username]);

    if ($checkStmt->rowCount() > 0) {
        returnJSON(['message' => 'Username or email already exists'], 409);
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password, 'customer')");

    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    $user_id = $conn->lastInsertId();

    // Log registration
    try {
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'registration', 'New user registered')");
        $logStmt->execute([':user_id' => $user_id]);
    }
    catch (PDOException $e) {
    // Ignore log error
    }

    returnJSON([
        'message' => 'Registration successful',
        'user_id' => $user_id
    ], 201);

}
catch (Exception $e) {
    returnJSON(['message' => 'Registration failed: ' . $e->getMessage()], 500);
}