-- ahgIntegrityPlugin - Database Schema
-- Enterprise-grade automated integrity assurance

-- ============================================================
-- Table: integrity_schedule
-- Scoped verification schedules with concurrency controls
-- ============================================================
CREATE TABLE IF NOT EXISTS `integrity_schedule` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `scope_type` ENUM('global','repository','hierarchy') NOT NULL DEFAULT 'global',
    `repository_id` INT NULL,
    `information_object_id` INT NULL,
    `algorithm` ENUM('sha256','sha512') NOT NULL DEFAULT 'sha256',
    `frequency` ENUM('daily','weekly','monthly','ad_hoc') NOT NULL DEFAULT 'weekly',
    `cron_expression` VARCHAR(100) NULL COMMENT 'Optional cron expression override',
    `batch_size` INT UNSIGNED NOT NULL DEFAULT 200,
    `io_throttle_ms` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Microsleep between objects (ms)',
    `max_memory_mb` INT UNSIGNED NOT NULL DEFAULT 512,
    `max_runtime_minutes` INT UNSIGNED NOT NULL DEFAULT 120,
    `max_concurrent_runs` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `last_run_at` DATETIME NULL,
    `next_run_at` DATETIME NULL,
    `total_runs` INT UNSIGNED NOT NULL DEFAULT 0,
    `notify_on_failure` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_on_mismatch` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_email` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_is_schedule_enabled` (`is_enabled`),
    INDEX `idx_is_schedule_next_run` (`next_run_at`),
    INDEX `idx_is_schedule_scope` (`scope_type`, `repository_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: integrity_run
-- Execution records for scheduled or manual verification runs
-- ============================================================
CREATE TABLE IF NOT EXISTS `integrity_run` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` BIGINT UNSIGNED NULL,
    `status` ENUM('running','completed','partial','failed','timeout','cancelled') NOT NULL DEFAULT 'running',
    `algorithm` ENUM('sha256','sha512') NOT NULL DEFAULT 'sha256',
    `objects_scanned` INT UNSIGNED NOT NULL DEFAULT 0,
    `objects_passed` INT UNSIGNED NOT NULL DEFAULT 0,
    `objects_failed` INT UNSIGNED NOT NULL DEFAULT 0,
    `objects_missing` INT UNSIGNED NOT NULL DEFAULT 0,
    `objects_error` INT UNSIGNED NOT NULL DEFAULT 0,
    `objects_skipped` INT UNSIGNED NOT NULL DEFAULT 0,
    `bytes_scanned` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `triggered_by` ENUM('scheduler','manual','cli','api') NOT NULL DEFAULT 'manual',
    `triggered_by_user` VARCHAR(255) NULL,
    `lock_token` VARCHAR(64) NULL,
    `error_message` TEXT NULL,
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ir_schedule` (`schedule_id`),
    INDEX `idx_ir_status` (`status`),
    INDEX `idx_ir_started` (`started_at`),
    CONSTRAINT `fk_ir_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `integrity_schedule`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: integrity_ledger
-- Append-only verification ledger. NEVER UPDATE or DELETE rows.
-- ============================================================
CREATE TABLE IF NOT EXISTS `integrity_ledger` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `run_id` BIGINT UNSIGNED NULL,
    `digital_object_id` INT NOT NULL COMMENT 'No FK — survives object deletion',
    `information_object_id` INT NULL COMMENT 'Denormalized for scoped queries',
    `repository_id` INT NULL COMMENT 'Denormalized for scoped queries',
    `file_path` VARCHAR(1024) NULL,
    `file_size` BIGINT UNSIGNED NULL,
    `file_exists` TINYINT(1) NOT NULL DEFAULT 0,
    `file_readable` TINYINT(1) NOT NULL DEFAULT 0,
    `algorithm` VARCHAR(10) NOT NULL,
    `expected_hash` VARCHAR(128) NULL,
    `computed_hash` VARCHAR(128) NULL,
    `hash_match` TINYINT(1) NULL,
    `outcome` ENUM('pass','mismatch','missing','unreadable','permission_error','path_drift','no_baseline','error') NOT NULL,
    `error_detail` TEXT NULL,
    `duration_ms` INT UNSIGNED NULL,
    `verified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_il_run` (`run_id`),
    INDEX `idx_il_digital_object` (`digital_object_id`),
    INDEX `idx_il_outcome` (`outcome`),
    INDEX `idx_il_verified` (`verified_at`),
    INDEX `idx_il_repository` (`repository_id`),
    INDEX `idx_il_info_object` (`information_object_id`),
    CONSTRAINT `fk_il_run` FOREIGN KEY (`run_id`) REFERENCES `integrity_run`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: integrity_dead_letter
-- Persistent failure queue for objects that fail repeatedly
-- ============================================================
CREATE TABLE IF NOT EXISTS `integrity_dead_letter` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `digital_object_id` INT NOT NULL,
    `failure_type` ENUM('mismatch','missing','unreadable','permission_error','path_drift','error') NOT NULL,
    `status` ENUM('open','acknowledged','investigating','resolved','ignored') NOT NULL DEFAULT 'open',
    `consecutive_failures` INT UNSIGNED NOT NULL DEFAULT 1,
    `first_failure_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_failure_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_error_detail` TEXT NULL,
    `last_run_id` BIGINT UNSIGNED NULL,
    `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_retries` INT UNSIGNED NOT NULL DEFAULT 3,
    `next_retry_at` DATETIME NULL,
    `acknowledged_by` VARCHAR(255) NULL,
    `acknowledged_at` DATETIME NULL,
    `resolution_notes` TEXT NULL,
    `resolved_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_idl_object_failure` (`digital_object_id`, `failure_type`),
    INDEX `idx_idl_status` (`status`),
    INDEX `idx_idl_next_retry` (`next_retry_at`),
    CONSTRAINT `fk_idl_run` FOREIGN KEY (`last_run_id`) REFERENCES `integrity_run`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed data: 2 default schedules
-- ============================================================
INSERT IGNORE INTO `integrity_schedule` (`id`, `name`, `description`, `scope_type`, `algorithm`, `frequency`, `batch_size`, `io_throttle_ms`, `max_memory_mb`, `max_runtime_minutes`, `max_concurrent_runs`, `is_enabled`, `next_run_at`, `notify_on_failure`, `notify_on_mismatch`)
VALUES
(1, 'Daily Sample Check', 'Verifies a sample of 200 digital objects daily to detect early signs of data corruption', 'global', 'sha256', 'daily', 200, 10, 512, 30, 1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 1, 1),
(2, 'Weekly Full Scan', 'Comprehensive weekly verification of all master digital objects across all repositories', 'global', 'sha256', 'weekly', 0, 5, 1024, 480, 1, 0, NULL, 1, 1);
