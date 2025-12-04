<?php
/**
 * BuzzarFeed - My Account Page
 *
 * User account management page with profile information and danger zone
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

// Redirect if not logged in
if (!Session::isLoggedIn()) {
    Helpers::redirect('login.php');
}

// Get user data
$userId = Session::get('user_id');
$db = Database::getInstance();

$user = $db->querySingle(
    "SELECT u.*, ut.type_name, ut.description as type_description
     FROM users u
     INNER JOIN user_types ut ON u.user_type_id = ut.user_type_id
     WHERE u.user_id = ?",
    [$userId]
);

if (!$user) {
    Session::destroy();
    Helpers::redirect('login.php');
}

$pageTitle = "My Account - BuzzarFeed";
$pageDescription = "Manage your BuzzarFeed account settings";

// Handle form submissions
$errors = [];
$success = '';
$activeTab = Helpers::get('tab', 'profile'); // profile, password, danger

// Handle profile update
if (Helpers::isPost() && Helpers::post('action') === 'update_profile') {
    $name = Helpers::sanitize(Helpers::post('name', ''));

    if (empty($name)) {
        $errors[] = "Name is required.";
    } else {
        // Check if name already exists (excluding current user)
        $existingName = $db->querySingle(
            "SELECT user_id FROM users WHERE name = ? AND user_id != ?",
            [$name, $userId]
        );

        if ($existingName) {
            $errors[] = "This name is already taken. Please choose a different name.";
        } else {
            try {
                $db->execute(
                    "UPDATE users SET name = ?, updated_at = NOW() WHERE user_id = ?",
                    [$name, $userId]
                );

                Session::set('user_name', $name);
                $user['name'] = $name;
                $success = "Profile updated successfully!";
            } catch (\Exception $e) {
                error_log("Profile Update Error: " . $e->getMessage());
                $errors[] = "Failed to update profile. Please try again.";
            }
        }
    }
    $activeTab = 'profile';
}

// Handle password change
if (Helpers::isPost() && Helpers::post('action') === 'change_password') {
    $currentPassword = Helpers::post('current_password', '');
    $newPassword = Helpers::post('new_password', '');
    $confirmPassword = Helpers::post('confirm_password', '');

    if (empty($currentPassword)) {
        $errors[] = "Current password is required.";
    } elseif (!Helpers::verifyPassword($currentPassword, $user['hashed_password'])) {
        $errors[] = "Current password is incorrect.";
    }

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
            $hashedPassword = Helpers::hashPassword($newPassword);
            $db->execute(
                "UPDATE users SET hashed_password = ?, updated_at = NOW() WHERE user_id = ?",
                [$hashedPassword, $userId]
            );

            $success = "Password changed successfully!";
        } catch (\Exception $e) {
            error_log("Password Change Error: " . $e->getMessage());
            $errors[] = "Failed to change password. Please try again.";
        }
    }
    $activeTab = 'password';
}

// Handle account deletion
if (Helpers::isPost() && Helpers::post('action') === 'delete_account') {
    $confirmEmail = Helpers::post('confirm_email', '');

    if ($confirmEmail !== $user['email']) {
        $errors[] = "Email does not match. Please type your email correctly to confirm deletion.";
    } else {
        try {
            // Begin transaction
            $db->beginTransaction();

            // Delete related records in the correct order (child to parent)
            
            // 1. Delete reviews written by this user
            $db->execute("DELETE FROM reviews WHERE user_id = ?", [$userId]);
            
            // 2. Delete stall applications
            $db->execute("DELETE FROM applications WHERE user_id = ?", [$userId]);
            
            // 3. Get all stalls owned by this user
            $ownedStalls = $db->query("SELECT stall_id FROM food_stalls WHERE owner_id = ?", [$userId]);
            
            if (!empty($ownedStalls)) {
                $stallIds = array_column($ownedStalls, 'stall_id');
                $placeholders = implode(',', array_fill(0, count($stallIds), '?'));
                
                // Delete reviews on owned stalls
                $db->execute("DELETE FROM reviews WHERE stall_id IN ($placeholders)", $stallIds);
                
                // Delete stall locations
                $db->execute("DELETE FROM stall_locations WHERE stall_id IN ($placeholders)", $stallIds);
                
                // Delete menu items
                $db->execute("DELETE FROM menu_items WHERE stall_id IN ($placeholders)", $stallIds);
                
                // Delete food stalls
                $db->execute("DELETE FROM food_stalls WHERE stall_id IN ($placeholders)", $stallIds);
            }
            
            // 4. Delete session tokens
            $db->execute("DELETE FROM session_tokens WHERE user_id = ?", [$userId]);
            
            // 5. Delete reset tokens
            $db->execute("DELETE FROM reset_tokens WHERE user_id = ?", [$userId]);
            
            // 6. Finally, delete the user
            $db->execute("DELETE FROM users WHERE user_id = ?", [$userId]);

            // Commit transaction
            $db->commit();

            // Destroy session
            Session::destroy();

            // Redirect to homepage with message
            Session::start();
            Session::setFlash('Your account has been deleted successfully.', 'success');
            Helpers::redirect('index.php');
        } catch (\Exception $e) {
            $db->rollback();
            error_log("Account Deletion Error: " . $e->getMessage());
            $errors[] = "Failed to delete account. Please try again or contact support. Error: " . $e->getMessage();
        }
    }
    $activeTab = 'danger';
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

    <!-- CSS -->
    <link rel="stylesheet" href="<?= CSS_URL ?>/variables.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/base.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/button.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/forms.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/dropdown.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/styles.css">

    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        /* Body has the page background; main grows to push footer to the bottom */
        .account-page {
            background-color: var(--color-primary-beige);
        }

        /* Main content should grow to fill remaining height */
        main.account-container {
            flex: 1 0 auto;
            width: 100%;
        }

        .account-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 3rem 1.25rem;
            width: 100%;
        }

        .account-header {
            margin-bottom: 1.5rem;
            padding-top: 0;
        }

        /* Prevent default h1 top margin from creating apparent header extension at scroll top */
        .account-header h1 {
            margin-top: 0;
        }

        .account-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .account-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 1.75rem;
        }

        .account-sidebar {
            background: transparent;
            border: 3px solid #2C2C2C;
            border-radius: 0;
            padding: 1rem;
            height: fit-content;
            box-shadow: none;
        }

        .account-tabs {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .account-tabs li {
            margin-bottom: 0.35rem;
        }

        .tab-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            color: var(--color-dark);
            text-decoration: none;
            border-radius: 0;
            border: 3px solid #2C2C2C;
            background: transparent;
            transition: all 0.2s;
        }

        .tab-link:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .tab-link.active {
            background-color: #4A9A4A;
            color: white;
        }

        .tab-link.danger {
            color: var(--color-error);
        }

        .tab-link.danger.active {
            background-color: #C63D2D;
            color: white;
        }

        .account-content {
            background: transparent;
            border: 3px solid #2C2C2C;
            border-radius: 0;
            padding: 1.5rem;
            box-shadow: none;
        }

        /* Square, outlined inputs */
        .account-content input,
        .account-content select,
        .account-content textarea {
            border: 2px solid #2C2C2C;
            border-radius: 5px;
            background: transparent;
            box-shadow: none;
        }

        .account-content input:focus,
        .account-content select:focus,
        .account-content textarea:focus {
            outline: none;
            border-color: #2C2C2C;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .section-title {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--color-border);
        }

        .info-group {
            margin-bottom: 0.9rem;
        }

        .info-label {
            font-weight: 600;
            color: var(--color-text-light);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            color: var(--color-dark);
            padding: 0.75rem;
            background-color: transparent;
            border: 2px solid #2C2C2C;
            border-radius: 5px;
        }

        .danger-zone {
            border: 3px solid var(--color-error);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .danger-zone h3 {
            color: var(--color-error);
            margin-bottom: 1rem;
        }

        .danger-list {
            list-style: disc;
            padding-left: 1rem;
            margin: 0.75rem 0;
            color: var(--color-text);
        }

        .danger-list li {
            margin-bottom: 0.35rem;
        }

        @media (max-width: 768px) {
            .account-layout {
                grid-template-columns: 1fr;
            }

            .account-sidebar {
                order: -1;
            }

            .account-tabs {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
                gap: 0.5rem;
            }
        }

        .account-actions {
            margin: 0.75rem 0 0;
            display: flex;
            justify-content: flex-end;
            padding: 0 0.5rem;
        }

        @media (max-width: 768px) {
            .account-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body class="account-page">
    <!-- Header -->
    <?php require INCLUDES_PATH . '/header.php'; ?>

    <main class="account-container">
        <div class="account-header">
            <h1>My Account</h1>
        </div>

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
                <p><?= Helpers::escape($success) ?></p>
            </div>
        <?php endif; ?>

        <div class="account-layout">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar">
                <ul class="account-tabs">
                    <li>
                        <a href="?tab=profile" class="tab-link <?= $activeTab === 'profile' ? 'active' : '' ?>">
                            <i class="fas fa-user"></i>
                            Profile Information
                        </a>
                    </li>
                    <li>
                        <a href="?tab=password" class="tab-link <?= $activeTab === 'password' ? 'active' : '' ?>">
                            <i class="fas fa-lock"></i>
                            Change Password
                        </a>
                    </li>
                    <li>
                        <a href="?tab=danger" class="tab-link danger <?= $activeTab === 'danger' ? 'active' : '' ?>">
                            <i class="fas fa-exclamation-triangle"></i>
                            Danger Zone
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- Main Content -->
            <div class="account-content">
                <!-- Profile Information Tab -->
                <section class="content-section <?= $activeTab === 'profile' ? 'active' : '' ?>">
                    <form id="profileForm" method="POST" action="my-account.php?tab=profile">
                        <input type="hidden" name="action" value="update_profile">

                        <?php
                        echo Input::make([
                            'name' => 'name',
                            'type' => Input::TYPE_TEXT,
                            'label' => 'Name',
                            'value' => $user['name'],
                            'required' => true
                        ])->render();
                        ?>

                        <div class="info-group">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= Helpers::escape($user['email']) ?></div>
                            <small style="color: var(--color-text-light); margin-top: 0.5rem; display: block;">
                                Email address cannot be changed.
                            </small>
                        </div>

                        <div class="info-group">
                            <div class="info-label">Account Type</div>
                            <div class="info-value"><?= Helpers::escape(ucwords(str_replace('_', ' ', $user['type_name']))) ?></div>
                        </div>

                        <div class="info-group">
                            <div class="info-label">Member Since</div>
                            <div class="info-value"><?= date('F j, Y', strtotime($user['created_at'])) ?></div>
                        </div>
                    </form>
                </section>

                <!-- Change Password Tab -->
                <section class="content-section <?= $activeTab === 'password' ? 'active' : '' ?>">
                    <form id="passwordForm" method="POST" action="my-account.php?tab=password">
                        <input type="hidden" name="action" value="change_password">

                        <?php
                        echo Input::make([
                            'name' => 'current_password',
                            'type' => Input::TYPE_PASSWORD,
                            'label' => 'Current Password',
                            'placeholder' => 'Enter your current password',
                            'required' => true,
                            'showToggle' => true
                        ])->render();

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
                        ?>
                    </form>
                </section>

                <!-- Danger Zone Tab -->
                <section class="content-section <?= $activeTab === 'danger' ? 'active' : '' ?>">
                  <p>Deleting your account will:</p>
                    <ul class="danger-list">
                      <li>Permanently remove your profile and account details</li>
                      <li>Erase all your reviews, ratings, and comments</li>
                      <li>Delete your saved stalls and favorites</li>
                      <li>Prevent you from recovering your data in the future</li>
                    </ul>
                    <p><strong>This action is irreversible.</strong></p>

                        <form id="deleteAccountForm" method="POST" action="my-account.php?tab=danger" onsubmit="return confirm('Are you absolutely sure? This action cannot be undone!');">
                            <input type="hidden" name="action" value="delete_account">

                            <?php
                            echo Input::make([
                                'name' => 'confirm_email',
                                'type' => Input::TYPE_EMAIL,
                                'label' => 'Type your email to confirm: ' . $user['email'],
                                'placeholder' => 'Enter your email address',
                                'required' => true
                            ])->render();
                            ?>
                        </form>
                    </div>
                </section>
            </div>
        </div>
        <div class="account-actions">
            <?php
            if ($activeTab === 'profile') {
                echo Button::make([
                    'text' => 'UPDATE PROFILE',
                    'type' => 'submit',
                    'variant' => Button::VARIANT_SECONDARY,
                    'attributes' => ['form' => 'profileForm']
                ])->render();
            } elseif ($activeTab === 'password') {
                echo Button::make([
                    'text' => 'CHANGE PASSWORD',
                    'type' => 'submit',
                    'variant' => Button::VARIANT_SECONDARY,
                    'attributes' => ['form' => 'passwordForm']
                ])->render();
            } elseif ($activeTab === 'danger') {
                echo Button::make([
                    'text' => 'DELETE ACCOUNT',
                    'type' => 'submit',
                    'variant' => Button::VARIANT_PRIMARY,
                    'class' => 'btn-danger',
                    'attributes' => [
                        'style' => 'background-color: #C63D2D;',
                        'form' => 'deleteAccountForm'
                    ]
                ])->render();
            }
            ?>
        </div>
    </main>

    <!-- Footer -->
    <?php require INCLUDES_PATH . '/footer.php'; ?>

    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
