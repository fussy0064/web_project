<?php
/**
 * storage.php - Uploads a file to Cloudflare R2 (or any S3-compatible
 * storage) using the standard AWS Signature Version 4 request-signing
 * algorithm, implemented directly with cURL - no AWS SDK dependency.
 *
 * Why this exists: on a stateless host like Vercel, PHP's local disk
 * (public/uploads/) doesn't persist between requests, so product image
 * uploads need to go to external object storage instead. This file is only
 * used when the R2_* environment variables are configured; otherwise
 * products/create.php and update.php fall back to the local-disk behavior
 * unchanged (so this has zero effect on a traditional XAMPP/EC2 deployment).
 *
 * Required environment variables (set these in Vercel's Environment
 * Variables, NOT hardcoded here):
 *   R2_ACCOUNT_ID      - your Cloudflare account ID
 *   R2_ACCESS_KEY_ID    - R2 API token access key ID
 *   R2_SECRET_ACCESS_KEY - R2 API token secret
 *   R2_BUCKET           - the bucket name you created
 *   R2_PUBLIC_URL       - the public base URL for the bucket (either the
 *                         bucket's r2.dev URL, or your own custom domain
 *                         connected to the bucket), e.g.
 *                         https://pub-xxxxxxxx.r2.dev
 */

function isExternalStorageConfigured(): bool
{
    return (bool) (getenv('R2_ACCOUNT_ID') && getenv('R2_ACCESS_KEY_ID') && getenv('R2_SECRET_ACCESS_KEY') && getenv('R2_BUCKET'));
}

/**
 * Signs and sends a PUT request to R2 using AWS Signature V4.
 * Returns the public URL of the uploaded object, or throws an Exception.
 */
function uploadToR2(string $localFilePath, string $objectKey, string $contentType): string
{
    $accountId = getenv('R2_ACCOUNT_ID');
    $accessKey = getenv('R2_ACCESS_KEY_ID');
    $secretKey = getenv('R2_SECRET_ACCESS_KEY');
    $bucket = getenv('R2_BUCKET');
    $publicUrl = rtrim(getenv('R2_PUBLIC_URL') ?: '', '/');

    $endpointHost = "{$accountId}.r2.cloudflarestorage.com";
    $region = 'auto';
    $service = 's3';

    $payload = file_get_contents($localFilePath);
    if ($payload === false) {
        throw new Exception('Could not read file for upload');
    }

    $amzDate = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    $payloadHash = hash('sha256', $payload);

    $canonicalUri = '/' . $bucket . '/' . implode('/', array_map('rawurlencode', explode('/', $objectKey)));

    $canonicalHeaders =
        "content-type:{$contentType}\n" .
        "host:{$endpointHost}\n" .
        "x-amz-content-sha256:{$payloadHash}\n" .
        "x-amz-date:{$amzDate}\n";
    $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date';

    $canonicalRequest = implode("\n", [
        'PUT',
        $canonicalUri,
        '', // no query string
        $canonicalHeaders,
        $signedHeaders,
        $payloadHash,
    ]);

    $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
    $stringToSign = implode("\n", [
        'AWS4-HMAC-SHA256',
        $amzDate,
        $credentialScope,
        hash('sha256', $canonicalRequest),
    ]);

    // Derive the signing key: HMAC chain of date -> region -> service -> aws4_request
    $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    $authorizationHeader = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

    $url = "https://{$endpointHost}{$canonicalUri}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: {$contentType}",
            "x-amz-content-sha256: {$payloadHash}",
            "x-amz-date: {$amzDate}",
            "Authorization: {$authorizationHeader}",
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('R2 upload failed: ' . $curlError);
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("R2 upload failed with HTTP {$httpCode}: {$response}");
        throw new Exception('R2 upload failed with HTTP ' . $httpCode);
    }

    if ($publicUrl) {
        return $publicUrl . '/' . $objectKey;
    }
    // Fallback: the bucket's default r2.dev-style public URL, if you didn't
    // set R2_PUBLIC_URL. You should set it for a real deployment instead.
    return "https://{$bucket}.{$accountId}.r2.dev/{$objectKey}";
}
