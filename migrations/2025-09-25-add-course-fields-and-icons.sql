-- Migration: add icon, features and highlight_badge to courses; create icons table and seed common icons
ALTER TABLE courses
  ADD COLUMN icon VARCHAR(255) DEFAULT NULL,
  ADD COLUMN features TEXT DEFAULT NULL,
  ADD COLUMN highlight_badge VARCHAR(100) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS icons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed a few commonly used icons (filenames refer to public/assets/images/icons/*.svg)
INSERT INTO icons (name, filename) VALUES
  ('Target', 'target.svg'),
  ('Book Stack', 'book-stack.svg'),
  ('Book Open', 'book-open.svg'),
  ('Trophy', 'trophy.svg'),
  ('Star', 'star.svg'),
  ('Laptop', 'laptop.svg'),
  ('Teacher', 'teacher.svg'),
  ('Results', 'results.svg'),
  ('Graduation', 'graduation.svg')
ON DUPLICATE KEY UPDATE name=VALUES(name);
