-- ahgResearchPlugin - Data Management Plans (DMP).
--
-- Funder-grade DMPs (Science Europe / Horizon Europe core structure) owned by a
-- researcher and optionally tied to a research_project. Standalone tables, no
-- ENUMs, no FK to core AtoM tables.

CREATE TABLE IF NOT EXISTS `research_dmp` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `researcher_id`    BIGINT UNSIGNED NOT NULL,
    `project_id`       BIGINT UNSIGNED DEFAULT NULL COMMENT 'Optional research_project.id (logical FK)',
    `title`            VARCHAR(255) NOT NULL,
    `funder`           VARCHAR(255) DEFAULT NULL,
    `grant_number`     VARCHAR(128) DEFAULT NULL,
    `status`           VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, active, final',
    `version`          VARCHAR(20) NOT NULL DEFAULT '1.0',

    -- Science Europe core sections.
    `data_description`     TEXT DEFAULT NULL COMMENT '1. Data summary / description',
    `fair_findable`        TEXT DEFAULT NULL COMMENT '2a. Making data findable',
    `fair_accessible`      TEXT DEFAULT NULL COMMENT '2b. Making data accessible',
    `fair_interoperable`   TEXT DEFAULT NULL COMMENT '2c. Making data interoperable',
    `fair_reusable`        TEXT DEFAULT NULL COMMENT '2d. Increasing data re-use',
    `resources_costs`      TEXT DEFAULT NULL COMMENT '3. Allocation of resources',
    `data_security`        TEXT DEFAULT NULL COMMENT '4. Data security',
    `ethics_legal`         TEXT DEFAULT NULL COMMENT '5. Ethical aspects',
    `other_issues`         TEXT DEFAULT NULL COMMENT '6. Other issues',

    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_researcher` (`researcher_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_dmp_dataset` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dmp_id`           BIGINT UNSIGNED NOT NULL,
    `name`             VARCHAR(255) NOT NULL,
    `description`      TEXT DEFAULT NULL,
    `data_type`        VARCHAR(120) DEFAULT NULL COMMENT 'e.g. images, interviews, survey, geospatial',
    `formats`          VARCHAR(255) DEFAULT NULL COMMENT 'File formats',
    `est_volume`       VARCHAR(64) DEFAULT NULL COMMENT 'Estimated volume e.g. 20 GB',
    `sensitivity`      VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open, restricted, sensitive',
    `personal_data`    TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Contains personal data',
    `license`          VARCHAR(128) DEFAULT NULL,
    `repository`       VARCHAR(255) DEFAULT NULL COMMENT 'Target repository for sharing/preservation',
    `retention_period` VARCHAR(64) DEFAULT NULL,
    `sharing_policy`   TEXT DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dmp` (`dmp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
