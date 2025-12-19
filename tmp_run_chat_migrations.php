<?php
// Run chat attachments migrations
require_once __DIR__ . '/public/config/db.php';

try {
    // Migration 1: Create table
    $sql1 = file_get_contents(__DIR__ . '/migrations/2025-10-05-create-chat-attachments.sql');
    $pdo->exec($sql1);
    echo "✓ Created chat_attachments table\n";
    
    // Migration 2: Alter table (if exists)
    if (file_exists(__DIR__ . '/migrations/2025-10-05-alter-chat-attachments-add-meta.sql')) {
        $sql2 = file_get_contents(__DIR__ . '/migrations/2025-10-05-alter-chat-attachments-add-meta.sql');
        $pdo->exec($sql2);
        echo "✓ Altered chat_attachments table\n";
    }
    
    echo "\n✅ Migrations completed successfully!\n";
} catch (Throwable $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
}
