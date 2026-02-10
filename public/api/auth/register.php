<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required fields']);
    exit;
}

$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];

// Basic validation
if (empty($username) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid email format']);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['message' => 'Email already registered']);
    exit;
}

// Hash password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
if ($stmt->execute([$username, $email, $password_hash])) {
    http_response_code(201);
    echo json_encode(['message' => 'User registered successfully']);
}
else {
    http_response_code(500);
    echo json_encode(['message' => 'Registration failed']);
}
?>