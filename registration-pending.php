<?php
/**
 * BuzzarFeed - Registration Pending Page
 * 
 * Success page shown after stall registration submission
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;

Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    Helpers::redirect('login.php');
    exit;
}

// Check if user is a stall owner
if (Session::get('user_type') !== 'food_stall_owner') {
    Session::setFlash('Access denied. This page is only for stall owners.', 'error');
    Helpers::redirect('index.php');
    exit;
}

// Check if user actually has a pending application
try {
    $db = Database::getInstance();
    $userId = Session::getUserId();
    
    $pendingApp = $db->querySingle(
        "SELECT application_id, stall_name, created_at FROM applications 
         WHERE user_id = ? AND current_status_id = 1",
        [$userId]
    );
    
    if (!$pendingApp) {
        // No pending application found
        Session::setFlash('You do not have any pending applications.', 'info');
        Helpers::redirect('index.php');
        exit;
    }
} catch (\Exception $e) {
    error_log("Error checking pending application: " . $e->getMessage());
    Session::setFlash('An error occurred. Please try again.', 'error');
    Helpers::redirect('index.php');
    exit;
}

$pageTitle = "Application Pending - BuzzarFeed";
$pageDescription = "Your stall registration application is pending approval";
$userName = Session::get('user_name', 'there');
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
        }
        
        main {
            flex: 1 0 auto;
        }
        
        /* Success Section */
        .success-section {
            background: linear-gradient(135deg, #ED6027 0%, #E8663E 100%);
            padding: 80px 20px;
            text-align: center;
            min-height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .success-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .success-title {
            font-size: 48px;
            font-weight: 700;
            color: white;
            margin: 0 0 20px 0;
            line-height: 1.2;
        }
        
        .success-subtitle {
            font-size: 18px;
            color: #FEEED5;
            margin: 0 0 60px 0;
        }
        
        /* Illustration */
        .illustration {
            margin-bottom: 50px;
        }
        
        .illustration-icon {
            font-size: 200px;
            color: white;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }
        
        .illustration-icon .person {
            position: relative;
            z-index: 2;
        }
        
        .illustration-icon .clock {
            position: absolute;
            font-size: 80px;
            top: -20px;
            left: -80px;
            color: white;
        }
        
        /* Message Box */
        .message-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 40px;
        }
        
        .message-text {
            font-size: 16px;
            color: #FEEED5;
            line-height: 1.8;
            margin: 0 0 20px 0;
        }
        
        .message-text strong {
            color: white;
            font-weight: 600;
        }
        
        .email-notice {
            font-size: 14px;
            color: #FEEED5;
            margin: 0;
            font-style: italic;
        }
        
        /* Action Button */
        .action-btn {
            background: #FEEED5;
            color: #2C2C2C;
            padding: 16px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .action-btn:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 768px) {
            .success-title {
                font-size: 36px;
            }
            
            .illustration-icon {
                font-size: 150px;
            }
            
            .illustration-icon .clock {
                font-size: 60px;
                left: -60px;
            }
            
            .message-box {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <section class="success-section">
            <div class="success-content">
                <h1 class="success-title">We've got your application!</h1>
                <p class="success-subtitle">Your application is pending admin approval.</p>
                
                <!-- Illustration -->
                <div class="illustration">
                    <div class="illustration-icon">
                        <i class="fas fa-clock clock"></i>
                        <i class="fas fa-user-check person"></i>
                    </div>
                </div>
                
                <!-- Message Box -->
                <div class="message-box">
                    <p class="message-text">
                        Your stall application is now under review. Our team will <strong>closely</strong> your details and <strong>notify you once your application has been reviewed.</strong> This process typically takes <strong>3-5 business days.</strong>
                    </p>
                    <p class="message-text">
                        In the meantime, you can continue to use BuzzarFeed as a food enthusiast to discover and review amazing food stalls at BGC Night Market Bazaar.
                    </p>
                    <p class="email-notice">
                        📧 We will email you the application result at your registered email address.
                    </p>
                </div>
                
                <!-- Action Button -->
                <a href="<?= BASE_URL ?>my-account.php" class="action-btn">
                    Go to My Account
                </a>
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
