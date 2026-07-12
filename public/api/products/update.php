<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
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
    if ($isFormData && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $data['image_url'] = 'uploads/' . $file_name;
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

    echo json_encode(['message' => 'Product updated successfully']);

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