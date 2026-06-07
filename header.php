<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart count from session or localStorage (via JavaScript)
// PHP cart count can be added later when implementing database cart
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fefaf7;
            color: #2d2a24;
        }

        /* Navigation */
        .navbar {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            padding: 1rem 5%;
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            color: #5a3f2c;
            font-weight: 500;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #c45c4a;
        }

        /* Dropdown Menu - Improved */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-btn {
            background: none;
            border: none;
            color: #5a3f2c;
            font-weight: 500;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            transition: 0.3s;
        }

        .dropdown-btn:hover {
            color: #c45c4a;
        }

        .dropdown-btn i {
            font-size: 0.8rem;
            transition: transform 0.3s;
        }

        .dropdown:hover .dropdown-btn i {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 220px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-radius: 12px;
            z-index: 1000;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        /* Show dropdown on hover with smooth transition */
        .dropdown:hover .dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Keep dropdown visible when hovering over the content */
        .dropdown-content:hover {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1.2rem;
            color: #5a3f2c;
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0e0d4;
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a:hover {
            background: #fef6ef;
            color: #c45c4a;
            padding-left: 1.8rem;
        }

        .dropdown-content i {
            width: 20px;
        }

        /* For touch devices - click to open */
        .dropdown-content.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .cart-link {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -12px;
            background: #c45c4a;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 20px;
            text-align: center;
        }

        .logout-btn {
            background: transparent;
            border: 1px solid #c45c4a;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            color: #c45c4a;
            transition: 0.3s;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #c45c4a;
            color: white;
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-outline {
            border: 2px solid #c45c4a;
            background: transparent;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            color: #c45c4a;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-outline:hover {
            background: #c45c4a;
            color: white;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: #c45c4a;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: #a84a3a;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(196,92,74,0.3);
        }

        /* Footer Styles */
        footer {
            background: #2a1f18;
            color: #d4bca8;
            padding: 2rem 5% 1rem;
            margin-top: 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-section h4 {
            color: white;
            margin-bottom: 1rem;
        }

        .footer-section a {
            color: #d4bca8;
            text-decoration: none;
            transition: 0.3s;
        }

        .footer-section a:hover {
            color: #c45c4a;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: #d4bca8;
            font-size: 1.5rem;
            transition: 0.3s;
        }

        .social-links a:hover {
            color: #c45c4a;
        }

        .copyright {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(212,188,168,0.2);
        }

        @media (max-width: 768px) {
            .nav-links {
                justify-content: center;
                width: 100%;
            }
            .navbar {
                flex-direction: column;
            }
            .dropdown-content {
                position: static;
                box-shadow: none;
                margin-top: 0.5rem;
                background: #f8f9fa;
                opacity: 1;
                visibility: visible;
                transform: none;
                display: none;
            }
            .dropdown-content.show {
                display: block;
            }
            .dropdown:hover .dropdown-content {
                display: none;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="dashboard.php" class="logo">🌺 Ray & Roses</a>
    <div class="nav-links">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="shop.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : ''; ?>">
            <i class="fas fa-store"></i> Shop
        </a>
        
        <!-- Pages Dropdown Menu -->
        <div class="dropdown" id="infoDropdown">
            <button class="dropdown-btn" id="dropdownBtn">
                <i class="fas fa-info-circle"></i> Info 
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-content" id="dropdownContent">
                <a href="about.php">
                    <i class="fas fa-seedling"></i> About Us
                </a>
                <a href="contact.php">
                    <i class="fas fa-envelope"></i> Contact
                </a>
                <a href="faq.php">
                    <i class="fas fa-question-circle"></i> FAQ
                </a>
                <a href="terms.php">
                    <i class="fas fa-file-contract"></i> Terms of Service
                </a>
            </div>
        </div>
        
        <a href="cart.php" class="cart-link <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i> Cart 
            <span class="cart-count" id="cartCount">0</span>
        </a>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="user-menu">
                <a href="account.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Account
                </a>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-outline">Sign In</a>
            <a href="register.php" class="btn-primary">Get Started</a>
        <?php endif; ?>
    </div>
</nav>

<script>
// Get cart count from localStorage
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        el.textContent = totalItems;
    });
}

// Update cart count when page loads and when cart changes
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Listen for storage events (when cart changes in another tab)
    window.addEventListener('storage', function(e) {
        if (e.key === 'flowerCart') {
            updateCartCount();
        }
    });
});

// Custom event for cart updates (can be called from other pages)
window.updateCartCountGlobal = updateCartCount;

// For touch devices - click to toggle dropdown
const dropdownBtn = document.getElementById('dropdownBtn');
const dropdownContent = document.getElementById('dropdownContent');

if (dropdownBtn && dropdownContent) {
    dropdownBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Toggle the dropdown
        dropdownContent.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdownBtn.contains(e.target) && !dropdownContent.contains(e.target)) {
            dropdownContent.classList.remove('show');
        }
    });
    
    // Prevent dropdown from closing when clicking inside
    dropdownContent.addEventListener('click', function(e) {
        e.stopPropagation();
    });
}
</script>