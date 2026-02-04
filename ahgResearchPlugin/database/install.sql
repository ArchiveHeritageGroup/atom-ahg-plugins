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
    `delivery_method` ENUM('email','download','post','collect') DEFAULT 'email',
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
    `reproduction_type` ENUM('photocopy','scan','photograph','digital_copy','transcription','certification','other') DEFAULT 'scan',
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
    `activity_type` ENUM('view','search','download','cite','annotate','collect','book','request','export','share','login','logout') NOT NULL,
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
    `digital_object_id` INT DEFAULT NULL,
    `annotation_type` ENUM('note','highlight','bookmark','tag','transcription','correction') DEFAULT 'note',
    `title` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `target_selector` TEXT,
    `canvas_id` VARCHAR(500),
    `iiif_annotation_id` VARCHAR(255),
    `tags` VARCHAR(500) DEFAULT NULL,
    `is_private` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_type` (`annotation_type`)
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
    `role` ENUM('owner','admin','editor','viewer') DEFAULT 'viewer',
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

END//

DELIMITER ;

-- Run the upgrade procedure
CALL upgrade_research_tables();

-- Drop the procedure after use
DROP PROCEDURE IF EXISTS upgrade_research_tables;

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
