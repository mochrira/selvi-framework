<?php

namespace Selvi;

class Env {
    
    private static $loaded = false;
    private static $vars = [];
    
    /**
     * Load environment variables from .ENV file
     * 
     * @param string $path Path to .ENV file
     * @return void
     * @throws \RuntimeException if file not found or not readable
     */
    public static function load(string $path): void {
        if (self::$loaded) {
            return;
        }
        
        if (!file_exists($path)) {
            throw new \RuntimeException("Environment file not found: {$path}");
        }
        
        if (!is_readable($path)) {
            throw new \RuntimeException("Environment file not readable: {$path}");
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Set to $_ENV, $_SERVER, and internal cache
                self::$vars[$key] = $value;
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                
                // Use putenv for compatibility
                putenv("{$key}={$value}");
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable value
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        // Check internal cache first
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }
        
        // Check $_ENV
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        // Check $_SERVER
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        // Use getenv as fallback
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Check if environment variable exists
     * 
     * @param string $key Variable name
     * @return bool
     */
    public static function has(string $key): bool {
        return self::get($key) !== null;
    }
    
    /**
     * Get all loaded environment variables
     * 
     * @return array
     */
    public static function all(): array {
        return self::$vars;
    }
    
    /**
     * Clear all loaded environment variables
     * 
     * @return void
     */
    public static function clear(): void {
        foreach (self::$vars as $key => $value) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
        
        self::$vars = [];
        self::$loaded = false;
    }
}
