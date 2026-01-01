-- Migration: add payments columns for post-UTME registrations

ALTER TABLE payments 
  ADD COLUMN IF NOT EXISTS form_fee_paid TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS tutor_fee_paid TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS registration_type VARCHAR(20) DEFAULT 'regular';

    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_postutme_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  -- Optional: Populate or migrate existing data as needed after running migration.
