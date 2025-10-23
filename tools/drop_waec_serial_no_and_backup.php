<?php
// tools/drop_waec_serial_no_and_backup.php
// Safely backup post_utme_registrations.waec_serial_no into a backup table and drop the legacy column.
// Usage: php tools/drop_waec_serial_no_and_backup.php
require __DIR__ . '/../public/config/db.php';

function columnExists(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column");
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

$table = 'post_utme_registrations';
$col = 'waec_serial_no';

if (!columnExists($pdo, $table, $col)) {
    echo "Column '$col' not found on table '$table'. Nothing to do.\n";
    exit(0);
}

echo "Found legacy column '$col' on '$table'. Preparing backup and drop.\n";

// Create backup table if not exists
$backupTable = 'post_utme_waec_serial_no_backup';
$pdo->exec("CREATE TABLE IF NOT EXISTS `$backupTable` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    waec_serial_no VARCHAR(255) DEFAULT NULL,
    backed_up_at DATETIME NOT NULL,
    INDEX (registration_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Count rows to backup
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$col` IS NOT NULL AND `$col` <> ''");
$countStmt->execute();
$toBackup = (int)$countStmt->fetchColumn();

if ($toBackup > 0) {
    echo "Backing up $toBackup rows to `$backupTable`...\n";
    $ins = $pdo->prepare("INSERT INTO `$backupTable` (registration_id, waec_serial_no, backed_up_at) SELECT id, `$col`, NOW() FROM `$table` WHERE `$col` IS NOT NULL AND `$col` <> ''");
    $ins->execute();
    echo "Backup completed. Inserted rows: " . $ins->rowCount() . "\n";
} else {
    echo "No non-empty values found in `$col`; backup table will remain empty.\n";
}

// Perform the drop (ALTER TABLE may not be allowed inside transactions in some MySQL setups)
try {
    echo "Dropping column `$col` from `$table`...\n";
    $pdo->exec("ALTER TABLE `$table` DROP COLUMN `$col`");
    echo "Column dropped successfully.\n";
} catch (Throwable $e) {
    // Don't attempt rollBack - ALTER TABLE may auto-commit or not be in a transaction
    echo "Failed to drop column: " . $e->getMessage() . "\n";
    echo "You can inspect the backup table 'post_utme_waec_serial_no_backup' and run the DROP manually when ready.\n";
    exit(2);
}

// Final verification
if (!columnExists($pdo, $table, $col)) {
    echo "Verification: column '$col' no longer exists on '$table'.\n";
} else {
    echo "Verification: column '$col' still exists. Please check.\n";
}

echo "Operation complete. A backup table named `$backupTable` contains the archived values.\n";

?>
