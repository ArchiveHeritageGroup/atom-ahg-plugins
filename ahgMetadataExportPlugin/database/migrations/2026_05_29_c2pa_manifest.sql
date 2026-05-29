-- ============================================================================
-- C2PA content credentials manifest store (Heratio #749/#753 parity) — 2026-05-29
-- ============================================================================
-- One signed C2PA manifest per issuance for a digital/information object.
-- manifest_json holds the canonical (JCS) signed manifest; signature is
-- detached Ed25519 over SHA-256(JCS(claim)). Idempotent.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ahg_c2pa_manifest` (
  `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `digital_object_id`      INT UNSIGNED DEFAULT NULL,
  `information_object_id`  INT UNSIGNED DEFAULT NULL,
  `manifest_label`         VARCHAR(160) NOT NULL,
  `asset_hash`             CHAR(64) NOT NULL COMMENT 'SHA-256 of the host asset',
  `kid`                    VARCHAR(64) NOT NULL COMMENT 'Ed25519 key id (ed25519:<hex16>)',
  `signature_hex`          VARCHAR(160) NOT NULL COMMENT 'Detached Ed25519 signature, hex',
  `manifest_json`          LONGTEXT NOT NULL COMMENT 'Canonical (JCS) signed manifest',
  `created_at`             DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_c2pa_do` (`digital_object_id`),
  KEY `idx_c2pa_io` (`information_object_id`),
  UNIQUE KEY `uk_c2pa_label` (`manifest_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
