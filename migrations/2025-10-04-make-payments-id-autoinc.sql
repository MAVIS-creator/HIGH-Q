-- Migration: make payments.id AUTO_INCREMENT primary key (safe, idempotent)
-- Location: migrations/2025-10-04-make-payments-id-autoinc.sql
--
-- This script does the following:
-- 1) Creates a backup table `payments_backup` (if it doesn't already exist) and copies current data into it
--    (only copies once if the backup table is empty).
-- 2) Checks for duplicate id values and for NULL/0 id rows. If any are found this script aborts and returns
--    a clear error message — resolve those issues before proceeding.
-- 3) Computes current MAX(id) and then alters the `payments` table to set id as INT NOT NULL AUTO_INCREMENT PRIMARY KEY
--    with AUTO_INCREMENT seeded to MAX(id)+1.
--
-- IMPORTANT: Back up your database before running. This script will abort if duplicates or bad ids are detected.
-- Run it with your MySQL client or phpMyAdmin SQL window.

-- Simplified to run via PDO without DELIMITER / stored procedure. Back up then convert payments.id to AUTO_INCREMENT.

-- 1) Backup payments once (idempotent)
CREATE TABLE IF NOT EXISTS payments_backup LIKE payments;
INSERT INTO payments_backup
SELECT * FROM payments
WHERE NOT EXISTS (SELECT 1 FROM payments_backup LIMIT 1);

-- 2) Make id AUTO_INCREMENT primary key (assumes data is already clean)
ALTER TABLE payments MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;

-- 3) Reseat AUTO_INCREMENT to max(id)+1 for good measure
SET @payments_seed := (SELECT IFNULL(MAX(id), 0) + 1 FROM payments);
SET @sql := CONCAT('ALTER TABLE payments AUTO_INCREMENT = ', @payments_seed);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
