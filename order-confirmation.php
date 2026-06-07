<?php
session_start();
require_once 'config/database.php';
require_once 'config/check_auth.php';

include 'header.php';
?>

<style>
    .confirmation-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    .confirmation-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }

    .success-icon {
        font-size: 4rem;
        color: #4caf50;
        margin-bottom: 1rem;
    }

    .confirmation-card h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .order-number {
        background: #fef6ef;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        display: inline-block;
        margin: 1rem 0;
        font-weight: 600;
        color: #c45c4a;
    }

    .order-details {
        text-align: left;
        margin: 2rem 0;
        padding: 1rem;
        background: #f9f9f9;
        border-radius: 10px;
    }

    .order-details p {
        margin-bottom: 0.5rem;
    }

    .btn-continue {
        display: inline-block;
        background: #c45c4a;
        color: white;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }

    .btn-continue:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }
</style>

<div class="confirmation-container">
    <div class="confirmation-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Order Placed Successfully!</h1>
        <p>Thank you for your purchase. Your beautiful flowers will be on their way soon!</p>
        
        <div id="orderDetails"></div>
        
        <p>A confirmation email has been sent to your email address.</p>
        <div style="margin-top: 2rem;">
            <a href="shop.php" class="btn-continue">Continue Shopping →</a>
        </div>
    </div>
</div>

<script>
// Get last order from localStorage
const lastOrder = JSON.parse(localStorage.getItem('lastOrder'));

if (lastOrder) {
    const orderDetails = document.getElementById('orderDetails');
    orderDetails.innerHTML = `
        <div class="order-number">Order #: ${lastOrder.order_number}</div>
        <div class="order-details">
            <p><strong>Total Amount:</strong> $${lastOrder.total.toFixed(2)}</p>
            <p><strong>Payment Method:</strong> ${lastOrder.payment_method.charAt(0).toUpperCase() + lastOrder.payment_method.slice(1)}</p>
            <p><strong>Shipping to:</strong> ${lastOrder.shipping.address}, ${lastOrder.shipping.city}</p>
        </div>
    `;
}

// Clear cart from localStorage
localStorage.removeItem('flowerCart');
localStorage.removeItem('appliedCoupon');
localStorage.removeItem('discount');
localStorage.removeItem('lastOrder');

// Update cart count
function updateCartCount() {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        if (el) el.textContent = '0';
    });
}
updateCartCount();
</script>

<?php include 'footer.php'; ?>