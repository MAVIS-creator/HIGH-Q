-- Migration: convert icons table to include Boxicons classes and create normalized course_features table

-- Create `icons` table if it doesn't exist. We include a UNIQUE index on filename so
-- INSERT ... ON DUPLICATE KEY UPDATE works when seeding.
CREATE TABLE IF NOT EXISTS icons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  `class` VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_filename (filename)
);

-- Ensure compatibility with legacy icons schema that may not contain filename
SET @has_filename := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'icons'
    AND COLUMN_NAME = 'filename'
);
SET @sql_add_filename := IF(@has_filename = 0,
  'ALTER TABLE icons ADD COLUMN filename VARCHAR(255) NULL AFTER name',
  'SELECT 1'
);
PREPARE stmt_add_filename FROM @sql_add_filename;
EXECUTE stmt_add_filename;
DEALLOCATE PREPARE stmt_add_filename;

-- Backfill filename where missing so future lookups are stable
UPDATE icons
SET filename = CONCAT(REPLACE(LOWER(name), ' ', '-'), '.svg')
WHERE (filename IS NULL OR filename = '')
  AND (name IS NOT NULL AND name <> '');

-- Idempotent seed/update for icon classes
INSERT INTO icons (name, filename, `class`)
SELECT 'Target', 'target.svg', 'bx bxs-bullseye'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Target' OR filename = 'target.svg');
UPDATE icons SET `class` = 'bx bxs-bullseye', filename = 'target.svg' WHERE name = 'Target' OR filename = 'target.svg';

INSERT INTO icons (name, filename, `class`)
SELECT 'Book Stack', 'book-stack.svg', 'bx bxs-book-bookmark'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Book Stack' OR filename = 'book-stack.svg');
UPDATE icons SET `class` = 'bx bxs-book-bookmark', filename = 'book-stack.svg' WHERE name = 'Book Stack' OR filename = 'book-stack.svg';

INSERT INTO icons (name, filename, `class`)
SELECT 'Book Open', 'book-open.svg', 'bx bxs-book-open'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Book Open' OR filename = 'book-open.svg');
UPDATE icons SET `class` = 'bx bxs-book-open', filename = 'book-open.svg' WHERE name = 'Book Open' OR filename = 'book-open.svg';

INSERT INTO icons (name, filename, `class`)
SELECT 'Trophy', 'trophy.svg', 'bx bxs-trophy'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Trophy' OR filename = 'trophy.svg');
UPDATE icons SET `class` = 'bx bxs-trophy', filename = 'trophy.svg' WHERE name = 'Trophy' OR filename = 'trophy.svg';

INSERT INTO icons (name, filename, `class`)
SELECT 'Star', 'star.svg', 'bx bxs-star'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Star' OR filename = 'star.svg');
UPDATE icons SET `class` = 'bx bxs-star', filename = 'star.svg' WHERE name = 'Star' OR filename = 'star.svg';

INSERT INTO icons (name, filename, `class`)
SELECT 'Laptop', 'laptop.svg', 'bx bxs-laptop'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Laptop' OR filename = 'laptop.svg');
UPDATE icons SET `class` = 'bx bxs-laptop', filename = 'laptop.svg' WHERE name = 'Laptop' OR filename = 'laptop.svg';

INSERT INTO icons (name, filename, `class`)
SELECT 'Teacher', 'teacher.svg', 'bx bxs-user'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Teacher' OR filename = 'teacher.svg');
UPDATE icons SET `class` = 'bx bxs-user', filename = 'teacher.svg' WHERE name = 'Teacher' OR filename = 'teacher.svg';

INSERT INTO icons (name, filename, `class`)
SELECT 'Results', 'results.svg', 'bx bxs-bar-chart-alt-2'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Results' OR filename = 'results.svg');
UPDATE icons SET `class` = 'bx bxs-bar-chart-alt-2', filename = 'results.svg' WHERE name = 'Results' OR filename = 'results.svg';

INSERT INTO icons (name, filename, `class`)
SELECT 'Graduation', 'graduation.svg', 'bx bxs-graduation'
WHERE NOT EXISTS (SELECT 1 FROM icons WHERE name = 'Graduation' OR filename = 'graduation.svg');
UPDATE icons SET `class` = 'bx bxs-graduation', filename = 'graduation.svg' WHERE name = 'Graduation' OR filename = 'graduation.svg';

-- Create normalized features table
CREATE TABLE IF NOT EXISTS course_features (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  feature_text VARCHAR(500) NOT NULL,
  position INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_course_id (course_id),
  CONSTRAINT fk_course_features_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Note: migrating existing newline-separated features from courses.features is handled by the PHP helper
-- See migrations/migrate_course_features.php
