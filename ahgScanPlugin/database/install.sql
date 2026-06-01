-- =====================================================
-- ahgScanPlugin Database Schema
--
-- Watched-folder streaming ingest. Builds on ahgIngestPlugin's
-- ingest_session / ingest_row / ingest_job tables (dependency).
--
-- Idempotent: CREATE TABLE IF NOT EXISTS + guarded ALTERs (no
-- ADD COLUMN IF NOT EXISTS — MySQL 8 does not support it).
-- No ENUM columns. No FOREIGN KEY to core AtoM tables.
-- Never INSERT INTO atom_plugin.
-- =====================================================

-- ---------------------------------------------------------------------------
-- 1. scan_folder: watched-folder configuration
--    Each folder binds 1:1 to an ingest_session (session_kind='watched_folder')
--    that holds the processing config (sector, standard, parent, derivatives...).
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS scan_folder (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL,
    label VARCHAR(255) NOT NULL,
    path VARCHAR(1024) NOT NULL,
    layout VARCHAR(32) NOT NULL DEFAULT 'flat' COMMENT 'flat, path',
    ingest_session_id INT NOT NULL,
    disposition_success VARCHAR(32) NOT NULL DEFAULT 'move' COMMENT 'move, delete, leave',
    disposition_failure VARCHAR(32) NOT NULL DEFAULT 'quarantine' COMMENT 'quarantine, leave',
    processed_path VARCHAR(1024) DEFAULT NULL COMMENT 'archive dir for successful files; default <path>/.processed',
    failed_path VARCHAR(1024) DEFAULT NULL COMMENT 'quarantine dir for failed files; default <path>/.failed',
    min_quiet_seconds INT NOT NULL DEFAULT 10 COMMENT 'file must be idle this long before ingest',
    auto_commit TINYINT(1) NOT NULL DEFAULT 1,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_scanned_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_scan_folder_code (code),
    KEY ix_scan_folder_enabled (enabled),
    KEY ix_scan_folder_session (ingest_session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. scan_event: per-pass audit log (one row per watcher pass per folder)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS scan_event (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folder_id INT NOT NULL,
    detected INT NOT NULL DEFAULT 0 COMMENT 'files seen on disk this pass',
    enqueued INT NOT NULL DEFAULT 0 COMMENT 'new files staged as ingest_row',
    skipped_duplicate INT NOT NULL DEFAULT 0,
    skipped_quiet INT NOT NULL DEFAULT 0 COMMENT 'still being written',
    failed INT NOT NULL DEFAULT 0,
    job_id INT DEFAULT NULL COMMENT 'ingest_job created/launched this pass',
    status VARCHAR(32) NOT NULL DEFAULT 'completed' COMMENT 'completed, failed, idle',
    message TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY ix_scan_event_folder (folder_id),
    KEY ix_scan_event_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. Streaming-mode columns on ingest_session (added by ahgIngestPlugin's
--    base schema only in newer builds — add here if missing so a watched
--    folder's backing session is distinguishable from a wizard session).
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- 2b. Guarded ALTERs for scan_folder. The CREATE above only fires on a fresh
--     DB; an older Heratio-parity migration may have already created
--     scan_folder WITHOUT auto_commit/processed_path/failed_path/created_by.
--     CREATE TABLE IF NOT EXISTS then no-ops, leaving the table short of the
--     columns WatchedFolderService::create()/update() write. Add them here so
--     install is idempotent against both a fresh and a legacy scan_folder.
-- ---------------------------------------------------------------------------

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scan_folder' AND COLUMN_NAME = 'auto_commit');
SET @sql := IF(@col = 0,
    'ALTER TABLE scan_folder ADD COLUMN auto_commit TINYINT(1) NOT NULL DEFAULT 1 AFTER min_quiet_seconds',
    'SELECT ''scan_folder.auto_commit exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scan_folder' AND COLUMN_NAME = 'processed_path');
SET @sql := IF(@col = 0,
    'ALTER TABLE scan_folder ADD COLUMN processed_path VARCHAR(1024) DEFAULT NULL COMMENT ''archive dir for successful files; default <path>/.processed'' AFTER disposition_failure',
    'SELECT ''scan_folder.processed_path exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scan_folder' AND COLUMN_NAME = 'failed_path');
SET @sql := IF(@col = 0,
    'ALTER TABLE scan_folder ADD COLUMN failed_path VARCHAR(1024) DEFAULT NULL COMMENT ''quarantine dir for failed files; default <path>/.failed'' AFTER processed_path',
    'SELECT ''scan_folder.failed_path exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scan_folder' AND COLUMN_NAME = 'created_by');
SET @sql := IF(@col = 0,
    'ALTER TABLE scan_folder ADD COLUMN created_by INT DEFAULT NULL AFTER last_scanned_at',
    'SELECT ''scan_folder.created_by exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ingest_session' AND COLUMN_NAME = 'session_kind');
SET @sql := IF(@col = 0,
    'ALTER TABLE ingest_session ADD COLUMN session_kind VARCHAR(32) NOT NULL DEFAULT ''wizard'' COMMENT ''wizard, watched_folder'' AFTER entity_type, ADD KEY ix_session_kind (session_kind)',
    'SELECT ''ingest_session.session_kind exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ingest_session' AND COLUMN_NAME = 'source_ref');
SET @sql := IF(@col = 0,
    'ALTER TABLE ingest_session ADD COLUMN source_ref VARCHAR(255) DEFAULT NULL COMMENT ''scan_folder.code or other source identifier''',
    'SELECT ''ingest_session.source_ref exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
