-- ============================================================================
-- ahgResearchPlugin - Research Enhancements (2026-05-16)
-- Spec: docs/atom-heratio-research-enhancements-spec.md
-- 8 new tables for: Studio artefacts, notebooks, cross-fonds queries,
--                   collaboration presence, ORCID links, offline sync.
--
-- Tables intentionally omitted (already exist):
--   research_evidence_comment -> use research_comment (polymorphic)
--   research_annotation       -> project_id + visibility already added
-- ============================================================================

-- §1.1 - §1.3 Studio artefacts
CREATE TABLE IF NOT EXISTS `research_studio_artefact` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `created_by` INT NULL,
    `output_type` VARCHAR(40) NOT NULL COMMENT 'briefing, study_guide, faq, timeline, diagram, video_script, spreadsheet, audio',
    `title` VARCHAR(500) NULL,
    `body` MEDIUMTEXT,
    `body_format` VARCHAR(20) DEFAULT 'markdown' COMMENT 'markdown, html, json, mermaid, csv',
    `source_object_ids` JSON NULL COMMENT 'IO ids the artefact was synthesised from',
    `citations` JSON NULL COMMENT 'list of {n, object_id, title, snippet, url} backing each [N] marker',
    `model` VARCHAR(120) NULL,
    `tokens_used` INT DEFAULT 0,
    `generation_time_ms` INT NULL,
    `audio_url` VARCHAR(500) NULL,
    `audio_digital_object_id` INT NULL,
    `audio_duration_seconds` INT NULL,
    `audio_transcript` MEDIUMTEXT,
    `xlsx_path` VARCHAR(500) NULL,
    `status` VARCHAR(20) DEFAULT 'ready' COMMENT 'pending, generating, ready, error',
    `error_text` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_output_type` (`output_type`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §1.5 Researcher notebooks
CREATE TABLE IF NOT EXISTS `research_notebook` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `summary` TEXT,
    `cover_object_id` INT NULL,
    `promoted_to_project_id` INT NULL,
    `promoted_at` DATETIME NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_promoted` (`promoted_to_project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_notebook_item` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `notebook_id` INT NOT NULL,
    `item_type` VARCHAR(30) NOT NULL COMMENT 'saved_query, ai_output, source_pin, note',
    `title` VARCHAR(500) NULL,
    `body` MEDIUMTEXT,
    `source_object_id` INT NULL,
    `saved_search_id` INT NULL,
    `ai_output_payload` JSON NULL,
    `pinned` TINYINT(1) DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notebook` (`notebook_id`),
    KEY `idx_item_type` (`item_type`),
    KEY `idx_source_object` (`source_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §1.6 Cross-fonds queries
CREATE TABLE IF NOT EXISTS `research_cross_fonds_query` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NULL,
    `query_text` VARCHAR(1000) NOT NULL,
    `fonds_ids` JSON NULL,
    `results_count` INT DEFAULT 0,
    `elapsed_ms` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §2.3 Real-time collaboration
CREATE TABLE IF NOT EXISTS `research_collaboration_session` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `started_by` INT NOT NULL,
    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `ended_at` DATETIME NULL,
    `expires_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_active` (`project_id`, `ended_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_collaboration_presence` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `researcher_id` INT NOT NULL,
    `session_id` INT NULL,
    `cursor_target` VARCHAR(200) NULL COMMENT 'route+anchor that identifies what the collaborator is viewing',
    `user_color` VARCHAR(7) NULL COMMENT '#rrggbb assigned for this session',
    `last_seen_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_project_researcher` (`project_id`, `researcher_id`),
    KEY `idx_session` (`session_id`),
    KEY `idx_last_seen` (`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §2.4 ORCID link
-- (Existing OrcidService writes some columns onto research_researcher; this table
--  is the canonical token + sync metadata store described by the spec.)
CREATE TABLE IF NOT EXISTS `research_orcid_link` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `orcid_id` VARCHAR(19) NOT NULL,
    `access_token_encrypted` TEXT,
    `refresh_token_encrypted` TEXT,
    `scope` VARCHAR(200) NULL,
    `expires_at` DATETIME NULL,
    `last_synced_at` DATETIME NULL,
    `last_works_count` INT NULL,
    `last_error` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_researcher` (`researcher_id`),
    UNIQUE KEY `uniq_orcid` (`orcid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- §2.7 Offline sync audit
CREATE TABLE IF NOT EXISTS `research_offline_sync_log` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `researcher_id` INT NOT NULL,
    `sync_started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `sync_completed_at` DATETIME NULL,
    `queued_count` INT DEFAULT 0,
    `applied_count` INT DEFAULT 0,
    `conflict_count` INT DEFAULT 0,
    `payload_hash` VARCHAR(64) NULL,
    `error_text` TEXT,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_started` (`sync_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
