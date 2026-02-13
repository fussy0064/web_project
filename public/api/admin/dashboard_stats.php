<?php
require_once '../config.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$conn = getDBConnection();

try {
    // Get total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $userResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get total products
    $stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products");
    $stmt->execute();
    $productResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get total orders (excluding cancelled)
    $stmt = $conn->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE status != 'cancelled'");
    $stmt->execute();
    $orderCountResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get total revenue (only delivered)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total_revenue FROM orders WHERE status = 'delivered'");
    $stmt->execute();
    $revenueResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Combine results for response compatibility
    $orderResult = [
        'total_orders' => $orderCountResult['total_orders'],
        'total_revenue' => $revenueResult['total_revenue']
    ];



    // Get active sellers
    $stmt = $conn->prepare("SELECT COUNT(*) as active_sellers FROM users WHERE role = 'seller' AND status = 'active'");
    $stmt->execute();
    $sellerResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get today's revenue (only delivered)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as daily_revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'delivered'");
    $stmt->execute();
    $todayResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get growth data
    $stmt = $conn->prepare("SELECT COUNT(*) as yesterday_users FROM users WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $stmt->execute();
    $yesterdayResult = $stmt->fetch(PDO::FETCH_ASSOC);

    $userGrowth = $yesterdayResult['yesterday_users'] > 0 ? 
        round((($userResult['total_users'] - $yesterdayResult['yesterday_users']) / $yesterdayResult['yesterday_users']) * 100, 2) : 0;

    // Get total logs
    $stmt = $conn->prepare("SELECT COUNT(*) as total_logs FROM system_logs WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $logResult = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'total_users' => $userResult['total_users'] ?? 0,
        'total_products' => $productResult['total_products'] ?? 0,
        'total_orders' => $orderResult['total_orders'] ?? 0,
        'total_revenue' => $orderResult['total_revenue'] ?? 0,
        'active_sellers' => $sellerResult['active_sellers'] ?? 0,
        'daily_revenue' => $todayResult['daily_revenue'] ?? 0,
        'user_growth' => $userGrowth,
        'product_growth' => 5, // Static for demo
        'order_growth' => 12, // Static for demo
        'seller_growth' => 2, // Static for demo
        'system_health' => 98, // Static for demo
        'total_logs' => $logResult['total_logs'] ?? 0
    ]);

}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
?>