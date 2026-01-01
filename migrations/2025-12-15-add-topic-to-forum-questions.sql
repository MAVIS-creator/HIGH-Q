-- Migration: add topic to forum_questions and an index for filtering
-- Idempotent; creates table if missing

CREATE TABLE IF NOT EXISTS `forum_questions` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) DEFAULT NULL,
  `topic` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `forum_questions`
  ADD COLUMN IF NOT EXISTS `topic` varchar(100) NULL;

CREATE INDEX IF NOT EXISTS `idx_forum_questions_topic` ON `forum_questions` (`topic`);
