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
$stallsWithLocation = array_filter($stalls, function ($stall) {
    return !empty($stall['latitude']) && !empty($stall['longitude']);
});

// Get explore stalls (random 3 for now, can be enhanced with geolocation)
$exploreStalls = array_slice($stalls, 0, 3);
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
        <?php include __DIR__ . '/sections/map/MapSection.php'; ?>
        <?php include __DIR__ . '/sections/map/ExploreSection.php'; ?>

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
                pin.addEventListener('mouseenter', function (e) {
                    const stallName = this.dataset.stallName;
                    const stallDesc = this.dataset.stallDesc;
                    const stallRating = parseFloat(this.dataset.stallRating);
                    const stallCategories = this.dataset.stallCategories;
                    currentStallId = this.dataset.stallId;

                    // Update tooltip content
                    tooltip.querySelector('.tooltip-name').textContent = stallName;
                    tooltip.querySelector('.tooltip-categories').innerHTML =
                        stallCategories.split(', ').map(cat =>
                            `<span class=\"tooltip-cat\">${cat}</span>`
                        ).join('');

                    // Generate stars
                    const fullStars = Math.floor(stallRating);
                    const hasHalf = (stallRating - fullStars) >= 0.5;
                    let starsHtml = '';
                    for (let i = 0; i < fullStars; i++) {
                        starsHtml += '<i class=\"fas fa-star\"></i>';
                    }
                    if (hasHalf) {
                        starsHtml += '<i class=\"fas fa-star-half-alt\"></i>';
                    }
                    for (let i = fullStars + (hasHalf ? 1 : 0); i < 5; i++) {
                        starsHtml += '<i class=\"far fa-star\"></i>';
                    }

                    tooltip.querySelector('.tooltip-rating').innerHTML =
                        `<div class=\"tooltip-stars\">${starsHtml}</div>` +
                        `<span>${stallRating > 0 ? stallRating.toFixed(1) : 'No ratings'}</span>`;

                    tooltip.querySelector('.tooltip-desc').textContent = stallDesc + '...';

                    // Smart positioning: always show below if any part would be outside the top edge
                    const rect = this.getBoundingClientRect();
                    const containerRect = document.getElementById('mapContainer').getBoundingClientRect();
                    // Temporarily show tooltip to get accurate dimensions
                    tooltip.classList.remove('hidden');
                    tooltip.style.visibility = 'hidden';
                    const tooltipHeight = tooltip.offsetHeight || 120;
                    const tooltipWidth = tooltip.offsetWidth || 300;
                    tooltip.style.visibility = '';
                    tooltip.classList.add('hidden');

                    let left = (rect.left - containerRect.left + rect.width / 2);
                    let topAbove = rect.top - containerRect.top - 10;
                    let topBelow = rect.bottom - containerRect.top + 10;

                    let showAbove = true;
                    // If tooltip would overflow top edge, show below
                    if (topAbove - tooltipHeight < 0) {
                        showAbove = false;
                    }

                    // Default transform and margin
                    let transform = showAbove ? 'translate(-50%, -100%)' : 'translate(-50%, 10px)';
                    let marginTop = showAbove ? '-15px' : '0';
                    let top = showAbove ? topAbove : topBelow;

                    // Check left/right overflow and adjust
                    let adjustedLeft = left;
                    const minLeft = tooltipWidth / 2 + 8; // 8px padding
                    const maxLeft = containerRect.width - tooltipWidth / 2 - 8;
                    if (left < minLeft) {
                        adjustedLeft = minLeft;
                        // Only shift vertically, keep horizontal close to pin
                        transform = showAbove ? 'translate(0, -100%)' : 'translate(0, 0)';
                        // Reduce gap for left edge
                        topAbove = rect.top - containerRect.top - 2;
                        topBelow = rect.bottom - containerRect.top + 2;
                        top = showAbove ? topAbove : topBelow;
                    } else if (left > maxLeft) {
                        adjustedLeft = maxLeft;
                        transform = showAbove ? 'translate(-100%, -100%)' : 'translate(-100%, 0)';
                        // Reduce gap for right edge
                        topAbove = rect.top - containerRect.top - 2;
                        topBelow = rect.bottom - containerRect.top + 2;
                        top = showAbove ? topAbove : topBelow;
                    }

                    tooltip.style.transform = transform;
                    tooltip.style.marginTop = marginTop;
                    tooltip.style.left = adjustedLeft + 'px';
                    tooltip.style.top = top + 'px';
                    tooltip.classList.remove('hidden');
                });

                pin.addEventListener('mouseleave', function () {
                    // Delay hiding to allow moving to tooltip
                    setTimeout(() => {
                        if (!tooltip.matches(':hover')) {
                            tooltip.classList.add('hidden');
                        }
                    }, 100);
                });
            });

            tooltip.addEventListener('mouseleave', function () {
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