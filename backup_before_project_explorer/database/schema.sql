-- ETS Project Management Hub - Complete Database Schema
-- ISU-Cauayan Extension Training Services

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+08:00";

CREATE DATABASE IF NOT EXISTS `isu-proj-hub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `isu-proj-hub`;

-- ============================================================
-- AUTHENTICATION & USERS
-- ============================================================

CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','faculty','viewer') NOT NULL DEFAULT 'faculty',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faculty_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `fk_fp_dept` (`department_id`),
  CONSTRAINT `fk_fp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ORGANIZATIONAL HIERARCHY
-- ============================================================

CREATE TABLE `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_code` (`program_code`),
  KEY `fk_prog_creator` (`created_by`),
  CONSTRAINT `fk_prog_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `program_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `assignment_type` enum('leader','member') NOT NULL DEFAULT 'member',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prog_faculty` (`program_id`,`faculty_id`),
  KEY `fk_pa_prog` (`program_id`),
  KEY `fk_pa_faculty` (`faculty_id`),
  CONSTRAINT `fk_pa_prog` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `funding_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `partner_agencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `agency_type` enum('government','ngo','private','academic') DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `beneficiary_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `group_type` enum('individual','organization','community','lgu') DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_code` (`project_code`),
  KEY `fk_proj_program` (`program_id`),
  KEY `fk_proj_funding` (`funding_source_id`),
  KEY `fk_proj_partner` (`partner_agency_id`),
  KEY `fk_proj_beneficiary` (`beneficiary_group_id`),
  KEY `fk_proj_creator` (`created_by`),
  CONSTRAINT `fk_proj_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  CONSTRAINT `fk_proj_funding` FOREIGN KEY (`funding_source_id`) REFERENCES `funding_sources` (`id`),
  CONSTRAINT `fk_proj_partner` FOREIGN KEY (`partner_agency_id`) REFERENCES `partner_agencies` (`id`),
  CONSTRAINT `fk_proj_beneficiary` FOREIGN KEY (`beneficiary_group_id`) REFERENCES `beneficiary_groups` (`id`),
  CONSTRAINT `fk_proj_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `assignment_type` enum('leader','member') NOT NULL DEFAULT 'member',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proj_faculty` (`project_id`,`faculty_id`),
  KEY `fk_prja_proj` (`project_id`),
  KEY `fk_prja_faculty` (`faculty_id`),
  CONSTRAINT `fk_prja_proj` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prja_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `component_code` (`component_code`),
  KEY `fk_comp_project` (`project_id`),
  KEY `fk_comp_creator` (`created_by`),
  CONSTRAINT `fk_comp_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comp_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `component_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `component_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `assignment_type` enum('leader','member') NOT NULL DEFAULT 'member',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comp_faculty` (`component_id`,`faculty_id`),
  KEY `fk_compa_comp` (`component_id`),
  KEY `fk_compa_faculty` (`faculty_id`),
  CONSTRAINT `fk_compa_comp` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_compa_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `extension_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `activity_code` (`activity_code`),
  KEY `fk_act_component` (`component_id`),
  KEY `fk_act_type` (`activity_type_id`),
  KEY `fk_act_creator` (`created_by`),
  CONSTRAINT `fk_act_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_act_type` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`),
  CONSTRAINT `fk_act_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `assignment_type` enum('leader','member') NOT NULL DEFAULT 'member',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `act_faculty` (`activity_id`,`faculty_id`),
  KEY `fk_acta_act` (`activity_id`),
  KEY `fk_acta_faculty` (`faculty_id`),
  CONSTRAINT `fk_acta_act` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acta_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ACTIVITY IMPLEMENTATION
-- ============================================================

CREATE TABLE `activity_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `fk_ap_activity` (`activity_id`),
  KEY `fk_ap_beneficiary` (`beneficiary_group_id`),
  CONSTRAINT `fk_ap_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ap_beneficiary` FOREIGN KEY (`beneficiary_group_id`) REFERENCES `beneficiary_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('present','absent','excused') NOT NULL DEFAULT 'present',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_att_activity` (`activity_id`),
  KEY `fk_att_participant` (`participant_id`),
  CONSTRAINT `fk_att_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_participant` FOREIGN KEY (`participant_id`) REFERENCES `activity_participants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_photo_activity` (`activity_id`),
  CONSTRAINT `fk_photo_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_resource_speakers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `affiliation` varchar(200) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_speaker_activity` (`activity_id`),
  CONSTRAINT `fk_speaker_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `overall_rating` int(1) DEFAULT NULL,
  `content_rating` int(1) DEFAULT NULL,
  `speaker_rating` int(1) DEFAULT NULL,
  `venue_rating` int(1) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_eval_activity` (`activity_id`),
  CONSTRAINT `fk_eval_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_outputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `output_type` enum('material','report','certificate','other') DEFAULT 'other',
  `file_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_output_activity` (`activity_id`),
  CONSTRAINT `fk_output_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PROPOSALS & APPROVALS
-- ============================================================

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_number` varchar(30) NOT NULL,
  `project_id` int(11) NOT NULL,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposal_number` (`proposal_number`),
  KEY `fk_prop_project` (`project_id`),
  KEY `fk_prop_submitter` (`submitted_by`),
  CONSTRAINT `fk_prop_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prop_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proposal_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pv_proposal` (`proposal_id`),
  CONSTRAINT `fk_pv_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proposal_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `action` enum('submitted','reviewed','approved','returned','rejected') NOT NULL,
  `remarks` text DEFAULT NULL,
  `performed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pa_proposal` (`proposal_id`),
  CONSTRAINT `fk_pa_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DOCUMENTS
-- ============================================================

CREATE TABLE `document_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_doc_category` (`category_id`),
  KEY `fk_doc_uploader` (`uploaded_by`),
  CONSTRAINT `fk_doc_category` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`),
  CONSTRAINT `fk_doc_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- REPORTS
-- ============================================================

CREATE TABLE `accomplishment_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_number` (`report_number`),
  KEY `fk_ar_project` (`project_id`),
  KEY `fk_ar_submitter` (`submitted_by`),
  CONSTRAINT `fk_ar_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ar_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `report_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `action` enum('submitted','reviewed','approved','returned') NOT NULL,
  `remarks` text DEFAULT NULL,
  `performed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ra_report` (`report_id`),
  CONSTRAINT `fk_ra_report` FOREIGN KEY (`report_id`) REFERENCES `accomplishment_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MOA
-- ============================================================

CREATE TABLE `moas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `moa_number` (`moa_number`),
  KEY `fk_moa_project` (`project_id`),
  KEY `fk_moa_partner` (`partner_agency_id`),
  CONSTRAINT `fk_moa_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_moa_partner` FOREIGN KEY (`partner_agency_id`) REFERENCES `partner_agencies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CERTIFICATES
-- ============================================================

CREATE TABLE `certificate_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('participation','completion','recognition','appreciation') NOT NULL,
  `template_path` varchar(255) DEFAULT NULL,
  `fields` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `fk_cert_template` (`template_id`),
  KEY `fk_cert_activity` (`activity_id`),
  CONSTRAINT `fk_cert_template` FOREIGN KEY (`template_id`) REFERENCES `certificate_templates` (`id`),
  CONSTRAINT `fk_cert_activity` FOREIGN KEY (`activity_id`) REFERENCES `extension_activities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SYSTEM
-- ============================================================

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `is_override` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Departments
INSERT INTO `departments` (`name`, `code`) VALUES
('College of Agriculture', 'CA'),
('College of Engineering', 'CE'),
('College of Education', 'CED'),
('College of Arts and Sciences', 'CAS'),
('College of Business and Accountancy', 'CBA'),
('College of Computing and Information Sciences', 'CCIS'),
('College of Veterinary Medicine', 'CVM'),
('College of Nursing', 'CN'),
('Extension Training Services', 'ETS');

-- Users (password for all: password123)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'admin'),
('faculty1', 'faculty1@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty'),
('faculty2', 'faculty2@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty'),
('viewer', 'viewer@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'viewer');

-- Faculty Profiles
INSERT INTO `faculty_profiles` (`user_id`, `employee_id`, `first_name`, `last_name`, `middle_name`, `department_id`, `position`, `is_extensionist`) VALUES
(1, 'EMP-001', 'Admin', 'User', '', 9, 'System Administrator', 0),
(2, 'EMP-002', 'Juan', 'Dela Cruz', 'Reyes', 1, 'Instructor III', 1),
(3, 'EMP-003', 'Maria', 'Santos', 'Garcia', 6, 'Assistant Professor II', 1),
(4, 'EMP-004', 'Viewer', 'Official', '', 9, 'Campus Director', 0);

-- Funding Sources
INSERT INTO `funding_sources` (`name`, `description`) VALUES
('University Fund', 'Internal university funding'),
('CHED', 'Commission on Higher Education'),
('DOST', 'Department of Science and Technology'),
('DA', 'Department of Agriculture'),
('LGU', 'Local Government Unit'),
('External Grant', 'External grants and donations');

-- Activity Types
INSERT INTO `activity_types` (`name`) VALUES
('Training'),
('Seminar'),
('Workshop'),
('Orientation'),
('Monitoring'),
('Evaluation'),
('Turnover'),
('Meeting');

-- Document Categories
INSERT INTO `document_categories` (`name`, `description`) VALUES
('Proposals', 'Project proposals'),
('MOA', 'Memorandum of Agreement'),
('Accomplishment Reports', 'Quarterly and annual reports'),
('Narrative Reports', 'Activity narrative reports'),
('Terminal Reports', 'Project completion reports'),
('Attendance Sheets', 'Participant attendance records'),
('Certificates', 'Generated certificates'),
('Photos', 'Activity documentation photos'),
('Evaluation Forms', 'Post-activity evaluations'),
('Letters', 'Official correspondence'),
('Supporting Documents', 'Other supporting files');

-- Certificate Templates
INSERT INTO `certificate_templates` (`name`, `type`, `fields`) VALUES
('Certificate of Participation', 'participation', '["recipient_name","activity_title","date","venue"]'),
('Certificate of Completion', 'completion', '["recipient_name","program_title","date"]'),
('Certificate of Recognition', 'recognition', '["recipient_name","achievement","date"]'),
('Certificate of Appreciation', 'appreciation', '["recipient_name","contribution","date"]');

-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('school_name', 'Isabela State University - Cauayan', 'general'),
('school_address', 'Cauayan, Isabela, Philippines', 'general'),
('system_name', 'ETS Project Management Hub', 'general'),
('academic_year', '2025-2026', 'general'),
('contact_email', 'ets@isu-cauayan.edu.ph', 'general'),
('contact_phone', '', 'general');

-- Partner Agencies
INSERT INTO `partner_agencies` (`name`, `agency_type`, `contact_person`, `contact_number`) VALUES
('Cauayan City Agriculture Office', 'government', 'Engr. Pedro Santos', '09171234567'),
('Department of Agriculture - Isabela', 'government', 'Dr. Maria Reyes', '09181234567'),
('Isabela Provincial Health Office', 'government', 'Dr. Jose Garcia', '09191234567');

-- Beneficiary Groups
INSERT INTO `beneficiary_groups` (`name`, `group_type`, `barangay`, `municipality`, `province`) VALUES
('San Isidro Farmers Association', 'organization', 'San Isidro', 'Cauayan', 'Isabela'),
('District I Senior Citizens', 'organization', 'District I', 'Cauayan', 'Isabela'),
('Cauayan Youth Organization', 'organization', 'Poblacion', 'Cauayan', 'Isabela');

-- Sample Program
INSERT INTO `programs` (`program_code`, `title`, `description`, `college`, `campus`, `status`, `objectives`, `created_by`) VALUES
('PROG-2026-0001', 'Community Development Extension Program', 'A comprehensive community development program for Cauayan City', 'College of Agriculture', 'Cauayan Campus', 'active', 'To improve the quality of life of community members through extension services', 1);

-- Sample Project
INSERT INTO `projects` (`project_code`, `program_id`, `title`, `description`, `start_date`, `end_date`, `budget`, `funding_source_id`, `partner_agency_id`, `status`, `created_by`) VALUES
('PROJ-2026-0001', 1, 'Organic Farming Technology Transfer', 'Training program for organic farming techniques', '2026-01-15', '2026-12-31', 250000.00, 1, 1, 'ongoing', 1);

-- Sample Component
INSERT INTO `components` (`component_code`, `project_id`, `title`, `description`, `start_date`, `end_date`, `status`, `created_by`) VALUES
('COMP-2026-0001', 1, 'Farmer Training Component', 'Series of training sessions for local farmers', '2026-02-01', '2026-06-30', 'in_progress', 1);

-- Sample Activity
INSERT INTO `extension_activities` (`activity_code`, `component_id`, `activity_type_id`, `title`, `description`, `venue`, `start_datetime`, `end_datetime`, `target_participants`, `budget_allocation`, `status`, `created_by`) VALUES
('ACT-2026-0001', 1, 1, 'Organic Fertilizer Production Training', 'Hands-on training on producing organic fertilizers', 'Brgy. San Isidro Hall, Cauayan City', '2026-03-15 08:00:00', '2026-03-17 17:00:00', 50, 35000.00, 'completed', 1);

-- Assignments
INSERT INTO `program_assignments` (`program_id`, `faculty_id`, `assignment_type`, `assigned_by`) VALUES
(1, 2, 'leader', 1);

INSERT INTO `project_assignments` (`project_id`, `faculty_id`, `assignment_type`, `assigned_by`) VALUES
(1, 2, 'leader', 1);

INSERT INTO `component_assignments` (`component_id`, `faculty_id`, `assignment_type`, `assigned_by`) VALUES
(1, 3, 'leader', 1);

INSERT INTO `activity_assignments` (`activity_id`, `faculty_id`, `assignment_type`, `assigned_by`) VALUES
(1, 2, 'leader', 1),
(1, 3, 'member', 1);

-- Sample Participants
INSERT INTO `activity_participants` (`activity_id`, `name`, `age`, `gender`, `barangay`, `municipality`, `province`, `organization`, `occupation`, `attendance_status`, `certificate_status`) VALUES
(1, 'Pedro Santos', 45, 'male', 'San Isidro', 'Cauayan', 'Isabela', 'San Isidro Farmers Association', 'Farmer', 'present', 'issued'),
(1, 'Maria Reyes', 38, 'female', 'San Isidro', 'Cauayan', 'Isabela', 'San Isidro Farmers Association', 'Farmer', 'present', 'issued'),
(1, 'Juan Cruz', 52, 'male', 'San Isidro', 'Cauayan', 'Isabela', '', 'Farmer', 'present', 'not_issued');

-- Sample Proposal
INSERT INTO `proposals` (`proposal_number`, `project_id`, `title`, `submitted_by`, `date_submitted`, `status`) VALUES
('PROP-2026-0001', 1, 'Proposal for Organic Farming Technology Transfer', 2, '2026-01-10', 'approved');

-- Notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `action_url`) VALUES
(2, 'Project Approved', 'Your project "Organic Farming Technology Transfer" has been approved.', 'success', '/index.php?module=projects&action=view&id=1'),
(1, 'New Proposal Submitted', 'A new proposal has been submitted for review.', 'info', '/index.php?module=proposals');

-- Audit Logs
INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`) VALUES
(1, 'create', 'programs', 1, 'Created program: Community Development Extension Program', '127.0.0.1'),
(1, 'create', 'projects', 1, 'Created project: Organic Farming Technology Transfer', '127.0.0.1'),
(1, 'approve', 'proposals', 1, 'Approved proposal for Organic Farming Technology Transfer', '127.0.0.1');

COMMIT;
