-- HIGH-Q Exam Portal foundational schema
-- Week 1 deliverable: isolated exam_ prefixed tables only
-- Safe to review independently from the main HIGH-Q site schema.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS exam_students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_students_email (email),
  KEY idx_exam_students_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_student_profiles (
  student_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  full_name VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  class_level VARCHAR(100) NULL,
  school_name VARCHAR(190) NULL,
  avatar_path VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_exam_student_profiles_student
    FOREIGN KEY (student_id) REFERENCES exam_students(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_admins (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(190) NULL,
  role ENUM('super_admin','manager','editor','reviewer') NOT NULL DEFAULT 'manager',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_admins_username (username),
  KEY idx_exam_admins_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_subjects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_subjects_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_definitions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  slug VARCHAR(120) NOT NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  duration_minutes INT UNSIGNED NOT NULL,
  rules_json JSON NULL,
  difficulty ENUM('beginner','intermediate','advanced','mixed') NOT NULL DEFAULT 'mixed',
  access_type ENUM('free','subscription','premium') NOT NULL DEFAULT 'free',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_definitions_slug (slug),
  KEY idx_exam_definitions_category (category_id),
  CONSTRAINT fk_exam_definitions_category
    FOREIGN KEY (category_id) REFERENCES exam_categories(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_definition_subjects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  question_count INT UNSIGNED NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_definition_subjects_exam_subject (exam_id, subject_id),
  CONSTRAINT fk_exam_definition_subjects_exam
    FOREIGN KEY (exam_id) REFERENCES exam_definitions(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_definition_subjects_subject
    FOREIGN KEY (subject_id) REFERENCES exam_subjects(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_questions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_id BIGINT UNSIGNED NOT NULL,
  question_text LONGTEXT NOT NULL,
  explanation LONGTEXT NULL,
  difficulty ENUM('beginner','intermediate','advanced','mixed') NOT NULL DEFAULT 'mixed',
  source_label VARCHAR(190) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_exam_questions_subject (subject_id),
  KEY idx_exam_questions_difficulty (difficulty),
  CONSTRAINT fk_exam_questions_subject
    FOREIGN KEY (subject_id) REFERENCES exam_subjects(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_exam_questions_created_by
    FOREIGN KEY (created_by) REFERENCES exam_admins(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_question_options (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id BIGINT UNSIGNED NOT NULL,
  option_label CHAR(1) NOT NULL,
  option_text TEXT NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_question_options_question_label (question_id, option_label),
  KEY idx_exam_question_options_question (question_id),
  CONSTRAINT fk_exam_question_options_question
    FOREIGN KEY (question_id) REFERENCES exam_questions(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  exam_id BIGINT UNSIGNED NOT NULL,
  status ENUM('pending','in_progress','submitted','expired','cancelled') NOT NULL DEFAULT 'pending',
  mode ENUM('practice','mock','timed') NOT NULL DEFAULT 'practice',
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  expires_at DATETIME NULL,
  score_cached DECIMAL(8,2) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_exam_attempts_student (student_id),
  KEY idx_exam_attempts_exam (exam_id),
  KEY idx_exam_attempts_status (status),
  CONSTRAINT fk_exam_attempts_student
    FOREIGN KEY (student_id) REFERENCES exam_students(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_attempts_exam
    FOREIGN KEY (exam_id) REFERENCES exam_definitions(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_attempt_subjects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_attempt_subjects_attempt_subject (attempt_id, subject_id),
  CONSTRAINT fk_exam_attempt_subjects_attempt
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_attempt_subjects_subject
    FOREIGN KEY (subject_id) REFERENCES exam_subjects(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_attempt_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  selected_option_id BIGINT UNSIGNED NULL,
  answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_attempt_answers_attempt_question (attempt_id, question_id),
  CONSTRAINT fk_exam_attempt_answers_attempt
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_attempt_answers_question
    FOREIGN KEY (question_id) REFERENCES exam_questions(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_attempt_answers_option
    FOREIGN KEY (selected_option_id) REFERENCES exam_question_options(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_attempt_flags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_attempt_flags_attempt_question (attempt_id, question_id),
  CONSTRAINT fk_exam_attempt_flags_attempt
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_attempt_flags_question
    FOREIGN KEY (question_id) REFERENCES exam_questions(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_key VARCHAR(190) NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_sessions_session_key (session_key),
  KEY idx_exam_sessions_student (student_id),
  CONSTRAINT fk_exam_sessions_student
    FOREIGN KEY (student_id) REFERENCES exam_students(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_results (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id BIGINT UNSIGNED NOT NULL,
  total_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  total_questions INT UNSIGNED NOT NULL DEFAULT 0,
  correct_answers INT UNSIGNED NOT NULL DEFAULT 0,
  wrong_answers INT UNSIGNED NOT NULL DEFAULT 0,
  percentile DECIMAL(6,2) NULL,
  status ENUM('passed','failed','pending') NOT NULL DEFAULT 'pending',
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_results_attempt (attempt_id),
  CONSTRAINT fk_exam_results_attempt
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_result_subject_breakdowns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  result_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  total_questions INT UNSIGNED NOT NULL DEFAULT 0,
  correct_answers INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uq_exam_result_subject_breakdowns_result_subject (result_id, subject_id),
  CONSTRAINT fk_exam_result_subject_breakdowns_result
    FOREIGN KEY (result_id) REFERENCES exam_results(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_result_subject_breakdowns_subject
    FOREIGN KEY (subject_id) REFERENCES exam_subjects(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_leaderboard_cache (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NULL,
  exam_id BIGINT UNSIGNED NULL,
  period_key VARCHAR(50) NOT NULL,
  rank_position INT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  payload_json JSON NULL,
  cached_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_exam_leaderboard_cache_lookup (period_key, category_id, exam_id),
  CONSTRAINT fk_exam_leaderboard_cache_student
    FOREIGN KEY (student_id) REFERENCES exam_students(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_leaderboard_cache_category
    FOREIGN KEY (category_id) REFERENCES exam_categories(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_exam_leaderboard_cache_exam
    FOREIGN KEY (exam_id) REFERENCES exam_definitions(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_subscription_plans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  duration_days INT UNSIGNED NOT NULL DEFAULT 30,
  benefits_json JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_subscription_plans_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_student_subscriptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  status ENUM('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
  starts_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_exam_student_subscriptions_student (student_id),
  KEY idx_exam_student_subscriptions_status (status),
  CONSTRAINT fk_exam_student_subscriptions_student
    FOREIGN KEY (student_id) REFERENCES exam_students(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_student_subscriptions_plan
    FOREIGN KEY (plan_id) REFERENCES exam_subscription_plans(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  subscription_id BIGINT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'NGN',
  provider VARCHAR(50) NULL,
  reference VARCHAR(120) NULL,
  status ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  metadata_json JSON NULL,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_payments_reference (reference),
  KEY idx_exam_payments_student (student_id),
  CONSTRAINT fk_exam_payments_student
    FOREIGN KEY (student_id) REFERENCES exam_students(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_exam_payments_subscription
    FOREIGN KEY (subscription_id) REFERENCES exam_student_subscriptions(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_type ENUM('student','admin','system') NOT NULL,
  actor_id BIGINT UNSIGNED NULL,
  action VARCHAR(150) NOT NULL,
  context VARCHAR(120) NULL,
  meta_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_exam_activity_logs_actor (actor_type, actor_id),
  KEY idx_exam_activity_logs_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  setting_value LONGTEXT NULL,
  value_type ENUM('string','number','boolean','json') NOT NULL DEFAULT 'string',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO exam_categories (slug, name, description, sort_order)
VALUES
  ('jamb', 'JAMB', 'Joint Admissions and Matriculation Board practice exams.', 1),
  ('waec', 'WAEC', 'West African Examinations Council preparation exams.', 2),
  ('neco', 'NECO', 'National Examinations Council preparation exams.', 3),
  ('gce', 'GCE', 'General Certificate of Education practice exams.', 4),
  ('putme', 'Post-UTME', 'University screening and post-UTME simulations.', 5)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  sort_order = VALUES(sort_order);

INSERT INTO exam_subjects (slug, name, description)
VALUES
  ('english', 'English Language', 'Core English language questions for exam prep.'),
  ('mathematics', 'Mathematics', 'General mathematics and quantitative reasoning.'),
  ('biology', 'Biology', 'Biology objective question bank.'),
  ('chemistry', 'Chemistry', 'Chemistry objective question bank.'),
  ('physics', 'Physics', 'Physics objective question bank.'),
  ('economics', 'Economics', 'Economics theory and objective support.'),
  ('government', 'Government', 'Government and civic studies.'),
  ('literature', 'Literature in English', 'Literature comprehension and analysis.')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description);

INSERT INTO exam_subscription_plans (slug, name, description, price, duration_days, benefits_json)
VALUES
  ('free-trial', 'Free Trial', 'Starter access for onboarding and limited practice.', 0.00, 7, JSON_ARRAY('Limited access exams', 'Basic leaderboard visibility')),
  ('standard', 'Standard', 'Full access to standard practice materials and history.', 5000.00, 30, JSON_ARRAY('Full practice access', 'Results history', 'Leaderboard participation')),
  ('premium', 'Premium', 'Expanded access for serious prep and premium analytics.', 12000.00, 90, JSON_ARRAY('Everything in Standard', 'Extended subscription', 'Priority premium exam access'))
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  price = VALUES(price),
  duration_days = VALUES(duration_days),
  benefits_json = VALUES(benefits_json);

INSERT INTO exam_settings (setting_key, setting_value, value_type)
VALUES
  ('session_timeout_minutes', '120', 'number'),
  ('allow_guest_browse', 'true', 'boolean'),
  ('leaderboard_enabled', 'true', 'boolean'),
  ('default_exam_mode', 'practice', 'string')
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  value_type = VALUES(value_type);

SET FOREIGN_KEY_CHECKS = 1;

-- Intentionally no default seeded exam_admins account here.
-- Week 2/3 can create a bootstrap admin via a controlled script once auth flows are finalized.
