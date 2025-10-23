-- Safe ALTER: Add columns to `payments` only if they don't already exist.
-- This uses information_schema to check column existence and runs the ALTER only when needed.

-- Add `form_fee_paid`
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'form_fee_paid');
SET @s = IF(@exists = 0, 'ALTER TABLE `payments` ADD COLUMN `form_fee_paid` tinyint(1) DEFAULT 0;', 'SELECT "column form_fee_paid already exists";');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add `tutor_fee_paid`
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'tutor_fee_paid');
SET @s = IF(@exists = 0, 'ALTER TABLE `payments` ADD COLUMN `tutor_fee_paid` tinyint(1) DEFAULT 0;', 'SELECT "column tutor_fee_paid already exists";');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add `registration_type`
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'registration_type');
SET @s = IF(@exists = 0, 'ALTER TABLE `payments` ADD COLUMN `registration_type` varchar(20) DEFAULT ''regular'';', 'SELECT "column registration_type already exists";');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Note: Run this file using the MySQL client connected to the correct database. If your MySQL user lacks privileges, run as a privileged user.
