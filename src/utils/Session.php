<?php
/**
 * BuzzarFeed - Session Management
 * 
 * Secure session management utilities
 * Following ISO 9241: Security and Maintainability
 * 
 * @package BuzzarFeed\Utils
 * @version 1.0
 */

namespace BuzzarFeed\Utils;

class Session {
    
    /**
     * Start session with security settings
     * 
     * @return void
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            
            session_name(SESSION_NAME);
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Set session variable
     * 
     * @param string $key Session key
     * @param mixed $value Session value
     * @return void
     */
    public static function set(string $key, $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     * 
     * @param string $key Session key
     * @param mixed $default Default value if not set
     * @return mixed Session value or default
     */
    public static function get(string $key, $default = null) {
        self::start();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**
     * Check if session variable exists
     * 
     * @param string $key Session key
     * @return bool True if exists
     */
    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     * 
     * @param string $key Session key
     * @return void
     */
    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Destroy session
     * 
     * @return void
     */
    public static function destroy(): void {
        self::start();
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in
     */
    public static function isLoggedIn(): bool {
        return self::has('user_id');
    }
    
    /**
     * Get logged in user ID
     * 
     * @return int|null User ID or null
     */
    public static function getUserId(): ?int {
        return self::get('user_id');
    }
    
    /**
     * Get logged in user type
     * 
     * @return string|null User type or null
     */
    public static function getUserType(): ?string {
        return self::get('user_type');
    }
    
    /**
     * Set flash message
     * 
     * @param string $message Flash message
     * @param string $type Message type (success, error, info)
     * @return void
     */
    public static function setFlash(string $message, string $type = 'info'): void {
        self::set('flash_message', ['message' => $message, 'type' => $type]);
    }
    
    /**
     * Get and remove flash message
     * 
     * @return array|null Flash message array or null
     */
    public static function getFlash(): ?array {
        $flash = self::get('flash_message');
        self::remove('flash_message');
        return $flash;
    }
    
    /**
     * Regenerate session ID for security
     * 
     * @param bool $deleteOldSession Whether to delete old session
     * @return bool Success status
     */
    public static function regenerate(bool $deleteOldSession = true): bool {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return session_regenerate_id($deleteOldSession);
        }
        return false;
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCsrfToken(): string {
        $token = Helpers::generateToken(CSRF_TOKEN_LENGTH);
        self::set('csrf_token', $token);
        return $token;
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if valid
     */
    public static function verifyCsrfToken(string $token): bool {
        $sessionToken = self::get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }
}
