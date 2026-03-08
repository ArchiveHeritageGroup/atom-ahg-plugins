-- =====================================================
-- Registry ERD Documentation Migration
-- Date: 2026-03-07
-- =====================================================

CREATE TABLE IF NOT EXISTS `registry_erd` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_name` varchar(255) NOT NULL COMMENT 'e.g. ahgPreservationPlugin',
  `vendor_id` bigint unsigned DEFAULT NULL COMMENT 'FK to registry_vendor.id',
  `display_name` varchar(255) NOT NULL COMMENT 'e.g. Digital Preservation',
  `slug` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'general' COMMENT 'core, sector, compliance, browse, ai, ingest, rights, research, collection, exhibition, integration, reporting',
  `description` text COMMENT 'Short description of this ERD group',
  `tables_json` json DEFAULT NULL COMMENT 'Array of table names to auto-render schema from information_schema',
  `diagram` longtext COMMENT 'ASCII ERD diagram (rendered in <pre> block)',
  `diagram_image` varchar(500) DEFAULT NULL COMMENT 'Uploaded ERD diagram image/document path',
  `notes` text COMMENT 'Additional notes or markdown content',
  `icon` varchar(100) DEFAULT 'fas fa-database' COMMENT 'Font Awesome icon class',
  `color` varchar(50) DEFAULT 'primary' COMMENT 'Bootstrap color class',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 100,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_erd_slug` (`slug`),
  UNIQUE KEY `uq_erd_plugin` (`plugin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Seed: Standards (already built)
-- =====================================================
INSERT INTO `registry_erd` (`plugin_name`, `display_name`, `slug`, `category`, `description`, `tables_json`, `icon`, `color`, `sort_order`) VALUES
('ahgRegistryPlugin', 'Standards & Conformance', 'standards-conformance', 'core',
 'Standards directory, Heratio extensions, vendor conformance declarations, and setup guides.',
 '["registry_standard","registry_standard_extension","registry_software_standard","registry_setup_guide"]',
 'fas fa-balance-scale', 'danger', 10),

('ahgAuditTrailPlugin', 'Audit Trail', 'audit-trail', 'compliance',
 'Audit logging for all entity changes with field-level detail tracking.',
 '["audit_log","audit_log_detail"]',
 'fas fa-history', 'secondary', 20),

('ahgSecurityClearancePlugin', 'Security Classification', 'security-classification', 'compliance',
 'NARSSA-aligned security classification, user clearance levels, and access control.',
 '["security_classification","security_classification_record","security_user_clearance"]',
 'fas fa-shield-alt', 'warning', 30),

('ahgPrivacyPlugin', 'Privacy & Compliance', 'privacy-compliance', 'compliance',
 'POPIA/GDPR/CCPA compliance: breach management, consent tracking, SAR requests, data retention.',
 '["privacy_breach","privacy_breach_record","privacy_consent","privacy_sar_request","privacy_data_retention"]',
 'fas fa-user-shield', 'info', 40),

('ahgPreservationPlugin', 'Digital Preservation', 'digital-preservation', 'core',
 'PREMIS events, checksums, fixity verification, format registry, PRONOM sync, replication.',
 '["preservation_event","preservation_checksum","preservation_format","preservation_replication","preservation_package"]',
 'fas fa-archive', 'success', 50),

('ahgConditionPlugin', 'Condition Assessment', 'condition-assessment', 'collection',
 'Spectrum 5.1-aligned condition assessment with treatment proposals and photo documentation.',
 '["condition_assessment","condition_assessment_detail","condition_treatment_proposal","condition_photo"]',
 'fas fa-clipboard-check', 'primary', 60),

('ahgLoanPlugin', 'Loan Management', 'loan-management', 'collection',
 'Incoming/outgoing loan tracking with item-level condition checks and insurance.',
 '["loan","loan_item","loan_condition"]',
 'fas fa-exchange-alt', 'info', 70),

('ahgHeritageAccountingPlugin', 'Heritage Accounting', 'heritage-accounting', 'compliance',
 'GRAP 103 / IPSAS 45 heritage asset accounting with valuation and movement tracking.',
 '["heritage_asset","heritage_valuation","heritage_movement"]',
 'fas fa-calculator', 'success', 80),

('ahgIiifPlugin', 'IIIF Integration', 'iiif-integration', 'core',
 'IIIF manifests, canvases, annotations, annotation bodies, and OCR text storage.',
 '["iiif_manifest","iiif_canvas","iiif_annotation","iiif_annotation_body","iiif_ocr_text"]',
 'fas fa-images', 'primary', 90),

('ahgResearchPlugin', 'Research Portal', 'research-portal', 'research',
 'Researcher registration, reading room booking, access requests, and usage logging.',
 '["research_request","research_request_item","research_booking","research_access_log"]',
 'fas fa-microscope', 'warning', 100),

('ahgDoiPlugin', 'DOI Integration', 'doi-integration', 'integration',
 'DataCite DOI minting, queue processing, verification, and sync.',
 '["doi_record","doi_queue"]',
 'fas fa-fingerprint', 'dark', 110),

('ahgExtendedRightsPlugin', 'Extended Rights', 'extended-rights', 'rights',
 'RightsStatements.org, embargo management, TK Labels, and batch rights operations.',
 '["extended_rights","embargo_record"]',
 'fas fa-gavel', 'danger', 120),

('ahgProvenancePlugin', 'Provenance Tracking', 'provenance-tracking', 'collection',
 'Chain of custody and provenance event tracking for archival records.',
 '["provenance_event"]',
 'fas fa-route', 'secondary', 130),

('ahgDonorAgreementPlugin', 'Donor Agreements', 'donor-agreements', 'collection',
 'Donor/institution agreement management with SA regulatory compliance.',
 '["donor_agreement"]',
 'fas fa-file-contract', 'primary', 140),

('ahgExhibitionPlugin', 'Exhibition Management', 'exhibition-management', 'exhibition',
 'Exhibition planning, object loans, venue management for GLAM/DAM institutions.',
 '["exhibition","exhibition_item","exhibition_venue"]',
 'fas fa-palette', 'info', 150),

('ahgCustomFieldsPlugin', 'Custom Fields (EAV)', 'custom-fields', 'core',
 'Admin-configurable custom metadata fields using Entity-Attribute-Value pattern.',
 '["custom_field_definition","custom_field_value"]',
 'fas fa-th-list', 'success', 160),

('ahgAIPlugin', 'AI & NER', 'ai-ner', 'ai',
 'Named Entity Recognition, translation, summarization, spellcheck, face detection, LLM suggestions.',
 '["ai_entity","ai_entity_link","ai_translation","ai_suggestion"]',
 'fas fa-brain', 'purple', 170),

('ahgIngestPlugin', 'Data Ingest', 'data-ingest', 'ingest',
 'OAIS-aligned 6-step batch ingestion pipeline with AI processing.',
 '["ingest_session","ingest_file","ingest_mapping","ingest_row","ingest_validation","ingest_job"]',
 'fas fa-file-import', 'warning', 180),

('ahgFeedbackPlugin', 'User Feedback', 'user-feedback', 'research',
 'User feedback and suggestions management.',
 '["feedback"]',
 'fas fa-comment-dots', 'info', 190),

('ahgWorkflowPlugin', 'Workflow Engine', 'workflow-engine', 'reporting',
 'Configurable approval workflow with steps, assignments, and history.',
 '["workflow_definition","workflow_step","workflow_instance","workflow_history"]',
 'fas fa-project-diagram', 'primary', 200),

('ahgReportBuilderPlugin', 'Report Builder', 'report-builder', 'reporting',
 'Enterprise report builder with templates, sections, SQL queries, scheduling.',
 '["report_template","report_section","report_schedule","report_output"]',
 'fas fa-chart-bar', 'success', 210),

('ahgLibraryPlugin', 'Library Cataloging', 'library-cataloging', 'sector',
 'Library cataloging with MARC-inspired fields, ISBN lookup, and cover images.',
 '["library_item"]',
 'fas fa-book', 'primary', 220),

('ahgMuseumPlugin', 'Museum Cataloging', 'museum-cataloging', 'sector',
 'Museum cataloging with CCO, CIDOC-CRM, Spectrum 5.1, Getty vocabulary linking.',
 '["museum_object","museum_exhibition"]',
 'fas fa-landmark', 'warning', 230),

('ahgGalleryPlugin', 'Gallery Management', 'gallery-management', 'sector',
 'Gallery/exhibition management, artist tracking, loans, and provenance.',
 '["gallery_artwork","gallery_exhibition","gallery_artist"]',
 'fas fa-paint-brush', 'danger', 240),

('ahgDAMPlugin', 'Digital Asset Management', 'dam', 'sector',
 'Digital Asset Management with IPTC metadata, watermarks, and asset workflows.',
 '["dam_asset","dam_collection","dam_watermark"]',
 'fas fa-photo-video', 'info', 250),

('ahgContactPlugin', 'Extended Contacts', 'extended-contacts', 'collection',
 'Extended contact information for actors (phone, email, address, social media).',
 '["contact_information"]',
 'fas fa-address-book', 'secondary', 260),

('ahgICIPPlugin', 'Indigenous Cultural IP', 'icip', 'rights',
 'Indigenous Cultural & Intellectual Property management and TK Labels.',
 '["icip_record","icip_community"]',
 'fas fa-feather-alt', 'success', 270),

('ahgCDPAPlugin', 'CDPA (Zimbabwe)', 'cdpa-zimbabwe', 'compliance',
 'Cyber & Data Protection Act [Chapter 12:07] — POTRAZ compliance.',
 '["cdpa_license","cdpa_request"]',
 'fas fa-flag', 'danger', 280),

('ahgNAZPlugin', 'NAZ (Zimbabwe)', 'naz-zimbabwe', 'compliance',
 'National Archives Act [Chapter 25:06] — 25-year rule, closure, permits.',
 '["naz_closure","naz_transfer","naz_permit"]',
 'fas fa-landmark', 'warning', 290),

('ahgRicExplorerPlugin', 'RiC / Fuseki', 'ric-fuseki', 'integration',
 'Records in Context (RiC-O) triplestore integration with Apache Jena Fuseki.',
 '["ric_sync_config","ric_sync_log"]',
 'fas fa-project-diagram', 'dark', 300),

('ahgSettingsPlugin', 'AHG Settings', 'ahg-settings', 'core',
 'Centralized AHG plugin settings management (section-based admin UI).',
 '["ahg_settings"]',
 'fas fa-cog', 'secondary', 310)

ON DUPLICATE KEY UPDATE display_name=VALUES(display_name);
