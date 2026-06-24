<?php
session_start();
require_once 'config/database.php';
require_once 'config/check_auth.php'; // Ensure user is logged in

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Initialize cart from database
$cart_items = [];
$cart_total = 0;
$cart_count = 0;

// Fetch cart items from database
$cart_sql = "SELECT c.id as cart_id, c.product_id, c.quantity, p.id, p.name, p.price, p.image 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if($cart_result && $cart_result->num_rows > 0) {
    while($row = $cart_result->fetch_assoc()) {
        $cart_items[] = $row;
        $cart_total += $row['price'] * $row['quantity'];
        $cart_count += $row['quantity'];
    }
}
$cart_stmt->close();

// Store cart count in session for header display
$_SESSION['cart_count'] = $cart_count;

// Handle AJAX requests for cart operations
if(isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch($_POST['action']) {
        case 'add':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            // Check if product exists
            $check_sql = "SELECT id, price FROM products WHERE id = ? AND status = 'active'";
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
            
        case 'update':
            $cart_id = intval($_POST['cart_id']);
            $quantity = intval($_POST['quantity']);
            
            if($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                $delete_sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("ii", $cart_id, $user_id);
                if($delete_stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Item removed from cart'];
                }
                $delete_stmt->close();
            } else {
                $update_sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $quantity, $cart_id, $user_id);
                if($update_stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Cart updated successfully'];
                }
                $update_stmt->close();
            }
            break;
            
        case 'remove':
            $cart_id = intval($_POST['cart_id']);
            $delete_sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $cart_id, $user_id);
            if($delete_stmt->execute()) {
                $response = ['success' => true, 'message' => 'Item removed from cart'];
            }
            $delete_stmt->close();
            break;
            
        case 'clear':
            $delete_sql = "DELETE FROM cart WHERE user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $user_id);
            if($delete_stmt->execute()) {
                $response = ['success' => true, 'message' => 'Cart cleared successfully'];
            }
            $delete_stmt->close();
            break;
    }
    
    // Update session cart count
    $count_sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $_SESSION['cart_count'] = $count_row['total'] ?? 0;
    $count_stmt->close();
    
    echo json_encode($response);
    exit();
}

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
        transition: 0.3s;
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
        transition: 0.3s;
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
        transition: 0.3s;
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

    /* Continue Shopping Button */
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
        transition: 0.3s;
    }

    .add-cart-recommend:hover {
        background: #c45c4a;
    }

    .toast {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        background: #2d2a24;
        color: white;
        padding: 0.6rem 1.2rem;
        border-radius: 50px;
        z-index: 3000;
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
        font-size: 0.8rem;
    }

    .toast.show {
        opacity: 1;
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

    <div id="cartContent">
        <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-bag"></i>
            <h2>Your cart is empty</h2>
            <p>Add some beautiful flowers to get started!</p>
            <a href="shop.php" class="shop-now-btn"><i class="fas fa-store"></i> Shop Now</a>
        </div>
        <?php else: ?>
        <div class="cart-wrapper">
            <div class="cart-items-section">
                <?php foreach($cart_items as $item): ?>
                <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
                    <div class="cart-item-img" style="background-image: url('<?php echo $item['image'] ?? 'https://via.placeholder.com/55'; ?>');"></div>
                    <div class="cart-item-details">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p>Fresh flowers</p>
                    </div>
                    <div class="cart-item-price">KSh <?php echo number_format($item['price'], 2); ?></div>
                    <div class="cart-item-quantity">
                        <button onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1)">-</button>
                        <span id="qty-<?php echo $item['cart_id']; ?>"><?php echo $item['quantity']; ?></span>
                        <button onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1)">+</button>
                    </div>
                    <div class="cart-item-subtotal">KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                    <button class="cart-item-remove" onclick="removeItem(<?php echo $item['cart_id']; ?>)" title="Remove"><i class="fas fa-trash-alt"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="cart-summary">
                <h3>Summary</h3>
                <div class="summary-row"><span>Subtotal</span><span>KSh <?php echo number_format($cart_total, 2); ?></span></div>
                <div class="summary-row"><span>Shipping</span><span><?php echo $cart_total > 50 ? 'Free' : 'KSh 5.99'; ?></span></div>
                <div class="coupon-section">
                    <div class="coupon-input">
                        <input type="text" id="couponCode" placeholder="Coupon code">
                        <button onclick="applyCoupon()">Apply</button>
                    </div>
                </div>
                <div class="summary-row total"><span>Total</span><span>KSh <?php echo number_format($cart_total + ($cart_total > 50 ? 0 : 5.99), 2); ?></span></div>
                <button class="checkout-btn" onclick="proceedToCheckout()"><i class="fas fa-credit-card"></i> Checkout</button>
                <a href="shop.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if(!empty($recommended_products)): ?>
    <div class="recommended-section">
        <h3 class="recommended-title">You May Also Like</h3>
        <div class="recommended-grid">
            <?php foreach($recommended_products as $product): ?>
            <div class="recommended-card">
                <div class="recommended-img" style="background-image: url('<?php echo $product['image']; ?>');"></div>
                <div class="recommended-info">
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <div class="recommended-price">KSh <?php echo number_format($product['price'], 2); ?></div>
                    <button class="add-cart-recommend" onclick="addToCart(<?php echo $product['id']; ?>)">
                        <i class="fas fa-cart-plus"></i> Add
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="toast" id="toast"></div>

<script>
function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function updateQuantity(cartId, delta) {
    const qtySpan = document.getElementById('qty-' + cartId);
    let newQty = parseInt(qtySpan.textContent) + delta;
    
    if (newQty < 1) {
        if (confirm('Remove this item from cart?')) {
            removeItem(cartId);
        }
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'update',
            cart_id: cartId,
            quantity: newQty
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showToast(data.message || 'Error updating cart');
        }
    })
    .catch(error => {
        showToast('Error updating cart');
    });
}

function removeItem(cartId) {
    if (!confirm('Remove this item from cart?')) return;
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'remove',
            cart_id: cartId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showToast(data.message || 'Error removing item');
        }
    })
    .catch(error => {
        showToast('Error removing item');
    });
}

function addToCart(productId) {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'add',
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.message || 'Error adding to cart');
        }
    })
    .catch(error => {
        showToast('Error adding to cart');
    });
}

function applyCoupon() {
    const code = document.getElementById('couponCode').value;
    if (code === 'WELCOME10') {
        showToast('10% off applied!');
    } else if (code === 'FLOWER20') {
        showToast('20% off applied!');
    } else {
        showToast('Invalid coupon code');
    }
}

function proceedToCheckout() {
    <?php if(empty($cart_items)): ?>
    showToast('Cart is empty!');
    <?php else: ?>
    window.location.href = 'checkout.php';
    <?php endif; ?>
}

// Update cart count in header
document.addEventListener('DOMContentLoaded', function() {
    const cartCount = <?php echo $cart_count; ?>;
    document.querySelectorAll('.cart-count').forEach(el => {
        el.textContent = cartCount;
    });
});
</script>

<?php include 'footer.php'; ?>