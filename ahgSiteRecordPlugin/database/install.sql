-- ahgSiteRecordPlugin - schema
--
-- Replaces RARI's rock_forms `site_records` table (issue #299). That table was
-- never used in production, so this is a redesign rather than a migration.
--
-- Three changes of substance against the legacy shape:
--
--   1. A site record EXTENDS an authority record (UNIQUE actor_id) instead of
--      restating it. The legacy table carried site_name and alternative_name,
--      which the actor already holds as authorized_form_of_name and its other
--      names - two copies of a name drift apart, and then neither is trusted.
--
--   2. Checkbox groups become rows in ahg_site_attribute, not JSON blobs in a
--      text column. The legacy encoding was unqueryable, and it silently dropped
--      three values ("Overhang", "Crayon", "Silcrete") because the form and the
--      processing map disagreed - a bug shape that rows make impossible.
--
--   3. Every table carries created_at/updated_at/created_by/updated_by. The
--      legacy tables had no audit columns at all, so nothing recorded who
--      changed a site's coordinates.
--
-- Collation follows AtoM core (utf8mb4_0900_ai_ci) rather than the older AHG
-- plugin default, so a future string join against actor/actor_i18n cannot fail
-- on a collation mismatch.

CREATE TABLE IF NOT EXISTS `ahg_site_record` (
    `id` INT NOT NULL AUTO_INCREMENT,

    -- The authority record IS the site. One site record per actor.
    `actor_id` INT NOT NULL,

    `site_number` VARCHAR(100) DEFAULT NULL,
    `date_visited` DATE DEFAULT NULL,

    -- Sector-neutral, dropdown-backed. The legacy form hardcoded the nine South
    -- African provinces, which is exactly what stops it serving anyone else.
    `region_code` VARCHAR(63) DEFAULT NULL COMMENT 'ahg_dropdown taxonomy site_region',
    `sub_region_code` VARCHAR(63) DEFAULT NULL COMMENT 'ahg_dropdown taxonomy site_sub_region',

    -- LOCALITY. Read only through LocalityVisibilityService - never directly.
    `latitude` DECIMAL(10, 7) DEFAULT NULL,
    `longitude` DECIMAL(10, 7) DEFAULT NULL,
    `coordinate_datum` VARCHAR(31) DEFAULT 'WGS84',
    `altitude_m` INT DEFAULT NULL,
    `map_sheet` VARCHAR(63) DEFAULT NULL COMMENT 'e.g. a 1:50,000 sheet reference such as 2328BD',

    -- Locality as it was originally written down: legacy "S 29 12 30" notation,
    -- map sheet strings, site codes. Kept verbatim so structuring the data never
    -- destroys the source. Sensitive - gated with the coordinates.
    `locality_original` TEXT,

    -- Sensitive unless someone has deliberately said otherwise. A new record,
    -- and any record where this was never set, is protected.
    `locality_sensitive` TINYINT(1) NOT NULL DEFAULT 1,

    `aspect_code` VARCHAR(63) DEFAULT NULL COMMENT 'ahg_dropdown taxonomy site_aspect',

    -- Replaces the legacy "H x W x D" string packed into a JSON column.
    `height_m` DECIMAL(8, 2) DEFAULT NULL,
    `width_m` DECIMAL(8, 2) DEFAULT NULL,
    `depth_m` DECIMAL(8, 2) DEFAULT NULL,

    `site_description` TEXT,
    `photograph_numbers` VARCHAR(255) DEFAULT NULL,
    `contact_name` VARCHAR(255) DEFAULT NULL,
    `contact_email` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_site_record_actor` (`actor_id`),
    KEY `idx_site_record_number` (`site_number`),
    KEY `idx_site_record_map_sheet` (`map_sheet`),
    KEY `idx_site_record_region` (`region_code`),
    CONSTRAINT `fk_site_record_actor` FOREIGN KEY (`actor_id`)
        REFERENCES `actor` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Recorded attributes: tradition, site type, damage, surface contents,
-- excavation potential, mineral contents, deposit depth, deposit contents.
--
-- One row per selected value, so a value can be counted, filtered and reported.
-- `taxonomy` names an ahg_dropdown taxonomy and `code` one of its codes; `note`
-- carries the free text behind an "Other" option.
CREATE TABLE IF NOT EXISTS `ahg_site_attribute` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `site_record_id` INT NOT NULL,
    `taxonomy` VARCHAR(63) NOT NULL COMMENT 'ahg_dropdown taxonomy, e.g. site_tradition, site_type, site_damage',
    `code` VARCHAR(63) NOT NULL COMMENT 'ahg_dropdown code within that taxonomy',
    `note` VARCHAR(255) DEFAULT NULL COMMENT 'free text accompanying an "other" selection',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_site_attribute` (`site_record_id`, `taxonomy`, `code`),
    KEY `idx_site_attribute_taxonomy` (`taxonomy`, `code`),
    CONSTRAINT `fk_site_attribute_record` FOREIGN KEY (`site_record_id`)
        REFERENCES `ahg_site_record` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Field recorders. Linked to an authority record where the person is known to the
-- system, free text where they are not - the legacy field was a single
-- comma-separated string, so it could never answer "what has this person recorded".
CREATE TABLE IF NOT EXISTS `ahg_site_recorder` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `site_record_id` INT NOT NULL,
    `actor_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL COMMENT 'as written on the form; kept even when actor_id is set',
    `role_code` VARCHAR(63) DEFAULT NULL COMMENT 'ahg_dropdown taxonomy site_recorder_role',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_site_recorder_record` (`site_record_id`),
    KEY `idx_site_recorder_actor` (`actor_id`),
    CONSTRAINT `fk_site_recorder_record` FOREIGN KEY (`site_record_id`)
        REFERENCES `ahg_site_record` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_site_recorder_actor` FOREIGN KEY (`actor_id`)
        REFERENCES `actor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
