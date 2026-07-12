<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$conn = getDBConnection();

// Handle GET request to read profile
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $conn->prepare("SELECT id, username, email, role, created_at, status FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode($user);
        }
        else {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
        }
    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Database error']);
    }
}
// Handle PUT request to update profile
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Only allow updating unprivileged fields
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    $updates = [];
    $params = [':id' => $_SESSION['user_id']];

    // Validate and prepare email update
    if ($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid email format']);
            exit;
        }

        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $_SESSION['user_id']]);
        if ($check->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(['message' => 'Email already in use']);
            exit;
        }

        $updates[] = "email = :email";
        $params[':email'] = $email;
    }

    // Validate and prepare password update
    if ($password) {
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['message' => 'Password must be at least 6 characters']);
            exit;
        }
        $updates[] = "password_hash = :password";
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if (empty($updates)) {
        echo json_encode(['message' => 'No changes provided']);
        exit;
    }

    try {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['message' => 'Profile updated successfully']);

        // Log action
        try {
            $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'profile_update', 'User updated profile')");
            $logStmt->execute([':user_id' => $_SESSION['user_id']]);
        }
        catch (Exception $e) {
        }

    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Update failed: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>