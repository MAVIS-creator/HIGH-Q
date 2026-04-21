-- Add onboarding tour tracking fields to users table.
-- Safe to run once; if columns already exist, ignore duplicate-column errors.

ALTER TABLE users
  ADD COLUMN onboarding_tour_pending TINYINT(1) NOT NULL DEFAULT 1 AFTER email_verified_at,
  ADD COLUMN onboarding_tour_started_at DATETIME NULL AFTER onboarding_tour_pending,
  ADD COLUMN onboarding_tour_completed_at DATETIME NULL AFTER onboarding_tour_started_at;
