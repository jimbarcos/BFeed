<?php
/**
 * BuzzarFeed - Stall Detail Page
 * 
 * View stall information, menu items, and reviews
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;
use BuzzarFeed\Services\ReviewReportService;

Session::start();

$reportService = new ReviewReportService();

$db = Database::getInstance();
$stallId = Helpers::get('id');

if (!$stallId) {
    Helpers::redirect('stalls.php');
    exit;
}

// Fetch stall details with location and owner name
$stall = $db->querySingle(
    "SELECT fs.*, sl.address, sl.latitude, sl.longitude, u.name as owner_name
     FROM food_stalls fs
     LEFT JOIN stall_locations sl ON fs.stall_id = sl.stall_id
     LEFT JOIN users u ON fs.owner_id = u.user_id
     WHERE fs.stall_id = ? AND fs.is_active = 1",
    [$stallId]
);

if (!$stall) {
    Session::setFlash('Stall not found.', 'error');
    Helpers::redirect('stalls.php');
    exit;
}

// Decode food categories
$categories = !empty($stall['food_categories']) ? json_decode($stall['food_categories'], true) : [];

// Fetch menu items
$menuItems = $db->query(
    "SELECT * FROM menu_items WHERE stall_id = ? AND is_available = 1 ORDER BY category_id, name",
    [$stallId]
);

// Group menu items by category
$groupedMenuItems = [];
foreach ($menuItems as $item) {
    $categoryName = $item['category_id'] ? 'Category ' . $item['category_id'] : 'Uncategorized';
    if (!isset($groupedMenuItems[$categoryName])) {
        $groupedMenuItems[$categoryName] = [];
    }
    $groupedMenuItems[$categoryName][] = $item;
}

// Get filter and sort parameters
$filterRating = Helpers::get('rating', '');
$sortBy = Helpers::get('sort', 'newest');

// Build WHERE clause for rating filter
$whereClause = "r.stall_id = ?";
$params = [$stallId];

if (!empty($filterRating) && $filterRating !== 'all') {
    $whereClause .= " AND r.rating = ?";
    $params[] = $filterRating;
}

// Build ORDER BY clause for sorting
$orderClause = match($sortBy) {
    'oldest' => 'r.created_at ASC',
    'highest' => 'r.rating DESC, r.created_at DESC',
    'lowest' => 'r.rating ASC, r.created_at DESC',
    'most_liked' => 'like_count DESC, r.created_at DESC',
    'most_disliked' => 'dislike_count DESC, r.created_at DESC',
    default => 'r.created_at DESC' // newest
};

// Fetch reviews with user information and reaction counts
$reviews = $db->query(
    "SELECT r.*, u.name as username, u.profile_image,
     (SELECT COUNT(*) FROM review_reactions WHERE review_id = r.review_id AND reaction_type = 'like') as like_count,
     (SELECT COUNT(*) FROM review_reactions WHERE review_id = r.review_id AND reaction_type = 'dislike') as dislike_count
     FROM reviews r
     LEFT JOIN users u ON r.user_id = u.user_id
     WHERE $whereClause
     ORDER BY $orderClause",
    $params
);

// Get user's reactions if logged in
$userReactions = [];
if (Session::isLoggedIn()) {
    $userReactionsData = $db->query(
        "SELECT review_id, reaction_type FROM review_reactions WHERE user_id = ?",
        [Session::get('user_id')]
    );
    foreach ($userReactionsData as $reaction) {
        $userReactions[$reaction['review_id']] = $reaction['reaction_type'];
    }
}

// Calculate rating statistics
$ratingStats = $db->querySingle(
    "SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as average_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
     FROM reviews WHERE stall_id = ?",
    [$stallId]
);

$totalReviews = $ratingStats['total_reviews'] ?? 0;
$averageRating = $totalReviews > 0 ? round($ratingStats['average_rating'], 1) : 0;

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = Helpers::post('action');
    
    if ($action === 'submit_review') {
        if (!Session::isLoggedIn()) {
            Session::setFlash('Please log in to write a review.', 'error');
        } elseif (Session::get('user_id') == $stall['owner_id']) {
            Session::setFlash('You cannot review your own stall.', 'error');
        } else {
            $rating = Helpers::post('rating');
            $title = Helpers::post('review_title');
            $comment = Helpers::post('review_comment');
            $anonymous = Helpers::post('anonymous') === 'on';
            
            // Validate rating
            if (empty($rating) || $rating < 1 || $rating > 5) {
                Session::setFlash('Please select a rating between 1 and 5 stars.', 'error');
                Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
                exit;
            }
            
            try {
                $db->execute(
                    "INSERT INTO reviews (stall_id, user_id, rating, title, comment, is_anonymous, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    [$stallId, Session::get('user_id'), $rating, $title, $comment, $anonymous ? 1 : 0]
                );
                
                Session::setFlash('Review submitted successfully!', 'success');
                Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
                exit;
            } catch (\Exception $e) {
                $error = "Error submitting review: " . $e->getMessage();
                Session::setFlash($error, 'error');
                Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
                exit;
            }
        }
    }
    
    if ($action === 'update_review') {
        if (!Session::isLoggedIn()) {
            Session::setFlash('Please log in to update your review.', 'error');
        } else {
            $reviewId = Helpers::post('review_id');
            $rating = Helpers::post('rating');
            $title = Helpers::post('review_title');
            $comment = Helpers::post('review_comment');
            $anonymous = Helpers::post('anonymous') === 'on';
            
            try {
                $db->execute(
                    "UPDATE reviews SET rating = ?, title = ?, comment = ?, is_anonymous = ?, updated_at = NOW()
                     WHERE review_id = ? AND user_id = ?",
                    [$rating, $title, $comment, $anonymous ? 1 : 0, $reviewId, Session::get('user_id')]
                );
                
                Session::setFlash('Review updated successfully!', 'success');
                Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
                exit;
            } catch (\Exception $e) {
                Session::setFlash('Error updating review: ' . $e->getMessage(), 'error');
                Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
                exit;
            }
        }
    }
    
    if ($action === 'delete_review') {
        if (!Session::isLoggedIn()) {
            Session::setFlash('Please log in to delete your review.', 'error');
        } else {
            $reviewId = Helpers::post('review_id');
            
            try {
                $db->execute(
                    "DELETE FROM reviews WHERE review_id = ? AND user_id = ?",
                    [$reviewId, Session::get('user_id')]
                );
                
                Session::setFlash('Review deleted successfully!', 'success');
                Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
                exit;
            } catch (\Exception $e) {
                Session::setFlash('Error deleting review: ' . $e->getMessage(), 'error');
                Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
                exit;
            }
        }
    }
    
    if ($action === 'react_review') {
        if (!Session::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please log in to react']);
            exit;
        }
        
        $reviewId = Helpers::post('review_id');
        $reactionType = Helpers::post('reaction_type');
        
        try {
            // Check if user already reacted
            $existingReaction = $db->querySingle(
                "SELECT * FROM review_reactions WHERE review_id = ? AND user_id = ?",
                [$reviewId, Session::get('user_id')]
            );
            
            if ($existingReaction) {
                if ($existingReaction['reaction_type'] === $reactionType) {
                    // Remove reaction if same type
                    $db->execute(
                        "DELETE FROM review_reactions WHERE review_id = ? AND user_id = ?",
                        [$reviewId, Session::get('user_id')]
                    );
                } else {
                    // Update to new reaction type
                    $db->execute(
                        "UPDATE review_reactions SET reaction_type = ? WHERE review_id = ? AND user_id = ?",
                        [$reactionType, $reviewId, Session::get('user_id')]
                    );
                }
            } else {
                // Add new reaction
                $db->execute(
                    "INSERT INTO review_reactions (review_id, user_id, reaction_type) VALUES (?, ?, ?)",
                    [$reviewId, Session::get('user_id'), $reactionType]
                );
            }
            
            // Get updated counts
            $counts = $db->querySingle(
                "SELECT 
                    (SELECT COUNT(*) FROM review_reactions WHERE review_id = ? AND reaction_type = 'like') as like_count,
                    (SELECT COUNT(*) FROM review_reactions WHERE review_id = ? AND reaction_type = 'dislike') as dislike_count",
                [$reviewId, $reviewId]
            );
            
            echo json_encode([
                'success' => true,
                'like_count' => $counts['like_count'],
                'dislike_count' => $counts['dislike_count']
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($action === 'report_review') {
        if (!Session::isLoggedIn()) {
            Session::setFlash('Please log in to report reviews.', 'error');
            Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
            exit;
        }
        
        $reviewId = Helpers::post('review_id');
        $reportReason = Helpers::post('report_reason');
        $customReason = Helpers::post('custom_reason');
        
        try {
            // Check if user is trying to report their own review
            $review = $db->querySingle(
                "SELECT user_id FROM reviews WHERE review_id = ?",
                [$reviewId]
            );
            
            if ($review && $review['user_id'] == Session::get('user_id')) {
                Session::setFlash('You cannot report your own review.', 'error');
                Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
                exit;
            }
            
            $reportService->reportReview(
                $reviewId,
                Session::get('user_id'),
                $reportReason,
                $customReason
            );
            
            Session::setFlash('Thank you for reporting! Our moderation team will review this shortly.', 'success');
            Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
            exit;
        } catch (\Exception $e) {
            if ($e->getMessage() === 'PENDING_REPORT') {
                Session::setFlash('This review is already under review by our moderation team.', 'info');
            } else {
                Session::setFlash('Error: ' . $e->getMessage(), 'error');
            }
            Helpers::redirect('stall-detail.php?id=' . $stallId . '&tab=reviews');
            exit;
        }
    }
}

$pageTitle = Helpers::escape($stall['name']) . " - BuzzarFeed";
$pageDescription = Helpers::escape($stall['description']);
$currentTab = Helpers::get('tab', 'menu');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $pageDescription ?>">
    <title><?= $pageTitle ?></title>
    
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
    <link rel="stylesheet" href="<?= CSS_URL ?>/stall-detail.css">
</head>
<body style="background-color: #d65520;">
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <!-- Stall Header -->
        <section class="stall-header">
            <div class="container">
                <!-- Back Button inside header -->
                <a href="stalls.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Stalls
                </a>
                <div class="stall-header-content">
                    <div class="stall-logo">
                        <?php if (!empty($stall['logo_path'])): ?>
                            <img src="<?= BASE_URL . Helpers::escape($stall['logo_path']) ?>" alt="<?= Helpers::escape($stall['name']) ?>">
                        <?php else: ?>
                            <div class="no-logo"><i class="fas fa-store"></i></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stall-info">
                        <h1><?= Helpers::escape($stall['name']) ?></h1>
                        <p class="stall-description"><?= Helpers::escape($stall['description']) ?></p>
                        
                        <div class="stall-categories-list">
                            <?php foreach ($categories as $category): ?>
                                <span class="category-badge"><?= Helpers::escape($category) ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="stall-meta">
                                <div class="meta-item">
                                    <i class="far fa-clock"></i>
                                    <span class="meta-label">Operating Hours:</span>
                                    <span class="meta-value"><?= Helpers::escape($stall['hours'] ?? '9:00 AM - 10:00 PM') ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <span class="meta-label">Owner:</span>
                                     <span class="meta-value"><?= Helpers::escape($stall['owner_name'] ?? $stall['name'] ?? 'Unknown') ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span class="meta-label">Located at:</span>
                                    <span class="meta-value"><?= Helpers::escape($stall['address'] ?? 'BGC Night Market') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        


                    
    
        <!-- Tab Content -->
        <section class="tab-content-section">
            <div class="container">
                <?php if (Session::get('flash_message')): ?>
                    <?php
                    $flashMessage = Session::getFlash();
                    $flashType = Session::get('flash_type', 'success');
                    ?>
                    <div class="flash-message flash-<?= $flashType ?>">
                        <?= Helpers::escape(is_array($flashMessage) ? $flashMessage['message'] ?? '' : $flashMessage) ?>
                    </div>
                <?php endif; ?>

                <div class="content-card">
                    <div class="tabs">
                        <a href="?id=<?= $stallId ?>&tab=menu" 
                        class="tab-btn <?= $currentTab === 'menu' ? 'active' : '' ?>">
                            Menu Items
                        </a>

                        <a href="?id=<?= $stallId ?>&tab=reviews" 
                        class="tab-btn <?= $currentTab === 'reviews' ? 'active' : '' ?>">
                            Reviews
                        </a>
                    </div>

            <!-- Tab Content Area -->
            <?php if ($currentTab === 'menu'): ?>

                <!-- Menu Items Tab -->
                <?php if (empty($menuItems)): ?>
                    <div class="no-menu-items">
                        <i class="fas fa-utensils"></i>
                        <h3>No Menu Items Available</h3>
                        <p>This stall hasn't added any menu items yet.</p>
                    </div>
                <?php else: ?>

                    <?php 
                    $itemsByCategory = [];
                    foreach ($menuItems as $item) {
                        $cat = 'Uncategorized';
                        $itemsByCategory[$cat][] = $item;
                    }
                    
                    foreach ($itemsByCategory as $categoryName => $items): 
                    ?>
                        <div class="menu-category-section">
                            <h2 class="category-title"><?= Helpers::escape($categoryName) ?></h2>
                            <div class="menu-items-grid">
                                <?php foreach ($items as $item): ?>
                                    <div class="menu-item-card">
                                        <div class="menu-item-image">
                                            <?php if (!empty($item['image_path'])): ?>
                                                <img src="<?= BASE_URL . Helpers::escape($item['image_path']) ?>" alt="<?= Helpers::escape($item['name']) ?>">
                                            <?php else: ?>
                                                <div class="no-image"><i class="fas fa-image"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="menu-item-info">
                                            <h4><?= Helpers::escape($item['name']) ?></h4>
                                            <?php if (!empty($item['description'])): ?>
                                                <p class="item-desc"><?= Helpers::escape($item['description']) ?></p>
                                            <?php endif; ?>
                                            <p class="item-price">₱<?= number_format($item['price'], 2) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

                 <?php else: ?>
                    <!-- Reviews Tab -->
                        <h2 class="content-title">Reviews & Ratings</h2>
                        
                        <!-- Rating Summary -->
                        <div class="reviews-summary">
                            <div class="rating-bars">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <?php $count = $ratingStats[Helpers::numberToWord($i) . '_star'] ?? 0; ?>
                                    <div class="rating-bar-row">
                                        <span class="rating-label"><?= strtoupper(Helpers::numberToWord($i)) ?></span>
                                        <i class="fas fa-star star-icon"></i>
                                        <div class="rating-bar-bg">
                                            <div class="rating-bar-fill" style="width: <?= $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0 ?>%"></div>
                                        </div>
                                        <span class="rating-count"><?= $count ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="average-rating-box">
                                <div class="avg-rating-number"><?= $averageRating ?></div>
                                <div class="avg-rating-stars">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $averageRating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i - 0.5 <= $averageRating) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="total-ratings"><?= $totalReviews ?> Ratings</div>
                            </div>
                        </div>
        
                        
                        <!-- Filters and Sort -->
                        <div class="review-controls">
                        <div class="review-filters">
                            <div class="filter-group">
                                <select id="ratingFilter" onchange="applyFilters()">
                                    <option value="all" <?= $filterRating === '' || $filterRating === 'all' ? 'selected' : '' ?>>All Ratings</option>
                                    <option value="5" <?= $filterRating === '5' ? 'selected' : '' ?>>★★★★★ (5)</option>
                                    <option value="4" <?= $filterRating === '4' ? 'selected' : '' ?>>★★★★ (4)</option>
                                    <option value="3" <?= $filterRating === '3' ? 'selected' : '' ?>>★★★ (3)</option>
                                    <option value="2" <?= $filterRating === '2' ? 'selected' : '' ?>>★★ (2)</option>
                                    <option value="1" <?= $filterRating === '1' ? 'selected' : '' ?>>★ (1)</option>
                                </select>
                                <i class="fas fa-chevron-down select-arrow"></i>
                            </div>

                            <div class="filter-group">
                                <select id="sortFilter" onchange="applyFilters()">
                                    <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                    <option value="highest" <?= $sortBy === 'highest' ? 'selected' : '' ?>>Highest Rating</option>
                                    <option value="lowest" <?= $sortBy === 'lowest' ? 'selected' : '' ?>>Lowest Rating</option>
                                    <option value="most_liked" <?= $sortBy === 'most_liked' ? 'selected' : '' ?>>Most Liked</option>
                                    <option value="most_disliked" <?= $sortBy === 'most_disliked' ? 'selected' : '' ?>>Most Disliked</option>
                                </select>
                                <i class="fas fa-chevron-down select-arrow"></i>
                            </div>
                        </div>
                        
                        <!-- Write review button -->
                        <?php if (Session::isLoggedIn() && Session::get('user_id') != $stall['owner_id']): ?>
                            <button class="btn-write-review" onclick="openReviewModal()">
                                <i class="fas fa-pen"></i> Write a Review
                            </button>
                        <?php endif; ?>
                    </div>

                        
                        <!-- Reviews List -->
                        <div class="reviews-list">
                            <?php if (empty($reviews)): ?>
                                <div class="no-reviews">
                                    <i class="fas fa-comments"></i>
                                    <?php if (Session::isLoggedIn()): ?>
                                        <p>No reviews yet. Be the first to review!</p>
                                    <?php else: ?>
                                        <p>Sign Up or Log in to tell your ultimate experience</p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-card">
                                        <div class="review-header">
                                            <div class="review-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $review['rating'] ? '' : 'empty' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="review-actions-header">
                                                <span class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                                                <?php if (Session::isLoggedIn() && Session::get('user_id') == $review['user_id']): ?>
                                                    <div class="review-owner-actions">
                                                        <button class="btn-icon" onclick="openEditReviewModal(<?= $review['review_id'] ?>, <?= $review['rating'] ?>, '<?= htmlspecialchars($review['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($review['comment'], ENT_QUOTES) ?>', <?= $review['is_anonymous'] ?>)" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon btn-danger" onclick="openDeleteReviewModal(<?= $review['review_id'] ?>)" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php elseif (Session::isLoggedIn()): ?>
                                                    <button class="btn-icon btn-report" onclick="openReportModal(<?= $review['review_id'] ?>)" title="Report Review">
                                                        <i class="fas fa-flag"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($review['title'])): ?>
                                            <h4 class="review-title"><?= Helpers::escape($review['title']) ?></h4>
                                        <?php endif; ?>
                                        <div class="review-author">
                                            <i class="fas fa-user-circle"></i>
                                            <?= $review['is_anonymous'] ? 'Anonymous' : Helpers::escape($review['username']) ?>
                                        </div>
                                        <p class="review-comment"><?= Helpers::escape($review['comment']) ?></p>
                                        
                                        <!-- Like/Dislike Buttons -->
                                        <div class="review-reactions">
                                            <button class="reaction-btn like-btn <?= isset($userReactions[$review['review_id']]) && $userReactions[$review['review_id']] === 'like' ? 'active' : '' ?>" 
                                                    onclick="reactToReview(<?= $review['review_id'] ?>, 'like')" 
                                                    data-review-id="<?= $review['review_id'] ?>" 
                                                    data-reaction="like">
                                                <i class="fas fa-thumbs-up"></i>
                                                <span class="like-count"><?= $review['like_count'] ?></span>
                                            </button>
                                            <button class="reaction-btn dislike-btn <?= isset($userReactions[$review['review_id']]) && $userReactions[$review['review_id']] === 'dislike' ? 'active' : '' ?>" 
                                                    onclick="reactToReview(<?= $review['review_id'] ?>, 'dislike')" 
                                                    data-review-id="<?= $review['review_id'] ?>" 
                                                    data-reaction="dislike">
                                                <i class="fas fa-thumbs-down"></i>
                                                <span class="dislike-count"><?= $review['dislike_count'] ?></span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-pen"></i> Leave a Review</h3>
                <span class="modal-close" onclick="closeReviewModal()">&times;</span>
            </div>
            <form method="POST" id="reviewForm">
                <input type="hidden" name="action" value="submit_review">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Rating (5/5)</label>
                        <div class="star-rating-input">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="rating" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="review_title" class="form-label">Review Title</label>
                        <input type="text" id="review_title" name="review_title" class="form-input" 
                               placeholder="Summarize your experience" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="review_comment" class="form-label">Review Details</label>
                        <textarea id="review_comment" name="review_comment" class="form-textarea" 
                                  rows="4" placeholder="Write the details of your experience here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="anonymous">
                            Post anonymously
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeReviewModal()">Cancel</button>
                    <button type="submit" class="btn-submit-review">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Login Required Modal -->
    <div id="loginRequiredModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-body text-center">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>Login Required</h3>
                <p>Please sign up or log in to write a review.</p>
                <div class="modal-actions">
                    <a href="signup.php" class="btn-primary">Sign Up</a>
                    <a href="login.php" class="btn-secondary">Log In</a>
                </div>
                <button class="btn-text" onclick="closeLoginModal()">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Edit Review Modal -->
    <div id="editReviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Your Review</h3>
                <span class="modal-close" onclick="closeEditReviewModal()">&times;</span>
            </div>
            <form method="POST" id="editReviewForm">
                <input type="hidden" name="action" value="update_review">
                <input type="hidden" name="review_id" id="edit_review_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Rating (5/5)</label>
                        <div class="star-rating-input" id="edit-star-rating">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="edit_rating" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_review_title" class="form-label">Review Title</label>
                        <input type="text" id="edit_review_title" name="review_title" class="form-input" 
                               placeholder="Summarize your experience" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_review_comment" class="form-label">Review Details</label>
                        <textarea id="edit_review_comment" name="review_comment" class="form-textarea" 
                                  rows="4" placeholder="Write the details of your experience here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="anonymous" id="edit_anonymous">
                            Post anonymously
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditReviewModal()">Cancel</button>
                    <button type="submit" class="btn-submit-review">Update Review</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Review Modal -->
    <div id="deleteReviewModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h3>Delete Review</h3>
                <span class="modal-close" onclick="closeDeleteReviewModal()">&times;</span>
            </div>
            <form method="POST" id="deleteReviewForm">
                <input type="hidden" name="action" value="delete_review">
                <input type="hidden" name="review_id" id="delete_review_id">
                
                <div class="modal-body">
                    <div class="delete-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Are you sure you want to delete your review? This action cannot be undone.</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeDeleteReviewModal()">Cancel</button>
                    <button type="submit" class="btn-delete-confirm">Delete Review</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Review Modal -->
    <div id="reportReviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-flag"></i> Report Review</h3>
                <span class="modal-close" onclick="closeReportModal()">&times;</span>
            </div>
            <form method="POST" id="reportReviewForm">
                <input type="hidden" name="action" value="report_review">
                <input type="hidden" name="review_id" id="report_review_id">
                
                <div class="modal-body">
                    <p class="report-description">Help us maintain a respectful community. Please select the reason for reporting this review:</p>
                    
                    <div class="form-group">
                        <label class="form-label">Report Reason <span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="report_reason" value="vulgar" required>
                                <span class="radio-text"><i class="fas fa-language"></i> Vulgar or Offensive Language</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="report_reason" value="inappropriate" required>
                                <span class="radio-text"><i class="fas fa-ban"></i> Inappropriate Content</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="report_reason" value="spam" required>
                                <span class="radio-text"><i class="fas fa-envelope-open-text"></i> Spam or Advertisement</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="report_reason" value="harassment" required>
                                <span class="radio-text"><i class="fas fa-user-slash"></i> Harassment or Bullying</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="report_reason" value="misleading" required>
                                <span class="radio-text"><i class="fas fa-exclamation-circle"></i> Misleading or False Information</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="report_reason" value="other" required id="report_reason_other">
                                <span class="radio-text"><i class="fas fa-ellipsis-h"></i> Other</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="custom_reason_group" style="display: none;">
                        <label for="custom_reason" class="form-label">Please describe the issue</label>
                        <textarea id="custom_reason" name="custom_reason" class="form-textarea" 
                                  rows="3" placeholder="Provide additional details about why you're reporting this review..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeReportModal()">Cancel</button>
                    <button type="submit" class="btn-report-submit">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
    <script>
        // Star rating functionality for main review form
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star-rating-input i');
            const ratingInput = document.getElementById('rating');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    ratingInput.value = rating;
                    
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                        }
                    });
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = this.getAttribute('data-rating');
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('hover');
                        } else {
                            s.classList.remove('hover');
                        }
                    });
                });
            });
            
            document.querySelector('.star-rating-input').addEventListener('mouseout', function() {
                stars.forEach(s => s.classList.remove('hover'));
            });
        });
        
        // Filter and sort functionality
        function applyFilters() {
            const rating = document.getElementById('ratingFilter').value;
            const sort = document.getElementById('sortFilter').value;
            const url = new URL(window.location.href);
            
            if (rating === 'all') {
                url.searchParams.delete('rating');
            } else {
                url.searchParams.set('rating', rating);
            }
            
            url.searchParams.set('sort', sort);
            url.searchParams.set('tab', 'reviews');
            
            window.location.href = url.toString();
        }
        
        // React to review (like/dislike)
        async function reactToReview(reviewId, reactionType) {
            <?php if (!Session::isLoggedIn()): ?>
                document.getElementById('loginRequiredModal').style.display = 'flex';
                return;
            <?php endif; ?>
            
            const formData = new FormData();
            formData.append('action', 'react_review');
            formData.append('review_id', reviewId);
            formData.append('reaction_type', reactionType);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update counts
                    const likeBtn = document.querySelector(`button[data-review-id="${reviewId}"][data-reaction="like"]`);
                    const dislikeBtn = document.querySelector(`button[data-review-id="${reviewId}"][data-reaction="dislike"]`);
                    
                    likeBtn.querySelector('.like-count').textContent = data.like_count;
                    dislikeBtn.querySelector('.dislike-count').textContent = data.dislike_count;
                    
                    // Toggle active state
                    if (reactionType === 'like') {
                        likeBtn.classList.toggle('active');
                        dislikeBtn.classList.remove('active');
                    } else {
                        dislikeBtn.classList.toggle('active');
                        likeBtn.classList.remove('active');
                    }
                } else {
                    alert(data.message || 'Error processing reaction');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error processing reaction');
            }
        }
        
        function openReviewModal() {
            <?php if (!Session::isLoggedIn()): ?>
                document.getElementById('loginRequiredModal').style.display = 'flex';
            <?php else: ?>
                document.getElementById('reviewModal').style.display = 'flex';
            <?php endif; ?>
        }
        
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }
        
        function closeLoginModal() {
            document.getElementById('loginRequiredModal').style.display = 'none';
        }
        
        // Edit review modal
        function openEditReviewModal(reviewId, rating, title, comment, isAnonymous) {
            document.getElementById('edit_review_id').value = reviewId;
            document.getElementById('edit_rating').value = rating;
            document.getElementById('edit_review_title').value = title;
            document.getElementById('edit_review_comment').value = comment;
            document.getElementById('edit_anonymous').checked = isAnonymous == 1;
            
            // Set star rating
            const editStars = document.querySelectorAll('#edit-star-rating i');
            editStars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                } else {
                    star.classList.remove('fas');
                    star.classList.add('far');
                }
            });
            
            // Add event listeners to edit stars
            editStars.forEach(star => {
                star.addEventListener('click', function() {
                    const newRating = this.getAttribute('data-rating');
                    document.getElementById('edit_rating').value = newRating;
                    
                    editStars.forEach((s, idx) => {
                        if (idx < newRating) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                        }
                    });
                });
            });
            
            document.getElementById('editReviewModal').style.display = 'flex';
        }
        
        function closeEditReviewModal() {
            document.getElementById('editReviewModal').style.display = 'none';
        }
        
        // Delete review modal
        function openDeleteReviewModal(reviewId) {
            document.getElementById('delete_review_id').value = reviewId;
            document.getElementById('deleteReviewModal').style.display = 'flex';
        }
        
        function closeDeleteReviewModal() {
            document.getElementById('deleteReviewModal').style.display = 'none';
        }
        
        // Report review modal
        function openReportModal(reviewId) {
            document.getElementById('report_review_id').value = reviewId;
            document.getElementById('reportReviewModal').style.display = 'flex';
            
            // Reset form
            document.getElementById('reportReviewForm').reset();
            document.getElementById('custom_reason_group').style.display = 'none';
        }
        
        function closeReportModal() {
            document.getElementById('reportReviewModal').style.display = 'none';
        }
        
        // Show/hide custom reason textarea
        document.addEventListener('DOMContentLoaded', function() {
            const reportReasons = document.querySelectorAll('input[name="report_reason"]');
            reportReasons.forEach(radio => {
                radio.addEventListener('change', function() {
                    const customReasonGroup = document.getElementById('custom_reason_group');
                    if (this.value === 'other') {
                        customReasonGroup.style.display = 'block';
                        document.getElementById('custom_reason').required = true;
                    } else {
                        customReasonGroup.style.display = 'none';
                        document.getElementById('custom_reason').required = false;
                    }
                });
            });
        });
        
        // Close modal on outside click
        window.onclick = function(event) {
            const reviewModal = document.getElementById('reviewModal');
            const loginModal = document.getElementById('loginRequiredModal');
            const editModal = document.getElementById('editReviewModal');
            const deleteModal = document.getElementById('deleteReviewModal');
            const reportModal = document.getElementById('reportReviewModal');
            
            if (event.target === reviewModal) {
                closeReviewModal();
            }
            if (event.target === loginModal) {
                closeLoginModal();
            }
            if (event.target === editModal) {
                closeEditReviewModal();
            }
            if (event.target === deleteModal) {
                closeDeleteReviewModal();
            }
            if (event.target === reportModal) {
                closeReportModal();
            }
        };
    </script>
</body>
</html>