-- Migration: create student_registrations and student_programs
CREATE TABLE IF NOT EXISTS `student_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(150) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `previous_education` text DEFAULT NULL,
  `academic_goals` text DEFAULT NULL,
  `emergency_contact_name` varchar(200) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `emergency_relationship` varchar(100) DEFAULT NULL,
  `agreed_terms` tinyint(1) DEFAULT 0,
  `status` enum('pending','paid','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `student_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `registration_idx` (`registration_id`),
  KEY `course_idx` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: If you want foreign keys, add them in your environment (some installs omit FK constraints)
-- ALTER TABLE `student_registrations` ADD CONSTRAINT fk_sr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE `student_programs` ADD CONSTRAINT fk_sp_reg FOREIGN KEY (registration_id) REFERENCES student_registrations(id) ON DELETE CASCADE;
-- ALTER TABLE `student_programs` ADD CONSTRAINT fk_sp_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE;
