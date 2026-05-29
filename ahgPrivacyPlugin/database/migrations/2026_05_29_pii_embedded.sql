-- ============================================================================
-- Embedded-metadata PII findings (Heratio #751 parity) — 2026-05-29
-- ============================================================================
-- Records PII discovered in EMBEDDED file metadata (EXIF/IPTC/XMP already
-- extracted into dam_iptc_metadata / digital_object_metadata / media_metadata)
-- — primarily GPS coordinates and people — so the privacy team can review and
-- the GPS-redaction gate can withhold flagged coordinates from downstream
-- surfaces (OCFL, C2PA, IIIF, AI context).
--
-- Idempotent: CREATE TABLE IF NOT EXISTS + INSERT IGNORE seeds.
-- Jurisdiction-neutral: GPS + creator-contact are PII under GDPR Art 4(1),
-- POPIA s1, CCPA 1798.140(o), CDPA.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ahg_pii_finding_embedded` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `digital_object_id`   INT UNSIGNED NOT NULL,
  `pii_type`            VARCHAR(64) NOT NULL COMMENT 'ahg_dropdown.pii_type_embedded',
  `source_table`        VARCHAR(64) NOT NULL COMMENT 'digital_object_metadata | dam_iptc_metadata | media_metadata',
  `source_field`        VARCHAR(128) NOT NULL COMMENT 'Column / composite path that produced the hit',
  `source_value`        TEXT DEFAULT NULL COMMENT 'Raw matched value — redactable via UI',
  `confidence`          DECIMAL(3,2) NOT NULL DEFAULT 0.70,
  `resolution_status`   VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'ahg_dropdown.pii_resolution',
  `scanned_at`          DATETIME NOT NULL,
  `resolved_at`         DATETIME DEFAULT NULL,
  `resolved_by_user_id` INT DEFAULT NULL,
  `notes`               TEXT DEFAULT NULL,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pii_emb_do` (`digital_object_id`),
  KEY `idx_pii_emb_type` (`pii_type`),
  KEY `idx_pii_emb_status` (`resolution_status`),
  UNIQUE KEY `uk_pii_emb_dedup` (`digital_object_id`, `pii_type`, `source_table`, `source_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `ahg_dropdown`
  (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`, `is_default`, `is_active`)
VALUES
  ('pii_type_embedded', 'Embedded PII type', 'privacy', 'gps_coordinate',  'GPS coordinate',      10, 0, 1),
  ('pii_type_embedded', 'Embedded PII type', 'privacy', 'person_name',     'Person name',         20, 0, 1),
  ('pii_type_embedded', 'Embedded PII type', 'privacy', 'person_contact',  'Person contact info', 30, 0, 1),
  ('pii_type_embedded', 'Embedded PII type', 'privacy', 'sensitive_date',  'Sensitive date',      40, 0, 1),
  ('pii_resolution',    'PII resolution',    'privacy', 'pending',   'Pending review',    10, 1, 1),
  ('pii_resolution',    'PII resolution',    'privacy', 'redacted',  'Redacted',          20, 0, 1),
  ('pii_resolution',    'PII resolution',    'privacy', 'cleared',   'Cleared (not PII)', 30, 0, 1),
  ('pii_resolution',    'PII resolution',    'privacy', 'escalated', 'Escalated to DPO',  40, 0, 1);
