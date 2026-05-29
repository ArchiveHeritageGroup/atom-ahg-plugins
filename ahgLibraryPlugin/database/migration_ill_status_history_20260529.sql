-- ============================================================================
-- ILL status history (#106) — 2026-05-29
-- ============================================================================
-- Audit trail of ISO 10160/10161 ILL transaction state transitions.
-- Idempotent.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `library_ill_status_history` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ill_request_id` BIGINT UNSIGNED NOT NULL,
  `from_status`    VARCHAR(30) DEFAULT NULL,
  `to_status`      VARCHAR(30) NOT NULL,
  `notes`          TEXT DEFAULT NULL,
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_illh_request` (`ill_request_id`),
  KEY `idx_illh_to` (`to_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
