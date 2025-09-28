-- Add maintenance_allowed_ips column to site_settings (comma-separated list)
ALTER TABLE `site_settings`
  ADD COLUMN IF NOT EXISTS `maintenance_allowed_ips` VARCHAR(1024) DEFAULT NULL AFTER `maintenance`;
