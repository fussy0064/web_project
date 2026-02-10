<?php
require_once 'config/db.php';

$username = 'Admin';
$email = 'admin@example.com';
$password = 'admin123';
$role = 'admin';

$password_hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo "Admin user already exists.\n";
        // Optional: Update password
        $update = $conn->prepare("UPDATE users SET password_hash = ?, role = ? WHERE email = ?");
        $update->execute([$password_hash, $role, $email]);
        echo "Admin password reset to 'admin123'.\n";
    }
    else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash, $role]);
        echo "Admin user created successfully.\n";
    }

    echo "Credentials:\nEmail: $email\nPassword: $password\n";
}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>