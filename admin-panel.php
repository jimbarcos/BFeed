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
    
    try {
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
    } catch (\Exception $e) {
        error_log("Application Action Error: " . $e->getMessage());
        Session::setFlash('Error: ' . $e->getMessage(), 'error');
    }
    
    Helpers::redirect('admin-panel.php?tab=pending-stalls');
    exit;
}

// Get current tab
$currentTab = Helpers::get('tab', 'pending-stalls');

// Get pending applications with user details
$pendingApps = [];
if ($currentTab === 'pending-stalls') {
    $pendingApps = $applicationService->getPendingApplications();
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
    
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #489A44; /* full-page green background */
        }
        
        main {
            flex: 1 0 auto;
            padding: 0;
            background: #489A44; /* ensure area around containers stays green */
        }
        
        /* Hero Section */
        .admin-hero {
            background: linear-gradient(135deg, #ED6027 0%, #E8663E 100%);
            padding: 60px 20px;
            text-align: center;
            color: white;
        }
        
        .admin-hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #FEEED5;
        }
        
        .admin-hero p {
            font-size: 16px;
            margin: 0 0 30px 0;
            color: #FEEED5;
        }
        
        .convert-user-btn {
            background: #489A44;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .convert-user-btn:hover {
            background: #3d8439;
        }
        
        /* Statistics Cards */
        .stats-container {
            background: #489A44;
            padding: 40px 20px;
        }
        
        .stats-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }
        
        .stat-card {
            background: #FEEED5;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            border: 3px solid #2C2C2C;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: 700;
            color: #ED6027;
            margin: 0 0 10px 0;
        }
        
        .stat-label {
            font-size: 14px;
            font-weight: 600;
            color: #2C2C2C;
            margin: 0;
        }
        
        /* Tab Navigation */
        .tabs-container {
            background: #FEEED5; /* beige tab body */
            padding: 20px;
            max-width: 1200px;
            margin: -30px auto 0;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .tabs {
            display: flex;
            gap: 10px;
        }
        
        .tab-btn {
            padding: 15px 25px;
            background: #FEEED5; /* beige */
            border: none;
            font-size: 14px;
            font-weight: 600;
            color: #2C2C2C;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 8px 8px 0 0;
        }
        
        .tab-btn:hover {
            background: #FFF5F0; /* lighter beige on hover */
        }
        
        .tab-btn.active {
            background: #ED6027; /* orange active tab */
            color: white;
        }
        
        /* Content Container */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #FEEED5;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            background: white;
            color: #489A44;
            padding: 20px;
            border-radius: 8px;
            margin: 0 0 30px 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        /* Application Cards */
        .application-card {
            background: #FEEED5;
            border: 2px solid #2C2C2C;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .app-title {
            font-size: 24px;
            font-weight: 700;
            color: #2C2C2C;
            margin: 0 0 5px 0;
        }
        
        .app-id {
            font-size: 14px;
            color: #666;
            margin: 0 0 5px 0;
        }
        
        .app-date {
            font-size: 12px;
            color: #999;
            margin: 0;
        }
        
        .app-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 14px;
            color: #2C2C2C;
        }
        
        /* Document Viewer */
        .documents-section {
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
        }
        
        .documents-title {
            font-size: 16px;
            font-weight: 700;
            color: #2C2C2C;
            margin: 0 0 15px 0;
        }
        
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .document-item {
            text-align: center;
        }
        
        .document-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            border: 2px solid #E0E0E0;
        }
        
        .document-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
        }
        
        .document-link {
            display: inline-block;
            padding: 8px 15px;
            background: #ED6027;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .document-link:hover {
            background: #d55520;
        }
        
        /* Review Form */
        .review-section {
            margin: 20px 0;
        }
        
        .review-textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            box-sizing: border-box;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-approve {
            flex: 1;
            background: #489A44;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-approve:hover {
            background: #3d8439;
        }
        
        .btn-decline {
            flex: 1;
            background: #DC3545;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-decline:hover {
            background: #c82333;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid #489A44;
            color: #489A44;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .pagination-btn:hover {
            background: #489A44;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #E0E0E0;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: #666;
            margin: 0 0 10px 0;
        }
        
        .empty-state p {
            font-size: 14px;
            color: #999;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .app-info {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                text-align: left;
            }
        }
    </style>
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
        <div class="tabs-container">
            <div class="tabs">
                <a href="?tab=pending-stalls" class="tab-btn <?= $currentTab === 'pending-stalls' ? 'active' : '' ?>">
                    Pending Stalls
                </a>
                <a href="?tab=recent-reviews" class="tab-btn <?= $currentTab === 'recent-reviews' ? 'active' : '' ?>">
                    Recent Reviews for Moderation
                </a>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content-container">
            <?php if ($currentTab === 'pending-stalls'): ?>
                <h2 class="section-title">Pending Stall Registrations (<?= count($pendingApps) ?>)</h2>
                
                <?php if (Session::get('flash_message')): ?>
                    <?php 
                    $flashMessage = Session::getFlash();
                    $flashType = Session::get('flash_type', 'success');
                    $bgColor = $flashType === 'error' ? '#f8d7da' : '#d4edda';
                    $textColor = $flashType === 'error' ? '#721c24' : '#155724';
                    ?>
                    <div style="padding: 15px; background: <?= $bgColor ?>; color: <?= $textColor ?>; border-radius: 8px; margin-bottom: 20px;">
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
                    <?php foreach ($pendingApps as $app): ?>
                        <div class="application-card">
                            <div class="app-header">
                                <div>
                                    <h3 class="app-title"><?= Helpers::escape($app['stall_name']) ?></h3>
                                    <p class="app-id">#<?= $app['application_id'] ?> <?= Helpers::escape($app['applicant_name']) ?></p>
                                    <p class="app-date">Registered on: <?= date('m/d/y', strtotime($app['created_at'])) ?></p>
                                </div>
                            </div>
                            
                            <div class="app-info">
                                <div class="info-item">
                                    <span class="info-label">Description:</span>
                                    <span class="info-value"><?= Helpers::escape($app['stall_description']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value"><?= Helpers::escape($app['location']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Food type:</span>
                                    <span class="info-value">
                                        <?php
                                        $categories = json_decode($app['food_categories'], true);
                                        echo is_array($categories) ? Helpers::escape(implode(', ', $categories)) : 'N/A';
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Hours:</span>
                                    <span class="info-value">Not specified</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Applicant Email:</span>
                                    <span class="info-value"><?= Helpers::escape($app['applicant_email']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Map Coordinates:</span>
                                    <span class="info-value">
                                        <?= $app['map_x'] ? number_format($app['map_x'], 2) . '%, ' . number_format($app['map_y'], 2) . '%' : 'Not set' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Documents Section -->
                            <div class="documents-section">
                                <h4 class="documents-title">Application Documents</h4>
                                <div class="documents-grid">
                                    <?php if ($app['bir_registration_path']): ?>
                                        <div class="document-item">
                                            <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $app['bir_registration_path'])): ?>
                                                <img src="<?= BASE_URL . Helpers::escape($app['bir_registration_path']) ?>" 
                                                     alt="BIR Registration" 
                                                     class="document-preview"
                                                     onclick="window.open('<?= BASE_URL . Helpers::escape($app['bir_registration_path']) ?>', '_blank')">
                                            <?php else: ?>
                                                <div class="document-preview" style="display: flex; align-items: center; justify-content: center; background: #f0f0f0;">
                                                    <i class="fas fa-file-pdf" style="font-size: 48px; color: #ED6027;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="document-label">BIR Registration</div>
                                            <a href="<?= BASE_URL . Helpers::escape($app['bir_registration_path']) ?>" 
                                               target="_blank" 
                                               class="document-link">
                                                <i class="fas fa-download"></i> View
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['business_permit_path']): ?>
                                        <div class="document-item">
                                            <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $app['business_permit_path'])): ?>
                                                <img src="<?= BASE_URL . Helpers::escape($app['business_permit_path']) ?>" 
                                                     alt="Business Permit" 
                                                     class="document-preview"
                                                     onclick="window.open('<?= BASE_URL . Helpers::escape($app['business_permit_path']) ?>', '_blank')">
                                            <?php else: ?>
                                                <div class="document-preview" style="display: flex; align-items: center; justify-content: center; background: #f0f0f0;">
                                                    <i class="fas fa-file-pdf" style="font-size: 48px; color: #ED6027;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="document-label">Business Permit</div>
                                            <a href="<?= BASE_URL . Helpers::escape($app['business_permit_path']) ?>" 
                                               target="_blank" 
                                               class="document-link">
                                                <i class="fas fa-download"></i> View
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['dti_sec_path']): ?>
                                        <div class="document-item">
                                            <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $app['dti_sec_path'])): ?>
                                                <img src="<?= BASE_URL . Helpers::escape($app['dti_sec_path']) ?>" 
                                                     alt="DTI/SEC" 
                                                     class="document-preview"
                                                     onclick="window.open('<?= BASE_URL . Helpers::escape($app['dti_sec_path']) ?>', '_blank')">
                                            <?php else: ?>
                                                <div class="document-preview" style="display: flex; align-items: center; justify-content: center; background: #f0f0f0;">
                                                    <i class="fas fa-file-pdf" style="font-size: 48px; color: #ED6027;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="document-label">DTI / SEC</div>
                                            <a href="<?= BASE_URL . Helpers::escape($app['dti_sec_path']) ?>" 
                                               target="_blank" 
                                               class="document-link">
                                                <i class="fas fa-download"></i> View
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['stall_logo_path']): ?>
                                        <div class="document-item">
                                            <img src="<?= BASE_URL . Helpers::escape($app['stall_logo_path']) ?>" 
                                                 alt="Stall Logo" 
                                                 class="document-preview"
                                                 onclick="window.open('<?= BASE_URL . Helpers::escape($app['stall_logo_path']) ?>', '_blank')">
                                            <div class="document-label">Stall Logo</div>
                                            <a href="<?= BASE_URL . Helpers::escape($app['stall_logo_path']) ?>" 
                                               target="_blank" 
                                               class="document-link">
                                                <i class="fas fa-download"></i> View
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Review Section -->
                            <form method="POST" class="review-section">
                                <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                <textarea 
                                    name="review_notes" 
                                    class="review-textarea" 
                                    placeholder="Add review notes (optional)..."
                                ></textarea>
                                
                                <div class="action-buttons">
                                    <button type="submit" name="action" value="approve" class="btn-approve">
                                        Approve
                                    </button>
                                    <button type="submit" name="action" value="decline" class="btn-decline">
                                        Decline
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <button class="pagination-btn">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="pagination-btn">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($currentTab === 'recent-reviews'): ?>
                <h2 class="section-title">Recent Reviews for Moderation</h2>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Review Moderation</h3>
                    <p>This feature is coming soon.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
