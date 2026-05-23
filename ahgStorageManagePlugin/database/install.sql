-- ============================================================================
-- heratio#145 — Strongroom space allocation (AtoM Heratio / PSIS port of #144).
-- ============================================================================
-- Schema is kept literally identical to the Heratio Laravel side
-- (packages/ahg-storage-manage/database/install.sql in the heratio repo).
-- Any future change to these tables must land on BOTH sides in the same release.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS ahg_strongroom (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug                 VARCHAR(255) NOT NULL,
    name                 VARCHAR(255) NOT NULL,
    location_description TEXT,
    capacity_value       DECIMAL(12,2),
    capacity_unit        VARCHAR(20) NOT NULL DEFAULT 'linear_meters'
                         COMMENT 'linear_meters, shelves, boxes, cubic_meters',
    notes                TEXT,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,
    UNIQUE KEY uq_strongroom_slug (slug),
    INDEX ix_strongroom_name (name)
);

CREATE TABLE IF NOT EXISTS ahg_physical_object_storage (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    physical_object_id INT NOT NULL,
    strongroom_id      BIGINT UNSIGNED NOT NULL,
    size_units_used    DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NULL,
    updated_at         TIMESTAMP NULL,
    UNIQUE KEY uq_physical_object (physical_object_id),
    INDEX ix_strongroom (strongroom_id),
    CONSTRAINT fk_phyo FOREIGN KEY (physical_object_id) REFERENCES physical_object(id) ON DELETE CASCADE,
    CONSTRAINT fk_strr FOREIGN KEY (strongroom_id)      REFERENCES ahg_strongroom(id)  ON DELETE RESTRICT
);

SET FOREIGN_KEY_CHECKS = 1;
