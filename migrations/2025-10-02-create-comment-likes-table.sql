-- Migration: create comment_likes table to prevent duplicate likes per session/IP
CREATE TABLE IF NOT EXISTS comment_likes (
  id INT NOT NULL AUTO_INCREMENT,
  comment_id INT NOT NULL,
  session_id VARCHAR(128) DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_comment_like (comment_id, session_id, ip),
  KEY idx_comment_id (comment_id)
);
