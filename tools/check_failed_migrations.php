<?php
require_once __DIR__ . '/../public/config/db.php';

$migrations = $pdo->query("SELECT filename, status, error_message FROM migrations WHERE status = 'failed' ORDER BY filename")->fetchAll(PDO::FETCH_ASSOC);

echo "=== FAILED MIGRATIONS DETAILS ===\n\n";
foreach ($migrations as $m) {
  echo "File: {$m['filename']}\n";
  echo "Error: " . substr($m['error_message'], 0, 200) . "\n\n";
}
