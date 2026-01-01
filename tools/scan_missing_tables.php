<?php
require 'public/config/db.php';

// Get all successful migrations
$migrations = $pdo->query("SELECT filename FROM migrations WHERE status = 'success' ORDER BY filename")->fetchAll(PDO::FETCH_COLUMN);

// Parse table names from migration filenames (heuristic)
$expected_tables = [
    'audit_logs', 'blocked_ips', 'chat_attachments', 'chat_messages', 'chat_threads',
    'comment_likes', 'comments', 'course_features', 'courses', 'forum_questions',
    'forum_replies', 'forum_votes', 'icons', 'ip_logs', 'login_attempts',
    'mac_blocklist', 'menus', 'migrations', 'newsletter_subscribers', 'notifications',
    'password_resets', 'payments', 'post_likes', 'post_utme_registrations', 'posts',
    'role_permissions', 'roles', 'site_settings', 'student_registrations',
    'testimonials', 'users', 'universal_registrations'
];

// Get all actual tables
$actual = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$actual_set = array_flip($actual);

echo "=== MISSING TABLE SCAN ===\n\n";
$missing = [];
foreach ($expected_tables as $table) {
    if (!isset($actual_set[$table])) {
        $missing[] = $table;
        echo "✗ $table - MISSING\n";
    }
}

if (empty($missing)) {
    echo "✓ All expected tables exist\n";
} else {
    echo "\n" . count($missing) . " tables missing. Create them? (y/n): ";
}

echo "\n\nActual table count: " . count($actual) . "\n";
echo "Expected table count: " . count($expected_tables) . "\n";
