<?php
/**
 * BuzzarFeed - Register Stall Page
 * 
 * Page for food stall owners to register their stall
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Database;

Session::start();

// Check if user is logged in and is a stall owner
if (!Session::isLoggedIn()) {
    Session::setFlash('Please log in to register a stall.', 'error');
    Helpers::redirect('login.php');
    exit;
}

if (Session::get('user_type') !== 'food_stall_owner') {
    Session::setFlash('Only food stall owners can register stalls.', 'error');
    Helpers::redirect('index.php');
    exit;
}

// Check if user already has a registered stall
if (Session::get('has_stall', false)) {
    Session::setFlash('You have already registered a stall.', 'info');
    Helpers::redirect('my-stall.php');
    exit;
}

// Check if user has a pending application
try {
    $db = Database::getInstance();
    $userId = Session::getUserId();
    
    // Check for pending application (status_id = 1 is 'Pending')
    $pendingApp = $db->querySingle(
        "SELECT application_id FROM applications 
         WHERE user_id = ? AND current_status_id = 1",
        [$userId]
    );
    
    if ($pendingApp) {
        // User already has a pending application
        Session::setFlash('You already have a pending stall application under review.', 'info');
        Helpers::redirect('registration-pending.php');
        exit;
    }
} catch (\Exception $e) {
    error_log("Error checking pending application: " . $e->getMessage());
}

$pageTitle = "Register Your Stall - BuzzarFeed";
$pageDescription = "Register your food stall at BGC Night Market Bazaar";

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $stallName = trim(Helpers::post('stall_name', ''));
    $description = trim(Helpers::post('description', ''));
    $location = trim(Helpers::post('location', ''));
    $categories = Helpers::post('categories', []);
    
    // Validation
    if (empty($stallName)) {
        $errors['stall_name'] = 'Stall name is required';
    } elseif (strlen($stallName) < 2) {
        $errors['stall_name'] = 'Stall name must be at least 2 characters';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    } elseif (strlen($description) < 5) {
        $errors['description'] = 'Description must be at least 5 characters';
    }
    
    if (empty($location)) {
        $errors['location'] = 'Location is required';
    }
    
    if (empty($categories)) {
        $errors['categories'] = 'Please select at least one food category';
    }
    
    // File uploads validation
    $birFile = $_FILES['bir_registration'] ?? null;
    $permitFile = $_FILES['business_permit'] ?? null;
    $dtiFile = $_FILES['dti_sec'] ?? null;
    $logoFile = $_FILES['stall_logo'] ?? null;
    
    if (!$birFile || $birFile['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['bir_registration'] = 'BIR Registration is required';
    }
    
    if (!$permitFile || $permitFile['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['business_permit'] = 'Business Permit / Mayor\'s Permit is required';
    }
    
    if (!$dtiFile || $dtiFile['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['dti_sec'] = 'DTI / SEC document is required';
    }
    
    if (!$logoFile || $logoFile['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['stall_logo'] = 'Stall Logo is required';
    }
    
    // Check file sizes (max 5MB) and upload errors
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $filesToCheck = [
        'bir_registration' => $birFile,
        'business_permit' => $permitFile,
        'dti_sec' => $dtiFile,
        'stall_logo' => $logoFile
    ];

    foreach ($filesToCheck as $field => $file) {
        if (!$file) {
            continue;
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            // already handled above for required files
            continue;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[$field] = 'Error uploading file. Please try again.';
            continue;
        }

        if (isset($file['size']) && $file['size'] > $maxFileSize) {
            $errors[$field] = 'File must be 5MB or smaller.';
        }
    }

    // Check terms agreement
    if (!Helpers::post('agree_terms')) {
        $errors['agree_terms'] = 'You must agree to the Terms of Services';
    }
    
    // If no errors, process registration
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            $userId = Session::getUserId();
            
            // Get map coordinates
            $mapX = Helpers::post('map_x', null);
            $mapY = Helpers::post('map_y', null);
            
            // Convert categories array to JSON
            $categoriesJson = json_encode($categories);
            
            // Create unique identifier for this application
            $uuid = uniqid();
            
            // Create stall-specific upload directory
            // Sanitize stall name for directory name
            $sanitizedStallName = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($stallName));
            $uploadDir = __DIR__ . '/uploads/applications/' . $sanitizedStallName . '_' . $uuid . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $birPath = null;
            $permitPath = null;
            $dtiPath = null;
            $logoPath = null;
            
            // Upload BIR Registration
            if ($birFile && $birFile['error'] === UPLOAD_ERR_OK) {
                $birFileName = 'bir_' . basename($birFile['name']);
                $birPath = 'uploads/applications/' . $sanitizedStallName . '_' . $uuid . '/' . $birFileName;
                move_uploaded_file($birFile['tmp_name'], $uploadDir . $birFileName);
            }
            
            // Upload Business Permit
            if ($permitFile && $permitFile['error'] === UPLOAD_ERR_OK) {
                $permitFileName = 'permit_' . basename($permitFile['name']);
                $permitPath = 'uploads/applications/' . $sanitizedStallName . '_' . $uuid . '/' . $permitFileName;
                move_uploaded_file($permitFile['tmp_name'], $uploadDir . $permitFileName);
            }
            
            // Upload DTI/SEC
            if ($dtiFile && $dtiFile['error'] === UPLOAD_ERR_OK) {
                $dtiFileName = 'dti_' . basename($dtiFile['name']);
                $dtiPath = 'uploads/applications/' . $sanitizedStallName . '_' . $uuid . '/' . $dtiFileName;
                move_uploaded_file($dtiFile['tmp_name'], $uploadDir . $dtiFileName);
            }
            
            // Upload Stall Logo
            if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
                $logoFileName = 'logo_' . basename($logoFile['name']);
                $logoPath = 'uploads/applications/' . $sanitizedStallName . '_' . $uuid . '/' . $logoFileName;
                move_uploaded_file($logoFile['tmp_name'], $uploadDir . $logoFileName);
            }
            
            // Insert application into database with status_id = 1 (Pending)
            $query = "INSERT INTO applications 
                      (user_id, stall_name, stall_description, location, food_categories, 
                       bir_registration_path, business_permit_path, dti_sec_path, stall_logo_path,
                       map_x, map_y, current_status_id, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            
            $db->execute($query, [
                $userId,
                $stallName,
                $description,
                $location,
                $categoriesJson,
                $birPath,
                $permitPath,
                $dtiPath,
                $logoPath,
                $mapX,
                $mapY
            ]);
            
            // Get the application ID
            $applicationId = $db->lastInsertId();
            
            Session::setFlash('Your stall registration has been submitted for review!', 'success');
            Helpers::redirect('registration-pending.php');
            exit;
            
        } catch (\Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors['general'] = 'An error occurred while submitting your application. Please try again.';
        }
    }
}

// Valid categories
$validCategories = [
    'beverages' => 'Beverages',
    'rice_meals' => 'Rice Meals',
    'snacks' => 'Snacks',
    'street_food' => 'Street Food',
    'fast_food' => 'Fast Food',
    'pastries' => 'Pastries',
    'others' => 'Others'
];
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
            background: #FEEED5;
        }
        
        main {
            flex: 1 0 auto;
            padding: 40px 20px;
        }
        
        .register-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Location Search Section */
        .location-search {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .location-input-wrapper {
            position: relative;
        }
        
        .location-input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }
        
        .location-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
        }
        
        /* Map Section */
        .map-section {
            background: linear-gradient(135deg, #ED6027 0%, #E8663E 100%);
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .map-title {
            color: white;
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 30px 0;
        }
        
        .map-placeholder {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            position: relative;
            cursor: crosshair;
        }
        
        .map-image {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 8px;
        }
        
        .map-pin {
            position: absolute;
            width: 30px;
            height: 30px;
            transform: translate(-50%, -100%);
            pointer-events: none;
            transition: all 0.2s ease;
        }
        
        .map-pin i {
            font-size: 30px;
            color: #ED6027;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }
        
        .map-icon {
            font-size: 120px;
            color: #ED6027;
            margin-bottom: 20px;
        }
        
        .hidden {
            display: none;
        }
        
        .map-location-btn {
            background: #FEEED5;
            padding: 15px 30px;
            border-radius: 25px;
            border: none;
            font-size: 14px;
            color: #666;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .map-location-btn i {
            color: #ED6027;
        }
        
        /* Registration Form */
        .registration-form {
            background: #FEEED5;
        }
        
        .form-title {
            font-size: 32px;
            font-weight: 700;
            color: #2C2C2C;
            margin: 0 0 30px 0;
            text-align: center;
        }
        
        .form-title .orange {
            color: #ED6027;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2C2C2C;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            box-sizing: border-box;
        }
        
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            resize: vertical;
            min-height: 100px;
            box-sizing: border-box;
        }
        
        /* Categories */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        
        .category-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .category-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .category-checkbox label {
            cursor: pointer;
            font-size: 14px;
            color: #2C2C2C;
        }
        
        /* File Upload */
        .file-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .file-upload-box {
            background: white;
            border: none;
            border-radius: 8px;
            padding: 20px;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .file-upload-box:hover {
            background: #FFF5F0;
        }
        
        .file-upload-box i {
            font-size: 32px;
            color: #ED6027;
        }
        
        .file-upload-info {
            flex: 1;
        }
        
        .file-upload-text {
            font-size: 14px;
            color: #2C2C2C;
            margin: 0 0 5px 0;
            font-weight: 600;
        }
        
        .file-upload-hint {
            font-size: 12px;
            color: #999;
            margin: 0;
        }
        
        .file-upload-input {
            display: none;
        }
        
        .file-name {
            margin-top: 5px;
            font-size: 12px;
            color: #489A44;
            font-weight: 600;
        }
        
        /* Terms */
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 20px 0;
        }
        
        .terms-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            cursor: pointer;
        }
        
        .terms-label {
            font-size: 14px;
            color: #2C2C2C;
            cursor: pointer;
        }
        
        .terms-label a {
            color: #ED6027;
            text-decoration: underline;
        }
        
        /* Submit Button */
        .submit-btn {
            width: 100%;
            background: #ED6027;
            color: white;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .submit-btn:hover {
            background: #d55520;
            transform: translateY(-2px);
        }
        
        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Error Messages */
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .map-title {
                font-size: 28px;
            }
            
            .form-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <div class="register-container">
            <!-- Location Search -->
            <div class="location-search">
                <div class="location-input-wrapper">
                    <i class="fas fa-map-marker-alt"></i>
                    <input 
                        type="text" 
                        class="location-input" 
                        placeholder="Location 32, 13-3rd your address, BGC Night Market"
                        readonly
                    >
                </div>
            </div>
            
            <!-- Map Section -->
            <div class="map-section">
                <h2 class="map-title">Map</h2>
                
                <div class="map-placeholder" id="mapContainer">
                    <img src="<?= IMAGES_URL ?>/maps.png" alt="BGC Night Market Map" class="map-image" id="mapImage">
                    <div class="map-pin hidden" id="mapPin">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                </div>
                
                <button class="map-location-btn" type="button">
                    <i class="fas fa-map-marker-alt"></i>
                    Click on the map to pin your location
                </button>
            </div>
            
            <!-- Registration Form -->
            <form class="registration-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="map_x" id="map_x" value="">
                <input type="hidden" name="map_y" id="map_y" value="">
                <h2 class="form-title">
                    <span class="orange">Register</span> Your Stall
                </h2>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        Your stall registration has been submitted successfully!
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $errors['general'] ?>
                    </div>
                <?php endif; ?>
                
                <!-- Stall Name -->
                <div class="form-group">
                    <label class="form-label" for="stall_name">Name of Stall</label>
                    <input 
                        type="text" 
                        id="stall_name" 
                        name="stall_name" 
                        class="form-input"
                        value="<?= Helpers::escape(Helpers::post('stall_name', '')) ?>"
                        required
                    >
                    <?php if (isset($errors['stall_name'])): ?>
                        <div class="error-message"><?= $errors['stall_name'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label class="form-label" for="description">Stall Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea"
                        required
                    ><?= Helpers::escape(Helpers::post('description', '')) ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <div class="error-message"><?= $errors['description'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Location -->
                <div class="form-group">
                    <label class="form-label" for="location">Location</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        class="form-input"
                        value="<?= Helpers::escape(Helpers::post('location', '')) ?>"
                        required
                    >
                    <?php if (isset($errors['location'])): ?>
                        <div class="error-message"><?= $errors['location'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Food Categories -->
                <div class="form-group">
                    <label class="form-label">Food Categories:</label>
                    <div class="categories-grid">
                        <?php foreach ($validCategories as $value => $label): ?>
                            <div class="category-checkbox">
                                <input 
                                    type="checkbox" 
                                    id="cat_<?= $value ?>" 
                                    name="categories[]" 
                                    value="<?= $value ?>"
                                    <?= in_array($value, Helpers::post('categories', [])) ? 'checked' : '' ?>
                                >
                                <label for="cat_<?= $value ?>"><?= $label ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errors['categories'])): ?>
                        <div class="error-message"><?= $errors['categories'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- BIR Registration -->
                <div class="form-group">
                    <label class="form-label">BIR Registration</label>
                    <div class="file-upload-wrapper">
                        <label class="file-upload-box" for="bir_registration">
                            <i class="fas fa-upload"></i>
                            <input 
                                type="file" 
                                id="bir_registration" 
                                name="bir_registration" 
                                class="file-upload-input"
                                accept=".png,.jpg,.jpeg,.pdf"
                            >
                        </label>
                        <div class="file-upload-info">
                            <p class="file-upload-text">Upload File (.png, .jpeg, .pdf format only)</p>
                            <p class="file-upload-hint">Maximum file size: 5MB</p>
                            <div class="file-name" id="bir_file_name"></div>
                        </div>
                    </div>
                    <?php if (isset($errors['bir_registration'])): ?>
                        <div class="error-message"><?= $errors['bir_registration'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Business Permit -->
                <div class="form-group">
                    <label class="form-label">Business Permit / Mayor's Permit:</label>
                    <div class="file-upload-wrapper">
                        <label class="file-upload-box" for="business_permit">
                            <i class="fas fa-upload"></i>
                            <input 
                                type="file" 
                                id="business_permit" 
                                name="business_permit" 
                                class="file-upload-input"
                                accept=".png,.jpg,.jpeg,.pdf"
                            >
                        </label>
                        <div class="file-upload-info">
                            <p class="file-upload-text">Upload File (.png, .jpeg, .pdf format only)</p>
                            <p class="file-upload-hint">Maximum file size: 5MB</p>
                            <div class="file-name" id="permit_file_name"></div>
                        </div>
                    </div>
                    <?php if (isset($errors['business_permit'])): ?>
                        <div class="error-message"><?= $errors['business_permit'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- DTI / SEC -->
                <div class="form-group">
                    <label class="form-label">DTI / SEC:</label>
                    <div class="file-upload-wrapper">
                        <label class="file-upload-box" for="dti_sec">
                            <i class="fas fa-upload"></i>
                            <input 
                                type="file" 
                                id="dti_sec" 
                                name="dti_sec" 
                                class="file-upload-input"
                                accept=".png,.jpg,.jpeg,.pdf"
                            >
                        </label>
                        <div class="file-upload-info">
                            <p class="file-upload-text">Upload File (.png, .jpeg, .pdf format only)</p>
                            <p class="file-upload-hint">Maximum file size: 5MB</p>
                            <div class="file-name" id="dti_file_name"></div>
                        </div>
                    </div>
                    <?php if (isset($errors['dti_sec'])): ?>
                        <div class="error-message"><?= $errors['dti_sec'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Stall Logo -->
                <div class="form-group">
                    <label class="form-label">Stall Logo</label>
                    <div class="file-upload-wrapper">
                        <label class="file-upload-box" for="stall_logo">
                            <i class="fas fa-upload"></i>
                            <input 
                                type="file" 
                                id="stall_logo" 
                                name="stall_logo" 
                                class="file-upload-input"
                                accept=".png,.jpg,.jpeg"
                            >
                        </label>
                        <div class="file-upload-info">
                            <p class="file-upload-text">Upload Image (.png, .jpeg format only)</p>
                            <p class="file-upload-hint">Maximum file size: 5MB</p>
                            <div class="file-name" id="logo_file_name"></div>
                        </div>
                    </div>
                    <?php if (isset($errors['stall_logo'])): ?>
                        <div class="error-message"><?= $errors['stall_logo'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Terms -->
                <div class="terms-checkbox">
                    <input 
                        type="checkbox" 
                        id="agree_terms" 
                        name="agree_terms"
                        <?= Helpers::post('agree_terms') ? 'checked' : '' ?>
                    >
                    <label class="terms-label" for="agree_terms">
                        I agree to all statements in <a href="<?= BASE_URL ?>terms-of-service.php">Terms of Services</a>
                    </label>
                </div>
                <?php if (isset($errors['agree_terms'])): ?>
                    <div class="error-message"><?= $errors['agree_terms'] ?></div>
                <?php endif; ?>
                
                <!-- Submit Button -->
                <button type="submit" class="submit-btn">
                    Register Stall
                </button>
            </form>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- JavaScript for file upload display and map interaction -->
    <script>
        // Show selected file names
        const fileInputs = [
            { input: 'bir_registration', display: 'bir_file_name' },
            { input: 'business_permit', display: 'permit_file_name' },
            { input: 'dti_sec', display: 'dti_file_name' },
            { input: 'stall_logo', display: 'logo_file_name' }
        ];
        
        const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

        fileInputs.forEach(({ input, display }) => {
            const inputEl = document.getElementById(input);
            const displayEl = document.getElementById(display);
            
            inputEl.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    if (file.size > MAX_FILE_SIZE) {
                        // Clear the selection and show an error
                        this.value = '';
                        displayEl.textContent = 'File too large (max 5MB)';
                        displayEl.style.color = '#dc3545';
                    } else {
                        displayEl.textContent = '✓ ' + file.name;
                        displayEl.style.color = '#489A44';
                    }
                } else {
                    displayEl.textContent = '';
                    displayEl.style.color = '';
                }
            });
        });
        
        // Map pin functionality
        const mapContainer = document.getElementById('mapContainer');
        const mapImage = document.getElementById('mapImage');
        const mapPin = document.getElementById('mapPin');
        const mapXInput = document.getElementById('map_x');
        const mapYInput = document.getElementById('map_y');
        const locationInput = document.querySelector('.location-input');
        
        mapContainer.addEventListener('click', function(e) {
            // Get the bounding rectangle of the actual image
            const rect = mapImage.getBoundingClientRect();
            
            // Calculate click position relative to image
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Make sure click is within image bounds
            if (x < 0 || y < 0 || x > rect.width || y > rect.height) {
                return;
            }
            
            // Calculate percentage position relative to image
            const xPercent = (x / rect.width) * 100;
            const yPercent = (y / rect.height) * 100;
            
            // Position the pin relative to container
            mapPin.style.left = xPercent + '%';
            mapPin.style.top = yPercent + '%';
            mapPin.classList.remove('hidden');
            
            // Store coordinates in hidden inputs
            mapXInput.value = xPercent.toFixed(2);
            mapYInput.value = yPercent.toFixed(2);
            
            // Update location input with coordinates
            locationInput.value = `Location pinned at coordinates (${xPercent.toFixed(1)}%, ${yPercent.toFixed(1)}%)`;
            
            console.log('Pin placed at:', xPercent.toFixed(2) + '%', yPercent.toFixed(2) + '%');
            console.log('Hidden input values - X:', mapXInput.value, 'Y:', mapYInput.value);
        });
    </script>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
