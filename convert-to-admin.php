<?php
/**
 * BuzzarFeed - Convert User to Admin
 * 
 * Page for administrators to convert regular users to admin
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;
use BuzzarFeed\Services\AdminLogService;

Session::start();

// Check if user is logged in and is an admin
if (!Session::isLoggedIn()) {
    Session::setFlash('Please log in to access this page.', 'error');
    Helpers::redirect('login.php');
    exit;
}

if (Session::get('user_type') !== 'admin') {
    Session::setFlash('Access denied. Admin privileges required.', 'error');
    Helpers::redirect('index.php');
    exit;
}

$db = Database::getInstance();
$logService = new AdminLogService();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userEmail = Helpers::sanitize(Helpers::post('user_email', ''));
    
    if (empty($userEmail)) {
        $error = 'Please enter a user email address.';
    } elseif (!Helpers::validateEmail($userEmail)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if user exists
            $user = $db->querySingle(
                "SELECT u.*, ut.type_name 
                 FROM users u
                 JOIN user_types ut ON u.user_type_id = ut.user_type_id
                 WHERE u.email = ?",
                [$userEmail]
            );
            
            if (!$user) {
                $error = 'No user found with this email address.';
            } elseif ($user['type_name'] === 'admin') {
                $error = 'This user is already an admin.';
            } else {
                // Get admin user_type_id
                $adminType = $db->querySingle(
                    "SELECT user_type_id FROM user_types WHERE type_name = 'admin'"
                );
                
                if (!$adminType) {
                    throw new \Exception('Admin user type not found in database.');
                }
                
                // Start transaction
                $db->execute("START TRANSACTION");
                
                try {
                    // If user is a food stall owner, remove their stalls
                    if ($user['type_name'] === 'food_stall_owner') {
                        // Get all stalls owned by this user
                        $stalls = $db->query(
                            "SELECT stall_id FROM food_stalls WHERE owner_id = ?",
                            [$user['user_id']]
                        );
                        
                        // Delete each stall and related data
                        foreach ($stalls as $stall) {
                            $stallId = $stall['stall_id'];
                            
                            // Delete review reactions first (foreign key constraint)
                            $reviews = $db->query("SELECT review_id FROM reviews WHERE stall_id = ?", [$stallId]);
                            foreach ($reviews as $review) {
                                $db->execute("DELETE FROM review_reactions WHERE review_id = ?", [$review['review_id']]);
                            }
                            
                            // Delete reviews
                            $db->execute("DELETE FROM reviews WHERE stall_id = ?", [$stallId]);
                            
                            // Delete menu items
                            $db->execute("DELETE FROM menu_items WHERE stall_id = ?", [$stallId]);
                            
                            // Delete stall location
                            $db->execute("DELETE FROM stall_locations WHERE stall_id = ?", [$stallId]);
                            
                            // Delete the stall itself
                            $db->execute("DELETE FROM food_stalls WHERE stall_id = ?", [$stallId]);
                        }
                        
                        // Delete any pending applications
                        $db->execute("DELETE FROM applications WHERE user_id = ?", [$user['user_id']]);
                    }
                    
                    // Convert user to admin
                    $db->execute(
                        "UPDATE users SET user_type_id = ?, updated_at = NOW() WHERE user_id = ?",
                        [$adminType['user_type_id'], $user['user_id']]
                    );
                    
                    // Commit transaction
                    $db->execute("COMMIT");
                    
                    // Log admin action
                    $adminId = Session::get('user_id');
                    if ($adminId) {
                        $logService->logUserConversion(
                            $adminId,
                            $user['user_id'],
                            $user['name'],
                            $userEmail
                        );
                    }
                    
                    $success = "User '{$user['name']}' ({$userEmail}) has been successfully converted to admin.";
                    
                    if ($user['type_name'] === 'food_stall_owner') {
                        $success .= " All their stalls have been removed.";
                    }
                    
                    error_log("Admin Conversion: User {$user['user_id']} converted to admin by " . Session::get('user_name'));
                    
                } catch (\Exception $e) {
                    $db->execute("ROLLBACK");
                    throw $e;
                }
            }
        } catch (\Exception $e) {
            error_log("Convert to Admin Error: " . $e->getMessage());
            $error = 'An error occurred while converting the user. Please try again.';
        }
    }
}

$pageTitle = "Convert User to Admin - BuzzarFeed";
$pageDescription = "Convert a user to admin with full privileges";
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
            background: #489A44;
        }
        
        main {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .convert-container {
            background: #FEEED5;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }
        
        .convert-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .convert-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #2C2C2C;
            margin: 0 0 20px 0;
        }
        
        .warning-box {
            background: #FFF4E5;
            border-left: 4px solid #ED6027;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .warning-box p {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #2C2C2C;
        }
        
        .warning-box p:last-child {
            margin-bottom: 0;
        }
        
        .warning-box strong {
            color: #ED6027;
        }
        
        .warning-box ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        
        .warning-box li {
            margin-bottom: 8px;
            font-size: 14px;
            color: #2C2C2C;
        }
        
        .caution-text {
            background: #FFE5E5;
            border: 2px solid #ED6027;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .caution-text p {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #2C2C2C;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: #2C2C2C;
            margin-bottom: 10px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            border: 2px solid #2C2C2C;
            border-radius: 8px;
            font-family: 'Sora', sans-serif;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #ED6027;
        }
        
        .form-input::placeholder {
            color: #999;
        }
        
        .btn-convert {
            width: 100%;
            padding: 16px;
            background: #ED6027;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-convert:hover {
            background: #d45521;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(237, 96, 39, 0.3);
        }
        
        .action-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #E0E0E0;
        }
        
        .link-back {
            color: #ED6027;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .link-back:hover {
            text-decoration: underline;
        }
        
        .link-home {
            color: #489A44;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        
        .link-home:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .alert-error {
            background: #FFE5E5;
            color: #D32F2F;
            border: 2px solid #D32F2F;
        }
        
        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 2px solid #2E7D32;
        }
        
        @media (max-width: 768px) {
            .convert-container {
                padding: 30px 20px;
            }
            
            .convert-header h1 {
                font-size: 24px;
            }
            
            .action-links {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <div class="convert-container">
            <div class="convert-header">
                <h1>Convert User to Admin</h1>
            </div>
            
            <!-- Warning Box -->
            <div class="warning-box">
                <p><strong>Converting a user to admin will:</strong></p>
                <ul>
                    <li>Remove ALL stalls they currently own</li>
                    <li>Prevent them from registering new stalls</li>
                    <li>Give them full admin privileges</li>
                </ul>
            </div>
            
            <!-- Caution Notice -->
            <div class="caution-text">
                <p>This action cannot be easily undone.</p>
            </div>
            
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?= Helpers::escape($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= Helpers::escape($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Convert Form -->
            <form method="POST" action="convert-to-admin.php">
                <div class="form-group">
                    <label for="user_email" class="form-label">User Email</label>
                    <input 
                        type="email" 
                        id="user_email" 
                        name="user_email" 
                        class="form-input" 
                        placeholder="Enter email of the user to convert to admin"
                        required
                        value="<?= Helpers::escape(Helpers::post('user_email', '')) ?>"
                    >
                </div>
                
                <button type="submit" class="btn-convert">
                    CONVERT TO ADMIN
                </button>
            </form>
            
            <!-- Action Links -->
            <div class="action-links">
                <a href="admin-panel.php" class="link-back">
                    <i class="fas fa-arrow-left"></i> Back to Admin Panel
                </a>
                <a href="<?= BASE_URL ?>" class="link-home">
                    Home
                </a>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
