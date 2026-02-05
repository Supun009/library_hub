<?php
require_once 'config/db_config.php';
try {
    // Add phone_number to members
    $pdo->exec("ALTER TABLE members ADD COLUMN phone_number VARCHAR(20)");
    echo "Phone number column added to members.<br>";

    // Create settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Seed default settings
    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
        ('fine_per_day', '0.50'),
        ('loan_period_days', '14'),
        ('max_books_per_member', '5')
    ");
    
    echo "Settings table created and seeded.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
