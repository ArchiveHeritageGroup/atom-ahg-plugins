-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgTiffPdfMergePlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------

-- Table: tiff_pdf_merge_job
CREATE TABLE IF NOT EXISTS `tiff_pdf_merge_job` (
  `id` int NOT NULL AUTO_INCREMENT,
  `information_object_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `job_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` VARCHAR(58) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, queued, processing, completed, failed',
  `total_files` int DEFAULT '0',
  `processed_files` int DEFAULT '0',
  `output_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output_path` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output_digital_object_id` int DEFAULT NULL,
  `pdf_standard` VARCHAR(42) COLLATE utf8mb4_unicode_ci DEFAULT 'pdfa-2b' COMMENT 'pdf, pdfa-1b, pdfa-2b, pdfa-3b',
  `compression_quality` int DEFAULT '85',
  `page_size` VARCHAR(39) COLLATE utf8mb4_unicode_ci DEFAULT 'auto' COMMENT 'auto, a4, letter, legal, a3',
  `orientation` VARCHAR(37) COLLATE utf8mb4_unicode_ci DEFAULT 'auto' COMMENT 'auto, portrait, landscape',
  `dpi` int DEFAULT '300',
  `preserve_originals` tinyint(1) DEFAULT '1',
  `attach_to_record` tinyint(1) DEFAULT '1',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `options` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tpm_job_status` (`status`),
  KEY `idx_tpm_job_user` (`user_id`),
  KEY `idx_tpm_job_info_object` (`information_object_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tiff_pdf_merge_file
CREATE TABLE IF NOT EXISTS `tiff_pdf_merge_file` (
  `id` int NOT NULL AUTO_INCREMENT,
  `merge_job_id` int NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint DEFAULT '0',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'image/tiff',
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `bit_depth` int DEFAULT NULL,
  `color_space` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page_order` int DEFAULT '0',
  `status` VARCHAR(51) COLLATE utf8mb4_unicode_ci DEFAULT 'uploaded' COMMENT 'uploaded, processing, processed, failed',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `checksum_md5` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tpm_file_job` (`merge_job_id`),
  KEY `idx_tpm_file_order` (`merge_job_id`,`page_order`),
  CONSTRAINT `tiff_pdf_merge_file_ibfk_1` FOREIGN KEY (`merge_job_id`) REFERENCES `tiff_pdf_merge_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tiff_pdf_settings
CREATE TABLE IF NOT EXISTS `tiff_pdf_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` VARCHAR(42) COLLATE utf8mb4_unicode_ci DEFAULT 'string' COMMENT 'string, integer, boolean, json',
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


