<?php
/**
 * BuzzarFeed - Homepage (Using Modular Architecture)
 *
 * Main landing page using new component system
 * Following ISO 9241: Maintainability, Reusability, Extensibility
 *
 * @package BuzzarFeed
 * @version 2.0
 * @author BuzzarFeed Development Team
 * @date November 2025
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/bootstrap.php';
} catch (Exception $e) {
    die("Bootstrap Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}

use BuzzarFeed\Sections\Home\HeroSection;
use BuzzarFeed\Sections\Home\FeaturedStallsSection;
use BuzzarFeed\Sections\Home\ReviewsSection;
use BuzzarFeed\Components\Common\Button;
use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Services\StallService;
use BuzzarFeed\Services\ReviewService;

// Initialize services
$stallService = new StallService();
$reviewService = new ReviewService();

// Fetch data from database
$randomStalls = $stallService->getAllActiveStalls(); // Get all stalls for carousel
$featuredStalls = $stallService->getRandomStalls(2);
$recentReviews = $reviewService->getRecentReviews(6);

// Page metadata
$pageTitle = "BuzzarFeed - Discover the Flavors of BGC Night Market";
$pageDescription = "Explore food stalls, menus, and honest reviews from fellow food lovers at BGC Night Market Bazaar";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo Helpers::escape($pageDescription); ?>">
    <meta name="keywords" content="BGC Night Market, food stalls, food reviews, Manila food blog, bazaar food">
    <meta name="author" content="BuzzarFeed">

    <title><?php echo Helpers::escape($pageTitle); ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo IMAGES_URL; ?>favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://api.fontshare.com/v2/css?f[]=geist@400,500,600,700&display=swap" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>variables.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>components/button.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>components/dropdown.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Header Navigation -->
    <?php include INCLUDES_PATH . '/header.php'; ?>

    <!-- Hero Section -->
    <?php
    $heroSection = new HeroSection([
        'title' => 'Discover the Flavors<br>of <span class="highlight-orange">BGC Night Market</span>',
        'description' => 'Taste. Try. Savor. Explore top stalls, menus, and honest reviews from fellow food lovers — at BGC Night Market.',
        'ctaText' => 'Discover Stalls',
        'ctaLink' => '#featured-stalls'
    ]);
    echo $heroSection->render();
    ?>

    <!-- Featured Brands Carousel -->
    <section class="brands-carousel">
        <div class="container">
            <p class="carousel-tagline">
                <span class="highlight-orange">From local favorites to</span> <span class="highlight-green">hidden gems</span>
                <span class="highlight-orange">— discover them here.</span>
            </p>

            <div class="carousel-wrapper">
                <button class="carousel-btn carousel-prev" aria-label="Previous">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="carousel-container">
                    <div class="carousel-track">
                        <?php 
                        // Display stall logos from database
                        $carouselStalls = $randomStalls; // Display all random stalls
                        if (!empty($carouselStalls)):
                            foreach ($carouselStalls as $stall): 
                        ?>
                        <a href="stall-detail.php?id=<?php echo $stall['id']; ?>" class="carousel-item" style="text-decoration: none; color: inherit; border: 1px solid rgba(0, 0, 0, 0.1); border-radius: 12px; padding: 20px; transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px;" onmouseover="this.style.borderColor='rgba(0, 0, 0, 0.2)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.1)';" onmouseout="this.style.borderColor='rgba(0, 0, 0, 0.1)'; this.style.boxShadow='none';">
                            <?php if (!empty($stall['image'])): ?>
                                <img src="<?php echo Helpers::escape($stall['image']); ?>" 
                                     alt="<?php echo Helpers::escape($stall['name']); ?>" 
                                     class="brand-logo-img" style="max-width: 120px; max-height: 120px; object-fit: contain; margin-bottom: 10px;">
                            <?php else: ?>
                                <img src="<?php echo IMAGES_URL; ?>star.svg" alt="Featured brand star" class="star-icon" style="max-width: 80px; max-height: 80px; margin-bottom: 10px;">
                            <?php endif; ?>
                            <span class="brand-logo" style="display: block; font-weight: 500; text-align: center; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo Helpers::escape($stall['name']); ?>"><?php echo Helpers::escape($stall['name']); ?></span>
                        </a>
                        <?php 
                            endforeach;
                        else:
                            // Fallback if no stalls
                            for ($i = 0; $i < 4; $i++): 
                        ?>
                        <div class="carousel-item">
                            <img src="<?php echo IMAGES_URL; ?>star.svg" alt="Featured brand star" class="star-icon">
                            <span class="brand-logo">Logo/brand</span>
                        </div>
                        <?php 
                            endfor;
                        endif;
                        ?>
                    </div>
                </div>

                <button class="carousel-btn carousel-next" aria-label="Next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="carousel-dots">
                <?php 
                $totalStalls = !empty($randomStalls) ? count($randomStalls) : 4;
                for ($i = 0; $i < $totalStalls; $i++): 
                ?>
                <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- Featured Stalls Section -->
    <?php
    $featuredStallsSection = new FeaturedStallsSection([
        'title' => '<span class="highlight-green">Explore</span> featured<br>stalls',
        'location' => 'Terra 28th, 28th St. corner 7th Ave. BGC',
        'stalls' => $featuredStalls
    ]);
    echo $featuredStallsSection->render();
    ?>

    <!-- Reviews Section -->
    <?php
    $reviewsSection = new ReviewsSection([
        'title' => 'See what foodies are <span class="highlight-green">raving</span> about...',
        'reviews' => $recentReviews
    ]);
    echo $reviewsSection->render();
    ?>

    <!-- Join BuzzarFeed CTA Section -->
    <section class="join-section">
        <div class="container">
            <h2 class="section-title centered">Join BuzzarFeed!</h2>

            <div class="join-cards">
                <!-- Food Enthusiast Card -->
                <article class="join-card join-card-enthusiast">
                    <h3 class="join-card-title">
                        Are you a...<br>
                        <span class="join-card-role">Food Enthusiast</span>
                    </h3>
                    <ul class="join-card-benefits">
                        <li><i class="fas fa-check"></i> Access to a variety food reviews</li>
                        <li><i class="fas fa-check"></i> Write and food review cards</li>
                        <li><i class="fas fa-check"></i> Engage with the foodies</li>
                        <li><i class="fas fa-check"></i> Discover hidden food stalls</li>
                    </ul>
                    <?php
                    echo (new Button([
                        'text' => 'Get started',
                        'href' => 'signup.php?type=user',
                        'variant' => Button::VARIANT_PRIMARY,
                        'class' => 'btn-join'
                    ]))->render();
                    ?>
                </article>

                <!-- Food Stall Owner Card -->
                <article class="join-card join-card-owner">
                    <h3 class="join-card-title">
                        Are you a...<br>
                        <span class="join-card-role">Food Stall Owner</span>
                    </h3>
                    <ul class="join-card-benefits">
                        <li><i class="fas fa-check"></i> Access to a variety food reviews</li>
                        <li><i class="fas fa-check"></i> Access to deal called stall pages</li>
                        <li><i class="fas fa-check"></i> Rate food stall operation</li>
                        <li><i class="fas fa-check"></i> Receive full time feature</li>
                    </ul>
                    <?php
                    echo (new Button([
                        'text' => 'Get started',
                        'href' => 'signup.php?type=owner',
                        'variant' => Button::VARIANT_PRIMARY,
                        'class' => 'btn-join'
                    ]))->render();
                    ?>
                </article>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <!-- JavaScript -->
    <script src="<?php echo JS_URL; ?>main.js"></script>
</body>
</html>
