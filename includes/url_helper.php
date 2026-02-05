<?php
/**
 * URL Helper Functions
 * 
 * These functions provide a centralized way to generate URLs throughout the application.
 * This makes it easy to change URL structure or deploy to different environments.
 */

/**
 * Get the base URL for the application
 * 
 * @return string
 */
function getBaseUrl() {
    static $baseUrl = null;
    
    if ($baseUrl === null) {
        $config = require __DIR__ . '/../config/app_config.php';
        $baseUrl = rtrim($config['base_url'], '/');
    }
    
    return $baseUrl;
}

/**
 * Generate a URL for the application
 * 
 * @param string $path The path relative to base URL
 * @return string
 */
function url($path = '') {
    $baseUrl = getBaseUrl();
    $path = ltrim($path, '/');
    
    return $path ? $baseUrl . '/' . $path : $baseUrl;
}

/**
 * Generate an admin URL
 * 
 * @param string $path The path relative to admin directory
 * @return string
 */
function adminUrl($path = '') {
    return url('admin/' . ltrim($path, '/'));
}

/**
 * Generate a member URL
 * 
 * @param string $path The path relative to member directory
 * @return string
 */
function memberUrl($path = '') {
    return url('member/' . ltrim($path, '/'));
}

/**
 * Generate an auth URL
 * 
 * @param string $path The path relative to auth directory
 * @return string
 */
function authUrl($path = '') {
    return url('auth/' . ltrim($path, '/'));
}

/**
 * Generate an asset URL (for CSS, JS, images)
 * 
 * @param string $path The path relative to assets directory
 * @return string
 */
function asset($path = '') {
    static $assetUrl = null;
    
    if ($assetUrl === null) {
        $config = require __DIR__ . '/../config/app_config.php';
        $assetUrl = rtrim($config['asset_url'], '/');
    }
    
    $path = ltrim($path, '/');
    return $path ? $assetUrl . '/' . $path : $assetUrl;
}

/**
 * Generate an API URL
 * 
 * @param string $path The path relative to api directory
 * @return string
 */
function apiUrl($path = '') {
    return url('api/' . ltrim($path, '/'));
}

/**
 * Check if current URL matches the given path
 * 
 * @param string $path The path to check
 * @return bool
 */
function isCurrentUrl($path) {
    $currentPath = $_SERVER['REQUEST_URI'];
    $checkPath = url($path);
    
    return strpos($currentPath, $checkPath) !== false;
}

/**
 * Redirect to a URL
 * 
 * @param string $path The path to redirect to
 * @param int $statusCode HTTP status code (default: 302)
 * @return void
 */
function redirect($path, $statusCode = 302) {
    header('Location: ' . url($path), true, $statusCode);
    exit;
}
