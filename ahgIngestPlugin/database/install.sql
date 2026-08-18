-- =====================================================
-- ahgIngestPlugin Database Schema
-- =====================================================

-- Ingest sessions (wizard state persistence)
CREATE TABLE IF NOT EXISTS ingest_session (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(500),
    sector VARCHAR(50) COMMENT 'archive, museum, library, gallery, dam' NOT NULL DEFAULT 'archive',
    standard VARCHAR(47) COMMENT 'isadg, dc, spectrum, cco, rad, dacs' NOT NULL DEFAULT 'isadg',
    repository_id INT DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    parent_placement VARCHAR(51) COMMENT 'existing, new, top_level, csv_hierarchy' DEFAULT 'top_level',
    new_parent_title VARCHAR(500) DEFAULT NULL,
    new_parent_level VARCHAR(100) DEFAULT NULL,
    output_create_records TINYINT(1) DEFAULT 1,
    output_generate_sip TINYINT(1) DEFAULT 0,
    output_generate_aip TINYINT(1) DEFAULT 0,
    output_generate_dip TINYINT(1) DEFAULT 0,
    output_sip_path VARCHAR(1000) DEFAULT NULL,
    output_aip_path VARCHAR(1000) DEFAULT NULL,
    output_dip_path VARCHAR(1000) DEFAULT NULL,
    derivative_thumbnails TINYINT(1) DEFAULT 1,
    derivative_reference TINYINT(1) DEFAULT 1,
    derivative_normalize_format VARCHAR(50) DEFAULT NULL,
    security_classification_id INT DEFAULT NULL,
    process_ner TINYINT(1) DEFAULT 0,
    process_ocr TINYINT(1) DEFAULT 0,
    process_virus_scan TINYINT(1) DEFAULT 1,
    process_summarize TINYINT(1) DEFAULT 0,
    process_spellcheck TINYINT(1) DEFAULT 0,
    process_translate TINYINT(1) DEFAULT 0,
    process_translate_lang VARCHAR(10) DEFAULT NULL,
    process_format_id TINYINT(1) DEFAULT 0,
    process_face_detect TINYINT(1) DEFAULT 0,
    entity_type VARCHAR(30) NOT NULL DEFAULT 'description' COMMENT 'description, accession',
    status VARCHAR(91) COMMENT 'configure, upload, map, validate, preview, commit, completed, failed, cancelled' DEFAULT 'configure',
    config JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uploaded files for an ingest session
CREATE TABLE IF NOT EXISTS `ingest_file` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `file_type` varchar(31) NOT NULL COMMENT 'csv, zip, ead, directory',
  `original_name` varchar(500) DEFAULT NULL,
  `stored_path` varchar(1000) NOT NULL,
  `file_size` bigint DEFAULT '0',
  `mime_type` varchar(100) DEFAULT NULL,
  `row_count` int DEFAULT NULL,
  `delimiter` varchar(5) DEFAULT NULL,
  `encoding` varchar(50) DEFAULT NULL,
  `headers` json DEFAULT NULL,
  `extracted_path` varchar(1000) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `stage` varchar(32) DEFAULT NULL,
  `source_hash` char(64) DEFAULT NULL,
  `error_message` text,
  `attempts` int NOT NULL DEFAULT '0',
  `last_attempt_at` datetime DEFAULT NULL,
  `resolved_io_id` int DEFAULT NULL,
  `resolved_do_id` int DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `sidecar_path` varchar(1024) DEFAULT NULL,
  `sidecar_json` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `ix_ingest_file_status` (`status`),
  KEY `ix_ingest_file_hash` (`source_hash`),
  KEY `ix_ingest_file_io` (`resolved_io_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Column mapping for this session
CREATE TABLE IF NOT EXISTS ingest_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    source_column VARCHAR(255) NOT NULL,
    target_field VARCHAR(255) DEFAULT NULL,
    is_ignored TINYINT(1) DEFAULT 0,
    default_value VARCHAR(500) DEFAULT NULL,
    transform VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    KEY idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Validation results
CREATE TABLE IF NOT EXISTS `ingest_validation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `row_number` int NOT NULL,
  `severity` varchar(28) DEFAULT 'error' COMMENT 'error, warning, info',
  `field_name` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_excluded` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_row` (`row_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Parsed rows with enriched data
CREATE TABLE IF NOT EXISTS `ingest_row` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `row_number` int NOT NULL,
  `legacy_id` varchar(255) DEFAULT NULL,
  `parent_id_ref` varchar(255) DEFAULT NULL,
  `level_of_description` varchar(100) DEFAULT NULL,
  `title` varchar(1000) DEFAULT NULL,
  `data` json NOT NULL,
  `enriched_data` json DEFAULT NULL,
  `digital_object_path` varchar(1000) DEFAULT NULL,
  `digital_object_matched` tinyint(1) DEFAULT '0',
  `metadata_extracted` json DEFAULT NULL,
  `checksum_sha256` varchar(64) DEFAULT NULL,
  `is_valid` tinyint(1) DEFAULT '1',
  `is_excluded` tinyint(1) DEFAULT '0',
  `created_atom_id` int DEFAULT NULL,
  `created_do_id` int DEFAULT NULL,
  `created_accession_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_legacy` (`legacy_id`),
  KEY `idx_valid` (`is_valid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Commit job tracking
CREATE TABLE IF NOT EXISTS ingest_job (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    status VARCHAR(57) COMMENT 'queued, running, completed, failed, cancelled' DEFAULT 'queued',
    total_rows INT DEFAULT 0,
    processed_rows INT DEFAULT 0,
    created_records INT DEFAULT 0,
    created_dos INT DEFAULT 0,
    sip_package_id INT DEFAULT NULL,
    aip_package_id INT DEFAULT NULL,
    dip_package_id INT DEFAULT NULL,
    error_count INT DEFAULT 0,
    error_log JSON DEFAULT NULL,
    manifest_path VARCHAR(1000) DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_session (session_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Watched (hot) folder registry for unattended auto-ingest (php symfony ingest:watch).
CREATE TABLE IF NOT EXISTS ingest_watch_folder (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    watch_path VARCHAR(1024) NOT NULL,
    label VARCHAR(255) DEFAULT NULL,
    config TEXT COMMENT 'JSON snapshot of the template ingest_session config',
    user_id INT DEFAULT NULL COMMENT 'creator; becomes the user_id of auto-created sessions',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0/1 - disabled folders are skipped',
    last_scan_at DATETIME DEFAULT NULL,
    last_status VARCHAR(255) DEFAULT NULL,
    files_ingested INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_watch_path (watch_path(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Merged in from database/migration_watch_folder.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- Watched (hot) folder registry for unattended auto-ingest.
-- Each row is a server folder scanned by `php symfony ingest:watch` (cron).
-- New files dropped in the folder are auto-ingested using the snapshotted
-- template config, then moved to a .processed/<timestamp>/ subfolder.
-- Run once per instance (idempotent).

CREATE TABLE IF NOT EXISTS ingest_watch_folder (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    watch_path VARCHAR(1024) NOT NULL,
    label VARCHAR(255) DEFAULT NULL,
    config TEXT COMMENT 'JSON snapshot of the template ingest_session config (sector, standard, repository, processing + output flags)',
    user_id INT DEFAULT NULL COMMENT 'creator; becomes the user_id of auto-created ingest sessions',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0/1 - disabled folders are skipped by the watcher',
    last_scan_at DATETIME DEFAULT NULL,
    last_status VARCHAR(255) DEFAULT NULL COMMENT 'free text: last scan outcome',
    files_ingested INT NOT NULL DEFAULT 0 COMMENT 'cumulative count of files auto-ingested',
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_watch_path (watch_path(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
