<?php
/**
 * BuzzarFeed - My Reviews Page
 *
 * Display all reviews written by the logged-in user
 *
 * @package BuzzarFeed
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;

// Start session
Session::start();

// Redirect if not logged in
if (!Session::isLoggedIn()) {
    Helpers::redirect('login.php');
}

// Get user data
$userId = Session::get('user_id');
$db = Database::getInstance();

// Pagination settings
$perPage = 5;
$currentPage = max(1, (int)Helpers::get('page', 1));

// Get total reviews count for this user
$totalReviewsRow = $db->querySingle(
    "SELECT COUNT(*) AS total FROM reviews WHERE user_id = ?",
    [$userId]
);
$totalReviews = (int)($totalReviewsRow['total'] ?? 0);
$totalPages = $totalReviews > 0 ? (int)ceil($totalReviews / $perPage) : 1;

// Clamp current page within valid range
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

// Fetch paginated reviews by the user with stall information and reaction counts
$reviews = $db->query(
    "SELECT 
        r.review_id,
        r.rating,
        r.comment,
        r.created_at,
        fs.stall_id,
        fs.name as stall_name,
        fs.logo_path as stall_logo,
        (SELECT COUNT(*) FROM review_reactions WHERE review_id = r.review_id AND reaction_type = 'like') as like_count,
        (SELECT COUNT(*) FROM review_reactions WHERE review_id = r.review_id AND reaction_type = 'dislike') as dislike_count
     FROM reviews r
     INNER JOIN food_stalls fs ON r.stall_id = fs.stall_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC
     LIMIT ? OFFSET ?",
    [$userId, $perPage, $offset]
);

$pageTitle = "My Reviews - BuzzarFeed";
$pageDescription = "View and manage all your food stall reviews";
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
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/dropdown.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/styles.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/my-reviews.css">
</head>
<body class="reviews-page">
    <!-- Header -->
    <?php require INCLUDES_PATH . '/header.php'; ?>

    <main class="reviews-container">
        <div class="reviews-header">
            <h1>My Reviews</h1>
            <div class="reviews-stats">
                <div class="stat-item">
                    <i class="fas fa-star"></i>
                    <span><strong><?= $totalReviews ?></strong> Total Reviews</span>
                </div>
                <?php if (count($reviews) > 0): 
                    $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
                    $totalLikes = array_sum(array_column($reviews, 'like_count'));
                    $totalDislikes = array_sum(array_column($reviews, 'dislike_count'));
                ?>
                <div class="stat-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Average Rating: <strong><?= number_format($avgRating, 1) ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-thumbs-up"></i>
                    <span><strong><?= $totalLikes ?></strong> Total Likes</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-comment-slash"></i>
                <h2>No Reviews Yet</h2>
                <p>You haven't written any reviews yet. Start exploring food stalls and share your experiences!</p>
                <a href="<?= BASE_URL ?>stalls.php" class="btn">
                    Explore Stalls
                </a>
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): 
                    $tally = $review['like_count'] - $review['dislike_count'];
                    $tallyClass = $tally > 0 ? '' : ($tally < 0 ? 'negative' : 'neutral');
                ?>
                    <div class="review-card">
                        <div class="review-card-header">
                            <?php if ($review['stall_logo']): ?>
                                <img src="<?= Helpers::escape($review['stall_logo']) ?>" 
                                     alt="<?= Helpers::escape($review['stall_name']) ?>" 
                                     class="stall-logo">
                            <?php else: ?>
                                <div class="stall-logo" style="background-color: var(--color-primary-beige); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-store" style="color: var(--color-text-light); font-size: 1.5rem;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="review-header-info">
                                <h3 class="stall-name">
                                    <a href="<?= BASE_URL ?>stall-detail.php?id=<?= $review['stall_id'] ?>">
                                        <?= Helpers::escape($review['stall_name']) ?>
                                    </a>
                                </h3>
                                <div class="review-meta">
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star star <?= $i <= $review['rating'] ? '' : 'text-muted' ?>" 
                                               style="<?= $i > $review['rating'] ? 'opacity: 0.3;' : '' ?>"></i>
                                        <?php endfor; ?>
                                        <span class="rating-value"><?= number_format($review['rating'], 1) ?></span>
                                    </div>
                                    <span>•</span>
                                    <div class="review-date">
                                        <i class="far fa-calendar"></i>
                                        <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="review-content">
                            <p class="review-text"><?= Helpers::escape($review['comment']) ?></p>
                        </div>

                        <div class="review-footer">
                            <div class="review-stats">
                                <div class="stat-badge likes">
                                    <i class="fas fa-thumbs-up"></i>
                                    <span class="count"><?= $review['like_count'] ?></span>
                                </div>
                                <div class="stat-badge dislikes">
                                    <i class="fas fa-thumbs-down"></i>
                                    <span class="count"><?= $review['dislike_count'] ?></span>
                                </div>
                            </div>
                            <div class="tally-score <?= $tallyClass ?>">
                                <i class="fas fa-chart-bar"></i>
                                <span>Tally: <?= $tally > 0 ? '+' : '' ?><?= $tally ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="reviews-pagination" aria-label="My reviews pagination">
                    <?php if ($currentPage > 1): ?>
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a class="page-link <?= $p === $currentPage ? 'active' : '' ?>" href="?page=<?= $p ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>">Next &raquo;</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php require INCLUDES_PATH . '/footer.php'; ?>

    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
