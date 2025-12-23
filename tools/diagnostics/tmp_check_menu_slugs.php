<?php
include 'admin/includes/db.php';
$res = $pdo->query('SELECT DISTINCT menu_slug FROM role_permissions ORDER BY menu_slug');
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo $row['menu_slug'] . PHP_EOL;
}
