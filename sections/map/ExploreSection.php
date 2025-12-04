<?php
// Explore stalls partial for the map page

use BuzzarFeed\Utils\Helpers;
?>
<hr class="section-divider">

<!-- Explore Stalls Section -->
<section class="explore-section">
    <h2 class="explore-title">Explore Food Stalls</h2>

    <?php if (empty($exploreStalls)): ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>No stalls available at the moment.</p>
        </div>
    <?php else: ?>
        <div class="explore-grid">
            <?php foreach ($exploreStalls as $stall): ?>
                <a href="stall-detail.php?id=<?= $stall['id'] ?>" class="stall-card">
                    <?php if (!empty($stall['image'])): ?>
                        <img src="<?= BASE_URL . Helpers::escape($stall['image']) ?>" alt="<?= Helpers::escape($stall['name']) ?>"
                            class="stall-image">
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
                                    echo '<i class="fas fa-star star"></i>';
                                }
                                if ($hasHalfStar) {
                                    echo '<i class="fas fa-star-half-alt star"></i>';
                                }
                                for ($i = $fullStars + ($hasHalfStar ? 1 : 0); $i < 5; $i++) {
                                    echo '<i class="far fa-star star empty"></i>';
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
