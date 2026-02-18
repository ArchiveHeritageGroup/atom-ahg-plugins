-- ============================================================
-- ahgResearchPlugin - Database Schema
-- Version 2.0.0 - Professional Research Support Platform
-- DO NOT include INSERT INTO atom_plugin
-- ============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- ============================================================
-- PHASE 1.2: RESEARCHER TYPES (Admin-Configurable)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_researcher_type` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `max_booking_days_advance` INT DEFAULT 14,
    `max_booking_hours_per_day` INT DEFAULT 4,
    `max_materials_per_booking` INT DEFAULT 10,
    `can_remote_access` TINYINT(1) DEFAULT 0,
    `can_request_reproductions` TINYINT(1) DEFAULT 1,
    `can_export_data` TINYINT(1) DEFAULT 1,
    `requires_id_verification` TINYINT(1) DEFAULT 1,
    `auto_approve` TINYINT(1) DEFAULT 0,
    `expiry_months` INT DEFAULT 12,
    `priority_level` INT DEFAULT 5,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 100,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_active` (`is_active`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default researcher types
INSERT INTO `research_researcher_type` (`name`, `code`, `description`, `max_booking_days_advance`, `max_booking_hours_per_day`, `max_materials_per_booking`, `can_remote_access`, `can_request_reproductions`, `can_export_data`, `requires_id_verification`, `auto_approve`, `expiry_months`, `priority_level`, `sort_order`) VALUES
('General Public', 'public', 'Members of the general public with casual research needs', 7, 2, 5, 0, 1, 0, 1, 0, 6, 1, 10),
('Registered Researcher', 'researcher', 'Registered independent researchers', 14, 4, 10, 0, 1, 1, 1, 0, 12, 3, 20),
('Academic Staff', 'academic', 'University and college academic staff members', 30, 8, 20, 1, 1, 1, 0, 1, 24, 7, 30),
('Postgraduate Student', 'postgraduate', 'Masters and doctoral students', 21, 6, 15, 0, 1, 1, 1, 0, 12, 5, 40),
('Undergraduate Student', 'undergraduate', 'Undergraduate students', 7, 3, 5, 0, 1, 0, 1, 0, 6, 2, 50),
('Visiting Scholar', 'visiting', 'Visiting researchers from other institutions', 30, 8, 20, 1, 1, 1, 1, 0, 3, 6, 60),
('Institutional Partner', 'partner', 'Staff from partner institutions', 60, 8, 30, 1, 1, 1, 0, 1, 36, 8, 70),
('Heritage Professional', 'professional', 'Archivists, librarians, and heritage professionals', 30, 8, 25, 1, 1, 1, 0, 1, 24, 7, 80),
('Genealogist', 'genealogist', 'Family history researchers', 14, 4, 10, 0, 1, 1, 1, 0, 12, 4, 90),
('Media/Journalist', 'media', 'Journalists and media professionals', 14, 4, 10, 0, 1, 1, 1, 0, 6, 4, 100)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================================
-- PHASE 1.3: VERIFICATION SYSTEM
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_verification` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `verification_type` ENUM('id_document','institutional_letter','institutional_email','orcid','staff_approval','professional_membership','other') NOT NULL,
    `document_type` VARCHAR(100),
    `document_reference` VARCHAR(255),
    `document_path` VARCHAR(500),
    `verification_data` JSON,
    `status` ENUM('pending','verified','rejected','expired') DEFAULT 'pending',
    `verified_by` INT,
    `verified_at` DATETIME,
    `expires_at` DATE,
    `rejection_reason` TEXT,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`verification_type`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CORE TABLES (Existing - with modifications)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_researcher` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `title` VARCHAR(50) DEFAULT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `affiliation_type` ENUM('academic','government','private','independent','student','other') DEFAULT 'independent',
    `institution` VARCHAR(255) DEFAULT NULL,
    `department` VARCHAR(255) DEFAULT NULL,
    `position` VARCHAR(255) DEFAULT NULL,
    `student_id` VARCHAR(100) DEFAULT NULL,
    `research_interests` TEXT,
    `current_project` TEXT,
    `orcid_id` VARCHAR(50) DEFAULT NULL,
    `orcid_verified` TINYINT(1) DEFAULT 0,
    `orcid_access_token` TEXT,
    `orcid_refresh_token` TEXT,
    `orcid_token_expires_at` DATETIME,
    `researcher_id_wos` VARCHAR(50) DEFAULT NULL,
    `scopus_id` VARCHAR(50) DEFAULT NULL,
    `isni` VARCHAR(50) DEFAULT NULL,
    `researcher_type_id` INT DEFAULT NULL,
    `timezone` VARCHAR(50) DEFAULT 'Africa/Johannesburg',
    `preferred_language` VARCHAR(10) DEFAULT 'en',
    `api_key` VARCHAR(64) DEFAULT NULL,
    `api_key_expires_at` DATETIME DEFAULT NULL,
    `id_type` ENUM('passport','national_id','drivers_license','student_card','other') DEFAULT NULL,
    `id_number` VARCHAR(100) DEFAULT NULL,
    `id_verified` TINYINT(1) DEFAULT 0,
    `id_verified_by` INT DEFAULT NULL,
    `id_verified_at` DATETIME DEFAULT NULL,
    `status` ENUM('pending','approved','suspended','expired','rejected') DEFAULT 'pending',
    `rejection_reason` TEXT,
    `approved_by` INT DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `expires_at` DATE DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_api_key` (`api_key`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_email` (`email`),
    KEY `idx_orcid` (`orcid_id`),
    KEY `idx_type` (`researcher_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit table for rejected/archived researchers
CREATE TABLE IF NOT EXISTS `research_researcher_audit` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `original_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `title` VARCHAR(50) DEFAULT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `affiliation_type` VARCHAR(50) DEFAULT NULL,
    `institution` VARCHAR(255) DEFAULT NULL,
    `department` VARCHAR(255) DEFAULT NULL,
    `position` VARCHAR(255) DEFAULT NULL,
    `research_interests` TEXT,
    `current_project` TEXT,
    `orcid_id` VARCHAR(50) DEFAULT NULL,
    `id_type` VARCHAR(50) DEFAULT NULL,
    `id_number` VARCHAR(100) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL COMMENT 'Status at time of archival',
    `rejection_reason` TEXT,
    `archived_by` INT DEFAULT NULL,
    `archived_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `original_created_at` DATETIME DEFAULT NULL,
    `original_updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_reading_room` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(20) DEFAULT NULL,
    `description` TEXT,
    `amenities` TEXT,
    `capacity` INT DEFAULT 10,
    `location` VARCHAR(255) DEFAULT NULL,
    `operating_hours` TEXT,
    `rules` TEXT,
    `advance_booking_days` INT DEFAULT 14,
    `max_booking_hours` INT DEFAULT 4,
    `cancellation_hours` INT DEFAULT 24,
    `is_active` TINYINT(1) DEFAULT 1,
    `opening_time` TIME DEFAULT '09:00:00',
    `closing_time` TIME DEFAULT '17:00:00',
    `days_open` VARCHAR(50) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_booking` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `reading_room_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `booking_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `purpose` TEXT,
    `status` ENUM('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
    `confirmed_by` INT DEFAULT NULL,
    `confirmed_at` DATETIME DEFAULT NULL,
    `cancelled_at` DATETIME DEFAULT NULL,
    `cancellation_reason` TEXT,
    `checked_in_at` DATETIME DEFAULT NULL,
    `checked_out_at` DATETIME DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_room` (`reading_room_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_date` (`booking_date`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 3.1: ENHANCED MATERIAL REQUESTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_material_request` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `booking_id` INT NOT NULL,
    `object_id` INT NOT NULL,
    `quantity` INT DEFAULT 1,
    `notes` TEXT,
    `request_type` ENUM('reading_room','reproduction','loan','remote_access') DEFAULT 'reading_room',
    `priority` ENUM('normal','high','rush') DEFAULT 'normal',
    `handling_instructions` TEXT,
    `location_code` VARCHAR(100),
    `shelf_location` VARCHAR(255),
    `box_number` VARCHAR(50),
    `folder_number` VARCHAR(50),
    `curatorial_approval_required` TINYINT(1) DEFAULT 0,
    `curatorial_approved_by` INT DEFAULT NULL,
    `curatorial_approved_at` DATETIME DEFAULT NULL,
    `paging_slip_printed` TINYINT(1) DEFAULT 0,
    `status` ENUM('requested','retrieved','delivered','in_use','returned','unavailable') DEFAULT 'requested',
    `retrieved_by` INT DEFAULT NULL,
    `retrieved_at` DATETIME DEFAULT NULL,
    `returned_at` DATETIME DEFAULT NULL,
    `condition_notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`request_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_request_status_history` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `request_id` INT NOT NULL,
    `request_type` ENUM('material','reproduction') NOT NULL DEFAULT 'material',
    `old_status` VARCHAR(50),
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by` INT,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_request` (`request_id`, `request_type`),
    KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 3.2: REPRODUCTION REQUESTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_reproduction_request` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `reference_number` VARCHAR(50),
    `purpose` TEXT,
    `intended_use` ENUM('personal','academic','publication','exhibition','commercial','other') DEFAULT 'personal',
    `publication_details` TEXT,
    `status` ENUM('draft','submitted','processing','awaiting_payment','in_production','completed','cancelled') DEFAULT 'draft',
    `estimated_cost` DECIMAL(10,2) DEFAULT NULL,
    `final_cost` DECIMAL(10,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'ZAR',
    `payment_reference` VARCHAR(100),
    `payment_date` DATE,
    `payment_method` VARCHAR(50),
    `invoice_number` VARCHAR(50),
    `invoice_date` DATE,
    `delivery_method` ENUM('email','download','post','collect','digital','pickup','courier','physical') DEFAULT 'email',
    `delivery_address` TEXT,
    `delivery_email` VARCHAR(255),
    `completed_at` DATETIME,
    `processed_by` INT,
    `notes` TEXT,
    `admin_notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reference` (`reference_number`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`),
    KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_reproduction_item` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `request_id` INT NOT NULL,
    `object_id` INT NOT NULL,
    `digital_object_id` INT DEFAULT NULL,
    `reproduction_type` ENUM('photocopy','scan','photograph','digital_copy','digital_scan','transcription','certification','certified_copy','microfilm','other') DEFAULT 'scan',
    `format` VARCHAR(50) DEFAULT 'PDF',
    `resolution` VARCHAR(50),
    `color_mode` ENUM('color','grayscale','bw') DEFAULT 'grayscale',
    `quantity` INT DEFAULT 1,
    `page_range` VARCHAR(100),
    `special_instructions` TEXT,
    `unit_price` DECIMAL(10,2),
    `total_price` DECIMAL(10,2),
    `status` ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    `completed_at` DATETIME,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_request` (`request_id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_reproduction_file` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `item_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` BIGINT,
    `mime_type` VARCHAR(100),
    `checksum` VARCHAR(64),
    `download_count` INT DEFAULT 0,
    `download_expires_at` DATETIME,
    `download_token` VARCHAR(64),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_item` (`item_id`),
    KEY `idx_token` (`download_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 2.1: RESEARCH PROJECTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_project` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `owner_id` INT NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `description` TEXT,
    `project_type` ENUM('thesis','dissertation','publication','exhibition','documentary','genealogy','institutional','personal','other') DEFAULT 'personal',
    `institution` VARCHAR(255),
    `supervisor` VARCHAR(255),
    `funding_source` VARCHAR(255),
    `grant_number` VARCHAR(100),
    `ethics_approval` VARCHAR(100),
    `start_date` DATE,
    `expected_end_date` DATE,
    `actual_end_date` DATE,
    `status` ENUM('planning','active','on_hold','completed','archived') DEFAULT 'planning',
    `visibility` ENUM('private','collaborators','public') DEFAULT 'private',
    `share_token` VARCHAR(64),
    `metadata` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_owner` (`owner_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`project_type`),
    KEY `idx_share` (`share_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_project_collaborator` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `role` ENUM('owner','editor','contributor','viewer') DEFAULT 'contributor',
    `permissions` JSON,
    `invited_by` INT,
    `invited_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `accepted_at` DATETIME,
    `status` ENUM('pending','accepted','declined','removed') DEFAULT 'pending',
    `notes` TEXT,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_project_researcher` (`project_id`, `researcher_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_project_resource` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `resource_type` ENUM('collection','saved_search','annotation','bibliography','object','external_link','document','note') NOT NULL,
    `resource_id` INT DEFAULT NULL,
    `object_id` INT DEFAULT NULL,
    `external_url` VARCHAR(1000),
    `title` VARCHAR(500),
    `description` TEXT,
    `notes` TEXT,
    `tags` VARCHAR(500),
    `added_by` INT,
    `sort_order` INT DEFAULT 0,
    `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_type` (`resource_type`),
    KEY `idx_object` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_project_milestone` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `due_date` DATE,
    `completed_at` DATETIME,
    `completed_by` INT,
    `status` ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_status` (`status`),
    KEY `idx_due` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 2.2: ACTIVITY TRACKING
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_activity_log` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `activity_type` ENUM('view','search','download','cite','annotate','collect','book','request','export','share','login','logout','create','clipboard_add') NOT NULL,
    `entity_type` VARCHAR(50),
    `entity_id` INT,
    `entity_title` VARCHAR(500),
    `details` JSON,
    `session_id` VARCHAR(64),
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_type` (`activity_type`),
    KEY `idx_date` (`created_at`),
    KEY `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- EXISTING TABLES (Collections, Annotations, etc.)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_collection` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `is_public` TINYINT(1) DEFAULT 0,
    `share_token` VARCHAR(64) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_share_token` (`share_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_collection_item` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `collection_id` INT NOT NULL,
    `object_id` INT NOT NULL,
    `notes` TEXT,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_item` (`collection_id`, `object_id`),
    KEY `idx_collection` (`collection_id`),
    KEY `idx_object` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_annotation` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `object_id` INT DEFAULT NULL,
    `entity_type` VARCHAR(50) DEFAULT 'information_object',
    `digital_object_id` INT DEFAULT NULL,
    `annotation_type` ENUM('note','highlight','bookmark','tag','transcription','correction') DEFAULT 'note',
    `title` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `content_format` ENUM('text','html') DEFAULT 'text',
    `target_selector` TEXT,
    `canvas_id` VARCHAR(500),
    `iiif_annotation_id` VARCHAR(255),
    `tags` VARCHAR(500) DEFAULT NULL,
    `is_private` TINYINT(1) DEFAULT 1,
    `visibility` ENUM('private','shared','public') DEFAULT 'private',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_type` (`annotation_type`),
    FULLTEXT INDEX `idx_annotation_fulltext` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 4: SEARCH ALERTS (Real-Time)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_saved_search` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `search_query` TEXT NOT NULL,
    `search_filters` JSON,
    `search_type` VARCHAR(50) DEFAULT 'informationobject',
    `total_results_at_save` INT DEFAULT NULL,
    `facets` JSON,
    `alert_enabled` TINYINT(1) DEFAULT 0,
    `alert_frequency` ENUM('realtime','daily','weekly','monthly') DEFAULT 'weekly',
    `last_alert_at` DATETIME DEFAULT NULL,
    `new_results_count` INT DEFAULT 0,
    `is_public` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_alert` (`alert_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_search_alert_log` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `saved_search_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `previous_count` INT,
    `new_count` INT,
    `new_items_count` INT,
    `notification_sent` TINYINT(1) DEFAULT 0,
    `notification_method` VARCHAR(50),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_search` (`saved_search_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 5: BIBLIOGRAPHY MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_bibliography` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `citation_style` VARCHAR(50) DEFAULT 'chicago',
    `is_public` TINYINT(1) DEFAULT 0,
    `share_token` VARCHAR(64),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_share` (`share_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_bibliography_entry` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `bibliography_id` INT NOT NULL,
    `object_id` INT DEFAULT NULL,
    `entry_type` ENUM('archival','book','article','chapter','thesis','website','other') DEFAULT 'archival',
    `csl_data` JSON,
    `title` VARCHAR(500),
    `authors` TEXT,
    `date` VARCHAR(50),
    `publisher` VARCHAR(255),
    `container_title` VARCHAR(500),
    `volume` VARCHAR(50),
    `issue` VARCHAR(50),
    `pages` VARCHAR(50),
    `doi` VARCHAR(255),
    `url` VARCHAR(1000),
    `accessed_date` DATE,
    `archive_name` VARCHAR(255),
    `archive_location` VARCHAR(255),
    `collection_title` VARCHAR(500),
    `box` VARCHAR(50),
    `folder` VARCHAR(50),
    `notes` TEXT,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bibliography` (`bibliography_id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_type` (`entry_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 6: PRIVATE WORKSPACES
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_workspace` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `owner_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `visibility` ENUM('private','members','public') DEFAULT 'private',
    `share_token` VARCHAR(64),
    `settings` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_owner` (`owner_id`),
    KEY `idx_visibility` (`visibility`),
    KEY `idx_share` (`share_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_workspace_member` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `workspace_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `role` ENUM('owner','admin','editor','viewer','member','contributor') DEFAULT 'viewer',
    `invited_by` INT,
    `invited_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `accepted_at` DATETIME,
    `status` ENUM('pending','accepted','declined','removed') DEFAULT 'pending',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_workspace_researcher` (`workspace_id`, `researcher_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_workspace_resource` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `workspace_id` INT NOT NULL,
    `resource_type` ENUM('collection','project','bibliography','saved_search','document','link') NOT NULL,
    `resource_id` INT DEFAULT NULL,
    `external_url` VARCHAR(1000),
    `title` VARCHAR(500),
    `description` TEXT,
    `added_by` INT,
    `sort_order` INT DEFAULT 0,
    `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_workspace` (`workspace_id`),
    KEY `idx_type` (`resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_discussion` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `workspace_id` INT DEFAULT NULL,
    `project_id` INT DEFAULT NULL,
    `parent_id` INT DEFAULT NULL,
    `researcher_id` INT NOT NULL,
    `subject` VARCHAR(500),
    `content` TEXT NOT NULL,
    `is_pinned` TINYINT(1) DEFAULT 0,
    `is_resolved` TINYINT(1) DEFAULT 0,
    `resolved_by` INT,
    `resolved_at` DATETIME,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_workspace` (`workspace_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_parent` (`parent_id`),
    KEY `idx_researcher` (`researcher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 7: ANALYTICS
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_statistics_daily` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `stat_date` DATE NOT NULL,
    `stat_type` VARCHAR(50) NOT NULL,
    `dimension` VARCHAR(100),
    `dimension_value` VARCHAR(255),
    `count_value` INT DEFAULT 0,
    `sum_value` DECIMAL(15,2) DEFAULT NULL,
    `metadata` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat` (`stat_date`, `stat_type`, `dimension`, `dimension_value`),
    KEY `idx_date` (`stat_date`),
    KEY `idx_type` (`stat_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CITATION LOG (Enhanced)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_citation_log` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT DEFAULT NULL,
    `object_id` INT NOT NULL,
    `citation_style` VARCHAR(50) NOT NULL,
    `citation_text` TEXT NOT NULL,
    `export_format` VARCHAR(20),
    `session_id` VARCHAR(64),
    `ip_address` VARCHAR(45),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_style` (`citation_style`),
    KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PASSWORD RESET
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_password_reset` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_token` (`token`),
    KEY `idx_user` (`user_id`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 8: API KEYS TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_api_key` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `api_key` VARCHAR(64) NOT NULL,
    `permissions` JSON,
    `rate_limit` INT DEFAULT 1000,
    `last_used_at` DATETIME,
    `request_count` INT DEFAULT 0,
    `expires_at` DATETIME,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_api_key` (`api_key`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_api_log` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `api_key_id` INT,
    `researcher_id` INT,
    `endpoint` VARCHAR(255) NOT NULL,
    `method` VARCHAR(10) NOT NULL,
    `request_params` JSON,
    `response_code` INT,
    `response_time_ms` INT,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_key` (`api_key_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_endpoint` (`endpoint`),
    KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 9: INTERNATIONALIZATION
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_researcher_type_i18n` (
    `id` INT NOT NULL,
    `culture` VARCHAR(10) NOT NULL DEFAULT 'en',
    `name` VARCHAR(100),
    `description` TEXT,
    PRIMARY KEY (`id`, `culture`),
    CONSTRAINT `fk_researcher_type_i18n` FOREIGN KEY (`id`) REFERENCES `research_researcher_type` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 149: RESEARCH JOURNAL (Phase 1)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_journal_entry` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `project_id` INT DEFAULT NULL,
  `entry_date` DATE NOT NULL,
  `title` VARCHAR(500),
  `content` TEXT NOT NULL,
  `content_format` ENUM('text','html') DEFAULT 'html',
  `entry_type` ENUM('manual','auto_booking','auto_material','auto_annotation',
                    'auto_search','auto_collection','reflection','milestone') DEFAULT 'manual',
  `time_spent_minutes` INT DEFAULT NULL,
  `tags` VARCHAR(500) DEFAULT NULL,
  `is_private` TINYINT(1) DEFAULT 1,
  `related_entity_type` VARCHAR(50) DEFAULT NULL,
  `related_entity_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_date` (`entry_date`),
  FULLTEXT INDEX `idx_journal_fulltext` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 149: RESEARCH REPORTS (Phase 2)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_report` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `project_id` INT DEFAULT NULL,
  `title` VARCHAR(500) NOT NULL,
  `template_type` ENUM('research_summary','genealogical','historical',
                       'source_analysis','finding_aid','custom') DEFAULT 'custom',
  `description` TEXT,
  `status` ENUM('draft','in_progress','review','completed','archived') DEFAULT 'draft',
  `metadata` JSON,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_report_section` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_id` INT NOT NULL,
  `section_type` ENUM('title_page','toc','heading','text','bibliography',
                      'collection_list','annotation_list','timeline','custom') DEFAULT 'text',
  `title` VARCHAR(500),
  `content` TEXT,
  `content_format` ENUM('text','html') DEFAULT 'html',
  `bibliography_id` INT DEFAULT NULL,
  `collection_id` INT DEFAULT NULL,
  `settings` JSON,
  `sort_order` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_report` (`report_id`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_report_template` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `sections_config` JSON NOT NULL,
  `is_system` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `research_report_template` (`name`, `code`, `description`, `sections_config`, `is_system`) VALUES
('Research Summary', 'research_summary', 'General research summary report',
 '["title_page","toc","heading:Introduction","text:Background","text:Methodology","text:Findings","text:Conclusion","bibliography"]', 1),
('Genealogical Report', 'genealogical', 'Family history research report',
 '["title_page","toc","heading:Family Overview","text:Origins","text:Family Timeline","text:Notable Members","collection_list","bibliography"]', 1),
('Historical Analysis', 'historical', 'Historical research analysis',
 '["title_page","toc","heading:Historical Context","text:Primary Sources","text:Analysis","text:Interpretation","annotation_list","bibliography"]', 1),
('Source Analysis', 'source_analysis', 'Archival source analysis report',
 '["title_page","toc","heading:Source Description","text:Provenance","text:Content Analysis","text:Reliability Assessment","annotation_list","bibliography"]', 1),
('Finding Aid', 'finding_aid', 'Collection finding aid',
 '["title_page","toc","heading:Collection Overview","text:Administrative History","text:Scope and Content","collection_list","text:Access Conditions"]', 1),
('Custom Report', 'custom', 'Blank report with no predefined sections',
 '["title_page","text:Content"]', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================================
-- ISSUE 149: NOTIFICATIONS (Phase 4)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_notification` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `type` ENUM('alert','invitation','comment','reply','system','reminder','collaboration') NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `message` TEXT,
  `link` VARCHAR(1000) DEFAULT NULL,
  `related_entity_type` VARCHAR(50) DEFAULT NULL,
  `related_entity_id` INT DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_notification_preference` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `notification_type` VARCHAR(50) NOT NULL,
  `email_enabled` TINYINT(1) DEFAULT 1,
  `in_app_enabled` TINYINT(1) DEFAULT 1,
  `digest_frequency` ENUM('immediate','daily','weekly','none') DEFAULT 'immediate',
  UNIQUE KEY `uk_researcher_type` (`researcher_id`, `notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 149: INSTITUTIONAL SHARING (Phase 6)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_institution` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(500) NOT NULL,
  `code` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `url` VARCHAR(1000) DEFAULT NULL,
  `contact_name` VARCHAR(255) DEFAULT NULL,
  `contact_email` VARCHAR(255) DEFAULT NULL,
  `logo_path` VARCHAR(500) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_institutional_share` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `institution_id` INT DEFAULT NULL,
  `share_token` VARCHAR(64) NOT NULL UNIQUE,
  `share_type` ENUM('view','contribute','full') DEFAULT 'view',
  `shared_by` INT NOT NULL,
  `accepted_by` INT DEFAULT NULL,
  `status` ENUM('pending','active','revoked','expired') DEFAULT 'pending',
  `message` TEXT,
  `permissions` JSON,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_project` (`project_id`),
  KEY `idx_institution` (`institution_id`),
  KEY `idx_token` (`share_token`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_external_collaborator` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `share_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `institution` VARCHAR(500) DEFAULT NULL,
  `orcid_id` VARCHAR(50) DEFAULT NULL,
  `access_token` VARCHAR(64) NOT NULL UNIQUE,
  `role` ENUM('viewer','contributor') DEFAULT 'viewer',
  `last_accessed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_share` (`share_id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 149: COLLABORATION ENHANCEMENTS (Phase 7)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_comment` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `entity_type` ENUM('report','report_section','annotation','journal_entry','collection') NOT NULL,
  `entity_id` INT NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `content` TEXT NOT NULL,
  `is_resolved` TINYINT(1) DEFAULT 0,
  `resolved_by` INT DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_peer_review` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_id` INT NOT NULL,
  `requested_by` INT NOT NULL,
  `reviewer_id` INT NOT NULL,
  `status` ENUM('pending','in_progress','completed','declined') DEFAULT 'pending',
  `feedback` TEXT,
  `rating` INT DEFAULT NULL,
  `requested_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  KEY `idx_report` (`report_id`),
  KEY `idx_reviewer` (`reviewer_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CLIPBOARD INTEGRATION
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_clipboard_project` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `project_id` INT NOT NULL,
  `object_id` INT NOT NULL,
  `is_pinned` TINYINT(1) DEFAULT 0,
  `notes` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_project` (`project_id`),
  UNIQUE KEY `uk_project_object` (`project_id`, `researcher_id`, `object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ALTER STATEMENTS FOR EXISTING INSTALLATIONS
-- ============================================================

-- Add new columns to research_researcher if table exists
-- These are safe to run multiple times due to IF NOT EXISTS pattern

DELIMITER //

DROP PROCEDURE IF EXISTS upgrade_research_tables//

CREATE PROCEDURE upgrade_research_tables()
BEGIN
    -- Add ORCID verification fields
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'orcid_verified') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `orcid_verified` TINYINT(1) DEFAULT 0 AFTER `orcid_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'orcid_access_token') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `orcid_access_token` TEXT AFTER `orcid_verified`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'orcid_refresh_token') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `orcid_refresh_token` TEXT AFTER `orcid_access_token`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'orcid_token_expires_at') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `orcid_token_expires_at` DATETIME AFTER `orcid_refresh_token`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'researcher_id_wos') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `researcher_id_wos` VARCHAR(50) AFTER `orcid_token_expires_at`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'scopus_id') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `scopus_id` VARCHAR(50) AFTER `researcher_id_wos`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'isni') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `isni` VARCHAR(50) AFTER `scopus_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'researcher_type_id') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `researcher_type_id` INT AFTER `isni`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'timezone') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `timezone` VARCHAR(50) DEFAULT 'Africa/Johannesburg' AFTER `researcher_type_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'preferred_language') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `preferred_language` VARCHAR(10) DEFAULT 'en' AFTER `timezone`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'api_key') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `api_key` VARCHAR(64) AFTER `preferred_language`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'api_key_expires_at') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `api_key_expires_at` DATETIME AFTER `api_key`;
    END IF;

    -- Add project_id to booking
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_booking' AND column_name = 'project_id') THEN
        ALTER TABLE `research_booking` ADD COLUMN `project_id` INT AFTER `reading_room_id`;
    END IF;

    -- Add enhanced material request fields
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'request_type') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `request_type` ENUM('reading_room','reproduction','loan','remote_access') DEFAULT 'reading_room' AFTER `notes`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'priority') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `priority` ENUM('normal','high','rush') DEFAULT 'normal' AFTER `request_type`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'handling_instructions') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `handling_instructions` TEXT AFTER `priority`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'location_code') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `location_code` VARCHAR(100) AFTER `handling_instructions`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'shelf_location') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `shelf_location` VARCHAR(255) AFTER `location_code`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'box_number') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `box_number` VARCHAR(50) AFTER `shelf_location`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'folder_number') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `folder_number` VARCHAR(50) AFTER `box_number`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'curatorial_approval_required') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `curatorial_approval_required` TINYINT(1) DEFAULT 0 AFTER `folder_number`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'curatorial_approved_by') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `curatorial_approved_by` INT AFTER `curatorial_approval_required`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'curatorial_approved_at') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `curatorial_approved_at` DATETIME AFTER `curatorial_approved_by`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'paging_slip_printed') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `paging_slip_printed` TINYINT(1) DEFAULT 0 AFTER `curatorial_approved_at`;
    END IF;

    -- Add project_id to saved_search
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_saved_search' AND column_name = 'project_id') THEN
        ALTER TABLE `research_saved_search` ADD COLUMN `project_id` INT AFTER `researcher_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_saved_search' AND column_name = 'total_results_at_save') THEN
        ALTER TABLE `research_saved_search` ADD COLUMN `total_results_at_save` INT AFTER `search_type`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_saved_search' AND column_name = 'facets') THEN
        ALTER TABLE `research_saved_search` ADD COLUMN `facets` JSON AFTER `total_results_at_save`;
    END IF;

    -- Change alert_frequency to allow realtime
    -- Note: This requires careful handling in MySQL

    -- Add project_id to collection
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_collection' AND column_name = 'project_id') THEN
        ALTER TABLE `research_collection` ADD COLUMN `project_id` INT AFTER `researcher_id`;
    END IF;

    -- Add project_id to annotation
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_annotation' AND column_name = 'project_id') THEN
        ALTER TABLE `research_annotation` ADD COLUMN `project_id` INT AFTER `researcher_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_annotation' AND column_name = 'canvas_id') THEN
        ALTER TABLE `research_annotation` ADD COLUMN `canvas_id` VARCHAR(500) AFTER `target_selector`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_annotation' AND column_name = 'iiif_annotation_id') THEN
        ALTER TABLE `research_annotation` ADD COLUMN `iiif_annotation_id` VARCHAR(255) AFTER `canvas_id`;
    END IF;

    -- Issue 149: entity_type for note linking
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_annotation' AND column_name = 'entity_type') THEN
        ALTER TABLE `research_annotation` ADD COLUMN `entity_type` VARCHAR(50) DEFAULT 'information_object' AFTER `object_id`;
    END IF;

    -- Issue 149: content_format for rich text notes
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_annotation' AND column_name = 'content_format') THEN
        ALTER TABLE `research_annotation` ADD COLUMN `content_format` ENUM('text','html') DEFAULT 'text' AFTER `content`;
    END IF;

    -- Issue 149: visibility for note sharing
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_annotation' AND column_name = 'visibility') THEN
        ALTER TABLE `research_annotation` ADD COLUMN `visibility` ENUM('private','shared','public') DEFAULT 'private' AFTER `is_private`;
    END IF;

    -- ================================================================
    -- Issue 159 Phase 2: Evidence Set upgrade on research_collection_item
    -- ================================================================
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_collection_item' AND column_name = 'object_type') THEN
        ALTER TABLE `research_collection_item` ADD COLUMN `object_type` VARCHAR(50) DEFAULT 'information_object' AFTER `object_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_collection_item' AND column_name = 'culture') THEN
        ALTER TABLE `research_collection_item` ADD COLUMN `culture` VARCHAR(10) DEFAULT NULL AFTER `object_type`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_collection_item' AND column_name = 'external_uri') THEN
        ALTER TABLE `research_collection_item` ADD COLUMN `external_uri` VARCHAR(1000) DEFAULT NULL AFTER `culture`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_collection_item' AND column_name = 'tags') THEN
        ALTER TABLE `research_collection_item` ADD COLUMN `tags` VARCHAR(500) DEFAULT NULL AFTER `external_uri`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_collection_item' AND column_name = 'reference_code') THEN
        ALTER TABLE `research_collection_item` ADD COLUMN `reference_code` VARCHAR(255) DEFAULT NULL AFTER `tags`;
    END IF;

END//

DELIMITER ;

-- Run the upgrade procedure
CALL upgrade_research_tables();

-- Drop the procedure after use
DROP PROCEDURE IF EXISTS upgrade_research_tables;

-- ============================================================
-- ISSUE 159 PHASE 2a: SNAPSHOTS (Immutable Research State Freeze)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_snapshot` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `description` TEXT,
    `hash_sha256` VARCHAR(64),
    `query_state_json` JSON,
    `rights_state_json` JSON,
    `metadata_json` JSON,
    `item_count` INT DEFAULT 0,
    `status` ENUM('active','archived') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`),
    KEY `idx_hash` (`hash_sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_snapshot_item` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `snapshot_id` INT NOT NULL,
    `object_id` INT NOT NULL,
    `object_type` VARCHAR(50) DEFAULT 'information_object',
    `culture` VARCHAR(10) DEFAULT NULL,
    `slug` VARCHAR(255) DEFAULT NULL,
    `metadata_version_json` JSON,
    `rights_snapshot_json` JSON,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_snapshot` (`snapshot_id`),
    KEY `idx_object` (`object_id`, `object_type`),
    KEY `idx_sort` (`snapshot_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 159 PHASE 2a: HYPOTHESES
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_hypothesis` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `statement` TEXT NOT NULL,
    `status` ENUM('proposed','testing','supported','refuted') DEFAULT 'proposed',
    `evidence_count` INT DEFAULT 0,
    `tags` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_hypothesis_evidence` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `hypothesis_id` INT NOT NULL,
    `source_type` VARCHAR(50) NOT NULL,
    `source_id` INT NOT NULL,
    `relationship` ENUM('supports','refutes','neutral') NOT NULL,
    `confidence` DECIMAL(5,2) DEFAULT NULL,
    `note` TEXT,
    `added_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_hypothesis` (`hypothesis_id`),
    KEY `idx_source` (`source_type`, `source_id`),
    KEY `idx_relationship` (`relationship`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 159 PHASE 2a: SOURCE ASSESSMENT & TRUST
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_source_assessment` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `object_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `source_type` ENUM('primary','secondary','tertiary') NOT NULL,
    `source_form` ENUM('born_digital','scan','original','transcription','translation') DEFAULT 'original',
    `completeness` ENUM('complete','partial','fragment','missing_pages','redacted') DEFAULT 'complete',
    `trust_score` INT DEFAULT NULL,
    `rationale` TEXT,
    `bias_context` TEXT,
    `assessed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_source_type` (`source_type`),
    KEY `idx_trust` (`trust_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_quality_metric` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `object_id` INT NOT NULL,
    `metric_type` ENUM('ocr_confidence','image_quality','digitisation_completeness','fixity_status') NOT NULL,
    `metric_value` DECIMAL(10,4) NOT NULL,
    `source_service` VARCHAR(100) DEFAULT NULL,
    `raw_data_json` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_type` (`metric_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 159 PHASE 2a: W3C WEB ANNOTATIONS v2
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_annotation_v2` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `motivation` ENUM('commenting','describing','classifying','linking','questioning','tagging','highlighting') NOT NULL DEFAULT 'commenting',
    `body_json` JSON,
    `creator_json` JSON,
    `generated_json` JSON,
    `status` ENUM('active','archived','deleted') DEFAULT 'active',
    `visibility` ENUM('private','shared','public') DEFAULT 'private',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_motivation` (`motivation`),
    KEY `idx_status` (`status`),
    KEY `idx_visibility` (`visibility`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_annotation_target` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `annotation_id` INT NOT NULL,
    `source_type` VARCHAR(50) NOT NULL,
    `source_id` INT DEFAULT NULL,
    `selector_type` ENUM('TextQuoteSelector','FragmentSelector','SvgSelector','PointSelector','RangeSelector','TimeSelector') DEFAULT NULL,
    `selector_json` JSON,
    `source_url` VARCHAR(1000) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_annotation` (`annotation_id`),
    KEY `idx_source` (`source_type`, `source_id`),
    KEY `idx_selector` (`selector_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 159 PHASE 2a: ASSERTIONS (Knowledge Graph)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_assertion` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `subject_type` VARCHAR(50) NOT NULL,
    `subject_id` INT NOT NULL,
    `subject_label` VARCHAR(500) DEFAULT NULL,
    `predicate` VARCHAR(255) NOT NULL,
    `object_value` TEXT,
    `object_type` VARCHAR(50) DEFAULT NULL,
    `object_id` INT DEFAULT NULL,
    `object_label` VARCHAR(500) DEFAULT NULL,
    `assertion_type` ENUM('biographical','chronological','spatial','relational','attributive') NOT NULL,
    `status` ENUM('proposed','verified','disputed','retracted') DEFAULT 'proposed',
    `confidence` DECIMAL(5,2) DEFAULT NULL,
    `version` INT DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_subject` (`subject_type`, `subject_id`),
    KEY `idx_predicate` (`predicate`),
    KEY `idx_assertion_type` (`assertion_type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_assertion_evidence` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `assertion_id` INT NOT NULL,
    `source_type` VARCHAR(50) NOT NULL,
    `source_id` INT NOT NULL,
    `selector_json` JSON,
    `relationship` ENUM('supports','refutes') NOT NULL,
    `note` TEXT,
    `added_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_assertion` (`assertion_id`),
    KEY `idx_source` (`source_type`, `source_id`),
    KEY `idx_relationship` (`relationship`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 159 PHASE 2b: AI EXTRACTION ORCHESTRATION
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_extraction_job` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `collection_id` INT DEFAULT NULL,
    `researcher_id` INT NOT NULL,
    `extraction_type` ENUM('ocr','ner','summarize','translate','spellcheck','face_detection','form_extraction') NOT NULL,
    `parameters_json` JSON,
    `status` ENUM('queued','running','completed','failed') DEFAULT 'queued',
    `progress` INT DEFAULT 0,
    `total_items` INT DEFAULT 0,
    `processed_items` INT DEFAULT 0,
    `error_log` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_collection` (`collection_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_type` (`extraction_type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_extraction_result` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `job_id` INT NOT NULL,
    `object_id` INT NOT NULL,
    `result_type` ENUM('entity','summary','translation','transcription','form_field','face') NOT NULL,
    `data_json` JSON,
    `confidence` DECIMAL(5,4) DEFAULT NULL,
    `model_version` VARCHAR(100) DEFAULT NULL,
    `input_hash` VARCHAR(64) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_job` (`job_id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_type` (`result_type`),
    KEY `idx_confidence` (`confidence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_validation_queue` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `result_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `status` ENUM('pending','accepted','rejected','modified') DEFAULT 'pending',
    `modified_data_json` JSON,
    `reviewer_id` INT DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_result` (`result_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`),
    KEY `idx_reviewer` (`reviewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_document_template` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `document_type` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `fields_json` JSON,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`document_type`),
    KEY `idx_creator` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 159 PHASE 2c: CROSS-COLLECTION SYNTHESIS
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_entity_resolution` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `entity_a_type` VARCHAR(50) NOT NULL,
    `entity_a_id` INT NOT NULL,
    `entity_b_type` VARCHAR(50) NOT NULL,
    `entity_b_id` INT NOT NULL,
    `confidence` DECIMAL(5,4) DEFAULT NULL,
    `match_method` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('proposed','accepted','rejected') DEFAULT 'proposed',
    `resolver_id` INT DEFAULT NULL,
    `resolved_at` DATETIME DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_entity_a` (`entity_a_type`, `entity_a_id`),
    KEY `idx_entity_b` (`entity_b_type`, `entity_b_id`),
    KEY `idx_status` (`status`),
    KEY `idx_confidence` (`confidence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_timeline_event` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `label` VARCHAR(500) NOT NULL,
    `description` TEXT,
    `date_start` DATE NOT NULL,
    `date_end` DATE DEFAULT NULL,
    `date_type` ENUM('event','creation','accession','publication') DEFAULT 'event',
    `source_type` VARCHAR(50) DEFAULT NULL,
    `source_id` INT DEFAULT NULL,
    `position` INT DEFAULT 0,
    `color` VARCHAR(7) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_dates` (`date_start`, `date_end`),
    KEY `idx_source` (`source_type`, `source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_map_point` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `label` VARCHAR(500) NOT NULL,
    `description` TEXT,
    `latitude` DECIMAL(10,7) NOT NULL,
    `longitude` DECIMAL(10,7) NOT NULL,
    `place_name` VARCHAR(500) DEFAULT NULL,
    `date_valid_from` DATE DEFAULT NULL,
    `date_valid_to` DATE DEFAULT NULL,
    `source_type` VARCHAR(50) DEFAULT NULL,
    `source_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_coords` (`latitude`, `longitude`),
    KEY `idx_source` (`source_type`, `source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 159 PHASE 2d: DATA PACKAGING (ODRL + Access Decisions)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_rights_policy` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `target_type` VARCHAR(50) NOT NULL,
    `target_id` INT NOT NULL,
    `policy_type` ENUM('permission','prohibition','obligation') NOT NULL,
    `action_type` ENUM('use','reproduce','distribute','modify','archive','display') NOT NULL,
    `constraints_json` JSON,
    `policy_json` JSON,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_target` (`target_type`, `target_id`),
    KEY `idx_policy_type` (`policy_type`),
    KEY `idx_action` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_access_decision` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `policy_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `action_requested` VARCHAR(50) NOT NULL,
    `decision` ENUM('permitted','denied') NOT NULL,
    `rationale` TEXT,
    `evaluated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_policy` (`policy_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_decision` (`decision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ISSUE 159: Extend activity_log ENUM for canonical events
-- ============================================================

-- MySQL doesn't support ALTER ENUM cleanly via IF NOT EXISTS, use procedure
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS upgrade_research_phase2_activity_log()
BEGIN
    DECLARE col_type VARCHAR(1000);

    SELECT COLUMN_TYPE INTO col_type
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'research_activity_log'
      AND column_name = 'activity_type';

    IF col_type IS NOT NULL AND col_type NOT LIKE '%snapshot_created%' THEN
        ALTER TABLE `research_activity_log` MODIFY COLUMN `activity_type`
            ENUM('view','search','download','cite','annotate','collect','book','request','export','share','login','logout',
                 'snapshot_created','snapshot_compared','assertion_created','assertion_verified','assertion_disputed',
                 'extraction_queued','extraction_completed','validation_accepted','validation_rejected',
                 'hypothesis_created','hypothesis_updated','policy_evaluated','doi_minted',
                 'create','clipboard_add') DEFAULT 'view';
    END IF;
END//

DELIMITER ;

CALL upgrade_research_phase2_activity_log();
DROP PROCEDURE IF EXISTS upgrade_research_phase2_activity_log;

-- ============================================================
-- ISSUE 159 ENHANCEMENTS: Schema Upgrades
-- ============================================================

DELIMITER //

DROP PROCEDURE IF EXISTS upgrade_research_159_enhancements//

CREATE PROCEDURE upgrade_research_159_enhancements()
BEGIN
    -- ================================================================
    -- Enhancement 1: Snapshot frozen status + frozen_at
    -- ================================================================
    DECLARE snapshot_col_type VARCHAR(1000);

    SELECT COLUMN_TYPE INTO snapshot_col_type
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'research_snapshot'
      AND column_name = 'status';

    IF snapshot_col_type IS NOT NULL AND snapshot_col_type NOT LIKE '%frozen%' THEN
        ALTER TABLE `research_snapshot` MODIFY COLUMN `status`
            ENUM('active','frozen','archived') DEFAULT 'active';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_snapshot' AND column_name = 'frozen_at') THEN
        ALTER TABLE `research_snapshot` ADD COLUMN `frozen_at` DATETIME DEFAULT NULL AFTER `status`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_snapshot' AND column_name = 'citation_id') THEN
        ALTER TABLE `research_snapshot` ADD COLUMN `citation_id` VARCHAR(100) DEFAULT NULL AFTER `frozen_at`;
    END IF;

    -- ================================================================
    -- Enhancement 4: Entity Resolution evidence + sameAs
    -- ================================================================
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_entity_resolution' AND column_name = 'evidence_json') THEN
        ALTER TABLE `research_entity_resolution` ADD COLUMN `evidence_json` JSON DEFAULT NULL AFTER `notes`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_entity_resolution' AND column_name = 'relationship_type') THEN
        ALTER TABLE `research_entity_resolution` ADD COLUMN `relationship_type` VARCHAR(50) DEFAULT 'sameAs' AFTER `evidence_json`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_entity_resolution' AND column_name = 'proposer_id') THEN
        ALTER TABLE `research_entity_resolution` ADD COLUMN `proposer_id` INT DEFAULT NULL AFTER `relationship_type`;
    END IF;

    -- ================================================================
    -- Enhancement 5: Saved Search structured queries + diff + citation
    -- ================================================================
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_saved_search' AND column_name = 'query_ast_json') THEN
        ALTER TABLE `research_saved_search` ADD COLUMN `query_ast_json` JSON DEFAULT NULL AFTER `search_filters`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_saved_search' AND column_name = 'result_snapshot_json') THEN
        ALTER TABLE `research_saved_search` ADD COLUMN `result_snapshot_json` JSON DEFAULT NULL AFTER `query_ast_json`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_saved_search' AND column_name = 'citation_id') THEN
        ALTER TABLE `research_saved_search` ADD COLUMN `citation_id` VARCHAR(100) DEFAULT NULL AFTER `result_snapshot_json`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_saved_search' AND column_name = 'last_result_count') THEN
        ALTER TABLE `research_saved_search` ADD COLUMN `last_result_count` INT DEFAULT NULL AFTER `citation_id`;
    END IF;

END//

DELIMITER ;

CALL upgrade_research_159_enhancements();
DROP PROCEDURE IF EXISTS upgrade_research_159_enhancements;

-- ============================================================
-- RESTORE SETTINGS
-- ============================================================

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- =============================================================================
-- IIIF Research Rooms (Issue #164 Phase 7)
-- Collaborative IIIF viewing/annotation sessions
-- Added: 2026-02-18
-- =============================================================================

CREATE TABLE IF NOT EXISTS `research_room` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `status` ENUM('draft','active','archived') DEFAULT 'draft',
    `created_by` INT NOT NULL,
    `max_participants` INT DEFAULT 10,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_project` (`project_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_room_participant` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id` BIGINT UNSIGNED NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('owner','editor','viewer') DEFAULT 'viewer',
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX `idx_room_user` (`room_id`, `user_id`),
    FOREIGN KEY (`room_id`) REFERENCES `research_room`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_room_manifest` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id` BIGINT UNSIGNED NOT NULL,
    `object_id` INT NOT NULL,
    `manifest_json` LONGTEXT,
    `derivative_type` ENUM('full','subset','annotated') DEFAULT 'full',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_room` (`room_id`),
    FOREIGN KEY (`room_id`) REFERENCES `research_room`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
