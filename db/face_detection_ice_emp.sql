-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2026 at 05:28 PM
-- Server version: 10.4.14-MariaDB
-- PHP Version: 7.4.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `face_detection_emp_temp`
--

-- --------------------------------------------------------

--
-- Table structure for table `application_settings`
--

CREATE TABLE `application_settings` (
  `id` int(11) NOT NULL,
  `setting_group` varchar(80) NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `application_settings`
--

INSERT INTO `application_settings` (`id`, `setting_group`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'attendance_rules', 'office_start_time', '09:00', '2026-04-19 07:53:35', '2026-04-19 07:55:55'),
(2, 'attendance_rules', 'grace_minutes', '15', '2026-04-19 07:53:35', '2026-04-19 07:53:35'),
(3, 'attendance_rules', 'minimum_work_hours', '08:00:00', '2026-04-19 07:53:35', '2026-04-19 07:53:35'),
(4, 'attendance_rules', 'half_day_hours', '04:00:00', '2026-04-19 07:53:35', '2026-04-19 07:53:35'),
(5, 'attendance_rules', 'standard_duty_hours', '08:00:00', '2026-04-19 07:53:35', '2026-04-19 07:55:55'),
(6, 'attendance_rules', 'max_break_minutes', '45', '2026-04-19 07:53:35', '2026-04-19 07:55:55'),
(7, 'attendance_rules', 'break_alert_minutes', '30', '2026-04-19 07:53:35', '2026-04-19 07:53:35'),
(8, 'attendance_rules', 'overtime_enabled', '1', '2026-04-19 07:53:35', '2026-04-19 07:53:35'),
(22, 'attendance_rules', 'break_time', '30', '2026-04-25 13:49:14', '2026-04-25 13:49:14');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','half_day','on_leave') NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `break_start_time` time DEFAULT NULL,
  `break_end_time` time DEFAULT NULL,
  `break_seconds` int(11) NOT NULL DEFAULT 0,
  `break_deduction_seconds` int(11) NOT NULL DEFAULT 0,
  `total_work_seconds` int(11) NOT NULL DEFAULT 0,
  `overtime_seconds` int(11) NOT NULL DEFAULT 0,
  `shift_name` varchar(80) NOT NULL DEFAULT 'General',
  `remarks` varchar(255) DEFAULT NULL,
  `overtime_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `completed_work_logs`
--

CREATE TABLE `completed_work_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_definition_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `source_type` varchar(60) NOT NULL,
  `source_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `template_name` varchar(160) NOT NULL,
  `image_file` varchar(255) DEFAULT NULL,
  `show_image_file` varchar(255) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `action_type` varchar(40) NOT NULL DEFAULT 'save',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Operations', 'OPS', 'active', '2026-04-19 08:21:23', '2026-04-19 08:21:23', NULL),
(2, 'Creative', 'CRTV', 'active', '2026-04-19 08:21:23', '2026-04-19 08:21:23', NULL),
(3, 'Media', 'MEDIA', 'active', '2026-04-19 08:21:23', '2026-04-19 08:21:23', NULL),
(4, 'Marketing', 'MKT', 'active', '2026-04-19 08:21:23', '2026-04-19 08:21:23', NULL),
(5, 'HR', 'HR', 'active', '2026-04-19 08:21:23', '2026-04-19 08:21:23', NULL),
(6, 'Accounts', 'ACC', 'active', '2026-04-19 08:21:23', '2026-04-19 08:21:23', NULL),
(7, 'Digital Marketing Head', 'DMH', 'active', '2026-04-19 08:23:37', '2026-04-19 08:23:37', NULL),
(8, 'video editor', 'VE', 'active', '2026-04-20 14:28:03', '2026-04-20 14:28:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_awards`
--

CREATE TABLE `employee_awards` (
  `id` int(11) NOT NULL,
  `award_type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `award_year` int(11) NOT NULL,
  `award_month` tinyint(4) DEFAULT NULL,
  `display_mode` enum('auto','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `user_id` int(11) DEFAULT NULL,
  `award_title` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `workflow_id` int(11) DEFAULT NULL,
  `current_stage_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `client_name` varchar(180) DEFAULT NULL,
  `task_type` varchar(120) DEFAULT NULL,
  `task_date` date DEFAULT NULL,
  `task_time` time DEFAULT NULL,
  `task_location` varchar(180) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `priority` varchar(40) NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','review','completed') NOT NULL DEFAULT 'pending',
  `deadline` date NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `workflow_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`workflow_json`)),
  `current_step_index` int(11) NOT NULL DEFAULT 0,
  `current_department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `task_logs`
--

CREATE TABLE `task_logs` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `template_workflows`
--

CREATE TABLE `template_workflows` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `template_name` varchar(160) NOT NULL,
  `image_file` varchar(255) DEFAULT NULL,
  `show_image_file` varchar(255) DEFAULT NULL,
  `status` enum('photo_pending','content_pending','verification_pending','verified','rejected') NOT NULL DEFAULT 'photo_pending',
  `photo_user_id` int(11) DEFAULT NULL,
  `content_user_id` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `photo_completed_at` datetime DEFAULT NULL,
  `content_completed_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `time_tracking_sessions`
--

CREATE TABLE `time_tracking_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `status` enum('working','break','offline') NOT NULL DEFAULT 'offline',
  `punch_in_time` datetime DEFAULT NULL,
  `punch_out_time` datetime DEFAULT NULL,
  `total_work_seconds` int(11) NOT NULL DEFAULT 0,
  `total_break_seconds` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','employee') NOT NULL,
  `department` varchar(120) DEFAULT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `shift_name` varchar(120) DEFAULT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `employee_code` varchar(40) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `face_reference_image` varchar(255) DEFAULT NULL,
  `face_reference_thumbnail` varchar(255) DEFAULT NULL,
  `face_reference_descriptor` longtext DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `profile_thumbnail` varchar(255) DEFAULT NULL,
  `profile_image_path` varchar(255) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `workflows`
--

CREATE TABLE `workflows` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `workflows`
--

INSERT INTO `workflows` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Campaign Production', 'Creative assets move from capture to editing to campaign release.', '2026-04-16 13:49:15'),
(2, 'Content Publication', 'Editorial work routed from preparation to final publication.', '2026-04-16 13:49:15');

-- --------------------------------------------------------

--
-- Table structure for table `workflow_stages`
--

CREATE TABLE `workflow_stages` (
  `id` int(11) NOT NULL,
  `workflow_id` int(11) NOT NULL,
  `role` enum('admin','manager','employee') NOT NULL,
  `stage_name` varchar(150) NOT NULL,
  `stage_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `workflow_stages`
--

INSERT INTO `workflow_stages` (`id`, `workflow_id`, `role`, `stage_name`, `stage_order`, `created_at`) VALUES
(1, 1, 'employee', 'Camera', 1, '2026-04-16 13:49:15'),
(2, 1, 'employee', 'Editor', 2, '2026-04-16 13:49:15'),
(3, 1, 'manager', 'Marketing', 3, '2026-04-16 13:49:15'),
(4, 2, 'employee', 'Draft', 1, '2026-04-16 13:49:15'),
(5, 2, 'manager', 'Review', 2, '2026-04-16 13:49:15'),
(6, 2, 'admin', 'Publish', 3, '2026-04-16 13:49:15');

-- --------------------------------------------------------

--
-- Table structure for table `work_definitions`
--

CREATE TABLE `work_definitions` (
  `id` int(11) NOT NULL,
  `work_key` varchar(80) NOT NULL,
  `work_name` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `completion_points` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `allowed_user_ids` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `work_definitions`
--

INSERT INTO `work_definitions` (`id`, `work_key`, `work_name`, `description`, `completion_points`, `is_active`, `created_by`, `created_at`, `updated_at`, `allowed_user_ids`) VALUES
(1, 'template_save', 'Template Save', 'Template name with main image and show image saved from backend.', 10, 1, 1, '2026-05-16 10:08:33', '2026-05-16 10:08:33', NULL),
(2, 'template_photo_upload', 'website photo update', 'Stage 1: template name, template image, and show image upload.', 10, 1, 1, '2026-05-16 10:47:27', '2026-06-03 15:35:35', NULL),
(3, 'template_content_entry', 'website content writing', 'Stage 2: product title, description, SEO, and tag entry.', 40, 1, 1, '2026-05-16 10:47:27', '2026-06-03 16:06:30', NULL),
(4, 'template_verification', 'templete varification', 'Stage 3: admin/manager verification before product goes live.', 5, 1, 1, '2026-05-16 10:47:27', '2026-06-03 16:10:35', NULL),
(5, 'template_mapping', 'Template Mappings', 'Stage 4: Mapping template to category/subcategory.', 6, 1, NULL, '2026-06-03 16:18:45', '2026-06-03 16:19:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `work_roles`
--

CREATE TABLE `work_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `work_roles`
--

INSERT INTO `work_roles` (`id`, `name`, `slug`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Video Editor', 'video-editor', '', 'active', '2026-05-19 19:40:25', '2026-06-08 14:33:28');

-- --------------------------------------------------------

--
-- Table structure for table `work_role_permissions`
--

CREATE TABLE `work_role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_key` varchar(160) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `work_role_permissions`
--

INSERT INTO `work_role_permissions` (`id`, `role_id`, `permission_key`, `created_at`) VALUES
(19, 1, 'backend.dashboard', '2026-06-08 14:33:28'),
(20, 1, 'backend.contact', '2026-06-08 14:33:28'),
(21, 1, 'website.language', '2026-06-08 14:33:28'),
(22, 1, 'website.category', '2026-06-08 14:33:28'),
(23, 1, 'website.subcategory', '2026-06-08 14:33:28'),
(24, 1, 'cards.template', '2026-06-08 14:33:28'),
(25, 1, 'cards.product', '2026-06-08 14:33:28'),
(26, 1, 'cards.template_mapping', '2026-06-08 14:33:28'),
(27, 1, 'analytics.internal', '2026-06-08 14:33:28'),
(28, 1, 'analytics.search', '2026-06-08 14:33:28'),
(29, 1, 'work.definitions', '2026-06-08 14:33:28'),
(30, 1, 'work.tasks', '2026-06-08 14:33:28'),
(31, 1, 'work.attendance', '2026-06-08 14:33:28'),
(32, 1, 'work.reports.performance', '2026-06-08 14:33:28'),
(33, 1, 'work.reports.tasks', '2026-06-08 14:33:28'),
(34, 1, 'work.reports.attendance', '2026-06-08 14:33:28'),
(35, 1, 'work.leaderboard', '2026-06-08 14:33:28'),
(36, 1, 'work.workflows', '2026-06-08 14:33:28'),
(37, 1, 'account.profile', '2026-06-08 14:33:28');

-- --------------------------------------------------------

--
-- Table structure for table `work_system_role_permissions`
--

CREATE TABLE `work_system_role_permissions` (
  `id` int(11) NOT NULL,
  `system_role` enum('admin','manager','employee') NOT NULL,
  `permission_key` varchar(160) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `work_system_role_permissions`
--

INSERT INTO `work_system_role_permissions` (`id`, `system_role`, `permission_key`, `created_at`) VALUES
(1, 'manager', 'backend.dashboard', '2026-05-20 12:48:31'),
(2, 'manager', 'website.language', '2026-05-20 12:48:31'),
(3, 'manager', 'website.category', '2026-05-20 12:48:31'),
(4, 'manager', 'website.subcategory', '2026-05-20 12:48:31'),
(5, 'manager', 'cards.template', '2026-05-20 12:48:31'),
(6, 'manager', 'cards.product', '2026-05-20 12:48:31'),
(7, 'manager', 'cards.template_mapping', '2026-05-20 12:48:31'),
(8, 'manager', 'analytics.internal', '2026-05-20 12:48:31'),
(9, 'manager', 'analytics.search', '2026-05-20 12:48:31'),
(10, 'manager', 'work.definitions', '2026-05-20 12:48:31'),
(11, 'manager', 'work.tasks', '2026-05-20 12:48:31'),
(12, 'manager', 'work.attendance', '2026-05-20 12:48:31'),
(13, 'manager', 'work.reports.performance', '2026-05-20 12:48:31'),
(14, 'manager', 'work.reports.tasks', '2026-05-20 12:48:31'),
(15, 'manager', 'work.reports.attendance', '2026-05-20 12:48:31'),
(16, 'manager', 'work.leaderboard', '2026-05-20 12:48:31'),
(17, 'manager', 'work.workflows', '2026-05-20 12:48:31'),
(18, 'manager', 'account.profile', '2026-05-20 12:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `work_user_roles`
--

CREATE TABLE `work_user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application_settings`
--
ALTER TABLE `application_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_setting_group_key` (`setting_group`,`setting_key`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_attendance_user_date` (`user_id`,`date`);

--
-- Indexes for table `completed_work_logs`
--
ALTER TABLE `completed_work_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_completed_work_user` (`user_id`),
  ADD KEY `idx_completed_work_source` (`source_type`,`source_id`),
  ADD KEY `idx_completed_work_definition` (`work_definition_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `employee_awards`
--
ALTER TABLE `employee_awards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_award_type_period` (`award_type`,`award_year`,`award_month`),
  ADD KEY `fk_employee_awards_user` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tasks_workflow` (`workflow_id`),
  ADD KEY `fk_tasks_stage` (`current_stage_id`),
  ADD KEY `fk_tasks_creator` (`created_by`),
  ADD KEY `fk_tasks_assignee` (`assigned_to`);

--
-- Indexes for table `task_logs`
--
ALTER TABLE `task_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_logs_task` (`task_id`),
  ADD KEY `idx_task_logs_department` (`department_id`),
  ADD KEY `idx_task_logs_user` (`user_id`);

--
-- Indexes for table `template_workflows`
--
ALTER TABLE `template_workflows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_template_workflow_template` (`template_id`),
  ADD KEY `idx_template_workflow_status` (`status`),
  ADD KEY `idx_template_workflow_product` (`product_id`);

--
-- Indexes for table `time_tracking_sessions`
--
ALTER TABLE `time_tracking_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_time_tracking_user_date` (`user_id`,`session_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `fk_users_manager` (`manager_id`);

--
-- Indexes for table `workflows`
--
ALTER TABLE `workflows`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workflow_stages`
--
ALTER TABLE `workflow_stages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_workflow_stages_workflow` (`workflow_id`);

--
-- Indexes for table `work_definitions`
--
ALTER TABLE `work_definitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `work_key` (`work_key`);

--
-- Indexes for table `work_roles`
--
ALTER TABLE `work_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `work_role_permissions`
--
ALTER TABLE `work_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_work_role_permission` (`role_id`,`permission_key`),
  ADD KEY `idx_work_role_permissions_key` (`permission_key`);

--
-- Indexes for table `work_system_role_permissions`
--
ALTER TABLE `work_system_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_work_system_role_permission` (`system_role`,`permission_key`),
  ADD KEY `idx_work_system_role_permissions_key` (`permission_key`);

--
-- Indexes for table `work_user_roles`
--
ALTER TABLE `work_user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_work_user_roles_user` (`user_id`),
  ADD KEY `idx_work_user_roles_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application_settings`
--
ALTER TABLE `application_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `completed_work_logs`
--
ALTER TABLE `completed_work_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employee_awards`
--
ALTER TABLE `employee_awards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `template_workflows`
--
ALTER TABLE `template_workflows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_tracking_sessions`
--
ALTER TABLE `time_tracking_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workflows`
--
ALTER TABLE `workflows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `workflow_stages`
--
ALTER TABLE `workflow_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `work_definitions`
--
ALTER TABLE `work_definitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `work_roles`
--
ALTER TABLE `work_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_role_permissions`
--
ALTER TABLE `work_role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `work_system_role_permissions`
--
ALTER TABLE `work_system_role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `work_user_roles`
--
ALTER TABLE `work_user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_awards`
--
ALTER TABLE `employee_awards`
  ADD CONSTRAINT `fk_employee_awards_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_tasks_stage` FOREIGN KEY (`current_stage_id`) REFERENCES `workflow_stages` (`id`),
  ADD CONSTRAINT `fk_tasks_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_tracking_sessions`
--
ALTER TABLE `time_tracking_sessions`
  ADD CONSTRAINT `fk_time_tracking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `workflow_stages`
--
ALTER TABLE `workflow_stages`
  ADD CONSTRAINT `fk_workflow_stages_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_role_permissions`
--
ALTER TABLE `work_role_permissions`
  ADD CONSTRAINT `fk_work_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `work_roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_user_roles`
--
ALTER TABLE `work_user_roles`
  ADD CONSTRAINT `fk_work_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `work_roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
