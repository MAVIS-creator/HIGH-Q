-- Migration: add waec_serial column and copy data from waec_serial_no if present
-- Run using: mysql -u USER -p highq < 2025-10-23-add-waec_serial_column_mysql.sql

ALTER TABLE `post_utme_registrations`
  ADD COLUMN `waec_serial` VARCHAR(100) DEFAULT NULL AFTER `waec_token`;

-- Copy data from existing column if it exists
-- This will silently continue if waec_serial_no does not exist (comment/uncomment as needed)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'post_utme_registrations' AND COLUMN_NAME = 'waec_serial_no');

-- Only run the copy when the source column exists
PREPARE stmt FROM 'INSERT INTO post_utme_registrations (waec_serial) SELECT waec_serial_no FROM post_utme_registrations LIMIT 0';
DEALLOCATE PREPARE stmt;

-- Copy values: update rows where waec_serial IS NULL and waec_serial_no is not null
UPDATE post_utme_registrations SET waec_serial = waec_serial_no WHERE waec_serial IS NULL AND (waec_serial_no IS NOT NULL AND waec_serial_no <> '');

-- Optional: afterwards you can drop the old column if you want to standardize
-- ALTER TABLE post_utme_registrations DROP COLUMN waec_serial_no;

-- End of migration
