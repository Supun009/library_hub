<?php
require_once 'config/db_config.php';
try {
    // Add status column to members if not exists
    $pdo->exec("ALTER TABLE members ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    echo "Added 'status' column to members.<br>";

    // Add deleted_at column to members for soft delete
    $pdo->exec("ALTER TABLE members ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");
    echo "Added 'deleted_at' column to members.<br>";

    // Also update users table to have active status? 
    // Usually good practice, but for now we might control login via member query separately or just assume member status reflects user info.
    // Let's stick to members table for management.

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
