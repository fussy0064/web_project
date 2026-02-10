<?php
require_once 'config.php';
$conn = getDBConnection();
echo json_encode(['status' => 'success', 'message' => 'Database connected']);
?>