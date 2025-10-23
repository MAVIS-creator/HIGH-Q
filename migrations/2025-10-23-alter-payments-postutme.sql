-- Add post-utme related columns to payments table if not exists
ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `form_fee_paid` tinyint(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `tutor_fee_paid` tinyint(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `registration_type` varchar(20) DEFAULT 'regular';

-- Note: Some MySQL versions don't support ADD COLUMN IF NOT EXISTS; run checks or execute manually if you get errors.
