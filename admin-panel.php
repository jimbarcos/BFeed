<?php
/**
 * BuzzarFeed - Admin Panel
 * 
 * Dashboard for administrators to manage stall applications and reviews
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;
use BuzzarFeed\Services\ApplicationService;
use BuzzarFeed\Services\AdminLogService;
use BuzzarFeed\Services\ReviewReportService;

Session::start();

// Check if user is logged in and is an admin
if (!Session::isLoggedIn()) {
    Session::setFlash('Please log in to access the admin panel.', 'error');
    Helpers::redirect('login.php');
    exit;
}

if (Session::get('user_type') !== 'admin') {
    Session::setFlash('Access denied. Admin privileges required.', 'error');
    Helpers::redirect('index.php');
    exit;
}

$db = Database::getInstance();
$applicationService = new ApplicationService();
$logService = new AdminLogService();
$reportService = new ReviewReportService();
$adminName = Session::get('user_name');

// Get statistics
$totalUsers = $db->querySingle("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
$pendingStalls = $db->querySingle("SELECT COUNT(*) as count FROM applications WHERE current_status_id = 1")['count'] ?? 0;
$approvedStalls = $db->querySingle("SELECT COUNT(*) as count FROM food_stalls")['count'] ?? 0;

// Handle application actions (approve/decline)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = Helpers::post('action');
    $applicationId = Helpers::post('application_id');
    $reviewNotes = Helpers::post('review_notes', '');
    
    // Handle review moderation actions
    $reviewAction = Helpers::post('review_action');
    $reviewId = Helpers::post('review_id');
    $moderationReason = Helpers::post('moderation_reason', '');

    try {
        // Application actions
        if ($action) {
            switch ($action) {
                case 'approve':
                    if ($applicationId) {
                        $applicationService->approveApplication($applicationId, $reviewNotes);
                        Session::setFlash("Application approved successfully! Stall is now live.", 'success');
                    }
                    break;

                case 'decline':
                    if ($applicationId) {
                        $applicationService->declineApplication($applicationId, $reviewNotes);
                        Session::setFlash('Application declined and applicant has been notified.', 'success');
                    }
                    break;

                case 'hide':
                    if ($applicationId) {
                        $applicationService->archiveApplication($applicationId);
                        Session::setFlash('Application archived successfully.', 'success');
                    }
                    break;
            }
            Helpers::redirect('admin-panel.php?tab=pending-applications#main-tabs');
        }
        
        // Review moderation actions
        if ($reviewAction && $reviewId) {
            $adminId = Session::get('user_id');
            
            switch ($reviewAction) {
                case 'delete':
                    if ($moderationReason) {
                        $reportService->deleteReview($reviewId, $adminId, $moderationReason);
                        Session::setFlash('Review has been deleted and user has been notified.', 'success');
                    } else {
                        Session::setFlash('Please provide a reason for deleting the review.', 'error');
                    }
                    break;
                    
                case 'dismiss':
                    $reportService->dismissReports($reviewId, $adminId, $moderationReason ?: 'No violation found');
                    Session::setFlash('Reports dismissed successfully.', 'success');
                    break;
            }
            Helpers::redirect('admin-panel.php?tab=recent-reviews#main-tabs');
        }
    } catch (\Exception $e) {
        error_log("Admin Action Error: " . $e->getMessage());
        Session::setFlash('Error: ' . $e->getMessage(), 'error');
        Helpers::redirect('admin-panel.php?tab=' . ($reviewAction ? 'recent-reviews' : 'pending-applications') . '#main-tabs');
    }
    exit;
}

// Get current tab
$currentTab = Helpers::get('tab', 'pending-applications');

// Get pending applications with user details
$pendingApps = [];
if ($currentTab === 'pending-applications') {
    $pendingApps = $applicationService->getPendingApplications();
}

// Get admin logs
$adminLogs = [];
if ($currentTab === 'admin-logs') {
    $adminLogs = $logService->getAllLogs(100, 0);
}

// Get pending review reports
$pendingReports = [];
if ($currentTab === 'recent-reviews') {
    $pendingReports = $reportService->getPendingReports();
}

$pageTitle = "Admin Panel - BuzzarFeed";
$pageDescription = "Manage stall applications and moderate reviews";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= Helpers::escape($pageDescription) ?>">
    <title><?= Helpers::escape($pageTitle) ?></title>

    <link rel="icon" type="image/png" href="<?= IMAGES_URL ?>/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= CSS_URL ?>/variables.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/base.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/button.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/dropdown.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/styles.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/admin-panel.css">

</head>

<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <div class="admin-hero">
            <h1>Admin Panel</h1>
            <p>Welcome back, <?= Helpers::escape($adminName) ?>! Manage stall applications and content moderation.</p>
            <a href="<?= BASE_URL ?>convert-to-admin.php" class="convert-user-btn">
                <i class="fas fa-user-shield"></i>
                Convert User to Admin
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalUsers ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $pendingStalls ?></div>
                    <div class="stat-label">Pending Stalls</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $approvedStalls ?></div>
                    <div class="stat-label">Approved Stalls</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs-container" id="main-tabs">
            <div class="tabs">
                <a href="?tab=pending-applications#main-tabs"
                    class="tab-btn <?= $currentTab === 'pending-applications' ? 'active' : '' ?>">
                    Pending Applications
                </a>
                <a href="?tab=recent-reviews#main-tabs"
                    class="tab-btn <?= $currentTab === 'recent-reviews' ? 'active' : '' ?>">
                    Reviews for Moderation
                </a>
                <a href="?tab=admin-logs#main-tabs"
                    class="tab-btn <?= $currentTab === 'admin-logs' ? 'active' : '' ?>">
                    Admin Logs
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content-container">
            <?php if ($currentTab === 'pending-applications'): ?>
                <div class="tabs-and-content-container">
                    <div class="section-title-container">
                        <h2 class="section-title">Pending Applications</h2>
                    </div>

                    <div class="tab-content-area">
                        <?php if (Session::get('flash_message')): ?>
                            <?php
                            $flashMessage = Session::getFlash();
                            $flashType = Session::get('flash_type', 'success');
                            ?>
                            <div class="flash-message <?= $flashType ?>">
                                <?= Helpers::escape(is_array($flashMessage) ? $flashMessage['message'] ?? '' : $flashMessage) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($pendingApps)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No Pending Applications</h3>
                                <p>All applications have been reviewed.</p>
                            </div>
                        <?php else: ?>

                            <div class="applications-list">
                                <div class="grid-layout list-header">
                                    <div class="header-cell center">ID</div>
                                    <div class="header-cell">Stall Name</div>
                                    <div class="header-cell">Owner</div>
                                    <div class="header-cell">Location</div>
                                    <div class="header-cell">Category</div>
                                    <div class="header-cell center">Date</div>
                                    <div class="header-cell">Action</div>
                                </div>

                                <?php foreach ($pendingApps as $app): ?>
                                    <div class="application-entry">
                                        
                                        <div class="grid-layout list-row">
                                            <div class="data-cell center"><?= str_pad($app['application_id'], 2, '0', STR_PAD_LEFT) ?>
                                            </div>
                                            <div class="data-cell" title="<?= Helpers::escape($app['stall_name']) ?>">
                                                <?= Helpers::escape($app['stall_name']) ?>
                                            </div>
                                            <div class="data-cell" title="<?= Helpers::escape($app['applicant_name']) ?>">
                                                <?= Helpers::escape($app['applicant_name']) ?>
                                            </div>
                                            <div class="data-cell" title="<?= Helpers::escape($app['location']) ?>">
                                                <?= Helpers::escape($app['location']) ?>
                                            </div>
                                            <div class="data-cell">
                                                <?php
                                                $categories = json_decode($app['food_categories'], true);
                                                if (is_array($categories)) {
                                                    echo Helpers::escape(implode(', ', array_slice($categories, 0, 2)));
                                                    if (count($categories) > 2)
                                                        echo '...';
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </div>
                                            <div class="data-cell center"><?= date('n/j/y', strtotime($app['created_at'])) ?></div>
                                            <div class="cell-actions">
                                                <button type="button" class="btn-view view-application-trigger"
                                                    data-id="<?= $app['application_id'] ?>">
                                                    View Details
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mobile-card">
                                            <div class="mobile-card-header">
                                                <div class="mobile-card-id">#<?= str_pad($app['application_id'], 2, '0', STR_PAD_LEFT) ?></div>
                                                <div class="mobile-card-date">
                                                    <i class="far fa-calendar"></i> <?= date('M j, Y', strtotime($app['created_at'])) ?>
                                                </div>
                                            </div>
                                            
                                            <h3 class="mobile-card-title"><?= Helpers::escape($app['stall_name']) ?></h3>
                                            <div class="mobile-card-owner">
                                                <i class="fas fa-user"></i> <?= Helpers::escape($app['applicant_name']) ?>
                                            </div>
                                            
                                            <div class="mobile-card-details">
                                                <div class="mobile-card-detail">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?= Helpers::escape($app['location']) ?></span>
                                                </div>
                                                <div class="mobile-card-detail">
                                                    <i class="fas fa-utensils"></i>
                                                    <span>
                                                        <?php
                                                        $categories = json_decode($app['food_categories'], true);
                                                        if (is_array($categories)) {
                                                            echo Helpers::escape(implode(', ', array_slice($categories, 0, 2)));
                                                            if (count($categories) > 2)
                                                                echo '...';
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="mobile-card-actions">
                                                <button type="button" class="btn-view view-application-trigger"
                                                    data-id="<?= $app['application_id'] ?>">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div> <?php endforeach; ?>
                            </div>

                            <div class="pagination">
                            <button id="prevBtn" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button id="nextBtn" class="pagination-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <?php endif; ?>

            <?php elseif ($currentTab === 'recent-reviews'): ?>
                <div class="tabs-and-content-container">
                    <div class="section-title-container">
                        <h2 class="section-title">Reviews for Moderation</h2>
                    </div>

                    <div class="tab-content-area">
                        <?php if (Session::get('flash_message')): ?>
                            <?php
                            $flashMessage = Session::getFlash();
                            $flashType = Session::get('flash_type', 'success');
                            ?>
                            <div class="flash-message <?= $flashType ?>">
                                <?= Helpers::escape(is_array($flashMessage) ? $flashMessage['message'] ?? '' : $flashMessage) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($pendingReports)): ?>
                            <div class="empty-state">
                                <i class="fas fa-star-half-alt" style="display: block; margin-bottom: 15px; font-size: 64px; color: #E0E0E0;"></i>
                                <h3>Moderation Queue Empty</h3>
                                <p>There are currently no reviews flagged for moderation.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pendingReports as $report): ?>
                                <div class="report-card">
                                    <div class="report-header">
                                        <div class="report-meta">
                                            <span class="report-count">
                                                <i class="fas fa-flag"></i> 
                                                <?= $report['total_reports'] ?> Report<?= $report['total_reports'] > 1 ? 's' : '' ?>
                                            </span>
                                            <span class="report-date">
                                                <?php 
                                                    $timestamp = strtotime($report['first_report_date']);
                                                    $phtTimestamp = $timestamp + (16 * 3600); // Add 16 hours for PHT (UTC+8)
                                                    echo date('M d, Y h:i A', $phtTimestamp);
                                                ?>
                                            </span>
                                        </div>
                                        <?php if ($report['is_hidden']): ?>
                                            <span class="badge-hidden">Already Hidden</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="report-content">
                                        <div class="review-info">
                                            <h3 class="stall-name">
                                                <i class="fas fa-store"></i> 
                                                <?= Helpers::escape($report['stall_name']) ?>
                                            </h3>
                                            <div class="reviewer-info">
                                                <span class="reviewer-name">
                                                    <i class="fas fa-user"></i> 
                                                    <?= Helpers::escape($report['reviewer_name']) ?>
                                                </span>
                                                <span class="rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fa<?= $i <= $report['rating'] ? 's' : 'r' ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </span>
                                            </div>
                                        </div>

                                        <?php if ($report['review_title']): ?>
                                            <h4 class="review-title"><?= Helpers::escape($report['review_title']) ?></h4>
                                        <?php endif; ?>
                                        
                                        <p class="review-comment"><?= Helpers::escape($report['review_comment']) ?></p>

                                        <!-- All Reports for this Review -->
                                        <div class="all-reports-section">
                                            <strong><i class="fas fa-exclamation-triangle"></i> Reports:</strong>
                                            <?php foreach ($report['reports'] as $individualReport): ?>
                                                <div class="individual-report">
                                                    <div class="report-header-inline">
                                                        <span class="reason-badge reason-<?= $individualReport['report_reason'] ?>">
                                                            <?= ucfirst($individualReport['report_reason']) ?>
                                                        </span>
                                                        <span class="reporter-info">
                                                            by <strong><?= Helpers::escape($individualReport['reporter_name']) ?></strong>
                                                        </span>
                                                        <span class="report-time">
                                                            <?php 
                                                                $reportTimestamp = strtotime($individualReport['created_at']);
                                                                $reportPhtTimestamp = $reportTimestamp + (16 * 3600);
                                                                echo date('M d, h:i A', $reportPhtTimestamp);
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <?php if ($individualReport['custom_reason']): ?>
                                                        <p class="custom-reason"><?= Helpers::escape($individualReport['custom_reason']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="report-actions">
                                        <form method="POST" class="moderation-form">
                                            <input type="hidden" name="review_id" value="<?= $report['review_id'] ?>">
                                            
                                            <div class="action-group">
                                                <textarea 
                                                    name="moderation_reason" 
                                                    class="moderation-textarea" 
                                                    placeholder="Reason for action (required for hiding)..."
                                                    rows="2"
                                                ></textarea>
                                                
                                                <div class="button-group">
                                                    <button 
                                                        type="submit" 
                                                        name="review_action" 
                                                        value="dismiss" 
                                                        class="btn-dismiss"
                                                        onclick="return confirm('Dismiss all <?= $report['total_reports'] ?> report(s) for this review?')"
                                                    >
                                                        <i class="fas fa-times-circle"></i> Dismiss All Reports
                                                    </button>
                                                    <button 
                                                        type="submit" 
                                                        name="review_action" 
                                                        value="delete" 
                                                        class="btn-delete"
                                                        onclick="return confirm('Delete this review permanently and notify the user?')"
                                                    >
                                                        <i class="fas fa-trash"></i> Delete Review
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($currentTab === 'admin-logs'): ?>
                <div class="tabs-and-content-container">
                    <div class="section-title-container">
                        <h2 class="section-title">Admin Activity Logs</h2>
                    </div>

                    <div class="tab-content-area">
                        <?php if (empty($adminLogs)): ?>
                            <div class="empty-state">
                                <i class="fas fa-history" style="display: block; margin-bottom: 15px; font-size: 64px; color: #E0E0E0;"></i>
                                <h3>No Admin Logs</h3>
                                <p>There are no admin activity logs to display.</p>
                            </div>
                        <?php else: ?>
                            <!-- Desktop Table -->
                            <div class="list-header grid-layout-logs">
                                <div class="header-cell">ID</div>
                                <div class="header-cell">Admin</div>
                                <div class="header-cell">Email</div>
                                <div class="header-cell">Action</div>
                                <div class="header-cell">Entity</div>
                                <div class="header-cell">Details</div>
                                <div class="header-cell">Date & Time</div>
                            </div>

                            <?php foreach ($adminLogs as $log): ?>
                                <div class="log-entry">
                                    <div class="list-row grid-layout-logs">
                                        <div class="data-cell"><?= Helpers::escape($log['log_id']) ?></div>
                                        <div class="data-cell"><?= Helpers::escape($log['admin_name']) ?></div>
                                        <div class="data-cell" title="<?= Helpers::escape($log['admin_email']) ?>"><?= Helpers::escape($log['admin_email']) ?></div>
                                        <div class="data-cell">
                                            <span class="action-badge action-<?= Helpers::escape(strtolower($log['action'])) ?>">
                                                <?= Helpers::escape(ucwords(str_replace('_', ' ', $log['action']))) ?>
                                            </span>
                                        </div>
                                        <div class="data-cell">
                                            <span class="entity-badge entity-<?= Helpers::escape($log['entity']) ?>">
                                                <?= Helpers::escape(ucfirst($log['entity'])) ?>
                                            </span>
                                        </div>
                                        <div class="data-cell log-details">
                                            <button class="btn-view-details" onclick="showLogDetails(<?= Helpers::escape($log['log_id']) ?>, '<?= Helpers::escape($log['details'] ?? 'N/A', ENT_QUOTES) ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </div>
                                        <div class="data-cell">
                                            <?php 
                                                $timestamp = strtotime($log['created_at']);
                                                $phtTimestamp = $timestamp + (16 * 3600); // Add 16 hours for PHT (UTC+8)
                                                echo Helpers::escape(date('M d, Y h:i A', $phtTimestamp));
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Mobile Card -->
                                    <div class="mobile-card">
                                        <div class="mobile-card-header">
                                            <div class="mobile-card-id">#<?= Helpers::escape($log['log_id']) ?></div>
                                            <div class="mobile-card-date">
                                                <?php 
                                                    $timestamp = strtotime($log['created_at']);
                                                    $phtTimestamp = $timestamp + (16 * 3600); // Add 16 hours for PHT (UTC+8)
                                                    echo Helpers::escape(date('M d, Y', $phtTimestamp));
                                                ?>
                                            </div>
                                        </div>
                                        <div class="mobile-card-title"><?= Helpers::escape($log['admin_name']) ?></div>
                                        <div class="mobile-card-owner">
                                            <i class="fas fa-envelope"></i>
                                            <?= Helpers::escape($log['admin_email']) ?>
                                        </div>
                                        <div class="mobile-card-details">
                                            <div class="mobile-card-detail">
                                                <i class="fas fa-bolt"></i>
                                                <span class="action-badge action-<?= Helpers::escape(strtolower($log['action'])) ?>">
                                                    <?= Helpers::escape(ucwords(str_replace('_', ' ', $log['action']))) ?>
                                                </span>
                                            </div>
                                            <div class="mobile-card-detail">
                                                <i class="fas fa-cube"></i>
                                                <span class="entity-badge entity-<?= Helpers::escape($log['entity']) ?>">
                                                    <?= Helpers::escape(ucfirst($log['entity'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mobile-card-log-details">
                                            <strong>Details:</strong><br>
                                            <?= Helpers::escape($log['details'] ?? 'N/A') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="pagination">
                                <button id="prevBtnLogs" class="pagination-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button id="nextBtnLogs" class="pagination-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="applicationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalStallName">Loading...</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <p>Loading application details...</p>
            </div>
        </div>
    </div>

    <div id="logDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Log Details</h3>
                <button type="button" class="modal-close" onclick="closeLogDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="logDetailsBody">
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script id="applications-data" type="application/json">
        <?php echo json_encode($pendingApps ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
    </script>

    <script id="base-url-data" type="application/json">
        <?= json_encode(defined('BASE_URL') ? BASE_URL : '/', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // PAGINATION
            const itemsPerPage = 10;
            let currentPage = 1;
            const allItems = document.querySelectorAll('.application-entry');
            const totalItems = allItems.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);

            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            function showPage(page) {
                const startIndex = (page - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;

                allItems.forEach((item, index) => {
                    if (index >= startIndex && index < endIndex) {
                        item.style.display = 'block'; 
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (prevBtn) {
                    prevBtn.disabled = (page === 1);
                    prevBtn.style.opacity = (page === 1) ? '0.5' : '1';
                }
                if (nextBtn) {
                    nextBtn.disabled = (page >= totalPages);
                    nextBtn.style.opacity = (page >= totalPages) ? '0.5' : '1';
                }
            }

            function scrollToTableTop() {
                const tableTop = document.querySelector('.section-title-container');
                if (tableTop) {

                    tableTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            if (totalItems > 0) showPage(1);

            if (prevBtn) prevBtn.addEventListener('click', () => { 
                if (currentPage > 1) { 
                    currentPage--; 
                    showPage(currentPage); 
                    scrollToTableTop() 
                } 
            });

            if (nextBtn) nextBtn.addEventListener('click', () => { 
                if (currentPage < totalPages) { 
                    currentPage++; 
                    showPage(currentPage); 
                    scrollToTableTop();
                } 
            });


            // MODAL
            let applicationsData = [];
            let baseUrl = '/';

            try {
                applicationsData = JSON.parse(document.getElementById('applications-data').textContent);
                baseUrl = JSON.parse(document.getElementById('base-url-data').textContent);
            } catch (e) { console.error("Data Error:", e); }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Document Helper
            function renderDocumentItem(path, label) {
                if (!path) return '';
                const cleanPath = path.startsWith('/') ? path.substring(1) : path;
                const cleanBase = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
                const fullPath = cleanBase + cleanPath;
                const isImage = path.match(/\.(jpg|jpeg|png|gif|webp)$/i);

                return `
                    <div class="document-item">
                        ${isImage ?
                        `<img src="${fullPath}" class="document-preview" onclick="window.open('${fullPath}', '_blank')" />` :
                        `<div class="document-preview" onclick="window.open('${fullPath}', '_blank')" 
                                  style="display: flex; align-items: center; justify-content: center; background: #eee;">
                                <i class="fas fa-file-alt" style="font-size: 40px; color: #555;"></i>
                            </div>`
                    }
                        <div class="document-label">${label}</div>
                        <a href="${fullPath}" target="_blank" class="document-link">View</a>
                    </div>
                `;
            }

            const closeModal = () => {
                const modal = document.getElementById('applicationModal');
                if (modal) modal.classList.remove('active');
                document.body.style.overflow = '';
            };

            window.showLogDetails = (logId, details) => {
                const modal = document.getElementById('logDetailsModal');
                const body = document.getElementById('logDetailsBody');
                body.innerHTML = `<p style="white-space: pre-wrap; word-wrap: break-word; font-size: 16px; line-height: 1.6;">${escapeHtml(details)}</p>`;
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            };

            window.closeLogDetailsModal = () => {
                const modal = document.getElementById('logDetailsModal');
                if (modal) modal.classList.remove('active');
                document.body.style.overflow = '';
            };

            // Event Delegation
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.view-application-trigger');
                if (btn) {
                    e.preventDefault();
                    const applicationId = btn.getAttribute('data-id');
                    const app = applicationsData.find(a => a.application_id == applicationId);

                    if (!app) return;

                    const modal = document.getElementById('applicationModal');
                    const modalTitle = document.getElementById('modalStallName');
                    const modalBody = document.getElementById('modalBody');

                    let categories = 'N/A';
                    try {
                        if (app.food_categories) {
                            const rawCats = typeof app.food_categories === 'string'
                                ? JSON.parse(app.food_categories)
                                : app.food_categories;
                            categories = Array.isArray(rawCats) ? rawCats.join(', ') : 'N/A';
                        }
                    } catch (e) { categories = app.food_categories || 'N/A'; }

                    let logoHtml = '<div class="stall-logo-placeholder"><i class="fas fa-store"></i></div>';
                    if (app.stall_logo_path) {
                        const cleanPath = app.stall_logo_path.startsWith('/') ? app.stall_logo_path.substring(1) : app.stall_logo_path;
                        const cleanBase = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
                        const fullLogoPath = cleanBase + cleanPath;
                        logoHtml = `<img src="${fullLogoPath}" class="stall-logo-large" alt="Stall Logo">`;
                    }

                    modalTitle.textContent = app.stall_name || 'Application Details';

                    modalBody.innerHTML = `
                        <div class="modal-top-split">
                            <div class="modal-info-left">
                                <h4 class="modal-section-title">Application Information</h4>
                                <div class="modal-info-grid">
                                    <div class="modal-info-item"><span class="modal-info-label">ID:</span><span class="modal-info-value">#${app.application_id}</span></div>
                                    <div class="modal-info-item"><span class="modal-info-label">Applicant:</span><span class="modal-info-value">${escapeHtml(app.applicant_name)}</span></div>
                                    <div class="modal-info-item"><span class="modal-info-label">Email:</span><span class="modal-info-value">${escapeHtml(app.applicant_email)}</span></div>
                                    <div class="modal-info-item"><span class="modal-info-label">Location:</span><span class="modal-info-value">${escapeHtml(app.location)}</span></div>
                                    <div class="modal-info-item"><span class="modal-info-label">Categories:</span><span class="modal-info-value">${escapeHtml(categories)}</span></div>
                                    <div class="modal-info-item"><span class="modal-info-label">Date:</span><span class="modal-info-value">${new Date(app.created_at).toLocaleDateString()}</span></div>
                                </div>
                                <div class="modal-info-item" style="margin-top: 15px;">
                                    <span class="modal-info-label">Description:</span>
                                    <span class="modal-info-value">${escapeHtml(app.stall_description)}</span>
                                </div>
                            </div>
                            <div class="modal-logo-right">
                                ${logoHtml}
                            </div>
                        </div>

                        <hr class="modal-divider">
                        
                        <div class="modal-section">
                            <h4 class="modal-section-title">Legal Documents</h4>
                            <div class="documents-grid">
                                ${renderDocumentItem(app.bir_registration_path, 'BIR Registration')}
                                ${renderDocumentItem(app.business_permit_path, 'Business Permit')}
                                ${renderDocumentItem(app.dti_sec_path, 'DTI / SEC')}
                            </div>
                        </div>
                        
                        <div class="modal-review-section">
                            <form method="POST">
                                <input type="hidden" name="application_id" value="${app.application_id}">
                                <textarea name="review_notes" class="review-textarea" placeholder="Add review notes (optional)..."></textarea>
                                <div class="modal-actions">
                                    <button type="submit" name="action" value="decline" class="btn-decline" onclick="return confirm('Decline this application?')">Decline</button>
                                    <button type="submit" name="action" value="approve" class="btn-approve" onclick="return confirm('Approve this application?')">Approve</button>
                                </div>
                            </form>
                        </div>
                    `;

                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }

                if (e.target.id === 'applicationModal' || e.target.classList.contains('modal-close')) {
                    closeModal();
                }
                
                if (e.target.id === 'logDetailsModal') {
                    closeLogDetailsModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal();
                    closeLogDetailsModal();
                }
            });

            // ADMIN LOGS PAGINATION
            const logsItemsPerPage = 10;
            let logsCurrentPage = 1;
            const allLogsItems = document.querySelectorAll('.log-entry');
            const totalLogsItems = allLogsItems.length;
            const totalLogsPages = Math.ceil(totalLogsItems / logsItemsPerPage);

            const prevBtnLogs = document.getElementById('prevBtnLogs');
            const nextBtnLogs = document.getElementById('nextBtnLogs');

            function showLogsPage(page) {
                const startIndex = (page - 1) * logsItemsPerPage;
                const endIndex = startIndex + logsItemsPerPage;

                allLogsItems.forEach((item, index) => {
                    if (index >= startIndex && index < endIndex) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (prevBtnLogs) {
                    prevBtnLogs.disabled = (page === 1);
                    prevBtnLogs.style.opacity = (page === 1) ? '0.5' : '1';
                }
                if (nextBtnLogs) {
                    nextBtnLogs.disabled = (page >= totalLogsPages);
                    nextBtnLogs.style.opacity = (page >= totalLogsPages) ? '0.5' : '1';
                }
            }

            if (totalLogsItems > 0) showLogsPage(1);

            if (prevBtnLogs) prevBtnLogs.addEventListener('click', () => { 
                if (logsCurrentPage > 1) { 
                    logsCurrentPage--; 
                    showLogsPage(logsCurrentPage); 
                    scrollToTableTop();
                } 
            });

            if (nextBtnLogs) nextBtnLogs.addEventListener('click', () => { 
                if (logsCurrentPage < totalLogsPages) { 
                    logsCurrentPage++; 
                    showLogsPage(logsCurrentPage); 
                    scrollToTableTop();
                } 
            });
        });
    </script>
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>

</html>