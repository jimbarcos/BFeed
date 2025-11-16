<?php
/**
 * BuzzarFeed - Configuration File
 * 
 * Central configuration for the application
 * Following ISO 9241 principles: Maintainability, Reusability, Extensibility
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

// Load environment variables first (before any other configuration)
require_once __DIR__ . '/../src/utils/Env.php';

use BuzzarFeed\Utils\Env;

// Load .env file
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    Env::load($envPath);
}

// Development mode based on environment
define('DEVELOPMENT_MODE', Env::get('APP_ENV', 'production') === 'development' || Env::get('APP_DEBUG', false));

// Error reporting
if (DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Application Information
define('APP_NAME', Env::get('APP_NAME', 'BuzzarFeed'));
define('APP_VERSION', Env::get('APP_VERSION', '1.0.0'));
define('APP_DESCRIPTION', Env::get('APP_DESCRIPTION', 'Discover the Flavors of BGC Night Market'));

// Paths - Centralized path management
define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('COMPONENTS_PATH', SRC_PATH . '/components');
define('SECTIONS_PATH', SRC_PATH . '/sections');
define('PAGES_PATH', SRC_PATH . '/pages');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('UTILS_PATH', SRC_PATH . '/utils');

// URL Configuration - Simplified for InfinityFree
// InfinityFree doesn't always populate $_SERVER variables correctly
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = 'https://'; // InfinityFree uses HTTPS
    $host = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . $host . '/');
} else {
    // Fallback
    define('BASE_URL', '/');
}

define('ASSETS_URL', BASE_URL . 'assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMAGES_URL', ASSETS_URL . 'images/');

// Database Configuration
define('DB_HOST', Env::get('DB_HOST', 'localhost'));
define('DB_NAME', Env::get('DB_NAME', ''));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));
define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));
define('DB_PORT', Env::get('DB_PORT', 3306));

// Session Configuration
define('SESSION_LIFETIME', Env::get('SESSION_LIFETIME', 3600));
define('SESSION_NAME', Env::get('SESSION_NAME', 'buzzarfeed_session'));

// Security Configuration
define('HASH_ALGO', PASSWORD_DEFAULT);
define('CSRF_TOKEN_LENGTH', Env::get('CSRF_TOKEN_LENGTH', 32));

// Pagination Configuration
define('ITEMS_PER_PAGE', Env::get('ITEMS_PER_PAGE', 12));
define('MAX_PAGINATION_LINKS', Env::get('MAX_PAGINATION_LINKS', 5));

// File Upload Configuration
define('MAX_FILE_SIZE', Env::get('MAX_FILE_SIZE', 5242880)); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('UPLOAD_PATH', ROOT_PATH . '/' . Env::get('UPLOAD_PATH', 'uploads/'));

// Email Configuration
define('MAIL_DRIVER', Env::get('MAIL_DRIVER', 'smtp'));
define('MAIL_HOST', Env::get('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', Env::get('MAIL_PORT', 587));
define('MAIL_USERNAME', Env::get('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', Env::get('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', Env::get('MAIL_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', Env::get('MAIL_FROM_ADDRESS', 'noreply@buzzarfeed.com'));
define('MAIL_FROM_NAME', Env::get('MAIL_FROM_NAME', APP_NAME));

// API Configuration
define('API_VERSION', Env::get('API_VERSION', 'v1'));
define('API_BASE_URL', BASE_URL . 'api/' . API_VERSION . '/');
define('API_RATE_LIMIT', Env::get('API_RATE_LIMIT', 60));

// External API Keys
define('GOOGLE_MAPS_API_KEY', Env::get('GOOGLE_MAPS_API_KEY', ''));
define('GOOGLE_CLIENT_ID', Env::get('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', Env::get('GOOGLE_CLIENT_SECRET', ''));

// Social Media API Keys
define('FACEBOOK_APP_ID', Env::get('FACEBOOK_APP_ID', ''));
define('FACEBOOK_APP_SECRET', Env::get('FACEBOOK_APP_SECRET', ''));

// Payment Gateway
define('STRIPE_PUBLIC_KEY', Env::get('STRIPE_PUBLIC_KEY', ''));
define('STRIPE_SECRET_KEY', Env::get('STRIPE_SECRET_KEY', ''));

// Analytics
define('GOOGLE_ANALYTICS_ID', Env::get('GOOGLE_ANALYTICS_ID', ''));

// Cache Configuration
define('CACHE_DRIVER', Env::get('CACHE_DRIVER', 'file'));
define('CACHE_LIFETIME', Env::get('CACHE_LIFETIME', 3600));

// Logging
define('LOG_LEVEL', Env::get('LOG_LEVEL', DEVELOPMENT_MODE ? 'debug' : 'warning'));
define('LOG_FILE', ROOT_PATH . '/' . Env::get('LOG_FILE', 'logs/app.log'));

// Color Scheme (for reusability in components)
define('COLOR_PRIMARY_BEIGE', '#FEEED5');
define('COLOR_ORANGE', '#ED6027');
define('COLOR_GREEN', '#489A44');
define('COLOR_DARK', '#2C2C2C');
define('COLOR_WHITE', '#FFFFFF');

// Account Types
define('ACCOUNT_TYPE_ENTHUSIAST', 'enthusiast');
define('ACCOUNT_TYPE_OWNER', 'owner');
define('ACCOUNT_TYPE_ADMIN', 'admin');

// Review Ratings
define('MIN_RATING', 1);
define('MAX_RATING', 5);

// Time Zone
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Manila'));

// Autoloader Registration
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'BuzzarFeed\\';
    $base_dir = SRC_PATH . '/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Try lowercase version for case-sensitive filesystems
    $file_lower = $base_dir . str_replace('\\', '/', strtolower($relative_class)) . '.php';
    if (file_exists($file_lower)) {
        require_once $file_lower;
        return;
    }
    
    // Try with lowercase directories but original filename
    $parts = explode('/', str_replace('\\', '/', $relative_class));
    $filename = array_pop($parts);
    $dir_parts = array_map('strtolower', $parts);
    $dir_parts[] = $filename;
    $file_mixed = $base_dir . implode('/', $dir_parts) . '.php';
    if (file_exists($file_mixed)) {
        require_once $file_mixed;
        return;
    }
});

/**
 * Load environment-specific configuration
 */
if (file_exists(CONFIG_PATH . '/config.local.php')) {
    require_once CONFIG_PATH . '/config.local.php';
}
