<?php
/**
 * BuzzarFeed - 400 Bad Request Error Page
 * 
 * Custom error page for malformed requests
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

// Define this as an error page to allow session initialization in header
define('IS_ERROR_PAGE', true);

require_once __DIR__ . '/../bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;

// Start session BEFORE setting HTTP response code
Session::start();

// Now set 400 header after session is started
http_response_code(400);

// Log detailed error information
$errorDetails = [
    'timestamp' => date('Y-m-d H:i:s'),
    'error_code' => 400,
    'error_type' => 'Bad Request',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
    'query_string' => $_SERVER['QUERY_STRING'] ?? 'None',
    'http_referer' => $_SERVER['HTTP_REFERER'] ?? 'Direct access',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
];

// Log to error log
error_log("=== 400 BAD REQUEST ERROR ===");
error_log("Timestamp: " . $errorDetails['timestamp']);
error_log("Request URI: " . $errorDetails['request_uri']);
error_log("Request Method: " . $errorDetails['request_method']);
error_log("Query String: " . $errorDetails['query_string']);
error_log("Referrer: " . $errorDetails['http_referer']);
error_log("User Agent: " . $errorDetails['user_agent']);
error_log("IP Address: " . $errorDetails['ip_address']);
error_log("============================");

$pageTitle = "400 - Bad Request | BuzzarFeed";
$pageDescription = "The request could not be understood by the server";
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
        
        .error-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 80px 20px;
            text-align: center;
        }
        
        .error-code {
            font-size: 150px;
            font-weight: 700;
            color: var(--color-orange);
            line-height: 1;
            margin: 0 0 20px 0;
            text-shadow: 4px 4px 0px rgba(237, 96, 39, 0.2);
        }
        
        .error-icon {
            font-size: 120px;
            color: var(--color-orange);
            margin-bottom: 30px;
            opacity: 0.8;
        }
        
        .error-title {
            font-size: 36px;
            font-weight: 700;
            color: var(--color-text-primary);
            margin: 0 0 20px 0;
        }
        
        .error-description {
            font-size: 18px;
            color: var(--color-text-secondary);
            margin: 0 0 40px 0;
            line-height: 1.6;
        }
        
        .error-info-box {
            background-color: var(--color-primary-beige);
            border-left: 4px solid var(--color-orange);
            border-radius: 8px;
            padding: 25px;
            margin: 40px 0;
            text-align: left;
        }
        
        .error-info-box h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text-primary);
            margin: 0 0 15px 0;
        }
        
        .error-info-box p {
            font-size: 15px;
            color: var(--color-text-secondary);
            margin: 0;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-large {
            padding: 14px 32px;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .error-code {
                font-size: 100px;
            }
            
            .error-icon {
                font-size: 80px;
            }
            
            .error-title {
                font-size: 28px;
            }
            
            .error-description {
                font-size: 16px;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .error-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            
            <h1 class="error-code">400</h1>
            
            <h2 class="error-title">Bad Request</h2>
            
            <p class="error-description">
                The server couldn't understand your request. It's like ordering a dish that's not on the menu! 
                The request might be malformed, have invalid syntax, or contain invalid parameters.
            </p>
            
            <div class="error-actions">
                <a href="<?= BASE_URL ?>" class="btn btn-primary btn-large">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
                <a href="javascript:history.back()" class="btn btn-outline btn-large">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>
            
            <div class="error-info-box">
                <h3>Common causes of 400 errors:</h3>
                <p>
                    <strong>Invalid URL format</strong> - The URL might contain invalid characters or syntax.<br><br>
                    <strong>Malformed request</strong> - The request data sent to the server is not properly formatted.<br><br>
                    <strong>Browser cache issues</strong> - Try clearing your browser cache and cookies.<br><br>
                    <strong>Expired link</strong> - The link you clicked might be outdated or expired.
                </p>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>
