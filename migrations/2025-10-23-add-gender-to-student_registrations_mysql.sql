-- Migration: add gender column to student_registrations (MySQL)
-- Run on your production or local database using mysql client or phpMyAdmin.
-- Usage: mysql -u user -p database_name < 2025-10-23-add-gender-to-student_registrations_mysql.sql

ALTER TABLE `student_registrations`
  ADD COLUMN `gender` VARCHAR(16) DEFAULT NULL AFTER `first_name`;

-- Optional: set a default value for existing rows if you want, e.g. UNKNOWN
-- UPDATE student_registrations SET gender = 'unknown' WHERE gender IS NULL;

-- End of migration
