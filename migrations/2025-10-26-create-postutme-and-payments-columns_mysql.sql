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
  -- Migration: add payments columns for post-UTME and create post_utme_registrations table (MySQL)
  -- Run with: mysql -u user -p database < 2025-10-26-create-postutme-and-payments-columns_mysql.sql

  -- This migration is written to be safe to run multiple times.
  -- It will add missing columns to `payments` only when they do not already exist,
  -- and it will create the `post_utme_registrations` table if absent.

  DELIMITER $$
  DROP PROCEDURE IF EXISTS add_payments_columns$$
  CREATE PROCEDURE add_payments_columns()
  BEGIN
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'form_fee_paid'
    ) THEN
      ALTER TABLE `payments` ADD COLUMN `form_fee_paid` TINYINT(1) DEFAULT 0;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'tutor_fee_paid'
    ) THEN
      ALTER TABLE `payments` ADD COLUMN `tutor_fee_paid` TINYINT(1) DEFAULT 0;
    END IF;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'registration_type'
    ) THEN
      ALTER TABLE `payments` ADD COLUMN `registration_type` VARCHAR(20) DEFAULT 'regular';
    END IF;
  END$$
  CALL add_payments_columns()$$
  DROP PROCEDURE IF EXISTS add_payments_columns$$
  DELIMITER ;

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
    `parent_phone` VARCHAR(50) DEFAULT NULL,
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
    `jamb_subjects_text` TEXT DEFAULT NULL,

    -- Course Preferences
    `course_first_choice` VARCHAR(255) DEFAULT NULL,
    `course_second_choice` VARCHAR(255) DEFAULT NULL,
    `institution_first_choice` VARCHAR(255) DEFAULT NULL,

    -- Parent Details
    `father_name` VARCHAR(255) DEFAULT NULL,
    `father_phone` VARCHAR(50) DEFAULT NULL,
    `father_email` VARCHAR(255) DEFAULT NULL,
    `father_occupation` VARCHAR(255) DEFAULT NULL,
    `mother_name` VARCHAR(255) DEFAULT NULL,
    `mother_phone` VARCHAR(50) DEFAULT NULL,
    `mother_occupation` VARCHAR(255) DEFAULT NULL,

    -- Sponsor & Next of kin
    `sponsor_name` VARCHAR(255) DEFAULT NULL,
    `sponsor_address` VARCHAR(500) DEFAULT NULL,
    `sponsor_email` VARCHAR(255) DEFAULT NULL,
    `sponsor_phone` VARCHAR(50) DEFAULT NULL,
    `sponsor_relationship` VARCHAR(100) DEFAULT NULL,
    `next_of_kin_name` VARCHAR(255) DEFAULT NULL,
    `next_of_kin_address` VARCHAR(500) DEFAULT NULL,
    `next_of_kin_email` VARCHAR(255) DEFAULT NULL,
    `next_of_kin_phone` VARCHAR(50) DEFAULT NULL,
    `next_of_kin_relationship` VARCHAR(100) DEFAULT NULL,

    -- Education History
    `primary_school` VARCHAR(255) DEFAULT NULL,
    `primary_year_ended` SMALLINT DEFAULT NULL,
    `secondary_school` VARCHAR(255) DEFAULT NULL,
    `secondary_year_ended` SMALLINT DEFAULT NULL,

    -- O'Level Details
    `exam_type` VARCHAR(20) DEFAULT NULL,
    `candidate_name` VARCHAR(255) DEFAULT NULL,
    `exam_number` VARCHAR(50) DEFAULT NULL,
    `exam_year_month` VARCHAR(20) DEFAULT NULL,
    `olevel_results` JSON DEFAULT NULL,
    `waec_token` VARCHAR(100) DEFAULT NULL,
    `waec_serial` VARCHAR(100) DEFAULT NULL,

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
