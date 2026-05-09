-- Queue for AI-proposed admin tasks that require human confirmation.

CREATE TABLE IF NOT EXISTS ai_action_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action_type VARCHAR(100) NOT NULL,
  proposal LONGTEXT NOT NULL,
  context LONGTEXT NULL,
  status ENUM('queued','approved','rejected','executed','failed') NOT NULL DEFAULT 'queued',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ai_action_queue_user_id (user_id),
  INDEX idx_ai_action_queue_status (status),
  INDEX idx_ai_action_queue_created_at (created_at)
);