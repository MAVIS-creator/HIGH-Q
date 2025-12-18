<?php
require 'admin/includes/db.php';
$result = $pdo->query('SELECT title, slug FROM courses WHERE is_active=1 ORDER BY title');
$courses = $result->fetchAll(PDO::FETCH_ASSOC);
echo "Database courses:\n";
foreach($courses as $c) {
  echo "- slug: '" . $c['slug'] . "' => title: '" . $c['title'] . "'\n";
}
