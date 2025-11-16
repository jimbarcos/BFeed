<?php
/**
 * BuzzarFeed - Bootstrap File
 * 
 * Initialize application and autoload dependencies
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

// Load configuration
if (!file_exists(__DIR__ . '/config/config.php')) {
    die('Error: config/config.php not found at ' . __DIR__ . '/config/config.php');
}

try {
    require_once __DIR__ . '/config/config.php';
} catch (Exception $e) {
    die('Error loading config: ' . $e->getMessage());
}

// Load Composer autoloader (for PHPMailer and other dependencies)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// PSR-4 Autoloader for BuzzarFeed namespace
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'BuzzarFeed\\';
    
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    // and append with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

// Start session
if (!file_exists(SRC_PATH . '/utils/Session.php')) {
    die('Error: Session.php not found at ' . SRC_PATH . '/utils/Session.php');
}

try {
    require_once SRC_PATH . '/utils/Session.php';
    // Don't auto-start session - let pages start it when needed
    // \BuzzarFeed\Utils\Session::start();
} catch (Exception $e) {
    die('Error loading Session: ' . $e->getMessage());
}

// Set up error handler for production
if (!DEVELOPMENT_MODE) {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}");
        return true;
    });
}

// Helper function to load components
function component($name, $props = []) {
    return \BuzzarFeed\Components\ComponentFactory::create($name, $props);
}

// Helper function to render components
function render($component) {
    if ($component instanceof \BuzzarFeed\Components\BaseComponent) {
        echo $component->render();
    } elseif (is_string($component)) {
        echo $component;
    }
}
