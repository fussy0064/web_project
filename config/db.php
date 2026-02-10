<?php
$host = 'localhost';
$db_name = 'electronics_db';
$username = 'fussy'; // Assuming default user for this environment or 'root' if configured, using what's likely available or standard. 
// Given the environment, I'll try standard 'root' or 'fussy' with no password or 'password'. 
// However, the specific user 'admin' mentioned in the plan was for the APP level. 
// For DB connection, I will use standard defaults. 
// Wait, the user provided constraint that I should use MySQL.
// I will use 'root' and empty password which is common in dev, or 'fussy' if that's the system user. 
// Better yet, I'll use environment variables or default to a safe guess.
// Let's try 'root' with empty password first, as is common in many local setups, or standard user.
// actually, I'll use 'root' and no password.

$username = 'fussy';
$password = 'fussy';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    echo "Connection error: " . $e->getMessage();
    exit;
}
?>