<?php
/**
 * BuzzarFeed - Manage Stall Page
 * 
 * Dashboard for stall owners to manage their stall information, menu, and reviews
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
    Session::setFlash('Please log in to manage your stall.', 'error');
    Helpers::redirect('login.php');
    exit;
}

// Check if user is a food stall owner
if (Session::get('user_type') !== 'food_stall_owner') {
    Session::setFlash('Access denied. Only stall owners can access this page.', 'error');
    Helpers::redirect('index.php');
    exit;
}

$db = Database::getInstance();
$userId = Session::get('user_id');

// Check if user has an approved stall
$stall = $db->querySingle(
    "SELECT fs.*, sl.address, sl.latitude, sl.longitude
     FROM food_stalls fs
     LEFT JOIN stall_locations sl ON fs.stall_id = sl.stall_id
     WHERE fs.owner_id = ? AND fs.is_active = 1",
    [$userId]
);

if (!$stall) {
    Session::setFlash('You don\'t have an approved stall yet. Please register your stall or wait for approval.', 'error');
    Helpers::redirect('index.php');
    exit;
}

// Get current tab
$currentTab = Helpers::get('tab', 'stall-info');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = Helpers::post('action');
    
    if ($action === 'update_stall_info') {
        $stallName = Helpers::post('stall_name');
        $description = Helpers::post('description');
        $location = Helpers::post('location');
        $categories = Helpers::post('categories', []);
        $hours = Helpers::post('hours');
        $latitude = Helpers::post('latitude');
        $longitude = Helpers::post('longitude');
        
        try {
            // Handle logo upload
            $logoPath = $stall['logo_path'];
            if (isset($_FILES['new_logo']) && $_FILES['new_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/stalls/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = pathinfo($_FILES['new_logo']['name'], PATHINFO_EXTENSION);
                $allowedExtensions = ['png', 'jpg', 'jpeg'];
                
                if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                    // Validate file size (5MB max)
                    if ($_FILES['new_logo']['size'] > 5 * 1024 * 1024) {
                        throw new \Exception('File size exceeds 5MB limit');
                    }
                    
                    $newFileName = 'stall_' . $stall['stall_id'] . '_' . time() . '.' . $fileExtension;
                    $newFilePath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['new_logo']['tmp_name'], $newFilePath)) {
                        // Delete old logo if exists
                        if (!empty($stall['logo_path']) && file_exists(__DIR__ . $stall['logo_path'])) {
                            unlink(__DIR__ . $stall['logo_path']);
                        }
                        $logoPath = '/uploads/stalls/' . $newFileName;
                    } else {
                        throw new \Exception('Failed to move uploaded file');
                    }
                } else {
                    throw new \Exception('Invalid file type. Only PNG and JPEG files are allowed');
                }
            }
            
            // Update stall information
            $db->execute(
                "UPDATE food_stalls 
                 SET name = ?, description = ?, food_categories = ?, hours = ?, logo_path = ?, updated_at = NOW()
                 WHERE stall_id = ?",
                [$stallName, $description, json_encode($categories), $hours, $logoPath, $stall['stall_id']]
            );
            
            // Update location with coordinates
            if (!empty($location)) {
                $db->execute(
                    "UPDATE stall_locations 
                     SET address = ?, latitude = ?, longitude = ?, updated_at = NOW()
                     WHERE stall_id = ?",
                    [$location, $latitude, $longitude, $stall['stall_id']]
                );
            }
            
            Session::setFlash('Stall information updated successfully!', 'success');
            Helpers::redirect('manage-stall.php?tab=stall-info');
            exit;
        } catch (\Exception $e) {
            $error = "Error updating stall information: " . $e->getMessage();
        }
    }
    
    if ($action === 'add_menu_item') {
        $itemName = Helpers::post('item_name');
        $itemDesc = Helpers::post('item_description');
        $itemPrice = Helpers::post('item_price');
        
        try {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/menu_items/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
                $allowedExtensions = ['png', 'jpg', 'jpeg'];
                
                if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                    if ($_FILES['item_image']['size'] > 5 * 1024 * 1024) {
                        throw new \Exception('File size exceeds 5MB limit');
                    }
                    
                    $newFileName = 'menu_' . $stall['stall_id'] . '_' . time() . '.' . $fileExtension;
                    $newFilePath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['item_image']['tmp_name'], $newFilePath)) {
                        $imagePath = '/uploads/menu_items/' . $newFileName;
                    } else {
                        throw new \Exception('Failed to upload image');
                    }
                } else {
                    throw new \Exception('Invalid file type. Only PNG and JPEG files are allowed');
                }
            }
            
            // Insert menu item
            $db->execute(
                "INSERT INTO menu_items (stall_id, name, description, price, image_path, is_available, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())",
                [$stall['stall_id'], $itemName, $itemDesc, $itemPrice, $imagePath]
            );
            
            Session::setFlash('Menu item added successfully!', 'success');
            Helpers::redirect('manage-stall.php?tab=menu-items');
            exit;
        } catch (\Exception $e) {
            $error = "Error adding menu item: " . $e->getMessage();
        }
    }
    
    if ($action === 'update_menu_item') {
        $itemId = Helpers::post('item_id');
        $itemName = Helpers::post('item_name');
        $itemDesc = Helpers::post('item_description');
        $itemPrice = Helpers::post('item_price');
        
        try {
            // Verify ownership
            $menuItem = $db->querySingle(
                "SELECT * FROM menu_items WHERE item_id = ? AND stall_id = ?",
                [$itemId, $stall['stall_id']]
            );
            
            if (!$menuItem) {
                throw new \Exception('Menu item not found or access denied');
            }
            
            // Handle image upload
            $imagePath = $menuItem['image_path'];
            if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/menu_items/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
                $allowedExtensions = ['png', 'jpg', 'jpeg'];
                
                if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                    if ($_FILES['item_image']['size'] > 5 * 1024 * 1024) {
                        throw new \Exception('File size exceeds 5MB limit');
                    }
                    
                    $newFileName = 'menu_' . $stall['stall_id'] . '_' . time() . '.' . $fileExtension;
                    $newFilePath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['item_image']['tmp_name'], $newFilePath)) {
                        // Delete old image if exists
                        if (!empty($menuItem['image_path']) && file_exists(__DIR__ . $menuItem['image_path'])) {
                            unlink(__DIR__ . $menuItem['image_path']);
                        }
                        $imagePath = '/uploads/menu_items/' . $newFileName;
                    }
                }
            }
            
            // Update menu item
            $db->execute(
                "UPDATE menu_items 
                 SET name = ?, description = ?, price = ?, image_path = ?, updated_at = NOW()
                 WHERE item_id = ? AND stall_id = ?",
                [$itemName, $itemDesc, $itemPrice, $imagePath, $itemId, $stall['stall_id']]
            );
            
            Session::setFlash('Menu item updated successfully!', 'success');
            Helpers::redirect('manage-stall.php?tab=menu-items');
            exit;
        } catch (\Exception $e) {
            $error = "Error updating menu item: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete_menu_item') {
        $itemId = Helpers::post('item_id');
        
        try {
            // Verify ownership and get image path
            $menuItem = $db->querySingle(
                "SELECT * FROM menu_items WHERE item_id = ? AND stall_id = ?",
                [$itemId, $stall['stall_id']]
            );
            
            if (!$menuItem) {
                throw new \Exception('Menu item not found or access denied');
            }
            
            // Delete image file if exists
            if (!empty($menuItem['image_path']) && file_exists(__DIR__ . $menuItem['image_path'])) {
                unlink(__DIR__ . $menuItem['image_path']);
            }
            
            // Delete from database
            $db->execute(
                "DELETE FROM menu_items WHERE item_id = ? AND stall_id = ?",
                [$itemId, $stall['stall_id']]
            );
            
            Session::setFlash('Menu item deleted successfully!', 'success');
            Helpers::redirect('manage-stall.php?tab=menu-items');
            exit;
        } catch (\Exception $e) {
            $error = "Error deleting menu item: " . $e->getMessage();
        }
    }
}

// Decode food categories
$categoriesJson = $stall['food_categories'] ?? '';
$categories = [];
if (!empty($categoriesJson)) {
    $decoded = json_decode($categoriesJson, true);
    $categories = is_array($decoded) ? $decoded : [];
}

// Define valid categories
$validCategories = [
    'Beverages' => 'Beverages',
    'Street Food' => 'Street Food',
    'Rice Meals' => 'Rice Meals',
    'Fast Food' => 'Fast Food',
    'Snacks' => 'Snacks',
    'Pastries' => 'Pastries',
    'Others' => 'Others'
];

// Fetch menu items for this stall
$menuItems = $db->query(
    "SELECT * FROM menu_items WHERE stall_id = ? ORDER BY created_at DESC",
    [$stall['stall_id']]
);

// Fetch reviews for this stall dynamically
$reviews = $db->query(
    "SELECT r.*, u.name as username
     FROM reviews r
     INNER JOIN users u ON r.user_id = u.user_id
     WHERE r.stall_id = ? AND r.is_hidden = 0
     ORDER BY r.created_at DESC",
    [$stall['stall_id']]
);

// Calculate rating distribution from actual reviews
$ratingDistribution = [
    5 => 0,
    4 => 0,
    3 => 0,
    2 => 0,
    1 => 0
];

foreach ($reviews as $review) {
    if (isset($ratingDistribution[$review['rating']])) {
        $ratingDistribution[$review['rating']]++;
    }
}

$totalRatings = array_sum($ratingDistribution);
$averageRating = $totalRatings > 0 ? round($stall['average_rating'], 1) : 0;

$pageTitle = "Manage Your Stall - BuzzarFeed";
$pageDescription = "Manage your stall information, menu items, and customer reviews";
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
    <link rel="stylesheet" href="<?= CSS_URL ?>/manage-stall.css">
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <!-- Page Header -->
        <section class="page-header">
            <div class="container">
                <h1>Manage Your Stall</h1>
                <p>Update your stall information, add menu items, and view customer reviews.</p>
            </div>
        </section>
        
        <!-- Tabs Navigation -->
        <section class="tabs-section">
            <div class="container">
                <div class="tabs">
                    <a href="?tab=stall-info" class="tab-btn <?= $currentTab === 'stall-info' ? 'active' : '' ?>">
                        Stall Information
                    </a>
                    <a href="?tab=menu-items" class="tab-btn <?= $currentTab === 'menu-items' ? 'active' : '' ?>">
                        Menu Items
                    </a>
                    <a href="?tab=recent-reviews" class="tab-btn <?= $currentTab === 'recent-reviews' ? 'active' : '' ?>">
                        Recent Reviews
                    </a>
                </div>
            </div>
        </section>
        
        <!-- Tab Content -->
        <section class="tab-content-section">
            <div class="container">
                
                <?php if ($currentTab === 'stall-info'): ?>
                    <!-- Stall Information Tab -->
                    <div class="content-card">
                        <h2 class="content-title">Update Stall Information</h2>
                        
                        <?php if (isset($error)): ?>
                            <div class="error-message"><?= Helpers::escape($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if (Session::get('flash_message')): ?>
                            <?php 
                                $flashMsg = Session::getFlash(); 
                                $message = '';
                                if (is_array($flashMsg) && isset($flashMsg[0])) {
                                    $message = $flashMsg[0];
                                } elseif (is_string($flashMsg)) {
                                    $message = $flashMsg;
                                }
                            ?>
                            <?php if (!empty($message)): ?>
                                <div class="success-message">
                                    <i class="fas fa-check-circle"></i>
                                    <?= Helpers::escape($message) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" class="stall-form">
                            <input type="hidden" name="action" value="update_stall_info">
                            <input type="hidden" name="latitude" id="map_latitude" value="<?= Helpers::escape($stall['latitude'] ?? '') ?>">
                            <input type="hidden" name="longitude" id="map_longitude" value="<?= Helpers::escape($stall['longitude'] ?? '') ?>">
                            
                            <!-- Stall Name -->
                            <div class="form-group">
                                <label for="stall_name" class="form-label">Stall Name</label>
                                    <input type="text" id="stall_name" name="stall_name" class="form-input" 
                                        value="<?= Helpers::escape($stall['name']) ?>" required>
                            </div>
                            
                            <!-- Stall Description -->
                            <div class="form-group">
                                <label for="description" class="form-label">Stall Description</label>
                                <textarea id="description" name="description" class="form-textarea" 
                                          rows="4" required><?= Helpers::escape($stall['description']) ?></textarea>
                            </div>
                            
                            <!-- Location -->
                            <div class="form-group">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" id="location" name="location" class="form-input" 
                                       value="<?= Helpers::escape($stall['address']) ?>" required>
                            </div>
                            
                            <!-- Map Section -->
                            <div class="map-section-wrapper">
                                <div class="map-pinned-badge">
                                    <i class="fas fa-check-circle"></i>
                                    Click on map to edit pinned location
                                </div>
                                <h3 class="map-section-title">Map</h3>
                                <div class="map-container" id="editable-map">
                                    <img src="<?= IMAGES_URL ?>/maps.png" alt="BGC Night Market Map" class="map-image">
                                    <?php if (!empty($stall['latitude']) && !empty($stall['longitude'])): ?>
                                        <div class="map-pin" id="stall-pin" style="left: <?= $stall['latitude'] ?>%; top: <?= $stall['longitude'] ?>%;">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="map-pin" id="stall-pin" style="left: 50%; top: 50%; display: none;">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="map-location-display">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= Helpers::escape($stall['address']) ?>
                                </div>
                                <p class="map-hint">Click on the map above to update your stall's location pin</p>
                            </div>
                            
                            <!-- Food Categories -->
                            <div class="form-group">
                                <label class="form-label">Food Categories:</label>
                                <div class="categories-grid">
                                    <?php foreach ($validCategories as $value => $label): ?>
                                        <div class="category-checkbox">
                                            <input type="checkbox" id="cat_<?= $value ?>" name="categories[]" value="<?= $value ?>"
                                                   <?= in_array($value, $categories) ? 'checked' : '' ?>>
                                            <label for="cat_<?= $value ?>"><?= $label ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Operating Hours -->
                            <div class="form-group">
                                <label for="hours" class="form-label">Operating Hours</label>
                                <input type="text" id="hours" name="hours" class="form-input" 
                                       value="<?= Helpers::escape($stall['hours'] ?? '') ?>" 
                                       placeholder="e.g., 9:00 AM - 9:00 PM">
                            </div>
                            
                            <!-- Current Logo -->
                            <div class="form-group">
                                <label class="form-label">Current Logo</label>
                                <div class="logo-preview">
                                    <?php if (!empty($stall['logo_path'])): ?>
                                        <img src="<?= BASE_URL . Helpers::escape($stall['logo_path']) ?>" 
                                             alt="Stall Logo" class="current-logo-img">
                                    <?php else: ?>
                                        <div class="no-logo">No logo uploaded</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Upload New Logo -->
                            <div class="form-group">
                                <label for="new_logo" class="form-label">Upload Logo (.png, .jpeg format only)</label>
                                <div class="file-upload-wrapper">
                                    <label for="new_logo" class="file-upload-label">
                                        <i class="fas fa-upload"></i>
                                        Choose File
                                    </label>
                                    <input type="file" id="new_logo" name="new_logo" accept="image/png,image/jpeg,image/jpg" class="file-input">
                                    <p class="file-upload-hint">Maximum file size: 5MB</p>
                                    <div id="file-name-display" class="file-name-display"></div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <button type="submit" class="submit-btn">
                                Update Stall Information
                            </button>
                        </form>
                    </div>
                    
                <?php elseif ($currentTab === 'menu-items'): ?>
                    <!-- Menu Items Tab -->
                    <div class="content-card">
                        <h2 class="content-title">Manage Menu Items</h2>
                        
                        <?php if (isset($error)): ?>
                            <div class="error-message"><?= Helpers::escape($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if (Session::get('flash_message')): ?>
                            <?php 
                                $flashMsg = Session::getFlash(); 
                                $message = '';
                                if (is_array($flashMsg) && isset($flashMsg[0])) {
                                    $message = $flashMsg[0];
                                } elseif (is_string($flashMsg)) {
                                    $message = $flashMsg;
                                }
                            ?>
                            <?php if (!empty($message)): ?>
                                <div class="success-message">
                                    <i class="fas fa-check-circle"></i>
                                    <?= Helpers::escape($message) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Add New Menu Item Form -->
                        <div class="add-menu-section">
                            <h3 class="section-subtitle">Add New Menu Item</h3>
                            <form method="POST" enctype="multipart/form-data" class="menu-form">
                                <input type="hidden" name="action" value="add_menu_item">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="item_name" class="form-label">Item Name</label>
                                        <input type="text" id="item_name" name="item_name" class="form-input" 
                                               placeholder="Enter item name" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="item_price" class="form-label">Price (PHP)</label>
                                        <input type="number" id="item_price" name="item_price" class="form-input" 
                                               placeholder="Enter price" step="0.01" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="item_description" class="form-label">Item Description</label>
                                    <textarea id="item_description" name="item_description" class="form-textarea" 
                                              rows="3" placeholder="Enter description"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="item_image" class="form-label">Upload Image (.png, .jpeg format only)</label>
                                    <div class="file-upload-wrapper">
                                        <label for="item_image" class="file-upload-label">
                                            <i class="fas fa-upload"></i>
                                            Choose File
                                        </label>
                                        <input type="file" id="item_image" name="item_image" accept="image/png,image/jpeg,image/jpg" class="file-input">
                                        <p class="file-upload-hint">Maximum file size: 5MB</p>
                                        <div id="item-file-name-display" class="file-name-display"></div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="submit-btn">
                                    <i class="fas fa-plus-circle"></i> Add Menu Item
                                </button>
                            </form>
                        </div>
                        
                        <!-- Existing Menu Items -->
                        <div class="existing-menu-section">
                            <h3 class="section-subtitle">Your Menu Items (<?= count($menuItems) ?>)</h3>
                            
                            <?php if (empty($menuItems)): ?>
                                <div class="no-items-message">
                                    <i class="fas fa-utensils"></i>
                                    <p>No menu items added yet. Add your first menu item above!</p>
                                </div>
                            <?php else: ?>
                                <div class="menu-items-grid">
                                    <?php foreach ($menuItems as $item): ?>
                                        <div class="menu-item-card">
                                            <div class="menu-item-image">
                                                <?php if (!empty($item['image_path'])): ?>
                                                    <img src="<?= BASE_URL . Helpers::escape($item['image_path']) ?>" alt="<?= Helpers::escape($item['name']) ?>">
                                                <?php else: ?>
                                                    <div class="no-image"><i class="fas fa-image"></i></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="menu-item-details">
                                                <h4 class="menu-item-name"><?= Helpers::escape($item['name']) ?></h4>
                                                <p class="menu-item-price">₱<?= number_format($item['price'], 2) ?></p>
                                                <?php if (!empty($item['description'])): ?>
                                                    <p class="menu-item-description"><?= Helpers::escape($item['description']) ?></p>
                                                <?php endif; ?>
                                                <div class="menu-item-actions">
                                                    <button class="btn-edit" onclick="openEditModal(<?= $item['item_id'] ?>, '<?= Helpers::escape($item['name']) ?>', '<?= Helpers::escape($item['description'] ?? '') ?>', <?= $item['price'] ?>, '<?= Helpers::escape($item['image_path'] ?? '') ?>')">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn-delete" onclick="openDeleteModal(<?= $item['item_id'] ?>, '<?= Helpers::escape($item['name']) ?>')">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php elseif ($currentTab === 'recent-reviews'): ?>
                    <!-- Recent Reviews Tab -->
                    <div class="content-card">
                        <h2 class="content-title">Reviews and Ratings</h2>
                        
                        <div class="reviews-summary">
                            <!-- Rating Bars -->
                            <div class="rating-bars">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <div class="rating-bar-row">
                                        <span class="rating-label"><?= strtoupper(Helpers::numberToWord($i)) ?></span>
                                        <i class="fas fa-star star-icon"></i>
                                        <div class="rating-bar-bg">
                                            <div class="rating-bar-fill" 
                                                 style="width: <?= $totalRatings > 0 ? ($ratingDistribution[$i] / $totalRatings) * 100 : 0 ?>%"></div>
                                        </div>
                                        <span class="rating-count"><?= $ratingDistribution[$i] ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <!-- Average Rating Box -->
                            <div class="average-rating-box">
                                <div class="avg-rating-number"><?= $averageRating ?></div>
                                <div class="avg-rating-stars">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $averageRating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i - 0.5 <= $averageRating) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="total-ratings"><?= $totalRatings ?> Ratings</div>
                            </div>
                        </div>
                        
                        <!-- Individual Reviews -->
                        <div class="reviews-list">
                            <?php if (empty($reviews)): ?>
                                <div class="no-items-message">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No reviews yet. Customers can leave reviews after visiting your stall.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-card">
                                        <div class="review-header">
                                            <div class="review-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $review['rating'] ? '' : 'empty' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                                        </div>
                                        <h4 class="review-title"><?= Helpers::escape($review['title'] ?? 'Review') ?></h4>
                                        <div class="review-author">
                                            <i class="fas fa-user-circle"></i>
                                            <?php if ($review['is_anonymous']): ?>
                                                Anonymous
                                            <?php else: ?>
                                                <?= Helpers::escape($review['username']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <p class="review-comment"><?= Helpers::escape($review['comment']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="reviews-pagination">
                            <button class="pagination-btn prev" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="pagination-btn next">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- Edit Menu Item Modal -->
    <div id="editMenuModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Menu Item</h3>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editMenuForm">
                <input type="hidden" name="action" value="update_menu_item">
                <input type="hidden" name="item_id" id="edit_item_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_item_name" class="form-label">Item Name</label>
                        <input type="text" id="edit_item_name" name="item_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_item_price" class="form-label">Price (PHP)</label>
                        <input type="number" id="edit_item_price" name="item_price" class="form-input" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_item_description" class="form-label">Description</label>
                        <textarea id="edit_item_description" name="item_description" class="form-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Current Image</label>
                        <div id="edit_current_image" class="current-image-preview"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_item_image" class="form-label">Upload New Image (optional)</label>
                        <div class="file-upload-wrapper">
                            <label for="edit_item_image" class="file-upload-label">
                                <i class="fas fa-upload"></i> Choose File
                            </label>
                            <input type="file" id="edit_item_image" name="item_image" accept="image/png,image/jpeg,image/jpg" class="file-input">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteMenuModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="modal-close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <form method="POST" id="deleteMenuForm">
                <input type="hidden" name="action" value="delete_menu_item">
                <input type="hidden" name="item_id" id="delete_item_id">
                
                <div class="modal-body">
                    <div class="delete-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Are you sure you want to delete <strong id="delete_item_name"></strong>?</p>
                        <p class="warning-text">This action cannot be undone.</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-delete-confirm">Delete</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Stall Information Modal -->
    <div id="editStallModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Edit Stall Information</h3>
                <span class="modal-close" onclick="closeEditStallModal()">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editStallForm">
                <input type="hidden" name="action" value="update_stall_info">
                <input type="hidden" name="latitude" id="edit_map_latitude" value="<?= Helpers::escape($stall['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="edit_map_longitude" value="<?= Helpers::escape($stall['longitude'] ?? '') ?>">
                
                <div class="modal-body">
                    <!-- Stall Name -->
                    <div class="form-group">
                        <label for="edit_stall_name" class="form-label">Stall Name</label>
                        <input type="text" id="edit_stall_name" name="stall_name" class="form-input" 
                               value="<?= Helpers::escape($stall['name']) ?>" required>
                    </div>
                    
                    <!-- Stall Description -->
                    <div class="form-group">
                        <label for="edit_description" class="form-label">Stall Description</label>
                        <textarea id="edit_description" name="description" class="form-textarea" 
                                  rows="4" required><?= Helpers::escape($stall['description']) ?></textarea>
                    </div>
                    
                    <!-- Location -->
                    <div class="form-group">
                        <label for="edit_location" class="form-label">Location</label>
                        <input type="text" id="edit_location" name="location" class="form-input" 
                               value="<?= Helpers::escape($stall['address']) ?>" required>
                    </div>
                    
                    <!-- Map Section -->
                    <div class="map-section-wrapper">
                        <div class="map-pinned-badge">
                            <i class="fas fa-check-circle"></i>
                            Click on map to edit pinned location
                        </div>
                        <h3 class="map-section-title">Map</h3>
                        <div class="map-container" id="edit-editable-map">
                            <img src="<?= IMAGES_URL ?>/maps.png" alt="BGC Night Market Map" class="map-image">
                            <div class="map-pin" id="edit-stall-pin" style="left: <?= $stall['latitude'] ?? 50 ?>%; top: <?= $stall['longitude'] ?? 50 ?>%; <?= empty($stall['latitude']) ? 'display: none;' : '' ?>">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                        </div>
                        <div class="map-location-display">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= Helpers::escape($stall['address']) ?>
                        </div>
                        <p class="map-hint">Click on the map above to update your stall's location pin</p>
                    </div>
                    
                    <!-- Food Categories -->
                    <div class="form-group">
                        <label class="form-label">Food Categories:</label>
                        <div class="categories-grid">
                            <?php foreach ($validCategories as $value => $label): ?>
                                <div class="category-checkbox">
                                    <input type="checkbox" id="edit_cat_<?= $value ?>" name="categories[]" value="<?= $value ?>"
                                           <?= in_array($value, $categories) ? 'checked' : '' ?>>
                                    <label for="edit_cat_<?= $value ?>"><?= $label ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
    
    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-body text-center">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 id="successMessage">Success!</h3>
                <button class="btn-primary" onclick="closeSuccessModal()">OK</button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
    <script>
        // Editable map functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mapContainer = document.getElementById('editable-map');
            const stallPin = document.getElementById('stall-pin');
            const latitudeInput = document.getElementById('map_latitude');
            const longitudeInput = document.getElementById('map_longitude');
            
            if (mapContainer && stallPin) {
                mapContainer.style.cursor = 'crosshair';
                
                mapContainer.addEventListener('click', function(e) {
                    const rect = mapContainer.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    // Calculate percentage position
                    const leftPercent = (x / rect.width) * 100;
                    const topPercent = (y / rect.height) * 100;
                    
                    // Update pin position
                    stallPin.style.left = leftPercent + '%';
                    stallPin.style.top = topPercent + '%';
                    stallPin.style.display = 'block';
                    
                    // Update hidden inputs
                    latitudeInput.value = leftPercent.toFixed(2);
                    longitudeInput.value = topPercent.toFixed(2);
                    
                    // Visual feedback
                    stallPin.style.animation = 'bounce 0.5s';
                    setTimeout(() => {
                        stallPin.style.animation = '';
                    }, 500);
                });
            }
            
            // File upload preview
            const fileInput = document.getElementById('new_logo');
            const fileNameDisplay = document.getElementById('file-name-display');
            
            if (fileInput && fileNameDisplay) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                        fileNameDisplay.innerHTML = `<i class="fas fa-file-image"></i> ${file.name} (${fileSize} MB)`;
                        fileNameDisplay.style.display = 'block';
                        
                        // Validate file size
                        if (file.size > 5 * 1024 * 1024) {
                            fileNameDisplay.innerHTML = '<i class="fas fa-exclamation-triangle"></i> File too large! Maximum 5MB allowed.';
                            fileNameDisplay.style.color = '#721c24';
                        }
                    }
                });
            }
            
            // File upload preview for menu item image
            const itemFileInput = document.getElementById('item_image');
            const itemFileNameDisplay = document.getElementById('item-file-name-display');
            
            if (itemFileInput && itemFileNameDisplay) {
                itemFileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                        itemFileNameDisplay.innerHTML = `<i class="fas fa-file-image"></i> ${file.name} (${fileSize} MB)`;
                        itemFileNameDisplay.style.display = 'block';
                        
                        if (file.size > 5 * 1024 * 1024) {
                            itemFileNameDisplay.innerHTML = '<i class="fas fa-exclamation-triangle"></i> File too large! Maximum 5MB allowed.';
                            itemFileNameDisplay.style.color = '#721c24';
                        }
                    }
                });
            }
        });
        
        // Modal functions
        function openEditModal(itemId, name, description, price, imagePath) {
            document.getElementById('edit_item_id').value = itemId;
            document.getElementById('edit_item_name').value = name;
            document.getElementById('edit_item_description').value = description;
            document.getElementById('edit_item_price').value = price;
            
            const imagePreview = document.getElementById('edit_current_image');
            if (imagePath) {
                imagePreview.innerHTML = `<img src="${imagePath}" alt="${name}" style="max-width: 200px; border-radius: 8px;">`;
            } else {
                imagePreview.innerHTML = '<p style="color: #999;">No image uploaded</p>';
            }
            
            document.getElementById('editMenuModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editMenuModal').style.display = 'none';
        }
        
        function openDeleteModal(itemId, name) {
            document.getElementById('delete_item_id').value = itemId;
            document.getElementById('delete_item_name').textContent = name;
            document.getElementById('deleteMenuModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteMenuModal').style.display = 'none';
        }
        
        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }
        
        // Close modals on outside click
        window.onclick = function(event) {
            const editModal = document.getElementById('editMenuModal');
            const deleteModal = document.getElementById('deleteMenuModal');
            const successModal = document.getElementById('successModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
            if (event.target === successModal) {
                closeSuccessModal();
            }
        };
        
        // Show success modal if there's a flash message on page load
        <?php if (Session::get('flash_message')): ?>
            setTimeout(function() {
                const successMessage = document.querySelector('.success-message');
                if (successMessage) {
                    successMessage.style.display = 'none';
                }
            }, 5000); // Auto-hide after 5 seconds
        <?php endif; ?>
    </script>
</body>
</html>
