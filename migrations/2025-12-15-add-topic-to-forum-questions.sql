-- Migration: add topic to forum_questions and an index for filtering
ALTER TABLE `forum_questions`
  ADD COLUMN `topic` varchar(100) NULL AFTER `name`;

CREATE INDEX `idx_forum_questions_topic` ON `forum_questions` (`topic`);
