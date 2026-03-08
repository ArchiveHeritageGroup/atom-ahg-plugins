-- ============================================================================
-- IIIF AI Extraction Migration
-- Date: 2026-03-08
-- Issue: #220 — AI-Powered IIIF Content Extraction
--
-- Creates ai_iiif_extraction table for tracking extraction pipeline results
-- and seeds iiif_ai settings into ahg_ai_settings.
-- ============================================================================

-- Extraction tracking table
CREATE TABLE IF NOT EXISTS `ai_iiif_extraction` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `information_object_id` INT NOT NULL COMMENT 'FK to information_object.id',
    `iiif_canvas_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'FK to iiif_canvas.id (optional)',
    `extraction_type` VARCHAR(50) NOT NULL COMMENT 'ocr, ner, translate, summarize, face',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, completed, failed',
    `input_source` VARCHAR(500) DEFAULT NULL COMMENT 'Cantaloupe IIIF URL or local path',
    `output_text` LONGTEXT DEFAULT NULL COMMENT 'Extracted/processed text',
    `output_json` JSON DEFAULT NULL COMMENT 'Structured extraction results',
    `error_message` TEXT DEFAULT NULL,
    `processing_time_ms` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_aie_io` (`information_object_id`),
    KEY `idx_aie_canvas` (`iiif_canvas_id`),
    KEY `idx_aie_type` (`extraction_type`),
    KEY `idx_aie_status` (`status`),
    KEY `idx_aie_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- Seed IIIF AI settings into ahg_ai_settings
-- ============================================================================

INSERT IGNORE INTO `ahg_ai_settings` (`feature`, `setting_key`, `setting_value`) VALUES
    ('iiif_ai', 'enabled', '1'),
    ('iiif_ai', 'auto_extract_on_manifest', '0'),
    ('iiif_ai', 'extract_types', '["ocr","ner"]'),
    ('iiif_ai', 'annotation_motivation', 'supplementing'),
    ('iiif_ai', 'max_canvas_batch', '50'),
    ('iiif_ai', 'ocr_language', 'eng'),
    ('iiif_ai', 'ocr_confidence_threshold', '0.60'),
    ('iiif_ai', 'api_timeout', '120');
