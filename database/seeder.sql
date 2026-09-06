-- ETS Hub Database Seeder
-- Run this after creating the schema
-- This will CLEAR all existing data and insert fresh seed data

USE `isu-proj-hub`;

-- ============================================================
-- CLEAR ALL EXISTING DATA
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE notifications;
TRUNCATE TABLE certificates;
TRUNCATE TABLE activity_evaluations;
TRUNCATE TABLE activity_outputs;
TRUNCATE TABLE activity_photos;
TRUNCATE TABLE activity_resource_speakers;
TRUNCATE TABLE activity_attendance;
TRUNCATE TABLE activity_participants;
TRUNCATE TABLE activity_assignments;
TRUNCATE TABLE extension_activities;
TRUNCATE TABLE component_assignments;
TRUNCATE TABLE components;
TRUNCATE TABLE project_assignments;
TRUNCATE TABLE projects;
TRUNCATE TABLE program_assignments;
TRUNCATE TABLE programs;
TRUNCATE TABLE accomplishment_reports;
TRUNCATE TABLE report_approvals;
TRUNCATE TABLE proposal_approvals;
TRUNCATE TABLE proposal_versions;
TRUNCATE TABLE proposals;
TRUNCATE TABLE documents;
TRUNCATE TABLE moas;
TRUNCATE TABLE faculty_profiles;
TRUNCATE TABLE users;
TRUNCATE TABLE departments;
TRUNCATE TABLE funding_sources;
TRUNCATE TABLE activity_types;
TRUNCATE TABLE document_categories;
TRUNCATE TABLE certificate_templates;
TRUNCATE TABLE partner_agencies;
TRUNCATE TABLE beneficiary_groups;
TRUNCATE TABLE settings;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- QUICK LOGIN ACCOUNTS
-- ============================================================
-- Username    Password       Role
-- --------    --------       ----
-- admin       password123    Administrator
-- etshead     password123    Administrator (ETS Head)
-- faculty1    password123    Faculty (Juan Dela Cruz)
-- faculty2    password123    Faculty (Maria Santos)
-- faculty3    password123    Faculty (Pedro Reyes)
-- faculty4    password123    Faculty (Ana Garcia)
-- viewer      password123    Viewer (College Dean)
-- ============================================================

-- ============================================================
-- DEPARTMENTS
-- ============================================================
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

-- ============================================================
-- USERS (password: password123)
-- ============================================================
INSERT INTO `users` (`username`, `email`, `password`, `role`, `is_active`) VALUES
('admin', 'admin@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'admin', 1),
('etshead', 'ets.head@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'admin', 1),
('faculty1', 'juan.delacruz@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty', 1),
('faculty2', 'maria.santos@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty', 1),
('faculty3', 'pedro.reyes@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty', 1),
('faculty4', 'ana.garcia@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'faculty', 1),
('viewer', 'dean@isu-cauayan.edu.ph', '$2y$12$9RyZgphPsFEARzNaZkT1o.vZBPubkt/10gwmH4n0MBp6rbm4KbVCu', 'viewer', 1);

-- ============================================================
-- FACULTY PROFILES
-- ============================================================
INSERT INTO `faculty_profiles` (`user_id`, `employee_id`, `first_name`, `last_name`, `middle_name`, `department_id`, `position`, `contact_number`, `email`, `specialization`, `is_extensionist`) VALUES
(1, 'EMP-001', 'System', 'Administrator', '', 9, 'System Administrator', '09171234567', 'admin@isu-cauayan.edu.ph', '', 0),
(2, 'EMP-002', 'Roberto', 'Mendoza', 'Santos', 9, 'ETS Head', '09171234568', 'ets.head@isu-cauayan.edu.ph', 'Extension Program Management', 1),
(3, 'EMP-003', 'Juan', 'Dela Cruz', 'Reyes', 1, 'Instructor III', '09171234569', 'juan.delacruz@isu-cauayan.edu.ph', 'Organic Farming, Crop Production', 1),
(4, 'EMP-004', 'Maria', 'Santos', 'Garcia', 6, 'Assistant Professor II', '09171234570', 'maria.santos@isu-cauayan.edu.ph', 'Information Technology, Digital Literacy', 1),
(5, 'EMP-005', 'Pedro', 'Reyes', 'Cruz', 1, 'Associate Professor I', '09171234571', 'pedro.reyes@isu-cauayan.edu.ph', 'Animal Science, Livestock', 1),
(6, 'EMP-006', 'Ana', 'Garcia', 'Lopez', 3, 'Instructor II', '09171234572', 'ana.garcia@isu-cauayan.edu.ph', 'Community Education, Literacy', 1),
(7, 'EMP-007', 'Dean', 'Official', '', 4, 'College Dean', '09171234573', 'dean@isu-cauayan.edu.ph', '', 0);

-- ============================================================
-- FUNDING SOURCES
-- ============================================================
INSERT INTO `funding_sources` (`name`, `description`) VALUES
('University Fund', 'Internal ISU funding'),
('CHED', 'Commission on Higher Education'),
('DOST', 'Department of Science and Technology'),
('DA', 'Department of Agriculture'),
('LGU', 'Local Government Unit - Cauayan City'),
('External Grant', 'External grants and donations'),
('GAA', 'General Appropriations Act');

-- ============================================================
-- ACTIVITY TYPES
-- ============================================================
INSERT INTO `activity_types` (`name`) VALUES
('Training'),
('Seminar'),
('Workshop'),
('Orientation'),
('Monitoring'),
('Evaluation'),
('Turnover'),
('Meeting'),
('Field Visit'),
('Technology Demonstration');

-- ============================================================
-- DOCUMENT CATEGORIES
-- ============================================================
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

-- ============================================================
-- CERTIFICATE TEMPLATES
-- ============================================================
INSERT INTO `certificate_templates` (`name`, `type`, `fields`) VALUES
('Certificate of Participation', 'participation', '["recipient_name","activity_title","date","venue"]'),
('Certificate of Completion', 'completion', '["recipient_name","program_title","date"]'),
('Certificate of Recognition', 'recognition', '["recipient_name","achievement","date"]'),
('Certificate of Appreciation', 'appreciation', '["recipient_name","contribution","date"]');

-- ============================================================
-- PARTNER AGENCIES
-- ============================================================
INSERT INTO `partner_agencies` (`name`, `agency_type`, `contact_person`, `contact_number`, `email`, `address`) VALUES
('Cauayan City Agriculture Office', 'government', 'Engr. Pedro Santos', '09171234580', 'agriculture@cauayan.gov.ph', 'Cauayan City Hall, Isabela'),
('Department of Agriculture - Isabela', 'government', 'Dr. Maria Reyes', '09171234581', 'da.isabela@gov.ph', 'Ilagan, Isabela'),
('Isabela Provincial Health Office', 'government', 'Dr. Jose Garcia', '09171234582', 'health@isabela.gov.ph', 'Ilagan, Isabela'),
('Cauayan City Health Office', 'government', 'Dr. Linda Cruz', '09171234583', 'health@cauayan.gov.ph', 'Cauayan City, Isabela'),
('Philippine Red Cross - Isabela Chapter', 'ngo', 'Roberto Bautista', '09171234584', 'redcross@isabela.ph', 'Ilagan, Isabela'),
('Isabela State University - Echague', 'academic', 'Dr. Carmen Rivera', '09171234585', 'echague@isu.edu.ph', 'Echague, Isabela'),
('Municipal Agriculture Office - Echague', 'government', 'Engr. Mark Tan', '09171234586', 'agri@echague.gov.ph', 'Echague, Isabela');

-- ============================================================
-- BENEFICIARY GROUPS
-- ============================================================
INSERT INTO `beneficiary_groups` (`name`, `group_type`, `barangay`, `municipality`, `province`, `contact_person`, `contact_number`) VALUES
('San Isidro Farmers Association', 'organization', 'San Isidro', 'Cauayan', 'Isabela', 'Pedro Santos', '09171234590'),
('District I Senior Citizens', 'organization', 'District I', 'Cauayan', 'Isabela', 'Lorna Garcia', '09171234591'),
('Cauayan Youth Organization', 'organization', 'Poblacion', 'Cauayan', 'Isabela', 'Mark Reyes', '09171234592'),
('Rizal Organic Farmers Cooperative', 'organization', 'Rizal', 'Cauayan', 'Isabela', 'Juan Ramos', '09171234593'),
('Villa Luna Women\'s Association', 'organization', 'Villa Luna', 'Cauayan', 'Isabela', 'Maria Bautista', '09171234594'),
('District II Parents Association', 'organization', 'District II', 'Cauayan', 'Isabela', 'Roberto Cruz', '09171234595'),
('Cauayan Corn Farmers Association', 'organization', 'Minante', 'Cauayan', 'Isabela', 'Antonio Mendoza', '09171234596'),
('Cauayan LGU', 'lgu', '', 'Cauayan', 'Isabela', 'Mayor Office', '09171234597');

-- ============================================================
-- SETTINGS
-- ============================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('school_name', 'Isabela State University - Cauayan', 'general'),
('school_address', 'Cauayan, Isabela, Philippines', 'general'),
('system_name', 'ETS Project Management Hub', 'general'),
('academic_year', '2025-2026', 'general'),
('contact_email', 'ets@isu-cauayan.edu.ph', 'general'),
('contact_phone', '+63 78 652 0000', 'general'),
('website', 'https://isu.edu.ph', 'general');

-- ============================================================
-- PROGRAMS
-- ============================================================
INSERT INTO `programs` (`program_code`, `title`, `description`, `college`, `campus`, `start_date`, `end_date`, `status`, `objectives`, `expected_outputs`, `budget_allocation`, `created_by`) VALUES
('PROG-2026-0001', 'Community Development and Livelihood Program', 'A comprehensive program aimed at improving community livelihoods through skills training and technology transfer.', 'College of Agriculture', 'Cauayan Campus', '2026-01-01', '2028-12-31', 'active', 'To improve the quality of life of community members through sustainable livelihood programs and skills development.', 'Trained community members, improved livelihood skills, sustainable income-generating projects', 500000.00, 1),
('PROG-2026-0002', 'Digital Literacy and ICT Skills Development', 'Program to enhance digital literacy among community members and faculty.', 'College of Computing and Information Sciences', 'Cauayan Campus', '2026-02-01', '2027-12-31', 'active', 'To bridge the digital divide by providing ICT skills training to community members.', 'Digitally literate community members, trained faculty, online learning resources', 300000.00, 1),
('PROG-2026-0003', 'Agricultural Technology Transfer Program', 'Transfer of modern agricultural technologies to local farmers.', 'College of Agriculture', 'Cauayan Campus', '2026-01-15', '2027-06-30', 'active', 'To increase agricultural productivity through adoption of modern farming technologies.', 'Adopted technologies, increased crop yields, trained farmers', 750000.00, 1);

-- ============================================================
-- PROGRAM ASSIGNMENTS
-- ============================================================
INSERT INTO `program_assignments` (`program_id`, `faculty_id`, `assignment_type`, `assigned_by`) VALUES
(1, 2, 'leader', 1),
(1, 3, 'member', 1),
(2, 4, 'leader', 1),
(3, 3, 'leader', 1),
(3, 5, 'member', 1);

-- ============================================================
-- PROJECTS
-- ============================================================
INSERT INTO `projects` (`project_code`, `program_id`, `title`, `description`, `start_date`, `end_date`, `budget`, `funding_source_id`, `partner_agency_id`, `beneficiary_group_id`, `location`, `objectives`, `expected_outputs`, `status`, `completion_percentage`, `created_by`) VALUES
('PROJ-2026-0001', 1, 'Organic Farming Technology Transfer', 'Training program for organic farming techniques for local farmers in Cauayan City.', '2026-01-15', '2026-12-31', 250000.00, 1, 1, 1, 'Brgy. San Isidro, Cauayan City', 'To equip farmers with sustainable organic farming skills and knowledge.', 'Trained farmers, organic farming manual, demonstration farm', 'ongoing', 45, 1),
('PROJ-2026-0002', 1, 'Livelihood Skills Training: Bread and Pastry Making', 'Hands-on training for out-of-school youth on bread and pastry production.', '2026-03-01', '2026-08-31', 120000.00, 1, NULL, 3, 'ISU-Cauayan Campus', 'To provide practical skills for self-employment.', 'Trained youth, business plans, startup kits', 'ongoing', 30, 1),
('PROJ-2026-0003', 2, 'Digital Literacy Program for Senior Citizens', 'Workshop series teaching basic computer skills to senior citizens.', '2026-02-01', '2026-07-31', 80000.00, 5, 4, 2, 'Cauayan City Hall', 'To improve digital literacy among elderly residents.', 'Digitally literate seniors, training materials', 'ongoing', 60, 1),
('PROJ-2026-0004', 3, 'Corn Production Technology Dissemination', 'Technology transfer for improved corn production techniques.', '2026-01-20', '2026-12-31', 350000.00, 4, 2, 7, 'Minante, Cauayan City', 'To increase corn yield through modern production techniques.', 'Improved farming practices, higher yields, trained farmers', 'ongoing', 25, 1),
('PROJ-2025-0005', 1, 'Community Health and Nutrition Awareness', 'Health education and nutrition counseling for community members.', '2025-06-01', '2025-12-31', 95000.00, 6, 3, NULL, 'District I, Cauayan City', 'To improve health awareness and nutrition practices.', 'Health awareness materials, trained health advocates', 'completed', 100, 1);

-- ============================================================
-- PROJECT ASSIGNMENTS
-- ============================================================
INSERT INTO `project_assignments` (`project_id`, `faculty_id`, `assignment_type`, `assigned_by`) VALUES
(1, 3, 'leader', 1),
(1, 5, 'member', 1),
(2, 3, 'leader', 1),
(3, 4, 'leader', 1),
(4, 5, 'leader', 1),
(4, 3, 'member', 1),
(5, 3, 'leader', 1),
(5, 6, 'member', 1);

-- ============================================================
-- COMPONENTS
-- ============================================================
INSERT INTO `components` (`component_code`, `project_id`, `title`, `description`, `start_date`, `end_date`, `status`, `expected_outputs`, `completion_percentage`, `created_by`) VALUES
('COMP-2026-0001', 1, 'Farmer Training Component', 'Series of training sessions for local farmers on organic farming.', '2026-02-01', '2026-06-30', 'in_progress', 'Trained farmers, training manuals, demonstration plots', 50, 1),
('COMP-2026-0002', 1, 'Technology Demonstration Component', 'Establishment of demonstration farms for organic techniques.', '2026-03-01', '2026-10-31', 'in_progress', 'Demo farms, documented results, farmer adoption', 30, 1),
('COMP-2026-0003', 2, 'Baking Skills Training', 'Hands-on bread and pastry making workshops.', '2026-03-15', '2026-07-31', 'in_progress', 'Skilled youth, recipe book, products', 40, 1),
('COMP-2026-0004', 3, 'Computer Basics Workshop', 'Basic computer operations and internet skills.', '2026-02-15', '2026-05-31', 'completed', 'Computer literate seniors, training guides', 100, 1),
('COMP-2026-0005', 4, 'Corn Production Training', 'Training on modern corn production techniques.', '2026-02-01', '2026-06-30', 'in_progress', 'Trained farmers, production guide', 20, 1);

-- ============================================================
-- COMPONENT ASSIGNMENTS
-- ============================================================
INSERT INTO `component_assignments` (`component_id`, `faculty_id`, `assignment_type`, `assigned_by`) VALUES
(1, 3, 'leader', 1),
(1, 5, 'member', 1),
(2, 3, 'leader', 1),
(3, 3, 'leader', 1),
(4, 4, 'leader', 1),
(5, 5, 'leader', 1);

-- ============================================================
-- EXTENSION ACTIVITIES
-- ============================================================
INSERT INTO `extension_activities` (`activity_code`, `component_id`, `activity_type_id`, `title`, `description`, `objectives`, `venue`, `start_datetime`, `end_datetime`, `target_participants`, `budget_allocation`, `status`, `created_by`) VALUES
('ACT-2026-0001', 1, 1, 'Organic Fertilizer Production Training', 'Hands-on training on producing organic fertilizers from local materials.', 'Teach farmers to produce organic fertilizer', 'Brgy. San Isidro Hall', '2026-03-15 08:00:00', '2026-03-17 17:00:00', 50, 35000.00, 'completed', 1),
('ACT-2026-0002', 1, 1, 'Pest Management in Organic Farming', 'Training on natural pest control methods.', 'Equip farmers with organic pest management skills', 'Brgy. San Isidro Hall', '2026-04-15 08:00:00', '2026-04-16 17:00:00', 45, 25000.00, 'completed', 1),
('ACT-2026-0003', 1, 10, 'Organic Farm Demonstration', 'On-site demonstration of organic farming techniques.', 'Show practical application of organic farming', 'Demo Farm, San Isidro', '2026-05-10 08:00:00', '2026-05-10 17:00:00', 40, 15000.00, 'completed', 1),
('ACT-2026-0004', 3, 3, 'Bread Making Workshop', 'Hands-on bread making workshop for youth.', 'Teach bread making skills', 'ISU-Cauayan Bakery Lab', '2026-04-01 08:00:00', '2026-04-03 17:00:00', 25, 20000.00, 'completed', 1),
('ACT-2026-0005', 3, 3, 'Pastry Decoration Workshop', 'Advanced pastry techniques and decoration.', 'Teach pastry decoration skills', 'ISU-Cauayan Bakery Lab', '2026-05-01 08:00:00', '2026-05-02 17:00:00', 25, 18000.00, 'completed', 1),
('ACT-2026-0006', 4, 3, 'Basic Computer Operations', 'Introduction to computers, typing, and file management.', 'Teach basic computer skills', 'ISU-Cauayan Computer Lab', '2026-03-01 09:00:00', '2026-03-05 16:00:00', 30, 15000.00, 'completed', 1),
('ACT-2026-0007', 4, 3, 'Internet and Email Workshop', 'Using internet, email, and online services.', 'Teach internet navigation', 'ISU-Cauayan Computer Lab', '2026-04-01 09:00:00', '2026-04-03 16:00:00', 30, 12000.00, 'completed', 1),
('ACT-2026-0008', 5, 1, 'Modern Corn Production Techniques', 'Training on improved corn varieties and production methods.', 'Introduce modern corn farming', 'Minante Agricultural Center', '2026-03-20 08:00:00', '2026-03-22 17:00:00', 60, 30000.00, 'completed', 1);

-- ============================================================
-- ACTIVITY ASSIGNMENTS
-- ============================================================
INSERT INTO `activity_assignments` (`activity_id`, `faculty_id`, `assignment_type`, `assigned_by`) VALUES
(1, 3, 'leader', 1), (1, 5, 'member', 1),
(2, 3, 'leader', 1), (2, 5, 'member', 1),
(3, 3, 'leader', 1), (3, 5, 'member', 1),
(4, 3, 'leader', 1),
(5, 3, 'leader', 1),
(6, 4, 'leader', 1),
(7, 4, 'leader', 1),
(8, 5, 'leader', 1), (8, 3, 'member', 1);

-- ============================================================
-- ACTIVITY PARTICIPANTS
-- ============================================================
INSERT INTO `activity_participants` (`activity_id`, `name`, `age`, `gender`, `barangay`, `municipality`, `province`, `organization`, `occupation`, `attendance_status`, `certificate_status`, `qr_code`) VALUES
(1, 'Pedro Santos', 45, 'male', 'San Isidro', 'Cauayan', 'Isabela', 'San Isidro Farmers Association', 'Farmer', 'present', 'issued', 'QR-FARM001'),
(1, 'Maria Reyes', 38, 'female', 'San Isidro', 'Cauayan', 'Isabela', 'San Isidro Farmers Association', 'Farmer', 'present', 'issued', 'QR-FARM002'),
(1, 'Juan Cruz', 52, 'male', 'San Isidro', 'Cauayan', 'Isabela', '', 'Farmer', 'present', 'issued', 'QR-FARM003'),
(1, 'Lorna Garcia', 41, 'female', 'San Isidro', 'Cauayan', 'Isabela', '', 'Farmer', 'present', 'issued', 'QR-FARM004'),
(1, 'Ricardo Bautista', 55, 'male', 'San Isidro', 'Cauayan', 'Isabela', '', 'Farmer', 'present', 'not_issued', 'QR-FARM005'),
(4, 'Ana Mendoza', 22, 'female', 'District I', 'Cauayan', 'Isabela', 'OSY Program', 'Student', 'present', 'issued', 'QR-BAKE001'),
(4, 'Mark Reyes', 20, 'male', 'District II', 'Cauayan', 'Isabela', '', 'Student', 'present', 'issued', 'QR-BAKE002'),
(4, 'Linda Garcia', 24, 'female', 'Poblacion', 'Cauayan', 'Isabela', '', 'Student', 'present', 'not_issued', 'QR-BAKE003'),
(6, 'Lorna Garcia', 65, 'female', 'District I', 'Cauayan', 'Isabela', 'Senior Citizens Association', 'Retired', 'present', 'issued', 'QR-DIGI001'),
(6, 'Ricardo Bautista', 70, 'male', 'District I', 'Cauayan', 'Isabela', 'Senior Citizens Association', 'Retired', 'present', 'issued', 'QR-DIGI002'),
(8, 'Antonio Mendoza', 48, 'male', 'Minante', 'Cauayan', 'Isabela', 'Corn Farmers Association', 'Farmer', 'present', 'issued', 'QR-CORN001'),
(8, 'Roberto Cruz', 39, 'male', 'Minante', 'Cauayan', 'Isabela', '', 'Farmer', 'present', 'issued', 'QR-CORN002');

-- ============================================================
-- ACTIVITY ATTENDANCE
-- ============================================================
INSERT INTO `activity_attendance` (`activity_id`, `participant_id`, `session_date`, `time_in`, `time_out`, `status`) VALUES
(1, 1, '2026-03-15', '08:00:00', '17:00:00', 'present'),
(1, 1, '2026-03-16', '08:00:00', '17:00:00', 'present'),
(1, 1, '2026-03-17', '08:00:00', '17:00:00', 'present'),
(1, 2, '2026-03-15', '08:00:00', '17:00:00', 'present'),
(1, 2, '2026-03-16', '08:00:00', '17:00:00', 'present'),
(1, 2, '2026-03-17', '08:15:00', '17:00:00', 'present'),
(1, 3, '2026-03-15', '08:00:00', '17:00:00', 'present'),
(1, 3, '2026-03-16', '08:00:00', '12:00:00', 'present'),
(1, 3, '2026-03-17', NULL, NULL, 'absent');

-- ============================================================
-- ACTIVITY RESOURCE SPEAKERS
-- ============================================================
INSERT INTO `activity_resource_speakers` (`activity_id`, `name`, `affiliation`, `topic`, `contact_number`, `email`) VALUES
(1, 'Dr. Elena Torres', 'DA-Isabela', 'Organic Fertilizer Production', '09171234600', 'torres@da.gov.ph'),
(1, 'Prof. Mark Santos', 'ISU-Echague', 'Composting Techniques', '09171234601', 'santos@isu.edu.ph'),
(2, 'Dr. Roberto Lim', 'ISU-Cauayan', 'Natural Pest Control', '09171234602', 'lim@isu.edu.ph'),
(6, 'Ms. Jennifer Lee', 'DepEd Isabela', 'Basic Computer Skills', '09171234603', 'lee@deped.gov.ph');

-- ============================================================
-- ACTIVITY EVALUATIONS
-- ============================================================
INSERT INTO `activity_evaluations` (`activity_id`, `participant_id`, `overall_rating`, `content_rating`, `speaker_rating`, `venue_rating`, `comments`) VALUES
(1, 1, 5, 5, 5, 4, 'Very informative training. Learned a lot about organic farming.'),
(1, 2, 4, 4, 5, 4, 'Good training, would like more hands-on practice.'),
(1, 3, 5, 5, 5, 5, 'Excellent! Will apply what I learned on my farm.'),
(4, 6, 5, 5, 5, 5, 'Great workshop! Now I can bake bread for my family.'),
(4, 7, 4, 4, 4, 4, 'Enjoyed the workshop. Want to learn more recipes.');

-- ============================================================
-- ACTIVITY OUTPUTS
-- ============================================================
INSERT INTO `activity_outputs` (`activity_id`, `title`, `description`, `output_type`, `created_by`) VALUES
(1, 'Organic Fertilizer Production Manual', 'Step-by-step guide for producing organic fertilizers.', 'material', 1),
(4, 'Bread Making Recipe Book', 'Collection of bread recipes taught during the workshop.', 'material', 1),
(6, 'Computer Basics Training Guide', 'Guide for basic computer operations.', 'material', 1);

-- ============================================================
-- PROPOSALS
-- ============================================================
INSERT INTO `proposals` (`proposal_number`, `project_id`, `title`, `submitted_by`, `date_submitted`, `status`, `remarks`, `version`) VALUES
('PROP-2026-0001', 1, 'Proposal for Organic Farming Technology Transfer', 3, '2026-01-10', 'approved', 'Approved by ETS. Well-structured proposal.', 1),
('PROP-2026-0002', 2, 'Proposal for Livelihood Skills Training', 3, '2026-02-15', 'approved', 'Approved with minor budget adjustments.', 1),
('PROP-2026-0003', 3, 'Proposal for Digital Literacy Program', 4, '2026-01-20', 'approved', 'Approved. Good community impact.', 1),
('PROP-2026-0004', 4, 'Proposal for Corn Production Technology', 5, '2026-01-25', 'approved', 'Approved. Strong partnership with DA.', 1),
('PROP-2026-0005', 1, 'Revised Proposal for Phase 2 Training', 3, '2026-06-01', 'submitted', '', 2);

-- ============================================================
-- PROPOSAL APPROVALS
-- ============================================================
INSERT INTO `proposal_approvals` (`proposal_id`, `approver_id`, `action`, `remarks`, `performed_at`) VALUES
(1, 1, 'approved', 'Approved by ETS. Well-structured proposal.', '2026-01-12 10:00:00'),
(2, 1, 'approved', 'Approved with minor budget adjustments.', '2026-02-17 14:00:00'),
(3, 1, 'approved', 'Approved. Good community impact.', '2026-01-22 09:00:00'),
(4, 1, 'approved', 'Approved. Strong partnership with DA.', '2026-01-27 11:00:00');

-- ============================================================
-- ACCOMPLISHMENT REPORTS
-- ============================================================
INSERT INTO `accomplishment_reports` (`report_number`, `report_type`, `period_quarter`, `period_year`, `project_id`, `title`, `summary`, `submitted_by`, `date_submitted`, `status`) VALUES
('AR-2026-0001', 'quarterly', 'Q1', 2026, 1, 'Q1 Accomplishment Report - Organic Farming', 'First quarter activities completed. 3 training sessions conducted with 45 participants.', 3, '2026-04-05', 'approved'),
('AR-2026-0002', 'quarterly', 'Q1', 2026, 3, 'Q1 Accomplishment Report - Digital Literacy', 'Computer basics workshop completed. 30 seniors trained.', 4, '2026-04-03', 'approved'),
('AR-2025-0003', 'terminal', NULL, 2025, 5, 'Terminal Report - Health and Nutrition', 'Project completed successfully. 200 community members reached.', 3, '2026-01-15', 'approved');

-- ============================================================
-- REPORT APPROVALS
-- ============================================================
INSERT INTO `report_approvals` (`report_id`, `approver_id`, `action`, `remarks`, `performed_at`) VALUES
(1, 1, 'approved', 'Good progress. Continue with Q2 activities.', '2026-04-08 10:00:00'),
(2, 1, 'approved', 'Excellent work with the seniors program.', '2026-04-05 14:00:00'),
(3, 1, 'approved', 'Terminal report accepted. Well documented.', '2026-01-18 09:00:00');

-- ============================================================
-- DOCUMENTS
-- ============================================================
INSERT INTO `documents` (`title`, `category_id`, `entity_type`, `entity_id`, `file_path`, `file_name`, `file_type`, `version`, `description`, `status`, `uploaded_by`) VALUES
('Organic Farming Proposal', 1, 'project', 1, 'documents/proposal_organic.pdf', 'proposal_organic.pdf', 'pdf', 1, 'Initial project proposal', 'approved', 3),
('MOA with City Agriculture', 2, 'project', 1, 'documents/moa_agriculture.pdf', 'moa_agriculture.pdf', 'pdf', 1, 'Memorandum of Agreement', 'approved', 1),
('Training Attendance Sheet', 6, 'activity', 1, 'documents/attendance_training.pdf', 'attendance_training.pdf', 'pdf', 1, 'Attendance for organic farming training', 'approved', 3),
('Q1 Report 2026', 3, 'project', 1, 'documents/q1_report.pdf', 'q1_report.pdf', 'pdf', 1, 'Quarterly accomplishment report', 'approved', 3);

-- ============================================================
-- MOAs
-- ============================================================
INSERT INTO `moas` (`moa_number`, `project_id`, `partner_agency_id`, `date_signed`, `expiration_date`, `status`, `remarks`, `created_by`) VALUES
('MOA-2026-0001', 1, 1, '2026-01-05', '2027-01-05', 'active', 'Partnership for organic farming project', 1),
('MOA-2026-0002', 3, 4, '2026-01-25', '2026-07-25', 'active', 'Venue partnership for digital literacy program', 1),
('MOA-2025-0003', 5, 3, '2025-05-20', '2025-11-20', 'expired', 'Health program partnership - expired', 1);

-- ============================================================
-- CERTIFICATES
-- ============================================================
INSERT INTO `certificates` (`certificate_number`, `template_id`, `activity_id`, `recipient_name`, `recipient_type`, `date_issued`, `qr_code`, `status`, `generated_by`) VALUES
('CERT-2026-0001', 1, 1, 'Pedro Santos', 'participant', '2026-03-17', 'CERT-QR001', 'issued', 1),
('CERT-2026-0002', 1, 1, 'Maria Reyes', 'participant', '2026-03-17', 'CERT-QR002', 'issued', 1),
('CERT-2026-0003', 1, 4, 'Ana Mendoza', 'participant', '2026-04-03', 'CERT-QR003', 'issued', 1),
('CERT-2026-0004', 1, 6, 'Lorna Garcia', 'participant', '2026-03-05', 'CERT-QR004', 'issued', 1),
('CERT-2026-0005', 4, 1, 'Dr. Elena Torres', 'resource_speaker', '2026-03-17', 'CERT-QR005', 'issued', 1);

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `action_url`) VALUES
(3, 'Proposal Approved', 'Your proposal for "Organic Farming Technology Transfer" has been approved.', 'success', '/index.php?module=proposals'),
(3, 'Report Approved', 'Your Q1 accomplishment report has been approved.', 'success', '/index.php?module=reports'),
(1, 'New Proposal Submitted', 'A new proposal "Revised Proposal for Phase 2 Training" has been submitted for review.', 'info', '/index.php?module=proposals'),
(4, 'Assignment', 'You have been assigned as Digital Literacy Program Leader.', 'info', '/index.php?module=programs'),
(5, 'Project Update', 'Corn Production Technology project is 25% complete.', 'info', '/index.php?module=projects');

-- ============================================================
-- AUDIT LOGS
-- ============================================================
INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `is_override`) VALUES
(1, 'create', 'program', 1, 'Created program: Community Development and Livelihood Program', '127.0.0.1', 0),
(1, 'create', 'project', 1, 'Created project: Organic Farming Technology Transfer', '127.0.0.1', 0),
(1, 'approve', 'proposal', 1, 'Approved proposal for Organic Farming Technology Transfer', '127.0.0.1', 0),
(1, 'approve', 'proposal', 2, 'Approved proposal for Livelihood Skills Training', '127.0.0.1', 0),
(1, 'approve', 'report', 1, 'Approved Q1 accomplishment report', '127.0.0.1', 0),
(3, 'create', 'proposal', 1, 'Submitted proposal: Organic Farming Technology Transfer', '127.0.0.1', 0),
(3, 'login', 'user', 3, 'User logged in', '127.0.0.1', 0),
(1, 'login', 'user', 1, 'User logged in', '127.0.0.1', 0);

-- ============================================================
-- ACTIVITY PHOTOS (metadata only - files must be uploaded separately)
-- ============================================================
INSERT INTO `activity_photos` (`activity_id`, `file_path`, `caption`, `uploaded_by`) VALUES
(1, 'activities/organic_training_1.jpg', 'Farmers learning organic fertilizer production', 1),
(1, 'activities/organic_training_2.jpg', 'Hands-on composting practice', 1),
(4, 'activities/bread_workshop_1.jpg', 'Youth learning bread making', 1),
(6, 'activities/computer_class_1.jpg', 'Seniors learning computer basics', 1),
(8, 'activities/corn_training_1.jpg', 'Modern corn production techniques', 1);
