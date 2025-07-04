<?php

/**
 * Configuration helper to load environment variables
 */
class Config {
    private static $config = null;
    
    public static function load() {
        if (self::$config === null) {
            self::$config = [];
            
            // Load from .env file
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue; // Skip comments
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        self::$config[trim($key)] = trim($value);
                    }
                }
            }
            
            // Override with system environment variables if they exist
            foreach (self::$config as $key => $value) {
                $envValue = getenv($key);
                if ($envValue !== false) {
                    self::$config[$key] = $envValue;
                }
            }
        }
        
        return self::$config;
    }
    
    public static function get($key, $default = null) {
        $config = self::load();
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    public static function getRequired($key) {
        $value = self::get($key);
        if ($value === null) {
            throw new Exception("Required configuration key '{$key}' is not set");
        }
        return $value;
    }
}
