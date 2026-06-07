<?php
session_start();
require_once 'config/database.php';

// Include header
include 'header.php';
?>

<style>
    .terms-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    /* Hero Section */
    .terms-hero {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 3rem 5%;
        text-align: center;
        border-radius: 20px;
        margin-bottom: 3rem;
        position: relative;
        overflow: hidden;
    }

    .terms-hero::before {
        content: '⚖️📜🌺🌹';
        position: absolute;
        font-size: 100px;
        opacity: 0.1;
        right: -20px;
        bottom: -20px;
        letter-spacing: 15px;
    }

    .terms-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .terms-hero p {
        font-size: 1rem;
        opacity: 0.95;
    }

    .last-updated {
        text-align: center;
        color: #7b6b5c;
        font-size: 0.85rem;
        margin-bottom: 2rem;
    }

    /* Terms Content */
    .terms-content {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .terms-section {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f0e0d4;
    }

    .terms-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .terms-section h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: #3d2a1f;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .terms-section h2 i {
        color: #c45c4a;
        font-size: 1.3rem;
    }

    .terms-section h3 {
        font-size: 1.1rem;
        color: #3d2a1f;
        margin: 1rem 0 0.5rem;
    }

    .terms-section p {
        color: #5a3f2c;
        line-height: 1.6;
        margin-bottom: 0.8rem;
    }

    .terms-section ul, 
    .terms-section ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }

    .terms-section li {
        color: #5a3f2c;
        line-height: 1.6;
        margin-bottom: 0.3rem;
    }

    .highlight {
        background: #fef6ef;
        padding: 1rem;
        border-radius: 10px;
        border-left: 3px solid #c45c4a;
        margin: 1rem 0;
    }

    .table-of-contents {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
    }

    .table-of-contents h3 {
        margin-top: 0;
        margin-bottom: 1rem;
        color: #3d2a1f;
    }

    .table-of-contents ul {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.5rem;
        list-style: none;
        margin-left: 0;
    }

    .table-of-contents li a {
        color: #c45c4a;
        text-decoration: none;
        transition: 0.3s;
        display: inline-block;
        padding: 0.3rem 0;
    }

    .table-of-contents li a:hover {
        color: #a84a3a;
        transform: translateX(5px);
    }

    .accept-terms {
        text-align: center;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #f0e0d4;
    }

    .accept-terms p {
        margin-bottom: 1rem;
    }

    .btn-accept {
        background: #c45c4a;
        color: white;
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-accept:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .terms-content {
            padding: 1.5rem;
        }
        
        .terms-hero h1 {
            font-size: 1.8rem;
        }
        
        .terms-section h2 {
            font-size: 1.3rem;
        }
        
        .table-of-contents ul {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="terms-container">
    <!-- Hero Section -->
    <div class="terms-hero">
        <h1><i class="fas fa-file-contract"></i> Terms of Service</h1>
        <p>Please read these terms carefully before using our services</p>
    </div>

    <div class="last-updated">
        <i class="fas fa-calendar-alt"></i> Last Updated: <?php echo date('F d, Y'); ?>
    </div>

    <div class="terms-content">
        <!-- Table of Contents -->
        <div class="table-of-contents">
            <h3><i class="fas fa-list"></i> Table of Contents</h3>
            <ul>
                <li><a href="#acceptance">1. Acceptance of Terms</a></li>
                <li><a href="#accounts">2. Account Registration</a></li>
                <li><a href="#orders">3. Ordering and Payments</a></li>
                <li><a href="#delivery">4. Delivery Policy</a></li>
                <li><a href="#returns">5. Returns and Refunds</a></li>
                <li><a href="#products">6. Product Information</a></li>
                <li><a href="#intellectual">7. Intellectual Property</a></li>
                <li><a href="#privacy">8. Privacy Policy</a></li>
                <li><a href="#limitations">9. Limitations of Liability</a></li>
                <li><a href="#termination">10. Termination</a></li>
                <li><a href="#governing">11. Governing Law</a></li>
                <li><a href="#changes">12. Changes to Terms</a></li>
                <li><a href="#contact">13. Contact Information</a></li>
            </ul>
        </div>

        <!-- Section 1 -->
        <div id="acceptance" class="terms-section">
            <h2><i class="fas fa-check-circle"></i> 1. Acceptance of Terms</h2>
            <p>By accessing and using Ray & Roses website, you agree to be bound by these Terms of Service, all applicable laws, and regulations. If you do not agree with any part of these terms, you may not use our services.</p>
            <div class="highlight">
                <i class="fas fa-info-circle"></i> <strong>Important:</strong> By placing an order with Ray & Roses, you confirm that you are at least 18 years old and have the legal capacity to enter into binding contracts.
            </div>
        </div>

        <!-- Section 2 -->
        <div id="accounts" class="terms-section">
            <h2><i class="fas fa-user-circle"></i> 2. Account Registration</h2>
            <p>To access certain features of our website, you may be required to create an account. You agree to:</p>
            <ul>
                <li>Provide accurate, current, and complete information during registration</li>
                <li>Maintain the security of your password and accept responsibility for all activities under your account</li>
                <li>Notify us immediately of any unauthorized use of your account</li>
                <li>Be responsible for all charges and activities that occur under your account</li>
            </ul>
            <p>We reserve the right to refuse service, terminate accounts, or remove content at our sole discretion.</p>
        </div>

        <!-- Section 3 -->
        <div id="orders" class="terms-section">
            <h2><i class="fas fa-shopping-cart"></i> 3. Ordering and Payments</h2>
            <p>When you place an order with Ray & Roses, you agree to the following terms:</p>
            <ul>
                <li>All orders are subject to acceptance and availability</li>
                <li>Prices are subject to change without notice, but changes will not affect orders already placed</li>
                <li>We accept various payment methods including Credit/Debit Cards, M-Pesa, PayPal, and Cash on Delivery</li>
                <li>Payment must be received in full before order processing (except for Cash on Delivery)</li>
                <li>We reserve the right to refuse or cancel any order for any reason</li>
                <li>In case of pricing errors, we will notify you and give you the option to proceed or cancel</li>
            </ul>
            <div class="highlight">
                <i class="fas fa-credit-card"></i> <strong>Payment Security:</strong> All transactions are encrypted using SSL technology. Your payment information is never stored on our servers.
            </div>
        </div>

        <!-- Section 4 -->
        <div id="delivery" class="terms-section">
            <h2><i class="fas fa-truck"></i> 4. Delivery Policy</h2>
            <p>Our delivery terms are as follows:</p>
            <ul>
                <li>Delivery times are estimates and not guaranteed</li>
                <li>Same-day delivery is available for orders placed before 2 PM</li>
                <li>We are not responsible for delays caused by weather, traffic, or other unforeseen circumstances</li>
                <li>A valid phone number and address must be provided for delivery</li>
                <li>If recipient is not available, we will attempt to leave the flowers with a neighbor or leave a delivery notice</li>
                <li>Delivery fees are non-refundable once the order has been dispatched</li>
                <li>Free delivery applies to orders over $50 within Nairobi only</li>
            </ul>
        </div>

        <!-- Section 5 -->
        <div id="returns" class="terms-section">
            <h2><i class="fas fa-undo-alt"></i> 5. Returns and Refunds</h2>
            <p>Due to the perishable nature of flowers, our return policy is limited:</p>
            <ul>
                <li>Fresh flowers cannot be returned due to their perishable nature</li>
                <li>If you are unsatisfied with your order, contact us within 24 hours of delivery</li>
                <li>Provide photos of the product for our review</li>
                <li>Refunds or replacements will be issued at our discretion</li>
                <li>Non-flower items (vases, gift items) can be returned within 14 days in original condition</li>
                <li>Shipping costs for returns are the customer's responsibility unless the item is defective</li>
            </ul>
            <div class="highlight">
                <i class="fas fa-gift"></i> <strong>Gift Orders:</strong> Gift recipients are also eligible for refunds or replacements under the same terms.
            </div>
        </div>

        <!-- Section 6 -->
        <div id="products" class="terms-section">
            <h2><i class="fas fa-seedling"></i> 6. Product Information</h2>
            <p>We strive to provide accurate product descriptions and images. However, please note:</p>
            <ul>
                <li>Colors may vary slightly from images due to monitor settings and natural flower variations</li>
                <li>Flowers are natural products and may differ in size, shape, and color from displayed images</li>
                <li>We reserve the right to substitute flowers of equal or greater value if specific flowers are unavailable</li>
                <li>Product availability is subject to seasonal changes</li>
                <li>All prices are in Kenyan Shillings (KES) unless otherwise specified</li>
            </ul>
        </div>

        <!-- Section 7 -->
        <div id="intellectual" class="terms-section">
            <h2><i class="fas fa-copyright"></i> 7. Intellectual Property</h2>
            <p>All content on this website, including but not limited to text, graphics, logos, images, and software, is the property of Ray & Roses and is protected by copyright laws.</p>
            <p>You may not:</p>
            <ul>
                <li>Reproduce, distribute, or modify any content without written permission</li>
                <li>Use our trademarks or logos without authorization</li>
                <li>Frame or mirror any part of our website</li>
                <li>Use any automated system to extract data from our website</li>
            </ul>
        </div>

        <!-- Section 8 -->
        <div id="privacy" class="terms-section">
            <h2><i class="fas fa-shield-alt"></i> 8. Privacy Policy</h2>
            <p>Your privacy is important to us. We collect and use your information as follows:</p>
            <ul>
                <li>We collect personal information necessary to process your orders</li>
                <li>We do not sell or share your personal information with third parties for marketing purposes</li>
                <li>We use SSL encryption to protect your data during transmission</li>
                <li>You may opt out of marketing communications at any time</li>
                <li>We retain your information as long as your account is active or as needed to provide services</li>
            </ul>
            <p>For more details, please review our full <a href="privacy.php" style="color: #c45c4a;">Privacy Policy</a>.</p>
        </div>

        <!-- Section 9 -->
        <div id="limitations" class="terms-section">
            <h2><i class="fas fa-exclamation-triangle"></i> 9. Limitations of Liability</h2>
            <p>To the maximum extent permitted by law, Ray & Roses shall not be liable for:</p>
            <ul>
                <li>Any indirect, incidental, or consequential damages</li>
                <li>Loss of profits, data, or business opportunities</li>
                <li>Damages arising from inability to use our services</li>
                <li>Issues caused by events beyond our reasonable control (force majeure)</li>
            </ul>
            <p>Our total liability shall not exceed the amount paid for the specific order in question.</p>
        </div>

        <!-- Section 10 -->
        <div id="termination" class="terms-section">
            <h2><i class="fas fa-ban"></i> 10. Termination</h2>
            <p>We reserve the right to terminate or suspend your account immediately, without prior notice, for conduct that we believe violates these Terms of Service or is harmful to other users or our business.</p>
            <p>Upon termination:</p>
            <ul>
                <li>Your right to use our services will immediately cease</li>
                <li>Any pending orders may be cancelled</li>
                <li>You will remain liable for all amounts due</li>
            </ul>
        </div>

        <!-- Section 11 -->
        <div id="governing" class="terms-section">
            <h2><i class="fas fa-gavel"></i> 11. Governing Law</h2>
            <p>These Terms of Service shall be governed by and construed in accordance with the laws of the Republic of Kenya. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts of Nairobi, Kenya.</p>
        </div>

        <!-- Section 12 -->
        <div id="changes" class="terms-section">
            <h2><i class="fas fa-edit"></i> 12. Changes to Terms</h2>
            <p>We reserve the right to modify these Terms of Service at any time. Changes become effective immediately upon posting to our website. Your continued use of our services after changes constitutes acceptance of the modified terms.</p>
            <p>We will notify users of significant changes via email or website notification.</p>
        </div>

        <!-- Section 13 -->
        <div id="contact" class="terms-section">
            <h2><i class="fas fa-envelope"></i> 13. Contact Information</h2>
            <p>If you have any questions about these Terms of Service, please contact us:</p>
            <ul>
                <li><strong>Email:</strong> <a href="mailto:legal@rayandroses.com" style="color: #c45c4a;">legal@rayandroses.com</a></li>
                <li><strong>Phone:</strong> <a href="tel:+254712345678" style="color: #c45c4a;">+254 712 345 678</a></li>
                <li><strong>Address:</strong> 123 Flower Street, Nairobi, Kenya</li>
                <li><strong>Business Hours:</strong> Monday - Friday, 9 AM - 6 PM</li>
            </ul>
        </div>

        <!-- Acceptance Section -->
        <div class="accept-terms">
            <p><i class="fas fa-heart" style="color: #c45c4a;"></i> By using Ray & Roses, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</p>
            <button class="btn-accept" onclick="acceptTerms()">
                <i class="fas fa-check-circle"></i> I Accept the Terms
            </button>
        </div>
    </div>
</div>

<script>
function acceptTerms() {
    // You can store acceptance in localStorage or database
    localStorage.setItem('termsAccepted', 'true');
    localStorage.setItem('termsAcceptedDate', new Date().toISOString());
    
    // Show confirmation
    alert('Thank you for accepting our Terms of Service. You can now continue shopping.');
    
    // Redirect to home or shop page
    window.location.href = 'shop.php';
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php include 'footer.php'; ?>