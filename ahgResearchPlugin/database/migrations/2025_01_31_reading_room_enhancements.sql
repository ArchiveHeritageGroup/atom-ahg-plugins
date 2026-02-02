-- ============================================================
-- ahgResearchPlugin - Reading Room Enhancements Migration
-- Version 2.1.0 - Aeon Parity Features
-- Date: 2025-01-31
-- ============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- ============================================================
-- PHASE 10: SEAT MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_reading_room_seat` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `reading_room_id` INT NOT NULL,
    `seat_number` VARCHAR(20) NOT NULL,
    `seat_label` VARCHAR(100) DEFAULT NULL COMMENT 'Display name like "Table A - Seat 1"',
    `seat_type` ENUM('standard','accessible','computer','microfilm','oversize','quiet','group') DEFAULT 'standard',
    `has_power` TINYINT(1) DEFAULT 1,
    `has_lamp` TINYINT(1) DEFAULT 1,
    `has_computer` TINYINT(1) DEFAULT 0,
    `has_magnifier` TINYINT(1) DEFAULT 0,
    `position_x` INT DEFAULT NULL COMMENT 'For visual seat map',
    `position_y` INT DEFAULT NULL COMMENT 'For visual seat map',
    `zone` VARCHAR(50) DEFAULT NULL COMMENT 'Reading room zone/section',
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_seat` (`reading_room_id`, `seat_number`),
    KEY `idx_room` (`reading_room_id`),
    KEY `idx_type` (`seat_type`),
    KEY `idx_active` (`is_active`),
    KEY `idx_zone` (`zone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_seat_assignment` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `booking_id` INT NOT NULL,
    `seat_id` INT NOT NULL,
    `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `assigned_by` INT DEFAULT NULL,
    `released_at` DATETIME DEFAULT NULL,
    `released_by` INT DEFAULT NULL,
    `status` ENUM('assigned','occupied','released','no_show') DEFAULT 'assigned',
    `notes` TEXT,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_booking_seat` (`booking_id`, `seat_id`),
    KEY `idx_booking` (`booking_id`),
    KEY `idx_seat` (`seat_id`),
    KEY `idx_status` (`status`),
    KEY `idx_date` (`assigned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 11: EQUIPMENT MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_equipment` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `reading_room_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) DEFAULT NULL,
    `equipment_type` ENUM('microfilm_reader','microfiche_reader','scanner','computer','magnifier','book_cradle','light_box','camera_stand','gloves','weights','other') NOT NULL,
    `brand` VARCHAR(100) DEFAULT NULL,
    `model` VARCHAR(100) DEFAULT NULL,
    `serial_number` VARCHAR(100) DEFAULT NULL,
    `description` TEXT,
    `location` VARCHAR(255) DEFAULT NULL COMMENT 'Where in the reading room',
    `requires_training` TINYINT(1) DEFAULT 0,
    `max_booking_hours` INT DEFAULT 4,
    `booking_increment_minutes` INT DEFAULT 30,
    `condition_status` ENUM('excellent','good','fair','needs_repair','out_of_service') DEFAULT 'good',
    `last_maintenance_date` DATE DEFAULT NULL,
    `next_maintenance_date` DATE DEFAULT NULL,
    `is_available` TINYINT(1) DEFAULT 1,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_room` (`reading_room_id`),
    KEY `idx_type` (`equipment_type`),
    KEY `idx_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_equipment_booking` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `booking_id` INT DEFAULT NULL COMMENT 'Linked reading room booking',
    `researcher_id` INT NOT NULL,
    `equipment_id` INT NOT NULL,
    `booking_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `purpose` TEXT,
    `status` ENUM('reserved','in_use','returned','cancelled','no_show') DEFAULT 'reserved',
    `checked_out_at` DATETIME DEFAULT NULL,
    `checked_out_by` INT DEFAULT NULL,
    `returned_at` DATETIME DEFAULT NULL,
    `returned_by` INT DEFAULT NULL,
    `condition_on_return` ENUM('excellent','good','fair','damaged') DEFAULT NULL,
    `return_notes` TEXT,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_equipment` (`equipment_id`),
    KEY `idx_date` (`booking_date`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 12: RETRIEVAL SCHEDULING & QUEUES
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_retrieval_schedule` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `reading_room_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT 'e.g., "Morning Retrieval", "Afternoon Retrieval"',
    `day_of_week` TINYINT DEFAULT NULL COMMENT '0=Sunday, 6=Saturday, NULL=all days',
    `retrieval_time` TIME NOT NULL,
    `cutoff_minutes_before` INT DEFAULT 30 COMMENT 'Minutes before retrieval time to stop accepting requests',
    `max_items_per_run` INT DEFAULT 50,
    `storage_location` VARCHAR(255) DEFAULT NULL COMMENT 'Which storage area this schedule serves',
    `is_active` TINYINT(1) DEFAULT 1,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_room` (`reading_room_id`),
    KEY `idx_day` (`day_of_week`),
    KEY `idx_time` (`retrieval_time`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_request_queue` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `queue_type` ENUM('retrieval','paging','return','curatorial','reproduction') DEFAULT 'retrieval',
    `filter_status` VARCHAR(100) DEFAULT NULL COMMENT 'Comma-separated statuses to include',
    `filter_room_id` INT DEFAULT NULL,
    `filter_priority` VARCHAR(50) DEFAULT NULL,
    `sort_field` VARCHAR(50) DEFAULT 'created_at',
    `sort_direction` ENUM('ASC','DESC') DEFAULT 'ASC',
    `auto_assign` TINYINT(1) DEFAULT 0,
    `assigned_staff_id` INT DEFAULT NULL,
    `color` VARCHAR(7) DEFAULT '#3498db' COMMENT 'Queue display color',
    `icon` VARCHAR(50) DEFAULT 'box',
    `is_default` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_type` (`queue_type`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default queues
INSERT INTO `research_request_queue` (`name`, `code`, `description`, `queue_type`, `filter_status`, `sort_field`, `sort_direction`, `color`, `icon`, `is_default`, `sort_order`) VALUES
('New Requests', 'new', 'Newly submitted material requests awaiting processing', 'retrieval', 'requested', 'created_at', 'ASC', '#3498db', 'inbox', 1, 10),
('Rush Priority', 'rush', 'High priority and rush requests', 'paging', 'requested', 'priority', 'DESC', '#e74c3c', 'bolt', 0, 20),
('Ready for Retrieval', 'retrieval', 'Requests ready to be retrieved from storage', 'retrieval', 'requested', 'created_at', 'ASC', '#f39c12', 'box-archive', 0, 30),
('In Transit', 'transit', 'Materials being transported to reading room', 'paging', 'retrieved', 'retrieved_at', 'ASC', '#9b59b6', 'truck', 0, 40),
('Ready for Delivery', 'delivery', 'Materials ready to be delivered to researcher', 'paging', 'delivered', 'updated_at', 'ASC', '#27ae60', 'hand-holding', 0, 50),
('Curatorial Review', 'curatorial', 'Requests requiring curatorial approval', 'curatorial', 'requested', 'created_at', 'ASC', '#e67e22', 'user-shield', 0, 60),
('Pending Return', 'return', 'Materials to be returned to storage', 'return', 'in_use', 'updated_at', 'ASC', '#1abc9c', 'undo', 0, 70)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================================
-- PHASE 13: ACTIVITIES (Classes, Exhibits, Special Events)
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_activity` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `activity_type` ENUM('class','tour','exhibit','loan','conservation','photography','filming','event','meeting','other') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `organizer_id` INT DEFAULT NULL COMMENT 'Researcher ID if registered',
    `organizer_name` VARCHAR(255),
    `organizer_email` VARCHAR(255),
    `organizer_phone` VARCHAR(50),
    `organization` VARCHAR(255),
    `expected_attendees` INT DEFAULT NULL,
    `reading_room_id` INT DEFAULT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `recurring` TINYINT(1) DEFAULT 0,
    `recurrence_pattern` JSON DEFAULT NULL,
    `setup_requirements` TEXT,
    `av_requirements` TEXT,
    `catering_notes` TEXT,
    `special_instructions` TEXT,
    `status` ENUM('requested','tentative','confirmed','in_progress','completed','cancelled') DEFAULT 'requested',
    `confirmed_by` INT DEFAULT NULL,
    `confirmed_at` DATETIME DEFAULT NULL,
    `cancelled_by` INT DEFAULT NULL,
    `cancelled_at` DATETIME DEFAULT NULL,
    `cancellation_reason` TEXT,
    `notes` TEXT,
    `admin_notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`activity_type`),
    KEY `idx_organizer` (`organizer_id`),
    KEY `idx_room` (`reading_room_id`),
    KEY `idx_date` (`start_date`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_activity_material` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `activity_id` INT NOT NULL,
    `object_id` INT NOT NULL,
    `purpose` TEXT,
    `handling_notes` TEXT,
    `display_notes` TEXT COMMENT 'For exhibits',
    `insurance_value` DECIMAL(15,2) DEFAULT NULL,
    `loan_agreement_signed` TINYINT(1) DEFAULT 0,
    `condition_before` TEXT,
    `condition_after` TEXT,
    `status` ENUM('requested','approved','rejected','retrieved','in_use','returned','damaged') DEFAULT 'requested',
    `approved_by` INT DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `retrieved_at` DATETIME DEFAULT NULL,
    `returned_at` DATETIME DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity` (`activity_id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_activity_participant` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `activity_id` INT NOT NULL,
    `researcher_id` INT DEFAULT NULL COMMENT 'If registered researcher',
    `name` VARCHAR(255),
    `email` VARCHAR(255),
    `phone` VARCHAR(50),
    `organization` VARCHAR(255),
    `role` ENUM('organizer','instructor','presenter','student','visitor','assistant','staff','other') DEFAULT 'visitor',
    `dietary_requirements` TEXT,
    `accessibility_needs` TEXT,
    `registration_status` ENUM('pending','confirmed','waitlist','cancelled','attended','no_show') DEFAULT 'pending',
    `registered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `confirmed_at` DATETIME DEFAULT NULL,
    `checked_in_at` DATETIME DEFAULT NULL,
    `checked_out_at` DATETIME DEFAULT NULL,
    `feedback` TEXT,
    `notes` TEXT,
    PRIMARY KEY (`id`),
    KEY `idx_activity` (`activity_id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_role` (`role`),
    KEY `idx_status` (`registration_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 14: WALK-IN VISITOR MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_walk_in_visitor` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `reading_room_id` INT NOT NULL,
    `visit_date` DATE NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `id_type` ENUM('passport','national_id','drivers_license','student_card','other') DEFAULT NULL,
    `id_number` VARCHAR(100) DEFAULT NULL,
    `organization` VARCHAR(255) DEFAULT NULL,
    `purpose` TEXT,
    `research_topic` TEXT,
    `rules_acknowledged` TINYINT(1) DEFAULT 0,
    `rules_acknowledged_at` DATETIME DEFAULT NULL,
    `photo_permission` TINYINT(1) DEFAULT 0,
    `converted_to_researcher_id` INT DEFAULT NULL COMMENT 'If they registered',
    `seat_id` INT DEFAULT NULL,
    `check_in_time` TIME NOT NULL,
    `check_out_time` TIME DEFAULT NULL,
    `checked_in_by` INT DEFAULT NULL,
    `checked_out_by` INT DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_room` (`reading_room_id`),
    KEY `idx_date` (`visit_date`),
    KEY `idx_email` (`email`),
    KEY `idx_converted` (`converted_to_researcher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 15: CALL SLIP / PRINT TEMPLATES
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_print_template` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `template_type` ENUM('call_slip','paging_slip','receipt','badge','label','report','letter') NOT NULL,
    `description` TEXT,
    `template_html` LONGTEXT NOT NULL,
    `css_styles` TEXT,
    `page_size` ENUM('a4','a5','letter','label_4x6','label_2x4','badge','custom') DEFAULT 'a4',
    `orientation` ENUM('portrait','landscape') DEFAULT 'portrait',
    `margin_top` INT DEFAULT 10,
    `margin_right` INT DEFAULT 10,
    `margin_bottom` INT DEFAULT 10,
    `margin_left` INT DEFAULT 10,
    `copies_default` INT DEFAULT 1,
    `variables` JSON COMMENT 'Available template variables',
    `is_default` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_type` (`template_type`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default print templates
INSERT INTO `research_print_template` (`name`, `code`, `template_type`, `description`, `template_html`, `css_styles`, `page_size`, `variables`, `is_default`) VALUES
('Standard Call Slip', 'call_slip_standard', 'call_slip', 'Standard call slip for material retrieval',
'<div class="call-slip">
  <div class="header">
    <h1>{{repository_name}}</h1>
    <h2>Material Request</h2>
    <div class="barcode">{{request_barcode}}</div>
  </div>
  <div class="request-info">
    <div class="row"><strong>Request #:</strong> {{request_id}}</div>
    <div class="row"><strong>Date:</strong> {{request_date}}</div>
    <div class="row"><strong>Priority:</strong> {{priority}}</div>
  </div>
  <div class="material-info">
    <div class="row"><strong>Title:</strong> {{item_title}}</div>
    <div class="row"><strong>Reference:</strong> {{reference_code}}</div>
    <div class="row"><strong>Location:</strong> {{location_code}}</div>
    <div class="row"><strong>Shelf:</strong> {{shelf_location}}</div>
    <div class="row"><strong>Box:</strong> {{box_number}} <strong>Folder:</strong> {{folder_number}}</div>
  </div>
  <div class="researcher-info">
    <div class="row"><strong>Researcher:</strong> {{researcher_name}}</div>
    <div class="row"><strong>Booking:</strong> {{booking_date}} {{start_time}}-{{end_time}}</div>
    <div class="row"><strong>Reading Room:</strong> {{reading_room}} Seat: {{seat_number}}</div>
  </div>
  <div class="handling">
    <div class="row"><strong>Handling:</strong> {{handling_instructions}}</div>
  </div>
  <div class="footer">
    <div class="row">Retrieved by: _______________ Time: _______________</div>
    <div class="row">Delivered by: _______________ Time: _______________</div>
  </div>
</div>',
'.call-slip { font-family: Arial, sans-serif; padding: 10mm; border: 1px solid #000; }
.call-slip .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 5mm; margin-bottom: 5mm; }
.call-slip h1 { margin: 0; font-size: 16pt; }
.call-slip h2 { margin: 2mm 0; font-size: 12pt; }
.call-slip .barcode { font-family: "Libre Barcode 39", monospace; font-size: 24pt; }
.call-slip .row { margin: 2mm 0; }
.call-slip .material-info { background: #f5f5f5; padding: 3mm; margin: 3mm 0; }
.call-slip .footer { border-top: 1px solid #000; padding-top: 3mm; margin-top: 5mm; }',
'a5',
'["repository_name","request_id","request_date","priority","item_title","reference_code","location_code","shelf_location","box_number","folder_number","researcher_name","booking_date","start_time","end_time","reading_room","seat_number","handling_instructions","request_barcode"]',
1),

('Researcher Badge', 'researcher_badge', 'badge', 'Researcher identification badge',
'<div class="badge">
  <div class="photo">{{photo_or_placeholder}}</div>
  <div class="info">
    <div class="name">{{researcher_name}}</div>
    <div class="type">{{researcher_type}}</div>
    <div class="institution">{{institution}}</div>
    <div class="card-number">{{card_number}}</div>
    <div class="barcode">{{card_barcode}}</div>
    <div class="expires">Valid until: {{expires_at}}</div>
  </div>
</div>',
'.badge { width: 85mm; height: 54mm; border: 1px solid #333; border-radius: 3mm; overflow: hidden; display: flex; }
.badge .photo { width: 30mm; height: 100%; background: #eee; display: flex; align-items: center; justify-content: center; }
.badge .photo img { max-width: 100%; max-height: 100%; object-fit: cover; }
.badge .info { flex: 1; padding: 3mm; font-family: Arial, sans-serif; }
.badge .name { font-size: 12pt; font-weight: bold; }
.badge .type { font-size: 9pt; color: #666; }
.badge .institution { font-size: 8pt; margin-top: 2mm; }
.badge .card-number { font-size: 10pt; margin-top: 3mm; font-family: monospace; }
.badge .barcode { font-size: 16pt; font-family: "Libre Barcode 39", monospace; }
.badge .expires { font-size: 7pt; color: #999; margin-top: 2mm; }',
'badge',
'["researcher_name","researcher_type","institution","card_number","card_barcode","expires_at","photo_or_placeholder"]',
1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================================
-- ALTER STATEMENTS FOR EXISTING TABLES
-- ============================================================

DELIMITER //

DROP PROCEDURE IF EXISTS upgrade_reading_room_tables//

CREATE PROCEDURE upgrade_reading_room_tables()
BEGIN
    -- Add photo and card fields to researcher
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'photo_path') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `photo_path` VARCHAR(500) DEFAULT NULL AFTER `notes`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'card_number') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `card_number` VARCHAR(50) DEFAULT NULL AFTER `photo_path`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'card_barcode') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `card_barcode` VARCHAR(100) DEFAULT NULL AFTER `card_number`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'card_issued_at') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `card_issued_at` DATETIME DEFAULT NULL AFTER `card_barcode`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_researcher' AND column_name = 'renewal_reminder_sent') THEN
        ALTER TABLE `research_researcher` ADD COLUMN `renewal_reminder_sent` TINYINT(1) DEFAULT 0 AFTER `expires_at`;
    END IF;

    -- Add walk-in and rules fields to booking
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_booking' AND column_name = 'is_walk_in') THEN
        ALTER TABLE `research_booking` ADD COLUMN `is_walk_in` TINYINT(1) DEFAULT 0 AFTER `notes`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_booking' AND column_name = 'rules_acknowledged') THEN
        ALTER TABLE `research_booking` ADD COLUMN `rules_acknowledged` TINYINT(1) DEFAULT 0 AFTER `is_walk_in`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_booking' AND column_name = 'rules_acknowledged_at') THEN
        ALTER TABLE `research_booking` ADD COLUMN `rules_acknowledged_at` DATETIME DEFAULT NULL AFTER `rules_acknowledged`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_booking' AND column_name = 'seat_id') THEN
        ALTER TABLE `research_booking` ADD COLUMN `seat_id` INT DEFAULT NULL AFTER `rules_acknowledged_at`;
    END IF;

    -- Add location tracking to material request
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'location_current') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `location_current` VARCHAR(255) DEFAULT NULL COMMENT 'Current location: storage/transit/reading_room/returned' AFTER `shelf_location`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'retrieval_scheduled_for') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `retrieval_scheduled_for` DATETIME DEFAULT NULL AFTER `location_current`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'queue_id') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `queue_id` INT DEFAULT NULL AFTER `retrieval_scheduled_for`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'call_slip_printed_at') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `call_slip_printed_at` DATETIME DEFAULT NULL AFTER `paging_slip_printed`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND column_name = 'call_slip_printed_by') THEN
        ALTER TABLE `research_material_request` ADD COLUMN `call_slip_printed_by` INT DEFAULT NULL AFTER `call_slip_printed_at`;
    END IF;

    -- Add seat capacity tracking to reading room
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_reading_room' AND column_name = 'has_seat_management') THEN
        ALTER TABLE `research_reading_room` ADD COLUMN `has_seat_management` TINYINT(1) DEFAULT 0 COMMENT 'Enable individual seat tracking' AFTER `capacity`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_reading_room' AND column_name = 'walk_ins_allowed') THEN
        ALTER TABLE `research_reading_room` ADD COLUMN `walk_ins_allowed` TINYINT(1) DEFAULT 1 AFTER `has_seat_management`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_reading_room' AND column_name = 'walk_in_capacity') THEN
        ALTER TABLE `research_reading_room` ADD COLUMN `walk_in_capacity` INT DEFAULT 5 AFTER `walk_ins_allowed`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'research_reading_room' AND column_name = 'floor_plan_path') THEN
        ALTER TABLE `research_reading_room` ADD COLUMN `floor_plan_path` VARCHAR(500) DEFAULT NULL AFTER `walk_in_capacity`;
    END IF;

END//

DELIMITER ;

-- Run the upgrade procedure
CALL upgrade_reading_room_tables();

-- Drop the procedure after use
DROP PROCEDURE IF EXISTS upgrade_reading_room_tables;

-- ============================================================
-- INDEXES FOR PERFORMANCE (wrapped in procedure for safety)
-- ============================================================

DELIMITER //

DROP PROCEDURE IF EXISTS add_reading_room_indexes//

CREATE PROCEDURE add_reading_room_indexes()
BEGIN
    -- Add index on booking seat_id if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'research_booking' AND index_name = 'idx_booking_seat') THEN
        CREATE INDEX idx_booking_seat ON research_booking(seat_id);
    END IF;

    -- Add index on request queue_id if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND index_name = 'idx_request_queue') THEN
        CREATE INDEX idx_request_queue ON research_material_request(queue_id);
    END IF;

    -- Add index on retrieval_scheduled_for if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'research_material_request' AND index_name = 'idx_request_retrieval') THEN
        CREATE INDEX idx_request_retrieval ON research_material_request(retrieval_scheduled_for);
    END IF;
END//

DELIMITER ;

CALL add_reading_room_indexes();
DROP PROCEDURE IF EXISTS add_reading_room_indexes;

-- ============================================================
-- RESTORE SETTINGS
-- ============================================================

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
