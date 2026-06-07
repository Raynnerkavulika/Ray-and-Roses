<?php
session_start();
// No login required for landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petal & Stem | Fresh Flowers Delivered</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fffaf7;
            overflow-x: hidden;
        }

        /* Custom Cursor & Selection */
        ::selection {
            background: #e8a48c;
            color: white;
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Animated Background Gradient */
        .hero {
            background: linear-gradient(135deg, #fff5ef 0%, #ffe8e0 50%, #fff0e8 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23f5d5c1" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') repeat-x bottom;
            background-size: cover;
            opacity: 0.4;
            pointer-events: none;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1.2rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.3s ease;
            background: transparent;
        }

        .navbar.scrolled {
            background: rgba(255, 248, 240, 0.95);
            backdrop-filter: blur(15px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 0.8rem 5%;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #5a3f2c;
            font-weight: 500;
            transition: 0.3s;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #c45c4a;
            transition: width 0.3s;
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }

        .btn-outline {
            border: 2px solid #c45c4a;
            background: transparent;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            color: #c45c4a;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline:hover {
            background: #c45c4a;
            color: white;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: #c45c4a;
            color: white;
            padding: 0.5rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            transition: 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #a84a3a;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(196,92,74,0.3);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 0 5%;
            position: relative;
        }

        .hero-content {
            flex: 1;
            animation: fadeInUp 1s ease;
        }

        .hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.2;
            color: #3d2a1f;
            margin-bottom: 1.5rem;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #7a5a48;
            margin-bottom: 2rem;
            line-height: 1.6;
            max-width: 500px;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-image {
            flex: 1;
            animation: fadeInRight 1s ease;
            position: relative;
        }

        .hero-image img {
            width: 100%;
            max-width: 500px;
            border-radius: 30px;
            box-shadow: 0 30px 50px rgba(0,0,0,0.1);
        }

        .floating-badge {
            position: absolute;
            background: white;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: float 3s ease-in-out infinite;
        }

        .badge-1 {
            top: 20%;
            right: -20px;
        }

        .badge-2 {
            bottom: 10%;
            left: -20px;
            animation-delay: 1s;
        }

        /* User Menu for Logged In Users */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            color: #5a3f2c;
            font-weight: 500;
        }

        .logout-btn {
            background: transparent;
            border: 1px solid #c45c4a;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            color: #c45c4a;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: #c45c4a;
            color: white;
        }

        /* Features Section */
        .features {
            padding: 5rem 5%;
            background: white;
        }

        .section-title {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #3d2a1f;
            margin-bottom: 3rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            transition: 0.3s;
            border-radius: 20px;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: #fffaf7;
        }

        .feature-icon {
            font-size: 3rem;
            color: #c45c4a;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            margin-bottom: 0.5rem;
            color: #3d2a1f;
        }

        /* Gallery Preview */
        .gallery-preview {
            padding: 5rem 5%;
            background: linear-gradient(135deg, #fff5ef, #ffe8e0);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .gallery-item {
            border-radius: 20px;
            overflow: hidden;
            height: 300px;
            position: relative;
            cursor: pointer;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.5s;
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }

        .gallery-overlay {
            position: absolute;
            bottom: -100%;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 1rem;
            color: white;
            transition: 0.3s;
        }

        .gallery-item:hover .gallery-overlay {
            bottom: 0;
        }

        /* CTA Section */
        .cta {
            padding: 5rem 5%;
            text-align: center;
            background: #3d2a1f;
            color: white;
        }

        .cta h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta .btn-primary {
            background: white;
            color: #c45c4a;
            margin-top: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cta .btn-primary:hover {
            background: #ffe8e0;
            transform: scale(1.05);
        }

        /* Footer */
        footer {
            background: #2a1f18;
            color: #d4bca8;
            padding: 3rem 5% 1rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-section h4 {
            color: white;
            margin-bottom: 1rem;
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
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(212,188,168,0.2);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                text-align: center;
                padding-top: 100px;
            }
            .hero-content h1 {
                font-size: 2.5rem;
            }
            .hero-content p {
                margin-left: auto;
                margin-right: auto;
            }
            .hero-buttons {
                justify-content: center;
            }
            .nav-links {
                display: none;
            }
            .navbar {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="logo">Petal & Stem</div>
    <div class="nav-links">
        <a href="index.php" class="active">Home</a>
        <a href="#features">Features</a>
        <a href="#gallery">Gallery</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="user-menu">
                <span class="user-name">🌸 Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-outline"><i class="fas fa-sign-in-alt"></i> Sign In</a>
            <a href="register.php" class="btn-primary"><i class="fas fa-user-plus"></i> Get Started</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <div class="hero-content">
        <h1>Fresh Flowers<br>Delivered With Love</h1>
        <p>Discover nature's most beautiful creations. Hand-picked, fresh, and delivered right to your doorstep.</p>
        <div class="hero-buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="shop.php" class="btn-primary" style="padding: 1rem 2rem;"><i class="fas fa-shopping-bag"></i> Shop Now </a>
            <?php else: ?>
                <a href="register.php" class="btn-primary" style="padding: 1rem 2rem;"><i class="fas fa-shopping-cart"></i> Start Shopping </a>
            <?php endif; ?>
            <a href="#features" style="text-decoration: none; padding: 1rem 2rem; color: #c45c4a; font-weight: 600;"><i class="fas fa-info-circle"></i> Learn More</a>
        </div>
    </div>
    <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format" alt="Beautiful flowers">
        <div class="floating-badge badge-1">
            <i class="fas fa-truck" style="color: #c45c4a;"></i>
            <span>Free Delivery</span>
        </div>
        <div class="floating-badge badge-2">
            <i class="fas fa-star" style="color: #ffd700;"></i>
            <span>4.9 Rating</span>
        </div>
    </div>
</section>

<section id="features" class="features">
    <h2 class="section-title">Why Choose Us?</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-leaf"></i></div>
            <h3>100% Fresh</h3>
            <p>Directly from local farms, delivered within 24 hours</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-gem"></i></div>
            <h3>Premium Quality</h3>
            <p>Only the finest blooms make it to our collection</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-clock"></i></div>
            <h3>Same-Day Delivery</h3>
            <p>Order before 2PM for same-day delivery</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-heart"></i></div>
            <h3>Loved by Thousands</h3>
            <p>Join 10,000+ happy customers</p>
        </div>
    </div>
</section>

<section id="gallery" class="gallery-preview">
    <h2 class="section-title">Our Beautiful Collection</h2>
    <div class="gallery-grid">
        <div class="gallery-item">
            <img src="https://images.unsplash.com/photo-1561181286-d3fee7d78f8f?w=400&auto=format" alt="Rose Bouquet">
            <div class="gallery-overlay">Romantic Roses</div>
        </div>
        <div class="gallery-item">
            <img src="https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=400&auto=format" alt="Sunflowers">
            <div class="gallery-overlay">Sunny Daisies</div>
        </div>
        <div class="gallery-item">
            <img src="https://images.unsplash.com/photo-1562690868-60bbe7293e94?w=400&auto=format" alt="Orchids">
            <div class="gallery-overlay">Exotic Orchids</div>
        </div>
        <div class="gallery-item">
            <img src="https://images.unsplash.com/photo-1582793988951-9aed5509eb97?w=400&auto=format" alt="Peonies">
            <div class="gallery-overlay">Peony Dreams</div>
        </div>
    </div>
</section>

<section class="cta">
    <h2>Ready to Bring Nature Home?</h2>
    <p>Create an account and start exploring our stunning flower collections</p>
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="shop.php" class="btn-primary"><i class="fas fa-shopping-bag"></i> Shop Now </a>
    <?php else: ?>
        <a href="register.php" class="btn-primary"><i class="fas fa-user-plus"></i> Create Free Account </a>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>

<script>
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>
</body>
</html>