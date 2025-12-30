-- ============================================================================
-- CCO/CDWA Museum Controlled Vocabularies - Complete Fixed SQL
-- Creates taxonomies, root terms, and child terms with proper parent_id
-- ============================================================================

USE atom292;
START TRANSACTION;

-- ============================================================================
-- 1. CREATOR ROLE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @creator_role_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@creator_role_taxonomy_id, 'Creator roles for museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@creator_role_taxonomy_id, 'en', 'Creator Role (CCO)', 'CCO/CDWA creator roles for museum cataloging');

-- Root term for Creator Role
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_creator_role = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_creator_role, @creator_role_taxonomy_id, NULL, 'en', 'root_creator_role');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_creator_role, 'en', 'Creator Role (root)');

-- Creator Role Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'artist');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Artist');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'architect');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Architect');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'author');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Author');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'calligrapher');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Calligrapher');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'carver');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Carver');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'ceramicist');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ceramicist');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'designer');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Designer');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'draftsman');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Draftsman');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'embroiderer');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Embroiderer');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'engraver');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Engraver');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'goldsmith');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Goldsmith');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'illustrator');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Illustrator');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'jeweler');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Jeweler');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'maker');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Maker');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'manufacturer');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Manufacturer');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'painter');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Painter');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'photographer');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Photographer');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'potter');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Potter');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'printmaker');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Printmaker');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'sculptor');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Sculptor');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'silversmith');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Silversmith');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'weaver');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Weaver');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @creator_role_taxonomy_id, @root_creator_role, 'en', 'workshop_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Workshop of');

-- ============================================================================
-- 2. ATTRIBUTION QUALIFIER TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @attribution_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@attribution_taxonomy_id, 'Attribution qualifiers for museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@attribution_taxonomy_id, 'en', 'Attribution Qualifier (CCO)', 'CCO/CDWA attribution qualifiers');

-- Root term for Attribution Qualifier
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_attribution = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_attribution, @attribution_taxonomy_id, NULL, 'en', 'root_attribution_qualifier');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_attribution, 'en', 'Attribution Qualifier (root)');

-- Attribution Qualifier Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'ascribed_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ascribed to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'attributed_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Attributed to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'circle_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Circle of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'copy_after');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copy after');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'follower_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Follower of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'manner_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Manner of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'possibly');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Possibly');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'probably');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Probably');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'school_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'School of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'studio_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Studio of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'style_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Style of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @attribution_taxonomy_id, @root_attribution, 'en', 'workshop_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Workshop of');

-- ============================================================================
-- 3. DATE QUALIFIER TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @date_qualifier_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@date_qualifier_taxonomy_id, 'Date qualifiers for museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@date_qualifier_taxonomy_id, 'en', 'Date Qualifier (CCO)', 'CCO/CDWA date qualifiers');

-- Root term for Date Qualifier
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_date_qualifier = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_date_qualifier, @date_qualifier_taxonomy_id, NULL, 'en', 'root_date_qualifier');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_date_qualifier, 'en', 'Date Qualifier (root)');

-- Date Qualifier Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'about');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'About');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'approximately');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Approximately');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'before');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Before');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'after');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'After');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'circa');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Circa');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'early');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Early');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'mid');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Mid');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'late');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Late');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'probably');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Probably');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @date_qualifier_taxonomy_id, @root_date_qualifier, 'en', 'possibly');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Possibly');

-- ============================================================================
-- 4. CONDITION TERM TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @condition_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@condition_taxonomy_id, 'Condition terms for museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@condition_taxonomy_id, 'en', 'Condition Term (CCO)', 'CCO/CDWA condition assessment terms');

-- Root term for Condition Term
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_condition = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_condition, @condition_taxonomy_id, NULL, 'en', 'root_condition_term');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_condition, 'en', 'Condition Term (root)');

-- Condition Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'excellent');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Excellent');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'very_good');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Very good');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'good');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Good');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'fair');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fair');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'poor');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Poor');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'fragmentary');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fragmentary');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'damaged');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Damaged');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @condition_taxonomy_id, @root_condition, 'en', 'restored');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Restored');

-- ============================================================================
-- 5. SUBJECT TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @subject_type_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@subject_type_taxonomy_id, 'Subject types for museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@subject_type_taxonomy_id, 'en', 'Subject Type (CCO)', 'CCO/CDWA subject indexing types');

-- Root term for Subject Type
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_subject_type = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_subject_type, @subject_type_taxonomy_id, NULL, 'en', 'root_subject_type');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_subject_type, 'en', 'Subject Type (root)');

-- Subject Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @subject_type_taxonomy_id, @root_subject_type, 'en', 'iconography');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Iconography');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @subject_type_taxonomy_id, @root_subject_type, 'en', 'narrative');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Narrative');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @subject_type_taxonomy_id, @root_subject_type, 'en', 'description');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Description');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @subject_type_taxonomy_id, @root_subject_type, 'en', 'interpretation');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Interpretation');

-- ============================================================================
-- 6. INSCRIPTION TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @inscription_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@inscription_taxonomy_id, 'Inscription types for museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@inscription_taxonomy_id, 'en', 'Inscription Type (CCO)', 'CCO/CDWA inscription and mark types');

-- Root term for Inscription Type
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_inscription = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_inscription, @inscription_taxonomy_id, NULL, 'en', 'root_inscription_type');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_inscription, 'en', 'Inscription Type (root)');

-- Inscription Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'signature');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Signature');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'date');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Date');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'title');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Title');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'dedication');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Dedication');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'inscription');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Inscription');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'label');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Label');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'stamp');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Stamp');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'watermark');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Watermark');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'monogram');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Monogram');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @inscription_taxonomy_id, @root_inscription, 'en', 'hallmark');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Hallmark');

-- ============================================================================
-- 7. RELATED WORK TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @related_work_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@related_work_taxonomy_id, 'Related work relationship types', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@related_work_taxonomy_id, 'en', 'Related Work Type (CCO)', 'CCO/CDWA related work relationship types');

-- Root term for Related Work Type
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_related_work = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_related_work, @related_work_taxonomy_id, NULL, 'en', 'root_related_work_type');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_related_work, 'en', 'Related Work Type (root)');

-- Related Work Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'part_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Part of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'companion_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Companion to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'copy_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copy of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'derived_from');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Derived from');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'model_for');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Model for');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'pendant_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Pendant to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'preparatory_for');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Preparatory for');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'related_to');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Related to');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'replica_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Replica of');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'study_for');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Study for');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @related_work_taxonomy_id, @root_related_work, 'en', 'version_of');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Version of');

-- ============================================================================
-- 8. RIGHTS TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @rights_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@rights_taxonomy_id, 'Rights types for museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@rights_taxonomy_id, 'en', 'Rights Type (CCO)', 'CCO/CDWA rights and licensing types');

-- Root term for Rights Type
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_rights = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_rights, @rights_taxonomy_id, NULL, 'en', 'root_rights_type');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_rights, 'en', 'Rights Type (root)');

-- Rights Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'copyright');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Copyright');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'trademark');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Trademark');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'license');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'License');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'public_domain');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Public domain');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'creative_commons');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Creative Commons');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @rights_taxonomy_id, @root_rights, 'en', 'unknown');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Unknown');

-- ============================================================================
-- 9. WORK TYPE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @work_type_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@work_type_taxonomy_id, 'Work types for museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@work_type_taxonomy_id, 'en', 'Work Type (CCO)', 'CCO/CDWA work type classification');

-- Root term for Work Type
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_work_type = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_work_type, @work_type_taxonomy_id, NULL, 'en', 'root_work_type');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_work_type, 'en', 'Work Type (root)');

-- Work Type Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @work_type_taxonomy_id, @root_work_type, 'en', 'visual_works');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Visual Works');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @work_type_taxonomy_id, @root_work_type, 'en', 'built_works');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Built Works');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @work_type_taxonomy_id, @root_work_type, 'en', 'movable_works');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Movable Works');

-- ============================================================================
-- 10. MATERIAL TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @materials_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@materials_taxonomy_id, 'Materials used in museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@materials_taxonomy_id, 'en', 'Material (CCO)', 'CCO/CDWA materials vocabulary');

-- Root term for Material
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_material = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_material, @materials_taxonomy_id, NULL, 'en', 'root_material');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_material, 'en', 'Material (root)');

-- Material Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'oil_paint');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Oil paint');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'canvas');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Canvas');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'paper');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Paper');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'wood');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Wood');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'metal');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Metal');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'stone');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Stone');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'textile');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Textile');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'ceramic');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Ceramic');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'glass');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Glass');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @materials_taxonomy_id, @root_material, 'en', 'plastic');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Plastic');

-- ============================================================================
-- 11. TECHNIQUE TAXONOMY
-- ============================================================================
INSERT INTO object (class_name, created_at, updated_at) 
VALUES ('QubitTaxonomy', NOW(), NOW());
SET @techniques_taxonomy_id = LAST_INSERT_ID();

INSERT INTO taxonomy (id, usage, parent_id, source_culture) 
VALUES (@techniques_taxonomy_id, 'Techniques used in creating museum objects', 30, 'en');

INSERT INTO taxonomy_i18n (id, culture, name, note) 
VALUES (@techniques_taxonomy_id, 'en', 'Technique (CCO)', 'CCO/CDWA techniques vocabulary');

-- Root term for Technique
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @root_technique = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) 
VALUES (@root_technique, @techniques_taxonomy_id, NULL, 'en', 'root_technique');
INSERT INTO term_i18n (id, culture, name) 
VALUES (@root_technique, 'en', 'Technique (root)');

-- Technique Terms
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'painted');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Painted');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'glazed');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Glazed');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'carved');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Carved');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'etched');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Etched');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'printed');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Printed');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'woven');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Woven');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'cast');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Cast');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'molded');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Molded');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'assembled');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Assembled');

INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @term_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, parent_id, source_culture, code) VALUES (@term_id, @techniques_taxonomy_id, @root_technique, 'en', 'fired');
INSERT INTO term_i18n (id, culture, name) VALUES (@term_id, 'en', 'Fired');

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