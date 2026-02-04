<?php
require_once 'config/db_config.php';
try {
    // Add due_date to issues table
    $pdo->exec("ALTER TABLE issues ADD COLUMN due_date DATE AFTER issue_date");
    echo "Column 'due_date' added to 'issues' table successfully.<br>";

    // Update existing records to have a due_date (e.g., 14 days after issue_date)
    $pdo->exec("UPDATE issues SET due_date = DATE_ADD(issue_date, INTERVAL 14 DAY) WHERE due_date IS NULL");
    echo "Updated existing issues with default due date (14 days).";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
