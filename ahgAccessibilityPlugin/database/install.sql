-- ahgAccessibilityPlugin — schema
-- WCAG 1.1.1 image alternative text store. Soft reference to digital_object.id
-- (no FK, consistent with the AHG plugin convention of not constraining base
-- AtoM tables). One row per (digital object, language).
--
-- NOTE: plugins are enabled manually — this file MUST NOT INSERT INTO atom_plugin.

CREATE TABLE IF NOT EXISTS `image_alt_text` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `digital_object_id` INT NOT NULL COMMENT 'soft reference to digital_object.id - no FK',
    `lang` VARCHAR(16) NOT NULL DEFAULT 'en' COMMENT 'BCP-47-ish language code, e.g. en, af, fr',
    `alt_text` TEXT NULL COMMENT 'human-authored text alternative for the image (WCAG 1.1.1)',
    `contributed_by` INT NULL COMMENT 'soft reference to the user who first authored this entry',
    `updated_by` INT NULL COMMENT 'soft reference to the user who last edited this entry',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `image_alt_text_object_lang_unique` (`digital_object_id`, `lang`),
    KEY `image_alt_text_object_idx` (`digital_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
