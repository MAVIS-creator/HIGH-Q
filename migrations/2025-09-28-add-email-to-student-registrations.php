<?php
/**
 * Idempotent migration: add `email` column to student_registrations if missing.
 * Run with: php migrations/2025-09-28-add-email-to-student-registrations.php
 */

require_once __DIR__ . "/../public/config/db.php";

try {
    $row = $pdo->query("SHOW COLUMNS FROM student_registrations LIKE 'email'")->fetch();
    if ($row) {
        echo "Column 'email' already exists in student_registrations.\n";
        exit(0);
    }

    $sql = "ALTER TABLE student_registrations ADD COLUMN email varchar(255) DEFAULT NULL AFTER last_name";
    $pdo->exec($sql);
    echo "Added 'email' column to student_registrations.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
