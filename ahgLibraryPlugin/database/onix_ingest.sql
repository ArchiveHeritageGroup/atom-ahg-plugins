-- ahgLibraryPlugin — ONIX ingestion (clone of Heratio library_onix_ingest).
-- Parse + validate publisher ONIX feeds into a review queue before commit.

CREATE TABLE IF NOT EXISTS `library_onix_ingest` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename`       VARCHAR(255) NULL,
    `source`         VARCHAR(20) NOT NULL DEFAULT 'file' COMMENT 'file, api, paste',
    `onix_version`   VARCHAR(8) NULL COMMENT '3.0, 2.1',
    `status`         VARCHAR(20) NOT NULL DEFAULT 'parsed' COMMENT 'parsed, committed, failed',
    `record_count`   INT UNSIGNED NOT NULL DEFAULT 0,
    `valid_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `error_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `imported_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `order_id`       BIGINT UNSIGNED NULL,
    `notes`          TEXT NULL,
    `created_by`     INT UNSIGNED NULL,
    `created_at`     DATETIME NULL,
    `updated_at`     DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_onix_status` (`status`),
    KEY `idx_onix_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_onix_ingest_line` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ingest_id`       BIGINT UNSIGNED NOT NULL,
    `product_ref`     VARCHAR(255) NULL COMMENT 'ONIX RecordReference',
    `isbn`            VARCHAR(20) NULL,
    `issn`            VARCHAR(20) NULL,
    `title`           VARCHAR(500) NULL,
    `subtitle`        VARCHAR(500) NULL,
    `author`          VARCHAR(500) NULL,
    `publisher`       VARCHAR(255) NULL,
    `pub_year`        VARCHAR(8) NULL,
    `edition`         VARCHAR(100) NULL,
    `material_type`   VARCHAR(50) NULL,
    `price`           DECIMAL(12,2) NULL,
    `currency`        VARCHAR(8) NULL,
    `supplier`        VARCHAR(255) NULL,
    `status`          VARCHAR(20) NOT NULL DEFAULT 'parsed' COMMENT 'parsed, valid, invalid, duplicate, imported, skipped',
    `error`           VARCHAR(1000) NULL,
    `library_item_id` BIGINT UNSIGNED NULL,
    `order_line_id`   BIGINT UNSIGNED NULL,
    `raw`             LONGTEXT NULL,
    `created_at`      DATETIME NULL,
    `updated_at`      DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_onixline_ingest` (`ingest_id`),
    KEY `idx_onixline_status` (`status`),
    KEY `idx_onixline_isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
