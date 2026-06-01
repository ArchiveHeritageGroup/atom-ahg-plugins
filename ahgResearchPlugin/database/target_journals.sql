-- =============================================================================
-- ahgResearchPlugin :: Target-journal directory (#114 / Heratio #1107)
--
-- Journals to publish TO, each with its subject scope and submission rules.
-- The directory CORE is jurisdiction-neutral; the DHET accredited list is the
-- South-African accreditation MODULE (accreditation_market = 'ZA'). Other
-- markets seed from DOAJ / Scopus / Web of Science / ERIH-PLUS.
--
-- Mirrors the Heratio ResearchTargetJournalService data model.
-- NEVER use ENUM (VARCHAR + COMMENT instead). InnoDB / utf8mb4.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `research_target_journal` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `issn` VARCHAR(20) DEFAULT NULL,
  `eissn` VARCHAR(20) DEFAULT NULL,
  `publisher` VARCHAR(255) DEFAULT NULL,
  `homepage_url` VARCHAR(1000) DEFAULT NULL,
  `submission_url` VARCHAR(1000) DEFAULT NULL,
  `languages` VARCHAR(120) DEFAULT NULL,
  `subject_scope` TEXT DEFAULT NULL COMMENT 'what the journal mainly accepts',
  `article_types` VARCHAR(255) DEFAULT NULL,
  `accreditation` VARCHAR(255) DEFAULT NULL COMMENT 'DHET, IBSS, Scopus, WoS, DOAJ, Sabinet, ERIH-PLUS, ...',
  `accreditation_market` VARCHAR(8) DEFAULT NULL COMMENT 'per-market module tag, e.g. ZA for DHET',
  `reference_style` VARCHAR(40) DEFAULT NULL COMMENT 'APA, Harvard, Vancouver, Chicago, MLA, IEEE',
  `structure_notes` TEXT DEFAULT NULL,
  `max_words` INT DEFAULT NULL,
  `abstract_max_words` INT DEFAULT NULL,
  `peer_review` VARCHAR(40) DEFAULT NULL COMMENT 'double-blind, single-blind, open, none',
  `open_access` TINYINT(1) NOT NULL DEFAULT 0,
  `apc_amount` VARCHAR(60) DEFAULT NULL COMMENT 'article processing charge note',
  `turnaround` VARCHAR(120) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, discontinued',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_issn` (`issn`),
  KEY `idx_title` (`title`),
  KEY `idx_market` (`accreditation_market`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
