<?php
// index.php

// Load dependencies
require_once __DIR__ . '/../includes/url_helper.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/Router.php';

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
            redirect('admin/dashboard');
        } else {
            redirect('member');
        }
    } else {
        redirect('login');
    }
});

// Auth Routes
$router->get('/login', function() { require __DIR__ . '/../auth/login.php'; });
$router->post('/login', function() { require __DIR__ . '/../auth/login.php'; });

$router->get('/signup', function() { require __DIR__ . '/../auth/signup.php'; });
$router->post('/signup', function() { require __DIR__ . '/../auth/signup.php'; });

$router->get('/logout', function() { require __DIR__ . '/../auth/logout.php'; });

// Admin Routes
$router->get('/admin/dashboard', function() { require __DIR__ . '/../admin/dashboard.php'; });

$router->get('/admin/books', function() { require __DIR__ . '/../admin/manage_books.php'; });
$router->post('/admin/books', function() { require __DIR__ . '/../admin/manage_books.php'; });

$router->get('/admin/books/add', function() { require __DIR__ . '/../admin/add_book.php'; });
$router->post('/admin/books/add', function() { require __DIR__ . '/../admin/add_book.php'; });

$router->get('/admin/books/edit', function() { require __DIR__ . '/../admin/edit_book.php'; });
$router->post('/admin/books/edit', function() { require __DIR__ . '/../admin/edit_book.php'; });

$router->get('/admin/members', function() { require __DIR__ . '/../admin/manage_members.php'; });
$router->post('/admin/members', function() { require __DIR__ . '/../admin/manage_members.php'; });

$router->get('/admin/members/edit', function() { require __DIR__ . '/../admin/edit_member.php'; });
$router->post('/admin/members/edit', function() { require __DIR__ . '/../admin/edit_member.php'; });

$router->get('/admin/issue', function() { require __DIR__ . '/../admin/issue_book.php'; });
$router->post('/admin/issue', function() { require __DIR__ . '/../admin/issue_book.php'; });

$router->get('/admin/return', function() { require __DIR__ . '/../admin/return_book.php'; });
$router->post('/admin/return', function() { require __DIR__ . '/../admin/return_book.php'; });

$router->get('/admin/transactions', function() { require __DIR__ . '/../admin/transactions.php'; });

$router->get('/admin/search', function() { require __DIR__ . '/../admin/search.php'; });

$router->get('/admin/profile', function() { require __DIR__ . '/../admin/profile.php'; });
$router->post('/admin/profile', function() { require __DIR__ . '/../admin/profile.php'; });

// AJAX Routes
$router->post('/admin/ajax/add-author', function() { require __DIR__ . '/../admin/ajax_add_author.php'; });
$router->post('/admin/ajax/add-category', function() { require __DIR__ . '/../admin/ajax_add_category.php'; });
$router->get('/admin/ajax/search-books', function() { require __DIR__ . '/../admin/ajax_search_books.php'; });
$router->get('/admin/ajax/search-members', function() { require __DIR__ . '/../admin/ajax_search_members.php'; });

// Categories and Authors Management
$router->get('/admin/categories', function() { require __DIR__ . '/../admin/manage_categories.php'; });
$router->post('/admin/categories', function() { require __DIR__ . '/../admin/manage_categories.php'; });

$router->get('/admin/authors', function() { require __DIR__ . '/../admin/manage_authors.php'; });
$router->post('/admin/authors', function() { require __DIR__ . '/../admin/manage_authors.php'; });

// Admin Member Management Routes
$router->get('/admin/members/edit', function() { require __DIR__ . '/../admin/edit_member.php'; });
$router->post('/admin/members/edit', function() { require __DIR__ . '/../admin/edit_member.php'; });
$router->get('/admin/members/history', function() { require __DIR__ . '/../admin/member_history.php'; });

// Member Routes
$router->get('/member', function() { require __DIR__ . '/../member/index.php'; });
$router->get('/member/loans', function() { require __DIR__ . '/../member/my_loans.php'; });
$router->get('/member/search', function() { require __DIR__ . '/../member/search.php'; });

// API Routes
$router->get('/api/search', function() { require __DIR__ . '/../api/global_search.php'; });

/**
 * Dispatch the Request
 * --------------------------------------------------------------------------
 */
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

