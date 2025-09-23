-- Migration: add bank details to site_settings
ALTER TABLE site_settings
  ADD COLUMN bank_name VARCHAR(255) DEFAULT NULL,
  ADD COLUMN bank_account_name VARCHAR(255) DEFAULT NULL,
  ADD COLUMN bank_account_number VARCHAR(255) DEFAULT NULL;

-- Optionally update any existing seed row with example values (uncomment and edit as needed)
-- UPDATE site_settings SET bank_name='[Bank Name]', bank_account_name='High Q Solid Academy Limited', bank_account_number='1234567890' WHERE id = 1;
