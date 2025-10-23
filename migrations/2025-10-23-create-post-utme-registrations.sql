CREATE TABLE IF NOT EXISTS `post_utme_registrations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `status` varchar(20) DEFAULT 'pending',
  
    -- Personal Information
    `institution` varchar(255) DEFAULT NULL,
    `first_name` varchar(100) DEFAULT NULL,
    `surname` varchar(100) DEFAULT NULL,
    `other_name` varchar(100) DEFAULT NULL,
    `gender` enum('male','female') DEFAULT NULL,
    `address` text DEFAULT NULL,
    `parent_phone` varchar(20) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `nin_number` varchar(50) DEFAULT NULL,
    `state_of_origin` varchar(100) DEFAULT NULL,
    `local_government` varchar(100) DEFAULT NULL,
    `place_of_birth` varchar(255) DEFAULT NULL,
    `marital_status` varchar(50) DEFAULT NULL,
    `disability` text DEFAULT NULL,
    `nationality` varchar(100) DEFAULT NULL,
    `religion` varchar(100) DEFAULT NULL,
    `mode_of_entry` varchar(100) DEFAULT NULL,

    -- JAMB Details
    `jamb_registration_number` varchar(50) DEFAULT NULL,
    `jamb_score` int(11) DEFAULT NULL,
    `jamb_subjects` text DEFAULT NULL,

    -- Course Preferences
    `course_first_choice` varchar(255) DEFAULT NULL,
    `course_second_choice` varchar(255) DEFAULT NULL,
    `institution_first_choice` varchar(255) DEFAULT NULL,

    -- Parent Details
    `father_name` varchar(255) DEFAULT NULL,
    `father_phone` varchar(20) DEFAULT NULL,
    `father_email` varchar(255) DEFAULT NULL,
    `father_occupation` varchar(255) DEFAULT NULL,
    `mother_name` varchar(255) DEFAULT NULL,
    `mother_phone` varchar(20) DEFAULT NULL,
    `mother_occupation` varchar(255) DEFAULT NULL,

    -- Education History
    `primary_school` varchar(255) DEFAULT NULL,
    `primary_year_ended` year DEFAULT NULL,
    `secondary_school` varchar(255) DEFAULT NULL,
    `secondary_year_ended` year DEFAULT NULL,

    -- Sponsor Details
    `sponsor_name` varchar(255) DEFAULT NULL,
    `sponsor_address` text DEFAULT NULL,
    `sponsor_email` varchar(255) DEFAULT NULL,
    `sponsor_phone` varchar(20) DEFAULT NULL,
    `sponsor_relationship` varchar(100) DEFAULT NULL,

    -- Next of Kin
    `next_of_kin_name` varchar(255) DEFAULT NULL,
    `next_of_kin_address` text DEFAULT NULL,
    `next_of_kin_email` varchar(255) DEFAULT NULL,
    `next_of_kin_phone` varchar(20) DEFAULT NULL,
    `next_of_kin_relationship` varchar(100) DEFAULT NULL,

    -- O'Level Details
    `exam_type` enum('WAEC','NECO','GCE') DEFAULT NULL,
    `candidate_name` varchar(255) DEFAULT NULL,
    `exam_number` varchar(50) DEFAULT NULL,
    `exam_year_month` varchar(20) DEFAULT NULL,
    `olevel_results` text DEFAULT NULL,

    -- Token Details
    `waec_token` varchar(100) DEFAULT NULL,
    `waec_serial_no` varchar(100) DEFAULT NULL,

    -- System Fields
    `passport_photo` varchar(255) DEFAULT NULL,
    `payment_status` enum('pending','partial','completed') DEFAULT 'pending',
    `form_fee_paid` tinyint(1) DEFAULT 0,
    `tutor_fee_paid` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id_idx` (`user_id`),
    KEY `jamb_score_idx` (`jamb_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
