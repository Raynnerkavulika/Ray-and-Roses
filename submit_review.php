<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = intval($_POST['product_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    if($product_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }
    
    // Check if user has already reviewed this product
    $check_sql = "SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $product_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
        exit();
    }
    $check_stmt->close();
    
    // Insert review
    $insert_sql = "INSERT INTO product_reviews (product_id, user_id, rating, title, comment, status, created_at) 
                   VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iiiss", $product_id, $user_id, $rating, $title, $comment);
    
    if($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
    }
    $insert_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>