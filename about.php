<?php
session_start();
require_once 'config/database.php';

// Include header
include 'header.php';
?>

<style>
    .about-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    /* Hero Section */
    .about-hero {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 4rem 5%;
        text-align: center;
        border-radius: 20px;
        margin-bottom: 3rem;
        position: relative;
        overflow: hidden;
    }

    .about-hero::before {
        content: '🌸🌺🌷🌹';
        position: absolute;
        font-size: 120px;
        opacity: 0.1;
        right: -30px;
        bottom: -30px;
        letter-spacing: 20px;
    }

    .about-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .about-hero p {
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
        opacity: 0.95;
    }

    /* Our Story Section */
    .story-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        margin-bottom: 4rem;
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .story-content h2 {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        color: #3d2a1f;
        margin-bottom: 1rem;
    }

    .story-content p {
        color: #5a3f2c;
        line-height: 1.8;
        margin-bottom: 1rem;
    }

    .story-image {
        border-radius: 15px;
        overflow: hidden;
    }

    .story-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }

    .story-image:hover img {
        transform: scale(1.05);
    }

    /* Mission & Vision */
    .mission-vision {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
        margin-bottom: 4rem;
    }

    .mv-card {
        background: white;
        padding: 2rem;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: transform 0.3s;
    }

    .mv-card:hover {
        transform: translateY(-5px);
    }

    .mv-card i {
        font-size: 3rem;
        color: #c45c4a;
        margin-bottom: 1rem;
    }

    .mv-card h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: #3d2a1f;
        margin-bottom: 1rem;
    }

    .mv-card p {
        color: #7b6b5c;
        line-height: 1.6;
    }

    /* Values Section */
    .values-section {
        margin-bottom: 4rem;
        text-align: center;
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        color: #3d2a1f;
        margin-bottom: 2rem;
        position: relative;
        display: inline-block;
    }

    .section-title:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: #c45c4a;
        border-radius: 2px;
    }

    .values-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .value-card {
        background: white;
        padding: 2rem;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s;
    }

    .value-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .value-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .value-icon i {
        font-size: 2rem;
        color: white;
    }

    .value-card h4 {
        font-size: 1.2rem;
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .value-card p {
        color: #7b6b5c;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    /* Team Section */
    .team-section {
        margin-bottom: 4rem;
        text-align: center;
    }

    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .team-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s;
    }

    .team-card:hover {
        transform: translateY(-5px);
    }

    .team-image {
        height: 250px;
        background-size: cover;
        background-position: center;
    }

    .team-info {
        padding: 1.5rem;
        text-align: center;
    }

    .team-info h4 {
        font-size: 1.2rem;
        color: #3d2a1f;
        margin-bottom: 0.3rem;
    }

    .team-info p {
        color: #c45c4a;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }

    .team-info .bio {
        color: #7b6b5c;
        font-size: 0.85rem;
        line-height: 1.5;
    }

    /* Stats Section */
    .stats-section {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 3rem;
        border-radius: 20px;
        margin-bottom: 3rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        text-align: center;
    }

    .stat-item h3 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stat-item p {
        font-size: 1rem;
        opacity: 0.9;
    }

    /* CTA Section */
    .cta-section {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .cta-section h2 {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .cta-section p {
        color: #7b6b5c;
        margin-bottom: 1.5rem;
    }

    .cta-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-primary, .btn-secondary {
        display: inline-block;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }

    .btn-primary {
        background: #c45c4a;
        color: white;
    }

    .btn-primary:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: transparent;
        border: 2px solid #c45c4a;
        color: #c45c4a;
    }

    .btn-secondary:hover {
        background: #c45c4a;
        color: white;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .story-section {
            grid-template-columns: 1fr;
        }
        
        .mission-vision {
            grid-template-columns: 1fr;
        }
        
        .about-hero h1 {
            font-size: 2rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="about-container">
    <!-- Hero Section -->
    <div class="about-hero">
        <h1>Our Story</h1>
        <p>Bringing nature's beauty to your doorstep with love and care since 2020</p>
    </div>

    <!-- Our Story Section -->
    <div class="story-section">
        <div class="story-content">
            <h2>How Ray & Roses Began</h2>
            <p>Ray & Roses was born from a simple idea: everyone deserves to experience the joy of fresh, beautiful flowers. What started as a small home-based business has grown into a beloved flower shop serving thousands of happy customers across Kenya.</p>
            <p>Our founder, Raymond, discovered his passion for floristry while looking for the perfect bouquet for his mother's birthday. Realizing the lack of fresh, high-quality flowers delivered with care, he decided to create a service that would make everyone feel special.</p>
            <p>Today, Ray & Roses is known for our handcrafted arrangements, exceptional customer service, and commitment to freshness. We partner with local farms to bring you the finest blooms while supporting sustainable agriculture.</p>
        </div>
        <div class="story-image">
            <img src="https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format" alt="Our flower shop">
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="mission-vision">
        <div class="mv-card">
            <i class="fas fa-bullseye"></i>
            <h3>Our Mission</h3>
            <p>To spread joy and create memorable moments through fresh, beautifully arranged flowers delivered with care and passion.</p>
        </div>
        <div class="mv-card">
            <i class="fas fa-eye"></i>
            <h3>Our Vision</h3>
            <p>To become Kenya's most beloved flower shop, known for quality, creativity, and making every occasion special.</p>
        </div>
    </div>

    <!-- Our Values -->
    <div class="values-section">
        <h2 class="section-title">Our Core Values</h2>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h4>Freshness First</h4>
                <p>We source our flowers daily from local farms to ensure maximum freshness and longevity.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h4>Customer Love</h4>
                <p>Your satisfaction is our priority. We go above and beyond to make every customer happy.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h4>Premium Quality</h4>
                <p>Only the finest blooms make it to our collection. We never compromise on quality.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h4>Timely Delivery</h4>
                <p>We understand the importance of timing. Your flowers will arrive when promised.</p>
            </div>
        </div>
    </div>

    <!-- Our Team -->
    <div class="team-section">
        <h2 class="section-title">Meet Our Team</h2>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-image" style="background-image: url('https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&auto=format');"></div>
                <div class="team-info">
                    <h4>Raymond Mwangi</h4>
                    <p>Founder & Head Florist</p>
                    <p class="bio">With over 10 years of experience, Raymond brings passion and creativity to every arrangement.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image" style="background-image: url('https://images.unsplash.com/photo-1590736704728-f47a5d8b0760?w=400&auto=format');"></div>
                <div class="team-info">
                    <h4>Sarah Wanjiku</h4>
                    <p>Senior Florist</p>
                    <p class="bio">Sarah's artistic eye creates stunning bouquets that capture emotions perfectly.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image" style="background-image: url('https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=400&auto=format');"></div>
                <div class="team-info">
                    <h4>James Otieno</h4>
                    <p>Delivery Manager</p>
                    <p class="bio">James ensures your flowers arrive fresh and on time, every time.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>5,000+</h3>
                <p>Happy Customers</p>
            </div>
            <div class="stat-item">
                <h3>15,000+</h3>
                <p>Bouquets Delivered</p>
            </div>
            <div class="stat-item">
                <h3>50+</h3>
                <p>Flower Varieties</p>
            </div>
            <div class="stat-item">
                <h3>4.9 ★</h3>
                <p>Customer Rating</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
        <h2>Ready to Experience Ray & Roses?</h2>
        <p>Browse our collection and find the perfect flowers for any occasion</p>
        <div class="cta-buttons">
            <a href="shop.php" class="btn-primary">Shop Now</a>
            <a href="contact.php" class="btn-secondary">Contact Us</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>