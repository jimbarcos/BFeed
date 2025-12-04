<?php
/**
 * BuzzarFeed - Login Page (Using Modular Architecture)
 *
 * User login page using new component system
 * Following ISO 9241: Maintainability, Reusability, Extensibility
 *
 * @package BuzzarFeed
 * @version 2.0
 * @author BuzzarFeed Development Team
 * @date November 2025
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/bootstrap.php';
} catch (Exception $e) {
    die("Bootstrap Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}

use BuzzarFeed\Components\Common\Button;
use BuzzarFeed\Components\Common\Input;
use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;

// Start session for this page
Session::start();

// Redirect if already logged in
if (Session::isLoggedIn()) {
    Helpers::redirect('index.php');
}

// Page metadata
$pageTitle = "Log In - BuzzarFeed";
$pageDescription = "Log in to your BuzzarFeed account to access your saved stalls and reviews.";

// Handle form submission
$errors = [];
$success = false;

if (Helpers::isPost()) {
    // Get form data
    $email = Helpers::sanitize(Helpers::post('email', ''));
    $password = Helpers::post('password', '');

    // Validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!Helpers::validateEmail($email)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // If no errors, process login
    if (empty($errors)) {
        try {
            // Get database instance
            $db = Database::getInstance();

            // Get user by email with user type information
            $user = $db->querySingle(
                "SELECT u.user_id, u.name, u.email, u.hashed_password, u.user_type_id, u.is_active,
                        ut.type_name
                 FROM users u
                 INNER JOIN user_types ut ON u.user_type_id = ut.user_type_id
                 WHERE u.email = ?",
                [$email]
            );

            if (!$user) {
                $errors[] = "Invalid email or password.";
            } elseif (!$user['is_active']) {
                $errors[] = "Your account has been deactivated. Please contact support.";
            } elseif (!Helpers::verifyPassword($password, $user['hashed_password'])) {
                $errors[] = "Invalid email or password.";
            } else {
                // Login successful - set session variables
                Session::set('user_id', $user['user_id']);
                Session::set('user_name', $user['name']);
                Session::set('user_email', $user['email']);
                Session::set('user_type_id', $user['user_type_id']);
                Session::set('user_type', $user['type_name']);
                Session::regenerate();

                Session::setFlash('Welcome back, ' . $user['name'] . '!', 'success');

                // Redirect to homepage
                Helpers::redirect('index.php');
            }
        } catch (\Exception $e) {
            error_log("Login Error: " . $e->getMessage());

            // Show detailed error in development mode
            if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
                $errors[] = "Login Error: " . $e->getMessage();
                $errors[] = "File: " . $e->getFile();
                $errors[] = "Line: " . $e->getLine();
            } else {
                $errors[] = "An error occurred during login. Please try again.";
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
    <link href="https://api.fontshare.com/v2/css?f[]=geist@400,500,600,700&display=swap" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Modular CSS Architecture -->
    <link rel="stylesheet" href="<?= CSS_URL ?>/variables.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/base.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/button.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/forms.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/dropdown.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/styles.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/login.css">
</head>
<body class="login-page">
    <!-- Header Navigation -->
    <?php require INCLUDES_PATH . '/header.php'; ?>

    <!-- Login Section -->
    <section class="login-section">
        <div class="container login-container">
            <!-- Left Side - Login Form -->
            <div class="login-form-wrapper">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>Back</span>
                </a>

                <h1 class="login-title">Log In</h1>
                <p class="login-subtitle">
                    Don't have an account? <a href="signup.php" class="link-orange">Create account</a>
                </p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= Helpers::escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p>Login successful! Redirecting...</p>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="login-form" id="loginForm">
                    <!-- Email Field using Input component -->
                    <?php
                    echo Input::make([
                        'name' => 'email',
                        'type' => Input::TYPE_EMAIL,
                        'placeholder' => 'Email',
                        'value' => Helpers::post('email', ''),
                        'required' => true,
                        'error' => ''
                    ])->render();
                    ?>

                    <!-- Password Field using Input component -->
                    <?php
                    echo Input::make([
                        'name' => 'password',
                        'type' => Input::TYPE_PASSWORD,
                        'placeholder' => 'Password',
                        'required' => true,
                        'showToggle' => true,
                        'error' => ''
                    ])->render();
                    ?>

                    <!-- Forgot Password Link -->
                    <div class="forgot-password-wrapper">
                        <a href="forgot-password.php" class="forgot-password-link">Forgot password?</a>
                    </div>

                    <!-- Submit Button using Button component -->
                    <?php
                    echo Button::make([
                        'text' => 'SIGN IN',
                        'type' => 'submit',
                        'variant' => Button::VARIANT_SECONDARY,
                        'size' => Button::SIZE_LARGE,
                        'class' => 'btn-full btn-submit'
                    ])->render();
                    ?>
                </form>
            </div>

            <!-- Right Side - Illustration -->
            <div class="login-visual">
              <img src="../assets/images/Login-Image.png" alt="Login illustration">
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php require INCLUDES_PATH . '/footer.php'; ?>

    <!-- Modular JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
