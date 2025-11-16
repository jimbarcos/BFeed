<?php
/**
 * BuzzarFeed - About Page
 * 
 * Learn about BuzzarFeed and meet the team
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;

Session::start();

$pageTitle = "About Us - BuzzarFeed";
$pageDescription = "Everything you need to know about BuzzarFeed - Your digital guide to the flavors of the BGC Night Market Bazaar";

// Hardcoded team members 
$teamMembers = [
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruzs', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
    ['name' => 'Juan Dela Cruz', 'position' => 'Member'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= Helpers::escape($pageDescription) ?>">
    <title><?= Helpers::escape($pageTitle) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= IMAGES_URL ?>/favicon.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Modular CSS Architecture -->
    <link rel="stylesheet" href="<?= CSS_URL ?>/variables.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/base.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/button.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/dropdown.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/styles.css">
    
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        main {
            flex: 1 0 auto;
        }
        
        /* Hero Section */
        .hero-section {
            background: #FEEED5;
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Decorative shapes */
        .deco-shape {
            position: absolute;
            opacity: 0.8;
        }
        
        .deco-shape.green-1 {
            width: 250px;
            height: 300px;
            background: #489A44;
            top: -50px;
            left: -80px;
            transform: rotate(-15deg);
            clip-path: polygon(20% 0%, 80% 0%, 100% 20%, 100% 80%, 80% 100%, 20% 100%, 0% 80%, 0% 20%);
        }
        
        .deco-shape.green-2 {
            width: 200px;
            height: 250px;
            background: #489A44;
            bottom: -50px;
            right: 50px;
            transform: rotate(25deg);
            clip-path: polygon(20% 0%, 80% 0%, 100% 20%, 100% 80%, 80% 100%, 20% 100%, 0% 80%, 0% 20%);
        }
        
        .deco-shape.orange-1 {
            width: 180px;
            height: 180px;
            background: #ED6027;
            top: 50px;
            right: -60px;
            transform: rotate(30deg);
            border-radius: 30%;
        }
        
        .deco-shape.orange-2 {
            width: 150px;
            height: 150px;
            background: #ED6027;
            bottom: 100px;
            left: 100px;
            transform: rotate(-20deg);
            border-radius: 30%;
        }
        
        .hero-content {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 56px;
            font-weight: 700;
            color: #2C2C2C;
            margin: 0 0 30px 0;
            line-height: 1.2;
        }
        
        .hero-title .highlight {
            color: #ED6027;
            background: white;
            padding: 5px 15px;
            border-radius: 8px;
            display: inline-block;
        }
        
        .hero-title .icon-stall {
            color: #489A44;
            background: white;
            padding: 5px 15px;
            border-radius: 8px;
            display: inline-block;
        }
        
        .hero-title .icon-fire {
            color: #ED6027;
            background: white;
            padding: 5px 15px;
            border-radius: 8px;
            display: inline-block;
        }
        
        .hero-subtitle {
            font-size: 18px;
            color: #666;
            margin: 0;
        }
        
        /* What We're About Section */
        .about-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 20px;
        }
        
        .about-header {
            margin-bottom: 50px;
        }
        
        .about-title {
            font-size: 48px;
            font-weight: 700;
            color: #2C2C2C;
            margin: 0 0 20px 0;
        }
        
        .about-title .green {
            color: #489A44;
        }
        
        .about-description {
            font-size: 18px;
            color: #666;
            line-height: 1.8;
            max-width: 600px;
        }
        
        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            position: relative;
        }
        
        .about-card {
            background: white;
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .about-card:hover {
            transform: translateY(-5px);
        }
        
        .about-card.orange {
            background: #ED6027;
            color: white;
        }
        
        .about-card.green {
            background: #489A44;
            color: white;
        }
        
        .card-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 15px 0;
        }
        
        .card-text {
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
        }
        
        .orange-star {
            width: 150px;
            height: 150px;
            background: #ED6027;
            position: absolute;
            right: 50px;
            top: 50%;
            transform: translateY(-50%) rotate(15deg);
            border-radius: 30%;
        }
        
        /* Team Section */
        .team-section {
            background: #489A44;
            padding: 80px 20px;
            position: relative;
            overflow: hidden;
        }
        
        .team-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
        }
        
        .team-title-box {
            background: linear-gradient(135deg, #FFB800 0%, #ED6027 100%);
            padding: 40px 60px;
            border-radius: 12px;
            display: inline-block;
            position: absolute;
            right: 50px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }
        
        .team-title {
            font-size: 48px;
            font-weight: 700;
            color: white;
            margin: 0;
            line-height: 1.2;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            max-width: 850px;
        }
        
        .team-grid .team-member:nth-child(7) {
            grid-column: 1 / 2;
        }
        
        .team-grid .team-member:nth-child(8) {
            grid-column: 2 / 3;
        }
        
        .team-grid .team-member:nth-child(9) {
            grid-column: 3 / 4;
        }
        
        .team-grid .team-member:nth-child(10) {
            grid-column: 2 / 3;
        }
        
        .team-member {
            text-align: center;
        }
        
        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .member-photo i {
            font-size: 60px;
            color: #ED6027;
        }
        
        .member-info {
            background: #FEEED5;
            padding: 12px 20px;
            border-radius: 25px;
        }
        
        .member-name {
            font-size: 14px;
            font-weight: 600;
            color: #ED6027;
            margin: 0 0 3px 0;
        }
        
        .member-position {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        
        .team-star-1 {
            width: 120px;
            height: 120px;
            background: #ED6027;
            position: absolute;
            left: 50px;
            top: 100px;
            border-radius: 30%;
        }
        
        .team-star-2 {
            width: 150px;
            height: 150px;
            background: #ED6027;
            position: absolute;
            right: 50px;
            bottom: 100px;
            border-radius: 50%;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #ED6027 0%, #E8663E 100%);
            padding: 80px 20px;
            text-align: center;
            color: white;
        }
        
        .cta-title {
            font-size: 48px;
            font-weight: 700;
            margin: 0 0 20px 0;
            color: white;
        }
        
        .cta-text {
            font-size: 18px;
            margin: 0 0 40px 0;
            opacity: 0.95;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .cta-btn {
            padding: 16px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .cta-btn.primary {
            background: #489A44;
            color: white;
        }
        
        .cta-btn.primary:hover {
            background: #3d7d3a;
            transform: translateY(-2px);
        }
        
        .cta-btn.secondary {
            background: white;
            color: #ED6027;
        }
        
        .cta-btn.secondary:hover {
            background: #FEEED5;
            transform: translateY(-2px);
        }
        
        @media (max-width: 1200px) {
            .team-grid {
                padding-right: 0;
                margin-top: 200px;
            }
            
            .team-title-box {
                position: static;
                transform: none;
                margin-bottom: 50px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 36px;
            }
            
            .about-title {
                font-size: 36px;
            }
            
            .team-title {
                font-size: 36px;
            }
            
            .cta-title {
                font-size: 36px;
            }
            
            .about-grid {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .orange-star {
                display: none;
            }
            
            .deco-shape {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <!-- Decorative Shapes -->
            <div class="deco-shape green-1"></div>
            <div class="deco-shape green-2"></div>
            <div class="deco-shape orange-1"></div>
            <div class="deco-shape orange-2"></div>
            
            <div class="hero-content">
                <h1 class="hero-title">
                    Everything <span class="icon-stall"><i class="fas fa-store"></i></span> You<br>
                    Need <span class="icon-fire"><i class="fas fa-fire"></i></span> To Know<br>
                    About <span class="highlight">BuzzarFeed</span>
                </h1>
                <p class="hero-subtitle">
                    Welcome to BuzzarFeed — your digital guide to the flavors of the BGC Night Market Bazaar.
                </p>
            </div>
        </section>
        
        <!-- What We're About Section -->
        <section class="about-section">
            <div class="about-header">
                <h2 class="about-title">
                    <span class="green">What</span> we're<br>
                    all about
                </h2>
                <p class="about-description">
                    To connect food lovers with the vibrant stalls and hidden gems that make the night market buzz with life. From crispy street snacks to hearty rice meals, fresh sweet indulgences to refreshing drinks, every stall has a story — and we're here to help you discover yours.
                </p>
            </div>
            
            <div class="about-grid">
                <div class="about-card orange">
                    <div class="card-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="card-title">What we do</h3>
                    <p class="card-text">
                        Browse through a curated list of vendors, each with their menus and reviews. Discover crowd favorites, dig into detailed reviews, and find what's buzzing.
                    </p>
                </div>
                
                <div class="about-card green">
                    <div class="card-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="card-title">What we do</h3>
                    <p class="card-text">
                        Browse through a curated list of vendors, each with their menus and reviews. Discover crowd favorites, dig into detailed reviews, and find what's buzzing.
                    </p>
                </div>
                
                <div class="about-card green">
                    <div class="card-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="card-title">What we do</h3>
                    <p class="card-text">
                        Browse through a curated list of vendors, each with their menus and reviews. Quickly find stalls by cuisine, price, or vibes. Filter by what's open now, or explore our bazaar map to chart your own food adventure.
                    </p>
                </div>
                
                <div class="orange-star"></div>
            </div>
        </section>
        
        <!-- Team Section -->
        <section class="team-section">
            <div class="team-star-1"></div>
            <div class="team-star-2"></div>
            
            <div class="team-container">
                <div class="team-title-box">
                    <h2 class="team-title">
                        Meet the<br>
                        Team
                    </h2>
                </div>
                
                <div class="team-grid">
                    <?php foreach ($teamMembers as $member): ?>
                        <div class="team-member">
                            <div class="member-photo">
                                <i class="fas fa-smile"></i>
                            </div>
                            <div class="member-info">
                                <p class="member-name"><?= Helpers::escape($member['name']) ?></p>
                                <p class="member-position"><?= Helpers::escape($member['position']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- CTA Section -->
        <section class="cta-section">
            <h2 class="cta-title">Ready to Join the Buzz?</h2>
            <p class="cta-text">
                Be part of the BuzzarFeed community — discover stalls, write reviews, and<br>
                connect with fellow foodies.
            </p>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>signup" class="cta-btn primary">Sign Up Now</a>
                <a href="<?= BASE_URL ?>stalls" class="cta-btn secondary">Explore Stalls</a>
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
