-- ============================================================================
-- Serials bindery workflow (#105) — 2026-05-29
-- ============================================================================
-- A bindery batch groups received serial issues sent out for binding, tracked
-- from send to return. library_serial_issue gains bindery_batch_id (the
-- existing bound_volume_id still records the resulting bound volume).
-- Idempotent: CREATE TABLE IF NOT EXISTS + guarded ALTER.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `library_bindery_batch` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_number`  VARCHAR(40) NOT NULL,
  `vendor_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT 'bindery vendor (ahg_vendors.id)',
  `status`        VARCHAR(20) NOT NULL DEFAULT 'sent' COMMENT 'sent, returned, cancelled',
  `sent_date`     DATE DEFAULT NULL,
  `returned_date` DATE DEFAULT NULL,
  `item_count`    INT UNSIGNED NOT NULL DEFAULT 0,
  `notes`         TEXT DEFAULT NULL,
  `created_by`    INT DEFAULT NULL,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bindery_batch_number` (`batch_number`),
  KEY `idx_bindery_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_serial_issue' AND COLUMN_NAME = 'bindery_batch_id');
SET @sql = IF(@col = 0, 'ALTER TABLE library_serial_issue ADD COLUMN bindery_batch_id BIGINT UNSIGNED NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_serial_issue' AND INDEX_NAME = 'idx_serial_bindery_batch');
SET @sql = IF(@idx = 0, 'CREATE INDEX idx_serial_bindery_batch ON library_serial_issue (bindery_batch_id)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
