<?php
// Test password verification
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$passwords_to_test = ['admin123', 'password', 'admin', 'Password123'];

foreach ($passwords_to_test as $password) {
    if (password_verify($password, $hash)) {
        echo "✓ Password '$password' matches the hash!\n";
    } else {
        echo "✗ Password '$password' does NOT match\n";
    }
}

// Generate new hash for admin123
echo "\n--- Generating new hash for 'admin123' ---\n";
$new_hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "New hash: $new_hash\n";
?>
