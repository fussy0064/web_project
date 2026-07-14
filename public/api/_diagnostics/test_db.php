<?php
/**
 * test_db.php - a throwaway diagnostic endpoint to confirm the database
 * connection (host, port, SSL, credentials) actually works once deployed,
 * before relying on it across the whole app.
 *
 * SECURITY: protected by a shared secret (DIAGNOSTIC_KEY env var) so random
 * visitors can't probe it. DELETE THIS FILE once you've confirmed things
 * work - it's not meant to stay in a production deployment.
 */

require_once __DIR__ . '/../config.php';

$providedKey = $_GET['key'] ?? '';
$expectedKey = getenv('DIAGNOSTIC_KEY') ?: '';

if (!$expectedKey || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(404);
    echo json_encode(['message' => 'Not found']);
    exit;
}

try {
    $stmt = $conn->query('SELECT COUNT(*) as user_count FROM users');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $sslStmt = $conn->query("SHOW STATUS LIKE 'Ssl_cipher'");
    $sslRow = $sslStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'db_connected' => true,
        'user_count' => $row['user_count'],
        'ssl_in_use' => !empty($sslRow['Value']),
        'ssl_cipher' => $sslRow['Value'] ?? null,
        'db_backed_sessions' => (bool) USE_DB_SESSIONS,
    ]);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['db_connected' => false, 'error' => $e->getMessage()]);
}
