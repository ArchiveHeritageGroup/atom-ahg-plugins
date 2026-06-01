-- =====================================================================
-- ahgResearchPlugin :: Journal Builder + Manuscript Workspace (#115)
-- =====================================================================
-- Mirrors the Heratio ResearchJournalService schema. Two modes over one
-- set of tables:
--   * publication: institutional journal -> issues -> articles -> TOC -> publish
--   * manuscript:  single article drafted toward an external target journal
--                  (references research_target_journal from the #114 twin
--                   when present; degrades gracefully when absent).
--
-- NOTE: This is DISTINCT from the legacy researcher logbook table
--       `research_journal_entry` (managed by lib/Services/JournalService.php).
--       Do not confuse the two; they coexist.
--
-- InnoDB / utf8mb4. No ENUM (VARCHAR + COMMENT). IF NOT EXISTS throughout.
-- =====================================================================

-- ── Journals (publication or manuscript container) ───────────────────
CREATE TABLE IF NOT EXISTS `research_journal` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `researcher_id`     BIGINT UNSIGNED NULL,
    `kind`              VARCHAR(20) NOT NULL DEFAULT 'publication' COMMENT 'publication, manuscript',
    `title`             VARCHAR(255) NOT NULL DEFAULT 'Untitled journal',
    `subtitle`          VARCHAR(255) NULL,
    `issn`              VARCHAR(20) NULL,
    `eissn`             VARCHAR(20) NULL,
    `publisher`         VARCHAR(255) NULL,
    `description`       TEXT NULL,
    `aims_scope`        TEXT NULL,
    `editor_name`       VARCHAR(255) NULL,
    `editor_email`      VARCHAR(255) NULL,
    `target_journal_id` BIGINT UNSIGNED NULL COMMENT 'FK (soft) to research_target_journal (#114) when manuscript mode',
    `cover_object_id`   BIGINT UNSIGNED NULL COMMENT 'optional cover digital object (parity with Heratio schema)',
    `doi`               VARCHAR(128) NULL,
    `status`            VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, published, archived',
    `created_at`        DATETIME NULL,
    `updated_at`        DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rj_researcher` (`researcher_id`),
    KEY `idx_rj_kind` (`kind`),
    KEY `idx_rj_status` (`status`),
    KEY `idx_rj_target` (`target_journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Issues (volume / number / date) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `research_journal_issue` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `journal_id`  BIGINT UNSIGNED NOT NULL,
    `volume`      VARCHAR(40) NULL,
    `number`      VARCHAR(40) NULL,
    `title`       VARCHAR(255) NULL,
    `issue_date`  DATE NULL,
    `description` TEXT NULL,
    `status`      VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, published',
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME NULL,
    `updated_at`  DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rji_journal` (`journal_id`),
    KEY `idx_rji_sort` (`journal_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Articles (placed in an issue, or unassigned manuscript draft) ────
CREATE TABLE IF NOT EXISTS `research_journal_article` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `journal_id`        BIGINT UNSIGNED NOT NULL,
    `issue_id`          BIGINT UNSIGNED NULL COMMENT 'NULL = unassigned / manuscript draft',
    `title`             VARCHAR(500) NOT NULL DEFAULT 'Untitled article',
    `authors`           TEXT NULL,
    `abstract`          TEXT NULL,
    `keywords`          VARCHAR(500) NULL,
    `body_markdown`     LONGTEXT NULL,
    `body_html`         LONGTEXT NULL,
    `reference_style`   VARCHAR(40) NULL COMMENT 'APA, Harvard, Vancouver, Chicago, MLA, IEEE',
    `target_journal_id` BIGINT UNSIGNED NULL COMMENT 'FK (soft) to research_target_journal (#114)',
    `doi`               VARCHAR(128) NULL,
    `word_count`        INT NOT NULL DEFAULT 0,
    `status`            VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, submitted, published',
    `sort_order`        INT NOT NULL DEFAULT 0,
    `created_at`        DATETIME NULL,
    `updated_at`        DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rja_journal` (`journal_id`),
    KEY `idx_rja_issue` (`issue_id`),
    KEY `idx_rja_sort` (`journal_id`, `sort_order`),
    KEY `idx_rja_target` (`target_journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
