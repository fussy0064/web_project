<?php
require_once 'public/api/config.php';
$conn = getDBConnection();

$users = [
    [
        'username' => 'admin',
        'email' => 'admin@electroshop.com',
        'password' => 'admin123',
        'role' => 'admin'
    ],
    [
        'username' => 'seller',
        'email' => 'seller@electroshop.com',
        'password' => 'seller123',
        'role' => 'seller'
    ],
    [
        'username' => 'customer',
        'email' => 'customer@electroshop.com',
        'password' => 'customer123',
        'role' => 'customer'
    ]
];

echo "Fixing users...\n";

foreach ($users as $user) {
    // Check by username OR email to avoid duplicates
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
    $stmt->execute([':u' => $user['username'], ':e' => $user['email']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $hash = password_hash($user['password'], PASSWORD_DEFAULT);

    if ($existing) {
        $stmt = $conn->prepare("UPDATE users SET username = :u, email = :e, password_hash = :p, role = :r WHERE id = :id");
        $stmt->execute([
            ':u' => $user['username'],
            ':e' => $user['email'],
            ':p' => $hash,
            ':r' => $user['role'],
            ':id' => $existing['id']
        ]);
        echo " ✓ Updated user {$user['username']} ({$user['role']})\n";
    }
    else {
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:u, :e, :p, :r)");
            $stmt->execute([
                ':u' => $user['username'],
                ':e' => $user['email'],
                ':p' => $hash,
                ':r' => $user['role']
            ]);
            echo " ✓ Created user {$user['username']} ({$user['role']})\n";
        }
        catch (PDOException $e) {
            echo " ✗ Failed to create {$user['username']}: " . $e->getMessage() . "\n";
        }
    }
}
?>