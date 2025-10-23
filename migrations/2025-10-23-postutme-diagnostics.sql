-- Diagnostics: check post_utme_registrations existence and row counts
-- Run in your MySQL client to quickly diagnose why the admin students page shows no rows.

SHOW TABLES LIKE 'post_utme_registrations';
SELECT COUNT(*) AS cnt_postutme FROM post_utme_registrations;
SELECT COUNT(*) AS cnt_student_registrations FROM student_registrations;

-- Also show the columns on post_utme_registrations to ensure expected fields exist
SHOW COLUMNS FROM post_utme_registrations;

-- If tables are missing, create a minimal table (example only — use the full migration in repository if present)
-- CREATE TABLE post_utme_registrations (
--   id INT AUTO_INCREMENT PRIMARY KEY,
--   status VARCHAR(32) DEFAULT 'pending',
--   institution VARCHAR(255),
--   first_name VARCHAR(255),
--   surname VARCHAR(255),
--   other_name VARCHAR(255),
--   gender VARCHAR(16),
--   parent_phone VARCHAR(50),
--   email VARCHAR(255),
--   nin_number VARCHAR(50),
--   state_of_origin VARCHAR(128),
--   local_government VARCHAR(128),
--   jamb_registration_number VARCHAR(128),
--   jamb_score INT,
--   jamb_subjects TEXT,
--   course_first_choice VARCHAR(255),
--   course_second_choice VARCHAR(255),
--   institution_first_choice VARCHAR(255),
--   father_name VARCHAR(255),
--   father_phone VARCHAR(50),
--   mother_name VARCHAR(255),
--   mother_phone VARCHAR(50),
--   exam_type VARCHAR(32),
--   candidate_name VARCHAR(255),
--   exam_number VARCHAR(64),
--   exam_year_month VARCHAR(32),
--   olevel_results JSON,
--   passport_photo VARCHAR(512),
--   payment_status VARCHAR(32) DEFAULT 'pending',
--   created_at DATETIME DEFAULT CURRENT_TIMESTAMP
-- );

-- End diagnostics
