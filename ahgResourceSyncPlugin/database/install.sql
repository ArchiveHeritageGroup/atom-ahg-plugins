-- ----------------------------------------------------------------------------
-- ahgResourceSyncPlugin install schema
--
-- ResourceSync 1.1 (NISO Z39.99-2017) Source endpoints for AtoM / PSIS.
--
-- The plugin reuses the same tombstone table the OAI-PMH surface uses
-- (oai_deleted_record) so ResourceSync ChangeList and OAI ListRecords report
-- the SAME deletion set. On a Heratio (Laravel) host this table is created by
-- the ahg-oai migration; on a Symfony AtoM host without OAI installed, this
-- file creates it so the ResourceSync ChangeList tombstones work standalone.
--
-- Conventions per project rules:
--   * CREATE TABLE IF NOT EXISTS (idempotent install)
--   * NO ENUM columns
--   * NO FOREIGN KEY to core tables (information_object is upstream-locked)
--   * NEVER INSERT INTO atom_plugin (plugins enabled manually)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `oai_deleted_record` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `oai_local_identifier`  BIGINT UNSIGNED NOT NULL,
    `deleted_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reason`                VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_oai_deleted_record_oai_local_identifier` (`oai_local_identifier`),
    KEY `idx_oai_deleted_record_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
