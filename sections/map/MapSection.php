<?php
// Map page sections partial

use BuzzarFeed\Utils\Helpers;

$categoryColors = [
    'Beverages' => '#dd452a',
    'Rice Meals' => '#ffae1b',
    'Snacks' => '#ffbda8',
    'Street Food' => '#c6b2e5',
    'Fast Food' => '#4caf50',
    'Pastries' => '#fee717',
    'Others' => '#9e9e9e',
];

// Default color if no match found
$defaultPinColor = '#ed6027';
?>
<main>
    <!-- Hero Section -->
    <section class="hero-section">
        <h1>Where the Flavor Lives</h1>
        <p>Locate stalls, filter by food type, and never miss a hidden gem at "the bazaar"</p>
    </section>

    <!-- Category Filters -->
    <section class="filters-section" id="map-view">
        <div class="category-filters">
            <a href="?category=all#map-view"
                class="filter-btn <?= empty($category) || $category === 'all' ? 'active' : '' ?>">
                <span class="filter-icon">🍽️</span>
                All stalls
            </a>
            <?php
            $categoryIcons = [
                'All Stalls' => '🍽️',
                'Beverages' => '🥤',
                'Rice Meals' => '🍛',
                'Snacks' => '🍿',
                'Street Food' => '🌮',
                'Fast Food' => '🍔',
                'Pastries' => '🥐',
                'Others' => '🍴'
            ];
            foreach ($allCategories as $cat):
                if ($cat === 'All stalls')
                    continue;
                $icon = $categoryIcons[$cat] ?? '🍴';
                ?>
                <a href="?category=<?= urlencode($cat) ?>#map-view"
                    class="filter-btn <?= $category === $cat ? 'active' : '' ?>">
                    <span class="filter-icon"><?= $icon ?></span>
                    <?= Helpers::escape($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="map-section">
        <div class="map-container-wrapper">
            <h2 class="map-title">Map</h2>
            <div class="map-container" id="mapContainer">
                <img src="<?= IMAGES_URL ?>/maps.png" alt="BGC Night Market Map" class="map-image" id="mapImage">

                <?php foreach ($stallsWithLocation as $stall): ?>
                    <?php
                    // 3. DETERMINE PIN COLOR
                    // Default to the first category of the stall
                    $displayCat = $stall['categories'][0] ?? 'Others';

                    // If a specific filter is active and the stall belongs to it, force that color
                    // (This ensures that if you filter by "Snacks", the pin turns the "Snacks" color)
                    if (!empty($category) && $category !== 'all' && in_array($category, $stall['categories'])) {
                        $displayCat = $category;
                    }

                    // Get the hex code
                    $pinColor = $categoryColors[$displayCat] ?? $defaultPinColor;
                    ?>

                    <div class="map-pin"
                        style="left: <?= $stall['latitude'] ?>%; top: <?= $stall['longitude'] ?>%; color: <?= $pinColor ?>;"
                        data-stall-id="<?= $stall['id'] ?>" data-stall-name="<?= Helpers::escape($stall['name']) ?>"
                        data-stall-desc="<?= Helpers::escape(substr($stall['description'], 0, 100)) ?>"
                        data-stall-rating="<?= $stall['rating'] ?>"
                        data-stall-categories="<?= Helpers::escape(implode(', ', array_slice($stall['categories'], 0, 2))) ?>">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                <?php endforeach; ?>

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
</main>