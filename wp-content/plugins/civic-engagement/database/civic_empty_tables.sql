-- Civic Platform Empty Table Structure
-- Generated from current schema.
-- Creates empty custom civic tables with indexes, AUTO_INCREMENT, and foreign keys.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `wp_civic_thread_responses`;
DROP TABLE IF EXISTS `wp_civic_thread_fields`;
DROP TABLE IF EXISTS `wp_civic_threads`;
DROP TABLE IF EXISTS `wp_civic_schedule_notes`;
DROP TABLE IF EXISTS `wp_civic_schedules`;
DROP TABLE IF EXISTS `wp_civic_reps`;
DROP TABLE IF EXISTS `wp_civic_event_registrations`;
DROP TABLE IF EXISTS `wp_civic_event_fields`;
DROP TABLE IF EXISTS `wp_civic_events`;
DROP TABLE IF EXISTS `wp_civic_electoral_areas`;
DROP TABLE IF EXISTS `wp_civic_contacts`;
DROP TABLE IF EXISTS `wp_civic_activities`;

SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

--
-- Table structure for table `wp_civic_activities`
--

CREATE TABLE `wp_civic_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `activity_type` enum('rep','thread_response','event_registration','schedule','manual') NOT NULL,
  `related_id` bigint(20) UNSIGNED DEFAULT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_contacts`
--

CREATE TABLE `wp_civic_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(191) NOT NULL,
  `latest_name` varchar(255) DEFAULT NULL,
  `latest_phone` varchar(50) DEFAULT NULL,
  `latest_whatsapp` varchar(50) DEFAULT NULL,
  `latest_address` text DEFAULT NULL,
  `latest_eircode` varchar(50) DEFAULT NULL,
  `latest_electoral_area` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_electoral_areas`
--

CREATE TABLE `wp_civic_electoral_areas` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `wp_civic_events`
--

CREATE TABLE `wp_civic_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `summary` varchar(500) NOT NULL,
  `description` longtext DEFAULT NULL,
  `location` varchar(500) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `registration_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_event_fields`
--

CREATE TABLE `wp_civic_event_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_key` varchar(255) NOT NULL,
  `field_type` enum('text','textarea','dropdown','radio','checkbox') NOT NULL,
  `field_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_options`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_event_registrations`
--

CREATE TABLE `wp_civic_event_registrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `name_snapshot` varchar(255) NOT NULL,
  `email_snapshot` varchar(191) NOT NULL,
  `phone_snapshot` varchar(50) DEFAULT NULL,
  `address_snapshot` text DEFAULT NULL,
  `eircode_snapshot` varchar(50) DEFAULT NULL,
  `electoral_area_id` int(11) NOT NULL,
  `electoral_area_snapshot` varchar(255) DEFAULT NULL,
  `registration_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`registration_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_reps`
--

CREATE TABLE `wp_civic_reps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `name_snapshot` varchar(255) NOT NULL,
  `email_snapshot` varchar(191) NOT NULL,
  `phone_snapshot` varchar(50) DEFAULT NULL,
  `whatsapp_snapshot` varchar(50) DEFAULT NULL,
  `address_snapshot` text DEFAULT NULL,
  `eircode_snapshot` varchar(50) DEFAULT NULL,
  `electoral_area_id` int(11) NOT NULL,
  `electoral_area_snapshot` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `details` longtext DEFAULT NULL,
  `map_lat` decimal(10,7) DEFAULT NULL,
  `map_lng` decimal(10,7) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'submitted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_schedules`
--

CREATE TABLE `wp_civic_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('meeting','motion','question','rep_followup','public_announcement','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `details` longtext DEFAULT NULL,
  `status` enum('open','pending','scheduled','completed','cancelled') NOT NULL DEFAULT 'pending',
  `internal_comment` longtext DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(4) NOT NULL DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_schedule_notes`
--

CREATE TABLE `wp_civic_schedule_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `schedule_id` bigint(20) UNSIGNED NOT NULL,
  `note` text NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_threads`
--

CREATE TABLE `wp_civic_threads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `summary` varchar(500) NOT NULL,
  `description` longtext DEFAULT NULL,
  `response_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_thread_fields`
--

CREATE TABLE `wp_civic_thread_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_key` varchar(255) NOT NULL,
  `field_type` enum('text','textarea','dropdown','radio','checkbox') NOT NULL,
  `field_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_options`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `wp_civic_thread_responses`
--

CREATE TABLE `wp_civic_thread_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `name_snapshot` varchar(255) NOT NULL,
  `email_snapshot` varchar(191) NOT NULL,
  `phone_snapshot` varchar(50) DEFAULT NULL,
  `address_snapshot` text DEFAULT NULL,
  `eircode_snapshot` varchar(50) DEFAULT NULL,
  `electoral_area_id` int(11) NOT NULL,
  `electoral_area_snapshot` varchar(255) DEFAULT NULL,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`response_data`)),
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wp_civic_activities`
--
ALTER TABLE `wp_civic_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_id` (`contact_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_contact_created` (`contact_id`,`created_at`),
  ADD KEY `idx_type_created` (`activity_type`,`created_at`);

--
-- Indexes for table `wp_civic_contacts`
--
ALTER TABLE `wp_civic_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_email` (`email`);

--
-- Indexes for table `wp_civic_electoral_areas`
--
ALTER TABLE `wp_civic_electoral_areas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wp_civic_events`
--
ALTER TABLE `wp_civic_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_slug` (`slug`),
  ADD KEY `idx_event_status` (`status`);

--
-- Indexes for table `wp_civic_event_fields`
--
ALTER TABLE `wp_civic_event_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_field` (`event_id`,`field_key`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `wp_civic_event_registrations`
--
ALTER TABLE `wp_civic_event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_id` (`contact_id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `wp_civic_reps`
--
ALTER TABLE `wp_civic_reps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_id` (`contact_id`),
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Indexes for table `wp_civic_schedules`
--
ALTER TABLE `wp_civic_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_schedule_slug` (`slug`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_is_public` (`is_public`),
  ADD KEY `idx_public_start` (`is_public`,`start_date`),
  ADD KEY `idx_status_review` (`status`);

--
-- Indexes for table `wp_civic_schedule_notes`
--
ALTER TABLE `wp_civic_schedule_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `wp_civic_threads`
--
ALTER TABLE `wp_civic_threads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_thread_slug` (`slug`),
  ADD KEY `idx_status_dates` (`status`,`start_date`,`end_date`);

--
-- Indexes for table `wp_civic_thread_fields`
--
ALTER TABLE `wp_civic_thread_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_field` (`thread_id`,`field_key`),
  ADD KEY `idx_thread_id` (`thread_id`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `wp_civic_thread_responses`
--
ALTER TABLE `wp_civic_thread_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_id` (`contact_id`),
  ADD KEY `idx_thread_id` (`thread_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `wp_civic_activities`
--
ALTER TABLE `wp_civic_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_contacts`
--
ALTER TABLE `wp_civic_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_electoral_areas`
--
ALTER TABLE `wp_civic_electoral_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_events`
--
ALTER TABLE `wp_civic_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_event_fields`
--
ALTER TABLE `wp_civic_event_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_event_registrations`
--
ALTER TABLE `wp_civic_event_registrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_reps`
--
ALTER TABLE `wp_civic_reps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_schedules`
--
ALTER TABLE `wp_civic_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_schedule_notes`
--
ALTER TABLE `wp_civic_schedule_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_threads`
--
ALTER TABLE `wp_civic_threads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_thread_fields`
--
ALTER TABLE `wp_civic_thread_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_civic_thread_responses`
--
ALTER TABLE `wp_civic_thread_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `wp_civic_activities`
--
ALTER TABLE `wp_civic_activities`
  ADD CONSTRAINT `fk_activity_contact` FOREIGN KEY (`contact_id`) REFERENCES `wp_civic_contacts` (`id`);

--
-- Constraints for table `wp_civic_event_fields`
--
ALTER TABLE `wp_civic_event_fields`
  ADD CONSTRAINT `fk_event_fields_event` FOREIGN KEY (`event_id`) REFERENCES `wp_civic_events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_civic_event_registrations`
--
ALTER TABLE `wp_civic_event_registrations`
  ADD CONSTRAINT `fk_event_registration_contact` FOREIGN KEY (`contact_id`) REFERENCES `wp_civic_contacts` (`id`),
  ADD CONSTRAINT `fk_event_registration_event` FOREIGN KEY (`event_id`) REFERENCES `wp_civic_events` (`id`);

--
-- Constraints for table `wp_civic_reps`
--
ALTER TABLE `wp_civic_reps`
  ADD CONSTRAINT `fk_reps_contact` FOREIGN KEY (`contact_id`) REFERENCES `wp_civic_contacts` (`id`);

--
-- Constraints for table `wp_civic_thread_fields`
--
ALTER TABLE `wp_civic_thread_fields`
  ADD CONSTRAINT `fk_thread_fields_thread` FOREIGN KEY (`thread_id`) REFERENCES `wp_civic_threads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_civic_thread_responses`
--
ALTER TABLE `wp_civic_thread_responses`
  ADD CONSTRAINT `fk_thread_response_contact` FOREIGN KEY (`contact_id`) REFERENCES `wp_civic_contacts` (`id`),
  ADD CONSTRAINT `fk_thread_response_thread` FOREIGN KEY (`thread_id`) REFERENCES `wp_civic_threads` (`id`);


SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
