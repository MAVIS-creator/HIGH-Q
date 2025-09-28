-- Add a column to track when verification emails were sent (for rate limiting)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email_verification_sent_at` DATETIME DEFAULT NULL AFTER `email_verification_token`;
