<?php
/**
 * Application Configuration
 * 
 * This file contains environment-specific configuration.
 * Configuration is loaded from .env file.
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

return [
    // Base URL - from environment variable or default
    'base_url' => env('APP_URL', '/lib_system/library_system'),
    
    // Asset URL (for CSS, JS, images)
    'asset_url' => env('ASSET_URL', '/lib_system/library_system/assets'),
    
    // Application name
    'app_name' => env('APP_NAME', 'LibraryHub'),
    
    // Environment (development, staging, production)
    'environment' => env('APP_ENV', 'development'),
    
    // Debug mode
    'debug' => env('APP_DEBUG', true),
];
