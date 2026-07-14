<?php
require_once '../config.php';
require_once '../lib/storage.php';

// Check if user is seller or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle both JSON and FormData
    $isFormData = isset($_POST['name']);

    if ($isFormData) {
        // FormData from admin form
        $seller_id = $_POST['seller_id'] ?? $_SESSION['user_id'];
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? 0;
        $brand = $_POST['brand'] ?? '';
        $model = $_POST['model'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock_quantity = $_POST['stock_quantity'] ?? 0;
        $condition = $_POST['condition'] ?? 'New';
        $warranty = $_POST['warranty'] ?? 'No warranty';

        // Handle file upload
        $image_url = '';
        if (isset($_FILES['image'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // App-level size cap, independent of php.ini's
                // upload_max_filesize/post_max_size (which may be raised or
                // lowered by hosting config without the app's knowledge).
                if ($_FILES['image']['size'] > MAX_UPLOAD_BYTES) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Image too large. Max size is ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . 'MB']);
                    exit;
                }

                // SECURITY FIX: previously any file extension was accepted and saved
                // directly into a web-served directory (e.g. a file named
                // "shell.php" would have been saved and could be executed by
                // Apache). Now we whitelist extensions AND verify the upload is
                // actually a real image using getimagesize().
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

                if (isExternalStorageConfigured()) {
                    // External object storage (e.g. Cloudflare R2) - required
                    // on stateless hosts like Vercel where local disk doesn't
                    // persist between requests.
                    try {
                        $image_url = uploadToR2($_FILES['image']['tmp_name'], 'products/' . $file_name, $mimeType);
                    }
                    catch (Exception $e) {
                        error_log('R2 upload failed: ' . $e->getMessage());
                        http_response_code(500);
                        echo json_encode(['message' => 'Failed to upload image to storage. Contact the administrator.']);
                        exit;
                    }
                }
                else {
                    // Local disk (traditional single-server deployment, e.g. XAMPP/EC2)
                    $upload_dir = __DIR__ . '/../../uploads/';
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            http_response_code(500);
                            echo json_encode(['message' => 'Failed to create upload directory']);
                            exit;
                        }
                    }

                    if (!is_writable($upload_dir)) {
                        error_log('Upload directory not writable: ' . realpath($upload_dir));
                        http_response_code(500);
                        echo json_encode(['message' => 'Server upload directory is not writable. Contact the administrator.']);
                        exit;
                    }

                    $upload_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_url = 'uploads/' . $file_name;
                    }
                    else {
                        error_log('move_uploaded_file failed writing to: ' . $upload_path);
                        http_response_code(500);
                        echo json_encode(['message' => 'Failed to save uploaded file. Contact the administrator.']);
                        exit;
                    }
                }
            }
            elseif ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['image']['error'] === UPLOAD_ERR_FORM_SIZE) {
                // PHP itself rejected the file for exceeding
                // upload_max_filesize/post_max_size or the form's MAX_FILE_SIZE.
                http_response_code(400);
                echo json_encode(['message' => 'Image too large. Max size is ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . 'MB']);
                exit;
            }
            elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                // File was sent but some other error occurred
                http_response_code(400);
                echo json_encode(['message' => 'File upload error code: ' . $_FILES['image']['error']]);
                exit;
            }
        }

        // If no file uploaded, keep existing (or empty for new)
        if (empty($image_url)) {
            $image_url = $_POST['image_url'] ?? '';
        }
    }
    else {
        // JSON data
        $data = json_decode(file_get_contents('php://input'), true);

        $seller_id = $_SESSION['user_id'];
        $name = $data['name'] ?? '';
        $category_id = $data['category_id'] ?? 0;
        $brand = $data['brand'] ?? '';
        $model = $data['model'] ?? '';
        $description = $data['description'] ?? '';
        $price = $data['price'] ?? 0;
        $stock_quantity = $data['stock_quantity'] ?? 0;
        $image_url = $data['image_url'] ?? '';
        $condition = $data['condition'] ?? 'New';
        $warranty = $data['warranty'] ?? 'No warranty';
    }

    // Validation
    if (empty($name) || empty($description) || $price <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Required fields missing or invalid']);
        exit;
    }

    $conn = getDBConnection();

    // Insert product using PDO
    $stmt = $conn->prepare("INSERT INTO products (seller_id, name, category_id, brand, model, description, price, stock_quantity, image_url, `condition`, warranty) 
                           VALUES (:seller_id, :name, :category_id, :brand, :model, :description, :price, :stock_quantity, :image_url, :condition, :warranty)");

    $stmt->bindParam(':seller_id', $seller_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':brand', $brand);
    $stmt->bindParam(':model', $model);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':stock_quantity', $stock_quantity);
    $stmt->bindParam(':image_url', $image_url);
    $stmt->bindParam(':condition', $condition);
    $stmt->bindParam(':warranty', $warranty);

    try {
        $stmt->execute();
        $product_id = $conn->lastInsertId();

        // Log product creation
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description) VALUES (:user_id, 'product_create', CONCAT('Created product: ', :name))");
        $logStmt->bindParam(':user_id', $seller_id);
        $logStmt->bindParam(':name', $name);
        $logStmt->execute();

        echo json_encode([
            'message' => 'Product created successfully',
            'product_id' => $product_id
        ]);
    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to create product: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>