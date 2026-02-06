<?php
// index.php

// Load dependencies
require_once __DIR__ . '/includes/url_helper.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/includes/Router.php';

// Initialize Router with Base URL path
// Extract the path component from the configured Base URL
$basePath = parse_url(getBaseUrl(), PHP_URL_PATH);
$router = new Router($basePath);

/**
 * Define Routes
 * --------------------------------------------------------------------------
 */

// Home Route
$router->get('/', function() {
    if (isLoggedIn()) {
        if (hasRole('admin')) {
            redirect(adminUrl('dashboard.php'));
        } else {
            redirect(memberUrl('index.php'));
        }
    } else {
        redirect(authUrl('login.php'));
    }
});

// Example of a clean route wrapping a legacy file (Future Migration Step)
// $router->get('/login', function() {
//     require __DIR__ . '/auth/login.php'; // Note: Requires fixing relative paths in login.php
// });

/**
 * Dispatch the Request
 * --------------------------------------------------------------------------
 */
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

