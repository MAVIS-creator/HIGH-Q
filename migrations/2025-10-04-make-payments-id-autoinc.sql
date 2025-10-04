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

DELIMITER $$

CREATE PROCEDURE migrate_payments_autoinc()
BEGIN
  DECLARE dup_count INT DEFAULT 0;
  DECLARE bad_count INT DEFAULT 0;
  DECLARE max_id_val INT DEFAULT 0;

  -- 1) Ensure a backup exists; copy data only if backup table is empty
  CREATE TABLE IF NOT EXISTS payments_backup LIKE payments;
  IF (SELECT COUNT(*) FROM payments_backup) = 0 THEN
    INSERT INTO payments_backup SELECT * FROM payments;
  END IF;

  -- 2) Checks: duplicates and null/zero ids
  SELECT COUNT(*) INTO dup_count FROM (
    SELECT id FROM payments GROUP BY id HAVING COUNT(*) > 1
  ) AS dup_q;

  SELECT COUNT(*) INTO bad_count FROM payments WHERE id IS NULL OR id = 0;

  IF dup_count > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = CONCAT('Migration aborted: found ', dup_count, ' duplicate id(s) in payments. Resolve duplicates before running this migration.');
  END IF;

  IF bad_count > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = CONCAT('Migration aborted: found ', bad_count, ' payments with NULL or 0 id. Assign valid unique ids (or remove) before running this migration.');
  END IF;

  -- 3) Compute MAX(id) and alter table
  SELECT IFNULL(MAX(id), 0) INTO max_id_val FROM payments;
  SET @seed = max_id_val + 1;

  -- Alter the column to be AUTO_INCREMENT and PRIMARY KEY
  SET @sql = CONCAT('ALTER TABLE payments MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, AUTO_INCREMENT = ', @seed, ';');
  PREPARE stmt FROM @sql;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;

END$$

DELIMITER ;

-- Execute the procedure (will abort with a clear error message if pre-checks fail)
CALL migrate_payments_autoinc();

-- Cleanup
DROP PROCEDURE IF EXISTS migrate_payments_autoinc();

-- OPTIONAL: If you need to assign ids to rows with id=NULL or id=0, you can run the following (only after backup):
-- SET @max = (SELECT IFNULL(MAX(id), 0) FROM payments);
-- UPDATE payments
-- SET id = (@max := @max + 1)
-- WHERE id IS NULL OR id = 0
-- ORDER BY created_at ASC;
-- Then re-run this migration.

-- If duplicates exist and you want an automated resolution, ask for assistance — duplicates are
-- dangerous to resolve automatically without understanding the business meaning.
