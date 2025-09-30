-- Migration: add category_id and tags columns to posts
-- Run this after taking a DB backup

ALTER TABLE posts
  ADD COLUMN category_id INT NULL AFTER slug,
  ADD COLUMN tags TEXT NULL AFTER category_id;

-- Add an index on category_id for faster filtering (optional)
ALTER TABLE posts
  ADD INDEX idx_posts_category_id (category_id);

-- Rollback:
-- ALTER TABLE posts DROP COLUMN tags;
-- ALTER TABLE posts DROP COLUMN category_id;
