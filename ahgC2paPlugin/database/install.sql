-- PSIS / AtoM-AHG - C2PA (Coalition for Content Provenance and Authenticity) manifests.
--
-- PSIS twin of Heratio packages/ahg-c2pa/database/install.sql. Schema is
-- byte-compatible so manifests written on PSIS and Heratio cross-verify.
--
-- Every AI-touched / DAM-signed artefact gets a C2PA 2.1 manifest. Manifests are
-- signed Ed25519 (shared key with ahgAiCompliancePlugin / ahg/inference-receipts)
-- and either embedded into the host media (JPEG/JUMBF via c2patool) or written as
-- a sidecar JSON next to the file.
--
-- This table is the durable log of every manifest emitted. It answers "what AI/
-- provenance activity ever touched this IO?" without crawling sidecar files, and
-- lets us re-issue a manifest if the on-disk copy is lost.
--
-- Conventions: CREATE TABLE IF NOT EXISTS, no ENUM, no FOREIGN KEY to core AtoM
-- tables (information_object_id is a plain indexed column).

CREATE TABLE IF NOT EXISTS `ahg_c2pa_manifest` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `information_object_id`    INT UNSIGNED NOT NULL,

    -- C2PA action label (c2pa.actions.v2). Typical values:
    --   'ai-generated' - output wholly produced by an AI model
    --   'ai-assisted'  - a human revised the AI output
    --   'placed'       - asset placed/signed with no AI involvement
    `action`                   VARCHAR(32) NOT NULL COMMENT 'ai-generated, ai-assisted, placed, edited',

    `model_id`                 VARCHAR(128) NOT NULL,
    `model_version`            VARCHAR(64) DEFAULT NULL,

    -- Authoritative JSON form (RFC 8785 JCS-canonical) - what the claim
    -- signature is computed over.
    `manifest_json`            LONGTEXT NOT NULL,

    -- Optional CBOR encoding of the same manifest (JUMBF on-wire form).
    `manifest_cbor`            LONGBLOB DEFAULT NULL,

    -- Absolute path of the sidecar (.c2pa.json) written, when applicable.
    `sidecar_path`             VARCHAR(512) DEFAULT NULL,

    -- Ed25519 signature over SHA-256(JCS(claim)), hex-encoded.
    `claim_signature`          VARCHAR(128) NOT NULL,

    -- Key id (first 16 hex of SHA-256(public_key)); resolves through
    -- ai_inference_key (ahgAiCompliancePlugin) to the public key for verification.
    `kid`                      VARCHAR(32) NOT NULL,

    `created_at`               DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    PRIMARY KEY (`id`),
    KEY `idx_io` (`information_object_id`),
    KEY `idx_action` (`action`),
    KEY `idx_kid` (`kid`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
