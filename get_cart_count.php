<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

$count_sql = "SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$cart_count = $count_row['total'] ?? 0;
$count_stmt->close();

echo json_encode(['success' => true, 'count' => $cart_count]);
?>