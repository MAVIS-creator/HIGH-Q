-- Migration: add contact_tiktok column (idempotent)
-- Note: site_settings table may not exist yet; this is a safe migration

CREATE TABLE IF NOT EXISTS site_settings (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(255) NOT NULL UNIQUE,
  setting_value LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE site_settings
  ADD COLUMN IF NOT EXISTS contact_tiktok VARCHAR(512) DEFAULT NULL;

-- Copy data from legacy contact_twitter if it exists
ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS contact_twitter VARCHAR(512) DEFAULT NULL;
UPDATE site_settings SET contact_tiktok = contact_twitter WHERE contact_tiktok IS NULL AND (contact_twitter IS NOT NULL AND contact_twitter <> '');
