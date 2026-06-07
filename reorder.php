<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

if(!isset($_POST['order_number'])) {
    echo json_encode(['success' => false, 'message' => 'Order number required']);
    exit();
}

$order_number = $_POST['order_number'];
$user_id = $_SESSION['user_id'];

// Get order items with product images
$items_sql = "SELECT oi.*, p.image 
              FROM order_items oi 
              JOIN orders o ON oi.order_id = o.id 
              LEFT JOIN products p ON oi.product_id = p.id
              WHERE o.order_number = ? AND o.user_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("si", $order_number, $user_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$cart_items = [];
while($item = $items_result->fetch_assoc()) {
    $cart_items[] = [
        'id' => $item['product_id'],
        'name' => $item['product_name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'image' => $item['image'] ?? null
    ];
}

if(count($cart_items) > 0) {
    echo json_encode(['success' => true, 'items' => $cart_items]);
} else {
    echo json_encode(['success' => false, 'message' => 'No items found']);
}

$items_stmt->close();
$conn->close();
?>