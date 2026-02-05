<?php
/**
 * Simple .env File Loader
 * 
 * Loads environment variables from .env file into $_ENV and getenv()
 * This is a lightweight implementation similar to vlucas/phpdotenv
 */

class EnvLoader {
    protected $path;
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    /**
     * Load the .env file
     * 
     * @return void
     */
    public function load() {
        if (!file_exists($this->path)) {
            return; // Silently fail if .env doesn't exist (use defaults)
        }
        
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse the line
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                
                $name = trim($name);
                $value = trim($value);
                
                // Remove quotes if present
                $value = $this->removeQuotes($value);
                
                // Set the environment variable
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
    }
    
    /**
     * Remove quotes from value
     * 
     * @param string $value
     * @return string
     */
    protected function removeQuotes($value) {
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
}

/**
 * Helper function to get environment variable with fallback
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        $value = $_ENV[$key] ?? $default;
    }
    
    // Convert string booleans to actual booleans
    if ($value === 'true' || $value === '(true)') {
        return true;
    }
    
    if ($value === 'false' || $value === '(false)') {
        return false;
    }
    
    // Convert string null to actual null
    if ($value === 'null' || $value === '(null)') {
        return null;
    }
    
    return $value;
}

// Load .env file
$envLoader = new EnvLoader(__DIR__ . '/../.env');
$envLoader->load();
