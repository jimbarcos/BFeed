<?php
/**
 * BuzzarFeed - Header Component
 *
 * Reusable header navigation component for all pages
 * Includes logo, main navigation, and user authentication links
 *
 * @package BuzzarFeed
 * @version 1.0
 * @author BuzzarFeed Development Team
 */

use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;

// Check if we're on an error page
$isErrorPage = defined('IS_ERROR_PAGE') && IS_ERROR_PAGE === true;

// For error pages, session is already started in the error page file
// For regular pages, Session::isLoggedIn() will start it automatically

// Check if user is logged in (works for both regular and error pages now)
$isLoggedIn = Session::isLoggedIn();
$userName = $isLoggedIn ? Session::get('user_name') : '';
$userType = $isLoggedIn ? Session::get('user_type') : '';
$hasStall = $isLoggedIn ? Session::get('has_stall', false) : false;

// Refresh user type from database to ensure it's current (in case admin changed it)
if ($isLoggedIn) {
    try {
        $db = Database::getInstance();
        $userId = Session::get('user_id');
        $currentUser = $db->querySingle(
            "SELECT ut.type_name,
                    (SELECT COUNT(*) FROM food_stalls WHERE owner_id = u.user_id AND is_active = 1) as has_approved_stall,
                    (SELECT COUNT(*) FROM applications WHERE user_id = u.user_id AND current_status_id = 1) as has_pending_application
             FROM users u
             INNER JOIN user_types ut ON u.user_type_id = ut.user_type_id
             WHERE u.user_id = ?",
            [$userId]
        );

        if ($currentUser) {
            // Update session if user type changed
            if ($currentUser['type_name'] !== $userType) {
                Session::set('user_type', $currentUser['type_name']);
                $userType = $currentUser['type_name'];
            }

            // Update stall status flags
            $hasApprovedStall = $currentUser['has_approved_stall'] > 0;
            $hasPendingApplication = $currentUser['has_pending_application'] > 0;
            Session::set('has_approved_stall', $hasApprovedStall);
            Session::set('has_pending_application', $hasPendingApplication);
        }
    } catch (\Exception $e) {
        error_log("Error refreshing user session data: " . $e->getMessage());
    }
}

// Determine which button to show for stall owners
$hasApprovedStall = $isLoggedIn ? Session::get('has_approved_stall', false) : false;
$hasPendingApplication = $isLoggedIn ? Session::get('has_pending_application', false) : false;
$showManageStallBtn = $isLoggedIn && $userType === 'food_stall_owner' && $hasApprovedStall;
$showPendingApplicationBtn = $isLoggedIn && $userType === 'food_stall_owner' && $hasPendingApplication;
$showRegisterStallBtn = $isLoggedIn && $userType === 'food_stall_owner' && !$hasApprovedStall && !$hasPendingApplication;

// Check if user is an admin
$isAdmin = $isLoggedIn && $userType === 'admin';
?>

<header class="header">
    <div class="container">
        <nav class="nav-container">
            <!-- Logo -->
            <a href="<?= BASE_URL ?>" class="logo">
                <img src="<?= IMAGES_URL ?>/Logo-Header.png" alt="BuzzarFeed" class="logo-img">
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Centered Navigation Menu -->
            <ul class="nav-menu">
                <li>
                    <a href="<?= BASE_URL ?>stalls.php" class="nav-link">
                        Stalls
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>about.php" class="nav-link">
                        About
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>map.php" class="nav-link">
                        Map
                    </a>
                </li>
            </ul>

            <!-- Authentication Links on Right -->
            <div class="nav-actions">
                <?php if ($isAdmin): ?>
                    <!-- Admin Panel Button -->
                    <a href="<?= BASE_URL ?>admin-panel.php" class="btn btn-primary">
                        Admin Panel
                    </a>
                <?php endif; ?>

                <?php if ($showManageStallBtn): ?>
                    <!-- Manage Stall Button for Approved Stall Owners -->
                    <a href="<?= BASE_URL ?>manage-stall.php" class="btn btn-secondary">
                        Manage Your Stall
                    </a>
                <?php elseif ($showPendingApplicationBtn): ?>
                    <!-- Pending Application Button for Stall Owners with Pending Application -->
                    <a href="<?= BASE_URL ?>registration-pending.php" class="btn btn-warning">
                        <i class="fas fa-clock"></i> Application Pending
                    </a>
                <?php elseif ($showRegisterStallBtn): ?>
                    <!-- Register Stall Button for Stall Owners without Stall -->
                    <a href="<?= BASE_URL ?>register-stall.php" class="btn btn-secondary">
                        Register Your Stall
                    </a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <!-- Logged In User -->
                    <div class="user-dropdown">
                        <div class="user-dropdown-btn" id="userDropdownBtn" role="button" tabindex="0">
                            <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                            <span class="dropdown-arrow" aria-hidden="true">▼</span>
                        </div>
                        <ul class="dropdown-menu" id="userDropdownMenu">
                            <li>
                                <a href="<?= BASE_URL ?>my-account.php" class="dropdown-item">
                                    <i class="fas fa-user-circle"></i> My Account
                                </a>
                            </li>
                            <?php if ($userType === 'food_stall_owner'): ?>
                                <li>
                                    <a href="<?= BASE_URL ?>manage-stall.php" class="dropdown-item">
                                        <i class="fas fa-store"></i> My Stall
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a href="<?= BASE_URL ?>my-reviews.php" class="dropdown-item">
                                    <i class="fas fa-star"></i> My Reviews
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="<?= BASE_URL ?>logout.php" class="dropdown-item logout-link">
                                    <i class="fas fa-sign-out-alt"></i> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Not Logged In -->
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-outline">
                        Log In
                    </a>
                    <a href="<?= BASE_URL ?>signup.php" class="btn btn-secondary">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<?php if ($isLoggedIn): ?>
    <!-- User Dropdown Script -->
    <script src="<?= JS_URL ?>/modules/dropdown.js"></script>
<?php endif; ?>
