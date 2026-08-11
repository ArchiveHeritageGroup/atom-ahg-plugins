-- ============================================================
-- Migration: per-step evidence, and per-procedure outcome proposals
--
-- Two tables, both keyed the way the workflow already keys things, so they need
-- no per-procedure code:
--
--   spectrum_evidence           (procedure_type, record_id, step_key)
--                               mirrors spectrum_workflow_step_state
--   spectrum_outcome_proposal   (procedure_type, record_id, handler)
--
-- Evidence stores ONLY the stored filename, never a path. The directory is
-- recomputed server-side from the procedure and record. That removes the whole
-- class of stored-path traversal, and avoids the three incompatible file_path
-- conventions already in this codebase (relative-to-upload-dir, /uploads/... web
-- path, and absolute).
-- ============================================================

CREATE TABLE IF NOT EXISTS `spectrum_evidence` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `procedure_type` VARCHAR(64) NOT NULL,
    `record_id` INT NOT NULL COMMENT '0 = institution-level procedure, matching spectrum_workflow_state',
    `step_key` VARCHAR(100) DEFAULT NULL COMMENT 'null = evidence for the procedure as a whole',
    `evidence_type` VARCHAR(50) NOT NULL DEFAULT 'document'
        COMMENT 'document, report, certificate, photograph, correspondence, invoice, receipt, other',
    `original_name` VARCHAR(255) NOT NULL COMMENT 'as uploaded, for display and download only',
    `stored_name` VARCHAR(128) NOT NULL COMMENT 'random; the directory is derived, never stored',
    `mime_type` VARCHAR(128) DEFAULT NULL,
    `size_bytes` BIGINT UNSIGNED DEFAULT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `note` TEXT,
    `uploaded_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_evidence_stored` (`stored_name`),
    KEY `idx_evidence_step` (`procedure_type`, `record_id`, `step_key`),
    KEY `idx_evidence_record` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spectrum_outcome_proposal` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `procedure_type` VARCHAR(64) NOT NULL,
    `record_id` INT NOT NULL,
    `handler` VARCHAR(64) NOT NULL COMMENT 'key into the outcome handler registry',
    `payload` JSON NOT NULL COMMENT 'what the handler proposes to write, for review before it is written',
    `summary` VARCHAR(255) DEFAULT NULL COMMENT 'one line for the review list',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending'
        COMMENT 'pending, accepted, rejected, superseded, failed',
    `proposed_by` INT DEFAULT NULL,
    `proposed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `decided_by` INT DEFAULT NULL,
    `decided_at` DATETIME DEFAULT NULL,
    `decision_note` TEXT,
    `result_note` TEXT COMMENT 'what actually happened when it was applied, including failure',
    PRIMARY KEY (`id`),
    KEY `idx_proposal_status` (`status`, `procedure_type`),
    KEY `idx_proposal_record` (`record_id`, `procedure_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Declare the first outcome: an approved valuation proposes a
-- revaluation of the heritage asset. Nothing is posted by the
-- transition; the proposal is reviewed on the GRAP screen.
--
-- requires_evidence: a valuation with no valuer's report attached
-- raises nothing. An accounting figure should be able to point at
-- the document it came from.
-- ------------------------------------------------------------
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(
        config_json,
        '$.outcomes',
        CAST('[{"on_state": "approved", "handler": "heritage_revaluation", "requires_evidence": true}]' AS JSON)
    )
WHERE procedure_type = 'valuation' AND is_active = 1;

-- ------------------------------------------------------------
-- Conservation: completing a treatment proposes the condition it
-- left the object in. Not an accounting outcome - proof that the
-- mechanism is not accounting-specific.
--
-- heritage_asset.last_condition_assessment has never been written
-- by any code, while spectrum_condition_check holds real rows: the
-- two plugins hold halves of the same fact.
-- ------------------------------------------------------------
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(
        config_json,
        '$.outcomes',
        CAST('[{"on_state": "completed", "handler": "conservation_record", "requires_evidence": false}]' AS JSON)
    )
WHERE procedure_type = 'conservation' AND is_active = 1;
