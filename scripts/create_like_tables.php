<?php
// scripts/create_like_tables.php
// Run from project root: php scripts/create_like_tables.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load public DB config
require __DIR__ . '/../public/config/db.php';

$sqls = [
    // from migrations/2025-10-01-create-post-likes-table.sql
    "CREATE TABLE IF NOT EXISTS post_likes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      post_id INT NOT NULL,
      session_id VARCHAR(128) DEFAULT NULL,
      ip VARCHAR(45) DEFAULT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY ux_post_session_ip (post_id, session_id, ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // from migrations/2025-10-02-create-comment-likes-table.sql
    "CREATE TABLE IF NOT EXISTS comment_likes (
      id INT NOT NULL AUTO_INCREMENT,
      comment_id INT NOT NULL,
      session_id VARCHAR(128) DEFAULT NULL,
      ip VARCHAR(45) DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uniq_comment_like (comment_id, session_id, ip),
      KEY idx_comment_id (comment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];

foreach ($sqls as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "Statement #" . ($i+1) . " executed successfully\n";
    } catch (Throwable $e) {
        echo "Statement #" . ($i+1) . " failed: " . $e->getMessage() . "\n";
    }
}

// report existence
try {
    $q = $pdo->query("SHOW TABLES LIKE 'post_likes'"); $r = $q ? $q->fetchAll() : [];
    echo "post_likes: " . (empty($r) ? 'MISSING' : 'OK') . "\n";
    $q2 = $pdo->query("SHOW TABLES LIKE 'comment_likes'"); $r2 = $q2 ? $q2->fetchAll() : [];
    echo "comment_likes: " . (empty($r2) ? 'MISSING' : 'OK') . "\n";
} catch (Throwable $e) {
    echo "Check failed: " . $e->getMessage() . "\n";
}

echo "Done.\n";
