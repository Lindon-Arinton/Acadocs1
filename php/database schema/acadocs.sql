-- phpMyAdmin SQL Dump (merged/adapted for this project)
-- Source snapshot exported Jul 26, 2026
--
-- Safe to import repeatedly on the same database: every CREATE TABLE uses
-- IF NOT EXISTS, keys/AUTO_INCREMENT/foreign keys are declared inline (no
-- separate ALTER TABLE statements that would fail on a second run), and
-- data INSERTs use IGNORE so existing rows are left untouched.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `acadocs`
--
-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('Announcement','Questionnaires','Forms') NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `biometric_employees`
--

CREATE TABLE IF NOT EXISTS `biometric_employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `ac_no` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `department` varchar(150) DEFAULT NULL,
  `is_placeholder` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ac_no` (`ac_no`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `biometric_employees`
--

INSERT IGNORE INTO `biometric_employees` (`id`, `ac_no`, `name`, `department`, `is_placeholder`, `created_at`) VALUES
(12, '28', 'VERGARA, RYAN B.', 'Matabungkay NHS', 0, '2026-07-25 02:54:27'),
(13, '30', 'Unmapped (AC-30)', 'Matabungkay NHS', 1, '2026-07-25 02:54:27'),
(14, '32', 'FAMERONAG, JIMMILYN', 'Matabungkay NHS', 0, '2026-07-25 02:54:27'),
(15, '34', 'LUNDAG, ALFRED L.', 'Matabungkay NHS', 0, '2026-07-25 02:54:27'),
(16, '35', 'ROBLES, GIL G.', 'Matabungkay NHS', 0, '2026-07-25 02:54:27'),
(17, '37', 'TESALONA, PEDRITO G.', 'Matabungkay NHS', 0, '2026-07-25 02:54:27'),
(18, '39', 'Unmapped (AC-39)', 'Matabungkay NHS', 1, '2026-07-25 02:54:27'),
(19, '40', 'PANGANIBAN, HENRY S.', 'Matabungkay NHS', 0, '2026-07-25 02:54:27'),
(20, '41', 'DELOS REYES, JOANNE', 'Matabungkay NHS', 0, '2026-07-25 02:54:27'),
(21, '42', 'Unmapped (AC-42)', 'Matabungkay NHS', 1, '2026-07-25 02:54:27'),
(22, '43', 'Unmapped (AC-43)', 'Matabungkay NHS', 1, '2026-07-25 02:54:28'),
(23, '46', 'Unmapped (AC-46)', 'Matabungkay NHS', 1, '2026-07-25 02:54:28');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('direct','group') NOT NULL DEFAULT 'direct',
  `name` varchar(150) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversations`
--

INSERT IGNORE INTO `conversations` (`id`, `type`, `name`, `created_by`, `created_at`) VALUES
(1, 'group', 'Teachers', 10, '2026-07-25 15:44:12');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE IF NOT EXISTS `conversation_participants` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `muted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_user` (`conversation_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversation_participants`
--

INSERT IGNORE INTO `conversation_participants` (`id`, `conversation_id`, `user_id`, `last_read_at`, `created_at`) VALUES
(1, 1, 10, '2026-07-26 05:40:58', '2026-07-25 15:44:12'),
(2, 1, 20, NULL, '2026-07-25 15:44:12'),
(3, 1, 21, NULL, '2026-07-25 15:44:12'),
(4, 1, 31, NULL, '2026-07-25 15:44:12'),
(5, 1, 35, NULL, '2026-07-25 15:44:12'),
(6, 1, 27, NULL, '2026-07-25 15:44:12'),
(7, 1, 22, NULL, '2026-07-25 15:44:12'),
(8, 1, 36, NULL, '2026-07-25 15:44:12'),
(9, 1, 9, NULL, '2026-07-25 15:44:12'),
(10, 1, 28, NULL, '2026-07-25 15:44:12'),
(11, 1, 39, NULL, '2026-07-25 15:44:12'),
(12, 1, 34, NULL, '2026-07-25 15:44:12'),
(13, 1, 17, NULL, '2026-07-25 15:44:12'),
(14, 1, 16, NULL, '2026-07-25 15:44:12'),
(15, 1, 33, NULL, '2026-07-25 15:44:12'),
(16, 1, 19, NULL, '2026-07-25 15:44:12'),
(17, 1, 40, NULL, '2026-07-25 15:44:12'),
(18, 1, 41, NULL, '2026-07-25 15:44:12'),
(19, 1, 8, NULL, '2026-07-25 15:44:12'),
(20, 1, 12, NULL, '2026-07-25 15:44:12'),
(21, 1, 37, NULL, '2026-07-25 15:44:12'),
(22, 1, 11, NULL, '2026-07-25 15:44:12'),
(23, 1, 14, NULL, '2026-07-25 15:44:12'),
(24, 1, 18, NULL, '2026-07-25 15:44:12'),
(25, 1, 24, NULL, '2026-07-25 15:44:12'),
(26, 1, 23, NULL, '2026-07-25 15:44:12'),
(27, 1, 13, NULL, '2026-07-25 15:44:12'),
(28, 1, 32, NULL, '2026-07-25 15:44:12'),
(29, 1, 15, '2026-07-25 16:14:38', '2026-07-25 15:44:12'),
(30, 1, 25, NULL, '2026-07-25 15:44:12'),
(31, 1, 26, NULL, '2026-07-25 15:44:12'),
(32, 1, 30, NULL, '2026-07-25 15:44:12'),
(33, 1, 29, NULL, '2026-07-25 15:44:12'),
(34, 1, 38, NULL, '2026-07-25 15:44:12');

-- --------------------------------------------------------

--
-- Table structure for table `deped_documents`
--

CREATE TABLE IF NOT EXISTS `deped_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `completion_rate` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `prepared_by` varchar(100) NOT NULL,
  `last_updated` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deped_kpi_reports`
--

CREATE TABLE IF NOT EXISTS `deped_kpi_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `gross_enrolment_rate` decimal(5,2) DEFAULT NULL,
  `net_enrolment_rate` decimal(5,2) DEFAULT NULL,
  `cohort_survival_rate` decimal(5,2) DEFAULT NULL,
  `repetition_rate` decimal(5,2) DEFAULT NULL,
  `promotion_rate` decimal(5,2) DEFAULT NULL,
  `retention_rate` decimal(5,2) DEFAULT NULL,
  `graduation_rate` decimal(5,2) DEFAULT NULL,
  `completion_rate` decimal(5,2) DEFAULT NULL,
  `transition_rate` decimal(5,2) DEFAULT NULL,
  `dropout_rate` decimal(5,2) DEFAULT NULL,
  `enrolment_total` int(10) UNSIGNED DEFAULT NULL,
  `enrolment_male` int(10) UNSIGNED DEFAULT NULL,
  `enrolment_female` int(10) UNSIGNED DEFAULT NULL,
  `source_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_year` (`school_year`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deped_kpi_reports`
--

INSERT IGNORE INTO `deped_kpi_reports` (`id`, `school_year`, `gross_enrolment_rate`, `net_enrolment_rate`, `cohort_survival_rate`, `repetition_rate`, `promotion_rate`, `retention_rate`, `graduation_rate`, `completion_rate`, `transition_rate`, `dropout_rate`, `enrolment_total`, `enrolment_male`, `enrolment_female`, `source_file`, `created_at`) VALUES
(1, '2020-2021', 64.60, 52.15, 91.90, 2.79, 99.60, 100.00, 100.00, 91.90, 75.47, 0.42, NULL, NULL, NULL, 'KEY-PERFORMANCE-INDICATOR-2020-2021.docx', '2026-07-26 02:12:25'),
(2, '2021-2022', 63.25, 52.04, 96.20, 1.83, 99.61, 95.74, 99.05, 99.52, 81.19, 0.39, NULL, NULL, NULL, 'KEY-PERFORMANCE-INDICATOR-2021-2022.docx', '2026-07-26 02:12:25'),
(3, '2022-2023', 69.11, 62.74, 98.10, 0.90, 96.54, 95.74, 95.73, 99.36, 73.30, 2.49, NULL, NULL, NULL, 'KEY-PERFORMANCE-INDICATOR-2022-2023.docx', '2026-07-26 02:12:25'),
(4, '2023-2024', 64.83, 61.02, 93.81, 0.82, 92.83, 100.00, 95.00, 95.00, 67.63, 2.43, 698, 367, 331, 'KEY-PERFORMANCE-INDICATOR-2023-2024.docx', '2026-07-26 02:12:25');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE IF NOT EXISTS `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `type` enum('DLL','Lesson Plan','Assessment','Report') NOT NULL,
  `subject` varchar(100) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `date_submitted` datetime NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('Submitted','Reviewed','Returned','Pending') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT IGNORE INTO `documents` (`id`, `teacher_id`, `type`, `subject`, `grade_level`, `date_submitted`, `file_path`, `status`, `created_at`) VALUES
(1, 7, 'DLL', 'Math', '9', '2026-07-25 07:56:39', 'writable/uploads/documents/20260725_075639_Research-Proposal-JCB.docx', 'Returned', '2026-07-25 07:56:39');

-- --------------------------------------------------------

--
-- Table structure for table `document_feedback`
--

CREATE TABLE IF NOT EXISTS `document_feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `document_id` int(10) UNSIGNED NOT NULL,
  `author` varchar(100) NOT NULL,
  `comment` text NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `document_feedback_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_feedback`
--

INSERT IGNORE INTO `document_feedback` (`id`, `document_id`, `author`, `comment`, `date`, `created_at`) VALUES
(1, 1, 'Jorge Bautista', 'dsd', '2026-07-25', '2026-07-25 08:47:21');

-- --------------------------------------------------------

--
-- Table structure for table `document_files`
--

CREATE TABLE IF NOT EXISTS `document_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `document_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `document_files_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_links`
--

CREATE TABLE IF NOT EXISTS `document_links` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` enum('Forms','Questionnaires','Templates','Guidelines') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `added_by` varchar(100) NOT NULL,
  `date_added` date NOT NULL,
  `access_level` enum('All Users','Teachers','Admin') DEFAULT 'All Users',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_by_level`
--

CREATE TABLE IF NOT EXISTS `enrollment_by_level` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `students` int(10) UNSIGNED NOT NULL,
  `male` int(10) UNSIGNED DEFAULT NULL,
  `female` int(10) UNSIGNED DEFAULT NULL,
  `sections` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollment_year_grade` (`school_year`,`grade_level`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollment_by_level`
--

INSERT IGNORE INTO `enrollment_by_level` (`id`, `school_year`, `grade_level`, `students`, `sections`) VALUES
(17, '2023-2024', 'Grade 7', 155, 4),
(18, '2023-2024', 'Grade 8', 166, 5),
(19, '2023-2024', 'Grade 9', 180, 5),
(20, '2023-2024', 'Grade 10', 180, 5),
(21, '2024-2025', 'Grade 7', 228, 6),
(22, '2024-2025', 'Grade 8', 149, 4),
(23, '2024-2025', 'Grade 9', 153, 4),
(24, '2024-2025', 'Grade 10', 184, 5);

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_snapshots`
--

CREATE TABLE IF NOT EXISTS `kpi_snapshots` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `total_enrollment` int(10) UNSIGNED NOT NULL,
  `submission_compliance` decimal(5,2) NOT NULL,
  `average_mps` decimal(5,2) NOT NULL,
  `dropout_count` int(10) UNSIGNED NOT NULL,
  `parent_attendance` decimal(5,2) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kpi_snapshots`
--

INSERT IGNORE INTO `kpi_snapshots` (`id`, `school_year`, `total_enrollment`, `submission_compliance`, `average_mps`, `dropout_count`, `parent_attendance`, `recorded_at`) VALUES
(1, '2024-2025', 714, 0.00, 69.89, 10, 74.00, '2026-07-26 02:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `reply_to_id` int(10) UNSIGNED DEFAULT NULL,
  `body` text NOT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_ext` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`),
  KEY `reply_to_id` (`reply_to_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_reply_to_fk` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_reactions`
--

CREATE TABLE IF NOT EXISTS `message_reactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `message_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `emoji` varchar(8) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_user` (`message_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `message_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation_typing`
--

CREATE TABLE IF NOT EXISTS `conversation_typing` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_user` (`conversation_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `conversation_typing_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_typing_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `sub` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `ref_type` varchar(50) DEFAULT NULL,
  `ref_id` int(10) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ref_lookup` (`user_id`,`ref_type`,`ref_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT IGNORE INTO `notifications` (`id`, `user_id`, `type`, `title`, `sub`, `url`, `ref_type`, `ref_id`, `is_read`, `created_at`, `updated_at`) VALUES
(1, 8, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(2, 9, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(3, 11, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(4, 12, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(5, 13, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(6, 14, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(7, 15, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 1, '2026-07-25 07:36:05', '2026-07-25 07:47:03'),
(8, 16, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(9, 17, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(10, 18, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(11, 19, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(12, 20, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(13, 21, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(14, 22, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(15, 23, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(16, 24, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(17, 25, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(18, 27, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(19, 28, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(20, 29, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(21, 30, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(22, 31, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(23, 32, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(24, 33, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(25, 34, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(26, 35, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(27, 36, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(28, 37, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(29, 38, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(30, 39, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(31, 40, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(32, 41, 'task_assigned', 'New Task: Submit Q1 DLL', 'Due Jul 26, 2026', 'http://localhost:8080/my-tasks', 'task_assigned', 1, 0, '2026-07-25 07:36:05', '2026-07-25 07:36:05'),
(33, 10, 'task_submission', 'Remelyn Diaz submitted', 'Task: Submit Q1 DLL', 'http://localhost:8080/tasks/1', 'task_submission', 1, 1, '2026-07-25 07:47:22', '2026-07-26 02:14:52'),
(34, 15, 'document_feedback', 'New feedback on your DLL submission', 'dsd', 'http://localhost:8080/submit-documents', 'document_feedback', 1, 1, '2026-07-25 08:47:21', '2026-07-25 15:14:20'),
(35, 8, 'task_feedback', 'New feedback on: Submit Q1 DLL', 'Good work, please format the docx slightly better.', 'http://localhost:8080/my-tasks', 'task_feedback', 2, 0, '2026-07-25 12:55:37', '2026-07-25 12:55:37'),
(70, 8, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(71, 9, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(72, 11, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(73, 12, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(74, 13, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(75, 14, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(76, 15, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(77, 16, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(78, 17, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(79, 18, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(80, 19, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(81, 20, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(82, 21, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(83, 22, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(84, 23, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(85, 24, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(86, 25, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(87, 27, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(88, 28, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(89, 29, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(90, 30, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(91, 31, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(92, 32, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(93, 33, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(94, 34, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(95, 35, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(96, 36, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(97, 37, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(98, 38, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(99, 39, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(100, 40, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31'),
(101, 41, 'parent_meeting', 'Parent Meeting: Recognition for Grade 7', 'Jul 31, 2026', 'http://localhost:8080/parent-meetings', NULL, NULL, 0, '2026-07-26 06:23:31', '2026-07-26 06:23:31');

-- --------------------------------------------------------

--
-- Table structure for table `parent_meetings`
--

CREATE TABLE IF NOT EXISTS `parent_meetings` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `expected_parents` int(10) UNSIGNED NOT NULL,
  `actual_attendance` int(10) UNSIGNED DEFAULT NULL,
  `attendance_rate` decimal(5,2) DEFAULT NULL,
  `attendance_file_path` varchar(500) DEFAULT NULL,
  `attendance_file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parent_meetings`
--

INSERT IGNORE INTO `parent_meetings` (`id`, `title`, `date`, `expected_parents`, `actual_attendance`, `attendance_rate`, `attendance_file_path`, `attendance_file_name`, `created_at`) VALUES
(2, 'Recognition for Grade 7', '2026-07-31', 450, NULL, NULL, NULL, NULL, '2026-07-26 06:23:31');

-- --------------------------------------------------------

--
-- Table structure for table `performance_by_level`
--

CREATE TABLE IF NOT EXISTS `performance_by_level` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `term` tinyint(3) UNSIGNED DEFAULT NULL,
  `grade_level` varchar(50) NOT NULL,
  `mps` decimal(5,2) NOT NULL,
  `nds` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `perf_level_year_term_grade` (`school_year`,`term`,`grade_level`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `performance_by_level`
--

INSERT IGNORE INTO `performance_by_level` (`id`, `school_year`, `term`, `grade_level`, `mps`, `nds`) VALUES
(1, '2024-2025', 1, 'Grade 7', 63.50, NULL),
(2, '2024-2025', 1, 'Grade 8', 59.82, NULL),
(3, '2024-2025', 1, 'Grade 9', 65.54, NULL),
(4, '2024-2025', 1, 'Grade 10', 61.70, NULL),
(5, '2024-2025', 2, 'Grade 7', 65.20, NULL),
(6, '2024-2025', 2, 'Grade 8', 62.71, NULL),
(7, '2024-2025', 2, 'Grade 9', 66.69, NULL),
(8, '2024-2025', 2, 'Grade 10', 62.59, NULL),
(9, '2024-2025', 3, 'Grade 7', 72.85, NULL),
(10, '2024-2025', 3, 'Grade 8', 67.47, NULL),
(11, '2024-2025', 3, 'Grade 9', 72.11, NULL),
(12, '2024-2025', 3, 'Grade 10', 67.13, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `performance_by_subject`
--

CREATE TABLE IF NOT EXISTS `performance_by_subject` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `term` tinyint(3) UNSIGNED DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `mps` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `perf_subject_year_term_subject_grade` (`school_year`,`term`,`subject`,`grade_level`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `performance_by_subject`
--

INSERT IGNORE INTO `performance_by_subject` (`id`, `school_year`, `term`, `subject`, `grade_level`, `instructor`, `mps`) VALUES
(1, '2024-2025', 1, 'English', 'Grade 7', 'Subject Teacher', 62.10),
(2, '2024-2025', 1, 'Filipino', 'Grade 7', 'Subject Teacher', 69.56),
(3, '2024-2025', 1, 'Science', 'Grade 7', 'Subject Teacher', 64.59),
(4, '2024-2025', 1, 'Mathematics', 'Grade 7', 'Subject Teacher', 47.97),
(5, '2024-2025', 1, 'AP', 'Grade 7', 'Subject Teacher', 61.20),
(6, '2024-2025', 1, 'TLE', 'Grade 7', 'Subject Teacher', 62.57),
(7, '2024-2025', 1, 'MAPEH', 'Grade 7', 'Subject Teacher', 59.62),
(8, '2024-2025', 1, 'ESP', 'Grade 7', 'Subject Teacher', 80.35),
(9, '2024-2025', 1, 'English', 'Grade 8', 'Subject Teacher', 68.50),
(10, '2024-2025', 1, 'Filipino', 'Grade 8', 'Subject Teacher', 62.22),
(11, '2024-2025', 1, 'Science', 'Grade 8', 'Subject Teacher', 37.11),
(12, '2024-2025', 1, 'Mathematics', 'Grade 8', 'Subject Teacher', 38.17),
(13, '2024-2025', 1, 'AP', 'Grade 8', 'Subject Teacher', 72.63),
(14, '2024-2025', 1, 'TLE', 'Grade 8', 'Subject Teacher', 71.11),
(15, '2024-2025', 1, 'MAPEH', 'Grade 8', 'Subject Teacher', 59.86),
(16, '2024-2025', 1, 'ESP', 'Grade 8', 'Subject Teacher', 68.92),
(17, '2024-2025', 1, 'English', 'Grade 9', 'Subject Teacher', 71.05),
(18, '2024-2025', 1, 'Filipino', 'Grade 9', 'Subject Teacher', 54.28),
(19, '2024-2025', 1, 'Science', 'Grade 9', 'Subject Teacher', 53.86),
(20, '2024-2025', 1, 'Mathematics', 'Grade 9', 'Subject Teacher', 62.83),
(21, '2024-2025', 1, 'AP', 'Grade 9', 'Subject Teacher', 63.80),
(22, '2024-2025', 1, 'TLE', 'Grade 9', 'Subject Teacher', 66.12),
(23, '2024-2025', 1, 'MAPEH', 'Grade 9', 'Subject Teacher', 72.53),
(24, '2024-2025', 1, 'ESP', 'Grade 9', 'Subject Teacher', 79.81),
(25, '2024-2025', 1, 'English', 'Grade 10', 'Subject Teacher', 67.90),
(26, '2024-2025', 1, 'Filipino', 'Grade 10', 'Subject Teacher', 57.88),
(27, '2024-2025', 1, 'Science', 'Grade 10', 'Subject Teacher', 55.74),
(28, '2024-2025', 1, 'Mathematics', 'Grade 10', 'Subject Teacher', 52.19),
(29, '2024-2025', 1, 'AP', 'Grade 10', 'Subject Teacher', 66.27),
(30, '2024-2025', 1, 'TLE', 'Grade 10', 'Subject Teacher', 68.13),
(31, '2024-2025', 1, 'MAPEH', 'Grade 10', 'Subject Teacher', 69.57),
(32, '2024-2025', 1, 'ESP', 'Grade 10', 'Subject Teacher', 55.88),
(33, '2024-2025', 2, 'English', 'Grade 7', 'Subject Teacher', 64.95),
(34, '2024-2025', 2, 'Filipino', 'Grade 7', 'Subject Teacher', 65.52),
(35, '2024-2025', 2, 'Science', 'Grade 7', 'Subject Teacher', 62.39),
(36, '2024-2025', 2, 'Mathematics', 'Grade 7', 'Subject Teacher', 54.32),
(37, '2024-2025', 2, 'AP', 'Grade 7', 'Subject Teacher', 70.21),
(38, '2024-2025', 2, 'TLE', 'Grade 7', 'Subject Teacher', 69.88),
(39, '2024-2025', 2, 'MAPEH', 'Grade 7', 'Subject Teacher', 52.85),
(40, '2024-2025', 2, 'ESP', 'Grade 7', 'Subject Teacher', 81.45),
(41, '2024-2025', 2, 'English', 'Grade 8', 'Subject Teacher', 70.53),
(42, '2024-2025', 2, 'Filipino', 'Grade 8', 'Subject Teacher', 64.88),
(43, '2024-2025', 2, 'Science', 'Grade 8', 'Subject Teacher', 42.45),
(44, '2024-2025', 2, 'Mathematics', 'Grade 8', 'Subject Teacher', 51.29),
(45, '2024-2025', 2, 'AP', 'Grade 8', 'Subject Teacher', 71.42),
(46, '2024-2025', 2, 'TLE', 'Grade 8', 'Subject Teacher', 70.44),
(47, '2024-2025', 2, 'MAPEH', 'Grade 8', 'Subject Teacher', 59.48),
(48, '2024-2025', 2, 'ESP', 'Grade 8', 'Subject Teacher', 71.21),
(49, '2024-2025', 2, 'English', 'Grade 9', 'Subject Teacher', 71.41),
(50, '2024-2025', 2, 'Filipino', 'Grade 9', 'Subject Teacher', 66.25),
(51, '2024-2025', 2, 'Science', 'Grade 9', 'Subject Teacher', 63.58),
(52, '2024-2025', 2, 'Mathematics', 'Grade 9', 'Subject Teacher', 55.90),
(53, '2024-2025', 2, 'AP', 'Grade 9', 'Subject Teacher', 59.52),
(54, '2024-2025', 2, 'TLE', 'Grade 9', 'Subject Teacher', 67.67),
(55, '2024-2025', 2, 'MAPEH', 'Grade 9', 'Subject Teacher', 69.36),
(56, '2024-2025', 2, 'ESP', 'Grade 9', 'Subject Teacher', 79.82),
(57, '2024-2025', 2, 'English', 'Grade 10', 'Subject Teacher', 67.93),
(58, '2024-2025', 2, 'Filipino', 'Grade 10', 'Subject Teacher', 64.00),
(59, '2024-2025', 2, 'Science', 'Grade 10', 'Subject Teacher', 56.99),
(60, '2024-2025', 2, 'Mathematics', 'Grade 10', 'Subject Teacher', 50.44),
(61, '2024-2025', 2, 'AP', 'Grade 10', 'Subject Teacher', 66.37),
(62, '2024-2025', 2, 'TLE', 'Grade 10', 'Subject Teacher', 64.34),
(63, '2024-2025', 2, 'MAPEH', 'Grade 10', 'Subject Teacher', 63.77),
(64, '2024-2025', 2, 'ESP', 'Grade 10', 'Subject Teacher', 66.90),
(65, '2024-2025', 3, 'English', 'Grade 7', 'Subject Teacher', 68.05),
(66, '2024-2025', 3, 'Filipino', 'Grade 7', 'Subject Teacher', 81.91),
(67, '2024-2025', 3, 'Science', 'Grade 7', 'Subject Teacher', 70.56),
(68, '2024-2025', 3, 'Mathematics', 'Grade 7', 'Subject Teacher', 61.13),
(69, '2024-2025', 3, 'AP', 'Grade 7', 'Subject Teacher', 71.94),
(70, '2024-2025', 3, 'TLE', 'Grade 7', 'Subject Teacher', 71.28),
(71, '2024-2025', 3, 'MAPEH', 'Grade 7', 'Subject Teacher', 65.80),
(72, '2024-2025', 3, 'ESP', 'Grade 7', 'Subject Teacher', 92.13),
(73, '2024-2025', 3, 'English', 'Grade 8', 'Subject Teacher', 74.27),
(74, '2024-2025', 3, 'Filipino', 'Grade 8', 'Subject Teacher', 61.20),
(75, '2024-2025', 3, 'Science', 'Grade 8', 'Subject Teacher', 46.02),
(76, '2024-2025', 3, 'Mathematics', 'Grade 8', 'Subject Teacher', 65.27),
(77, '2024-2025', 3, 'AP', 'Grade 8', 'Subject Teacher', 71.42),
(78, '2024-2025', 3, 'TLE', 'Grade 8', 'Subject Teacher', 82.24),
(79, '2024-2025', 3, 'MAPEH', 'Grade 8', 'Subject Teacher', 72.89),
(80, '2024-2025', 3, 'ESP', 'Grade 8', 'Subject Teacher', 66.41),
(81, '2024-2025', 3, 'English', 'Grade 9', 'Subject Teacher', 71.59),
(82, '2024-2025', 3, 'Filipino', 'Grade 9', 'Subject Teacher', 74.53),
(83, '2024-2025', 3, 'Science', 'Grade 9', 'Subject Teacher', 61.12),
(84, '2024-2025', 3, 'Mathematics', 'Grade 9', 'Subject Teacher', 68.91),
(85, '2024-2025', 3, 'AP', 'Grade 9', 'Subject Teacher', 69.90),
(86, '2024-2025', 3, 'TLE', 'Grade 9', 'Subject Teacher', 68.13),
(87, '2024-2025', 3, 'MAPEH', 'Grade 9', 'Subject Teacher', 78.90),
(88, '2024-2025', 3, 'ESP', 'Grade 9', 'Subject Teacher', 83.79),
(89, '2024-2025', 3, 'English', 'Grade 10', 'Subject Teacher', 70.44),
(90, '2024-2025', 3, 'Filipino', 'Grade 10', 'Subject Teacher', 72.54),
(91, '2024-2025', 3, 'Science', 'Grade 10', 'Subject Teacher', 60.28),
(92, '2024-2025', 3, 'Mathematics', 'Grade 10', 'Subject Teacher', 51.73),
(93, '2024-2025', 3, 'AP', 'Grade 10', 'Subject Teacher', 71.15),
(94, '2024-2025', 3, 'TLE', 'Grade 10', 'Subject Teacher', 67.84),
(95, '2024-2025', 3, 'MAPEH', 'Grade 10', 'Subject Teacher', 74.76),
(96, '2024-2025', 3, 'ESP', 'Grade 10', 'Subject Teacher', 68.28);

-- --------------------------------------------------------

--
-- Table structure for table `room_properties`
--

CREATE TABLE IF NOT EXISTS `room_properties` (
  `id` int(10) UNSIGNED NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `building_name` varchar(100) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `condition_status` enum('Excellent','Good','Fair','Poor') NOT NULL,
  `last_inspection` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_role` enum('teacher','adas','specific') NOT NULL,
  `deadline` datetime NOT NULL,
  `status` enum('Open','Closed') DEFAULT 'Open',
  `created_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT IGNORE INTO `tasks` (`id`, `title`, `description`, `assigned_role`, `deadline`, `status`, `created_by`, `created_at`) VALUES
(1, 'Submit Q1 DLL', 'must be done till tomorrow', 'teacher', '2026-07-26 00:00:00', 'Closed', 'Jorge Bautista', '2026-07-25 07:36:05');

-- --------------------------------------------------------

--
-- Table structure for table `task_assignees`
--

CREATE TABLE IF NOT EXISTS `task_assignees` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_user_unique` (`task_id`,`user_id`),
  KEY `task_assignees_user_fk` (`user_id`),
  CONSTRAINT `task_assignees_task_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_assignees_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_feedback`
--

CREATE TABLE IF NOT EXISTS `task_feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_submission_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_submission_id` (`task_submission_id`),
  CONSTRAINT `task_feedback_ibfk_1` FOREIGN KEY (`task_submission_id`) REFERENCES `task_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_submissions`
--

CREATE TABLE IF NOT EXISTS `task_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Submitted','Reviewed') DEFAULT 'Submitted',
  `submitted_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id` (`task_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_submissions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_submissions`
--

INSERT IGNORE INTO `task_submissions` (`id`, `task_id`, `user_id`, `file_path`, `file_name`, `notes`, `status`, `submitted_at`, `created_at`) VALUES
(1, 1, 15, 'C:\\Users\\Huawei Matebook\\Desktop\\Acadocs1\\php\\writable\\uploads/tasks/1\\1784965642_02b99ff6b28f5020c497.pdf', 'Basic-Ed-Enrollment-Form.pdf', '', 'Submitted', '2026-07-25 16:23:26', '2026-07-25 07:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `task_submission_files`
--

CREATE TABLE IF NOT EXISTS `task_submission_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_submission_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_submission_id` (`task_submission_id`),
  CONSTRAINT `task_submission_files_ibfk_1` FOREIGN KEY (`task_submission_id`) REFERENCES `task_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_submission_files`
--

INSERT IGNORE INTO `task_submission_files` (`id`, `task_submission_id`, `file_path`, `file_name`, `created_at`) VALUES
(5, 1, 'C:\\Users\\Huawei Matebook\\Desktop\\Acadocs1\\php\\writable\\uploads/tasks/1\\1784996606_60e3b5324329f0c0d8bb.jpg', '777639.jpg', '2026-07-25 16:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `submission_rate` decimal(5,2) DEFAULT 0.00,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT IGNORE INTO `teachers` (`id`, `employee_id`, `name`, `email`, `grade_level`, `submission_rate`, `user_id`, `created_at`) VALUES
(1, 'T-005', 'Judith Abitong', 'judith.abitong@deped.gov.ph', NULL, 0.00, 8, '2026-07-25 02:39:20'),
(2, 'T-006', 'Elizabeth Badillo', 'elizabeth.amado@deped.gov.ph', NULL, 0.00, 9, '2026-07-25 02:39:20'),
(3, 'T-012', 'Mark Clinton Borja', 'mark.borja@deped.gov.ph', NULL, 0.00, 11, '2026-07-25 02:39:20'),
(4, 'T-014', 'Judith De Villa', 'judith.devilla001@deped.gov.ph', NULL, 0.00, 12, '2026-07-25 02:39:20'),
(5, 'T-018', 'Porferia Dela Guerra', 'porferia.delaguerra002@deped.gov.ph', NULL, 0.00, 13, '2026-07-25 02:39:20'),
(6, 'T-010', 'Maureen Layca Delos Reyes', 'maureenlayca.delosreyes@deped.gov.ph', NULL, 0.00, 14, '2026-07-25 02:39:20'),
(7, 'T-033', 'Remelyn Diaz', 'remelyn.labajo@deped.gov.ph', NULL, 0.00, 15, '2026-07-25 02:39:20'),
(8, 'T-023', 'Jimmilyn Fameronag', 'jimmilyn.fameronag@deped.gov.ph', NULL, 0.00, 16, '2026-07-25 02:39:20'),
(9, 'T-024', 'Jerico Fameronag', 'jerico.fameronag@deped.gov.ph', NULL, 0.00, 17, '2026-07-25 02:39:20'),
(10, 'T-016', 'Merian Gonzales', 'merian.gonzales@deped.gov.ph', NULL, 0.00, 18, '2026-07-25 02:39:20'),
(11, 'T-045', 'John Carlo Hernandez', 'johncarlo.hernandez@deped.gov.ph', NULL, 0.00, 19, '2026-07-25 02:39:20'),
(12, 'T-038', 'Abegail Incilan', 'abegail.incilan@deped.gov.ph', NULL, 0.00, 20, '2026-07-25 02:39:20'),
(13, 'T-036', 'Agnes Javier', 'agnes.javier004@deped.gov.ph', NULL, 0.00, 21, '2026-07-25 02:39:20'),
(14, 'T-044', 'Danica Roma Javier', 'danica.javier@deped.gov.ph', NULL, 0.00, 22, '2026-07-25 02:39:20'),
(15, 'T-011', 'Nancy Maano', 'maano.nancy.noceda@gmail.com', NULL, 0.00, 23, '2026-07-25 02:39:20'),
(16, 'T-022', 'Michael Macalindong', 'michael.macalindong@deped.gov.ph', NULL, 0.00, 24, '2026-07-25 02:39:20'),
(17, 'T-015', 'Rhea Magyaya', 'rhea.magyaya@deped.gov.ph', NULL, 0.00, 25, '2026-07-25 02:39:20'),
(18, 'T-008', 'Beverly Iodine Mapa', 'beverlyiodine.mapa001@deped.gov.ph', NULL, 0.00, 27, '2026-07-25 02:39:20'),
(19, 'T-007', 'Evangeline Mendoza', 'evangeline.mendoza011@deped.gov.ph', NULL, 0.00, 28, '2026-07-25 02:39:20'),
(20, 'T-017', 'Ruelito Mendoza', 'ruelito.mendoza002@deped.gov.ph', NULL, 0.00, 29, '2026-07-25 02:39:20'),
(21, 'T-029', 'Robelyn Ordonia', 'robelyn.ordonia@deped.gov.ph', NULL, 0.00, 30, '2026-07-25 02:39:20'),
(22, 'T-001', 'Angelique Piscal', 'angelique.piscal@deped.gov.ph', NULL, 0.00, 31, '2026-07-25 02:39:20'),
(23, 'T-020', 'Rechelle Ramos', 'rechelle.ramos001@deped.gov.ph', NULL, 0.00, 32, '2026-07-25 02:39:20'),
(24, 'T-021', 'Joanne Ricalde', 'joanne.ricalde@deped.gov.ph', NULL, 0.00, 33, '2026-07-25 02:39:20'),
(25, 'T-004', 'Gil Robles', 'gil.robles001@deped.gov.ph', NULL, 0.00, 34, '2026-07-25 02:39:20'),
(26, 'T-031', 'Annie Rollon', 'annie.delavega001@deped.gov.ph', NULL, 0.00, 35, '2026-07-25 02:39:20'),
(27, 'T-009', 'Edmarie Sagala', 'edmarie.sagala001@deped.gov.ph', NULL, 0.00, 36, '2026-07-25 02:39:20'),
(28, 'T-019', 'Julius Salviejo', 'julius.salviejo@deped.gov.ph', NULL, 0.00, 37, '2026-07-25 02:39:20'),
(29, 'T-027', 'Shiela Mae Sanchez', 'shielamae.sanchez002@deped.gov.ph', NULL, 0.00, 38, '2026-07-25 02:39:20'),
(30, 'T-013', 'Geryl Sandoval', 'geryl.aguila@deped.gov.ph', NULL, 0.00, 39, '2026-07-25 02:39:20'),
(31, 'T-002', 'Jorge Taguibao', 'jorge.taguibao@deped.gov.ph', NULL, 0.00, 40, '2026-07-25 02:39:20'),
(32, 'T-003', 'Joy Valdez', 'joy.valdez003@deped.gov.ph', NULL, 0.00, 41, '2026-07-25 02:39:20');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE IF NOT EXISTS `teacher_subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE IF NOT EXISTS `templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_ext` varchar(20) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` varchar(100) NOT NULL,
  `date_added` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `template_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `templates`
--

INSERT IGNORE INTO `templates` (`id`, `category_id`, `title`, `description`, `file_path`, `file_name`, `file_ext`, `file_size`, `uploaded_by`, `date_added`, `created_at`) VALUES
(1, 1, 'CERTIFICATE', '', 'C:\\Users\\Huawei Matebook\\Desktop\\Acadocs1\\php\\writable\\uploads/templates/1\\1784966595_8a72451447d84b77a049.docx', 'CERTIFICATE.docx', 'docx', 1114356, 'Rhonnel Magyaya', '2026-07-25', '2026-07-25 08:03:15');

-- --------------------------------------------------------

--
-- Table structure for table `template_categories`
--

CREATE TABLE IF NOT EXISTS `template_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `template_categories`
--

INSERT IGNORE INTO `template_categories` (`id`, `name`, `created_by`, `created_at`) VALUES
(1, 'Certificate', 'System', '2026-07-24 14:26:21'),
(2, 'BAC Forms', 'System', '2026-07-24 14:26:21'),
(3, 'Research Template', 'System', '2026-07-24 14:26:21'),
(4, 'Travel-Records Checklist', 'System', '2026-07-24 14:26:21'),
(5, 'Curriculum Implementation Division-CID', 'System', '2026-07-24 14:26:21'),
(6, 'COA Forms', 'System', '2026-07-24 14:26:21'),
(7, 'HRD Forms', 'System', '2026-07-24 14:26:21');

-- --------------------------------------------------------

--
-- Table structure for table `time_records`
--

CREATE TABLE IF NOT EXISTS `time_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Late','Absent','On Leave') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `time_records_employee_id_date` (`employee_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_records`
--

INSERT IGNORE INTO `time_records` (`id`, `date`, `employee_name`, `employee_id`, `time_in`, `time_out`, `status`, `remarks`, `created_at`) VALUES
(19, '2026-07-13', 'Angelique Piscal', 'AC-1', '06:48:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(20, '2026-07-14', 'Angelique Piscal', 'AC-1', '06:17:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(21, '2026-07-15', 'Angelique Piscal', 'AC-1', '06:45:00', '17:32:00', 'Present', '', '2026-07-25 02:54:27'),
(22, '2026-07-16', 'Angelique Piscal', 'AC-1', '06:35:00', '18:12:00', 'Present', '', '2026-07-25 02:54:27'),
(23, '2026-07-17', 'Angelique Piscal', 'AC-1', '06:47:00', '17:26:00', 'Present', '', '2026-07-25 02:54:27'),
(24, '2026-07-13', 'Jorge Taguibao', 'AC-2', '06:44:00', '16:37:00', 'Present', '', '2026-07-25 02:54:27'),
(25, '2026-07-14', 'Jorge Taguibao', 'AC-2', '06:52:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(26, '2026-07-15', 'Jorge Taguibao', 'AC-2', '07:02:00', '15:31:00', 'Present', '', '2026-07-25 02:54:27'),
(27, '2026-07-16', 'Jorge Taguibao', 'AC-2', '06:33:00', '15:41:00', 'Present', '', '2026-07-25 02:54:27'),
(28, '2026-07-17', 'Jorge Taguibao', 'AC-2', '06:40:00', '15:32:00', 'Present', '', '2026-07-25 02:54:27'),
(29, '2026-07-13', 'Joy Valdez', 'AC-3', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(30, '2026-07-14', 'Joy Valdez', 'AC-3', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(31, '2026-07-15', 'Joy Valdez', 'AC-3', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(32, '2026-07-16', 'Joy Valdez', 'AC-3', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(33, '2026-07-17', 'Joy Valdez', 'AC-3', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(34, '2026-07-13', 'Gil Robles', 'AC-4', '06:50:00', '16:54:00', 'Present', '', '2026-07-25 02:54:27'),
(35, '2026-07-14', 'Gil Robles', 'AC-4', '06:15:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(36, '2026-07-15', 'Gil Robles', 'AC-4', '07:19:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(37, '2026-07-16', 'Gil Robles', 'AC-4', '07:04:00', '15:11:00', 'Present', '', '2026-07-25 02:54:27'),
(38, '2026-07-17', 'Gil Robles', 'AC-4', '06:56:00', '15:31:00', 'Present', '', '2026-07-25 02:54:27'),
(44, '2026-07-13', 'Elizabeth Badillo', 'AC-6', '06:37:00', '16:37:00', 'Present', '', '2026-07-25 02:54:27'),
(45, '2026-07-14', 'Elizabeth Badillo', 'AC-6', '06:26:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(46, '2026-07-15', 'Elizabeth Badillo', 'AC-6', '06:27:00', '16:23:00', 'Present', '', '2026-07-25 02:54:27'),
(47, '2026-07-16', 'Elizabeth Badillo', 'AC-6', '06:16:00', '16:49:00', 'Present', '', '2026-07-25 02:54:27'),
(48, '2026-07-17', 'Elizabeth Badillo', 'AC-6', '06:17:00', '15:30:00', 'Present', '', '2026-07-25 02:54:27'),
(49, '2026-07-13', 'Evangeline Mendoza', 'AC-7', '06:29:00', '16:37:00', 'Present', '', '2026-07-25 02:54:27'),
(50, '2026-07-14', 'Evangeline Mendoza', 'AC-7', '06:28:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(51, '2026-07-15', 'Evangeline Mendoza', 'AC-7', '06:28:00', '16:14:00', 'Present', '', '2026-07-25 02:54:27'),
(52, '2026-07-16', 'Evangeline Mendoza', 'AC-7', '06:32:00', '15:10:00', 'Present', '', '2026-07-25 02:54:27'),
(53, '2026-07-17', 'Evangeline Mendoza', 'AC-7', '06:37:00', '16:43:00', 'Present', '', '2026-07-25 02:54:27'),
(54, '2026-07-13', 'Beverly Iodine Mapa', 'AC-8', '07:00:00', '17:09:00', 'Present', '', '2026-07-25 02:54:27'),
(55, '2026-07-14', 'Beverly Iodine Mapa', 'AC-8', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(56, '2026-07-15', 'Beverly Iodine Mapa', 'AC-8', '07:01:00', '17:32:00', 'Present', '', '2026-07-25 02:54:27'),
(57, '2026-07-16', 'Beverly Iodine Mapa', 'AC-8', '06:59:00', '15:41:00', 'Present', '', '2026-07-25 02:54:27'),
(58, '2026-07-17', 'Beverly Iodine Mapa', 'AC-8', '07:03:00', '16:46:00', 'Present', '', '2026-07-25 02:54:27'),
(59, '2026-07-13', 'Edmarie Sagala', 'AC-9', '05:59:00', '16:38:00', 'Present', '', '2026-07-25 02:54:27'),
(60, '2026-07-14', 'Edmarie Sagala', 'AC-9', '06:05:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(61, '2026-07-15', 'Edmarie Sagala', 'AC-9', '06:11:00', '18:15:00', 'Present', 'Multiple punches collapsed to earliest/latest (import)', '2026-07-25 02:54:27'),
(62, '2026-07-16', 'Edmarie Sagala', 'AC-9', '06:17:00', '15:31:00', 'Present', '', '2026-07-25 02:54:27'),
(63, '2026-07-17', 'Edmarie Sagala', 'AC-9', '06:19:00', '18:24:00', 'Present', '', '2026-07-25 02:54:27'),
(64, '2026-07-13', 'Maureen Layca Delos Reyes', 'AC-10', '06:43:00', '16:36:00', 'Present', '', '2026-07-25 02:54:27'),
(65, '2026-07-14', 'Maureen Layca Delos Reyes', 'AC-10', '06:41:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(66, '2026-07-15', 'Maureen Layca Delos Reyes', 'AC-10', '06:42:00', '15:07:00', 'Present', '', '2026-07-25 02:54:27'),
(67, '2026-07-16', 'Maureen Layca Delos Reyes', 'AC-10', '06:45:00', '15:16:00', 'Present', '', '2026-07-25 02:54:27'),
(68, '2026-07-17', 'Maureen Layca Delos Reyes', 'AC-10', '06:46:00', '15:28:00', 'Present', '', '2026-07-25 02:54:27'),
(69, '2026-07-13', 'Nancy Maano', 'AC-11', '06:07:00', '16:38:00', 'Present', 'Multiple punches collapsed to earliest/latest (import)', '2026-07-25 02:54:27'),
(70, '2026-07-14', 'Nancy Maano', 'AC-11', '06:18:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(71, '2026-07-15', 'Nancy Maano', 'AC-11', '06:28:00', '15:22:00', 'Present', '', '2026-07-25 02:54:27'),
(72, '2026-07-16', 'Nancy Maano', 'AC-11', '06:23:00', '15:41:00', 'Present', '', '2026-07-25 02:54:27'),
(73, '2026-07-17', 'Nancy Maano', 'AC-11', '06:26:00', '15:25:00', 'Present', '', '2026-07-25 02:54:27'),
(74, '2026-07-13', 'Mark Clinton Borja', 'AC-12', '06:39:00', '18:45:00', 'Present', '', '2026-07-25 02:54:27'),
(75, '2026-07-14', 'Mark Clinton Borja', 'AC-12', '06:30:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(76, '2026-07-15', 'Mark Clinton Borja', 'AC-12', '06:25:00', '18:46:00', 'Present', '', '2026-07-25 02:54:27'),
(77, '2026-07-16', 'Mark Clinton Borja', 'AC-12', '06:28:00', '18:38:00', 'Present', '', '2026-07-25 02:54:27'),
(78, '2026-07-17', 'Mark Clinton Borja', 'AC-12', '06:35:00', '18:49:00', 'Present', '', '2026-07-25 02:54:27'),
(79, '2026-07-13', 'Geryl Sandoval', 'AC-13', '05:52:00', '16:55:00', 'Present', '', '2026-07-25 02:54:27'),
(80, '2026-07-14', 'Geryl Sandoval', 'AC-13', '06:15:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(81, '2026-07-15', 'Geryl Sandoval', 'AC-13', '06:25:00', '17:08:00', 'Present', '', '2026-07-25 02:54:27'),
(82, '2026-07-16', 'Geryl Sandoval', 'AC-13', '06:28:00', '15:30:00', 'Present', '', '2026-07-25 02:54:27'),
(83, '2026-07-17', 'Geryl Sandoval', 'AC-13', '06:37:00', '17:08:00', 'Present', '', '2026-07-25 02:54:27'),
(84, '2026-07-13', 'Judith De Villa', 'AC-14', '06:30:00', '16:41:00', 'Present', '', '2026-07-25 02:54:27'),
(85, '2026-07-14', 'Judith De Villa', 'AC-14', '06:41:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(86, '2026-07-15', 'Judith De Villa', 'AC-14', '06:39:00', '16:30:00', 'Present', '', '2026-07-25 02:54:27'),
(87, '2026-07-16', 'Judith De Villa', 'AC-14', '06:37:00', '15:39:00', 'Present', '', '2026-07-25 02:54:27'),
(88, '2026-07-17', 'Judith De Villa', 'AC-14', '06:44:00', '15:18:00', 'Present', '', '2026-07-25 02:54:27'),
(89, '2026-07-13', 'Rhea Magyaya', 'AC-15', '06:51:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(90, '2026-07-14', 'Rhea Magyaya', 'AC-15', '06:46:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(91, '2026-07-15', 'Rhea Magyaya', 'AC-15', '06:58:00', '15:21:00', 'Present', '', '2026-07-25 02:54:27'),
(92, '2026-07-16', 'Rhea Magyaya', 'AC-15', '06:59:00', '16:52:00', 'Present', '', '2026-07-25 02:54:27'),
(93, '2026-07-17', 'Rhea Magyaya', 'AC-15', '08:36:00', '15:26:00', 'Late', '', '2026-07-25 02:54:27'),
(94, '2026-07-13', 'Merian Gonzales', 'AC-16', '06:59:00', '16:37:00', 'Present', '', '2026-07-25 02:54:27'),
(95, '2026-07-14', 'Merian Gonzales', 'AC-16', '06:32:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(96, '2026-07-15', 'Merian Gonzales', 'AC-16', '06:39:00', '16:01:00', 'Present', '', '2026-07-25 02:54:27'),
(97, '2026-07-16', 'Merian Gonzales', 'AC-16', '07:00:00', '15:39:00', 'Present', '', '2026-07-25 02:54:27'),
(98, '2026-07-17', 'Merian Gonzales', 'AC-16', '06:54:00', '14:35:00', 'Present', '', '2026-07-25 02:54:27'),
(99, '2026-07-13', 'Ruelito Mendoza', 'AC-17', '06:48:00', '16:36:00', 'Present', '', '2026-07-25 02:54:27'),
(100, '2026-07-14', 'Ruelito Mendoza', 'AC-17', '06:20:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(101, '2026-07-15', 'Ruelito Mendoza', 'AC-17', '06:41:00', '15:58:00', 'Present', '', '2026-07-25 02:54:27'),
(102, '2026-07-16', 'Ruelito Mendoza', 'AC-17', '06:31:00', '15:35:00', 'Present', '', '2026-07-25 02:54:27'),
(103, '2026-07-17', 'Ruelito Mendoza', 'AC-17', '06:43:00', '16:12:00', 'Present', '', '2026-07-25 02:54:27'),
(104, '2026-07-13', 'Porferia Dela Guerra', 'AC-18', '06:37:00', '16:47:00', 'Present', '', '2026-07-25 02:54:27'),
(105, '2026-07-14', 'Porferia Dela Guerra', 'AC-18', '06:40:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(106, '2026-07-15', 'Porferia Dela Guerra', 'AC-18', '06:50:00', '17:06:00', 'Present', '', '2026-07-25 02:54:27'),
(107, '2026-07-16', 'Porferia Dela Guerra', 'AC-18', '06:36:00', '16:18:00', 'Present', '', '2026-07-25 02:54:27'),
(108, '2026-07-17', 'Porferia Dela Guerra', 'AC-18', '06:48:00', '16:25:00', 'Present', '', '2026-07-25 02:54:27'),
(109, '2026-07-13', 'Julius Salviejo', 'AC-19', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(110, '2026-07-14', 'Julius Salviejo', 'AC-19', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(111, '2026-07-15', 'Julius Salviejo', 'AC-19', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(112, '2026-07-16', 'Julius Salviejo', 'AC-19', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(113, '2026-07-17', 'Julius Salviejo', 'AC-19', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(114, '2026-07-13', 'Rechelle Ramos', 'AC-20', '06:31:00', '17:14:00', 'Present', '', '2026-07-25 02:54:27'),
(115, '2026-07-14', 'Rechelle Ramos', 'AC-20', '06:26:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(116, '2026-07-15', 'Rechelle Ramos', 'AC-20', '06:15:00', '16:00:00', 'Present', '', '2026-07-25 02:54:27'),
(117, '2026-07-16', 'Rechelle Ramos', 'AC-20', '06:18:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(118, '2026-07-17', 'Rechelle Ramos', 'AC-20', '06:27:00', '17:18:00', 'Present', '', '2026-07-25 02:54:27'),
(119, '2026-07-13', 'Joanne Ricalde', 'AC-21', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(120, '2026-07-14', 'Joanne Ricalde', 'AC-21', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(121, '2026-07-15', 'Joanne Ricalde', 'AC-21', '07:38:00', '17:54:00', 'Late', '', '2026-07-25 02:54:27'),
(122, '2026-07-16', 'Joanne Ricalde', 'AC-21', '06:48:00', '17:42:00', 'Present', '', '2026-07-25 02:54:27'),
(123, '2026-07-17', 'Joanne Ricalde', 'AC-21', '06:58:00', '15:18:00', 'Present', '', '2026-07-25 02:54:27'),
(124, '2026-07-13', 'Michael Macalindong', 'AC-22', '06:39:00', '16:48:00', 'Present', '', '2026-07-25 02:54:27'),
(125, '2026-07-14', 'Michael Macalindong', 'AC-22', '06:41:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(126, '2026-07-15', 'Michael Macalindong', 'AC-22', '06:52:00', '15:19:00', 'Present', '', '2026-07-25 02:54:27'),
(127, '2026-07-16', 'Michael Macalindong', 'AC-22', '06:41:00', '15:41:00', 'Present', '', '2026-07-25 02:54:27'),
(128, '2026-07-17', 'Michael Macalindong', 'AC-22', '06:37:00', '15:24:00', 'Present', '', '2026-07-25 02:54:27'),
(129, '2026-07-13', 'Jimmilyn Fameronag', 'AC-23', '06:49:00', '16:46:00', 'Present', '', '2026-07-25 02:54:27'),
(130, '2026-07-14', 'Jimmilyn Fameronag', 'AC-23', '06:42:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(131, '2026-07-15', 'Jimmilyn Fameronag', 'AC-23', '06:47:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(132, '2026-07-16', 'Jimmilyn Fameronag', 'AC-23', '06:49:00', '15:41:00', 'Present', '', '2026-07-25 02:54:27'),
(133, '2026-07-17', 'Jimmilyn Fameronag', 'AC-23', '06:57:00', '15:31:00', 'Present', '', '2026-07-25 02:54:27'),
(134, '2026-07-13', 'Jerico Fameronag', 'AC-24', '06:50:00', '16:46:00', 'Present', '', '2026-07-25 02:54:27'),
(135, '2026-07-14', 'Jerico Fameronag', 'AC-24', '06:43:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(136, '2026-07-15', 'Jerico Fameronag', 'AC-24', '06:48:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(137, '2026-07-16', 'Jerico Fameronag', 'AC-24', '06:51:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(138, '2026-07-17', 'Jerico Fameronag', 'AC-24', '07:01:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(149, '2026-07-13', 'Shiela Mae Sanchez', 'AC-27', '06:40:00', '16:39:00', 'Present', '', '2026-07-25 02:54:27'),
(150, '2026-07-14', 'Shiela Mae Sanchez', 'AC-27', '06:43:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(151, '2026-07-15', 'Shiela Mae Sanchez', 'AC-27', '06:44:00', '16:18:00', 'Present', '', '2026-07-25 02:54:27'),
(152, '2026-07-16', 'Shiela Mae Sanchez', 'AC-27', '06:43:00', '16:02:00', 'Present', '', '2026-07-25 02:54:27'),
(153, '2026-07-17', 'Shiela Mae Sanchez', 'AC-27', '07:12:00', '15:30:00', 'Present', '', '2026-07-25 02:54:27'),
(154, '2026-07-13', 'VERGARA, RYAN B.', 'AC-28', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(155, '2026-07-14', 'VERGARA, RYAN B.', 'AC-28', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(156, '2026-07-15', 'VERGARA, RYAN B.', 'AC-28', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(157, '2026-07-16', 'VERGARA, RYAN B.', 'AC-28', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(158, '2026-07-17', 'VERGARA, RYAN B.', 'AC-28', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(159, '2026-07-13', 'Robelyn Ordonia', 'AC-29', '06:45:00', '16:51:00', 'Present', '', '2026-07-25 02:54:27'),
(160, '2026-07-14', 'Robelyn Ordonia', 'AC-29', '06:38:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(161, '2026-07-15', 'Robelyn Ordonia', 'AC-29', '06:52:00', '16:01:00', 'Present', '', '2026-07-25 02:54:27'),
(162, '2026-07-16', 'Robelyn Ordonia', 'AC-29', '06:37:00', '13:01:00', 'Present', '', '2026-07-25 02:54:27'),
(163, '2026-07-17', 'Robelyn Ordonia', 'AC-29', '06:47:00', '15:31:00', 'Present', '', '2026-07-25 02:54:27'),
(164, '2026-07-13', 'Unmapped (AC-30)', 'AC-30', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(165, '2026-07-14', 'Unmapped (AC-30)', 'AC-30', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(166, '2026-07-15', 'Unmapped (AC-30)', 'AC-30', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(167, '2026-07-16', 'Unmapped (AC-30)', 'AC-30', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(168, '2026-07-17', 'Unmapped (AC-30)', 'AC-30', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(169, '2026-07-13', 'Annie Rollon', 'AC-31', '05:48:00', '16:41:00', 'Present', '', '2026-07-25 02:54:27'),
(170, '2026-07-14', 'Annie Rollon', 'AC-31', '05:49:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(171, '2026-07-15', 'Annie Rollon', 'AC-31', '05:50:00', '16:20:00', 'Present', '', '2026-07-25 02:54:27'),
(172, '2026-07-16', 'Annie Rollon', 'AC-31', '05:52:00', '17:02:00', 'Present', '', '2026-07-25 02:54:27'),
(173, '2026-07-17', 'Annie Rollon', 'AC-31', '05:44:00', '15:30:00', 'Present', '', '2026-07-25 02:54:27'),
(174, '2026-07-13', 'FAMERONAG, JIMMILYN', 'AC-32', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(175, '2026-07-14', 'FAMERONAG, JIMMILYN', 'AC-32', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(176, '2026-07-15', 'FAMERONAG, JIMMILYN', 'AC-32', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(177, '2026-07-16', 'FAMERONAG, JIMMILYN', 'AC-32', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(178, '2026-07-17', 'FAMERONAG, JIMMILYN', 'AC-32', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(179, '2026-07-13', 'Remelyn Diaz', 'AC-33', '07:31:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(180, '2026-07-14', 'Remelyn Diaz', 'AC-33', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(181, '2026-07-15', 'Remelyn Diaz', 'AC-33', '07:38:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(182, '2026-07-16', 'Remelyn Diaz', 'AC-33', '15:46:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(183, '2026-07-17', 'Remelyn Diaz', 'AC-33', '07:12:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(184, '2026-07-13', 'LUNDAG, ALFRED L.', 'AC-34', '06:19:00', '16:12:00', 'Present', '', '2026-07-25 02:54:27'),
(185, '2026-07-14', 'LUNDAG, ALFRED L.', 'AC-34', '06:20:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(186, '2026-07-15', 'LUNDAG, ALFRED L.', 'AC-34', '06:27:00', '16:21:00', 'Present', '', '2026-07-25 02:54:27'),
(187, '2026-07-16', 'LUNDAG, ALFRED L.', 'AC-34', '06:17:00', '16:14:00', 'Present', '', '2026-07-25 02:54:27'),
(188, '2026-07-17', 'LUNDAG, ALFRED L.', 'AC-34', '06:24:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(189, '2026-07-13', 'ROBLES, GIL G.', 'AC-35', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(190, '2026-07-14', 'ROBLES, GIL G.', 'AC-35', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(191, '2026-07-15', 'ROBLES, GIL G.', 'AC-35', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(192, '2026-07-16', 'ROBLES, GIL G.', 'AC-35', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(193, '2026-07-17', 'ROBLES, GIL G.', 'AC-35', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(194, '2026-07-13', 'Agnes Javier', 'AC-36', '06:08:00', '17:06:00', 'Present', '', '2026-07-25 02:54:27'),
(195, '2026-07-14', 'Agnes Javier', 'AC-36', '06:46:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(196, '2026-07-15', 'Agnes Javier', 'AC-36', '06:44:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(197, '2026-07-16', 'Agnes Javier', 'AC-36', '06:35:00', '17:33:00', 'Present', '', '2026-07-25 02:54:27'),
(198, '2026-07-17', 'Agnes Javier', 'AC-36', '06:46:00', '17:26:00', 'Present', '', '2026-07-25 02:54:27'),
(199, '2026-07-13', 'TESALONA, PEDRITO G.', 'AC-37', '06:33:00', '17:04:00', 'Present', '', '2026-07-25 02:54:27'),
(200, '2026-07-14', 'TESALONA, PEDRITO G.', 'AC-37', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(201, '2026-07-15', 'TESALONA, PEDRITO G.', 'AC-37', '08:14:00', '17:19:00', 'Late', '', '2026-07-25 02:54:27'),
(202, '2026-07-16', 'TESALONA, PEDRITO G.', 'AC-37', '08:15:00', '18:12:00', 'Late', '', '2026-07-25 02:54:27'),
(203, '2026-07-17', 'TESALONA, PEDRITO G.', 'AC-37', '08:19:00', '17:42:00', 'Late', '', '2026-07-25 02:54:27'),
(204, '2026-07-13', 'Abegail Incilan', 'AC-38', '06:37:00', '16:39:00', 'Present', '', '2026-07-25 02:54:27'),
(205, '2026-07-14', 'Abegail Incilan', 'AC-38', '06:42:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(206, '2026-07-15', 'Abegail Incilan', 'AC-38', '06:43:00', '16:18:00', 'Present', 'Multiple punches collapsed to earliest/latest (import)', '2026-07-25 02:54:27'),
(207, '2026-07-16', 'Abegail Incilan', 'AC-38', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(208, '2026-07-17', 'Abegail Incilan', 'AC-38', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(209, '2026-07-13', 'Unmapped (AC-39)', 'AC-39', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(210, '2026-07-14', 'Unmapped (AC-39)', 'AC-39', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(211, '2026-07-15', 'Unmapped (AC-39)', 'AC-39', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(212, '2026-07-16', 'Unmapped (AC-39)', 'AC-39', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(213, '2026-07-17', 'Unmapped (AC-39)', 'AC-39', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(214, '2026-07-13', 'PANGANIBAN, HENRY S.', 'AC-40', '05:34:00', '15:28:00', 'Present', '', '2026-07-25 02:54:27'),
(215, '2026-07-14', 'PANGANIBAN, HENRY S.', 'AC-40', '05:45:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(216, '2026-07-15', 'PANGANIBAN, HENRY S.', 'AC-40', '05:50:00', '16:05:00', 'Present', '', '2026-07-25 02:54:27'),
(217, '2026-07-16', 'PANGANIBAN, HENRY S.', 'AC-40', '05:42:00', '15:21:00', 'Present', '', '2026-07-25 02:54:27'),
(218, '2026-07-17', 'PANGANIBAN, HENRY S.', 'AC-40', '05:44:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:27'),
(219, '2026-07-13', 'DELOS REYES, JOANNE', 'AC-41', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(220, '2026-07-14', 'DELOS REYES, JOANNE', 'AC-41', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(221, '2026-07-15', 'DELOS REYES, JOANNE', 'AC-41', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(222, '2026-07-16', 'DELOS REYES, JOANNE', 'AC-41', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(223, '2026-07-17', 'DELOS REYES, JOANNE', 'AC-41', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(224, '2026-07-13', 'Unmapped (AC-42)', 'AC-42', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(225, '2026-07-14', 'Unmapped (AC-42)', 'AC-42', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:27'),
(226, '2026-07-15', 'Unmapped (AC-42)', 'AC-42', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(227, '2026-07-16', 'Unmapped (AC-42)', 'AC-42', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(228, '2026-07-17', 'Unmapped (AC-42)', 'AC-42', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(229, '2026-07-13', 'Unmapped (AC-43)', 'AC-43', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(230, '2026-07-14', 'Unmapped (AC-43)', 'AC-43', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(231, '2026-07-15', 'Unmapped (AC-43)', 'AC-43', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(232, '2026-07-16', 'Unmapped (AC-43)', 'AC-43', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(233, '2026-07-17', 'Unmapped (AC-43)', 'AC-43', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(234, '2026-07-13', 'Danica Roma Javier', 'AC-44', '07:34:00', '16:39:00', 'Late', '', '2026-07-25 02:54:28'),
(235, '2026-07-14', 'Danica Roma Javier', 'AC-44', '06:38:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:28'),
(236, '2026-07-15', 'Danica Roma Javier', 'AC-44', '07:47:00', '16:08:00', 'Late', '', '2026-07-25 02:54:28'),
(237, '2026-07-16', 'Danica Roma Javier', 'AC-44', '07:13:00', '15:42:00', 'Present', '', '2026-07-25 02:54:28'),
(238, '2026-07-17', 'Danica Roma Javier', 'AC-44', '08:00:00', '15:53:00', 'Late', '', '2026-07-25 02:54:28'),
(239, '2026-07-13', 'John Carlo Hernandez', 'AC-45', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(240, '2026-07-14', 'John Carlo Hernandez', 'AC-45', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(241, '2026-07-15', 'John Carlo Hernandez', 'AC-45', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(242, '2026-07-16', 'John Carlo Hernandez', 'AC-45', '17:16:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:54:28'),
(243, '2026-07-17', 'John Carlo Hernandez', 'AC-45', '07:50:00', '15:30:00', 'Late', '', '2026-07-25 02:54:28'),
(244, '2026-07-13', 'Unmapped (AC-46)', 'AC-46', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(245, '2026-07-14', 'Unmapped (AC-46)', 'AC-46', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(246, '2026-07-15', 'Unmapped (AC-46)', 'AC-46', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(247, '2026-07-16', 'Unmapped (AC-46)', 'AC-46', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(248, '2026-07-17', 'Unmapped (AC-46)', 'AC-46', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:54:28'),
(250, '2026-07-13', 'Judith Abitong', 'AC-5', '06:46:00', '16:39:00', 'Present', '', '2026-07-25 02:59:03'),
(251, '2026-07-14', 'Judith Abitong', 'AC-5', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:59:03'),
(252, '2026-07-15', 'Judith Abitong', 'AC-5', '06:48:00', '17:38:00', 'Present', 'Multiple punches collapsed to earliest/latest (import)', '2026-07-25 02:59:03'),
(253, '2026-07-16', 'Judith Abitong', 'AC-5', '06:48:00', '18:11:00', 'Present', '', '2026-07-25 02:59:03'),
(254, '2026-07-17', 'Judith Abitong', 'AC-5', '07:05:00', '14:28:00', 'Present', '', '2026-07-25 02:59:03'),
(255, '2026-07-13', 'Jorge Bautista', 'AC-25', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:59:03'),
(256, '2026-07-14', 'Jorge Bautista', 'AC-25', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:59:03'),
(257, '2026-07-15', 'Jorge Bautista', 'AC-25', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:59:03'),
(258, '2026-07-16', 'Jorge Bautista', 'AC-25', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:59:03'),
(259, '2026-07-17', 'Jorge Bautista', 'AC-25', '18:35:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:59:03'),
(260, '2026-07-13', 'Rhonnel Magyaya', 'AC-26', '06:51:00', '17:00:00', 'Present', '', '2026-07-25 02:59:03'),
(261, '2026-07-14', 'Rhonnel Magyaya', 'AC-26', NULL, NULL, 'Absent', 'No punches recorded (import)', '2026-07-25 02:59:03'),
(262, '2026-07-15', 'Rhonnel Magyaya', 'AC-26', '06:58:00', '17:18:00', 'Present', '', '2026-07-25 02:59:03'),
(263, '2026-07-16', 'Rhonnel Magyaya', 'AC-26', '08:41:00', '17:51:00', 'Late', '', '2026-07-25 02:59:03'),
(264, '2026-07-17', 'Rhonnel Magyaya', 'AC-26', '08:36:00', NULL, 'Present', 'Missing time-out (import)', '2026-07-25 02:59:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','secretary','adas') NOT NULL,
  `ac_no` varchar(20) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `ac_no` (`ac_no`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`, `ac_no`, `position`, `photo`, `created_at`) VALUES
(4, 'Carmen Lopez', 'secretary@school.edu', '$2y$10$iVemOAuYsrWtDhAe3BmrJe2qYKXSUFiTNFDH851mKd31CMTTxmDYi', 'adas', NULL, NULL, NULL, '2026-07-17 08:27:53'),
(8, 'Judith Abitong', 'judith.abitong@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '5', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(9, 'Elizabeth Badillo', 'elizabeth.amado@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '6', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(10, 'Jorge Bautista', 'jorge.bautista002@deped.gov.ph', '$2y$10$mZiXiBtaycSs2DmiVudkq.NT6PlyGsTBSLisKR0G6tH6HQzsVL2Au', 'admin', '25', 'Principal III', NULL, '2026-07-25 02:38:37'),
(11, 'Mark Clinton Borja', 'mark.borja@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '12', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(12, 'Judith De Villa', 'judith.devilla001@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '14', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(13, 'Porferia Dela Guerra', 'porferia.delaguerra002@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '18', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(14, 'Maureen Layca Delos Reyes', 'maureenlayca.delosreyes@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '10', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(15, 'Remelyn Diaz', 'remelyn.labajo@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '33', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(16, 'Jimmilyn Fameronag', 'jimmilyn.fameronag@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '23', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(17, 'Jerico Fameronag', 'jerico.fameronag@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '24', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(18, 'Merian Gonzales', 'merian.gonzales@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '16', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(19, 'John Carlo Hernandez', 'johncarlo.hernandez@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '45', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(20, 'Abegail Incilan', 'abegail.incilan@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '38', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(21, 'Agnes Javier', 'agnes.javier004@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '36', 'Master Teacher II', NULL, '2026-07-25 02:38:37'),
(22, 'Danica Roma Javier', 'danica.javier@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '44', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(23, 'Nancy Maano', 'maano.nancy.noceda@gmail.com', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '11', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(24, 'Michael Macalindong', 'michael.macalindong@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '22', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(25, 'Rhea Magyaya', 'rhea.magyaya@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '15', 'Master Teacher II', NULL, '2026-07-25 02:38:37'),
(26, 'Rhonnel Magyaya', 'rhonnel.magyaya@deped.gov.ph', '$2y$10$EoWuuEchqWy.jPRi50J/8e1ucyvKvJFL6xcq3YJAshBThctyEzOz.', 'adas', '26', 'ADAS II', NULL, '2026-07-25 02:38:37'),
(27, 'Beverly Iodine Mapa', 'beverlyiodine.mapa001@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '8', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(28, 'Evangeline Mendoza', 'evangeline.mendoza011@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '7', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(29, 'Ruelito Mendoza', 'ruelito.mendoza002@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '17', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(30, 'Robelyn Ordonia', 'robelyn.ordonia@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '29', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(31, 'Angelique Piscal', 'angelique.piscal@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '1', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(32, 'Rechelle Ramos', 'rechelle.ramos001@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '20', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(33, 'Joanne Ricalde', 'joanne.ricalde@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '21', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(34, 'Gil Robles', 'gil.robles001@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '4', 'Teacher II', NULL, '2026-07-25 02:38:37'),
(35, 'Annie Rollon', 'annie.delavega001@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '31', 'Teacher II', NULL, '2026-07-25 02:38:37'),
(36, 'Edmarie Sagala', 'edmarie.sagala001@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '9', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(37, 'Julius Salviejo', 'julius.salviejo@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '19', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(38, 'Shiela Mae Sanchez', 'shielamae.sanchez002@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '27', 'Teacher I', NULL, '2026-07-25 02:38:37'),
(39, 'Geryl Sandoval', 'geryl.aguila@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '13', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(40, 'Jorge Taguibao', 'jorge.taguibao@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '2', 'Teacher III', NULL, '2026-07-25 02:38:37'),
(41, 'Joy Valdez', 'joy.valdez003@deped.gov.ph', '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher', '3', 'Master Teacher I', NULL, '2026-07-25 02:38:37');

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;