<?php
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied']);
    exit;
}

$conn = getDBConnection();

// Get total users
$userStmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
$userStmt->execute();
$userResult = $userStmt->get_result()->fetch_assoc();

// Get total products
$productStmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products");
$productStmt->execute();
$productResult = $productStmt->get_result()->fetch_assoc();

// Get total orders
$orderStmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(total) as total_revenue FROM orders");
$orderStmt->execute();
$orderResult = $orderStmt->get_result()->fetch_assoc();

// Get active sellers
$sellerStmt = $conn->prepare("SELECT COUNT(*) as active_sellers FROM users WHERE role = 'seller' AND status = 'active'");
$sellerStmt->execute();
$sellerResult = $sellerStmt->get_result()->fetch_assoc();

// Get today's revenue
$todayStmt = $conn->prepare("SELECT SUM(total) as daily_revenue FROM orders WHERE DATE(created_at) = CURDATE()");
$todayStmt->execute();
$todayResult = $todayStmt->get_result()->fetch_assoc();

// Get growth data (simplified)
$yesterdayStmt = $conn->prepare("SELECT COUNT(*) as yesterday_users FROM users WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$yesterdayStmt->execute();
$yesterdayResult = $yesterdayStmt->get_result()->fetch_assoc();

$userGrowth = $yesterdayResult['yesterday_users'] > 0 ? 
    round((($userResult['total_users'] - $yesterdayResult['yesterday_users']) / $yesterdayResult['yesterday_users']) * 100, 2) : 0;

// Get total logs
$logStmt = $conn->prepare("SELECT COUNT(*) as total_logs FROM system_logs WHERE DATE(created_at) = CURDATE()");
$logStmt->execute();
$logResult = $logStmt->get_result()->fetch_assoc();

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

// Close statements and connection
$userStmt->close();
$productStmt->close();
$orderStmt->close();
$sellerStmt->close();
$todayStmt->close();
$yesterdayStmt->close();
$logStmt->close();
$conn->close();
?>