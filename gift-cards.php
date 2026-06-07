<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include header
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gift Cards - Ray & Roses</title>
    <style>
        .gift-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 5%;
        }
        
        .gift-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .gift-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2d2a24;
            margin-bottom: 1rem;
        }
        
        .gift-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .gift-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .gift-card {
            background: linear-gradient(135deg, #fff 0%, #fef6ef 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            position: relative;
        }
        
        .gift-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(196,92,74,0.2);
        }
        
        .gift-card.selected {
            border: 3px solid #c45c4a;
            box-shadow: 0 20px 40px rgba(196,92,74,0.3);
        }
        
        .gift-card-header {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .gift-card-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .gift-card-header h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .gift-card-price {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .gift-card-price small {
            font-size: 0.9rem;
        }
        
        .gift-card-body {
            padding: 1.5rem;
            text-align: center;
        }
        
        .gift-card-body p {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .gift-card-body .validity {
            font-size: 0.85rem;
            color: #c45c4a;
            margin-top: 0.5rem;
        }
        
        .select-card-btn {
            background: #c45c4a;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            width: 100%;
        }
        
        .select-card-btn:hover {
            background: #a84a3a;
        }
        
        /* Custom Gift Card Form */
        .custom-gift-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }
        
        .custom-gift-section h2 {
            color: #c45c4a;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2d2a24;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e0d4c8;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
        }
        
        .amount-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .amount-input-group input {
            flex: 1;
        }
        
        .amount-input-group span {
            font-weight: bold;
            color: #2d2a24;
        }
        
        .delivery-options {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .delivery-option {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e0d4c8;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: 0.3s;
        }
        
        .delivery-option:hover {
            border-color: #c45c4a;
        }
        
        .delivery-option.selected {
            border-color: #c45c4a;
            background: #fef6ef;
        }
        
        .delivery-option i {
            font-size: 1.5rem;
            color: #c45c4a;
            margin-bottom: 0.5rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(196,92,74,0.3);
        }
        
        .info-box {
            background: #fef6ef;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
        }
        
        .info-box h3 {
            color: #c45c4a;
            margin-bottom: 1rem;
        }
        
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-box li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .info-box li:before {
            content: "🎁";
            position: absolute;
            left: 0;
        }
        
        @media (max-width: 768px) {
            .gift-header h1 {
                font-size: 1.8rem;
            }
            
            .gift-cards-grid {
                grid-template-columns: 1fr;
            }
            
            .delivery-options {
                flex-direction: column;
            }
        }
        
        /* Success Message */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .success-message.show {
            display: block;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<div class="gift-container">
    <div class="gift-header">
        <h1>🎁 Gift Cards</h1>
        <p>Give the gift of beautiful flowers. Perfect for any occasion!</p>
    </div>
    
    <div id="successMessage" class="success-message">
        <i class="fas fa-check-circle"></i> Gift card added to cart successfully!
    </div>
    
    <!-- Pre-defined Gift Cards -->
    <div class="gift-cards-grid">
        <?php
        $giftCards = [
            ['amount' => 500, 'price' => 'KES 500', 'savings' => '', 'validity' => 'Valid for 6 months'],
            ['amount' => 1000, 'price' => 'KES 1,000', 'savings' => 'Save KES 0', 'validity' => 'Valid for 6 months'],
            ['amount' => 2000, 'price' => 'KES 2,000', 'savings' => 'Free delivery on next order', 'validity' => 'Valid for 12 months'],
            ['amount' => 5000, 'price' => 'KES 5,000', 'savings' => 'Bonus KES 500 free', 'validity' => 'Valid for 12 months'],
            ['amount' => 10000, 'price' => 'KES 10,000', 'savings' => 'Bonus KES 1,000 free', 'validity' => 'Valid for 18 months']
        ];
        
        foreach($giftCards as $card):
        ?>
        <div class="gift-card" data-amount="<?php echo $card['amount']; ?>">
            <div class="gift-card-header">
                <i class="fas fa-gift"></i>
                <h3>Ray & Roses</h3>
                <div class="gift-card-price">
                    <?php echo $card['price']; ?>
                </div>
            </div>
            <div class="gift-card-body">
                <?php if($card['savings']): ?>
                    <p>🎉 <?php echo $card['savings']; ?></p>
                <?php endif; ?>
                <p>Perfect for birthdays, anniversaries, or just because!</p>
                <div class="validity">
                    <i class="fas fa-calendar-alt"></i> <?php echo $card['validity']; ?>
                </div>
                <button class="select-card-btn" onclick="selectGiftCard(<?php echo $card['amount']; ?>)">
                    Select This Card
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Custom Gift Card -->
    <div class="custom-gift-section">
        <h2>✨ Create Custom Gift Card</h2>
        <form id="giftCardForm">
            <div class="form-group">
                <label>Select Amount (KES):</label>
                <div class="amount-input-group">
                    <input type="number" id="customAmount" name="amount" min="100" max="50000" step="100" required>
                    <span>KES</span>
                </div>
            </div>
            
            <div class="form-group">
                <label>Recipient's Name:</label>
                <input type="text" id="recipientName" name="recipient_name" placeholder="Enter recipient's name" required>
            </div>
            
            <div class="form-group">
                <label>Recipient's Email:</label>
                <input type="email" id="recipientEmail" name="recipient_email" placeholder="recipient@example.com">
                <small style="color: #666;">We'll email the gift card directly to them</small>
            </div>
            
            <div class="form-group">
                <label>Your Name (Sender):</label>
                <input type="text" id="senderName" name="sender_name" placeholder="Your name" required>
            </div>
            
            <div class="form-group">
                <label>Personal Message:</label>
                <textarea id="message" name="message" rows="3" placeholder="Write a heartfelt message..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Delivery Method:</label>
                <div class="delivery-options">
                    <div class="delivery-option" data-method="email">
                        <i class="fas fa-envelope"></i>
                        <p>Email Delivery</p>
                        <small>Instant delivery to email</small>
                    </div>
                    <div class="delivery-option" data-method="physical">
                        <i class="fas fa-truck"></i>
                        <p>Physical Card</p>
                        <small>Delivered with a fresh flower bouquet <br> (+KES 200 delivery fee)</small>
                    </div>
                </div>
                <input type="hidden" id="deliveryMethod" name="delivery_method" value="email">
            </div>
            
            <div class="form-group">
                <label>Delivery Date (for physical cards):</label>
                <input type="date" id="deliveryDate" name="delivery_date">
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-shopping-cart"></i> Add to Cart
            </button>
        </form>
    </div>
    
    <div class="info-box">
        <h3>💡 Gift Card Information</h3>
        <ul>
            <li>Gift cards are delivered instantly via email (or physically with a flower bouquet)</li>
            <li>No expiration date on gift cards (check individual cards for validity)</li>
            <li>Can be used both online and in-store</li>
            <li>Any remaining balance stays on the card for future use</li>
            <li>Gift cards are non-refundable but can be transferred</li>
            <li>For corporate or bulk orders, contact us at <strong>info@rayandroses.com</strong></li>
        </ul>
    </div>
</div>

<script>
    // Gift card selection
    function selectGiftCard(amount) {
        document.getElementById('customAmount').value = amount;
        document.getElementById('customGiftSection').scrollIntoView({ behavior: 'smooth' });
        
        // Highlight the selected card
        document.querySelectorAll('.gift-card').forEach(card => {
            card.classList.remove('selected');
        });
        event.currentTarget.closest('.gift-card').classList.add('selected');
    }
    
    // Delivery method selection
    const deliveryOptions = document.querySelectorAll('.delivery-option');
    const deliveryMethodInput = document.getElementById('deliveryMethod');
    const deliveryDateInput = document.getElementById('deliveryDate');
    
    deliveryOptions.forEach(option => {
        option.addEventListener('click', function() {
            deliveryOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            const method = this.getAttribute('data-method');
            deliveryMethodInput.value = method;
            
            // Show/hide delivery date based on method
            if (method === 'physical') {
                deliveryDateInput.parentElement.style.display = 'block';
                deliveryDateInput.required = true;
            } else {
                deliveryDateInput.parentElement.style.display = 'none';
                deliveryDateInput.required = false;
            }
        });
    });
    
    // Set default delivery date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('deliveryDate').value = tomorrow.toISOString().split('T')[0];
    
    // Hide delivery date initially (email is default)
    deliveryDateInput.parentElement.style.display = 'none';
    
    // Form submission
    document.getElementById('giftCardForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const amount = document.getElementById('customAmount').value;
        const recipientName = document.getElementById('recipientName').value;
        const senderName = document.getElementById('senderName').value;
        const message = document.getElementById('message').value;
        const deliveryMethod = document.getElementById('deliveryMethod').value;
        
        if(!amount || !recipientName || !senderName) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Create gift card object
        const giftCard = {
            type: 'gift_card',
            amount: parseInt(amount),
            recipient_name: recipientName,
            sender_name: senderName,
            message: message,
            delivery_method: deliveryMethod,
            quantity: 1
        };
        
        // Get existing cart
        let cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
        
        // Check if gift card already in cart
        const existingIndex = cart.findIndex(item => 
            item.type === 'gift_card' && 
            item.amount === giftCard.amount && 
            item.recipient_name === giftCard.recipient_name
        );
        
        if(existingIndex > -1) {
            cart[existingIndex].quantity += 1;
        } else {
            cart.push(giftCard);
        }
        
        // Save to localStorage
        localStorage.setItem('flowerCart', JSON.stringify(cart));
        
        // Show success message
        const successMsg = document.getElementById('successMessage');
        successMsg.classList.add('show');
        
        // Update cart count
        if(typeof updateCartCountGlobal === 'function') {
            updateCartCountGlobal();
        }
        
        // Reset form
        this.reset();
        document.getElementById('deliveryMethod').value = 'email';
        deliveryDateInput.parentElement.style.display = 'none';
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Hide success message after 3 seconds
        setTimeout(() => {
            successMsg.classList.remove('show');
        }, 3000);
        
        // Ask if user wants to checkout
        setTimeout(() => {
            if(confirm('Gift card added to cart! Would you like to proceed to checkout?')) {
                window.location.href = 'cart.php';
            }
        }, 500);
    });
    
    // Add click handlers to gift cards
    document.querySelectorAll('.gift-card').forEach((card, index) => {
        card.addEventListener('click', function(e) {
            if(!e.target.classList.contains('select-card-btn')) {
                const amount = this.getAttribute('data-amount');
                document.getElementById('customAmount').value = amount;
                document.querySelector('.custom-gift-section').scrollIntoView({ behavior: 'smooth' });
                
                document.querySelectorAll('.gift-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
            }
        });
    });
</script>

<?php include 'footer.php'; ?>

</body>
</html>