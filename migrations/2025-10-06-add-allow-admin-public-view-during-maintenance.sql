-- Migration: Add allow_admin_public_view_during_maintenance to site_settings
ALTER TABLE site_settings
ADD COLUMN allow_admin_public_view_during_maintenance TINYINT(1) NOT NULL DEFAULT 0 AFTER maintenance_allowed_ips;