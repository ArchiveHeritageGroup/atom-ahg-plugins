-- ahgRequestToPublishPlugin - publication-request workflow layer.
--
-- Companion tables to request_to_publish(_i18n): a receipt token + curator
-- triage state per request, and a peer-review record. Keeps the object-coupled
-- core table untouched. No ENUMs; no FK to core AtoM tables.

CREATE TABLE IF NOT EXISTS `rtp_workflow` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`     INT NOT NULL COMMENT 'request_to_publish.id (logical FK)',
    `receipt_token`  CHAR(32) NOT NULL COMMENT 'Anonymous tracking token',
    `is_anonymous`   TINYINT(1) NOT NULL DEFAULT 0,
    `triage_status`  VARCHAR(20) NOT NULL DEFAULT 'new' COMMENT 'new, triaged, in_review, decided',
    `priority`       VARCHAR(10) NOT NULL DEFAULT 'normal' COMMENT 'low, normal, high',
    `assigned_to`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'Curator user id',
    `assigned_name`  VARCHAR(255) DEFAULT NULL,
    `internal_notes` TEXT DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_request` (`request_id`),
    UNIQUE KEY `uniq_token` (`receipt_token`),
    KEY `idx_triage` (`triage_status`),
    KEY `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rtp_review` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`    INT NOT NULL COMMENT 'request_to_publish.id (logical FK)',
    `reviewer_id`   BIGINT UNSIGNED DEFAULT NULL,
    `reviewer_name` VARCHAR(255) DEFAULT NULL,
    `verdict`       VARCHAR(20) NOT NULL DEFAULT 'abstain' COMMENT 'recommend_approve, recommend_reject, needs_changes, abstain',
    `comments`      TEXT DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
