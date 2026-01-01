-- Migration: ensure payments.id is AUTO_INCREMENT PRIMARY KEY (idempotent)
-- Safe to run multiple times

ALTER TABLE payments MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
-- SET id = (@max := @max + 1)
-- WHERE id IS NULL OR id = 0
-- ORDER BY created_at ASC;
-- Then re-run this migration.

-- If duplicates exist and you want an automated resolution, ask for assistance — duplicates are
-- dangerous to resolve automatically without understanding the business meaning.
