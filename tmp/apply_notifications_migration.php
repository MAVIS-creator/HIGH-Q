<?php
// Apply migration: Add unique key to notifications
require_once __DIR__ . '/../admin/includes/db.php';

try {
    echo "Applying migration: 2025-12-16-add-unique-key-to-notifications.sql\n\n";
    
    $sql = "ALTER TABLE notifications 
            ADD UNIQUE KEY unique_user_notification (user_id, type, reference_id)";
    
    $pdo->exec($sql);
    
    echo "✅ Migration applied successfully!\n";
    echo "The notifications table now has a unique key on (user_id, type, reference_id).\n";
    echo "This will allow notification read status to persist across page reloads.\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "ℹ️  Unique key already exists. No action needed.\n";
    } else {
        echo "❌ Error applying migration: " . $e->getMessage() . "\n";
        exit(1);
    }
}
