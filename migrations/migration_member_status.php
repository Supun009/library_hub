<?php
require_once 'config/db_config.php';

echo "=== Member Status Migration ===\n\n";

try {
    // Check if status column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM members LIKE 'status'");
    $statusExists = $stmt->fetch();
    
    if ($statusExists) {
        echo "Status column exists. Checking current type...\n";
        
        // Check if 'suspended' is already in the ENUM
        $currentType = $statusExists['Type'];
        echo "Current type: $currentType\n";
        
        if (strpos($currentType, 'suspended') === false) {
            echo "Adding 'suspended' to status ENUM...\n";
            $pdo->exec("ALTER TABLE members MODIFY COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'");
            echo "✅ Successfully added 'suspended' status!\n";
        } else {
            echo "✅ 'suspended' status already exists!\n";
        }
    } else {
        echo "Status column doesn't exist. Creating it...\n";
        $pdo->exec("ALTER TABLE members ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'");
        echo "✅ Created status column with suspended status!\n";
    }
    
    // Check deleted_at column
    $stmt = $pdo->query("SHOW COLUMNS FROM members LIKE 'deleted_at'");
    $deletedAtExists = $stmt->fetch();
    
    if (!$deletedAtExists) {
        echo "\nAdding deleted_at column...\n";
        $pdo->exec("ALTER TABLE members ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");
        echo "✅ Added deleted_at column!\n";
    } else {
        echo "\n✅ deleted_at column already exists!\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "\nStatus Values:\n";
    echo "- active: Normal member, can borrow books\n";
    echo "- inactive: Manually deactivated by admin\n";
    echo "- suspended: Auto-suspended due to long overdue books\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
