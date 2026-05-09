-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2026 at 06:51 PM
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
-- Table structure for table `ai_action_queue`
--

CREATE TABLE `ai_action_queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `proposal` longtext NOT NULL,
  `context` longtext DEFAULT NULL,
  `status` enum('queued','approved','rejected','executed','failed') NOT NULL DEFAULT 'queued',
  `review_note` varchar(500) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `execution_note` varchar(500) DEFAULT NULL,
  `executed_by` int(11) DEFAULT NULL,
  `executed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','confirmed','rejected','completed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `meta` longtext DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(28, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":3}', '2025-09-25 22:44:02'),
(29, 1, 'site_settings_upsert_failed', NULL, NULL, '{\"error\":\"There is already an active transaction\"}', '2025-09-27 14:49:20'),
(30, 1, 'site_settings_upsert_failed', NULL, NULL, '{\"error\":\"There is already an active transaction\"}', '2025-09-27 20:23:15'),
(31, 1, 'site_settings_upsert_failed', NULL, NULL, '{\"error\":\"There is already an active transaction\"}', '2025-09-27 20:23:31'),
(32, 1, 'site_settings_upsert_failed', NULL, NULL, '{\"error\":\"There is already an active transaction\"}', '2025-09-27 20:23:50'),
(33, 1, 'site_settings_upsert_failed', NULL, NULL, '{\"error\":\"There is already an active transaction\"}', '2025-09-28 01:30:55'),
(34, 1, 'student_delete', NULL, NULL, '{\"student_id\":4}', '2025-09-28 14:51:23'),
(35, 1, 'student_delete', NULL, NULL, '{\"student_id\":2}', '2025-09-28 14:51:30'),
(36, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":2}', '2025-09-28 15:55:18'),
(37, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":3}', '2025-09-28 15:55:26'),
(38, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":4}', '2025-09-28 15:55:31'),
(39, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-09-28 17:44:21'),
(40, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-09-28 17:45:25'),
(41, 1, 'course_created', NULL, NULL, '{\"slug\":\"professional\"}', '2025-09-28 17:47:17'),
(42, 1, 'course_updated', NULL, NULL, '{\"course_id\":5}', '2025-09-28 17:53:54'),
(43, 1, 'course_updated', NULL, NULL, '{\"course_id\":5}', '2025-09-28 17:55:12'),
(44, 1, 'course_created', NULL, NULL, '{\"slug\":\"cbt\"}', '2025-09-28 17:57:01'),
(45, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-09-29 19:52:46'),
(46, 1, 'security_scan_queued', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-09-29 19:57:12'),
(47, 1, 'course_updated', NULL, NULL, '{\"course_id\":5}', '2025-09-30 08:40:07'),
(48, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-09-30 08:40:49'),
(49, 1, 'post_created', NULL, NULL, '{\"slug\":\"introducing-the-high-q-solid-academy-website\"}', '2025-09-30 20:57:18'),
(50, 1, 'post_updated', NULL, NULL, '{\"post_id\":1}', '2025-09-30 21:16:30'),
(51, 1, 'post_updated', NULL, NULL, '{\"post_id\":1}', '2025-09-30 21:16:31'),
(52, 1, 'post_updated', NULL, NULL, '{\"post_id\":1}', '2025-09-30 21:16:33'),
(53, 1, 'post_updated', NULL, NULL, '{\"post_id\":1}', '2025-09-30 21:16:33'),
(54, 1, 'post_updated', NULL, NULL, '{\"post_id\":1}', '2025-09-30 21:16:33'),
(55, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":1}', '2025-10-01 20:49:55'),
(56, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":2}', '2025-10-02 04:39:39'),
(57, 1, 'confirm_registration', NULL, NULL, '{\"registration_id\":5}', '2025-10-02 05:38:22'),
(58, 1, 'create_payment_for_registration', NULL, NULL, '{\"registration_id\":5,\"payment_id\":\"5\",\"reference\":\"REG-20251002073822-bf3a66\",\"amount\":10000}', '2025-10-02 05:38:22'),
(59, 1, 'reject_registration', NULL, NULL, '{\"registration_id\":6,\"reason\":\"Didnt meet up with the standard sorry, Try again later\"}', '2025-10-02 05:53:23'),
(60, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":6}', '2025-10-02 20:49:27'),
(61, 1, 'confirm_registration', NULL, NULL, '{\"registration_id\":7}', '2025-10-02 20:51:09'),
(62, 1, 'create_payment_for_registration', NULL, NULL, '{\"registration_id\":7,\"payment_id\":\"6\",\"reference\":\"REG-20251002225109-b99141\",\"amount\":10000}', '2025-10-02 20:51:09'),
(63, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-02 22:19:03'),
(64, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":5,\"reason\":\"\"}', '2025-10-03 17:31:39'),
(65, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":6,\"reason\":\"\"}', '2025-10-03 17:31:52'),
(66, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":8}', '2025-10-03 20:13:49'),
(67, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":7}', '2025-10-03 20:16:32'),
(68, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":7}', '2025-10-04 10:13:03'),
(69, 1, 'confirm_registration', NULL, NULL, '{\"registration_id\":9}', '2025-10-04 10:14:43'),
(70, 1, 'create_payment_for_registration', NULL, NULL, '{\"registration_id\":9,\"payment_id\":\"8\",\"reference\":\"REG-20251004121443-f292cf\",\"amount\":10000}', '2025-10-04 10:14:43'),
(71, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":9}', '2025-10-04 10:18:44'),
(72, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-04 10:19:58'),
(73, 1, 'confirm_registration', NULL, NULL, '{\"registration_id\":10}', '2025-10-04 10:20:46'),
(74, 1, 'create_payment_for_registration', NULL, NULL, '{\"registration_id\":10,\"payment_id\":\"9\",\"reference\":\"REG-20251004122046-4b79ae\",\"amount\":10000}', '2025-10-04 10:20:46'),
(75, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":10}', '2025-10-04 10:26:23'),
(76, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":8,\"reason\":\"\"}', '2025-10-04 13:35:52'),
(77, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":11}', '2025-10-04 15:30:37'),
(78, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":14}', '2025-10-04 15:30:41'),
(79, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":13}', '2025-10-04 15:30:43'),
(80, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":12}', '2025-10-04 15:30:48'),
(81, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":9,\"reason\":\"\"}', '2025-10-04 19:59:29'),
(82, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":10,\"reason\":\"\"}', '2025-10-04 19:59:32'),
(83, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":11,\"reason\":\"\"}', '2025-10-04 19:59:35'),
(84, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":12,\"reason\":\"\"}', '2025-10-04 19:59:38'),
(85, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":13,\"reason\":\"\"}', '2025-10-04 19:59:42'),
(86, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":14,\"reason\":\"\"}', '2025-10-04 19:59:45'),
(87, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":15,\"reason\":\"\"}', '2025-10-04 20:03:51'),
(88, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":19}', '2025-10-04 20:05:16'),
(89, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":16,\"reason\":\"\"}', '2025-10-04 21:07:43'),
(90, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":17,\"reason\":\"\"}', '2025-10-04 21:07:46'),
(91, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":17,\"reason\":\"\"}', '2025-10-04 21:07:46'),
(92, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":18,\"reason\":\"\"}', '2025-10-04 21:07:47'),
(93, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":3}', '2025-10-04 22:12:51'),
(94, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":3}', '2025-10-04 22:12:55'),
(95, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"-istura-motolani\"}', '2025-10-04 22:21:04'),
(96, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":4}', '2025-10-05 01:46:35'),
(97, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":4}', '2025-10-05 01:46:41'),
(98, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":4}', '2025-10-05 01:47:37'),
(99, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":4}', '2025-10-05 01:47:46'),
(100, 1, 'tutor_deleted', NULL, NULL, '{\"tutor_id\":4}', '2025-10-05 01:49:41'),
(101, 1, 'tutor_deleted', NULL, NULL, '{\"tutor_id\":3}', '2025-10-05 01:49:47'),
(102, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"-istura-motolani\"}', '2025-10-05 02:01:40'),
(103, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":6}', '2025-10-05 11:14:30'),
(104, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-05 11:14:44'),
(105, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-05 11:33:50'),
(106, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"-r-an\"}', '2025-10-05 11:43:17'),
(107, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":7}', '2025-10-05 11:44:31'),
(108, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":7}', '2025-10-05 11:57:17'),
(109, 1, 'role_updated', NULL, NULL, '{\"role_id\":1}', '2025-10-05 12:53:05'),
(110, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-05 12:53:15'),
(111, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-05 12:53:50'),
(112, 1, 'post_updated', NULL, NULL, '{\"post_id\":1}', '2025-10-05 12:55:24'),
(113, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-05 12:56:50'),
(114, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-05 13:14:04'),
(115, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-05 13:14:12'),
(116, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":6}', '2025-10-05 13:14:20'),
(117, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-05 13:19:27'),
(118, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":7}', '2025-10-05 17:59:42'),
(119, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"-ina-luwasegun\"}', '2025-10-05 18:01:07'),
(120, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"-ide-shifisan\"}', '2025-10-05 18:02:43'),
(121, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"-kintunde-reoluwa\"}', '2025-10-05 18:04:41'),
(122, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"-iss-emi\"}', '2025-10-05 18:12:35'),
(123, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":6}', '2025-10-05 18:14:10'),
(124, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":6}', '2025-10-05 18:14:18'),
(125, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":6}', '2025-10-05 18:14:44'),
(126, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":7}', '2025-10-05 18:15:46'),
(127, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":9}', '2025-10-05 18:16:05'),
(128, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":9}', '2025-10-05 18:16:49'),
(129, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-05 18:18:07'),
(130, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":11}', '2025-10-05 18:18:55'),
(131, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":8}', '2025-10-05 18:19:43'),
(132, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":11}', '2025-10-05 18:27:49'),
(133, 1, 'course_created', NULL, NULL, '{\"slug\":\"digital-skills\"}', '2025-10-05 18:30:55'),
(134, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-05 18:31:10'),
(135, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-10-05 18:31:38'),
(136, 1, 'course_created', NULL, NULL, '{\"slug\":\"professional-services\"}', '2025-10-05 18:34:04'),
(137, 1, 'course_updated', NULL, NULL, '{\"course_id\":8}', '2025-10-05 18:36:07'),
(138, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-05 20:13:59'),
(139, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-05 21:17:36'),
(140, 1, 'tutor_created', NULL, NULL, '{\"slug\":\"-\"}', '2025-10-05 23:41:19'),
(141, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-05 23:42:39'),
(142, 1, 'course_created', NULL, NULL, '{\"slug\":\"tutorial-classes\"}', '2025-10-05 23:56:56'),
(143, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 00:25:51'),
(144, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":20}', '2025-10-06 00:26:36'),
(145, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 00:38:50'),
(146, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 00:48:53'),
(147, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 00:49:17'),
(148, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 00:55:29'),
(149, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 00:55:46'),
(150, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:14:09'),
(151, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:14:23'),
(152, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:29:22'),
(153, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:29:35'),
(154, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:34:25'),
(155, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:34:33'),
(156, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:34:48'),
(157, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:35:45'),
(158, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:40:20'),
(159, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:50:20'),
(160, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:50:31'),
(161, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:52:11'),
(162, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 01:53:27'),
(163, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 02:01:43'),
(164, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 02:02:25'),
(165, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 02:04:53'),
(166, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-06 02:05:02'),
(167, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":21}', '2025-10-06 02:24:32'),
(168, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":20}', '2025-10-06 02:24:34'),
(169, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":19}', '2025-10-06 02:24:38'),
(170, 1, 'post_updated', NULL, NULL, '{\"post_id\":1}', '2025-10-06 12:03:20'),
(171, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 12:10:30'),
(172, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 13:00:32'),
(173, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 13:00:32'),
(174, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 13:00:36'),
(175, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 13:00:36'),
(176, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":11}', '2025-10-06 13:00:48'),
(177, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":11}', '2025-10-06 13:00:48'),
(178, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":11}', '2025-10-06 13:01:23'),
(179, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":11}', '2025-10-06 13:01:23'),
(180, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:12:59'),
(181, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:12:59'),
(182, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:13:32'),
(183, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:13:32'),
(184, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:20:11'),
(185, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:20:11'),
(186, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:21:13'),
(187, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:21:13'),
(188, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:23:06'),
(189, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:23:06'),
(190, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:23:11'),
(191, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:23:11'),
(192, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:27:45'),
(193, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:27:45'),
(194, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:58:17'),
(195, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:58:17'),
(196, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:58:23'),
(197, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 13:58:23'),
(198, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 14:05:24'),
(199, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":10}', '2025-10-06 14:05:24'),
(200, 1, 'tutor_deleted', NULL, NULL, '{\"tutor_id\":13}', '2025-10-06 15:10:49'),
(201, 1, 'tutor_deleted', NULL, NULL, '{\"tutor_id\":13}', '2025-10-06 15:10:53'),
(202, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 15:10:59'),
(203, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 15:10:59'),
(204, 1, 'tutor_deleted', NULL, NULL, '{\"tutor_id\":13}', '2025-10-06 15:11:00'),
(205, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 15:11:06'),
(206, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 15:11:06'),
(207, 1, 'tutor_deleted', NULL, NULL, '{\"tutor_id\":13}', '2025-10-06 15:11:07'),
(208, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 15:11:22'),
(209, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-06 15:11:22'),
(210, 1, 'course_updated', NULL, NULL, '{\"course_id\":7}', '2025-10-07 09:43:22'),
(211, 1, 'course_updated', NULL, NULL, '{\"course_id\":3}', '2025-10-07 09:44:25'),
(212, 1, 'course_updated', NULL, NULL, '{\"course_id\":8}', '2025-10-07 09:44:52'),
(213, 1, 'course_updated', NULL, NULL, '{\"course_id\":6}', '2025-10-07 09:45:20'),
(214, 1, 'course_updated', NULL, NULL, '{\"course_id\":9}', '2025-10-07 09:45:41'),
(215, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-08 07:05:15'),
(216, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-08 07:05:15'),
(217, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":21}', '2025-10-16 10:35:53'),
(218, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":22,\"reason\":\"\"}', '2025-10-16 11:33:31'),
(219, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-19 23:40:42'),
(220, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-19 23:40:50'),
(221, 1, 'banish_user', NULL, NULL, '{\"user_id\":2}', '2025-10-20 00:25:32'),
(222, 1, 'reactivate_user', NULL, NULL, '{\"user_id\":2}', '2025-10-20 00:25:38'),
(223, 1, 'edit_user', NULL, NULL, '{\"user_id\":2}', '2025-10-20 00:33:43'),
(224, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-20 00:35:34'),
(225, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-20 00:35:39'),
(226, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-20 00:35:50'),
(227, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-20 00:36:01'),
(228, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-20 00:36:08'),
(229, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-20 00:46:03'),
(230, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-20 01:00:30'),
(231, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":26}', '2025-10-23 17:19:12'),
(232, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":15}', '2025-10-23 17:19:34'),
(233, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":1}', '2025-10-23 18:24:01'),
(234, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":1,\"reason\":\"\"}', '2025-10-23 18:24:02'),
(235, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":1}', '2025-10-23 18:24:37'),
(236, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":1,\"reason\":\"\"}', '2025-10-23 18:24:38'),
(237, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":1}', '2025-10-23 18:24:38'),
(238, 1, 'comment_deleted', NULL, NULL, '{\"comment_id\":1}', '2025-10-23 18:24:39'),
(239, 1, 'comment_destroyed', NULL, NULL, '{\"comment_id\":2}', '2025-10-23 18:24:39'),
(240, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":1}', '2025-10-23 19:52:18'),
(241, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":1,\"reason\":\"\"}', '2025-10-23 19:52:31'),
(242, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":1}', '2025-10-23 19:52:41'),
(243, 1, 'comment_deleted', NULL, NULL, '{\"comment_id\":1}', '2025-10-23 19:52:50'),
(244, 1, 'comment_destroyed', NULL, NULL, '{\"comment_id\":2}', '2025-10-23 19:53:05'),
(245, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":27}', '2025-10-23 20:23:29'),
(246, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":27}', '2025-10-25 13:05:22'),
(247, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-25 13:05:51'),
(248, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-25 13:05:51'),
(249, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-25 13:05:56'),
(250, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-25 13:05:56'),
(251, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":26}', '2025-10-25 19:37:48'),
(252, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":24}', '2025-10-25 22:13:06'),
(253, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-10-25 22:13:24'),
(254, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":32}', '2025-10-30 06:47:31'),
(255, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":31}', '2025-10-30 06:47:34'),
(256, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":30}', '2025-10-30 06:47:37'),
(257, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":29}', '2025-10-30 06:48:22'),
(258, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":28}', '2025-10-30 06:48:25'),
(259, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-30 06:48:58'),
(260, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-30 06:48:58'),
(261, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-30 06:49:18'),
(262, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-30 06:49:18'),
(263, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-30 06:49:22'),
(264, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-30 06:49:22'),
(265, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-30 06:49:39'),
(266, 1, 'tutor_updated', NULL, NULL, '{\"tutor_id\":12}', '2025-10-30 06:49:39'),
(267, 1, 'create_payment_link', NULL, NULL, '{\"payment_id\":\"34\",\"email\":\"akintunde.dolapo1@gmail.com\",\"emailed\":false}', '2025-10-30 21:23:20'),
(268, 1, 'create_payment_link', NULL, NULL, '{\"payment_id\":\"35\",\"email\":\"akintunde.dolapo1@gmail.com\",\"emailed\":true}', '2025-10-30 21:26:05'),
(269, 1, 'create_payment_link', NULL, NULL, '{\"payment_id\":\"36\",\"email\":\"akintunde.dolapo1@gmail.com\",\"emailed\":true}', '2025-10-30 23:04:42'),
(270, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":25}', '2025-11-01 23:31:56'),
(271, 1, 'registration_delete', NULL, NULL, '{\"registration_id\":17}', '2025-11-02 04:01:54'),
(272, 1, 'postutme_delete', NULL, NULL, '{\"postutme_id\":5}', '2025-11-02 04:02:02'),
(273, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":36,\"reason\":\"\"}', '2025-11-02 04:02:22'),
(274, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":35,\"reason\":\"\"}', '2025-11-02 04:02:24'),
(275, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":34,\"reason\":\"\"}', '2025-11-02 04:02:28'),
(276, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":33,\"reason\":\"\"}', '2025-11-02 04:02:30'),
(277, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":40}', '2025-11-03 13:56:21'),
(278, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-11-03 13:57:39'),
(279, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-11-03 13:57:55'),
(280, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-11-03 13:59:07'),
(281, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-11-03 13:59:29'),
(282, 1, 'security_scan_queued', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-11-03 14:00:50'),
(283, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2025-11-03 17:33:37'),
(284, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":41,\"reason\":\"\"}', '2025-11-07 10:47:34'),
(285, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":42,\"reason\":\"\"}', '2025-11-07 10:47:38'),
(286, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":42,\"reason\":\"\"}', '2025-11-07 10:47:39'),
(287, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":42,\"reason\":\"\"}', '2025-11-07 10:47:41'),
(288, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":39,\"reason\":\"\"}', '2025-11-07 10:47:48'),
(289, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":43,\"reason\":\"\"}', '2025-11-11 22:04:04'),
(290, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":44}', '2025-11-11 22:14:42'),
(291, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":38,\"reason\":\"\"}', '2025-11-13 06:43:09'),
(292, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":37,\"reason\":\"\"}', '2025-11-13 06:43:12'),
(293, 1, 'reject_payment', NULL, NULL, '{\"payment_id\":23,\"reason\":\"\"}', '2025-11-13 06:43:20'),
(294, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":1}', '2025-12-16 14:35:53'),
(295, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":1}', '2025-12-16 14:57:53'),
(296, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":6}', '2025-12-16 15:12:16'),
(297, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":1}', '2025-12-16 15:31:23'),
(298, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":5}', '2025-12-16 16:18:14'),
(299, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":60}', '2025-12-23 19:21:44'),
(300, 1, 'security_scan_completed', NULL, NULL, '{\"scan_type\":\"quick\",\"critical\":0,\"warnings\":1,\"info\":0}', '2025-12-24 14:11:13'),
(301, 1, 'banish_user', NULL, NULL, '{\"user_id\":2}', '2025-12-25 06:26:04'),
(302, 1, 'comment_approved', NULL, NULL, '{\"comment_id\":7}', '2025-12-25 13:53:58'),
(303, 1, 'reactivate_user', NULL, NULL, '{\"user_id\":2}', '2025-12-25 15:28:40'),
(304, 1, 'banish_user', NULL, NULL, '{\"user_id\":2}', '2025-12-25 15:34:27'),
(305, 1, 'reactivate_user', NULL, NULL, '{\"user_id\":2}', '2025-12-26 17:34:42'),
(306, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":17}', '2026-01-23 13:52:19'),
(307, 1, 'chat_reply', NULL, NULL, '{\"thread_id\":17,\"message_preview\":\"try the link again now sir\"}', '2026-01-23 13:52:51'),
(308, 1, 'chat_closed', NULL, NULL, '{\"thread_id\":17}', '2026-01-23 13:54:11'),
(309, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":16}', '2026-02-01 00:26:48'),
(310, 1, 'chat_reply', NULL, NULL, '{\"thread_id\":16,\"message_preview\":\"How you doing rn\"}', '2026-02-01 00:27:10'),
(311, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2026-02-01 00:30:15'),
(312, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":18}', '2026-02-08 19:57:18'),
(313, 1, 'chat_reply', NULL, NULL, '{\"thread_id\":18,\"message_preview\":\"How you doing, and how may i help you\"}', '2026-02-08 19:57:34'),
(314, 1, 'chat_closed', NULL, NULL, '{\"thread_id\":18}', '2026-02-08 20:01:40'),
(315, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":19}', '2026-02-08 20:02:02'),
(316, 1, 'chat_reply', NULL, NULL, '{\"thread_id\":19,\"message_preview\":\"I am good\"}', '2026-02-08 20:02:10'),
(317, 1, 'chat_closed', NULL, NULL, '{\"thread_id\":19}', '2026-02-08 20:20:50'),
(318, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2026-02-09 09:57:00'),
(319, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2026-02-09 09:57:23'),
(320, 1, 'settings_saved', NULL, NULL, '{\"by\":\"akintunde.dolapo1@gmail.com\"}', '2026-02-09 09:57:30'),
(321, 1, 'chat_claimed', NULL, NULL, '{\"thread_id\":21}', '2026-04-14 23:55:00'),
(322, 1, 'chat_reply', NULL, NULL, '{\"thread_id\":21,\"message_preview\":\"Please direct a detailed response on your issue so that i an help you further\"}', '2026-04-14 23:55:39'),
(323, 1, 'chat_closed', NULL, NULL, '{\"thread_id\":21}', '2026-04-14 23:58:23'),
(324, 1, 'tour_start', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-04-21 02:13:01'),
(325, 1, 'tour_skip', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-04-21 02:13:37'),
(326, 1, 'ai_assistant_error', NULL, NULL, '{\"message\":\"AI request failed: SSL connection timeout\",\"role\":\"Admin\"}', '2026-04-21 02:15:28'),
(327, 1, 'ai_assistant_query', NULL, NULL, '{\"provider\":\"groq\",\"model\":\"llama-3.1-8b-instant\",\"role\":\"Admin\",\"allowed_modules\":[\"dashboard\",\"users\",\"roles\",\"settings\",\"courses\",\"tutors\",\"students\",\"payments\",\"post\",\"comments\",\"chat\",\"create_payment_link\",\"icons\",\"audit_logs\",\"appointments\",\"academic\",\"sentinel\",\"patcher\",\"automator\",\"trap\",\"testimonials\",\"ai_assistant\",\"ai_queue\",\"ai_provider\"]}', '2026-04-21 02:34:33'),
(328, 1, 'ai_assistant_query', NULL, NULL, '{\"provider\":\"groq\",\"model\":\"llama-3.1-8b-instant\",\"role\":\"Admin\",\"allowed_modules\":[\"dashboard\",\"users\",\"roles\",\"settings\",\"courses\",\"tutors\",\"students\",\"payments\",\"post\",\"comments\",\"chat\",\"create_payment_link\",\"icons\",\"audit_logs\",\"appointments\",\"academic\",\"sentinel\",\"patcher\",\"automator\",\"trap\",\"testimonials\",\"ai_assistant\",\"ai_queue\",\"ai_provider\"]}', '2026-04-21 02:38:31'),
(329, 1, 'ai_assistant_query', NULL, NULL, '{\"provider\":\"groq\",\"model\":\"llama-3.1-8b-instant\",\"role\":\"Admin\",\"allowed_modules\":[\"dashboard\",\"users\",\"roles\",\"settings\",\"courses\",\"tutors\",\"students\",\"payments\",\"post\",\"comments\",\"chat\",\"create_payment_link\",\"icons\",\"audit_logs\",\"appointments\",\"academic\",\"sentinel\",\"patcher\",\"automator\",\"trap\",\"testimonials\",\"ai_assistant\",\"ai_queue\",\"ai_provider\"]}', '2026-04-21 03:38:03'),
(330, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":62}', '2026-04-21 04:50:08'),
(331, 1, 'ai_assistant_query', NULL, NULL, '{\"provider\":\"groq\",\"model\":\"llama-3.1-8b-instant\",\"role\":\"Admin\",\"allowed_modules\":[\"dashboard\",\"users\",\"roles\",\"settings\",\"courses\",\"tutors\",\"students\",\"payments\",\"post\",\"comments\",\"chat\",\"create_payment_link\",\"icons\",\"audit_logs\",\"appointments\",\"academic\",\"sentinel\",\"patcher\",\"automator\",\"trap\",\"testimonials\",\"ai_assistant\",\"ai_queue\",\"ai_provider\"]}', '2026-04-29 15:47:46'),
(332, 1, 'ai_assistant_query', NULL, NULL, '{\"provider\":\"groq\",\"model\":\"llama-3.1-8b-instant\",\"role\":\"Admin\",\"allowed_modules\":[\"dashboard\",\"users\",\"roles\",\"settings\",\"courses\",\"tutors\",\"students\",\"payments\",\"post\",\"comments\",\"chat\",\"create_payment_link\",\"icons\",\"audit_logs\",\"appointments\",\"academic\",\"sentinel\",\"patcher\",\"automator\",\"trap\",\"testimonials\",\"ai_assistant\",\"ai_queue\",\"ai_provider\"]}', '2026-04-29 15:47:57'),
(333, 1, 'tour_restart', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-05-03 17:09:28'),
(334, 1, 'tour_start', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-05-03 17:09:33'),
(335, 1, 'tour_restart', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-05-03 17:12:26'),
(336, 1, 'tour_restart', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-05-03 17:12:35'),
(337, 1, 'ai_assistant_error', NULL, NULL, '{\"message\":\"AI provider returned HTTP 401\",\"role\":\"Admin\"}', '2026-05-03 17:12:45'),
(338, 1, 'ai_assistant_error', NULL, NULL, '{\"message\":\"AI provider returned HTTP 401\",\"role\":\"Admin\"}', '2026-05-03 17:13:03'),
(339, 1, 'tour_restart', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-05-03 17:17:32'),
(340, 1, 'tour_start', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-05-03 17:17:33'),
(341, 1, 'tour_complete', NULL, NULL, '{\"role\":\"Admin\",\"db_updated\":true}', '2026-05-03 17:18:31'),
(343, 1, 'confirm_payment', NULL, NULL, '{\"payment_id\":63}', '2026-05-03 19:14:35'),
(344, 1, 'ai_assistant_query', NULL, NULL, '{\"provider\":\"local_fallback\",\"model\":\"highq-knowledge\",\"role\":\"Admin\",\"allowed_modules\":[\"dashboard\",\"users\",\"roles\",\"settings\",\"courses\",\"tutors\",\"students\",\"payments\",\"post\",\"comments\",\"chat\",\"create_payment_link\",\"icons\",\"audit_logs\",\"appointments\",\"academic\",\"sentinel\",\"patcher\",\"automator\",\"trap\",\"testimonials\",\"ai_assistant\",\"ai_queue\",\"ai_provider\"]}', '2026-05-03 19:22:07'),
(345, 1, 'ai_assistant_query', NULL, NULL, '{\"provider\":\"local_fallback\",\"model\":\"highq-knowledge\",\"role\":\"Admin\",\"allowed_modules\":[\"dashboard\",\"users\",\"roles\",\"settings\",\"courses\",\"tutors\",\"students\",\"payments\",\"post\",\"comments\",\"chat\",\"create_payment_link\",\"icons\",\"audit_logs\",\"appointments\",\"academic\",\"sentinel\",\"patcher\",\"automator\",\"trap\",\"testimonials\",\"ai_assistant\",\"ai_queue\",\"ai_provider\"]}', '2026-05-09 11:53:21'),
(346, 1, 'ai_assistant_query', NULL, NULL, '{\"provider\":\"groq\",\"model\":\"llama-3.1-8b-instant\",\"role\":\"Admin\",\"allowed_modules\":[\"dashboard\",\"users\",\"roles\",\"settings\",\"courses\",\"tutors\",\"students\",\"payments\",\"post\",\"comments\",\"chat\",\"create_payment_link\",\"icons\",\"audit_logs\",\"appointments\",\"academic\",\"sentinel\",\"patcher\",\"automator\",\"trap\",\"testimonials\",\"ai_assistant\",\"ai_queue\",\"ai_provider\"]}', '2026-05-09 11:55:57');

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `chat_attachments`
--

CREATE TABLE `chat_attachments` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_url` varchar(1024) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `original_name` varchar(512) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_attachments`
--

INSERT INTO `chat_attachments` (`id`, `message_id`, `file_url`, `created_at`, `original_name`, `mime_type`) VALUES
(1, 46, 'http://localhost/HIGH-Q/uploads/chat/795da8dfc894779b.pdf', '2026-02-08 20:16:17', 'ggg.pdf', 'application/pdf'),
(2, 51, 'http://localhost/HIGH-Q/uploads/chat/af75cef7dd8f34be.png', '2026-04-14 23:56:05', 'FastAPI.png', 'image/png');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(8, 3, 1, 'Akintunde Dolapo', 'could you render clear messages please', 1, '2025-09-25 17:11:36'),
(9, 4, NULL, 'Akintunde Dolapo', '', 0, '2025-10-05 21:11:29'),
(10, 5, NULL, 'Samuel', 'Can I make payments now', 0, '2025-10-14 15:57:28'),
(11, 6, NULL, 'Mavis', 'Am bored', 0, '2025-12-17 15:25:41'),
(12, 7, NULL, 'UI Test', 'Hello via automated test at 2025-12-19T18:10:34.9468126+01:00', 0, '2025-12-19 17:10:35'),
(13, 8, NULL, 'UI Test', 'Hello test 2025-12-19T18:11:16.2568472+01:00', 0, '2025-12-19 17:11:16'),
(14, 9, NULL, 'UI Test', 'Hello test 2025-12-19T18:11:29.3555498+01:00', 0, '2025-12-19 17:11:29'),
(15, 9, NULL, 'UI Test', 'Hello with image 2025-12-19T18:11:40.6549334+01:00', 0, '2025-12-19 17:11:40'),
(16, 9, NULL, 'UI Test', 'Attachment via PS form', 0, '2025-12-19 17:26:14'),
(17, 9, NULL, 'UI Test', 'Hello curl attach', 0, '2025-12-19 17:26:59'),
(18, 9, NULL, 'UI Test', 'Hello curl attach 2', 0, '2025-12-19 17:27:43'),
(19, 9, NULL, 'UI Test', 'Hello curl attach 3', 0, '2025-12-19 17:28:16'),
(20, 9, NULL, 'UI Test', 'Hello curl attach 4', 0, '2025-12-19 17:29:06'),
(21, 9, NULL, 'UI Test', 'Hello curl attach 5', 0, '2025-12-19 17:29:41'),
(22, 9, NULL, 'UI Test', 'Hello curl attach 6', 0, '2025-12-19 17:30:42'),
(23, 9, NULL, 'UI Test', 'Hello curl real image<br><img src=\"http://localhost/HIGH-Q/public/uploads/chat/e1c7762f00573aed.png\" style=\"max-width:100%;border-radius:8px\">', 0, '2025-12-19 17:30:59'),
(24, 9, NULL, 'UI Test', 'Landing styled test', 0, '2025-12-19 17:35:31'),
(25, 10, NULL, 'Ishola Samuel', 'I don&#039;t know how to register', 0, '2025-12-19 23:10:13'),
(26, 11, NULL, 'Ishola Samuel', 'I don&#039;t know how to register', 0, '2025-12-19 23:10:13'),
(27, 12, NULL, 'Dolapo', 'How is tution fee for the training\r\nhope is friendly', 0, '2025-12-23 15:57:37'),
(28, 13, NULL, 'Temiloluwa Atobatele', 'I’ll like to make an enquiry.', 0, '2025-12-23 18:38:02'),
(29, 13, NULL, 'Guest', 'Hi.', 0, '2025-12-23 18:38:46'),
(30, 14, NULL, 'Wahala', 'Is this working perfectly?!', 0, '2025-12-23 18:43:41'),
(31, 14, NULL, 'Guest', 'Shortly', 0, '2025-12-23 18:44:07'),
(32, 14, NULL, 'Guest', 'Connect me with an agent', 0, '2025-12-23 18:44:41'),
(33, 14, NULL, 'Guest', 'E be like say you never finish work for this one body ooooo', 0, '2025-12-23 18:45:34'),
(34, 14, NULL, 'Guest', 'Bye', 0, '2025-12-23 18:45:47'),
(35, 15, NULL, 'Opeyemi Micheal Osuntogun', 'Morning tonight sir', 0, '2025-12-23 20:03:48'),
(36, 15, NULL, 'Guest', 'I need you to explain in full detail what a cadet institution means,', 0, '2025-12-23 20:04:38'),
(37, 15, NULL, 'Guest', 'I mean the Nigeria Army cadet and tell me the difference between Nigeria Army cadet 🪖, Nigeria Navy cadet and Nigeria Air Force Cadet', 0, '2025-12-23 20:05:58'),
(38, 16, NULL, 'Opeyemi Micheal Osuntogun', 'Hi', 0, '2025-12-23 20:30:58'),
(39, 17, NULL, 'oyewole', 'i am having isues with the application gorm', 0, '2026-01-23 13:49:56'),
(40, 17, 1, 'Akintunde Dolapo', 'try the link again now sir', 1, '2026-01-23 13:52:51'),
(41, 16, 1, 'Akintunde Dolapo', 'How you doing rn', 1, '2026-02-01 00:27:10'),
(42, 18, NULL, 'Mavis', 'Hi, i need help', 0, '2026-02-08 19:57:10'),
(43, 18, 1, 'Akintunde Dolapo', 'How you doing, and how may i help you', 1, '2026-02-08 19:57:34'),
(44, 19, NULL, 'Mavis', 'Hi', 0, '2026-02-08 20:01:51'),
(45, 19, 1, 'Akintunde Dolapo', 'I am good', 1, '2026-02-08 20:02:10'),
(46, 19, NULL, 'Guest', 'This is the file please help me make check it please<br><a href=\"http://localhost/HIGH-Q/download_attachment.php?file=795da8dfc894779b.pdf\" target=\"_blank\">ggg.pdf</a>', 0, '2026-02-08 20:16:17'),
(47, 19, NULL, 'Guest', '', 0, '2026-02-08 20:18:24'),
(48, 20, NULL, 'dave', 'help me pass', 0, '2026-02-09 21:53:33'),
(49, 21, NULL, 'Mavis', 'I have an issue while sending/registering on ym site', 0, '2026-04-14 23:54:30'),
(50, 21, 1, 'Akintunde Dolapo', 'Please direct a detailed response on your issue so that i an help you further', 1, '2026-04-14 23:55:39'),
(51, 21, NULL, 'Guest', 'This is my issue<br><img src=\"http://localhost/HIGH-Q/uploads/chat/af75cef7dd8f34be.png\" style=\"max-width:100%;border-radius:8px\">', 0, '2026-04-14 23:56:05');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_threads`
--

INSERT INTO `chat_threads` (`id`, `visitor_name`, `visitor_email`, `user_id`, `assigned_admin_id`, `status`, `created_at`, `last_activity`) VALUES
(2, 'Akintunde Dolapo', 'akintunde.dolapo1@gmail.com', NULL, 1, 'closed', '2025-09-24 23:03:27', '2025-09-25 16:41:17'),
(3, 'Micheal', 'mavisenquires@gmail.com', NULL, 1, 'closed', '2025-09-25 16:45:55', '2025-09-25 17:11:55'),
(4, 'Akintunde Dolapo', '', NULL, NULL, 'open', '2025-10-05 21:11:29', '2025-10-05 21:11:29'),
(5, 'Samuel', '', NULL, 1, 'open', '2025-10-14 15:57:28', '2025-12-16 16:18:14'),
(6, 'Mavis', 'akintunde.dolapo1@gmail.com', NULL, NULL, 'open', '2025-12-17 15:25:41', '2025-12-17 15:25:41'),
(7, 'UI Test', 'ui@example.com', NULL, NULL, 'open', '2025-12-19 17:10:35', '2025-12-19 17:10:35'),
(8, 'UI Test', '', NULL, NULL, 'open', '2025-12-19 17:11:16', '2025-12-19 17:11:16'),
(9, 'UI Test', '', NULL, NULL, 'open', '2025-12-19 17:11:29', '2025-12-19 17:35:31'),
(10, 'Ishola Samuel', 'isholasamuel062@gmail.com', NULL, NULL, 'open', '2025-12-19 23:10:13', '2025-12-19 23:10:13'),
(11, 'Ishola Samuel', 'isholasamuel062@gmail.com', NULL, NULL, 'open', '2025-12-19 23:10:13', '2025-12-19 23:10:13'),
(12, 'Dolapo', 'adenijidolapo9@gmail.com', NULL, NULL, 'open', '2025-12-23 15:57:37', '2025-12-23 15:57:37'),
(13, 'Temiloluwa Atobatele', 'teechristie100@gmail.com', NULL, NULL, 'open', '2025-12-23 18:38:02', '2025-12-23 18:38:46'),
(14, 'Wahala', 'yoyoto@gmail.com', NULL, NULL, 'open', '2025-12-23 18:43:41', '2025-12-23 18:45:47'),
(15, 'Opeyemi Micheal Osuntogun', 'opeyemiosuntogun24@gmail.com', NULL, NULL, 'open', '2025-12-23 20:03:48', '2025-12-23 20:05:58'),
(16, 'Opeyemi Micheal Osuntogun', 'opeyemiosuntogun24@gmail.com', NULL, 1, 'open', '2025-12-23 20:30:58', '2026-02-01 00:27:10'),
(17, 'oyewole', 'oyewolevictor787@gmail.com', NULL, 1, 'closed', '2026-01-23 13:49:56', '2026-01-23 13:54:11'),
(18, 'Mavis', 'akintunde.dolapo1@gmail.com', NULL, 1, 'closed', '2026-02-08 19:57:10', '2026-02-08 20:01:40'),
(19, 'Mavis', 'akintunde.dolapo1@gmail.com', NULL, 1, 'closed', '2026-02-08 20:01:51', '2026-02-08 20:20:50'),
(20, 'dave', 'opy.omo0108@gmail.com', NULL, NULL, 'open', '2026-02-09 21:53:33', '2026-02-09 21:53:33'),
(21, 'Mavis', 'akintunde.dolapo1@gmail.com', NULL, 1, 'closed', '2026-04-14 23:54:30', '2026-04-14 23:58:23');

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
  `ip` varchar(64) DEFAULT NULL,
  `admin_reply_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved','spam','deleted') DEFAULT 'pending',
  `session_id` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `parent_id`, `user_id`, `name`, `email`, `content`, `ip`, `admin_reply_by`, `status`, `session_id`, `created_at`, `is_approved`) VALUES
(1, 1, NULL, NULL, 'Akintunde Dolapo', 'akintunde.dolapo1@gmail.com', 'This is a great post', NULL, NULL, 'approved', NULL, '2025-10-01 20:49:44', 0),
(3, 3, NULL, NULL, 'Samuel', 'isholasamuel062@gmail.com', 'This is okay', NULL, NULL, 'pending', '3njgje61npkja37limpev9bquo', '2025-10-14 15:25:45', 0),
(4, 1, NULL, NULL, 'Stella', 'susankearny2008@gmail.com', 'This site is perfect 👌.', NULL, NULL, 'pending', '8oeclq2juvt8e7tuq1q1hcnuct', '2025-10-24 19:58:09', 0),
(5, 1, NULL, NULL, 'Stella', 'susankearny2008@gmail.com', 'This site is perfect 👌.', NULL, NULL, 'pending', '8oeclq2juvt8e7tuq1q1hcnuct', '2025-10-24 19:58:16', 0),
(6, 1, NULL, NULL, 'Stella', 'susankearny2008@gmail.com', 'This site is perfect 👌.', NULL, NULL, 'approved', '8oeclq2juvt8e7tuq1q1hcnuct', '2025-10-24 19:58:22', 0),
(7, 1, NULL, NULL, 'Quam Adebule', 'adebulequamokikiola@gmail.com', 'Wow . This is impressive', NULL, NULL, 'approved', 'k7gdt30s6pa9b3r78j1sv60dml', '2025-12-18 16:01:24', 0),
(8, 1, 7, NULL, 'MAVIS GAMING', 'akintunde.dolapo1@gmail.com', 'Yes it is, worked on very well', NULL, NULL, 'approved', 'big7475kr7kgmbm6888fvooufi', '2026-02-09 09:58:00', 0),
(9, 1, NULL, NULL, 'MAVIS GAMING', 'akintunde.dolapo1@gmail.com', 'Yes it is, worked on very well', NULL, NULL, 'deleted', 'big7475kr7kgmbm6888fvooufi', '2026-02-09 09:58:54', 0),
(10, 1, 6, NULL, 'MAVIS GAMING', 'akintunde.dolapo1@gmail.com', 'Bet yh it is', NULL, NULL, 'approved', 'big7475kr7kgmbm6888fvooufi', '2026-02-09 10:43:37', 0),
(11, 1, NULL, NULL, 'Omotosho David', 'opy.omo0108@gmail.com', 'Nice one bro', NULL, NULL, 'approved', '1cef4ssnim9nfimdp00k07j6od', '2026-02-09 21:48:55', 0);

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_likes`
--

INSERT INTO `comment_likes` (`id`, `comment_id`, `session_id`, `ip`, `created_at`) VALUES
(1, 1, '4gk27n7710ghvfqbg15lkoemfv', NULL, '2026-01-23 13:47:22'),
(2, 6, '4gk27n7710ghvfqbg15lkoemfv', NULL, '2026-01-23 13:47:38');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `slug`, `description`, `duration`, `price`, `tutor_id`, `created_by`, `is_active`, `created_at`, `updated_at`, `icon`, `features`, `highlight_badge`) VALUES
(3, 'JAMB/Other Enquires on JAMB', 'jamb-post-utme', 'Comprehensive guidance on JAMB and Post-UTME requirements, registration, and prep.', '4-6 months', 10000.00, NULL, 1, 1, '2025-09-24 22:58:48', '2025-12-23 17:23:55', 'bx bxs-bar-chart-alt-2', NULL, 'Expert Guidance'),
(5, 'Professional Services', 'professional', 'Consulting, documentation support, and career guidance for students and professionals.', 'As needed', NULL, NULL, 1, 1, '2025-09-28 17:47:15', '2025-12-23 17:23:55', 'bx bx-briefcase', NULL, 'On-Demand Help'),
(6, 'CBT Training', 'cbt', 'Hands-on CBT simulations to build speed, accuracy, and confidence for computer-based exams.', '2-4 weeks', 15000.00, NULL, 1, 1, '2025-09-28 17:56:59', '2025-12-23 17:23:55', 'bx bx-desktop', NULL, 'Real CBT Practice'),
(7, 'Digital Skills', 'digital-skills', 'Practical digital skills: productivity, collaboration tools, online research, and safety.', '6-10 weeks', 0.00, NULL, 1, 1, '2025-10-05 18:30:55', '2025-12-23 17:23:55', 'bx bx-cloud', NULL, 'Future-Proof Skills'),
(8, 'WAEC/NECO', 'professional-services', 'Complete preparation for West African Senior School Certificate Examination and NECO.', '6-12 months', 8000.00, NULL, 1, 1, '2025-10-05 18:34:04', '2025-10-07 09:44:52', 'bx bxs-user', NULL, '99% pass rate'),
(9, 'Tutorial Classes', 'tutorial-classes', 'Structured tutorial sessions covering core subjects with continuous assessment.', '3-9 months', 0.00, NULL, 1, 1, '2025-10-05 23:56:55', '2025-12-23 17:23:55', 'bx bx-book-reader', NULL, 'Core Mastery'),
(17, 'JAMB Preparation', 'jamb-preparation', 'Comprehensive preparation for JAMB with targeted tutoring and CBT mock tests.', '4-6 months', NULL, NULL, 1, 1, '2026-01-23 00:36:07', '2026-01-23 00:36:07', 'bx bx-target-lock', NULL, 'Top JAMB Scores'),
(18, 'WAEC Preparation', 'waec-preparation', 'Complete preparation for WAEC covering core subjects, practicals, and past questions.', '6-12 months', NULL, NULL, 1, 1, '2026-01-23 00:36:07', '2026-01-23 00:36:07', 'bx bx-book', NULL, 'Core Subjects + Practicals'),
(19, 'NECO Preparation', 'neco-preparation', 'National Examination Council preparation with experienced tutors and structured mock exams.', '6-12 months', NULL, NULL, 1, 1, '2026-01-23 00:36:07', '2026-01-23 00:36:07', 'bx bx-book-open', NULL, 'NECO Excellence'),
(20, 'Post-UTME', 'post-utme', 'University-specific entrance examination prep with practice tests and interview guidance.', '2-4 months', NULL, NULL, 1, 1, '2026-01-23 00:36:07', '2026-01-23 00:36:07', 'bx bx-award', NULL, 'University Focused'),
(21, 'Special Tutorials', 'special-tutorials', 'Intensive one-on-one and small group tutorial sessions tailored to individual needs.', 'Flexible', NULL, NULL, 1, 1, '2026-01-23 00:36:07', '2026-01-23 00:36:07', 'bx bx-star', NULL, 'Personalized Mentorship'),
(22, 'Computer Training', 'computer-training', 'Modern computer skills and digital literacy training: MS Office, internet skills, and programming basics.', '3-6 months', NULL, NULL, 1, 1, '2026-01-23 00:36:07', '2026-01-23 00:36:07', 'bx bx-laptop', NULL, 'Digital Skills'),
(23, 'JAMB/UTME Preparation', 'jamb', 'Comprehensive preparation for JAMB and university entrance exams.', '3 Months', 10000.00, NULL, NULL, 1, '2026-04-21 05:15:40', '2026-04-21 05:15:40', NULL, NULL, NULL),
(24, 'WAEC/NECO/GCE', 'waec', 'O-Level exam preparation and tutoring for secondary school leavers.', '4 Months', 8000.00, NULL, NULL, 1, '2026-04-21 05:15:40', '2026-04-21 05:15:40', NULL, NULL, NULL),
(25, 'International Programs', 'international-programs', 'SAT, IELTS, TOEFL, and JUPEB exam preparation.', 'Flexible', 15000.00, NULL, NULL, 1, '2026-04-21 05:15:40', '2026-04-21 05:15:40', NULL, NULL, NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_features`
--

INSERT INTO `course_features` (`id`, `course_id`, `feature_text`, `position`, `created_at`) VALUES
(43, 17, 'Mock CBT drills', 0, '2026-02-19 19:20:37'),
(44, 17, 'Exam-focused curriculum', 1, '2026-02-19 19:20:37'),
(45, 17, 'Score tracking & analytics', 2, '2026-02-19 19:20:37'),
(46, 17, 'One-on-one tutor support', 3, '2026-02-19 19:20:37'),
(50, 18, 'Core + elective subjects', 0, '2026-02-19 19:20:37'),
(51, 18, 'Practicals and labs', 1, '2026-02-19 19:20:37'),
(52, 18, 'Past questions & marking guides', 2, '2026-02-19 19:20:37'),
(53, 18, 'Weekly progress reviews', 3, '2026-02-19 19:20:37'),
(57, 19, 'Comprehensive subject coverage', 0, '2026-02-19 19:20:37'),
(58, 19, 'Timed practice sessions', 1, '2026-02-19 19:20:37'),
(59, 19, 'Detailed feedback & corrections', 2, '2026-02-19 19:20:37'),
(60, 19, 'Exam strategy workshops', 3, '2026-02-19 19:20:37'),
(64, 20, 'Campus-specific practice tests', 0, '2026-02-19 19:20:37'),
(65, 20, 'Interview prep & coaching', 1, '2026-02-19 19:20:37'),
(66, 20, 'Speed & accuracy drills', 2, '2026-02-19 19:20:37'),
(67, 20, 'Result-driven study plans', 3, '2026-02-19 19:20:37'),
(71, 21, 'One-on-one coaching', 0, '2026-02-19 19:20:37'),
(72, 21, 'Custom study schedules', 1, '2026-02-19 19:20:37'),
(73, 21, 'Remedial + advanced tracks', 2, '2026-02-19 19:20:37'),
(74, 21, 'Performance monitoring', 3, '2026-02-19 19:20:37'),
(78, 22, 'MS Office mastery', 0, '2026-02-19 19:20:37'),
(79, 22, 'Internet & research skills', 1, '2026-02-19 19:20:37'),
(80, 22, 'Intro to programming', 2, '2026-02-19 19:20:37'),
(81, 22, 'Practical projects', 3, '2026-02-19 19:20:37');

-- --------------------------------------------------------

--
-- Table structure for table `forum_questions`
--

CREATE TABLE `forum_questions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_questions`
--

INSERT INTO `forum_questions` (`id`, `name`, `topic`, `content`, `created_at`) VALUES
(1, 'MAVIS GAMING', 'Payments', 'When does the next payment date open', '2026-02-09 09:59:47');

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE `forum_replies` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_votes`
--

CREATE TABLE `forum_votes` (
  `id` int(11) NOT NULL,
  `question_id` int(11) DEFAULT NULL,
  `reply_id` int(11) DEFAULT NULL,
  `vote` tinyint(4) NOT NULL DEFAULT 0,
  `session_id` varchar(128) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `icons`
--

CREATE TABLE `icons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `class` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `icons`
--

INSERT INTO `icons` (`id`, `name`, `filename`, `class`, `created_at`) VALUES
(1, 'Target', 'target.svg', 'bx bxs-bullseye', '2026-01-23 00:32:16'),
(2, 'Book Stack', 'book-stack.svg', 'bx bxs-book-bookmark', '2026-01-23 00:32:16'),
(3, 'Book Open', 'book-open.svg', 'bx bxs-book-open', '2026-01-23 00:32:16'),
(4, 'Trophy', 'trophy.svg', 'bx bxs-trophy', '2026-01-23 00:32:16'),
(5, 'Star', 'star.svg', 'bx bxs-star', '2026-01-23 00:32:16'),
(6, 'Laptop', 'laptop.svg', 'bx bxs-laptop', '2026-01-23 00:32:16'),
(7, 'Teacher', 'teacher.svg', 'bx bxs-user', '2026-01-23 00:32:16'),
(8, 'Results', 'results.svg', 'bx bxs-bar-chart-alt-2', '2026-01-23 00:32:16'),
(9, 'Graduation', 'graduation.svg', 'bx bxs-graduation', '2026-01-23 00:32:16');

-- --------------------------------------------------------

--
-- Table structure for table `ip_logs`
--

CREATE TABLE `ip_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `path` varchar(1024) DEFAULT NULL,
  `referer` varchar(1024) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `headers` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ip_logs`
--

INSERT INTO `ip_logs` (`id`, `ip`, `user_agent`, `path`, `referer`, `user_id`, `headers`, `created_at`) VALUES
(1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', NULL, NULL, NULL, '2026-01-23 01:33:09'),
(2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 01:33:11'),
(3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-01-23 01:33:13'),
(4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 01:33:19'),
(5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 01:33:21'),
(6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 01:33:31'),
(7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/programs.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 01:34:11'),
(8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 01:34:11'),
(9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/exams.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-01-23 01:34:18'),
(10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/exams.php', NULL, NULL, '2026-01-23 01:34:20'),
(11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 01:34:20'),
(12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-01-23 01:34:25'),
(13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-01-23 01:36:17'),
(14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 01:36:22'),
(15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 01:36:22'),
(16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 01:52:57'),
(17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 01:52:58'),
(18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 01:53:07'),
(19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:03:13'),
(20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:04:16'),
(21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 02:04:17'),
(22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:04:19'),
(23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:04:39'),
(24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 02:04:39'),
(25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:09:31'),
(26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 02:09:31'),
(27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:09:44'),
(28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 02:09:46'),
(29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:11:11'),
(30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 02:11:11'),
(31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:17:49'),
(32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 02:17:49'),
(33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:17:54'),
(34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:18:50'),
(35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 02:18:50'),
(36, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:20:31'),
(37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 02:20:31'),
(38, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:20:50'),
(39, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:21:40'),
(40, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:24:34'),
(41, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:24:36'),
(42, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:29:22'),
(43, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 02:33:25'),
(44, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:37:01'),
(45, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', '/HIGH-Q/find-your-path-quiz.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 02:37:38'),
(46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/', NULL, NULL, NULL, '2026-01-23 13:35:43'),
(47, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/', NULL, NULL, '2026-01-23 13:41:34'),
(48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-23 13:41:35'),
(49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/programs.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-23 13:42:09'),
(50, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/find-your-path-quiz.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-01-23 13:43:03'),
(51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/path-digital.php?goal=career&qual=inschool&match=69', 'http://localhost/HIGH-Q/find-your-path-quiz.php', NULL, NULL, '2026-01-23 13:44:15'),
(52, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php?path=digital', 'http://localhost/HIGH-Q/path-digital.php?goal=career&qual=inschool&match=69', NULL, NULL, '2026-01-23 13:44:45'),
(53, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php?step=2&type=digital', 'http://localhost/HIGH-Q/register-new.php?path=digital', NULL, NULL, '2026-01-23 13:44:57'),
(54, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/register-new.php?step=2&type=digital', NULL, NULL, '2026-01-23 13:45:06'),
(55, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/find-your-path-quiz.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-23 13:45:10'),
(56, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/path-postutme.php?goal=university&qual=diploma&match=62', 'http://localhost/HIGH-Q/find-your-path-quiz.php', NULL, NULL, '2026-01-23 13:45:51'),
(57, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php?path=postutme', 'http://localhost/HIGH-Q/path-postutme.php?goal=university&qual=diploma&match=62', NULL, NULL, '2026-01-23 13:46:09'),
(58, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/community.php', 'http://localhost/HIGH-Q/register-new.php?path=postutme', NULL, NULL, '2026-01-23 13:46:34'),
(59, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/community.php', NULL, NULL, '2026-01-23 13:46:53'),
(60, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/news.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-01-23 13:47:02'),
(61, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-01-23 13:47:08'),
(62, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-01-23 13:48:20'),
(63, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-01-23 13:48:28'),
(64, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-01-23 13:48:30'),
(65, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-01-23 13:48:31'),
(66, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-01-23 13:51:46'),
(67, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-01-23 13:51:46'),
(68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat&thread=17', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-01-23 13:52:07'),
(69, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 'http://localhost/HIGH-Q/admin/index.php?pages=chat&thread=17', 1, NULL, '2026-01-23 13:52:23'),
(70, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176354403', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:52:34'),
(71, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176359404', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:52:39'),
(72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176364410', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:52:44'),
(73, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176369403', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:52:49'),
(74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:52:53'),
(75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176385130', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:05'),
(76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176390130', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:10'),
(77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176395127', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:15'),
(78, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176400132', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:20'),
(79, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176405127', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:25'),
(80, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176410234', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:30'),
(81, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176415122', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:35'),
(82, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176420122', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:40'),
(83, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176425130', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:45'),
(84, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176430127', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:50'),
(85, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176435122', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:53:55'),
(86, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176440125', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:54:00'),
(87, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176445121', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:54:05'),
(88, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17&ajax=1&_=1769176449920', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:54:09'),
(89, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=17', 1, NULL, '2026-01-23 13:54:12'),
(90, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=chat', 1, NULL, '2026-01-23 13:54:37'),
(91, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-01-23 13:54:37'),
(92, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=chat', 1, NULL, '2026-01-24 17:20:13'),
(93, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-01-24 17:20:13'),
(94, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-01-24 17:20:13'),
(95, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-24 17:20:15'),
(96, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.woff2', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-01-24 17:20:15'),
(97, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.woff', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-01-24 17:20:16'),
(98, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.ttf', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-01-24 17:20:16'),
(99, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-01-31 21:34:53'),
(100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-31 21:35:45'),
(101, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.woff2', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-01-31 21:35:45'),
(102, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.woff', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-01-31 21:35:47'),
(103, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.ttf', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-01-31 21:35:47'),
(104, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-01-31 23:03:04'),
(105, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-01-31 23:03:13'),
(106, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:03:46'),
(107, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-01-31 23:26:11'),
(108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:26:23'),
(109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-31 23:26:23'),
(110, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-31 23:26:30'),
(111, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:26:31'),
(112, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:26:37'),
(113, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:08'),
(114, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-31 23:30:08'),
(115, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:09'),
(116, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:09'),
(117, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:09'),
(118, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:09'),
(119, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:10'),
(120, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:10'),
(121, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:10'),
(122, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:10'),
(123, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:30:38'),
(124, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-31 23:30:41'),
(125, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/programs.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-31 23:31:00'),
(126, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-01-31 23:31:07'),
(127, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/news.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-01-31 23:31:11'),
(128, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/community.php', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-01-31 23:31:13'),
(129, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/community.php', NULL, NULL, '2026-01-31 23:31:24'),
(130, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-01-31 23:31:46'),
(131, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-31 23:31:46'),
(132, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-01-31 23:31:59'),
(133, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/program-single.php?slug=special-tutorials', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:32:36'),
(134, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/program-single.php?slug=special-tutorials', NULL, NULL, '2026-01-31 23:32:45'),
(135, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:33:17'),
(136, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-31 23:33:17'),
(137, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-01-31 23:34:01'),
(138, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:34:16'),
(139, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/', NULL, NULL, NULL, '2026-01-31 23:34:39'),
(140, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/', NULL, NULL, '2026-01-31 23:34:47'),
(141, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-01-31 23:35:08'),
(142, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/program-single.php?slug=waec-preparation', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-01-31 23:35:16'),
(143, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/programs.php', 'http://localhost/HIGH-Q/program-single.php?slug=waec-preparation', NULL, NULL, '2026-01-31 23:35:49'),
(144, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-01-31 23:35:49'),
(145, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/program-single.php?slug=ssce-gce-exams', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-01-31 23:35:55'),
(146, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/program-single.php?slug=ssce-gce-exams', NULL, NULL, '2026-02-01 00:12:33'),
(147, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-01 00:12:35'),
(148, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-01 00:12:35'),
(149, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-01 00:12:53'),
(150, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-02-01 00:19:12'),
(151, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-02-01 00:23:40'),
(152, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-02-01 00:23:40'),
(153, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-02-01 00:24:31'),
(154, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-02-01 00:24:31'),
(155, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-02-01 00:26:39'),
(156, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 'http://localhost/HIGH-Q/admin/index.php?pages=chat', 1, NULL, '2026-02-01 00:26:52'),
(157, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16&ajax=1&_=1769905623957', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 1, NULL, '2026-02-01 00:27:04'),
(158, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16&ajax=1&_=1769905628960', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 1, NULL, '2026-02-01 00:27:09'),
(159, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 1, NULL, '2026-02-01 00:27:12'),
(160, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16&ajax=1&_=1769905643396', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 1, NULL, '2026-02-01 00:27:23'),
(161, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16&ajax=1&_=1769905648407', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 1, NULL, '2026-02-01 00:27:28'),
(162, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16&ajax=1&_=1769905653400', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 1, NULL, '2026-02-01 00:27:33'),
(163, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=16', 1, NULL, '2026-02-01 00:27:37'),
(164, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=audit_logs', 'http://localhost/HIGH-Q/admin/index.php?pages=chat', 1, NULL, '2026-02-01 00:27:54'),
(165, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=audit_logs', 1, NULL, '2026-02-01 00:29:25'),
(166, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-02-01 00:29:25'),
(167, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-02-01 00:29:41'),
(168, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-01 00:30:15'),
(169, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-01 00:30:15'),
(170, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-01 00:30:29'),
(171, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-02-01 00:30:29'),
(172, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-02-01 01:01:29'),
(173, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-01 01:01:29'),
(174, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 19:27:50'),
(175, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:28:56'),
(176, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:32:23'),
(177, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 19:34:21'),
(178, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:36:17'),
(179, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:39:04'),
(180, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:39:46'),
(181, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:43:58'),
(182, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:48:20'),
(183, '::1', 'WhatsApp/2.23.20.0', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:49:20'),
(184, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '/HIGH-Q/about.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-08 19:49:26'),
(185, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-08 19:49:27'),
(186, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:50:39'),
(187, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/about.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-08 19:51:40'),
(188, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-08 19:51:43'),
(189, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/programs.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/about.php', NULL, NULL, '2026-02-08 19:51:55'),
(190, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/exams.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/programs.php', NULL, NULL, '2026-02-08 19:52:09'),
(191, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/exams.php', NULL, NULL, NULL, '2026-02-08 19:52:18'),
(192, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/exams.php', NULL, NULL, NULL, '2026-02-08 19:52:20'),
(193, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/exams.php', NULL, NULL, NULL, '2026-02-08 19:52:20'),
(194, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/news.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/exams.php', NULL, NULL, '2026-02-08 19:52:24'),
(195, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/news.php', NULL, NULL, NULL, '2026-02-08 19:52:30'),
(196, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/news.php', NULL, NULL, NULL, '2026-02-08 19:52:32'),
(197, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/news.php', NULL, NULL, NULL, '2026-02-08 19:52:32'),
(198, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/community.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/news.php', NULL, NULL, '2026-02-08 19:52:41'),
(199, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/community.php', NULL, NULL, NULL, '2026-02-08 19:52:51');
INSERT INTO `ip_logs` (`id`, `ip`, `user_agent`, `path`, `referer`, `user_id`, `headers`, `created_at`) VALUES
(200, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/community.php', NULL, NULL, NULL, '2026-02-08 19:52:53'),
(201, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/community.php', NULL, NULL, NULL, '2026-02-08 19:52:53'),
(202, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/community.php', NULL, NULL, '2026-02-08 19:53:05'),
(203, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/contact.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/register-new.php', NULL, NULL, '2026-02-08 19:53:40'),
(204, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/find-your-path-quiz.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/contact.php', NULL, NULL, '2026-02-08 19:54:06'),
(205, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-02-08 19:56:41'),
(206, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 19:56:41'),
(207, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-02-08 19:56:46'),
(208, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 'http://localhost/HIGH-Q/admin/index.php?pages=chat', 1, NULL, '2026-02-08 19:57:22'),
(209, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580647511', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:57:27'),
(210, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580652512', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:57:32'),
(211, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:57:36'),
(212, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580661243', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:57:41'),
(213, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580666496', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:57:46'),
(214, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580671502', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:57:51'),
(215, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580676508', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:57:56'),
(216, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580681495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:01'),
(217, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580686500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:06'),
(218, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580691508', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:11'),
(219, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580696502', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:16'),
(220, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580701508', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:21'),
(221, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580706504', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:26'),
(222, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580711499', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:31'),
(223, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580716511', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:36'),
(224, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580721573', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:41'),
(225, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580739497', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:58:59'),
(226, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 19:59:42'),
(227, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580799506', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 19:59:59'),
(228, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580859514', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 20:00:59'),
(229, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580897498', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 20:01:37'),
(230, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18&ajax=1&_=1770580901248', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 20:01:41'),
(231, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=18', 1, NULL, '2026-02-08 20:01:41'),
(232, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 'http://localhost/HIGH-Q/admin/index.php?pages=chat', 1, NULL, '2026-02-08 20:02:04'),
(233, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580929782', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:09'),
(234, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:11'),
(235, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580937047', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:17'),
(236, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580942508', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:22'),
(237, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580947503', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:27'),
(238, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580952497', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:32'),
(239, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580957508', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:37'),
(240, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580962507', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:42'),
(241, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580967505', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:47'),
(242, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580972496', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:52'),
(243, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580977495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:02:57'),
(244, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580982508', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:03:02'),
(245, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580987501', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:03:07'),
(246, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770580992501', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:03:12'),
(247, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581039494', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:03:59'),
(248, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581099503', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:04:59'),
(249, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581159500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:05:59'),
(250, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581219500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:06:59'),
(251, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 20:08:09'),
(252, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 20:10:57'),
(253, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 20:12:17'),
(254, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581545553', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:12:25'),
(255, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581547047', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:12:27'),
(256, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581552500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:12:32'),
(257, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581557503', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:12:37'),
(258, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581562498', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:12:42'),
(259, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581567050', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:12:47'),
(260, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581572498', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:12:52'),
(261, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581577506', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:12:57'),
(262, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581582500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:02'),
(263, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581587498', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:07'),
(264, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581592506', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:12'),
(265, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581597498', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:17'),
(266, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581602493', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:22'),
(267, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581607544', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:27'),
(268, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581612497', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:32'),
(269, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581617496', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:37'),
(270, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581622500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:42'),
(271, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581627497', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:47'),
(272, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581639505', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:13:59'),
(273, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581699498', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:14:59'),
(274, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 20:15:36'),
(275, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581759500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:15:59'),
(276, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581782186', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:16:22'),
(277, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581787051', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:16:27'),
(278, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581792497', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:16:32'),
(279, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581797505', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:16:37'),
(280, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581802537', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:16:42'),
(281, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581807506', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:16:47'),
(282, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581812500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:16:52'),
(283, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581817501', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:16:57'),
(284, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581822499', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:02'),
(285, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581827499', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:07'),
(286, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581832506', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:12'),
(287, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581837503', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:17'),
(288, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581842495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:22'),
(289, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581847501', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:27'),
(290, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581852495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:32'),
(291, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581857504', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:37'),
(292, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581862496', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:42'),
(293, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581867048', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:47'),
(294, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581872496', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:52'),
(295, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581877508', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:17:57'),
(296, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581882495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:02'),
(297, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581887505', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:07'),
(298, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581892502', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:12'),
(299, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581897501', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:17'),
(300, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581902509', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:22'),
(301, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581907045', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:27'),
(302, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581912051', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:32'),
(303, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581917499', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:37'),
(304, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581922501', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:42'),
(305, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581927495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:47'),
(306, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581932495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:52'),
(307, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581937504', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:18:57'),
(308, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581942499', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:19:02'),
(309, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581947494', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:19:07'),
(310, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581952500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:19:12'),
(311, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581957511', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:19:17'),
(312, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581962507', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:19:22'),
(313, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581967501', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:19:27'),
(314, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770581999500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:19:59'),
(315, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582002057', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:02'),
(316, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582007047', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:07'),
(317, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582012501', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:12'),
(318, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582017505', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:17'),
(319, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582022506', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:22'),
(320, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582027494', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:27'),
(321, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582032499', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:32'),
(322, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582037494', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:37'),
(323, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582042502', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:42'),
(324, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582047050', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:47'),
(325, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582052495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:52'),
(326, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582057500', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:20:57'),
(327, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582062507', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:02'),
(328, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582067495', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:07'),
(329, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582072503', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:12'),
(330, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582077497', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:17'),
(331, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582082591', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:22'),
(332, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582087496', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:27'),
(333, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582092505', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:32'),
(334, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582097494', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:37'),
(335, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582102502', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:42'),
(336, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582107494', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:47'),
(337, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582119497', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:21:59'),
(338, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 20:22:00'),
(339, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582179504', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:22:59'),
(340, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582239506', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:23:59'),
(341, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19&ajax=1&_=1770582299499', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=19', 1, NULL, '2026-02-08 20:24:59'),
(342, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 20:31:36'),
(343, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-08 20:38:24'),
(344, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 20:39:08'),
(345, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-08 20:39:23'),
(346, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-09 08:37:02'),
(347, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-09 08:39:34'),
(348, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 08:39:34'),
(349, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/programs.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-02-09 08:48:31'),
(350, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 08:48:31'),
(351, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-02-09 08:49:49'),
(352, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 08:49:49'),
(353, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-02-09 09:02:50'),
(354, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 09:02:50'),
(355, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-02-09 09:04:05'),
(356, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 09:04:05'),
(357, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-02-09 09:21:39'),
(358, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 09:21:41'),
(359, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-02-09 09:26:39'),
(360, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 09:26:39'),
(361, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-02-09 09:33:32'),
(362, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/news.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-09 09:33:46'),
(363, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 09:33:46');
INSERT INTO `ip_logs` (`id`, `ip`, `user_agent`, `path`, `referer`, `user_id`, `headers`, `created_at`) VALUES
(364, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/community.php', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-02-09 09:33:48'),
(365, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/community.php', NULL, NULL, '2026-02-09 09:33:55'),
(366, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-02-09 09:35:27'),
(367, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/privacy.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-09 09:36:19'),
(368, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/terms.php', 'http://localhost/HIGH-Q/privacy.php', NULL, NULL, '2026-02-09 09:36:27'),
(369, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/terms.php', 'http://localhost/HIGH-Q/privacy.php', NULL, NULL, '2026-02-09 09:48:47'),
(370, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/privacy.php', 'http://localhost/HIGH-Q/terms.php', NULL, NULL, '2026-02-09 09:49:10'),
(371, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/terms.php', 'http://localhost/HIGH-Q/privacy.php', NULL, NULL, '2026-02-09 09:55:57'),
(372, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/terms.php', NULL, NULL, '2026-02-09 09:56:18'),
(373, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-09 09:56:26'),
(374, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-02-09 09:56:45'),
(375, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-02-09 09:56:45'),
(376, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-02-09 09:56:51'),
(377, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 09:56:59'),
(378, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 09:57:00'),
(379, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 09:57:09'),
(380, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 09:57:23'),
(381, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 09:57:25'),
(382, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 09:57:30'),
(383, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 09:57:32'),
(384, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-09 09:57:36'),
(385, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-09 09:58:57'),
(386, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-09 09:59:09'),
(387, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/community.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-02-09 09:59:27'),
(388, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/community.php', 'http://localhost/HIGH-Q/community.php', NULL, NULL, '2026-02-09 09:59:48'),
(389, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/community.php?topic=Payments', 'http://localhost/HIGH-Q/community.php', NULL, NULL, '2026-02-09 09:59:55'),
(390, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/news.php', 'http://localhost/HIGH-Q/community.php?topic=Payments', NULL, NULL, '2026-02-09 10:10:06'),
(391, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 10:10:06'),
(392, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/news.php', 'http://localhost/HIGH-Q/community.php?topic=Payments', NULL, NULL, '2026-02-09 10:42:47'),
(393, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 10:42:48'),
(394, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-02-09 10:42:56'),
(395, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-02-09 11:13:31'),
(396, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 11:17:12'),
(397, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=post_edit&id=1', 'http://localhost/HIGH-Q/admin/index.php?pages=post', 1, NULL, '2026-02-09 11:20:09'),
(398, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 11:22:41'),
(399, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 11:26:23'),
(400, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 11:26:39'),
(401, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-02-09 11:28:57'),
(402, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 11:35:59'),
(403, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 11:36:09'),
(404, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-02-09 11:44:51'),
(405, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-02-09 11:45:01'),
(406, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-02-09 11:45:07'),
(407, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-02-09 11:49:11'),
(408, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-02-09 19:44:41'),
(409, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=post', 1, NULL, '2026-02-09 19:46:52'),
(410, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-02-09 19:46:52'),
(411, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-02-09 19:47:20'),
(412, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/privacy.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-09 19:47:36'),
(413, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/privacy.php', NULL, NULL, '2026-02-09 19:47:45'),
(414, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-09 21:35:36'),
(415, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/news.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-09 21:47:13'),
(416, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 21:47:15'),
(417, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/index.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/news.php', NULL, NULL, '2026-02-09 21:47:23'),
(418, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/news.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/index.php', NULL, NULL, '2026-02-09 21:47:50'),
(419, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 21:47:54'),
(420, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/post.php?id=1', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/news.php', NULL, NULL, '2026-02-09 21:48:07'),
(421, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/exams.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/post.php?id=1', NULL, NULL, '2026-02-09 21:49:20'),
(422, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-09 21:51:58'),
(423, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/exams.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-09 21:52:06'),
(424, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/exams.php', NULL, NULL, '2026-02-09 21:52:46'),
(425, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-09 21:52:48'),
(426, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/contact.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/register-new.php', NULL, NULL, '2026-02-09 22:00:06'),
(427, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-10 02:09:15'),
(428, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-02-10 02:09:37'),
(429, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-02-10 02:09:37'),
(430, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/programs.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-10 02:10:11'),
(431, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-10 02:10:12'),
(432, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-02-10 02:10:17'),
(433, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/news.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-10 02:10:22'),
(434, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-02-10 02:10:26'),
(435, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-02-10 02:10:44'),
(436, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-02-10 02:10:46'),
(437, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-02-10 02:10:47'),
(438, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-15 15:02:08'),
(439, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-20 10:27:36'),
(440, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/find-your-path-quiz.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-20 10:28:52'),
(441, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/find-your-path-quiz.php', NULL, NULL, NULL, '2026-02-20 10:28:55'),
(442, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/find-your-path-quiz.php', NULL, NULL, NULL, '2026-02-20 10:28:57'),
(443, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/find-your-path-quiz.php', NULL, NULL, NULL, '2026-02-20 10:28:58'),
(444, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/about.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-20 10:29:28'),
(445, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-20 10:29:31'),
(446, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/about.php', NULL, NULL, NULL, '2026-02-20 10:29:33'),
(447, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/about.php', NULL, NULL, NULL, '2026-02-20 10:29:42'),
(448, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/about.php', NULL, NULL, NULL, '2026-02-20 10:29:42'),
(449, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-20 10:32:21'),
(450, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/about.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-20 10:32:32'),
(451, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-20 10:32:33'),
(452, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/', NULL, NULL, NULL, '2026-02-20 10:33:43'),
(453, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-02-20 10:39:44'),
(454, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-02-20 10:39:49'),
(455, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-20 10:39:49'),
(456, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/program-single.php?slug=jamb-preparation', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-20 10:39:59'),
(457, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php?ref=jamb-preparation', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/program-single.php?slug=jamb-preparation', NULL, NULL, '2026-02-20 10:40:43'),
(458, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-02-20 10:40:45'),
(459, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/register-new.php?ref=jamb-preparation', NULL, NULL, NULL, '2026-02-20 10:40:48'),
(460, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/register-new.php?ref=jamb-preparation', NULL, NULL, NULL, '2026-02-20 10:40:50'),
(461, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', '/HIGH-Q/register-new.php?ref=jamb-preparation', NULL, NULL, NULL, '2026-02-20 10:40:52'),
(462, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/program-single.php?slug=special-tutorials', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/', NULL, NULL, '2026-02-20 10:43:37'),
(463, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '/HIGH-Q/programs.php', 'https://slaphappy-premillennially-louann.ngrok-free.dev/HIGH-Q/program-single.php?slug=special-tutorials', NULL, NULL, '2026-02-20 10:44:22'),
(464, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/about.php', NULL, NULL, NULL, '2026-04-15 00:43:58'),
(465, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-15 00:43:59'),
(466, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-04-15 00:44:02'),
(467, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-04-15 00:44:24'),
(468, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-15 00:44:48'),
(469, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-15 00:44:48'),
(470, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-04-15 00:44:52'),
(471, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-04-15 00:44:52'),
(472, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=roles', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-04-15 00:44:53'),
(473, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=roles', 1, NULL, '2026-04-15 00:44:57'),
(474, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=courses', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-04-15 00:44:58'),
(475, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=tutors', 'http://localhost/HIGH-Q/admin/index.php?pages=courses', 1, NULL, '2026-04-15 00:45:01'),
(476, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-04-15 00:54:01'),
(477, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-15 00:54:49'),
(478, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-15 00:54:49'),
(479, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-04-15 00:54:53'),
(480, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 'http://localhost/HIGH-Q/admin/index.php?pages=chat', 1, NULL, '2026-04-15 00:55:03'),
(481, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210908301', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:08'),
(482, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210913300', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:13'),
(483, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210918300', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:18'),
(484, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210923302', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:23'),
(485, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210928301', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:28'),
(486, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210933296', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:33'),
(487, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210938301', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:38'),
(488, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:41'),
(489, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210946940', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:46'),
(490, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210951945', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:52'),
(491, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210956949', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:55:57'),
(492, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210961950', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:02'),
(493, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210966941', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:06'),
(494, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210971289', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:11'),
(495, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210976289', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:16'),
(496, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210981288', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:21'),
(497, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210986301', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:26'),
(498, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210991302', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:31'),
(499, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776210996301', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:36'),
(500, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211001296', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:41'),
(501, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211006293', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:46'),
(502, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211011293', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:51'),
(503, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211016297', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:56:56'),
(504, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211021296', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:01'),
(505, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211026298', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:06'),
(506, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211031295', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:11'),
(507, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211036292', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:16'),
(508, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211041302', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:21'),
(509, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211046293', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:26'),
(510, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211051291', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:31'),
(511, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211056296', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:36'),
(512, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211061298', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:41'),
(513, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211066298', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:46'),
(514, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211071301', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:51'),
(515, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211076303', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:57:56'),
(516, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211081292', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:58:01'),
(517, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211086297', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:58:06'),
(518, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211091303', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:58:11'),
(519, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211096300', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:58:16'),
(520, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21&ajax=1&_=1776211101295', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:58:21'),
(521, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/admin/index.php?pages=chat', 'http://localhost/HIGH-Q/admin/index.php?pages=chat_view&thread_id=21', 1, NULL, '2026-04-15 00:58:24'),
(522, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-04-15 01:18:14'),
(523, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-04-17 21:22:24'),
(524, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-17 21:23:00'),
(525, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-17 21:23:01'),
(526, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-04-17 21:23:12'),
(527, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=post', 1, NULL, '2026-04-17 21:23:18'),
(528, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-17 21:23:18'),
(529, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-04-17 21:23:22'),
(530, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=icons', 'http://localhost/HIGH-Q/admin/index.php?pages=post', 1, NULL, '2026-04-17 21:23:25'),
(531, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=icons', 'http://localhost/HIGH-Q/admin/index.php?pages=post', 1, NULL, '2026-04-17 21:23:25'),
(532, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=icons', 1, NULL, '2026-04-17 21:23:45'),
(533, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=roles', 'http://localhost/HIGH-Q/admin/index.php?pages=post', 1, NULL, '2026-04-17 21:23:51'),
(534, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=roles', 1, NULL, '2026-04-17 21:24:02'),
(535, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-17 21:24:02'),
(536, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-04-17 21:27:29'),
(537, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-17 21:27:29'),
(538, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/about.php', NULL, NULL, NULL, '2026-04-21 01:54:09'),
(539, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-21 01:54:11'),
(540, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-04-21 02:00:19'),
(541, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-04-21 02:26:22'),
(542, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php/admin', NULL, NULL, NULL, '2026-04-21 03:12:27'),
(543, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php/admin', NULL, NULL, NULL, '2026-04-21 03:12:33'),
(544, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-21 03:12:58'),
(545, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-21 03:12:58'),
(546, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-21 03:34:18'),
(547, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-21 03:34:18'),
(548, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_provider', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-04-21 03:45:10'),
(549, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_queue', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_provider', 1, NULL, '2026-04-21 03:45:29'),
(550, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_assistant', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_queue', 1, NULL, '2026-04-21 03:45:32'),
(551, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_assistant', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_queue', 1, NULL, '2026-04-21 04:36:21'),
(552, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_queue', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_assistant', 1, NULL, '2026-04-21 04:36:28'),
(553, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_queue', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_queue', 1, NULL, '2026-04-21 04:36:32'),
(554, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_queue', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_queue', 1, NULL, '2026-04-21 04:36:36'),
(555, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_assistant', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_queue', 1, NULL, '2026-04-21 04:36:40');
INSERT INTO `ip_logs` (`id`, `ip`, `user_agent`, `path`, `referer`, `user_id`, `headers`, `created_at`) VALUES
(556, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_assistant', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_queue', 1, NULL, '2026-04-21 04:38:33'),
(557, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-21 04:38:46'),
(558, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-21 04:38:46'),
(559, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-04-21 04:38:50'),
(560, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-04-21 04:38:50'),
(561, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-04-21 04:39:00'),
(562, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-04-21 04:39:01'),
(563, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-04-21 04:39:06'),
(564, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-04-21 04:39:06'),
(565, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/', NULL, NULL, NULL, '2026-04-21 04:39:51'),
(566, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/privacy.php', 'http://localhost/HIGH-Q/', NULL, NULL, '2026-04-21 04:40:03'),
(567, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/terms.php', 'http://localhost/HIGH-Q/privacy.php', NULL, NULL, '2026-04-21 04:40:20'),
(568, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/find-your-path-quiz.php', 'http://localhost/HIGH-Q/terms.php', NULL, NULL, '2026-04-21 04:40:32'),
(569, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/find-your-path-quiz.php', NULL, NULL, '2026-04-21 04:40:44'),
(570, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-21 05:08:58'),
(571, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-21 05:08:58'),
(572, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-04-21 05:09:01'),
(573, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_provider', 'http://localhost/HIGH-Q/admin/index.php?pages=settings', 1, NULL, '2026-04-21 05:09:08'),
(574, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=audit_logs', 'http://localhost/HIGH-Q/admin/index.php?pages=ai_provider', 1, NULL, '2026-04-21 05:09:12'),
(575, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=automator', 'http://localhost/HIGH-Q/admin/index.php?pages=audit_logs', 1, NULL, '2026-04-21 05:09:17'),
(576, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=automator', 1, NULL, '2026-04-21 05:09:20'),
(577, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-04-21 05:09:21'),
(578, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=courses', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-04-21 05:09:40'),
(579, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=tutors', 'http://localhost/HIGH-Q/admin/index.php?pages=courses', 1, NULL, '2026-04-21 05:09:42'),
(580, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic', 'http://localhost/HIGH-Q/admin/index.php?pages=tutors', 1, NULL, '2026-04-21 05:09:43'),
(581, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:16:25'),
(582, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-21 05:16:26'),
(583, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic', 'http://localhost/HIGH-Q/admin/index.php?pages=tutors', 1, NULL, '2026-04-21 05:19:27'),
(584, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&source=universal', 'http://localhost/HIGH-Q/admin/index.php?pages=academic', 1, NULL, '2026-04-21 05:19:31'),
(585, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&source=universal', 'http://localhost/HIGH-Q/admin/index.php?pages=academic', 1, NULL, '2026-04-21 05:22:07'),
(586, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&source=universal', 'http://localhost/HIGH-Q/admin/index.php?pages=academic', 1, NULL, '2026-04-21 05:22:09'),
(587, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&source=universal&program_type=jamb', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&source=universal', 1, NULL, '2026-04-21 05:22:22'),
(588, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&source=universal&program_type=', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&source=universal&program_type=jamb', 1, NULL, '2026-04-21 05:22:24'),
(589, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&source=universal&program_type=', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&source=universal&program_type=jamb', 1, NULL, '2026-04-21 05:29:22'),
(590, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=legacy_postutme', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&source=universal&program_type=', 1, NULL, '2026-04-21 05:29:33'),
(591, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=legacy_regular', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=legacy_postutme', 1, NULL, '2026-04-21 05:29:38'),
(592, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=digital', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=legacy_regular', 1, NULL, '2026-04-21 05:29:41'),
(593, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=postutme', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=digital', 1, NULL, '2026-04-21 05:29:44'),
(594, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=waec', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=postutme', 1, NULL, '2026-04-21 05:29:48'),
(595, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=jamb', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=waec', 1, NULL, '2026-04-21 05:29:50'),
(596, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=regular', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=jamb', 1, NULL, '2026-04-21 05:29:52'),
(597, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=regular', 1, NULL, '2026-04-21 05:29:58'),
(598, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:38:26'),
(599, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:38:46'),
(600, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-21 05:38:46'),
(601, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:38:50'),
(602, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:38:51'),
(603, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:38:51'),
(604, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:38:51'),
(605, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:38:51'),
(606, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/assets/vendor/fonts/boxicons.woff2', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-21 05:38:57'),
(607, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/assets/vendor/fonts/boxicons.woff', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-21 05:38:57'),
(608, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/assets/vendor/fonts/boxicons.ttf', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-21 05:38:57'),
(609, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:39:06'),
(610, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-21 05:39:20'),
(611, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.woff2', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-21 05:39:28'),
(612, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-21 05:39:28'),
(613, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.woff', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-21 05:39:28'),
(614, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.ttf', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-21 05:39:28'),
(615, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:39:33'),
(616, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-21 05:39:34'),
(617, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:39:38'),
(618, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '/HIGH-Q/register-new.php', NULL, NULL, NULL, '2026-04-21 05:39:41'),
(619, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php?step=2&type=jamb', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-04-21 05:47:23'),
(620, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-21 05:47:23'),
(621, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payments', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=', 1, NULL, '2026-04-21 05:50:06'),
(622, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/receipt.php?ref=JAMB-20260421-149995', NULL, NULL, '2026-04-21 05:53:48'),
(623, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=jamb', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=waec', 1, NULL, '2026-04-21 05:59:18'),
(624, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&action=export_single&id=1', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=jamb', 1, NULL, '2026-04-21 06:01:48'),
(625, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=jamb', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=waec', 1, NULL, '2026-04-21 06:16:52'),
(626, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&program_type=jamb', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=waec', 1, NULL, '2026-04-21 06:17:01'),
(627, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&action=export_single&id=1', 'http://localhost/HIGH-Q/admin/index.php?pages=academic&program_type=jamb', 1, NULL, '2026-04-21 06:17:16'),
(628, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/receipt.php?ref=JAMB-20260421-149995', NULL, NULL, '2026-04-21 09:27:00'),
(629, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-21 09:40:19'),
(630, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-21 09:40:19'),
(631, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_assistant', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-04-21 09:40:33'),
(632, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-04-29 16:25:48'),
(633, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.woff2', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-29 16:25:54'),
(634, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.woff', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-29 16:25:54'),
(635, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/vendor/fonts/boxicons.ttf', 'http://localhost/HIGH-Q/assets/vendor/boxicons/boxicons.min.css', NULL, NULL, '2026-04-29 16:25:54'),
(636, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-04-29 16:26:19'),
(637, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-04-29 16:26:26'),
(638, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-04-29 16:47:38'),
(639, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-04-29 16:47:38'),
(640, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-04-29 18:19:39'),
(641, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-04-30 13:53:23'),
(642, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-04-30 13:53:50'),
(643, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-04-30 13:53:50'),
(644, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-05-03 16:21:14'),
(645, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-05-03 16:21:17'),
(646, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-03 16:21:18'),
(647, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-05-03 16:22:51'),
(648, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-05-03 16:23:04'),
(649, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-05-03 16:35:29'),
(650, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-03 16:35:30'),
(651, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php?step=2&type=digital', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-05-03 16:35:32'),
(652, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-03 16:35:32'),
(653, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php?step=1', 'http://localhost/HIGH-Q/register-new.php?step=2&type=digital', NULL, NULL, '2026-05-03 16:35:36'),
(654, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-05-03 16:40:35'),
(655, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 16:40:35'),
(656, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php?step=2&type=jamb', 'http://localhost/HIGH-Q/register-new.php?step=1', NULL, NULL, '2026-05-03 17:15:04'),
(657, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-03 17:15:04'),
(658, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index2.php', NULL, NULL, NULL, '2026-05-03 17:34:09'),
(659, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/indext.php', NULL, NULL, NULL, '2026-05-03 17:34:17'),
(660, '', NULL, '/public/errors/404.php', NULL, NULL, NULL, '2026-05-03 17:40:49'),
(661, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-05-03 17:57:25'),
(662, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 17:57:25'),
(663, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-05-03 17:57:29'),
(664, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 17:57:29'),
(665, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 17:57:45'),
(666, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/', NULL, NULL, '2026-05-03 17:57:47'),
(667, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-03 17:57:48'),
(668, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-05-03 17:57:49'),
(669, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-05-03 18:06:07'),
(670, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 18:06:07'),
(671, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-05-03 18:06:10'),
(672, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-05-03 18:06:10'),
(673, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-05-03 18:09:32'),
(674, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-05-03 18:09:33'),
(675, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:09:39'),
(676, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 18:09:40'),
(677, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:09:46'),
(678, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-05-03 18:09:47'),
(679, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-05-03 18:12:20'),
(680, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-05-03 18:12:21'),
(681, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-05-03 18:12:29'),
(682, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-05-03 18:12:29'),
(683, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-05-03 18:12:38'),
(684, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-05-03 18:12:38'),
(685, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-05-03 18:17:23'),
(686, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-05-03 18:17:24'),
(687, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:17:37'),
(688, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 18:17:37'),
(689, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=users', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:17:44'),
(690, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/uploads/avatars/avatar_68f5810003ae9.jpg', 'http://localhost/HIGH-Q/admin/index.php?pages=users', NULL, NULL, '2026-05-03 18:17:44'),
(691, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=roles', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:17:47'),
(692, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=settings', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:17:54'),
(693, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=courses', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:17:58'),
(694, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=tutors', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:00'),
(695, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payments', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:03'),
(696, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=post', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:05'),
(697, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=comments', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:07'),
(698, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=chat', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:09'),
(699, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=create_payment_link', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:11'),
(700, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=audit_logs', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:16'),
(701, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=appointments', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:18'),
(702, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:20'),
(703, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=sentinel', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:22'),
(704, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=automator', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:24'),
(705, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=trap', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:25'),
(706, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=ai_assistant', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:27'),
(707, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payment', 'http://localhost/HIGH-Q/admin/index.php?pages=users', 1, NULL, '2026-05-03 18:18:34'),
(708, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=appointments', 'http://localhost/HIGH-Q/admin/index.php?pages=payment', 1, NULL, '2026-05-03 18:18:44'),
(709, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-05-03 19:57:54'),
(710, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/program-single.php?slug=jamb', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-05-03 19:59:14'),
(711, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/program-single.php?slug=jamb', NULL, NULL, '2026-05-03 19:59:29'),
(712, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-03 19:59:29'),
(713, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-05-03 20:02:59'),
(714, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 20:03:00'),
(715, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=tutors', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-05-03 20:03:04'),
(716, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-05-03 20:04:39'),
(717, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/post.php?id=1', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-05-03 20:06:51'),
(718, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/community.php', 'http://localhost/HIGH-Q/post.php?id=1', NULL, NULL, '2026-05-03 20:07:05'),
(719, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/community.php', NULL, NULL, '2026-05-03 20:07:36'),
(720, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-03 20:07:37'),
(721, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php?step=2&type=postutme', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-05-03 20:09:42'),
(722, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php?step=1', 'http://localhost/HIGH-Q/register-new.php?step=2&type=postutme', NULL, NULL, '2026-05-03 20:09:51'),
(723, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php?step=2&type=jamb', 'http://localhost/HIGH-Q/register-new.php?step=1', NULL, NULL, '2026-05-03 20:09:55'),
(724, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payments', 'http://localhost/HIGH-Q/admin/index.php?pages=tutors', 1, NULL, '2026-05-03 20:13:34'),
(725, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payments', 'http://localhost/HIGH-Q/admin/index.php?pages=payments', 1, NULL, '2026-05-03 20:14:57'),
(726, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payments', 'http://localhost/HIGH-Q/admin/index.php?pages=payments', 1, NULL, '2026-05-03 20:14:58'),
(727, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payments', 'http://localhost/HIGH-Q/admin/index.php?pages=payments', 1, NULL, '2026-05-03 20:14:58'),
(728, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payments', 'http://localhost/HIGH-Q/admin/index.php?pages=payments', 1, NULL, '2026-05-03 20:14:58'),
(729, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/receipt.php?ref=JAMB-20260503-7d57a1', NULL, NULL, '2026-05-03 20:15:10'),
(730, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/exams.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-05-03 20:15:18'),
(731, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/index.php?pages=payments', 1, NULL, '2026-05-03 20:21:09'),
(732, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-03 20:21:09'),
(733, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=payments', 'http://localhost/HIGH-Q/admin/index.php?pages=dashboard', 1, NULL, '2026-05-03 20:23:10'),
(734, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic', 'http://localhost/HIGH-Q/admin/index.php?pages=payments', 1, NULL, '2026-05-03 20:23:25'),
(735, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=academic&action=export_single&id=2', 'http://localhost/HIGH-Q/admin/index.php?pages=academic', 1, NULL, '2026-05-03 20:24:08'),
(736, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', NULL, NULL, NULL, '2026-05-08 22:28:30'),
(737, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/exams.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-05-08 22:28:38'),
(738, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/about.php', NULL, NULL, NULL, '2026-05-09 12:52:32'),
(739, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-09 12:52:33'),
(740, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-05-09 12:53:04'),
(741, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-09 12:53:04'),
(742, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/admin/index.php?pages=dashboard', 'http://localhost/HIGH-Q/admin/login.php', 1, NULL, '2026-05-09 12:55:51'),
(743, '::1', NULL, '/HIGH-Q/', NULL, NULL, NULL, '2026-05-09 12:55:51'),
(744, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/index.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-05-09 13:14:16'),
(745, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/index.php', NULL, NULL, '2026-05-09 13:15:36'),
(746, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-09 13:15:37'),
(747, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/programs.php', 'http://localhost/HIGH-Q/about.php', NULL, NULL, '2026-05-09 13:15:41'),
(748, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-09 13:15:41'),
(749, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/privacy.php', 'http://localhost/HIGH-Q/programs.php', NULL, NULL, '2026-05-09 13:15:56'),
(750, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/terms.php', 'http://localhost/HIGH-Q/privacy.php', NULL, NULL, '2026-05-09 13:16:11'),
(751, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/news.php', 'http://localhost/HIGH-Q/terms.php', NULL, NULL, '2026-05-09 13:16:18'),
(752, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-09 13:16:18'),
(753, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/community.php', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-05-09 13:16:21'),
(754, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/community.php', 'http://localhost/HIGH-Q/news.php', NULL, NULL, '2026-05-09 13:16:46'),
(755, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/register-new.php', 'http://localhost/HIGH-Q/community.php', NULL, NULL, '2026-05-09 13:17:02'),
(756, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-09 13:17:03');
INSERT INTO `ip_logs` (`id`, `ip`, `user_agent`, `path`, `referer`, `user_id`, `headers`, `created_at`) VALUES
(757, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/contact.php', 'http://localhost/HIGH-Q/register-new.php', NULL, NULL, '2026-05-09 13:17:04'),
(758, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-09 13:17:04'),
(759, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-05-09 17:33:19'),
(760, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-09 17:33:19'),
(761, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/about.php', 'http://localhost/HIGH-Q/contact.php', NULL, NULL, '2026-05-09 17:35:16'),
(762, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '/HIGH-Q/assets/assets/images/library.jpg', 'http://localhost/HIGH-Q/assets/css/public.css', NULL, NULL, '2026-05-09 17:35:17');

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
-- Table structure for table `mac_blocklist`
--

CREATE TABLE `mac_blocklist` (
  `id` int(10) UNSIGNED NOT NULL,
  `mac` varchar(128) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(80) NOT NULL,
  `title` varchar(150) NOT NULL,
  `icon` varchar(80) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 100,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `slug`, `title`, `icon`, `url`, `sort_order`, `enabled`, `created_at`, `updated_at`) VALUES
(1, 'dashboard', 'Dashboard', 'bx bxs-dashboard', 'index.php?pages=dashboard', 10, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(2, 'users', 'Manage Users', 'bx bxs-user-detail', 'index.php?pages=users', 20, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(3, 'roles', 'Roles Management', 'bx bxs-shield', 'index.php?pages=roles', 30, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(4, 'settings', 'Site Settings', 'bx bxs-cog', 'index.php?pages=settings', 40, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(5, 'courses', 'Courses', 'bx bxs-book', 'index.php?pages=courses', 50, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(6, 'tutors', 'Tutors', 'bx bxs-chalkboard', 'index.php?pages=tutors', 60, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(7, 'academic', 'Academic Management', 'bx bxs-graduation', 'index.php?pages=academic', 70, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(8, 'payments', 'Payments', 'bx bxs-credit-card', 'index.php?pages=payments', 80, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(9, 'create_payment_link', 'Create Payment Link', 'bx bx-link', 'index.php?pages=payment', 90, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(10, 'icons', 'Icons', 'bx bx-image', 'index.php?pages=icons', 100, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(11, 'post', 'News / Blog', 'bx bxs-news', 'index.php?pages=post', 110, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(12, 'comments', 'Comments', 'bx bxs-comment-detail', 'index.php?pages=comments', 120, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(13, 'testimonials', 'Testimonials', 'bx bxs-quote-alt-right', 'index.php?pages=testimonials', 130, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(14, 'chat', 'Chat Support', 'bx bxs-message-dots', 'index.php?pages=chat', 140, 1, '2026-01-23 13:51:46', '2026-01-23 13:51:46'),
(15, 'audit_logs', 'Audit Logs', 'bx bxs-report', 'index.php?pages=audit_logs', 180, 1, '2026-01-23 13:51:46', '2026-04-21 02:12:58'),
(16, 'appointments', 'Appointments', 'bx bx-calendar', 'index.php?pages=appointments', 190, 1, '2026-01-23 13:51:46', '2026-04-21 02:12:58'),
(17, 'sentinel', 'Security Scan', 'bx bxs-shield-alt', 'index.php?pages=sentinel', 200, 1, '2026-01-23 13:51:46', '2026-04-21 02:12:58'),
(18, 'patcher', 'Smart Patcher', 'bx bx-wrench', '../pages/patcher.php', 210, 1, '2026-01-23 13:51:46', '2026-04-21 02:12:58'),
(19, 'automator', 'Automator', 'bx bx-cog', 'index.php?pages=automator', 220, 1, '2026-01-23 13:51:46', '2026-04-21 02:12:58'),
(20, 'trap', 'Canary Trap', 'bx bx-bug', 'index.php?pages=trap', 230, 1, '2026-01-23 13:51:46', '2026-04-21 02:12:58'),
(5035, 'ai_assistant', 'AI Assistant', 'bx bx-bot', 'index.php?pages=ai_assistant', 150, 1, '2026-04-21 02:12:58', '2026-04-21 02:12:58'),
(5036, 'ai_queue', 'AI Review Queue', 'bx bx-list-check', 'index.php?pages=ai_queue', 160, 1, '2026-04-21 02:12:58', '2026-04-21 02:12:58'),
(5037, 'ai_provider', 'AI Provider Settings', 'bx bx-slider-alt', 'index.php?pages=ai_provider', 170, 1, '2026-04-21 02:12:58', '2026-04-21 02:12:58');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `filename` varchar(512) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `filename`, `applied_at`) VALUES
(1, '2025-09-19-add-created_by-to-posts.sql', '2026-01-23 00:31:47'),
(2, '2025-09-19-create-site_settings.sql', '2026-01-23 00:31:47'),
(3, '2025-09-20-add-payments-columns.sql', '2026-01-23 00:32:16'),
(4, '2025-09-23-add-bank-details-to-site_settings.sql', '2026-01-23 00:32:16'),
(5, '2025-09-24-alter-payments-add-metadata.sql', '2026-01-23 00:32:16'),
(6, '2025-09-24-create-student-registrations.sql', '2026-01-23 00:32:16'),
(7, '2025-09-25-add-course-fields-and-icons.sql', '2026-01-23 00:32:16'),
(8, '2025-09-25-convert-icons-and-normalize-features.sql', '2026-01-23 00:32:16'),
(9, '2025-09-25-drop-unused.sql', '2026-01-23 00:32:16'),
(10, '2025-09-26-create-notifications-table.sql', '2026-01-23 00:32:16'),
(11, '2025-09-28-add-email-verification-sent-at.sql', '2026-01-23 00:32:16'),
(12, '2025-09-28-add-email-verification-to-users.sql', '2026-01-23 00:32:16'),
(13, '2025-09-28-add-maintenance-allowed-ips.sql', '2026-01-23 00:32:16'),
(14, '2025-09-29-create-ip-logs-and-mac-blocklist.sql', '2026-01-23 00:32:16'),
(15, '2025-09-30-add-categoryid-and-tags-to-posts.sql', '2026-01-23 00:32:16'),
(16, '2025-09-30-add-comments-ip.sql', '2026-01-23 00:32:32'),
(17, '2025-09-30-add-featured-image-to-posts.sql', '2026-01-23 00:32:32'),
(18, '2025-09-30-create-forum-questions.sql', '2026-01-23 00:32:32'),
(19, '2025-09-30-create-forum-replies.sql', '2026-01-23 00:32:32'),
(20, '2025-09-30-create-newsletter-subscribers.sql', '2026-01-23 00:32:33'),
(21, '2025-09-30-create-post-likes.sql', '2026-01-23 00:32:33'),
(22, '2025-10-01-create-post-likes-table.sql', '2026-01-23 00:32:33'),
(23, '2025-10-02-create-comment-likes-table.sql', '2026-01-23 00:32:33'),
(24, '2025-10-03-create-forum-replies.sql', '2026-01-23 00:32:33'),
(25, '2025-10-04-make-payments-id-autoinc.sql', '2026-01-23 00:33:28'),
(26, '2025-10-05-add-contact-tiktok-column.sql', '2026-01-23 00:33:28'),
(27, '2025-10-05-create-chat-attachments.sql', '2026-01-23 00:34:00'),
(28, '2025-10-05b-alter-chat-attachments-add-meta.sql', '2026-01-23 00:34:00'),
(29, '2025-10-06-add-allow-admin-public-view-during-maintenance.sql', '2026-01-23 00:34:00'),
(30, '2025-10-06-add-column-to-site_settings.sql', '2026-01-23 00:34:00'),
(31, '2025-10-06-add-unsubscribe-token-to-newsletter.sql', '2026-01-23 00:34:00'),
(32, '2025-10-23-add-gender-to-student_registrations_mysql.sql', '2026-01-23 00:34:00'),
(33, '2025-10-23-add-post-utme-tables.sql', '2026-01-23 00:34:00'),
(34, '2025-10-23-01-create-post-utme-registrations.sql', '2026-01-23 00:34:19'),
(35, '2025-10-23-00-create-postutme-and-payments-columns.sql', '2026-01-23 00:34:44'),
(36, '2025-10-23-00b-create-postutme-and-payments-columns_mysql.sql', '2026-01-23 00:34:44'),
(37, '2025-10-23-add-waec_serial_column_mysql.sql', '2026-01-23 00:35:02'),
(38, '2025-10-23-alter-payments-postutme.sql', '2026-01-23 00:35:02'),
(39, '2025-10-23-postutme-diagnostics.sql', '2026-01-23 00:35:18'),
(40, '2025-10-24-drop-waec_serial_no_safe.sql', '2026-01-23 00:35:34'),
(41, '2025-10-26-create-postutme-and-payments-columns_mysql.sql', '2026-01-23 00:36:07'),
(42, '2025-10-30-add-activated-at-to-payments.sql', '2026-01-23 00:36:07'),
(43, '2025-11-12-create-appointments-table.sql', '2026-01-23 00:36:07'),
(44, '2025-11-13-create-menus-table.sql', '2026-01-23 00:36:07'),
(45, '2025-12-15-add-parent-to-forum-replies.sql', '2026-01-23 00:36:07'),
(46, '2025-12-15-add-topic-to-forum-questions.sql', '2026-01-23 00:36:07'),
(47, '2025-12-15-create-forum-votes.sql', '2026-01-23 00:36:07'),
(48, '2025-12-16-add-google2fa-to-users.sql', '2026-01-23 00:36:07'),
(49, '2025-12-16-add-unique-key-to-notifications.sql', '2026-01-23 00:36:07'),
(50, '2025-12-23-remove-upserted-slugs.sql', '2026-01-23 00:36:07'),
(51, '2025-12-23-upsert-program-slugs.sql', '2026-01-23 00:36:07'),
(52, '2025-12-27-create-testimonials-table.sql', '2026-01-23 00:36:08'),
(53, '2025-12-27-create-universal-registrations.sql', '2026-01-23 00:36:08'),
(54, '_seed_icons.sql', '2026-01-23 00:36:08'),
(55, 'postutme_create_only.sql', '2026-01-23 00:36:08');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `unsubscribe_token` varchar(128) DEFAULT NULL,
  `token_created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `created_at`, `unsubscribe_token`, `token_created_at`) VALUES
(1, 'akintunde.dolapo1@gmail.com', '2026-02-09 10:42:53', '3c2149416316301ed70392af502056db6ae4f533a8403bb5', '2026-02-09 10:42:53');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `reference_id`, `is_read`, `read_at`, `created_at`) VALUES
(1, 1, 'chat', 17, 1, '2026-02-01 00:29:17', '2026-01-23 13:52:07'),
(2, 1, 'comment', 3, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(3, 1, 'comment', 4, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(4, 1, 'comment', 5, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(5, 1, 'payment', 60, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(6, 1, 'payment', 59, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(7, 1, 'payment', 58, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(8, 1, 'payment', 56, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(9, 1, 'payment', 57, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(10, 1, 'payment', 55, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(11, 1, 'payment', 54, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(12, 1, 'payment', 53, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(13, 1, 'payment', 52, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(14, 1, 'payment', 51, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(15, 1, 'payment', 50, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(16, 1, 'payment', 49, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(17, 1, 'payment', 48, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(18, 1, 'payment', 47, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(19, 1, 'payment', 46, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(20, 1, 'payment', 45, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(21, 1, 'payment', 44, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(22, 1, 'payment', 40, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(23, 1, 'payment', 32, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(24, 1, 'payment', 31, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(25, 1, 'chat', 16, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(27, 1, 'chat', 15, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(28, 1, 'chat', 14, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(29, 1, 'chat', 13, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(30, 1, 'chat', 12, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(31, 1, 'chat', 11, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(32, 1, 'chat', 10, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(33, 1, 'chat', 9, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(34, 1, 'chat', 8, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(35, 1, 'chat', 7, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(36, 1, 'chat', 6, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(37, 1, 'chat', 5, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(38, 1, 'chat', 4, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(39, 1, 'chat', 3, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(40, 1, 'chat', 2, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(41, 1, 'user', 2, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17'),
(42, 1, 'user', 1, 1, '2026-02-01 00:29:17', '2026-02-01 00:29:17');

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
  `activated_at` datetime DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL CHECK (json_valid(`metadata`)),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payer_account_name` varchar(255) DEFAULT NULL,
  `payer_account_number` varchar(100) DEFAULT NULL,
  `payer_bank_name` varchar(150) DEFAULT NULL,
  `form_fee_paid` bit(1) DEFAULT b'0',
  `tutor_fee_paid` bit(1) DEFAULT b'0',
  `registration_type` varchar(10) DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `amount`, `payment_method`, `reference`, `status`, `created_at`, `activated_at`, `gateway`, `receipt_path`, `metadata`, `confirmed_at`, `updated_at`, `payer_account_name`, `payer_account_number`, `payer_bank_name`, `form_fee_paid`, `tutor_fee_paid`, `registration_type`) VALUES
(2, NULL, 50000.00, 'bank', 'REG-20250928051557-cb3b75', 'confirmed', '2025-09-28 03:15:57', NULL, NULL, NULL, NULL, '2025-09-28 03:24:05', '2025-09-28 03:24:05', NULL, NULL, NULL, b'0', b'0', 'regular'),
(3, NULL, 50000.00, 'bank', 'REG-20250928053802-c2aabc', 'failed', '2025-09-28 03:38:02', NULL, NULL, NULL, NULL, NULL, '2025-09-28 03:56:27', NULL, NULL, NULL, b'0', b'0', 'regular'),
(4, NULL, 50000.00, 'bank', 'REG-20250928055652-9edc68', 'failed', '2025-09-28 03:56:52', NULL, NULL, NULL, NULL, NULL, '2025-09-28 13:03:14', NULL, NULL, NULL, b'0', b'0', 'regular'),
(5, NULL, 10000.00, 'bank', 'REG-20251002073822-bf3a66', 'failed', '2025-10-02 05:38:22', NULL, NULL, NULL, NULL, NULL, '2025-10-03 17:31:39', NULL, NULL, NULL, b'0', b'0', 'regular'),
(6, NULL, 10000.00, 'bank', 'REG-20251002225109-b99141', 'failed', '2025-10-02 20:51:09', NULL, NULL, NULL, NULL, NULL, '2025-10-03 17:31:52', NULL, NULL, NULL, b'0', b'0', 'regular'),
(7, NULL, 15000.00, 'bank', 'REG-20251003221807-bb988d', 'confirmed', '2025-10-03 20:18:07', NULL, NULL, NULL, NULL, '2025-10-04 10:13:03', '2025-10-04 10:13:03', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'regular'),
(8, NULL, 10000.00, 'bank', 'REG-20251004121443-f292cf', 'failed', '2025-10-04 10:14:43', NULL, NULL, NULL, NULL, NULL, '2025-10-04 13:35:52', NULL, NULL, NULL, b'0', b'0', 'regular'),
(9, NULL, 10000.00, 'bank', 'REG-20251004122046-4b79ae', 'failed', '2025-10-04 10:20:46', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:29', NULL, NULL, NULL, b'0', b'0', 'regular'),
(10, NULL, 20000.00, 'paystack', 'pay_68e126bf6cf71', 'failed', '2025-10-04 13:53:03', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:32', NULL, NULL, NULL, b'0', b'0', 'regular'),
(11, NULL, 20000.00, 'paystack', 'pay_68e1279a8dca6', 'failed', '2025-10-04 13:56:42', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:35', NULL, NULL, NULL, b'0', b'0', 'regular'),
(12, NULL, 20000.00, 'paystack', 'pay_68e1288b622bf', 'failed', '2025-10-04 14:00:43', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:38', NULL, NULL, NULL, b'0', b'0', 'regular'),
(13, NULL, 20000.00, 'paystack', 'pay_68e12be78fac4', 'failed', '2025-10-04 14:15:03', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:42', NULL, NULL, NULL, b'0', b'0', 'regular'),
(14, NULL, 20000.00, 'paystack', 'pay_68e13dfddd6a4', 'failed', '2025-10-04 15:32:13', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:45', NULL, NULL, NULL, b'0', b'0', 'regular'),
(15, NULL, 20000.00, 'paystack', 'pay_68e174476aa38', 'failed', '2025-10-04 19:23:51', NULL, NULL, NULL, NULL, NULL, '2025-10-04 20:03:51', NULL, NULL, NULL, b'0', b'0', 'regular'),
(16, NULL, 20000.00, 'paystack', 'pay_68e17a2adc6b5', 'failed', '2025-10-04 19:48:58', NULL, NULL, NULL, NULL, NULL, '2025-10-04 21:07:43', NULL, NULL, NULL, b'0', b'0', 'regular'),
(17, NULL, 20000.00, 'paystack', 'pay_68e17ab28863b', 'failed', '2025-10-04 19:51:14', NULL, NULL, NULL, NULL, NULL, '2025-10-04 21:07:46', NULL, NULL, NULL, b'0', b'0', 'regular'),
(18, NULL, 20000.00, 'paystack', 'pay_68e17afe40072', 'failed', '2025-10-04 19:52:30', NULL, NULL, NULL, NULL, NULL, '2025-10-04 21:07:47', NULL, NULL, NULL, b'0', b'0', 'regular'),
(19, NULL, 20000.00, 'paystack', 'pay_68e17b4559ed1', 'confirmed', '2025-10-04 19:53:41', NULL, NULL, NULL, NULL, '2025-10-04 20:05:16', '2025-10-04 20:05:16', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'regular'),
(20, NULL, 15000.00, 'bank', 'REG-20251006022615-7746b4', 'confirmed', '2025-10-06 00:26:15', NULL, NULL, NULL, NULL, '2025-10-06 00:26:36', '2025-10-06 00:26:36', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'regular'),
(21, NULL, 0.00, 'bank', 'REG-20251016123342-2c2329', 'confirmed', '2025-10-16 10:33:42', NULL, NULL, NULL, NULL, '2025-10-16 10:35:53', '2025-10-16 10:35:53', NULL, NULL, NULL, b'0', b'0', 'regular'),
(22, NULL, 82500.00, 'bank', 'REG-20251016133123-7ea7be', 'failed', '2025-10-16 11:31:23', NULL, NULL, NULL, '{\"fixed_programs\":[7],\"varies_programs\":[6]}', NULL, '2025-10-16 11:33:31', NULL, NULL, NULL, b'0', b'0', 'regular'),
(23, NULL, 20000.00, 'bank_transfer', 'pay_68f0d828aaa47', 'failed', '2025-10-16 11:34:00', NULL, NULL, NULL, NULL, NULL, '2025-11-13 06:43:20', NULL, NULL, NULL, b'0', b'0', 'regular'),
(24, NULL, 20000.00, 'bank_transfer', 'pay_68f577cb4de9d', 'confirmed', '2025-10-19 23:44:11', NULL, NULL, NULL, NULL, '2025-10-25 22:13:06', '2025-10-25 22:13:06', NULL, NULL, NULL, b'0', b'0', 'regular'),
(25, NULL, 91500.00, 'bank', 'REG-20251022164804-a36e13', 'confirmed', '2025-10-22 14:48:04', NULL, NULL, NULL, '{\"fixed_programs\":[3,7],\"varies_programs\":[]}', NULL, '2025-10-22 14:52:01', NULL, NULL, NULL, b'0', b'0', 'regular'),
(26, NULL, 82500.00, 'bank', 'REG-20251024215305-d80bba', 'confirmed', '2025-10-24 19:53:05', NULL, NULL, NULL, '{\"fixed_programs\":[7],\"varies_programs\":[]}', '2025-10-25 19:37:48', '2025-10-25 19:37:48', NULL, NULL, NULL, b'0', b'0', 'regular'),
(27, NULL, 20000.00, 'bank_transfer', 'pay_68fccb0363f3c', 'confirmed', '2025-10-25 13:05:07', NULL, NULL, NULL, NULL, '2025-10-25 13:05:22', '2025-10-25 13:05:22', NULL, NULL, NULL, b'0', b'0', 'regular'),
(28, NULL, 1023.28, 'bank', 'PTU-20251029160118-c0e751', 'confirmed', '2025-10-29 15:01:18', NULL, NULL, NULL, NULL, '2025-10-30 06:48:25', '2025-10-30 06:48:25', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(29, NULL, 1166.88, 'bank', 'PTU-20251029160628-238e26', 'confirmed', '2025-10-29 15:06:28', NULL, NULL, NULL, NULL, '2025-10-30 06:48:22', '2025-10-30 06:48:22', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(30, NULL, 1012.66, 'bank', 'PTU-20251029161129-3179df', 'confirmed', '2025-10-29 15:11:29', NULL, NULL, NULL, NULL, '2025-10-30 06:47:37', '2025-10-30 06:47:37', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(31, NULL, 1121.55, 'bank', 'PTU-20251029161942-527ab0', 'confirmed', '2025-10-29 15:19:42', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":1000,\"tutor_fee\":0,\"service_charge\":121.55},\"total\":1121.55,\"registration_type\":\"postutme\"}', '2025-10-30 06:47:34', '2025-10-30 06:47:34', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(32, NULL, 1044.56, 'bank', 'PTU-20251029184430-508fa9', 'confirmed', '2025-10-29 17:44:30', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"44.56\"},\"total\":\"1044.56\",\"registration_type\":\"postutme\"}', '2025-10-30 06:47:31', '2025-10-30 06:47:31', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(33, NULL, 1080.60, 'bank', 'PTU-20251030140609-552912', 'failed', '2025-10-30 13:06:09', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"80.60\"},\"total\":\"1080.60\",\"registration_type\":\"postutme\"}', NULL, '2025-11-02 04:02:30', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(34, NULL, 10000.00, 'bank', 'ADMIN-20251030222318-86ad9e86', 'failed', '2025-10-30 21:23:18', NULL, NULL, NULL, '{\"email_to\":\"akintunde.dolapo1@gmail.com\",\"message\":\"OIEWFKkm dek, ieoimed\",\"emailed\":false,\"created_by\":1}', NULL, '2025-11-02 04:02:28', NULL, NULL, NULL, b'0', b'0', 'regular'),
(35, NULL, 20000.00, 'bank', 'ADMIN-20251030222601-f1649a0c', 'failed', '2025-10-30 21:26:01', NULL, NULL, NULL, '{\"email_to\":\"akintunde.dolapo1@gmail.com\",\"message\":\"eniowejnwee\",\"emailed\":true,\"created_by\":1}', NULL, '2025-11-02 04:02:24', NULL, NULL, NULL, b'0', b'0', 'regular'),
(36, NULL, 20000.00, 'bank', 'ADMIN-20251031000438-2c81ffac', 'failed', '2025-10-30 23:04:38', NULL, NULL, NULL, '{\"email_to\":\"akintunde.dolapo1@gmail.com\",\"message\":\"sdkldsk\",\"base_amount\":20000,\"surcharge\":[],\"surcharge_amount\":0,\"emailed\":true,\"created_by\":1}', NULL, '2025-11-02 04:02:22', NULL, NULL, NULL, b'0', b'0', 'regular'),
(37, NULL, 1147.09, 'bank', 'PTU-20251102153505-fda105', 'failed', '2025-11-02 14:35:05', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"147.09\"},\"total\":\"1147.09\",\"registration_type\":\"postutme\"}', NULL, '2025-11-13 06:43:12', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(38, NULL, 1143.22, 'bank', 'PTU-20251103094246-b86705', 'failed', '2025-11-03 08:42:46', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"143.22\"},\"total\":\"1143.22\",\"registration_type\":\"postutme\"}', NULL, '2025-11-13 06:43:09', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(39, NULL, 1163.56, 'bank', 'PTU-20251103094413-b1574c', 'failed', '2025-11-03 08:44:13', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"163.56\"},\"total\":\"1163.56\",\"registration_type\":\"postutme\"}', NULL, '2025-11-07 10:47:48', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(40, NULL, 1011.72, 'bank', 'PTU-20251103100215-337833', 'confirmed', '2025-11-03 09:02:15', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"11.72\"},\"total\":\"1011.72\",\"registration_type\":\"postutme\"}', '2025-11-03 13:56:21', '2025-11-03 13:56:21', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(41, NULL, 1128.07, 'bank', 'PTU-20251107104659-d2a0e6', 'failed', '2025-11-07 09:46:59', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"128.07\"},\"total\":\"1128.07\",\"registration_type\":\"postutme\"}', NULL, '2025-11-07 10:47:34', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(42, NULL, 1062.49, 'bank', 'PTU-20251107105916-8fe553', 'failed', '2025-11-07 09:59:16', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"62.49\"},\"total\":\"1062.49\",\"registration_type\":\"postutme\"}', NULL, '2025-11-07 10:47:41', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(43, NULL, 1152.96, 'bank', 'PTU-20251110235516-7776df', 'failed', '2025-11-10 22:55:16', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"152.96\"},\"total\":\"1152.96\",\"registration_type\":\"postutme\"}', NULL, '2025-11-11 22:04:04', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(44, NULL, 91500.00, 'bank', 'REG-20251111230412-17f377', 'confirmed', '2025-11-11 22:04:12', '2025-11-11 23:14:24', NULL, NULL, '{\"fixed_programs\":[3,7],\"varies_programs\":[],\"fallback\":true}', '2025-11-11 22:14:42', '2025-11-11 22:14:42', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'regular'),
(45, NULL, 20000.00, 'bank_transfer', 'pay_6940b22de412c', 'pending', '2025-12-16 01:13:17', NULL, NULL, NULL, NULL, NULL, '2025-12-16 01:13:17', NULL, NULL, NULL, b'0', b'0', 'regular'),
(46, NULL, 11500.00, 'bank', 'REG-20251218170140-8aa2fd', 'pending', '2025-12-18 16:01:40', NULL, NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-18 16:01:40', NULL, NULL, NULL, b'0', b'0', 'regular'),
(47, NULL, 82500.00, 'bank', 'REG-20251218172232-91fd99', 'pending', '2025-12-18 16:22:32', NULL, NULL, NULL, '{\"fixed_programs\":[7],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-18 16:22:32', NULL, NULL, NULL, b'0', b'0', 'regular'),
(48, NULL, 10500.00, 'bank', 'REG-20251219173015-298cd5', 'pending', '2025-12-19 16:30:15', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:30:15', NULL, NULL, NULL, b'0', b'0', 'regular'),
(49, NULL, 10500.00, 'bank', 'REG-20251219173036-8bc01f', 'pending', '2025-12-19 16:30:36', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:30:36', NULL, NULL, NULL, b'0', b'0', 'regular'),
(50, NULL, 10500.00, 'bank', 'REG-20251219173050-f0efdb', 'pending', '2025-12-19 16:30:50', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:30:50', NULL, NULL, NULL, b'0', b'0', 'regular'),
(51, NULL, 10500.00, 'bank', 'REG-20251219173123-ac1151', 'pending', '2025-12-19 16:31:23', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:31:23', NULL, NULL, NULL, b'0', b'0', 'regular'),
(52, NULL, 10500.00, 'bank', 'REG-20251219173619-34c556', 'pending', '2025-12-19 16:36:19', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:36:19', NULL, NULL, NULL, b'0', b'0', 'regular'),
(53, NULL, 1117.17, 'bank', 'PTU-20251219174243-a32d9a', 'pending', '2025-12-19 16:42:43', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"117.17\"},\"total\":\"1117.17\",\"registration_type\":\"postutme\"}', NULL, '2025-12-19 16:42:43', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(54, NULL, 1021.89, 'bank', 'PTU-20251219174404-084b1c', 'pending', '2025-12-19 16:44:04', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"21.89\"},\"total\":\"1021.89\",\"registration_type\":\"postutme\"}', NULL, '2025-12-19 16:44:04', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(55, NULL, 10500.00, 'bank', 'REG-20251219175107-cab16a', 'pending', '2025-12-19 16:51:07', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[]}', NULL, '2025-12-19 16:51:07', NULL, NULL, NULL, b'0', b'0', 'regular'),
(56, NULL, 11500.00, 'bank', 'REG-20251223171054-ea892c', 'pending', '2025-12-23 16:10:54', NULL, NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-23 16:10:54', NULL, NULL, NULL, b'0', b'0', 'regular'),
(57, NULL, 11500.00, 'bank', 'REG-20251223171054-a8124f', 'pending', '2025-12-23 16:10:54', NULL, NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-23 16:10:54', NULL, NULL, NULL, b'0', b'0', 'regular'),
(58, NULL, 11500.00, 'bank', 'REG-20251223171055-dc753f', 'pending', '2025-12-23 16:10:55', NULL, NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-23 16:10:55', NULL, NULL, NULL, b'0', b'0', 'regular'),
(59, NULL, 11500.00, 'bank', 'REG-20251223171056-46c03e', 'pending', '2025-12-23 16:10:56', '2025-12-23 17:10:58', NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-23 16:10:58', NULL, NULL, NULL, b'0', b'0', 'regular'),
(60, NULL, 12500.00, 'bank', 'REG-20251223201954-841091', 'confirmed', '2025-12-23 19:19:54', '2025-12-23 20:20:00', NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', '2025-12-23 19:21:44', '2025-12-23 19:21:44', NULL, NULL, NULL, b'0', b'0', 'regular'),
(61, NULL, 0.00, 'bank', 'REG-20251223202237-faae72', '', '2025-12-23 19:22:37', '2025-12-23 20:22:40', NULL, NULL, '{\"fixed_programs\":[],\"varies_programs\":[5],\"fallback\":true}', NULL, '2025-12-23 19:23:15', 'Opeyemi Micheal Osuntogun', '7059451671', 'opay', b'0', b'0', 'regular'),
(62, NULL, 12500.00, 'online', 'JAMB-20260421-149995', 'confirmed', '2026-04-21 04:49:50', '2026-04-21 05:49:51', NULL, NULL, '{\"program_type\":\"jamb\",\"registration_id\":1,\"email\":\"akintunde.dolapo1@gmail.com\",\"phone\":\"+2347082184560\",\"name\":\"Dolapo Elisha\"}', '2026-04-21 04:50:08', '2026-04-21 04:50:08', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'jamb'),
(63, NULL, 12500.00, 'online', 'JAMB-20260503-7d57a1', 'confirmed', '2026-05-03 19:12:45', '2026-05-03 20:12:46', NULL, NULL, '{\"program_type\":\"jamb\",\"registration_id\":2,\"email\":\"akintunde.dolapo1@gmail.com\",\"phone\":\"+2347082184560\",\"name\":\"Dolapo Elisha\"}', '2026-05-03 19:14:14', '2026-05-03 19:14:14', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'jamb');

-- --------------------------------------------------------

--
-- Table structure for table `payments_backup`
--

CREATE TABLE `payments_backup` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activated_at` datetime DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL CHECK (json_valid(`metadata`)),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payer_account_name` varchar(255) DEFAULT NULL,
  `payer_account_number` varchar(100) DEFAULT NULL,
  `payer_bank_name` varchar(150) DEFAULT NULL,
  `form_fee_paid` bit(1) DEFAULT b'0',
  `tutor_fee_paid` bit(1) DEFAULT b'0',
  `registration_type` varchar(10) DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments_backup`
--

INSERT INTO `payments_backup` (`id`, `student_id`, `amount`, `payment_method`, `reference`, `status`, `created_at`, `activated_at`, `gateway`, `receipt_path`, `metadata`, `confirmed_at`, `updated_at`, `payer_account_name`, `payer_account_number`, `payer_bank_name`, `form_fee_paid`, `tutor_fee_paid`, `registration_type`) VALUES
(2, NULL, 50000.00, 'bank', 'REG-20250928051557-cb3b75', 'confirmed', '2025-09-28 03:15:57', NULL, NULL, NULL, NULL, '2025-09-28 03:24:05', '2025-09-28 03:24:05', NULL, NULL, NULL, b'0', b'0', 'regular'),
(3, NULL, 50000.00, 'bank', 'REG-20250928053802-c2aabc', 'failed', '2025-09-28 03:38:02', NULL, NULL, NULL, NULL, NULL, '2025-09-28 03:56:27', NULL, NULL, NULL, b'0', b'0', 'regular'),
(4, NULL, 50000.00, 'bank', 'REG-20250928055652-9edc68', 'failed', '2025-09-28 03:56:52', NULL, NULL, NULL, NULL, NULL, '2025-09-28 13:03:14', NULL, NULL, NULL, b'0', b'0', 'regular'),
(5, NULL, 10000.00, 'bank', 'REG-20251002073822-bf3a66', 'failed', '2025-10-02 05:38:22', NULL, NULL, NULL, NULL, NULL, '2025-10-03 17:31:39', NULL, NULL, NULL, b'0', b'0', 'regular'),
(6, NULL, 10000.00, 'bank', 'REG-20251002225109-b99141', 'failed', '2025-10-02 20:51:09', NULL, NULL, NULL, NULL, NULL, '2025-10-03 17:31:52', NULL, NULL, NULL, b'0', b'0', 'regular'),
(7, NULL, 15000.00, 'bank', 'REG-20251003221807-bb988d', 'confirmed', '2025-10-03 20:18:07', NULL, NULL, NULL, NULL, '2025-10-04 10:13:03', '2025-10-04 10:13:03', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'regular'),
(8, NULL, 10000.00, 'bank', 'REG-20251004121443-f292cf', 'failed', '2025-10-04 10:14:43', NULL, NULL, NULL, NULL, NULL, '2025-10-04 13:35:52', NULL, NULL, NULL, b'0', b'0', 'regular'),
(9, NULL, 10000.00, 'bank', 'REG-20251004122046-4b79ae', 'failed', '2025-10-04 10:20:46', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:29', NULL, NULL, NULL, b'0', b'0', 'regular'),
(10, NULL, 20000.00, 'paystack', 'pay_68e126bf6cf71', 'failed', '2025-10-04 13:53:03', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:32', NULL, NULL, NULL, b'0', b'0', 'regular'),
(11, NULL, 20000.00, 'paystack', 'pay_68e1279a8dca6', 'failed', '2025-10-04 13:56:42', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:35', NULL, NULL, NULL, b'0', b'0', 'regular'),
(12, NULL, 20000.00, 'paystack', 'pay_68e1288b622bf', 'failed', '2025-10-04 14:00:43', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:38', NULL, NULL, NULL, b'0', b'0', 'regular'),
(13, NULL, 20000.00, 'paystack', 'pay_68e12be78fac4', 'failed', '2025-10-04 14:15:03', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:42', NULL, NULL, NULL, b'0', b'0', 'regular'),
(14, NULL, 20000.00, 'paystack', 'pay_68e13dfddd6a4', 'failed', '2025-10-04 15:32:13', NULL, NULL, NULL, NULL, NULL, '2025-10-04 19:59:45', NULL, NULL, NULL, b'0', b'0', 'regular'),
(15, NULL, 20000.00, 'paystack', 'pay_68e174476aa38', 'failed', '2025-10-04 19:23:51', NULL, NULL, NULL, NULL, NULL, '2025-10-04 20:03:51', NULL, NULL, NULL, b'0', b'0', 'regular'),
(16, NULL, 20000.00, 'paystack', 'pay_68e17a2adc6b5', 'failed', '2025-10-04 19:48:58', NULL, NULL, NULL, NULL, NULL, '2025-10-04 21:07:43', NULL, NULL, NULL, b'0', b'0', 'regular'),
(17, NULL, 20000.00, 'paystack', 'pay_68e17ab28863b', 'failed', '2025-10-04 19:51:14', NULL, NULL, NULL, NULL, NULL, '2025-10-04 21:07:46', NULL, NULL, NULL, b'0', b'0', 'regular'),
(18, NULL, 20000.00, 'paystack', 'pay_68e17afe40072', 'failed', '2025-10-04 19:52:30', NULL, NULL, NULL, NULL, NULL, '2025-10-04 21:07:47', NULL, NULL, NULL, b'0', b'0', 'regular'),
(19, NULL, 20000.00, 'paystack', 'pay_68e17b4559ed1', 'confirmed', '2025-10-04 19:53:41', NULL, NULL, NULL, NULL, '2025-10-04 20:05:16', '2025-10-04 20:05:16', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'regular'),
(20, NULL, 15000.00, 'bank', 'REG-20251006022615-7746b4', 'confirmed', '2025-10-06 00:26:15', NULL, NULL, NULL, NULL, '2025-10-06 00:26:36', '2025-10-06 00:26:36', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'regular'),
(21, NULL, 0.00, 'bank', 'REG-20251016123342-2c2329', 'confirmed', '2025-10-16 10:33:42', NULL, NULL, NULL, NULL, '2025-10-16 10:35:53', '2025-10-16 10:35:53', NULL, NULL, NULL, b'0', b'0', 'regular'),
(22, NULL, 82500.00, 'bank', 'REG-20251016133123-7ea7be', 'failed', '2025-10-16 11:31:23', NULL, NULL, NULL, '{\"fixed_programs\":[7],\"varies_programs\":[6]}', NULL, '2025-10-16 11:33:31', NULL, NULL, NULL, b'0', b'0', 'regular'),
(23, NULL, 20000.00, 'bank_transfer', 'pay_68f0d828aaa47', 'failed', '2025-10-16 11:34:00', NULL, NULL, NULL, NULL, NULL, '2025-11-13 06:43:20', NULL, NULL, NULL, b'0', b'0', 'regular'),
(24, NULL, 20000.00, 'bank_transfer', 'pay_68f577cb4de9d', 'confirmed', '2025-10-19 23:44:11', NULL, NULL, NULL, NULL, '2025-10-25 22:13:06', '2025-10-25 22:13:06', NULL, NULL, NULL, b'0', b'0', 'regular'),
(25, NULL, 91500.00, 'bank', 'REG-20251022164804-a36e13', 'confirmed', '2025-10-22 14:48:04', NULL, NULL, NULL, '{\"fixed_programs\":[3,7],\"varies_programs\":[]}', NULL, '2025-10-22 14:52:01', NULL, NULL, NULL, b'0', b'0', 'regular'),
(26, NULL, 82500.00, 'bank', 'REG-20251024215305-d80bba', 'confirmed', '2025-10-24 19:53:05', NULL, NULL, NULL, '{\"fixed_programs\":[7],\"varies_programs\":[]}', '2025-10-25 19:37:48', '2025-10-25 19:37:48', NULL, NULL, NULL, b'0', b'0', 'regular'),
(27, NULL, 20000.00, 'bank_transfer', 'pay_68fccb0363f3c', 'confirmed', '2025-10-25 13:05:07', NULL, NULL, NULL, NULL, '2025-10-25 13:05:22', '2025-10-25 13:05:22', NULL, NULL, NULL, b'0', b'0', 'regular'),
(28, NULL, 1023.28, 'bank', 'PTU-20251029160118-c0e751', 'confirmed', '2025-10-29 15:01:18', NULL, NULL, NULL, NULL, '2025-10-30 06:48:25', '2025-10-30 06:48:25', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(29, NULL, 1166.88, 'bank', 'PTU-20251029160628-238e26', 'confirmed', '2025-10-29 15:06:28', NULL, NULL, NULL, NULL, '2025-10-30 06:48:22', '2025-10-30 06:48:22', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(30, NULL, 1012.66, 'bank', 'PTU-20251029161129-3179df', 'confirmed', '2025-10-29 15:11:29', NULL, NULL, NULL, NULL, '2025-10-30 06:47:37', '2025-10-30 06:47:37', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(31, NULL, 1121.55, 'bank', 'PTU-20251029161942-527ab0', 'confirmed', '2025-10-29 15:19:42', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":1000,\"tutor_fee\":0,\"service_charge\":121.55},\"total\":1121.55,\"registration_type\":\"postutme\"}', '2025-10-30 06:47:34', '2025-10-30 06:47:34', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(32, NULL, 1044.56, 'bank', 'PTU-20251029184430-508fa9', 'confirmed', '2025-10-29 17:44:30', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"44.56\"},\"total\":\"1044.56\",\"registration_type\":\"postutme\"}', '2025-10-30 06:47:31', '2025-10-30 06:47:31', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(33, NULL, 1080.60, 'bank', 'PTU-20251030140609-552912', 'failed', '2025-10-30 13:06:09', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"80.60\"},\"total\":\"1080.60\",\"registration_type\":\"postutme\"}', NULL, '2025-11-02 04:02:30', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(34, NULL, 10000.00, 'bank', 'ADMIN-20251030222318-86ad9e86', 'failed', '2025-10-30 21:23:18', NULL, NULL, NULL, '{\"email_to\":\"akintunde.dolapo1@gmail.com\",\"message\":\"OIEWFKkm dek, ieoimed\",\"emailed\":false,\"created_by\":1}', NULL, '2025-11-02 04:02:28', NULL, NULL, NULL, b'0', b'0', 'regular'),
(35, NULL, 20000.00, 'bank', 'ADMIN-20251030222601-f1649a0c', 'failed', '2025-10-30 21:26:01', NULL, NULL, NULL, '{\"email_to\":\"akintunde.dolapo1@gmail.com\",\"message\":\"eniowejnwee\",\"emailed\":true,\"created_by\":1}', NULL, '2025-11-02 04:02:24', NULL, NULL, NULL, b'0', b'0', 'regular'),
(36, NULL, 20000.00, 'bank', 'ADMIN-20251031000438-2c81ffac', 'failed', '2025-10-30 23:04:38', NULL, NULL, NULL, '{\"email_to\":\"akintunde.dolapo1@gmail.com\",\"message\":\"sdkldsk\",\"base_amount\":20000,\"surcharge\":[],\"surcharge_amount\":0,\"emailed\":true,\"created_by\":1}', NULL, '2025-11-02 04:02:22', NULL, NULL, NULL, b'0', b'0', 'regular'),
(37, NULL, 1147.09, 'bank', 'PTU-20251102153505-fda105', 'failed', '2025-11-02 14:35:05', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"147.09\"},\"total\":\"1147.09\",\"registration_type\":\"postutme\"}', NULL, '2025-11-13 06:43:12', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(38, NULL, 1143.22, 'bank', 'PTU-20251103094246-b86705', 'failed', '2025-11-03 08:42:46', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"143.22\"},\"total\":\"1143.22\",\"registration_type\":\"postutme\"}', NULL, '2025-11-13 06:43:09', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(39, NULL, 1163.56, 'bank', 'PTU-20251103094413-b1574c', 'failed', '2025-11-03 08:44:13', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"163.56\"},\"total\":\"1163.56\",\"registration_type\":\"postutme\"}', NULL, '2025-11-07 10:47:48', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(40, NULL, 1011.72, 'bank', 'PTU-20251103100215-337833', 'confirmed', '2025-11-03 09:02:15', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"11.72\"},\"total\":\"1011.72\",\"registration_type\":\"postutme\"}', '2025-11-03 13:56:21', '2025-11-03 13:56:21', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(41, NULL, 1128.07, 'bank', 'PTU-20251107104659-d2a0e6', 'failed', '2025-11-07 09:46:59', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"128.07\"},\"total\":\"1128.07\",\"registration_type\":\"postutme\"}', NULL, '2025-11-07 10:47:34', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(42, NULL, 1062.49, 'bank', 'PTU-20251107105916-8fe553', 'failed', '2025-11-07 09:59:16', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"62.49\"},\"total\":\"1062.49\",\"registration_type\":\"postutme\"}', NULL, '2025-11-07 10:47:41', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(43, NULL, 1152.96, 'bank', 'PTU-20251110235516-7776df', 'failed', '2025-11-10 22:55:16', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"152.96\"},\"total\":\"1152.96\",\"registration_type\":\"postutme\"}', NULL, '2025-11-11 22:04:04', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(44, NULL, 91500.00, 'bank', 'REG-20251111230412-17f377', 'confirmed', '2025-11-11 22:04:12', '2025-11-11 23:14:24', NULL, NULL, '{\"fixed_programs\":[3,7],\"varies_programs\":[],\"fallback\":true}', '2025-11-11 22:14:42', '2025-11-11 22:14:42', 'Akintunde Dolapo Elisha', '0202029291', 'PBS', b'0', b'0', 'regular'),
(45, NULL, 20000.00, 'bank_transfer', 'pay_6940b22de412c', 'pending', '2025-12-16 01:13:17', NULL, NULL, NULL, NULL, NULL, '2025-12-16 01:13:17', NULL, NULL, NULL, b'0', b'0', 'regular'),
(46, NULL, 11500.00, 'bank', 'REG-20251218170140-8aa2fd', 'pending', '2025-12-18 16:01:40', NULL, NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-18 16:01:40', NULL, NULL, NULL, b'0', b'0', 'regular'),
(47, NULL, 82500.00, 'bank', 'REG-20251218172232-91fd99', 'pending', '2025-12-18 16:22:32', NULL, NULL, NULL, '{\"fixed_programs\":[7],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-18 16:22:32', NULL, NULL, NULL, b'0', b'0', 'regular'),
(48, NULL, 10500.00, 'bank', 'REG-20251219173015-298cd5', 'pending', '2025-12-19 16:30:15', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:30:15', NULL, NULL, NULL, b'0', b'0', 'regular'),
(49, NULL, 10500.00, 'bank', 'REG-20251219173036-8bc01f', 'pending', '2025-12-19 16:30:36', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:30:36', NULL, NULL, NULL, b'0', b'0', 'regular'),
(50, NULL, 10500.00, 'bank', 'REG-20251219173050-f0efdb', 'pending', '2025-12-19 16:30:50', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:30:50', NULL, NULL, NULL, b'0', b'0', 'regular'),
(51, NULL, 10500.00, 'bank', 'REG-20251219173123-ac1151', 'pending', '2025-12-19 16:31:23', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:31:23', NULL, NULL, NULL, b'0', b'0', 'regular'),
(52, NULL, 10500.00, 'bank', 'REG-20251219173619-34c556', 'pending', '2025-12-19 16:36:19', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-19 16:36:19', NULL, NULL, NULL, b'0', b'0', 'regular'),
(53, NULL, 1117.17, 'bank', 'PTU-20251219174243-a32d9a', 'pending', '2025-12-19 16:42:43', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"117.17\"},\"total\":\"1117.17\",\"registration_type\":\"postutme\"}', NULL, '2025-12-19 16:42:43', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(54, NULL, 1021.89, 'bank', 'PTU-20251219174404-084b1c', 'pending', '2025-12-19 16:44:04', NULL, NULL, NULL, '{\"components\":{\"post_form_fee\":\"1000.00\",\"tutor_fee\":\"0.00\",\"service_charge\":\"21.89\"},\"total\":\"1021.89\",\"registration_type\":\"postutme\"}', NULL, '2025-12-19 16:44:04', NULL, NULL, NULL, b'1', b'1', 'postutme'),
(55, NULL, 10500.00, 'bank', 'REG-20251219175107-cab16a', 'pending', '2025-12-19 16:51:07', NULL, NULL, NULL, '{\"fixed_programs\":[8],\"varies_programs\":[]}', NULL, '2025-12-19 16:51:07', NULL, NULL, NULL, b'0', b'0', 'regular'),
(56, NULL, 11500.00, 'bank', 'REG-20251223171054-ea892c', 'pending', '2025-12-23 16:10:54', NULL, NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-23 16:10:54', NULL, NULL, NULL, b'0', b'0', 'regular'),
(57, NULL, 11500.00, 'bank', 'REG-20251223171054-a8124f', 'pending', '2025-12-23 16:10:54', NULL, NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-23 16:10:54', NULL, NULL, NULL, b'0', b'0', 'regular'),
(58, NULL, 11500.00, 'bank', 'REG-20251223171055-dc753f', 'pending', '2025-12-23 16:10:55', NULL, NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-23 16:10:55', NULL, NULL, NULL, b'0', b'0', 'regular'),
(59, NULL, 11500.00, 'bank', 'REG-20251223171056-46c03e', 'pending', '2025-12-23 16:10:56', '2025-12-23 17:10:58', NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', NULL, '2025-12-23 16:10:58', NULL, NULL, NULL, b'0', b'0', 'regular'),
(60, NULL, 12500.00, 'bank', 'REG-20251223201954-841091', 'confirmed', '2025-12-23 19:19:54', '2025-12-23 20:20:00', NULL, NULL, '{\"fixed_programs\":[3],\"varies_programs\":[],\"fallback\":true}', '2025-12-23 19:21:44', '2025-12-23 19:21:44', NULL, NULL, NULL, b'0', b'0', 'regular'),
(61, NULL, 0.00, 'bank', 'REG-20251223202237-faae72', '', '2025-12-23 19:22:37', '2025-12-23 20:22:40', NULL, NULL, '{\"fixed_programs\":[],\"varies_programs\":[5],\"fallback\":true}', NULL, '2025-12-23 19:23:15', 'Opeyemi Micheal Osuntogun', '7059451671', 'opay', b'0', b'0', 'regular');

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
  `category_id` int(11) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(1024) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `author_id`, `created_by`, `title`, `slug`, `category_id`, `tags`, `excerpt`, `featured_image`, `content`, `category`, `status`, `is_featured`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Introducing the HIGH Q SOLID ACADEMY Website!', 'introducing-the-high-q-solid-academy-website', 2, 'academy, education, online-learning, tutorials, support, launch', 'We are proud to announce the official launch of the HIGH Q SOLID ACADEMY Website — a modern, dynamic, and learner-focused platform built to transform how studen...', 'http://localhost/HIGH-Q/uploads/posts/post_68dc442e4c4c0.jpeg', 'We are proud to announce the official launch of the HIGH Q SOLID ACADEMY Website — a modern, dynamic, and learner-focused platform built to transform how students access knowledge, interact with mentors, and grow their academic potential.\r\n\r\nAt HIGH Q SOLID ACADEMY, we believe that learning should be accessible, interactive, and supportive for every student, no matter where they are. Our new website brings this vision to life with an intuitive design, robust features, and tools that simplify the learning journey from start to finish.\r\n\r\n🌟 Key Features of the New Site\r\n\r\nHere’s what you’ll discover when you visit the platform:\r\n\r\n1. Seamless Registration\r\nNo more long processes or paperwork. Our digital registration system makes it effortless to sign up and join the academy. Whether you’re a first-time student or a returning learner, enrollment is just a few clicks away.\r\n\r\n2. Online Tutorials and Learning Resources\r\nStudents can access structured lessons, study materials, and online tutorials designed by seasoned educators. The platform supports anytime, anywhere learning, ensuring that you’re never limited by time or place when it comes to education.\r\n\r\n3. Real-Time Chat Support\r\nLearning doesn’t have to be a lonely journey. Our chat support connects you directly with the academy team to answer your questions, guide you through technical challenges, or provide academic support instantly.\r\n\r\n4. Personalized Student Dashboard\r\nEvery learner gets their own digital hub where they can track courses, assignments, progress, and upcoming events. It’s like having a personal assistant that keeps your academic life organized.\r\n\r\n5. Community Interaction & Collaboration\r\nWe’ve built in ways for students to connect, collaborate, and engage with one another. From discussion spaces to interactive activities, the site is designed to foster a true community of learning.\r\n\r\n💡 Why This Matters\r\n\r\nThe world is moving fast, and education must evolve with it. By combining technology, accessibility, and human support, HIGH Q SOLID ACADEMY is setting a new standard for what digital learning can be. Our platform is not just about delivering lessons; it’s about creating an ecosystem of growth where students feel empowered, connected, and prepared for the future.\r\n\r\nWe know that every learner is unique. That’s why we’ve built the site to be flexible, engaging, and responsive to individual needs. Whether you’re preparing for exams, exploring new subjects, or seeking academic mentorship, our platform adapts to your journey.\r\n\r\n🎯 What’s Next?\r\n\r\nThis launch is only the beginning. Over the coming months, we’ll be rolling out even more exciting features, including advanced learning analytics, gamified progress tracking, and interactive workshops. Our mission is to ensure that every student feels supported, motivated, and equipped to succeed.\r\n\r\n🚀 Join Us Today\r\n\r\nThe HIGH Q SOLID ACADEMY Website is now live and ready for you. We invite students, parents, and educators to explore the platform and experience the future of learning firsthand.\r\n\r\n👉 Visit the site today, register, and take your education to the next level with HIGH Q SOLID ACADEMY!\r\n\r\nTogether, we’re building more than a website. We’re building a movement.', NULL, 'published', 0, NULL, '2025-09-30 20:57:18', '2025-10-06 12:03:20'),
(3, 1, NULL, 'Test Newsletter Post 2025-10-06 10:13', 'test-newsletter-post-2025-10-06-10:13', NULL, NULL, 'Test excerpt for newsletter.', NULL, 'This is a test post for newsletter delivery.', NULL, 'published', 0, NULL, '2025-10-06 08:13:20', '2025-10-06 08:13:20');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `session_id`, `ip`, `created_at`) VALUES
(2, 1, '1cef4ssnim9nfimdp00k07j6od', NULL, '2026-02-09 21:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `post_utme_registrations`
--

CREATE TABLE `post_utme_registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `institution` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `parent_phone` varchar(50) DEFAULT NULL,
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
  `jamb_registration_number` varchar(50) DEFAULT NULL,
  `jamb_score` int(11) DEFAULT NULL,
  `jamb_subjects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jamb_subjects`)),
  `course_first_choice` varchar(255) DEFAULT NULL,
  `course_second_choice` varchar(255) DEFAULT NULL,
  `institution_first_choice` varchar(255) DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `father_phone` varchar(20) DEFAULT NULL,
  `father_email` varchar(255) DEFAULT NULL,
  `father_occupation` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `mother_phone` varchar(20) DEFAULT NULL,
  `mother_occupation` varchar(255) DEFAULT NULL,
  `primary_school` varchar(255) DEFAULT NULL,
  `primary_year_ended` year(4) DEFAULT NULL,
  `secondary_school` varchar(255) DEFAULT NULL,
  `secondary_year_ended` year(4) DEFAULT NULL,
  `exam_type` enum('WAEC','NECO','GCE') DEFAULT NULL,
  `candidate_name` varchar(255) DEFAULT NULL,
  `exam_number` varchar(50) DEFAULT NULL,
  `waec_serial` varchar(100) DEFAULT NULL,
  `exam_year_month` varchar(20) DEFAULT NULL,
  `olevel_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`olevel_results`)),
  `passport_photo` varchar(255) DEFAULT NULL,
  `payment_status` varchar(10) DEFAULT 'pending',
  `form_fee_paid` tinyint(1) DEFAULT 0,
  `tutor_fee_paid` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `waec_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_utme_waec_serial_no_backup`
--

CREATE TABLE `post_utme_waec_serial_no_backup` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `waec_serial_no` varchar(255) DEFAULT NULL,
  `backed_up_at` datetime NOT NULL
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `max_count`, `created_at`) VALUES
(1, 'Admin', 'admin', 2, '2025-08-29 08:46:25'),
(2, 'Sub-Admin', 'sub-admin', 3, '2025-08-29 08:46:25'),
(3, 'Moderator', 'moderator', NULL, '2025-08-29 08:46:25'),
(4, 'Applicant', 'applicant', NULL, '2025-10-20 00:23:28');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `menu_slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `menu_slug`) VALUES
(17, 3, 'dashboard'),
(18, 3, 'post'),
(19, 3, 'comments'),
(20, 3, 'chat'),
(26, 2, 'dashboard'),
(27, 2, 'courses'),
(28, 2, 'tutors'),
(29, 2, 'students'),
(30, 2, 'payments'),
(31, 1, 'dashboard'),
(32, 1, 'users'),
(33, 1, 'roles'),
(34, 1, 'settings'),
(35, 1, 'courses'),
(36, 1, 'tutors'),
(37, 1, 'students'),
(38, 1, 'payments'),
(39, 1, 'post'),
(40, 1, 'comments'),
(41, 1, 'chat'),
(42, 1, 'create_payment_link'),
(43, 1, 'icons'),
(44, 1, 'audit_logs'),
(45, 1, 'appointments'),
(46, 1, 'academic'),
(47, 1, 'sentinel'),
(48, 1, 'patcher'),
(49, 1, 'automator'),
(50, 1, 'trap'),
(51, 1, 'testimonials'),
(52, 1, 'ai_assistant'),
(53, 1, 'ai_queue'),
(54, 1, 'ai_provider');

-- --------------------------------------------------------

--
-- Table structure for table `security_scans`
--

CREATE TABLE `security_scans` (
  `id` int(11) NOT NULL,
  `scan_type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `threat_count` int(11) NOT NULL DEFAULT 0,
  `report_file` varchar(255) DEFAULT NULL,
  `scan_date` datetime NOT NULL DEFAULT current_timestamp(),
  `duration` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `updated_at`) VALUES
(1, 'system_settings', '{\n    \"site\": {\n        \"name\": \"HIGH Q SOLID ACADEMY\",\n        \"tagline\": \"\",\n        \"logo\": \"\",\n        \"bank_name\": \"Moniepoint PBS\",\n        \"bank_account_name\": \"High Q Solid Academy\",\n        \"bank_account_number\": \"5017167271\",\n        \"vision\": \"\",\n        \"about\": \"\"\n    },\n    \"contact\": {\n        \"phone\": \"+2348072088794\",\n        \"email\": \"highqsolidacademy@gmail.com\",\n        \"address\": \"\",\n        \"facebook\": \"\",\n        \"tiktok\": \"\",\n        \"instagram\": \"https://www.instagram.com/highqsolidacademy?igsh=aXFpb2RndWRwMm5v\"\n    },\n    \"security\": {\n        \"registration\": \"1\",\n        \"email_verification\": \"1\",\n        \"verify_registration_before_payment\": \"1\",\n        \"maintenance_allowed_ips\": \"\",\n        \"enforcement_mode\": \"mac\",\n        \"maintenance\": \"0\",\n        \"two_factor\": \"0\",\n        \"comment_moderation\": \"0\",\n        \"allow_admin_public_view_during_maintenance\": \"0\"\n    },\n    \"notifications\": {\n        \"email\": \"1\",\n        \"push\": \"1\",\n        \"sms\": \"0\"\n    },\n    \"advanced\": {\n        \"ip_logging\": \"1\",\n        \"brute_force\": \"1\",\n        \"auto_backup\": \"1\",\n        \"max_login_attempts\": \"5\",\n        \"session_timeout\": \"30\",\n        \"security_scanning\": \"0\",\n        \"ssl_enforce\": \"0\"\n    }\n}', '2026-02-09 09:57:30');

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
  `maintenance_allowed_ips` varchar(1024) DEFAULT NULL,
  `allow_admin_public_view_during_maintenance` tinyint(1) NOT NULL DEFAULT 0,
  `registration` tinyint(1) DEFAULT 1,
  `email_verification` tinyint(1) DEFAULT 1,
  `two_factor` tinyint(1) DEFAULT 0,
  `comment_moderation` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `contact_tiktok` varchar(512) DEFAULT NULL,
  `new_column_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `site_name`, `tagline`, `logo_url`, `vision`, `about`, `contact_phone`, `contact_email`, `contact_address`, `contact_facebook`, `contact_twitter`, `contact_instagram`, `maintenance`, `maintenance_allowed_ips`, `allow_admin_public_view_during_maintenance`, `registration`, `email_verification`, `two_factor`, `comment_moderation`, `updated_at`, `created_at`, `bank_name`, `bank_account_name`, `bank_account_number`, `contact_tiktok`, `new_column_name`) VALUES
(1, 'HIGH Q SOLID ACADEMY', '', '', '', '', '+2348072088794', 'highqsolidacademy@gmail.com', '', '', NULL, 'https://www.instagram.com/highqsolidacademy?igsh=aXFpb2RndWRwMm5v', 0, NULL, 0, 1, 1, 0, 0, '2026-02-09 09:57:30', '2026-01-23 00:31:47', 'Moniepoint PBS', 'High Q Solid Academy', '5017167271', '', NULL);

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
  `gender` varchar(16) DEFAULT NULL,
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
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role_institution` varchar(255) DEFAULT NULL COMMENT 'e.g., "LAUTECH Engineering Student" or "Cybersecurity Professional"',
  `testimonial_text` text NOT NULL,
  `image_path` varchar(500) DEFAULT NULL COMMENT 'Optional student/graduate photo',
  `outcome_badge` varchar(100) DEFAULT NULL COMMENT 'e.g., "305 JAMB Score", "Admitted to Engineering", "Tech Job Placement"',
  `display_order` int(11) DEFAULT 0 COMMENT 'Lower numbers appear first',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `name`, `role_institution`, `testimonial_text`, `image_path`, `outcome_badge`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'ADEDUNYE KINGSLEY OLUWAPELUMI', 'Ambrose Ali University', 'I choose HQ because of the passion and zeal toward the success of every student', NULL, 'JAMB: 242', 1, 1, '2026-01-23 00:53:29', '2026-01-23 00:53:29'),
(2, 'Ayodele Joseph Teminijesu', 'Lasutech', 'Top-notch lessons! HQ is very patient and knows how to simplify even the toughest topics. The environment is conducive to learning and the focus on JAMB past questions was incredibly helpful. 10/10 recommended!', NULL, 'JAMB Success', 2, 1, '2026-01-23 00:53:29', '2026-01-23 00:53:29'),
(3, 'Fadele Oluwanifemi Abigail', 'Ladoke Akintola University Of Technology', 'At first, it was the only tutorial I\'ve been hearing people talking about. It has been said that the academy is really a high quality.', NULL, 'JAMB: 235', 3, 1, '2026-01-23 00:53:29', '2026-01-23 00:53:29'),
(4, 'Robinson Delight', 'Current Student', 'HQ tutorial is the best and they teach well', NULL, 'JAMB: 167', 4, 1, '2026-01-23 00:53:29', '2026-01-23 00:53:29'),
(5, 'Adeyemi Wahab Ayoade', 'Adekunle Ajasin University Akungba', 'I chose this tutorial because it is well-structured, engaging, and provides clear explanations that improve my understanding of the subject.', NULL, 'JAMB Success', 5, 1, '2026-01-23 00:53:29', '2026-01-23 00:53:29'),
(6, 'Ogunsanya Zainab Olayinka', 'Olabisi Onabanjo University', 'It stands out from other tutorials.', NULL, 'JAMB: 218', 6, 1, '2026-01-23 00:53:29', '2026-01-23 00:53:29');

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
  `subjects` longtext DEFAULT NULL CHECK (json_valid(`subjects`)),
  `contact_email` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `name`, `slug`, `photo`, `short_bio`, `long_bio`, `qualifications`, `subjects`, `contact_email`, `phone`, `rating`, `is_featured`, `created_at`, `updated_at`) VALUES
(6, 'Raheem Omotolani', '-aheem-motolani', 'uploads/tutors/1758822852_c59684e11458.jpeg', '6 years of Experience', 'Solid academic credentials, a passion for teaching science and technology, and a desire to motivate the next generation of students.', 'NCE ( Biology/Computer Science), AOCOED  B.Sc Ed ( Intergrated Science Education), UNILAG  TRCN Certification', '[\"Biology\",\"Computer Science\"]', NULL, NULL, NULL, 1, '2025-10-05 02:01:40', '2025-12-28 05:50:32'),
(7, 'Adewole Daniel', '-dewole-aniel', 'uploads/tutors/dan.jpg', NULL, 'Competent economist and finance specialist with strong credentials and a keen analytical spirit.', 'AAT, B.Sc,  ACA', '[\"Financial Accounting\",\"Economics\",\" Statistics\"]', NULL, NULL, NULL, 1, '2025-10-05 11:43:17', '2025-12-28 05:50:35'),
(8, 'Aina Oluwasegun', '-ina-luwasegun', NULL, '4 years of experince', 'ICAN-certified, a skilled accountant committed to providing top-notch financial education.', 'ICAN (ACA)', '[\"Accounting\"]', NULL, NULL, NULL, 1, '2025-10-05 18:01:07', '2025-12-28 05:50:37'),
(9, 'Mide Oshifisan', '-ide-shifisan', 'uploads/tutors/mide.jpg', '4 years of experince', 'UI/UX educator and creative design specialist with a love for building distinctive brands and inspiring upcoming designers.', 'B.Sc', '[\"Pro Brand Designer \\/ Design Tutor\",\"UI\\/UX Tutor\"]', NULL, NULL, NULL, 1, '2025-10-05 18:02:43', '2025-12-28 05:50:39'),
(10, 'Akintunde Oreoluwa', '-kintunde-reoluwa', 'uploads/tutors/ore.jpg', '3 years of experience', 'Committed economics teacher with a solid academic background who is passionate about developing critical thinkers.', 'B.Sc(Ed) Economics', '[\"Economics\"]', NULL, NULL, NULL, 1, '2025-10-05 18:04:41', '2025-12-28 05:50:41'),
(11, 'Atobatele Temiloluwa', '-tobatele-emiloluwa', 'uploads/tutors/temi.jpg', '3 years of experience', 'Dedicated to developing sharp business brains, this versatile educator has experience in accounting, commerce, and economics.', 'Bsc.Ed', '[\"Economics\",\"Commerce\",\" Accounting.\"]', NULL, NULL, NULL, 1, '2025-10-05 18:12:35', '2025-12-28 05:50:45'),
(12, 'Osuntogun Opeyemi', 'osuntogun-opeyemi', 'uploads/tutors/ope.jpg', '3 years of experience', 'Competent maths teacher with a solid foundation in electrical and electronic engineering.', '', '[\"Mathematics\"]', '', '', NULL, 1, '2025-10-05 23:41:19', '2025-12-28 05:50:48'),
(14, 'Sanyaolu Samuel Obanijesu', 'sanyaolu-samuel-obanijesu', '../uploads/tutors/tutor_6950c275c0756_1766900341.jpg', 'Core Science Tutor', 'A versatile science specialist dedicated to mastering the core logic of Mathematics, Further Mathematics, and the laboratory sciences to ensure students remain \'Always Ahead of Others', '', '[\"Mathematics\",\"Physics\",\"Chemistry\",\"Biology\",\"Further Mathematics\"]', '', '', NULL, 1, '2025-12-28 05:35:41', '2025-12-28 05:50:50');

-- --------------------------------------------------------

--
-- Table structure for table `universal_registrations`
--

CREATE TABLE `universal_registrations` (
  `id` int(11) NOT NULL,
  `program_type` varchar(50) NOT NULL,
  `first_name` varchar(150) NOT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `amount` decimal(12,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'online',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `universal_registrations`
--

INSERT INTO `universal_registrations` (`id`, `program_type`, `first_name`, `last_name`, `email`, `phone`, `status`, `payment_reference`, `payment_status`, `amount`, `payment_method`, `payload`, `created_at`, `updated_at`) VALUES
(1, 'jamb', 'Dolapo', 'Elisha', 'akintunde.dolapo1@gmail.com', '+2347082184560', 'pending', 'JAMB-20260421-149995', 'pending', 12500.00, 'online', '{\"program_type\":\"jamb\",\"registration_type\":\"jamb\",\"surname\":\"Akintunde\",\"first_name\":\"Dolapo\",\"last_name\":\"Elisha\",\"date_of_birth\":\"2005-01-26\",\"home_address\":\"34b Olaonipekun Avenue, Maya, Ikorodu, Lagos\",\"phone\":\"+2347082184560\",\"email\":\"akintunde.dolapo1@gmail.com\",\"nin\":\"75973483882\",\"profile_code\":\"\",\"gender\":\"male\",\"marital_status\":\"Single\",\"state_of_origin\":\"Lagos\",\"local_government\":\"Ikeja\",\"intended_course\":\"Cyber Security\",\"institution\":\"LAUTEcH\",\"jamb_subject_1\":\"Use of English\",\"jamb_subject_2\":\"Mathematics\",\"jamb_subject_3\":\"Physics\",\"jamb_subject_4\":\"chemistry\",\"education_level\":\"SS3\",\"sponsor_name\":\"Akintunde\",\"sponsor_phone\":\"+2347082184560\",\"sponsor_address\":\"34b Olaonipekun Avenue, Maya, Ikorodu, Lagos\",\"next_of_kin_name\":\"Akintunde\",\"next_of_kin_phone\":\"+2348148934812\",\"next_of_kin_address\":\"34b Olaonipekun Avenue, Maya, Ikorodu, Lagos\",\"next_of_kin_relationship\":\"Parent\",\"terms\":\"on\"}', '2026-04-21 04:49:50', '2026-04-21 04:49:50'),
(2, 'jamb', 'Dolapo', 'Elisha', 'akintunde.dolapo1@gmail.com', '+2347082184560', 'pending', 'JAMB-20260503-7d57a1', 'pending', 12500.00, 'online', '{\"program_type\":\"jamb\",\"registration_type\":\"jamb\",\"surname\":\"Akintunde\",\"first_name\":\"Dolapo\",\"last_name\":\"Elisha\",\"date_of_birth\":\"2005-01-26\",\"home_address\":\"34b Olaonipekun Avenue, Maya, Ikorodu, Lagos\",\"phone\":\"+2347082184560\",\"email\":\"akintunde.dolapo1@gmail.com\",\"nin\":\"75973483882\",\"profile_code\":\"\",\"gender\":\"male\",\"marital_status\":\"Single\",\"state_of_origin\":\"Lagos\",\"local_government\":\"Ikeja\",\"intended_course\":\"Cyber Security\",\"institution\":\"LAUTEcH\",\"jamb_subject_1\":\"Use of English\",\"jamb_subject_2\":\"Mathematics\",\"jamb_subject_3\":\"Physics\",\"jamb_subject_4\":\"chemistry\",\"education_level\":\"SS3\",\"sponsor_name\":\"Akintunde\",\"sponsor_phone\":\"+2347082184560\",\"sponsor_address\":\"34b Olaonipekun Avenue, Maya, Ikorodu, Lagos\",\"next_of_kin_name\":\"Akintunde\",\"next_of_kin_phone\":\"+2347082184560\",\"next_of_kin_address\":\"34b Olaonipekun Avenue, Maya, Ikorodu, Lagos\",\"next_of_kin_relationship\":\"Parent\",\"terms\":\"on\",\"passport_photo\":\"uploads\\/passports\\/passport_jamb_69f79e17b8943.jpg\"}', '2026-05-03 19:12:23', '2026-05-03 19:12:23');

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
  `email_verification_token` varchar(128) DEFAULT NULL,
  `email_verification_sent_at` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `onboarding_tour_pending` tinyint(1) NOT NULL DEFAULT 1,
  `onboarding_tour_started_at` datetime DEFAULT NULL,
  `onboarding_tour_completed_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `twofa_secret` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `google2fa_secret` varchar(32) DEFAULT NULL COMMENT 'Google Authenticator secret key',
  `google2fa_enabled` tinyint(1) DEFAULT 0 COMMENT 'Whether Google 2FA is enabled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `phone`, `email`, `password`, `avatar`, `email_verification_token`, `email_verification_sent_at`, `email_verified_at`, `onboarding_tour_pending`, `onboarding_tour_started_at`, `onboarding_tour_completed_at`, `is_active`, `twofa_secret`, `last_login`, `created_at`, `updated_at`, `google2fa_secret`, `google2fa_enabled`) VALUES
(1, 1, 'Akintunde Dolapo', '+2347082184560', 'akintunde.dolapo1@gmail.com', '$2y$10$sMjrVYcbmDLD4FSp8KTz3OTT41/poIWTzJIhgaQdcWK7y6d3ylL9i', NULL, NULL, NULL, NULL, 0, '2026-05-03 19:17:33', '2026-05-03 19:18:31', 1, NULL, NULL, '2025-08-31 15:22:34', '2026-05-03 17:18:31', '4KVDRIN3UI5S6LYC', 1),
(2, 3, 'MAVIS GAMING', '+2347045019083', 'akintundeibunkunoluwa31@gmail.com', '$2y$10$x4esB3tnRh4popDC/ykrGekIJLdm.oIHxyJI67jf7LMq77fUHOSoe', 'uploads/avatars/avatar_68f5810003ae9.jpg', NULL, NULL, '2025-10-20 01:23:55', 1, NULL, NULL, 1, NULL, NULL, '2025-10-20 00:23:28', '2025-12-26 17:34:42', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_action_queue`
--
ALTER TABLE `ai_action_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_action_queue_user_id` (`user_id`),
  ADD KEY `idx_ai_action_queue_status` (`status`),
  ADD KEY `idx_ai_action_queue_created_at` (`created_at`),
  ADD KEY `idx_ai_action_queue_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_ai_action_queue_executed_by` (`executed_by`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip` (`ip`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `chat_attachments`
--
ALTER TABLE `chat_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`);

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
  ADD KEY `idx_comments_post_id` (`post_id`),
  ADD KEY `idx_comments_session_id` (`session_id`);

--
-- Indexes for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_comment_like` (`comment_id`,`session_id`,`ip`),
  ADD KEY `idx_comment_id` (`comment_id`);

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
-- Indexes for table `forum_questions`
--
ALTER TABLE `forum_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forum_questions_topic` (`topic`);

--
-- Indexes for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `idx_forum_replies_parent` (`parent_id`);

--
-- Indexes for table `forum_votes`
--
ALTER TABLE `forum_votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fv_question` (`question_id`),
  ADD KEY `idx_fv_reply` (`reply_id`),
  ADD KEY `idx_fv_session` (`session_id`);

--
-- Indexes for table `icons`
--
ALTER TABLE `icons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_filename` (`filename`);

--
-- Indexes for table `ip_logs`
--
ALTER TABLE `ip_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mac_blocklist`
--
ALTER TABLE `mac_blocklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mac` (`mac`),
  ADD KEY `enabled` (`enabled`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_menus_slug` (`slug`),
  ADD KEY `idx_menus_enabled_sort` (`enabled`,`sort_order`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_notification` (`user_id`,`type`,`reference_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_type_ref` (`type`,`reference_id`);

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
-- Indexes for table `payments_backup`
--
ALTER TABLE `payments_backup`
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
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_posts_category_id` (`category_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_post_session_ip` (`post_id`,`session_id`,`ip`);

--
-- Indexes for table `post_utme_registrations`
--
ALTER TABLE `post_utme_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_postutme_user` (`user_id`);

--
-- Indexes for table `post_utme_waec_serial_no_backup`
--
ALTER TABLE `post_utme_waec_serial_no_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

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
-- Indexes for table `security_scans`
--
ALTER TABLE `security_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scan_date` (`scan_date`),
  ADD KEY `idx_scan_type` (`scan_type`);

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
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_order` (`is_active`,`display_order`);

--
-- Indexes for table `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `universal_registrations`
--
ALTER TABLE `universal_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program_status` (`program_type`,`status`),
  ADD KEY `idx_payment_ref` (`payment_reference`),
  ADD KEY `idx_created` (`created_at`);

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
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_email_verification_token` (`email_verification_token`(64)),
  ADD KEY `idx_users_google2fa` (`google2fa_enabled`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_action_queue`
--
ALTER TABLE `ai_action_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=347;

--
-- AUTO_INCREMENT for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_attachments`
--
ALTER TABLE `chat_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `chat_threads`
--
ALTER TABLE `chat_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `course_features`
--
ALTER TABLE `course_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `forum_questions`
--
ALTER TABLE `forum_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_votes`
--
ALTER TABLE `forum_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `icons`
--
ALTER TABLE `icons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `ip_logs`
--
ALTER TABLE `ip_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=763;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mac_blocklist`
--
ALTER TABLE `mac_blocklist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7183;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `payments_backup`
--
ALTER TABLE `payments_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post_utme_registrations`
--
ALTER TABLE `post_utme_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_utme_waec_serial_no_backup`
--
ALTER TABLE `post_utme_waec_serial_no_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `security_scans`
--
ALTER TABLE `security_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tutors`
--
ALTER TABLE `tutors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `universal_registrations`
--
ALTER TABLE `universal_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `chat_attachments`
--
ALTER TABLE `chat_attachments`
  ADD CONSTRAINT `fk_chat_message` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `comments_ibfk_4` FOREIGN KEY (`admin_reply_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_courses_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_courses_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_features`
--
ALTER TABLE `course_features`
  ADD CONSTRAINT `fk_course_features_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `fk_forum_replies_question` FOREIGN KEY (`question_id`) REFERENCES `forum_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `post_utme_registrations`
--
ALTER TABLE `post_utme_registrations`
  ADD CONSTRAINT `fk_postutme_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
