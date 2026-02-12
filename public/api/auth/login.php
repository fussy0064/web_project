<?php
ob_start();
require_once '../config.php';

function returnJSON($data, $code = 200) {
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

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        returnJSON(['message' => 'Email and password are required'], 400);
    }

    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            try {
                $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'login', 'User logged in successfully')");
                $logStmt->execute([':user_id' => $user['id']]);
            } catch (PDOException $e) {
                // Ignore log error
            }

            returnJSON([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            returnJSON(['message' => 'Invalid credentials'], 401);
        }
    } else {
        returnJSON(['message' => 'Invalid credentials'], 401);
    }

} catch (Exception $e) {
    returnJSON(['message' => 'Server error: ' . $e->getMessage()], 500);
}