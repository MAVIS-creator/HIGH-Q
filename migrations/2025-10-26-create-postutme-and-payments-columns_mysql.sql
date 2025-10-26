-- Migration: add payments columns for post-UTME and create post_utme_registrations table (MySQL)
-- Run with: mysql -u user -p database < 2025-10-26-create-postutme-and-payments-columns_mysql.sql

-- Add columns to payments table if they don't already exist
ALTER TABLE `payments` 
  ADD COLUMN IF NOT EXISTS `form_fee_paid` TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `tutor_fee_paid` TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `registration_type` VARCHAR(20) DEFAULT 'regular';

-- Create table for post-UTME registrations
CREATE TABLE IF NOT EXISTS `post_utme_registrations` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'pending',

  -- Personal Information
  `institution` VARCHAR(255) DEFAULT NULL,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `surname` VARCHAR(100) DEFAULT NULL,
  `other_name` VARCHAR(100) DEFAULT NULL,
  `gender` VARCHAR(10) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `parent_phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `nin_number` VARCHAR(50) DEFAULT NULL,
  `state_of_origin` VARCHAR(100) DEFAULT NULL,
  `local_government` VARCHAR(100) DEFAULT NULL,
  `place_of_birth` VARCHAR(255) DEFAULT NULL,
  `marital_status` VARCHAR(50) DEFAULT NULL,
  `disability` TEXT DEFAULT NULL,
  `nationality` VARCHAR(100) DEFAULT NULL,
  `religion` VARCHAR(100) DEFAULT NULL,
  `mode_of_entry` VARCHAR(100) DEFAULT NULL,

  -- JAMB Details
  `jamb_registration_number` VARCHAR(50) DEFAULT NULL,
  `jamb_score` INT DEFAULT NULL,
  `jamb_subjects` JSON DEFAULT NULL,

  -- Course Preferences
  `course_first_choice` VARCHAR(255) DEFAULT NULL,
  `course_second_choice` VARCHAR(255) DEFAULT NULL,
  `institution_first_choice` VARCHAR(255) DEFAULT NULL,

  -- Parent Details
  `father_name` VARCHAR(255) DEFAULT NULL,
  `father_phone` VARCHAR(20) DEFAULT NULL,
  `father_email` VARCHAR(255) DEFAULT NULL,
  `father_occupation` VARCHAR(255) DEFAULT NULL,
  `mother_name` VARCHAR(255) DEFAULT NULL,
  `mother_phone` VARCHAR(20) DEFAULT NULL,
  `mother_occupation` VARCHAR(255) DEFAULT NULL,

  -- Education History
  `primary_school` VARCHAR(255) DEFAULT NULL,
  `primary_year_ended` SMALLINT DEFAULT NULL,
  `secondary_school` VARCHAR(255) DEFAULT NULL,
  `secondary_year_ended` SMALLINT DEFAULT NULL,

  -- O'Level Details
  `exam_type` VARCHAR(10) DEFAULT NULL,
  `candidate_name` VARCHAR(255) DEFAULT NULL,
  `exam_number` VARCHAR(50) DEFAULT NULL,
  `exam_year_month` VARCHAR(20) DEFAULT NULL,
  `olevel_results` JSON DEFAULT NULL,

  -- System Fields
  `passport_photo` VARCHAR(255) DEFAULT NULL,
  `payment_status` VARCHAR(20) DEFAULT 'pending',
  `form_fee_paid` TINYINT(1) DEFAULT 0,
  `tutor_fee_paid` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_postutme_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Populate or migrate existing data as needed after running migration.

COMMIT;
