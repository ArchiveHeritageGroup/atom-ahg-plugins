-- ahgRdmPlugin install schema — reverse port of Heratio ahg-rdm (heratio#1338).
-- PSIS/AtoM port: atom-ahg-plugins#168 (Phase 1).
--
-- A Dataset is a thin wrapper: it links a research project to a container
-- information_object (io_parent_id) under which deposited files live as child
-- IOs (each with a digital_object), created the same way ahgIngestPlugin creates
-- them. No bespoke file storage — digital_object stays the single source of truth.
--
-- These are AHG SIDECAR tables only. They NEVER ALTER a Qubit/AtoM base table
-- (information_object, digital_object, object, …) and use no MySQL ENUM —
-- controlled values come from ahg_dropdown (taxonomies dataset_status /
-- rdm_disposition), per the project rules.

CREATE TABLE IF NOT EXISTS rdm_dataset (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id      INT NULL,                       -- FK research_project.id (nullable: standalone dataset allowed)
    dmp_id          INT NULL,                       -- FK-by-convention research_dmp.id (Feature 1, later phase)
    io_parent_id    INT NOT NULL,                   -- container information_object.id; files deposit as its children
    title           VARCHAR(500) NOT NULL,
    description     TEXT NULL,
    -- status drives the deposit -> scan -> review -> publish lifecycle; values
    -- come from ahg_dropdown (taxonomy 'dataset_status'), NEVER a MySQL ENUM.
    status          VARCHAR(40) NOT NULL DEFAULT 'draft',
    -- POPIA scan verdict (CLEAR | PERSONAL | SPECIAL_CATEGORY), set by PopiaScanService.
    verdict         VARCHAR(32) NULL,
    scanned_at      TIMESTAMP NULL,
    -- Human-gate disposition (later phase): restrict|embargo|de-identify|release.
    -- 'release' (open) is blocked while any PERSONAL/SPECIAL finding is unresolved.
    disposition     VARCHAR(40) NULL,
    disposition_by  INT NULL,
    disposition_at  TIMESTAMP NULL,
    doi             VARCHAR(255) NULL,
    created_by      INT NULL,                       -- auth user id of the depositor
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rdm_dataset_project (project_id),
    KEY idx_rdm_dataset_dmp (dmp_id),
    KEY idx_rdm_dataset_io (io_parent_id),
    KEY idx_rdm_dataset_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rdm_dataset_file (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dataset_id      INT UNSIGNED NOT NULL,          -- FK rdm_dataset.id
    io_id           INT NOT NULL,                   -- the child information_object created for this file
    do_id           INT NULL,                       -- the master digital_object.id
    original_name   VARCHAR(1024) NOT NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rdm_file_dataset (dataset_id),
    KEY idx_rdm_file_io (io_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- POPIA scan findings (later phase). One row per detected item. The scan NEVER
-- auto-decides: findings land 'pending' and a human confirms/overrides each.
-- 'method' = deterministic | ner | lexicon; 'sample' is a MASKED snippet
-- (never the full PII value).
CREATE TABLE IF NOT EXISTS rdm_scan_finding (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dataset_id      INT UNSIGNED NOT NULL,
    dataset_file_id INT UNSIGNED NULL,              -- FK rdm_dataset_file.id
    file_name       VARCHAR(1024) NULL,
    type            VARCHAR(60) NOT NULL,           -- sa_id_number|email|phone|passport|person|location|org|special_category
    category        VARCHAR(40) NOT NULL DEFAULT 'personal', -- personal | special_category
    sample          VARCHAR(255) NULL,              -- MASKED
    confidence      VARCHAR(20) NOT NULL DEFAULT 'high',     -- high | medium | low (AI-suggested = medium/low)
    method          VARCHAR(20) NOT NULL DEFAULT 'deterministic',
    review_status   VARCHAR(20) NOT NULL DEFAULT 'pending',  -- pending|confirmed|dismissed (set by the human gate)
    reviewed_by     INT NULL,
    reviewed_at     TIMESTAMP NULL,
    decision_note   VARCHAR(500) NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rdm_finding_dataset (dataset_id),
    KEY idx_rdm_finding_status (review_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotent guarded ADD COLUMNs for installs whose rdm_dataset predates a
-- column (the CREATE above is skipped when the table already exists).
SET @c := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rdm_dataset' AND column_name = 'verdict');
SET @s := IF(@c = 0, 'ALTER TABLE rdm_dataset ADD COLUMN verdict VARCHAR(32) NULL AFTER status, ADD COLUMN scanned_at TIMESTAMP NULL AFTER verdict', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rdm_dataset' AND column_name = 'disposition');
SET @s := IF(@c = 0, 'ALTER TABLE rdm_dataset ADD COLUMN disposition VARCHAR(40) NULL AFTER scanned_at, ADD COLUMN disposition_by INT NULL AFTER disposition, ADD COLUMN disposition_at TIMESTAMP NULL AFTER disposition_by', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rdm_dataset' AND column_name = 'dmp_id');
SET @s := IF(@c = 0, 'ALTER TABLE rdm_dataset ADD COLUMN dmp_id INT NULL AFTER project_id, ADD KEY idx_rdm_dataset_dmp (dmp_id)', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rdm_scan_finding' AND column_name = 'reviewed_by');
SET @s := IF(@c = 0, 'ALTER TABLE rdm_scan_finding ADD COLUMN reviewed_by INT NULL AFTER review_status, ADD COLUMN reviewed_at TIMESTAMP NULL AFTER reviewed_by, ADD COLUMN decision_note VARCHAR(500) NULL AFTER reviewed_at', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- #176: protected-object relocation map. When a dataset is restricted/embargoed,
-- its digital_object files are MOVED out of the public /uploads tree into a
-- non-web-served protected dir, so a guessed raw URL can't fetch the bytes. One
-- row per relocated digital_object; the authed download controller serves them
-- via X-Accel-Redirect after an ODRL check. release() moves them back + clears.
CREATE TABLE IF NOT EXISTS rdm_protected_object (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dataset_id     INT UNSIGNED NOT NULL,           -- FK rdm_dataset.id
    io_id          INT NOT NULL,                    -- information_object.id the DO hangs on
    do_id          INT NOT NULL,                    -- digital_object.id that was moved
    original_path  VARCHAR(1024) NOT NULL,          -- web-relative path it was moved FROM (e.g. /uploads/r/.../file)
    protected_path VARCHAR(1024) NOT NULL,          -- absolute path it was moved TO
    moved_at       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rdm_protected_do (do_id),
    KEY idx_rdm_protected_dataset (dataset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/seed_dropdowns.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ahgRdmPlugin dropdown seed — reverse port of Heratio ahg-rdm (heratio#1338/#1340).
-- INSERT IGNORE so re-runs never duplicate. Views read these from ahg_dropdown —
-- no hardcoded <option> lists, no ENUM column.

-- Dataset lifecycle statuses (taxonomy 'dataset_status').
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, icon, sort_order, is_active, created_at) VALUES
 ('dataset_status', 'Dataset Status', 'rdm', 'draft',      'Draft',                  '#6c757d', NULL, 10, 1, NOW()),
 ('dataset_status', 'Dataset Status', 'rdm', 'scanning',   'POPIA scanning',         '#0dcaf0', NULL, 20, 1, NOW()),
 ('dataset_status', 'Dataset Status', 'rdm', 'review',     'Awaiting human review',  '#ffc107', NULL, 30, 1, NOW()),
 ('dataset_status', 'Dataset Status', 'rdm', 'restricted', 'Restricted / embargoed', '#dc3545', NULL, 40, 1, NOW()),
 ('dataset_status', 'Dataset Status', 'rdm', 'published',  'Published (open)',       '#198754', NULL, 50, 1, NOW());

-- Human-gate disposition (taxonomy 'rdm_disposition'): the access decision a
-- reviewer applies after confirming/dismissing findings. 'release' = open;
-- blocked while PII is unresolved.
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, icon, sort_order, is_active, created_at) VALUES
 ('rdm_disposition', 'Dataset Disposition', 'rdm', 'restrict',    'Restrict access',          '#dc3545', NULL, 10, 1, NOW()),
 ('rdm_disposition', 'Dataset Disposition', 'rdm', 'embargo',     'Embargo (time-limited)',   '#fd7e14', NULL, 20, 1, NOW()),
 ('rdm_disposition', 'Dataset Disposition', 'rdm', 'de-identify', 'De-identify then release', '#0dcaf0', NULL, 30, 1, NOW()),
 ('rdm_disposition', 'Dataset Disposition', 'rdm', 'release',     'Release (open access)',    '#198754', NULL, 40, 1, NOW());
