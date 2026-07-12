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

    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'customer';
    // Validate role
    $validRoles = ['admin', 'seller', 'customer'];
    if (!in_array($role, $validRoles)) {
        $role = 'customer';
    }
    $dbRole = $role;


    // Status is optional, default to active if column exists, but we are not sure.
    // We will just insert required fields.

    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    $conn = getDBConnection();

    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $email, ':username' => $username]);

        if ($stmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(['message' => 'User already exists']);
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        // Assuming status column might not exist based on previous checks, so skipping it.
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password, :role)");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $password_hash,
            ':role' => $dbRole
        ]);

        $newUserId = $conn->lastInsertId();

        // Log action
        try {
            $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'create_user', :description)");
            $description = "Created user $username ($dbRole)";
            $logStmt->execute([':user_id' => $_SESSION['user_id'], ':description' => $description]);
        }
        catch (PDOException $e) {
        }

        echo json_encode(['message' => 'User created successfully', 'user_id' => $newUserId]);

    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error creating user: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>