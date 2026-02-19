-- Upsert core program slugs and features
-- Run with: mysql -u root -p highq < migrations/2025-12-23-upsert-program-slugs.sql

-- Backward compatibility: older installs use sort_order instead of position
SET @has_position := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'course_features'
    AND COLUMN_NAME = 'position'
);
SET @sql_add_position := IF(@has_position = 0,
  'ALTER TABLE course_features ADD COLUMN `position` INT NOT NULL DEFAULT 0 AFTER feature_text',
  'SELECT 1'
);
PREPARE stmt_add_position FROM @sql_add_position;
EXECUTE stmt_add_position;
DEALLOCATE PREPARE stmt_add_position;

-- JAMB Preparation
UPDATE courses
SET title='JAMB Preparation', slug='jamb-preparation', description='Comprehensive preparation for JAMB with targeted tutoring and CBT mock tests.', duration='4-6 months', price=NULL, is_active=1, icon='bx bx-target-lock', highlight_badge='Top JAMB Scores'
WHERE slug='jamb-preparation';
INSERT INTO courses (title, slug, description, duration, price, tutor_id, created_by, is_active, icon, highlight_badge)
SELECT 'JAMB Preparation', 'jamb-preparation', 'Comprehensive preparation for JAMB with targeted tutoring and CBT mock tests.', '4-6 months', NULL, NULL, 1, 1, 'bx bx-target-lock', 'Top JAMB Scores'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug='jamb-preparation');
SET @cid := (SELECT id FROM courses WHERE slug='jamb-preparation' LIMIT 1);
DELETE FROM course_features WHERE course_id=@cid;
INSERT INTO course_features (course_id, feature_text, position)
SELECT @cid, feat, pos FROM (
  SELECT 'Mock CBT drills' AS feat, 0 AS pos UNION ALL
  SELECT 'Exam-focused curriculum', 1 UNION ALL
  SELECT 'Score tracking & analytics', 2 UNION ALL
  SELECT 'One-on-one tutor support', 3
) AS f WHERE @cid IS NOT NULL;

-- WAEC Preparation
UPDATE courses
SET title='WAEC Preparation', slug='waec-preparation', description='Complete preparation for WAEC covering core subjects, practicals, and past questions.', duration='6-12 months', price=NULL, is_active=1, icon='bx bx-book', highlight_badge='Core Subjects + Practicals'
WHERE slug='waec-preparation';
INSERT INTO courses (title, slug, description, duration, price, tutor_id, created_by, is_active, icon, highlight_badge)
SELECT 'WAEC Preparation', 'waec-preparation', 'Complete preparation for WAEC covering core subjects, practicals, and past questions.', '6-12 months', NULL, NULL, 1, 1, 'bx bx-book', 'Core Subjects + Practicals'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug='waec-preparation');
SET @cid := (SELECT id FROM courses WHERE slug='waec-preparation' LIMIT 1);
DELETE FROM course_features WHERE course_id=@cid;
INSERT INTO course_features (course_id, feature_text, position)
SELECT @cid, feat, pos FROM (
  SELECT 'Core + elective subjects' AS feat, 0 AS pos UNION ALL
  SELECT 'Practicals and labs', 1 UNION ALL
  SELECT 'Past questions & marking guides', 2 UNION ALL
  SELECT 'Weekly progress reviews', 3
) AS f WHERE @cid IS NOT NULL;

-- NECO Preparation
UPDATE courses
SET title='NECO Preparation', slug='neco-preparation', description='National Examination Council preparation with experienced tutors and structured mock exams.', duration='6-12 months', price=NULL, is_active=1, icon='bx bx-book-open', highlight_badge='NECO Excellence'
WHERE slug='neco-preparation';
INSERT INTO courses (title, slug, description, duration, price, tutor_id, created_by, is_active, icon, highlight_badge)
SELECT 'NECO Preparation', 'neco-preparation', 'National Examination Council preparation with experienced tutors and structured mock exams.', '6-12 months', NULL, NULL, 1, 1, 'bx bx-book-open', 'NECO Excellence'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug='neco-preparation');
SET @cid := (SELECT id FROM courses WHERE slug='neco-preparation' LIMIT 1);
DELETE FROM course_features WHERE course_id=@cid;
INSERT INTO course_features (course_id, feature_text, position)
SELECT @cid, feat, pos FROM (
  SELECT 'Comprehensive subject coverage' AS feat, 0 AS pos UNION ALL
  SELECT 'Timed practice sessions', 1 UNION ALL
  SELECT 'Detailed feedback & corrections', 2 UNION ALL
  SELECT 'Exam strategy workshops', 3
) AS f WHERE @cid IS NOT NULL;

-- Post-UTME
UPDATE courses
SET title='Post-UTME', slug='post-utme', description='University-specific entrance examination prep with practice tests and interview guidance.', duration='2-4 months', price=NULL, is_active=1, icon='bx bx-award', highlight_badge='University Focused'
WHERE slug='post-utme';
INSERT INTO courses (title, slug, description, duration, price, tutor_id, created_by, is_active, icon, highlight_badge)
SELECT 'Post-UTME', 'post-utme', 'University-specific entrance examination prep with practice tests and interview guidance.', '2-4 months', NULL, NULL, 1, 1, 'bx bx-award', 'University Focused'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug='post-utme');
SET @cid := (SELECT id FROM courses WHERE slug='post-utme' LIMIT 1);
DELETE FROM course_features WHERE course_id=@cid;
INSERT INTO course_features (course_id, feature_text, position)
SELECT @cid, feat, pos FROM (
  SELECT 'Campus-specific practice tests' AS feat, 0 AS pos UNION ALL
  SELECT 'Interview prep & coaching', 1 UNION ALL
  SELECT 'Speed & accuracy drills', 2 UNION ALL
  SELECT 'Result-driven study plans', 3
) AS f WHERE @cid IS NOT NULL;

-- Special Tutorials
UPDATE courses
SET title='Special Tutorials', slug='special-tutorials', description='Intensive one-on-one and small group tutorial sessions tailored to individual needs.', duration='Flexible', price=NULL, is_active=1, icon='bx bx-star', highlight_badge='Personalized Mentorship'
WHERE slug='special-tutorials';
INSERT INTO courses (title, slug, description, duration, price, tutor_id, created_by, is_active, icon, highlight_badge)
SELECT 'Special Tutorials', 'special-tutorials', 'Intensive one-on-one and small group tutorial sessions tailored to individual needs.', 'Flexible', NULL, NULL, 1, 1, 'bx bx-star', 'Personalized Mentorship'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug='special-tutorials');
SET @cid := (SELECT id FROM courses WHERE slug='special-tutorials' LIMIT 1);
DELETE FROM course_features WHERE course_id=@cid;
INSERT INTO course_features (course_id, feature_text, position)
SELECT @cid, feat, pos FROM (
  SELECT 'One-on-one coaching' AS feat, 0 AS pos UNION ALL
  SELECT 'Custom study schedules', 1 UNION ALL
  SELECT 'Remedial + advanced tracks', 2 UNION ALL
  SELECT 'Performance monitoring', 3
) AS f WHERE @cid IS NOT NULL;

-- Computer Training
UPDATE courses
SET title='Computer Training', slug='computer-training', description='Modern computer skills and digital literacy training: MS Office, internet skills, and programming basics.', duration='3-6 months', price=NULL, is_active=1, icon='bx bx-laptop', highlight_badge='Digital Skills'
WHERE slug='computer-training';
INSERT INTO courses (title, slug, description, duration, price, tutor_id, created_by, is_active, icon, highlight_badge)
SELECT 'Computer Training', 'computer-training', 'Modern computer skills and digital literacy training: MS Office, internet skills, and programming basics.', '3-6 months', NULL, NULL, 1, 1, 'bx bx-laptop', 'Digital Skills'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug='computer-training');
SET @cid := (SELECT id FROM courses WHERE slug='computer-training' LIMIT 1);
DELETE FROM course_features WHERE course_id=@cid;
INSERT INTO course_features (course_id, feature_text, position)
SELECT @cid, feat, pos FROM (
  SELECT 'MS Office mastery' AS feat, 0 AS pos UNION ALL
  SELECT 'Internet & research skills', 1 UNION ALL
  SELECT 'Intro to programming', 2 UNION ALL
  SELECT 'Practical projects', 3
) AS f WHERE @cid IS NOT NULL;

-- Preserve existing legacy rows but ensure slugs remain available in DB for public/front-end
