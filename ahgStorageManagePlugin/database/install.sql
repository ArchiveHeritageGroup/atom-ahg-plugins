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

CREATE TABLE IF NOT EXISTS ahg_storage_location (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) NOT NULL,
    description     TEXT,
    location_type   VARCHAR(50) DEFAULT NULL COMMENT 'building, floor, room, aisle, bay, rack, shelf, container, storage_unit',
    parent_id       BIGINT UNSIGNED DEFAULT NULL,
    capacity_value  DECIMAL(10,2) DEFAULT NULL,
    capacity_unit   VARCHAR(50) DEFAULT NULL COMMENT 'cubic_metres, linear_metres, items',
    notes           TEXT,
    level           INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'depth in the tree; 0 is a root location',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    UNIQUE KEY uq_storage_location_slug (slug),
    INDEX ix_storage_location_parent (parent_id),
    INDEX ix_storage_location_type (location_type),
    CONSTRAINT fk_storage_location_parent FOREIGN KEY (parent_id)
        REFERENCES ahg_storage_location(id) ON DELETE SET NULL
);

-- Closure table for ahg_storage_location (atom-ahg-plugins#193, option 2).
-- parent_id stays the source of truth; this is a maintained index of every
-- ancestor/descendant pair, so "everything under Strongroom B", a location's
-- path and capacity roll-ups are single joins instead of parent walks.
-- Same shape as Heratio's closure tables (heratio#1333): ancestor, descendant,
-- depth, with a (X, X, 0) self row per location. StorageLocationService keeps
-- it in step on create, move and delete; rebuildClosure() re-derives it.
CREATE TABLE IF NOT EXISTS ahg_storage_location_closure (
    ancestor    BIGINT UNSIGNED NOT NULL,
    descendant  BIGINT UNSIGNED NOT NULL,
    depth       INT UNSIGNED NOT NULL,
    PRIMARY KEY (ancestor, descendant),
    INDEX ix_slc_anc_depth_desc (ancestor, depth, descendant),
    INDEX ix_slc_desc_depth (descendant, depth),
    CONSTRAINT fk_slc_ancestor   FOREIGN KEY (ancestor)   REFERENCES ahg_storage_location(id) ON DELETE CASCADE,
    CONSTRAINT fk_slc_descendant FOREIGN KEY (descendant) REFERENCES ahg_storage_location(id) ON DELETE CASCADE
);

-- Backfill from parent_id. Idempotent: INSERT IGNORE on the primary key, so a
-- re-run of this file adds only missing pairs. The depth cap stops a cycle in
-- hand-edited data from recursing without end.
INSERT IGNORE INTO ahg_storage_location_closure (ancestor, descendant, depth)
WITH RECURSIVE paths (ancestor, descendant, depth) AS (
    SELECT id, id, 0 FROM ahg_storage_location
    UNION ALL
    SELECT p.ancestor, c.id, p.depth + 1
    FROM paths p
    JOIN ahg_storage_location c ON c.parent_id = p.descendant
    WHERE p.depth < 100
)
SELECT ancestor, descendant, depth FROM paths;

-- Where a physical object is now. A small current-state index, written in the
-- same transaction as the movement row below, the same split as parent_id and
-- its closure: the log is the authoritative history, this is what browse reads
-- so it never has to work out the latest movement per object.
--
-- A separate table rather than a column on physical_object, because plugins do
-- not alter base AtoM tables.
CREATE TABLE IF NOT EXISTS ahg_physical_object_location (
    physical_object_id INT NOT NULL PRIMARY KEY,
    location_id        BIGINT UNSIGNED NOT NULL,
    created_at         TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX ix_pol_location (location_id),
    CONSTRAINT fk_pol_object   FOREIGN KEY (physical_object_id) REFERENCES physical_object(id)      ON DELETE CASCADE,
    CONSTRAINT fk_pol_location FOREIGN KEY (location_id)        REFERENCES ahg_storage_location(id) ON DELETE RESTRICT
);

-- Every move, append-only. A move recorded wrongly is corrected by a further
-- move, never by editing or deleting a row: a history somebody can quietly edit
-- demonstrates nothing about where the holdings have been.
--
-- NULL on one side is meaningful and load-bearing: from_location_id NULL is a
-- first placement, to_location_id NULL is a removal from storage. That is why
-- the foreign keys are RESTRICT and not SET NULL - nulling a deleted location's
-- id would silently turn "moved out of Room A" into "taken out of storage".
-- The name snapshots keep the row readable after a location is renamed, and
-- readable at all if a location is ever force-deleted with checks off.
CREATE TABLE IF NOT EXISTS ahg_storage_movement (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_type       VARCHAR(32) NOT NULL COMMENT 'physical_object, storage_location',
    subject_id         BIGINT UNSIGNED NOT NULL,
    subject_name       VARCHAR(255) DEFAULT NULL COMMENT 'snapshot, so history reads after a rename',
    from_location_id   BIGINT UNSIGNED DEFAULT NULL COMMENT 'object: NULL is a first placement. location: the old parent, NULL is the root',
    from_location_name VARCHAR(255) DEFAULT NULL,
    to_location_id     BIGINT UNSIGNED DEFAULT NULL COMMENT 'object: NULL is removal from storage. location: the new parent, NULL is the root',
    to_location_name   VARCHAR(255) DEFAULT NULL,
    batch_id           CHAR(36) DEFAULT NULL COMMENT 'one row per subject; shared id groups a bulk move',
    note               TEXT,
    user_id            INT DEFAULT NULL COMMENT 'no FK: history outlives the account',
    username           VARCHAR(255) DEFAULT NULL COMMENT 'snapshot, so a deleted account still reads',
    moved_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX ix_movement_subject (subject_type, subject_id, moved_at),
    INDEX ix_movement_batch (batch_id),
    INDEX ix_movement_from (from_location_id),
    INDEX ix_movement_to (to_location_id),
    INDEX ix_movement_moved_at (moved_at),
    CONSTRAINT fk_movement_from FOREIGN KEY (from_location_id) REFERENCES ahg_storage_location(id) ON DELETE RESTRICT,
    CONSTRAINT fk_movement_to   FOREIGN KEY (to_location_id)   REFERENCES ahg_storage_location(id) ON DELETE RESTRICT
);

SET FOREIGN_KEY_CHECKS = 1;
