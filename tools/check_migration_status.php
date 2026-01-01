<?php
require 'public/config/db.php';

$key_migrations = [
  '2025-09-19-create-site_settings.sql',
  '2025-09-25-add-course-fields-and-icons.sql',
  '2025-09-25-convert-icons-and-normalize-features.sql'
];

foreach ($key_migrations as $m) {
  $result = $pdo->query("SELECT status FROM migrations WHERE filename = '$m'")->fetch();
  echo "$m: " . ($result ? $result['status'] : 'NOT FOUND') . "\n";
}
