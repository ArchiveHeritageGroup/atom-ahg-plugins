-- ============================================================
-- ahgResearchPlugin - Database Schema
-- Version 2.0.0 - Professional Research Support Platform
-- DO NOT include INSERT INTO atom_plugin
-- ============================================================

-- Disable FK checks for the whole install so tables can be created in any order
-- (several tables carry foreign keys to tables defined later in this file).
-- Re-enabled at the very end.
SET FOREIGN_KEY_CHECKS=0;

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
    `verification_type` VARCHAR(121) COMMENT 'id_document, institutional_letter, institutional_email, orcid, staff_approval, professional_membership, other' NOT NULL,
    `document_type` VARCHAR(100),
    `document_reference` VARCHAR(255),
    `document_path` VARCHAR(500),
    `verification_data` JSON,
    `status` VARCHAR(48) COMMENT 'pending, verified, rejected, expired' DEFAULT 'pending',
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
    `affiliation_type` VARCHAR(70) COMMENT 'academic, government, private, independent, student, other' DEFAULT 'independent',
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
    `id_type` VARCHAR(71) COMMENT 'passport, national_id, drivers_license, student_card, other' DEFAULT NULL,
    `id_number` VARCHAR(100) DEFAULT NULL,
    `id_verified` TINYINT(1) DEFAULT 0,
    `id_verified_by` INT DEFAULT NULL,
    `id_verified_at` DATETIME DEFAULT NULL,
    `status` VARCHAR(59) COMMENT 'pending, approved, suspended, expired, rejected' DEFAULT 'pending',
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
    `status` VARCHAR(61) COMMENT 'pending, confirmed, cancelled, completed, no_show' DEFAULT 'pending',
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
    `request_type` VARCHAR(59) COMMENT 'reading_room, reproduction, loan, remote_access' DEFAULT 'reading_room',
    `priority` VARCHAR(30) COMMENT 'normal, high, rush' DEFAULT 'normal',
    `handling_instructions` TEXT,
    `location_code` VARCHAR(100),
    `shelf_location` VARCHAR(255),
    `box_number` VARCHAR(50),
    `folder_number` VARCHAR(50),
    `curatorial_approval_required` TINYINT(1) DEFAULT 0,
    `curatorial_approved_by` INT DEFAULT NULL,
    `curatorial_approved_at` DATETIME DEFAULT NULL,
    `paging_slip_printed` TINYINT(1) DEFAULT 0,
    `status` VARCHAR(74) COMMENT 'requested, retrieved, delivered, in_use, returned, unavailable' DEFAULT 'requested',
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
    `request_type` VARCHAR(34) COMMENT 'material, reproduction' NOT NULL DEFAULT 'material',
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
    `intended_use` VARCHAR(74) COMMENT 'personal, academic, publication, exhibition, commercial, other' DEFAULT 'personal',
    `publication_details` TEXT,
    `status` VARCHAR(95) COMMENT 'draft, submitted, processing, awaiting_payment, in_production, completed, cancelled' DEFAULT 'draft',
    `estimated_cost` DECIMAL(10,2) DEFAULT NULL,
    `final_cost` DECIMAL(10,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'ZAR',
    `payment_reference` VARCHAR(100),
    `payment_date` DATE,
    `payment_method` VARCHAR(50),
    `invoice_number` VARCHAR(50),
    `invoice_date` DATE,
    `delivery_method` VARCHAR(78) COMMENT 'email, download, post, collect, digital, pickup, courier, physical' DEFAULT 'email',
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
    `reproduction_type` VARCHAR(131) COMMENT 'photocopy, scan, photograph, digital_copy, digital_scan, transcription, certification, certified_copy, microfilm, other' DEFAULT 'scan',
    `format` VARCHAR(50) DEFAULT 'PDF',
    `resolution` VARCHAR(50),
    `color_mode` VARCHAR(32) COMMENT 'color, grayscale, bw' DEFAULT 'grayscale',
    `quantity` INT DEFAULT 1,
    `page_range` VARCHAR(100),
    `special_instructions` TEXT,
    `unit_price` DECIMAL(10,2),
    `total_price` DECIMAL(10,2),
    `status` VARCHAR(54) COMMENT 'pending, in_progress, completed, cancelled' DEFAULT 'pending',
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
    `project_type` VARCHAR(113) COMMENT 'thesis, dissertation, publication, exhibition, documentary, genealogy, institutional, personal, other' DEFAULT 'personal',
    `institution` VARCHAR(255),
    `supervisor` VARCHAR(255),
    `funding_source` VARCHAR(255),
    `grant_number` VARCHAR(100),
    `ethics_approval` VARCHAR(100),
    `start_date` DATE,
    `expected_end_date` DATE,
    `actual_end_date` DATE,
    `status` VARCHAR(58) COMMENT 'planning, active, on_hold, completed, archived' DEFAULT 'planning',
    `visibility` VARCHAR(42) COMMENT 'private, collaborators, public' DEFAULT 'private',
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
    `role` VARCHAR(46) COMMENT 'owner, editor, contributor, viewer' DEFAULT 'contributor',
    `permissions` JSON,
    `invited_by` INT,
    `invited_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `accepted_at` DATETIME,
    `status` VARCHAR(48) COMMENT 'pending, accepted, declined, removed' DEFAULT 'pending',
    `notes` TEXT,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_project_researcher` (`project_id`, `researcher_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_project_resource` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `resource_type` VARCHAR(101) COMMENT 'collection, saved_search, annotation, bibliography, object, external_link, document, note' NOT NULL,
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
    `status` VARCHAR(54) COMMENT 'pending, in_progress, completed, cancelled' DEFAULT 'pending',
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
    `activity_type` VARCHAR(127) COMMENT 'view, search, download, cite, annotate, collect, book, request, export, share, login, logout, create, clipboard_add' NOT NULL,
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
    `annotation_type` VARCHAR(69) COMMENT 'note, highlight, bookmark, tag, transcription, correction' DEFAULT 'note',
    `title` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `content_format` VARCHAR(22) COMMENT 'text, html' DEFAULT 'text',
    `target_selector` TEXT,
    `canvas_id` VARCHAR(500),
    `iiif_annotation_id` VARCHAR(255),
    `tags` VARCHAR(500) DEFAULT NULL,
    `is_private` TINYINT(1) DEFAULT 1,
    `visibility` VARCHAR(35) COMMENT 'private, shared, public' DEFAULT 'private',
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
    `alert_frequency` VARCHAR(44) COMMENT 'realtime, daily, weekly, monthly' DEFAULT 'weekly',
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
    `entry_type` VARCHAR(68) COMMENT 'archival, book, article, chapter, thesis, website, other' DEFAULT 'archival',
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
    `visibility` VARCHAR(36) COMMENT 'private, members, public' DEFAULT 'private',
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
    `role` VARCHAR(61) COMMENT 'owner, admin, editor, viewer, member, contributor' DEFAULT 'viewer',
    `invited_by` INT,
    `invited_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `accepted_at` DATETIME,
    `status` VARCHAR(48) COMMENT 'pending, accepted, declined, removed' DEFAULT 'pending',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_workspace_researcher` (`workspace_id`, `researcher_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_workspace_resource` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `workspace_id` INT NOT NULL,
    `resource_type` VARCHAR(75) COMMENT 'collection, project, bibliography, saved_search, document, link' NOT NULL,
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
  `content_format` VARCHAR(22) COMMENT 'text, html' DEFAULT 'html',
  `entry_type` VARCHAR(117) COMMENT 'manual, auto_booking, auto_material, auto_annotation, auto_search, auto_collection, reflection, milestone' DEFAULT 'manual',
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
  `template_type` VARCHAR(92) COMMENT 'research_summary, genealogical, historical, source_analysis, finding_aid, custom' DEFAULT 'custom',
  `description` TEXT,
  `status` VARCHAR(59) COMMENT 'draft, in_progress, review, completed, archived' DEFAULT 'draft',
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
  `section_type` VARCHAR(108) COMMENT 'title_page, toc, heading, text, bibliography, collection_list, annotation_list, timeline, custom' DEFAULT 'text',
  `title` VARCHAR(500),
  `content` TEXT,
  `content_format` VARCHAR(22) COMMENT 'text, html' DEFAULT 'html',
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
  `type` VARCHAR(78) COMMENT 'alert, invitation, comment, reply, system, reminder, collaboration' NOT NULL,
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
  `digest_frequency` VARCHAR(42) COMMENT 'immediate, daily, weekly, none' DEFAULT 'immediate',
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
  `share_type` VARCHAR(34) COMMENT 'view, contribute, full' DEFAULT 'view',
  `shared_by` INT NOT NULL,
  `accepted_by` INT DEFAULT NULL,
  `status` VARCHAR(45) COMMENT 'pending, active, revoked, expired' DEFAULT 'pending',
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
  `role` VARCHAR(31) COMMENT 'viewer, contributor' DEFAULT 'viewer',
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
  `entity_type` VARCHAR(73) COMMENT 'report, report_section, annotation, journal_entry, collection' NOT NULL,
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
  `status` VARCHAR(53) COMMENT 'pending, in_progress, completed, declined' DEFAULT 'pending',
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
        ALTER TABLE `research_material_request` ADD COLUMN `request_type` VARCHAR(59) COMMENT 'reading_room, reproduction, loan, remote_access' DEFAULT 'reading_room' AFTER `notes`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'priority') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `priority` VARCHAR(30) COMMENT 'normal, high, rush' DEFAULT 'normal' AFTER `request_type`;
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
        ALTER TABLE `research_annotation` ADD COLUMN `content_format` VARCHAR(22) COMMENT 'text, html' DEFAULT 'text' AFTER `content`;
    END IF;

    -- Issue 149: visibility for note sharing
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_annotation' AND column_name = 'visibility') THEN
        ALTER TABLE `research_annotation` ADD COLUMN `visibility` VARCHAR(35) COMMENT 'private, shared, public' DEFAULT 'private' AFTER `is_private`;
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
    `status` VARCHAR(28) COMMENT 'active, archived' DEFAULT 'active',
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
    `status` VARCHAR(49) COMMENT 'proposed, testing, supported, refuted' DEFAULT 'proposed',
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
    `relationship` VARCHAR(38) COMMENT 'supports, refutes, neutral' NOT NULL,
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
    `source_type` VARCHAR(40) COMMENT 'primary, secondary, tertiary' NOT NULL,
    `source_form` VARCHAR(68) COMMENT 'born_digital, scan, original, transcription, translation' DEFAULT 'original',
    `completeness` VARCHAR(64) COMMENT 'complete, partial, fragment, missing_pages, redacted' DEFAULT 'complete',
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
    `metric_type` VARCHAR(83) COMMENT 'ocr_confidence, image_quality, digitisation_completeness, fixity_status' NOT NULL,
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
    `motivation` VARCHAR(92) COMMENT 'commenting, describing, classifying, linking, questioning, tagging, highlighting' NOT NULL DEFAULT 'commenting',
    `body_json` JSON,
    `creator_json` JSON,
    `generated_json` JSON,
    `status` VARCHAR(37) COMMENT 'active, archived, deleted' DEFAULT 'active',
    `visibility` VARCHAR(35) COMMENT 'private, shared, public' DEFAULT 'private',
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
    `selector_type` VARCHAR(104) COMMENT 'TextQuoteSelector, FragmentSelector, SvgSelector, PointSelector, RangeSelector, TimeSelector' DEFAULT NULL,
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
    `assertion_type` VARCHAR(73) COMMENT 'biographical, chronological, spatial, relational, attributive' NOT NULL,
    `status` VARCHAR(51) COMMENT 'proposed, verified, disputed, retracted' DEFAULT 'proposed',
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
    `relationship` VARCHAR(29) COMMENT 'supports, refutes' NOT NULL,
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
    `extraction_type` VARCHAR(87) COMMENT 'ocr, ner, summarize, translate, spellcheck, face_detection, form_extraction' NOT NULL,
    `parameters_json` JSON,
    `status` VARCHAR(46) COMMENT 'queued, running, completed, failed' DEFAULT 'queued',
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
    `result_type` VARCHAR(73) COMMENT 'entity, summary, translation, transcription, form_field, face' NOT NULL,
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
    `status` VARCHAR(49) COMMENT 'pending, accepted, rejected, modified' DEFAULT 'pending',
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
    `status` VARCHAR(40) COMMENT 'proposed, accepted, rejected' DEFAULT 'proposed',
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
    `date_type` VARCHAR(51) COMMENT 'event, creation, accession, publication' DEFAULT 'event',
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
    `policy_type` VARCHAR(47) COMMENT 'permission, prohibition, obligation' NOT NULL,
    `action_type` VARCHAR(64) COMMENT 'use, reproduce, distribute, modify, archive, display' NOT NULL,
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
    `decision` VARCHAR(29) COMMENT 'permitted, denied' NOT NULL,
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
            VARCHAR(376) COMMENT 'view, search, download, cite, annotate, collect, book, request, export, share, login, logout, snapshot_created, snapshot_compared, assertion_created, assertion_verified, assertion_disputed, extraction_queued, extraction_completed, validation_accepted, validation_rejected, hypothesis_created, hypothesis_updated, policy_evaluated, doi_minted, create, clipboard_add' DEFAULT 'view';
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
            VARCHAR(36) COMMENT 'active, frozen, archived' DEFAULT 'active';
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
    `status` VARCHAR(35) COMMENT 'draft, active, archived' DEFAULT 'draft',
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
    `role` VARCHAR(33) COMMENT 'owner, editor, viewer' DEFAULT 'viewer',
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX `idx_room_user` (`room_id`, `user_id`),
    FOREIGN KEY (`room_id`) REFERENCES `research_room`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_room_manifest` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id` BIGINT UNSIGNED NOT NULL,
    `object_id` INT NOT NULL,
    `manifest_json` LONGTEXT,
    `derivative_type` VARCHAR(35) COMMENT 'full, subset, annotated' DEFAULT 'full',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_room` (`room_id`),
    FOREIGN KEY (`room_id`) REFERENCES `research_room`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_equipment_maintenance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_id` INT NOT NULL,
    `description` TEXT,
    `condition_before` VARCHAR(50),
    `condition_after` VARCHAR(50),
    `next_maintenance_date` DATE,
    `performed_by` INT,
    `performed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_equipment` (`equipment_id`),
    FOREIGN KEY (`equipment_id`) REFERENCES `research_equipment`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Dropdown Manager seed data (ahg_dropdown)
-- ============================================================

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('seat_type', 'Seat Type', 'standard', 'Regular desk/table', 10),
('seat_type', 'Seat Type', 'accessible', 'Wheelchair accessible', 20),
('seat_type', 'Seat Type', 'computer', 'With workstation', 30),
('seat_type', 'Seat Type', 'microfilm', 'Microfilm reader', 40),
('seat_type', 'Seat Type', 'oversize', 'Large format materials', 50),
('seat_type', 'Seat Type', 'quiet', 'Silent study zone', 60),
('seat_type', 'Seat Type', 'group', 'Group/collaborative', 70);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('booking_status', 'Booking Status', 'pending', 'Pending', 10),
('booking_status', 'Booking Status', 'confirmed', 'Confirmed', 20),
('booking_status', 'Booking Status', 'checked_in', 'Checked In', 30),
('booking_status', 'Booking Status', 'checked_out', 'Checked Out', 40),
('booking_status', 'Booking Status', 'completed', 'Completed', 50),
('booking_status', 'Booking Status', 'cancelled', 'Cancelled', 60),
('booking_status', 'Booking Status', 'no_show', 'No Show', 70);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('researcher_status', 'Researcher Status', 'pending', 'Pending', 10),
('researcher_status', 'Researcher Status', 'approved', 'Approved', 20),
('researcher_status', 'Researcher Status', 'suspended', 'Suspended', 30),
('researcher_status', 'Researcher Status', 'expired', 'Expired', 40),
('researcher_status', 'Researcher Status', 'rejected', 'Rejected', 50);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('researcher_type', 'Researcher Type', 'academic', 'Academic Researcher', 10),
('researcher_type', 'Researcher Type', 'independent', 'Independent Researcher', 20),
('researcher_type', 'Researcher Type', 'student', 'Student', 30),
('researcher_type', 'Researcher Type', 'government', 'Government Official', 40),
('researcher_type', 'Researcher Type', 'journalist', 'Journalist', 50),
('researcher_type', 'Researcher Type', 'genealogist', 'Genealogist', 60),
('researcher_type', 'Researcher Type', 'legal', 'Legal Professional', 70),
('researcher_type', 'Researcher Type', 'other', 'Other', 80);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('material_request_status', 'Material Request Status', 'pending', 'Pending', 10),
('material_request_status', 'Material Request Status', 'approved', 'Approved', 20),
('material_request_status', 'Material Request Status', 'retrieved', 'Retrieved', 30),
('material_request_status', 'Material Request Status', 'delivered', 'Delivered', 40),
('material_request_status', 'Material Request Status', 'returned', 'Returned', 50),
('material_request_status', 'Material Request Status', 'denied', 'Denied', 60);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('reproduction_type', 'Reproduction Type', 'digital_scan', 'Digital Scan', 10),
('reproduction_type', 'Reproduction Type', 'photocopy', 'Photocopy', 20),
('reproduction_type', 'Reproduction Type', 'photograph', 'Photograph', 30),
('reproduction_type', 'Reproduction Type', 'certified_copy', 'Certified Copy', 40),
('reproduction_type', 'Reproduction Type', 'microfilm', 'Microfilm', 50);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('reproduction_request_status', 'Reproduction Request Status', 'draft', 'Draft', 10),
('reproduction_request_status', 'Reproduction Request Status', 'submitted', 'Submitted', 20),
('reproduction_request_status', 'Reproduction Request Status', 'processing', 'Processing', 30),
('reproduction_request_status', 'Reproduction Request Status', 'completed', 'Completed', 40),
('reproduction_request_status', 'Reproduction Request Status', 'cancelled', 'Cancelled', 50);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('equipment_type', 'Equipment Type', 'microfilm_reader', 'Microfilm Reader', 10),
('equipment_type', 'Equipment Type', 'microfiche_reader', 'Microfiche Reader', 20),
('equipment_type', 'Equipment Type', 'scanner', 'Scanner', 30),
('equipment_type', 'Equipment Type', 'computer', 'Computer Workstation', 40),
('equipment_type', 'Equipment Type', 'laptop', 'Laptop', 50),
('equipment_type', 'Equipment Type', 'magnifier', 'Magnifier/Loupe', 60),
('equipment_type', 'Equipment Type', 'book_cradle', 'Book Cradle', 60),
('equipment_type', 'Equipment Type', 'light_box', 'Light Box', 70),
('equipment_type', 'Equipment Type', 'camera_stand', 'Camera Stand', 80),
('equipment_type', 'Equipment Type', 'projector', 'Projector', 90),
('equipment_type', 'Equipment Type', 'gloves', 'Gloves', 90),
('equipment_type', 'Equipment Type', 'audio_player', 'Audio Player', 100),
('equipment_type', 'Equipment Type', 'weights', 'Weights', 100),
('equipment_type', 'Equipment Type', 'video_player', 'Video Player', 110),
('equipment_type', 'Equipment Type', 'other', 'Other', 200);

-- ============================================================================
-- 2026-05-16: RESEARCH ENHANCEMENTS (spec: docs/atom-heratio-research-enhancements-spec.md)
-- Studio artefacts, notebooks, cross-fonds queries, collab presence,
-- ORCID link store, offline sync audit.
-- (See database/migrations/2026_05_16_research_enhancements.sql for the same DDL.)
-- ============================================================================
-- ============================================================================
-- ahgResearchPlugin - Research Enhancements (2026-05-16)
-- Spec: docs/atom-heratio-research-enhancements-spec.md
-- 8 new tables for: Studio artefacts, notebooks, cross-fonds queries,
--                   collaboration presence, ORCID links, offline sync.
--
-- Tables intentionally omitted (already exist):
--   research_evidence_comment -> use research_comment (polymorphic)
--   research_annotation       -> project_id + visibility already added
-- ============================================================================

-- §1.1 - §1.3 Studio artefacts
CREATE TABLE IF NOT EXISTS `research_studio_artefact` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `created_by` INT NULL,
    `output_type` VARCHAR(40) NOT NULL COMMENT 'briefing, study_guide, faq, timeline, diagram, video_script, spreadsheet, audio',
    `title` VARCHAR(500) NULL,
    `body` MEDIUMTEXT,
    `body_format` VARCHAR(20) DEFAULT 'markdown' COMMENT 'markdown, html, json, mermaid, csv',
    `source_object_ids` JSON NULL COMMENT 'IO ids the artefact was synthesised from',
    `citations` JSON NULL COMMENT 'list of {n, object_id, title, snippet, url} backing each [N] marker',
    `model` VARCHAR(120) NULL,
    `tokens_used` INT DEFAULT 0,
    `generation_time_ms` INT NULL,
    `audio_url` VARCHAR(500) NULL,
    `audio_digital_object_id` INT NULL,
    `audio_duration_seconds` INT NULL,
    `audio_transcript` MEDIUMTEXT,
    `xlsx_path` VARCHAR(500) NULL,
    `status` VARCHAR(20) DEFAULT 'ready' COMMENT 'pending, generating, ready, error',
    `error_text` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_output_type` (`output_type`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §1.5 Researcher notebooks
CREATE TABLE IF NOT EXISTS `research_notebook` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `summary` TEXT,
    `cover_object_id` INT NULL,
    `promoted_to_project_id` INT NULL,
    `promoted_at` DATETIME NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_promoted` (`promoted_to_project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_notebook_item` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `notebook_id` INT NOT NULL,
    `item_type` VARCHAR(30) NOT NULL COMMENT 'saved_query, ai_output, source_pin, note',
    `title` VARCHAR(500) NULL,
    `body` MEDIUMTEXT,
    `source_object_id` INT NULL,
    `saved_search_id` INT NULL,
    `ai_output_payload` JSON NULL,
    `pinned` TINYINT(1) DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notebook` (`notebook_id`),
    KEY `idx_item_type` (`item_type`),
    KEY `idx_source_object` (`source_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §1.6 Cross-fonds queries
CREATE TABLE IF NOT EXISTS `research_cross_fonds_query` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NULL,
    `query_text` VARCHAR(1000) NOT NULL,
    `fonds_ids` JSON NULL,
    `results_count` INT DEFAULT 0,
    `elapsed_ms` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §2.3 Real-time collaboration
CREATE TABLE IF NOT EXISTS `research_collaboration_session` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `started_by` INT NOT NULL,
    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `ended_at` DATETIME NULL,
    `expires_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_active` (`project_id`, `ended_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_collaboration_presence` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `session_id` INT NULL,
    `cursor_target` VARCHAR(200) NULL COMMENT 'route+anchor that identifies what the collaborator is viewing',
    `user_color` VARCHAR(7) NULL COMMENT '#rrggbb assigned for this session',
    `last_seen_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_project_researcher` (`project_id`, `researcher_id`),
    KEY `idx_session` (`session_id`),
    KEY `idx_last_seen` (`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §2.4 ORCID link
-- (Existing OrcidService writes some columns onto research_researcher; this table
--  is the canonical token + sync metadata store described by the spec.)
CREATE TABLE IF NOT EXISTS `research_orcid_link` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `orcid_id` VARCHAR(19) NOT NULL,
    `access_token_encrypted` TEXT,
    `refresh_token_encrypted` TEXT,
    `scope` VARCHAR(200) NULL,
    `expires_at` DATETIME NULL,
    `last_synced_at` DATETIME NULL,
    `last_works_count` INT NULL,
    `last_error` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_researcher` (`researcher_id`),
    UNIQUE KEY `uniq_orcid` (`orcid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §2.7 Offline sync audit
CREATE TABLE IF NOT EXISTS `research_offline_sync_log` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `sync_started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `sync_completed_at` DATETIME NULL,
    `queued_count` INT DEFAULT 0,
    `applied_count` INT DEFAULT 0,
    `conflict_count` INT DEFAULT 0,
    `payload_hash` VARCHAR(64) NULL,
    `error_text` TEXT,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_started` (`sync_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- §3 Training curriculum + LMS (#117) — folded in from training.sql so a base
-- install.sql run creates them. Previously these lived only in the standalone
-- training.sql, so a fresh install (which runs install.sql) left them out,
-- causing 500 "Table 'training_course' doesn't exist" on /research/training.
-- Mirrors the Heratio table names exactly. CREATE TABLE IF NOT EXISTS = safe to
-- re-run on instances where training.sql was already applied.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `training_course` (
  `id` int NOT NULL AUTO_INCREMENT,
  `researcher_id` int DEFAULT NULL COMMENT 'FK researcher (course author), nullable',
  `title` varchar(255) NOT NULL,
  `description` text,
  `audience` varchar(255) DEFAULT NULL COMMENT 'audience / role the course targets (data, not hard-coded)',
  `language` varchar(40) DEFAULT NULL COMMENT 'course language code/name (data)',
  `pass_mark` int NOT NULL DEFAULT 80 COMMENT 'default pass mark percentage 0-100',
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, published, archived',
  `sort_order` int DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_course_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_module` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `lecture_id` int DEFAULT NULL COMMENT 'FK research_lecture (curriculum lecture, #116) - degrade gracefully if absent',
  `body_markdown` mediumtext COMMENT 'own Markdown content when no lecture reused',
  `body_html` mediumtext COMMENT 'rendered HTML cache of body_markdown',
  `sort_order` int DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_module_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_assessment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `pass_mark` int DEFAULT NULL COMMENT 'overrides course pass_mark when set (0-100)',
  `questions_json` longtext COMMENT 'JSON [{q, options:[...], answer:index}]',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_assessment_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_enrolment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `learner_name` varchar(255) DEFAULT NULL,
  `learner_email` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'enrolled' COMMENT 'enrolled, in_progress, completed',
  `score` int DEFAULT NULL COMMENT 'best assessment score percentage',
  `enrolled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_enrolment_course` (`course_id`),
  KEY `idx_training_enrolment_user` (`user_id`),
  KEY `idx_training_enrolment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enrolment_id` int NOT NULL,
  `module_id` int NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_training_progress_enrol_module` (`enrolment_id`,`module_id`),
  KEY `idx_training_progress_enrol` (`enrolment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_certificate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enrolment_id` int NOT NULL,
  `certificate_no` varchar(40) NOT NULL,
  `score` int DEFAULT NULL,
  `issued_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_training_certificate_no` (`certificate_no`),
  KEY `idx_training_certificate_enrol` (`enrolment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- §4 Remaining research tables (folded in from migrations/* and the supplementary
--    SQL files: lecture.sql, journal_builder.sql, dmp.sql, target_journals.sql, etc.)
--    so a single install.sql run reproduces the full Research Portal schema that
--    the reference (PSIS/archive) instance runs. Structure captured from archive.
--    FK checks disabled around this block so table create order can't break a run.
-- =============================================================================
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE IF NOT EXISTS `research_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `activity_type` varchar(86) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'class, tour, exhibit, loan, conservation, photography, filming, event, meeting, other',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `organizer_id` int DEFAULT NULL COMMENT 'Researcher ID if registered',
  `organizer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organizer_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organizer_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organization` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expected_attendees` int DEFAULT NULL,
  `reading_room_id` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `recurring` tinyint(1) DEFAULT '0',
  `recurrence_pattern` json DEFAULT NULL,
  `setup_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `av_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `catering_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `special_instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(71) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'requested' COMMENT 'requested, tentative, confirmed, in_progress, completed, cancelled',
  `confirmed_by` int DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `cancelled_by` int DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`activity_type`),
  KEY `idx_organizer` (`organizer_id`),
  KEY `idx_room` (`reading_room_id`),
  KEY `idx_date` (`start_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_activity_material` (
  `id` int NOT NULL AUTO_INCREMENT,
  `activity_id` int NOT NULL,
  `object_id` int NOT NULL,
  `purpose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `handling_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `display_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'For exhibits',
  `insurance_value` decimal(15,2) DEFAULT NULL,
  `loan_agreement_signed` tinyint(1) DEFAULT '0',
  `condition_before` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `condition_after` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(71) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'requested' COMMENT 'requested, approved, rejected, retrieved, in_use, returned, damaged',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `retrieved_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity` (`activity_id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_activity_participant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `activity_id` int NOT NULL,
  `researcher_id` int DEFAULT NULL COMMENT 'If registered researcher',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organization` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(78) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'visitor' COMMENT 'organizer, instructor, presenter, student, visitor, assistant, staff, other',
  `dietary_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `accessibility_needs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `registration_status` varchar(63) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, confirmed, waitlist, cancelled, attended, no_show',
  `registered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` datetime DEFAULT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_activity` (`activity_id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`registration_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_ai_disclosure_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `tool` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `model` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `output_ref` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logged_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radl_project` (`project_id`),
  KEY `radl_project_created` (`project_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_analysis_code` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `kind` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'theme_tag' COMMENT 'theme_tag, memo',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rac_project` (`project_id`),
  KEY `idx_rac_kind` (`kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_analysis_result` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `result_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other' COMMENT 'chart, table, theme, statistic, other',
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_data_ref` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'what data this result was produced from (dataset name, query, collection, file)',
  `source_data_version` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'version / snapshot / date of the source data',
  `method` text COLLATE utf8mb4_unicode_ci COMMENT 'the analytical method / technique applied',
  `code_ref` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'where the code/notebook/script lives (repo URL, notebook name, file path)',
  `generated_at` datetime DEFAULT NULL COMMENT 'when the external result was produced',
  `researcher_decision` text COLLATE utf8mb4_unicode_ci COMMENT 'the human decision/interpretation drawn from this result',
  `artifact_path` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'path RELATIVE to config(heratio.storage_path); never an absolute path',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `ai_model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_at` datetime DEFAULT NULL,
  `ai_decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_decided_at` datetime DEFAULT NULL,
  `ai_decided_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rar_project` (`project_id`),
  KEY `idx_rar_type` (`result_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_analysis_result_claim` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `result_id` bigint unsigned NOT NULL,
  `assertion_id` int NOT NULL,
  `relationship` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'supports' COMMENT 'supports, weakens, contextualises',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_rarc` (`result_id`,`assertion_id`),
  KEY `idx_rarc_result` (`result_id`),
  KEY `idx_rarc_assertion` (`assertion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_argument` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `central_thesis` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ai_model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_at` datetime DEFAULT NULL,
  `ai_decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_decided_at` datetime DEFAULT NULL,
  `ai_decided_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ra_project_idx` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_argument_step` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `argument_id` bigint unsigned NOT NULL,
  `slot` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assertion_id` int DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ras_argument_idx` (`argument_id`),
  KEY `ras_assertion_idx` (`assertion_id`),
  KEY `ras_sort_idx` (`argument_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_claim_meta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assertion_id` int NOT NULL,
  `evidence_type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confidence_level` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provenance_kind` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT 'original',
  `supporting_sources` text COLLATE utf8mb4_unicode_ci,
  `opposing_sources` text COLLATE utf8mb4_unicode_ci,
  `quotations` mediumtext COLLATE utf8mb4_unicode_ci,
  `method_theory_link` text COLLATE utf8mb4_unicode_ci,
  `researcher_notes` text COLLATE utf8mb4_unicode_ci,
  `unresolved_weaknesses` text COLLATE utf8mb4_unicode_ci,
  `ethical_concerns` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_assertion_uniq` (`assertion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_contradiction` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `claim_a_id` int NOT NULL,
  `claim_b_id` int DEFAULT NULL,
  `kind` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ai_flagged',
  `signature` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `detail` text COLLATE utf8mb4_unicode_ci,
  `severity` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `source` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'heuristic',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rc_signature_uniq` (`project_id`,`signature`),
  KEY `rc_project_status` (`project_id`,`status`),
  KEY `rc_claim_a` (`claim_a_id`),
  KEY `rc_claim_b` (`claim_b_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_copilot_answer` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` int NOT NULL,
  `researcher_id` int DEFAULT NULL,
  `question` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sources_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `ai_model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_at` datetime DEFAULT NULL,
  `ai_decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_decided_at` datetime DEFAULT NULL,
  `ai_decided_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rca_workspace_idx` (`workspace_id`),
  KEY `rca_researcher_idx` (`researcher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_custody_handoff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `material_request_id` int NOT NULL,
  `handoff_type` varchar(50) NOT NULL COMMENT 'checkout|checkin|transfer|return_to_storage|condition_check',
  `from_handler_id` int DEFAULT NULL COMMENT 'Staff/researcher releasing',
  `to_handler_id` int DEFAULT NULL COMMENT 'Staff/researcher receiving',
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `condition_at_handoff` varchar(50) DEFAULT NULL COMMENT 'excellent|good|fair|poor|critical',
  `condition_notes` text,
  `signature_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` int DEFAULT NULL,
  `barcode_scanned` varchar(100) DEFAULT NULL,
  `spectrum_movement_id` int DEFAULT NULL COMMENT 'FK to spectrum_movement if created',
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`material_request_id`),
  KEY `idx_type` (`handoff_type`),
  KEY `idx_spectrum` (`spectrum_movement_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
CREATE TABLE IF NOT EXISTS `research_decision_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `decision_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `summary` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `related_ref` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decided_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rdl_project_idx` (`project_id`),
  KEY `rdl_type_idx` (`decision_type`),
  KEY `rdl_project_decided_idx` (`project_id`,`decided_at`),
  CONSTRAINT `rdl_project_fk` FOREIGN KEY (`project_id`) REFERENCES `research_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_dmp` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `researcher_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL COMMENT 'Optional research_project.id (logical FK)',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `funder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grant_number` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft, active, final',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0',
  `data_description` text COLLATE utf8mb4_unicode_ci COMMENT '1. Data summary / description',
  `fair_findable` text COLLATE utf8mb4_unicode_ci COMMENT '2a. Making data findable',
  `fair_accessible` text COLLATE utf8mb4_unicode_ci COMMENT '2b. Making data accessible',
  `fair_interoperable` text COLLATE utf8mb4_unicode_ci COMMENT '2c. Making data interoperable',
  `fair_reusable` text COLLATE utf8mb4_unicode_ci COMMENT '2d. Increasing data re-use',
  `resources_costs` text COLLATE utf8mb4_unicode_ci COMMENT '3. Allocation of resources',
  `data_security` text COLLATE utf8mb4_unicode_ci COMMENT '4. Data security',
  `ethics_legal` text COLLATE utf8mb4_unicode_ci COMMENT '5. Ethical aspects',
  `other_issues` text COLLATE utf8mb4_unicode_ci COMMENT '6. Other issues',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_dmp_dataset` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dmp_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `data_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. images, interviews, survey, geospatial',
  `formats` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File formats',
  `est_volume` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Estimated volume e.g. 20 GB',
  `sensitivity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open' COMMENT 'open, restricted, sensitive',
  `personal_data` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Contains personal data',
  `license` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repository` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Target repository for sharing/preservation',
  `retention_period` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sharing_policy` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dmp` (`dmp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reading_room_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipment_type` varchar(127) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'microfilm_reader, microfiche_reader, scanner, computer, magnifier, book_cradle, light_box, camera_stand, gloves, weights, other',
  `brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Where in the reading room',
  `requires_training` tinyint(1) DEFAULT '0',
  `max_booking_hours` int DEFAULT '4',
  `booking_increment_minutes` int DEFAULT '30',
  `condition_status` varchar(57) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'good' COMMENT 'excellent, good, fair, needs_repair, out_of_service',
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_room` (`reading_room_id`),
  KEY `idx_type` (`equipment_type`),
  KEY `idx_available` (`is_available`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_equipment_booking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int DEFAULT NULL COMMENT 'Linked reading room booking',
  `researcher_id` int NOT NULL,
  `equipment_id` int NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(52) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'reserved' COMMENT 'reserved, in_use, returned, cancelled, no_show',
  `checked_out_at` datetime DEFAULT NULL,
  `checked_out_by` int DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `returned_by` int DEFAULT NULL,
  `condition_on_return` varchar(37) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'excellent, good, fair, damaged',
  `return_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_equipment` (`equipment_id`),
  KEY `idx_date` (`booking_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_ethics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `title` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approval_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'human_subjects' COMMENT 'from ahg_dropdown research_ethics_approval_type: human_subjects, animal, data_protection, biosafety, other',
  `reference_number` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the committee or body reference / protocol number',
  `committee_name` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'name of the ethics committee, review board or governance body - free-text DATA, no jurisdiction assumed',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'from ahg_dropdown research_ethics_status: not_required, pending, approved, conditions, expired, rejected',
  `decision_date` date DEFAULT NULL COMMENT 'date the decision was issued',
  `expiry_date` date DEFAULT NULL COMMENT 'date the approval lapses; drives the expiring-soon flag',
  `consent_basis` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'informed_consent' COMMENT 'from ahg_dropdown research_consent_basis: informed_consent, legitimate_interest, public_task, anonymised, not_applicable - GENERIC governance concepts, never one law''s terms',
  `data_sensitivity` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none' COMMENT 'from ahg_dropdown research_data_sensitivity: none, personal, special_category, restricted',
  `notes` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'free-text notes about the approval, conditions or consent arrangements',
  `dmp_id` bigint unsigned DEFAULT NULL COMMENT 'FK-by-convention to research_dmp.id (sibling slice) - the plan that governs this data',
  `owner_id` int DEFAULT NULL COMMENT 'research_researcher.id - the record owner',
  `created_by` int DEFAULT NULL COMMENT 'research_researcher.id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reth_project_idx` (`project_id`),
  KEY `reth_type_idx` (`approval_type`),
  KEY `reth_status_idx` (`status`),
  KEY `reth_expiry_idx` (`expiry_date`),
  KEY `reth_dmp_idx` (`dmp_id`),
  KEY `reth_owner_idx` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_export_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `format` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zip' COMMENT 'zip, markdown, json, bibtex, ris, csl',
  `exported_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'human-readable name or email of the exporter',
  `exported_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rel_project_idx` (`project_id`),
  KEY `rel_format_idx` (`format`),
  KEY `rel_project_at_idx` (`project_id`,`exported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_field_alert` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `watch_id` bigint unsigned DEFAULT NULL,
  `alert_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'update',
  `title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci,
  `url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `detected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rfa_project_idx` (`project_id`),
  KEY `rfa_type_idx` (`alert_type`),
  KEY `rfa_project_read_idx` (`project_id`,`is_read`),
  KEY `rfa_watch_idx` (`watch_id`),
  CONSTRAINT `rfa_project_fk` FOREIGN KEY (`project_id`) REFERENCES `research_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_field_watch` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `doi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `added_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_checked_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rfw_project_idx` (`project_id`),
  KEY `rfw_project_doi_idx` (`project_id`,`doi`),
  CONSTRAINT `rfw_project_fk` FOREIGN KEY (`project_id`) REFERENCES `research_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_funding` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `title` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'a short label for this funding line, e.g. the award or programme name',
  `funder_name` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'the funding body - free-text DATA, no jurisdiction or country assumed',
  `funder_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other' COMMENT 'from ahg_dropdown research_funder_type: government, research_council, foundation, charity, industry, internal, other',
  `award_reference` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the funder grant / award reference number',
  `amount` decimal(14,2) DEFAULT NULL COMMENT 'the awarded / requested amount, stored as exact decimal (never a float)',
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD' COMMENT 'ISO 4217 code from ahg_dropdown research_currency - NO single currency is canonical; the schema default is a neutral placeholder only',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'applied' COMMENT 'from ahg_dropdown research_funding_status: applied, awarded, active, completed, declined',
  `start_date` date DEFAULT NULL COMMENT 'start of the award period; drives the active-now indicator',
  `end_date` date DEFAULT NULL COMMENT 'end of the award period; drives the active-now indicator',
  `notes` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'free-text notes about the funding line, conditions or reporting obligations',
  `dmp_id` bigint unsigned DEFAULT NULL COMMENT 'FK-by-convention to research_dmp.id (sibling slice) - the plan whose data this funding supports',
  `owner_id` int DEFAULT NULL COMMENT 'research_researcher.id - the record owner',
  `created_by` int DEFAULT NULL COMMENT 'research_researcher.id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rfund_project_idx` (`project_id`),
  KEY `rfund_type_idx` (`funder_type`),
  KEY `rfund_status_idx` (`status`),
  KEY `rfund_currency_idx` (`currency`),
  KEY `rfund_dates_idx` (`start_date`,`end_date`),
  KEY `rfund_dmp_idx` (`dmp_id`),
  KEY `rfund_owner_idx` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_grant_call` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `researcher_id` int DEFAULT NULL COMMENT 'research_researcher.id - owner of the watch',
  `project_id` int DEFAULT NULL COMMENT 'optional project this call is being tracked against',
  `funder` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'watching' COMMENT 'from ahg_dropdown grant_call_status: watching, preparing, submitted, awarded, declined, closed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rgc_researcher_idx` (`researcher_id`),
  KEY `rgc_project_idx` (`project_id`),
  KEY `rgc_status_idx` (`status`),
  KEY `rgc_deadline_idx` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_grant_draft` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `funder_template` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ahg_dropdown grant_funder_template.code, e.g. generic, nrf, erc, nih, wellcome',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'from ahg_dropdown grant_draft_status: draft, in_review, ready, submitted',
  `created_by` int DEFAULT NULL COMMENT 'research_researcher.id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ai_model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_at` datetime DEFAULT NULL,
  `ai_decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_decided_at` datetime DEFAULT NULL,
  `ai_decided_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rgd_project_idx` (`project_id`),
  KEY `rgd_template_idx` (`funder_template`),
  KEY `rgd_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_grant_section` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `draft_id` bigint unsigned NOT NULL,
  `section_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'stable key within the funder template, e.g. summary, aims, methodology',
  `label` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'the section draft text the researcher edits',
  `sort_order` int NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rgs_draft_idx` (`draft_id`,`sort_order`),
  KEY `rgs_key_idx` (`draft_id`,`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_impact_signal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `submission_id` bigint unsigned DEFAULT NULL,
  `doi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signal_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'citation',
  `title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci,
  `url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `risig_project_idx` (`project_id`),
  KEY `risig_project_type_idx` (`project_id`,`signal_type`),
  KEY `risig_submission_idx` (`submission_id`),
  KEY `risig_doi_idx` (`doi`),
  KEY `risig_source_idx` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_inbox_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `researcher_id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `kind` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'note',
  `title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `origin` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `source_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_path` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inbox',
  `captured_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rii_researcher_idx` (`researcher_id`),
  KEY `rii_project_idx` (`project_id`),
  KEY `rii_status_idx` (`status`),
  KEY `rii_kind_idx` (`kind`),
  KEY `rii_captured_idx` (`captured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_journal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `researcher_id` bigint unsigned DEFAULT NULL,
  `kind` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'publication' COMMENT 'publication, manuscript',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Untitled journal',
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eissn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `aims_scope` text COLLATE utf8mb4_unicode_ci,
  `editor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editor_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_journal_id` bigint unsigned DEFAULT NULL COMMENT 'FK (soft) to research_target_journal (#114) when manuscript mode',
  `cover_object_id` bigint unsigned DEFAULT NULL COMMENT 'optional cover digital object (parity with Heratio schema)',
  `doi` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft, published, archived',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rj_researcher` (`researcher_id`),
  KEY `idx_rj_kind` (`kind`),
  KEY `idx_rj_status` (`status`),
  KEY `idx_rj_target` (`target_journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_journal_article` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `journal_id` bigint unsigned NOT NULL,
  `issue_id` bigint unsigned DEFAULT NULL COMMENT 'NULL = unassigned / manuscript draft',
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Untitled article',
  `authors` text COLLATE utf8mb4_unicode_ci,
  `abstract` text COLLATE utf8mb4_unicode_ci,
  `keywords` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body_markdown` longtext COLLATE utf8mb4_unicode_ci,
  `body_html` longtext COLLATE utf8mb4_unicode_ci,
  `reference_style` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'APA, Harvard, Vancouver, Chicago, MLA, IEEE',
  `target_journal_id` bigint unsigned DEFAULT NULL COMMENT 'FK (soft) to research_target_journal (#114)',
  `doi` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `word_count` int NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft, submitted, published',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rja_journal` (`journal_id`),
  KEY `idx_rja_issue` (`issue_id`),
  KEY `idx_rja_sort` (`journal_id`,`sort_order`),
  KEY `idx_rja_target` (`target_journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_journal_issue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `journal_id` bigint unsigned NOT NULL,
  `volume` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft, published',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rji_journal` (`journal_id`),
  KEY `idx_rji_sort` (`journal_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_lead` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `information_object_id` bigint unsigned NOT NULL,
  `source_discovery_id` bigint unsigned DEFAULT NULL,
  `headline` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_text` text COLLATE utf8mb4_unicode_ci,
  `why_it_matters` text COLLATE utf8mb4_unicode_ci,
  `connection_count` int unsigned NOT NULL DEFAULT '0',
  `confidence` smallint unsigned NOT NULL DEFAULT '0',
  `evidence` json DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `ai_labelled` tinyint(1) NOT NULL DEFAULT '1',
  `curated_by` bigint unsigned DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_research_lead_io` (`information_object_id`),
  KEY `ix_research_lead_status` (`status`),
  KEY `ix_research_lead_status_conf` (`status`,`confidence`),
  KEY `ix_research_lead_source` (`source_discovery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_lecture` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `researcher_id` bigint unsigned DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standalone' COMMENT 'curriculum, talk, standalone',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Untitled lecture',
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `speaker_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `speaker_affiliation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_minutes` int unsigned DEFAULT NULL,
  `recording_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slides_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curriculum_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'free-text ref to a training curriculum item',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft, scheduled, delivered, published, archived',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lecture_type` (`type`),
  KEY `idx_lecture_status` (`status`),
  KEY `idx_lecture_researcher` (`researcher_id`),
  KEY `idx_lecture_scheduled_at` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_lecture_resource` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lecture_id` bigint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Resource',
  `url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resource_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'link' COMMENT 'reading, slides, video, link, file',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lecture_resource_lecture` (`lecture_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_lecture_section` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lecture_id` bigint unsigned NOT NULL,
  `heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body_markdown` mediumtext COLLATE utf8mb4_unicode_ci,
  `body_html` mediumtext COLLATE utf8mb4_unicode_ci,
  `media_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `media_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'image, video, audio, embed',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lecture_section_lecture` (`lecture_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_memory_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `researcher_id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `kind` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `source_ref` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rmi_researcher_idx` (`researcher_id`),
  KEY `rmi_project_idx` (`project_id`),
  KEY `rmi_kind_idx` (`kind`),
  KEY `rmi_status_idx` (`status`),
  KEY `rmi_researcher_status_idx` (`researcher_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_method_protocol` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `template_code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'references research_method_template.code',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON: area-key => researcher answer text',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft, in_review, final (from ahg_dropdown method_protocol_status)',
  `created_by` int DEFAULT NULL COMMENT 'research_researcher.id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rmp_project_idx` (`project_id`),
  KEY `rmp_template_idx` (`template_code`),
  KEY `rmp_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_method_template` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'stable template key, e.g. case-study',
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discipline` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'broad discipline grouping, jurisdiction-neutral',
  `description` text COLLATE utf8mb4_unicode_ci,
  `guidance` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON: ordered map of area-key => {label, prompt, placeholder}',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rmt_code_uq` (`code`),
  KEY `rmt_active_idx` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_milestone` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `title` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'the milestone or deliverable name - free-text DATA, no jurisdiction or institution assumed',
  `milestone_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'milestone' COMMENT 'from ahg_dropdown milestone_type: milestone, deliverable, decision_point, review, dissemination, other',
  `description` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'free-text description of the milestone or deliverable',
  `due_date` date DEFAULT NULL COMMENT 'the planned date the milestone or deliverable is due',
  `completed_date` date DEFAULT NULL COMMENT 'the date the milestone or deliverable was actually completed, if any',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned' COMMENT 'from ahg_dropdown milestone_status: planned, in_progress, completed, delayed, cancelled',
  `progress_pct` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'progress towards completion, 0-100',
  `deliverable` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the concrete deliverable expected at this milestone - free-text DATA',
  `owner_id` int DEFAULT NULL COMMENT 'research_researcher.id - the record owner',
  `created_by` int DEFAULT NULL COMMENT 'research_researcher.id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rmilestone_project_idx` (`project_id`),
  KEY `rmilestone_type_idx` (`milestone_type`),
  KEY `rmilestone_status_idx` (`status`),
  KEY `rmilestone_due_idx` (`due_date`),
  KEY `rmilestone_owner_idx` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_output` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `output_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'journal_article' COMMENT 'from ahg_dropdown research_output_type: journal_article, dataset, software, presentation, thesis, report, chapter, other',
  `title` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authors` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'free-text author list as DATA - never parsed into a jurisdiction-specific format',
  `venue` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'journal, conference, repository or publisher name',
  `identifier_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'from ahg_dropdown research_output_identifier_type: doi, handle, isbn, url, other',
  `identifier` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the bare identifier value, e.g. 10.1234/abcd for a DOI',
  `identifier_url` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'optional explicit resolvable URL; if blank the type+identifier resolves one (e.g. doi -> https://doi.org/...)',
  `output_date` date DEFAULT NULL COMMENT 'date the output was published / released',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned' COMMENT 'from ahg_dropdown research_output_status: planned, in_progress, published',
  `notes` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'abstract or free-text notes about the output',
  `dmp_id` bigint unsigned DEFAULT NULL COMMENT 'FK-by-convention to research_dmp.id (sibling slice) - the plan that governs this output',
  `owner_id` int DEFAULT NULL COMMENT 'research_researcher.id - the output owner',
  `created_by` int DEFAULT NULL COMMENT 'research_researcher.id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rout_project_idx` (`project_id`),
  KEY `rout_type_idx` (`output_type`),
  KEY `rout_status_idx` (`status`),
  KEY `rout_dmp_idx` (`dmp_id`),
  KEY `rout_owner_idx` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_print_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_type` varchar(65) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'call_slip, paging_slip, receipt, badge, label, report, letter',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `template_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `css_styles` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `page_size` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'a4' COMMENT 'a4, a5, letter, label_4x6, label_2x4, badge, custom',
  `orientation` varchar(28) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'portrait' COMMENT 'portrait, landscape',
  `margin_top` int DEFAULT '10',
  `margin_right` int DEFAULT '10',
  `margin_bottom` int DEFAULT '10',
  `margin_left` int DEFAULT '10',
  `copies_default` int DEFAULT '1',
  `variables` json DEFAULT NULL COMMENT 'Available template variables',
  `is_default` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_type` (`template_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_question_brief` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `current_version` int NOT NULL DEFAULT '0',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ai_model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_at` datetime DEFAULT NULL,
  `ai_decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_decided_at` datetime DEFAULT NULL,
  `ai_decided_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rqb_project_uq` (`project_id`),
  KEY `rqb_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_question_brief_version` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `brief_id` bigint unsigned NOT NULL,
  `version_no` int NOT NULL,
  `broad_topic` text COLLATE utf8mb4_unicode_ci,
  `problem_statement` text COLLATE utf8mb4_unicode_ci,
  `research_gap` text COLLATE utf8mb4_unicode_ci,
  `primary_question` text COLLATE utf8mb4_unicode_ci,
  `secondary_questions` text COLLATE utf8mb4_unicode_ci,
  `hypothesis` text COLLATE utf8mb4_unicode_ci,
  `scope_boundaries` text COLLATE utf8mb4_unicode_ci,
  `key_definitions` text COLLATE utf8mb4_unicode_ci,
  `assumptions` text COLLATE utf8mb4_unicode_ci,
  `bias_risks` text COLLATE utf8mb4_unicode_ci,
  `change_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rqbv_brief_version_uq` (`brief_id`,`version_no`),
  KEY `rqbv_brief_idx` (`brief_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_reading_room_seat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reading_room_id` int NOT NULL,
  `seat_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `seat_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Display name like "Table A - Seat 1"',
  `seat_type` varchar(69) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'standard' COMMENT 'standard, accessible, computer, microfilm, oversize, quiet, group',
  `has_power` tinyint(1) DEFAULT '1',
  `has_lamp` tinyint(1) DEFAULT '1',
  `has_computer` tinyint(1) DEFAULT '0',
  `has_magnifier` tinyint(1) DEFAULT '0',
  `position_x` int DEFAULT NULL COMMENT 'For visual seat map',
  `position_y` int DEFAULT NULL COMMENT 'For visual seat map',
  `zone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Reading room zone/section',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_room_seat` (`reading_room_id`,`seat_number`),
  KEY `idx_room` (`reading_room_id`),
  KEY `idx_type` (`seat_type`),
  KEY `idx_active` (`is_active`),
  KEY `idx_zone` (`zone`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_replication_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `built_by` int DEFAULT NULL COMMENT 'research_researcher.id of who built the pack',
  `built_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rrl_project_idx` (`project_id`),
  KEY `rrl_project_built_idx` (`project_id`,`built_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_request_correspondence` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `request_type` varchar(50) NOT NULL DEFAULT 'material' COMMENT 'material|reproduction',
  `sender_type` varchar(50) NOT NULL DEFAULT 'staff' COMMENT 'staff|researcher',
  `sender_id` int DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Staff-only note, hidden from researcher',
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`,`request_type`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
CREATE TABLE IF NOT EXISTS `research_request_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `queue_type` varchar(57) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'retrieval' COMMENT 'retrieval, paging, return, curatorial, reproduction',
  `filter_status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Comma-separated statuses to include',
  `filter_room_id` int DEFAULT NULL,
  `filter_priority` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_field` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'created_at',
  `sort_direction` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'ASC' COMMENT 'ASC, DESC',
  `auto_assign` tinyint(1) DEFAULT '0',
  `assigned_staff_id` int DEFAULT NULL,
  `color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '#3498db' COMMENT 'Queue display color',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'box',
  `is_default` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_type` (`queue_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_retrieval_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reading_room_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., "Morning Retrieval", "Afternoon Retrieval"',
  `day_of_week` tinyint DEFAULT NULL COMMENT '0=Sunday, 6=Saturday, NULL=all days',
  `retrieval_time` time NOT NULL,
  `cutoff_minutes_before` int DEFAULT '30' COMMENT 'Minutes before retrieval time to stop accepting requests',
  `max_items_per_run` int DEFAULT '50',
  `storage_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Which storage area this schedule serves',
  `is_active` tinyint(1) DEFAULT '1',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room` (`reading_room_id`),
  KEY `idx_day` (`day_of_week`),
  KEY `idx_time` (`retrieval_time`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_review_comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `assertion_id` int DEFAULT NULL COMMENT 'Anchor to a claim in research_assertion; NULL = project-level comment',
  `thread_id` int DEFAULT NULL COMMENT 'Self-ref to the root comment id; NULL = a root comment',
  `author_id` int NOT NULL COMMENT 'users.id of the comment author',
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rrc_project` (`project_id`),
  KEY `idx_rrc_assertion` (`assertion_id`),
  KEY `idx_rrc_thread` (`thread_id`),
  KEY `idx_rrc_author` (`author_id`),
  KEY `idx_rrc_resolved` (`resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_review_run` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `persona` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'methodologist',
  `model` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Gateway model that answered, if known',
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `findings` json DEFAULT NULL COMMENT 'Grouped findings: major/minor concerns, objections, required revisions, rejection risks, strongest contribution, weakest section, missing literature',
  `created_by` int NOT NULL COMMENT 'users.id who triggered the run',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rrr_project` (`project_id`),
  KEY `idx_rrr_persona` (`persona`),
  KEY `idx_rrr_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_seat_assignment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `seat_id` int NOT NULL,
  `assigned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `released_by` int DEFAULT NULL,
  `status` varchar(44) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'assigned' COMMENT 'assigned, occupied, released, no_show',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_booking_seat` (`booking_id`,`seat_id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_seat` (`seat_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`assigned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_source_triage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `source_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` int NOT NULL,
  `triage_category` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `ai_preview` text COLLATE utf8mb4_unicode_ci,
  `ai_preview_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rst_unique` (`project_id`,`source_type`,`source_id`),
  KEY `rst_project_idx` (`project_id`),
  KEY `rst_category_idx` (`triage_category`),
  KEY `rst_read_idx` (`read_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_submission` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `venue_ref` int DEFAULT NULL,
  `venue_name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'drafting',
  `manuscript_title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_at` date DEFAULT NULL,
  `decision_at` date DEFAULT NULL,
  `doi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repository_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ai_model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_at` datetime DEFAULT NULL,
  `ai_decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_decided_at` datetime DEFAULT NULL,
  `ai_decided_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rsub_project_idx` (`project_id`),
  KEY `rsub_venue_idx` (`venue_ref`),
  KEY `rsub_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_submission_requirement` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `met` tinyint(1) NOT NULL DEFAULT '0',
  `note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rsreq_submission_idx` (`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_submission_response` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint unsigned NOT NULL,
  `reviewer_label` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `point` text COLLATE utf8mb4_unicode_ci,
  `response` text COLLATE utf8mb4_unicode_ci,
  `revision_note` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rsresp_submission_idx` (`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_target_journal` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eissn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `homepage_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submission_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `languages` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_scope` text COLLATE utf8mb4_unicode_ci COMMENT 'what the journal mainly accepts',
  `article_types` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accreditation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'DHET, IBSS, Scopus, WoS, DOAJ, Sabinet, ERIH-PLUS, ...',
  `accreditation_market` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'per-market module tag, e.g. ZA for DHET',
  `reference_style` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'APA, Harvard, Vancouver, Chicago, MLA, IEEE',
  `structure_notes` text COLLATE utf8mb4_unicode_ci,
  `max_words` int DEFAULT NULL,
  `abstract_max_words` int DEFAULT NULL,
  `peer_review` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'double-blind, single-blind, open, none',
  `open_access` tinyint(1) NOT NULL DEFAULT '0',
  `apc_amount` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'article processing charge note',
  `turnaround` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'active, discontinued',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_issn` (`issn`),
  KEY `idx_title` (`title`),
  KEY `idx_market` (`accreditation_market`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_team_member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `person_name` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'the contributor name - free-text DATA, no institution or jurisdiction assumed',
  `role` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'researcher' COMMENT 'from ahg_dropdown research_team_role: principal_investigator, co_investigator, researcher, student, advisor, partner, technician, other',
  `affiliation` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'institution / organisation - free-text DATA, no country or institution defaulted',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'contact email for the contributor',
  `orcid` varchar(19) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the bare ORCID iD (####-####-####-###X) - an international persistent identifier; rendered as a link to https://orcid.org/{orcid}, never fetched',
  `is_lead` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'flags a project lead (e.g. principal investigator) for highlighting in the summary',
  `contribution_note` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'free-text description of the contribution; the international CRediT taxonomy is a recognised reference, but this field is free text',
  `start_date` date DEFAULT NULL COMMENT 'date the contributor joined the project',
  `end_date` date DEFAULT NULL COMMENT 'date the contributor left the project, if any',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'from ahg_dropdown research_team_status: active, inactive, former',
  `owner_id` int DEFAULT NULL COMMENT 'research_researcher.id - the record owner',
  `created_by` int DEFAULT NULL COMMENT 'research_researcher.id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rteam_project_idx` (`project_id`),
  KEY `rteam_role_idx` (`role`),
  KEY `rteam_status_idx` (`status`),
  KEY `rteam_lead_idx` (`is_lead`),
  KEY `rteam_dates_idx` (`start_date`,`end_date`),
  KEY `rteam_owner_idx` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_walk_in_visitor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reading_room_id` int NOT NULL,
  `visit_date` date NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_type` varchar(65) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'passport, national_id, drivers_license, student_card, other',
  `id_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organization` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `research_topic` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rules_acknowledged` tinyint(1) DEFAULT '0',
  `rules_acknowledged_at` datetime DEFAULT NULL,
  `photo_permission` tinyint(1) DEFAULT '0',
  `converted_to_researcher_id` int DEFAULT NULL COMMENT 'If they registered',
  `seat_id` int DEFAULT NULL,
  `check_in_time` time NOT NULL,
  `check_out_time` time DEFAULT NULL,
  `checked_in_by` int DEFAULT NULL,
  `checked_out_by` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room` (`reading_room_id`),
  KEY `idx_date` (`visit_date`),
  KEY `idx_email` (`email`),
  KEY `idx_converted` (`converted_to_researcher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_writing_doc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `doc_type` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'section' COMMENT 'thesis_chapter, article, review, section, other',
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT 'draft, in_review, final, archived',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_doc_type` (`doc_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_writing_section` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doc_id` int NOT NULL,
  `heading` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort_order` int DEFAULT '0',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doc` (`doc_id`),
  KEY `idx_sort` (`doc_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `research_writing_version` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doc_id` int NOT NULL,
  `version_no` int NOT NULL DEFAULT '1',
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `note` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `ai_model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_at` datetime DEFAULT NULL,
  `ai_decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_decided_at` datetime DEFAULT NULL,
  `ai_decided_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc` (`doc_id`),
  KEY `idx_doc_version` (`doc_id`,`version_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS=1;
