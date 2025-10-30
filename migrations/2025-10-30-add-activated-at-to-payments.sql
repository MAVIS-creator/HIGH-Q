-- Migration: add activated_at to payments
ALTER TABLE payments
  ADD COLUMN activated_at DATETIME NULL AFTER created_at;
