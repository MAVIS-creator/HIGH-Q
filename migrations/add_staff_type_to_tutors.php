<?php
/**
 * Migration: Add staff type column to tutors table
 * Allows distinguishing between tutors and administrative staff
 */

require_once __DIR__ . '/../admin/includes/db.php';

try {
    echo "Adding 'type' column to tutors table...\n";
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM tutors LIKE 'type'");
    if ($stmt->rowCount() > 0) {
        echo "✓ 'type' column already exists.\n";
    } else {
        // Add the column with default value 'tutor'
        $pdo->exec("ALTER TABLE tutors ADD COLUMN `type` ENUM('tutor', 'admin_staff') DEFAULT 'tutor' AFTER `slug`");
        echo "✓ 'type' column added successfully!\n";
    }
    
    // Verify the column
    $stmt = $pdo->query('DESCRIBE tutors');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nUpdated table structure:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
