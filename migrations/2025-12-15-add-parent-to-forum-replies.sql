-- Migration: add parent_id to forum_replies for nested threads
ALTER TABLE `forum_replies`
  ADD COLUMN `parent_id` INT NULL AFTER `question_id`,
  ADD INDEX `idx_forum_replies_parent` (`parent_id`);
