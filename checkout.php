<?php
session_start();
require_once 'config/database.php';
require_once 'config/check_auth.php'; // Ensure user is logged in

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user data from database
$user_sql = "SELECT first_name, last_name, email, phone FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Include header
include 'header.php';
?>

<style>
    .checkout-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    .checkout-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .checkout-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .checkout-header p {
        color: #7b6b5c;
    }

    .checkout-wrapper {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 2rem;
    }

    .checkout-form {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f0e0d4;
    }

    .form-section h3 {
        font-size: 1.2rem;
        color: #3d2a1f;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #555;
        font-size: 0.85rem;
    }

    .form-group label .required {
        color: #f44336;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 2px solid #f0e0d4;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        transition: 0.3s;
        font-size: 0.9rem;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #c45c4a;
        box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
    }

    .payment-methods {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .payment-option {
        flex: 1;
        border: 2px solid #f0e0d4;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        cursor: pointer;
        transition: 0.3s;
        min-width: 100px;
    }

    .payment-option:hover {
        border-color: #c45c4a;
    }

    .payment-option.selected {
        border-color: #c45c4a;
        background: #fef6ef;
    }

    .payment-option i {
        font-size: 2rem;
        margin-bottom: 0.3rem;
        display: block;
    }

    .payment-option span {
        font-size: 0.85rem;
    }

    .order-summary {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
        height: fit-content;
    }

    .order-summary h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f0e0d4;
    }

    .cart-items-preview {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 1rem;
    }

    .cart-item-preview {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.8rem;
        padding: 0.5rem;
        border-bottom: 1px solid #f0e0d4;
        font-size: 0.85rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        color: #5a3f2c;
    }

    .summary-row.total {
        border-top: 2px solid #f0e0d4;
        margin-top: 0.5rem;
        padding-top: 1rem;
        font-size: 1.2rem;
        font-weight: 700;
        color: #c45c4a;
    }

    .place-order-btn {
        width: 100%;
        background: #c45c4a;
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .place-order-btn:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    .place-order-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    /* Back to Cart Button - Styled */
    .back-to-cart {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
        padding: 0.7rem 1rem;
        background: #f5f0eb;
        border-radius: 50px;
        color: #c45c4a;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .back-to-cart:hover {
        background: #c45c4a;
        color: white;
        transform: translateX(-3px);
        border-color: #c45c4a;
    }

    .back-to-cart i {
        font-size: 0.85rem;
        transition: transform 0.3s ease;
    }

    .back-to-cart:hover i {
        transform: translateX(-3px);
    }

    /* M-Pesa Modal Styles */
    .mpesa-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 4000;
        justify-content: center;
        align-items: center;
    }

    .mpesa-modal-content {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        max-width: 400px;
        width: 90%;
        text-align: center;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .mpesa-modal-content i {
        font-size: 4rem;
        color: #4CAF50;
        margin-bottom: 1rem;
    }

    .mpesa-modal-content h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .mpesa-modal-content p {
        color: #666;
        margin-bottom: 0.5rem;
    }

    .mpesa-modal-content .amount {
        font-size: 1.8rem;
        font-weight: bold;
        color: #c45c4a;
        margin: 1rem 0;
    }

    .mpesa-modal-content .phone-number {
        background: #f5f0eb;
        padding: 0.5rem;
        border-radius: 10px;
        font-weight: bold;
        margin: 1rem 0;
    }

    .modal-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .modal-btn {
        flex: 1;
        padding: 0.8rem;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
    }

    .modal-btn.confirm {
        background: #c45c4a;
        color: white;
    }

    .modal-btn.confirm:hover {
        background: #a84a3a;
    }

    .modal-btn.cancel {
        background: #f0e0d4;
        color: #3d2a1f;
    }

    .modal-btn.cancel:hover {
        background: #e0d0c4;
    }

    .loader {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #c45c4a;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 0.5rem;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .checkout-wrapper {
            grid-template-columns: 1fr;
        }
        .form-row {
            grid-template-columns: 1fr;
        }
        .payment-methods {
            flex-direction: column;
        }
    }
</style>

<div class="checkout-container">
    <div class="checkout-header">
        <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        <p>Complete your order to get your beautiful flowers delivered</p>
    </div>

    <div id="checkoutContent">
        <div class="checkout-wrapper">
            <div class="checkout-form">
                <form id="checkoutForm">
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" id="firstName" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" id="lastName" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Address <span class="required">*</span></label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number <span class="required">*</span></label>
                            <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
                        <div class="form-group">
                            <label>Street Address <span class="required">*</span></label>
                            <input type="text" name="address" id="address" placeholder="House number and street name" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>City <span class="required">*</span></label>
                                <input type="text" name="city" id="city" placeholder="e.g., Nairobi" required>
                            </div>
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" id="postalCode" placeholder="e.g., 00100">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Delivery Notes (Optional)</label>
                            <textarea name="notes" id="notes" rows="3" placeholder="Special instructions for delivery..."></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                        <div class="payment-methods">
                            <div class="payment-option" data-method="card">
                                <i class="fas fa-credit-card"></i>
                                <span>Credit Card</span>
                            </div>
                            <div class="payment-option" data-method="mpesa">
                                <i class="fas fa-mobile-alt"></i>
                                <span>M-Pesa</span>
                            </div>
                            <div class="payment-option" data-method="paypal">
                                <i class="fab fa-paypal"></i>
                                <span>PayPal</span>
                            </div>
                            <div class="payment-option" data-method="cod">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Cash on Delivery</span>
                            </div>
                        </div>
                        <input type="hidden" name="payment_method" id="paymentMethod" value="card">
                        
                        <div id="mpesaDetails" style="display: none; margin-top: 1rem;">
                            <div class="form-group">
                                <label>M-Pesa Phone Number</label>
                                <input type="tel" id="mpesaNumber" placeholder="0712345678">
                                <small>You will receive a prompt on your phone to complete payment</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="cart-items-preview" id="cartItemsPreview"></div>
                <div id="orderTotals"></div>
                <button class="place-order-btn" onclick="placeOrder()">
                    <i class="fas fa-check-circle"></i> Place Order
                </button>
                <a href="cart.php" class="back-to-cart">
                    <i class="fas fa-arrow-left"></i> Back to Cart
                </a>
            </div>
        </div>
    </div>
</div>

<!-- M-Pesa Payment Modal -->
<div id="mpesaModal" class="mpesa-modal">
    <div class="mpesa-modal-content">
        <i class="fas fa-mobile-alt"></i>
        <h3>M-Pesa Payment</h3>
        <p>You will receive a prompt on your phone to complete payment</p>
        <div class="amount" id="modalAmount">$0.00</div>
        <div class="phone-number" id="modalPhone">Phone: </div>
        <p><small>Please check your phone and enter your M-Pesa PIN to complete the payment</small></p>
        <div class="modal-buttons">
            <button class="modal-btn cancel" onclick="closeMpesaModal()">Cancel</button>
            <button class="modal-btn confirm" id="confirmPaymentBtn" onclick="confirmMpesaPayment()">
                I've Completed Payment
            </button>
        </div>
    </div>
</div>

<script>
let cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
let appliedCoupon = localStorage.getItem('appliedCoupon');
let discount = parseFloat(localStorage.getItem('discount')) || 0;
let pendingOrderData = null;
let orderCheckInterval = null;

// Payment method selection
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
        const method = this.getAttribute('data-method');
        document.getElementById('paymentMethod').value = method;
        
        if (method === 'mpesa') {
            document.getElementById('mpesaDetails').style.display = 'block';
        } else {
            document.getElementById('mpesaDetails').style.display = 'none';
        }
    });
});

function getCartSubtotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

function getCartTotal() {
    let total = getCartSubtotal();
    if (appliedCoupon === 'WELCOME10') {
        total = total * 0.9;
    } else if (appliedCoupon === 'FLOWER20') {
        total = total * 0.8;
    } else if (appliedCoupon === 'FREE50') {
        total = total - 5;
    }
    return total;
}

function getShipping() {
    const subtotal = getCartSubtotal();
    if (subtotal > 50) return 0;
    return 5.99;
}

function renderOrderSummary() {
    const cartItemsPreview = document.getElementById('cartItemsPreview');
    const orderTotals = document.getElementById('orderTotals');
    
    if (cart.length === 0) {
        window.location.href = 'cart.php';
        return;
    }
    
    let itemsHtml = '';
    cart.forEach(item => {
        itemsHtml += `
            <div class="cart-item-preview">
                <span>${escapeHtml(item.name)} x ${item.quantity}</span>
                <span>$${(item.price * item.quantity).toFixed(2)}</span>
            </div>
        `;
    });
    cartItemsPreview.innerHTML = itemsHtml;
    
    const subtotal = getCartSubtotal();
    const shipping = getShipping();
    const discountAmount = appliedCoupon ? (appliedCoupon === 'FREE50' ? 5 : subtotal * (discount / 100)) : 0;
    const total = getCartTotal() + shipping;
    
    let totalsHtml = `
        <div class="summary-row">
            <span>Subtotal</span>
            <span>$${subtotal.toFixed(2)}</span>
        </div>
        <div class="summary-row">
            <span>Shipping</span>
            <span>${shipping === 0 ? 'Free' : '$' + shipping.toFixed(2)}</span>
        </div>
    `;
    
    if (appliedCoupon) {
        totalsHtml += `
            <div class="summary-row discount">
                <span>Discount (${discount}% off)</span>
                <span>-$${discountAmount.toFixed(2)}</span>
            </div>
        `;
    }
    
    totalsHtml += `
        <div class="summary-row total">
            <span>Total</span>
            <span>$${total.toFixed(2)}</span>
        </div>
    `;
    
    orderTotals.innerHTML = totalsHtml;
}

function validateForm() {
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const address = document.getElementById('address').value;
    const city = document.getElementById('city').value;
    
    if (!firstName || !lastName || !email || !phone || !address || !city) {
        showToast('Please fill in all required fields');
        return false;
    }
    
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        showToast('Please enter a valid email address');
        return false;
    }
    
    return true;
}

function prepareOrderData() {
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const address = document.getElementById('address').value;
    const city = document.getElementById('city').value;
    const postalCode = document.getElementById('postalCode').value;
    const notes = document.getElementById('notes').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    const subtotal = getCartSubtotal();
    const shipping = getShipping();
    const discountAmount = appliedCoupon ? (appliedCoupon === 'FREE50' ? 5 : subtotal * (discount / 100)) : 0;
    const total = getCartTotal() + shipping;
    const orderNumber = 'ORD-' + Date.now();
    
    return {
        order_number: orderNumber,
        first_name: firstName,
        last_name: lastName,
        email: email,
        phone: phone,
        address: address,
        city: city,
        postal_code: postalCode,
        notes: notes,
        subtotal: subtotal,
        shipping_cost: shipping,
        discount: discountAmount,
        total: total,
        payment_method: paymentMethod,
        payment_status: paymentMethod === 'mpesa' ? 'pending' : 'completed',
        items: cart.map(item => ({
            product_id: item.id,
            product_name: item.name,
            quantity: item.quantity,
            price: item.price,
            subtotal: item.price * item.quantity
        }))
    };
}

function placeOrder() {
    if (cart.length === 0) {
        showToast('Your cart is empty!');
        window.location.href = 'cart.php';
        return;
    }
    
    if (!validateForm()) {
        return;
    }
    
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    if (paymentMethod === 'mpesa') {
        // For M-Pesa, show payment modal first
        const mpesaNumber = document.getElementById('mpesaNumber').value;
        if (!mpesaNumber) {
            showToast('Please enter your M-Pesa phone number');
            return;
        }
        
        // Validate phone number format
        const phonePattern = /^07[0-9]{8}$|^01[0-9]{8}$/;
        if (!phonePattern.test(mpesaNumber)) {
            showToast('Please enter a valid Kenyan phone number (e.g., 0712345678)');
            return;
        }
        
        // Prepare order data
        pendingOrderData = prepareOrderData();
        pendingOrderData.mpesa_phone = mpesaNumber;
        
        // Show M-Pesa payment modal
        const modal = document.getElementById('mpesaModal');
        document.getElementById('modalAmount').innerText = '$' + pendingOrderData.total.toFixed(2);
        document.getElementById('modalPhone').innerHTML = `Phone: ${mpesaNumber}`;
        modal.style.display = 'flex';
        
        // Initiate STK Push
        initiateMpesaPayment();
    } else {
        // For other payment methods, process normally
        processOrder();
    }
}

function initiateMpesaPayment() {
    const placeOrderBtn = document.querySelector('.place-order-btn');
    placeOrderBtn.disabled = true;
    
    // Send STK Push request to backend
    fetch('mpesa_stk_push.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            phone: pendingOrderData.mpesa_phone,
            amount: pendingOrderData.total,
            order_number: pendingOrderData.order_number
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store checkout request ID to check payment status
            localStorage.setItem('pendingOrderNumber', pendingOrderData.order_number);
            localStorage.setItem('checkoutRequestID', data.CheckoutRequestID);
            
            // Start polling for payment status
            startPaymentStatusCheck(pendingOrderData.order_number);
        } else {
            showToast('Failed to initiate M-Pesa payment: ' + (data.message || 'Unknown error'));
            document.querySelector('.place-order-btn').disabled = false;
            closeMpesaModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error initiating M-Pesa payment. Please try again.');
        document.querySelector('.place-order-btn').disabled = false;
        closeMpesaModal();
    });
}

function startPaymentStatusCheck(orderNumber) {
    // Check payment status every 3 seconds for up to 2 minutes
    let attempts = 0;
    const maxAttempts = 40;
    
    if (orderCheckInterval) {
        clearInterval(orderCheckInterval);
    }
    
    orderCheckInterval = setInterval(() => {
        attempts++;
        
        fetch(`check_payment_status.php?order_number=${orderNumber}`)
            .then(response => response.json())
            .then(data => {
                if (data.payment_status === 'completed') {
                    // Payment successful
                    clearInterval(orderCheckInterval);
                    orderCheckInterval = null;
                    completeOrderAfterPayment(orderNumber);
                } else if (data.payment_status === 'failed' || attempts >= maxAttempts) {
                    // Payment failed or timed out
                    clearInterval(orderCheckInterval);
                    orderCheckInterval = null;
                    if (data.payment_status === 'failed') {
                        showToast('M-Pesa payment failed. Please try again.');
                    } else {
                        showToast('Payment verification timed out. Please check your order status.');
                    }
                    document.querySelector('.place-order-btn').disabled = false;
                    closeMpesaModal();
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
                if (attempts >= maxAttempts) {
                    clearInterval(orderCheckInterval);
                    orderCheckInterval = null;
                    document.querySelector('.place-order-btn').disabled = false;
                    closeMpesaModal();
                }
            });
    }, 3000);
}

function confirmMpesaPayment() {
    // User confirms they've completed payment on their phone
    // The payment status check is already running, so just show waiting message
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    confirmBtn.innerHTML = '<span class="loader"></span> Verifying...';
    confirmBtn.disabled = true;
    
    // Check status immediately
    fetch(`check_payment_status.php?order_number=${pendingOrderData.order_number}`)
        .then(response => response.json())
        .then(data => {
            if (data.payment_status === 'completed') {
                if (orderCheckInterval) {
                    clearInterval(orderCheckInterval);
                    orderCheckInterval = null;
                }
                completeOrderAfterPayment(pendingOrderData.order_number);
            } else {
                // Still waiting, keep checking
                showToast('Verifying payment... Please wait a moment');
                confirmBtn.innerHTML = 'I\'ve Completed Payment';
                confirmBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            confirmBtn.innerHTML = 'I\'ve Completed Payment';
            confirmBtn.disabled = false;
        });
}

function completeOrderAfterPayment(orderNumber) {
    // Save the order to database with completed status
    const orderData = { ...pendingOrderData };
    orderData.payment_status = 'completed';
    orderData.order_number = orderNumber;
    
    fetch('save_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Save order to localStorage for confirmation page
            localStorage.setItem('lastOrder', JSON.stringify({
                order_number: orderNumber,
                total: pendingOrderData.total,
                payment_method: 'mpesa',
                payment_status: 'completed',
                shipping: { 
                    address: pendingOrderData.address, 
                    city: pendingOrderData.city 
                }
            }));
            
            // Clear cart and related data
            localStorage.removeItem('flowerCart');
            localStorage.removeItem('appliedCoupon');
            localStorage.removeItem('discount');
            localStorage.removeItem('pendingOrderNumber');
            localStorage.removeItem('checkoutRequestID');
            
            closeMpesaModal();
            showToast('Payment successful! Order placed successfully.');
            
            setTimeout(() => {
                window.location.href = 'order-confirmation.php';
            }, 1500);
        } else {
            showToast('Error saving order: ' + data.message);
            document.querySelector('.place-order-btn').disabled = false;
            closeMpesaModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error completing order. Please contact support.');
        document.querySelector('.place-order-btn').disabled = false;
        closeMpesaModal();
    });
}

function processOrder() {
    const orderData = prepareOrderData();
    orderData.payment_status = 'completed';
    
    const placeOrderBtn = document.querySelector('.place-order-btn');
    const originalText = placeOrderBtn.innerHTML;
    placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    placeOrderBtn.disabled = true;
    
    fetch('save_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('lastOrder', JSON.stringify({
                order_number: orderData.order_number,
                total: orderData.total,
                payment_method: orderData.payment_method,
                shipping: { address: orderData.address, city: orderData.city }
            }));
            
            localStorage.removeItem('flowerCart');
            localStorage.removeItem('appliedCoupon');
            localStorage.removeItem('discount');
            
            showToast('Order placed successfully! Redirecting...');
            
            setTimeout(() => {
                window.location.href = 'order-confirmation.php';
            }, 1500);
        } else {
            showToast('Error: ' + data.message);
            placeOrderBtn.innerHTML = originalText;
            placeOrderBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error placing order. Please try again.');
        placeOrderBtn.innerHTML = originalText;
        placeOrderBtn.disabled = false;
    });
}

function closeMpesaModal() {
    const modal = document.getElementById('mpesaModal');
    modal.style.display = 'none';
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    confirmBtn.innerHTML = 'I\'ve Completed Payment';
    confirmBtn.disabled = false;
    
    if (orderCheckInterval) {
        clearInterval(orderCheckInterval);
        orderCheckInterval = null;
    }
}

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
    }, 3000);
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

renderOrderSummary();

function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        if (el) el.textContent = totalItems;
    });
}
updateCartCount();
</script>

<style>
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
</style>

<?php include 'footer.php'; ?>