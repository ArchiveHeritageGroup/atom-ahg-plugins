-- ============================================================================
-- ahgPrivacyPlugin (#130 AC#5) - DSAR <-> information_object scope link.
--
-- Records which archival descriptions a DSAR covers. When an IO is added to a
-- DSAR's scope (or when the DSAR moves to "processing") an
-- information_object_privacy profile is pre-populated so the officer can mark
-- fields for redaction as part of the response. Soft links only (no hard FKs)
-- so the migration is safe to re-run and never fails on table ordering.
--
-- Twin of heratio install-dsar-scope.sql (#1108 deliverable 5).
-- ============================================================================

CREATE TABLE IF NOT EXISTS `privacy_dsar_object` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dsar_id`               INT UNSIGNED NOT NULL,
  `information_object_id` INT NOT NULL,
  `privacy_id`            INT UNSIGNED DEFAULT NULL COMMENT 'information_object_privacy.id once pre-populated',
  `created_by`            INT DEFAULT NULL COMMENT 'user.id',
  `created_at`            DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dsar_object` (`dsar_id`, `information_object_id`),
  KEY `idx_dsar_object_dsar` (`dsar_id`),
  KEY `idx_dsar_object_io` (`information_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
