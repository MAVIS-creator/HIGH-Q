-- Add activated_at column to payments to record when a payment link was activated (user opened link)
ALTER TABLE `payments`
  ADD COLUMN `activated_at` DATETIME NULL DEFAULT NULL AFTER `created_at`;

-- Optional index to help queries that check activation window
CREATE INDEX IF NOT EXISTS `idx_payments_activated_at` ON `payments` (`activated_at`);
