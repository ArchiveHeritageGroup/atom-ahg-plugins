-- ahgArchaeologyPlugin - schema
--
-- Archaeological site, find and stratigraphic context recording. AtoM-line port
-- of the Heratio ahg-archaeology module (heratio#1428 phases 1-4b), tracked here
-- as atom-ahg-plugins#190.
--
-- Four tables ship together, deliberately. The plugin installer runs install.sql
-- and nothing else - migration_*.sql files in a plugin's database/ directory are
-- never executed - so a table added "later" in a migration would silently not
-- exist on any fresh install. Everything the module will need is created now,
-- even where the UI for it lands in a later phase.
--
-- Shape follows library_item: the descriptive record lives on information_object
-- and only domain-specific fields live here. Titles are NOT duplicated - they
-- come from information_object_i18n. Hierarchy, ACL, digital objects and ICIP
-- protocols are all inherited from the core object for free.
--
-- No ENUM columns anywhere: VARCHAR with a COMMENT listing the valid values, per
-- the project standard. An ENUM cannot be extended without an ALTER, and an
-- unexpected value becomes an empty string rather than an error.
--
-- Collation is utf8mb4_0900_ai_ci to match AtoM core rather than the older AHG
-- plugin default, so a future join against information_object_i18n or term_i18n
-- cannot fail on a collation mismatch.
--
-- `information_object_id` is indexed but carries no foreign key. AtoM deletes
-- descriptions through Propel with its own cascade ordering, and an FK here
-- would either block those deletes or fire at the wrong point in it.

-- ---------------------------------------------------------------------------
-- Sites
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `archaeology_site` (
    `id` INT NOT NULL AUTO_INCREMENT,

    -- Descriptive record. Title, dates, scope and ACL live there.
    `information_object_id` INT DEFAULT NULL,

    -- Institutional number, plus the national register number. In South Africa a
    -- site carries a SAHRA/ASAPA identifier distinct from the holding
    -- institution's own numbering.
    `site_number` VARCHAR(100) NOT NULL,
    `national_site_number` VARCHAR(100) DEFAULT NULL,

    -- Term references, not free text. Uncontrolled period strings cannot be
    -- browsed, faceted or reconciled, and terms carry ICIP protocols
    -- (term_protocol) plus Getty/AAT links, which matters for burial sites.
    `site_type_id` INT DEFAULT NULL,
    `period_id` INT DEFAULT NULL,

    -- Region/locality rather than province/district, so the module is not tied
    -- to one country's administrative tiers.
    `region` VARCHAR(150) DEFAULT NULL,
    `locality` VARCHAR(255) DEFAULT NULL,
    `location_description` TEXT,
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `elevation_m` SMALLINT DEFAULT NULL,
    `spatial_accuracy_m` INT DEFAULT NULL
        COMMENT 'Radius of positional uncertainty; blank means unrecorded, not exact',
    `area_sqm` DECIMAL(12, 2) DEFAULT NULL,

    -- Dating as strings: archaeological dates are rarely calendar dates
    -- ("c. 1200 AD", "2500 BP", "MIS 5").
    `date_earliest` VARCHAR(50) DEFAULT NULL,
    `date_latest` VARCHAR(50) DEFAULT NULL,
    `dating_note` TEXT,

    -- Investigation history.
    `discovery_date` DATE DEFAULT NULL,
    `discovered_by` VARCHAR(255) DEFAULT NULL,
    `excavated` TINYINT(1) NOT NULL DEFAULT 0,
    `excavation_years` VARCHAR(100) DEFAULT NULL,
    `excavator` VARCHAR(255) DEFAULT NULL,
    `excavation_institution` VARCHAR(255) DEFAULT NULL,
    `permit_number` VARCHAR(100) DEFAULT NULL
        COMMENT 'Excavation/collection permit, e.g. SAHRA under the NHRA',

    -- Management.
    `protection_status_id` INT DEFAULT NULL,
    `threats` TEXT,
    `research_potential` VARCHAR(30) DEFAULT 'medium'
        COMMENT 'low, medium, high',
    `publications` TEXT,
    `notes` TEXT,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active'
        COMMENT 'active, inactive, destroyed',

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_arch_site_number` (`site_number`),
    KEY `idx_arch_site_io` (`information_object_id`),
    KEY `idx_arch_site_national` (`national_site_number`),
    KEY `idx_arch_site_type` (`site_type_id`),
    KEY `idx_arch_site_period` (`period_id`),
    KEY `idx_arch_site_region` (`region`),
    KEY `idx_arch_site_excavated` (`excavated`),
    KEY `idx_arch_site_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- Contexts (stratigraphic units)
-- ---------------------------------------------------------------------------
--
-- The unit an excavation is actually dug and recorded in. Created before
-- archaeology_object because a find references it.
CREATE TABLE IF NOT EXISTS `archaeology_context` (
    `id` INT NOT NULL AUTO_INCREMENT,

    -- Descriptive record. The context's plan and section drawings hang off this
    -- as ordinary digital objects.
    `information_object_id` INT DEFAULT NULL,

    `site_id` INT NOT NULL,

    -- Unique within a site. A string, not an integer: single-context recording
    -- numbers are not always numeric ([1003], SF221, A.14).
    `context_number` VARCHAR(50) NOT NULL,

    -- Controlled vocabulary. Terms can carry ICIP protocols for sensitive
    -- contexts, which is what makes a burial behave differently from a rubble
    -- layer without anyone remembering to make it so.
    `context_type_id` INT DEFAULT NULL
        COMMENT 'term in the Archaeological Context Type taxonomy: deposit, cut, fill, layer, surface, masonry, skeleton, structure',

    `description` TEXT,
    `interpretation` TEXT,

    -- The "layer" geometry: upper and lower excavated surfaces. Three decimals
    -- is millimetre precision, which is what a total station gives.
    `top_elevation_m` DECIMAL(8, 3) DEFAULT NULL,
    `bottom_elevation_m` DECIMAL(8, 3) DEFAULT NULL,

    -- Provenance of excavation.
    `excavation_reference` VARCHAR(100) DEFAULT NULL
        COMMENT 'Trench / square / spit. Free text; see #190 follow-on - blocks per-trench sections until controlled',
    `excavator` VARCHAR(255) DEFAULT NULL,
    `excavation_date` DATE DEFAULT NULL,

    `phase_id` INT DEFAULT NULL
        COMMENT 'term in the Archaeological Phase taxonomy',

    `date_earliest` VARCHAR(50) DEFAULT NULL,
    `date_latest` VARCHAR(50) DEFAULT NULL,
    `dating_note` TEXT,

    `status` VARCHAR(30) NOT NULL DEFAULT 'active'
        COMMENT 'active, inactive',

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_arch_ctx_site_number` (`site_id`, `context_number`),
    KEY `idx_arch_ctx_io` (`information_object_id`),
    KEY `idx_arch_ctx_type` (`context_type_id`),
    KEY `idx_arch_ctx_phase` (`phase_id`),
    KEY `idx_arch_ctx_status` (`status`),
    CONSTRAINT `fk_arch_ctx_site` FOREIGN KEY (`site_id`)
        REFERENCES `archaeology_site` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- Stratigraphic relationships (the Harris Matrix edges)
-- ---------------------------------------------------------------------------
--
-- Each logical relationship is stored as TWO rows, one per direction, so a
-- context sheet can list its own relationships without a UNION. The service
-- keeps the mirror in step: above<->below, cuts<->cut_by, fills<->filled_by,
-- and same_as / bonds_with / abuts are symmetric.
--
-- Only above, cuts and fills form the directed later-than graph used for cycle
-- detection and for layering the matrix. same_as merges two contexts into one
-- node. bonds_with and abuts carry no ordering at all.
CREATE TABLE IF NOT EXISTS `archaeology_context_relationship` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `context_id` INT NOT NULL,
    `related_context_id` INT NOT NULL,
    `relationship_type` VARCHAR(20) NOT NULL
        COMMENT 'above, below, cuts, cut_by, fills, filled_by, same_as, bonds_with, abuts',
    `note` VARCHAR(255) DEFAULT NULL,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_arch_ctxrel` (`context_id`, `related_context_id`, `relationship_type`),
    KEY `idx_arch_ctxrel_related` (`related_context_id`),
    KEY `idx_arch_ctxrel_type` (`relationship_type`),
    CONSTRAINT `fk_arch_ctxrel_ctx` FOREIGN KEY (`context_id`)
        REFERENCES `archaeology_context` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_arch_ctxrel_related` FOREIGN KEY (`related_context_id`)
        REFERENCES `archaeology_context` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- Finds
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `archaeology_object` (
    `id` INT NOT NULL AUTO_INCREMENT,

    `information_object_id` INT DEFAULT NULL,
    `accession_number` VARCHAR(100) NOT NULL,

    `site_id` INT DEFAULT NULL,

    -- The structured replacement for a free-text stratigraphic reference. This
    -- is what makes "everything from context 1002" answerable.
    `context_id` INT DEFAULT NULL,

    -- Typology, all term-backed. Per-material attributes (bead diameter and
    -- perforation, glass series, ceramic fabric) belong in
    -- custom_field_definition rather than as columns here.
    `object_type_id` INT DEFAULT NULL,
    `material_id` INT DEFAULT NULL,
    `technique_id` INT DEFAULT NULL,
    `period_id` INT DEFAULT NULL,

    -- Recovery.
    `recovery_method_id` INT DEFAULT NULL,
    `context_reference` VARCHAR(100) DEFAULT NULL
        COMMENT 'Legacy free-text stratigraphic unit; retained for backfill into context_id',
    `excavation_reference` VARCHAR(100) DEFAULT NULL,
    `find_date` DATE DEFAULT NULL,
    `find_location` VARCHAR(255) DEFAULT NULL,
    `finder` VARCHAR(255) DEFAULT NULL,

    -- Dating.
    `date_earliest` VARCHAR(50) DEFAULT NULL,
    `date_latest` VARCHAR(50) DEFAULT NULL,
    `dating_method_id` INT DEFAULT NULL,
    `dating_note` TEXT,

    -- Quantification. A count lets a bulk assemblage ("312 potsherds from
    -- context 1002") be one record rather than 312, which is how excavated
    -- material is actually catalogued.
    `item_count` INT NOT NULL DEFAULT 1,
    `weight_g` DECIMAL(12, 3) DEFAULT NULL,
    `length_mm` DECIMAL(10, 2) DEFAULT NULL,
    `width_mm` DECIMAL(10, 2) DEFAULT NULL,
    `thickness_mm` DECIMAL(10, 2) DEFAULT NULL,
    `diameter_mm` DECIMAL(10, 2) DEFAULT NULL,
    `dimensions_note` VARCHAR(255) DEFAULT NULL,

    -- Custody.
    `condition_id` INT DEFAULT NULL,
    `repository_id` INT DEFAULT NULL,
    `storage_location` VARCHAR(255) DEFAULT NULL,
    `provenance` TEXT,
    `notes` TEXT,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active'
        COMMENT 'active, inactive, deaccessioned',

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_arch_obj_accession` (`accession_number`),
    KEY `idx_arch_obj_io` (`information_object_id`),
    KEY `idx_arch_obj_site` (`site_id`),
    KEY `idx_arch_obj_context` (`context_id`),
    KEY `idx_arch_obj_type` (`object_type_id`),
    KEY `idx_arch_obj_material` (`material_id`),
    KEY `idx_arch_obj_period` (`period_id`),
    KEY `idx_arch_obj_repository` (`repository_id`),
    KEY `idx_arch_obj_status` (`status`),
    CONSTRAINT `fk_arch_obj_site` FOREIGN KEY (`site_id`)
        REFERENCES `archaeology_site` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_arch_obj_context` FOREIGN KEY (`context_id`)
        REFERENCES `archaeology_context` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
