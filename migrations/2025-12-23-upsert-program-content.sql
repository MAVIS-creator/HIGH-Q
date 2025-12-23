-- Migration: Upsert program content for public pages
-- Date: 2025-12-23
-- Ensures all referenced program slugs exist with meaningful content

INSERT INTO `courses` (`title`, `slug`, `description`, `duration`, `price`, `is_active`, `icon`, `highlight_badge`, `created_by`)
VALUES
  ('JAMB Preparation', 'jamb-preparation', 'Comprehensive JAMB prep with CBT drills, mock exams, and weekly performance reviews.', '4-6 months', 50000.00, 1, 'bx bx-target-lock', '305+ JAMB Scores', 1),
  ('WAEC Preparation', 'waec-preparation', 'Complete WASSCE prep across core subjects with practicals, labs, and examiner-style marking.', '6-12 months', 80000.00, 1, 'bx bx-book', '98% Pass Rate', 1),
  ('NECO Preparation', 'neco-preparation', 'NECO-focused tutoring, mock exams, and curated study materials for top performance.', '6-12 months', 75000.00, 1, 'bx bx-book-open', 'Proven Success', 1),
  ('Post-UTME', 'post-utme', 'University-specific Post-UTME training with practice tests and interview readiness.', '2-4 months', 30000.00, 1, 'bx bx-award', 'University Ready', 1),
  ('Special Tutorials', 'special-tutorials', 'Intensive one-on-one and small group tutorials tailored to individual goals.', 'Flexible', NULL, 1, 'bx bx-star', 'Personalized Learning', 1),
  ('Computer Training', 'computer-training', 'Digital literacy, MS Office, internet skills, and coding fundamentals for beginners.', '3-6 months', 45000.00, 1, 'bx bx-laptop', 'Digital Skills', 1),
  ('CBT Training', 'cbt', 'Hands-on CBT simulations to build speed, accuracy, and confidence for computer-based exams.', '2-4 weeks', 15000.00, 1, 'bx bx-desktop', 'Real CBT Practice', 1),
  ('Digital Skills', 'digital-skills', 'Practical digital skills: productivity, collaboration tools, online research, and safety.', '6-10 weeks', 0.00, 1, 'bx bx-cloud', 'Future-Proof Skills', 1),
  ('Professional Services', 'professional', 'Consulting, documentation support, and career guidance for students and professionals.', 'As needed', NULL, 1, 'bx bx-briefcase', 'On-Demand Help', 1),
  ('Tutorial Classes', 'tutorial-classes', 'Structured tutorial sessions covering core subjects with continuous assessment.', '3-9 months', 0.00, 1, 'bx bx-book-reader', 'Core Mastery', 1),
  ('JAMB/Other Enquires on JAMB', 'jamb-post-utme', 'Comprehensive guidance on JAMB and Post-UTME requirements, registration, and prep.', '4-6 months', 10000.00, 1, 'bx bxs-bar-chart-alt-2', 'Expert Guidance', 1)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `duration` = VALUES(`duration`),
  `price` = VALUES(`price`),
  `icon` = VALUES(`icon`),
  `highlight_badge` = VALUES(`highlight_badge`),
  `is_active` = VALUES(`is_active`),
  `updated_at` = CURRENT_TIMESTAMP;
