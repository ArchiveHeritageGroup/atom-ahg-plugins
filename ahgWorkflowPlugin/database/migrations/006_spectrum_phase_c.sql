-- ============================================================================
-- Spectrum Phase C — PSIS Symfony port (2026-05-23).
--
-- Run this ONCE against an existing install to add per-object compliance
-- cache + cross-procedure chain rules. Idempotent — uses CREATE TABLE IF NOT EXISTS.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ahg_spectrum_object_compliance` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `object_id` INT NOT NULL,
    `object_type` VARCHAR(50) NOT NULL DEFAULT 'information_object',
    `spectrum_procedure` VARCHAR(64) NOT NULL COMMENT 'one of the 21 Spectrum procedure codes',
    `status` VARCHAR(20) NOT NULL DEFAULT 'not_started'
      COMMENT 'one of: not_started, in_progress, completed, overdue, rejected',
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `last_task_id` INT NULL,
    `last_computed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    UNIQUE KEY `uq_object_procedure` (`object_id`, `object_type`, `spectrum_procedure`),
    INDEX `ix_procedure_status` (`spectrum_procedure`, `status`),
    INDEX `ix_object` (`object_id`, `object_type`),
    INDEX `ix_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ahg_spectrum_chain_rule` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `from_procedure` VARCHAR(64) NOT NULL,
    `to_procedure` VARCHAR(64) NOT NULL,
    `trigger_event` VARCHAR(20) NOT NULL DEFAULT 'on_complete',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `notes` TEXT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    UNIQUE KEY `uq_chain` (`from_procedure`, `to_procedure`, `trigger_event`),
    INDEX `ix_from` (`from_procedure`, `is_active`),
    INDEX `ix_to` (`to_procedure`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
