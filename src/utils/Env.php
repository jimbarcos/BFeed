<?php
/**
 * BuzzarFeed - Environment Variable Loader
 * 
 * Simple .env file parser and loader
 * Loads environment variables from .env file
 * 
 * @package BuzzarFeed\Utils
 * @version 1.0
 */

namespace BuzzarFeed\Utils;

class Env {
    
    /**
     * @var array Loaded environment variables
     */
    private static array $variables = [];
    
    /**
     * @var bool Whether .env has been loaded
     */
    private static bool $loaded = false;
    
    /**
     * Load environment variables from .env file
     * 
     * @param string $path Path to .env file
     * @return void
     */
    public static function load(string $path): void {
        if (self::$loaded) {
            return;
        }
        
        if (!file_exists($path)) {
            throw new \Exception(".env file not found at: {$path}");
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse line
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes from value
                $value = self::removeQuotes($value);
                
                // Parse variable references like ${VAR_NAME}
                $value = self::parseVariableReferences($value);
                
                // Store in both our array and $_ENV
                self::$variables[$key] = $value;
                $_ENV[$key] = $value;
                
                // Also set as server variable
                if (!isset($_SERVER[$key])) {
                    $_SERVER[$key] = $value;
                }
                
                // Set as environment variable (if putenv is available)
                if (function_exists('putenv')) {
                    putenv("{$key}={$value}");
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get an environment variable
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed Variable value
     */
    public static function get(string $key, $default = null) {
        // Check our loaded variables first
        if (isset(self::$variables[$key])) {
            return self::castValue(self::$variables[$key]);
        }
        
        // Check $_ENV
        if (isset($_ENV[$key])) {
            return self::castValue($_ENV[$key]);
        }
        
        // Check $_SERVER
        if (isset($_SERVER[$key])) {
            return self::castValue($_SERVER[$key]);
        }
        
        // Check getenv()
        $value = getenv($key);
        if ($value !== false) {
            return self::castValue($value);
        }
        
        return $default;
    }
    
    /**
     * Check if an environment variable exists
     * 
     * @param string $key Variable name
     * @return bool
     */
    public static function has(string $key): bool {
        return isset(self::$variables[$key]) 
            || isset($_ENV[$key]) 
            || isset($_SERVER[$key]) 
            || getenv($key) !== false;
    }
    
    /**
     * Get all environment variables
     * 
     * @return array
     */
    public static function all(): array {
        return self::$variables;
    }
    
    /**
     * Remove quotes from value
     * 
     * @param string $value
     * @return string
     */
    private static function removeQuotes(string $value): string {
        // Remove surrounding quotes
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
    
    /**
     * Parse variable references like ${VAR_NAME}
     * 
     * @param string $value
     * @return string
     */
    private static function parseVariableReferences(string $value): string {
        if (strpos($value, '${') === false) {
            return $value;
        }
        
        return preg_replace_callback('/\$\{([A-Z_]+)\}/', function($matches) {
            return self::get($matches[1], $matches[0]);
        }, $value);
    }
    
    /**
     * Cast value to appropriate type
     * 
     * @param string $value
     * @return mixed
     */
    private static function castValue(string $value) {
        // Boolean values
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === '(true)') {
            return true;
        }
        if ($lower === 'false' || $lower === '(false)') {
            return false;
        }
        
        // Null value
        if ($lower === 'null' || $lower === '(null)') {
            return null;
        }
        
        // Empty string
        if ($lower === 'empty' || $lower === '(empty)') {
            return '';
        }
        
        // Numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
    
    /**
     * Require an environment variable (throw exception if not found)
     * 
     * @param string $key Variable name
     * @return mixed Variable value
     * @throws \Exception If variable not found
     */
    public static function require(string $key) {
        $value = self::get($key);
        
        if ($value === null) {
            throw new \Exception("Required environment variable '{$key}' is not set");
        }
        
        return $value;
    }
}
