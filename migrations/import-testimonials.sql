-- Import real student testimonials from HQ Student Feature Submission CSV
-- Run: mysql -u root highq < migrations/import-testimonials.sql

-- Step 1: Clear all existing sample testimonials
DELETE FROM testimonials;

-- Step 2: Reset auto-increment
ALTER TABLE testimonials AUTO_INCREMENT = 1;

-- Step 3: Insert real student testimonials from CSV
INSERT INTO testimonials (name, role_institution, testimonial_text, outcome_badge, display_order, is_active) VALUES
('ADEDUNYE KINGSLEY OLUWAPELUMI', 'Ambrose Ali University', 'I choose HQ because of the passion and zeal toward the success of every student', 'JAMB: 242', 1, 1),
('Ayodele Joseph Teminijesu', 'Lasutech', 'Top-notch lessons! HQ is very patient and knows how to simplify even the toughest topics. The environment is conducive to learning and the focus on JAMB past questions was incredibly helpful. 10/10 recommended!', 'JAMB Success', 2, 1),
('Fadele Oluwanifemi Abigail', 'Ladoke Akintola University Of Technology', 'At first, it was the only tutorial I''ve been hearing people talking about. It has been said that the academy is really a high quality.', 'JAMB: 235', 3, 1),
('Robinson Delight', 'Current Student', 'HQ tutorial is the best and they teach well', 'JAMB: 167', 4, 1),
('Adeyemi Wahab Ayoade', 'Adekunle Ajasin University Akungba', 'I chose this tutorial because it is well-structured, engaging, and provides clear explanations that improve my understanding of the subject.', 'JAMB Success', 5, 1),
('Ogunsanya Zainab Olayinka', 'Olabisi Onabanjo University', 'It stands out from other tutorials.', 'JAMB: 218', 6, 1);

-- Verification: Display imported records
SELECT id, name, outcome_badge, is_active FROM testimonials ORDER BY display_order;
