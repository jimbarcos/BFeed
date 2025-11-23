<?php
/**
 * BuzzarFeed - Stalls Page
 * 
 * Browse all food stalls at BGC Night Market Bazaar
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Services\StallService;

Session::start();

$pageTitle = "Browse Food Stalls - BuzzarFeed";
$pageDescription = "Discover amazing food stalls at BGC Night Market Bazaar";

// Initialize service
$stallService = new StallService();

// Get filter parameters
$searchTerm = Helpers::get('search', '');
$category = Helpers::get('category', '');

// Fetch stalls based on filters
if (!empty($searchTerm)) {
    $stalls = $stallService->searchStalls($searchTerm);
} elseif (!empty($category) && $category !== 'all') {
    $stalls = $stallService->getStallsByCategory($category);
} else {
    $stalls = $stallService->getAllActiveStalls();
}

// Define standard food categories
$standardCategories = ['Beverages', 'Street Food', 'Rice Meals', 'Fast Food', 'Snacks', 'Pastries', 'Others'];

// Get all categories from database
$dbCategories = $stallService->getAllCategories();

// Always show all standard categories regardless of what's in DB
$allCategories = $standardCategories;
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
    <link rel="stylesheet" href="<?= CSS_URL ?>/stalls.css">
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <!-- Hero Section with Search -->
        <section class="hero-section">
            <h1>Start Your Food Hunt Here</h1>
            
            <form action="stalls.php" method="GET" class="search-bar-container">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search the Buzz..." value="<?= Helpers::escape($searchTerm) ?>">
                </div>
                <button type="submit" class="filter-btn">
                </button>
            </form>
            
            <div class="category-filters">
                <a href="?category=all" class="category-btn <?= empty($category) || $category === 'all' ? 'active' : '' ?>">All Stalls</a>
                <?php foreach ($allCategories as $cat): ?>
                    <a href="?category=<?= urlencode($cat) ?>" class="category-btn <?= $category === $cat ? 'active' : '' ?>">
                        <?= Helpers::escape($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Stalls Grid -->
        <section class="stalls-container">
            <?php if (empty($stalls)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Stalls Found</h3>
                    <p>Try adjusting your search or browse all stalls.</p>
                </div>
            <?php else: ?>
                <div class="stalls-grid">
                    <?php foreach ($stalls as $stall): ?>
                        <a href="stall-detail.php?id=<?= $stall['id'] ?>" class="stall-card">
                            <?php if (!empty($stall['image'])): ?>
                                <img src="<?= BASE_URL . Helpers::escape($stall['image']) ?>" alt="<?= Helpers::escape($stall['name']) ?>" class="stall-image">
                            <?php else: ?>
                                <div class="stall-image-placeholder">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="stall-content">
                                <?php if (!empty($stall['categories']) && is_array($stall['categories'])): ?>
                                    <div class="stall-categories">
                                        <?php foreach ($stall['categories'] as $category): ?>
                                            <span class="category-tag"><?= Helpers::escape($category) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <h3 class="stall-name"><?= Helpers::escape($stall['name']) ?></h3>
                                
                                <div class="stall-rating">
                                    <div class="stars">
                                        <?php
                                        $fullStars = floor($stall['rating']);
                                        $hasHalfStar = ($stall['rating'] - $fullStars) >= 0.5;
                                        
                                        for ($i = 0; $i < $fullStars; $i++) {
                                            echo '<i class="fas fa-star star"></i>';
                                        }
                                        if ($hasHalfStar) {
                                            echo '<i class="fas fa-star-half-alt star"></i>';
                                        }
                                        for ($i = $fullStars + ($hasHalfStar ? 1 : 0); $i < 5; $i++) {
                                            echo '<i class="far fa-star star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-text">
                                        <?= $stall['rating'] > 0 ? number_format($stall['rating'], 1) : 'No ratings' ?>
                                        <?php if ($stall['reviews'] > 0): ?>
                                            (<?= $stall['reviews'] ?> Reviews)
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="stall-hours">
                                    <i class="far fa-clock"></i>
                                    <span><?= Helpers::escape($stall['hours']) ?></span>
                                </div>
                                
                                <p class="stall-description">
                                    <?= Helpers::escape($stall['description']) ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>