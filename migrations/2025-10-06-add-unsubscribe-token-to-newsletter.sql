-- Migration: add unsubscribe_token and token_created_at to newsletter_subscribers
ALTER TABLE newsletter_subscribers
ADD COLUMN unsubscribe_token VARCHAR(128) DEFAULT NULL,
ADD COLUMN token_created_at DATETIME DEFAULT NULL;

-- Note: after running this migration, existing subscribers will not have tokens until they re-subscribe