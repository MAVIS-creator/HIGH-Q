<?php
/**
 * Idempotent migration: add `passport_path` column to student_registrations if missing.
 * Run with: php migrations/2025-10-20-add-passport-path.php
 */

require_once __DIR__ . "/../public/config/db.php";

try {
    $row = $pdo->query("SHOW COLUMNS FROM student_registrations LIKE 'passport_path'")->fetch();
    if ($row) {
        echo "Column 'passport_path' already exists in student_registrations.\n";
        exit(0);
    }

    $sql = "ALTER TABLE student_registrations ADD COLUMN passport_path varchar(512) DEFAULT NULL AFTER email";
    $pdo->exec($sql);
    echo "Added 'passport_path' column to student_registrations.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
