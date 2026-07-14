<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/storage.php';
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

// Handle both JSON and FormData
$data = [];
$isFormData = false;

if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
    $data = $_POST;
    $isFormData = true;
}
else {
    $data = json_decode(file_get_contents('php://input'), true);
}

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Product ID required']);
    exit;
}

try {
    $product_id = $data['id'];
    $conn = getDBConnection(); // Ensure connection is established

    // Authorization check
    if ($_SESSION['role'] === 'seller') {
        // Seller can only update their own products
        $check = $conn->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ?");
        $check->execute([$product_id, $_SESSION['user_id']]);
        if (!$check->fetch()) {
            http_response_code(403);
            echo json_encode(['message' => 'Access denied: You can only update your own products']);
            exit;
        }
    }
    else if ($_SESSION['role'] !== 'admin') {
        // Only seller and admin allowed
        http_response_code(403);
        echo json_encode(['message' => 'Access denied']);
        exit;
    }

    // Handle File Upload if present
    if ($isFormData && isset($_FILES['image']) &&
        ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['image']['error'] === UPLOAD_ERR_FORM_SIZE)) {
        http_response_code(400);
        echo json_encode(['message' => 'Image too large. Max size is ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . 'MB']);
        exit;
    }

    if ($isFormData && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // App-level size cap, independent of php.ini's
        // upload_max_filesize/post_max_size.
        if ($_FILES['image']['size'] > MAX_UPLOAD_BYTES) {
            http_response_code(400);
            echo json_encode(['message' => 'Image too large. Max size is ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . 'MB']);
            exit;
        }

        // SECURITY FIX: previously any file extension was accepted (e.g. a
        // "shell.php" upload would have been saved into a web-served
        // directory and could be executed). Whitelist extensions and verify
        // the upload is actually a real image using getimagesize().
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions, true)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid image type. Allowed: jpg, jpeg, png, gif, webp']);
            exit;
        }

        $imageInfo = @getimagesize($_FILES['image']['tmp_name']);
        if ($imageInfo === false) {
            http_response_code(400);
            echo json_encode(['message' => 'Uploaded file is not a valid image']);
            exit;
        }

        $mimeType = $imageInfo['mime'] ?? 'application/octet-stream';
        $file_name = uniqid() . '.' . $file_extension;
        $imageUploadWarning = null;

        if (isExternalStorageConfigured()) {
            try {
                $data['image_url'] = uploadToR2($_FILES['image']['tmp_name'], 'products/' . $file_name, $mimeType);
            }
            catch (Exception $e) {
                error_log('R2 upload failed: ' . $e->getMessage());
                $imageUploadWarning = 'Product saved, but the image failed to upload.';
            }
        }
        else {
            $upload_dir = __DIR__ . '/../../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (!is_writable($upload_dir)) {
                error_log('Upload directory not writable: ' . realpath($upload_dir));
                $imageUploadWarning = 'Product saved, but the image could not be updated (server upload directory is not writable).';
            }
            else {
                $upload_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $data['image_url'] = 'uploads/' . $file_name;
                }
                else {
                    error_log('move_uploaded_file failed writing to: ' . $upload_path);
                    $imageUploadWarning = 'Product saved, but the image failed to upload.';
                }
            }
        }
    }
    // If we're updating and NOT uploading a new file, but client sent image_url, keep it.
    // However, usually client sends existing image_url if not changing.
    // If client sends empty image_url, it clears it? 
    // Usually FormData won't include image_url if only file is sent.

    // Prepare update query dynamically
    $fields = [];
    $params = [];

    // List of allowed fields
    $allowedFields = ['name', 'category_id', 'brand', 'model', 'description', 'price', 'stock_quantity', 'image_url', 'condition', 'warranty', 'status'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "`$field` = :$field";
            $params[":$field"] = $data[$field];
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['message' => 'No fields to update']);
        exit;
    }

    $params[':id'] = $product_id;

    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'message' => $imageUploadWarning ?? 'Product updated successfully'
    ]);

    // Log action
    try {
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'product_update', :desc)");
        $logStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':desc' => "Updated product ID $product_id"
        ]);
    }
    catch (Exception $e) {
    // Ignore log errors
    }

}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error updating product: ' . $e->getMessage()]);
}
?>