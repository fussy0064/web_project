<?php
/**
 * test_storage.php - a throwaway diagnostic endpoint to confirm the R2
 * (or other external storage) upload path actually works once deployed,
 * before relying on it for real product images.
 *
 * SECURITY: protected by a shared secret (DIAGNOSTIC_KEY env var) so random
 * visitors can't use it to upload arbitrary files to your bucket. DELETE
 * THIS FILE once you've confirmed things work.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/storage.php';

header('Content-Type: application/json');

$providedKey = $_GET['key'] ?? '';
$expectedKey = getenv('DIAGNOSTIC_KEY') ?: '';

if (!$expectedKey || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(404);
    echo json_encode(['message' => 'Not found']);
    exit;
}

if (!isExternalStorageConfigured()) {
    echo json_encode([
        'external_storage_configured' => false,
        'message' => 'R2_ACCOUNT_ID / R2_ACCESS_KEY_ID / R2_SECRET_ACCESS_KEY / R2_BUCKET are not all set. Local-disk fallback would be used, which does not persist on Vercel.',
    ]);
    exit;
}

try {
    // Write a tiny known-content test file and upload it.
    $tmpPath = tempnam(sys_get_temp_dir(), 'r2test');
    file_put_contents($tmpPath, 'diagnostic test file from web_project');

    $url = uploadToR2($tmpPath, 'diagnostics/test-' . time() . '.txt', 'text/plain');
    unlink($tmpPath);

    echo json_encode([
        'external_storage_configured' => true,
        'upload_succeeded' => true,
        'uploaded_url' => $url,
        'note' => 'Open uploaded_url in your browser to confirm it is really reachable.',
    ]);
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'external_storage_configured' => true,
        'upload_succeeded' => false,
        'error' => $e->getMessage(),
    ]);
}
