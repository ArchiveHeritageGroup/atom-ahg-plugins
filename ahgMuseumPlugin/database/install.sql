-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgMuseumPlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------

-- Table: getty_vocabulary_link
CREATE TABLE IF NOT EXISTS `getty_vocabulary_link` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `term_id` int unsigned NOT NULL,
  `vocabulary` VARCHAR(26) NOT NULL COMMENT 'aat, tgn, ulan',
  `getty_uri` varchar(255) NOT NULL,
  `getty_id` varchar(50) NOT NULL,
  `getty_pref_label` varchar(500) DEFAULT NULL,
  `getty_scope_note` text,
  `status` VARCHAR(51) NOT NULL DEFAULT 'pending' COMMENT 'confirmed, suggested, rejected, pending',
  `confidence` decimal(3,2) NOT NULL DEFAULT '0.00',
  `confirmed_by_user_id` int unsigned DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_term_getty` (`term_id`,`getty_uri`),
  KEY `idx_vocabulary` (`vocabulary`),
  KEY `idx_status` (`status`),
  KEY `idx_getty_id` (`getty_id`),
  KEY `idx_vocab_status` (`vocabulary`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: provenance_entry
CREATE TABLE IF NOT EXISTS `provenance_entry` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `information_object_id` int unsigned NOT NULL,
  `sequence` smallint unsigned NOT NULL DEFAULT '1',
  `owner_name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_type` VARCHAR(108) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown' COMMENT 'person, family, dealer, auction_house, museum, corporate, government, religious, artist, unknown',
  `owner_actor_id` int unsigned DEFAULT NULL,
  `owner_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_location_tgn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date_qualifier` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'circa, before, after, by',
  `end_date` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_date_qualifier` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'circa, before, after, by',
  `transfer_type` VARCHAR(138) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown' COMMENT 'sale, auction, gift, bequest, inheritance, commission, exchange, seizure, restitution, transfer, loan, found, created, unknown',
  `transfer_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `sale_currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auction_house` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auction_lot` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certainty` VARCHAR(59) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown' COMMENT 'certain, probable, possible, uncertain, unknown',
  `sources` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_gap` tinyint(1) NOT NULL DEFAULT '0',
  `gap_explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pe_object` (`information_object_id`),
  KEY `idx_pe_object_seq` (`information_object_id`,`sequence`),
  KEY `idx_pe_owner` (`owner_name`),
  KEY `idx_pe_transfer` (`transfer_type`),
  KEY `idx_pe_certainty` (`certainty`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- Museum Plugin Install
-- =====================================================

-- Add Museum display standard term (taxonomy_id = 70)
-- Check if already exists first
SET @museum_exists = (SELECT COUNT(*) FROM term WHERE code = 'museum' AND taxonomy_id = 70);

-- Create object only if term doesn't exist
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @museum_exists = 0;

SET @museum_id = LAST_INSERT_ID();

-- Create term only if we just created an object
INSERT INTO term (id, taxonomy_id, code, source_culture, parent_id)
SELECT @museum_id, 70, 'museum', 'en', 110 FROM DUAL WHERE @museum_exists = 0 AND @museum_id > 0;

-- Create term_i18n only if we just created a term
INSERT INTO term_i18n (id, culture, name)
SELECT @museum_id, 'en', 'Museum (CCO), Cataloging Cultural Objects' FROM DUAL WHERE @museum_exists = 0 AND @museum_id > 0;

-- =====================================================
-- Museum Level of Description Terms (taxonomy_id = 34)
-- =====================================================

-- Object
SET @obj_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Object' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @obj_exists IS NULL;
SET @obj_id = IF(@obj_exists IS NULL, LAST_INSERT_ID(), @obj_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @obj_id, 34, 'en' FROM DUAL WHERE @obj_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @obj_id, 'en', 'Object' FROM DUAL WHERE @obj_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@obj_id, 'level-object');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@obj_id, 'museum', 50);

-- Artwork
SET @art_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Artwork' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @art_exists IS NULL;
SET @art_id = IF(@art_exists IS NULL, LAST_INSERT_ID(), @art_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @art_id, 34, 'en' FROM DUAL WHERE @art_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @art_id, 'en', 'Artwork' FROM DUAL WHERE @art_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@art_id, 'level-artwork');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@art_id, 'museum', 30);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@art_id, 'gallery', 10);

-- Artifact
SET @artf_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Artifact' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @artf_exists IS NULL;
SET @artf_id = IF(@artf_exists IS NULL, LAST_INSERT_ID(), @artf_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @artf_id, 34, 'en' FROM DUAL WHERE @artf_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @artf_id, 'en', 'Artifact' FROM DUAL WHERE @artf_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@artf_id, 'level-artifact');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@artf_id, 'museum', 20);

-- Specimen
SET @spec_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Specimen' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @spec_exists IS NULL;
SET @spec_id = IF(@spec_exists IS NULL, LAST_INSERT_ID(), @spec_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @spec_id, 34, 'en' FROM DUAL WHERE @spec_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @spec_id, 'en', 'Specimen' FROM DUAL WHERE @spec_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@spec_id, 'level-specimen');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@spec_id, 'museum', 60);

-- Installation
SET @inst_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Installation' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @inst_exists IS NULL;
SET @inst_id = IF(@inst_exists IS NULL, LAST_INSERT_ID(), @inst_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @inst_id, 34, 'en' FROM DUAL WHERE @inst_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @inst_id, 'en', 'Installation' FROM DUAL WHERE @inst_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@inst_id, 'level-installation');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@inst_id, 'museum', 40);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@inst_id, 'gallery', 40);

-- 3D Model
SET @model_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='3D Model' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @model_exists IS NULL;
SET @model_id = IF(@model_exists IS NULL, LAST_INSERT_ID(), @model_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @model_id, 34, 'en' FROM DUAL WHERE @model_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @model_id, 'en', '3D Model' FROM DUAL WHERE @model_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@model_id, 'level-3d-model');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@model_id, 'museum', 10);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@model_id, 'dam', 60);

-- =====================================================
-- Museum Metadata Table
-- =====================================================

CREATE TABLE IF NOT EXISTS `museum_metadata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `work_type` varchar(50) DEFAULT NULL,
  `object_type` varchar(255) DEFAULT NULL,
  `classification` varchar(255) DEFAULT NULL,
  `materials` text,
  `techniques` text,
  `measurements` varchar(255) DEFAULT NULL,
  `dimensions` varchar(255) DEFAULT NULL,
  `creation_date_earliest` date DEFAULT NULL,
  `creation_date_latest` date DEFAULT NULL,
  `inscription` text,
  `inscriptions` text,
  `condition_notes` text,
  `provenance` text,
  `style_period` varchar(255) DEFAULT NULL,
  `cultural_context` varchar(255) DEFAULT NULL,
  `current_location` text,
  `edition_description` text,
  `state_description` varchar(512) DEFAULT NULL,
  `state_identification` varchar(100) DEFAULT NULL,
  `facture_description` text,
  `technique_cco` varchar(512) DEFAULT NULL,
  `technique_qualifier` varchar(255) DEFAULT NULL,
  `orientation` varchar(100) DEFAULT NULL,
  `physical_appearance` text,
  `color` varchar(255) DEFAULT NULL,
  `shape` varchar(255) DEFAULT NULL,
  `condition_term` varchar(100) DEFAULT NULL,
  `condition_date` date DEFAULT NULL,
  `condition_description` text,
  `condition_agent` varchar(255) DEFAULT NULL,
  `treatment_type` varchar(255) DEFAULT NULL,
  `treatment_date` date DEFAULT NULL,
  `treatment_agent` varchar(255) DEFAULT NULL,
  `treatment_description` text,
  `inscription_transcription` text,
  `inscription_type` varchar(100) DEFAULT NULL,
  `inscription_location` varchar(255) DEFAULT NULL,
  `inscription_language` varchar(100) DEFAULT NULL,
  `inscription_translation` text,
  `mark_type` varchar(100) DEFAULT NULL,
  `mark_description` text,
  `mark_location` varchar(255) DEFAULT NULL,
  `related_work_type` varchar(100) DEFAULT NULL,
  `related_work_relationship` varchar(255) DEFAULT NULL,
  `related_work_label` varchar(512) DEFAULT NULL,
  `related_work_id` varchar(255) DEFAULT NULL,
  `current_location_repository` varchar(512) DEFAULT NULL,
  `current_location_geography` varchar(512) DEFAULT NULL,
  `current_location_coordinates` varchar(100) DEFAULT NULL,
  `current_location_ref_number` varchar(255) DEFAULT NULL,
  `creation_place` varchar(512) DEFAULT NULL,
  `creation_place_type` varchar(100) DEFAULT NULL,
  `discovery_place` varchar(512) DEFAULT NULL,
  `discovery_place_type` varchar(100) DEFAULT NULL,
  `provenance_text` text,
  `ownership_history` text,
  `legal_status` varchar(255) DEFAULT NULL,
  `rights_type` varchar(100) DEFAULT NULL,
  `rights_holder` varchar(512) DEFAULT NULL,
  `rights_date` varchar(100) DEFAULT NULL,
  `rights_remarks` text,
  `cataloger_name` varchar(255) DEFAULT NULL,
  `cataloging_date` date DEFAULT NULL,
  `cataloging_institution` varchar(512) DEFAULT NULL,
  `cataloging_remarks` text,
  `record_type` varchar(100) DEFAULT NULL,
  `record_level` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `creator_identity` varchar(512) DEFAULT NULL,
  `creator_role` varchar(255) DEFAULT NULL,
  `creator_extent` varchar(255) DEFAULT NULL,
  `creator_qualifier` varchar(255) DEFAULT NULL,
  `creator_attribution` varchar(255) DEFAULT NULL,
  `creation_date_display` varchar(255) DEFAULT NULL,
  `creation_date_qualifier` varchar(100) DEFAULT NULL,
  `style` varchar(255) DEFAULT NULL,
  `period` varchar(255) DEFAULT NULL,
  `cultural_group` varchar(255) DEFAULT NULL,
  `movement` varchar(255) DEFAULT NULL,
  `school` varchar(255) DEFAULT NULL,
  `dynasty` varchar(255) DEFAULT NULL,
  `subject_indexing_type` varchar(100) DEFAULT NULL,
  `subject_display` text,
  `subject_extent` varchar(255) DEFAULT NULL,
  `historical_context` text,
  `architectural_context` text,
  `archaeological_context` text,
  `object_class` varchar(255) DEFAULT NULL,
  `object_category` varchar(255) DEFAULT NULL,
  `object_sub_category` varchar(255) DEFAULT NULL,
  `edition_number` varchar(100) DEFAULT NULL,
  `edition_size` varchar(100) DEFAULT NULL,
  `title_type` varchar(100) DEFAULT NULL,
  `credit_line` text,
  `creator_display` text,
  `materials_display` text,
  `height_value` varchar(50) DEFAULT NULL,
  `width_value` varchar(50) DEFAULT NULL,
  `depth_value` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_object` (`object_id`),
  CONSTRAINT `museum_metadata_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `information_object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
