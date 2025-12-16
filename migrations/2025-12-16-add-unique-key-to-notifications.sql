-- Add unique key for notification persistence
-- This allows INSERT...ON DUPLICATE KEY UPDATE to work properly

ALTER TABLE notifications 
ADD UNIQUE KEY unique_user_notification (user_id, type, reference_id);
