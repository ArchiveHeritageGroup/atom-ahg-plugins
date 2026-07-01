-- Per-record Spectrum workflow step checklist (tick steps off independently of
-- the approval state machine). One row per (procedure_type, record_id, step_key).
-- Run once per instance (idempotent).

CREATE TABLE IF NOT EXISTS spectrum_workflow_step_state (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    procedure_type VARCHAR(64) NOT NULL,
    record_id INT NOT NULL,
    step_key VARCHAR(100) NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    completed_by INT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_step (procedure_type, record_id, step_key),
    KEY idx_record (record_id, procedure_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
