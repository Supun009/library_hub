<?php
require_once 'config/db_config.php';
try {
    $pdo->exec("ALTER TABLE books ADD COLUMN publication_year INT");
    echo "Column added successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
