<?php
require_once 'config/db_config.php';

echo "=== Settings Table ===\n\n";

try {
    // Check if settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    if (!$stmt->fetch()) {
        echo "❌ Settings table does not exist!\n";
        exit;
    }
    
    echo "✅ Settings table exists\n\n";
    
    // Show structure
    echo "Table Structure:\n";
    $stmt = $pdo->query("DESCRIBE settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\nSettings Data:\n";
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['setting_key']} = {$row['setting_value']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
