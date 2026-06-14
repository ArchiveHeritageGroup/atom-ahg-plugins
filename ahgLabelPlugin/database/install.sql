-- ahgLabelPlugin - label template definitions for batch printing.
-- Standalone table; no ENUMs, no FK to core AtoM tables.

CREATE TABLE IF NOT EXISTS `label_template` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(255) NOT NULL,
    `page_size`        VARCHAR(10) NOT NULL DEFAULT 'A4' COMMENT 'A4, Letter',
    `columns`          TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `rows`             TINYINT UNSIGNED NOT NULL DEFAULT 8,
    `label_width_mm`   DECIMAL(6,2) NOT NULL DEFAULT 63.50,
    `label_height_mm`  DECIMAL(6,2) NOT NULL DEFAULT 33.90,
    `margin_mm`        DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    `gutter_mm`        DECIMAL(5,2) NOT NULL DEFAULT 2.50,
    `font_size_pt`     TINYINT UNSIGNED NOT NULL DEFAULT 9,
    `show_title`       TINYINT(1) NOT NULL DEFAULT 1,
    `show_identifier`  TINYINT(1) NOT NULL DEFAULT 1,
    `show_repository`  TINYINT(1) NOT NULL DEFAULT 0,
    `show_barcode`     TINYINT(1) NOT NULL DEFAULT 1,
    `barcode_source`   VARCHAR(20) NOT NULL DEFAULT 'identifier' COMMENT 'identifier, accession, call_number, isbn',
    `show_qr`          TINYINT(1) NOT NULL DEFAULT 0,
    `qr_target`        VARCHAR(20) NOT NULL DEFAULT 'url' COMMENT 'url, identifier',
    `is_default`       TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
