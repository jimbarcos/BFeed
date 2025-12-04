<?php
/**
 * BuzzarFeed - Forgot Password Page
 *
 * Request password reset via email
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
use BuzzarFeed\Utils\EmailService;

// Start session
Session::start();

// Redirect if already logged in
if (Session::isLoggedIn()) {
    Helpers::redirect('index.php');
}

$pageTitle = "Forgot Password - BuzzarFeed";
$pageDescription = "Reset your BuzzarFeed password";

// Handle form submission
$errors = [];
$success = '';

if (Helpers::isPost()) {
    $email = Helpers::sanitize(Helpers::post('email', ''));

    // Validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!Helpers::validateEmail($email)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($errors)) {
        try {
            $db = Database::getInstance();

            // Check if user exists
            $user = $db->querySingle(
                "SELECT user_id, name, email FROM users WHERE email = ?",
                [$email]
            );

            if ($user) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Get user IP and user agent
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

                // Save token to database
                $db->execute(
                    "INSERT INTO password_reset_tokens (user_id, token, expires_at, ip_address, user_agent)
                     VALUES (?, ?, ?, ?, ?)",
                    [$user['user_id'], $token, $expiresAt, $ipAddress, $userAgent]
                );

                // Create reset link
                $resetLink = BASE_URL . "reset-password.php?token=" . urlencode($token);

                // Send email
                try {
                    $emailService = EmailService::getInstance();
                    $emailSent = $emailService->sendPasswordResetEmail(
                        $user['email'],
                        $user['name'],
                        $token,
                        $resetLink
                    );

                    if ($emailSent) {
                        $success = "Password reset instructions have been sent to your email address. Please allow 2-3 minutes for the email to arrive.";
                        error_log("Password reset email sent successfully to: {$email}");
                    } else {
                        $errors[] = "Failed to send password reset email. Please check the error logs.";
                        error_log("Email service returned false for: {$email}");
                    }
                } catch (\Exception $emailException) {
                    error_log("Email exception: " . $emailException->getMessage());
                    if (DEVELOPMENT_MODE) {
                        $errors[] = "Email Error: " . $emailException->getMessage();
                    } else {
                        $errors[] = "Failed to send password reset email. Please try again later.";
                    }
                }
            } else {
                // Don't reveal if email exists or not (security best practice)
                $success = "If an account exists with that email, password reset instructions have been sent.";
                error_log("Password reset requested for non-existent email: {$email}");
            }

        } catch (\Exception $e) {
            error_log("Forgot Password Error: " . $e->getMessage());

            if (DEVELOPMENT_MODE) {
                $errors[] = "Error: " . $e->getMessage();
            } else {
                $errors[] = "An error occurred. Please try again later.";
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
        body {
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 150vh;
            background: var(--color-primary-beige);
        }

        main {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3.5rem 1.25rem;
        }

        .forgot-password-page {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .forgot-password-container {
            max-width: 700px;
            width: 100%;
        }

        .forgot-password-card {
            background: var(--color-orange);
            border: 3px solid #494646;
            box-shadow: 5px 5px 0 #494646;
            padding: 3rem;
            color: var(--color-white);
        }

        .forgot-password-header h1 {
            font-size: 2rem;
            margin: 0 0 1.25rem 0;
            color: var(--color-white);
            font-weight: 800;
            text-align: left;
        }

        .forgot-password-card .form-group {
            margin-bottom: 1.25rem;
        }

        .forgot-password-card .form-label {
            color: var(--color-white);
            text-align: left;
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.95rem;
            font-weight: 100;
        }

        .forgot-password-card input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #2C2C2C;
            border-radius: 5px;
            font-size: 1rem;
            background: #FEEED5;
            color: var(--color-dark);
        }

        .forgot-password-card input[type="email"]::placeholder {
            color: #6b6b6b;
        }

        .forgot-password-card .btn-reset {
            width: 100%;
            justify-content: center;
        }

        .back-to-login {
            text-align: left;
            margin-top: 1rem;
        }

        .back-to-login a {
            color: var(--color-white);
            text-decoration: none;
            font-size: 0.95rem;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: left;
        }

        .alert-error {
            background: rgba(232, 102, 62, 0.9);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .alert-error ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .alert-success {
            background: rgba(72, 154, 68, 0.9);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .signin-link {
          font-weight: 500; /* medium */
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="forgot-password-page">
            <div class="forgot-password-container">
                <div class="forgot-password-card">
                    <div class="forgot-password-header">
                        <h1>Forgot Password</h1>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul style="margin: 0; padding-left: 1.25rem;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= Helpers::escape($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p style="margin: 0; color: var(--color-primary-beige);"><?= Helpers::escape($success) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="forgot-password.php">
                        <div class="form-group">
                            <label class="form-label">Email address</label>
                            <input
                                type="email"
                                name="email"
                                placeholder="Enter your email"
                                required
                                value="<?= Helpers::escape(Helpers::post('email', '')) ?>"
                            >
                        </div>

                        <?php
                        echo Button::make([
                            'text' => 'RESET PASSWORD',
                            'type' => 'submit',
                            'variant' => Button::VARIANT_PRIMARY,
                            'class' => 'btn-full btn-reset'
                        ])->render();
                        ?>
                    </form>

                    <div class="back-to-login">
                        <a href="login.php">
                            Remember your password? <span class="signin-link">Sign In</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
