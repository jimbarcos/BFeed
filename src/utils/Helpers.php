<?php
/**
 * BuzzarFeed - Helper Utilities
 * 
 * Collection of reusable utility functions
 * Following ISO 9241: Reusability and Maintainability
 * 
 * @package BuzzarFeed\Utils
 * @version 1.0
 */

namespace BuzzarFeed\Utils;

class Helpers {
    
    /**
     * Sanitize user input
     * 
     * @param string $data Input data
     * @return string Sanitized data
     */
    public static function sanitize(string $data): string {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Escape HTML output
     * 
     * @param string|null $data Data to escape
     * @return string Escaped data
     */
    public static function escape(?string $data): string {
        if ($data === null) {
            return '';
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email to validate
     * @return bool True if valid
     */
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return array Validation result with 'valid' and 'message'
     */
    public static function validatePassword(string $password): array {
        $minLength = 8;
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        return [
            'valid' => empty($errors),
            'message' => implode('. ', $errors)
        ];
    }
    
    /**
     * Generate a random token
     * 
     * @param int $length Token length
     * @return string Random token
     */
    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hash password
     * 
     * @param string $password Plain password
     * @return string Hashed password
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, HASH_ALGO);
    }
    
    /**
     * Verify password
     * 
     * @param string $password Plain password
     * @param string $hash Hashed password
     * @return bool True if matches
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Redirect to another page
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function redirect(string $url, int $statusCode = 302): void {
        header("Location: {$url}", true, $statusCode);
        exit();
    }
    
    /**
     * Get current URL
     * 
     * @return string Current URL
     */
    public static function getCurrentUrl(): string {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Format date for display
     * 
     * @param string $date Date string
     * @param string $format Date format
     * @return string Formatted date
     */
    public static function formatDate(string $date, string $format = 'F j, Y'): string {
        return date($format, strtotime($date));
    }
    
    /**
     * Truncate text with ellipsis
     * 
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to append
     * @return string Truncated text
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Generate slug from text
     * 
     * @param string $text Text to convert
     * @return string Slug
     */
    public static function slugify(string $text): string {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool True if AJAX request
     */
    public static function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is POST
     * 
     * @return bool True if POST request
     */
    public static function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Check if request is GET
     * 
     * @return bool True if GET request
     */
    public static function isGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Get request parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed Parameter value or default
     */
    public static function get(string $key, $default = null) {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    
    /**
     * Get POST parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed Parameter value or default
     */
    public static function post(string $key, $default = null) {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Return JSON response
     * 
     * @param mixed $data Data to encode
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function jsonResponse($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Upload file with validation
     * 
     * @param array $file File from $_FILES
     * @param string $destination Destination path
     * @return array Result with 'success' and 'message' or 'path'
     */
    public static function uploadFile(array $file, string $destination): array {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error'];
        }
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File too large'];
        }
        
        // Check file type
        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = self::generateToken(16) . '.' . $extension;
        $filepath = $destination . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'path' => $filename];
        }
        
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    /**
     * Convert number to word (1-5)
     * 
     * @param int $number Number to convert
     * @return string Word representation
     */
    public static function numberToWord(int $number): string {
        $words = [
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five'
        ];
        
        return $words[$number] ?? (string)$number;
    }
}
