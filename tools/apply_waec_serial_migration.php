<?php
// tools/apply_waec_serial_migration.php
// Safely adds waec_serial column and copies values from waec_serial_no if present.
require __DIR__ . '/../public/config/db.php';

function columnExists($pdo, $table, $col) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $st->execute([$table, $col]);
    return (bool)$st->fetchColumn();
}

$table = 'post_utme_registrations';
if (!columnExists($pdo, $table, 'waec_serial')) {
    echo "Adding column waec_serial...\n";
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `waec_serial` VARCHAR(100) DEFAULT NULL AFTER `waec_token`");
    echo "Added waec_serial.\n";
} else {
    echo "waec_serial already exists.\n";
}

// If waec_serial_no exists and waec_serial is empty, copy values
if (columnExists($pdo, $table, 'waec_serial_no')) {
    echo "Copying values from waec_serial_no to waec_serial where missing...\n";
    $rows = $pdo->prepare("UPDATE `$table` SET waec_serial = waec_serial_no WHERE (waec_serial IS NULL OR waec_serial = '') AND (waec_serial_no IS NOT NULL AND waec_serial_no <> '')");
    $rows->execute();
    echo "Copied " . $rows->rowCount() . " rows.\n";
} else {
    echo "No waec_serial_no column found; nothing to copy.\n";
}

echo "Migration complete.\n";
