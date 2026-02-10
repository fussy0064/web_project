<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Validation
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

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['message' => 'Password must be at least 6 characters']);
        exit;
    }

    // $conn is already available from config.php, but we can call getDBConnection() if strictly needed.
    // However, config.php creates $conn. Let's use the $conn from config or get a new one to be safe/explicit if code style prefers.
    // Given config.php content: $conn = getDBConnection();
    // We can just use $conn.

    if (!isset($conn)) {
        $conn = getDBConnection();
    }

    try {
        // Check if user already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $checkStmt->execute([':email' => $email, ':username' => $username]);

        if ($checkStmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(['message' => 'Username or email already exists']);
            exit;
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

        echo json_encode([
            'message' => 'Registration successful',
            'user_id' => $user_id
        ]);

    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Registration failed: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>