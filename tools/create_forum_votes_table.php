<?php
require 'public/config/db.php';

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS forum_votes (
  id INT NOT NULL AUTO_INCREMENT,
  question_id INT NULL,
  reply_id INT NULL,
  vote TINYINT NOT NULL DEFAULT 0,
  session_id VARCHAR(128) DEFAULT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_fv_question (question_id),
  INDEX idx_fv_reply (reply_id),
  INDEX idx_fv_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL;

try {
  $pdo->exec($sql);
  echo "✓ forum_votes table created successfully\n";
  
  // Mark migration as successful
  $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (filename, status) VALUES (?, ?)");
  $stmt->execute(['2025-12-15-create-forum-votes.sql', 'success']);
  echo "✓ Migration marked as complete\n";
} catch (Exception $e) {
  echo "✗ Error: " . $e->getMessage() . "\n";
}
