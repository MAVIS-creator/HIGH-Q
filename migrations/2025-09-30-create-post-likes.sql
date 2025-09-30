-- Migration: create post_likes table to track unique likes per post and IP
CREATE TABLE IF NOT EXISTS `post_likes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `ip` VARCHAR(64) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_ip_unique` (`post_id`, `ip`),
  INDEX (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
