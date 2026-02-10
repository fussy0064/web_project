<?php
require_once 'config/db.php';
$conn = getDBConnection();

$users = [
    [
        'username' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'admin123',
        'role' => 'admin'
    ],
    [
        'username' => 'Seller',
        'email' => 'seller@example.com',
        'password' => 'seller123',
        'role' => 'seller'
    ],
    [
        'username' => 'Client',
        'email' => 'client@example.com',
        'password' => 'client123',
        'role' => 'customer'
    ]
];

try {
    foreach ($users as $user) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$user['email']]);

        $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);

        if ($stmt->fetch()) {
            echo "User {$user['username']} already exists. Updating...\n";
            $update = $conn->prepare("UPDATE users SET password_hash = ?, role = ? WHERE email = ?");
            $update->execute([$password_hash, $user['role'], $user['email']]);
        }
        else {
            $insert = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $insert->execute([$user['username'], $user['email'], $password_hash, $user['role']]);
            echo "User {$user['username']} created.\n";
        }
        echo "Credentials: {$user['email']} / {$user['password']}\n---\n";
    }
}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>