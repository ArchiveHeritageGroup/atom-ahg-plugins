-- ============================================================================
-- heratio#143 Phase 1-3 + Spectrum#A — PSIS Symfony port (2026-05-23).
--
-- Run this ONCE against an existing installation to add the visual-workflow
-- + Spectrum-procedure schema. Idempotent re-runs not supported (MySQL has
-- no ADD COLUMN IF NOT EXISTS); check first with:
--
--   SELECT COUNT(*) FROM information_schema.COLUMNS
--   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ahg_workflow' AND COLUMN_NAME='spectrum_procedure';
--
-- If that returns 1, the migration has already been applied.
-- ============================================================================

ALTER TABLE `ahg_workflow` ADD COLUMN `spectrum_procedure` VARCHAR(64) NULL
  COMMENT 'Spectrum 5.1 procedure code (object_entry, acquisition, inventory, location_movement, cataloguing, object_exit, loans_in, loans_out, insurance, damage_loss, conservation, audit, condition_check, valuation, risk_management, emergency_planning, use_of_collections, rights_management, reproduction, deaccessioning, retrospective_doc), or NULL';
ALTER TABLE `ahg_workflow` ADD INDEX `ix_spectrum_procedure` (`spectrum_procedure`);

CREATE TABLE IF NOT EXISTS `ahg_workflow_edge` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workflow_id` INT NOT NULL,
    `from_step_id` INT NOT NULL,
    `to_step_id` INT NOT NULL,
    `condition_expr` VARCHAR(500) NULL COMMENT 'optional gating expression — free text for now, structure later',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `uq_workflow_edge` (`workflow_id`, `from_step_id`, `to_step_id`),
    INDEX `ix_workflow` (`workflow_id`),
    INDEX `ix_from_step` (`from_step_id`),
    INDEX `ix_to_step` (`to_step_id`),
    CONSTRAINT `fk_wfedge_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `ahg_workflow`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wfedge_from` FOREIGN KEY (`from_step_id`) REFERENCES `ahg_workflow_step`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wfedge_to` FOREIGN KEY (`to_step_id`) REFERENCES `ahg_workflow_step`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
