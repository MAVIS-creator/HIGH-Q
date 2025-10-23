-- Migration: backup and drop legacy waec_serial_no column from post_utme_registrations
-- This script creates a backup table and copies existing non-empty waec_serial_no values into it, then drops the original column.
-- Run manually using your mysql client: mysql -u USER -p highq < 2025-10-24-drop-waec_serial_no_safe.sql

SET @table := 'post_utme_registrations';
SET @col := 'waec_serial_no';

-- Create backup table
CREATE TABLE IF NOT EXISTS post_utme_waec_serial_no_backup (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NOT NULL,
  waec_serial_no VARCHAR(255) DEFAULT NULL,
  backed_up_at DATETIME NOT NULL,
  INDEX (registration_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Copy non-empty values
INSERT INTO post_utme_waec_serial_no_backup (registration_id, waec_serial_no, backed_up_at)
SELECT id, waec_serial_no, NOW() FROM post_utme_registrations WHERE waec_serial_no IS NOT NULL AND waec_serial_no <> '';

-- Finally, drop column (uncomment to execute)
-- ALTER TABLE post_utme_registrations DROP COLUMN waec_serial_no;

-- Note: We intentionally leave the DROP commented. Review backup table contents before dropping.
