<?php
session_start();
require_once 'config/database.php';
require_once 'config/check_auth.php'; // Ensure user is logged in

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle add to wishlist (from GET request)
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $product_id = $_GET['add'];
    
    // Initialize wishlist if not exists
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    // Add product to wishlist if not already there
    if (!in_array($product_id, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $product_id;
        $wishlist_message = "Product added to wishlist!";
    } else {
        $wishlist_message = "Product already in wishlist!";
    }
}

// Handle remove from wishlist
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $product_id = $_GET['remove'];
    if (isset($_SESSION['wishlist'])) {
        $key = array_search($product_id, $_SESSION['wishlist']);
        if ($key !== false) {
            unset($_SESSION['wishlist'][$key]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Reindex array
            $wishlist_message = "Product removed from wishlist!";
        }
    }
}

// Handle clear wishlist
if (isset($_GET['clear'])) {
    $_SESSION['wishlist'] = [];
    $wishlist_message = "Wishlist cleared!";
}

// Get wishlist products from DATABASE
$wishlist_products = [];
if (isset($_SESSION['wishlist']) && !empty($_SESSION['wishlist'])) {
    // Create placeholders for SQL query
    $placeholders = implode(',', array_fill(0, count($_SESSION['wishlist']), '?'));
    $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters dynamically
    $types = str_repeat('i', count($_SESSION['wishlist']));
    $stmt->bind_param($types, ...$_SESSION['wishlist']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $wishlist_products[] = $row;
    }
    $stmt->close();
}

// Include header
include 'header.php';
?>

<style>
    /* Wishlist Page Styles */
    .wishlist-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    .wishlist-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .wishlist-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .wishlist-header p {
        color: #7b6b5c;
    }

    /* Message Alert */
    .alert-message {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        text-align: center;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Wishlist Grid */
    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    .wishlist-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: all 0.3s;
        position: relative;
    }

    .wishlist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }

    .product-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: #c45c4a;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 1;
    }

    .product-rating {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(0,0,0,0.7);
        color: gold;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        z-index: 1;
    }

    .remove-wishlist {
        position: absolute;
        top: 1rem;
        right: 4rem;
        background: white;
        color: #c45c4a;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.3s;
        z-index: 1;
        text-decoration: none;
    }

    .remove-wishlist:hover {
        background: #c45c4a;
        color: white;
        transform: scale(1.1);
    }

    .product-img {
        height: 260px;
        background-size: cover;
        background-position: center;
        transition: transform 0.5s;
    }

    .wishlist-card:hover .product-img {
        transform: scale(1.05);
    }

    .product-info {
        padding: 1.2rem;
    }

    .product-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        color: #3d2a1f;
    }

    .product-category {
        font-size: 0.8rem;
        color: #7b6b5c;
        margin-bottom: 0.5rem;
        display: inline-block;
        background: #fef6ef;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
    }

    .price-stock {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 0.8rem 0;
    }

    .price {
        font-weight: 700;
        font-size: 1.3rem;
        color: #c45c4a;
    }

    .stock {
        font-size: 0.8rem;
        color: #7b6b5c;
    }

    .stock.in-stock {
        color: #4caf50;
    }

    .stock.low-stock {
        color: #ff9800;
    }

    .action-buttons {
        display: flex;
        gap: 0.8rem;
        margin-top: 1rem;
    }

    .add-to-cart-btn {
        flex: 1;
        background: #2d2a24;
        color: white;
        border: none;
        padding: 0.7rem;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .add-to-cart-btn:hover {
        background: #c45c4a;
        transform: scale(0.98);
    }

    .view-btn {
        flex: 1;
        background: transparent;
        border: 1px solid #e2cbb8;
        color: #5a3f2c;
        padding: 0.7rem;
        border-radius: 10px;
        text-decoration: none;
        text-align: center;
        transition: 0.3s;
    }

    .view-btn:hover {
        background: #fef6ef;
        border-color: #c45c4a;
    }

    /* Empty Wishlist */
    .empty-wishlist {
        text-align: center;
        padding: 4rem;
        background: white;
        border-radius: 20px;
    }

    .empty-wishlist i {
        font-size: 4rem;
        color: #e2cbb8;
        margin-bottom: 1rem;
    }

    .empty-wishlist h2 {
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .empty-wishlist p {
        color: #7b6b5c;
        margin-bottom: 1.5rem;
    }

    .shop-now-btn {
        display: inline-block;
        background: #c45c4a;
        color: white;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }

    .shop-now-btn:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    /* Wishlist Actions */
    .wishlist-actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 2rem;
        gap: 1rem;
    }

    .clear-wishlist {
        background: transparent;
        border: 1px solid #e2cbb8;
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        color: #c45c4a;
        cursor: pointer;
        transition: 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .clear-wishlist:hover {
        background: #c45c4a;
        color: white;
        border-color: #c45c4a;
    }

    .add-all-to-cart {
        background: #c45c4a;
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        cursor: pointer;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .add-all-to-cart:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .wishlist-actions {
            flex-direction: column;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="wishlist-container">
    <div class="wishlist-header">
        <h1><i class="fas fa-heart"></i> My Wishlist</h1>
        <p>Your favorite flowers, saved for later</p>
    </div>

    <?php if(isset($wishlist_message)): ?>
    <div class="alert-message">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($wishlist_message); ?>
    </div>
    <?php endif; ?>

    <?php if(empty($wishlist_products)): ?>
    <div class="empty-wishlist">
        <i class="fas fa-heart-broken"></i>
        <h2>Your wishlist is empty</h2>
        <p>Start adding your favorite flowers to your wishlist!</p>
        <a href="shop.php" class="shop-now-btn">🌸 Browse Collection</a>
    </div>
    <?php else: ?>
    
    <div class="wishlist-actions">
        <button class="add-all-to-cart" onclick="addAllToCart()">
            <i class="fas fa-cart-plus"></i> Add All to Cart
        </button>
        <a href="?clear=1" class="clear-wishlist" onclick="return confirm('Are you sure you want to clear your entire wishlist?')">
            <i class="fas fa-trash-alt"></i> Clear Wishlist
        </a>
    </div>

    <div class="wishlist-grid">
        <?php foreach($wishlist_products as $product): ?>
        <div class="wishlist-card" data-product-id="<?php echo $product['id']; ?>">
            <?php if($product['stock'] < 5 && $product['stock'] > 0): ?>
            <div class="product-badge">🔥 Almost Gone</div>
            <?php elseif($product['stock'] == 0): ?>
            <div class="product-badge">Out of Stock</div>
            <?php endif; ?>
            <div class="product-rating">⭐ <?php echo $product['rating'] ?? 4.5; ?></div>
            <a href="?remove=<?php echo $product['id']; ?>" class="remove-wishlist" onclick="return confirm('Remove from wishlist?')">
                <i class="fas fa-times"></i>
            </a>
            <div class="product-img" style="background-image: url('<?php echo $product['image']; ?>');"></div>
            <div class="product-info">
                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                <span class="product-category">
                    <i class="fas fa-tag"></i> <?php echo ucfirst($product['category']); ?>
                </span>
                <div class="price-stock">
                    <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                    <span class="stock <?php echo $product['stock'] > 10 ? 'in-stock' : ($product['stock'] > 0 ? 'low-stock' : ''); ?>">
                        <?php echo $product['stock'] > 10 ? 'In Stock' : ($product['stock'] > 0 ? "Only {$product['stock']} left" : 'Out of Stock'); ?>
                    </span>
                </div>
                <div class="action-buttons">
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo $product['image']; ?>')">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Add single item to cart
function addToCart(id, name, price, image) {
    let cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
    
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            quantity: 1,
            image: image
        });
    }
    
    localStorage.setItem('flowerCart', JSON.stringify(cart));
    updateCartCount();
    showToast(`${name} added to cart! ✨`);
}

// Add all wishlist items to cart
function addAllToCart() {
    let cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
    const products = <?php echo json_encode($wishlist_products); ?>;
    
    products.forEach(product => {
        const existing = cart.find(item => item.id === product.id);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                quantity: 1,
                image: product.image
            });
        }
    });
    
    localStorage.setItem('flowerCart', JSON.stringify(cart));
    updateCartCount();
    showToast(`Added all ${products.length} items to cart! 🎉`);
    
    setTimeout(() => {
        window.location.href = 'cart.php';
    }, 1000);
}

// Update cart count badge
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        if (el) el.textContent = totalItems;
    });
}

// Show toast notification
function showToast(message) {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
        
        if (!document.querySelector('#toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
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
            `;
            document.head.appendChild(style);
        }
    }
    
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 2000);
}

// Initialize cart count
updateCartCount();
</script>

<?php include 'footer.php'; ?>