-- ============================================================================
-- CCO/CDWA Museum Controlled Vocabularies - Taxonomy Setup
-- Creates taxonomies and terms for museum cataloging
-- ============================================================================

-- Get the next available taxonomy ID (adjust if needed)
SET @base_taxonomy_id = (SELECT COALESCE(MAX(id), 500) + 1 FROM taxonomy);

-- ============================================================================
-- 1. CREATOR ROLES TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @creator_role_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@creator_role_taxonomy_id, 'Creator roles for museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@creator_role_taxonomy_id, 'en', 'Creator Role (CCO)', 'CCO/CDWA creator roles for museum cataloging');

-- Creator Role Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'artist');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Artist');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'architect');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Architect');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'author');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Author');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'calligrapher');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Calligrapher');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'carver');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Carver');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'ceramicist');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ceramicist');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'designer');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Designer');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'draftsman');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Draftsman');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'embroiderer');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Embroiderer');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'engraver');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Engraver');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'goldsmith');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Goldsmith');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'illustrator');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Illustrator');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'jeweler');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Jeweler');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'maker');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Maker');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'manufacturer');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Manufacturer');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'painter');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Painter');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'photographer');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Photographer');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'potter');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Potter');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'printmaker');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Printmaker');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'sculptor');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Sculptor');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'silversmith');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Silversmith');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'weaver');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Weaver');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @creator_role_taxonomy_id, 'workshop_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Workshop of');

-- ============================================================================
-- 2. ATTRIBUTION QUALIFIER TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @attribution_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@attribution_taxonomy_id, 'Attribution qualifiers for museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@attribution_taxonomy_id, 'en', 'Attribution Qualifier (CCO)', 'CCO/CDWA attribution qualifiers');

-- Attribution Qualifier Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'ascribed_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ascribed to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'attributed_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Attributed to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'circle_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Circle of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'copy_after');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copy after');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'follower_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Follower of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'manner_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Manner of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'possibly');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Possibly');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'probably');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Probably');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'school_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'School of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'studio_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Studio of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'style_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Style of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @attribution_taxonomy_id, 'workshop_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Workshop of');

-- ============================================================================
-- 3. DATE QUALIFIER TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @date_qualifier_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@date_qualifier_taxonomy_id, 'Date qualifiers for museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@date_qualifier_taxonomy_id, 'en', 'Date Qualifier (CCO)', 'CCO/CDWA date qualifiers');

-- Date Qualifier Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'about');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'About');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'approximately');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Approximately');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'before');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Before');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'after');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'After');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'circa');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Circa');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'early');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Early');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'mid');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Mid');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'late');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Late');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'probably');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Probably');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @date_qualifier_taxonomy_id, 'possibly');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Possibly');

-- ============================================================================
-- 4. CONDITION TERM TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @condition_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@condition_taxonomy_id, 'Condition terms for museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@condition_taxonomy_id, 'en', 'Condition Term (CCO)', 'CCO/CDWA condition assessment terms');

-- Condition Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @condition_taxonomy_id, 'excellent');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Excellent');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @condition_taxonomy_id, 'very_good');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Very good');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @condition_taxonomy_id, 'good');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Good');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @condition_taxonomy_id, 'fair');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fair');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @condition_taxonomy_id, 'poor');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Poor');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @condition_taxonomy_id, 'fragmentary');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fragmentary');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @condition_taxonomy_id, 'damaged');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Damaged');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @condition_taxonomy_id, 'restored');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Restored');

-- ============================================================================
-- 5. SUBJECT TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @subject_type_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@subject_type_taxonomy_id, 'Subject types for museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@subject_type_taxonomy_id, 'en', 'Subject Type (CCO)', 'CCO/CDWA subject indexing types');

-- Subject Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @subject_type_taxonomy_id, 'iconography');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Iconography');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @subject_type_taxonomy_id, 'narrative');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Narrative');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @subject_type_taxonomy_id, 'description');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Description');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @subject_type_taxonomy_id, 'interpretation');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Interpretation');

-- ============================================================================
-- 6. INSCRIPTION TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @inscription_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@inscription_taxonomy_id, 'Inscription types for museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@inscription_taxonomy_id, 'en', 'Inscription Type (CCO)', 'CCO/CDWA inscription and mark types');

-- Inscription Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'signature');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Signature');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'date');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Date');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'title');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Title');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'dedication');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Dedication');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'inscription');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Inscription');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'label');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Label');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'stamp');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Stamp');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'watermark');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Watermark');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'monogram');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Monogram');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @inscription_taxonomy_id, 'hallmark');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Hallmark');

-- ============================================================================
-- 7. RELATED WORK TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @related_work_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@related_work_taxonomy_id, 'Related work relationship types', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@related_work_taxonomy_id, 'en', 'Related Work Type (CCO)', 'CCO/CDWA related work relationship types');

-- Related Work Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'part_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Part of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'companion_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Companion to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'copy_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copy of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'derived_from');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Derived from');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'model_for');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Model for');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'pendant_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Pendant to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'preparatory_for');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Preparatory for');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'related_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Related to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'replica_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Replica of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'study_for');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Study for');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @related_work_taxonomy_id, 'version_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Version of');

-- ============================================================================
-- 8. RIGHTS TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @rights_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@rights_taxonomy_id, 'Rights types for museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@rights_taxonomy_id, 'en', 'Rights Type (CCO)', 'CCO/CDWA rights and licensing types');

-- Rights Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @rights_taxonomy_id, 'copyright');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copyright');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @rights_taxonomy_id, 'trademark');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Trademark');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @rights_taxonomy_id, 'license');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'License');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @rights_taxonomy_id, 'public_domain');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Public domain');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @rights_taxonomy_id, 'creative_commons');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Creative Commons');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @rights_taxonomy_id, 'unknown');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Unknown');

-- ============================================================================
-- 9. WORK TYPE TAXONOMY (for Visual/Built/Movable works)
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @work_type_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@work_type_taxonomy_id, 'Work types for museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@work_type_taxonomy_id, 'en', 'Work Type (CCO)', 'CCO/CDWA work type classification');

-- Work Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @work_type_taxonomy_id, 'visual_works');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Visual Works');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @work_type_taxonomy_id, 'built_works');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Built Works');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @work_type_taxonomy_id, 'movable_works');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Movable Works');

-- ============================================================================
-- 10. MATERIALS TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @materials_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@materials_taxonomy_id, 'Materials used in museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@materials_taxonomy_id, 'en', 'Material (CCO)', 'CCO/CDWA materials vocabulary');

-- Material Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'oil_paint');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Oil paint');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'canvas');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Canvas');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'paper');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Paper');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'wood');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Wood');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'metal');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Metal');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'stone');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Stone');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'textile');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Textile');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'ceramic');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ceramic');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'glass');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Glass');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @materials_taxonomy_id, 'plastic');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Plastic');

-- ============================================================================
-- 11. TECHNIQUES TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @techniques_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id) 
VALUES (@techniques_taxonomy_id, 'Techniques used in creating museum objects', NULL);

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@techniques_taxonomy_id, 'en', 'Technique (CCO)', 'CCO/CDWA techniques vocabulary');

-- Technique Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'painted');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Painted');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'glazed');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Glazed');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'carved');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Carved');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'etched');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Etched');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'printed');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Printed');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'woven');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Woven');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'cast');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Cast');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'molded');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Molded');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'assembled');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Assembled');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, code) VALUES (@term_id, @techniques_taxonomy_id, 'fired');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fired');

-- ============================================================================
-- OUTPUT TAXONOMY IDs FOR REFERENCE
-- ============================================================================
SELECT 'CCO/CDWA Taxonomies Created:' AS message;
SELECT 
    t.id AS taxonomy_id,
    ti.name AS taxonomy_name
FROM taxonomy t
JOIN taxonomy_i18n ti ON t.id = ti.id
WHERE ti.name LIKE '%(CCO)%'
ORDER BY t.id;
