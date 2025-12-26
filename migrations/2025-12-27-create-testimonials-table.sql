-- Create testimonials table for Wall of Fame
-- Run with: mysql -u root -p highq < migrations/2025-12-27-create-testimonials-table.sql

CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role_institution VARCHAR(255) DEFAULT NULL COMMENT 'e.g., "LAUTECH Engineering Student" or "Cybersecurity Professional"',
    testimonial_text TEXT NOT NULL,
    image_path VARCHAR(500) DEFAULT NULL COMMENT 'Optional student/graduate photo',
    outcome_badge VARCHAR(100) DEFAULT NULL COMMENT 'e.g., "305 JAMB Score", "Admitted to Engineering", "Tech Job Placement"',
    display_order INT DEFAULT 0 COMMENT 'Lower numbers appear first',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_order (is_active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample testimonials matching the ones added to homepage
INSERT INTO testimonials (name, role_institution, testimonial_text, outcome_badge, display_order, is_active) VALUES
('Aisha O.', 'LAUTECH Engineering Student', 'I came to HQ Academy with doubts about my ability to succeed. The structured approach and supportive tutors helped me not just pass my exams, but gain admission to LAUTECH Engineering. The WAEC and Post-UTME preparation was comprehensive and gave me the confidence I needed.', 'Admitted to Engineering', 1, 1),
('Tunde A.', 'JAMB High Scorer', 'The JAMB + CBT Mastery program at HQ Academy completely transformed my preparation. The structured mock exams and personalized tutor feedback pushed me past the 300 mark. I scored 305 in JAMB and am now studying Medicine. Best decision I ever made.', '305 JAMB Score', 2, 1),
('Chidinma E.', 'Cybersecurity Professional', 'The Digital Skills track plus interview coaching was a game-changer. Within 10 weeks of completing the program, I landed a cybersecurity internship at a top tech company. The practical, hands-on approach prepared me for real-world challenges.', 'Tech Job Placement', 3, 1),
('Ibrahim K.', 'OAU Law Student', 'HQ Academy didn''t just prepare me for JAMB—they prepared me for university life. The study habits and critical thinking skills I developed have been invaluable. My Post-UTME score was excellent and I''m now at OAU studying Law.', 'University Admission', 4, 1),
('Blessing M.', 'WAEC Excellence', 'I struggled with Mathematics and English throughout secondary school. The tutorial classes at HQ Academy changed everything. I went from failing grades to 7 distinctions in WAEC. The tutors never gave up on me.', '7 A1s in WAEC', 5, 1),
('Samuel O.', 'Data Analyst', 'The digital skills program opened doors I never knew existed. I learned Excel, data visualization, and basic programming. Now I work as a junior data analyst and earning well. HQ Academy invested in my future.', 'Career Launch', 6, 1);

-- Grant necessary permissions (adjust username if needed)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON highq.testimonials TO 'root'@'localhost';
