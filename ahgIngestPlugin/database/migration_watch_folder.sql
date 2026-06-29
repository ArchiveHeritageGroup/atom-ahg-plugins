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
