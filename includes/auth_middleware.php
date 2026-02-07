<?php
// includes/auth_middleware.php
session_start();

require_once __DIR__ . '/url_helper.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to enforce login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login');
    }
}

// Function to check for specific role
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // role_id 1 = admin, 2 = member (based on our schema setup)
    if ($role === 'admin' && $_SESSION['role_id'] == 1) return true;
    if ($role === 'member' && $_SESSION['role_id'] == 2) return true;
    
    return false;
}

// Function to enforce specific role
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        // Redirect to their appropriate dashboard if they have the wrong role
        if (hasRole('admin')) {
            redirect(adminUrl('dashboard'));
        } else {
            redirect(memberUrl('/'));
        }
    }
}
?>
