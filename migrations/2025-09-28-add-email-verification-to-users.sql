-- Add email verification fields to users table
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email_verification_token` VARCHAR(128) DEFAULT NULL AFTER `avatar`,
  ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME DEFAULT NULL AFTER `email_verification_token`;

-- Optional: index for token lookup
CREATE INDEX IF NOT EXISTS `idx_users_email_verification_token` ON `users` (`email_verification_token`(64));
