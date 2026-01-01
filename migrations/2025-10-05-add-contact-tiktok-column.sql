-- Migration: ensure site_settings table exists with contact_tiktok column

CREATE TABLE IF NOT EXISTS site_settings (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(255),
  setting_value LONGTEXT,
  contact_twitter VARCHAR(512),
  contact_tiktok VARCHAR(512),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS contact_tiktok VARCHAR(512);
