-- Migration: add contact_tiktok column and migrate existing contact_twitter values into it
SET @OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE='ALLOW_INVALID_DATES';
START TRANSACTION;
ALTER TABLE site_settings
  ADD COLUMN contact_tiktok VARCHAR(512) DEFAULT NULL;

-- Copy data from legacy contact_twitter into new column for existing rows
UPDATE site_settings SET contact_tiktok = contact_twitter WHERE contact_tiktok IS NULL AND (contact_twitter IS NOT NULL AND contact_twitter <> '');

COMMIT;
SET SQL_MODE=@OLD_SQL_MODE;

-- Note: keep contact_twitter column for backward compatibility; applications should start reading/writing contact_tiktok first.
