-- ahgRicManagePlugin schema.
--
-- Additive only. Does NOT modify any core AtoM table, and does NOT enable the
-- plugin (no INSERT INTO atom_plugin). The RiC standard term (taxonomy 70,
-- code 'ric') is created by bin/seed-ric-standard.php via the AtoM API so the
-- nested set + object row + i18n are all correct - it is NOT hand-seeded here.
--
-- Typed RiC relations reuse the existing ric_relation_meta sidecar (keyed by
-- relation.id); this file only adds the record-centric RiC metadata store.

CREATE TABLE IF NOT EXISTS ric_record_meta (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'FK -> information_object.id (a RiC Record)',
    entity_type VARCHAR(50) NOT NULL DEFAULT 'Record'
        COMMENT 'RiC-O entity type: Record, RecordSet, RecordPart, RecordResource, Instantiation',
    ric_data LONGTEXT NULL
        COMMENT 'JSON of record-centric RiC properties (identifier, scope, authenticity, integrity, extensible)',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ric_record_object (object_id),
    KEY idx_ric_record_entity (entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
