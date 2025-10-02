-- Migration: add session_id to comments so we can show pending comments to their author session
-- Run this in your MySQL instance (phpMyAdmin, CLI or your migration runner)

ALTER TABLE `comments`
  ADD COLUMN `session_id` VARCHAR(128) DEFAULT NULL AFTER `status`;

-- optional index for faster lookups by session
CREATE INDEX `idx_comments_session_id` ON `comments` (`session_id`);
