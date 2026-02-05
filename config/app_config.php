<?php
/**
 * Application Configuration
 * 
 * This file contains environment-specific configuration.
 * Configuration is loaded from .env file or server environment variables.
 * 
 * IMPORTANT: Always set these values in .env for development
 * or as server environment variables for production.
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

return [
    // Base URL - MUST be set in .env or server environment
    // Example: /lib_system/library_system or https://yourdomain.com
    'base_url' => env('APP_URL', ''),
    
    // Asset URL (for CSS, JS, images)
    // Defaults to base_url + /assets if not specified
    'asset_url' => env('ASSET_URL', env('APP_URL', '') . '/assets'),
    
    // Application name
    'app_name' => env('APP_NAME', 'Library Management System'),
    
    // Environment (development, staging, production)
    // Defaults to production for safety
    'environment' => env('APP_ENV', 'production'),
    
    // Debug mode
    // Defaults to false for security - NEVER enable in production
    'debug' => env('APP_DEBUG', false),
];
