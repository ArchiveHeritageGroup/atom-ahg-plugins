-- ahgOcflPlugin: install schema (idempotent).
--
-- Maps AtoM information_object ids to OCFL object ids in the storage root.
-- One row per IO that has been ingested; head_version tracks the newest
-- version written.
--
-- NOTE: no FOREIGN KEY to core tables (information_object), no ENUM, and no
-- INSERT INTO atom_plugin (plugins are enabled manually). Per AtoM rules.
--
-- Copyright (C) The Archive and Heritage Group (Pty) Ltd
-- License: AGPL-3.0-or-later

CREATE TABLE IF NOT EXISTS `ahg_ocfl_object_map` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `information_object_id` BIGINT UNSIGNED NOT NULL,
    `ocfl_object_id`        VARCHAR(255)    NOT NULL,
    `storage_root`          VARCHAR(512)    NOT NULL DEFAULT 'ocfl' COMMENT 'absolute path to the OCFL storage root',
    `head_version`          VARCHAR(16)     NOT NULL DEFAULT 'v1' COMMENT 'e.g. v1, v2, v3',
    `created_at`            TIMESTAMP NULL DEFAULT NULL,
    `updated_at`            TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_ocfl_io`        (`information_object_id`),
    UNIQUE KEY `uniq_ocfl_object_id` (`ocfl_object_id`),
    KEY        `idx_storage_root`    (`storage_root`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
