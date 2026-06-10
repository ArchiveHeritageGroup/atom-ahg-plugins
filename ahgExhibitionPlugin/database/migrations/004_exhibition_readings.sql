-- #136 analytics + conservation-forecast (Heratio parity, heratio#1146/1148/1173/1187/1188).
-- New tables backing the analytics + forecast pages:
--   ahg_exhibition_reading        sensor / occupancy metrics per space
--   ahg_exhibition_visit          one row per walkthrough session (auto visitor analytics)
--   ahg_exhibition_visit_event    per-object / tour / door events within a visit
--   ahg_exhibition_alert          conservation-threshold breaches raised on sensor ingest
-- Column names match Heratio so the ported ExhibitionSpaceService methods run verbatim.
-- Idempotent: CREATE TABLE IF NOT EXISTS (MySQL native). Safe to re-run.

-- ── heratio#1146 — sensor / occupancy readings per space ────────────────────
CREATE TABLE IF NOT EXISTS `ahg_exhibition_reading` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exhibition_space_id` INT NOT NULL,
    `metric` VARCHAR(32) NOT NULL COMMENT 'lux, temp_c, humidity, visitors',
    `value` DECIMAL(10,2) NOT NULL,
    `recorded_at` DATETIME NOT NULL,
    INDEX `idx_space_metric_time` (`exhibition_space_id`, `metric`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── heratio#1173 — automatic visitor analytics: one row per walkthrough session ─
CREATE TABLE IF NOT EXISTS `ahg_exhibition_visit` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `building_id` VARCHAR(64) NOT NULL,
    `session_token` VARCHAR(64) NOT NULL,
    `device` VARCHAR(16) NULL,
    `cur_room` INT NULL,
    `room_entered_at` DATETIME NULL,
    `room_seconds_json` JSON NULL,
    `started_at` DATETIME NOT NULL,
    `last_seen` DATETIME NOT NULL,
    UNIQUE KEY `uq_visit` (`building_id`, `session_token`),
    INDEX `idx_building_started` (`building_id`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── heratio#1173 — per-object / tour / door events within a visit ───────────
CREATE TABLE IF NOT EXISTS `ahg_exhibition_visit_event` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `building_id` VARCHAR(64) NOT NULL,
    `session_token` VARCHAR(64) NOT NULL,
    `type` VARCHAR(16) NOT NULL COMMENT 'object, tour, door',
    `room_id` INT NULL,
    `object_id` INT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_building_type` (`building_id`, `type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── heratio#1188 — conservation threshold alerts raised on sensor ingest ────
CREATE TABLE IF NOT EXISTS `ahg_exhibition_alert` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exhibition_space_id` INT NOT NULL,
    `metric` VARCHAR(24) NOT NULL,
    `value` DECIMAL(10,3) NULL,
    `threshold` VARCHAR(64) NULL,
    `severity` VARCHAR(12) NOT NULL DEFAULT 'warning' COMMENT 'warning, critical',
    `message` VARCHAR(255) NULL,
    `acknowledged` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_space_created` (`exhibition_space_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
