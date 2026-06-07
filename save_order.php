<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to place order']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if(!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit();
}

// Validate required fields
$required = ['order_number', 'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'total', 'payment_method', 'items'];
foreach($required as $field) {
    if(empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit();
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Combine address fields into shipping_address
    $shipping_address = $data['address'] . ', ' . $data['city'];
    if(!empty($data['postal_code'])) {
        $shipping_address .= ', ' . $data['postal_code'];
    }
    
    // Insert order - using your actual table column names
    $order_sql = "INSERT INTO orders (order_number, user_id, first_name, last_name, email, phone, address, city, postal_code, notes, shipping_address, total_amount, discount, shipping_cost, payment_method, payment_status, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())";
    
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("sisssssssssddds", 
        $data['order_number'],
        $user_id,
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['city'],
        $data['postal_code'],
        $data['notes'],
        $shipping_address,
        $data['total'],
        $data['discount'],
        $data['shipping_cost'],
        $data['payment_method']
    );
    
    if(!$order_stmt->execute()) {
        throw new Exception('Failed to insert order: ' . $conn->error);
    }
    
    $order_id = $conn->insert_id;
    $order_stmt->close();
    
    // Insert order items - calculate subtotal if column exists, otherwise skip
    $item_sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_sql);
    
    foreach($data['items'] as $item) {
        $item_stmt->bind_param("iisid", $order_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['price']);
        if(!$item_stmt->execute()) {
            throw new Exception('Failed to insert order item: ' . $conn->error);
        }
    }
    $item_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'order_id' => $order_id, 'order_number' => $data['order_number']]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>