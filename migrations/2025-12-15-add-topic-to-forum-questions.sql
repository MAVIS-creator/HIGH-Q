-- Migration: ensure topic column exists and is indexed

ALTER TABLE forum_questions ADD COLUMN IF NOT EXISTS topic VARCHAR(100);
