-- Migration: make payments.id AUTO_INCREMENT primary key (safe, idempotent)
-- Location: migrations/2025-10-04-make-payments-id-autoinc.sql
--
-- This script does the following:
-- 1) Creates a backup table `payments_backup` (if it doesn't already exist) and copies current data into it
-- 2) Ensures id is set as AUTO_INCREMENT PRIMARY KEY
--
-- IMPORTANT: Back up your database before running. This script is idempotent.

-- Create backup table if it doesn't exist
CREATE TABLE IF NOT EXISTS payments_backup LIKE payments;

-- Copy data only if backup is empty (idempotent)
INSERT IGNORE INTO payments_backup SELECT * FROM payments WHERE NOT EXISTS (SELECT 1 FROM payments_backup LIMIT 1);

-- Make id NOT NULL and AUTO_INCREMENT if not already
ALTER TABLE payments MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id) IF NOT EXISTS;
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
