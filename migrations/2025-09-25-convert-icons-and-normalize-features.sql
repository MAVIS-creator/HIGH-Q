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

-- Seed/update icons with boxicons class names (adjust as needed)
INSERT INTO icons (name, filename, `class`) VALUES
  ('Target', 'target.svg', 'bx bxs-bullseye'),
  ('Book Stack', 'book-stack.svg', 'bx bxs-book-bookmark'),
  ('Book Open', 'book-open.svg', 'bx bxs-book-open'),
  ('Trophy', 'trophy.svg', 'bx bxs-trophy'),
  ('Star', 'star.svg', 'bx bxs-star'),
  ('Laptop', 'laptop.svg', 'bx bxs-laptop'),
  ('Teacher', 'teacher.svg', 'bx bxs-user'),
  ('Results', 'results.svg', 'bx bxs-bar-chart-alt-2'),
  ('Graduation', 'graduation.svg', 'bx bxs-graduation')
ON DUPLICATE KEY UPDATE `class` = VALUES(`class`), name = VALUES(name);

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
