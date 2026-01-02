-- ============================================================
-- ahgLibraryPlugin - Database Schema
-- Generated from actual database structure
-- DO NOT include INSERT INTO atom_plugin
-- ============================================================

-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: archive
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `library_item`
--

DROP TABLE IF EXISTS `library_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `library_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `information_object_id` int unsigned NOT NULL,
  `material_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monograph' COMMENT 'monograph, serial, volume, issue, chapter, article, manuscript, map, pamphlet',
  `subtitle` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsibility_statement` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `call_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `classification_scheme` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'dewey, lcc, udc, bliss, colon, custom',
  `classification_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dewey_decimal` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cutter_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shelf_location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `copy_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `volume_designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issn` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lccn` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oclc_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openlibrary_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `goodreads_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `librarything_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openlibrary_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ebook_preview_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_url_original` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edition` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edition_statement` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_place` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_date` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `copyright_date` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `printing` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagination` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dimensions` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `physical_details` text COLLATE utf8mb4_unicode_ci,
  `language` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accompanying_material` text COLLATE utf8mb4_unicode_ci,
  `series_title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `series_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `series_issn` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subseries_title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `general_note` text COLLATE utf8mb4_unicode_ci,
  `bibliography_note` text COLLATE utf8mb4_unicode_ci,
  `contents_note` text COLLATE utf8mb4_unicode_ci,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `target_audience` text COLLATE utf8mb4_unicode_ci,
  `system_requirements` text COLLATE utf8mb4_unicode_ci,
  `binding_note` text COLLATE utf8mb4_unicode_ci,
  `frequency` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `former_frequency` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numbering_peculiarities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_start_date` date DEFAULT NULL,
  `publication_end_date` date DEFAULT NULL,
  `publication_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'current, ceased, suspended',
  `total_copies` smallint unsigned NOT NULL DEFAULT '1',
  `available_copies` smallint unsigned NOT NULL DEFAULT '1',
  `circulation_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available' COMMENT 'available, on_loan, processing, lost, withdrawn, reference',
  `cataloging_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cataloging_rules` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'aacr2, rda, isbd',
  `encoding_level` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_item_creator`
--

DROP TABLE IF EXISTS `library_item_creator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `library_item_creator` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `library_item_id` bigint unsigned NOT NULL,
  `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'author',
  `sort_order` int DEFAULT '0',
  `authority_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_library_item_id` (`library_item_id`),
  KEY `idx_name` (`name`(100)),
  CONSTRAINT `library_item_creator_ibfk_1` FOREIGN KEY (`library_item_id`) REFERENCES `library_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_item_subject`
--

DROP TABLE IF EXISTS `library_item_subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `library_item_subject` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `library_item_id` bigint unsigned NOT NULL,
  `heading` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'topic',
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_library_item_id` (`library_item_id`),
  KEY `idx_heading` (`heading`(100)),
  CONSTRAINT `library_item_subject_ibfk_1` FOREIGN KEY (`library_item_id`) REFERENCES `library_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=359 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_settings`
--

DROP TABLE IF EXISTS `library_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `library_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 17:01:32


-- ============================================================
-- Library Level of Description Terms
-- Creates terms if they don't exist (AtoM object inheritance)
-- ============================================================

-- Book
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @book_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@book_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@book_id, 'en', 'Book');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@book_id, 'book');

-- Monograph
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @mono_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@mono_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@mono_id, 'en', 'Monograph');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@mono_id, 'monograph');

-- Periodical
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @period_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@period_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@period_id, 'en', 'Periodical');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@period_id, 'periodical');

-- Journal
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @journal_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@journal_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@journal_id, 'en', 'Journal');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@journal_id, 'journal');

-- Article
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @article_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@article_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@article_id, 'en', 'Article');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@article_id, 'article');

-- Manuscript
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @manuscript_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@manuscript_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@manuscript_id, 'en', 'Manuscript');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@manuscript_id, 'manuscript');

-- Level of description sector mappings
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at) VALUES
(@book_id, 'library', 10, NOW()),
(@mono_id, 'library', 20, NOW()),
(@period_id, 'library', 30, NOW()),
(@journal_id, 'library', 40, NOW()),
(@article_id, 'library', 45, NOW()),
(@manuscript_id, 'library', 50, NOW());

-- Add existing Document term if it exists
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 60, NOW() FROM term t
JOIN term_i18n ti ON t.id = ti.id
WHERE ti.name = 'Document' AND t.taxonomy_id = 34 AND ti.culture = 'en'
LIMIT 1;


-- ISBN Providers
CREATE TABLE IF NOT EXISTS `atom_isbn_provider` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `api_endpoint` varchar(500) NOT NULL,
  `api_key_setting` varchar(100) DEFAULT NULL,
  `priority` int DEFAULT 10,
  `enabled` tinyint(1) DEFAULT 1,
  `rate_limit_per_minute` int DEFAULT 100,
  `response_format` varchar(20) DEFAULT 'json',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO atom_isbn_provider (name, slug, api_endpoint, api_key_setting, priority, enabled, rate_limit_per_minute, response_format) VALUES
('Open Library', 'openlibrary', 'https://openlibrary.org/api/books', NULL, 10, 1, 100, 'json'),
('Google Books', 'googlebooks', 'https://www.googleapis.com/books/v1/volumes', NULL, 20, 1, 100, 'json'),
('WorldCat', 'worldcat', 'https://www.worldcat.org/webservices/catalog/content/isbn/', NULL, 30, 0, 10, 'marcxml');

-- =====================================================
-- Library Display Standard Term (taxonomy_id = 70)
-- =====================================================
INSERT INTO term (taxonomy_id, code, source_culture)
SELECT 70, 'library', 'en' FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM term WHERE code = 'library' AND taxonomy_id = 70);

SET @library_id = (SELECT id FROM term WHERE code = 'library' AND taxonomy_id = 70);

INSERT IGNORE INTO term_i18n (id, culture, name)
VALUES (@library_id, 'en', 'Library (MARC-inspired)');
