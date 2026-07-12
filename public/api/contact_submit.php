<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $host = 'localhost';
    $dbname = 'electronics_db';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate Input
if (!isset($data['name']) || empty($data['name'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Name is required']);
    exit;
}

if (!isset($data['email']) || empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Valid email is required']);
    exit;
}

if (!isset($data['message']) || empty($data['message'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Message is required']);
    exit;
}

$name = htmlspecialchars(strip_tags($data['name']));
$email = htmlspecialchars(strip_tags($data['email']));
$subject = isset($data['subject']) ? htmlspecialchars(strip_tags($data['subject'])) : 'New Contact Request';
$message = htmlspecialchars(strip_tags($data['message']));

try {
    // Insert into DB
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message]);

    echo json_encode(['message' => 'Thank you! Your message has been sent successfully.', 'id' => $pdo->lastInsertId()]);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to store message: ' . $e->getMessage()]);
}
?>