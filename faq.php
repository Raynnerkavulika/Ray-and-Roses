<?php
session_start();
require_once 'config/database.php';

// Include header
include 'header.php';
?>

<style>
    .faq-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    /* Hero Section */
    .faq-hero {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 3rem 5%;
        text-align: center;
        border-radius: 20px;
        margin-bottom: 3rem;
        position: relative;
        overflow: hidden;
    }

    .faq-hero::before {
        content: '❓💐🌹🌺';
        position: absolute;
        font-size: 100px;
        opacity: 0.1;
        right: -20px;
        bottom: -20px;
        letter-spacing: 15px;
    }

    .faq-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .faq-hero p {
        font-size: 1rem;
        opacity: 0.95;
    }

    /* Search Bar */
    .search-section {
        margin-bottom: 2rem;
    }

    .search-box {
        max-width: 600px;
        margin: 0 auto;
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: 2px solid #f0e0d4;
        border-radius: 50px;
        font-size: 1rem;
        font-family: 'Inter', sans-serif;
        transition: 0.3s;
    }

    .search-box input:focus {
        outline: none;
        border-color: #c45c4a;
        box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
    }

    .search-box i {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        color: #c45c4a;
        font-size: 1.1rem;
    }

    /* Category Tabs */
    .category-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        justify-content: center;
        margin-bottom: 2rem;
    }

    .category-btn {
        background: transparent;
        border: 1px solid #e2cbb8;
        padding: 0.6rem 1.5rem;
        border-radius: 40px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        color: #5a3f2c;
        font-family: 'Inter', sans-serif;
    }

    .category-btn.active,
    .category-btn:hover {
        background: #c45c4a;
        border-color: #c45c4a;
        color: white;
    }

    /* FAQ Accordion */
    .faq-section {
        margin-bottom: 3rem;
    }

    .category-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        color: #3d2a1f;
        margin-bottom: 1.5rem;
        text-align: center;
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .category-title:after {
        content: '';
        display: block;
        width: 60px;
        height: 3px;
        background: #c45c4a;
        margin: 0.5rem auto 0;
        border-radius: 2px;
    }

    .faq-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 1rem;
    }

    .faq-item {
        background: white;
        border-radius: 12px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: 0.3s;
    }

    .faq-question {
        padding: 1.2rem 1.5rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        transition: 0.3s;
    }

    .faq-question:hover {
        background: #fef6ef;
    }

    .faq-question h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #3d2a1f;
        margin: 0;
        flex: 1;
    }

    .faq-icon {
        color: #c45c4a;
        font-size: 1.2rem;
        transition: transform 0.3s;
    }

    .faq-item.active .faq-icon {
        transform: rotate(180deg);
    }

    .faq-answer {
        padding: 0 1.5rem;
    }

    .faq-answer .answer-content {
        padding-bottom: 1.2rem;
        color: #7b6b5c;
        line-height: 1.6;
        border-top: 1px solid #f0e0d4;
        padding-top: 1rem;
    }

    .faq-answer ul, 
    .faq-answer ol {
        margin-left: 1.5rem;
        margin-top: 0.5rem;
    }

    .faq-answer li {
        margin-bottom: 0.3rem;
    }

    /* Still Have Questions Section */
    .still-questions {
        background: linear-gradient(135deg, #fef6ef, #ffe8e0);
        border-radius: 20px;
        padding: 3rem;
        text-align: center;
        margin-top: 2rem;
    }

    .still-questions h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .still-questions p {
        color: #7b6b5c;
        margin-bottom: 1.5rem;
    }

    .contact-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-contact, .btn-chat {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.5rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }

    .btn-contact {
        background: #c45c4a;
        color: white;
    }

    .btn-contact:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    .btn-chat {
        background: transparent;
        border: 2px solid #c45c4a;
        color: #c45c4a;
    }

    .btn-chat:hover {
        background: #c45c4a;
        color: white;
        transform: translateY(-2px);
    }

    /* No Results */
    .no-results {
        text-align: center;
        padding: 3rem;
        color: #7b6b5c;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .faq-grid {
            grid-template-columns: 1fr;
        }
        
        .faq-hero h1 {
            font-size: 1.8rem;
        }
        
        .category-tabs {
            gap: 0.5rem;
        }
        
        .category-btn {
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
        }
    }
</style>

<div class="faq-container">
    <!-- Hero Section -->
    <div class="faq-hero">
        <h1><i class="fas fa-question-circle"></i> Frequently Asked Questions</h1>
        <p>Find answers to common questions about our flowers, delivery, and services</p>
    </div>

    <!-- Search Bar -->
    <div class="search-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search FAQs...">
        </div>
    </div>

    <!-- Category Tabs -->
    <div class="category-tabs" id="categoryTabs">
        <button class="category-btn active" data-category="all">All Questions</button>
        <button class="category-btn" data-category="ordering">Ordering</button>
        <button class="category-btn" data-category="delivery">Delivery</button>
        <button class="category-btn" data-category="payment">Payment</button>
        <button class="category-btn" data-category="products">Products</button>
        <button class="category-btn" data-category="returns">Returns & Refunds</button>
    </div>

    <!-- FAQs Container -->
    <div id="faqsContainer"></div>

    <!-- Still Have Questions -->
    <div class="still-questions">
        <h2>Still Have Questions?</h2>
        <p>We're here to help! Contact us directly and we'll get back to you within 24 hours.</p>
        <div class="contact-buttons">
            <a href="contact.php" class="btn-contact"><i class="fas fa-envelope"></i> Contact Us</a>
            <a href="#" class="btn-chat" onclick="startChat()"><i class="fas fa-comment-dots"></i> Live Chat</a>
        </div>
    </div>
</div>

<script>
// FAQ Data
const faqs = [
    // Ordering Questions
    { category: "ordering", question: "How do I place an order?", answer: "You can place an order by browsing our shop page, selecting your desired flowers, adding them to cart, and proceeding to checkout. You'll need to create an account or login to complete your purchase." },
    { category: "ordering", question: "Can I cancel or modify my order?", answer: "Yes, you can cancel or modify your order within 1 hour of placing it. Please contact our customer service immediately at +254 712 345 678 or email info@rayandroses.com for assistance." },
    { category: "ordering", question: "Do I need an account to place an order?", answer: "Yes, you need to create an account to place an order. This helps us track your orders, provide personalized recommendations, and offer exclusive discounts." },
    { category: "ordering", question: "Can I order for same-day delivery?", answer: "Yes, same-day delivery is available for orders placed before 2 PM. Orders placed after 2 PM will be delivered the next business day." },
    
    // Delivery Questions
    { category: "delivery", question: "What are your delivery hours?", answer: "We deliver Monday through Saturday from 9 AM to 6 PM. Sunday deliveries are available for special occasions (additional charges may apply)." },
    { category: "delivery", question: "How much does delivery cost?", answer: "Delivery is free for orders over $50. For orders under $50, a flat delivery fee of $5.99 applies within Nairobi. Delivery fees to other locations vary based on distance." },
    { category: "delivery", question: "Do you deliver outside Nairobi?", answer: "Yes, we deliver to major towns across Kenya. Additional delivery charges may apply based on location. Please contact us for a quote." },
    { category: "delivery", question: "Can I track my order?", answer: "Yes, once your order is dispatched, you'll receive a tracking number via email and SMS to track your delivery in real-time." },
    
    // Payment Questions
    { category: "payment", question: "What payment methods do you accept?", answer: "We accept Credit/Debit Cards (Visa, Mastercard, Amex), M-Pesa, PayPal, and Cash on Delivery." },
    { category: "payment", question: "Is my payment information secure?", answer: "Absolutely! We use industry-standard SSL encryption to protect your payment information. We never store your full credit card details on our servers." },
    { category: "payment", question: "Do you offer installment payments?", answer: "Currently, we don't offer installment payments. Full payment is required at checkout." },
    { category: "payment", question: "How does M-Pesa payment work?", answer: "When you select M-Pesa at checkout, you'll receive a prompt on your phone to complete the payment. Enter your M-Pesa PIN to confirm the transaction." },
    
    // Products Questions
    { category: "products", question: "How fresh are your flowers?", answer: "We source our flowers daily from local farms. All flowers are hand-picked and delivered within 24 hours to ensure maximum freshness and longevity." },
    { category: "products", question: "How long do the flowers last?", answer: "With proper care, our flowers typically last 5-7 days. Some varieties like orchids and lilies can last up to 10-14 days." },
    { category: "products", question: "Do you offer custom arrangements?", answer: "Yes! We specialize in custom arrangements for weddings, corporate events, and special occasions. Contact us with your requirements and we'll create something unique for you." },
    { category: "products", question: "Are the flowers locally sourced?", answer: "Yes, we partner with local flower farms in Kenya, ensuring the freshest blooms while supporting local agriculture." },
    
    // Returns & Refunds
    { category: "returns", question: "What is your return policy?", answer: "Due to the perishable nature of flowers, we don't accept returns. However, if you're unsatisfied with your order, please contact us within 24 hours of delivery." },
    { category: "returns", question: "How do I get a refund?", answer: "If there's an issue with your order, contact us within 24 hours with photos of the product. We'll review and process a refund or replacement if applicable." },
    { category: "returns", question: "What if my flowers arrive damaged?", answer: "We take great care in packaging, but if your flowers arrive damaged, please take photos immediately and contact us within 2 hours of delivery. We'll arrange a replacement or refund." },
    { category: "returns", question: "Can I return a gift?", answer: "Yes, gift recipients can also request a replacement or refund following our standard policy. The refund will be processed to the original payment method." }
];

// DOM Elements
let currentCategory = 'all';
let currentSearch = '';

// Render FAQs
function renderFAQs() {
    const container = document.getElementById('faqsContainer');
    
    // Filter FAQs based on category and search
    let filteredFAQs = faqs;
    
    if (currentCategory !== 'all') {
        filteredFAQs = filteredFAQs.filter(faq => faq.category === currentCategory);
    }
    
    if (currentSearch) {
        const searchLower = currentSearch.toLowerCase();
        filteredFAQs = filteredFAQs.filter(faq => 
            faq.question.toLowerCase().includes(searchLower) || 
            faq.answer.toLowerCase().includes(searchLower)
        );
    }
    
    if (filteredFAQs.length === 0) {
        container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                <p>No questions found matching your search.</p>
                <p style="margin-top: 0.5rem;">Try different keywords or contact us directly.</p>
            </div>
        `;
        return;
    }
    
    // Group FAQs by category if showing all
    if (currentCategory === 'all' && !currentSearch) {
        const categories = ['ordering', 'delivery', 'payment', 'products', 'returns'];
        const categoryNames = {
            'ordering': 'Ordering',
            'delivery': 'Delivery Information',
            'payment': 'Payment Methods',
            'products': 'Products & Care',
            'returns': 'Returns & Refunds'
        };
        
        let html = '';
        categories.forEach(cat => {
            const catFAQs = filteredFAQs.filter(f => f.category === cat);
            if (catFAQs.length > 0) {
                html += `
                    <div class="faq-section" data-category="${cat}">
                        <h2 class="category-title">${categoryNames[cat]}</h2>
                        <div class="faq-grid">
                            ${catFAQs.map(faq => `
                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <h3>${escapeHtml(faq.question)}</h3>
                                        <i class="fas fa-chevron-down faq-icon"></i>
                                    </div>
                                    <div class="faq-answer" style="display: none;">
                                        <div class="answer-content">${escapeHtml(faq.answer)}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
        });
        container.innerHTML = html;
    } else {
        // Show as single list
        container.innerHTML = `
            <div class="faq-section">
                <div class="faq-grid">
                    ${filteredFAQs.map(faq => `
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <h3>${escapeHtml(faq.question)}</h3>
                                <i class="fas fa-chevron-down faq-icon"></i>
                            </div>
                            <div class="faq-answer" style="display: none;">
                                <div class="answer-content">${escapeHtml(faq.answer)}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
}

// Toggle FAQ answer
function toggleFAQ(element) {
    const faqItem = element.closest('.faq-item');
    const answer = faqItem.querySelector('.faq-answer');
    const icon = element.querySelector('.faq-icon');
    
    if (answer.style.display === 'none') {
        answer.style.display = 'block';
        faqItem.classList.add('active');
    } else {
        answer.style.display = 'none';
        faqItem.classList.remove('active');
    }
}

// Escape HTML
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Live Chat (simulated)
function startChat() {
    alert("Live chat is currently unavailable. Please contact us via email or phone for immediate assistance.\n\nEmail: info@rayandroses.com\nPhone: +254 712 345 678");
}

// Event Listeners
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCategory = btn.dataset.category;
        renderFAQs();
    });
});

document.getElementById('searchInput').addEventListener('input', (e) => {
    currentSearch = e.target.value;
    renderFAQs();
});

// Initialize
renderFAQs();
</script>

<?php include 'footer.php'; ?>