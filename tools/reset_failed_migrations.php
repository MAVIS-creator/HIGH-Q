<?php
require 'public/config/db.php';
// Reset failed migrations to allow re-running
$pdo->exec("UPDATE migrations SET status = 'pending' WHERE status = 'failed'");
echo "Reset failed migrations to pending status.\n";
