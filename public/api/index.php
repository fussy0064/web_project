<?php
require_once 'config.php';

// Get requested route
$route = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Simple routing
switch (true) {
    case preg_match('/^auth\/(.+)/', $route, $matches):
        require_once "auth/{$matches[1]}.php";
        break;

    case preg_match('/^admin\/(.+)/', $route, $matches):
        require_once "admin/{$matches[1]}.php";
        break;

    case preg_match('/^seller\/(.+)/', $route, $matches):
        require_once "seller/{$matches[1]}.php";
        break;

    case preg_match('/^products\/(.+)/', $route, $matches):
        require_once "products/{$matches[1]}.php";
        break;

    case preg_match('/^orders\/(.+)/', $route, $matches):
        require_once "orders/{$matches[1]}.php";
        break;

    case preg_match('/^user\/(.+)/', $route, $matches):
        require_once "user/{$matches[1]}.php";
        break;

    default:
        http_response_code(404);
        echo json_encode(['message' => 'API endpoint not found']);
        break;
}
?>