<?php
session_start();
require_once 'config/database.php';
require_once 'config/check_auth.php'; // Ensure user is logged in

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle AJAX requests for cart operations
if(isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch($_POST['action']) {
        case 'add':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity'] ?? 1);
            $user_id = $_SESSION['user_id'];
            
            // Check if product exists and has stock
            $check_sql = "SELECT id, price, stock FROM products WHERE id = ? AND status = 'active'";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if($check_result->num_rows > 0) {
                $product = $check_result->fetch_assoc();
                
                // Check if item already in cart
                $check_cart_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
                $check_cart_stmt = $conn->prepare($check_cart_sql);
                $check_cart_stmt->bind_param("ii", $user_id, $product_id);
                $check_cart_stmt->execute();
                $check_cart_result = $check_cart_stmt->get_result();
                
                if($check_cart_result->num_rows > 0) {
                    // Update quantity
                    $cart_item = $check_cart_result->fetch_assoc();
                    $new_quantity = $cart_item['quantity'] + $quantity;
                    
                    $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
                    
                    if($update_stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Cart updated successfully'];
                    }
                    $update_stmt->close();
                } else {
                    // Add new item
                    $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                    
                    if($insert_stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Product added to cart'];
                    }
                    $insert_stmt->close();
                }
                $check_cart_stmt->close();
            }
            $check_stmt->close();
            break;
            
        case 'get_count':
            $user_id = $_SESSION['user_id'];
            $count_sql = "SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param("i", $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            $cart_count = $count_row['total'] ?? 0;
            $count_stmt->close();
            
            $response = ['success' => true, 'count' => $cart_count];
            break;
    }
    
    echo json_encode($response);
    exit();
}

// Fetch product from database
$product_sql = "SELECT * FROM products WHERE id = ? AND status = 'active'";
$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

// If product not found, redirect to shop
if(!$product) {
    header("Location: shop.php");
    exit();
}

// Fetch approved reviews for this product
$reviews_sql = "SELECT r.*, u.first_name, u.last_name 
                FROM product_reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? AND r.status = 'approved' 
                ORDER BY r.created_at DESC";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews = [];
$total_reviews = 0;
$total_rating = 0;
$rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

while($review = $reviews_result->fetch_assoc()) {
    $reviews[] = $review;
    $total_reviews++;
    $total_rating += $review['rating'];
    $rating_counts[$review['rating']]++;
}

// Calculate average rating
$average_rating = $total_reviews > 0 ? round($total_rating / $total_reviews, 1) : 0;

// Calculate percentage for each rating
$rating_percentages = [];
for($i = 5; $i >= 1; $i--) {
    $rating_percentages[$i] = $total_reviews > 0 ? round(($rating_counts[$i] / $total_reviews) * 100) : 0;
}

// Get product images (for now just the main image)
$product_images = [$product['image']];

// Get related products (same category)
$related_sql = "SELECT * FROM products WHERE category = ? AND id != ? AND status = 'active' LIMIT 4";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("si", $product['category'], $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
$related_products = [];
while($row = $related_result->fetch_assoc()) {
    $related_products[] = $row;
}

include 'header.php';
?>

<style>
    /* Product Details Page Styles */
    .product-details-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    /* Breadcrumb */
    .breadcrumb {
        margin-bottom: 2rem;
        padding: 0.5rem 0;
        color: #7b6b5c;
    }

    .breadcrumb a {
        color: #c45c4a;
        text-decoration: none;
    }

    .breadcrumb a:hover {
        text-decoration: underline;
    }

    /* Product Main Section */
    .product-main {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        margin-bottom: 3rem;
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    /* Product Gallery */
    .product-gallery {
        position: sticky;
        top: 100px;
    }

    .main-image {
        width: 100%;
        height: 400px;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 1rem;
        background: #fef6ef;
    }

    .main-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .main-image img:hover {
        transform: scale(1.05);
    }

    .thumbnail-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.8rem;
    }

    .thumbnail {
        height: 80px;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: 0.3s;
    }

    .thumbnail.active {
        border-color: #c45c4a;
    }

    .thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .thumbnail:hover {
        transform: translateY(-2px);
    }

    /* Product Info */
    .product-info {
        padding: 1rem 0;
    }

    .product-category {
        display: inline-block;
        background: #fef6ef;
        padding: 0.3rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        color: #c45c4a;
        margin-bottom: 1rem;
    }

    .product-title {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        color: #3d2a1f;
        margin-bottom: 1rem;
    }

    .rating-section {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .stars {
        color: gold;
        font-size: 1.1rem;
    }

    .review-count {
        color: #7b6b5c;
        font-size: 0.9rem;
    }

    .write-review-btn {
        background: transparent;
        border: 1px solid #c45c4a;
        color: #c45c4a;
        padding: 0.3rem 1rem;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: 0.3s;
    }

    .write-review-btn:hover {
        background: #c45c4a;
        color: white;
    }

    .product-price {
        font-size: 2rem;
        font-weight: 700;
        color: #c45c4a;
        margin: 1rem 0;
    }

    .product-description {
        color: #5a3f2c;
        line-height: 1.6;
        margin: 1rem 0;
        padding: 1rem 0;
        border-top: 1px solid #f0e0d4;
        border-bottom: 1px solid #f0e0d4;
    }

    /* Product Meta */
    .product-meta {
        margin: 1rem 0;
    }

    .meta-item {
        display: flex;
        margin-bottom: 0.8rem;
    }

    .meta-label {
        width: 120px;
        font-weight: 600;
        color: #3d2a1f;
    }

    .meta-value {
        color: #7b6b5c;
    }

    .stock-status {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .stock-in-stock {
        background: #e8f5e9;
        color: #4caf50;
    }

    .stock-low-stock {
        background: #fff3e0;
        color: #ff9800;
    }

    .stock-out-of-stock {
        background: #fee;
        color: #f44336;
    }

    /* Quantity Selector */
    .quantity-section {
        margin: 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .quantity-label {
        font-weight: 600;
        color: #3d2a1f;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        border: 1px solid #e2cbb8;
        border-radius: 12px;
        overflow: hidden;
    }

    .quantity-btn {
        width: 40px;
        height: 40px;
        background: white;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        transition: 0.3s;
    }

    .quantity-btn:hover {
        background: #c45c4a;
        color: white;
    }

    .quantity-input {
        width: 60px;
        height: 40px;
        text-align: center;
        border: none;
        border-left: 1px solid #e2cbb8;
        border-right: 1px solid #e2cbb8;
        font-family: 'Inter', sans-serif;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .add-to-cart-btn {
        flex: 2;
        background: #c45c4a;
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .add-to-cart-btn:hover:not(:disabled) {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    .add-to-cart-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .add-to-cart-btn.loading {
        opacity: 0.7;
        cursor: wait;
    }

    .wishlist-btn {
        flex: 1;
        background: transparent;
        border: 2px solid #e2cbb8;
        padding: 1rem;
        border-radius: 12px;
        color: #c45c4a;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .wishlist-btn:hover {
        background: #c45c4a;
        color: white;
        border-color: #c45c4a;
    }

    /* Delivery Info */
    .delivery-info {
        background: #fef6ef;
        padding: 1rem;
        border-radius: 12px;
        margin: 1rem 0;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 0.8rem;
    }

    .info-item i {
        width: 24px;
        color: #c45c4a;
    }

    /* Tabs Section */
    .tabs-section {
        margin: 3rem 0;
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .tabs-header {
        display: flex;
        background: #fef6ef;
        border-bottom: 1px solid #f0e0d4;
    }

    .tab-btn {
        flex: 1;
        padding: 1rem;
        background: none;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        color: #7b6b5c;
    }

    .tab-btn.active {
        background: white;
        color: #c45c4a;
        border-bottom: 2px solid #c45c4a;
    }

    .tab-content {
        padding: 2rem;
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* Reviews Section */
    .review-summary {
        display: flex;
        gap: 2rem;
        padding-bottom: 2rem;
        margin-bottom: 2rem;
        border-bottom: 1px solid #f0e0d4;
        flex-wrap: wrap;
    }

    .average-rating {
        text-align: center;
        min-width: 150px;
    }

    .average-rating .big-rating {
        font-size: 3rem;
        font-weight: 700;
        color: #3d2a1f;
    }

    .rating-bars {
        flex: 1;
        min-width: 250px;
    }

    .rating-bar-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }

    .rating-bar-label {
        width: 60px;
        font-size: 0.85rem;
    }

    .rating-bar-bg {
        flex: 1;
        height: 8px;
        background: #f0e0d4;
        border-radius: 4px;
        overflow: hidden;
    }

    .rating-bar-fill {
        height: 100%;
        background: gold;
        border-radius: 4px;
    }

    .rating-count {
        width: 40px;
        font-size: 0.85rem;
        color: #7b6b5c;
    }

    .review-card {
        padding: 1rem;
        border-bottom: 1px solid #f0e0d4;
        margin-bottom: 1rem;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .reviewer-name {
        font-weight: 600;
        color: #3d2a1f;
    }

    .review-date {
        font-size: 0.8rem;
        color: #7b6b5c;
    }

    .review-title {
        font-weight: 600;
        margin: 0.5rem 0;
        color: #3d2a1f;
    }

    .review-comment {
        color: #5a3f2c;
        line-height: 1.5;
    }

    .no-reviews {
        text-align: center;
        padding: 2rem;
        color: #7b6b5c;
    }

    /* Review Modal */
    .review-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }

    .review-modal.show {
        display: flex;
    }

    .review-modal-content {
        background: white;
        border-radius: 20px;
        max-width: 500px;
        width: 90%;
        padding: 2rem;
    }

    .review-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f0e0d4;
    }

    .close-review-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .star-rating {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        justify-content: center;
    }

    .star-rating i {
        font-size: 2rem;
        color: #ddd;
        cursor: pointer;
        transition: 0.2s;
    }

    .star-rating i.active,
    .star-rating i:hover {
        color: gold;
    }

    /* Related Products */
    .related-section {
        margin-top: 3rem;
    }

    .related-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        text-align: center;
        margin-bottom: 2rem;
        color: #3d2a1f;
    }

    .related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
    }

    .related-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .related-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .related-img {
        height: 200px;
        background-size: cover;
        background-position: center;
    }

    .related-info {
        padding: 1rem;
        text-align: center;
    }

    .related-name {
        font-weight: 600;
        margin-bottom: 0.3rem;
        color: #3d2a1f;
    }

    .related-price {
        color: #c45c4a;
        font-weight: 700;
    }

    /* Toast */
    .toast {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        background: #2d2a24;
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 50px;
        z-index: 3000;
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
        font-family: 'Inter', sans-serif;
    }

    .toast.show {
        opacity: 1;
    }

    @media (max-width: 968px) {
        .product-main {
            grid-template-columns: 1fr;
        }
        .product-gallery {
            position: static;
        }
        .action-buttons {
            flex-direction: column;
        }
        .review-summary {
            flex-direction: column;
            align-items: center;
        }
    }
</style>

<div class="product-details-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="dashboard.php">Home</a> / 
        <a href="shop.php">Shop</a> / 
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <div class="product-main">
        <!-- Product Gallery -->
        <div class="product-gallery">
            <div class="main-image">
                <img id="mainImage" src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <?php if(count($product_images) > 1): ?>
            <div class="thumbnail-grid" id="thumbnailGrid">
                <?php foreach($product_images as $index => $img): ?>
                <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo $img; ?>', this)">
                    <img src="<?php echo $img; ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="product-info">
            <span class="product-category"><?php echo ucfirst($product['category']); ?></span>
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="rating-section">
                <div class="stars">
                    <?php 
                    for($i = 1; $i <= 5; $i++):
                        if($i <= round($average_rating)):
                            echo '<i class="fas fa-star"></i>';
                        else:
                            echo '<i class="far fa-star"></i>';
                        endif;
                    endfor;
                    ?>
                </div>
                <span class="review-count"><?php echo $total_reviews; ?> reviews</span>
                <button class="write-review-btn" onclick="openReviewModal()">
                    <i class="fas fa-pencil-alt"></i> Write a Review
                </button>
            </div>
            
            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
            
            <div class="product-description">
                <p><?php echo nl2br(htmlspecialchars($product['full_description'] ?: $product['description'])); ?></p>
            </div>
            
            <div class="product-meta">
                <div class="meta-item">
                    <span class="meta-label">Availability:</span>
                    <span class="meta-value">
                        <span class="stock-status <?php 
                            echo $product['stock'] > 10 ? 'stock-in-stock' : ($product['stock'] > 0 ? 'stock-low-stock' : 'stock-out-of-stock'); 
                        ?>">
                            <?php 
                            echo $product['stock'] > 10 ? 'In Stock' : ($product['stock'] > 0 ? "Only {$product['stock']} left" : 'Out of Stock'); 
                            ?>
                        </span>
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">SKU:</span>
                    <span class="meta-value"><?php echo $product['sku'] ?? 'N/A'; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Category:</span>
                    <span class="meta-value"><?php echo ucfirst($product['category']); ?></span>
                </div>
            </div>
            
            <!-- Quantity Selector -->
            <div class="quantity-section">
                <span class="quantity-label">Quantity:</span>
                <div class="quantity-selector">
                    <button class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                    <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    <button class="quantity-btn" onclick="changeQuantity(1)">+</button>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="add-to-cart-btn" id="addToCartBtn" onclick="addToCart()" <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
                <button class="wishlist-btn" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                    <i class="fas fa-heart"></i> Save to Wishlist
                </button>
            </div>
            
            <!-- Delivery Info -->
            <div class="delivery-info">
                <div class="info-item"><i class="fas fa-truck"></i><span>Free delivery on orders over $50</span></div>
                <div class="info-item"><i class="fas fa-shield-alt"></i><span>Freshness guaranteed for 7 days</span></div>
                <div class="info-item"><i class="fas fa-undo-alt"></i><span>30-day return policy</span></div>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="tabs-section">
        <div class="tabs-header">
            <button class="tab-btn active" onclick="switchTab('details')">Product Details</button>
            <button class="tab-btn" onclick="switchTab('reviews')">Reviews (<?php echo $total_reviews; ?>)</button>
            <button class="tab-btn" onclick="switchTab('shipping')">Shipping Info</button>
        </div>
        
        <div id="details-tab" class="tab-content active">
            <h3>Product Specifications</h3>
            <ul style="margin-left: 1.5rem; color: #5a3f2c;">
                <li>Fresh, hand-selected flowers</li>
                <li>Arranged by expert florists</li>
                <li>Includes complimentary gift message</li>
                <li>Comes with flower care guide</li>
                <li>Eco-friendly packaging</li>
            </ul>
            <h3 style="margin-top: 1rem;">Care Instructions</h3>
            <ul style="margin-left: 1.5rem; color: #5a3f2c;">
                <li>Trim stems at 45-degree angle every 2-3 days</li>
                <li>Change water daily to keep flowers fresh</li>
                <li>Keep away from direct sunlight and heat sources</li>
                <li>Remove any leaves that fall below the water line</li>
                <li>Use flower food provided for longer life</li>
            </ul>
        </div>
        
        <div id="reviews-tab" class="tab-content">
            <?php if($total_reviews > 0): ?>
            <div class="review-summary">
                <div class="average-rating">
                    <div class="big-rating"><?php echo $average_rating; ?></div>
                    <div class="stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= round($average_rating)): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <div><?php echo $total_reviews; ?> reviews</div>
                </div>
                <div class="rating-bars">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                    <div class="rating-bar-item">
                        <span class="rating-bar-label"><?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?></span>
                        <div class="rating-bar-bg">
                            <div class="rating-bar-fill" style="width: <?php echo $rating_percentages[$i]; ?>%"></div>
                        </div>
                        <span class="rating-count"><?php echo $rating_counts[$i]; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <?php foreach($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <span class="reviewer-name"><?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'); ?></span>
                    <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                </div>
                <div class="stars">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? 'gold' : '#e2cbb8'; ?>"></i>
                    <?php endfor; ?>
                </div>
                <?php if($review['title']): ?>
                <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>
                <?php endif; ?>
                <div class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="no-reviews">
                <i class="fas fa-star" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem; display: block;"></i>
                <p>No reviews yet. Be the first to review this product!</p>
                <button class="write-review-btn" onclick="openReviewModal()" style="margin-top: 1rem;">
                    Write a Review
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="shipping-tab" class="tab-content">
            <h3>Shipping Information</h3>
            <ul style="margin-left: 1.5rem; color: #5a3f2c;">
                <li><strong>Free Shipping:</strong> On all orders over $50</li>
                <li><strong>Standard Shipping:</strong> $5.99 (3-5 business days)</li>
                <li><strong>Express Shipping:</strong> $12.99 (1-2 business days)</li>
                <li><strong>Same-Day Delivery:</strong> Available for orders placed before 2 PM</li>
            </ul>
            
            <h3 style="margin-top: 1rem;">Return Policy</h3>
            <ul style="margin-left: 1.5rem; color: #5a3f2c;">
                <li>30-day return policy for unused items</li>
                <li>Fresh flowers cannot be returned due to perishable nature</li>
                <li>Contact support within 24 hours for any quality issues</li>
                <li>We'll replace or refund damaged products</li>
            </ul>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if(!empty($related_products)): ?>
    <div class="related-section">
        <h2 class="related-title">You May Also Like</h2>
        <div class="related-grid">
            <?php foreach($related_products as $rel): ?>
            <a href="product_details.php?id=<?php echo $rel['id']; ?>" class="related-card">
                <div class="related-img" style="background-image: url('<?php echo $rel['image']; ?>');"></div>
                <div class="related-info">
                    <div class="related-name"><?php echo htmlspecialchars($rel['name']); ?></div>
                    <div class="related-price">$<?php echo number_format($rel['price'], 2); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="review-modal">
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h3><i class="fas fa-star"></i> Write a Review</h3>
            <button class="close-review-modal" onclick="closeReviewModal()">&times;</button>
        </div>
        <form id="reviewForm">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <div class="star-rating" id="starRating">
                <i class="far fa-star" data-rating="1"></i>
                <i class="far fa-star" data-rating="2"></i>
                <i class="far fa-star" data-rating="3"></i>
                <i class="far fa-star" data-rating="4"></i>
                <i class="far fa-star" data-rating="5"></i>
            </div>
            <input type="hidden" name="rating" id="selectedRating" required>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Review Title (Optional)</label>
                <input type="text" name="title" class="form-control" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Your Review *</label>
                <textarea name="comment" rows="4" required class="form-control" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 8px;"></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; padding: 0.8rem; background: #c45c4a; color: white; border: none; border-radius: 8px; cursor: pointer;">Submit Review</button>
        </form>
    </div>
</div>

<script>
// Change main image when thumbnail is clicked
function changeImage(src, element) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    element.classList.add('active');
}

// Change quantity
function changeQuantity(delta) {
    const quantityInput = document.getElementById('quantity');
    let currentValue = parseInt(quantityInput.value);
    const maxStock = <?php echo $product['stock']; ?>;
    let newValue = currentValue + delta;
    
    if (newValue < 1) newValue = 1;
    if (newValue > maxStock) newValue = maxStock;
    
    quantityInput.value = newValue;
}

// Add to cart using database
function addToCart() {
    const quantity = parseInt(document.getElementById('quantity').value);
    const productId = <?php echo $product['id']; ?>;
    const btn = document.getElementById('addToCartBtn');
    
    // Show loading state
    btn.disabled = true;
    btn.classList.add('loading');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'add',
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`${quantity} × ${<?php echo json_encode($product['name']); ?>} added to cart! ✨`);
            updateCartCount();
        } else {
            showToast(data.message || 'Error adding to cart');
        }
    })
    .catch(error => {
        showToast('Error adding to cart');
        console.error('Error:', error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.classList.remove('loading');
        btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
    });
}

// Add to wishlist
function addToWishlist(productId) {
    window.location.href = `wishlist.php?add=${productId}`;
}

// Update cart count from database
function updateCartCount() {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'get_count' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(el => {
                if (el) el.textContent = data.count;
            });
        }
    })
    .catch(error => console.error('Error fetching cart count:', error));
}

// Show toast notification
function showToast(message) {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 2000);
}

// Switch tabs
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById(`${tabName}-tab`).classList.add('active');
    event.target.classList.add('active');
}

// Star rating functionality
let currentRating = 0;
const stars = document.querySelectorAll('#starRating i');
stars.forEach(star => {
    star.addEventListener('click', function() {
        currentRating = parseInt(this.dataset.rating);
        document.getElementById('selectedRating').value = currentRating;
        stars.forEach(s => {
            if(parseInt(s.dataset.rating) <= currentRating) {
                s.className = 'fas fa-star';
            } else {
                s.className = 'far fa-star';
            }
        });
    });
});

// Open review modal
function openReviewModal() {
    document.getElementById('reviewModal').classList.add('show');
}

// Close review modal
function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('show');
}

// Submit review
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const rating = document.getElementById('selectedRating').value;
    if (!rating) {
        alert('Please select a rating');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('submit_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you for your review! It will be published after moderation.');
            closeReviewModal();
            location.reload();
        } else {
            alert('Error submitting review. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting review. Please try again.');
    });
});

// Initialize cart count
updateCartCount();
</script>

<?php include 'footer.php'; ?>