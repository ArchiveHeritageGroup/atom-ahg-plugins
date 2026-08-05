-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgStorageManagePlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------

-- Table: physical_object_extended
CREATE TABLE IF NOT EXISTS `physical_object_extended` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `physical_object_id` int NOT NULL,
  `building` varchar(100) DEFAULT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `aisle` varchar(50) DEFAULT NULL,
  `bay` varchar(50) DEFAULT NULL,
  `rack` varchar(50) DEFAULT NULL,
  `shelf` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `reference_code` varchar(100) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `depth` decimal(10,2) DEFAULT NULL,
  `total_capacity` int unsigned DEFAULT NULL COMMENT 'Total slots/spaces available',
  `used_capacity` int unsigned DEFAULT '0' COMMENT 'Currently occupied',
  `available_capacity` int unsigned GENERATED ALWAYS AS ((`total_capacity` - `used_capacity`)) STORED,
  `capacity_unit` varchar(50) DEFAULT NULL COMMENT 'boxes, files, metres, items etc',
  `total_linear_metres` decimal(10,2) DEFAULT NULL,
  `used_linear_metres` decimal(10,2) DEFAULT '0.00',
  `available_linear_metres` decimal(10,2) GENERATED ALWAYS AS ((`total_linear_metres` - `used_linear_metres`)) STORED,
  `climate_controlled` tinyint(1) DEFAULT '0',
  `temperature_min` decimal(5,2) DEFAULT NULL,
  `temperature_max` decimal(5,2) DEFAULT NULL,
  `humidity_min` decimal(5,2) DEFAULT NULL,
  `humidity_max` decimal(5,2) DEFAULT NULL,
  `security_level` varchar(50) DEFAULT NULL,
  `access_restrictions` text,
  `status` VARCHAR(53) DEFAULT 'active' COMMENT 'active, full, maintenance, decommissioned',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_physical_object_id` (`physical_object_id`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_reference_code` (`reference_code`),
  KEY `idx_building` (`building`),
  KEY `idx_status` (`status`),
  KEY `idx_available_capacity` (`available_capacity`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- ============================================================================
-- heratio#145 — Strongroom space allocation (AtoM Heratio / PSIS port of #144).
-- ============================================================================
-- Schema is kept literally identical to the Heratio Laravel side
-- (packages/ahg-storage-manage/database/install.sql in the heratio repo).
-- Any future change to these tables must land on BOTH sides in the same release.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS ahg_strongroom (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug                 VARCHAR(255) NOT NULL,
    name                 VARCHAR(255) NOT NULL,
    location_description TEXT,
    capacity_value       DECIMAL(12,2),
    capacity_unit        VARCHAR(20) NOT NULL DEFAULT 'linear_meters'
                         COMMENT 'linear_meters, shelves, boxes, cubic_meters',
    notes                TEXT,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,
    UNIQUE KEY uq_strongroom_slug (slug),
    INDEX ix_strongroom_name (name)
);

CREATE TABLE IF NOT EXISTS ahg_physical_object_storage (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    physical_object_id INT NOT NULL,
    strongroom_id      BIGINT UNSIGNED NOT NULL,
    size_units_used    DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NULL,
    updated_at         TIMESTAMP NULL,
    UNIQUE KEY uq_physical_object (physical_object_id),
    INDEX ix_strongroom (strongroom_id),
    CONSTRAINT fk_phyo FOREIGN KEY (physical_object_id) REFERENCES physical_object(id) ON DELETE CASCADE,
    CONSTRAINT fk_strr FOREIGN KEY (strongroom_id)      REFERENCES ahg_strongroom(id)  ON DELETE RESTRICT
);

SET FOREIGN_KEY_CHECKS = 1;
