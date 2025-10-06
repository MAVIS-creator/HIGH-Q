-- Migration: add new column to site_settings
ALTER TABLE site_settings
  ADD COLUMN new_column_name VARCHAR(255) DEFAULT NULL;
-- Replace 'new_column_name' with your actual column name and type as needed.