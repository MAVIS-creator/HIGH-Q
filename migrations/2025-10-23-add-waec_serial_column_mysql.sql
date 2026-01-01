-- Migration: add waec_serial column

ALTER TABLE post_utme_registrations ADD COLUMN IF NOT EXISTS waec_serial VARCHAR(100);
