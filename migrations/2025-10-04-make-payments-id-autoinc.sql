-- Migration: ensure payments.id is AUTO_INCREMENT PRIMARY KEY (idempotent)
-- Safe to run multiple times

ALTER TABLE payments MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
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
