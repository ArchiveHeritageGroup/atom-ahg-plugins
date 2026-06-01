-- =============================================================================
-- ahgResearchPlugin — Lecture Builder (#116)
-- PSIS-parity port of Heratio ResearchLectureService / ResearchLectureController.
--
-- One model, three uses via the `type` column:
--   curriculum : teaching content that feeds the training curriculum
--   talk       : public lecture/seminar record (speaker, schedule, recording)
--   standalone : reusable authored lecture (ordered sections + media)
--
-- A lecture has ordered content SECTIONS (heading, Markdown body, optional media)
-- and RESOURCES (reading / slides / video / link / file).
--
-- NEVER uses ENUM — VARCHAR + COMMENT listing valid values.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `research_lecture` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `researcher_id`       BIGINT UNSIGNED NULL,
    `type`                VARCHAR(20)  NOT NULL DEFAULT 'standalone' COMMENT 'curriculum, talk, standalone',
    `title`               VARCHAR(255) NOT NULL DEFAULT 'Untitled lecture',
    `subtitle`            VARCHAR(255) NULL,
    `summary`             TEXT NULL,
    `speaker_name`        VARCHAR(255) NULL,
    `speaker_affiliation` VARCHAR(255) NULL,
    `scheduled_at`        DATETIME NULL,
    `location`            VARCHAR(255) NULL,
    `duration_minutes`    INT UNSIGNED NULL,
    `recording_url`       VARCHAR(1000) NULL,
    `slides_url`          VARCHAR(1000) NULL,
    `curriculum_ref`      VARCHAR(255) NULL COMMENT 'free-text ref to a training curriculum item',
    `status`              VARCHAR(20)  NOT NULL DEFAULT 'draft' COMMENT 'draft, scheduled, delivered, published, archived',
    `created_at`          DATETIME NULL,
    `updated_at`          DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_lecture_type`         (`type`),
    KEY `idx_lecture_status`       (`status`),
    KEY `idx_lecture_researcher`   (`researcher_id`),
    KEY `idx_lecture_scheduled_at` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_lecture_section` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lecture_id`    BIGINT UNSIGNED NOT NULL,
    `heading`       VARCHAR(255) NULL,
    `body_markdown` MEDIUMTEXT NULL,
    `body_html`     MEDIUMTEXT NULL,
    `media_url`     VARCHAR(1000) NULL,
    `media_type`    VARCHAR(20) NULL COMMENT 'image, video, audio, embed',
    `sort_order`    INT NOT NULL DEFAULT 0,
    `created_at`    DATETIME NULL,
    `updated_at`    DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_lecture_section_lecture` (`lecture_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_lecture_resource` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lecture_id`    BIGINT UNSIGNED NOT NULL,
    `label`         VARCHAR(255) NOT NULL DEFAULT 'Resource',
    `url`           VARCHAR(1000) NULL,
    `resource_type` VARCHAR(20) NOT NULL DEFAULT 'link' COMMENT 'reading, slides, video, link, file',
    `sort_order`    INT NOT NULL DEFAULT 0,
    `created_at`    DATETIME NULL,
    `updated_at`    DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_lecture_resource_lecture` (`lecture_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
