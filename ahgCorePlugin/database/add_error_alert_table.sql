-- CallHub alert claims. One row per (fault signature, period) that has been sent.
--
-- The UNIQUE key is the duplicate-prevention mechanism, not a nicety: the drain
-- claims a pair here BEFORE writing to the notification spool, so a second drain -
-- concurrent, or a re-run after a crash - loses the insert and sends nothing.
--
-- period is an ISO year-week (2026-W37). CallHub never creates a second ticket for
-- a reference it has seen, so a timeless reference would swallow a fault that
-- recurs months later; the week gives one ticket per fault per week instead.
CREATE TABLE IF NOT EXISTS `ahg_error_alert` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `signature`     VARCHAR(64)  NOT NULL COMMENT 'ahg_error_log.signature',
    `period`        VARCHAR(16)  NOT NULL COMMENT 'ISO year-week, e.g. 2026-W37',
    `external_ref`  VARCHAR(128) NOT NULL COMMENT 'dedupe key sent to CallHub',
    `error_log_id`  INT UNSIGNED     NULL COMMENT 'the row that triggered it',
    `sent_at`       DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_signature_period` (`signature`, `period`),
    UNIQUE KEY `uniq_external_ref` (`external_ref`),
    KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
