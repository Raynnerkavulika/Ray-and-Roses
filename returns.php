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
    <title>Returns & Exchanges - Ray & Roses</title>
    <style>
        .returns-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 5%;
        }
        
        .returns-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .returns-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2d2a24;
            margin-bottom: 1rem;
        }
        
        .returns-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        /* Policy Cards */
        .policy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .policy-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .policy-card:hover {
            transform: translateY(-5px);
        }
        
        .policy-card i {
            font-size: 2.5rem;
            color: #c45c4a;
            margin-bottom: 1rem;
        }
        
        .policy-card h3 {
            color: #2d2a24;
            margin-bottom: 0.5rem;
        }
        
        .policy-card .timeframe {
            display: inline-block;
            background: #c45c4a;
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            margin-top: 1rem;
        }
        
        /* Policy Sections */
        .policy-section {
            background: #fef6ef;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .policy-section h2 {
            color: #c45c4a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .policy-section h3 {
            color: #2d2a24;
            margin: 1rem 0 0.5rem 0;
        }
        
        .policy-section p {
            line-height: 1.6;
            color: #555;
            margin-bottom: 1rem;
        }
        
        .policy-section ul, .policy-section ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .policy-section li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
            color: #555;
        }
        
        /* Steps */
        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .step {
            text-align: center;
            position: relative;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: #c45c4a;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin: 0 auto 1rem;
        }
        
        .step h4 {
            margin-bottom: 0.5rem;
            color: #2d2a24;
        }
        
        .step p {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Table */
        .return-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        
        .return-table th,
        .return-table td {
            border: 1px solid #e0d4c8;
            padding: 0.8rem;
            text-align: left;
        }
        
        .return-table th {
            background: #c45c4a;
            color: white;
        }
        
        .return-table tr:nth-child(even) {
            background: #f9f3ef;
        }
        
        /* Form */
        .return-form {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .return-form h2 {
            color: #c45c4a;
            margin-bottom: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
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
        
        .btn-submit {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(196,92,74,0.3);
        }
        
        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        
        /* FAQ */
        .faq-item {
            margin-bottom: 1rem;
            border-bottom: 1px solid #e0d4c8;
        }
        
        .faq-question {
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            padding: 1rem;
            font-weight: 600;
            color: #2d2a24;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-question:hover {
            color: #c45c4a;
        }
        
        .faq-answer {
            padding: 0 1rem 1rem 1rem;
            display: none;
            color: #666;
            line-height: 1.6;
        }
        
        .faq-answer.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .returns-header h1 {
                font-size: 1.8rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .return-table {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<div class="returns-container">
    <div class="returns-header">
        <h1>🔄 Returns & Exchanges</h1>
        <p>Your satisfaction is our priority. Learn about our return and exchange policies.</p>
    </div>
    
    <!-- Policy Cards -->
    <div class="policy-grid">
        <div class="policy-card">
            <i class="fas fa-calendar-alt"></i>
            <h3>Return Window</h3>
            <p>Fresh flowers within 24 hours</p>
            <p>Other products within 7 days</p>
            <span class="timeframe">24 Hours for Flowers</span>
        </div>
        
        <div class="policy-card">
            <i class="fas fa-gift"></i>
            <h3>Exchange Policy</h3>
            <p>Free exchange within 7 days</p>
            <p>Different arrangement or product</p>
            <span class="timeframe">No Restocking Fee</span>
        </div>
        
        <div class="policy-card">
            <i class="fas fa-credit-card"></i>
            <h3>Refund Method</h3>
            <p>Original payment method</p>
            <p>Store credit available</p>
            <span class="timeframe">5-7 Business Days</span>
        </div>
    </div>
    
    <!-- Return Conditions -->
    <div class="policy-section">
        <h2><i class="fas fa-clipboard-list"></i> Return Conditions</h2>
        <p>To be eligible for a return or exchange, please ensure:</p>
        <ul>
            <li><strong>Fresh Flowers:</strong> Must be reported within 24 hours of delivery with photo evidence</li>
            <li><strong>Non-floral Items:</strong> (vases, gifts, etc.) Must be unused and in original packaging within 7 days</li>
            <li><strong>Proof of Purchase:</strong> Order number or receipt is required for all returns</li>
            <li><strong>Original Condition:</strong> Products must be in original condition unless damaged or defective</li>
            <li><strong>Perishable items:</strong> Chocolates, cakes, and other perishable items cannot be returned unless spoiled</li>
        </ul>
    </div>
    
    <!-- Non-Returnable Items -->
    <div class="policy-section">
        <h2><i class="fas fa-ban"></i> Non-Returnable Items</h2>
        <ul>
            <li><strong>Custom arrangements:</strong> Personalized or specially ordered bouquets</li>
            <li><strong>Gift cards:</strong> Digital and physical gift cards (non-refundable)</li>
            <li><strong>Perishable goods:</strong> Chocolates, cakes, fruits, and food items</li>
            <li><strong>Sale items:</strong> Discounted or clearance items (unless damaged)</li>
            <li><strong>Seasonal items:</strong> Valentine's Day, Mother's Day, or holiday-specific arrangements</li>
        </ul>
    </div>
    
    <!-- Return Process -->
    <div class="policy-section">
        <h2><i class="fas fa-arrow-right"></i> How to Return or Exchange</h2>
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <h4>Contact Us</h4>
                <p>Call or email within the return window</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h4>Provide Details</h4>
                <p>Share order number, photos, and issue</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h4>Get Approval</h4>
                <p>We'll review and approve your return</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <h4>Return or Exchange</h4>
                <p>We'll pick up or you can drop off</p>
            </div>
            <div class="step">
                <div class="step-number">5</div>
                <h4>Refund/Exchange</h4>
                <p>Processed within 5-7 business days</p>
            </div>
        </div>
    </div>
    
    <!-- Delivery Issues -->
    <div class="policy-section">
        <h2><i class="fas fa-truck"></i> Delivery Issues</h2>
        <p>If your order arrives with any of the following issues, please contact us immediately:</p>
        <ul>
            <li><strong>Damaged flowers:</strong> Wilted, broken, or crushed blooms</li>
            <li><strong>Wrong arrangement:</strong> Different flowers than ordered</li>
            <li><strong>Missing items:</strong> Missing vase, card, or add-ons</li>
            <li><strong>Late delivery:</strong> Beyond the promised delivery window</li>
            <li><strong>Delivery to wrong address:</strong> Sent to incorrect location</li>
        </ul>
        <p><strong>Note:</strong> For delivery issues, we will redeliver a fresh arrangement at no cost or provide a full refund.</p>
    </div>
    
    <!-- Refund Timeline Table -->
    <div class="policy-section">
        <h2><i class="fas fa-clock"></i> Refund Processing Timeline</h2>
        <table class="return-table">
            <thead>
                <tr>
                    <th>Refund Type</th>
                    <th>Processing Time</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Credit Card</td>
                    <td>5-7 business days</td>
                    <td>Depends on your bank</td>
                </tr>
                <tr>
                    <td>M-Pesa</td>
                    <td>24-48 hours</td>
                    <td>Same number used for payment</td>
                </tr>
                <tr>
                    <td>Bank Transfer</td>
                    <td>3-5 business days</td>
                    <td>Provide bank details</td>
                </tr>
                <tr>
                    <td>Store Credit</td>
                    <td>Instant</td>
                    <td>Can be used immediately</td>
                </tr>
                <tr>
                    <td>Exchange</td>
                    <td>1-3 business days</td>
                    <td>After we receive return</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Return Request Form -->
    <div class="return-form">
        <h2>📝 Request Return or Exchange</h2>
        <div id="formAlert"></div>
        <form id="returnRequestForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Order Number *</label>
                    <input type="text" id="orderNumber" placeholder="e.g., RR-12345" required>
                </div>
                <div class="form-group">
                    <label>Order Date *</label>
                    <input type="date" id="orderDate" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="fullName" required>
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" id="email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" id="phone" placeholder="+254 XXX XXX XXX" required>
                </div>
                <div class="form-group">
                    <label>Request Type *</label>
                    <select id="requestType" required>
                        <option value="">Select...</option>
                        <option value="return">Return (Refund)</option>
                        <option value="exchange">Exchange</option>
                        <option value="damaged">Damaged Product</option>
                        <option value="wrong">Wrong Item Received</option>
                        <option value="delivery">Delivery Issue</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Product(s) to Return/Exchange *</label>
                <input type="text" id="productName" placeholder="Product name(s)" required>
            </div>
            
            <div class="form-group">
                <label>Reason for Return *</label>
                <textarea id="reason" rows="3" placeholder="Please explain why you're returning the item(s)" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Upload Photo (if damaged/wrong item)</label>
                <input type="file" id="photo" accept="image/*">
                <small>Please upload clear photos of the product and packaging</small>
            </div>
            
            <div class="form-group">
                <label>Preferred Resolution *</label>
                <select id="resolution" required>
                    <option value="">Select...</option>
                    <option value="refund">Full Refund</option>
                    <option value="exchange">Exchange for same item</option>
                    <option value="exchange_diff">Exchange for different item</option>
                    <option value="store_credit">Store Credit (+10% bonus)</option>
                    <option value="redelivery">Redelivery (for flowers)</option>
                </select>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit Request
            </button>
        </form>
    </div>
    
    <!-- FAQ Section -->
    <div class="policy-section">
        <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
        
        <div class="faq-item">
            <button class="faq-question">
                Can I return flowers if the recipient doesn't like them?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                Unfortunately, we cannot accept returns for flowers based on personal taste. However, if the arrangement doesn't match what was ordered or has quality issues, we'll gladly replace it.
            </div>
        </div>
        
        <div class="faq-item">
            <button class="faq-question">
                Who pays for return shipping?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                For damaged, defective, or incorrect items, we cover all return shipping costs. For other returns, the customer is responsible for return shipping fees.
            </div>
        </div>
        
        <div class="faq-item">
            <button class="faq-question">
                How long do I have to return flowers?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                Fresh flowers must be reported within 24 hours of delivery due to their perishable nature. Please inspect your flowers immediately upon arrival.
            </div>
        </div>
        
        <div class="faq-item">
            <button class="faq-question">
                Can I exchange an arrangement for a different one?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                Yes! If you're not satisfied with your arrangement, you can exchange it for any other arrangement of equal or lesser value within 7 days. If choosing a more expensive arrangement, you'll pay the difference.
            </div>
        </div>
        
        <div class="faq-item">
            <button class="faq-question">
                What if my flowers wilted quickly?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                We guarantee our flowers for 5 days (with proper care). If your flowers wilt prematurely, please contact us within 48 hours with photos, and we'll arrange a replacement or refund.
            </div>
        </div>
        
        <div class="faq-item">
            <button class="faq-question">
                Can I return gift cards?
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                Gift cards are non-refundable, but they never expire and can be transferred to another person. The balance can also be used for any future purchase.
            </div>
        </div>
    </div>
    
    <!-- Contact Info -->
    <div class="policy-section">
        <h2><i class="fas fa-phone-alt"></i> Need Help?</h2>
        <p>Our customer service team is here to assist you with any return or exchange questions:</p>
        <ul>
            <li><strong>Phone:</strong> <a href="tel:+254712345678">+254 712 345 678</a> (Mon-Fri, 9AM-6PM)</li>
            <li><strong>Email:</strong> <a href="mailto:returns@rayandroses.com">returns@rayandroses.com</a></li>
            <li><strong>WhatsApp:</strong> <a href="https://wa.me/254712345678">+254 712 345 678</a></li>
            <li><strong>Visit Us:</strong> 123 Flower Street, Nairobi (Returns counter open Mon-Sat, 10AM-4PM)</li>
        </ul>
    </div>
</div>

<script>
    // Set default order date to today
    document.getElementById('orderDate').valueAsDate = new Date();
    
    // FAQ Toggle
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(button => {
        button.addEventListener('click', () => {
            const answer = button.nextElementSibling;
            const icon = button.querySelector('i');
            
            // Close other FAQs
            document.querySelectorAll('.faq-answer').forEach(ans => {
                if(ans !== answer) {
                    ans.classList.remove('show');
                }
            });
            document.querySelectorAll('.faq-question i').forEach(icn => {
                if(icn !== icon) {
                    icn.classList.remove('fa-chevron-up');
                    icn.classList.add('fa-chevron-down');
                }
            });
            
            // Toggle current FAQ
            answer.classList.toggle('show');
            if(answer.classList.contains('show')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });
    });
    
    // Form Submission
    document.getElementById('returnRequestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const orderNumber = document.getElementById('orderNumber').value;
        const fullName = document.getElementById('fullName').value;
        const email = document.getElementById('email').value;
        const requestType = document.getElementById('requestType').value;
        
        // Validate
        if(!orderNumber || !fullName || !email || !requestType) {
            showAlert('Please fill in all required fields', 'error');
            return;
        }
        
        // In a real application, you would send this data to your server
        // For now, we'll just show a success message
        
        // Simulate saving to localStorage or sending email
        const returnRequest = {
            order_number: orderNumber,
            full_name: fullName,
            email: email,
            phone: document.getElementById('phone').value,
            request_type: requestType,
            product_name: document.getElementById('productName').value,
            reason: document.getElementById('reason').value,
            resolution: document.getElementById('resolution').value,
            date_submitted: new Date().toISOString()
        };
        
        // Store in localStorage for demo
        let returns = JSON.parse(localStorage.getItem('returnRequests')) || [];
        returns.push(returnRequest);
        localStorage.setItem('returnRequests', JSON.stringify(returns));
        
        // Show success message
        showAlert(`Thank you ${fullName}! Your return request for order ${orderNumber} has been submitted. We'll contact you within 24 hours.`, 'success');
        
        // Reset form
        this.reset();
        document.getElementById('orderDate').valueAsDate = new Date();
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    function showAlert(message, type) {
        const alertDiv = document.getElementById('formAlert');
        alertDiv.innerHTML = `<div class="alert ${type}">${message}</div>`;
        
        setTimeout(() => {
            alertDiv.innerHTML = '';
        }, 5000);
    }
    
    // Photo preview (optional)
    document.getElementById('photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(file) {
            if(file.size > 5 * 1024 * 1024) {
                alert('Photo must be less than 5MB');
                this.value = '';
            }
        }
    });
</script>

<?php include 'footer.php'; ?>

</body>
</html>