-- Migration: 2025-09-30 - add featured_image to posts
-- Adds a nullable featured_image column to store the image URL/path

-- Up: add column
ALTER TABLE `posts`
  ADD COLUMN `featured_image` VARCHAR(1024) DEFAULT NULL AFTER `excerpt`;

-- Optional: if you want to normalize existing relative paths stored elsewhere (example)
-- UPDATE `posts` SET `featured_image` = CONCAT('https://your-app-url.example', '/', `featured_image`) WHERE `featured_image` IS NOT NULL AND `featured_image` NOT LIKE 'http%';

-- Down: remove column
-- ALTER TABLE `posts` DROP COLUMN `featured_image`;

-- Note: replace 'https://your-app-url.example' above with your actual APP_URL if you want to migrate relative paths to full URLs.
