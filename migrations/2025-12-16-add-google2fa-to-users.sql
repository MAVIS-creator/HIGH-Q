-- Add Google 2FA columns to users table
-- Migration: 2025-12-16-add-google2fa-to-users.sql

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS google2fa_secret VARCHAR(32) DEFAULT NULL COMMENT 'Google Authenticator secret key',
ADD COLUMN IF NOT EXISTS google2fa_enabled BOOLEAN DEFAULT FALSE COMMENT 'Whether Google 2FA is enabled';

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_users_google2fa ON users(google2fa_enabled);
