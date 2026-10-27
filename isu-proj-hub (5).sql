-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 21, 2026 at 11:04 PM
-- Server version: 11.8.2-MariaDB
-- PHP Version: 8.5.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `isu-proj-hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `accomplishment_reports`
--

CREATE TABLE `accomplishment_reports` (
  `id` int(11) NOT NULL,
  `report_number` varchar(30) NOT NULL,
  `report_type` enum('quarterly','semi_annual','annual','terminal') NOT NULL,
  `period_quarter` enum('Q1','Q2','Q3','Q4') DEFAULT NULL,
  `period_year` year(4) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `submitted_by` int(11) NOT NULL,
  `date_submitted` date DEFAULT NULL,
  `status` enum('draft','submitted','under_review','approved','returned') NOT NULL DEFAULT 'draft',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accomplishment_reports`
--

INSERT INTO `accomplishment_reports` (`id`, `report_number`, `report_type`, `period_quarter`, `period_year`, `project_id`, `title`, `summary`, `submitted_by`, `date_submitted`, `status`, `reviewed_by`, `reviewed_at`, `remarks`, `attachment`, `created_at`, `updated_at`) VALUES
(1, 'AR-2026-0001', 'quarterly', 'Q1', '2026', 1, 'Q1 Accomplishment Report - Organic Farming', 'First quarter activities completed. 3 training sessions conducted with 45 participants.', 3, '2026-04-05', 'submitted', NULL, NULL, NULL, NULL, '2026-07-29 17:25:11', '2026-09-21 11:41:00'),
(2, 'AR-2026-0002', 'quarterly', 'Q1', '2026', 3, 'Q1 Accomplishment Report - Digital Literacy', 'Computer basics workshop completed. 30 seniors trained.', 4, '2026-04-03', 'submitted', NULL, NULL, NULL, NULL, '2026-07-29 17:25:11', '2026-09-21 11:41:00'),
(3, 'AR-2025-0003', 'terminal', NULL, '2025', 5, 'Terminal Report - Health and Nutrition', 'Project completed successfully. 200 community members reached.', 3, '2026-01-15', 'submitted', NULL, NULL, NULL, NULL, '2026-07-29 17:25:11', '2026-09-21 11:41:00'),
(4, 'AR-2026-0004', 'quarterly', 'Q1', '2026', 6, 'Q1 Accomplishment Report', 'test', 4, '2026-09-21', 'submitted', NULL, NULL, NULL, 'reports/6ab0a6863d866_1789961862.jpg', '2026-09-21 11:37:42', '2026-09-21 11:39:06');

-- --------------------------------------------------------

--
-- Table structure for table `activity_assignments`
--

CREATE TABLE `activity_assignments` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `assignment_type` enum('leader','member') NOT NULL DEFAULT 'member',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_assignments`
--

INSERT INTO `activity_assignments` (`id`, `activity_id`, `faculty_id`, `assignment_type`, `assigned_by`, `assigned_at`, `is_active`) VALUES
(2, 1, 5, 'member', 1, '2026-07-29 17:25:11', 1),
(4, 2, 5, 'member', 1, '2026-07-29 17:25:11', 1),
(6, 3, 5, 'member', 1, '2026-07-29 17:25:11', 1),
(9, 6, 4, 'leader', 1, '2026-07-29 17:25:11', 1),
(10, 7, 4, 'leader', 1, '2026-07-29 17:25:11', 1),
(11, 8, 5, 'leader', 1, '2026-07-29 17:25:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `activity_attendance`
--

CREATE TABLE `activity_attendance` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('present','absent','excused') NOT NULL DEFAULT 'present',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_attendance`
--

INSERT INTO `activity_attendance` (`id`, `activity_id`, `participant_id`, `session_date`, `time_in`, `time_out`, `status`, `created_at`) VALUES
(1, 1, 1, '2026-03-15', '08:00:00', '17:00:00', 'present', '2026-07-29 17:25:11'),
(2, 1, 1, '2026-03-16', '08:00:00', '17:00:00', 'present', '2026-07-29 17:25:11'),
(3, 1, 1, '2026-03-17', '08:00:00', '17:00:00', 'present', '2026-07-29 17:25:11'),
(4, 1, 2, '2026-03-15', '08:00:00', '17:00:00', 'present', '2026-07-29 17:25:11'),
(5, 1, 2, '2026-03-16', '08:00:00', '17:00:00', 'present', '2026-07-29 17:25:11'),
(6, 1, 2, '2026-03-17', '08:15:00', '17:00:00', 'present', '2026-07-29 17:25:11'),
(7, 1, 3, '2026-03-15', '08:00:00', '17:00:00', 'present', '2026-07-29 17:25:11'),
(8, 1, 3, '2026-03-16', '08:00:00', '12:00:00', 'present', '2026-07-29 17:25:11'),
(9, 1, 3, '2026-03-17', NULL, NULL, 'absent', '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `activity_evaluations`
--

CREATE TABLE `activity_evaluations` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `overall_rating` int(1) DEFAULT NULL,
  `content_rating` int(1) DEFAULT NULL,
  `speaker_rating` int(1) DEFAULT NULL,
  `venue_rating` int(1) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_evaluations`
--

INSERT INTO `activity_evaluations` (`id`, `activity_id`, `participant_id`, `overall_rating`, `content_rating`, `speaker_rating`, `venue_rating`, `comments`, `created_at`) VALUES
(1, 1, 1, 5, 5, 5, 4, 'Very informative training. Learned a lot about organic farming.', '2026-07-29 17:25:11'),
(2, 1, 2, 4, 4, 5, 4, 'Good training, would like more hands-on practice.', '2026-07-29 17:25:11'),
(3, 1, 3, 5, 5, 5, 5, 'Excellent! Will apply what I learned on my farm.', '2026-07-29 17:25:11'),
(4, 4, 6, 5, 5, 5, 5, 'Great workshop! Now I can bake bread for my family.', '2026-07-29 17:25:11'),
(5, 4, 7, 4, 4, 4, 4, 'Enjoyed the workshop. Want to learn more recipes.', '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `activity_outputs`
--

CREATE TABLE `activity_outputs` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `output_type` enum('material','report','certificate','other') DEFAULT 'other',
  `file_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_outputs`
--

INSERT INTO `activity_outputs` (`id`, `activity_id`, `title`, `description`, `output_type`, `file_path`, `created_by`, `created_at`) VALUES
(1, 1, 'Organic Fertilizer Production Manual', 'Step-by-step guide for producing organic fertilizers.', 'material', NULL, 1, '2026-07-29 17:25:11'),
(2, 4, 'Bread Making Recipe Book', 'Collection of bread recipes taught during the workshop.', 'material', NULL, 1, '2026-07-29 17:25:11'),
(3, 6, 'Computer Basics Training Guide', 'Guide for basic computer operations.', 'material', NULL, 1, '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `activity_participants`
--

CREATE TABLE `activity_participants` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `beneficiary_group_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `organization` varchar(200) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(200) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `attendance_status` enum('registered','present','absent') NOT NULL DEFAULT 'registered',
  `certificate_status` enum('not_issued','issued','claimed') NOT NULL DEFAULT 'not_issued',
  `qr_code` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_participants`
--

INSERT INTO `activity_participants` (`id`, `activity_id`, `beneficiary_group_id`, `name`, `age`, `gender`, `address`, `barangay`, `municipality`, `province`, `organization`, `occupation`, `email`, `phone`, `emergency_contact`, `emergency_phone`, `attendance_status`, `certificate_status`, `qr_code`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Pedro Santos', 45, 'male', NULL, 'San Isidro', 'Cauayan', 'Isabela', 'San Isidro Farmers Association', 'Farmer', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-FARM001', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(2, 1, NULL, 'Maria Reyes', 38, 'female', NULL, 'San Isidro', 'Cauayan', 'Isabela', 'San Isidro Farmers Association', 'Farmer', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-FARM002', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(3, 1, NULL, 'Juan Cruz', 52, 'male', NULL, 'San Isidro', 'Cauayan', 'Isabela', '', 'Farmer', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-FARM003', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(4, 1, NULL, 'Lorna Garcia', 41, 'female', NULL, 'San Isidro', 'Cauayan', 'Isabela', '', 'Farmer', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-FARM004', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(5, 1, NULL, 'Ricardo Bautista', 55, 'male', NULL, 'San Isidro', 'Cauayan', 'Isabela', '', 'Farmer', NULL, NULL, NULL, NULL, 'present', 'not_issued', 'QR-FARM005', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(6, 4, NULL, 'Ana Mendoza', 22, 'female', NULL, 'District I', 'Cauayan', 'Isabela', 'OSY Program', 'Student', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-BAKE001', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(7, 4, NULL, 'Mark Reyes', 20, 'male', NULL, 'District II', 'Cauayan', 'Isabela', '', 'Student', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-BAKE002', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(8, 4, NULL, 'Linda Garcia', 24, 'female', NULL, 'Poblacion', 'Cauayan', 'Isabela', '', 'Student', NULL, NULL, NULL, NULL, 'present', 'not_issued', 'QR-BAKE003', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(9, 6, NULL, 'Lorna Garcia', 65, 'female', NULL, 'District I', 'Cauayan', 'Isabela', 'Senior Citizens Association', 'Retired', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-DIGI001', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(10, 6, NULL, 'Ricardo Bautista', 70, 'male', NULL, 'District I', 'Cauayan', 'Isabela', 'Senior Citizens Association', 'Retired', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-DIGI002', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(11, 8, NULL, 'Antonio Mendoza', 48, 'male', NULL, 'Minante', 'Cauayan', 'Isabela', 'Corn Farmers Association', 'Farmer', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-CORN001', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(12, 8, NULL, 'Roberto Cruz', 39, 'male', NULL, 'Minante', 'Cauayan', 'Isabela', '', 'Farmer', NULL, NULL, NULL, NULL, 'present', 'issued', 'QR-CORN002', '2026-07-29 17:25:11', '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `activity_photos`
--

CREATE TABLE `activity_photos` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_photos`
--

INSERT INTO `activity_photos` (`id`, `activity_id`, `file_path`, `caption`, `uploaded_by`, `created_at`) VALUES
(1, 1, 'activities/organic_training_1.jpg', 'Farmers learning organic fertilizer production', 1, '2026-07-29 17:25:11'),
(2, 1, 'activities/organic_training_2.jpg', 'Hands-on composting practice', 1, '2026-07-29 17:25:11'),
(3, 4, 'activities/bread_workshop_1.jpg', 'Youth learning bread making', 1, '2026-07-29 17:25:11'),
(4, 6, 'activities/computer_class_1.jpg', 'Seniors learning computer basics', 1, '2026-07-29 17:25:11'),
(5, 8, 'activities/corn_training_1.jpg', 'Modern corn production techniques', 1, '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `activity_resource_speakers`
--

CREATE TABLE `activity_resource_speakers` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `affiliation` varchar(200) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_resource_speakers`
--

INSERT INTO `activity_resource_speakers` (`id`, `activity_id`, `name`, `affiliation`, `topic`, `contact_number`, `email`, `created_at`) VALUES
(1, 1, 'Dr. Elena Torres', 'DA-Isabela', 'Organic Fertilizer Production', '09171234600', 'torres@da.gov.ph', '2026-07-29 17:25:11'),
(2, 1, 'Prof. Mark Santos', 'ISU-Echague', 'Composting Techniques', '09171234601', 'santos@isu.edu.ph', '2026-07-29 17:25:11'),
(3, 2, 'Dr. Roberto Lim', 'ISU-Cauayan', 'Natural Pest Control', '09171234602', 'lim@isu.edu.ph', '2026-07-29 17:25:11'),
(4, 6, 'Ms. Jennifer Lee', 'DepEd Isabela', 'Basic Computer Skills', '09171234603', 'lee@deped.gov.ph', '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `activity_types`
--

CREATE TABLE `activity_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_types`
--

INSERT INTO `activity_types` (`id`, `name`, `is_active`, `created_at`) VALUES
(1, 'Training', 1, '2026-07-29 17:25:11'),
(2, 'Seminar', 1, '2026-07-29 17:25:11'),
(3, 'Workshop', 1, '2026-07-29 17:25:11'),
(4, 'Orientation', 1, '2026-07-29 17:25:11'),
(5, 'Monitoring', 1, '2026-07-29 17:25:11'),
(6, 'Evaluation', 1, '2026-07-29 17:25:11'),
(7, 'Turnover', 1, '2026-07-29 17:25:11'),
(8, 'Meeting', 1, '2026-07-29 17:25:11'),
(9, 'Field Visit', 1, '2026-07-29 17:25:11'),
(10, 'Technology Demonstration', 1, '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `is_override` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `is_override`, `created_at`) VALUES
(1, 1, 'create', 'program', 1, 'Created program: Community Development and Livelihood Program', NULL, NULL, '127.0.0.1', NULL, 0, '2026-07-29 17:25:11'),
(2, 1, 'create', 'project', 1, 'Created project: Organic Farming Technology Transfer', NULL, NULL, '127.0.0.1', NULL, 0, '2026-07-29 17:25:11'),
(3, 1, 'approve', 'proposal', 1, 'Approved proposal for Organic Farming Technology Transfer', NULL, NULL, '127.0.0.1', NULL, 0, '2026-07-29 17:25:11'),
(4, 1, 'approve', 'proposal', 2, 'Approved proposal for Livelihood Skills Training', NULL, NULL, '127.0.0.1', NULL, 0, '2026-07-29 17:25:11'),
(5, 1, 'approve', 'report', 1, 'Approved Q1 accomplishment report', NULL, NULL, '127.0.0.1', NULL, 0, '2026-07-29 17:25:11'),
(6, 3, 'create', 'proposal', 1, 'Submitted proposal: Organic Farming Technology Transfer', NULL, NULL, '127.0.0.1', NULL, 0, '2026-07-29 17:25:11'),
(7, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', NULL, 0, '2026-07-29 17:25:11'),
(8, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', NULL, 0, '2026-07-29 17:25:11'),
(9, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 17:33:28'),
(10, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 17:33:31'),
(11, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 17:34:13'),
(12, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 17:34:16'),
(13, 1, 'edit', 'program_assignment', 2, 'Updated assignments for program: Digital Literacy and ICT Skills Development', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:08:57'),
(14, 1, 'edit', 'program_assignment', 2, 'Updated assignments for program: Digital Literacy and ICT Skills Development', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:09:16'),
(15, 1, 'edit', 'program_assignment', 2, 'Updated assignments for program: Digital Literacy and ICT Skills Development', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:09:35'),
(16, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:11:53'),
(17, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:11:59'),
(18, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:12:25'),
(19, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:12:31'),
(20, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:13:35'),
(21, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:13:40'),
(22, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:13:46'),
(23, 2, 'login', 'user', 2, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:13:52'),
(24, 2, 'logout', 'user', 2, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:14:36'),
(25, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:14:43'),
(26, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:20:54'),
(27, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:20:57'),
(28, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:24:43'),
(29, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:24:47'),
(30, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:27:38'),
(31, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:27:41'),
(32, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:54:04'),
(33, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:54:07'),
(34, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:54:23'),
(35, 1, 'edit', 'program_assignment', 3, 'Updated assignments for program: Agricultural Technology Transfer Program', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:54:45'),
(36, 1, 'edit', 'program_assignment', 2, 'Updated assignments for program: Digital Literacy and ICT Skills Development', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:55:07'),
(37, 1, 'edit', 'program_assignment', 1, 'Updated assignments for program: Community Development and Livelihood Program', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 18:55:21'),
(38, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-29 19:32:44'),
(39, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:31:57'),
(40, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:32:01'),
(41, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:32:58'),
(42, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:33:01'),
(43, 1, 'edit', 'project_assignment', 1, 'Updated assignments for project: Organic Farming Technology Transfer', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:33:24'),
(44, 1, 'edit', 'project_assignment', 2, 'Updated assignments for project: Livelihood Skills Training: Bread and Pastry Making', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:33:36'),
(45, 1, 'edit', 'project_assignment', 4, 'Updated assignments for project: Corn Production Technology Dissemination', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:34:06'),
(46, 1, 'edit', 'project_assignment', 5, 'Updated assignments for project: Community Health and Nutrition Awareness', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:34:24'),
(47, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:34:38'),
(48, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:34:41'),
(49, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:35:15'),
(50, 1, 'edit', 'program_assignment', 1, 'Updated assignments for program: Community Development and Livelihood Program', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:39:41'),
(51, 1, 'edit', 'program_assignment', 1, 'Updated assignments for program: Community Development and Livelihood Program', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:40:16'),
(52, 1, 'edit', 'program_assignment', 1, 'Updated assignments for program: Community Development and Livelihood Program', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 09:42:06'),
(53, 1, 'edit', 'program_assignment', 3, 'Updated assignments for program: Agricultural Technology Transfer Program', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 10:55:58'),
(54, 1, 'edit', 'program_assignment', 3, 'Updated assignments for program: Agricultural Technology Transfer Program', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 10:56:25'),
(55, 1, 'edit', 'program_assignment', 1, 'Updated assignments for program: Community Development and Livelihood Program', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 11:04:27'),
(56, 1, 'edit', 'project_assignment', 4, 'Updated assignments for project: Corn Production Technology Dissemination', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 12:03:36'),
(57, 1, 'create', 'activity', 9, 'Created activity: Testing', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 12:05:08'),
(58, 1, 'edit', 'project_assignment', 4, 'Updated assignments for project: Corn Production Technology Dissemination', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 12:26:55'),
(59, 3, 'create', 'proposal', 7, 'Submitted program proposal: Test', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 12:49:55'),
(60, 1, 'approved', 'proposal', 7, 'Approved proposal', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 12:57:43'),
(61, 1, 'approved', 'proposal', 7, 'Approved proposal', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 13:02:55'),
(62, 1, 'approved', 'proposal', 7, 'Approved proposal', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 13:12:33'),
(63, 1, 'edit', 'project', 0, 'Updated project', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 13:19:53'),
(64, 1, 'edit', 'project', 0, 'Updated project', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 13:20:21'),
(65, 1, 'edit', 'project', 4, 'Updated project', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 0, '2026-07-30 13:35:04'),
(66, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-06 22:14:59'),
(67, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 16:56:16'),
(68, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 16:56:22'),
(69, 3, 'edit', 'project_assignment', 4, 'Updated assignments for project: Corn Production Technology Dissemination', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 16:57:35'),
(70, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 16:57:55'),
(71, 5, 'login', 'user', 5, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 16:58:48'),
(72, 5, 'logout', 'user', 5, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 16:59:26'),
(73, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 16:59:30'),
(74, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:43:57'),
(75, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:44:00'),
(76, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:44:19'),
(77, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:44:23'),
(78, 1, 'returned', 'proposal', 5, 'Returned proposal', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:45:30'),
(79, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:45:35'),
(80, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:45:38'),
(81, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:49:13'),
(82, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-08 17:49:17'),
(83, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:24:43'),
(84, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:27:52'),
(85, 1, 'create', 'explorer', 25, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:28:10'),
(86, 1, 'create', 'explorer', 26, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:28:12'),
(87, 1, 'upload', 'explorer', 27, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:28:14'),
(88, 1, 'delete', 'explorer', 25, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:28:16'),
(89, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:29:52'),
(90, 1, 'create', 'explorer', 28, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:30:10'),
(91, 1, 'create', 'explorer', 29, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:30:12'),
(92, 1, 'upload', 'explorer', 30, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:30:14'),
(93, 1, 'delete', 'explorer', 28, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:30:16'),
(94, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:31:51'),
(95, 1, 'create', 'explorer', 31, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:32:09'),
(96, 1, 'create', 'explorer', 32, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:32:11'),
(97, 1, 'upload', 'explorer', 33, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:32:13'),
(98, 1, 'rename', 'explorer', 33, 'Explorer rename', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:32:22'),
(99, 1, 'move', 'explorer', 33, 'Explorer move', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:32:24'),
(100, 1, 'delete', 'explorer', 33, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:32:30'),
(101, 1, 'delete', 'explorer', 31, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:32:36'),
(102, 1, 'create', 'explorer', 34, 'Explorer create', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:33:18'),
(103, 1, 'create', 'explorer', 35, 'Explorer create', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:33:41'),
(104, 1, 'delete', 'explorer', 34, 'Explorer delete', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:35:54'),
(105, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:37:20'),
(106, 1, 'create', 'explorer', 36, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:37:38'),
(107, 1, 'create', 'explorer', 37, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:37:40'),
(108, 1, 'upload', 'explorer', 38, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:37:42'),
(109, 1, 'rename', 'explorer', 38, 'Explorer rename', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:37:52'),
(110, 1, 'move', 'explorer', 38, 'Explorer move', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:37:54'),
(111, 1, 'delete', 'explorer', 38, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:38:01'),
(112, 1, 'delete', 'explorer', 36, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:38:05'),
(113, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:42:08'),
(114, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:42:11'),
(115, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:43:07'),
(116, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:43:10'),
(117, 1, 'upload', 'explorer', 39, 'Explorer upload', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:43:53'),
(118, 1, 'upload', 'explorer', 40, 'Explorer upload', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:46:57'),
(119, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:47:08'),
(120, 1, 'upload', 'explorer', 51, 'Explorer upload', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:47:28'),
(121, 1, 'create', 'explorer', 52, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:47:28'),
(122, 1, 'create', 'explorer', 53, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:47:30'),
(123, 1, 'upload', 'explorer', 54, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:47:32'),
(124, 1, 'rename', 'explorer', 54, 'Explorer rename', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:47:42'),
(125, 1, 'move', 'explorer', 54, 'Explorer move', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:47:44'),
(126, 1, 'delete', 'explorer', 54, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:47:50'),
(127, 1, 'delete', 'explorer', 52, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:47:55'),
(128, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-13 23:48:23'),
(129, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:49:35'),
(130, 1, 'create', 'explorer', 55, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:49:56'),
(131, 1, 'create', 'explorer', 56, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:49:58'),
(132, 1, 'upload', 'explorer', 57, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:50:00'),
(133, 1, 'rename', 'explorer', 57, 'Explorer rename', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:50:10'),
(134, 1, 'move', 'explorer', 57, 'Explorer move', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:50:12'),
(135, 1, 'delete', 'explorer', 57, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:50:18'),
(136, 1, 'delete', 'explorer', 55, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:50:22'),
(137, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:08'),
(138, 1, 'create', 'explorer', 58, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:32'),
(139, 1, 'upload', 'explorer', 59, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:34'),
(140, 1, 'delete', 'explorer', 58, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:38'),
(141, 1, 'create', 'explorer', 60, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:40'),
(142, 1, 'create', 'explorer', 61, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:42'),
(143, 1, 'upload', 'explorer', 62, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:44'),
(144, 1, 'rename', 'explorer', 62, 'Explorer rename', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:54'),
(145, 1, 'move', 'explorer', 62, 'Explorer move', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:58:56'),
(146, 1, 'delete', 'explorer', 62, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:59:03'),
(147, 1, 'delete', 'explorer', 60, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-13 23:59:07'),
(148, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:06'),
(149, 1, 'create', 'explorer', 63, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:31'),
(150, 1, 'upload', 'explorer', 64, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:33'),
(151, 1, 'delete', 'explorer', 63, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:37'),
(152, 1, 'create', 'explorer', 65, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:39'),
(153, 1, 'create', 'explorer', 66, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:41'),
(154, 1, 'upload', 'explorer', 67, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:43'),
(155, 1, 'rename', 'explorer', 67, 'Explorer rename', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:53'),
(156, 1, 'move', 'explorer', 67, 'Explorer move', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:05:55'),
(157, 1, 'delete', 'explorer', 67, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:06:01'),
(158, 1, 'delete', 'explorer', 65, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:06:05'),
(159, 1, 'create', 'explorer', 68, 'Explorer create', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-14 00:07:02'),
(160, 1, 'upload', 'explorer', 69, 'Explorer upload', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-14 00:08:38'),
(161, 1, 'delete', 'explorer', 68, 'Explorer delete', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-14 00:09:06'),
(162, 1, 'upload', 'explorer', 70, 'Explorer upload', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-14 00:09:46'),
(163, 1, 'upload', 'explorer', 71, 'Explorer upload', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 0, '2026-09-14 00:14:11'),
(164, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:19:51'),
(165, 1, 'create', 'explorer', 72, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:16'),
(166, 1, 'upload', 'explorer', 73, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:18'),
(167, 1, 'delete', 'explorer', 72, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:22'),
(168, 1, 'create', 'explorer', 74, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:24'),
(169, 1, 'create', 'explorer', 75, 'Explorer create', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:26'),
(170, 1, 'upload', 'explorer', 76, 'Explorer upload', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:28'),
(171, 1, 'rename', 'explorer', 76, 'Explorer rename', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:38'),
(172, 1, 'move', 'explorer', 76, 'Explorer move', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:40'),
(173, 1, 'delete', 'explorer', 76, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:46'),
(174, 1, 'delete', 'explorer', 74, 'Explorer delete', NULL, NULL, '127.0.0.1', '', 0, '2026-09-14 00:20:50'),
(175, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-20 10:13:04'),
(176, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-20 10:14:35'),
(177, 1, 'edit', 'settings', NULL, 'Updated general settings', NULL, NULL, '127.0.0.1', '', 0, '2026-09-20 10:14:54'),
(178, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-20 10:14:56'),
(179, 7, 'login', 'user', 7, 'User logged in', NULL, NULL, '127.0.0.1', '', 0, '2026-09-20 10:15:08'),
(180, 1, 'edit', 'settings', NULL, 'Updated SMTP settings and email preferences', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-20 10:15:38'),
(181, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:09:21'),
(182, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:09:25'),
(183, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:11:23'),
(184, 4, 'login', 'user', 4, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:11:27'),
(185, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 16; V2425A Build/BP2A.250605.031.A3_V000L1; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/143.0.7499.192 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/579.0.0.61.91;]', 0, '2026-09-21 11:13:46'),
(186, 4, 'create', 'proposal', 8, 'Submitted program proposal: test Propposal', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:15:48'),
(187, 4, 'logout', 'user', 4, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:16:05'),
(188, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:16:09'),
(189, 3, 'login', 'user', 3, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:19:02'),
(190, 3, 'logout', 'user', 3, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:19:15'),
(191, 4, 'login', 'user', 4, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:19:19'),
(192, 4, 'create', 'proposal', 9, 'Submitted program proposal: HAKDOG', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:20:19'),
(193, 1, 'login', 'user', 1, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 16; SM-M336B Build/BP2A.250605.031.A3; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/152.0.7977.87 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/579.0.0.61.91;]', 0, '2026-09-21 11:20:42'),
(194, 4, 'logout', 'user', 4, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:22:23'),
(195, 4, 'login', 'user', 4, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:22:46'),
(196, 1, 'create', 'program', 4, 'Auto-created program from approved proposal: HAKDOG', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:27:19'),
(197, 1, 'approved', 'proposal', 9, 'Approved proposal', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:27:19'),
(198, 1, 'create', 'program', 5, 'Auto-created program from approved proposal: test Propposal', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:28:02'),
(199, 1, 'approved', 'proposal', 8, 'Approved proposal', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:28:02'),
(200, 1, 'create', 'document', 5, 'Uploaded document: HEHE', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:31:37'),
(201, 4, 'create', 'project', 6, 'Created project: Test Hakdog123', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:34:43'),
(202, 4, 'create', 'report', 4, 'Created report: Q1 Accomplishment Report', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:37:42'),
(203, 1, 'logout', 'user', 1, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 11:45:47'),
(204, 4, 'login', 'user', 4, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 16:43:06'),
(205, 4, 'logout', 'user', 4, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 16:46:25'),
(206, 2, 'login', 'user', 2, 'User logged in', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 16:46:34'),
(207, 2, 'edit', 'settings', NULL, 'Updated SMTP settings and email preferences', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 16:50:44'),
(208, 2, 'edit', 'settings', NULL, 'Updated SMTP settings and email preferences', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 16:51:07'),
(209, 2, 'edit', 'settings', NULL, 'Updated SMTP settings and email preferences', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 16:51:11'),
(210, 2, 'logout', 'user', 2, 'User logged out', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 0, '2026-09-21 17:00:22');

-- --------------------------------------------------------

--
-- Table structure for table `beneficiary_groups`
--

CREATE TABLE `beneficiary_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `group_type` enum('individual','organization','community','lgu') DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `beneficiary_groups`
--

INSERT INTO `beneficiary_groups` (`id`, `name`, `group_type`, `barangay`, `municipality`, `province`, `contact_person`, `contact_number`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'San Isidro Farmers Association', 'organization', 'San Isidro', 'Cauayan', 'Isabela', 'Pedro Santos', '09171234590', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(2, 'District I Senior Citizens', 'organization', 'District I', 'Cauayan', 'Isabela', 'Lorna Garcia', '09171234591', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(3, 'Cauayan Youth Organization', 'organization', 'Poblacion', 'Cauayan', 'Isabela', 'Mark Reyes', '09171234592', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(4, 'Rizal Organic Farmers Cooperative', 'organization', 'Rizal', 'Cauayan', 'Isabela', 'Juan Ramos', '09171234593', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(5, 'Villa Luna Women\'s Association', 'organization', 'Villa Luna', 'Cauayan', 'Isabela', 'Maria Bautista', '09171234594', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(6, 'District II Parents Association', 'organization', 'District II', 'Cauayan', 'Isabela', 'Roberto Cruz', '09171234595', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(7, 'Cauayan Corn Farmers Association', 'organization', 'Minante', 'Cauayan', 'Isabela', 'Antonio Mendoza', '09171234596', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(8, 'Cauayan LGU', 'lgu', '', 'Cauayan', 'Isabela', 'Mayor Office', '09171234597', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `certificate_number` varchar(30) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `recipient_name` varchar(200) NOT NULL,
  `recipient_type` enum('participant','resource_speaker') NOT NULL DEFAULT 'participant',
  `date_issued` date DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('generated','printed','claimed') NOT NULL DEFAULT 'generated',
  `generated_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `certificate_number`, `template_id`, `activity_id`, `recipient_name`, `recipient_type`, `date_issued`, `qr_code`, `file_path`, `status`, `generated_by`, `created_at`) VALUES
(1, 'CERT-2026-0001', 1, 1, 'Pedro Santos', 'participant', '2026-03-17', 'CERT-QR001', NULL, '', 1, '2026-07-29 17:25:11'),
(2, 'CERT-2026-0002', 1, 1, 'Maria Reyes', 'participant', '2026-03-17', 'CERT-QR002', NULL, '', 1, '2026-07-29 17:25:11'),
(3, 'CERT-2026-0003', 1, 4, 'Ana Mendoza', 'participant', '2026-04-03', 'CERT-QR003', NULL, '', 1, '2026-07-29 17:25:11'),
(4, 'CERT-2026-0004', 1, 6, 'Lorna Garcia', 'participant', '2026-03-05', 'CERT-QR004', NULL, '', 1, '2026-07-29 17:25:11'),
(5, 'CERT-2026-0005', 4, 1, 'Dr. Elena Torres', 'resource_speaker', '2026-03-17', 'CERT-QR005', NULL, '', 1, '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `certificate_templates`
--

CREATE TABLE `certificate_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('participation','completion','recognition','appreciation') NOT NULL,
  `template_path` varchar(255) DEFAULT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificate_templates`
--

INSERT INTO `certificate_templates` (`id`, `name`, `type`, `template_path`, `fields`, `is_active`, `created_at`) VALUES
(1, 'Certificate of Participation', 'participation', NULL, '[\"recipient_name\",\"activity_title\",\"date\",\"venue\"]', 1, '2026-07-29 17:25:11'),
(2, 'Certificate of Completion', 'completion', NULL, '[\"recipient_name\",\"program_title\",\"date\"]', 1, '2026-07-29 17:25:11'),
(3, 'Certificate of Recognition', 'recognition', NULL, '[\"recipient_name\",\"achievement\",\"date\"]', 1, '2026-07-29 17:25:11'),
(4, 'Certificate of Appreciation', 'appreciation', NULL, '[\"recipient_name\",\"contribution\",\"date\"]', 1, '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `components`
--

CREATE TABLE `components` (
  `id` int(11) NOT NULL,
  `component_code` varchar(30) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
  `expected_outputs` text DEFAULT NULL,
  `completion_percentage` int(3) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `components`
--

INSERT INTO `components` (`id`, `component_code`, `project_id`, `title`, `description`, `start_date`, `end_date`, `status`, `expected_outputs`, `completion_percentage`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'COMP-2026-0001', 1, 'Farmer Training Component', 'Series of training sessions for local farmers on organic farming.', '2026-02-01', '2026-06-30', 'in_progress', 'Trained farmers, training manuals, demonstration plots', 50, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(2, 'COMP-2026-0002', 1, 'Technology Demonstration Component', 'Establishment of demonstration farms for organic techniques.', '2026-03-01', '2026-10-31', 'in_progress', 'Demo farms, documented results, farmer adoption', 30, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(3, 'COMP-2026-0003', 2, 'Baking Skills Training', 'Hands-on bread and pastry making workshops.', '2026-03-15', '2026-07-31', 'in_progress', 'Skilled youth, recipe book, products', 40, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(4, 'COMP-2026-0004', 3, 'Computer Basics Workshop', 'Basic computer operations and internet skills.', '2026-02-15', '2026-05-31', 'completed', 'Computer literate seniors, training guides', 100, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(5, 'COMP-2026-0005', 4, 'Corn Production Training', 'Training on modern corn production techniques.', '2026-02-01', '2026-06-30', 'in_progress', 'Trained farmers, production guide', 20, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `component_assignments`
--

CREATE TABLE `component_assignments` (
  `id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `assignment_type` enum('leader','member') NOT NULL DEFAULT 'member',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `component_assignments`
--

INSERT INTO `component_assignments` (`id`, `component_id`, `faculty_id`, `assignment_type`, `assigned_by`, `assigned_at`, `is_active`) VALUES
(2, 1, 5, 'member', 1, '2026-07-29 17:25:11', 1),
(5, 4, 4, 'leader', 1, '2026-07-29 17:25:11', 1),
(6, 5, 5, 'leader', 1, '2026-07-29 17:25:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `is_active`, `created_at`) VALUES
(1, 'College of Agriculture', 'CA', 1, '2026-07-29 17:25:11'),
(2, 'College of Engineering', 'CE', 1, '2026-07-29 17:25:11'),
(3, 'College of Education', 'CED', 1, '2026-07-29 17:25:11'),
(4, 'College of Arts and Sciences', 'CAS', 1, '2026-07-29 17:25:11'),
(5, 'College of Business and Accountancy', 'CBA', 1, '2026-07-29 17:25:11'),
(6, 'College of Computing and Information Sciences', 'CCIS', 1, '2026-07-29 17:25:11'),
(7, 'College of Veterinary Medicine', 'CVM', 1, '2026-07-29 17:25:11'),
(8, 'College of Nursing', 'CN', 1, '2026-07-29 17:25:11'),
(9, 'Extension Training Services', 'ETS', 1, '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `entity_type` enum('program','project','component','activity') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','archived') NOT NULL DEFAULT 'draft',
  `uploaded_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `title`, `category_id`, `entity_type`, `entity_id`, `file_path`, `file_name`, `file_type`, `file_size`, `version`, `description`, `status`, `uploaded_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Organic Farming Proposal', 1, 'project', 1, 'documents/proposal_organic.pdf', 'proposal_organic.pdf', 'pdf', NULL, 1, 'Initial project proposal', 'approved', 3, NULL, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(2, 'MOA with City Agriculture', 2, 'project', 1, 'documents/moa_agriculture.pdf', 'moa_agriculture.pdf', 'pdf', NULL, 1, 'Memorandum of Agreement', 'approved', 1, NULL, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(3, 'Training Attendance Sheet', 6, 'activity', 1, 'documents/attendance_training.pdf', 'attendance_training.pdf', 'pdf', NULL, 1, 'Attendance for organic farming training', 'approved', 3, NULL, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(4, 'Q1 Report 2026', 3, 'project', 1, 'documents/q1_report.pdf', 'q1_report.pdf', 'pdf', NULL, 1, 'Quarterly accomplishment report', 'approved', 3, NULL, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(5, 'HEHE', 12, 'program', 4, 'documents/private/6ab0a5190bbd8_1789961497.jpg', '803824453_1119679650387101_5441971826368791255_n (1).jpg', 'jpg', 223427, 1, 'Test', 'draft', 1, NULL, NULL, '2026-09-21 11:31:37', '2026-09-21 11:31:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_categories`
--

CREATE TABLE `document_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_categories`
--

INSERT INTO `document_categories` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Proposals', 'Project proposals', 1, '2026-07-29 17:25:11'),
(2, 'MOA', 'Memorandum of Agreement', 1, '2026-07-29 17:25:11'),
(3, 'Accomplishment Reports', 'Quarterly and annual reports', 1, '2026-07-29 17:25:11'),
(4, 'Narrative Reports', 'Activity narrative reports', 1, '2026-07-29 17:25:11'),
(5, 'Terminal Reports', 'Project completion reports', 1, '2026-07-29 17:25:11'),
(6, 'Attendance Sheets', 'Participant attendance records', 1, '2026-07-29 17:25:11'),
(7, 'Certificates', 'Generated certificates', 1, '2026-07-29 17:25:11'),
(8, 'Photos', 'Activity documentation photos', 1, '2026-07-29 17:25:11'),
(9, 'Evaluation Forms', 'Post-activity evaluations', 1, '2026-07-29 17:25:11'),
(10, 'Letters', 'Official correspondence', 1, '2026-07-29 17:25:11'),
(11, 'Supporting Documents', 'Other supporting files', 1, '2026-07-29 17:25:11'),
(12, 'Designation', 'Designation letters and office orders', 1, '2026-09-21 04:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `notification_id` int(11) NOT NULL,
  `category` varchar(30) NOT NULL,
  `status` enum('pending','sending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `available_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_error` varchar(300) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `explorer_migrations`
--

CREATE TABLE `explorer_migrations` (
  `version` varchar(100) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `explorer_migrations`
--

INSERT INTO `explorer_migrations` (`version`, `applied_at`) VALUES
('20260913_initial_placement', '2026-09-13 23:30:52'),
('20260913_program_groups', '2026-09-13 23:47:01');

-- --------------------------------------------------------

--
-- Table structure for table `explorer_nodes`
--

CREATE TABLE `explorer_nodes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `kind` enum('project','program','component_group','activity_group','component','activity','folder','file') NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `path` varchar(700) DEFAULT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `extension` varchar(30) DEFAULT NULL,
  `size` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `explorer_nodes`
--

INSERT INTO `explorer_nodes` (`id`, `parent_id`, `name`, `kind`, `entity_id`, `original_name`, `path`, `mime_type`, `extension`, `size`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'Organic Farming Technology Transfer', 'project', 1, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(2, 1, 'Community Development and Livelihood Program', 'program', 1, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(3, 41, 'Farmer Training Component', 'component', 1, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(4, 42, 'Organic Fertilizer Production Training', 'activity', 1, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(5, 42, 'Pest Management in Organic Farming', 'activity', 2, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(6, 42, 'Organic Farm Demonstration', 'activity', 3, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(7, 41, 'Technology Demonstration Component', 'component', 2, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(8, NULL, 'Livelihood Skills Training: Bread and Pastry Making', 'project', 2, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(9, 8, 'Community Development and Livelihood Program', 'program', 1, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(10, 43, 'Baking Skills Training', 'component', 3, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(11, 44, 'Bread Making Workshop', 'activity', 4, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(12, 44, 'Pastry Decoration Workshop', 'activity', 5, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(13, NULL, 'Digital Literacy Program for Senior Citizens', 'project', 3, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(14, 13, 'Digital Literacy and ICT Skills Development', 'program', 2, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(15, 47, 'Computer Basics Workshop', 'component', 4, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(16, 48, 'Basic Computer Operations', 'activity', 6, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(17, 48, 'Internet and Email Workshop', 'activity', 7, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-13 23:47:01', NULL),
(18, NULL, 'Corn Production Technology Dissemination', 'project', 4, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-30 13:35:04', NULL),
(19, 18, 'Agricultural Technology Transfer Program', 'program', 3, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(20, 49, 'Corn Production Training', 'component', 5, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-14 00:20:22', NULL),
(21, 50, 'Modern Corn Production Techniques', 'activity', 8, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-09-14 00:20:50', NULL),
(22, 50, 'Testing', 'activity', 9, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-30 12:05:08', '2026-09-13 23:47:28', NULL),
(23, NULL, 'Community Health and Nutrition Awareness', 'project', 5, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(24, 23, 'Community Development and Livelihood Program', 'program', 1, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(25, 21, 'Explorer HTTP test 5d8f0796', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:28:10', '2026-09-13 23:28:16', '2026-09-13 23:28:16'),
(26, 25, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:28:12', '2026-09-13 23:28:16', '2026-09-13 23:28:16'),
(27, 25, 'training-plan.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/component/5/activity/8/folder/25/6aa6c10e94c48_1789313294.txt', 'text/plain', 'txt', 29, 1, '2026-09-13 23:28:14', '2026-09-13 23:28:16', '2026-09-13 23:28:16'),
(28, 21, 'Explorer HTTP test 7ac5e338', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:30:10', '2026-09-13 23:30:16', '2026-09-13 23:30:16'),
(29, 28, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:30:12', '2026-09-13 23:30:16', '2026-09-13 23:30:16'),
(30, 28, 'training-plan.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/component/5/activity/8/folder/28/6aa6c1868ad79_1789313414.txt', 'text/plain', 'txt', 29, 1, '2026-09-13 23:30:14', '2026-09-13 23:30:16', '2026-09-13 23:30:16'),
(31, 21, 'Explorer HTTP test be331404', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:32:09', '2026-09-13 23:32:36', '2026-09-13 23:32:36'),
(32, 31, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:32:11', '2026-09-13 23:32:36', '2026-09-13 23:32:36'),
(33, 32, 'renamed.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/component/5/activity/8/folder/31/6aa6c1fde97c6_1789313533.txt', 'text/plain', 'txt', 29, 1, '2026-09-13 23:32:13', '2026-09-13 23:32:30', '2026-09-13 23:32:30'),
(34, 21, 'Explorer UI verification', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:33:18', '2026-09-13 23:35:54', '2026-09-13 23:35:54'),
(35, 34, 'Nested folder', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:33:41', '2026-09-13 23:35:54', '2026-09-13 23:35:54'),
(36, 21, 'Explorer HTTP test de91560a', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:37:38', '2026-09-13 23:38:05', '2026-09-13 23:38:05'),
(37, 36, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:37:40', '2026-09-13 23:38:05', '2026-09-13 23:38:05'),
(38, 37, 'renamed.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/component/5/activity/8/folder/36/6aa6c346b741c_1789313862.txt', 'text/plain', 'txt', 29, 1, '2026-09-13 23:37:42', '2026-09-13 23:38:01', '2026-09-13 23:38:01'),
(39, 22, 'ERD.png', 'file', NULL, 'ERD.png', 'explorer/project/4/program/3/component/5/activity/9/folder/22/6aa6c4b908e88_1789314233.png', 'image/png', 'png', 169749, 1, '2026-09-13 23:43:53', '2026-09-13 23:43:53', NULL),
(40, 22, 'CHAIN-OF-CUSTODY-UPDATED.docx', 'file', NULL, 'CHAIN-OF-CUSTODY-UPDATED.docx', 'explorer/project/4/program/3/component/5/activity/9/folder/22/6aa6c5713405e_1789314417.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', 3055491, 1, '2026-09-13 23:46:57', '2026-09-13 23:46:57', NULL),
(41, 2, 'Components', 'component_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(42, 2, 'Activities', 'activity_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(43, 9, 'Components', 'component_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(44, 9, 'Activities', 'activity_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(45, 24, 'Components', 'component_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(46, 24, 'Activities', 'activity_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(47, 14, 'Components', 'component_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(48, 14, 'Activities', 'activity_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(49, 19, 'Components', 'component_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(50, 19, 'Activities', 'activity_group', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:01', '2026-09-13 23:47:01', NULL),
(51, 22, 'CHAIN.pdf', 'file', NULL, 'CHAIN.pdf', 'explorer/project/4/program/3/activity/9/folder/22/6aa6c5903f0fb_1789314448.pdf', 'application/pdf', 'pdf', 3995096, 1, '2026-09-13 23:47:28', '2026-09-13 23:47:28', NULL),
(52, 21, 'Explorer HTTP test 6a515fc7', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:28', '2026-09-13 23:47:54', '2026-09-13 23:47:54'),
(53, 52, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:47:30', '2026-09-13 23:47:54', '2026-09-13 23:47:54'),
(54, 53, 'renamed.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/activity/8/folder/52/6aa6c594709d0_1789314452.txt', 'text/plain', 'txt', 29, 1, '2026-09-13 23:47:32', '2026-09-13 23:47:50', '2026-09-13 23:47:50'),
(55, 21, 'Explorer HTTP test 9d1ff2a8', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:49:56', '2026-09-13 23:50:22', '2026-09-13 23:50:22'),
(56, 55, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:49:58', '2026-09-13 23:50:22', '2026-09-13 23:50:22'),
(57, 56, 'renamed.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/activity/8/folder/55/6aa6c62813cc0_1789314600.txt', 'text/plain', 'txt', 29, 1, '2026-09-13 23:50:00', '2026-09-13 23:50:18', '2026-09-13 23:50:18'),
(58, 20, 'Explorer HTTP component 37118476', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:58:32', '2026-09-13 23:58:38', '2026-09-13 23:58:38'),
(59, 58, 'component-file.txt', 'file', NULL, 'component-file.txt', 'explorer/project/4/program/3/component/5/folder/58/6aa6c82a81bb7_1789315114.txt', 'text/plain', 'txt', 29, 1, '2026-09-13 23:58:34', '2026-09-13 23:58:38', '2026-09-13 23:58:38'),
(60, 21, 'Explorer HTTP test db771305', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:58:40', '2026-09-13 23:59:07', '2026-09-13 23:59:07'),
(61, 60, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-13 23:58:42', '2026-09-13 23:59:07', '2026-09-13 23:59:07'),
(62, 61, 'renamed.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/activity/8/folder/60/6aa6c834b42df_1789315124.txt', 'text/plain', 'txt', 29, 1, '2026-09-13 23:58:44', '2026-09-13 23:59:03', '2026-09-13 23:59:03'),
(63, 20, 'Explorer HTTP component d0fa25a3', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-14 00:05:31', '2026-09-14 00:05:37', '2026-09-14 00:05:37'),
(64, 63, 'component-file.txt', 'file', NULL, 'component-file.txt', 'explorer/project/4/program/3/component/5/folder/63/6aa6c9cd4640b_1789315533.txt', 'text/plain', 'txt', 29, 1, '2026-09-14 00:05:33', '2026-09-14 00:05:37', '2026-09-14 00:05:37'),
(65, 21, 'Explorer HTTP test f2b68d8c', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-14 00:05:39', '2026-09-14 00:06:05', '2026-09-14 00:06:05'),
(66, 65, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-14 00:05:41', '2026-09-14 00:06:05', '2026-09-14 00:06:05'),
(67, 66, 'renamed.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/activity/8/folder/65/6aa6c9d76f209_1789315543.txt', 'text/plain', 'txt', 29, 1, '2026-09-14 00:05:43', '2026-09-14 00:06:01', '2026-09-14 00:06:01'),
(68, 20, 'Queue UI test 1789315619623', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-14 00:07:02', '2026-09-14 00:09:06', '2026-09-14 00:09:06'),
(69, 68, 'test-explorer-fixture.txt', 'file', NULL, 'test-explorer-fixture.txt', 'explorer/project/4/program/3/component/5/folder/68/6aa6ca865fb26_1789315718.txt', 'text/plain', 'txt', 29, 1, '2026-09-14 00:08:38', '2026-09-14 00:09:06', '2026-09-14 00:09:06'),
(70, 20, 'Chapter-1-3-ATTENDANCE-MONITORING-FINAL-PAPERS-3.docx', 'file', NULL, 'Chapter-1-3-ATTENDANCE-MONITORING-FINAL-PAPERS-3.docx', 'explorer/project/4/program/3/component/5/folder/20/6aa6caca93bc0_1789315786.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', 5550056, 1, '2026-09-14 00:09:46', '2026-09-14 00:09:46', NULL),
(71, 20, 'CERT-2026-0002.pdf', 'file', NULL, 'CERT-2026-0002.pdf', 'explorer/project/4/program/3/component/5/folder/20/6aa6cbd30cabc_1789316051.pdf', 'application/pdf', 'pdf', 14270223, 1, '2026-09-14 00:14:11', '2026-09-14 00:14:11', NULL),
(72, 20, 'Explorer HTTP component 73ce6fe8', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-14 00:20:16', '2026-09-14 00:20:22', '2026-09-14 00:20:22'),
(73, 72, 'component-file.txt', 'file', NULL, 'component-file.txt', 'explorer/project/4/program/3/component/5/folder/72/6aa6cd420d600_1789316418.txt', 'text/plain', 'txt', 29, 1, '2026-09-14 00:20:18', '2026-09-14 00:20:22', '2026-09-14 00:20:22'),
(74, 21, 'Explorer HTTP test eb0a85d4', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-14 00:20:24', '2026-09-14 00:20:50', '2026-09-14 00:20:50'),
(75, 74, 'Nested destination', 'folder', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-14 00:20:26', '2026-09-14 00:20:50', '2026-09-14 00:20:50'),
(76, 75, 'renamed.txt', 'file', NULL, 'training-plan.txt', 'explorer/project/4/program/3/activity/8/folder/74/6aa6cd4c3cca2_1789316428.txt', 'text/plain', 'txt', 29, 1, '2026-09-14 00:20:28', '2026-09-14 00:20:46', '2026-09-14 00:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `extension_activities`
--

CREATE TABLE `extension_activities` (
  `id` int(11) NOT NULL,
  `activity_code` varchar(30) NOT NULL,
  `component_id` int(11) NOT NULL,
  `activity_type_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `target_participants` int(11) DEFAULT NULL,
  `budget_allocation` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('planned','ongoing','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `extension_activities`
--

INSERT INTO `extension_activities` (`id`, `activity_code`, `component_id`, `activity_type_id`, `title`, `description`, `objectives`, `venue`, `start_datetime`, `end_datetime`, `target_participants`, `budget_allocation`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'ACT-2026-0001', 1, 1, 'Organic Fertilizer Production Training', 'Hands-on training on producing organic fertilizers from local materials.', 'Teach farmers to produce organic fertilizer', 'Brgy. San Isidro Hall', '2026-03-15 08:00:00', '2026-03-17 17:00:00', 50, 35000.00, 'completed', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(2, 'ACT-2026-0002', 1, 1, 'Pest Management in Organic Farming', 'Training on natural pest control methods.', 'Equip farmers with organic pest management skills', 'Brgy. San Isidro Hall', '2026-04-15 08:00:00', '2026-04-16 17:00:00', 45, 25000.00, 'completed', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(3, 'ACT-2026-0003', 1, 10, 'Organic Farm Demonstration', 'On-site demonstration of organic farming techniques.', 'Show practical application of organic farming', 'Demo Farm, San Isidro', '2026-05-10 08:00:00', '2026-05-10 17:00:00', 40, 15000.00, 'completed', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(4, 'ACT-2026-0004', 3, 3, 'Bread Making Workshop', 'Hands-on bread making workshop for youth.', 'Teach bread making skills', 'ISU-Cauayan Bakery Lab', '2026-04-01 08:00:00', '2026-04-03 17:00:00', 25, 20000.00, 'completed', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(5, 'ACT-2026-0005', 3, 3, 'Pastry Decoration Workshop', 'Advanced pastry techniques and decoration.', 'Teach pastry decoration skills', 'ISU-Cauayan Bakery Lab', '2026-05-01 08:00:00', '2026-05-02 17:00:00', 25, 18000.00, 'completed', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(6, 'ACT-2026-0006', 4, 3, 'Basic Computer Operations', 'Introduction to computers, typing, and file management.', 'Teach basic computer skills', 'ISU-Cauayan Computer Lab', '2026-03-01 09:00:00', '2026-03-05 16:00:00', 30, 15000.00, 'completed', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(7, 'ACT-2026-0007', 4, 3, 'Internet and Email Workshop', 'Using internet, email, and online services.', 'Teach internet navigation', 'ISU-Cauayan Computer Lab', '2026-04-01 09:00:00', '2026-04-03 16:00:00', 30, 12000.00, 'completed', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(8, 'ACT-2026-0008', 5, 1, 'Modern Corn Production Techniques', 'Training on improved corn varieties and production methods.', 'Introduce modern corn farming', 'Minante Agricultural Center', '2026-03-20 08:00:00', '2026-03-22 17:00:00', 60, 30000.00, 'completed', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(9, 'ACT-2026-0009', 5, 3, 'Testing', 'Test', 'Blablabla', 'San fermin', '2026-07-30 18:00:00', '2026-07-30 19:00:00', 25, 25000.00, 'planned', 1, '2026-07-30 12:05:08', '2026-07-30 12:05:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faculty_profiles`
--

CREATE TABLE `faculty_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `is_extensionist` tinyint(1) NOT NULL DEFAULT 0,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faculty_profiles`
--

INSERT INTO `faculty_profiles` (`id`, `user_id`, `employee_id`, `first_name`, `last_name`, `middle_name`, `department_id`, `position`, `contact_number`, `email`, `specialization`, `is_extensionist`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 1, 'EMP-001', 'System', 'Administrator', '', 9, 'System Administrator', '09171234567', 'admin@isu-cauayan.edu.ph', '', 0, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(2, 2, 'EMP-002', 'Roberto', 'Mendoza', 'Santos', 9, 'ETS Head', '09171234568', 'ets.head@isu-cauayan.edu.ph', 'Extension Program Management', 1, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(3, 3, 'EMP-003', 'Juan', 'Dela Cruz', 'Reyes', 1, 'Instructor III', '09171234569', 'juan.delacruz@isu-cauayan.edu.ph', 'Organic Farming, Crop Production', 1, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(4, 4, 'EMP-004', 'Maria', 'Santos', 'Garcia', 6, 'Assistant Professor II', '09171234570', 'maria.santos@isu-cauayan.edu.ph', 'Information Technology, Digital Literacy', 1, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(5, 5, 'EMP-005', 'Pedro', 'Reyes', 'Cruz', 1, 'Associate Professor I', '09171234571', 'pedro.reyes@isu-cauayan.edu.ph', 'Animal Science, Livestock', 1, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(6, 6, 'EMP-006', 'Ana', 'Garcia', 'Lopez', 3, 'Instructor II', '09171234572', 'ana.garcia@isu-cauayan.edu.ph', 'Community Education, Literacy', 1, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(7, 7, 'EMP-007', 'Dean', 'Official', '', 4, 'College Dean', '09171234573', 'dean@isu-cauayan.edu.ph', '', 0, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `funding_sources`
--

CREATE TABLE `funding_sources` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `funding_sources`
--

INSERT INTO `funding_sources` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'University Fund', 'Internal ISU funding', 1, '2026-07-29 17:25:11'),
(2, 'CHED', 'Commission on Higher Education', 1, '2026-07-29 17:25:11'),
(3, 'DOST', 'Department of Science and Technology', 1, '2026-07-29 17:25:11'),
(4, 'DA', 'Department of Agriculture', 1, '2026-07-29 17:25:11'),
(5, 'LGU', 'Local Government Unit - Cauayan City', 1, '2026-07-29 17:25:11'),
(6, 'External Grant', 'External grants and donations', 1, '2026-07-29 17:25:11'),
(7, 'GAA', 'General Appropriations Act', 1, '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `moas`
--

CREATE TABLE `moas` (
  `id` int(11) NOT NULL,
  `moa_number` varchar(30) NOT NULL,
  `project_id` int(11) NOT NULL,
  `partner_agency_id` int(11) DEFAULT NULL,
  `date_signed` date DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `status` enum('pending','active','expired','terminated') NOT NULL DEFAULT 'pending',
  `attachment` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `moas`
--

INSERT INTO `moas` (`id`, `moa_number`, `project_id`, `partner_agency_id`, `date_signed`, `expiration_date`, `status`, `attachment`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'MOA-2026-0001', 1, 1, '2026-01-05', '2027-01-05', 'active', NULL, 'Partnership for organic farming project', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(2, 'MOA-2026-0002', 3, 4, '2026-01-25', '2026-07-25', 'active', NULL, 'Venue partnership for digital literacy program', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(3, 'MOA-2025-0003', 5, 3, '2025-05-20', '2025-11-20', 'expired', NULL, 'Health program partnership - expired', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `entity_type`, `entity_id`, `action_url`, `is_read`, `created_at`) VALUES
(1, 3, 'Proposal Approved', 'Your proposal for \"Organic Farming Technology Transfer\" has been approved.', 'success', NULL, NULL, '/index.php?module=proposals', 1, '2026-07-29 17:25:11'),
(2, 3, 'Report Approved', 'Your Q1 accomplishment report has been approved.', 'success', NULL, NULL, '/index.php?module=reports', 1, '2026-07-29 17:25:11'),
(3, 1, 'New Proposal Submitted', 'A new proposal \"Revised Proposal for Phase 2 Training\" has been submitted for review.', 'info', NULL, NULL, '/index.php?module=proposals', 1, '2026-07-29 17:25:11'),
(4, 4, 'Assignment', 'You have been assigned as Digital Literacy Program Leader.', 'info', NULL, NULL, '/index.php?module=programs', 0, '2026-07-29 17:25:11'),
(5, 5, 'Project Update', 'Corn Production Technology project is 25% complete.', 'info', NULL, NULL, '/index.php?module=projects', 0, '2026-07-29 17:25:11'),
(6, 3, 'New Leader Assignment', 'You have been assigned as Leader to Program: Digital Literacy and ICT Skills Development', 'info', 'program', 2, '/index.php?module=programs', 1, '2026-07-29 18:08:57'),
(7, 1, 'New Member Assignment', 'You have been assigned as Member to Program: Digital Literacy and ICT Skills Development', 'info', 'program', 2, '/index.php?module=programs', 1, '2026-07-29 18:08:57'),
(8, 3, 'New Member Assignment', 'You have been assigned as Member to Program: Digital Literacy and ICT Skills Development', 'info', 'program', 2, '/index.php?module=programs', 1, '2026-07-29 18:08:57'),
(9, 2, 'New Leader Assignment', 'You have been assigned as Leader to Program: Digital Literacy and ICT Skills Development', 'info', 'program', 2, '/index.php?module=programs', 0, '2026-07-29 18:09:16'),
(10, 2, 'New Leader Assignment', 'You have been assigned as Leader to Program: Digital Literacy and ICT Skills Development', 'info', 'program', 2, '/index.php?module=programs', 0, '2026-07-29 18:09:35'),
(11, 3, 'New Member Assignment', 'You have been assigned as Member to Program: Digital Literacy and ICT Skills Development', 'info', 'program', 2, '/index.php?module=programs', 1, '2026-07-29 18:09:35'),
(12, 6, 'New Leader Assignment', 'You have been assigned as Leader to Program: Agricultural Technology Transfer Program', 'info', 'program', 3, '/index.php?module=programs', 0, '2026-07-29 18:54:45'),
(13, 2, 'New Leader Assignment', 'You have been assigned as Leader to Program: Digital Literacy and ICT Skills Development', 'info', 'program', 2, '/index.php?module=programs', 0, '2026-07-29 18:55:07'),
(14, 2, 'New Leader Assignment', 'You have been assigned as Leader to Program: Community Development and Livelihood Program', 'info', 'program', 1, '/index.php?module=programs', 0, '2026-07-29 18:55:21'),
(15, 3, 'Assignment Removed', 'You have been removed from Program: Community Development and Livelihood Program', 'warning', 'program', 1, '/index.php?module=programs', 1, '2026-07-29 18:55:21'),
(16, 2, 'New Leader Assignment', 'You have been assigned as Leader to Project: Organic Farming Technology Transfer', 'info', 'project', 1, '/index.php?module=projects', 0, '2026-07-30 09:33:24'),
(17, 5, 'Assignment Removed', 'You have been removed from Project: Organic Farming Technology Transfer', 'warning', 'project', 1, '/index.php?module=projects', 0, '2026-07-30 09:33:24'),
(18, 2, 'New Leader Assignment', 'You have been assigned as Leader to Project: Livelihood Skills Training: Bread and Pastry Making', 'info', 'project', 2, '/index.php?module=projects', 0, '2026-07-30 09:33:36'),
(19, 5, 'New Leader Assignment', 'You have been assigned as Leader to Project: Corn Production Technology Dissemination', 'info', 'project', 4, '/index.php?module=projects', 0, '2026-07-30 09:34:06'),
(20, 3, 'Assignment Removed', 'You have been removed from Project: Corn Production Technology Dissemination', 'warning', 'project', 4, '/index.php?module=projects', 1, '2026-07-30 09:34:06'),
(21, 2, 'New Leader Assignment', 'You have been assigned as Leader to Project: Community Health and Nutrition Awareness', 'info', 'project', 5, '/index.php?module=projects', 0, '2026-07-30 09:34:24'),
(22, 6, 'Assignment Removed', 'You have been removed from Project: Community Health and Nutrition Awareness', 'warning', 'project', 5, '/index.php?module=projects', 0, '2026-07-30 09:34:24'),
(23, 2, 'New Leader Assignment', 'You have been assigned as Leader to Program: Community Development and Livelihood Program', 'info', 'program', 1, '/index.php?module=programs', 0, '2026-07-30 09:39:41'),
(24, 3, 'New Member Assignment', 'You have been assigned as Member to Program: Community Development and Livelihood Program', 'info', 'program', 1, '/index.php?module=programs', 1, '2026-07-30 09:39:41'),
(25, 2, 'New Leader Assignment', 'You have been assigned as Leader to Program: Community Development and Livelihood Program', 'info', 'program', 1, '/index.php?module=programs', 0, '2026-07-30 09:40:16'),
(26, 3, 'Assignment Removed', 'You have been removed from Program: Community Development and Livelihood Program', 'warning', 'program', 1, '/index.php?module=programs', 1, '2026-07-30 09:40:16'),
(27, 2, 'New Leader Assignment', 'You have been assigned as Leader to Program: Community Development and Livelihood Program', 'info', 'program', 1, '/index.php?module=programs', 0, '2026-07-30 09:42:06'),
(28, 3, 'New Member Assignment', 'You have been assigned as Member to Program: Community Development and Livelihood Program', 'info', 'program', 1, '/index.php?module=programs', 1, '2026-07-30 09:42:06'),
(29, 6, 'New Leader Assignment', 'You have been assigned as Leader to Program: Agricultural Technology Transfer Program', 'info', 'program', 3, '/index.php?module=programs', 0, '2026-07-30 10:55:58'),
(30, 3, 'New Member Assignment', 'You have been assigned as Member to Program: Agricultural Technology Transfer Program', 'info', 'program', 3, '/index.php?module=programs', 1, '2026-07-30 10:55:58'),
(31, 3, 'New Leader Assignment', 'You have been assigned as Leader to Program: Agricultural Technology Transfer Program', 'info', 'program', 3, '/index.php?module=programs', 1, '2026-07-30 10:56:25'),
(32, 3, 'New Member Assignment', 'You have been assigned as Member to Program: Agricultural Technology Transfer Program', 'info', 'program', 3, '/index.php?module=programs', 1, '2026-07-30 10:56:25'),
(33, 2, 'New Leader Assignment', 'You have been assigned as Leader to Program: Community Development and Livelihood Program', 'info', 'program', 1, '/index.php?module=programs', 0, '2026-07-30 11:04:27'),
(34, 3, 'Assignment Removed', 'You have been removed from Program: Community Development and Livelihood Program', 'warning', 'program', 1, '/index.php?module=programs', 1, '2026-07-30 11:04:27'),
(35, 5, 'New Leader Assignment', 'You have been assigned as Leader to Project: Corn Production Technology Dissemination', 'info', 'project', 4, '/index.php?module=projects', 0, '2026-07-30 12:03:36'),
(36, 3, 'New Member Assignment', 'You have been assigned as Member to Project: Corn Production Technology Dissemination', 'info', 'project', 4, '/index.php?module=projects', 1, '2026-07-30 12:03:36'),
(37, 3, 'New Leader Assignment', 'You have been assigned as Leader to Project: Corn Production Technology Dissemination', 'info', 'project', 4, '/index.php?module=projects', 1, '2026-07-30 12:26:55'),
(38, 3, 'New Member Assignment', 'You have been assigned as Member to Project: Corn Production Technology Dissemination', 'info', 'project', 4, '/index.php?module=projects', 1, '2026-07-30 12:26:55'),
(39, 1, 'New Program Proposal', 'Faculty submitted a proposal: Test', 'info', 'proposal', 7, '/index.php?module=proposals', 1, '2026-07-30 12:49:55'),
(40, 2, 'New Program Proposal', 'Faculty submitted a proposal: Test', 'info', 'proposal', 7, '/index.php?module=proposals', 0, '2026-07-30 12:49:55'),
(41, 3, 'Proposal Approved', 'Your proposal \'Test\' has been approved.', 'success', 'proposal', 7, '/index.php?module=proposals', 1, '2026-07-30 12:57:43'),
(42, 3, 'Proposal Approved', 'Your proposal \'Test\' has been approved.', 'success', 'proposal', 7, '/index.php?module=proposals', 1, '2026-07-30 13:02:55'),
(43, 3, 'Proposal Approved', 'Your proposal \'Test\' has been approved.', 'success', 'proposal', 7, '/index.php?module=proposals', 1, '2026-07-30 13:12:33'),
(44, 3, 'New Leader Assignment', 'You have been assigned as Leader to Project: Corn Production Technology Dissemination', 'info', 'project', 4, '/index.php?module=projects', 1, '2026-09-08 16:57:35'),
(45, 5, 'New Member Assignment', 'You have been assigned as Member to Project: Corn Production Technology Dissemination', 'info', 'project', 4, '/index.php?module=projects', 0, '2026-09-08 16:57:35'),
(46, 3, 'Proposal Returned', 'Your proposal \'Revised Proposal for Phase 2 Training\' has been returned.', 'warning', 'proposal', 5, '/index.php?module=proposals', 1, '2026-09-08 17:45:30'),
(47, 1, 'New Program Proposal', 'Faculty submitted a proposal: test Propposal', 'info', 'proposal', 8, '/index.php?module=proposals', 0, '2026-09-21 11:15:48'),
(48, 2, 'New Program Proposal', 'Faculty submitted a proposal: test Propposal', 'info', 'proposal', 8, '/index.php?module=proposals', 0, '2026-09-21 11:15:48'),
(49, 1, 'New Program Proposal', 'Faculty submitted a proposal: HAKDOG', 'info', 'proposal', 9, '/index.php?module=proposals', 0, '2026-09-21 11:20:19'),
(50, 2, 'New Program Proposal', 'Faculty submitted a proposal: HAKDOG', 'info', 'proposal', 9, '/index.php?module=proposals', 0, '2026-09-21 11:20:19'),
(51, 4, 'Proposal Approved', 'Your proposal \'HAKDOG\' has been approved.', 'success', 'proposal', 9, '/index.php?module=proposals', 0, '2026-09-21 11:27:19'),
(52, 4, 'Program Created', 'Your proposal has been approved and Program \'HAKDOG\' has been created.', 'success', 'program', 4, '/index.php?module=explorer', 0, '2026-09-21 11:27:19'),
(53, 4, 'Proposal Approved', 'Your proposal \'test Propposal\' has been approved.', 'success', 'proposal', 8, '/index.php?module=proposals', 0, '2026-09-21 11:28:02'),
(54, 4, 'Program Created', 'Your proposal has been approved and Program \'test Propposal\' has been created.', 'success', 'program', 5, '/index.php?module=explorer', 0, '2026-09-21 11:28:02');

-- --------------------------------------------------------

--
-- Table structure for table `partner_agencies`
--

CREATE TABLE `partner_agencies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `agency_type` enum('government','ngo','private','academic') DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partner_agencies`
--

INSERT INTO `partner_agencies` (`id`, `name`, `agency_type`, `contact_person`, `contact_number`, `email`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Cauayan City Agriculture Office', 'government', 'Engr. Pedro Santos', '09171234580', 'agriculture@cauayan.gov.ph', 'Cauayan City Hall, Isabela', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(2, 'Department of Agriculture - Isabela', 'government', 'Dr. Maria Reyes', '09171234581', 'da.isabela@gov.ph', 'Ilagan, Isabela', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(3, 'Isabela Provincial Health Office', 'government', 'Dr. Jose Garcia', '09171234582', 'health@isabela.gov.ph', 'Ilagan, Isabela', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(4, 'Cauayan City Health Office', 'government', 'Dr. Linda Cruz', '09171234583', 'health@cauayan.gov.ph', 'Cauayan City, Isabela', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(5, 'Philippine Red Cross - Isabela Chapter', 'ngo', 'Roberto Bautista', '09171234584', 'redcross@isabela.ph', 'Ilagan, Isabela', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(6, 'Isabela State University - Echague', 'academic', 'Dr. Carmen Rivera', '09171234585', 'echague@isu.edu.ph', 'Echague, Isabela', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(7, 'Municipal Agriculture Office - Echague', 'government', 'Engr. Mark Tan', '09171234586', 'agri@echague.gov.ph', 'Echague, Isabela', 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_code` varchar(30) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `college` varchar(150) DEFAULT NULL,
  `campus` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','active','completed','archived') NOT NULL DEFAULT 'draft',
  `objectives` text DEFAULT NULL,
  `expected_outputs` text DEFAULT NULL,
  `budget_allocation` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_code`, `title`, `description`, `college`, `campus`, `start_date`, `end_date`, `status`, `objectives`, `expected_outputs`, `budget_allocation`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PROG-2026-0001', 'Community Development and Livelihood Program', 'A comprehensive program aimed at improving community livelihoods through skills training and technology transfer.', 'College of Agriculture', 'Cauayan Campus', '2026-01-01', '2028-12-31', 'active', 'To improve the quality of life of community members through sustainable livelihood programs and skills development.', 'Trained community members, improved livelihood skills, sustainable income-generating projects', 500000.00, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(2, 'PROG-2026-0002', 'Digital Literacy and ICT Skills Development', 'Program to enhance digital literacy among community members and faculty.', 'College of Computing and Information Sciences', 'Cauayan Campus', '2026-02-01', '2027-12-31', 'active', 'To bridge the digital divide by providing ICT skills training to community members.', 'Digitally literate community members, trained faculty, online learning resources', 300000.00, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(3, 'PROG-2026-0003', 'Agricultural Technology Transfer Program', 'Transfer of modern agricultural technologies to local farmers.', 'College of Agriculture', 'Cauayan Campus', '2026-01-15', '2027-06-30', 'active', 'To increase agricultural productivity through adoption of modern farming technologies.', 'Adopted technologies, increased crop yields, trained farmers', 750000.00, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(4, 'PROG-2026-0004', 'HAKDOG', 'ABC', 'CCCISCT', 'Cauayan Campus', '2026-09-21', '2026-09-22', 'active', 'TESDT', '', 32110.00, 1, '2026-09-21 11:27:19', '2026-09-21 11:27:19', NULL),
(5, 'PROG-2026-0005', 'test Propposal', 'Hakdog', 'CCSICT', 'Cauayan Campus', '2026-09-21', '2026-09-30', 'active', 'Hakdog', '', 50000.00, 1, '2026-09-21 11:28:02', '2026-09-21 11:28:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `program_assignments`
--

CREATE TABLE `program_assignments` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `assignment_type` enum('leader','member') NOT NULL DEFAULT 'member',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_assignments`
--

INSERT INTO `program_assignments` (`id`, `program_id`, `faculty_id`, `assignment_type`, `assigned_by`, `assigned_at`, `is_active`) VALUES
(1, 1, 2, 'leader', 1, '2026-07-29 17:25:11', 1),
(3, 2, 4, 'leader', 1, '2026-07-29 17:25:11', 0),
(5, 3, 5, 'member', 1, '2026-07-29 17:25:11', 1),
(7, 2, 1, 'member', 1, '2026-07-29 18:08:57', 1),
(9, 2, 2, 'leader', 1, '2026-07-29 18:09:16', 1),
(12, 3, 6, 'leader', 1, '2026-07-29 18:54:45', 0),
(16, 1, 3, 'member', 1, '2026-07-30 09:39:41', 0),
(21, 3, 3, 'leader', 1, '2026-07-30 10:55:58', 1),
(25, 4, 4, 'leader', 1, '2026-09-21 11:27:19', 1),
(26, 5, 4, 'leader', 1, '2026-09-21 11:28:02', 1);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_code` varchar(30) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `funding_source_id` int(11) DEFAULT NULL,
  `partner_agency_id` int(11) DEFAULT NULL,
  `beneficiary_group_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `expected_outputs` text DEFAULT NULL,
  `status` enum('draft','pending','approved','ongoing','completed','cancelled','archived') NOT NULL DEFAULT 'draft',
  `completion_percentage` int(3) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_code`, `program_id`, `title`, `description`, `start_date`, `end_date`, `budget`, `funding_source_id`, `partner_agency_id`, `beneficiary_group_id`, `location`, `objectives`, `expected_outputs`, `status`, `completion_percentage`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PROJ-2026-0001', 1, 'Organic Farming Technology Transfer', 'Training program for organic farming techniques for local farmers in Cauayan City.', '2026-01-15', '2026-12-31', 250000.00, 1, 1, 1, 'Brgy. San Isidro, Cauayan City', 'To equip farmers with sustainable organic farming skills and knowledge.', 'Trained farmers, organic farming manual, demonstration farm', 'ongoing', 45, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(2, 'PROJ-2026-0002', 1, 'Livelihood Skills Training: Bread and Pastry Making', 'Hands-on training for out-of-school youth on bread and pastry production.', '2026-03-01', '2026-08-31', 120000.00, 1, NULL, 3, 'ISU-Cauayan Campus', 'To provide practical skills for self-employment.', 'Trained youth, business plans, startup kits', 'ongoing', 30, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(3, 'PROJ-2026-0003', 2, 'Digital Literacy Program for Senior Citizens', 'Workshop series teaching basic computer skills to senior citizens.', '2026-02-01', '2026-07-31', 80000.00, 5, 4, 2, 'Cauayan City Hall', 'To improve digital literacy among elderly residents.', 'Digitally literate seniors, training materials', 'ongoing', 60, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(4, 'PROJ-2026-0004', 3, 'Corn Production Technology Dissemination', 'Technology transfer for improved corn production techniques.', '2026-01-20', '2026-12-31', 350000.00, 4, 2, 7, 'Minante, Cauayan City', 'To increase corn yield through modern production techniques.', 'Improved farming practices, higher yields, trained farmers', 'ongoing', 50, 1, '2026-07-29 17:25:11', '2026-07-30 13:35:04', NULL),
(5, 'PROJ-2025-0005', 1, 'Community Health and Nutrition Awareness', 'Health education and nutrition counseling for community members.', '2025-06-01', '2025-12-31', 95000.00, 6, 3, NULL, 'District I, Cauayan City', 'To improve health awareness and nutrition practices.', 'Health awareness materials, trained health advocates', 'completed', 100, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(6, 'PROJ-2026-0006', 5, 'Test Hakdog123', 'Test', '2026-09-21', '2026-09-21', 0.00, NULL, NULL, NULL, 'ISU Cauayan', 'TEST', 'TEST', 'draft', 0, 4, '2026-09-21 11:34:43', '2026-09-21 11:34:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `project_assignments`
--

CREATE TABLE `project_assignments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `assignment_type` enum('leader','member') NOT NULL DEFAULT 'member',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_assignments`
--

INSERT INTO `project_assignments` (`id`, `project_id`, `faculty_id`, `assignment_type`, `assigned_by`, `assigned_at`, `is_active`) VALUES
(2, 1, 5, 'member', 1, '2026-07-29 17:25:11', 0),
(4, 3, 4, 'leader', 1, '2026-07-29 17:25:11', 1),
(5, 4, 5, 'leader', 3, '2026-07-29 17:25:11', 1),
(8, 5, 6, 'member', 1, '2026-07-29 17:25:11', 0),
(9, 1, 2, 'leader', 1, '2026-07-30 09:33:24', 1),
(10, 2, 2, 'leader', 1, '2026-07-30 09:33:36', 1),
(12, 5, 2, 'leader', 1, '2026-07-30 09:34:24', 1),
(14, 4, 3, 'leader', 3, '2026-07-30 12:03:36', 1),
(19, 6, 4, 'leader', 4, '2026-09-21 11:34:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `proposals`
--

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL,
  `proposal_number` varchar(30) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `date_submitted` date DEFAULT NULL,
  `status` enum('draft','submitted','under_review','approved','returned','rejected') NOT NULL DEFAULT 'draft',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `proposals`
--

INSERT INTO `proposals` (`id`, `proposal_number`, `project_id`, `title`, `submitted_by`, `date_submitted`, `status`, `reviewed_by`, `reviewed_at`, `remarks`, `attachment`, `version`, `created_at`, `updated_at`) VALUES
(1, 'PROP-2026-0001', 1, 'Proposal for Organic Farming Technology Transfer', 3, '2026-01-10', 'approved', NULL, NULL, 'Approved by ETS. Well-structured proposal.', NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(2, 'PROP-2026-0002', 2, 'Proposal for Livelihood Skills Training', 3, '2026-02-15', 'approved', NULL, NULL, 'Approved with minor budget adjustments.', NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(3, 'PROP-2026-0003', 3, 'Proposal for Digital Literacy Program', 4, '2026-01-20', 'approved', NULL, NULL, 'Approved. Good community impact.', NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(4, 'PROP-2026-0004', 4, 'Proposal for Corn Production Technology', 5, '2026-01-25', 'approved', NULL, NULL, 'Approved. Strong partnership with DA.', NULL, 1, '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(5, 'PROP-2026-0005', 1, 'Revised Proposal for Phase 2 Training', 3, '2026-06-01', 'returned', 1, '2026-09-08 17:45:30', 'test', NULL, 2, '2026-07-29 17:25:11', '2026-09-08 17:45:30'),
(7, 'PROP-2026-0006', NULL, 'Test', 3, '2026-07-30', 'approved', 1, '2026-07-30 13:12:33', '', NULL, 1, '2026-07-30 12:49:55', '2026-07-30 13:12:33'),
(8, 'PROP-2026-0007', NULL, 'test Propposal', 4, '2026-09-21', 'approved', 1, '2026-09-21 11:28:02', '{\"type\":\"program_proposal\",\"description\":\"Hakdog\",\"college\":\"CCSICT\",\"campus\":\"Cauayan Campus\",\"start_date\":\"2026-09-21\",\"end_date\":\"2026-09-30\",\"budget\":50000,\"funding_source\":\"Unknown\",\"objectives\":\"Hakdog\",\"beneficiaries\":\"PWD\",\"location\":\"Cauayan City\"}', 'proposals/6ab0a1642d18d_1789960548.docx', 1, '2026-09-21 11:15:48', '2026-09-21 11:28:02'),
(9, 'PROP-2026-0008', NULL, 'HAKDOG', 4, '2026-09-21', 'approved', 1, '2026-09-21 11:27:19', '{\"type\":\"program_proposal\",\"description\":\"ABC\",\"college\":\"CCCISCT\",\"campus\":\"Cauayan Campus\",\"start_date\":\"2026-09-21\",\"end_date\":\"2026-09-22\",\"budget\":32110,\"funding_source\":\"ANY\",\"objectives\":\"TESDT\",\"beneficiaries\":\"PWD\",\"location\":\"CAUAYAN\"}', 'proposals/6ab0a273b05ca_1789960819.pdf', 1, '2026-09-21 11:20:19', '2026-09-21 11:27:19');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_approvals`
--

CREATE TABLE `proposal_approvals` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `action` enum('submitted','reviewed','approved','returned','rejected') NOT NULL,
  `remarks` text DEFAULT NULL,
  `performed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `proposal_approvals`
--

INSERT INTO `proposal_approvals` (`id`, `proposal_id`, `approver_id`, `action`, `remarks`, `performed_at`) VALUES
(1, 1, 1, 'approved', 'Approved by ETS. Well-structured proposal.', '2026-01-12 10:00:00'),
(2, 2, 1, 'approved', 'Approved with minor budget adjustments.', '2026-02-17 14:00:00'),
(3, 3, 1, 'approved', 'Approved. Good community impact.', '2026-01-22 09:00:00'),
(4, 4, 1, 'approved', 'Approved. Strong partnership with DA.', '2026-01-27 11:00:00'),
(6, 7, 1, 'approved', '', '2026-07-30 13:02:55'),
(7, 7, 1, 'approved', '', '2026-07-30 13:12:33'),
(8, 5, 1, 'returned', 'test', '2026-09-08 17:45:30'),
(9, 9, 1, 'approved', '', '2026-09-21 11:27:19'),
(10, 8, 1, 'approved', '', '2026-09-21 11:28:02');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_versions`
--

CREATE TABLE `proposal_versions` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_approvals`
--

CREATE TABLE `report_approvals` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `action` enum('submitted','reviewed','approved','returned') NOT NULL,
  `remarks` text DEFAULT NULL,
  `performed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_approvals`
--

INSERT INTO `report_approvals` (`id`, `report_id`, `approver_id`, `action`, `remarks`, `performed_at`) VALUES
(1, 1, 1, 'approved', 'Good progress. Continue with Q2 activities.', '2026-04-08 10:00:00'),
(2, 2, 1, 'approved', 'Excellent work with the seniors program.', '2026-04-05 14:00:00'),
(3, 3, 1, 'approved', 'Terminal report accepted. Well documented.', '2026-01-18 09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `created_at`, `updated_at`) VALUES
(1, 'school_name', 'Isabela State University - Cauayan', 'general', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(2, 'school_address', 'Cauayan, Isabela, Philippines', 'general', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(3, 'system_name', 'ETS Project Management Hub', 'general', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(4, 'academic_year', '2025-2026', 'general', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(5, 'contact_email', 'ets@isu-cauayan.edu.ph', 'general', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(6, 'contact_phone', '+63 78 652 0000', 'general', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(7, 'website', 'https://isu.edu.ph', 'general', '2026-07-29 17:25:11', '2026-07-29 17:25:11'),
(8, 'mail_enabled', '1', 'mail', '2026-09-20 10:15:38', '2026-09-21 16:51:07'),
(9, 'mail_host', 'smtp.gmail.com', 'mail', '2026-09-20 10:15:38', '2026-09-21 16:50:44'),
(10, 'mail_port', '587', 'mail', '2026-09-20 10:15:38', '2026-09-20 10:15:38'),
(11, 'mail_encryption', 'tls', 'mail', '2026-09-20 10:15:38', '2026-09-20 10:15:38'),
(12, 'mail_auth', '1', 'mail', '2026-09-20 10:15:38', '2026-09-20 10:15:38'),
(13, 'mail_username', 'etsoffice26@gmail.com', 'mail', '2026-09-20 10:15:38', '2026-09-21 16:50:44'),
(14, 'mail_password', 'CaLBVrg4AKqC3MASQdXCc4JE6lEVkVuMoxCSl0cRTiRZ6OwESl9cJfZkF3KL2mM=', 'mail', '2026-09-20 10:15:38', '2026-09-21 16:50:44'),
(15, 'mail_from_email', 'etsoffice26@gmail.com', 'mail', '2026-09-20 10:15:38', '2026-09-21 16:50:44'),
(16, 'mail_from_name', 'ISU-Cauayan Extension Services', 'mail', '2026-09-20 10:15:38', '2026-09-20 10:15:38'),
(17, 'mail_reply_to', '', 'mail', '2026-09-20 10:15:38', '2026-09-20 10:15:38'),
(18, 'mail_app_url', 'https://ets-project-hub.great-site.net', 'mail', '2026-09-20 10:15:38', '2026-09-21 16:51:07'),
(19, 'mail_proposals', '1', 'mail', '2026-09-20 10:15:38', '2026-09-20 10:15:38'),
(20, 'mail_reports', '1', 'mail', '2026-09-20 10:15:38', '2026-09-21 16:51:11'),
(21, 'mail_assignments', '1', 'mail', '2026-09-20 10:15:38', '2026-09-20 10:15:38'),
(64, 'mail_worker_last_run', '2026-09-21 16:51:14', 'mail', '2026-09-21 16:51:14', '2026-09-21 16:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','faculty','viewer') NOT NULL DEFAULT 'faculty',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `registration_status` enum('approved','pending','rejected') NOT NULL DEFAULT 'approved',
  `registration_reviewed_by` int(11) DEFAULT NULL,
  `registration_reviewed_at` datetime DEFAULT NULL,
  `registration_rejection_reason` varchar(500) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `is_active`, `email_verified`, `last_login`, `last_password_change`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'admin', 'admin@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'admin', 1, 0, '2026-09-21 11:20:42', NULL, '2026-07-29 17:25:11', '2026-09-21 11:20:42', NULL),
(2, 'etshead', 'ets.head@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'admin', 1, 0, '2026-09-21 16:46:34', NULL, '2026-07-29 17:25:11', '2026-09-21 16:46:34', NULL),
(3, 'faculty1', 'juan.delacruz@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty', 1, 0, '2026-09-21 11:19:01', NULL, '2026-07-29 17:25:11', '2026-09-21 11:19:01', NULL),
(4, 'faculty2', 'maria.santos@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty', 1, 0, '2026-09-21 16:43:05', NULL, '2026-07-29 17:25:11', '2026-09-21 16:43:06', NULL),
(5, 'faculty3', 'pedro.reyes@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty', 1, 0, '2026-09-08 16:58:48', NULL, '2026-07-29 17:25:11', '2026-09-08 16:58:48', NULL),
(6, 'faculty4', 'ana.garcia@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty', 1, 0, NULL, NULL, '2026-07-29 17:25:11', '2026-07-29 17:25:11', NULL),
(7, 'viewer', 'dean@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'viewer', 1, 0, '2026-09-20 10:15:08', NULL, '2026-07-29 17:25:11', '2026-09-20 10:15:08', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accomplishment_reports`
--
ALTER TABLE `accomplishment_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_number` (`report_number`),
  ADD KEY `fk_ar_project` (`project_id`),
  ADD KEY `fk_ar_submitter` (`submitted_by`);

--
-- Indexes for table `activity_assignments`
--
ALTER TABLE `activity_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `act_faculty` (`activity_id`,`faculty_id`),
  ADD KEY `fk_acta_act` (`activity_id`),
  ADD KEY `fk_acta_faculty` (`faculty_id`);

--
-- Indexes for table `activity_attendance`
--
ALTER TABLE `activity_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_att_activity` (`activity_id`),
  ADD KEY `fk_att_participant` (`participant_id`);

--
-- Indexes for table `activity_evaluations`
--
ALTER TABLE `activity_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_eval_activity` (`activity_id`);

--
-- Indexes for table `activity_outputs`
--
ALTER TABLE `activity_outputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_output_activity` (`activity_id`);

--
-- Indexes for table `activity_participants`
--
ALTER TABLE `activity_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `fk_ap_activity` (`activity_id`),
  ADD KEY `fk_ap_beneficiary` (`beneficiary_group_id`);

--
-- Indexes for table `activity_photos`
--
ALTER TABLE `activity_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_photo_activity` (`activity_id`);

--
-- Indexes for table `activity_resource_speakers`
--
ALTER TABLE `activity_resource_speakers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_speaker_activity` (`activity_id`);

--
-- Indexes for table `activity_types`
--
ALTER TABLE `activity_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indexes for table `beneficiary_groups`
--
ALTER TABLE `beneficiary_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `fk_cert_template` (`template_id`),
  ADD KEY `fk_cert_activity` (`activity_id`);

--
-- Indexes for table `certificate_templates`
--
ALTER TABLE `certificate_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `components`
--
ALTER TABLE `components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `component_code` (`component_code`),
  ADD KEY `fk_comp_project` (`project_id`),
  ADD KEY `fk_comp_creator` (`created_by`);

--
-- Indexes for table `component_assignments`
--
ALTER TABLE `component_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comp_faculty` (`component_id`,`faculty_id`),
  ADD KEY `fk_compa_comp` (`component_id`),
  ADD KEY `fk_compa_faculty` (`faculty_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_doc_category` (`category_id`),
  ADD KEY `fk_doc_uploader` (`uploaded_by`);

--
-- Indexes for table `document_categories`
--
ALTER TABLE `document_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email_notification` (`notification_id`),
  ADD KEY `idx_email_delivery` (`status`,`available_at`);

--
-- Indexes for table `explorer_migrations`
--
ALTER TABLE `explorer_migrations`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `explorer_nodes`
--
ALTER TABLE `explorer_nodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `explorer_parent` (`parent_id`,`deleted_at`),
  ADD KEY `explorer_entity` (`kind`,`entity_id`),
  ADD KEY `explorer_owner_fk` (`created_by`);

--
-- Indexes for table `extension_activities`
--
ALTER TABLE `extension_activities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `activity_code` (`activity_code`),
  ADD KEY `fk_act_component` (`component_id`),
  ADD KEY `fk_act_type` (`activity_type_id`),
  ADD KEY `fk_act_creator` (`created_by`);

--
-- Indexes for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `fk_fp_dept` (`department_id`);

--
-- Indexes for table `funding_sources`
--
ALTER TABLE `funding_sources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `moas`
--
ALTER TABLE `moas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `moa_number` (`moa_number`),
  ADD KEY `fk_moa_project` (`project_id`),
  ADD KEY `fk_moa_partner` (`partner_agency_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_user` (`user_id`);

--
-- Indexes for table `partner_agencies`
--
ALTER TABLE `partner_agencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `fk_prog_creator` (`created_by`);

--
-- Indexes for table `program_assignments`
--
ALTER TABLE `program_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prog_faculty` (`program_id`,`faculty_id`),
  ADD KEY `fk_pa_prog` (`program_id`),
  ADD KEY `fk_pa_faculty` (`faculty_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `fk_proj_program` (`program_id`),
  ADD KEY `fk_proj_funding` (`funding_source_id`),
  ADD KEY `fk_proj_partner` (`partner_agency_id`),
  ADD KEY `fk_proj_beneficiary` (`beneficiary_group_id`),
  ADD KEY `fk_proj_creator` (`created_by`);

--
-- Indexes for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proj_faculty` (`project_id`,`faculty_id`),
  ADD KEY `fk_prja_proj` (`project_id`),
  ADD KEY `fk_prja_faculty` (`faculty_id`);

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proposal_number` (`proposal_number`),
  ADD KEY `fk_prop_project` (`project_id`),
  ADD KEY `fk_prop_submitter` (`submitted_by`);

--
-- Indexes for table `proposal_approvals`
--
ALTER TABLE `proposal_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pa_proposal` (`proposal_id`);

--
-- Indexes for table `proposal_versions`
--
ALTER TABLE `proposal_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pv_proposal` (`proposal_id`);

--
-- Indexes for table `report_approvals`
--
ALTER TABLE `report_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ra_report` (`report_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accomplishment_reports`
--
ALTER TABLE `accomplishment_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `activity_assignments`
--
ALTER TABLE `activity_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `activity_attendance`
--
ALTER TABLE `activity_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `activity_evaluations`
--
ALTER TABLE `activity_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `activity_outputs`
--
ALTER TABLE `activity_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `activity_participants`
--
ALTER TABLE `activity_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `activity_photos`
--
ALTER TABLE `activity_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `activity_resource_speakers`
--
ALTER TABLE `activity_resource_speakers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `activity_types`
--
ALTER TABLE `activity_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `beneficiary_groups`
--
ALTER TABLE `beneficiary_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `certificate_templates`
--
ALTER TABLE `certificate_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `components`
--
ALTER TABLE `components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `component_assignments`
--
ALTER TABLE `component_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `document_categories`
--
ALTER TABLE `document_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `explorer_nodes`
--
ALTER TABLE `explorer_nodes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `extension_activities`
--
ALTER TABLE `extension_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `funding_sources`
--
ALTER TABLE `funding_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `moas`
--
ALTER TABLE `moas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `partner_agencies`
--
ALTER TABLE `partner_agencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `program_assignments`
--
ALTER TABLE `program_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `proposal_approvals`
--
ALTER TABLE `proposal_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `proposal_versions`
--
ALTER TABLE `proposal_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_approvals`
--
ALTER TABLE `report_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accomplishment_reports`
--
ALTER TABLE `accomplishment_reports`
  ADD CONSTRAINT `fk_ar_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ar_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `activity_assignments`
--
ALTER TABLE `activity_assignments`
  ADD CONSTRAINT `fk_acta_act` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_acta_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_attendance`
--
ALTER TABLE `activity_attendance`
  ADD CONSTRAINT `fk_att_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_participant` FOREIGN KEY (`participant_id`) REFERENCES `activity_participants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_evaluations`
--
ALTER TABLE `activity_evaluations`
  ADD CONSTRAINT `fk_eval_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_outputs`
--
ALTER TABLE `activity_outputs`
  ADD CONSTRAINT `fk_output_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_participants`
--
ALTER TABLE `activity_participants`
  ADD CONSTRAINT `fk_ap_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ap_beneficiary` FOREIGN KEY (`beneficiary_group_id`) REFERENCES `beneficiary_groups` (`id`);

--
-- Constraints for table `activity_photos`
--
ALTER TABLE `activity_photos`
  ADD CONSTRAINT `fk_photo_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_resource_speakers`
--
ALTER TABLE `activity_resource_speakers`
  ADD CONSTRAINT `fk_speaker_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `fk_cert_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`),
  ADD CONSTRAINT `fk_cert_template` FOREIGN KEY (`template_id`) REFERENCES `certificate_templates` (`id`);

--
-- Constraints for table `components`
--
ALTER TABLE `components`
  ADD CONSTRAINT `fk_comp_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_comp_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `component_assignments`
--
ALTER TABLE `component_assignments`
  ADD CONSTRAINT `fk_compa_comp` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_compa_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_doc_category` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`),
  ADD CONSTRAINT `fk_doc_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD CONSTRAINT `fk_email_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `explorer_nodes`
--
ALTER TABLE `explorer_nodes`
  ADD CONSTRAINT `explorer_owner_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `explorer_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `explorer_nodes` (`id`);

--
-- Constraints for table `extension_activities`
--
ALTER TABLE `extension_activities`
  ADD CONSTRAINT `fk_act_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_act_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_act_type` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`);

--
-- Constraints for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  ADD CONSTRAINT `fk_fp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_fp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `moas`
--
ALTER TABLE `moas`
  ADD CONSTRAINT `fk_moa_partner` FOREIGN KEY (`partner_agency_id`) REFERENCES `partner_agencies` (`id`),
  ADD CONSTRAINT `fk_moa_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `fk_prog_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `program_assignments`
--
ALTER TABLE `program_assignments`
  ADD CONSTRAINT `fk_pa_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pa_prog` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_proj_beneficiary` FOREIGN KEY (`beneficiary_group_id`) REFERENCES `beneficiary_groups` (`id`),
  ADD CONSTRAINT `fk_proj_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_proj_funding` FOREIGN KEY (`funding_source_id`) REFERENCES `funding_sources` (`id`),
  ADD CONSTRAINT `fk_proj_partner` FOREIGN KEY (`partner_agency_id`) REFERENCES `partner_agencies` (`id`),
  ADD CONSTRAINT `fk_proj_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`);

--
-- Constraints for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD CONSTRAINT `fk_prja_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prja_proj` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proposals`
--
ALTER TABLE `proposals`
  ADD CONSTRAINT `fk_prop_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prop_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `proposal_approvals`
--
ALTER TABLE `proposal_approvals`
  ADD CONSTRAINT `fk_pa_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proposal_versions`
--
ALTER TABLE `proposal_versions`
  ADD CONSTRAINT `fk_pv_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_approvals`
--
ALTER TABLE `report_approvals`
  ADD CONSTRAINT `fk_ra_report` FOREIGN KEY (`report_id`) REFERENCES `accomplishment_reports` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
