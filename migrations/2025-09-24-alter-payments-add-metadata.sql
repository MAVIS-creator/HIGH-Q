-- Migration: add metadata and payer fields to payments
ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `metadata` longtext DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payer_account_name` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payer_account_number` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payer_bank_name` varchar(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `receipt_path` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `confirmed_at` datetime DEFAULT NULL;

-- Note: some MySQL versions don't support IF NOT EXISTS in ALTER COLUMN; run carefully in your environment.
