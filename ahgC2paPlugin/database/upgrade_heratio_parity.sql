-- PSIS / AtoM-AHG - bring an existing ahg_c2pa_manifest up to Heratio parity.
--
-- Run ONCE on an install whose ahg_c2pa_manifest was created by
-- ahgMetadataExportPlugin (columns manifest_label / asset_hash / signature_hex
-- only). Adds the Heratio columns so AI-output and provenance manifests record
-- their action, model and sidecar. All columns are nullable, so the
-- metadata-export inserts keep working unchanged. MySQL 8 has no
-- ADD COLUMN IF NOT EXISTS: check first with
--   SHOW COLUMNS FROM ahg_c2pa_manifest LIKE 'action';
-- C2paService::persist() works with or without this upgrade; without it those
-- five fields are simply not stored.

ALTER TABLE `ahg_c2pa_manifest`
    ADD COLUMN `action`        VARCHAR(32)  DEFAULT NULL COMMENT 'ai-generated, ai-assisted, c2pa.created, placed, edited' AFTER `information_object_id`,
    ADD COLUMN `model_id`      VARCHAR(128) DEFAULT NULL AFTER `action`,
    ADD COLUMN `model_version` VARCHAR(64)  DEFAULT NULL AFTER `model_id`,
    ADD COLUMN `manifest_cbor` LONGBLOB     DEFAULT NULL COMMENT 'CBOR form of the same manifest (JUMBF on-wire form)' AFTER `manifest_json`,
    ADD COLUMN `sidecar_path`  VARCHAR(512) DEFAULT NULL COMMENT 'Sidecar (.c2pa.json) or embedded copy written, when any' AFTER `manifest_cbor`,
    ADD KEY `idx_c2pa_action` (`action`),
    ADD KEY `idx_c2pa_kid` (`kid`);
