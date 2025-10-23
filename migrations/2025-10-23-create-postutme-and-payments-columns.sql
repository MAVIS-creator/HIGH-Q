-- Migration: create post_utme_registrations table and safely add payments columns
-- Run this against your MySQL database as a privileged user or via your migration runner.

-- Create table if not exists (MySQL)
CREATE TABLE IF NOT EXISTS post_utme_registrations (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'pending',

  institution VARCHAR(255) DEFAULT NULL,
  first_name VARCHAR(100) DEFAULT NULL,
  surname VARCHAR(100) DEFAULT NULL,
  other_name VARCHAR(100) DEFAULT NULL,
  gender VARCHAR(10) DEFAULT NULL,
  address TEXT,
  parent_phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  nin_number VARCHAR(50) DEFAULT NULL,
  state_of_origin VARCHAR(100) DEFAULT NULL,
  local_government VARCHAR(100) DEFAULT NULL,
  place_of_birth VARCHAR(255) DEFAULT NULL,
  marital_status VARCHAR(50) DEFAULT NULL,
  disability TEXT,
  nationality VARCHAR(100) DEFAULT NULL,
  religion VARCHAR(100) DEFAULT NULL,
  mode_of_entry VARCHAR(100) DEFAULT NULL,

  jamb_registration_number VARCHAR(50) DEFAULT NULL,
  jamb_score INT DEFAULT NULL,
  jamb_subjects JSON DEFAULT NULL,

  course_first_choice VARCHAR(255) DEFAULT NULL,
  course_second_choice VARCHAR(255) DEFAULT NULL,
  institution_first_choice VARCHAR(255) DEFAULT NULL,

  father_name VARCHAR(255) DEFAULT NULL,
  father_phone VARCHAR(20) DEFAULT NULL,
  father_email VARCHAR(255) DEFAULT NULL,
  father_occupation VARCHAR(255) DEFAULT NULL,
  mother_name VARCHAR(255) DEFAULT NULL,
  mother_phone VARCHAR(20) DEFAULT NULL,
  mother_occupation VARCHAR(255) DEFAULT NULL,

  primary_school VARCHAR(255) DEFAULT NULL,
  primary_year_ended YEAR DEFAULT NULL,
  secondary_school VARCHAR(255) DEFAULT NULL,
  secondary_year_ended YEAR DEFAULT NULL,

  exam_type ENUM('WAEC','NECO','GCE') DEFAULT NULL,
  candidate_name VARCHAR(255) DEFAULT NULL,
  exam_number VARCHAR(50) DEFAULT NULL,
  exam_year_month VARCHAR(20) DEFAULT NULL,
  olevel_results JSON DEFAULT NULL,

  passport_photo VARCHAR(255) DEFAULT NULL,
  payment_status VARCHAR(10) DEFAULT 'pending',
  form_fee_paid TINYINT(1) DEFAULT 0,
  tutor_fee_paid TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_postutme_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Safely add payments columns if they don't exist
-- Add form_fee_paid
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'form_fee_paid');
SET @s = IF(@exists = 0, 'ALTER TABLE `payments` ADD COLUMN `form_fee_paid` tinyint(1) DEFAULT 0;', 'SELECT "column form_fee_paid already exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add tutor_fee_paid
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'tutor_fee_paid');
SET @s = IF(@exists = 0, 'ALTER TABLE `payments` ADD COLUMN `tutor_fee_paid` tinyint(1) DEFAULT 0;', 'SELECT "column tutor_fee_paid already exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add registration_type
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'registration_type');
SET @s = IF(@exists = 0, 'ALTER TABLE `payments` ADD COLUMN `registration_type` varchar(20) DEFAULT ''regular'';', 'SELECT "column registration_type already exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- NOTE: run this file using your MySQL client connected to the correct database user with ALTER privileges.
