<?php
require 'config/db_config.php';
try {
    $pdo->exec("ALTER TABLE books ADD COLUMN publication_year INT(4) DEFAULT NULL");
    echo "Migration successful: Added publication_year column.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column publication_year already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
