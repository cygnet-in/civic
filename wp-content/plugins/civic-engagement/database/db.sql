-- =========================================================
-- wp_civic_contacts
-- =========================================================

CREATE TABLE `wp_civic_contacts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NOT NULL,
    `latest_name` VARCHAR(255) NULL,
    `latest_phone` VARCHAR(50) NULL,
    `latest_whatsapp` VARCHAR(50) NULL,
    `latest_address` TEXT NULL,
    `latest_eircode` VARCHAR(50) NULL,
    `latest_electoral_area` VARCHAR(255) NULL,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_activities
-- =========================================================

CREATE TABLE `wp_civic_activities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id` BIGINT UNSIGNED NOT NULL,

    `activity_type` ENUM(
        'rep',
        'thread_response',
        'event_registration',
        'schedule',
        'manual'
    ) NOT NULL,

    `related_id` BIGINT UNSIGNED NULL,
    `summary` VARCHAR(500) NULL,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_contact_id` (`contact_id`),
    KEY `idx_activity_type` (`activity_type`),
    KEY `idx_contact_created` (`contact_id`, `created_at`),
    KEY `idx_type_created` (`activity_type`, `created_at`),

    CONSTRAINT `fk_activity_contact`
        FOREIGN KEY (`contact_id`)
        REFERENCES `wp_civic_contacts` (`id`)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_reps
-- =========================================================

CREATE TABLE `wp_civic_reps` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id` BIGINT UNSIGNED NOT NULL,

    `name_snapshot` VARCHAR(255) NOT NULL,
    `email_snapshot` VARCHAR(191) NOT NULL,
    `phone_snapshot` VARCHAR(50) NULL,
    `whatsapp_snapshot` VARCHAR(50) NULL,
    `address_snapshot` TEXT NULL,
    `eircode_snapshot` VARCHAR(50) NULL,
    `electoral_area_snapshot` VARCHAR(255) NULL,

    `title` VARCHAR(255) NOT NULL,
    `details` LONGTEXT NULL,

    `map_lat` DECIMAL(10,7) NULL,
    `map_lng` DECIMAL(10,7) NULL,

    `status` VARCHAR(50) NOT NULL DEFAULT 'submitted',

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_contact_id` (`contact_id`),
    KEY `idx_status_created` (`status`, `created_at`),

    CONSTRAINT `fk_reps_contact`
        FOREIGN KEY (`contact_id`)
        REFERENCES `wp_civic_contacts` (`id`)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_threads
-- =========================================================

CREATE TABLE `wp_civic_threads` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `title` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,

    `is_public` TINYINT(1) NOT NULL DEFAULT 0,

    `created_by` BIGINT UNSIGNED NULL,

    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,

    `status` VARCHAR(50) NOT NULL DEFAULT 'active',

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_status_dates` (`status`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_thread_fields
-- =========================================================

CREATE TABLE `wp_civic_thread_fields` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `thread_id` BIGINT UNSIGNED NOT NULL,

    `field_label` VARCHAR(255) NOT NULL,

    `field_type` ENUM(
        'text',
        'textarea',
        'dropdown',
        'radio',
        'checkbox'
    ) NOT NULL,

    `field_options` JSON NULL,

    `sort_order` INT NOT NULL DEFAULT 0,

    `is_required` TINYINT(1) NOT NULL DEFAULT 0,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_thread_id` (`thread_id`),
    KEY `idx_sort_order` (`sort_order`),

    CONSTRAINT `fk_thread_fields_thread`
        FOREIGN KEY (`thread_id`)
        REFERENCES `wp_civic_threads` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_thread_responses
-- =========================================================

CREATE TABLE `wp_civic_thread_responses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `thread_id` BIGINT UNSIGNED NOT NULL,
    `contact_id` BIGINT UNSIGNED NOT NULL,

    `name_snapshot` VARCHAR(255) NOT NULL,
    `email_snapshot` VARCHAR(191) NOT NULL,
    `phone_snapshot` VARCHAR(50) NULL,
    `address_snapshot` TEXT NULL,
    `eircode_snapshot` VARCHAR(50) NULL,
    `electoral_area_snapshot` VARCHAR(255) NULL,

    `response_data` JSON NOT NULL,

    `is_public` TINYINT(1) NOT NULL DEFAULT 0,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_contact_id` (`contact_id`),
    KEY `idx_thread_id` (`thread_id`),

    CONSTRAINT `fk_thread_response_contact`
        FOREIGN KEY (`contact_id`)
        REFERENCES `wp_civic_contacts` (`id`)
        ON DELETE RESTRICT,

    CONSTRAINT `fk_thread_response_thread`
        FOREIGN KEY (`thread_id`)
        REFERENCES `wp_civic_threads` (`id`)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_events
-- =========================================================

CREATE TABLE `wp_civic_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `title` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,

    `location` VARCHAR(500) NULL,

    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,

    `is_public` TINYINT(1) NOT NULL DEFAULT 0,

    `status` VARCHAR(50) NOT NULL DEFAULT 'active',

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_event_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_event_fields
-- =========================================================

CREATE TABLE `wp_civic_event_fields` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `event_id` BIGINT UNSIGNED NOT NULL,

    `field_label` VARCHAR(255) NOT NULL,

    `field_type` ENUM(
        'text',
        'textarea',
        'dropdown',
        'radio',
        'checkbox'
    ) NOT NULL,

    `field_options` JSON NULL,

    `sort_order` INT NOT NULL DEFAULT 0,

    `is_required` TINYINT(1) NOT NULL DEFAULT 0,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_event_id` (`event_id`),

    CONSTRAINT `fk_event_fields_event`
        FOREIGN KEY (`event_id`)
        REFERENCES `wp_civic_events` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_event_registrations
-- =========================================================

CREATE TABLE `wp_civic_event_registrations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `event_id` BIGINT UNSIGNED NOT NULL,
    `contact_id` BIGINT UNSIGNED NOT NULL,

    `name_snapshot` VARCHAR(255) NOT NULL,
    `email_snapshot` VARCHAR(191) NOT NULL,
    `phone_snapshot` VARCHAR(50) NULL,
    `address_snapshot` TEXT NULL,
    `eircode_snapshot` VARCHAR(50) NULL,
    `electoral_area_snapshot` VARCHAR(255) NULL,

    `registration_data` JSON NOT NULL,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_contact_id` (`contact_id`),
    KEY `idx_event_id` (`event_id`),

    CONSTRAINT `fk_event_registration_contact`
        FOREIGN KEY (`contact_id`)
        REFERENCES `wp_civic_contacts` (`id`)
        ON DELETE RESTRICT,

    CONSTRAINT `fk_event_registration_event`
        FOREIGN KEY (`event_id`)
        REFERENCES `wp_civic_events` (`id`)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =========================================================
-- wp_civic_schedules
-- =========================================================

CREATE TABLE `wp_civic_schedules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `type` VARCHAR(100) NOT NULL,

    `title` VARCHAR(255) NOT NULL,
    `details` LONGTEXT NULL,

    `reported_by` VARCHAR(255) NULL,

    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',

    `review_date` DATETIME NULL,

    `internal_comment` LONGTEXT NULL,
    `response` LONGTEXT NULL,

    `is_public` TINYINT(1) NOT NULL DEFAULT 0,

    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,

    `source_type` VARCHAR(50) NULL,
    `source_id` BIGINT UNSIGNED NULL,

    `created_by` BIGINT UNSIGNED NULL,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_start_date` (`start_date`),
    KEY `idx_is_public` (`is_public`),
    KEY `idx_public_start` (`is_public`, `start_date`),
    KEY `idx_status_review` (`status`, `review_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;