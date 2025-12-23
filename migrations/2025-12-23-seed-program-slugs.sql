-- Migration: Seed Program Slugs
-- Date: 2025-12-23
-- Description: Ensure all standard programs exist in the courses table with proper slugs

INSERT INTO `courses` (`title`, `slug`, `description`, `duration`, `price`, `is_active`, `icon`, `highlight_badge`, `created_by`)
VALUES
  ('JAMB Preparation', 'jamb-preparation', 'Comprehensive preparation for Joint Admissions and Matriculation Board examinations with mock tests and targeted tutoring.', '4-6 months', 50000.00, 1, 'bx bx-target-lock', '305 - Highest JAMB Score 2025', 1),
  ('WAEC Preparation', 'waec-preparation', 'Complete preparation for West African Senior School Certificate Examination covering core subjects and practicals.', '6-12 months', 80000.00, 1, 'bx bx-book', '98% Success Rate', 1),
  ('NECO Preparation', 'neco-preparation', 'National Examination Council preparation with experienced tutors, mock exams, and curated study materials.', '6-12 months', 75000.00, 1, 'bx bx-book-open', 'Proven Excellence', 1),
  ('Post-UTME', 'post-utme', 'University-specific entrance examination preparation with practice tests and interview prep.', '2-4 months', 30000.00, 1, 'bx bx-award', 'University Ready', 1),
  ('Special Tutorials', 'special-tutorials', 'Intensive one-on-one and small group tutorial sessions tailored to individual needs.', 'Flexible', NULL, 1, 'bx bx-star', 'Personalized Learning', 1),
  ('Computer Training', 'computer-training', 'Modern computer skills and digital literacy training covering MS Office, internet skills, and programming basics.', '3-6 months', 45000.00, 1, 'bx bx-laptop', 'Digital Skills', 1)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `duration` = VALUES(`duration`),
  `price` = VALUES(`price`),
  `icon` = VALUES(`icon`),
  `highlight_badge` = VALUES(`highlight_badge`),
  `updated_at` = CURRENT_TIMESTAMP;
