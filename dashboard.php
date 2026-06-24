<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user data from database
$user_sql = "SELECT first_name, last_name, email, phone, created_at FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Fetch featured products from database with review counts and average ratings (limit to 6)
$featured_sql = "SELECT p.*, 
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN product_reviews r ON p.id = r.product_id AND r.status = 'approved'
                WHERE p.status = 'active' AND p.featured = 1 
                GROUP BY p.id 
                ORDER BY p.created_at DESC 
                LIMIT 6";
$featured_result = $conn->query($featured_sql);
$featured_products = [];

if($featured_result && $featured_result->num_rows > 0) {
    while($row = $featured_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// If no featured products, get any active products as fallback (limit to 6)
if(empty($featured_products)) {
    $fallback_sql = "SELECT p.*, 
                    COALESCE(AVG(r.rating), 0) as avg_rating,
                    COUNT(r.id) as review_count
                    FROM products p 
                    LEFT JOIN product_reviews r ON p.id = r.product_id AND r.status = 'approved'
                    WHERE p.status = 'active' 
                    GROUP BY p.id 
                    ORDER BY p.created_at DESC 
                    LIMIT 6";
    $fallback_result = $conn->query($fallback_sql);
    if($fallback_result && $fallback_result->num_rows > 0) {
        while($row = $fallback_result->fetch_assoc()) {
            $featured_products[] = $row;
        }
    }
}

// Special offers (using Font Awesome icons)
$special_offers = [
    ['title' => 'Free Delivery', 'desc' => 'On all orders over $50', 'icon' => 'fa-truck', 'color' => '#c45c4a', 'bg' => '#fee'],
    ['title' => '10% Off', 'desc' => 'First order coupon: WELCOME10', 'icon' => 'fa-percent', 'color' => '#4caf50', 'bg' => '#e8f5e9'],
    ['title' => 'Birthday Bonus', 'desc' => 'Get 15% off on your birthday month', 'icon' => 'fa-cake-candles', 'color' => '#ff9800', 'bg' => '#fff3e0'],
    ['title' => 'Refer a Friend', 'desc' => 'Earn $10 credit per referral', 'icon' => 'fa-users', 'color' => '#2196f3', 'bg' => '#e3f2fd'],
    ['title' => 'Free Vase', 'desc' => 'Free crystal vase with $75+ order', 'icon' => 'fa-gem', 'color' => '#9c27b0', 'bg' => '#f3e5f5'],
    ['title' => 'Weekly Special', 'desc' => 'Buy 2 get 1 free on bouquets', 'icon' => 'fa-gift', 'color' => '#e91e63', 'bg' => '#fce4ec'],
];

// Featured categories (using Font Awesome icons)
$featured_categories = [
    ['name' => 'Romantic Roses', 'icon' => 'fa-heart', 'color' => '#e91e63'],
    ['name' => 'Birthday Blooms', 'icon' => 'fa-cake-candles', 'color' => '#ff9800'],
    ['name' => 'Anniversary', 'icon' => 'fa-ring', 'color' => '#c45c4a'],
    ['name' => 'Get Well Soon', 'icon' => 'fa-heartbeat', 'color' => '#4caf50'],
];

// Include header
include 'header.php';
?>

<style>
    /* Dashboard specific styles */
    .welcome-banner {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 3rem 5%;
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::before {
        content: '\f50b \f33e \f335 \f579';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        font-size: 180px;
        opacity: 0.08;
        right: -30px;
        bottom: -50px;
        letter-spacing: 20px;
    }

    .welcome-content h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.2rem;
        margin-bottom: 0.5rem;
    }

    .welcome-content h1 .welcome-icon {
        margin-right: 0.5rem;
    }

    .welcome-content p {
        opacity: 0.95;
        font-size: 1.1rem;
    }

    .member-since {
        opacity: 0.85;
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    .member-since i {
        margin-right: 0.3rem;
    }

    .categories-section {
        padding: 2rem 5%;
        background: white;
    }

    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .category-card {
        text-align: center;
        padding: 1.5rem;
        background: #fef6ef;
        border-radius: 20px;
        transition: all 0.3s;
        cursor: pointer;
    }

    .category-card:hover {
        transform: translateY(-5px);
        background: #c45c4a;
        color: white;
    }

    .category-card:hover .category-icon {
        color: white !important;
    }

    .category-card .category-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
        transition: color 0.3s;
    }

    .category-card h4 {
        font-size: 1rem;
    }

    .offers-section {
        padding: 3rem 5%;
        background: linear-gradient(135deg, #fff5ef, #ffe8e0);
    }

    .section-title {
        text-align: center;
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        margin-bottom: 2rem;
        color: #3d2a1f;
        position: relative;
    }

    .section-title .title-icon {
        margin-right: 0.5rem;
        color: #c45c4a;
    }

    .section-title:after {
        content: '';
        display: block;
        width: 60px;
        height: 3px;
        background: #c45c4a;
        margin: 0.5rem auto 0;
        border-radius: 2px;
    }

    .offers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .offer-card {
        background: white;
        padding: 1.8rem;
        border-radius: 20px;
        text-align: center;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .offer-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }

    .offer-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2rem;
        transition: 0.3s;
    }

    .offer-card:hover .offer-icon {
        transform: scale(1.1);
    }

    .offer-card h3 {
        margin-bottom: 0.5rem;
        color: #3d2a1f;
        font-size: 1.2rem;
    }

    .offer-card p {
        color: #7b6b5c;
        font-size: 0.9rem;
    }

    .featured-section {
        padding: 3rem 5%;
        background: white;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Product Card Link Styles */
    .product-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: transform 0.3s;
    }

    .product-card-link:hover {
        transform: translateY(-5px);
    }

    .product-card-link:hover .product-card {
        transform: none;
    }

    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: box-shadow 0.3s;
        position: relative;
        height: 100%;
        cursor: pointer;
    }

    .product-card:hover {
        transform: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    }

    /* Badge positioning - Left side */
    .badge-left {
        position: absolute;
        top: 0.8rem;
        left: 0.8rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        z-index: 2;
    }

    .product-badge {
        background: #c45c4a;
        color: white;
        padding: 0.25rem 0.6rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-align: center;
        width: fit-content;
    }

    .product-rating {
        background: rgba(0,0,0,0.7);
        color: gold;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        gap: 0.2rem;
        width: fit-content;
        backdrop-filter: blur(4px);
    }

    .product-rating i {
        color: #ffc107;
    }

    /* Discount badge - Right side */
    .discount-badge {
        position: absolute;
        top: 0.8rem;
        right: 0.8rem;
        background: #e53935;
        color: white;
        padding: 0.25rem 0.6rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 2;
    }

    .product-img {
        height: 220px;
        background-size: cover;
        background-position: center;
        transition: transform 0.5s;
    }

    .product-card:hover .product-img {
        transform: scale(1.05);
    }

    .product-info {
        padding: 1rem;
    }

    .product-title {
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 0.3rem;
        color: #3d2a1f;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-desc {
        font-size: 0.75rem;
        color: #7b6b5c;
        margin-bottom: 0.5rem;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Price Styles with Discount */
    .price-container {
        margin-bottom: 0.5rem;
    }

    .product-price {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .original-price {
        font-size: 0.75rem;
        color: #999;
        text-decoration: line-through;
    }

    .discounted-price {
        font-weight: 700;
        color: #c45c4a;
        font-size: 1rem;
    }

    .regular-price {
        font-weight: 700;
        color: #c45c4a;
        font-size: 1rem;
    }

    .stock {
        font-size: 0.7rem;
        color: #7b6b5c;
        display: inline-block;
        margin-top: 0.3rem;
    }

    .stock.in-stock {
        color: #4caf50;
    }

    .stock.low-stock {
        color: #ff9800;
    }

    .view-all {
        text-align: center;
        margin-top: 2rem;
    }

    .view-all-link {
        display: inline-block;
        background: transparent;
        border: 2px solid #c45c4a;
        color: #c45c4a;
        padding: 0.6rem 1.5rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
        font-size: 0.9rem;
    }

    .view-all-link:hover {
        background: #c45c4a;
        color: white;
        transform: translateY(-2px);
    }

    .view-all-link i {
        margin-left: 0.3rem;
    }

    .no-results {
        text-align: center;
        padding: 3rem;
        grid-column: 1/-1;
    }

    .no-results i {
        font-size: 3rem;
        color: #ccc;
        margin-bottom: 1rem;
        display: block;
    }

    @media (max-width: 768px) {
        .welcome-content h1 {
            font-size: 1.5rem;
        }
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1rem;
        }
        .product-img {
            height: 180px;
        }
        .product-title {
            font-size: 0.9rem;
        }
        .product-desc {
            font-size: 0.7rem;
            -webkit-line-clamp: 2;
        }
        .badge-left {
            top: 0.5rem;
            left: 0.5rem;
            gap: 0.3rem;
        }
        .product-badge, .product-rating, .discount-badge {
            font-size: 0.6rem;
            padding: 0.2rem 0.5rem;
        }
    }
</style>

<div class="welcome-banner">
    <div class="welcome-content">
        <h1><i class="fas fa-hand-peace welcome-icon"></i> Welcome back, <?php echo htmlspecialchars($user_data['first_name']); ?>!</h1>
        <p><i class="fas fa-seedling" style="margin-right: 0.5rem;"></i>Discover hand-picked blooms, exclusive offers, and nature's finest creations.</p>
        <p class="member-since"><i class="fas fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($user_data['created_at'])); ?></p>
    </div>
</div>

<div class="categories-section">
    <div class="categories-grid">
        <?php foreach($featured_categories as $category): ?>
        <div class="category-card" onclick="window.location.href='shop.php'">
            <i class="fas <?php echo $category['icon']; ?> category-icon" style="color: <?php echo $category['color']; ?>"></i>
            <h4><?php echo $category['name']; ?></h4>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="offers-section">
    <h2 class="section-title"><i class="fas fa-gift title-icon"></i>Special Offers Just for You</h2>
    <div class="offers-grid">
        <?php foreach($special_offers as $offer): ?>
        <div class="offer-card">
            <div class="offer-icon" style="background: <?php echo $offer['bg']; ?>">
                <i class="fas <?php echo $offer['icon']; ?>" style="color: <?php echo $offer['color']; ?>"></i>
            </div>
            <h3><?php echo $offer['title']; ?></h3>
            <p><?php echo $offer['desc']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="featured-section">
    <h2 class="section-title"><i class="fas fa-star title-icon"></i>Featured Arrangements</h2>
    <div class="products-grid">
        <?php if(empty($featured_products)): ?>
            <div class="no-results">
                <i class="fas fa-box-open"></i>
                <p>No featured products available yet.</p>
                <a href="shop.php" class="view-all-link" style="margin-top: 1rem; display: inline-block;">Browse All Products <i class="fas fa-arrow-right"></i></a>
            </div>
        <?php else: ?>
            <?php foreach($featured_products as $product): 
                $discountPercent = 0;
                if(isset($product['original_price']) && $product['original_price'] && $product['original_price'] > $product['price']) {
                    $discountPercent = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                }
                // Get product rating
                $ratingValue = isset($product['avg_rating']) ? floatval($product['avg_rating']) : 0;
                $reviewCount = isset($product['review_count']) ? intval($product['review_count']) : 0;
                // Get description
                $description = isset($product['description']) ? $product['description'] : (isset($product['full_description']) ? substr($product['full_description'], 0, 80) : 'Beautiful flower arrangement');
                
                // Build left badges HTML
                $leftBadgesHtml = '';
                if($product['stock'] < 5 && $product['stock'] > 0) {
                    $leftBadgesHtml .= '<div class="product-badge"><i class="fas fa-fire"></i> Almost Gone</div>';
                }
                $leftBadgesHtml .= '<div class="product-rating"><i class="fas fa-star"></i> ' . number_format($ratingValue, 1) . ' (' . $reviewCount . ')</div>';
            ?>
            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="product-card-link">
                <div class="product-card">
                    <div class="product-img" style="background-image: url('<?php echo $product['image']; ?>'); background-size: cover; background-position: center;"></div>
                    
                    <!-- Left side badges -->
                    <div class="badge-left">
                        <?php echo $leftBadgesHtml; ?>
                    </div>
                    
                    <!-- Right side badge (Discount) -->
                    <?php if($discountPercent > 0): ?>
                    <div class="discount-badge"><i class="fas fa-tag"></i> -<?php echo $discountPercent; ?>%</div>
                    <?php endif; ?>
                    
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-desc"><?php echo htmlspecialchars(substr($description, 0, 70)) . (strlen($description) > 70 ? '...' : ''); ?></p>
                        <div class="price-container">
                            <div class="product-price">
                                <?php if($discountPercent > 0): ?>
                                    <span class="original-price">$<?php echo number_format($product['original_price'], 2); ?></span>
                                    <span class="discounted-price">$<?php echo number_format($product['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="regular-price">$<?php echo number_format($product['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="stock <?php echo $product['stock'] > 10 ? 'in-stock' : ($product['stock'] > 0 ? 'low-stock' : ''); ?>">
                            <?php if($product['stock'] > 10): ?>
                                <i class="fas fa-check-circle"></i> In Stock
                            <?php elseif($product['stock'] > 0): ?>
                                <i class="fas fa-exclamation-triangle"></i> Only <?php echo $product['stock']; ?> left
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i> Out of Stock
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="view-all">
        <a href="shop.php" class="view-all-link">Browse All Flowers <i class="fas fa-seedling"></i></a>
    </div>
</div>

<?php include 'footer.php'; ?>