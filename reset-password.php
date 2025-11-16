<?php
/**
 * BuzzarFeed - Reset Password Page
 * 
 * Allow users to reset their password using a valid token
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Components\Common\Button;
use BuzzarFeed\Components\Common\Input;
use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;

// Start session
Session::start();

// Redirect if already logged in
if (Session::isLoggedIn()) {
    Helpers::redirect('index.php');
}

$pageTitle = "Reset Password - BuzzarFeed";
$pageDescription = "Create a new password for your account";

// Get token from URL
$token = Helpers::get('token', '');
$errors = [];
$success = '';
$tokenValid = false;
$user = null;

// Validate token
if (empty($token)) {
    $errors[] = "Invalid or missing reset token.";
} else {
    try {
        $db = Database::getInstance();
        
        // Check if token exists and is valid
        $tokenData = $db->querySingle(
            "SELECT prt.*, u.user_id, u.name, u.email 
             FROM password_reset_tokens prt
             INNER JOIN users u ON prt.user_id = u.user_id
             WHERE prt.token = ? 
             AND prt.used = 0 
             AND prt.expires_at > NOW()",
            [$token]
        );
        
        if ($tokenData) {
            $tokenValid = true;
            $user = $tokenData;
        } else {
            $errors[] = "This password reset link is invalid or has expired. Please request a new one.";
        }
        
    } catch (\Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        $errors[] = "An error occurred. Please try again.";
    }
}

// Handle form submission
if (Helpers::isPost() && $tokenValid) {
    $newPassword = Helpers::post('new_password', '');
    $confirmPassword = Helpers::post('confirm_password', '');
    
    // Validation
    if (empty($newPassword)) {
        $errors[] = "New password is required.";
    } else {
        $passwordValidation = Helpers::validatePassword($newPassword);
        if (!$passwordValidation['valid']) {
            $errors[] = $passwordValidation['message'];
        }
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Hash new password
            $hashedPassword = Helpers::hashPassword($newPassword);
            
            // Begin transaction
            $db->beginTransaction();
            
            // Update user password
            $db->execute(
                "UPDATE users SET hashed_password = ?, updated_at = NOW() WHERE user_id = ?",
                [$hashedPassword, $user['user_id']]
            );
            
            // Mark token as used
            $db->execute(
                "UPDATE password_reset_tokens SET used = 1 WHERE token = ?",
                [$token]
            );
            
            // Commit transaction
            $db->commit();
            
            $success = true;
            Session::setFlash('Your password has been reset successfully. Please log in with your new password.', 'success');
            
            // Redirect to login page after 2 seconds
            header("Refresh: 2; url=login.php");
            
        } catch (\Exception $e) {
            $db->rollback();
            error_log("Password reset error: " . $e->getMessage());
            
            if (DEVELOPMENT_MODE) {
                $errors[] = "Error: " . $e->getMessage();
            } else {
                $errors[] = "An error occurred while resetting your password. Please try again.";
            }
        }
    }
}
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
    
    <!-- Modular CSS Architecture -->
    <link rel="stylesheet" href="<?= CSS_URL ?>/variables.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/base.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/button.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/forms.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/dropdown.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/styles.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/signup.css">
    
    <style>
        .reset-password-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-color: var(--color-primary-beige);
        }
        
        .reset-password-container {
            max-width: 500px;
            width: 100%;
        }
        
        .reset-password-card {
            background: white;
            border-radius: 24px;
            padding: 3rem 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border: 3px solid rgba(44, 44, 44, 0.15);
        }
        
        .reset-password-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .reset-password-icon {
            width: 80px;
            height: 80px;
            background-color: var(--color-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .reset-password-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .reset-password-header h1 {
            font-size: 1.875rem;
            margin-bottom: 0.75rem;
        }
        
        .reset-password-header p {
            font-size: 1rem;
            color: var(--color-text-light);
            line-height: 1.6;
        }
        
        .success-message {
            text-align: center;
            padding: 2rem;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background-color: var(--color-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .success-icon i {
            font-size: 3rem;
            color: white;
        }
        
        .password-requirements {
            background-color: #FFF3E0;
            border-left: 4px solid var(--color-orange);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .password-requirements h4 {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: var(--color-dark);
        }
        
        .password-requirements ul {
            list-style: disc;
            padding-left: 1.5rem;
            margin: 0;
        }
        
        .password-requirements li {
            font-size: 0.813rem;
            color: var(--color-text-light);
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="reset-password-page">
        <div class="reset-password-container">
            <div class="reset-password-card">
                <?php if ($success): ?>
                    <!-- Success State -->
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h1>Password Reset Successfully!</h1>
                        <p style="color: var(--color-text-light); margin-top: 1rem;">
                            Your password has been changed. Redirecting you to the login page...
                        </p>
                    </div>
                <?php elseif (!$tokenValid): ?>
                    <!-- Invalid Token State -->
                    <div class="reset-password-header">
                        <div class="reset-password-icon" style="background-color: var(--color-error);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h1>Invalid Reset Link</h1>
                        <p>This password reset link is invalid or has expired.</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= Helpers::escape($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="forgot-password.php" class="btn btn-primary">
                            Request New Reset Link
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Reset Password Form -->
                    <div class="reset-password-header">
                        <div class="reset-password-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h1>Create New Password</h1>
                        <p>Enter your new password below.</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= Helpers::escape($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="password-requirements">
                        <h4><i class="fas fa-info-circle"></i> Password Requirements:</h4>
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>Contains at least one uppercase letter</li>
                            <li>Contains at least one lowercase letter</li>
                            <li>Contains at least one number</li>
                        </ul>
                    </div>
                    
                    <form method="POST" action="reset-password.php?token=<?= urlencode($token) ?>">
                        <?php
                        echo Input::make([
                            'name' => 'new_password',
                            'type' => Input::TYPE_PASSWORD,
                            'label' => 'New Password',
                            'placeholder' => 'Enter your new password',
                            'required' => true,
                            'showToggle' => true
                        ])->render();
                        
                        echo Input::make([
                            'name' => 'confirm_password',
                            'type' => Input::TYPE_PASSWORD,
                            'label' => 'Confirm New Password',
                            'placeholder' => 'Confirm your new password',
                            'required' => true,
                            'showToggle' => true
                        ])->render();
                        
                        echo Button::make([
                            'text' => 'RESET PASSWORD',
                            'type' => 'submit',
                            'variant' => Button::VARIANT_PRIMARY,
                            'class' => 'btn-full'
                        ])->render();
                        ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
