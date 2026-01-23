-- Migration: add waec_serial column and copy data from waec_serial_no if present
-- Run using: mysql -u USER -p highq < 2025-10-23-add-waec_serial_column_mysql.sql

-- Add waec_serial if missing (place it after exam_number which exists in the create script)
SET @has_waec_serial := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'post_utme_registrations' AND COLUMN_NAME = 'waec_serial');
SET @sql := IF(@has_waec_serial = 0,
  'ALTER TABLE post_utme_registrations ADD COLUMN waec_serial VARCHAR(100) DEFAULT NULL AFTER exam_number;',
  'SELECT "waec_serial already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Copy data from legacy waec_serial_no if that column exists
SET @has_waec_serial_no := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'post_utme_registrations' AND COLUMN_NAME = 'waec_serial_no');
SET @copy_sql := IF(@has_waec_serial_no = 1,
  'UPDATE post_utme_registrations SET waec_serial = waec_serial_no WHERE waec_serial IS NULL AND waec_serial_no IS NOT NULL AND waec_serial_no <> '''';',
  'SELECT "waec_serial_no not present"'
);
PREPARE stmt FROM @copy_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional cleanup after verifying data
-- ALTER TABLE post_utme_registrations DROP COLUMN waec_serial_no;

-- End of migration
