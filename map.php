<?php
/**
 * BuzzarFeed - Map Page
 * 
 * Interactive map showing all approved food stalls
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Services\StallService;

Session::start();

$pageTitle = "Stall Map - BuzzarFeed";
$pageDescription = "Find food stalls on the interactive map";

// Initialize service
$stallService = new StallService();

// Get filter parameters
$category = Helpers::get('category', '');

// Fetch stalls based on filters
if (!empty($category) && $category !== 'all') {
    $stalls = $stallService->getStallsByCategory($category);
} else {
    $stalls = $stallService->getAllActiveStalls();
}

// Define standard food categories
$standardCategories = ['Beverages', 'Street Food', 'Rice Meals', 'Fast Food', 'Snacks', 'Pastries', 'Others'];
$allCategories = $standardCategories;

// Filter stalls that have map coordinates
$stallsWithLocation = array_filter($stalls, function($stall) {
    return !empty($stall['latitude']) && !empty($stall['longitude']);
});

// Get nearby stalls (random 3 for now, can be enhanced with geolocation)
$nearbyStalls = array_slice($stalls, 0, 3);
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
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= CSS_URL ?>/variables.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/base.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/button.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/dropdown.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/styles.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/map.css">
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <h1>Where the Flavor Lives</h1>
            <p>Locate stalls, filter by food type, and never miss a hidden gem at "the bazaar"</p>
        </section>
        
        <!-- Category Filters -->
        <section class="filters-section">
            <div class="category-filters">
                <a href="?category=all" class="filter-btn <?= empty($category) || $category === 'all' ? 'active' : '' ?>">
                    <span class="filter-icon">🍽️</span>
                    All stalls
                </a>
                <?php 
                $categoryIcons = [
                    'Beverages' => '🥤',
                    'Rice Meals' => '🍛',
                    'Snacks' => '🍿',
                    'Street Food' => '🌮',
                    'Fast Food' => '🍔',
                    'Pastries' => '🥐',
                    'Others' => '🍴'
                ];
                foreach ($allCategories as $cat): 
                    if ($cat === 'All stalls') continue;
                    $icon = $categoryIcons[$cat] ?? '🍴';
                ?>
                    <a href="?category=<?= urlencode($cat) ?>" class="filter-btn <?= $category === $cat ? 'active' : '' ?>">
                        <span class="filter-icon"><?= $icon ?></span>
                        <?= Helpers::escape($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Map Section -->
        <section class="map-section">
            <div class="map-container-wrapper">
                <h2 class="map-title">Map</h2>
                <div class="map-container" id="mapContainer">
                    <img src="<?= IMAGES_URL ?>/maps.png" alt="BGC Night Market Map" class="map-image" id="mapImage">
                    
                    <?php foreach ($stallsWithLocation as $stall): ?>
                        <div class="map-pin" 
                             style="left: <?= $stall['latitude'] ?>%; top: <?= $stall['longitude'] ?>%;"
                             data-stall-id="<?= $stall['id'] ?>"
                             data-stall-name="<?= Helpers::escape($stall['name']) ?>"
                             data-stall-desc="<?= Helpers::escape(substr($stall['description'], 0, 100)) ?>"
                             data-stall-rating="<?= $stall['rating'] ?>"
                             data-stall-categories="<?= Helpers::escape(implode(', ', array_slice($stall['categories'], 0, 2))) ?>">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Stall Info Tooltip -->
                    <div class="stall-tooltip hidden" id="stallTooltip">
                        <h4 class="tooltip-name"></h4>
                        <div class="tooltip-categories"></div>
                        <div class="tooltip-rating"></div>
                        <p class="tooltip-desc"></p>
                        <button class="tooltip-view-btn" onclick="viewStall()">View Stall</button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Nearby Stalls Section -->
        <section class="nearby-section">
            <h2 class="nearby-title">Nearby Food Stalls</h2>
            
            <?php if (empty($nearbyStalls)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>No stalls available at the moment.</p>
                </div>
            <?php else: ?>
                <div class="nearby-grid">
                    <?php foreach ($nearbyStalls as $stall): ?>
                        <div class="stall-card" onclick="window.location.href='stall-detail.php?id=<?= $stall['id'] ?>'">
                            <?php if (!empty($stall['image'])): ?>
                                <img src="<?= BASE_URL . Helpers::escape($stall['image']) ?>" alt="<?= Helpers::escape($stall['name']) ?>" class="stall-image">
                            <?php else: ?>
                                <div class="stall-image-placeholder">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="stall-content">
                                <div class="stall-categories">
                                    <?php foreach (array_slice($stall['categories'], 0, 2) as $cat): ?>
                                        <span class="category-tag"><?= Helpers::escape($cat) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                
                                <h3 class="stall-name"><?= Helpers::escape($stall['name']) ?></h3>
                                
                                <div class="stall-rating">
                                    <div class="stars">
                                        <?php
                                        $fullStars = floor($stall['rating']);
                                        $hasHalfStar = ($stall['rating'] - $fullStars) >= 0.5;
                                        
                                        for ($i = 0; $i < $fullStars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($hasHalfStar) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }
                                        for ($i = $fullStars + ($hasHalfStar ? 1 : 0); $i < 5; $i++) {
                                            echo '<i class="far fa-star"></i>';
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
    <script>
        // Map pin hover functionality
        const mapPins = document.querySelectorAll('.map-pin');
        const tooltip = document.getElementById('stallTooltip');
        let currentStallId = null;
        
        mapPins.forEach(pin => {
            pin.addEventListener('mouseenter', function(e) {
                const stallName = this.dataset.stallName;
                const stallDesc = this.dataset.stallDesc;
                const stallRating = parseFloat(this.dataset.stallRating);
                const stallCategories = this.dataset.stallCategories;
                currentStallId = this.dataset.stallId;
                
                // Update tooltip content
                tooltip.querySelector('.tooltip-name').textContent = stallName;
                tooltip.querySelector('.tooltip-categories').innerHTML = 
                    stallCategories.split(', ').map(cat => 
                        `<span class="tooltip-cat">${cat}</span>`
                    ).join('');
                
                // Generate stars
                const fullStars = Math.floor(stallRating);
                const hasHalf = (stallRating - fullStars) >= 0.5;
                let starsHtml = '';
                for (let i = 0; i < fullStars; i++) {
                    starsHtml += '<i class="fas fa-star"></i>';
                }
                if (hasHalf) {
                    starsHtml += '<i class="fas fa-star-half-alt"></i>';
                }
                for (let i = fullStars + (hasHalf ? 1 : 0); i < 5; i++) {
                    starsHtml += '<i class="far fa-star"></i>';
                }
                
                tooltip.querySelector('.tooltip-rating').innerHTML = 
                    `<div class="tooltip-stars">${starsHtml}</div>` +
                    `<span>${stallRating > 0 ? stallRating.toFixed(1) : 'No ratings'}</span>`;
                
                tooltip.querySelector('.tooltip-desc').textContent = stallDesc + '...';
                
                // Position tooltip near the pin
                const rect = this.getBoundingClientRect();
                const containerRect = document.getElementById('mapContainer').getBoundingClientRect();
                
                tooltip.style.left = (rect.left - containerRect.left + rect.width / 2) + 'px';
                tooltip.style.top = (rect.top - containerRect.top - 10) + 'px';
                tooltip.classList.remove('hidden');
            });
            
            pin.addEventListener('mouseleave', function() {
                // Delay hiding to allow moving to tooltip
                setTimeout(() => {
                    if (!tooltip.matches(':hover')) {
                        tooltip.classList.add('hidden');
                    }
                }, 100);
            });
        });
        
        tooltip.addEventListener('mouseleave', function() {
            this.classList.add('hidden');
        });
        
        function viewStall() {
            if (currentStallId) {
                window.location.href = `stall-detail.php?id=${currentStallId}`;
            }
        }
    </script>
</body>
</html>
