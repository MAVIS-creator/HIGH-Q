-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 26, 2025 at 01:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `highq`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `ip`, `user_agent`, `meta`, `created_at`) VALUES
(1, 1, 'role_updated', NULL, NULL, '{\"role_id\":2}', '2025-09-17 21:29:38'),
(2, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":1}', '2025-09-24 16:55:53'),
(3, 1, 'course_created', NULL, NULL, '{\"slug\":\"jamb-post-utme\"}', '2025-09-24 22:38:18'),
(4, 1, 'course_deleted', NULL, NULL, '{\"course_id\":1}', '2025-09-24 22:52:18'),
(5, 1, 'course_deleted', NULL, NULL, '{\"course_id\":1}', '2025-09-24 22:52:25'),
(6, 1, 'course_created', NULL, NULL, '{\"slug\":\"jamb-post-utme\"}', '2025-09-24 22:58:49'),
(7, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":2}', '2025-09-24 23:03:59'),
(8, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-09-25 12:04:50'),
(9, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-09-25 12:04:52'),
(10, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-09-25 12:04:54'),
(11, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-09-25 12:04:55'),
(12, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-09-25 12:04:56'),
(13, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-09-25 15:26:07'),
(14, 1, 'chat_reply', NULL, NULL, '{\"thread_id\":2,\"message_preview\":\"wewedf\"}', '2025-09-25 16:28:59'),
(15, 1, 'chat_closed', NULL, NULL, '{\"thread_id\":2}', '2025-09-25 16:36:25'),
(16, 1, 'chat_closed', NULL, NULL, '{\"thread_id\":2}', '2025-09-25 16:41:17'),
(17, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":3}', '2025-09-25 16:46:01'),
(18, 1, 'chat_reply', NULL, NULL, '{\"thread_id\":3,\"message_preview\":\"What\'s your issue please\"}', '2025-09-25 16:48:02'),
(19, 1, 'chat_reply', NULL, NULL, '{\"thread_id\":3,\"message_preview\":\"could you render clear messages please\"}', '2025-09-25 17:11:36'),
(20, 1, 'chat_closed', NULL, NULL, '{\"thread_id\":3}', '2025-09-25 17:11:55'),
(21, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"miss-omotola\"}', '2025-09-25 17:29:37'),
(22, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"miss-omotola-2\"}', '2025-09-25 17:54:13'),
(23, 1, 'tutor_deleted', NULL, NULL, '{\"tutor_id\":1}', '2025-09-25 22:34:10'),
(24, 1, 'tutor_deleted', NULL, NULL, '{\"tutor_id\":1}', '2025-09-25 22:34:19'),
(25, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":3}', '2025-09-25 22:39:40'),
(26, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":3}', '2025-09-25 22:39:41'),
(27, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":3}', '2025-09-25 22:43:31'),
(28, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":3}', '2025-09-25 22:44:02');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'General', 'general', '2025-09-18 12:44:37'),
(2, 'Announcements', 'announcements', '2025-09-18 12:44:37'),
(3, 'Tips', 'tips', '2025-09-18 12:44:37');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(150) DEFAULT NULL,
  `message` text NOT NULL,
  `is_from_staff` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `thread_id`, `sender_id`, `sender_name`, `message`, `is_from_staff`, `created_at`) VALUES
(2, 2, NULL, 'Akintunde Dolapo', 'Issuess', 0, '2025-09-24 23:03:27'),
(3, 2, 1, 'Akintunde Dolapo', 'wewedf', 1, '2025-09-25 16:28:59'),
(4, 3, NULL, 'Micheal', 'Issuess', 0, '2025-09-25 16:45:55'),
(5, 3, 1, 'Akintunde Dolapo', 'What\'s your issue please', 1, '2025-09-25 16:48:01'),
(6, 3, NULL, '', '', 0, '2025-09-25 17:10:53'),
(7, 3, NULL, '', '', 0, '2025-09-25 17:10:58'),
(8, 3, 1, 'Akintunde Dolapo', 'could you render clear messages please', 1, '2025-09-25 17:11:36');

-- --------------------------------------------------------

--
-- Table structure for table `chat_threads`
--

CREATE TABLE `chat_threads` (
  `id` int(11) NOT NULL,
  `visitor_name` varchar(150) DEFAULT NULL,
  `visitor_email` varchar(200) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `assigned_admin_id` int(11) DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_threads`
--

INSERT INTO `chat_threads` (`id`, `visitor_name`, `visitor_email`, `user_id`, `assigned_admin_id`, `status`, `created_at`, `last_activity`) VALUES
(2, 'Akintunde Dolapo', 'akintunde.dolapo1@gmail.com', NULL, 1, 'closed', '2025-09-24 23:03:27', '2025-09-25 16:41:17'),
(3, 'Micheal', 'mavisenquires@gmail.com', NULL, 1, 'closed', '2025-09-25 16:45:55', '2025-09-25 17:11:55');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `admin_reply_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved','spam','deleted') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------


--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `tutor_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `icon` varchar(255) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `highlight_badge` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `slug`, `description`, `duration`, `price`, `tutor_id`, `created_by`, `is_active`, `created_at`, `updated_at`, `icon`, `features`, `highlight_badge`) VALUES
(3, 'JAMB/Post-UTME', 'jamb-post-utme', 'Comprehensive preparation for JAMB and university entrance exams with proven success rates.', '4-6 months', 50000.00, NULL, 1, 1, '2025-09-24 22:58:48', '2025-09-25 15:26:07', 'bx bxs-bar-chart-alt-2', NULL, '305 - Our highest score in 2025');

-- --------------------------------------------------------

--
-- Table structure for table `course_features`
--

CREATE TABLE `course_features` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `feature_text` varchar(500) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `course_features`
--

INSERT INTO `course_features` (`id`, `course_id`, `feature_text`, `position`, `created_at`) VALUES
(6, 3, 'Expert tutoring', 0, '2025-09-25 15:26:07'),
(7, 3, 'Practice tests', 1, '2025-09-25 15:26:07'),
(8, 3, 'Score tracking', 2, '2025-09-25 15:26:07'),
(9, 3, 'University counseling', 3, '2025-09-25 15:26:07');

-- --------------------------------------------------------

--
-- Table structure for table `icons`
--

CREATE TABLE `icons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `class` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `icons`
--

INSERT INTO `icons` (`id`, `name`, `filename`, `created_at`, `class`) VALUES
(1, 'Target', 'target.svg', '2025-09-24 19:34:28', 'bx bxs-bullseye'),
(2, 'Book Stack', 'book-stack.svg', '2025-09-24 19:34:28', 'bx bxs-book-bookmark'),
(3, 'Book Open', 'book-open.svg', '2025-09-24 19:34:28', 'bx bxs-book-open'),
(4, 'Trophy', 'trophy.svg', '2025-09-24 19:34:28', 'bx bxs-trophy'),
(5, 'Star', 'star.svg', '2025-09-24 19:34:28', 'bx bxs-star'),
(6, 'Laptop', 'laptop.svg', '2025-09-24 19:34:28', 'bx bxs-laptop'),
(7, 'Teacher', 'teacher.svg', '2025-09-24 19:34:28', 'bx bxs-user'),
(8, 'Results', 'results.svg', '2025-09-24 19:34:28', 'bx bxs-bar-chart-alt-2'),
(9, 'Graduation', 'graduation.svg', '2025-09-24 19:34:28', 'bx bxs-graduation');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_attempt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gateway` varchar(50) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payer_account_name` varchar(255) DEFAULT NULL,
  `payer_account_number` varchar(100) DEFAULT NULL,
  `payer_bank_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `max_count` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `max_count`, `created_at`) VALUES
(1, 'Admin', 'admin', 2, '2025-08-29 08:46:25'),
(2, 'Sub-Admin', 'sub-admin', 3, '2025-08-29 08:46:25'),
(3, 'Moderator', 'moderator', NULL, '2025-08-29 08:46:25');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `menu_slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `menu_slug`) VALUES
(1, 1, 'dashboard'),
(2, 1, 'users'),
(3, 1, 'roles'),
(4, 1, 'settings'),
(5, 1, 'courses'),
(6, 1, 'tutors'),
(7, 1, 'students'),
(8, 1, 'payments'),
(9, 1, 'post'),
(10, 1, 'comments'),
(11, 1, 'chat'),
(17, 3, 'dashboard'),
(18, 3, 'post'),
(19, 3, 'comments'),
(20, 3, 'chat'),
(26, 2, 'dashboard'),
(27, 2, 'courses'),
(28, 2, 'tutors'),
(29, 2, 'students'),
(30, 2, 'payments');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(150) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `logo_url` varchar(1024) DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact_phone` varchar(64) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_address` text DEFAULT NULL,
  `contact_facebook` varchar(512) DEFAULT NULL,
  `contact_twitter` varchar(512) DEFAULT NULL,
  `contact_instagram` varchar(512) DEFAULT NULL,
  `maintenance` tinyint(1) DEFAULT 0,
  `registration` tinyint(1) DEFAULT 1,
  `email_verification` tinyint(1) DEFAULT 1,
  `two_factor` tinyint(1) DEFAULT 0,
  `comment_moderation` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `site_name`, `tagline`, `logo_url`, `vision`, `about`, `contact_phone`, `contact_email`, `contact_address`, `contact_facebook`, `contact_twitter`, `contact_instagram`, `maintenance`, `registration`, `email_verification`, `two_factor`, `comment_moderation`, `updated_at`, `created_at`, `bank_name`, `bank_account_name`, `bank_account_number`) VALUES
(1, 'HIGH Q SOLID ACADEMY', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1, 0, 1, '2025-09-22 22:52:09', '2025-09-22 22:52:09', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_programs`
--

CREATE TABLE `student_programs` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_registrations`
--

CREATE TABLE `student_registrations` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

CREATE TABLE `tutors` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `short_bio` varchar(255) DEFAULT NULL,
  `long_bio` text DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `subjects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`subjects`)),
  `contact_email` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `name`, `slug`, `photo`, `short_bio`, `long_bio`, `qualifications`, `subjects`, `contact_email`, `phone`, `rating`, `is_featured`, `created_at`, `updated_at`) VALUES
(3, 'Miss Omotola', '-iss-motola', 'uploads/tutors/1758822852_c59684e11458.jpeg', NULL, NULL, 'ewe', '[\"Biology\"]', NULL, NULL, NULL, 0, '2025-09-25 17:54:12', '2025-09-25 22:44:02');

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `twofa_secret` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `phone`, `email`, `password`, `avatar`, `is_active`, `twofa_secret`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 1, 'Akintunde Dolapo', '+2347082184560', 'akintunde.dolapo1@gmail.com', '$2y$10$sMjrVYcbmDLD4FSp8KTz3OTT41/poIWTzJIhgaQdcWK7y6d3ylL9i', NULL, 1, NULL, NULL, '2025-08-31 15:22:34', '2025-08-31 15:22:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_admin_id` (`assigned_admin_id`),
  ADD KEY `idx_chat_threads_status` (`status`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_reply_by` (`admin_reply_by`),
  ADD KEY `idx_comments_post_id` (`post_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_courses_slug` (`slug`);

--
-- Indexes for table `course_features`
--
ALTER TABLE `course_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`);

--
-- Indexes for table `icons`
--
ALTER TABLE `icons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_filename` (`filename`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_reference` (`reference`(191)),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_posts_slug` (`slug`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_programs`
--
ALTER TABLE `student_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_idx` (`registration_id`),
  ADD KEY `course_idx` (`course_id`);

--
-- Indexes for table `student_registrations`
--
ALTER TABLE `student_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- Indexes for table `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_threads`
--
ALTER TABLE `chat_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `course_features`
--
ALTER TABLE `course_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `icons`
--
ALTER TABLE `icons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_programs`
--
ALTER TABLE `student_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_registrations`
--
ALTER TABLE `student_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tutors`
--
ALTER TABLE `tutors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `chat_threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD CONSTRAINT `chat_threads_ibfk_1` FOREIGN KEY (`assigned_admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `comments_ibfk_4` FOREIGN KEY (`admin_reply_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `course_features`
--
ALTER TABLE `course_features`
  ADD CONSTRAINT `fk_course_features_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
