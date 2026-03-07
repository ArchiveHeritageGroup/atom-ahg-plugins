-- =====================================================
-- Registry Standards & Setup Guides Migration
-- Date: 2026-03-07
-- =====================================================

-- ---------------------------------------------------
-- 1. registry_standard — Reference to external standards
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_standard` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `acronym` varchar(50) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'descriptive' COMMENT 'descriptive, preservation, rights, accounting, compliance, metadata, interchange, sector',
  `description` text,
  `short_description` varchar(500) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `issuing_body` varchar(255) DEFAULT NULL,
  `current_version` varchar(50) DEFAULT NULL,
  `publication_year` int DEFAULT NULL,
  `sector_applicability` json DEFAULT NULL COMMENT '["archive","library","museum","gallery","dam"]',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_standard_slug` (`slug`),
  KEY `idx_standard_category` (`category`),
  KEY `idx_standard_active` (`is_active`),
  FULLTEXT KEY `ft_standard_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. registry_standard_extension — WHERE Heratio deviates/extends
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_standard_extension` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `standard_id` bigint unsigned NOT NULL,
  `extension_type` varchar(30) NOT NULL DEFAULT 'addition' COMMENT 'addition, deviation, implementation_note, api_binding',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `rationale` text COMMENT 'Why this extension exists',
  `plugin_name` varchar(100) DEFAULT NULL COMMENT 'Which plugin implements this',
  `api_endpoint` varchar(255) DEFAULT NULL COMMENT 'API route if applicable',
  `db_tables` varchar(500) DEFAULT NULL COMMENT 'Comma-separated table names affected',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int DEFAULT 100,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ext_standard` (`standard_id`),
  KEY `idx_ext_type` (`extension_type`),
  KEY `idx_ext_plugin` (`plugin_name`),
  CONSTRAINT `fk_ext_standard` FOREIGN KEY (`standard_id`) REFERENCES `registry_standard` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. registry_software_standard — Vendor conformance declarations
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_software_standard` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `software_id` bigint unsigned NOT NULL,
  `standard_id` bigint unsigned NOT NULL,
  `conformance_level` varchar(20) NOT NULL DEFAULT 'partial' COMMENT 'full, partial, extended, planned',
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_software_standard` (`software_id`, `standard_id`),
  KEY `idx_ss_software` (`software_id`),
  KEY `idx_ss_standard` (`standard_id`),
  CONSTRAINT `fk_ss_software` FOREIGN KEY (`software_id`) REFERENCES `registry_software` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_standard` FOREIGN KEY (`standard_id`) REFERENCES `registry_standard` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. registry_setup_guide — Deployment/config guides under Software
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_setup_guide` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `software_id` bigint unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'deployment' COMMENT 'security, deployment, configuration, optimization, troubleshooting, integration',
  `content` text NOT NULL COMMENT 'Markdown content',
  `short_description` varchar(500) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_user_id` int DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `view_count` int DEFAULT 0,
  `sort_order` int DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_guide_slug` (`software_id`, `slug`),
  KEY `idx_guide_software` (`software_id`),
  KEY `idx_guide_category` (`category`),
  KEY `idx_guide_active` (`is_active`),
  FULLTEXT KEY `ft_guide_search` (`title`, `content`),
  CONSTRAINT `fk_guide_software` FOREIGN KEY (`software_id`) REFERENCES `registry_software` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 5. Seed Data: Common GLAM/DAM Standards (links only)
-- ---------------------------------------------------
INSERT INTO `registry_standard` (`name`, `acronym`, `slug`, `category`, `short_description`, `website_url`, `issuing_body`, `current_version`, `publication_year`, `sector_applicability`, `is_featured`, `sort_order`) VALUES
-- Descriptive Standards
('General International Standard Archival Description', 'ISAD(G)', 'isad-g', 'descriptive', 'Standard for describing archival materials at all levels.', 'https://www.ica.org/en/isadg-general-international-standard-archival-description-second-edition', 'International Council on Archives (ICA)', '2nd Edition', 2000, '["archive"]', 1, 10),
('International Standard Archival Authority Record', 'ISAAR(CPF)', 'isaar-cpf', 'descriptive', 'Standard for creating authority records for corporate bodies, persons, and families.', 'https://www.ica.org/en/isaar-cpf-international-standard-archival-authority-record-corporate-bodies-persons-and-families-2nd', 'International Council on Archives (ICA)', '2nd Edition', 2004, '["archive"]', 1, 20),
('Describing Archives: A Content Standard', 'DACS', 'dacs', 'descriptive', 'US standard for describing archives, personal papers, and manuscripts.', 'https://saa-ts-dacs.github.io/', 'Society of American Archivists (SAA)', '2nd Edition', 2013, '["archive"]', 0, 30),
('Rules for Archival Description', 'RAD', 'rad', 'descriptive', 'Canadian standard for archival description.', 'https://archivescanada.ca/resources/rad/', 'Canadian Committee on Archival Description', '2nd Edition', 2008, '["archive"]', 0, 40),
('Dublin Core Metadata Element Set', 'DC', 'dublin-core', 'metadata', 'General-purpose metadata standard for cross-domain resource description.', 'https://www.dublincore.org/specifications/dublin-core/dcmi-terms/', 'Dublin Core Metadata Initiative (DCMI)', 'ISO 15836:2009', 2009, '["archive","library","museum","gallery","dam"]', 1, 50),
('Metadata Object Description Schema', 'MODS', 'mods', 'metadata', 'XML schema for bibliographic metadata, subset of MARC.', 'https://www.loc.gov/standards/mods/', 'Library of Congress', '3.8', 2021, '["library","archive"]', 0, 60),
('Encoded Archival Description', 'EAD', 'ead', 'interchange', 'XML standard for encoding finding aids.', 'https://www.loc.gov/ead/', 'Library of Congress / SAA', 'EAD3 1.1.1', 2019, '["archive"]', 0, 70),
('International Standard for Describing Functions', 'ISDF', 'isdf', 'descriptive', 'Standard for describing functions of corporate bodies.', 'https://www.ica.org/en/isdf-international-standard-describing-functions', 'International Council on Archives (ICA)', '1st Edition', 2007, '["archive"]', 0, 80),
('International Standard for Describing Institutions with Archival Holdings', 'ISDIAH', 'isdiah', 'descriptive', 'Standard for describing institutions that hold archival materials.', 'https://www.ica.org/en/isdiah-international-standard-describing-institutions-archival-holdings', 'International Council on Archives (ICA)', '1st Edition', 2008, '["archive"]', 0, 90),
('Records in Contexts', 'RiC', 'ric', 'descriptive', 'Next-generation archival description standard based on linked data and ontologies.', 'https://www.ica.org/standards/RiC/RiC-O_v0-2.html', 'International Council on Archives (ICA)', '0.2', 2021, '["archive"]', 1, 100),

-- Preservation Standards
('PREMIS Data Dictionary for Preservation Metadata', 'PREMIS', 'premis', 'preservation', 'Standard for metadata supporting the preservation of digital objects.', 'https://www.loc.gov/standards/premis/', 'Library of Congress', '3.0', 2015, '["archive","library","museum","dam"]', 1, 110),
('Open Archival Information System', 'OAIS', 'oais', 'preservation', 'Reference model for long-term preservation of digital information.', 'https://www.iso.org/standard/57284.html', 'Consultative Committee for Space Data Systems (CCSDS)', 'ISO 14721:2012', 2012, '["archive","library","museum","dam"]', 1, 120),
('PRONOM Technical Registry', 'PRONOM', 'pronom', 'preservation', 'File format registry for identification and preservation planning.', 'https://www.nationalarchives.gov.uk/PRONOM/', 'The National Archives (UK)', 'Ongoing', 2002, '["archive","library","dam"]', 0, 130),

-- Rights Standards
('RightsStatements.org', NULL, 'rightsstatements', 'rights', 'Standardized rights statements for cultural heritage objects.', 'https://rightsstatements.org/', 'RightsStatements.org Consortium', '1.0', 2016, '["archive","library","museum","gallery","dam"]', 0, 140),
('Creative Commons', 'CC', 'creative-commons', 'rights', 'Standardized licenses for sharing creative works.', 'https://creativecommons.org/licenses/', 'Creative Commons', '4.0', 2013, '["archive","library","museum","gallery","dam"]', 0, 150),
('Traditional Knowledge Labels', 'TK Labels', 'tk-labels', 'rights', 'Labels for indigenous cultural heritage rights.', 'https://localcontexts.org/labels/traditional-knowledge-labels/', 'Local Contexts', '2.0', 2022, '["archive","library","museum","gallery"]', 0, 155),

-- Museum/Gallery Standards
('Cataloguing Cultural Objects', 'CCO', 'cco', 'descriptive', 'Content standard for cultural heritage object description.', 'https://vra.org/cco/', 'Visual Resources Association (VRA)', '1.0', 2006, '["museum","gallery"]', 0, 160),
('Spectrum Collections Management Standard', 'Spectrum', 'spectrum', 'sector', 'Collections management procedures standard for museums.', 'https://collectionstrust.org.uk/spectrum/', 'Collections Trust (UK)', '5.1', 2017, '["museum","gallery"]', 1, 170),
('CIDOC Conceptual Reference Model', 'CIDOC-CRM', 'cidoc-crm', 'metadata', 'Ontology for cultural heritage information integration.', 'https://www.cidoc-crm.org/', 'ICOM/CIDOC', 'ISO 21127:2023', 2023, '["museum","gallery","archive"]', 0, 175),

-- DAM Standards
('IPTC Photo Metadata Standard', 'IPTC', 'iptc', 'metadata', 'Standard for photo and media metadata.', 'https://iptc.org/standards/photo-metadata/', 'International Press Telecommunications Council', '2024.1', 2024, '["dam","library"]', 0, 180),
('International Image Interoperability Framework', 'IIIF', 'iiif', 'interchange', 'APIs for interoperable image and AV delivery.', 'https://iiif.io/', 'IIIF Consortium', '3.0', 2020, '["archive","library","museum","gallery","dam"]', 1, 190),

-- Accounting Standards
('Generally Recognised Accounting Practice for Heritage Assets', 'GRAP 103', 'grap-103', 'accounting', 'South African standard for heritage asset accounting in public sector.', 'https://www.asb.co.za/', 'Accounting Standards Board (SA)', '2014', 2014, '["archive","library","museum","gallery"]', 0, 200),
('International Public Sector Accounting Standard — Heritage', 'IPSAS 45', 'ipsas-45', 'accounting', 'International standard for heritage asset accounting in public sector.', 'https://www.ipsasb.org/', 'International Public Sector Accounting Standards Board', '2023', 2023, '["archive","library","museum","gallery"]', 0, 210),

-- Compliance Standards
('Protection of Personal Information Act', 'POPIA', 'popia', 'compliance', 'South African data protection legislation.', 'https://popia.co.za/', 'Information Regulator (SA)', '2013', 2013, '["archive","library","museum","gallery","dam"]', 0, 220),
('General Data Protection Regulation', 'GDPR', 'gdpr', 'compliance', 'EU data protection regulation.', 'https://gdpr-info.eu/', 'European Union', '2016/679', 2016, '["archive","library","museum","gallery","dam"]', 0, 230),
('National Archives Act (Zimbabwe)', 'NAZ Act', 'naz-act', 'compliance', 'Zimbabwe National Archives Act [Chapter 25:06] — 25-year closure rule.', 'http://www.parlzim.gov.zw/', 'Parliament of Zimbabwe', 'Chapter 25:06', 1986, '["archive"]', 0, 240),
('Cyber and Data Protection Act (Zimbabwe)', 'CDPA', 'cdpa', 'compliance', 'Zimbabwe Cyber and Data Protection Act [Chapter 12:07].', 'https://www.potraz.gov.zw/', 'POTRAZ / Parliament of Zimbabwe', 'Chapter 12:07', 2021, '["archive","library","museum","gallery","dam"]', 0, 250)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ---------------------------------------------------
-- 6. Seed Data: Heratio Standard Extensions
-- ---------------------------------------------------
INSERT INTO `registry_standard_extension` (`standard_id`, `extension_type`, `title`, `description`, `rationale`, `plugin_name`, `db_tables`, `sort_order`) VALUES
-- ISAAR(CPF) extensions
((SELECT id FROM registry_standard WHERE slug = 'isaar-cpf'), 'addition', 'Structured Contact Records', 'Adds structured contact information (phone, email, address, role) to authority records via a dedicated contacts table, beyond ISAAR''s free-text address fields.', 'ISAAR(CPF) only provides free-text address area 5.2.1. Institutional users require structured, queryable contacts per authority record.', 'ahgContactPlugin', 'contact_information', 10),
((SELECT id FROM registry_standard WHERE slug = 'isaar-cpf'), 'addition', 'Actor Autocomplete & Browse', 'High-performance Laravel Query Builder browse with autocomplete search for authority records, replacing Symfony/Propel browse.', 'Base AtoM''s actor browse is limited. GLAM institutions with 50k+ authority records need fast, filterable browse.', 'ahgActorManagePlugin', NULL, 20),

-- ISAD(G) extensions
((SELECT id FROM registry_standard WHERE slug = 'isad-g'), 'addition', 'Security Classification', 'Adds security clearance levels (Unclassified through Top Secret) and embargo dates to archival descriptions, with ACL enforcement per user clearance level.', 'NARSSA and government archives require classification-based access control not covered by ISAD(G).', 'ahgSecurityClearancePlugin', 'security_clearance,security_clearance_i18n', 10),
((SELECT id FROM registry_standard WHERE slug = 'isad-g'), 'addition', 'Custom Metadata Fields (EAV)', 'Admin-configurable custom fields per entity type without code changes. Supports text, textarea, date, number, boolean, dropdown, url field types.', 'Institutions need institution-specific metadata fields beyond ISAD(G)''s fixed element set.', 'ahgCustomFieldsPlugin', 'custom_field_definition,custom_field_value', 20),
((SELECT id FROM registry_standard WHERE slug = 'isad-g'), 'addition', 'GLAM Sector Display Modes', 'Automatic detection and sector-specific display of archival descriptions (Archive, Library, Museum, Gallery, DAM) with faceted browse.', 'AtoM only serves archives. Heratio extends to all GLAM/DAM sectors with appropriate display conventions.', 'ahgDisplayPlugin', 'display_object_config,display_facet_cache', 30),

-- PREMIS extensions
((SELECT id FROM registry_standard WHERE slug = 'premis'), 'implementation_note', 'PREMIS Events & Fixity via CLI', 'PREMIS preservation events and fixity checking implemented as CLI commands with scheduling support. Integrates with PRONOM via Siegfried for format identification.', 'Full PREMIS implementation requires automated fixity verification and format identification at scale.', 'ahgPreservationPlugin', 'preservation_event,preservation_fixity', 10),

-- OAIS extensions
((SELECT id FROM registry_standard WHERE slug = 'oais'), 'implementation_note', 'OAIS Package Generation in Ingest', 'The 6-step ingest wizard generates SIP, AIP, and DIP packages per OAIS reference model, with JSON manifests and checksums.', 'OAIS compliance requires structured information packages during ingest.', 'ahgIngestPlugin', 'ingest_session,ingest_job', 10),

-- Spectrum extensions
((SELECT id FROM registry_standard WHERE slug = 'spectrum'), 'addition', 'Spectrum Procedures Integration', 'Maps Spectrum 5.1 procedures to Heratio workflows: Object Entry, Acquisition, Loans In/Out, Condition Assessment, Deaccession.', 'Museum clients require Spectrum procedure compliance for accreditation.', 'ahgSpectrumPlugin', NULL, 10),
((SELECT id FROM registry_standard WHERE slug = 'spectrum'), 'addition', 'Condition Assessment Module', 'Structured condition recording with photo evidence, damage types, conservation recommendations, and Spectrum 5.1 compliance fields.', 'Spectrum Condition Check procedure requires structured assessment records.', 'ahgConditionPlugin', 'condition_assessment,condition_photo', 20),

-- IIIF extensions
((SELECT id FROM registry_standard WHERE slug = 'iiif'), 'implementation_note', 'IIIF v2 & v3 Manifests with Annotations', 'Generates IIIF Presentation API v2 and v3 manifests for digital objects, with annotation support and Cantaloupe integration for Image API tiles.', 'GLAM institutions require interoperable image viewers and annotation capabilities.', 'ahgIiifPlugin', 'iiif_annotation', 10),

-- RiC extensions
((SELECT id FROM registry_standard WHERE slug = 'ric'), 'implementation_note', 'RiC-O Triplestore Sync', 'Syncs AtoM archival descriptions and authority records to an Apache Jena Fuseki triplestore as RiC-O linked data, with SPARQL query support.', 'RiC adoption requires linked data representation for archival entities.', 'ahgRicExplorerPlugin', NULL, 10),

-- Dublin Core
((SELECT id FROM registry_standard WHERE slug = 'dublin-core'), 'addition', 'GLAM Sector Metadata Enrichment', 'Extends Dublin Core records with sector-specific fields: Library (ISBN, call number), Museum (CCO fields), Gallery (exhibition history), DAM (IPTC, watermarks).', 'Dublin Core is intentionally minimal. Sector-specific use requires additional metadata.', 'ahgDisplayPlugin', NULL, 10),

-- POPIA
((SELECT id FROM registry_standard WHERE slug = 'popia'), 'implementation_note', 'Multi-Jurisdiction Privacy Compliance', 'Implements POPIA (SA), GDPR (EU), CCPA (US), PIPEDA (Canada), NDPA (Nigeria), DPA (Kenya), UK GDPR — with PII scanning and consent management.', 'International GLAM institutions operate across jurisdictions requiring parallel compliance.', 'ahgPrivacyPlugin', NULL, 10),

-- GRAP 103
((SELECT id FROM registry_standard WHERE slug = 'grap-103'), 'implementation_note', 'Heritage Asset Accounting with IPSAS Alignment', 'Implements GRAP 103 heritage asset valuation and reporting, aligned with IPSAS 45 for international applicability.', 'South African public sector archives require GRAP 103 compliance for heritage asset accounting.', 'ahgHeritageAccountingPlugin', NULL, 10)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- ---------------------------------------------------
-- 7. Nav settings for Standards
-- ---------------------------------------------------
INSERT INTO `registry_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('nav_show_standards', '1', 'boolean', 'Show Standards link in the navigation bar')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
