<?php
/**
 * BuzzarFeed - Sign Up Page (Using Modular Architecture)
 *
 * User registration page using new component system
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
use BuzzarFeed\Utils\EmailService;

// Start session for this page
Session::start();

// Redirect if already logged in
if (Session::isLoggedIn()) {
    Helpers::redirect('index.php');
}

// Page metadata
$pageTitle = "Sign Up - BuzzarFeed";
$pageDescription = "Create your BuzzarFeed account and start exploring food stalls or managing your own stall.";

// Handle form submission
$errors = [];
$success = false;

if (Helpers::isPost()) {
    // Get form data
    $name = Helpers::sanitize(Helpers::post('name', ''));
    $email = Helpers::sanitize(Helpers::post('email', ''));
    $password = Helpers::post('password', '');
    $confirmPassword = Helpers::post('confirm_password', '');
    $accountType = Helpers::post('account_type', '');
    $termsAgreed = Helpers::post('terms_agreed') !== null;

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!Helpers::validateEmail($email)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } else {
        $passwordValidation = Helpers::validatePassword($password);
        if (!$passwordValidation['valid']) {
            $errors[] = $passwordValidation['message'];
        }
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($accountType) || !in_array($accountType, [ACCOUNT_TYPE_ENTHUSIAST, ACCOUNT_TYPE_OWNER])) {
        $errors[] = "Please select an account type.";
    }

    if (!$termsAgreed) {
        $errors[] = "You must agree to the Terms of Service.";
    }

    // If no errors, process registration
    if (empty($errors)) {
        try {
            // Get database instance
            $db = Database::getInstance();

            // Check if email already exists
            $existingUser = $db->querySingle(
                "SELECT user_id FROM users WHERE email = ?",
                [$email]
            );

            if ($existingUser) {
                $errors[] = "Email address is already registered.";
            }

            // Check if name already exists
            $existingName = $db->querySingle(
                "SELECT user_id FROM users WHERE name = ?",
                [$name]
            );

            if ($existingName) {
                $errors[] = "This name is already taken. Please choose a different name.";
            }

            if (empty($errors)) {
                // Hash the password
                $hashedPassword = Helpers::hashPassword($password);

                // Get user_type_id based on account type
                $userTypeMap = [
                    ACCOUNT_TYPE_ENTHUSIAST => 'food_enthusiast',
                    ACCOUNT_TYPE_OWNER => 'food_stall_owner'
                ];

                $userTypeName = $userTypeMap[$accountType] ?? 'food_enthusiast';

                // Debug: Log the user type being searched
                error_log("Looking for user type: " . $userTypeName);

                $userType = $db->querySingle(
                    "SELECT user_type_id FROM user_types WHERE type_name = ?",
                    [$userTypeName]
                );

                // Debug: Log the result
                error_log("User type result: " . print_r($userType, true));

                if (!$userType) {
                    throw new \Exception("Invalid user type: " . $userTypeName . ". Please ensure user_types table has data.");
                }

                // Insert user into database
                $query = "INSERT INTO users (name, email, hashed_password, user_type_id, is_active, email_verified)
                          VALUES (?, ?, ?, ?, 1, 0)";

                // Debug: Log the query parameters
                error_log("Inserting user: name=$name, email=$email, user_type_id=" . $userType['user_type_id']);

                $result = $db->execute($query, [
                    $name,
                    $email,
                    $hashedPassword,
                    $userType['user_type_id']
                ]);

                // Get the newly created user ID
                $newUserId = $db->lastInsertId();

                // Debug: Log the result
                error_log("Insert result: " . $result . ", New user ID: " . $newUserId);

                // Auto-login: Set session variables
                Session::set('user_id', $newUserId);
                Session::set('user_name', $name);
                Session::set('user_email', $email);
                Session::set('user_type_id', $userType['user_type_id']);
                Session::set('user_type', $userTypeName);
                Session::regenerate();

                // Send welcome email (non-blocking - don't fail registration if email fails)
                try {
                    $emailService = EmailService::getInstance();
                    $emailSent = $emailService->sendWelcomeEmail($email, $name);

                    if ($emailSent) {
                        error_log("Welcome email sent successfully to: {$email}");
                    } else {
                        error_log("Failed to send welcome email to: {$email}");
                    }
                } catch (\Exception $emailError) {
                    error_log("Email error: " . $emailError->getMessage());
                    // Continue with registration even if email fails
                }

                $success = true;
                Session::setFlash('Welcome to BuzzarFeed, ' . $name . '! Your account has been created successfully.', 'success');

                // Redirect to homepage immediately (auto-login)
                Helpers::redirect('index.php');
            }
        } catch (\PDOException $e) {
            error_log("Database Error: " . $e->getMessage());

            // Show detailed error in development mode
            if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
                $errors[] = "Database Error: " . $e->getMessage();
                $errors[] = "Error Code: " . $e->getCode();
                $errors[] = "File: " . $e->getFile() . " (Line " . $e->getLine() . ")";
            } else {
                $errors[] = "A database error occurred. Please try again.";
            }
        } catch (\Exception $e) {
            error_log("Signup Error: " . $e->getMessage());

            // Show detailed error in development mode
            if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
                $errors[] = "Registration Error: " . $e->getMessage();
                $errors[] = "File: " . $e->getFile();
                $errors[] = "Line: " . $e->getLine();
            } else {
                $errors[] = "An error occurred during registration. Please try again.";
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
    <link rel="stylesheet" href="<?= CSS_URL ?>/signup.css">
</head>
<body class="signup-page">
    <!-- Header Navigation -->
    <?php require INCLUDES_PATH . '/header.php'; ?>

    <!-- Sign Up Section -->
    <section class="signup-section">
        <div class="container signup-container">
            <!-- Left Side - Polaroid Images -->
            <div class="signup-visual">
              <img src="../assets/images/Sign-Up-Image.png" alt="Sign Up Visual">
            </div>

            <!-- Right Side - Sign Up Form -->
            <div class="signup-form-wrapper">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>Back</span>
                </a>

                <h1 class="signup-title">Sign up</h1>
                <p class="signup-subtitle">
                    Already have an account? <a href="login.php" class="link-orange">Sign in</a>
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
                        <p>Account created successfully! <a href="login.php">Sign in now</a></p>
                    </div>
                <?php endif; ?>

                <form action="signup.php" method="POST" class="signup-form" id="signupForm">
                    <!-- Name Field using Input component -->
                    <?php
                    echo Input::make([
                        'name' => 'name',
                        'type' => Input::TYPE_TEXT,
                        'placeholder' => 'Name',
                        'value' => Helpers::post('name', ''),
                        'required' => true,
                        'error' => ''
                    ])->render();
                    ?>

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

                    <!-- Confirm Password Field using Input component -->
                    <?php
                    echo Input::make([
                        'name' => 'confirm_password',
                        'type' => Input::TYPE_PASSWORD,
                        'placeholder' => 'Confirm Password',
                        'required' => true,
                        'showToggle' => true,
                        'error' => ''
                    ])->render();
                    ?>

                    <!-- Account Type Selection -->
                    <div class="form-group">
                        <label class="form-label">Account type:</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input
                                    type="radio"
                                    name="account_type"
                                    value="<?= ACCOUNT_TYPE_ENTHUSIAST ?>"
                                    <?= (Helpers::post('account_type') === ACCOUNT_TYPE_ENTHUSIAST) ? 'checked' : '' ?>
                                    required
                                >
                                <span class="radio-label">
                                    <span class="radio-dot"></span>
                                    Food enthusiast
                                </span>
                            </label>
                            <label class="radio-option">
                                <input
                                    type="radio"
                                    name="account_type"
                                    value="<?= ACCOUNT_TYPE_OWNER ?>"
                                    <?= (Helpers::post('account_type') === ACCOUNT_TYPE_OWNER) ? 'checked' : '' ?>
                                >
                                <span class="radio-label">
                                    <span class="radio-dot"></span>
                                    Food stall owner
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Terms Agreement -->
                    <div class="form-group">
                        <label class="checkbox-option">
                            <input
                                type="checkbox"
                                name="terms_agreed"
                                id="terms_agreed"
                                <?= Helpers::post('terms_agreed') ? 'checked' : '' ?>
                                required
                            >
                            <span class="checkbox-label">
                                <span class="checkbox-box"></span>
                                I agree to all statements in <a href="terms.php" class="link-orange" target="_blank">Terms of Services</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button using Button component -->
                    <?php
                    echo Button::make([
                        'text' => 'CREATE ACCOUNT',
                        'type' => 'submit',
                        'variant' => Button::VARIANT_PRIMARY,
                        'size' => Button::SIZE_LARGE,
                        'class' => 'btn-full btn-submit'
                    ])->render();
                    ?>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php require INCLUDES_PATH . '/footer.php'; ?>

    <!-- Modular JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
