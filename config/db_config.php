<?php
// config/db_config.php

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database configuration from .env
$host = env('DB_HOST', '127.0.0.1');
$db_name = env('DB_DATABASE', 'library_db');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
$port = env('DB_PORT', '3306');

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO error mode to exception for better debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Load URL helper functions
require_once __DIR__ . '/../includes/url_helper.php';
?>
