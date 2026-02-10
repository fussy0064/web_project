<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required fields']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['message' => 'All fields are required']);
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    // Password is correct, start session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    echo json_encode(['message' => 'Login successful', 'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]]);
}
else {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid email or password']);
}
?>