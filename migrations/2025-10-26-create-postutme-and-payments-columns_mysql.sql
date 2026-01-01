-- Migration: add payments columns for post-UTME registrations

ALTER TABLE payments 
  ADD COLUMN IF NOT EXISTS form_fee_paid TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS tutor_fee_paid TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS registration_type VARCHAR(20) DEFAULT 'regular';
