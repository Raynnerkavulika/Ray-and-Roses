<?php
session_start();
require_once 'config/database.php';

// Handle contact form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } else {
        // In production, send email here
        // mail($to, $subject, $message, $headers);
        
        // For now, just show success message
        $success_message = "Thank you for contacting us! We'll get back to you within 24 hours.";
        
        // Clear form data
        $_POST = array();
    }
}

// Include header
include 'header.php';
?>

<style>
    .contact-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    /* Hero Section */
    .contact-hero {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 3rem 5%;
        text-align: center;
        border-radius: 20px;
        margin-bottom: 3rem;
        position: relative;
        overflow: hidden;
    }

    .contact-hero::before {
        content: '🌸🌺🌷🌹';
        position: absolute;
        font-size: 100px;
        opacity: 0.1;
        right: -20px;
        bottom: -20px;
        letter-spacing: 15px;
    }

    .contact-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .contact-hero p {
        font-size: 1rem;
        opacity: 0.95;
    }

    /* Contact Wrapper */
    .contact-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    /* Contact Info Section */
    .contact-info {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .info-section {
        margin-bottom: 2rem;
    }

    .info-section h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        color: #3d2a1f;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-details {
        list-style: none;
    }

    .info-details li {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 0.8rem;
        background: #fef6ef;
        border-radius: 12px;
        transition: transform 0.3s;
    }

    .info-details li:hover {
        transform: translateX(5px);
    }

    .info-details li i {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #c45c4a;
        font-size: 1.2rem;
    }

    .info-details li div {
        flex: 1;
    }

    .info-details li strong {
        display: block;
        color: #3d2a1f;
        margin-bottom: 0.2rem;
    }

    .info-details li span, 
    .info-details li a {
        color: #7b6b5c;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .info-details li a:hover {
        color: #c45c4a;
    }

    /* Business Hours */
    .business-hours {
        background: #fef6ef;
        border-radius: 15px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .hour-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0e0d4;
    }

    .hour-row:last-child {
        border-bottom: none;
    }

    .hour-day {
        font-weight: 600;
        color: #3d2a1f;
    }

    .hour-time {
        color: #7b6b5c;
    }

    /* Social Links */
    .social-links-section {
        margin-top: 1.5rem;
    }

    .social-links-section h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        color: #3d2a1f;
        margin-bottom: 1rem;
    }

    .social-grid {
        display: flex;
        gap: 1rem;
    }

    .social-link {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.8rem;
        background: #fef6ef;
        border-radius: 12px;
        text-decoration: none;
        color: #c45c4a;
        font-weight: 500;
        transition: 0.3s;
    }

    .social-link:hover {
        background: #c45c4a;
        color: white;
        transform: translateY(-3px);
    }

    /* Contact Form */
    .contact-form {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .contact-form h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .contact-form .subtitle {
        color: #7b6b5c;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #3d2a1f;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .form-group label .required {
        color: #f44336;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 2px solid #f0e0d4;
        border-radius: 12px;
        font-size: 0.9rem;
        font-family: 'Inter', sans-serif;
        transition: 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #c45c4a;
        box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .submit-btn {
        width: 100%;
        background: #c45c4a;
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .submit-btn:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    /* Map Section */
    .map-section {
        margin-top: 3rem;
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .map-container {
        height: 400px;
        width: 100%;
    }

    .map-container iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    /* Alerts */
    .alert-success {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        border-left: 4px solid #28a745;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-error {
        background: #fee;
        color: #f44336;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        border-left: 4px solid #f44336;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .contact-wrapper {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .contact-hero h1 {
            font-size: 1.8rem;
        }
        
        .social-grid {
            flex-wrap: wrap;
        }
        
        .map-container {
            height: 300px;
        }
    }
</style>

<div class="contact-container">
    <!-- Hero Section -->
    <div class="contact-hero">
        <h1><i class="fas fa-envelope-open-text"></i> Get in Touch</h1>
        <p>We'd love to hear from you! Reach out with any questions, feedback, or special requests.</p>
    </div>

    <?php if($success_message): ?>
    <div class="alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>

    <?php if($error_message): ?>
    <div class="alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <div class="contact-wrapper">
        <!-- Contact Info Section -->
        <div class="contact-info">
            <div class="info-section">
                <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                <ul class="info-details">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Visit Us</strong>
                            <span>123 Flower Street, Nairobi, Kenya</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <strong>Call Us</strong>
                            <a href="tel:+254712345678">+254 712 345 678</a>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email Us</strong>
                            <a href="mailto:info@rayandroses.com">info@rayandroses.com</a>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Quick Response</strong>
                            <span>We reply within 24 hours</span>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-clock"></i> Business Hours</h3>
                <div class="business-hours">
                    <div class="hour-row">
                        <span class="hour-day">Monday - Friday</span>
                        <span class="hour-time">9:00 AM - 6:00 PM</span>
                    </div>
                    <div class="hour-row">
                        <span class="hour-day">Saturday</span>
                        <span class="hour-time">10:00 AM - 4:00 PM</span>
                    </div>
                    <div class="hour-row">
                        <span class="hour-day">Sunday</span>
                        <span class="hour-time">Closed</span>
                    </div>
                    <div class="hour-row">
                        <span class="hour-day">Public Holidays</span>
                        <span class="hour-time">Closed</span>
                    </div>
                </div>
            </div>

            <div class="social-links-section">
                <h3><i class="fas fa-share-alt"></i> Connect With Us</h3>
                <div class="social-grid">
                    <a href="https://www.facebook.com" target="_blank" class="social-link">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="https://www.instagram.com" target="_blank" class="social-link">
                        <i class="fab fa-instagram"></i> Instagram
                    </a>
                    <a href="https://www.twitter.com" target="_blank" class="social-link">
                        <i class="fab fa-twitter"></i> Twitter
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form">
            <h2><i class="fas fa-paper-plane"></i> Send Us a Message</h2>
            <p class="subtitle">Have a question or special request? We'd love to help!</p>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name <span class="required">*</span></label>
                        <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="required">*</span></label>
                        <select name="subject" required>
                            <option value="">Select Subject</option>
                            <option value="general" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="order" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'order') ? 'selected' : ''; ?>>Order Status</option>
                            <option value="custom" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'custom') ? 'selected' : ''; ?>>Custom Arrangement</option>
                            <option value="corporate" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'corporate') ? 'selected' : ''; ?>>Corporate Orders</option>
                            <option value="feedback" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'feedback') ? 'selected' : ''; ?>>Feedback</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Message <span class="required">*</span></label>
                    <textarea name="message" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <!-- Map Section -->
    <div class="map-section">
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d255282.35887743752!2d36.68215981272057!3d-1.286389073473938!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f1172c84a6afd%3A0x4d6f8a8f6f0f2e5b!2sNairobi%2C%20Kenya!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>

    <!-- FAQ Note -->
    <div style="text-align: center; margin-top: 2rem; padding: 1rem; background: #fef6ef; border-radius: 12px;">
        <p style="color: #7b6b5c;">
            <i class="fas fa-question-circle" style="color: #c45c4a;"></i> 
            Have questions? Check out our <a href="faq.php" style="color: #c45c4a; text-decoration: none;">FAQ page</a> for quick answers to common questions.
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>