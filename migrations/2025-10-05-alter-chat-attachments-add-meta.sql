-- Migration: add original_name and mime_type to chat_attachments for better download support
ALTER TABLE chat_attachments
  ADD COLUMN IF NOT EXISTS original_name VARCHAR(512) NULL,
  ADD COLUMN IF NOT EXISTS mime_type VARCHAR(255) NULL;
