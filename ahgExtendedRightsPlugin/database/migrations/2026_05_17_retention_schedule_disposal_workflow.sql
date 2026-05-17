-- ahgExtendedRightsPlugin — retention schedule + disposal workflow (2026-05-17)
--
-- Records-management framework support: File-Plan-driven retention schedules
-- with multi-stage disposal workflow (records officer → legal → executive)
-- and full audit dual-write. Suitable for NARSSA, NARA, PRO Act (UK), ISO
-- 15489, and equivalent national archival frameworks.
--
-- BASE ATOM IS NOT MODIFIED. All new tables use FKs to base AtoM tables
-- for read-only referential integrity only.
--
-- Idempotent: CREATE TABLE IF NOT EXISTS guards every table.

-- ------------------------------------------------------------------
-- 1. retention_schedule — one row per File Plan record-series category.
--    Operators supply their organisation's File Plan codes via this table.
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS retention_schedule (
    id                       INT NOT NULL AUTO_INCREMENT,
    code                     VARCHAR(50)  NOT NULL COMMENT 'Operator-friendly identifier from the organisation File Plan',
    title                    VARCHAR(255) NOT NULL COMMENT 'Human-readable label, e.g. Communications: Press Releases',
    description              TEXT         NULL,
    active_period_years      INT NOT NULL DEFAULT 5  COMMENT 'Years the record is operationally active',
    dormant_period_years     INT NOT NULL DEFAULT 0  COMMENT 'Years held after active period before disposal trigger',
    trigger_event            VARCHAR(50)  NOT NULL DEFAULT 'creation_date' COMMENT 'creation_date, file_closure, fiscal_year_end, contract_end, employment_end',
    disposal_action          VARCHAR(20)  NOT NULL DEFAULT 'review' COMMENT 'destroy, transfer_narssa, transfer_other, review, permanent',
    legal_basis              VARCHAR(255) NULL COMMENT 'Statutory authority for the schedule, e.g. NARSSA Act 1996 §13(2)(d)',
    requires_legal_signoff   TINYINT(1) NOT NULL DEFAULT 0,
    requires_executive_signoff TINYINT(1) NOT NULL DEFAULT 0,
    is_active                TINYINT(1) NOT NULL DEFAULT 1,
    created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_code (code),
    KEY idx_disposal_action (disposal_action),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------
-- 2. retention_assignment — one row per (information_object, schedule).
--    The actual link between a record and its retention rule.
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS retention_assignment (
    id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    information_object_id     INT NOT NULL,
    retention_schedule_id     INT NOT NULL,
    trigger_event_date        DATE NOT NULL COMMENT 'When the retention clock starts',
    calculated_disposal_due   DATE NOT NULL COMMENT 'trigger_event_date + active_period_years + dormant_period_years',
    assigned_by               INT NULL COMMENT 'FK user.id',
    notes                     TEXT NULL,
    created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_io (information_object_id),
    KEY idx_schedule (retention_schedule_id),
    KEY idx_due (calculated_disposal_due),
    CONSTRAINT fk_ra_io FOREIGN KEY (information_object_id)
        REFERENCES information_object(id) ON DELETE CASCADE,
    CONSTRAINT fk_ra_sch FOREIGN KEY (retention_schedule_id)
        REFERENCES retention_schedule(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------
-- 3. disposal_action — one row per disposal decision (the workflow record).
--    Multi-stage sign-off chain: records_officer → legal → executive → executed.
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS disposal_action (
    id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    information_object_id       INT NOT NULL,
    retention_assignment_id     BIGINT UNSIGNED NULL,
    action_type                 VARCHAR(20) NOT NULL COMMENT 'destroy, transfer_narssa, transfer_other, review, defer',
    status                      VARCHAR(30) NOT NULL DEFAULT 'proposed' COMMENT 'proposed, officer_signed, legal_signed, executive_signed, approved, executed, rejected, deferred',
    proposed_by                 INT NULL,
    proposed_at                 DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    officer_signed_by           INT NULL,
    officer_signed_at           DATETIME NULL,
    legal_signed_by             INT NULL,
    legal_signed_at             DATETIME NULL,
    executive_signed_by         INT NULL,
    executive_signed_at         DATETIME NULL,
    executed_by                 INT NULL,
    executed_at                 DATETIME NULL,
    rejected_by                 INT NULL,
    rejected_at                 DATETIME NULL,
    rejection_reason            TEXT NULL,
    transfer_destination        VARCHAR(255) NULL COMMENT 'NARSSA / other-archive identifier when action_type starts with transfer_',
    transfer_manifest_path      VARCHAR(500) NULL COMMENT 'Path to the generated NARSSA transfer .tar.gz when applicable',
    notes                       TEXT NULL,
    created_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_io (information_object_id),
    KEY idx_assignment (retention_assignment_id),
    KEY idx_status (status),
    KEY idx_action_type (action_type),
    CONSTRAINT fk_da_io FOREIGN KEY (information_object_id)
        REFERENCES information_object(id) ON DELETE CASCADE,
    CONSTRAINT fk_da_ra FOREIGN KEY (retention_assignment_id)
        REFERENCES retention_assignment(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------
-- 4. Seed example schedules so demo PSIS has something visible.
-- ------------------------------------------------------------------
INSERT IGNORE INTO retention_schedule (code, title, description, active_period_years, dormant_period_years, trigger_event, disposal_action, legal_basis, requires_legal_signoff, requires_executive_signoff, is_active) VALUES
  ('COMM-001', 'Press releases (general)',           'Routine media releases', 2, 3, 'creation_date', 'destroy',         'NARSSA Act 1996 §13(2)(a)', 0, 0, 1),
  ('COMM-002', 'Cabinet briefings',                  'Briefings prepared for Cabinet',   5, 25, 'creation_date', 'transfer_narssa', 'NARSSA Act 1996 §13(2)(d)', 1, 1, 1),
  ('CORP-001', 'Annual reports',                     'Annual reports — permanent retention', 5,  0, 'creation_date', 'permanent',       'NARSSA Act 1996 §13(2)(c)', 0, 1, 1),
  ('CORP-002', 'Procurement records',                'Section 217 audit trail',          5,  7, 'fiscal_year_end', 'destroy',       'PFMA + NARSSA',             1, 0, 1),
  ('HR-001',   'Employee personnel files',           'Employee records',                 7, 30, 'employment_end',  'destroy',       'BCEA + POPIA',              1, 0, 1),
  ('LEG-001',  'Legal opinions / counsel records',   'Legal advice retained 25y',        5, 20, 'creation_date',   'review',        'NARSSA + Attorney-Client',   1, 1, 1);
