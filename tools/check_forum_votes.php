<?php
require 'public/config/db.php';

$result = $pdo->query("SHOW TABLES LIKE 'forum_votes'")->fetch();
echo $result ? 'forum_votes exists' : 'forum_votes missing';
