<?php
require 'public/config/db.php';

// Delete the pending migrations so they can be re-applied fresh
$migrations = [
  '2025-10-04-make-payments-id-autoinc.sql',
  '2025-10-05-add-contact-tiktok-column.sql',
  '2025-10-23-add-waec_serial_column_mysql.sql',
  '2025-10-26-create-postutme-and-payments-columns_mysql.sql',
  '2025-12-15-add-topic-to-forum-questions.sql',
  '2025-12-23-upsert-program-slugs.sql',
  'seed_icons.php'
];

foreach ($migrations as $m) {
  $stmt = $pdo->prepare("DELETE FROM migrations WHERE filename = ?");
  $stmt->execute([$m]);
}

echo "Deleted " . count($migrations) . " pending migrations. Ready to re-run.\n";
