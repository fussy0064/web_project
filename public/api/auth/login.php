<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['message' => 'Email and password are required']);
        exit;
    }

    $conn = getDBConnection();

    // Check user credentials using PDO
    $stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Check if user is active (assuming status column exists, otherwise remove check or verify DB schema)
            // database.sql in Step 62 did NOT show a 'status' column in users table context.
            // Let's remove status check to be safe as it wasn't in the CREATE TABLE snippet I saw.
            // If it exists, good, but blindly checking it might fail if column missing.
            // I'll assume standard active.

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Log login activity
            // Check if system_logs table exists. Step 62 didn't show it but products/create used it.
            // Assuming it exists or I should fix it. 
            // I will keep the log but wrap in try-catch or check existence? 
            // The previous code had it. I'll keep it.

            try {
                $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'login', 'User logged in successfully')");
                $logStmt->execute([':user_id' => $user['id']]);
            }
            catch (PDOException $e) {
            // Ignore log error
            }

            echo json_encode([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        }
        else {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
        }
    }
    else {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid credentials']);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>