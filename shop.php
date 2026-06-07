<?php
session_start();
require_once 'config/database.php';
require_once 'config/check_auth.php'; // Ensure user is logged in

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch products from database with review counts and average ratings
$products_sql = "SELECT p.*, 
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN product_reviews r ON p.id = r.product_id AND r.status = 'approved'
                WHERE p.status = 'active' 
                GROUP BY p.id 
                ORDER BY p.created_at DESC";
$products_result = $conn->query($products_sql);
$products = [];
if($products_result && $products_result->num_rows > 0) {
    while($row = $products_result->fetch_assoc()) {
        $products[$row['id']] = $row;
    }
}

// If no products in database, show empty array
if(empty($products)) {
    $products = [];
}

// Include header
include 'header.php';
?>

<style>
    /* Shop Page Specific Styles */
    .shop-hero {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 3rem 5%;
        text-align: center;
        margin-top: 0;
    }

    .shop-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .shop-hero p {
        font-size: 1.1rem;
        opacity: 0.95;
    }

    /* Filter Bar */
    .filter-section {
        padding: 2rem 5%;
        background: white;
        border-bottom: 1px solid #f0e0d4;
    }

    .filter-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
    }

    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
    }

    .filter-btn {
        background: transparent;
        border: 1px solid #e2cbb8;
        padding: 0.6rem 1.6rem;
        border-radius: 40px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        color: #5a3f2c;
        font-family: 'Inter', sans-serif;
    }

    .filter-btn.active, .filter-btn:hover {
        background: #c45c4a;
        border-color: #c45c4a;
        color: white;
    }

    .search-sort {
        display: flex;
        gap: 1rem;
    }

    .search-box {
        padding: 0.6rem 1rem;
        border: 1px solid #e2cbb8;
        border-radius: 40px;
        font-family: 'Inter', sans-serif;
        width: 200px;
    }

    .sort-select {
        padding: 0.6rem 1rem;
        border: 1px solid #e2cbb8;
        border-radius: 40px;
        font-family: 'Inter', sans-serif;
        background: white;
        cursor: pointer;
    }

    /* Products Grid */
    .products-section {
        padding: 3rem 5%;
        min-height: 60vh;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        max-width: 1400px;
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
        transform: translateY(-8px);
    }

    .product-card-link:hover .product-card {
        transform: none;
    }

    .product-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        transition: box-shadow 0.3s;
        position: relative;
        height: 100%;
        cursor: pointer;
    }

    .product-card:hover {
        transform: none;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    /* Badge positioning - Left side */
    .badge-left {
        position: absolute;
        top: 1rem;
        left: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        z-index: 2;
    }

    .product-badge {
        background: #c45c4a;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
        width: fit-content;
    }

    .product-rating {
        background: rgba(0,0,0,0.7);
        color: gold;
        padding: 0.25rem 0.7rem;
        border-radius: 20px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        width: fit-content;
        backdrop-filter: blur(4px);
    }

    /* Discount badge - Right side */
    .discount-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: #e53935;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        z-index: 2;
    }

    .product-img {
        height: 280px;
        background-size: cover;
        background-position: center;
        transition: transform 0.5s;
    }

    .product-card:hover .product-img {
        transform: scale(1.05);
    }

    .product-info {
        padding: 1.2rem;
    }

    .product-title {
        font-weight: 700;
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
        color: #3d2a1f;
    }

    .product-desc {
        font-size: 0.85rem;
        color: #7b6b5c;
        margin-bottom: 0.8rem;
        line-height: 1.4;
    }

    /* Price Stock Styles with Discount */
    .price-stock {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .product-price-card {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .original-price-card {
        font-size: 0.9rem;
        color: #999;
        text-decoration: line-through;
    }

    .discounted-price-card {
        font-size: 1.2rem;
        font-weight: 700;
        color: #c45c4a;
    }

    .price {
        font-weight: 700;
        font-size: 1.4rem;
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

    .stock.out-of-stock {
        color: #f44336;
    }

    .button-group {
        display: flex;
        gap: 0.8rem;
        margin-top: 0.5rem;
    }

    .add-to-cart {
        flex: 2;
        background: #2d2a24;
        border: none;
        padding: 0.8rem;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: 0.3s;
        font-family: 'Inter', sans-serif;
    }

    .add-to-cart:hover:not(:disabled) {
        background: #c45c4a;
        transform: scale(0.98);
    }

    .add-to-cart:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .add-to-wishlist {
        flex: 1;
        background: transparent;
        border: 1px solid #e2cbb8;
        padding: 0.8rem;
        border-radius: 12px;
        color: #c45c4a;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .add-to-wishlist:hover {
        background: #c45c4a;
        color: white;
        border-color: #c45c4a;
        transform: scale(0.98);
    }

    /* Cart Sidebar */
    .cart-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(5px);
        z-index: 2000;
        visibility: hidden;
        opacity: 0;
        transition: 0.3s;
    }

    .cart-overlay.open {
        visibility: visible;
        opacity: 1;
    }

    .cart-drawer {
        position: fixed;
        right: 0;
        top: 0;
        width: 400px;
        max-width: 85vw;
        height: 100%;
        background: white;
        box-shadow: -10px 0 30px rgba(0,0,0,0.1);
        z-index: 2001;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    }

    .cart-overlay.open .cart-drawer {
        transform: translateX(0);
    }

    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0e0d4;
        font-size: 1.3rem;
        font-weight: 700;
    }

    .close-cart {
        background: none;
        border: none;
        font-size: 1.8rem;
        cursor: pointer;
        color: #7b6b5c;
    }

    .cart-items {
        flex: 1;
        overflow-y: auto;
        margin: 1rem 0;
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.8rem;
        background: #fef6ef;
        border-radius: 12px;
        margin-bottom: 0.8rem;
    }

    .cart-item-info h4 {
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }

    .cart-item-price {
        color: #c45c4a;
        font-weight: 600;
    }

    .cart-item-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .cart-item-controls button {
        background: white;
        border: 1px solid #e2cbb8;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        cursor: pointer;
        transition: 0.2s;
    }

    .cart-item-controls button:hover {
        background: #c45c4a;
        color: white;
        border-color: #c45c4a;
    }

    .cart-total {
        padding-top: 1rem;
        border-top: 2px solid #f0e0d4;
        font-size: 1.2rem;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
    }

    .checkout-btn {
        background: #c45c4a;
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 12px;
        font-weight: 600;
        margin-top: 1rem;
        cursor: pointer;
        transition: 0.3s;
    }

    .checkout-btn:hover {
        background: #a84a3a;
    }

    .empty-cart {
        text-align: center;
        color: #7b6b5c;
        padding: 2rem;
    }

    /* Toast Notification */
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
    }

    .toast.show {
        opacity: 1;
    }

    /* No Results */
    .no-results {
        text-align: center;
        padding: 4rem;
        color: #7b6b5c;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-controls {
            flex-direction: column;
        }
        .search-sort {
            width: 100%;
        }
        .search-box, .sort-select {
            flex: 1;
        }
        .button-group {
            flex-direction: column;
        }
        .price-stock {
            flex-direction: column;
            align-items: flex-start;
        }
        .product-badge, .discount-badge, .product-rating {
            font-size: 0.65rem;
            padding: 0.2rem 0.6rem;
        }
    }
</style>

<div class="shop-hero">
    <h1>Our Flower Collection</h1>
    <p>Handcrafted with love, delivered with care</p>
</div>

<div class="filter-section">
    <div class="filter-controls">
        <div class="filter-buttons" id="filterButtons">
            <button class="filter-btn active" data-filter="all">All Flowers</button>
            <button class="filter-btn" data-filter="bouquet">Bouquets</button>
            <button class="filter-btn" data-filter="luxury">Luxury</button>
            <button class="filter-btn" data-filter="seasonal">Seasonal</button>
        </div>
        <div class="search-sort">
            <input type="text" id="searchInput" placeholder="Search flowers..." class="search-box">
            <select id="sortSelect" class="sort-select">
                <option value="default">Sort by: Featured</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
                <option value="rating">Rating: Highest</option>
            </select>
        </div>
    </div>
</div>

<section class="products-section">
    <div class="products-grid" id="productsGrid">
        <!-- Products will be loaded here dynamically -->
    </div>
</section>

<!-- Cart Sidebar -->
<div id="cartOverlay" class="cart-overlay">
    <div class="cart-drawer">
        <div class="cart-header">
            <span><i class="fas fa-flower"></i> Your Cart</span>
            <button class="close-cart" id="closeCartBtn">&times;</button>
        </div>
        <div class="cart-items" id="cartItems">
            <div class="empty-cart">Your cart is empty</div>
        </div>
        <div class="cart-total">
            <span>Total</span>
            <span id="cartTotal">$0.00</span>
        </div>
        <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout →</button>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
// Product data from PHP (fetched from database with review data)
const productsData = <?php echo json_encode(array_values($products)); ?>;

// Cart management
let cart = JSON.parse(localStorage.getItem('flowerCart')) || [];

// DOM Elements
let currentFilter = 'all';
let currentSearch = '';
let currentSort = 'default';

// Function to calculate discount percentage
function getDiscountPercentage(originalPrice, currentPrice) {
    if (!originalPrice || originalPrice <= currentPrice) return null;
    return Math.round(((originalPrice - currentPrice) / originalPrice) * 100);
}

// Function to get display rating (round to 1 decimal)
function getDisplayRating(rating) {
    if (!rating || rating == 0) return 'New';
    return rating.toFixed(1);
}

// Function to get star display (for product card - just show number)
function getRatingNumber(rating) {
    if (!rating || rating == 0) return '0.0';
    return rating.toFixed(1);
}

// Save cart to localStorage
function saveCart() {
    localStorage.setItem('flowerCart', JSON.stringify(cart));
    updateCartUI();
    updateCartCount();
}

// Update cart count badge
function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        if (el) el.textContent = totalItems;
    });
}

// Add to cart
function addToCart(product) {
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
    saveCart();
    showToast(`${product.name} added to cart! ✨`);
    openCart();
}

// Add to wishlist
function addToWishlist(productId) {
    window.location.href = `wishlist.php?add=${productId}`;
}

// Update cart item quantity
function updateQuantity(productId, delta) {
    const index = cart.findIndex(item => item.id === productId);
    if (index !== -1) {
        const newQty = cart[index].quantity + delta;
        if (newQty <= 0) {
            cart.splice(index, 1);
        } else {
            cart[index].quantity = newQty;
        }
        saveCart();
        showToast('Cart updated');
    }
}

// Remove from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
    showToast('Item removed from cart');
}

// Update cart UI
function updateCartUI() {
    const cartItemsDiv = document.getElementById('cartItems');
    const cartTotalSpan = document.getElementById('cartTotal');
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<div class="empty-cart">🌸 Your cart is empty</div>';
        cartTotalSpan.textContent = '$0.00';
        return;
    }
    
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <h4>${escapeHtml(item.name)}</h4>
                    <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                </div>
                <div class="cart-item-controls">
                    <button onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQuantity(${item.id}, 1)">+</button>
                    <button onclick="removeFromCart(${item.id})" style="background: #fee; color: #c45c4a;">🗑️</button>
                </div>
            </div>
        `;
    });
    
    cartItemsDiv.innerHTML = html;
    cartTotalSpan.textContent = `$${total.toFixed(2)}`;
    updateCartCount();
}

// Show toast notification
function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 2000);
}

// Filter and sort products
function filterAndSortProducts() {
    let filtered = [...productsData];
    
    // Apply category filter
    if (currentFilter !== 'all') {
        filtered = filtered.filter(p => p.category === currentFilter);
    }
    
    // Apply search filter
    if (currentSearch) {
        const searchLower = currentSearch.toLowerCase();
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(searchLower) || 
            (p.description && p.description.toLowerCase().includes(searchLower))
        );
    }
    
    // Apply sorting
    switch (currentSort) {
        case 'price-asc':
            filtered.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
            break;
        case 'price-desc':
            filtered.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
            break;
        case 'rating':
            filtered.sort((a, b) => (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0));
            break;
        default:
            break;
    }
    
    renderProducts(filtered);
}

// Render products
function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    
    if (products.length === 0) {
        grid.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3>No flowers found</h3>
                <p>Try adjusting your search or filter</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    products.forEach(product => {
        const stockStatus = product.stock > 10 ? 'in-stock' : (product.stock > 0 ? 'low-stock' : 'out-of-stock');
        const stockText = product.stock > 10 ? 'In Stock' : (product.stock > 0 ? `Only ${product.stock} left` : 'Out of Stock');
        const disabled = product.stock === 0 ? 'disabled' : '';
        const discountPercent = getDiscountPercentage(product.original_price, product.price);
        const displayPrice = parseFloat(product.price);
        const displayOriginalPrice = product.original_price ? parseFloat(product.original_price) : null;
        const productDesc = product.description || product.full_description || 'Beautiful flower arrangement';
        const rating = parseFloat(product.avg_rating) || 0;
        const reviewCount = parseInt(product.review_count) || 0;
        const ratingDisplay = rating > 0 ? rating.toFixed(1) : '0.0';
        
        // Build left badges HTML (product badge and rating)
        let leftBadgesHtml = '';
        if (product.stock < 5 && product.stock > 0) {
            leftBadgesHtml += '<div class="product-badge">🔥 Almost Gone</div>';
        }
        leftBadgesHtml += `<div class="product-rating"><i class="fas fa-star"></i> ${ratingDisplay} (${reviewCount})</div>`;
        
        html += `
            <a href="product_details.php?id=${product.id}" class="product-card-link">
                <div class="product-card" data-id="${product.id}">
                    <div class="product-img" style="background-image: url('${product.image}'); background-size: cover; background-position: center;"></div>
                    
                    <!-- Left side badges (Product Badge + Rating) -->
                    <div class="badge-left">
                        ${leftBadgesHtml}
                    </div>
                    
                    <!-- Right side badge (Discount) -->
                    ${discountPercent ? `<div class="discount-badge">-${discountPercent}% OFF</div>` : ''}
                    
                    <div class="product-info">
                        <h3 class="product-title">${escapeHtml(product.name)}</h3>
                        <p class="product-desc">${escapeHtml(productDesc.substring(0, 80))}${productDesc.length > 80 ? '...' : ''}</p>
                        <div class="price-stock">
                            <div class="product-price-card">
                                ${discountPercent ? `
                                    <span class="original-price-card">$${displayOriginalPrice.toFixed(2)}</span>
                                    <span class="discounted-price-card">$${displayPrice.toFixed(2)}</span>
                                ` : `
                                    <span class="price">$${displayPrice.toFixed(2)}</span>
                                `}
                            </div>
                            <span class="stock ${stockStatus}">${stockText}</span>
                        </div>
                        <div class="button-group" onclick="event.stopPropagation()">
                            <button class="add-to-cart" onclick="addToCart(${JSON.stringify(product).replace(/"/g, '&quot;')})" ${disabled}>
                                <i class="fas fa-shopping-cart"></i> ${disabled ? 'Out of Stock' : 'Add to Cart'}
                            </button>
                            <button class="add-to-wishlist" onclick="addToWishlist(${product.id})">
                                <i class="fas fa-heart"></i> Save
                            </button>
                        </div>
                    </div>
                </div>
            </a>
        `;
    });
    
    grid.innerHTML = html;
}

// Escape HTML
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Cart drawer controls
function openCart() {
    document.getElementById('cartOverlay').classList.add('open');
    updateCartUI();
}

function closeCart() {
    document.getElementById('cartOverlay').classList.remove('open');
}

// Checkout
function checkout() {
    if (cart.length === 0) {
        showToast('Your cart is empty! Add some beautiful flowers first 🌸');
        return;
    }
    showToast('Proceeding to checkout... 🎉');
    setTimeout(() => {
        window.location.href = 'checkout.php';
    }, 500);
}

// Event listeners
const cartIcon = document.getElementById('cartIcon');
if (cartIcon) {
    cartIcon.addEventListener('click', openCart);
}
document.getElementById('closeCartBtn')?.addEventListener('click', closeCart);
document.getElementById('checkoutBtn')?.addEventListener('click', checkout);

// Filter buttons
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.filter;
        filterAndSortProducts();
    });
});

// Search input
document.getElementById('searchInput')?.addEventListener('input', (e) => {
    currentSearch = e.target.value;
    filterAndSortProducts();
});

// Sort select
document.getElementById('sortSelect')?.addEventListener('change', (e) => {
    currentSort = e.target.value;
    filterAndSortProducts();
});

// Close cart when clicking overlay
document.getElementById('cartOverlay')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('cartOverlay')) {
        closeCart();
    }
});

// Initialize
filterAndSortProducts();
updateCartCount();

// Cart count badge shows items from localStorage
window.addEventListener('load', () => {
    updateCartUI();
});

// Update cart count globally when called from other pages
window.updateCartCountGlobal = updateCartCount;
</script>

<?php include 'footer.php'; ?>