<?php
/**
 * Auto-Suspend Members with Long Overdue Books
 * 
 * This script should be run periodically (e.g., via cron job) to automatically
 * suspend members who have books overdue for more than a specified number of days.
 * 
 * Usage: php auto_suspend_members.php
 * Or set up a cron job: 0 2 * * * php /path/to/auto_suspend_members.php
 */

require_once __DIR__ . '/../config/db_config.php';

// Configuration
$OVERDUE_DAYS_THRESHOLD = 30; // Suspend if overdue for more than 30 days
$DRY_RUN = false; // Set to true to see what would happen without making changes

try {
    // Calculate the threshold date in PHP
    $thresholdDate = date('Y-m-d', strtotime("-{$OVERDUE_DAYS_THRESHOLD} days"));
    
    // Find members with books overdue for more than threshold days
    $query = "
        SELECT DISTINCT m.member_id, m.full_name, m.email, m.status,
               COUNT(i.issue_id) as overdue_count,
               MIN(i.due_date) as earliest_due_date,
               DATEDIFF(CURDATE(), MIN(i.due_date)) as days_overdue
        FROM members m
        JOIN issues i ON m.member_id = i.member_id
        WHERE i.return_date IS NULL
          AND i.due_date < :threshold_date
          AND m.status = 'active'
        GROUP BY m.member_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':threshold_date', $thresholdDate, PDO::PARAM_STR);
    $stmt->execute();
    $membersToSuspend = $stmt->fetchAll();
    
    if (count($membersToSuspend) === 0) {
        echo "No members need to be suspended.\n";
        exit(0);
    }
    
    echo "Found " . count($membersToSuspend) . " member(s) to suspend:\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($membersToSuspend as $member) {
        echo sprintf(
            "ID: %d | Name: %s | Overdue Books: %d | Days Overdue: %d\n",
            $member['member_id'],
            $member['full_name'],
            $member['overdue_count'],
            $member['days_overdue']
        );
        
        if (!$DRY_RUN) {
            // Suspend the member (set status to 'suspended')
            $updateStmt = $pdo->prepare("UPDATE members SET status = 'suspended' WHERE member_id = ?");
            $updateStmt->execute([$member['member_id']]);
            
            // Optional: Log the suspension
            $logStmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, description, created_at)
                VALUES (NULL, 'auto_suspend', ?, NOW())
            ");
            $logDescription = sprintf(
                "Auto-suspended member ID %d (%s) - %d books overdue for %d days",
                $member['member_id'],
                $member['full_name'],
                $member['overdue_count'],
                $member['days_overdue']
            );
            
            try {
                $logStmt->execute([$logDescription]);
            } catch (PDOException $e) {
                // Log table might not exist, continue anyway
            }
            
            echo "  → SUSPENDED\n";
        } else {
            echo "  → Would suspend (DRY RUN)\n";
        }
    }
    
    echo str_repeat("-", 80) . "\n";
    
    if ($DRY_RUN) {
        echo "DRY RUN MODE - No changes were made.\n";
        echo "Set \$DRY_RUN = false to actually suspend members.\n";
    } else {
        echo "Successfully suspended " . count($membersToSuspend) . " member(s).\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
