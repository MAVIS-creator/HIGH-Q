-- Migration: add created_by to posts and copy from author_id
-- Run this after taking a DB backup

ALTER TABLE posts
  ADD COLUMN created_by INT NULL AFTER author_id;

UPDATE posts
  SET created_by = author_id
  WHERE created_by IS NULL;

ALTER TABLE posts
  ADD INDEX idx_created_by (created_by);

-- Rollback:
-- ALTER TABLE posts DROP COLUMN created_by;
