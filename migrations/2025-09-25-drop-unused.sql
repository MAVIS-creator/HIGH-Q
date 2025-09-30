-- Migration: cleanup unused columns/tables after normalizing course features
-- IMPORTANT: Review and backup your database before running these statements.

-- 1) Remove the old 'features' column from courses (safe after migrating data)
-- Run this once you've verified course_features has been populated correctly.
-- ALTER TABLE courses DROP COLUMN features;

-- 2) If you migrated icons to use the 'class' column and no longer need the image filename, you can remove filename:
-- ALTER TABLE icons DROP COLUMN filename;

-- 3) If you no longer need the 'icons' table and want to remove it entirely (make sure nothing references it):
-- DROP TABLE IF EXISTS icons;

-- 4) If you have any legacy tables you know are unused, list them below. Example:
-- DROP TABLE IF EXISTS legacy_table_name;

-- 5) Optional: remove migration helper script after use (file system cleanup, not SQL):
-- migrations/migrate_course_features.php

-- NOTE: All DROP/ALTER statements are commented out by default. Uncomment the ones you intend to run.
