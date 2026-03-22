-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 11:06 AM
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
-- Database: `thesis_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 2, 'change_password', NULL, NULL, NULL, '::1', '2025-10-15 06:17:14'),
(2, 1, 'create_user', 'users', 8, '', '::1', '2025-10-15 06:22:19'),
(3, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-15 07:02:12'),
(4, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-15 11:10:47'),
(5, 4, 'login', NULL, NULL, NULL, '::1', '2025-10-15 11:13:58'),
(6, 3, 'login', NULL, NULL, NULL, '::1', '2025-10-15 16:18:59'),
(7, 3, 'submit_thesis', 'thesis_submission', 1, 'Thesis Management System', '::1', '2025-10-15 16:20:03'),
(8, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-15 16:21:21'),
(9, 2, 'update_profile_picture', NULL, NULL, NULL, '::1', '2025-10-15 16:21:31'),
(10, 2, 'update_profile_picture', NULL, NULL, NULL, '::1', '2025-10-15 16:21:37'),
(11, 2, 'submit_thesis', 'thesis_submission', 2, 'Removing Thesis On College', '::1', '2025-10-15 16:23:24'),
(12, 4, 'login', NULL, NULL, NULL, '::1', '2025-10-15 16:27:30'),
(13, 1, 'login', NULL, NULL, NULL, '::1', '2025-10-15 16:28:30'),
(14, 5, 'login', NULL, NULL, NULL, '::1', '2025-10-15 16:30:50'),
(15, 1, 'login', NULL, NULL, NULL, '::1', '2025-10-15 16:31:28'),
(16, 5, 'login', NULL, NULL, NULL, '::1', '2025-10-15 16:32:20'),
(17, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-15 16:32:36'),
(18, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-17 05:04:25'),
(19, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-17 05:04:35'),
(20, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-17 05:04:51'),
(21, 4, 'login', NULL, NULL, NULL, '::1', '2025-10-17 05:07:06'),
(22, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-17 05:17:05'),
(23, 4, 'login', NULL, NULL, NULL, '::1', '2025-10-17 05:26:51'),
(24, 1, 'login', NULL, NULL, NULL, '::1', '2025-10-17 05:27:50'),
(25, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-17 05:56:36'),
(26, 2, 'login', NULL, NULL, NULL, '::1', '2025-10-17 10:39:08'),
(27, 2, 'update_profile', NULL, NULL, NULL, '::1', '2025-10-17 10:44:29'),
(28, 4, 'login', NULL, NULL, NULL, '::1', '2025-10-17 10:45:24'),
(29, 4, 'update_profile', NULL, NULL, NULL, '::1', '2025-10-17 10:50:12'),
(30, 1, 'login', NULL, NULL, NULL, '::1', '2025-10-17 10:52:48'),
(31, 1, 'update_profile_picture', NULL, NULL, NULL, '::1', '2025-10-17 10:55:55'),
(32, 1, 'create_user', 'users', 9, 'Mariel S', '::1', '2025-10-17 10:57:46'),
(33, 9, 'login', NULL, NULL, NULL, '::1', '2025-10-17 10:58:27'),
(34, 9, 'update_profile_picture', NULL, NULL, NULL, '::1', '2025-10-17 10:59:10'),
(35, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-20 06:22:24'),
(39, 10, 'role_selected', NULL, NULL, 'Role: student', '::1', '2025-11-20 06:39:32'),
(40, 10, 'submit_thesis', 'thesis_submission', 3, 'Machine Learning System', '::1', '2025-11-20 06:40:47'),
(41, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 06:41:23'),
(42, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 06:50:08'),
(43, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 06:50:24'),
(44, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 06:53:33'),
(45, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 06:55:00'),
(46, 4, 'download_thesis', 'thesis_submission', 3, 'ACTIVITY 3.pdf', '::1', '2025-11-20 06:55:06'),
(48, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 07:01:13'),
(49, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 07:02:37'),
(50, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 07:05:20'),
(51, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-20 07:05:29'),
(52, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 07:05:44'),
(53, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 07:15:18'),
(54, 10, 'submit_thesis', 'thesis_submission', 4, 'Library Management System', '::1', '2025-11-20 07:16:58'),
(55, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 07:17:13'),
(56, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 07:24:12'),
(57, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 07:27:35'),
(58, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 07:27:51'),
(59, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 07:28:19'),
(60, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 07:32:55'),
(61, 10, 'submit_thesis', 'thesis_submission', 5, 'AI Integration', '::1', '2025-11-20 07:51:32'),
(62, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 07:51:49'),
(63, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 07:54:13'),
(64, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 07:58:55'),
(65, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-20 08:00:15'),
(66, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 08:19:13'),
(67, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 08:22:01'),
(68, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 08:45:26'),
(69, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 08:46:48'),
(70, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 08:50:07'),
(71, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 08:50:16'),
(72, 10, 'update_profile', NULL, NULL, NULL, '::1', '2025-11-20 08:52:15'),
(73, 10, 'update_profile', NULL, NULL, NULL, '::1', '2025-11-20 08:54:07'),
(74, 10, 'submit_thesis', 'thesis_submission', 6, 'E-commerce System', '::1', '2025-11-20 09:10:00'),
(75, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 09:10:16'),
(76, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 09:11:40'),
(77, 10, 'submit_thesis', 'thesis_submission', 7, 'Online Class System', '::1', '2025-11-20 09:14:05'),
(78, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 09:14:16'),
(79, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 09:15:23'),
(80, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 09:15:31'),
(81, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 09:21:50'),
(82, 4, 'download_thesis', 'thesis_submission', 7, 'Egno_AceArthur_Asynchronous Lab Activity #3 (1).pdf', '::1', '2025-11-20 09:22:58'),
(83, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 09:29:12'),
(84, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 09:41:53'),
(85, 1, 'login', NULL, NULL, NULL, '::1', '2025-11-20 10:05:49'),
(86, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 10:07:44'),
(87, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 10:08:15'),
(88, 1, 'login', NULL, NULL, NULL, '::1', '2025-11-20 10:10:57'),
(89, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-20 10:11:06'),
(90, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 10:11:15'),
(91, 1, 'login', NULL, NULL, NULL, '::1', '2025-11-20 10:11:27'),
(92, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 10:11:38'),
(93, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 10:11:53'),
(94, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-20 10:12:18'),
(95, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-20 10:28:32'),
(96, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-20 10:34:32'),
(97, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 10:49:46'),
(99, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 10:51:23'),
(105, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 11:05:42'),
(111, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-20 11:15:34'),
(112, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 11:20:24'),
(114, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 11:40:15'),
(115, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 11:58:41'),
(116, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 11:59:07'),
(124, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 12:28:51'),
(126, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 12:35:17'),
(128, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 12:36:40'),
(130, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 12:39:36'),
(132, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 12:46:27'),
(134, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 12:49:05'),
(135, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 13:16:29'),
(137, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 13:50:08'),
(138, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-20 13:52:09'),
(139, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 13:52:56'),
(140, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-20 13:56:23'),
(141, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 13:56:37'),
(142, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 14:01:01'),
(145, 25, 'google_register', NULL, NULL, NULL, '::1', '2025-11-20 14:04:29'),
(146, 25, 'role_selected', NULL, NULL, 'Role: student', '::1', '2025-11-20 14:05:27'),
(147, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-20 14:05:46'),
(148, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 07:58:01'),
(149, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-21 07:58:30'),
(150, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 07:59:05'),
(151, 25, 'submit_thesis', 'thesis_submission', 14, 'Software Engineering', '::1', '2025-11-21 08:00:28'),
(152, 25, 'submit_thesis', 'thesis_submission', 15, 'Nervous System', '::1', '2025-11-21 08:01:33'),
(153, 25, 'submit_thesis', 'thesis_submission', 16, 'Circulatory System', '::1', '2025-11-21 08:02:57'),
(154, 4, 'login', NULL, NULL, NULL, '::1', '2025-11-21 08:03:06'),
(155, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 08:03:13'),
(156, 1, 'login', NULL, NULL, NULL, '::1', '2025-11-21 08:32:20'),
(157, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-21 08:33:10'),
(158, 5, 'login', NULL, NULL, NULL, '::1', '2025-11-21 08:49:40'),
(159, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-21 08:51:09'),
(160, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 08:56:15'),
(161, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 08:59:57'),
(162, 25, 'upload_revision', 'thesis_submission', 15, 'Version 2', '::1', '2025-11-21 09:00:27'),
(163, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 09:01:04'),
(164, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 09:02:15'),
(165, 25, 'submit_thesis', 'thesis_submission', 17, 'Customer Satisfaction', '::1', '2025-11-21 09:03:08'),
(166, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 09:03:14'),
(167, 3, 'login', NULL, NULL, NULL, '::1', '2025-11-21 09:05:20'),
(168, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 09:05:29'),
(169, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 09:07:40'),
(170, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 09:11:25'),
(171, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 09:11:35'),
(172, 5, 'login', NULL, NULL, NULL, '::1', '2025-11-21 09:20:30'),
(173, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 09:21:07'),
(174, 14, 'download_thesis', 'thesis_submission', 15, 'Act 3.pdf', '::1', '2025-11-21 09:30:18'),
(175, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 10:00:57'),
(176, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 10:01:06'),
(177, 25, 'upload_revision', 'thesis_submission', 15, 'Version 3', '::1', '2025-11-21 10:02:30'),
(178, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 10:03:02'),
(179, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 10:05:41'),
(180, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 10:06:24'),
(181, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 10:06:45'),
(182, 25, 'upload_revision', 'thesis_submission', 15, 'Version 4', '::1', '2025-11-21 10:07:19'),
(183, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 10:07:23'),
(184, 14, 'download_thesis', 'thesis_submission', 15, 'Act 3 (1).pdf', '::1', '2025-11-21 10:07:42'),
(185, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 10:28:09'),
(186, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 10:28:59'),
(187, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 10:44:19'),
(188, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 10:45:53'),
(189, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 10:46:19'),
(190, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-21 10:46:47'),
(191, 10, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 10:46:59'),
(192, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-21 10:47:07'),
(193, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-22 08:31:38'),
(194, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-22 08:33:15'),
(195, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-22 08:34:04'),
(196, 14, 'login', NULL, NULL, NULL, '::1', '2025-11-22 08:34:40'),
(197, 25, 'google_login', NULL, NULL, NULL, '::1', '2025-11-25 10:01:40');

-- --------------------------------------------------------

--
-- Table structure for table `approval_workflow`
--

CREATE TABLE `approval_workflow` (
  `approval_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `approver_role` enum('advisor','panelist','admin') NOT NULL,
  `decision` enum('pending','approved','revision_required','rejected') DEFAULT 'pending',
  `decision_date` timestamp NULL DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_workflow`
--

INSERT INTO `approval_workflow` (`approval_id`, `submission_id`, `approver_id`, `approver_role`, `decision`, `decision_date`, `comments`, `created_at`) VALUES
(4, 3, 4, 'advisor', 'revision_required', '2025-11-20 07:04:34', 'Pakipalitan ASAP', '2025-11-20 07:04:34'),
(5, 7, 4, 'advisor', 'approved', '2025-11-20 09:22:06', '', '2025-11-20 09:22:06'),
(6, 6, 4, 'advisor', 'pending', '2025-11-20 09:29:01', '', '2025-11-20 09:29:01'),
(7, 7, 4, 'advisor', 'approved', '2025-11-20 09:42:02', '', '2025-11-20 09:42:02'),
(14, 5, 4, 'advisor', 'approved', '2025-11-20 13:55:10', '', '2025-11-20 13:55:10'),
(15, 16, 14, 'advisor', 'approved', '2025-11-21 08:03:30', '', '2025-11-21 08:03:30'),
(16, 15, 14, 'advisor', 'revision_required', '2025-11-21 08:03:58', '', '2025-11-21 08:03:58'),
(17, 16, 14, 'advisor', 'approved', '2025-11-21 08:56:24', '', '2025-11-21 08:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewer_role` enum('advisor','panelist') NOT NULL,
  `feedback_text` text NOT NULL,
  `section` varchar(100) DEFAULT NULL,
  `page_number` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `feedback_type` enum('general','methodology','writing','structure','content') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `submission_id`, `reviewer_id`, `reviewer_role`, `feedback_text`, `section`, `page_number`, `rating`, `feedback_type`, `created_at`, `is_read`) VALUES
(3, 3, 4, 'advisor', 'Pwede na', '0', NULL, 4, 'general', '2025-11-20 06:53:09', 0),
(4, 3, 4, 'advisor', 'Pwede na nga', '0', NULL, 4, 'structure', '2025-11-20 07:04:07', 0),
(9, 5, 4, 'advisor', 'sige pwede na', '0', NULL, 3, 'structure', '2025-11-20 13:55:06', 0),
(10, 16, 14, 'advisor', 'asdasd', '0', NULL, 1, 'writing', '2025-11-21 08:04:15', 0),
(11, 15, 14, 'advisor', 'asfsds', '0', NULL, 4, 'writing', '2025-11-21 08:04:26', 0),
(12, 15, 14, 'advisor', 'ok na  toh', '0', NULL, 2, 'writing', '2025-11-21 08:58:28', 0),
(13, 15, 14, 'advisor', 'asdasd', '0', NULL, 2, 'general', '2025-11-21 09:08:07', 0),
(14, 15, 14, 'advisor', 'pwidi na', '0', NULL, 3, 'structure', '2025-11-21 10:06:37', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('submission','feedback','approval','revision','system') NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `notification_type`, `related_id`, `is_read`, `created_at`) VALUES
(1, 4, 'New Thesis Submission', 'A new thesis titled \'Thesis Management System\' has been submitted by Sunwoo Han', 'submission', 1, 0, '2025-10-15 16:20:03'),
(2, 4, 'New Thesis Submission', 'A new thesis titled \'Removing Thesis On College\' has been submitted by Reymart G', 'submission', 2, 0, '2025-10-15 16:23:24'),
(3, 5, 'New Review Assignment', 'You have been assigned to review the thesis: \"Removing Thesis On College\"', 'system', 2, 0, '2025-10-15 16:29:44'),
(4, 5, 'New Review Assignment', 'You have been assigned to review the thesis: \"Removing Thesis On College\"', 'system', 2, 0, '2025-10-15 16:29:57'),
(5, 2, 'Thesis Review Decision', 'Your thesis \"Removing Thesis On College\" has been reviewed. Decision: Revision Required', 'approval', 2, 0, '2025-10-17 05:15:59'),
(6, 3, 'New Feedback Received', 'Your thesis \"Thesis Management System\" has received new feedback from Dr. Liam Byrne', 'feedback', 1, 1, '2025-10-17 05:16:40'),
(7, 3, 'Thesis Approved!', 'Your thesis \"Thesis Management System\" has been reviewed. Decision: Approved', 'approval', 1, 1, '2025-10-17 05:16:49'),
(8, 2, 'New Feedback Received', 'Your thesis \"Removing Thesis On College\" has received new feedback from Dr. Liam Byrne', 'feedback', 2, 0, '2025-10-17 10:47:54'),
(9, 2, 'Thesis Review Decision', 'Your thesis \"Removing Thesis On College\" has been reviewed. Decision: Pending', 'approval', 2, 0, '2025-10-17 10:48:08'),
(10, 4, 'New Thesis Submission', 'A new thesis titled \'Machine Learning System\' has been submitted by Ace Arthur Egno', 'submission', 3, 0, '2025-11-20 06:40:47'),
(11, 10, 'New Feedback Received', 'Your thesis \"Machine Learning System\" has received new feedback from Dr. Liam Byrne', 'feedback', 3, 1, '2025-11-20 06:53:09'),
(12, 10, 'New Feedback Received', 'Your thesis \"Machine Learning System\" has received new feedback from Dr. Liam Byrne', 'feedback', 3, 1, '2025-11-20 07:04:07'),
(13, 10, 'Thesis Review Decision', 'Your thesis \"Machine Learning System\" has been reviewed. Decision: Revision Required', 'approval', 3, 1, '2025-11-20 07:04:34'),
(14, 4, 'New Thesis Submission', 'A new thesis titled \'Library Management System\' has been submitted by Ace Arthur Egno', 'submission', 4, 1, '2025-11-20 07:16:58'),
(15, 4, 'New Thesis Submission', 'A new thesis titled \'AI Integration\' has been submitted by Ace Arthur Egno', 'submission', 5, 1, '2025-11-20 07:51:32'),
(16, 4, 'New Thesis Submission', 'A new thesis titled \'E-commerce System\' has been submitted by Ace Arthur Egno', 'submission', 6, 1, '2025-11-20 09:10:00'),
(17, 4, 'New Thesis Submission', 'A new thesis titled \'Online Class System\' has been submitted by Ace Arthur Egno', 'submission', 7, 0, '2025-11-20 09:14:05'),
(18, 10, 'Thesis Approved!', 'Your thesis \"Online Class System\" has been reviewed. Decision: Approved', 'approval', 7, 0, '2025-11-20 09:22:06'),
(19, 10, 'Thesis Review Decision', 'Your thesis \"E-commerce System\" has been reviewed. Decision: Pending', 'approval', 6, 0, '2025-11-20 09:29:01'),
(20, 10, 'Thesis Approved!', 'Your thesis \"Online Class System\" has been reviewed. Decision: Approved', 'approval', 7, 0, '2025-11-20 09:42:02'),
(21, 4, 'New Thesis Submission', 'A new thesis titled \'Nervous System\' has been submitted by Ace Arthur Egno', 'submission', 8, 0, '2025-11-20 12:15:09'),
(22, 14, 'New Thesis Submission', 'A new thesis titled \'Circulatory System\' has been submitted by Ace Arthur Egno', 'submission', 9, 0, '2025-11-20 12:17:11'),
(23, 14, 'New Thesis Submission', 'A new thesis titled \'Skeletal System\' has been submitted by Ace Arthur Egno', 'submission', 10, 0, '2025-11-20 12:19:50'),
(24, 4, 'New Thesis Submission', 'A new thesis titled \'Software System\' has been submitted by Ace Arthur Egno', 'submission', 11, 0, '2025-11-20 12:22:51'),
(25, 14, 'New Thesis Submission', 'A new thesis titled \'Hardware Components\' has been submitted by Ace Arthur Egno', 'submission', 12, 0, '2025-11-20 12:23:39'),
(26, 14, 'New Thesis Submission', 'A new thesis titled \'Website Development\' has been submitted by Ace Arthur Egno', 'submission', 13, 0, '2025-11-20 12:25:41'),
(37, 10, 'New Feedback Received', 'Your thesis \"AI Integration\" has received new feedback from Dr. Liam Byrne', 'feedback', 5, 0, '2025-11-20 13:55:06'),
(38, 10, 'Thesis Approved!', 'Your thesis \"AI Integration\" has been reviewed. Status: Approved', 'approval', 5, 0, '2025-11-20 13:55:10'),
(39, 4, 'New Thesis Submission', 'A new thesis titled \'Software Engineering\' has been submitted by Ace Arthur Egno', 'submission', 14, 0, '2025-11-21 08:00:28'),
(40, 14, 'New Thesis Submission', 'A new thesis titled \'Nervous System\' has been submitted by Ace Arthur Egno', 'submission', 15, 0, '2025-11-21 08:01:33'),
(41, 14, 'New Thesis Submission', 'A new thesis titled \'Circulatory System\' has been submitted by Ace Arthur Egno', 'submission', 16, 0, '2025-11-21 08:02:57'),
(42, 25, 'Thesis Approved!', 'Your thesis \"Circulatory System\" has been reviewed. Status: Approved', 'approval', 16, 0, '2025-11-21 08:03:30'),
(43, 25, 'Thesis Review Status', 'Your thesis \"Nervous System\" has been reviewed. Status: Revision Required', 'approval', 15, 0, '2025-11-21 08:03:58'),
(44, 25, 'New Feedback Received', 'Your thesis \"Circulatory System\" has received new feedback from Mr. Reymart Goc-ong', 'feedback', 16, 0, '2025-11-21 08:04:15'),
(45, 25, 'New Feedback Received', 'Your thesis \"Nervous System\" has received new feedback from Mr. Reymart Goc-ong', 'feedback', 15, 0, '2025-11-21 08:04:26'),
(46, 25, 'Thesis Approved!', 'Your thesis \"Circulatory System\" has been reviewed. Status: Approved', 'approval', 16, 0, '2025-11-21 08:56:24'),
(47, 25, 'New Feedback Received', 'Your thesis \"Nervous System\" has received new feedback from Mr. Reymart Goc-ong', 'feedback', 15, 1, '2025-11-21 08:58:28'),
(48, 14, 'Thesis Revision Uploaded', 'A new revision (v2) has been uploaded for \"Nervous System\"', 'revision', 15, 0, '2025-11-21 09:00:27'),
(49, 14, 'New Thesis Submission', 'A new thesis titled \'Customer Satisfaction\' has been submitted by Ace Arthur Egno', 'submission', 17, 1, '2025-11-21 09:03:08'),
(50, 25, 'New Feedback Received', 'Your thesis \"Nervous System\" has received new feedback from Mr. Reymart Goc-ong', 'feedback', 15, 1, '2025-11-21 09:08:07'),
(51, 14, 'Thesis Revision Uploaded', 'A new revision (v3) has been uploaded for \"Nervous System\"', 'revision', 15, 1, '2025-11-21 10:02:30'),
(52, 25, 'New Feedback Received', 'Your thesis \"Nervous System\" has received new feedback from Mr. Reymart Goc-ong', 'feedback', 15, 1, '2025-11-21 10:06:37'),
(53, 14, 'Thesis Revision Uploaded', 'A new revision (v4) has been uploaded for \"Nervous System\"', 'revision', 15, 1, '2025-11-21 10:07:19');

-- --------------------------------------------------------

--
-- Table structure for table `panelist_assignments`
--

CREATE TABLE `panelist_assignments` (
  `assignment_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `panelist_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('assigned','completed','declined') DEFAULT 'assigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `repository_archive`
--

CREATE TABLE `repository_archive` (
  `archive_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `archived_by` int(11) NOT NULL,
  `archive_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `access_level` enum('public','restricted','private') DEFAULT 'public',
  `download_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `revision_history`
--

CREATE TABLE `revision_history` (
  `revision_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `version_number` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `revision_notes` text DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `changes_summary` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `revision_history`
--

INSERT INTO `revision_history` (`revision_id`, `submission_id`, `version_number`, `file_path`, `file_name`, `revision_notes`, `uploaded_by`, `uploaded_at`, `changes_summary`) VALUES
(3, 3, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_3_1763620847.pdf', 'ACTIVITY 3.pdf', NULL, 10, '2025-11-20 06:40:47', 'Initial submission'),
(4, 4, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_4_1763623018.pdf', 'Technical Architecture Diagram (1).pdf', NULL, 10, '2025-11-20 07:16:58', 'Initial submission'),
(5, 5, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_5_1763625092.pdf', 'GROUP-5-COSC-75-SDD.pdf', NULL, 10, '2025-11-20 07:51:32', 'Initial submission'),
(6, 6, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_6_1763629800.pdf', 'MATH 3- QUIZ # 3.pdf', NULL, 10, '2025-11-20 09:10:00', 'Initial submission'),
(7, 7, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_7_1763630045.pdf', 'Egno_AceArthur_Asynchronous Lab Activity #3 (1).pdf', NULL, 10, '2025-11-20 09:14:05', 'Initial submission'),
(14, 14, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_14_1763712028.pdf', 'Act 3.pdf', NULL, 25, '2025-11-21 08:00:28', 'Initial submission'),
(15, 15, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_15_1763712093.pdf', 'Group_5_SDD_Revised.pdf', NULL, 25, '2025-11-21 08:01:33', 'Initial submission'),
(16, 16, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_16_1763712177.pdf', 'Egno_AceArthur_Asynchronous Lab Activity #3 (1) (1).pdf', NULL, 25, '2025-11-21 08:02:57', 'Initial submission'),
(17, 15, 2, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_15_1763715627.pdf', 'Act 3.pdf', 'bago na yan', 25, '2025-11-21 09:00:27', 'eto na sir'),
(18, 17, 1, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_17_1763715788.pdf', 'Act 3.pdf', NULL, 25, '2025-11-21 09:03:08', 'Initial submission'),
(19, 15, 3, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_15_1763719350.pdf', 'Act 3.pdf', '', 25, '2025-11-21 10:02:30', 'Chapter 3 Revision'),
(20, 15, 4, 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_15_1763719639.pdf', 'Act 3 (1).pdf', 'HAHAHAHAAH', 25, '2025-11-21 10:07:19', 'eto na sir');

-- --------------------------------------------------------

--
-- Table structure for table `thesis_submissions`
--

CREATE TABLE `thesis_submissions` (
  `submission_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `abstract` text DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `thesis_type` enum('masters','phd','undergraduate') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('draft','submitted','under_review','revision_requested','approved','rejected') DEFAULT 'submitted',
  `current_version` int(11) DEFAULT 1,
  `advisor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thesis_submissions`
--

INSERT INTO `thesis_submissions` (`submission_id`, `student_id`, `title`, `abstract`, `keywords`, `department`, `program`, `thesis_type`, `file_path`, `file_name`, `file_size`, `submission_date`, `status`, `current_version`, `advisor_id`) VALUES
(3, 10, 'Machine Learning System', 'Sir tapos na po', 'stnp', 'Computer Science', 'Bachelor of Science in Computer Science', 'undergraduate', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_3_1763620847.pdf', 'ACTIVITY 3.pdf', 230377, '2025-11-20 06:40:47', 'revision_requested', 1, 4),
(4, 10, 'Library Management System', 'Paki check sir', 'check', 'Computer Science', 'Bachelor of Science in Computer Science', 'undergraduate', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_4_1763623018.pdf', 'Technical Architecture Diagram (1).pdf', 28810, '2025-11-20 07:16:58', 'submitted', 1, 4),
(5, 10, 'AI Integration', 'sige po sir', 'check', 'Computer Engineering', 'Bachelor of Science in Computer Science', 'undergraduate', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_5_1763625092.pdf', 'GROUP-5-COSC-75-SDD.pdf', 2760280, '2025-11-20 07:51:32', 'approved', 1, 4),
(6, 10, 'E-commerce System', 'Sir pareview na po', 'sige na pls', 'Computer Science', 'Computer Science', 'undergraduate', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_6_1763629800.pdf', 'MATH 3- QUIZ # 3.pdf', 266951, '2025-11-20 09:10:00', 'under_review', 1, 4),
(7, 10, 'Online Class System', 'pasado ba sir', 'oo', 'Computer Science', 'Bachelor of Science in Computer Science', 'undergraduate', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_7_1763630045.pdf', 'Egno_AceArthur_Asynchronous Lab Activity #3 (1).pdf', 457280, '2025-11-20 09:14:05', 'approved', 1, 4),
(14, 25, 'Software Engineering', 'sir ito na po', 'sinp', 'Computer Science', 'Bachelor of Science in Computer Science', 'undergraduate', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_14_1763712028.pdf', 'Act 3.pdf', 432319, '2025-11-21 08:00:28', 'submitted', 1, 4),
(15, 25, 'Nervous System', 'Sir nakakanerbyos po', 'nerbyos', 'Computer Engineering', 'Bachelor of Science in Computer Science', 'undergraduate', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_15_1763719639.pdf', 'Act 3 (1).pdf', 2689930, '2025-11-21 08:01:33', 'submitted', 4, 14),
(16, 25, 'Circulatory System', 'sir high blood na po', 'hb', 'Computer Engineering', 'Bachelor of Science in Computer Science', 'masters', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_16_1763712177.pdf', 'Egno_AceArthur_Asynchronous Lab Activity #3 (1) (1).pdf', 457280, '2025-11-21 08:02:57', 'approved', 1, 14),
(17, 25, 'Customer Satisfaction', 'asdasd', 'asdas', 'Computer Engineering', 'Bachelor of Science in Business Administration', 'undergraduate', 'C:\\xampp\\htdocs\\thesis_management/uploads/theses/thesis_17_1763715788.pdf', 'Act 3.pdf', 432319, '2025-11-21 09:03:08', 'submitted', 1, 14);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `google_uid` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('student','advisor','panelist','admin') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `google_uid`, `full_name`, `role`, `department`, `phone`, `profile_picture`, `bio`, `created_at`, `last_login`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@thesis.edu', NULL, 'System Administrator', 'admin', 'Information Technology', NULL, 'uploads/profiles/profile_1_1760698555.jfif', NULL, '2025-10-14 09:38:23', '2025-11-21 08:32:19', 'active'),
(2, 'reymart.g', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reymart.g@student.edu', NULL, 'Reymart G', 'student', 'Computer Science', '32323232323', 'uploads/profiles/profile_2_1760545297.jpg', 'secret', '2025-10-14 09:38:23', '2025-10-17 10:39:08', 'active'),
(3, 'sunwoo.han', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sunwoo.han@student.edu', NULL, 'Sunwoo Han', 'student', 'Computer Science', NULL, NULL, NULL, '2025-10-14 09:38:23', '2025-11-21 09:05:20', 'active'),
(4, 'dr.byrne', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr.byrne@faculty.edu', NULL, 'Dr. Liam Byrne', 'advisor', 'Computer Science', '32323232323', NULL, '(Sige kayo pogi) Basta saken yung dateng..', '2025-10-14 09:38:23', '2025-11-21 08:03:06', 'active'),
(5, 'prof.callas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof.callas@faculty.edu', NULL, 'Prof. Sabine Callas', 'panelist', 'Information Technology', NULL, NULL, NULL, '2025-10-14 09:38:23', '2025-11-21 09:20:30', 'active'),
(9, 'mariel.bscs35', '$2y$10$A.6JExO1h0uJY44XqG9PnO/SRx54dxeO0WkK2fEDRuQVnnOJX2hXa', 'mariel.ganda123@student.edu', NULL, 'Mariel S', 'student', 'Computer Science', '1111111', 'uploads/profiles/profile_9_1760698750.jfif', NULL, '2025-10-17 10:57:46', '2025-10-17 10:58:27', 'active'),
(10, 'egnoacearthur_c0e9', '', 'egnoacearthur@gmail.com', '12NBLNVCmaZf3e4zFgVN46HrXpi2', 'Ace Arthur Egno', 'student', 'Computer Science', '09691427201', 'https://lh3.googleusercontent.com/a/ACg8ocJ02lR7XX20BzoANm3JAq3gRGY4idHYKaF64NyLlnmQEHSMkATD=s96-c', '', '2025-11-20 06:31:52', '2025-11-21 10:46:59', 'active'),
(14, 'mr.goc-ong', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mr.goc-ong@advisor.edu', NULL, 'Mr. Reymart Goc-ong', 'advisor', 'Computer Engineering', '32323232323', 'uploads/profiles/profile_2_1760545297.jpg', 'secret', '2025-10-14 09:38:23', '2025-11-22 08:34:39', 'active'),
(25, 'acearthuregno_e74f', '', 'acearthuregno@gmail.com', 'oMznmX6wm7e5WQYeKIPApCXgDy73', 'Ace Arthur Egno', 'student', NULL, NULL, 'https://lh3.googleusercontent.com/a/ACg8ocJfezMyGjojZKVGnSAjJMfSwQc8OGk2N9sJAIfLMK5yz2DqSQ=s96-c', NULL, '2025-11-20 14:04:29', '2025-11-25 10:01:40', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `approval_workflow`
--
ALTER TABLE `approval_workflow`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `idx_submission` (`submission_id`),
  ADD KEY `idx_approver` (`approver_id`),
  ADD KEY `idx_decision` (`decision`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `idx_submission` (`submission_id`),
  ADD KEY `idx_reviewer` (`reviewer_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_type` (`notification_type`);

--
-- Indexes for table `panelist_assignments`
--
ALTER TABLE `panelist_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_submission` (`submission_id`),
  ADD KEY `idx_panelist` (`panelist_id`);

--
-- Indexes for table `repository_archive`
--
ALTER TABLE `repository_archive`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `archived_by` (`archived_by`),
  ADD KEY `idx_submission` (`submission_id`),
  ADD KEY `idx_access` (`access_level`);

--
-- Indexes for table `revision_history`
--
ALTER TABLE `revision_history`
  ADD PRIMARY KEY (`revision_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_submission` (`submission_id`),
  ADD KEY `idx_version` (`version_number`);

--
-- Indexes for table `thesis_submissions`
--
ALTER TABLE `thesis_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_advisor` (`advisor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_uid` (`google_uid`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_google_uid` (`google_uid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

--
-- AUTO_INCREMENT for table `approval_workflow`
--
ALTER TABLE `approval_workflow`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `panelist_assignments`
--
ALTER TABLE `panelist_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `repository_archive`
--
ALTER TABLE `repository_archive`
  MODIFY `archive_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revision_history`
--
ALTER TABLE `revision_history`
  MODIFY `revision_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `thesis_submissions`
--
ALTER TABLE `thesis_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `approval_workflow`
--
ALTER TABLE `approval_workflow`
  ADD CONSTRAINT `approval_workflow_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `thesis_submissions` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `approval_workflow_ibfk_2` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `thesis_submissions` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `panelist_assignments`
--
ALTER TABLE `panelist_assignments`
  ADD CONSTRAINT `panelist_assignments_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `thesis_submissions` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panelist_assignments_ibfk_2` FOREIGN KEY (`panelist_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panelist_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `repository_archive`
--
ALTER TABLE `repository_archive`
  ADD CONSTRAINT `repository_archive_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `thesis_submissions` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `repository_archive_ibfk_2` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `revision_history`
--
ALTER TABLE `revision_history`
  ADD CONSTRAINT `revision_history_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `thesis_submissions` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `revision_history_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `thesis_submissions`
--
ALTER TABLE `thesis_submissions`
  ADD CONSTRAINT `thesis_submissions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `thesis_submissions_ibfk_2` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
