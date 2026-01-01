<?php
require 'public/config/db.php';

$tables_to_check = ['site_settings', 'course_features'];
foreach ($tables_to_check as $t) {
  $result = $pdo->query("SHOW TABLES LIKE '$t'")->fetch();
  echo ($result ? "✓" : "✗") . " $t\n";
}
