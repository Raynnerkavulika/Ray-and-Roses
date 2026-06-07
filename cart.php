<?php
session_start();
require_once 'config/database.php';
require_once 'config/check_auth.php'; // Ensure user is logged in

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch recommended products from database
$recommended_sql = "SELECT id, name, price, image FROM products WHERE status = 'active' ORDER BY RAND() LIMIT 4";
$recommended_result = $conn->query($recommended_sql);
$recommended_products = [];

if($recommended_result && $recommended_result->num_rows > 0) {
    while($row = $recommended_result->fetch_assoc()) {
        $recommended_products[] = $row;
    }
}

if(empty($recommended_products)) {
    $fallback_sql = "SELECT id, name, price, image FROM products LIMIT 4";
    $fallback_result = $conn->query($fallback_sql);
    if($fallback_result && $fallback_result->num_rows > 0) {
        while($row = $fallback_result->fetch_assoc()) {
            $recommended_products[] = $row;
        }
    }
}

include 'header.php';
?>

<style>
    .cart-container {
        max-width: 1000px;
        margin: 1.5rem auto;
        padding: 0 1rem;
    }

    .cart-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .cart-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        color: #3d2a1f;
        margin-bottom: 0.2rem;
    }

    .cart-header p {
        color: #7b6b5c;
        font-size: 0.8rem;
    }

    .cart-wrapper {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    /* Cart Items - Left Side */
    .cart-items-section {
        flex: 2;
        min-width: 280px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .cart-item {
        display: flex;
        align-items: center;
        padding: 0.8rem;
        border-bottom: 1px solid #f0e0d4;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .cart-item-img {
        width: 55px;
        height: 55px;
        border-radius: 8px;
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
    }

    .cart-item-details {
        flex: 2;
        min-width: 120px;
    }

    .cart-item-details h4 {
        font-size: 0.85rem;
        font-weight: 600;
        color: #3d2a1f;
        margin-bottom: 0.2rem;
    }

    .cart-item-details p {
        font-size: 0.7rem;
        color: #7b6b5c;
    }

    .cart-item-price {
        font-weight: 600;
        color: #c45c4a;
        font-size: 0.85rem;
        min-width: 60px;
    }

    .cart-item-quantity {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .cart-item-quantity button {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 1px solid #e2cbb8;
        background: white;
        cursor: pointer;
        font-size: 0.75rem;
        font-weight: bold;
    }

    .cart-item-quantity button:hover {
        background: #c45c4a;
        color: white;
        border-color: #c45c4a;
    }

    .cart-item-quantity span {
        min-width: 20px;
        text-align: center;
        font-size: 0.8rem;
    }

    .cart-item-subtotal {
        font-weight: 600;
        color: #c45c4a;
        font-size: 0.85rem;
        min-width: 65px;
    }

    .cart-item-remove {
        background: none;
        border: none;
        color: #c45c4a;
        cursor: pointer;
        font-size: 0.9rem;
        padding: 4px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .cart-item-remove:hover {
        background: #fee;
        color: #a84a3a;
    }

    /* Cart Summary - Right Side */
    .cart-summary {
        flex: 1;
        min-width: 250px;
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: sticky;
        top: 90px;
        height: fit-content;
    }

    .cart-summary h3 {
        font-size: 1.1rem;
        color: #3d2a1f;
        margin-bottom: 0.8rem;
        padding-bottom: 0.4rem;
        border-bottom: 1px solid #f0e0d4;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.4rem 0;
        color: #5a3f2c;
        font-size: 0.8rem;
    }

    .summary-row.total {
        border-top: 1px solid #f0e0d4;
        margin-top: 0.4rem;
        padding-top: 0.6rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: #c45c4a;
    }

    .summary-row.discount {
        color: #4caf50;
    }

    .coupon-section {
        margin: 0.6rem 0;
        padding: 0.6rem 0;
        border-top: 1px solid #f0e0d4;
        border-bottom: 1px solid #f0e0d4;
    }

    .coupon-input {
        display: flex;
        gap: 0.4rem;
    }

    .coupon-input input {
        flex: 1;
        padding: 0.4rem 0.6rem;
        border: 1px solid #e2cbb8;
        border-radius: 6px;
        font-size: 0.75rem;
    }

    .coupon-input button {
        background: #2d2a24;
        color: white;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.7rem;
    }

    .coupon-input button:hover {
        background: #c45c4a;
    }

    .checkout-btn {
        width: 100%;
        background: #c45c4a;
        color: white;
        border: none;
        padding: 0.7rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }

    .checkout-btn:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    /* Continue Shopping Button - Styled */
    .continue-shopping {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 0.8rem;
        padding: 0.6rem 1rem;
        background: #f5f0eb;
        border-radius: 50px;
        color: #c45c4a;
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .continue-shopping:hover {
        background: #c45c4a;
        color: white;
        transform: translateX(-3px);
        border-color: #c45c4a;
    }

    .continue-shopping i {
        font-size: 0.8rem;
        transition: transform 0.3s ease;
    }

    .continue-shopping:hover i {
        transform: translateX(-3px);
    }

    /* Empty Cart */
    .empty-cart {
        text-align: center;
        padding: 2rem;
        background: white;
        border-radius: 12px;
    }

    .empty-cart i {
        font-size: 2.5rem;
        color: #e2cbb8;
        margin-bottom: 0.8rem;
    }

    .empty-cart h2 {
        font-size: 1.2rem;
        margin-bottom: 0.3rem;
    }

    .empty-cart p {
        font-size: 0.8rem;
        margin-bottom: 1rem;
    }

    .shop-now-btn {
        display: inline-block;
        background: #c45c4a;
        color: white;
        padding: 0.5rem 1.2rem;
        border-radius: 50px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: 0.3s;
    }

    .shop-now-btn:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    /* Recommended Products */
    .recommended-section {
        margin-top: 1.5rem;
    }

    .recommended-title {
        font-size: 1.2rem;
        text-align: center;
        margin-bottom: 1rem;
        color: #3d2a1f;
    }

    .recommended-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.8rem;
    }

    .recommended-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        transition: transform 0.2s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .recommended-card:hover {
        transform: translateY(-3px);
    }

    .recommended-img {
        height: 120px;
        background-size: cover;
        background-position: center;
    }

    .recommended-info {
        padding: 0.6rem;
        text-align: center;
    }

    .recommended-info h4 {
        font-size: 0.75rem;
        margin-bottom: 0.2rem;
    }

    .recommended-price {
        color: #c45c4a;
        font-weight: 600;
        font-size: 0.75rem;
        margin-bottom: 0.3rem;
    }

    .add-cart-recommend {
        background: #2d2a24;
        color: white;
        border: none;
        padding: 0.3rem 0.6rem;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.65rem;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
    }

    .add-cart-recommend:hover {
        background: #c45c4a;
    }

    @media (max-width: 700px) {
        .cart-wrapper {
            flex-direction: column;
        }
        .cart-item {
            flex-wrap: wrap;
        }
        .cart-item-details {
            flex: 1;
            min-width: 100%;
        }
    }
</style>

<div class="cart-container">
    <div class="cart-header">
        <h1><i class="fas fa-shopping-bag"></i> Your Cart</h1>
        <p>Review your items before checkout</p>
    </div>

    <div id="cartContent"></div>
</div>

<script>
const recommendedProducts = <?php echo json_encode($recommended_products); ?>;
let cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
let appliedCoupon = null;
let discount = 0;

function saveCart() {
    localStorage.setItem('flowerCart', JSON.stringify(cart));
    renderCart();
    updateCartCount();
}

function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.querySelectorAll('.cart-count').forEach(el => {
        if (el) el.textContent = totalItems;
    });
}

function updateQuantity(productId, delta) {
    const index = cart.findIndex(item => item.id == productId);
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

function removeItem(productId) {
    const idToRemove = parseInt(productId);
    cart = cart.filter(item => item.id != idToRemove);
    saveCart();
    showToast('Item removed');
}

function applyCoupon() {
    const code = document.getElementById('couponCode').value;
    if (code === 'WELCOME10') {
        discount = 10;
        appliedCoupon = 'WELCOME10';
        showToast('10% off applied!');
    } else if (code === 'FLOWER20') {
        discount = 20;
        appliedCoupon = 'FLOWER20';
        showToast('20% off applied!');
    } else if (code === 'FREE50') {
        if (getCartTotal() > 50) {
            discount = 5;
            appliedCoupon = 'FREE50';
            showToast('$5 off applied!');
        } else {
            showToast('Min $50 required');
        }
    } else {
        showToast('Invalid code');
    }
    renderCart();
}

function getCartSubtotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

function getCartTotal() {
    let total = getCartSubtotal();
    if (appliedCoupon === 'WELCOME10') total = total * 0.9;
    else if (appliedCoupon === 'FLOWER20') total = total * 0.8;
    else if (appliedCoupon === 'FREE50') total = total - 5;
    return total;
}

function getShipping() {
    return getCartSubtotal() > 50 ? 0 : 5.99;
}

function showToast(msg) {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2000);
}

function renderCart() {
    const container = document.getElementById('cartContent');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-bag"></i>
                <h2>Your cart is empty</h2>
                <p>Add some beautiful flowers to get started!</p>
                <a href="shop.php" class="shop-now-btn"><i class="fas fa-store"></i> Shop Now</a>
            </div>
        `;
        updateCartCount();
        return;
    }
    
    const subtotal = getCartSubtotal();
    const shipping = getShipping();
    const discountAmt = appliedCoupon ? (appliedCoupon === 'FREE50' ? 5 : subtotal * (discount / 100)) : 0;
    const total = getCartTotal() + shipping;
    
    let itemsHtml = '';
    cart.forEach(item => {
        itemsHtml += `
            <div class="cart-item">
                <div class="cart-item-img" style="background-image: url('${item.image || 'https://via.placeholder.com/55'}');"></div>
                <div class="cart-item-details">
                    <h4>${escapeHtml(item.name)}</h4>
                    <p>Fresh flowers</p>
                </div>
                <div class="cart-item-price">$${parseFloat(item.price).toFixed(2)}</div>
                <div class="cart-item-quantity">
                    <button onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQuantity(${item.id}, 1)">+</button>
                </div>
                <div class="cart-item-subtotal">$${(parseFloat(item.price) * item.quantity).toFixed(2)}</div>
                <button class="cart-item-remove" onclick="removeItem(${item.id})" title="Remove"><i class="fas fa-trash-alt"></i></button>
            </div>
        `;
    });
    
    let html = `
        <div class="cart-wrapper">
            <div class="cart-items-section">
                ${itemsHtml}
            </div>
            <div class="cart-summary">
                <h3>Summary</h3>
                <div class="summary-row"><span>Subtotal</span><span>$${subtotal.toFixed(2)}</span></div>
                <div class="summary-row"><span>Shipping</span><span>${shipping === 0 ? 'Free' : '$' + shipping.toFixed(2)}</span></div>
                ${appliedCoupon ? `<div class="summary-row discount"><span>Discount</span><span>-$${discountAmt.toFixed(2)}</span></div>` : ''}
                <div class="coupon-section">
                    <div class="coupon-input">
                        <input type="text" id="couponCode" placeholder="Coupon code">
                        <button onclick="applyCoupon()">Apply</button>
                    </div>
                </div>
                <div class="summary-row total"><span>Total</span><span>$${total.toFixed(2)}</span></div>
                <button class="checkout-btn" onclick="proceedToCheckout()"><i class="fas fa-credit-card"></i> Checkout</button>
                <a href="shop.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
    `;
    
    if (recommendedProducts.length > 0) {
        html += `
            <div class="recommended-section">
                <h3 class="recommended-title">You May Also Like</h3>
                <div class="recommended-grid">
                    ${recommendedProducts.map(p => `
                        <div class="recommended-card" onclick="addToCartById(${p.id})">
                            <div class="recommended-img" style="background-image: url('${p.image}');"></div>
                            <div class="recommended-info">
                                <h4>${escapeHtml(p.name)}</h4>
                                <div class="recommended-price">$${parseFloat(p.price).toFixed(2)}</div>
                                <button class="add-cart-recommend" onclick="event.stopPropagation(); addToCartById(${p.id})">
                                    <i class="fas fa-cart-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    updateCartCount();
}

function addToCartById(id) {
    let product = recommendedProducts.find(p => p.id == id);
    if (product) {
        let existing = cart.find(item => item.id == product.id);
        if (existing) existing.quantity++;
        else cart.push({ id: product.id, name: product.name, price: parseFloat(product.price), quantity: 1, image: product.image });
        saveCart();
        showToast(`${product.name} added!`);
    }
}

function proceedToCheckout() {
    if (cart.length === 0) { showToast('Cart is empty!'); return; }
    window.location.href = 'checkout.php';
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

window.removeItem = removeItem;
renderCart();

if (!document.querySelector('#toast-styles')) {
    const style = document.createElement('style');
    style.textContent = `.toast{position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);background:#2d2a24;color:white;padding:0.6rem 1.2rem;border-radius:50px;z-index:3000;opacity:0;transition:opacity 0.3s;pointer-events:none;font-size:0.8rem}.toast.show{opacity:1}`;
    document.head.appendChild(style);
}
</script>

<?php include 'footer.php'; ?>