-- PSIS / AtoM-AHG - C2PA (Coalition for Content Provenance and Authenticity) manifests.
--
-- The durable log of every signed C2PA manifest issued for a record: digitisation
-- provenance (ahg_c2pa_provenance.manifest_id points here), AI outputs (NER,
-- summaries, translations, description suggestions) and metadata-export
-- issuances. Answers "what provenance / AI activity ever touched this IO?"
-- without crawling sidecars, and lets a lost on-disk copy be re-issued.
--
-- The table is SHARED with ahgMetadataExportPlugin, whose migration
-- (2026_05_29_c2pa_manifest.sql) created it on PSIS first. This definition is
-- the union of that schema (manifest_label, asset_hash, signature_hex) and the
-- Heratio ahg-c2pa one (action, model_id, model_version, manifest_cbor,
-- sidecar_path); C2paService::persist() writes whichever columns exist. An
-- install that already has the older table gets the Heratio columns from
-- upgrade_heratio_parity.sql.
--
-- Conventions: CREATE TABLE IF NOT EXISTS, no ENUM, no FOREIGN KEY to core AtoM
-- tables (information_object_id is a plain indexed column).

CREATE TABLE IF NOT EXISTS `ahg_c2pa_manifest` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `digital_object_id`     INT UNSIGNED DEFAULT NULL,
    `information_object_id` INT UNSIGNED DEFAULT NULL,
    `action`                VARCHAR(32) DEFAULT NULL COMMENT 'ai-generated, ai-assisted, c2pa.created, placed, edited',
    `model_id`              VARCHAR(128) DEFAULT NULL,
    `model_version`         VARCHAR(64) DEFAULT NULL,
    `manifest_label`        VARCHAR(160) NOT NULL,
    `asset_hash`            CHAR(64) NOT NULL COMMENT 'SHA-256 of the host asset',
    `kid`                   VARCHAR(64) NOT NULL COMMENT 'Ed25519 key id; resolves through ai_inference_key',
    `signature_hex`         VARCHAR(160) NOT NULL COMMENT 'Ed25519 signature over SHA-256(JCS(claim)), hex',
    `manifest_json`         LONGTEXT NOT NULL COMMENT 'Canonical (JCS) signed manifest',
    `manifest_cbor`         LONGBLOB DEFAULT NULL COMMENT 'CBOR form of the same manifest (JUMBF on-wire form)',
    `sidecar_path`          VARCHAR(512) DEFAULT NULL COMMENT 'Sidecar (.c2pa.json) or embedded copy written, when any',
    `created_at`            DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_c2pa_label` (`manifest_label`),
    KEY `idx_c2pa_do` (`digital_object_id`),
    KEY `idx_c2pa_io` (`information_object_id`),
    KEY `idx_c2pa_action` (`action`),
    KEY `idx_c2pa_kid` (`kid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PSIS / AtoM-AHG - C2PA digitisation provenance / content-credentials records.
--
-- PSIS twin of Heratio packages/ahg-c2pa/database/install_provenance.sql (same
-- columns, so records cross-read). The record-of-capture for a digitised asset:
-- who digitised it, when, on what device and software, plus any AI-inference
-- steps that touched it. Each record can be bound to a signed C2PA manifest in
-- ahg_c2pa_manifest. Written by the auto-provenance watcher, the
-- c2pa:provenance-backfill task and the /admin/c2pa staff pages.
--
-- Conventions: CREATE TABLE IF NOT EXISTS, no ENUM, no FOREIGN KEY to core AtoM.

CREATE TABLE IF NOT EXISTS `ahg_c2pa_provenance` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `information_object_id` INT UNSIGNED NOT NULL,
    `digital_object_id`     INT UNSIGNED DEFAULT NULL COMMENT 'NULL = applies to the record as a whole',
    `captured_by`           VARCHAR(255) DEFAULT NULL,
    `captured_at`           DATETIME DEFAULT NULL,
    `capture_device`        VARCHAR(255) DEFAULT NULL,
    `capture_software`      VARCHAR(255) DEFAULT NULL,
    `notes`                 TEXT DEFAULT NULL,
    `asset_sha256`          CHAR(64) DEFAULT NULL COMMENT 'SHA-256 of the asset file when the record was made',
    `inference_steps`       LONGTEXT DEFAULT NULL COMMENT 'JSON array: step, model_id, model_version, output_sha256',
    `manifest_id`           BIGINT UNSIGNED DEFAULT NULL COMMENT 'ahg_c2pa_manifest.id when signed',
    `sign_status`           VARCHAR(16) NOT NULL DEFAULT 'unsigned' COMMENT 'signed, unsigned',
    `created_at`            DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at`            DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_io` (`information_object_id`),
    KEY `idx_do` (`digital_object_id`),
    KEY `idx_manifest` (`manifest_id`),
    KEY `idx_status` (`sign_status`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
