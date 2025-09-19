-- Migration: create site_settings table
-- Run this against your MySQL/MariaDB instance to create a structured settings table
CREATE TABLE IF NOT EXISTS site_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  site_name VARCHAR(255) DEFAULT NULL,
  tagline VARCHAR(255) DEFAULT NULL,
  logo_url VARCHAR(1024) DEFAULT NULL,
  vision TEXT DEFAULT NULL,
  about TEXT DEFAULT NULL,
  contact_phone VARCHAR(64) DEFAULT NULL,
  contact_email VARCHAR(255) DEFAULT NULL,
  contact_address TEXT DEFAULT NULL,
  contact_facebook VARCHAR(512) DEFAULT NULL,
  contact_twitter VARCHAR(512) DEFAULT NULL,
  contact_instagram VARCHAR(512) DEFAULT NULL,
  maintenance TINYINT(1) DEFAULT 0,
  registration TINYINT(1) DEFAULT 1,
  email_verification TINYINT(1) DEFAULT 1,
  two_factor TINYINT(1) DEFAULT 0,
  comment_moderation TINYINT(1) DEFAULT 1,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: seed a default row so reading the single-row table is simple
INSERT INTO site_settings (site_name, tagline, logo_url, vision, about)
SELECT 'HIGH Q SOLID ACADEMY', '', '', '', ''
WHERE NOT EXISTS (SELECT 1 FROM site_settings LIMIT 1);
