-- Migration: add activated_at to payments for recording payment link activation
ALTER TABLE `payments`
  ADD COLUMN `activated_at` datetime DEFAULT NULL AFTER `created_at`;

-- Note: nullable datetime. The application will set this when the user opens the payment link.
