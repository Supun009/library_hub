<?php
// includes/validation_helper.php

/**
 * Sanitize generic text input.
 * Trims whitespace and strips HTML tags.
 */
function sanitizeInput($data) {
    if (is_null($data)) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Validate username format.
 * - Must not contain spaces.
 * - Alphanumeric and underscores allowed.
 * - Min length 3, max length 20.
 */
function validateUsername($username) {
    if (strpos($username, ' ') !== false) {
        return "Username cannot contain spaces.";
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        return "Username must be 3-20 characters long and can only contain letters, numbers, and underscores.";
    }
    return true; // Valid
}

/**
 * Validate email format.
 */
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address.";
    }
    return true;
}

/**
 * Validate password strength (basic).
 * - Min length 6.
 */
function validatePassword($password) {
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters long.";
    }
    return true;
}

/**
 * Validate phone number (optional but useful).
 * - Digits, spaces, dashes, +, ().
 */
function validatePhone($phone) {
    if (empty($phone)) return true; // Optional field usually
    if (!preg_match('/^[0-9\-\+\(\)\s]{7,20}$/', $phone)) {
        return "Please enter a valid phone number.";
    }
    return true;
}
?>
