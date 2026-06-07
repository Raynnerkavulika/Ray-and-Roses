<!-- footer.php -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-grid">
            <!-- About Section -->
            <div class="footer-section">
                <h3 class="footer-title">
                    <i class="fas fa-seedling"></i> Ray & Roses
                </h3>
                <p class="footer-description">
                    Bringing nature's beauty to your doorstep since 2020. 
                    Fresh, handcrafted flower arrangements delivered with love.
                </p>
                <div class="footer-social">
                    <a href="https://www.facebook.com" target="_blank" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com" target="_blank" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.twitter.com" target="_blank" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.pinterest.com" target="_blank" class="social-icon"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>

            <!-- Quick Links Section -->
            <div class="footer-section">
                <h4 class="footer-heading">
                    <i class="fas fa-link"></i> Quick Links
                </h4>
                <ul class="footer-links">
                    <li><a href="shop.php"><i class="fas fa-chevron-right"></i> Shop Now</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php"><i class="fas fa-chevron-right"></i> My Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    <li><a href="faq.php"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                </ul>
            </div>

            <!-- Customer Service Section -->
            <div class="footer-section">
                <h4 class="footer-heading">
                    <i class="fas fa-headset"></i> Customer Service
                </h4>
                <ul class="footer-links">
                    <li><a href="delivery-info.php"><i class="fas fa-chevron-right"></i> Delivery Info</a></li>
                    <li><a href="returns.php"><i class="fas fa-chevron-right"></i> Returns & Exchanges</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="track-order.php"><i class="fas fa-chevron-right"></i> Track Order</a></li>
                    <?php endif; ?>
                    <li><a href="gift-cards.php"><i class="fas fa-chevron-right"></i> Gift Cards</a></li>
                    <li><a href="terms.php"><i class="fas fa-chevron-right"></i> Terms of Service</a></li>
                </ul>
            </div>

            <!-- Contact Info Section -->
            <div class="footer-section">
                <h4 class="footer-heading">
                    <i class="fas fa-address-card"></i> Contact Info
                </h4>
                <div class="footer-contact">
                    <p><i class="fas fa-map-marker-alt"></i> 123 Flower Street, Nairobi, Kenya</p>
                    <p><i class="fas fa-phone"></i> <a href="tel:+254712345678">+254 712 345 678</a></p>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:info@rayandroses.com">info@rayandroses.com</a></p>
                    <p><i class="fas fa-clock"></i> Mon-Fri: 9AM - 6PM</p>
                    <p><i class="fas fa-clock"></i> Sat: 10AM - 4PM</p>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> Ray & Roses. All rights reserved. | Designed with <i class="fas fa-heart" style="color: #c45c4a;"></i> for flower lovers</p>
                </div>
                <div class="payment-methods">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-amex"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-apple-pay"></i>
                    <i class="fab fa-google-pay"></i>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Footer Styles */
    .footer {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: #d4bca8;
        padding: 3rem 0 0;
        margin-top: 3rem;
        position: relative;
    }

    .footer::before {
        content: '🌸';
        position: absolute;
        top: 20px;
        left: 20px;
        font-size: 60px;
        opacity: 0.05;
        pointer-events: none;
    }

    .footer::after {
        content: '🌺';
        position: absolute;
        bottom: 20px;
        right: 20px;
        font-size: 60px;
        opacity: 0.05;
        pointer-events: none;
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 5%;
    }

    /* Footer Grid */
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    /* Footer Sections */
    .footer-section {
        animation: fadeInUp 0.6s ease;
    }

    .footer-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #fff, #c45c4a);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        display: inline-block;
    }

    .footer-description {
        line-height: 1.6;
        margin-bottom: 1.2rem;
        font-size: 0.9rem;
        color: #d4bca8;
    }

    .footer-heading {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #fff;
        position: relative;
        padding-bottom: 0.5rem;
        display: inline-block;
    }

    .footer-heading::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background: linear-gradient(90deg, #c45c4a, #e8876e);
    }

    /* Footer Links */
    .footer-links {
        list-style: none;
    }

    .footer-links li {
        margin-bottom: 0.6rem;
    }

    .footer-links a {
        color: #d4bca8;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .footer-links a i {
        font-size: 0.7rem;
        transition: transform 0.3s ease;
    }

    .footer-links a:hover {
        color: #c45c4a;
        transform: translateX(5px);
    }

    .footer-links a:hover i {
        transform: translateX(3px);
    }

    /* Social Icons */
    .footer-social {
        display: flex;
        gap: 0.8rem;
        margin-top: 1rem;
    }

    .social-icon {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d4bca8;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .social-icon:hover {
        background: #c45c4a;
        color: white;
        transform: translateY(-3px);
    }

    /* Contact Info */
    .footer-contact p {
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-size: 0.9rem;
        color: #d4bca8;
    }

    .footer-contact p i {
        width: 20px;
        color: #c45c4a;
    }

    .footer-contact a {
        color: #d4bca8;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-contact a:hover {
        color: #c45c4a;
    }

    /* Footer Bottom */
    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1.5rem 0;
    }

    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .copyright {
        font-size: 0.85rem;
        color: #d4bca8;
    }

    .payment-methods {
        display: flex;
        gap: 1rem;
        font-size: 1.5rem;
    }

    .payment-methods i {
        color: #d4bca8;
        transition: color 0.3s;
        cursor: pointer;
    }

    .payment-methods i:hover {
        color: #c45c4a;
    }

    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 968px) {
        .footer-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .footer-grid {
            grid-template-columns: 1fr;
        }
        
        .footer-bottom-content {
            flex-direction: column;
            text-align: center;
        }
        
        .payment-methods {
            justify-content: center;
        }
        
        .footer {
            padding: 2rem 0 0;
        }
    }

    /* Back to Top Button */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        z-index: 1000;
    }

    .back-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .back-to-top:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(196, 92, 74, 0.4);
    }
</style>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</a>

<script>
    // Back to top button functionality
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });
        
        backToTop.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Add current year to copyright if needed
    document.querySelectorAll('.copyright').forEach(el => {
        if (el.innerHTML.includes('<?php echo date('Y'); ?>')) {
            // Year is already set by PHP
        }
    });
</script>