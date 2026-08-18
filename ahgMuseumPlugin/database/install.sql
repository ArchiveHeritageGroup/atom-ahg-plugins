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

-- ---------------------------------------------------------------------------
-- Merged in from database/cco_taxonomies.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ============================================================================
-- CCO/CDWA Museum Controlled Vocabularies - Complete Fixed SQL
-- Creates taxonomies, root terms, and child terms with proper parent_id
-- ============================================================================

-- USE archive;
START TRANSACTION;

-- ============================================================================
-- 1. CREATOR ROLE TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @creator_role_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@creator_role_taxonomy_id, 'Creator roles for museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@creator_role_taxonomy_id, 'en', 'Creator Role (CCO)', 'CCO/CDWA creator roles for museum cataloging');

-- Root term for Creator Role
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_creator_role = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_creator_role, @creator_role_taxonomy_id, NULL, 'en', 'root_creator_role');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_creator_role, 'en', 'Creator Role (root)');

-- Creator Role Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'artist');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Artist');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'architect');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Architect');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'author');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Author');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'calligrapher');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Calligrapher');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'carver');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Carver');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'ceramicist');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ceramicist');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'designer');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Designer');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'draftsman');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Draftsman');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'embroiderer');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Embroiderer');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'engraver');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Engraver');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'goldsmith');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Goldsmith');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'illustrator');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Illustrator');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'jeweler');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Jeweler');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'maker');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Maker');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'manufacturer');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Manufacturer');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'painter');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Painter');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'photographer');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Photographer');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'potter');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Potter');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'printmaker');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Printmaker');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'sculptor');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Sculptor');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'silversmith');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Silversmith');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'weaver');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Weaver');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'workshop_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Workshop of');

-- ============================================================================
-- 2. ATTRIBUTION QUALIFIER TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @attribution_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@attribution_taxonomy_id, 'Attribution qualifiers for museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@attribution_taxonomy_id, 'en', 'Attribution Qualifier (CCO)', 'CCO/CDWA attribution qualifiers');

-- Root term for Attribution Qualifier
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_attribution = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_attribution, @attribution_taxonomy_id, NULL, 'en', 'root_attribution_qualifier');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_attribution, 'en', 'Attribution Qualifier (root)');

-- Attribution Qualifier Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'ascribed_to');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ascribed to');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'attributed_to');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Attributed to');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'circle_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Circle of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'copy_after');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copy after');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'follower_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Follower of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'manner_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Manner of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'possibly');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Possibly');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'probably');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Probably');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'school_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'School of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'studio_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Studio of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'style_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Style of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'workshop_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Workshop of');

-- ============================================================================
-- 3. DATE QUALIFIER TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @date_qualifier_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@date_qualifier_taxonomy_id, 'Date qualifiers for museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@date_qualifier_taxonomy_id, 'en', 'Date Qualifier (CCO)', 'CCO/CDWA date qualifiers');

-- Root term for Date Qualifier
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_date_qualifier = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_date_qualifier, @date_qualifier_taxonomy_id, NULL, 'en', 'root_date_qualifier');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_date_qualifier, 'en', 'Date Qualifier (root)');

-- Date Qualifier Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'about');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'About');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'approximately');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Approximately');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'before');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Before');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'after');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'After');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'circa');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Circa');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'early');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Early');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'mid');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Mid');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'late');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Late');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'probably');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Probably');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'possibly');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Possibly');

-- ============================================================================
-- 4. CONDITION TERM TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @condition_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@condition_taxonomy_id, 'Condition terms for museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@condition_taxonomy_id, 'en', 'Condition Term (CCO)', 'CCO/CDWA condition assessment terms');

-- Root term for Condition Term
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_condition = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_condition, @condition_taxonomy_id, NULL, 'en', 'root_condition_term');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_condition, 'en', 'Condition Term (root)');

-- Condition Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'excellent');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Excellent');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'very_good');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Very good');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'good');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Good');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'fair');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fair');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'poor');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Poor');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'fragmentary');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fragmentary');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'damaged');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Damaged');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'restored');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Restored');

-- ============================================================================
-- 5. SUBJECT TYPE TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @subject_type_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@subject_type_taxonomy_id, 'Subject types for museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@subject_type_taxonomy_id, 'en', 'Subject Type (CCO)', 'CCO/CDWA subject indexing types');

-- Root term for Subject Type
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_subject_type = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_subject_type, @subject_type_taxonomy_id, NULL, 'en', 'root_subject_type');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_subject_type, 'en', 'Subject Type (root)');

-- Subject Type Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @subject_type_taxonomy_id, @root_subject_type, 'en', 'iconography');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Iconography');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @subject_type_taxonomy_id, @root_subject_type, 'en', 'narrative');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Narrative');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @subject_type_taxonomy_id, @root_subject_type, 'en', 'description');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Description');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @subject_type_taxonomy_id, @root_subject_type, 'en', 'interpretation');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Interpretation');

-- ============================================================================
-- 6. INSCRIPTION TYPE TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @inscription_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@inscription_taxonomy_id, 'Inscription types for museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@inscription_taxonomy_id, 'en', 'Inscription Type (CCO)', 'CCO/CDWA inscription and mark types');

-- Root term for Inscription Type
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_inscription = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_inscription, @inscription_taxonomy_id, NULL, 'en', 'root_inscription_type');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_inscription, 'en', 'Inscription Type (root)');

-- Inscription Type Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'signature');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Signature');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'date');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Date');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'title');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Title');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'dedication');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Dedication');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'inscription');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Inscription');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'label');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Label');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'stamp');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Stamp');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'watermark');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Watermark');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'monogram');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Monogram');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'hallmark');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Hallmark');

-- ============================================================================
-- 7. RELATED WORK TYPE TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @related_work_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@related_work_taxonomy_id, 'Related work relationship types', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@related_work_taxonomy_id, 'en', 'Related Work Type (CCO)', 'CCO/CDWA related work relationship types');

-- Root term for Related Work Type
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_related_work = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_related_work, @related_work_taxonomy_id, NULL, 'en', 'root_related_work_type');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_related_work, 'en', 'Related Work Type (root)');

-- Related Work Type Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'part_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Part of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'companion_to');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Companion to');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'copy_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copy of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'derived_from');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Derived from');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'model_for');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Model for');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'pendant_to');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Pendant to');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'preparatory_for');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Preparatory for');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'related_to');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Related to');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'replica_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Replica of');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'study_for');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Study for');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'version_of');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Version of');

-- ============================================================================
-- 8. RIGHTS TYPE TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @rights_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@rights_taxonomy_id, 'Rights types for museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@rights_taxonomy_id, 'en', 'Rights Type (CCO)', 'CCO/CDWA rights and licensing types');

-- Root term for Rights Type
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_rights = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_rights, @rights_taxonomy_id, NULL, 'en', 'root_rights_type');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_rights, 'en', 'Rights Type (root)');

-- Rights Type Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'copyright');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copyright');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'trademark');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Trademark');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'license');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'License');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'public_domain');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Public domain');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'creative_commons');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Creative Commons');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'unknown');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Unknown');

-- ============================================================================
-- 9. WORK TYPE TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @work_type_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@work_type_taxonomy_id, 'Work types for museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@work_type_taxonomy_id, 'en', 'Work Type (CCO)', 'CCO/CDWA work type classification');

-- Root term for Work Type
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_work_type = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_work_type, @work_type_taxonomy_id, NULL, 'en', 'root_work_type');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_work_type, 'en', 'Work Type (root)');

-- Work Type Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @work_type_taxonomy_id, @root_work_type, 'en', 'visual_works');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Visual Works');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @work_type_taxonomy_id, @root_work_type, 'en', 'built_works');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Built Works');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @work_type_taxonomy_id, @root_work_type, 'en', 'movable_works');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Movable Works');

-- ============================================================================
-- 10. MATERIAL TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @materials_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@materials_taxonomy_id, 'Materials used in museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@materials_taxonomy_id, 'en', 'Material (CCO)', 'CCO/CDWA materials vocabulary');

-- Root term for Material
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_material = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_material, @materials_taxonomy_id, NULL, 'en', 'root_material');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_material, 'en', 'Material (root)');

-- Material Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'oil_paint');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Oil paint');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'canvas');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Canvas');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'paper');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Paper');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'wood');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Wood');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'metal');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Metal');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'stone');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Stone');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'textile');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Textile');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'ceramic');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ceramic');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'glass');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Glass');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'plastic');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Plastic');

-- ============================================================================
-- 11. TECHNIQUE TAXONOMY
-- ============================================================================
INSERT IGNORE INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @techniques_taxonomy_id = LAST_INSERT_ID();

INSERT IGNORE INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@techniques_taxonomy_id, 'Techniques used in creating museum objects', 30, 'en');

INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@techniques_taxonomy_id, 'en', 'Technique (CCO)', 'CCO/CDWA techniques vocabulary');

-- Root term for Technique
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_technique = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_technique, @techniques_taxonomy_id, NULL, 'en', 'root_technique');
INSERT IGNORE INTO term_i18n (id, culture, name) 
VALUES (@root_technique, 'en', 'Technique (root)');

-- Technique Terms
INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'painted');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Painted');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'glazed');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Glazed');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'carved');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Carved');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'etched');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Etched');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'printed');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Printed');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'woven');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Woven');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'cast');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Cast');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'molded');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Molded');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'assembled');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Assembled');

INSERT IGNORE INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT IGNORE INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'fired');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fired');

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================
COMMIT;

-- ============================================================================
-- GENERATE SLUGS (run after commit)
-- ============================================================================
-- After running this SQL, execute:
-- cd /usr/share/nginx/atom_psis
-- sudo -u www-data php symfony propel:generate-slugs
-- sudo -u www-data php symfony cc

-- ============================================================================
-- VERIFY
-- ============================================================================
SELECT 'CCO/CDWA Taxonomies Created:' AS message;
SELECT 
    t.id AS taxonomy_id,
    ti.name AS taxonomy_name,
    (SELECT COUNT(*) FROM term WHERE taxonomy_id = t.id AND parent_id IS NOT NULL) AS term_count
FROM taxonomy t
JOIN taxonomy_i18n ti ON t.id = ti.id
WHERE ti.name LIKE '%(CCO)%' AND ti.culture = 'en'
ORDER BY t.id;
-- ---------------------------------------------------------------------------
-- Merged in from database/exhibition_schema.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =====================================================
-- Exhibition Module Schema
-- Version: 1.0.0
-- Author: Johan Pieterse <johan@theahg.co.za>
-- =====================================================

-- =====================================================
-- Core Exhibition Tables
-- =====================================================

-- Main exhibition record
CREATE TABLE IF NOT EXISTS exhibition (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Basic Information
    title VARCHAR(500) NOT NULL,
    subtitle VARCHAR(500),
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    theme TEXT,

    -- Type and Status
    exhibition_type VARCHAR(59) COMMENT 'permanent, temporary, traveling, online, pop_up' DEFAULT 'temporary',
    status VARCHAR(99) COMMENT 'concept, planning, preparation, installation, open, closing, closed, archived, canceled' DEFAULT 'concept',

    -- Dates
    planning_start_date DATE,
    preparation_start_date DATE,
    installation_start_date DATE,
    opening_date DATE,
    closing_date DATE,
    actual_closing_date DATE,

    -- Venue Information
    venue_id BIGINT UNSIGNED,
    venue_name VARCHAR(255),
    venue_address TEXT,
    venue_city VARCHAR(100),
    venue_country VARCHAR(100),
    is_external_venue TINYINT(1) DEFAULT 0,

    -- Gallery/Space
    gallery_ids JSON, -- Array of gallery IDs within venue
    total_square_meters DECIMAL(10,2),

    -- Visitor Information
    admission_fee DECIMAL(10,2),
    admission_currency VARCHAR(3) DEFAULT 'ZAR',
    is_free_admission TINYINT(1) DEFAULT 0,
    expected_visitors INT,
    actual_visitors INT,

    -- Accessibility
    wheelchair_accessible TINYINT(1) DEFAULT 1,
    audio_guide_available TINYINT(1) DEFAULT 0,
    braille_available TINYINT(1) DEFAULT 0,
    sign_language_tours TINYINT(1) DEFAULT 0,

    -- Budget
    budget_amount DECIMAL(12,2),
    budget_currency VARCHAR(3) DEFAULT 'ZAR',
    actual_cost DECIMAL(12,2),

    -- Insurance
    total_insurance_value DECIMAL(15,2),
    insurance_policy_number VARCHAR(100),
    insurance_provider VARCHAR(255),

    -- Catalog/Publication
    has_catalog TINYINT(1) DEFAULT 0,
    catalog_isbn VARCHAR(20),
    catalog_publication_date DATE,

    -- Online/Virtual
    has_virtual_tour TINYINT(1) DEFAULT 0,
    virtual_tour_url VARCHAR(500),
    online_exhibition_url VARCHAR(500),

    -- Credits
    curator_id INT,
    curator_name VARCHAR(255),
    designer_name VARCHAR(255),
    organized_by VARCHAR(255),
    sponsored_by TEXT,

    -- Internal tracking
    project_code VARCHAR(50),
    notes TEXT,
    internal_notes TEXT,

    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_exhibition_status (status),
    INDEX idx_exhibition_type (exhibition_type),
    INDEX idx_exhibition_dates (opening_date, closing_date),
    INDEX idx_exhibition_venue (venue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exhibition sections/galleries (subdivisions within an exhibition)
CREATE TABLE IF NOT EXISTS exhibition_section (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    narrative TEXT, -- Storyline/interpretive text

    section_type VARCHAR(61) COMMENT 'gallery, room, alcove, corridor, outdoor, virtual' DEFAULT 'gallery',
    sequence_order INT DEFAULT 0,

    -- Physical space
    gallery_name VARCHAR(100),
    floor_level VARCHAR(20),
    square_meters DECIMAL(8,2),

    -- Environment
    target_temperature_min DECIMAL(4,1),
    target_temperature_max DECIMAL(4,1),
    target_humidity_min DECIMAL(4,1),
    target_humidity_max DECIMAL(4,1),
    max_lux_level INT,

    -- Theme/narrative
    theme VARCHAR(255),
    color_scheme VARCHAR(100),

    -- Audio/multimedia
    has_audio_guide TINYINT(1) DEFAULT 0,
    audio_guide_number VARCHAR(20),
    has_video TINYINT(1) DEFAULT 0,
    has_interactive TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_section_exhibition (exhibition_id),
    INDEX idx_section_order (exhibition_id, sequence_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Objects in exhibition (linking table with placement details)
CREATE TABLE IF NOT EXISTS exhibition_object (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED,
    information_object_id INT NOT NULL,

    -- Display order and position
    sequence_order INT DEFAULT 0,
    display_position VARCHAR(100), -- e.g., "Wall A", "Case 3", "Pedestal 2"

    -- Object status in exhibition
    status VARCHAR(78) COMMENT 'proposed, confirmed, on_loan_request, installed, removed, returned' DEFAULT 'proposed',

    -- If external loan required
    requires_loan TINYINT(1) DEFAULT 0,
    loan_id BIGINT UNSIGNED,
    lender_institution VARCHAR(255),

    -- Display requirements
    display_case_required TINYINT(1) DEFAULT 0,
    mount_required TINYINT(1) DEFAULT 0,
    mount_description TEXT,
    special_lighting TINYINT(1) DEFAULT 0,
    lighting_notes TEXT,
    security_level VARCHAR(39) COMMENT 'standard, enhanced, maximum' DEFAULT 'standard',

    -- Environment requirements
    climate_controlled TINYINT(1) DEFAULT 0,
    max_lux_level INT,
    uv_filtering_required TINYINT(1) DEFAULT 0,

    -- Rotation (for light-sensitive objects)
    rotation_required TINYINT(1) DEFAULT 0,
    max_display_days INT,
    display_start_date DATE,
    display_end_date DATE,

    -- Condition
    pre_installation_condition_report_id BIGINT UNSIGNED,
    post_exhibition_condition_report_id BIGINT UNSIGNED,

    -- Insurance for this specific object
    insurance_value DECIMAL(15,2),

    -- Label
    label_text TEXT,
    label_credits TEXT,
    extended_label TEXT,
    audio_stop_number VARCHAR(20),

    -- Notes
    installation_notes TEXT,
    handling_notes TEXT,

    installed_by INT,
    installed_at TIMESTAMP NULL,
    removed_by INT,
    removed_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES exhibition_section(id) ON DELETE SET NULL,
    INDEX idx_exobj_exhibition (exhibition_id),
    INDEX idx_exobj_section (section_id),
    INDEX idx_exobj_object (information_object_id),
    INDEX idx_exobj_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Storytelling/Narrative Tables
-- =====================================================

-- Storylines/Narratives that connect objects
CREATE TABLE IF NOT EXISTS exhibition_storyline (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    description TEXT,
    narrative_type VARCHAR(82) COMMENT 'thematic, chronological, biographical, geographical, technique, custom' DEFAULT 'thematic',

    -- Content
    introduction TEXT,
    body_text TEXT,
    conclusion TEXT,

    -- Sequence within exhibition
    sequence_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0, -- Main narrative path

    -- Target audience
    target_audience VARCHAR(57) COMMENT 'general, children, students, specialists, all' DEFAULT 'all',
    reading_level VARCHAR(41) COMMENT 'basic, intermediate, advanced' DEFAULT 'intermediate',

    -- Duration for audio/tour
    estimated_duration_minutes INT,

    -- Multimedia
    has_audio TINYINT(1) DEFAULT 0,
    audio_file_path VARCHAR(500),
    has_video TINYINT(1) DEFAULT 0,
    video_url VARCHAR(500),

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_storyline_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Storyline stops (objects in a narrative sequence)
CREATE TABLE IF NOT EXISTS exhibition_storyline_stop (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    storyline_id BIGINT UNSIGNED NOT NULL,
    exhibition_object_id BIGINT UNSIGNED NOT NULL,

    sequence_order INT DEFAULT 0,
    stop_number VARCHAR(10),

    -- Interpretive content for this stop
    title VARCHAR(255),
    narrative_text TEXT,
    key_points TEXT, -- JSON array of bullet points
    discussion_questions TEXT,

    -- Connections
    connection_to_next TEXT, -- How this relates to the next stop
    connection_to_theme TEXT,

    -- Multimedia
    audio_transcript TEXT,
    audio_duration_seconds INT,

    -- Timing
    suggested_viewing_minutes INT DEFAULT 2,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (storyline_id) REFERENCES exhibition_storyline(id) ON DELETE CASCADE,
    FOREIGN KEY (exhibition_object_id) REFERENCES exhibition_object(id) ON DELETE CASCADE,
    INDEX idx_stop_storyline (storyline_id),
    INDEX idx_stop_order (storyline_id, sequence_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Installation & Checklist Tables
-- =====================================================

-- Installation checklist templates
CREATE TABLE IF NOT EXISTS exhibition_checklist_template (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    description TEXT,
    checklist_type VARCHAR(89) COMMENT 'planning, preparation, installation, opening, during, closing, deinstallation' NOT NULL,

    -- Items as JSON array
    items JSON, -- [{name, description, required, category}]

    is_default TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exhibition checklist instances
CREATE TABLE IF NOT EXISTS exhibition_checklist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED,

    name VARCHAR(255) NOT NULL,
    checklist_type VARCHAR(89) COMMENT 'planning, preparation, installation, opening, during, closing, deinstallation' NOT NULL,

    due_date DATE,
    completed_date DATE,
    status VARCHAR(56) COMMENT 'not_started, in_progress, completed, overdue' DEFAULT 'not_started',

    assigned_to INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_checklist_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual checklist items
CREATE TABLE IF NOT EXISTS exhibition_checklist_item (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    checklist_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),

    is_required TINYINT(1) DEFAULT 0,
    is_completed TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    completed_by INT,

    notes TEXT,
    sequence_order INT DEFAULT 0,

    FOREIGN KEY (checklist_id) REFERENCES exhibition_checklist(id) ON DELETE CASCADE,
    INDEX idx_item_checklist (checklist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Venue & Gallery Tables
-- =====================================================

-- Venues (museums, galleries, external locations)
CREATE TABLE IF NOT EXISTS exhibition_venue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    venue_type VARCHAR(47) COMMENT 'internal, partner, external, online' DEFAULT 'internal',

    -- Address
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    province_state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'South Africa',

    -- Contact
    contact_name VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    website VARCHAR(500),

    -- Facilities
    total_square_meters DECIMAL(10,2),
    has_climate_control TINYINT(1) DEFAULT 0,
    has_security_system TINYINT(1) DEFAULT 0,
    has_loading_dock TINYINT(1) DEFAULT 0,
    accessibility_rating VARCHAR(31) COMMENT 'none, partial, full' DEFAULT 'partial',

    -- Insurance
    has_facility_insurance TINYINT(1) DEFAULT 0,
    insurance_policy_number VARCHAR(100),

    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Galleries/Rooms within venues
CREATE TABLE IF NOT EXISTS exhibition_gallery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venue_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    gallery_type VARCHAR(68) COMMENT 'gallery, hall, room, corridor, outdoor, foyer, stairwell' DEFAULT 'gallery',

    floor_level VARCHAR(20),
    square_meters DECIMAL(8,2),
    ceiling_height_meters DECIMAL(4,2),
    wall_linear_meters DECIMAL(8,2),

    -- Environment
    has_climate_control TINYINT(1) DEFAULT 0,
    target_temperature DECIMAL(4,1),
    target_humidity DECIMAL(4,1),
    natural_light TINYINT(1) DEFAULT 0,
    max_lux_level INT,

    -- Capacity
    max_visitors INT,
    max_objects INT,

    -- Features
    has_display_cases TINYINT(1) DEFAULT 0,
    number_of_cases INT DEFAULT 0,
    has_pedestals TINYINT(1) DEFAULT 0,
    number_of_pedestals INT DEFAULT 0,
    has_track_lighting TINYINT(1) DEFAULT 0,
    has_av_equipment TINYINT(1) DEFAULT 0,

    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,

    FOREIGN KEY (venue_id) REFERENCES exhibition_venue(id) ON DELETE CASCADE,
    INDEX idx_gallery_venue (venue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Events & Programs
-- =====================================================

-- Exhibition-related events (openings, tours, lectures)
CREATE TABLE IF NOT EXISTS exhibition_event (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    event_type VARCHAR(101) COMMENT 'opening, closing, tour, lecture, workshop, performance, family, school, vip, press, other' NOT NULL,
    description TEXT,

    -- Schedule
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    is_recurring TINYINT(1) DEFAULT 0,
    recurrence_pattern VARCHAR(100), -- e.g., "every Saturday"

    -- Location
    venue_id BIGINT UNSIGNED,
    gallery_id BIGINT UNSIGNED,
    location_notes VARCHAR(255),

    -- Capacity
    max_attendees INT,
    registered_attendees INT DEFAULT 0,
    actual_attendees INT,

    -- Registration
    requires_registration TINYINT(1) DEFAULT 0,
    registration_url VARCHAR(500),
    registration_deadline DATETIME,

    -- Cost
    is_free TINYINT(1) DEFAULT 1,
    ticket_price DECIMAL(10,2),
    ticket_currency VARCHAR(3) DEFAULT 'ZAR',

    -- Presenter
    presenter_name VARCHAR(255),
    presenter_bio TEXT,

    -- Status
    status VARCHAR(53) COMMENT 'scheduled, confirmed, canceled, completed' DEFAULT 'scheduled',

    notes TEXT,

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_event_exhibition (exhibition_id),
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Media & Documentation
-- =====================================================

-- Exhibition images and media
CREATE TABLE IF NOT EXISTS exhibition_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED,

    media_type VARCHAR(67) COMMENT 'image, video, audio, document, floorplan, poster, press' NOT NULL,
    usage_type VARCHAR(78) COMMENT 'promotional, installation, documentation, press, catalog, internal' DEFAULT 'documentation',

    file_path VARCHAR(500),
    file_name VARCHAR(255),
    mime_type VARCHAR(100),
    file_size BIGINT,

    title VARCHAR(255),
    caption TEXT,
    credits VARCHAR(500),

    is_primary TINYINT(1) DEFAULT 0, -- Main promotional image
    is_public TINYINT(1) DEFAULT 1,

    sequence_order INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_media_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert Default Checklist Templates
-- =====================================================

INSERT IGNORE INTO exhibition_checklist_template (name, description, checklist_type, items, is_default) VALUES
('Standard Planning Checklist', 'Basic planning checklist for exhibitions', 'planning',
 '[{"name":"Define exhibition concept","description":"Establish theme, narrative, and goals","required":true,"category":"Concept"},
   {"name":"Identify target audience","description":"Define primary and secondary audiences","required":true,"category":"Concept"},
   {"name":"Set budget","description":"Establish preliminary budget","required":true,"category":"Budget"},
   {"name":"Set timeline","description":"Create project timeline with milestones","required":true,"category":"Planning"},
   {"name":"Identify potential objects","description":"Create preliminary object list","required":true,"category":"Objects"},
   {"name":"Assess loan requirements","description":"Identify objects requiring loans","required":false,"category":"Objects"},
   {"name":"Select venue/galleries","description":"Confirm exhibition spaces","required":true,"category":"Venue"},
   {"name":"Assign project team","description":"Identify curator, designer, registrar","required":true,"category":"Team"}]',
 1),

('Standard Installation Checklist', 'Basic installation checklist', 'installation',
 '[{"name":"Confirm object locations","description":"Finalize placement plan","required":true,"category":"Layout"},
   {"name":"Prepare mounts and cases","description":"All display furniture ready","required":true,"category":"Display"},
   {"name":"Check environmental conditions","description":"Verify temperature and humidity","required":true,"category":"Environment"},
   {"name":"Install lighting","description":"Set up and focus lights","required":true,"category":"Lighting"},
   {"name":"Install objects","description":"Place all objects per plan","required":true,"category":"Objects"},
   {"name":"Complete condition reports","description":"Document pre-installation condition","required":true,"category":"Documentation"},
   {"name":"Install labels","description":"Place all object labels","required":true,"category":"Labels"},
   {"name":"Install graphics","description":"Place wall text and panels","required":true,"category":"Graphics"},
   {"name":"Test AV equipment","description":"Verify all multimedia works","required":false,"category":"AV"},
   {"name":"Security check","description":"Verify all security measures","required":true,"category":"Security"},
   {"name":"Final walkthrough","description":"Complete review before opening","required":true,"category":"Review"}]',
 1),

('Standard Closing Checklist', 'Basic deinstallation checklist', 'closing',
 '[{"name":"Post-exhibition condition reports","description":"Document condition of all objects","required":true,"category":"Documentation"},
   {"name":"Photography","description":"Final installation photography","required":false,"category":"Documentation"},
   {"name":"Deinstall objects","description":"Carefully remove all objects","required":true,"category":"Objects"},
   {"name":"Pack objects","description":"Pack for storage or return","required":true,"category":"Objects"},
   {"name":"Return loans","description":"Arrange return of borrowed objects","required":true,"category":"Loans"},
   {"name":"Remove graphics","description":"Take down all text panels","required":true,"category":"Graphics"},
   {"name":"Remove labels","description":"Collect all object labels","required":true,"category":"Labels"},
   {"name":"Archive materials","description":"File all exhibition documentation","required":true,"category":"Archive"},
   {"name":"Visitor statistics","description":"Compile final visitor numbers","required":true,"category":"Statistics"},
   {"name":"Budget reconciliation","description":"Final budget accounting","required":true,"category":"Budget"},
   {"name":"Team debrief","description":"Post-exhibition review meeting","required":false,"category":"Review"}]',
 1);

-- =====================================================
-- Workflow History for Exhibitions
-- =====================================================

-- Exhibition status history (uses existing workflow tables if available)
CREATE TABLE IF NOT EXISTS exhibition_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,

    from_status VARCHAR(50),
    to_status VARCHAR(50) NOT NULL,

    changed_by INT,
    change_reason TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_history_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/getty_aat_cache.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =====================================================
-- Getty AAT Local Cache Table
-- =====================================================
-- Stores AAT terms locally for fast autocomplete.
-- Populated via: php symfony museum:aat-sync
-- =====================================================

CREATE TABLE IF NOT EXISTS `getty_aat_cache` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `aat_id` varchar(20) NOT NULL COMMENT 'AAT numeric ID e.g. 300033618',
  `uri` varchar(255) NOT NULL COMMENT 'Full Getty URI',
  `pref_label` varchar(512) NOT NULL COMMENT 'English preferred label',
  `scope_note` text COMMENT 'Definition/scope note',
  `broader_label` varchar(512) DEFAULT NULL COMMENT 'Immediate broader term label',
  `broader_id` varchar(20) DEFAULT NULL COMMENT 'Immediate broader term AAT ID',
  `category` varchar(50) NOT NULL DEFAULT 'general' COMMENT 'object_types, materials, techniques, styles_periods, general',
  `synced_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_aat_id` (`aat_id`),
  KEY `idx_category` (`category`),
  KEY `idx_pref_label` (`pref_label`(100)),
  FULLTEXT KEY `ft_label` (`pref_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/loan_enhanced_schema.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =====================================================
-- Enhanced Loan Management Schema
-- Version: 1.1.0
-- Author: Johan Pieterse <johan@theahg.co.za>
-- =====================================================
-- Additional tables for enterprise GLAM loan management
-- Based on Spectrum 5.0 and CollectiveAccess features
-- =====================================================

-- =====================================================
-- FACILITY REPORTS (Borrower Venue Assessment)
-- =====================================================
-- Pre-loan assessment of the borrowing institution's facilities
-- Required for high-value or sensitive loans

CREATE TABLE IF NOT EXISTS loan_facility_report (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,

    -- Venue Information
    venue_name VARCHAR(255) NOT NULL,
    venue_address TEXT,
    venue_contact_name VARCHAR(255),
    venue_contact_email VARCHAR(255),
    venue_contact_phone VARCHAR(100),

    -- Assessment Date
    assessment_date DATE,
    assessed_by INT,

    -- Environmental Controls
    has_climate_control TINYINT(1) DEFAULT 0,
    temperature_min DECIMAL(5,2),
    temperature_max DECIMAL(5,2),
    humidity_min DECIMAL(5,2),
    humidity_max DECIMAL(5,2),
    has_uv_filtering TINYINT(1) DEFAULT 0,
    light_levels_lux INT,

    -- Security
    has_24hr_security TINYINT(1) DEFAULT 0,
    has_cctv TINYINT(1) DEFAULT 0,
    has_alarm_system TINYINT(1) DEFAULT 0,
    has_fire_suppression TINYINT(1) DEFAULT 0,
    fire_suppression_type VARCHAR(100),
    security_notes TEXT,

    -- Display/Storage
    display_case_type VARCHAR(100),
    mounting_method VARCHAR(100),
    barrier_distance DECIMAL(5,2),
    storage_type VARCHAR(100),

    -- Access
    public_access_hours TEXT,
    staff_supervision TINYINT(1) DEFAULT 0,
    photography_allowed TINYINT(1) DEFAULT 1,

    -- Overall Assessment
    overall_rating VARCHAR(63) COMMENT 'excellent, good, acceptable, marginal, unacceptable' DEFAULT 'acceptable',
    recommendations TEXT,
    conditions_required TEXT,

    -- Approval
    approved TINYINT(1) DEFAULT 0,
    approved_by INT,
    approved_date DATETIME,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_facility_loan (loan_id),
    INDEX idx_facility_rating (overall_rating),
    INDEX idx_facility_approved (approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Facility report images
CREATE TABLE IF NOT EXISTS loan_facility_image (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_report_id BIGINT UNSIGNED NOT NULL,

    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    mime_type VARCHAR(100),
    caption TEXT,
    image_type VARCHAR(87) COMMENT 'exterior, interior, display_area, storage, security, climate_control, other' DEFAULT 'other',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (facility_report_id) REFERENCES loan_facility_report(id) ON DELETE CASCADE,
    INDEX idx_facility_image_report (facility_report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CONDITION REPORTS
-- =====================================================
-- Detailed condition documentation before and after loans

CREATE TABLE IF NOT EXISTS loan_condition_report (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    loan_object_id BIGINT UNSIGNED,
    information_object_id INT,

    -- Report Type
    report_type VARCHAR(53) COMMENT 'pre_loan, post_loan, in_transit, periodic' NOT NULL DEFAULT 'pre_loan',

    -- Basic Info
    examination_date DATETIME NOT NULL,
    examiner_id INT,
    examiner_name VARCHAR(255),
    location VARCHAR(255),

    -- Overall Condition
    overall_condition VARCHAR(49) COMMENT 'excellent, good, fair, poor, critical' NOT NULL DEFAULT 'good',
    condition_stable TINYINT(1) DEFAULT 1,

    -- Structural Condition
    structural_condition TEXT,
    surface_condition TEXT,

    -- Specific Issues
    has_damage TINYINT(1) DEFAULT 0,
    damage_description TEXT,
    has_previous_repairs TINYINT(1) DEFAULT 0,
    repair_description TEXT,
    has_active_deterioration TINYINT(1) DEFAULT 0,
    deterioration_description TEXT,

    -- Measurements (if applicable)
    height_cm DECIMAL(10,2),
    width_cm DECIMAL(10,2),
    depth_cm DECIMAL(10,2),
    weight_kg DECIMAL(10,2),

    -- Handling Requirements
    handling_requirements TEXT,
    mounting_requirements TEXT,
    environmental_requirements TEXT,

    -- Recommendations
    treatment_recommendations TEXT,
    display_recommendations TEXT,

    -- Sign-off
    signed_by_lender INT,
    signed_by_borrower INT,
    lender_signature_date DATETIME,
    borrower_signature_date DATETIME,

    -- PDF Export
    pdf_generated TINYINT(1) DEFAULT 0,
    pdf_path VARCHAR(500),

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_object_id) REFERENCES loan_object(id) ON DELETE SET NULL,
    INDEX idx_condition_loan (loan_id),
    INDEX idx_condition_object (loan_object_id),
    INDEX idx_condition_type (report_type),
    INDEX idx_condition_date (examination_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Condition report images
CREATE TABLE IF NOT EXISTS loan_condition_image (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    condition_report_id BIGINT UNSIGNED NOT NULL,

    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    mime_type VARCHAR(100),

    -- Image details
    image_type VARCHAR(67) COMMENT 'overall, detail, damage, measurement, comparison, other' DEFAULT 'overall',
    caption TEXT,
    annotation_data JSON,

    -- Position on object (for mapping)
    view_position VARCHAR(64) COMMENT 'front, back, top, bottom, left, right, detail, other' DEFAULT 'front',

    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (condition_report_id) REFERENCES loan_condition_report(id) ON DELETE CASCADE,
    INDEX idx_condition_image_report (condition_report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COURIER/TRANSPORT MANAGEMENT
-- =====================================================

-- Courier/transport providers
CREATE TABLE IF NOT EXISTS loan_courier (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(100),
    address TEXT,
    website VARCHAR(255),

    -- Capabilities
    is_art_specialist TINYINT(1) DEFAULT 0,
    has_climate_control TINYINT(1) DEFAULT 0,
    has_gps_tracking TINYINT(1) DEFAULT 0,
    insurance_coverage DECIMAL(15,2),
    insurance_currency VARCHAR(3) DEFAULT 'ZAR',

    -- Rating
    quality_rating DECIMAL(3,2),
    notes TEXT,

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_courier_active (is_active),
    INDEX idx_courier_specialist (is_art_specialist)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipments
CREATE TABLE IF NOT EXISTS loan_shipment (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    courier_id BIGINT UNSIGNED,

    -- Shipment Type
    shipment_type VARCHAR(28) COMMENT 'outbound, return' NOT NULL DEFAULT 'outbound',

    -- Reference Numbers
    shipment_number VARCHAR(100),
    tracking_number VARCHAR(255),
    waybill_number VARCHAR(255),

    -- Route
    origin_address TEXT,
    destination_address TEXT,

    -- Dates
    scheduled_pickup DATETIME,
    actual_pickup DATETIME,
    scheduled_delivery DATETIME,
    actual_delivery DATETIME,

    -- Status
    status VARCHAR(98) COMMENT 'planned, picked_up, in_transit, customs, out_for_delivery, delivered, failed, returned' DEFAULT 'planned',

    -- Handling
    handling_instructions TEXT,
    special_requirements TEXT,

    -- Cost
    shipping_cost DECIMAL(12,2),
    insurance_cost DECIMAL(12,2),
    customs_cost DECIMAL(12,2),
    total_cost DECIMAL(12,2),
    cost_currency VARCHAR(3) DEFAULT 'ZAR',

    -- Documents
    customs_declaration_number VARCHAR(255),

    -- Couriers (if multiple handlers)
    courier_names TEXT,
    courier_contact VARCHAR(255),

    notes TEXT,

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    FOREIGN KEY (courier_id) REFERENCES loan_courier(id) ON DELETE SET NULL,
    INDEX idx_shipment_loan (loan_id),
    INDEX idx_shipment_status (status),
    INDEX idx_shipment_tracking (tracking_number),
    INDEX idx_shipment_dates (scheduled_delivery)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipment tracking events
CREATE TABLE IF NOT EXISTS loan_shipment_event (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id BIGINT UNSIGNED NOT NULL,

    event_time DATETIME NOT NULL,
    event_type VARCHAR(100),
    location VARCHAR(255),
    description TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (shipment_id) REFERENCES loan_shipment(id) ON DELETE CASCADE,
    INDEX idx_shipment_event (shipment_id, event_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATIONS
-- =====================================================

-- Notification templates
CREATE TABLE IF NOT EXISTS loan_notification_template (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,

    -- Template Content
    subject_template VARCHAR(500),
    body_template TEXT,

    -- Trigger
    trigger_event VARCHAR(100),
    trigger_days_before INT,

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_notification_code (code),
    INDEX idx_notification_trigger (trigger_event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification log
CREATE TABLE IF NOT EXISTS loan_notification_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED,

    notification_type VARCHAR(100),
    recipient_email VARCHAR(255),
    recipient_name VARCHAR(255),

    subject VARCHAR(500),
    body TEXT,

    status VARCHAR(42) COMMENT 'pending, sent, failed, bounced' DEFAULT 'pending',
    sent_at DATETIME,
    error_message TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES loan_notification_template(id) ON DELETE SET NULL,
    INDEX idx_notification_loan (loan_id),
    INDEX idx_notification_status (status),
    INDEX idx_notification_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PACKING LISTS
-- =====================================================

CREATE TABLE IF NOT EXISTS loan_packing_list (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    shipment_id BIGINT UNSIGNED,

    list_number VARCHAR(100),

    -- Crate/Container Info
    crate_count INT DEFAULT 1,
    total_weight_kg DECIMAL(10,2),
    total_volume_cbm DECIMAL(10,3),

    -- Packing Details
    packing_date DATE,
    packed_by VARCHAR(255),

    -- Verification
    verified_by VARCHAR(255),
    verification_date DATE,

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    FOREIGN KEY (shipment_id) REFERENCES loan_shipment(id) ON DELETE SET NULL,
    INDEX idx_packing_loan (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loan_packing_item (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    packing_list_id BIGINT UNSIGNED NOT NULL,
    loan_object_id BIGINT UNSIGNED,

    crate_number INT DEFAULT 1,
    item_number INT,

    object_description VARCHAR(500),

    -- Dimensions
    height_cm DECIMAL(10,2),
    width_cm DECIMAL(10,2),
    depth_cm DECIMAL(10,2),
    weight_kg DECIMAL(10,2),

    -- Packing Materials
    packing_materials TEXT,
    orientation VARCHAR(100),

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (packing_list_id) REFERENCES loan_packing_list(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_object_id) REFERENCES loan_object(id) ON DELETE SET NULL,
    INDEX idx_packing_item_list (packing_list_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COST TRACKING
-- =====================================================

CREATE TABLE IF NOT EXISTS loan_cost (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,

    cost_type VARCHAR(116) COMMENT 'transport, insurance, conservation, framing, crating, customs, courier_fee, handling, photography, other' NOT NULL,
    description VARCHAR(500),

    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ZAR',

    -- Payment Info
    vendor VARCHAR(255),
    invoice_number VARCHAR(100),
    invoice_date DATE,
    paid TINYINT(1) DEFAULT 0,
    paid_date DATE,

    -- Who Pays
    paid_by VARCHAR(36) COMMENT 'lender, borrower, shared' DEFAULT 'borrower',

    notes TEXT,

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_cost_loan (loan_id),
    INDEX idx_cost_type (cost_type),
    INDEX idx_cost_paid (paid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT NOTIFICATION TEMPLATES
-- =====================================================

INSERT IGNORE INTO loan_notification_template (code, name, description, subject_template, body_template, trigger_event, trigger_days_before, is_active)
VALUES
('loan_due_30', 'Loan Due in 30 Days', 'Reminder sent 30 days before loan end date',
 'Loan {{loan_number}} Due in 30 Days',
 'Dear {{partner_contact_name}},\n\nThis is a reminder that loan {{loan_number}} is due to be returned on {{end_date}}.\n\nPlease begin making arrangements for the return of the loaned objects.\n\nBest regards,\n{{institution_name}}',
 'due_date', 30, 1),

('loan_due_14', 'Loan Due in 14 Days', 'Reminder sent 14 days before loan end date',
 'Loan {{loan_number}} Due in 14 Days - Action Required',
 'Dear {{partner_contact_name}},\n\nThis is a reminder that loan {{loan_number}} is due to be returned on {{end_date}}.\n\nPlease ensure all necessary arrangements are in place for the safe return of the objects.\n\nIf you require an extension, please contact us immediately.\n\nBest regards,\n{{institution_name}}',
 'due_date', 14, 1),

('loan_due_7', 'Loan Due in 7 Days', 'Final reminder sent 7 days before loan end date',
 'URGENT: Loan {{loan_number}} Due in 7 Days',
 'Dear {{partner_contact_name}},\n\nThis is a final reminder that loan {{loan_number}} is due to be returned on {{end_date}}.\n\nPlease confirm the return arrangements as soon as possible.\n\nBest regards,\n{{institution_name}}',
 'due_date', 7, 1),

('loan_overdue', 'Loan Overdue', 'Notification sent when loan is overdue',
 'OVERDUE: Loan {{loan_number}} - Immediate Action Required',
 'Dear {{partner_contact_name}},\n\nLoan {{loan_number}} was due to be returned on {{end_date}} and is now overdue.\n\nPlease contact us immediately to arrange the return of the loaned objects.\n\nBest regards,\n{{institution_name}}',
 'overdue', 0, 1),

('loan_approved', 'Loan Approved', 'Notification when loan request is approved',
 'Loan Request {{loan_number}} Approved',
 'Dear {{partner_contact_name}},\n\nYour loan request {{loan_number}} has been approved.\n\nLoan Period: {{start_date}} to {{end_date}}\nPurpose: {{purpose}}\n\nWe will be in touch regarding the loan agreement and next steps.\n\nBest regards,\n{{institution_name}}',
 'status_change', 0, 1),

('loan_dispatched', 'Objects Dispatched', 'Notification when objects are dispatched',
 'Loan {{loan_number}} - Objects Dispatched',
 'Dear {{partner_contact_name}},\n\nThe objects for loan {{loan_number}} have been dispatched.\n\nTracking Number: {{tracking_number}}\nExpected Delivery: {{scheduled_delivery}}\n\nPlease confirm receipt once the objects arrive.\n\nBest regards,\n{{institution_name}}',
 'status_change', 0, 1)

ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- INSERT DEFAULT COURIERS (South African)
-- =====================================================

INSERT IGNORE INTO loan_courier (company_name, contact_email, is_art_specialist, has_climate_control, has_gps_tracking, notes, is_active)
VALUES
('Mtunzini Group', 'info@mtunzini.co.za', 1, 1, 1, 'Specialist art and heritage logistics in Southern Africa', 1),
('Crown Fine Art', 'southafrica@crownfineart.com', 1, 1, 1, 'International art logistics with SA presence', 1),
('DHL Express', 'info@dhl.co.za', 0, 0, 1, 'General courier with tracking', 1),
('RAM Hand-to-Hand', 'info@ram.co.za', 0, 0, 1, 'Secure courier service', 1),
('The Courier Guy', 'info@thecourierguy.co.za', 0, 0, 1, 'General courier with tracking', 1)
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name);

-- ---------------------------------------------------------------------------
-- Merged in from database/loan_schema.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =====================================================
-- Loan Management Module Schema
-- Version: 1.0.0
-- Author: Johan Pieterse <johan@theahg.co.za>
-- =====================================================
-- Comprehensive loan management for GLAM institutions
-- Supports both loan out (lending) and loan in (borrowing)
-- =====================================================

-- =====================================================
-- Core Loan Tables
-- =====================================================

-- Main loan record
CREATE TABLE IF NOT EXISTS loan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Loan identification
    loan_number VARCHAR(50) NOT NULL UNIQUE,
    loan_type VARCHAR(20) COMMENT 'out, in' NOT NULL,

    -- Basic information
    title VARCHAR(500),
    description TEXT,
    purpose VARCHAR(97) COMMENT 'exhibition, research, conservation, photography, education, filming, long_term, other' DEFAULT 'exhibition',

    -- Partner institution (borrower for loan_out, lender for loan_in)
    partner_institution VARCHAR(500) NOT NULL,
    partner_contact_name VARCHAR(255),
    partner_contact_email VARCHAR(255),
    partner_contact_phone VARCHAR(100),
    partner_address TEXT,

    -- Key dates
    request_date DATETIME,
    start_date DATE,
    end_date DATE,
    return_date DATE,

    -- Insurance
    insurance_type VARCHAR(54) COMMENT 'borrower, lender, shared, government, self' DEFAULT 'borrower',
    insurance_value DECIMAL(15,2),
    insurance_currency VARCHAR(3) DEFAULT 'ZAR',
    insurance_policy_number VARCHAR(100),
    insurance_provider VARCHAR(255),

    -- Fees
    loan_fee DECIMAL(12,2),
    loan_fee_currency VARCHAR(3) DEFAULT 'ZAR',

    -- Approval
    internal_approver_id INT,
    approved_date DATETIME,

    -- Related exhibition (if applicable)
    exhibition_id BIGINT UNSIGNED,

    -- Notes
    notes TEXT,

    -- Tracking
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_loan_number (loan_number),
    INDEX idx_loan_type (loan_type),
    INDEX idx_loan_partner (partner_institution(100)),
    INDEX idx_loan_dates (start_date, end_date),
    INDEX idx_loan_return (return_date),
    INDEX idx_loan_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Objects included in a loan (many-to-many)
CREATE TABLE IF NOT EXISTS loan_object (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    information_object_id INT NOT NULL,

    -- Object details (cached for external objects not in AtoM)
    object_title VARCHAR(500),
    object_identifier VARCHAR(255),

    -- Insurance
    insurance_value DECIMAL(15,2),

    -- Condition reporting
    condition_report_id BIGINT UNSIGNED,
    condition_on_departure TEXT,
    condition_on_return TEXT,

    -- Requirements
    special_requirements TEXT,
    display_requirements TEXT,

    -- Status tracking
    status VARCHAR(91) COMMENT 'pending, approved, prepared, dispatched, received, on_display, packed, returned' DEFAULT 'pending',

    -- Dates
    dispatched_date DATE,
    received_date DATE,
    returned_date DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_loanobj_loan (loan_id),
    INDEX idx_loanobj_object (information_object_id),
    INDEX idx_loanobj_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents attached to loans
CREATE TABLE IF NOT EXISTS loan_document (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,

    document_type VARCHAR(91) COMMENT 'agreement, condition_report, insurance, courier, correspondence, receipt, other' NOT NULL,

    file_path VARCHAR(500),
    file_name VARCHAR(255),
    mime_type VARCHAR(100),
    file_size BIGINT,

    description TEXT,

    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_loandoc_loan (loan_id),
    INDEX idx_loandoc_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loan extension history
CREATE TABLE IF NOT EXISTS loan_extension (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,

    previous_end_date DATE NOT NULL,
    new_end_date DATE NOT NULL,

    reason TEXT,
    approved_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_loanext_loan (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Workflow Tables (if not already created by framework)
-- =====================================================

-- Workflow definitions
CREATE TABLE IF NOT EXISTS workflow_definition (
    id VARCHAR(100) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    entity_type VARCHAR(100) NOT NULL,
    initial_state VARCHAR(100) NOT NULL,
    states JSON NOT NULL,
    transitions JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow instances
CREATE TABLE IF NOT EXISTS workflow_instance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,

    current_state VARCHAR(100) NOT NULL,
    is_complete TINYINT(1) DEFAULT 0,

    context JSON,

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_wi_workflow (workflow_id),
    INDEX idx_wi_entity (entity_type, entity_id),
    INDEX idx_wi_state (current_state),
    UNIQUE KEY uk_workflow_entity (workflow_id, entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow history
CREATE TABLE IF NOT EXISTS workflow_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id BIGINT UNSIGNED NOT NULL,

    from_state VARCHAR(100),
    to_state VARCHAR(100) NOT NULL,
    transition_name VARCHAR(100),

    comment TEXT,
    performed_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (instance_id) REFERENCES workflow_instance(id) ON DELETE CASCADE,
    INDEX idx_wh_instance (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert Loan Workflow Definitions
-- =====================================================

INSERT IGNORE INTO workflow_definition (id, name, description, entity_type, initial_state, states, transitions, is_active)
VALUES
('loan_out', 'Loan Out Workflow', 'Workflow for outgoing loans (lending objects)', 'loan', 'draft',
'["draft", "submitted", "under_review", "approved", "preparing", "dispatched", "on_loan", "return_requested", "returned", "closed", "cancelled"]',
'[
  {"name": "submit", "from": ["draft"], "to": "submitted", "label": "Submit for Review"},
  {"name": "review", "from": ["submitted"], "to": "under_review", "label": "Begin Review"},
  {"name": "approve", "from": ["under_review"], "to": "approved", "label": "Approve Loan"},
  {"name": "reject", "from": ["submitted", "under_review"], "to": "draft", "label": "Return to Draft"},
  {"name": "prepare", "from": ["approved"], "to": "preparing", "label": "Begin Preparation"},
  {"name": "dispatch", "from": ["preparing"], "to": "dispatched", "label": "Dispatch Objects"},
  {"name": "confirm_receipt", "from": ["dispatched"], "to": "on_loan", "label": "Confirm Receipt"},
  {"name": "request_return", "from": ["on_loan"], "to": "return_requested", "label": "Request Return"},
  {"name": "receive_return", "from": ["return_requested", "on_loan"], "to": "returned", "label": "Receive Return"},
  {"name": "close", "from": ["returned"], "to": "closed", "label": "Close Loan"},
  {"name": "cancel", "from": ["draft", "submitted", "under_review", "approved"], "to": "cancelled", "label": "Cancel Loan"}
]',
1),

('loan_in', 'Loan In Workflow', 'Workflow for incoming loans (borrowing objects)', 'loan', 'draft',
'["draft", "submitted", "approved", "awaiting_delivery", "received", "on_display", "packing", "returned", "closed", "cancelled"]',
'[
  {"name": "submit", "from": ["draft"], "to": "submitted", "label": "Submit Request"},
  {"name": "approve", "from": ["submitted"], "to": "approved", "label": "Mark Approved"},
  {"name": "reject", "from": ["submitted"], "to": "draft", "label": "Return to Draft"},
  {"name": "await_delivery", "from": ["approved"], "to": "awaiting_delivery", "label": "Await Delivery"},
  {"name": "receive", "from": ["awaiting_delivery"], "to": "received", "label": "Receive Objects"},
  {"name": "install", "from": ["received"], "to": "on_display", "label": "Install on Display"},
  {"name": "begin_packing", "from": ["on_display"], "to": "packing", "label": "Begin Packing"},
  {"name": "return", "from": ["packing", "received"], "to": "returned", "label": "Return Objects"},
  {"name": "close", "from": ["returned"], "to": "closed", "label": "Close Loan"},
  {"name": "cancel", "from": ["draft", "submitted", "approved"], "to": "cancelled", "label": "Cancel Request"}
]',
1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    states = VALUES(states),
    transitions = VALUES(transitions);
