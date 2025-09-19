-- Migration: add columns to payments to support gateways, receipts and metadata
ALTER TABLE `payments`
  ADD COLUMN `gateway` VARCHAR(50) DEFAULT NULL,
  ADD COLUMN `receipt_path` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `metadata` JSON DEFAULT NULL,
  ADD COLUMN `confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP;

-- Add unique index for reference to avoid duplicates
ALTER TABLE `payments` ADD UNIQUE KEY `uniq_reference` (`reference`(191));
