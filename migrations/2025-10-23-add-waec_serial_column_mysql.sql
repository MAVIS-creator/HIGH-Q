-- Migration: add waec_serial column and copy data from waec_serial_no if present
-- Idempotent and safe to run multiple times

ALTER TABLE `post_utme_registrations`
  ADD COLUMN IF NOT EXISTS `waec_serial` VARCHAR(100) DEFAULT NULL;

-- Copy values only if source column exists and target is empty
UPDATE post_utme_registrations SET waec_serial = waec_serial_no 
WHERE waec_serial IS NULL 
  AND waec_serial_no IS NOT NULL 
  AND waec_serial_no <> '';
